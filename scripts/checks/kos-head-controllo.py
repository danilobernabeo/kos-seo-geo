#!/usr/bin/env python3
"""
KOS-2 voce 24 — controllo degli head di kosgroup.com (consolidamento in un chunk unico). Solo libreria standard.

Uso (PowerShell, dalla radice del repository; la password dello stage solo nella variabile AUTH, mai nei file):
    $env:AUTH = "utente:password"
    py scripts/checks/kos-head-controllo.py --base https://<stage>.modx.dev --pagine pagine.txt [--confronta https://kosgroup.com]

pagine.txt: un percorso per riga (es. «/it/strutture/villa-dei-pini»), «#» per i commenti; si genera sul cloud con
    php scripts/checks/risorse-per-template.php /www --campione > pagine.txt

Per ogni pagina controlla:
  - un solo <title>, una sola description non vuota, al più un robots;
  - canonical: uno solo, assoluto, forma https://kosgroup.com/... (nessuno sulle pagine 30-34 paginate: avviso);
  - hreflang: o nessuno o la terna it/en/x-default, con href assoluti non vuoti; reciprocità (la pagina «en» dichiara
    a sua volta questa pagina come «it»), verificata se la pagina tradotta è nel campione;
  - og: site_name, locale, type, title, description, url, image con width/height/type; og:image raggiungibile (HEAD);
  - nessun tag MODX non risolto ([[ ... ]]) e nessun ?v= scritto a mano su custom.css (lo mette AssetVersion);
  - con --confronta: stessi title, description, canonical, og:title/og:description della produzione (differenze come
    avviso, perché alcune sono volute: ripiego della description og, hreflang corretti).
Esito: ERRORE (da correggere prima del rilascio), AVVISO (da guardare). Codice di uscita 1 se c'è almeno un errore.
"""
import argparse
import base64
import html.parser
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

CANONICO = 'https://kosgroup.com/'


class Head(html.parser.HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.dentro = False
        self.titoli, self.meta, self.link = [], [], []
        self._t = None

    def handle_starttag(self, tag, attrs):
        a = {k: (v or '') for k, v in attrs}
        if tag == 'head':
            self.dentro = True
        if not self.dentro:
            return
        if tag == 'title':
            self._t = ''
        elif tag == 'meta':
            self.meta.append(a)
        elif tag == 'link':
            self.link.append(a)

    def handle_data(self, d):
        if self._t is not None:
            self._t += d

    def handle_endtag(self, tag):
        if tag == 'title' and self._t is not None:
            self.titoli.append(self._t.strip())
            self._t = None
        if tag == 'head':
            self.dentro = False

    def m(self, chiave, valore):
        return [x.get('content', '') for x in self.meta if x.get(chiave, '').lower() == valore]


def apri(url, auth, metodo='GET'):
    req = urllib.request.Request(url, method=metodo, headers={'User-Agent': 'kos-head-controllo/1.0'})
    if auth:
        req.add_header('Authorization', 'Basic ' + base64.b64encode(auth.encode()).decode())
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return r.status, r.read().decode('utf-8', 'replace') if metodo == 'GET' else ''
    except urllib.error.HTTPError as e:
        return e.code, ''
    except Exception as e:                                   # rete, certificato
        return 0, str(e)


def analizza(testo):
    p = Head()
    p.feed(testo)
    return p


def campi(p):
    alt = {x.get('hreflang', '').lower(): x.get('href', '') for x in p.link if x.get('rel', '').lower() == 'alternate'}
    return {
        'title': p.titoli[0] if p.titoli else '',
        'description': (p.m('name', 'description') or [''])[0],
        'canonical': [x.get('href', '') for x in p.link if x.get('rel', '').lower() == 'canonical'],
        'hreflang': alt,
        'og:title': (p.m('property', 'og:title') or [''])[0],
        'og:description': (p.m('property', 'og:description') or [''])[0],
    }


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--base', required=True, help='sito da controllare (stage)')
    a.add_argument('--pagine', required=True)
    a.add_argument('--confronta', help='sito di confronto (produzione)')
    o = a.parse_args()
    auth = os.environ.get('AUTH', '')
    base = o.base.rstrip('/')
    percorsi = []
    grezzo = open(o.pagine, 'rb').read()
    # il redirect «>» di Windows PowerShell 5 scrive in UTF-16 con BOM; PowerShell 7 e gli altri in UTF-8
    testo_pagine = grezzo.decode('utf-16') if grezzo[:2] in (b'\xff\xfe', b'\xfe\xff') else grezzo.decode('utf-8-sig')
    for r in testo_pagine.splitlines():
        r = r.split('#', 1)[0].strip()
        if r:
            percorsi.append(r if r.startswith('/') else '/' + r)

    risultati, errori, avvisi, immagini = {}, 0, 0, {}
    for percorso in percorsi:
        stato, testo = apri(base + percorso, auth)
        msg = []
        if stato != 200:
            print(f'\n{percorso}\n  ERRORE risposta {stato} {testo[:80] if stato == 0 else ""}')
            errori += 1
            continue
        h = testo.split('</head>', 1)[0]
        p = analizza(h + '</head>')
        c = campi(p)
        risultati[percorso] = c
        E = lambda s: msg.append('ERRORE ' + s)
        W = lambda s: msg.append('AVVISO ' + s)

        if len(p.titoli) != 1 or not c['title']:
            E(f'<title>: {len(p.titoli)} trovati')
        desc = p.m('name', 'description')
        if len(desc) != 1:
            E(f'description: {len(desc)} trovate')
        elif not desc[0].strip():
            W('description vuota')
        if len(p.m('name', 'robots')) > 1:
            E('più di un meta robots')

        can = c['canonical']
        if len(can) > 1:
            E(f'{len(can)} canonical')
        elif not can:
            W('nessun canonical (corretto solo sulle pagine elenco paginate 30-34)')
        elif not can[0].startswith(CANONICO):
            E(f'canonical non nella forma {CANONICO}...: {can[0]}')

        alt = c['hreflang']
        if alt:
            if set(alt) != {'it', 'en', 'x-default'}:
                E(f'hreflang incompleti: {sorted(alt)}')
            for k, v in alt.items():
                if not v.startswith('http'):
                    E(f'hreflang {k} con href non assoluto o vuoto: «{v}»')
            if alt.get('x-default') != alt.get('it'):
                W('x-default diverso dalla versione italiana')

        og = {k: p.m('property', 'og:' + k) for k in
              ['site_name', 'locale', 'type', 'title', 'description', 'url', 'image', 'image:width', 'image:height', 'image:type']}
        for k, v in og.items():
            if len(v) != 1 or not v[0].strip():
                (W if k == 'description' else E)(f'og:{k}: {len(v)} trovati' + (' (vuoto)' if v and not v[0].strip() else ''))
        if og['image'] and og['image'][0]:
            u = og['image'][0]
            if u not in immagini:
                immagini[u] = apri(u, '', 'HEAD')[0]
            if immagini[u] != 200:
                E(f'og:image risponde {immagini[u]}: {u}')
        if og['locale'] and og['locale'][0] not in ('it_IT', 'en_GB'):
            E(f'og:locale {og["locale"][0]}')

        if '[[' in h or ']]' in h:
            E('tag MODX non risolto nell\'head: ' + re.search(r'.{0,40}(\[\[|\]\]).{0,40}', h).group(0).strip())
        if re.search(r'custom\.css\?v=20\d{6}', h):
            W('custom.css con versione scritta a mano')

        if o.confronta:
            s2, t2 = apri(o.confronta.rstrip('/') + percorso, '')
            if s2 == 200:
                c2 = campi(analizza(t2.split('</head>', 1)[0] + '</head>'))
                for k in ['title', 'description', 'canonical', 'og:title', 'og:description']:
                    if c[k] != c2[k]:
                        W(f'{k} diverso dalla produzione:\n        prod:  {c2[k]}\n        stage: {c[k]}')
            else:
                W(f'confronto: la produzione risponde {s2}')

        errori += sum(m.startswith('ERRORE') for m in msg)
        avvisi += sum(m.startswith('AVVISO') for m in msg)
        print(f'\n{percorso}  ({c["title"][:60]})' + ('' if msg else '  ok'))
        for m in msg:
            print('  ' + m)

    # reciprocità degli hreflang (solo tra pagine del campione)
    per_url = {}
    for perc, c in risultati.items():
        for u in c['canonical'][:1]:
            per_url[urllib.parse.urlsplit(u).path.rstrip('/')] = perc
    print('\n== Reciprocità hreflang')
    for perc, c in risultati.items():
        alt = c['hreflang']
        if not alt:
            continue
        for lingua, altra in (('it', 'en'), ('en', 'it')):
            q = per_url.get(urllib.parse.urlsplit(alt.get(altra, '')).path.rstrip('/'))
            if q and q != perc and risultati[q]['hreflang'].get(lingua) != alt.get(lingua):
                print(f'  ERRORE {perc}: la pagina «{altra}» {q} non la dichiara come «{lingua}»')
                errori += 1
    print(f'\n== {len(percorsi)} pagine, {errori} errori, {avvisi} avvisi')
    sys.exit(1 if errori else 0)


if __name__ == '__main__':
    main()
