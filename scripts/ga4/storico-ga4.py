#!/usr/bin/env python3
"""
Storico di GA4 in BigQuery, dall'API Google Analytics Data (dati aggregati, nessun identificativo di utente).
Il collegamento GA4 -> BigQuery parte dal giorno in cui si attiva e non recupera il passato.

Si lancia sul PC (l'ambiente di lavoro cloud non raggiunge le API Google), con il client OAuth del progetto.

Prerequisiti (una volta):
    - abilitare «Google Analytics Data API» nel progetto Google Cloud a cui appartiene il client OAuth;
    - py -m pip install google-analytics-data google-auth-oauthlib google-cloud-bigquery
    - il dataset di destinazione deve esistere gia' in europe-west3 (ga4storico_<sigla>)

Uso (PowerShell, dalla radice del repository):
    py scripts/ga4/storico-ga4.py --client-secret C:\\percorso\\client_secret.json --proprieta 317279845 \\
        --dataset ga4storico_vrd --dal 2022-06-01 --prova
    (stesso comando senza --prova per scrivere)

Opzioni:
    --progetto     progetto BigQuery (default kos-analytics-482819)
    --dal / --fino-al   intervallo (default: --fino-al = ieri)
    --tabelle      elenco separato da virgole (default: storico_pagine,storico_eventi); le altre sono nella
                   sezione «Tabelle opzionali» qui sotto
    --prova        scarica solo il primo mese dell'intervallo e stampa i conteggi, senza scrivere in BigQuery

Tabelle (partizionate per data_date):
    storico_pagine   per giorno, host, pagina, canale, sorgente/mezzo, dispositivo: sessioni, utenti attivi,
                     visualizzazioni, sessioni con interazione, secondi di interazione
    storico_eventi   per giorno, nome evento, host: conteggio eventi e utenti totali
Tabelle opzionali (si chiedono con --tabelle; tutte per giorno e host, dati aggregati):
    storico_geo      regione e citta': sessioni, utenti attivi, sessioni con interazione
    storico_utenti   fascia di eta', genere, nuovo/di ritorno: sessioni, utenti attivi
                     (eta' e genere ci sono solo dove Google Signals e' attivo e sopra le soglie di privacy)
    storico_orario   ora del giorno: sessioni, utenti attivi
    storico_arrivo   pagina di atterraggio e canale: sessioni, sessioni con interazione
    storico_uscite   clic su link in uscita (evento click): dominio e URL di destinazione, conteggio, utenti
Gli utenti non si sommano tra righe o giorni: servono per confronti dentro la stessa riga, non per totali di periodo.
Riprende per mese: i mesi gia' presenti nella tabella (qualunque riga) si saltano; per riscaricare un mese
si cancellano le sue righe in BigQuery. Il token OAuth si salva in %APPDATA%\\kos-seo-geo\\token-ga4.json
(fuori dal repository).
"""
import argparse
import calendar
import datetime as dt
import os
import sys

SCOPES = ['https://www.googleapis.com/auth/analytics.readonly', 'https://www.googleapis.com/auth/bigquery']
TABELLE = {
    'storico_pagine': {
        'dim': ['date', 'hostName', 'pagePath', 'sessionDefaultChannelGroup', 'sessionSourceMedium', 'deviceCategory'],
        'met': ['sessions', 'activeUsers', 'screenPageViews', 'engagedSessions', 'userEngagementDuration'],
        'col': ['data_date', 'host', 'pagina', 'canale', 'sorgente_mezzo', 'dispositivo',
                'sessioni', 'utenti_attivi', 'visualizzazioni', 'sessioni_con_interazione', 'secondi_interazione'],
    },
    'storico_eventi': {
        'dim': ['date', 'eventName', 'hostName'],
        'met': ['eventCount', 'totalUsers'],
        'col': ['data_date', 'nome_evento', 'host', 'conteggio_eventi', 'utenti_totali'],
    },
    'storico_geo': {
        'dim': ['date', 'hostName', 'region', 'city'],
        'met': ['sessions', 'activeUsers', 'engagedSessions'],
        'col': ['data_date', 'host', 'regione', 'citta', 'sessioni', 'utenti_attivi', 'sessioni_con_interazione'],
    },
    'storico_utenti': {
        'dim': ['date', 'hostName', 'userAgeBracket', 'userGender', 'newVsReturning'],
        'met': ['sessions', 'activeUsers'],
        'col': ['data_date', 'host', 'fascia_eta', 'genere', 'nuovo_ritorno', 'sessioni', 'utenti_attivi'],
    },
    'storico_orario': {
        'dim': ['date', 'hostName', 'hour'],
        'met': ['sessions', 'activeUsers'],
        'col': ['data_date', 'host', 'ora', 'sessioni', 'utenti_attivi'],
    },
    'storico_arrivo': {
        'dim': ['date', 'hostName', 'landingPage', 'sessionDefaultChannelGroup'],
        'met': ['sessions', 'engagedSessions'],
        'col': ['data_date', 'host', 'pagina_atterraggio', 'canale', 'sessioni', 'sessioni_con_interazione'],
    },
    'storico_uscite': {
        'dim': ['date', 'hostName', 'linkDomain', 'linkUrl'],
        'met': ['eventCount', 'totalUsers'],
        'col': ['data_date', 'host', 'dominio_link', 'url_link', 'conteggio_eventi', 'utenti_totali'],
        'eventi': ['click'],
    },
}
PREDEFINITE = ['storico_pagine', 'storico_eventi']


def credenziali(client_secret):
    from google.oauth2.credentials import Credentials
    from google.auth.transport.requests import Request
    from google_auth_oauthlib.flow import InstalledAppFlow
    cartella = os.path.join(os.environ.get('APPDATA') or os.path.expanduser('~'), 'kos-seo-geo')
    os.makedirs(cartella, exist_ok=True)
    tok = os.path.join(cartella, 'token-ga4.json')
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
    c = TABELLE[nome]['col']
    out = [bq.SchemaField(c[0], 'DATE')]
    for n in c[1:len(TABELLE[nome]['dim'])]:
        out.append(bq.SchemaField(n, 'STRING'))
    for n in c[len(TABELLE[nome]['dim']):]:
        out.append(bq.SchemaField(n, 'INTEGER'))
    return out


def mesi(dal, al):
    d = dal.replace(day=1)
    while d <= al:
        ultimo = d.replace(day=calendar.monthrange(d.year, d.month)[1])
        yield max(d, dal), min(ultimo, al)
        d = ultimo + dt.timedelta(days=1)


def scarica(client, proprieta, nome, inizio, fine):
    from google.analytics.data_v1beta.types import (DateRange, Dimension, Filter, FilterExpression, Metric,
                                                    RunReportRequest)
    t = TABELLE[nome]
    righe, offset = [], 0
    filtro = None
    if t.get('eventi'):
        filtro = FilterExpression(filter=Filter(field_name='eventName',
                                                in_list_filter=Filter.InListFilter(values=t['eventi'])))
    while True:
        r = client.run_report(RunReportRequest(
            property='properties/' + proprieta,
            dimensions=[Dimension(name=x) for x in t['dim']],
            metrics=[Metric(name=x) for x in t['met']],
            date_ranges=[DateRange(start_date=inizio.isoformat(), end_date=fine.isoformat())],
            dimension_filter=filtro, limit=100000, offset=offset))
        for rr in r.rows:
            v = [x.value for x in rr.dimension_values]
            d = dt.datetime.strptime(v[0], '%Y%m%d').date().isoformat()
            m = [int(float(x.value)) for x in rr.metric_values]
            righe.append(dict(zip(t['col'], [d] + v[1:] + m)))
        offset += len(r.rows)
        if offset >= r.row_count or not r.rows:
            return righe


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--client-secret', required=True)
    ap.add_argument('--proprieta', required=True, help='ID numerico della proprieta GA4')
    ap.add_argument('--dataset', required=True)
    ap.add_argument('--progetto', default='kos-analytics-482819')
    ap.add_argument('--dal', required=True)
    ap.add_argument('--fino-al')
    ap.add_argument('--tabelle', default=','.join(PREDEFINITE))
    ap.add_argument('--prova', action='store_true')
    a = ap.parse_args()
    dal = dt.date.fromisoformat(a.dal)
    al = dt.date.fromisoformat(a.fino_al) if a.fino_al else dt.date.today() - dt.timedelta(days=1)
    from google.analytics.data_v1beta import BetaAnalyticsDataClient
    from google.cloud import bigquery as bq
    cred = credenziali(a.client_secret)
    client = BetaAnalyticsDataClient(credentials=cred)
    scelte = [x.strip() for x in a.tabelle.split(',') if x.strip()]
    for x in scelte:
        if x not in TABELLE:
            sys.exit('tabella sconosciuta: %s (disponibili: %s)' % (x, ', '.join(TABELLE)))
    elenco = list(mesi(dal, al))
    if a.prova:
        elenco = elenco[:1]
    bqc = None if a.prova else bq.Client(project=a.progetto, credentials=cred)
    for nome in scelte:
        presenti = set()
        if bqc:
            tab = '%s.%s.%s' % (a.progetto, a.dataset, nome)
            try:
                bqc.get_table(tab)
            except Exception:
                t = bq.Table(tab, schema=schema(nome))
                t.time_partitioning = bq.TimePartitioning(field='data_date')
                bqc.create_table(t)
            for r in bqc.query('SELECT DISTINCT FORMAT_DATE("%%Y-%%m", data_date) m FROM `%s`' % tab).result():
                presenti.add(r.m)
        for inizio, fine in elenco:
            if inizio.strftime('%Y-%m') in presenti:
                print(nome, inizio.strftime('%Y-%m'), 'gia presente, salto')
                continue
            righe = scarica(client, a.proprieta, nome, inizio, fine)
            tot = sum(r[TABELLE[nome]['col'][-2]] for r in righe)
            print('%s %s: %d righe, somma %s = %d' % (nome, inizio.strftime('%Y-%m'), len(righe),
                                                      TABELLE[nome]['col'][-2], tot))
            if bqc and righe:
                job = bqc.load_table_from_json(righe, tab, job_config=bq.LoadJobConfig(
                    schema=schema(nome), write_disposition='WRITE_APPEND'))
                job.result()
    print('fatto' if not a.prova else 'prova terminata, nulla scritto')


if __name__ == '__main__':
    sys.exit(main())
