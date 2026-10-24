#!/usr/bin/env python3
"""
Storico di Search Console (ultimi 16 mesi) in BigQuery, nello stesso progetto e nella stessa località dell'esportazione
collettiva: l'esportazione nativa parte dal giorno dell'attivazione e non recupera il passato.

Si lancia sul PC (l'ambiente di lavoro cloud non raggiunge le API Google), con il client OAuth già esistente.

Installazione (una volta):
    py -m pip install google-api-python-client google-auth-oauthlib google-cloud-bigquery

Uso (PowerShell, dalla radice del repository):
    py scripts/gsc/storico-search-console.py --client-secret C:\\percorso\\client_secret.json --elenco
    py scripts/gsc/storico-search-console.py --client-secret C:\\percorso\\client_secret.json \\
        --proprieta sc-domain:anniazzurri.it --dataset searchconsole_aa --fino-al 2026-10-24

Opzioni:
    --progetto     progetto BigQuery (default kos-analytics-482819)
    --localita     località del dataset (default europe-west3, come GA4 e Google Ads)
    --dal / --fino-al   intervallo (default: da 16 mesi fa a ieri); --fino-al = giorno prima dell'avvio dell'esportazione
                   collettiva, così i due periodi non si sovrappongono
    --prova        scarica un solo giorno e mostra il conteggio, senza scrivere in BigQuery

Tabelle create nel dataset (partizionate per data_date), con colonne e convenzioni dell'esportazione collettiva così che
le due fonti si possano unire con una vista:
    storico_site_impression   per proprietà: query, paese, dispositivo (le query anonimizzate non sono incluse dall'API)
    storico_site_totali       per proprietà: paese e dispositivo, senza query (totali reali, comprese le anonimizzate)
    storico_url_impression    per pagina: pagina, query, paese, dispositivo
sum_top_position / sum_position = (posizione media - 1) × impressioni, come nell'esportazione collettiva (posizione da 0).

Riprende da dove si era fermato: i mesi già presenti in tutte e tre le tabelle vengono saltati. Il token OAuth si salva
in %APPDATA%\\kos-seo-geo\\token-gsc.json (fuori dal repository).
"""
import argparse
import datetime as dt
import json
import os
import sys
import time

SCOPES = ['https://www.googleapis.com/auth/webmasters.readonly', 'https://www.googleapis.com/auth/bigquery']
TABELLE = {
    'storico_site_impression': ('byProperty', ['query', 'country', 'device']),
    'storico_site_totali': ('byProperty', ['country', 'device']),
    'storico_url_impression': ('byPage', ['page', 'query', 'country', 'device']),
}


def credenziali(client_secret):
    from google.oauth2.credentials import Credentials
    from google.auth.transport.requests import Request
    from google_auth_oauthlib.flow import InstalledAppFlow
    cartella = os.path.join(os.environ.get('APPDATA') or os.path.expanduser('~'), 'kos-seo-geo')
    os.makedirs(cartella, exist_ok=True)
    tok = os.path.join(cartella, 'token-gsc.json')
    cred = Credentials.from_authorized_user_file(tok, SCOPES) if os.path.exists(tok) else None
    if cred and cred.expired and cred.refresh_token:
        cred.refresh(Request())
    if not cred or not cred.valid:
        cred = InstalledAppFlow.from_client_secrets_file(client_secret, SCOPES).run_local_server(port=0)
    with open(tok, 'w', encoding='utf-8') as f:
        f.write(cred.to_json())
    return cred


def schema(nome):
    from google.cloud import bigquery as bq
    c = [bq.SchemaField('data_date', 'DATE'), bq.SchemaField('site_url', 'STRING')]
    if nome == 'storico_url_impression':
        c.append(bq.SchemaField('url', 'STRING'))
    if nome != 'storico_site_totali':
        c.append(bq.SchemaField('query', 'STRING'))
    c += [bq.SchemaField('country', 'STRING'), bq.SchemaField('search_type', 'STRING'), bq.SchemaField('device', 'STRING'),
          bq.SchemaField('impressions', 'INTEGER'), bq.SchemaField('clicks', 'INTEGER'),
          bq.SchemaField('sum_position' if nome == 'storico_url_impression' else 'sum_top_position', 'FLOAT')]
    return c


def scarica(gsc, proprieta, giorno, aggregazione, dimensioni):
    righe, start = [], 0
    while True:
        corpo = {'startDate': giorno, 'endDate': giorno, 'dimensions': dimensioni, 'type': 'web',
                 'aggregationType': aggregazione, 'dataState': 'final', 'rowLimit': 25000, 'startRow': start}
        for tentativo in range(5):
            try:
                r = gsc.searchanalytics().query(siteUrl=proprieta, body=corpo).execute()
                break
            except Exception as e:                      # quota o errore temporaneo: attesa crescente
                if tentativo == 4:
                    raise
                print(f'    riprovo ({e.__class__.__name__})', flush=True)
                time.sleep(5 * (tentativo + 1))
        blocco = r.get('rows', [])
        righe += blocco
        if len(blocco) < 25000:
            return righe
        start += 25000


def converti(nome, proprieta, giorno, dimensioni, righe):
    out = []
    for r in righe:
        k = dict(zip(dimensioni, r['keys']))
        pos = (float(r.get('position', 1)) - 1) * int(r.get('impressions', 0))
        riga = {'data_date': giorno, 'site_url': proprieta, 'country': k.get('country'), 'search_type': 'WEB',
                'device': k.get('device'), 'impressions': int(r.get('impressions', 0)), 'clicks': int(r.get('clicks', 0))}
        if 'page' in k:
            riga['url'] = k['page']
        if 'query' in k:
            riga['query'] = k['query']
        riga['sum_position' if nome == 'storico_url_impression' else 'sum_top_position'] = pos
        out.append(riga)
    return out


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--client-secret', required=True)
    a.add_argument('--proprieta')
    a.add_argument('--dataset')
    a.add_argument('--progetto', default='kos-analytics-482819')
    a.add_argument('--localita', default='europe-west3')
    a.add_argument('--dal')
    a.add_argument('--fino-al')
    a.add_argument('--elenco', action='store_true')
    a.add_argument('--prova', action='store_true')
    o = a.parse_args()

    from googleapiclient.discovery import build
    cred = credenziali(o.client_secret)
    gsc = build('searchconsole', 'v1', credentials=cred, cache_discovery=False)
    if o.elenco:
        for s in gsc.sites().list().execute().get('siteEntry', []):
            print(f"{s['siteUrl']:45} {s['permissionLevel']}")
        return
    if not o.proprieta or not o.dataset:
        sys.exit('Servono --proprieta e --dataset (oppure --elenco)')

    oggi = dt.date.today()
    fine = dt.date.fromisoformat(o.fino_al) if o.fino_al else oggi - dt.timedelta(days=1)
    inizio = dt.date.fromisoformat(o.dal) if o.dal else (oggi.replace(day=1) - dt.timedelta(days=16 * 31)).replace(day=1)
    giorni = [inizio + dt.timedelta(days=i) for i in range((fine - inizio).days + 1)]

    if o.prova:
        g = fine.isoformat()
        for nome, (agg, dims) in TABELLE.items():
            righe = scarica(gsc, o.proprieta, g, agg, dims)
            print(f'{g} {nome}: {len(righe)} righe, {sum(r.get("clicks", 0) for r in righe)} clic, '
                  f'{sum(r.get("impressions", 0) for r in righe)} impressioni')
        return

    from google.cloud import bigquery as bq
    client = bq.Client(project=o.progetto, credentials=cred)
    ds = bq.Dataset(f'{o.progetto}.{o.dataset}')
    ds.location = o.localita
    ds = client.create_dataset(ds, exists_ok=True)
    if ds.location.lower() != o.localita.lower():
        sys.exit(f'Il dataset {o.dataset} esiste in {ds.location}, non in {o.localita}: fermo')
    presenti = {}
    for nome in TABELLE:
        t = bq.Table(f'{o.progetto}.{o.dataset}.{nome}', schema=schema(nome))
        t.time_partitioning = bq.TimePartitioning(field='data_date')
        client.create_table(t, exists_ok=True)
        q = f'SELECT DISTINCT FORMAT_DATE("%Y-%m", data_date) m FROM `{o.progetto}.{o.dataset}.{nome}`'
        presenti[nome] = {r.m for r in client.query(q, location=o.localita).result()}

    mesi = sorted({g.strftime('%Y-%m') for g in giorni})
    print(f'{o.proprieta} → {o.progetto}.{o.dataset} ({o.localita}), {giorni[0]} … {giorni[-1]}, {len(mesi)} mesi')
    for m in mesi:
        da_fare = [n for n in TABELLE if m not in presenti[n]]
        if not da_fare:
            print(f'{m}: già presente, salto')
            continue
        dati = {n: [] for n in da_fare}
        for g in [x for x in giorni if x.strftime('%Y-%m') == m]:
            for n in da_fare:
                agg, dims = TABELLE[n]
                dati[n] += converti(n, o.proprieta, g.isoformat(), dims, scarica(gsc, o.proprieta, g.isoformat(), agg, dims))
            print(f'  {g}', end='\r', flush=True)
        for n in da_fare:
            if not dati[n]:
                continue
            cfg = bq.LoadJobConfig(schema=schema(n), write_disposition='WRITE_APPEND',
                                   source_format=bq.SourceFormat.NEWLINE_DELIMITED_JSON)
            client.load_table_from_json(dati[n], f'{o.progetto}.{o.dataset}.{n}', job_config=cfg,
                                        location=o.localita).result()
        print(f'{m}: ' + ', '.join(f'{n} {len(dati[n])} righe' for n in da_fare) + ' ' * 10)
    print('Fatto.')


if __name__ == '__main__':
    main()
