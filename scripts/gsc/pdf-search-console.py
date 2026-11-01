#!/usr/bin/env python3
"""
KOS-8 / AA-3 — Search Console per i PDF (passo 2 del brief docs/pdf/brief-pdf.md). SOLA LETTURA su BigQuery.

Per ogni PDF che compare nei risultati (tabella storico_url_impression, 14/6/2025-23/10/2026): impressioni, clic,
posizione media, primo e ultimo giorno, numero di query, le 3 query principali e quanta parte delle impressioni cade su
query per cui compare anche una pagina HTML del sito (con la pagina HTML che ha più impressioni per la query principale
del PDF e la sua posizione). Se «pagina_html_top_query» è vuota, per quella query il PDF è l'unico risultato del sito.

Si lancia sul PC (l'ambiente di lavoro cloud non raggiunge BigQuery), con le credenziali già create da
storico-search-console.py (%APPDATA%\\kos-seo-geo\\token-gsc.json) oppure con quelle di gcloud (application-default).

Uso (PowerShell, dalla radice del repository):
    py scripts/gsc/pdf-search-console.py --sito aa  --out C:\\percorso\\inventari-pdf
    py scripts/gsc/pdf-search-console.py --sito kos --out C:\\percorso\\inventari-pdf

Output: pdf-search-console-<sito>-<AAAAMMGG>.csv (separatore «;», UTF-8 con BOM). Il percorso è in chiaro (senza %xx),
come nell'inventario (scripts/checks/pdf-inventario.php), così i due file si uniscono sul percorso.

Attenzione: l'API di Search Console non include le query anonimizzate, quindi impressioni e clic per URL sono un minimo.
"""
import argparse
import csv
import datetime as dt
import os
import sys
import urllib.parse

SCOPES = ['https://www.googleapis.com/auth/bigquery']
SQL = """
WITH base AS (
  SELECT data_date, REGEXP_EXTRACT(url, r'^https?://[^/]+([^?#]*)') path,
         REGEXP_CONTAINS(url, r'(?i)\\.pdf($|[?#])') is_pdf, query, impressions imp, clicks clk, sum_position sp
  FROM `{progetto}.searchconsole_{sito}.storico_url_impression` WHERE query IS NOT NULL),
u AS (SELECT path, SUM(imp) imp, SUM(clk) clk, SUM(sp)/SUM(imp)+1 pos, MIN(data_date) primo_giorno, MAX(data_date) ultimo_giorno
      FROM base WHERE is_pdf GROUP BY path),
pq AS (SELECT path, query, SUM(imp) imp, SUM(clk) clk, SUM(sp)/SUM(imp)+1 pos FROM base WHERE is_pdf GROUP BY 1, 2),
hq AS (SELECT query, ARRAY_AGG(STRUCT(path, pos) ORDER BY imp DESC LIMIT 1)[OFFSET(0)] h
       FROM (SELECT query, path, SUM(imp) imp, SUM(sp)/SUM(imp)+1 pos FROM base WHERE NOT is_pdf GROUP BY 1, 2) GROUP BY query),
pj AS (SELECT pq.*, hq.h.path hpath, hq.h.pos hpos FROM pq LEFT JOIN hq USING (query)),
q AS (SELECT path, COUNT(*) n_query, SUM(IF(hpath IS NOT NULL, imp, 0)) imp_con_html,
      ARRAY_TO_STRING(ARRAY_AGG(FORMAT('%s (%d imp, %d clic, pos %.1f)', query, imp, clk, pos) ORDER BY imp DESC LIMIT 3), ' | ') top_query,
      ARRAY_AGG(STRUCT(hpath, hpos) ORDER BY imp DESC LIMIT 1)[OFFSET(0)] h_top
      FROM pj GROUP BY path)
SELECT u.path, u.imp, u.clk, u.pos, u.primo_giorno, u.ultimo_giorno, q.n_query, q.imp_con_html, q.top_query,
       q.h_top.hpath pagina_html_top_query, q.h_top.hpos pos_html
FROM u JOIN q USING (path) ORDER BY u.imp DESC
"""
COLONNE = ['percorso', 'impressioni', 'clic', 'ctr', 'posizione', 'primo_giorno', 'ultimo_giorno', 'n_query',
           'impressioni_su_query_con_pagina_html', 'top_3_query', 'pagina_html_top_query', 'posizione_html']


def riga(r):
    imp, clk = int(r['imp']), int(r['clk'])
    return [urllib.parse.unquote(r['path'] or ''), imp, clk, round(clk / imp, 4) if imp else '',
            round(r['pos'], 1) if r['pos'] is not None else '', r['primo_giorno'], r['ultimo_giorno'], r['n_query'],
            r['imp_con_html'], r['top_query'], urllib.parse.unquote(r['pagina_html_top_query'] or ''),
            round(r['pos_html'], 1) if r['pos_html'] is not None else '']


def credenziali():
    tok = os.path.join(os.environ.get('APPDATA') or os.path.expanduser('~'), 'kos-seo-geo', 'token-gsc.json')
    if os.path.exists(tok):
        from google.oauth2.credentials import Credentials
        from google.auth.transport.requests import Request
        cred = Credentials.from_authorized_user_file(tok, SCOPES)
        if not cred.valid and cred.refresh_token:
            cred.refresh(Request())
        return cred
    import google.auth
    return google.auth.default(scopes=SCOPES)[0]


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--sito', required=True, choices=['aa', 'kos'])
    a.add_argument('--out', default='.')
    a.add_argument('--progetto', default='kos-analytics-482819')
    a.add_argument('--localita', default='europe-west3')
    o = a.parse_args()
    from google.cloud import bigquery
    client = bigquery.Client(project=o.progetto, credentials=credenziali(), location=o.localita)
    righe = list(client.query(SQL.format(progetto=o.progetto, sito=o.sito)).result())
    os.makedirs(o.out, exist_ok=True)
    f = os.path.join(o.out, 'pdf-search-console-%s-%s.csv' % (o.sito, dt.date.today().strftime('%Y%m%d')))
    with open(f, 'w', encoding='utf-8-sig', newline='') as fh:
        w = csv.writer(fh, delimiter=';')
        w.writerow(COLONNE)
        for r in righe:
            w.writerow(riga(r))
    print('== %s: %d PDF con impressioni, %d impressioni, %d clic' % (
        o.sito, len(righe), sum(int(r['imp']) for r in righe), sum(int(r['clk']) for r in righe)))
    print('== Scritto: ' + f)


if __name__ == '__main__':
    sys.exit(main())
