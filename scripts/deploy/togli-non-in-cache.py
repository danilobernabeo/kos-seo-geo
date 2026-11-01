#!/usr/bin/env python3
"""Toglie il «!» (chiamata non in cache) dagli snippet indicati negli elementi di un sito e scrive il manifest del pacchetto.

Uso: python3 scripts/deploy/togli-non-in-cache.py <sito> <export-elements> <pacchetto> <nome1,nome2,...> [--escludi file1,file2]
  <sito>            kosgroup | anniazzurri
  <export-elements> cartella dell'export di produzione (scripts/export-elements.php), per trovare gli elementi
  <pacchetto>       nome del pacchetto: scrive <sito>/deploy/<pacchetto>.json

Parte dall'elemento del progetto se esiste (<sito>/elements/...), altrimenti dalla baseline; il risultato va in
<sito>/elements/. Nello stesso file toglie il «!» anche da buildWhereExclude e pdoField annidati, perché un tag non in
cache dentro le proprietà di un tag in cache arriverebbe allo snippet come testo. Stampa le righe cambiate.
"""
import json, os, re, sys
sito, exp, pacchetto, nomi = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4].split(',')
escludi = set(sys.argv[sys.argv.index('--escludi') + 1].split(',')) if '--escludi' in sys.argv else set()
annidati = ['buildWhereExclude', 'pdoField']
pat = re.compile(r'\[\[!(' + '|'.join(map(re.escape, nomi)) + r')\b')
pat2 = re.compile(r'\[\[!(' + '|'.join(annidati) + r')\b')
tipi = {'templates': 'template', 'chunks': 'chunk'}
elementi = []
for d, tipo in tipi.items():
    for f in sorted(os.listdir(os.path.join(exp, d))):
        nome = f.rsplit('.', 1)[0]
        if nome in escludi:
            continue
        src_el = f'{sito}/elements/{d}/{f}'
        src = src_el if os.path.exists(src_el) else f'{sito}/baseline/{d}/{f}'
        if not os.path.exists(src):
            continue
        t = open(src, encoding='utf-8').read().replace('\r\n', '\n')
        if not pat.search(t):
            continue
        nuovo = pat.sub(r'[[\1', t)
        nuovo = pat2.sub(r'[[\1', nuovo)
        print(f'## {d}/{f}' + ('' if src == src_el else '  (dalla baseline)'))
        for a, b in zip(t.split('\n'), nuovo.split('\n')):
            if a != b:
                print('   - ' + a.strip()[:110] + '\n   + ' + b.strip()[:110])
        os.makedirs(os.path.dirname(src_el), exist_ok=True)
        open(src_el, 'w', encoding='utf-8').write(nuovo)
        elementi.append({'type': tipo, 'name': nome, 'file': src_el, 'baseline': f'{sito}/baseline/{d}/{f}'})
man = f'{sito}/deploy/{pacchetto}.json'
json.dump({'nome': f'{pacchetto}: chiamate in cache per {", ".join(nomi)} (tempo di risposta, censimento del 31/10)',
           'elements': elementi}, open(man, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
print(f'== {len(elementi)} elementi → {man}')
