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

Le ricerche vanno ripulite prima con pulisci-ricerche.py e controllate con controlla-dlp.py (vertex e yext si
rifiutano di partire senza il controllo). Gli esiti (ricerche e risultati) stanno con i dati della prova,
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
# varianti dell'app per la prova (16/11): kosgroup.com più l'archivio delle sole schede delle strutture di anniazzurri.it
VARIANTI = {'schede-aa': {'kosgroup': 'ricerca-kosgroup-schede-aa'}}
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


def cerca_vertex(sessione, cred, sito, q, app=None, espansione=True):
    import google.auth.transport.requests as tr
    if not cred.valid:
        cred.refresh(tr.Request())
    corpo = {'query': q, 'pageSize': 10, 'languageCode': 'it',
             'spellCorrectionSpec': {'mode': 'AUTO'}, 'queryExpansionSpec': {'condition': 'AUTO'}}
    if app or not espansione:
        # app con più archivi: Vertex non accetta l'espansione delle ricerche (errore 400, verifica del 16/11);
        # senza espansione anche per il confronto alla pari con quella forma (decisione del 16/11)
        del corpo['queryExpansionSpec']
    t0 = time.time()
    r = sessione.post(f"{BASE_VERTEX}/engines/{app or APP[sito]}/servingConfigs/default_search:search",
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


def controllo_dlp_fatto(percorso):
    """Il set ripulito è passato da controlla-dlp.py senza ritrovamenti aperti (decisione del 16/11)."""
    import hashlib
    ok = Path(str(percorso) + '.dlp-ok')
    return ok.exists() and ok.read_text().strip() == hashlib.sha256(Path(percorso).read_bytes()).hexdigest()


def lancia(o, motore):
    if not controllo_dlp_fatto(o.ricerche):
        sys.exit(f'{o.ricerche}: manca il controllo di Sensitive Data Protection (controlla-dlp.py) o il file è cambiato '
                 'dopo il controllo: nessuna ricerca lanciata')
    ricerche = leggi_ricerche(o.ricerche)
    variante = getattr(o, 'variante', None)
    app = VARIANTI[variante][o.sito] if variante else None
    etichetta = f'{motore}-{variante}' if variante else motore
    espansione = not getattr(o, 'senza_espansione', False)
    if not espansione:
        etichetta += '-senza-espansione'
    dest = Path(o.esiti) / f'{etichetta}-{o.sito}.jsonl'
    dest.parent.mkdir(parents=True, exist_ok=True)
    fatti = esiti_fatti(dest)
    s = requests.Session()
    if motore == 'vertex':
        cred, pausa = token_vertex(o.chiave), 0.25
    else:
        cred, pausa = Path(o.chiave_yext).read_text().strip(), 1.0
    if getattr(o, 'campione', None):
        # giro ridotto (es. Yext sul solo livello 2, decisione del 16/11): solo le ricerche del campione
        nel_campione = {v['ricerca'] for v in json.loads(Path(o.campione).read_text(encoding='utf-8'))
                        if not getattr(o, 'meta', None) or v.get('meta') == o.meta}
        ricerche = [r for r in ricerche if r['ricerca'] in nel_campione]
    nuove = [r for r in ricerche if r['ricerca'] not in fatti]
    print(f'{etichetta} {o.sito}: {len(nuove)} ricerche da lanciare ({len(fatti)} già fatte)')
    with dest.open('a', encoding='utf-8') as f:
        for i, r in enumerate(nuove, 1):
            for tentativo in range(3):
                try:
                    esito = (cerca_vertex(s, cred, o.sito, r['ricerca'], app, espansione) if motore == 'vertex'
                             else cerca_yext(s, cred, o.sito, r['ricerca']))
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


# Schede che l'export delle TV del 6/11 non contiene (verifica del 16/11 sul sorgente pubblicato):
# - tre schede con i dati della struttura dal 16/11 notte (registro, «KOS 24, tre schede senza nodo»);
# - Villa Margherita Vicenza: la struttura è la landing /villamargherita, le schede delle unità sono reparti
#   (#department, registro 12/11); per la ricerca valgono tutte e tre.
ALTRE_SCHEDE = {'kosgroup': [
    ('Poliambulatorio Villalba', 'Macerata', 'MC', 'it/centri/poliambulatorio-villalba'),
    ('Santo Stefano Casorate', 'Casorate Primo', 'PV', 'it/centri/santo-stefano-casorate'),
    ('Santo Stefano Mede', 'Mede', 'PV', 'it/centri/santo-stefano-mede'),
    ('Villa Margherita Vicenza', 'Arcugnano', 'VI', 'villamargherita'),
    ('Villa Margherita Vicenza', 'Arcugnano', 'VI', 'it/centri/villa-margherita-vicenza-riabilitazione'),
    ('Villa Margherita Vicenza', 'Arcugnano', 'VI', 'it/centri/villa-margherita-vicenza-psichiatria'),
]}


def strutture(sito):
    """Strutture del sito con nome e scheda; la ricerca di kosgroup.com trova anche le residenze Anni Azzurri."""
    siti = [sito] + (['anniazzurri'] if sito == 'kosgroup' else [])
    out = []
    for nome_sito in siti:
        percorso, base = STRUTTURE[nome_sito]
        d = json.loads(Path(percorso).read_text(encoding='utf-8'))
        elenco = d if isinstance(d, list) else next(v for v in d.values() if isinstance(v, list))
        for s in elenco:
            nome = s['tv'].get('str.nome', '')
            out.append({'nome': nome, 'url': url_canonico(base + s['uri']), 'token': norm(nome),
                        'citta': s['tv'].get('str.citta', ''), 'lat': s['tv'].get('str.lat'),
                        'lng': s['tv'].get('str.lng')})
        for nome, citta, _sigla, uri in ALTRE_SCHEDE.get(nome_sito, []):
            out.append({'nome': nome, 'url': url_canonico(base + uri), 'token': norm(nome), 'citta': citta,
                        'lat': None, 'lng': None})
    return out


GENERICI = set('residenza anni azzurri azzurra centro ambulatoriale santo stefano villa casa poliambulatorio laboratorio '
               'analisi punto prelievi istituto ospedale clinica dentale di dei del della la il le san sant santa polo '
               'geriatrico riabilitativo residenziale corte medico diagnostica'.split())


VUOTE = set('di dei del della la il le'.split())


def simile(a, b):
    import difflib
    if a == b:
        return True
    return len(a) >= 6 and len(b) >= 6 and difflib.SequenceMatcher(None, a, b).ratio() >= 0.88


def struttura_cercata(q, elenco, luogo=None):
    """Le strutture che la ricerca nomina (insieme: più schede possono rispondere allo stesso nome, es. Villa dei Pini).
    Servono tutti i token distintivi del nome, città della struttura esclusa quando il nome ha anche altre parole
    («villa dei pini» vale per Firenze e per Civitanova; «villa dei pini civitanova» solo per Civitanova); refusi
    tollerati sulle parole di 6 lettere e più. A parità vince chi ha più parole del nome e della città nella ricerca; a chi vince si
    aggiungono le schede il cui nome lo contiene (es. «villalba»: Clinica Dentale e Punto Prelievi Salus Villalba).
    Se il nome distintivo è solo una città con più strutture (es. Polo Geriatrico Riabilitativo Milano) servono almeno
    due altre parole del nome, altrimenti è una ricerca per luogo; il nome non vale nemmeno se sta dentro il nome di un
    comune più lungo (es. «borgo san lorenzo» non è la Residenza San Lorenzo): è una ricerca per luogo."""
    from collections import Counter
    tq = norm(q)
    per_citta = Counter(' '.join(norm(s['citta'])) for s in elenco)
    luogo_t = set(norm(luogo[0])) if luogo else set()

    def distintivi(s):
        return [t for t in s['token'] if t not in GENERICI and len(t) > 2]

    candidati, richiesti_di = [], {}
    for s in elenco:
        dist = distintivi(s)
        if not dist:
            continue
        citta = norm(s['citta'])
        senza_citta = [t for t in dist if t not in citta]
        richiesti = senza_citta or dist
        if not all(any(simile(d, t) for t in tq) for d in richiesti):
            continue
        if not senza_citta and per_citta[' '.join(citta)] > 1:
            # nome distintivo = città con più strutture: serve anche il resto del nome (almeno due parole,
            # es. «centro ambulatoriale santo stefano jesi»), altrimenti è una ricerca per luogo
            resto = [t for t in s['token'] if t not in citta and t not in VUOTE]
            if sum(1 for t in resto if any(simile(t, x) for x in tq)) < 2:
                continue
        if luogo_t and set(richiesti) <= luogo_t and luogo_t - set(s['token']) and not set(citta) & luogo_t:
            continue
        tutte = sum(1 for t in set(s['token']) | set(citta) if any(simile(t, x) for x in tq))
        richiesti_di[id(s)] = richiesti
        candidati.append(((len(richiesti), tutte), s))
    if not candidati:
        return []
    top = max(c[0] for c in candidati)
    scelte = [s for k, s in candidati if k == top]
    vincitori = list(scelte)
    def nominate(x):  # parole del nome (e della città) che la ricerca contiene
        return {t for t in set(x['token']) | set(norm(x['citta'])) if any(simile(t, y) for y in tq)}
    for s in elenco:
        if s not in scelte and any(set(richiesti_di[id(x)]) < set(distintivi(s)) and nominate(x) <= set(s['token'])
                                   for x in vincitori):
            scelte.append(s)
    return scelte


# comuni che sono anche parole comuni nelle ricerche: non contano come luogo
NON_LUOGHI = set('salve mese parenti posta carbone varsi sono cento fondi lavoro villa casa santa centro bella vita '
                 'monte piano valle porto calcio cane ponte pace sole nove mare colle cervo ospedaletto arco pero bra '
                 'campo campi fiume lago scala forno capo prato rocca torre pieve corte castello medicina dello minori '
                 'front monti opera piscina viale piatto venosa dolce bono stella alto quarto mele falcone paola '
                 'tortorella ginestra'.split())


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


def componi(schede, risultati):
    """Pagina dei risultati con il riconoscimento dei nomi: prima le schede delle strutture nominate, poi Vertex."""
    testa = [{'url': u, 'titolo': '', 'riconosciuta': True} for u in (schede or [])]
    visti = {x['url'] for x in testa}
    return testa + [x for x in risultati if x['url'] not in visti]


def livello1(o):
    ricerche = {r['ricerca']: r for r in leggi_ricerche(o.ricerche)}
    el = strutture(o.sito)
    noti = luoghi(o.comuni)
    rapporto = {'sito': o.sito, 'ricerche_distinte': len(ricerche),
                'ricerche_pesate': sum(int(r['ricerche']) for r in ricerche.values())}
    attesi = {}
    for q, r in ricerche.items():
        l = luogo_cercato(q, noti)
        s = struttura_cercata(q, el, l)
        attesi[q] = {'struttura': [x['url'] for x in s] or None, 'luogo': l,
                     'falso_allarme_luogo': bool(s and l)}
    peso = {q: int(r['ricerche']) for q, r in ricerche.items()}
    rapporto['nome_struttura'] = {'termini': sum(1 for a in attesi.values() if a['struttura']),
                                  'ricerche': sum(peso[q] for q, a in attesi.items() if a['struttura'])}
    rapporto['luoghi'] = {'termini': sum(1 for a in attesi.values() if a['luogo'] and not a['struttura']),
                          'ricerche': sum(peso[q] for q, a in attesi.items() if a['luogo'] and not a['struttura']),
                          'nome_struttura_con_luogo': sum(1 for a in attesi.values() if a['falso_allarme_luogo'])}
    forme = [('vertex', 'vertex', False), ('vertex_con_riconoscimento', 'vertex', True),
             ('vertex_schede_aa', 'vertex-schede-aa', False),
             ('vertex_schede_aa_con_riconoscimento', 'vertex-schede-aa', True), ('yext', 'yext', False)]
    for forma, motore, riconosci in forme:
        f = Path(o.esiti) / f'{motore}-{o.sito}.jsonl'
        if not f.exists():
            continue
        es = {}
        for x in f.open(encoding='utf-8'):
            e = json.loads(x)
            if riconosci:
                e['risultati'] = componi(attesi[e['ricerca']]['struttura'], e['risultati'])
            es[e['ricerca']] = e
        qs = [q for q in ricerche if q in es]
        tot = sum(peso[q] for q in qs)
        senza = [q for q in qs if not es[q]['risultati']]
        ms = sorted(es[q]['ms'] for q in qs if es[q].get('ms') is not None)
        strutt = [q for q in qs if attesi[q]['struttura']]
        trovate = [q for q in strutt if set(attesi[q]['struttura']) & {x['url'] for x in es[q]['risultati'][:3]}]
        rapporto[forma] = {
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
            p.add_argument('--variante', choices=list(VARIANTI))
            p.add_argument('--senza-espansione', action='store_true',
                           help='senza queryExpansionSpec: confronto alla pari con l\'app con più archivi')
        if nome == 'yext':
            p.add_argument('--chiave-yext', required=True)
        if nome in ('vertex', 'yext'):
            p.add_argument('--campione', help='campione-livello2-<sito>.json: giro ridotto alle sole ricerche del campione')
        if nome in ('vertex', 'yext'):
            p.add_argument('--meta', choices=['definizione', 'verifica'], help='con --campione: solo una metà')
        if nome == 'livello1':
            p.add_argument('--comuni', required=True)
    o = a.parse_args()
    if o.cmd in ('vertex', 'yext'):
        lancia(o, o.cmd)
    else:
        livello1(o)


if __name__ == '__main__':
    sys.exit(main())
