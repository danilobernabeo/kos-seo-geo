#!/usr/bin/env python3
"""Livello 2 della prova Vertex / Yext: pertinenza, risultato atteso tra i primi tre (piano in docs/ricerca/piano-prova-vertex.md).

Sottocomandi:
  campione  costruisce il campione e i risultati attesi dalle regole (nome di struttura, luogo); per le altre ricerche
            prepara le candidate (primi 10 di ogni motore) da giudicare a mano nel file dei giudizi
  misura    risultato atteso tra i primi tre per ogni forma (Vertex, con riconoscimento dei nomi, con l'archivio delle
            schede AA, Yext), pesato per frequenza e non pesato, con le ricerche da 4 parole in su a parte

Campione: kosgroup.com le 250 ricerche più frequenti più 150 estratte (seme fisso) dalle altre, 60 frasi da 4 parole in
su, 45 con refusi (parole fuori dal vocabolario dei siti), 45 con un luogo; anniazzurri.it tutte le ricerche ripulite.

Regole dei risultati attesi:
  nome di struttura  la scheda (o le schede omonime), come nel livello 1;
  luogo              solo ricerche per luogo pure («rsa a milano»; con altre parole si giudica a mano): le schede
                     delle strutture della stessa provincia e, su anniazzurri.it, la pagina «RSA in provincia di …»; per una regione quelle della regione; se non ce
                     ne sono, la pagina dell'elenco delle strutture;
  altro              giudizio nel file giudizi-livello2-<sito>.json: {"ricerca": [voce, ...]}, dove una voce è
                     un URL esatto, un prefisso con «*», «LUOGO:<comune, provincia o regione>» (schede della provincia
                     o della regione), «CITTA:<città>» (schede della città) o «TESTO:<espressione regolare>» (pagina
                     che contiene il testo: per la coda lunga, es. una prestazione senza pagina propria);
                     «NESSUNA:<motivo>» vuol dire nessuna pagina giusta sul sito: la ricerca esce dal calcolo e va
                     nell'elenco per motivo («contenuto mancante: …», «rimando a kosgroup.com», «struttura di altri»,
                     «non chiara»), da riportare nella restituzione.

Metà del campione (assegnata per hash della ricerca, stabile) serve a definire le regole di posizionamento, l'altra metà a
misurarle: `misura` riporta le due metà separate.

Tutti i file (campione, giudizi, esiti) stanno con i dati della prova, fuori dal repository.

  python3 -I scripts/ricerca/livello2.py campione --sito kosgroup --ricerche <dati>/ricerche-kosgroup.csv \
      --esiti <dati>/esiti --comuni <dati>/comuni.json --vocabolario <dati>/vocabolario.json
  python3 -I scripts/ricerca/livello2.py misura --sito kosgroup --ricerche ... --esiti ...
"""
import argparse
import importlib.util
import json
import random
import sys
from pathlib import Path

_spec = importlib.util.spec_from_file_location('misura', Path(__file__).with_name('misura.py'))
m = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(m)

SEME = 16112026
ELENCO = {'kosgroup': 'https://kosgroup.com/it/strutture*', 'anniazzurri': 'https://anniazzurri.it/residenze'}
# parole che non cambiano il bisogno di una ricerca per luogo («rsa a milano», «strutture vicino torino»): se la ricerca
# ha altre parole (es. «lavora con noi ... monza», «parkinson provincia ancona») si giudica a mano
PAROLE_GEO = set('a in di da del della nel nella e zona provincia vicino vicina vicine vicini me piu dove strutture '
                 'struttura centro centri sede sedi rsa residenza residenze casa di riposo anziani per anni azzurri kos '
                 'group gruppo clinica cliniche poliambulatorio poliambulatori ospedale ospedali'.split())
# pagine per provincia o regione di anniazzurri.it (sitemap del 16/11): per una ricerca per luogo valgono come le schede
PAGINE_LUOGO = {'anniazzurri': {
    'MI': 'rsa-a-milano-e-provincia', 'TO': 'rsa-in-provincia-di-torino', 'GE': 'rsa-in-provincia-di-genova',
    'VE': 'rsa-in-provincia-di-venezia', 'BO': 'rsa-in-provincia-di-bologna', 'FI': 'rsa-in-provincia-di-firenze',
    'AN': 'rsa-in-provincia-di-ancona', 'MB': 'rsa-in-provincia-di-monza-e-brianza', 'CN': 'rsa-in-provincia-di-cuneo',
    'MO': 'rsa-in-provincia-di-modena', 'NO': 'rsa-in-provincia-di-novara', 'BG': 'rsa-in-provincia-di-bergamo',
    'MC': 'rsa-in-provincia-di-macerata', 'BS': 'rsa-in-provincia-di-brescia',
    'lombardia': 'rsa-in-lombardia', 'piemonte': 'rsa-in-piemonte', 'liguria': 'rsa-in-liguria',
    'marche': 'rsa-nelle-marche', 'emilia romagna': 'rsa-in-emilia-romagna', 'veneto': 'rsa-in-veneto',
    'toscana': 'rsa-in-toscana', 'lazio': 'rsa-nel-lazio'}}


def pagine_luogo(sito, luogo, prov):
    """Pagine per provincia (se il luogo è un comune o una provincia) o per regione (se è una regione)."""
    mappa = PAGINE_LUOGO.get(sito, {})
    base = 'https://anniazzurri.it/'
    chiave = ' '.join(m.norm(luogo))
    if chiave in mappa:
        return [base + mappa[chiave]]
    sigle = prov.get(chiave, set())
    return [base + mappa[x] for x in sorted(sigle) if x in mappa] if len(sigle) <= 2 else []


FORME = [('vertex', 'vertex', False), ('vertex_con_riconoscimento', 'vertex', True),
         ('vertex_senza_espansione', 'vertex-senza-espansione', False),
         ('vertex_senza_espansione_con_riconoscimento', 'vertex-senza-espansione', True),
         ('vertex_schede_aa', 'vertex-schede-aa', False),
         ('vertex_schede_aa_con_riconoscimento', 'vertex-schede-aa', True),
         # forma completa (decisione del 17/11): simula la pagina dei risultati, non è una risposta di Vertex
         ('completa', 'vertex-schede-aa', 'completa'),
         # Yext (giro del 16/11): «yext» con le schede riportate al loro indirizzo dal nome dell'entità e le FAQ
         # giudicate; «yext_grezzo» com'è; «yext_faq_tutte_giuste» tetto con ogni FAQ giusta (decisione del 16/11)
         ('yext', 'yext', False), ('yext_grezzo', 'yext', False), ('yext_faq_tutte_giuste', 'yext', False)]
# coordinate delle schede aggiunte a mano (misura.ALTRE_SCHEDE) senza TV: Villa Margherita dal geo della landing
# (17/11); le altre dal comune, perché la pagina non ha il geo nel dato strutturato
COORD_ALTRE = {'https://kosgroup.com/villamargherita': (45.51335, 11.54432),
               'https://kosgroup.com/it/centri/villa-margherita-vicenza-riabilitazione': (45.51335, 11.54432),
               'https://kosgroup.com/it/centri/villa-margherita-vicenza-psichiatria': (45.51335, 11.54432)}


def geografia(comuni_json, geo_json):
    """Luogo normalizzato -> (lat, lng): comune dal file con le coordinate (codice ISTAT), provincia e regione come
    media dei loro comuni."""
    righe = [g for g in json.loads(Path(geo_json).read_text()) if str(g.get('istat', '')).isdigit()
             and g.get('lat') and g.get('lng')]
    geo = {int(g['istat']): (float(g['lat']), float(g['lng'])) for g in righe}
    per_nome = {' '.join(m.norm(g['comune'])): (float(g['lat']), float(g['lng'])) for g in righe}
    gruppi = {}
    out = {}
    for c in json.loads(Path(comuni_json).read_text(encoding='utf-8')):
        nome = ' '.join(m.norm(c['nome']))
        xy = geo.get(int(c['codice'])) or per_nome.get(nome)
        if not xy:
            continue
        out[nome] = xy
        for k in (c['provincia']['nome'], c['regione']['nome']):
            gruppi.setdefault(' '.join(m.norm(k)), []).append(xy)
    for k, l in gruppi.items():
        out.setdefault(k, (sum(a for a, _ in l) / len(l), sum(b for _, b in l) / len(l)))
    return out


def piu_vicine(xy, punti, n=3):
    import math
    def km(a, b):
        la1, lo1, la2, lo2 = map(math.radians, (*a, *b))
        h = math.sin((la2 - la1) / 2) ** 2 + math.cos(la1) * math.cos(la2) * math.sin((lo2 - lo1) / 2) ** 2
        return 6371 * 2 * math.asin(math.sqrt(h))
    return [u for u, _ in sorted(punti.items(), key=lambda p: km(xy, p[1]))[:n]]


ABBINAMENTI_YEXT = Path(__file__).resolve().parents[2] / 'docs/ricerca/abbinamenti-yext-2026-11-16.json'


def risultati_yext(ris, forma, faq_giusta):
    """Risultati Yext per il confronto: schede con «Altro» nel link principale riportate al loro indirizzo dal nome
    dell'entità (abbinamento esatto, elenco in docs/ricerca); FAQ senza indirizzo valutate con il giudizio."""
    if forma == 'yext_grezzo':
        return ris
    abb = json.loads(ABBINAMENTI_YEXT.read_text(encoding='utf-8'))['abbinamenti']
    out = []
    for x in ris:
        x = dict(x)
        if not x['url'].startswith('http') and x.get('categoria') == 'strutture':
            x['url'] = abb.get(x['titolo'], 'NON-ABBINATA')
        elif x.get('categoria') == 'faqs' and not x['url']:
            x['url'] = 'FAQ-GIUSTA' if (forma == 'yext_faq_tutte_giuste' or faq_giusta) else 'FAQ'
        out.append(x)
    return out


def province(comuni_json):
    """Luogo normalizzato -> insieme di sigle di provincia (comune, provincia o regione)."""
    out = {}
    for c in json.loads(Path(comuni_json).read_text(encoding='utf-8')):
        for chiave in (c['nome'], c['provincia']['nome'], c['regione']['nome']):
            out.setdefault(' '.join(m.norm(chiave)), set())
        out[' '.join(m.norm(c['nome']))].add(c['sigla'])
        out[' '.join(m.norm(c['provincia']['nome']))].add(c['sigla'])
        out[' '.join(m.norm(c['regione']['nome']))].add(c['sigla'])
    return out


def sigle_strutture(sito):
    """URL della scheda -> sigla di provincia, dalle TV e dalle schede aggiunte a mano in misura.ALTRE_SCHEDE."""
    siti = [sito] + (['anniazzurri'] if sito == 'kosgroup' else [])
    out = {}
    for s in siti:
        percorso, base = m.STRUTTURE[s]
        d = json.loads(Path(percorso).read_text(encoding='utf-8'))
        elenco = d if isinstance(d, list) else next(v for v in d.values() if isinstance(v, list))
        for x in elenco:
            out[m.url_canonico(base + x['uri'])] = x['tv'].get('str.provincia')
        for _nome, _citta, sigla, uri in m.ALTRE_SCHEDE.get(s, []):
            out[m.url_canonico(base + uri)] = sigla
    return out


def esiti(cartella, motore, sito):
    f = Path(cartella) / f'{motore}-{sito}.jsonl'
    if not f.exists():
        return {}
    return {e['ricerca']: e for e in (json.loads(x) for x in f.open(encoding='utf-8'))}


def meta(q):
    import hashlib
    return 'definizione' if int(hashlib.sha1(q.encode('utf-8')).hexdigest()[:8], 16) % 2 == 0 else 'verifica'


def campione(o):
    ricerche = sorted(m.leggi_ricerche(o.ricerche), key=lambda r: -int(r['ricerche']))
    el = m.strutture(o.sito)
    noti = m.luoghi(o.comuni)
    vocabolario = set(json.loads(Path(o.vocabolario).read_text(encoding='utf-8')))
    if o.sito == 'kosgroup':
        scelte = ricerche[:250]
        resto = ricerche[250:]
        rnd = random.Random(SEME)
        lunghe = [r for r in resto if len(r['ricerca'].split()) >= 4]
        refusi = [r for r in resto if r not in lunghe and not m.luogo_cercato(r['ricerca'], noti)
                  and any(t not in vocabolario and not t.isdigit() for t in m.norm(r['ricerca']))]
        con_luogo = [r for r in resto if r not in lunghe and m.luogo_cercato(r['ricerca'], noti)]
        strati = {'frequenti': scelte, 'lunghe': rnd.sample(lunghe, 60), 'refusi': rnd.sample(refusi, 45),
                  'luoghi': rnd.sample(con_luogo, 45)}
    else:
        strati = {'tutte': ricerche}
    prov = province(o.comuni)
    sigle = sigle_strutture(o.sito)
    motori = {k: esiti(o.esiti, k, o.sito) for k in ('vertex', 'vertex-schede-aa', 'yext')}
    out = []
    for strato, righe in strati.items():
        for r in righe:
            q = r['ricerca']
            luogo = m.luogo_cercato(q, noti)
            nominate = m.struttura_cercata(q, el, luogo)
            voce = {'ricerca': q, 'ricerche': int(r['ricerche']), 'strato': strato,
                    'parole': len(q.split()), 'meta': meta(q)}
            if nominate:
                voce.update(categoria='struttura', attesi=[x['url'] for x in nominate], fonte='regola')
            elif luogo and set(m.norm(q)) - set(luogo[0].split()) <= PAROLE_GEO:
                vicine = sorted(u for u, s in sigle.items() if s in prov.get(luogo[0], set()))
                vicine += pagine_luogo(o.sito, luogo[0], prov)
                voce.update(categoria='luogo', luogo=luogo[0], attesi=vicine or [ELENCO[o.sito]], fonte='regola')
            else:
                candidate = []
                for e in motori.values():
                    for x in e.get(q, {}).get('risultati', [])[:10]:
                        if x['url'] not in candidate:
                            candidate.append(x['url'])
                if luogo:
                    voce['luogo'] = luogo[0]
                voce.update(categoria='altro', attesi=None, fonte='da giudicare', candidate=candidate)
            out.append(voce)
    dest = Path(o.esiti) / f'campione-livello2-{o.sito}.json'
    if dest.exists() and not o.sostituisci:
        sys.exit(f'{dest} esiste ed è congelato (decisione del 16/11): per rifarlo serve --sostituisci, da annotare nel piano')
    dest.write_text(json.dumps(out, ensure_ascii=False, indent=1), encoding='utf-8')
    from collections import Counter
    print(o.sito, len(out), 'ricerche;', dict(Counter(v['categoria'] for v in out)), dict(Counter(v['strato'] for v in out)))


_PAGINE = {}


def testo_pagina(url, cache):
    import re
    import urllib.request
    if url not in _PAGINE:
        if url in cache:
            _PAGINE[url] = cache[url]
        else:
            try:
                h = urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': 'kos-ricerca-prova'}),
                                           timeout=30).read().decode('utf-8', 'replace')
                h = re.sub(r'<script.*?</script>|<style.*?</style>', ' ', h, flags=re.S)
                h = ' '.join(m.norm(re.sub(r'<[^>]+>', ' ', h)))
            except Exception:
                h = ''
            _PAGINE[url] = cache[url] = h
    return _PAGINE[url]


def espandi(attesi, prov, sigle, citta, sito):
    out = []
    for a in attesi:
        if a.startswith('LUOGO:'):
            out += [u for u, s in sigle.items() if s in prov.get(' '.join(m.norm(a[6:])), set())]
            out += pagine_luogo(sito, a[6:], prov)
        elif a.startswith('CITTA:'):
            out += [u for u, c in citta.items() if m.norm(c) == m.norm(a[6:])]
        else:
            out.append(a)
    return out


def corrisponde(url, attesi, cache=None):
    import re
    for a in attesi:
        if a.startswith('TESTO:'):
            if url.endswith('.pdf'):
                continue
            if cache is not None and re.search(a[6:], testo_pagina(url, cache)):
                return True
        elif url == a or (a.endswith('*') and url.startswith(a[:-1])):
            return True
    return False


def criterio_luoghi_uniforme(camp, giudizi, ver, prov, sigle, punti, geo, mancanti, elenco):
    """Stesso criterio per tutte le forme, Yext compreso (decisione del 17/11): per le ricerche con luogo (giudicate,
    non riconosciute dallo script) è giusta una struttura della stessa provincia (regione, se il luogo è una regione)
    o, se lì non ce ne sono, la struttura più vicina («per regola»). I falsi allarmi dello script contano per quello
    che sono: nomi di struttura letti come luogo e nomi di strutture di altri escono dalle ricerche per luogo."""
    def per_luogo(nomi):
        sig = set()
        for n in nomi:
            sig |= prov.get(' '.join(m.norm(n)), set())
        dentro = sorted(u for u, s_ in sigle.items() if s_ in sig)
        if dentro:
            return dentro, False
        xy = [geo[' '.join(m.norm(n))] for n in nomi if ' '.join(m.norm(n)) in geo]
        if not xy:
            return [], False
        return [piu_vicine(xy[0], punti, 1)[0]], True
    for v in camp:
        q = v['ricerca']
        g = giudizi.get(q) or []
        segni = [a for a in g if a.startswith(('LUOGO:', 'CITTA:'))]
        if q in ver['nomi_falsi']:
            # nome di una struttura di altri riconosciuto come nostro: nessuna pagina giusta
            v['attesi'], v['tipo_vero'] = [], 'escluso'
            mancanti.setdefault('struttura di altri (falso allarme del riconoscimento dei nomi)', []).append(
                (q, v['ricerche']))
        elif v['categoria'] == 'luogo' and q in ver['luoghi_falsi']:
            if g:
                v['attesi'] = [a for a in g if not a.startswith('NESSUNA:')]
            else:  # nome di struttura letto come luogo (es. «casa sanremo»): la scheda con quel nome
                t = set(m.norm(q))
                v['attesi'] = [x['url'] for x in elenco if t <= set(x['token'])] or v['attesi']
            v['tipo_vero'] = 'struttura'
        elif v['categoria'] == 'luogo':
            v['attesi'], v['per_regola'] = per_luogo([v['luogo']])
            v['tipo_vero'] = 'luogo'
        elif segni:
            altri = [a for a in g if not a.startswith(('LUOGO:', 'CITTA:', 'NESSUNA:'))]
            luoghi, regola = per_luogo([a[6:] for a in segni])
            v['attesi'], v['per_regola'] = altri + luoghi, regola and not altri
            if not altri:
                v['tipo_vero'] = 'luogo'
        if q in ver.get('nomi_mancati', []) and v['tipo_vero'] == 'altro':
            v['tipo_vero'] = 'struttura'


def misura(o):
    camp = json.loads((Path(o.esiti) / f'campione-livello2-{o.sito}.json').read_text(encoding='utf-8'))
    gf = Path(o.esiti) / f'giudizi-livello2-{o.sito}.json'
    giudizi = json.loads(gf.read_text(encoding='utf-8')) if gf.exists() else {}
    prov = province(o.comuni)
    sigle = sigle_strutture(o.sito)
    citta = {x['url']: x['citta'] for x in m.strutture(o.sito)}
    mancanti = {}
    for v in camp:
        v.setdefault('meta', meta(v['ricerca']))
        if v['attesi'] is None and v['ricerca'] in giudizi:
            g = giudizi[v['ricerca']]
            if g and g[0].startswith('NESSUNA:'):
                v['attesi'] = []
                mancanti.setdefault(g[0][8:], []).append((v['ricerca'], v['ricerche']))
                continue
            v['attesi'], v['fonte'] = espandi(g, prov, sigle, citta, o.sito), 'giudizio'
            if g and not v['attesi']:
                v['attesi'] = [ELENCO[o.sito]]  # luogo senza strutture: l'elenco
    cf = Path(o.esiti) / 'pagine-cache.json'
    cache = json.loads(cf.read_text(encoding='utf-8')) if cf.exists() else {}
    geo = geografia(o.comuni, o.geo) if getattr(o, 'geo', None) else None
    punti = {}
    if geo:
        for x in m.strutture(o.sito):
            if x.get('lat') and x.get('lng'):
                punti[x['url']] = (float(x['lat']), float(x['lng']))
            elif x['url'] in COORD_ALTRE:
                punti[x['url']] = COORD_ALTRE[x['url']]
            elif ' '.join(m.norm(x['citta'])) in geo:
                punti[x['url']] = geo[' '.join(m.norm(x['citta']))]
    vf = Path(o.esiti) / f'verita-riconoscimento-{o.sito}.json'
    ver = json.loads(vf.read_text(encoding='utf-8')) if vf.exists() else None
    for v in camp:
        v['tipo_vero'] = v['categoria']
    if geo and ver:
        criterio_luoghi_uniforme(camp, giudizi, ver, prov, sigle, punti, geo, mancanti, m.strutture(o.sito))
    valide = [v for v in camp if v['attesi']]
    rapporto = {'sito': o.sito, 'campione': len(camp), 'giudicate': len(valide),
                'da_giudicare': sum(1 for v in camp if v['attesi'] is None),
                'senza_pagina_giusta': {k: {'ricerche': len(x), 'volte': sum(n for _, n in x)}
                                        for k, x in sorted(mancanti.items())}}
    (Path(o.esiti) / f'senza-pagina-giusta-{o.sito}.json').write_text(
        json.dumps(mancanti, ensure_ascii=False, indent=1), encoding='utf-8')
    ff = Path(o.esiti) / f'giudizi-faq-yext-{o.sito}.json'
    faq = json.loads(ff.read_text(encoding='utf-8')) if ff.exists() else {}
    for forma, motore, riconosci in FORME:
        if riconosci == 'completa' and not geo:
            continue
        es = esiti(o.esiti, motore, o.sito)
        if not es and riconosci == 'completa':
            # anniazzurri.it non ha l'archivio delle schede AA: la forma completa usa la sua app
            motore = 'vertex'
            es = esiti(o.esiti, motore, o.sito)
        if not es:
            continue
        righe = []
        for v in valide:
            if v['ricerca'] not in es:
                continue
            ris = es[v['ricerca']]['risultati']
            if motore == 'yext':
                ris = risultati_yext(ris, forma, faq.get(v['ricerca'], False))
            luogo = v.get('luogo') if v['categoria'] != 'struttura' else None
            if riconosci == 'completa' and luogo and luogo in geo:
                # primo risultato: l'elenco delle strutture più vicine al luogo RICONOSCIUTO dallo script (anche se
                # è un falso allarme); giusto se tra le prime tre per distanza c'è una struttura attesa
                vicine = piu_vicine(geo[luogo], punti)
                giusto = any(corrisponde(u, v['attesi'], cache) for u in vicine)
                ris = [{'url': 'ELENCO-GIUSTO' if giusto else 'ELENCO', 'titolo': ''}] + ris
            elif riconosci:
                ris = m.componi(v['attesi'] if v['categoria'] == 'struttura' else None, ris)
            righe.append((v, any(x['url'] in ('FAQ-GIUSTA', 'ELENCO-GIUSTO') or corrisponde(x['url'], v['attesi'], cache)
                                 for x in ris[:3])))

        def quota(sel, pesata):
            n = sum((v['ricerche'] if pesata else 1) for v, _ in sel)
            k = sum((v['ricerche'] if pesata else 1) for v, ok in sel if ok)
            return round(k / n * 100, 1) if n else None
        lunghe = [x for x in righe if x[0]['parole'] >= 4]
        lunghe_senza_nomi = [x for x in lunghe if x[0]['categoria'] != 'struttura']
        rapporto[forma] = {'ricerche': len(righe), 'pesata': quota(righe, True), 'non_pesata': quota(righe, False),
                           'da_4_parole_pesata': quota(lunghe, True), 'da_4_parole_non_pesata': quota(lunghe, False),
                           'da_4_parole': {'ricerche': len(lunghe), 'nomi_di_struttura': len(lunghe) - len(lunghe_senza_nomi),
                                           'residenza_anni_azzurri': sum(1 for x in lunghe if x[0]['ricerca'].startswith(
                                               ('residenza anni azzurri', 'residenza anni azurri', 'residenza anni azzurrii'))),
                                           'senza_nomi_pesata': quota(lunghe_senza_nomi, True),
                                           'senza_nomi_non_pesata': quota(lunghe_senza_nomi, False)},
                           'per_meta_pesata': {k: quota([x for x in righe if x[0]['meta'] == k], True)
                                               for k in ('definizione', 'verifica')},
                           'per_categoria_pesata': {c: quota([x for x in righe if x[0]['tipo_vero'] == c], True)
                                                    for c in ('struttura', 'luogo', 'altro')},
                           'luoghi_senza_per_regola_pesata': quota([x for x in righe if x[0]['tipo_vero'] == 'luogo'
                                                                     and not x[0].get('per_regola')], True),
                           'luoghi_per_regola': {'ricerche': sum(1 for x in righe if x[0].get('per_regola')),
                                                 'giuste': sum(1 for x in righe if x[0].get('per_regola') and x[1])}}
    if ver:
        # quarto indicatore: riconoscimento di nomi e luoghi sulle ricerche reali del campione, contro il giudizio a mano
        # ({"nomi_falsi": [...], "nomi_mancati": [...], "luoghi_falsi": [...], "luoghi_mancati": [...]})
        nel = {v['ricerca'] for v in camp}
        def conta(riconosciute, falsi, mancati):
            falsi = [q for q in falsi if q in nel]
            mancati = [q for q in mancati if q in nel]
            giusti = len(riconosciute) - len(falsi)
            return {'riconosciute': len(riconosciute), 'falsi_allarmi': len(falsi), 'non_riconosciute': len(mancati),
                    'precisione': round(giusti / len(riconosciute) * 100, 1) if riconosciute else None,
                    'copertura': round(giusti / (giusti + len(mancati)) * 100, 1) if giusti + len(mancati) else None}
        rapporto['riconoscimento'] = {
            'nomi': conta([v for v in camp if v['categoria'] == 'struttura'], ver['nomi_falsi'], ver['nomi_mancati']),
            'luoghi': conta([v for v in camp if v['categoria'] == 'luogo' or v.get('luogo')], ver['luoghi_falsi'],
                            ver['luoghi_mancati'])}
    cf.write_text(json.dumps(cache, ensure_ascii=False), encoding='utf-8')
    print(json.dumps(rapporto, ensure_ascii=False, indent=1))


def main():
    a = argparse.ArgumentParser()
    sub = a.add_subparsers(dest='cmd', required=True)
    for nome in ('campione', 'misura'):
        p = sub.add_parser(nome)
        p.add_argument('--sito', required=True, choices=list(m.APP))
        p.add_argument('--ricerche', required=True)
        p.add_argument('--esiti', required=True)
        p.add_argument('--comuni', required=True)
        if nome == 'misura':
            p.add_argument('--geo', help='comuni con coordinate (italy_geo.json): abilita la forma completa')
        if nome == 'campione':
            p.add_argument('--vocabolario', required=True)
            p.add_argument('--sostituisci', action='store_true')
    o = a.parse_args()
    (campione if o.cmd == 'campione' else misura)(o)


if __name__ == '__main__':
    sys.exit(main())
