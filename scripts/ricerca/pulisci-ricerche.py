#!/usr/bin/env python3
"""Ripulisce dai dati personali le ricerche esportate da Yext, prima di inviarle a Vertex AI Search o a Yext.

Uso:
  python3 -I scripts/ricerca/pulisci-ricerche.py --sito kosgroup --csv <export Yext 12 mesi> \
      --manuale <correzioni.json> --uscita <cartella>

Regole (registro delle decisioni, 16/11/2026: «tutte le ricerche distinte dei 12 mesi, ripulite dai dati personali»):
  1. escluse: email, codici fiscali, numeri di telefono, date, codici alfanumerici da 6 caratteri in su con almeno due cifre
     (codici di prenotazione o di pratica), nomi di persona che non corrispondono a una pagina pubblica dei siti;
  2. tolte dal testo: età («83 anni», «classe 1936», «nato nel»);
  3. riscritte a mano: frasi con nomi, indirizzi o dettagli di vita privata (es. vedovanza, figli all'estero), tenendo
     il bisogno espresso, i refusi e la città; le riscritture sono nel file --manuale.

Il file --manuale e l'elenco dei nomi contengono i testi originali: stanno fuori dal repository
(sul PC di Danilo e nell'area di lavoro), mai in git. Qui solo le regole e i conteggi.

Formato di --manuale: {"riscrivi": {"testo originale": "testo ripulito"}, "escludi": ["testo", ...]}
Uscita: <cartella>/ricerche-<sito>.csv (ricerca ripulita, ricerche, clic, tasso di risultato Yext, regola applicata)
e il riepilogo dei conteggi per regola su stdout.
"""
import argparse
import csv
import json
import re
import sys
from collections import Counter
from pathlib import Path

ESCLUDI = [
    ('email', re.compile(r'@|\b(gmail|libero|hotmail|yahoo|alice|virgilio|tiscali|outlook)\b')),
    ('codice fiscale', re.compile(r'\b[a-z]{6}\d{2}[a-z]\d{2}[a-z]\d{3}[a-z]\b')),
    ('telefono', re.compile(r'(\+?39)?\s?\b3\d{2}[\s.]?\d{6,7}\b|\b0\d{1,3}[\s.]?\d{5,8}\b|\b\d{8,}\b')),
    ('data', re.compile(r'\b\d{1,2}[/.-]\d{1,2}[/.-]\d{2,4}\b')),
    ('codice', re.compile(r'^(?=(?:[a-z]*\d){2})(?=[a-z0-9]*[a-z])[a-z0-9]{6,}$')),
]
ETA = re.compile(r'\b(di\s+|ha\s+)?\d{2,3}\s*anni\b|\bclasse\s*19\d\d\b|\bnat[oa]\s+nel\s+19\d\d\b')


def leggi_export(percorso):
    with open(percorso, encoding='utf-8-sig', newline='') as f:
        righe = list(csv.reader(f))
    for r in righe[1:]:
        if len(r) < 6 or not r[0].strip():
            continue
        yield {
            'q': r[0].strip(),
            'ricerche': int(r[1].replace('.', '').replace(',', '')),
            'clic': int(r[3].replace('.', '').replace(',', '')),
            'risultato_yext': r[5].strip(),
        }


def pulisci(q, manuale, nomi):
    s = q.lower().strip()
    if s in manuale.get('escludi', []):
        return None, 'esclusa a mano'
    if s in nomi:
        return None, 'nome di persona'
    if s in manuale.get('riscrivi', {}):
        return manuale['riscrivi'][s], 'riscritta a mano'
    for nome, rx in ESCLUDI:
        if rx.search(s):
            return None, nome
    nuovo = re.sub(r'\s{2,}', ' ', ETA.sub(' ', s)).strip()
    if nuovo != s:
        return nuovo, 'età tolta'
    return s, ''


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--sito', required=True, choices=['kosgroup', 'anniazzurri'])
    a.add_argument('--csv', required=True)
    a.add_argument('--manuale', required=True)
    a.add_argument('--uscita', required=True)
    o = a.parse_args()
    man = json.loads(Path(o.manuale).read_text(encoding='utf-8'))
    nomi = {n.lower() for n in man.get('nomi', [])}
    regole = Counter()
    ricerche_per_regola = Counter()
    fuori = []
    for r in leggi_export(o.csv):
        nuovo, regola = pulisci(r['q'], man, nomi)
        regole[regola or 'invariata'] += 1
        ricerche_per_regola[regola or 'invariata'] += r['ricerche']
        if nuovo is None:
            continue
        fuori.append({'ricerca': nuovo, 'ricerche': r['ricerche'], 'clic': r['clic'],
                      'risultato_yext': r['risultato_yext'], 'regola': regola})
    # riscritture e tagli possono far coincidere due ricerche: si sommano
    unite = {}
    for f in fuori:
        u = unite.setdefault(f['ricerca'], {**f, 'ricerche': 0, 'clic': 0})
        u['ricerche'] += f['ricerche']
        u['clic'] += f['clic']
    Path(o.uscita).mkdir(parents=True, exist_ok=True)
    dest = Path(o.uscita) / f'ricerche-{o.sito}.csv'
    with open(dest, 'w', encoding='utf-8', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['ricerca', 'ricerche', 'clic', 'risultato_yext', 'regola'])
        w.writeheader()
        for u in sorted(unite.values(), key=lambda x: -x['ricerche']):
            w.writerow(u)
    print(f'{o.sito}: {len(unite)} ricerche distinte ripulite -> {dest}')
    for k, v in regole.most_common():
        print(f'  {k}: {v} termini, {ricerche_per_regola[k]} ricerche')


if __name__ == '__main__':
    sys.exit(main())
