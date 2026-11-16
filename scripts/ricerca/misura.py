#!/usr/bin/env python3
"""Prova comparativa Vertex AI Search / ricerca Yext (linea C, piano in docs/ricerca/piano-prova-vertex.md).

Sottocomandi:
  vertex   lancia le ricerche ripulite su Vertex AI Search e salva i primi 10 risultati con il tempo di risposta
  yext     lancia le stesse ricerche sulla ricerca Yext di oggi (circa 1 al secondo, source=CONFRONTO_VERTEX)
  livello1 controlli automatici su tutte le ricerche: senza risultati, tempo, nome di struttura -> scheda, luoghi

Esempi:
  python3 -I scripts/ricerca/misura.py vertex --sito anniazzurri --ricerche <dati>/ricerche-anniazzurri.csv \
      --chiave <kos-ricerca-chiavi>/kos-ricerca-....json --esiti <dati>/esiti
  python3 -I scripts/ricerca/misura.py yext --sito anniazzurri --ricerche ... --chiave-yext <file> --esiti ...
  python3 -I scripts/ricerca/misura.py livello1 --sito anniazzurri --ricerche ... --esiti ... --comuni <comuni.json>

Le ricerche vanno ripulite prima con pulisci-ricerche.py. Gli esiti (ricerche e risultati) stanno con i dati della prova,
fuori dal repository. Ogni comando riprende da dove si era fermato: le ricerche già presenti negli esiti si saltano.
"""
import argparse
import csv
import json
import re
import sys
import time
import unicodedata
from pathlib import Path

import requests

PROGETTO = 'kos-ricerca'
BASE_VERTEX = 'https://eu-discoveryengine.googleapis.com/v1/projects/kos-ricerca/locations/eu/collections/default_collection'
APP = {'kosgroup': 'ricerca-kosgroup', 'anniazzurri': 'ricerca-anniazzurri'}
YEXT = 'https://prod-cdn-cached.us.yextapis.com/v2/accounts/me/search/query'
ESPERIENZA = {'kosgroup': 'kos-care-search', 'anniazzurri': 'it-answer-anniazzurri'}
STRUTTURE = {
    'kosgroup': ('kosgroup/dati/voce-24/strutture-yext-2026-11-06.json', 'https://kosgroup.com/'),
    'anniazzurri': ('anniazzurri/dati/schede/strutture-yext-2026-11-06.json', 'https://anniazzurri.it/'),
}
RISULTATI_YEXT_PER_MODULO = 3  # quanti risultati per categoria mostra la pagina universale di Yext


def norm(s):
    s = unicodedata.normalize('NFKD', str(s).lower())
    s = ''.join(c for c in s if not unicodedata.combining(c))
    return re.sub(r"[^a-z0-9 ]+", ' ', s).split()


def url_canonico(u):
    u = (u or '').split('#')[0].split('?')[0].rstrip('/')
    return u.replace('://www.', '://').replace('http://', 'https://')


def leggi_ricerche(percorso):
    with open(percorso, encoding='utf-8', newline='') as f:
        return list(csv.DictReader(f))


def esiti_fatti(percorso):
    fatti = set()
    if percorso.exists():
        for riga in percorso.open(encoding='utf-8'):
            fatti.add(json.loads(riga)['ricerca'])
    return fatti


def token_vertex(chiave):
    from google.oauth2 import service_account
    import google.auth.transport.requests as tr
    c = service_account.Credentials.from_service_account_file(
        chiave, scopes=['https://www.googleapis.com/auth/cloud-platform'])
    c.refresh(tr.Request())
    return c


def cerca_vertex(sessione, cred, sito, q):
    import google.auth.transport.requests as tr
    if not cred.valid:
        cred.refresh(tr.Request())
    corpo = {'query': q, 'pageSize': 10, 'languageCode': 'it',
             'spellCorrectionSpec': {'mode': 'AUTO'}, 'queryExpansionSpec': {'condition': 'AUTO'}}
    t0 = time.time()
    r = sessione.post(f"{BASE_VERTEX}/engines/{APP[sito]}/servingConfigs/default_search:search",
                      headers={'Authorization': 'Bearer ' + cred.token, 'x-goog-user-project': PROGETTO},
                      json=corpo, timeout=60)
    ms = round((time.time() - t0) * 1000)
    j = r.json()
    risultati = []
    for x in j.get('results', []):
        d = x.get('document', {}).get('derivedStructData', {})
        risultati.append({'url': url_canonico(d.get('link')), 'titolo': d.get('title', '')})
    return {'http': r.status_code, 'ms': ms, 'totale': j.get('totalSize', 0), 'risultati': risultati,
            'correzione': (j.get('correctedQuery') or ''), 'errore': (j.get('error') or {}).get('message', '')}


def cerca_yext(sessione, chiave, sito, q):
    p = {'api_key': chiave, 'experienceKey': ESPERIENZA[sito], 'v': '20190101', 'version': 'PRODUCTION',
         'locale': 'it', 'input': q, 'source': 'CONFRONTO_VERTEX'}
    t0 = time.time()
    r = sessione.get(YEXT, params=p, timeout=60)
    ms = round((time.time() - t0) * 1000)
    j = r.json() if r.headers.get('content-type', '').startswith('application/json') else {}
    risultati, totale = [], 0
    for m in j.get('response', {}).get('modules', []):
        totale += m.get('resultsCount', 0)
        for x in m.get('results', [])[:RISULTATI_YEXT_PER_MODULO]:
            d = x.get('data', {})
            link = (d.get('c_primaryCTA') or {}).get('link') or d.get('website') or d.get('landingPageUrl') or ''
            risultati.append({'url': url_canonico(link), 'titolo': d.get('name', ''), 'categoria': m.get('verticalConfigId')})
    return {'http': r.status_code, 'ms': ms, 'totale': totale, 'risultati': risultati,
            'correzione': (j.get('response', {}).get('spellCheck') or {}).get('correctedQuery', ''),
            'limite_residuo': r.headers.get('rate-limit-remaining')}


def lancia(o, motore):
    ricerche = leggi_ricerche(o.ricerche)
    dest = Path(o.esiti) / f'{motore}-{o.sito}.jsonl'
    dest.parent.mkdir(parents=True, exist_ok=True)
    fatti = esiti_fatti(dest)
    s = requests.Session()
    if motore == 'vertex':
        cred, pausa = token_vertex(o.chiave), 0.25
    else:
        cred, pausa = Path(o.chiave_yext).read_text().strip(), 1.0
    nuove = [r for r in ricerche if r['ricerca'] not in fatti]
    print(f'{motore} {o.sito}: {len(nuove)} ricerche da lanciare ({len(fatti)} già fatte)')
    with dest.open('a', encoding='utf-8') as f:
        for i, r in enumerate(nuove, 1):
            for tentativo in range(3):
                try:
                    esito = (cerca_vertex if motore == 'vertex' else cerca_yext)(s, cred, o.sito, r['ricerca'])
                    if esito['http'] == 429:
                        time.sleep(30)
                        continue
                    break
                except requests.RequestException as e:
                    esito = {'http': 'errore', 'errore': str(e)[:200], 'risultati': [], 'totale': 0, 'ms': None}
                    time.sleep(5)
            f.write(json.dumps({'ricerca': r['ricerca'], **esito}, ensure_ascii=False) + '\n')
            f.flush()
            if i % 200 == 0:
                print(f'  {i}/{len(nuove)}')
            time.sleep(pausa)


def strutture(sito):
    percorso, base = STRUTTURE[sito]
    d = json.loads(Path(percorso).read_text(encoding='utf-8'))
    elenco = d if isinstance(d, list) else next(v for v in d.values() if isinstance(v, list))
    out = []
    for s in elenco:
        nome = s['tv'].get('str.nome', '')
        url = base + s['uri']
        out.append({'nome': nome, 'url': url_canonico(url), 'token': norm(nome),
                    'citta': s['tv'].get('str.citta', ''), 'lat': s['tv'].get('str.lat'), 'lng': s['tv'].get('str.lng')})
    return out


GENERICI = set('residenza anni azzurri centro ambulatoriale santo stefano villa casa poliambulatorio laboratorio analisi '
               'punto prelievi istituto ospedale clinica dentale di dei del della la il le san sant santa polo geriatrico '
               'riabilitativo residenziale corte'.split())


def struttura_cercata(q, elenco):
    """La struttura che la ricerca nomina: tutti i token distintivi del nome devono comparire (tolleranza sui refusi).
    Se i token distintivi sono solo il nome della città e in quella città ci sono più strutture (es. «Polo Geriatrico
    Riabilitativo Milano»), la sola città non basta: è una ricerca per luogo."""
    import difflib
    from collections import Counter
    tq = norm(q)
    per_citta = Counter(' '.join(norm(s['citta'])) for s in elenco)
    migliore = None
    for s in elenco:
        distintivi = [t for t in s['token'] if t not in GENERICI and len(t) > 2]
        if not distintivi:
            continue
        citta = norm(s['citta'])
        if set(distintivi) <= set(citta) and per_citta[' '.join(citta)] > 1:
            continue
        ok = all(any(difflib.SequenceMatcher(None, d, t).ratio() >= 0.85 for t in tq) for d in distintivi)
        if ok and (migliore is None or len(distintivi) > migliore[1]):
            migliore = (s, len(distintivi))
    return migliore[0] if migliore else None


# comuni che sono anche parole comuni nelle ricerche: non contano come luogo
NON_LUOGHI = set('salve mese parenti posta carbone varsi sono cento fondi lavoro villa casa santa centro bella vita '
                 'monte piano valle porto calcio cane ponte pace sole nove mare colle cervo ospedaletto arco pero bra '
                 'campo campi fiume lago scala forno capo prato rocca torre pieve corte castello'.split())


def luoghi(comuni_json):
    nomi = {}
    for c in json.loads(Path(comuni_json).read_text(encoding='utf-8')):
        for chiave, tipo in ((c['nome'], 'comune'), (c['provincia']['nome'], 'provincia'), (c['regione']['nome'], 'regione')):
            nomi[' '.join(norm(chiave))] = tipo
    return {k: v for k, v in nomi.items() if len(k) >= 4 and k not in NON_LUOGHI}


def luogo_cercato(q, luoghi_noti):
    t = norm(q)
    for n in (4, 3, 2, 1):
        for i in range(len(t) - n + 1):
            g = ' '.join(t[i:i + n])
            if g in luoghi_noti:
                return g, luoghi_noti[g]
    return None


def livello1(o):
    ricerche = {r['ricerca']: r for r in leggi_ricerche(o.ricerche)}
    el = strutture(o.sito)
    noti = luoghi(o.comuni)
    rapporto = {'sito': o.sito, 'ricerche_distinte': len(ricerche),
                'ricerche_pesate': sum(int(r['ricerche']) for r in ricerche.values())}
    attesi = {}
    for q, r in ricerche.items():
        s = struttura_cercata(q, el)
        l = luogo_cercato(q, noti)
        attesi[q] = {'struttura': s['url'] if s else None, 'luogo': l,
                     'falso_allarme_luogo': bool(s and l)}
    peso = {q: int(r['ricerche']) for q, r in ricerche.items()}
    rapporto['nome_struttura'] = {'termini': sum(1 for a in attesi.values() if a['struttura']),
                                  'ricerche': sum(peso[q] for q, a in attesi.items() if a['struttura'])}
    rapporto['luoghi'] = {'termini': sum(1 for a in attesi.values() if a['luogo'] and not a['struttura']),
                          'ricerche': sum(peso[q] for q, a in attesi.items() if a['luogo'] and not a['struttura']),
                          'nome_struttura_con_luogo': sum(1 for a in attesi.values() if a['falso_allarme_luogo'])}
    for motore in ('vertex', 'yext'):
        f = Path(o.esiti) / f'{motore}-{o.sito}.jsonl'
        if not f.exists():
            continue
        es = {json.loads(x)['ricerca']: json.loads(x) for x in f.open(encoding='utf-8')}
        qs = [q for q in ricerche if q in es]
        tot = sum(peso[q] for q in qs)
        senza = [q for q in qs if not es[q]['risultati']]
        ms = sorted(es[q]['ms'] for q in qs if es[q].get('ms') is not None)
        strutt = [q for q in qs if attesi[q]['struttura']]
        trovate = [q for q in strutt if attesi[q]['struttura'] in [x['url'] for x in es[q]['risultati'][:3]]]
        rapporto[motore] = {
            'ricerche_lanciate': len(qs),
            'senza_risultati_termini': len(senza),
            'senza_risultati_quota_pesata': round(sum(peso[q] for q in senza) / tot * 100, 1) if tot else None,
            'tempo_mediano_ms': ms[len(ms) // 2] if ms else None,
            'tempo_p95_ms': ms[int(len(ms) * 0.95)] if ms else None,
            'nome_struttura_scheda_tra_i_primi_tre_quota_pesata':
                round(sum(peso[q] for q in trovate) / sum(peso[q] for q in strutt) * 100, 1) if strutt else None,
        }
    Path(o.esiti, f'attesi-livello1-{o.sito}.json').write_text(json.dumps(attesi, ensure_ascii=False, indent=1), encoding='utf-8')
    print(json.dumps(rapporto, ensure_ascii=False, indent=1))


def main():
    a = argparse.ArgumentParser()
    sub = a.add_subparsers(dest='cmd', required=True)
    for nome in ('vertex', 'yext', 'livello1'):
        p = sub.add_parser(nome)
        p.add_argument('--sito', required=True, choices=list(APP))
        p.add_argument('--ricerche', required=True)
        p.add_argument('--esiti', required=True)
        if nome == 'vertex':
            p.add_argument('--chiave', required=True)
        if nome == 'yext':
            p.add_argument('--chiave-yext', required=True)
        if nome == 'livello1':
            p.add_argument('--comuni', required=True)
    o = a.parse_args()
    if o.cmd in ('vertex', 'yext'):
        lancia(o, o.cmd)
    else:
        livello1(o)


if __name__ == '__main__':
    sys.exit(main())
