#!/usr/bin/env python3
"""
Alt Text (audit AA slide 25) — aggiunge gli alt scritti a mano (anniazzurri/dati/alt-immagini.tsv) alle <img> dei
contenuti delle risorse che non ce l'hanno (alt assente o vuoto). Gli alt già presenti non si toccano.

Uso (dalla radice del repository), sull'esportazione di PRODUZIONE di scripts/checks/aa-esporta-contenuti.php:
    python3 scripts/immagini/applica-alt-contenuti.py <cartella aa-contenuti-...> <cartella di uscita> "<nome>" [--prova]
Esempio:
    python3 scripts/immagini/applica-alt-contenuti.py ~/aa-contenuti-20261023 anniazzurri/dati/alt-news-contenuti "Alt Text news"
Uscita: <id>.html + manifest.json per scripts/deploy/aggiorna-contenuti.php.
"""
import hashlib
import json
import os
import re
import sys
from urllib.parse import urlparse, unquote

ALT_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'anniazzurri', 'dati', 'alt-immagini.tsv')
args = [a for a in sys.argv[1:] if not a.startswith('--')]
if len(args) < 3:
    sys.exit(__doc__)
ESPORT, OUT, NOME = args[:3]
PROVA = '--prova' in sys.argv

ALT = {}
for r in open(ALT_FILE, encoding='utf-8'):
    if r.strip() and not r.startswith('#') and '\t' in r:
        p, a = r.rstrip('\n').split('\t', 1)
        ALT['/' + p.lstrip('/')] = a


def chiave(src):
    p = urlparse(src.strip()).path if re.match(r'^(https?:)?//', src.strip(), re.I) else src.strip()
    return '/' + unquote(re.sub(r'[?#].*$', '', p)).lstrip('/')


def html_attr(s):
    return s.replace('&', '&amp;').replace('"', '&quot;').replace('<', '&lt;').replace('>', '&gt;')


def applica(t):
    n = 0

    def s(m):
        nonlocal n
        tag = m.group(0)
        src = re.search(r'\bsrc\s*=\s*"([^"]*)"', tag, re.I)
        if not src or chiave(src.group(1)) not in ALT:
            return tag
        alt = re.search(r'\salt\s*=\s*"([^"]*)"', tag, re.I)
        if alt and alt.group(1).strip():
            return tag                                    # alt già scritto da chi ha inserito l'immagine: resta
        nuovo = f' alt="{html_attr(ALT[chiave(src.group(1))])}"'
        n += 1
        if alt:
            return tag.replace(alt.group(0), nuovo, 1)
        return re.sub(r'^<img\b', '<img' + nuovo, tag, count=1, flags=re.I)
    return re.sub(r'<img\b[^>]*>', s, t, flags=re.I), n


indice = json.load(open(os.path.join(ESPORT, 'indice.json'), encoding='utf-8'))
manifest, tot = [], 0
for r in indice:
    f = os.path.join(ESPORT, 'risorse', f"{r['id']}.html")
    t = open(f, encoding='utf-8', newline='').read()
    nuovo, n = applica(t)
    if not n:
        continue
    tot += n
    print(f"risorsa {r['id']} {r['pagetitle'][:70]}: {n} alt")
    manifest.append({'id': r['id'], 'pagetitle': r['pagetitle'], 'regole': {'ALT': n},
                     'sha1_prima': hashlib.sha1(t.encode('utf-8')).hexdigest(), 'file': f"{r['id']}.html"})
    if not PROVA:
        os.makedirs(OUT, exist_ok=True)
        open(os.path.join(OUT, f"{r['id']}.html"), 'w', encoding='utf-8', newline='').write(nuovo)
if not PROVA and manifest:
    json.dump({'nome': NOME, 'campo': 'content', 'risorse': manifest},
              open(os.path.join(OUT, 'manifest.json'), 'w', encoding='utf-8', newline=''), ensure_ascii=False, indent=1)
print(f'== {len(manifest)} contenuti, {tot} alt aggiunti')
