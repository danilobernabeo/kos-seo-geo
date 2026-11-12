"""
Linea C - Prepara per BigQuery (dataset kos-analytics-482819.yext_kos, europe-west3) i CSV prodotti da
esporta-statistiche.ps1 e esporta-entita.ps1.

Uso: python3 -I scripts/yext/prepara-bigquery.py <cartella CSV Yext> <cartella di uscita>

- colonne con nomi stabili (data, entita, azione/origine/dispositivo/ricerca, valore);
- righe a zero omesse (Yext restituisce ogni tipo di azione per ogni giorno, anche a zero);
- impressioni divise in due file (prima e dopo il 2023-07-01) per stare sotto il limite di caricamento;
- entità: solo il tipo «location»; il campo «entita» (meta.id) è la chiave delle statistiche
  (ENTITY_IDS) e coincide con il codice negozio dei profili Google e con lo store.id RetailTune.
Tabelle e schema: scripts/yext/bigquery.sql.
"""
import csv
import os
import sys

src, dst = sys.argv[1], sys.argv[2]
os.makedirs(dst, exist_ok=True)


def leggi(nome):
    return csv.DictReader(open(os.path.join(src, nome), encoding='utf-8-sig'))


def scrivi(nome, intestazione):
    f = open(os.path.join(dst, nome), 'w', newline='', encoding='utf-8')
    w = csv.writer(f)
    w.writerow(intestazione)
    return w


def converti(nome, colonna_valore, campi, uscite):
    n = 0
    for r in leggi(nome):
        v = int(float(r[colonna_valore]))
        if v == 0:
            continue
        riga = [r[c] for c in campi] + [v]
        uscite(riga).writerow(riga)
        n += 1
    print(nome, n, 'righe non nulle')


a = scrivi('yext_azioni.csv', ['data', 'entita', 'azione', 'valore'])
converti('azioni.csv', 'LISTINGS_ACTIONS', ['DAYS', 'ENTITY_IDS', 'ACTION'], lambda r: a)

intest = ['data', 'entita', 'origine', 'dispositivo', 'valore']
i1, i2 = scrivi('yext_impressioni_1.csv', intest), scrivi('yext_impressioni_2.csv', intest)
converti('impressioni.csv', 'LISTINGS_IMPRESSIONS', ['DAYS', 'ENTITY_IDS', 'APP', 'DEVICE'],
         lambda r: i1 if r[0] < '2023-07-01' else i2)

s = scrivi('yext_ricerche.csv', ['mese', 'entita', 'ricerca', 'valore'])
converti('ricerche.csv', 'LISTINGS_GOOGLE_SEARCH_TERM_IMPRESSIONS', ['MONTHS', 'ENTITY_IDS', 'RAW_SEARCH_TERM'],
         lambda r: s)

e = scrivi('yext_entita.csv', ['entita', 'uid', 'nome', 'citta', 'provincia', 'chiusa'])
for r in leggi('entita.csv'):
    if r['tipo'] == 'location':
        e.writerow([r['entita'], r['uid'], r['nome'], r['citta'], r['provincia'], r['chiusa'].lower()])
