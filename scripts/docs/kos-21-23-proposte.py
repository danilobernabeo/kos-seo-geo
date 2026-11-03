#!/usr/bin/env python3
"""Proposte di testo per le voci d'audit KOS-21 (meta title), KOS-22 (meta description) e KOS-23 (H1) di kosgroup.com.

Uso (dalla radice del repository):
  python3 scripts/docs/kos-21-23-proposte.py [--data 2026-11-01]

Legge da kosgroup/dati/kos-21-23/:
  - ac7a9f06-202609_KosGroup_Dettaglio_Audit_GEO_Ant.xlsx  fogli «Meta-Title», «Meta-Description», «H1» (dati dalla riga 4, URL in C)
  - meta-stato-20261027.csv            stato servito e campi MODX per URL (separatore «;», UTF-8 BOM)
  - medici-description-proposte.json   description delle schede medici: alias → [testo, nota]
  - testi-proposti-<data>.json         testi proposti (chiave = URL; title, description, h1, introtext, note), schema delle
                                       convenzioni, verifica del 1/11 (redirect, H1 corretti) e segnalazioni
Scrive:
  - kos-21-23-proposte-<data>.xlsx     fogli «Proposte da validare», «Segnalazioni», «Riepilogo»
  - kos-21-23-campi-<data>.json        per scripts/deploy/aggiorna-campi.php (solo righe con un cambiamento)
e stampa i controlli: title ≤ 65, description 120-150 (eccezioni con nota), nessun duplicato residuo tra title e tra
description (proposti + invariati, pagine non in redirect del CSV), nessun H1 duplicato tra le pagine del foglio H1.

Come si formano i campi: <title> = longtitle così com'è (suffisso « | KOS» scritto nel longtitle, conta nella lunghezza);
meta description = campo description; H1 = pagetitle nei template informativo, medici, tecnologie, video e convenzioni
(<h1>[[*pagetitle]]</h1>), scritto a mano nel contenuto negli altri template (cure, generica, generica-en, strutture,
specializzazioni, progetti-speciali): quelle modifiche sono manuali e non vanno nel JSON.
"""
import csv, json, os, sys
from collections import Counter, defaultdict

import openpyxl
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.worksheet.datavalidation import DataValidation

DATA = sys.argv[sys.argv.index('--data') + 1] if '--data' in sys.argv else '2026-11-01'
RADICE = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DIR = os.path.join(RADICE, 'kosgroup', 'dati', 'kos-21-23')
AUDIT = os.path.join(DIR, 'ac7a9f06-202609_KosGroup_Dettaglio_Audit_GEO_Ant.xlsx')
STATO = os.path.join(DIR, 'meta-stato-20261027.csv')
MEDICI = os.path.join(DIR, 'medici-description-proposte.json')
TESTI = os.path.join(DIR, f'testi-proposti-{DATA}.json')
OUT_XLSX = os.path.join(DIR, f'kos-21-23-proposte-{DATA}.xlsx')
OUT_JSON = os.path.join(DIR, f'kos-21-23-campi-{DATA}.json')

T_MAX, D_MIN, D_MAX = 65, 120, 150
SUFFISSO = ' | KOS'
H1_DA_PAGETITLE = {'informativo', 'medici', 'tecnologie', 'video', 'convenzioni'}
NOTA_PAGETITLE = 'H1 = pagetitle: il pagetitle compare anche in menu, briciole (anche BreadcrumbList) ed elenchi'
NOTA_CONTENUTO = 'H1 scritto nel contenuto della risorsa (template {t}): modifica manuale nel campo content, non nel JSON'
INV_T, INV_D, INV_H = '(invariato)', '(invariata)', '(invariato)'

VOCI = {  # tipo nel foglio dell'audit → (campo, etichetta)
    'Meta-Title Lungo': ('title', 'Title lungo'), 'Meta-Title Duplicato': ('title', 'Title duplicato'),
    'Meta-Title Mancante': ('title', 'Title mancante'),
    'Description Lunga': ('description', 'Description lunga'), 'Description Mancante': ('description', 'Description mancante'),
    'Description Duplicata': ('description', 'Description duplicata'),
    'H1 Duplicato': ('h1', 'H1 duplicato'), 'H1 Mancante': ('h1', 'H1 mancante'), 'H1 Multiplo': ('h1', 'H1 multiplo'),
}
ORDINE_VOCI = [v[1] for v in VOCI.values()]


def leggi_audit():
    wb = openpyxl.load_workbook(AUDIT, read_only=True, data_only=True)
    voci, ordine = defaultdict(list), []
    for foglio in ('Meta-Title', 'Meta-Description', 'H1'):
        for r in wb[foglio].iter_rows(min_row=4, values_only=True):
            if not r or not r[2]:
                continue
            url, tipo = str(r[2]).strip(), str(r[1]).strip()
            if tipo not in VOCI:
                sys.exit(f'Tipo sconosciuto nel foglio {foglio}: {tipo}')
            if url not in voci:
                ordine.append(url)
            if VOCI[tipo] not in voci[url]:
                voci[url].append(VOCI[tipo])
    return voci, ordine


def main():
    voci, _ = leggi_audit()
    with open(STATO, encoding='utf-8-sig', newline='') as f:
        stato = {r['url']: r for r in csv.DictReader(f, delimiter=';')}
    medici = json.load(open(MEDICI, encoding='utf-8'))
    testi = json.load(open(TESTI, encoding='utf-8'))
    schemi = testi.get('_schemi', {})
    verifica = testi.get('_verifica_20261101', {})
    redirect = verifica.get('redirect', {})
    h1_corretti = verifica.get('h1_corretti', {})
    segnalazioni = testi.get('_segnalazioni', [])

    mancano = [u for u in voci if u not in stato]
    if mancano:
        sys.exit('URL dell\'audit assenti dal CSV: ' + ', '.join(mancano))
    ignoti = [u for u in testi if not u.startswith('_') and u not in stato]
    if ignoti:
        sys.exit('URL nei testi assenti dal CSV: ' + ', '.join(ignoti))

    righe, risorse, avvisi = [], [], []
    for url, s in stato.items():
        campi_voci = [c for c, _ in voci.get(url, [])]
        etichette = [e for _, e in voci.get(url, [])]
        tpl = s['template']
        t_att, d_att, h_att = s['title'], s['description'], s['H1']
        lt_att, dc_att, pt_att = s['longtitle'], s['description (campo)'], s['pagetitle']
        p = dict(testi.get(url, {}))
        note = [p.pop('note')] if p.get('note') else []
        rid = redirect.get(url) or (s['url finale'] if s['url finale'] and s['url finale'] != url else '')
        alias = url.rstrip('/').rsplit('/', 1)[-1]
        uri_dest = None

        # description delle schede medici dal file dedicato (i testi possono sovrascriverla)
        if tpl == 'medici' and alias in medici and 'description' not in p:
            testo, nota_m = medici[alias]
            if testo:
                p['description'] = testo
            if nota_m:
                note.append(nota_m)
            elif testo and not D_MIN <= len(testo) <= D_MAX:
                note.append(f'Testo dal file delle description medici ({len(testo)} caratteri), usato così com\'è: niente fatti da aggiungere')
            if testo and nota_m and not D_MIN <= len(testo) <= D_MAX:
                note.append(f'{len(testo)} caratteri, testo dal file delle description medici usato così com\'è')

        # convenzioni: schema unico con il nome dell'ente come nel title
        if tpl == 'convenzioni' and 'description' not in p and 'description' in campi_voci:
            nome = t_att[:-len(SUFFISSO)] if t_att.endswith(SUFFISSO) else t_att
            for modello in schemi.get('convenzioni', {}).get('description', []):
                testo = modello.replace('{nome}', nome)
                if D_MIN <= len(testo) <= D_MAX:
                    p['description'] = testo
                    break
            else:
                avvisi.append(f'convenzione senza schema adatto: {nome}')

        # redirect: nessun testo sull'URL dell'audit (salvo testo per la scheda di destinazione, medici)
        if rid:
            note.insert(0, f'301 verso {rid} (verifica 1/11)')
            if tpl == 'medici' and p.get('description') and s['id']:
                uri_dest = rid.replace('https://kosgroup.com/', '')
                note.append(f'description applicata alla scheda di destinazione (id {s["id"]})')
                p = {'description': p['description']}
            else:
                p = {}
        if url in h1_corretti:
            note.append(f'H1 già corretto (verifica 1/11): «{h1_corretti[url]}»')

        t_new = p.get('title')
        d_new = p.get('description')
        h_new = p.get('h1')
        if t_new == t_att:
            t_new = None
        if d_new == d_att:
            d_new = None
        if h_new == h_att:
            h_new = None

        campi_modx, json_campi = [], {}
        if t_new is not None:
            campi_modx.append('longtitle')
            json_campi['longtitle'] = {'prima': lt_att, 'dopo': t_new}
        if d_new is not None:
            campi_modx.append('description')
            json_campi['description'] = {'prima': dc_att, 'dopo': d_new}
        if h_new is not None:
            if tpl in H1_DA_PAGETITLE:
                campi_modx.append('pagetitle')
                json_campi['pagetitle'] = {'prima': pt_att, 'dopo': h_new}
                note.append(NOTA_PAGETITLE)
            else:
                campi_modx.append('content (H1)')
                note.append(NOTA_CONTENUTO.format(t=tpl or '?'))
        i_new = p.get('introtext')  # correzione del sommario visibile (approvata caso per caso)
        if i_new is not None and i_new != s['introtext']:
            campi_modx.append('introtext')
            json_campi['introtext'] = {'prima': s['introtext'], 'dopo': i_new}
            note.append('Introtext corretto (sommario visibile sotto il titolo)')

        fuori = [n for n, v in (('title', t_new), ('description', d_new), ('H1', h_new))
                 if v is not None and n.lower() not in campi_voci]
        voci_txt = '; '.join(etichette) + (('; ' if etichette else '') + 'fuori audit: ' + ', '.join(fuori) if fuori else '')

        if d_new is not None and not D_MIN <= len(d_new) <= D_MAX and not any('caratteri' in n for n in note):
            avvisi.append(f'description fuori {D_MIN}-{D_MAX} senza nota: {url} ({len(d_new)})')
        if t_new is not None and len(t_new) > T_MAX:
            avvisi.append(f'title oltre {T_MAX}: {url} ({len(t_new)})')

        righe.append({
            'id': s['id'], 'url': url, 'template': tpl, 'voci': voci_txt, 'audit': bool(etichette), 'rid': bool(rid),
            't_att': t_att, 't_new': t_new, 'd_att': d_att, 'd_new': d_new, 'h_att': h_att, 'h_new': h_new,
            'campi': ', '.join(campi_modx), 'note': '; '.join(dict.fromkeys(note)), 'campi_voci': campi_voci,
            't_fin': t_new if t_new is not None else t_att, 'd_fin': d_new if d_new is not None else d_att,
            'h_fin': h_new if h_new is not None else h1_corretti.get(url, h_att),
        })
        if json_campi and s['id']:
            # home EN: il CSV del 27/10 abbinava /en alla cartella 385 (uri «en/», senza template); la pagina servita
            # è la 990 (uri «en», template generica-en), verificato sullo stage il 3/11 (scripts/checks/home-contesti.php)
            rid_id = {'385': 990}.get(s['id'], int(s['id']))
            r = {'id': rid_id}
            uri = uri_dest or url.replace('https://kosgroup.com/', '')
            if s['id'] == '385':
                r['uri'] = 'en'
            elif s['id'] != '1':  # home IT: uri della pagina iniziale non affidabile, solo id
                r['uri'] = uri
            r['campi'] = json_campi
            risorse.append(r)

    righe.sort(key=lambda r: (r['template'] or '~', r['url']))
    righe_audit = [r for r in righe if r['audit']]

    # ---------- controlli ----------
    attive = [r for r in righe if not r['rid']]
    problemi = list(avvisi)
    for chiave, nome in (('t_fin', 'title'), ('d_fin', 'description')):
        cnt = Counter(r[chiave] for r in attive if r[chiave])
        for testo, n in cnt.items():
            if n > 1:
                urls = [r['url'] for r in attive if r[chiave] == testo]
                problemi.append(f'{nome} duplicato ({n}): «{testo[:60]}» → ' + ', '.join(u.replace('https://kosgroup.com', '') for u in urls))
    pag_h1 = {u for u, v in voci.items() if any(c == 'h1' for c, _ in v)}
    cnt = Counter(r['h_fin'] for r in attive if r['url'] in pag_h1 and r['h_fin'])
    for testo, n in cnt.items():
        if n > 1:
            problemi.append(f'H1 duplicato tra le pagine del foglio H1: «{testo}»')
    for r in attive:
        if r['url'] in pag_h1 and ' | ' in (r['h_fin'] or '') and r['url'] not in h1_corretti:
            problemi.append(f'H1 multiplo residuo: {r["url"]}')
    cnt_all = Counter(r['h_fin'] for r in attive if r['h_fin'])
    h1_dup_sito = sorted(t for t, n in cnt_all.items() if n > 1)

    # ---------- xlsx ----------
    wb = openpyxl.Workbook()
    blu = PatternFill('solid', fgColor='1F4E79')
    giallo = PatternFill('solid', fgColor='FFF2CC')
    bianco = Font(bold=True, color='FFFFFF')
    a_capo = Alignment(wrap_text=True, vertical='top')

    def intestazione(ws, titoli, larghezze):
        ws.append(titoli)
        for i, w in enumerate(larghezze, 1):
            c = ws.cell(row=1, column=i)
            c.fill, c.font, c.alignment = blu, bianco, Alignment(wrap_text=True, vertical='center')
            ws.column_dimensions[openpyxl.utils.get_column_letter(i)].width = w
        ws.freeze_panes = 'A2'

    ws = wb.active
    ws.title = 'Proposte da validare'
    intestazione(ws, ['ID risorsa', 'URL', 'Template', 'Voci audit', 'Title attuale', 'Title proposto', 'Len',
                      'Description attuale', 'Description proposta', 'Len', 'H1 attuale', 'H1 proposto',
                      'Campo MODX da cambiare', 'Note', 'Validazione', 'Testo corretto'],
                 [8, 45, 13, 22, 40, 40, 6, 50, 50, 6, 35, 35, 16, 45, 15, 40])
    for r in righe_audit:
        ws.append([
            int(r['id']) if r['id'] else None, r['url'], r['template'], r['voci'],
            r['t_att'], r['t_new'] if r['t_new'] is not None else INV_T, len(r['t_new']) if r['t_new'] is not None else None,
            r['d_att'], r['d_new'] if r['d_new'] is not None else INV_D, len(r['d_new']) if r['d_new'] is not None else None,
            r['h_att'], r['h_new'] if r['h_new'] is not None else INV_H,
            r['campi'], r['note'], None, None,
        ])
    n = ws.max_row
    for row in ws.iter_rows(min_row=2, max_row=n):
        for c in row:
            c.alignment = a_capo
        row[14].fill = giallo
    dv = DataValidation(type='list', formula1='"Approvato,Da modificare,Respinto"', allow_blank=True)
    ws.add_data_validation(dv)
    dv.add(f'O2:O{n}')
    ws.auto_filter.ref = f'A1:P{n}'

    ws2 = wb.create_sheet('Segnalazioni')
    intestazione(ws2, ['Punto', 'Dettaglio', 'Proposta'], [32, 80, 60])
    for s in segnalazioni:
        ws2.append(s)
    for row in ws2.iter_rows(min_row=2):
        for c in row:
            c.alignment = a_capo

    # riepilogo
    ws3 = wb.create_sheet('Riepilogo')
    intestazione(ws3, ['Voce', 'Righe audit', 'Con proposta', 'Già risolte', 'In redirect (nessun testo sull’URL)', 'Invariate (motivo in Note)'],
                 [32, 12, 13, 12, 18, 18])
    campo_di = {e: c for c, e in VOCI.values()}
    for etich in ORDINE_VOCI:
        rr = [r for r in righe_audit if etich in r['voci'].split('; ')]
        campo = campo_di[etich]
        chiave = {'title': 't_new', 'description': 'd_new', 'h1': 'h_new'}[campo]
        con = sum(1 for r in rr if r[chiave] is not None)
        red = sum(1 for r in rr if r['rid'] and r[chiave] is None)
        risolte = sum(1 for r in rr if r[chiave] is None and not r['rid'] and (
            'superat' in r['note'] or 'già corretto' in r['note'] or 'già presenti' in r['note'] or 'già diversi' in r['note']))
        ws3.append([etich, len(rr), con, risolte, red, len(rr) - con - red - risolte])
    ws3.append([])
    ws3.append(['Pagine dell\'audit (righe del foglio)', len(righe_audit)])
    ws3.append(['Title proposti', sum(1 for r in righe_audit if r['t_new'] is not None),
                'di cui fuori audit', sum(1 for r in righe_audit if r['t_new'] is not None and 'title' not in r['campi_voci'])])
    ws3.append(['Description proposte', sum(1 for r in righe_audit if r['d_new'] is not None),
                'di cui fuori audit', sum(1 for r in righe_audit if r['d_new'] is not None and 'description' not in r['campi_voci'])])
    ws3.append(['H1 proposti', sum(1 for r in righe_audit if r['h_new'] is not None),
                'di cui nel contenuto (a mano)', sum(1 for r in righe_audit if r['h_new'] is not None and r['template'] not in H1_DA_PAGETITLE)])
    ws3.append(['Risorse nel JSON per aggiorna-campi.php', len(risorse)])
    ws3.append([])
    regole = [
        ('Title', f'Massimo {T_MAX} caratteri compreso il suffisso «{SUFFISSO.strip()}» (scritto nel longtitle, che diventa il <title> così com\'è); argomento principale, struttura o città se pertinenti; niente maiuscole a ogni parola né clickbait. Limite dell\'audit: 65-70.'),
        ('Description', f'Tra {D_MIN} e {D_MAX} caratteri (limite dell\'audit: 150), frase completa e informativa (chi, cosa, dove), utile anche come riassunto per gli assistenti AI; niente «Scopri…» ripetuto, punti esclamativi o formule vuote.'),
        ('H1', 'Uno per pagina; nei duplicati cambia la pagina meno importante (approfondimento rispetto al servizio, evento rispetto alla news, video rispetto alla news, versione italiana rispetto all\'inglese quando sono traduzioni).'),
        ('Campi MODX', 'title → longtitle; description → description; H1 → pagetitle nei template informativo, medici, tecnologie, video, convenzioni; negli altri template l\'H1 è nel contenuto (modifica manuale).'),
        ('Lunghezze', 'Caratteri Unicode (un carattere accentato o un apostrofo tipografico contano 1).'),
        ('Duplicati', 'Controllati su tutte le pagine del CSV del 27/10 non in redirect: title e description proposti più quelli invariati; H1 tra le pagine del foglio H1.'),
        ('Fatti', 'Solo informazioni presenti in title, description, pagetitle, introtext e URL attuali; dove mancano, proposta prudente con «da verificare sul testo della pagina» in Note.'),
        ('Fonti', 'Audit KosGroup settembre 2026 (fogli Meta-Title, Meta-Description, H1); stato del sito al 27/10 (meta-stato-20261027.csv) verificato il 1/11.'),
    ]
    for a, b in regole:
        ws3.append([a, b])
        ws3.cell(row=ws3.max_row, column=2).alignment = a_capo
        ws3.merge_cells(start_row=ws3.max_row, start_column=2, end_row=ws3.max_row, end_column=6)
        ws3.row_dimensions[ws3.max_row].height = 45
    for row in ws3.iter_rows(min_row=2):
        row[0].alignment = a_capo
    wb.save(OUT_XLSX)

    # ---------- json ----------
    man = {
        'nome': f'KOS-21/22/23: title (longtitle, con « | KOS»), description e H1 (pagetitle) da validare — proposte del {DATA}. '
                'Valori «prima» dall\'istantanea di produzione del 27/10 (meta-stato-20261027.csv), verificata il 1/11. '
                'Gli H1 scritti nel contenuto non sono qui (modifica manuale).',
        'risorse': risorse,
    }
    with open(OUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(man, f, ensure_ascii=False, indent=1)
        f.write('\n')

    # ---------- resoconto ----------
    print(f'Pagine dell\'audit: {len(righe_audit)} (CSV: {len(righe)})')
    print(f'Title proposti: {sum(1 for r in righe_audit if r["t_new"] is not None)}; '
          f'description: {sum(1 for r in righe_audit if r["d_new"] is not None)}; '
          f'H1: {sum(1 for r in righe_audit if r["h_new"] is not None)}; risorse nel JSON: {len(risorse)}')
    eccezioni = [r for r in righe_audit if r['d_new'] is not None and not D_MIN <= len(r['d_new']) <= D_MAX]
    print(f'Description fuori {D_MIN}-{D_MAX} con nota: {len(eccezioni)}')
    for r in eccezioni:
        print(f'   {len(r["d_new"])}  {r["url"].replace("https://kosgroup.com", "")}')
    if h1_dup_sito:
        print(f'H1 ripetuti sul sito fuori dal foglio H1 (informativo): {len(h1_dup_sito)} — ' + '; '.join(h1_dup_sito[:5]))
    print('Controlli: ' + ('nessun problema' if not problemi else f'{len(problemi)} problemi'))
    for p in problemi:
        print('  ! ' + p)
    print(f'Scritti: {os.path.relpath(OUT_XLSX, RADICE)}, {os.path.relpath(OUT_JSON, RADICE)}')
    return 1 if problemi else 0


if __name__ == '__main__':
    sys.exit(main())
