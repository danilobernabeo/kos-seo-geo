#!/usr/bin/env python3
"""AA-20 giro 2: pagine fuori audit (proposte del 27/10) -> foglio di validazione e JSON per aggiorna-campi.php.

Ingressi: anniazzurri/dati/aa-20/aa-20-title-description-2026-10-27.xlsx (foglio «Proposte da validare»),
anniazzurri/dati/aa-20/meta-stato-20261027.csv (campi MODX «prima»), facoltativo
anniazzurri/dati/aa-20/giro-2-validazione.json ({url: {"esito": "Approvato|Da modificare|Respinto",
"title": "...", "description": "..."}}) con le decisioni di Danilo.
Uscite: anniazzurri/dati/aa-20/aa-20-giro-2-2026-11-01.xlsx e anniazzurri/dati/aa-20/campi-giro-2.json
(nel JSON solo le righe approvate o, senza validazione, tutte con "da_validare": true).
Il title servito è longtitle + « | Anni Azzurri», salvo che il testo contenga già «Anni Azzurri» (snippet titoloPagina).
"""
import csv, json, os, sys
import openpyxl
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.worksheet.datavalidation import DataValidation

B = 'anniazzurri/dati/aa-20/'
SUF = ' | Anni Azzurri'
title_attuale_1_11 = json.load(open(B + 'title-serviti-20261101.json')) if os.path.exists(B + 'title-serviti-20261101.json') else {}
val = {k: x for k, x in json.load(open(B + 'giro-2-validazione.json')).items() if not k.startswith('_')} if os.path.exists(B + 'giro-2-validazione.json') else {}

def servito(lt):
    return lt if 'Anni Azzurri' in lt else lt + SUF

def longtitle(prop):
    return prop[:-len(SUF)] if prop.endswith(SUF) else prop

S = {r['url']: r for r in csv.DictReader(open(B + 'meta-stato-20261027.csv', encoding='utf-8-sig'), delimiter=';')}
ws = openpyxl.load_workbook(B + 'aa-20-title-description-2026-10-27.xlsx')['Proposte da validare']
righe, risorse, problemi = [], [], []
for r in ws.iter_rows(min_row=2, values_only=True):
    if not r[1]:
        continue
    rid, url, tpl, t_att, t_prop, _, d_att, d_prop, _ = r[:9]
    st = S[url]
    t_att = title_attuale_1_11.get(url, st['title'])
    v = val.get(url, {})
    if v.get('title'): t_prop = v['title']
    if v.get('description'): d_prop = v['description']
    campi = {}
    t_new = None
    if t_prop and t_prop not in ('(invariato)',):
        lt = longtitle(t_prop)
        t_new = servito(lt)
        if len(t_new) > 65: problemi.append(f'title oltre 65: {url} ({len(t_new)})')
        if lt != st['longtitle']:
            campi['longtitle'] = {'prima': st['longtitle'], 'dopo': lt}
    if d_prop and d_prop not in ('(invariata)',):
        if not 100 <= len(d_prop) <= 160: problemi.append(f'description {len(d_prop)} car.: {url}')
        if d_prop != st['description (campo)']:
            campi['description'] = {'prima': st['description (campo)'], 'dopo': d_prop}
    esito = v.get('esito', '')
    righe.append([int(rid), url, tpl, t_att, t_new or '(invariato)', len(t_new) if t_new else None,
                  d_att, d_prop if campi.get('description') else '(invariata)', len(d_prop) if campi.get('description') else None,
                  esito, ''])
    # "approva": ["description"] approva solo quei campi; "esito": "Approvato" li approva tutti
    approvati = set(v.get('approva', [])) | ({'longtitle', 'description'} if esito == 'Approvato' else set())
    if val:
        campi_ok = {c: x for c, x in campi.items() if c in approvati}
    else:
        campi_ok = campi
    if not esito and approvati: esito = 'Approvato: ' + ', '.join(sorted(c for c in approvati if c in campi))
    righe[-1][9] = esito
    if campi_ok and esito != 'Respinto':
        x = {'id': int(rid), 'uri': url.replace('https://anniazzurri.it/', ''), 'campi': campi_ok}
        if not val: x['da_validare'] = True
        risorse.append(x)

wb = openpyxl.Workbook(); w = wb.active; w.title = 'Proposte da validare'
w.append(['ID', 'URL', 'Template', 'Title servito (1/11)', 'Title proposto (servito)', 'Len', 'Description attuale',
          'Description proposta', 'Len', 'Validazione', 'Testo corretto'])
for x in righe: w.append(x)
for c in w[1]:
    c.font = Font(bold=True, color='FFFFFF'); c.fill = PatternFill('solid', fgColor='1F4E79')
for col, wd in zip('ABCDEFGHIJK', (7, 45, 16, 48, 48, 6, 55, 55, 6, 16, 40)):
    w.column_dimensions[col].width = wd
giallo = PatternFill('solid', fgColor='FFF2CC')
for row in w.iter_rows(min_row=2):
    for c in row: c.alignment = Alignment(wrap_text=True, vertical='top')
    row[9].fill = giallo; row[10].fill = giallo
dv = DataValidation(type='list', formula1='"Approvato,Da modificare,Respinto"', allow_blank=True)
w.add_data_validation(dv); dv.add(f'J2:J{w.max_row}')
w.freeze_panes = 'A2'
n = wb.create_sheet('Note')
for t in ['AA-20 giro 2: pagine fuori audit, proposte del 27/10 ricontrollate sul sito il 1/11 (tutte 200, description invariate).',
          'Dal 31/10 lo snippet titoloPagina non aggiunge « | Anni Azzurri» se il marchio è già nel testo: per 21 pagine il title servito è cambiato solo per questo.',
          'Colonna «Title proposto»: come sarà servito (longtitle + suffisso quando serve). Nel database si scrive il longtitle senza suffisso.',
          'Validazione: Approvato (si applica la proposta), Da modificare (scrivere il testo in «Testo corretto»), Respinto (resta com\'è).',
          'Rilascio: scripts/deploy/aggiorna-campi.php con anniazzurri/dati/aa-20/campi-giro-2.json, prima lo stage poi la produzione.']:
    n.append([t])
n.column_dimensions['A'].width = 140
wb.save(B + 'aa-20-giro-2-2026-11-01.xlsx')
nome = ('AA-20 giro 2: pagine fuori audit, proposte del 27/10 ricontrollate il 1/11; campi: '
        + ', '.join(sorted({c for r in risorse for c in r['campi']})) + ('' if val else ' — DA VALIDARE, non rilasciare')
        + '. Valori «prima» dall\'istantanea del 27/10 (meta-stato-20261027.csv); editedon conservato da aggiorna-campi.php.')
json.dump({'nome': nome, 'risorse': risorse},
          open(B + 'campi-giro-2.json', 'w'), ensure_ascii=False, indent=1)
print(f'righe {len(righe)}, risorse nel JSON {len(risorse)}, longtitle {sum("longtitle" in r["campi"] for r in risorse)}, '
      f'description {sum("description" in r["campi"] for r in risorse)}')
print('\n'.join(problemi) or 'controlli: nessun problema')
