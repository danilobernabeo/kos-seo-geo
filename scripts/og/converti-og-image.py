#!/usr/bin/env python3
"""
og:image di anniazzurri.it con lo snippet comune ogImage (shared/snippets/ogImage.php): immagine della pagina se esiste
davvero sul server, altrimenti l'immagine di condivisione del sito; dimensioni e tipo letti dal file.
Fuori perimetro (segnalazione dell'agenzia non quotata), stessa soluzione già in uso su kosgroup.com.

Uso (dalla radice del repository): python3 scripts/og/converti-og-image.py [--prova]
Parte dalla versione in anniazzurri/elements/ se esiste, altrimenti dalla baseline; scrive in anniazzurri/elements/.
"""
import os
import re
import sys

SITO = 'anniazzurri'
BASE = os.path.join(SITO, 'baseline')
OUT = os.path.join(SITO, 'elements')
PROVA = '--prova' in sys.argv
DEFAULT = 'assets/img/anni-azzurri-condivisione-social.jpg'

RIGA = re.compile(r'(?P<ind>[ \t]*)<meta property="og:image" content="(?P<c>[^"]*)">')
DIM = re.compile(r'[ \t]*<meta property="og:image:(?:width|height|type)" content="[^"]*">\r?\n')


def chiamata(c):
    tvs, legacy, versione = [], [], False
    c2 = re.sub(r'^(?:\[\[!?\+\+site_url\]\]|https://anniazzurri\.it/)', '', c)
    c2 = c2.replace('[[++assets_url]]', 'assets/')
    m = re.fullmatch(r'\[\[\*(\w+)\]\](\?v=.*)?', c2)
    if m:
        tv = m.group(1)
        tvs = [tv] if tv != 'newsimage' else ['newsImage']
        versione = bool(m.group(2))
    else:
        legacy = [c2]
    args = []
    if tvs:
        args.append(f"&tvs=`{','.join(tvs)}`")
    if legacy:
        args.append(f"&legacy=`{'|'.join(legacy)}`")
    args.append(f'&default=`{DEFAULT}`')
    args.append('&meta=`1`')
    if versione:
        args.append('&versione=`1`')
    return '[[ogImage? ' + ' '.join(args) + ']]'


def converti(t):
    if 'og:image"' not in t or '[[ogImage?' in t:
        return t, None
    m = RIGA.search(t)
    nuovo_tag = m.group('ind') + chiamata(m.group('c'))
    t = t[:m.start()] + nuovo_tag + t[m.end():]
    t = DIM.sub('', t)          # larghezza, altezza e tipo li scrive lo snippet leggendo il file
    return t, f"{m.group('c')}  →  {nuovo_tag.strip()}"


tot = 0
for cartella in ('chunks', 'templates'):
    for f in sorted(os.listdir(os.path.join(BASE, cartella))):
        if not f.endswith('.html'):
            continue
        p = os.path.join(OUT, cartella, f)
        if not os.path.exists(p):
            p = os.path.join(BASE, cartella, f)
        t = open(p, encoding='utf-8', newline='').read()
        nuovo, nota = converti(t)
        if not nota:
            continue
        tot += 1
        print(f'{cartella[:-1]} {f[:-5]}: {nota}')
        if not PROVA:
            os.makedirs(os.path.join(OUT, cartella), exist_ok=True)
            open(os.path.join(OUT, cartella, f), 'w', encoding='utf-8', newline='').write(nuovo)
print(f'== {tot} elementi' + (' (prova)' if PROVA else ''))
