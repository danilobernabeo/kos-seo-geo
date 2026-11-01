#!/usr/bin/env python3
"""
KOS-8 / AA-3 — Foglio di classificazione dei PDF per la revisione di Danilo (passo 3 del brief docs/pdf/brief-pdf.md).

Unisce l'inventario dei due siti (scripts/checks/pdf-inventario.php), Search Console (scripts/gsc/pdf-search-console.py)
e le regole di classificazione (scripts/pdf/classifica_regole.py) in un solo xlsx:
    Riepilogo · AA · KOS · Dati personali · Senza file
Il foglio NON contiene il testo dei PDF (solo se l'inizio del testo ha tracce di dati personali, senza riportarle).

Uso (dalla radice del repository):
    py scripts/pdf/classifica-pdf.py --inv-aa A.csv --gsc-aa B.csv --inv-kos C.csv --gsc-kos D.csv --out classificazione.xlsx
        [--audit-aa 202604_AnniAzzurri_Audit_GEO_v2.2_Ant.xlsx] [--audit-kos 202609_KosGroup_Dettaglio_Audit_GEO_Ant.xlsx]

Decisioni proposte: TIENI (indicizzabile, con titolo nei metadati) · ESCLUDI (noindex) · DECIDI (serve una scelta).
Un PDF da ESCLUDI con molto traffico diventa DECIDI (soglie SOGLIA_CLIC / SOGLIA_IMP).
"""
import argparse
import collections
import csv
import json
import os
import re
import sys
import unicodedata
import urllib.parse

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from classifica_regole import classifica, norma  # noqa: E402

SOGLIA_CLIC = 20
SOGLIA_IMP = 5000
LUNGHEZZA_TITOLO = 65
ORDINE = {'DECIDI': 0, 'TIENI': 1, 'ESCLUDI': 2}
RADICE = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))

CF = re.compile(r'\b[A-Z]{6}\d{2}[A-EHLMPR-T]\d{2}[A-Z]\d{3}[A-Z]\b', re.I)
NATO = re.compile(r'\b(nato|nata)\s+(a|il)\b|\bresidente\s+(in|a)\b|\bIBAN\b', re.I)


def leggi_csv(f):
    with open(f, encoding='utf-8-sig', newline='') as fh:
        return list(csv.DictReader(fh, delimiter=';'))


def num(x):
    try:
        return int(float(x))
    except (TypeError, ValueError):
        return 0


def pulisci_nome(s):
    return re.sub(r'\s+', ' ', re.sub(r'[-_]+', ' ', s)).strip()


def maiuscola(s):
    return s[:1].upper() + s[1:]


def nomi_strutture(sito):
    """alias della cartella/pagina → (nome, fonte)."""
    nomi = {}
    if sito == 'aa':
        f = os.path.join(RADICE, 'anniazzurri/dati/aa-20/meta-stato-20261027.csv')
        for r in leggi_csv(f):
            alias = urllib.parse.urlparse(r['url']).path.strip('/').split('/')[-1]
            nome = (r.get('H1') or r.get('pagetitle') or '').strip()
            if alias and nome:
                nomi[alias] = (nome, 'H1 della pagina (27/10)')
    else:
        d = json.load(open(os.path.join(RADICE, 'kosgroup/dati/voce-24/strutture-yext-2026-10-25.json'), encoding='utf-8'))
        for r in d['risorse']:
            nome = r['tv'].get('str.nome', '').strip()
            if nome:
                nomi[r['uri'].strip('/').split('/')[-1]] = (nome, 'str.nome da Yext (25/10)')
    return nomi


def struttura(percorso, nomi):
    parti = percorso.strip('/').split('/')
    alias = ''
    if len(parti) > 4 and parti[2] == 'strutture':
        alias = parti[3]
    elif len(parti) > 3 and parti[2] not in ('comunicati', 'news', 'newsletter', 'eventi', 'cv'):
        alias = parti[2]
    if not alias:
        return '', '', ''
    if alias in nomi:
        return alias, nomi[alias][0], ''
    return alias, maiuscola(pulisci_nome(alias)), 'nome ricavato dall\'alias, da verificare'


def titolo(classe, nome_struttura, stem):
    """Titolo proposto per un PDF da tenere: «<titolo del documento> – <nome della struttura>»."""
    stem = pulisci_nome(stem)
    if classe == 'Carta dei servizi':
        sub = re.sub(r'^(carta (dei|degli|della|delle) (servizi|alloggi))\s*', '', stem, flags=re.I).strip()
        base = 'Carta dei servizi' + (' ' + sub if sub else '') if 'alloggi' not in norma(stem) else maiuscola(stem)
    elif classe == 'Pieghevole':
        base = 'Pieghevole informativo' if norma(stem) == 'pieghevole informativo' else maiuscola(stem)
    elif classe == 'Comunicato stampa':
        return 'da fare dalla risorsa MODX: «Comunicato stampa – <titolo> – <mese anno>»'
    else:
        base = maiuscola(stem)
    return base + (' – ' + nome_struttura if nome_struttura else '')


def pagine(r):
    elenco = [x for x in (r.get('pagine_elenco') or '').split(' | ') if x] + [x for x in (r.get('pagine_link') or '').split(' | ') if x]
    uri = []
    for x in elenco:
        m = re.match(r'^(?:\[\w+\] )?\d+ (/\S*)', x)
        uri.append(m.group(1) if m else x)
    uri = list(dict.fromkeys(uri))
    if not uri:
        return '', 0
    return uri[0] + (' (+%d)' % (len(uri) - 1) if len(uri) > 1 else ''), len(uri)


def elabora(sito, inv, gsc):
    nomi = nomi_strutture(sito)
    g = {r['percorso']: r for r in gsc}
    righe = []
    for r in inv:
        p = r['percorso']
        classe, sotto, dec, motivo = classifica(p, r.get('testo_pag1_inizio', ''))
        s = g.get(p, {})
        imp, clk = num(s.get('impressioni')), num(s.get('clic'))
        alias, nome, nota_nome = struttura(p, nomi)
        note = []
        if dec == 'ESCLUDI' and (clk >= SOGLIA_CLIC or imp >= SOGLIA_IMP):
            note.append('proposto ESCLUDI dalla regola, ma ha traffico: %d clic, %d impressioni' % (clk, imp))
            dec = 'DECIDI'
        stem = os.path.splitext(os.path.basename(p))[0]
        t = titolo(classe, nome, stem) if (dec == 'TIENI' or classe in ('Scheda di servizio o percorso', 'Guida ai servizi della struttura', 'Comunicato stampa')) else ''
        if t and nota_nome and classe != 'Comunicato stampa':
            note.append(nota_nome)
        if t and classe != 'Comunicato stampa' and len(t) > LUNGHEZZA_TITOLO:
            note.append('titolo oltre %d caratteri: da accorciare' % LUNGHEZZA_TITOLO)
        testo = r.get('testo_pag1_inizio', '')
        dati = ''
        if CF.search(testo):
            dati = 'codice fiscale nell\'inizio del testo'
        elif NATO.search(testo):
            dati = 'dati anagrafici nell\'inizio del testo'
        if r.get('errore'):
            note.append('lettura: ' + r['errore'])
        elif num(r.get('testo_pag1_caratteri')) == 0:
            note.append('nessun testo in prima pagina (scansione o parser)')
        pag, npag = pagine(r)
        righe.append(dict(
            percorso=p, decisione=dec, classe=classe, sotto=sotto, motivo=motivo, titolo=t, lung=len(t) if t and classe != 'Comunicato stampa' else '',
            titolo_attuale=(r.get('titolo_info') or r.get('titolo_xmp') or '').strip(), alias=alias, nome=nome, pagina=pag, npagine=npag,
            altri=r.get('altri_elementi', ''), imp=imp, clk=clk, pos=s.get('posizione', ''), query=(s.get('top_3_query', '').split(' | ')[0] if s else ''),
            html=s.get('pagina_html_top_query', '') if s else '', pos_html=s.get('posizione_html', '') if s else '',
            md5=r['md5'], dati=dati, note='; '.join(note), byte=num(r.get('byte')), mod=r.get('modificato', '')))
    # duplicati: stesso contenuto in più percorsi
    gruppi = collections.defaultdict(list)
    for x in righe:
        gruppi[x['md5']].append(x)
    for lst in gruppi.values():
        if len(lst) < 2:
            continue
        lst.sort(key=lambda x: (-x['imp'], len(x['percorso']), x['percorso']))
        tieni = [x for x in lst if x['decisione'] == 'TIENI']
        for x in lst:
            x['dup'] = 'identico a %d altri' % (len(lst) - 1)
        if len(tieni) > 1:
            for x in tieni[1:]:
                x['dup'] = 'identico a %s: proposta canonical verso quello' % tieni[0]['percorso']
    for x in righe:
        x.setdefault('dup', '')
    righe.sort(key=lambda x: (ORDINE[x['decisione']], x['classe'], x['percorso']))
    return righe


def senza_file(sito, inv, gsc, audit_file, regole):
    """URL con impressioni (o negli audit) che non esistono più sul server."""
    sul_server = {r['percorso'] for r in inv}
    g = {r['percorso']: r for r in gsc}
    audit = set()
    if audit_file:
        import openpyxl
        ws = openpyxl.load_workbook(audit_file, read_only=True)['Gestione PDF']
        for r in ws.iter_rows(min_row=4, values_only=True):
            if r[1]:
                audit.add(urllib.parse.unquote(urllib.parse.urlparse(r[1]).path))
    out = []
    for p in sorted(set(g) | audit):
        if p in sul_server:
            continue
        s = g.get(p, {})
        nome = os.path.basename(p)
        out.append(dict(percorso=p, imp=num(s.get('impressioni')), clk=num(s.get('clic')), pos=s.get('posizione', ''), ultimo=s.get('ultimo_giorno', ''),
                        query=(s.get('top_3_query', '').split(' | ')[0] if s else ''), audit='sì' if p in audit else '',
                        regole='il nome del file compare nelle regole web' if nome in regole else ''))
    out.sort(key=lambda x: (-x['imp'], x['percorso']))
    return out


def scrivi(path, dati, audit_files):
    import openpyxl
    from openpyxl.styles import Alignment, Font, PatternFill
    from openpyxl.utils import get_column_letter
    from openpyxl.worksheet.datavalidation import DataValidation
    wb = openpyxl.Workbook()
    ris = wb.active
    ris.title = 'Riepilogo'
    colori = {'TIENI': 'E2F0D9', 'ESCLUDI': 'EDEDED', 'DECIDI': 'FFF2CC'}
    intest = PatternFill('solid', fgColor='1F3864')

    def foglio(nome, colonne, righe, larghezze, colore_col=None, validazione=None):
        ws = wb.create_sheet(nome)
        ws.append([c for c, _ in colonne])
        for c in ws[1]:
            c.font = Font(bold=True, color='FFFFFF')
            c.fill = intest
            c.alignment = Alignment(wrap_text=True, vertical='top')
        for r in righe:
            ws.append([r.get(k, '') for _, k in colonne])
        for i, w in enumerate(larghezze, 1):
            ws.column_dimensions[get_column_letter(i)].width = w
        ws.freeze_panes = 'B2'
        ws.auto_filter.ref = ws.dimensions
        if colore_col:
            for row in ws.iter_rows(min_row=2):
                f = colori.get(row[colore_col].value)
                if f:
                    row[colore_col].fill = PatternFill('solid', fgColor=f)
        if validazione:
            dv = DataValidation(type='list', formula1='"Confermo,TIENI,ESCLUDI,DECIDO DOPO"', allow_blank=True)
            ws.add_data_validation(dv)
            dv.add('%s2:%s%d' % (get_column_letter(validazione), get_column_letter(validazione), max(ws.max_row, 2)))
        return ws

    col = [('Proposta', 'decisione'), ('Classe', 'classe'), ('Sottotipo', 'sotto'), ('Regola che l\'ha prodotta', 'motivo'),
           ('Titolo proposto (metadati del file)', 'titolo'), ('Caratteri', 'lung'), ('Titolo attuale nei metadati', 'titolo_attuale'),
           ('Percorso', 'percorso'), ('Struttura (cartella)', 'alias'), ('Pagina che lo richiama', 'pagina'),
           ('Impressioni', 'imp'), ('Clic', 'clk'), ('Posizione media', 'pos'), ('Query principale', 'query'),
           ('Pagina HTML sulla stessa query', 'html'), ('Pos. HTML', 'pos_html'), ('Duplicati', 'dup'), ('Dati personali?', 'dati'),
           ('Note', 'note'), ('Decisione di Danilo', 'decisione_danilo')]
    larg = [10, 24, 24, 34, 52, 9, 30, 62, 34, 36, 11, 8, 10, 46, 36, 9, 40, 22, 52, 18]
    for sito, nome in (('aa', 'AA'), ('kos', 'KOS')):
        foglio(nome, col, dati[sito]['righe'], larg, colore_col=0, validazione=len(col))
    # dati personali
    dp = []
    for sito, nome in (('aa', 'AA'), ('kos', 'KOS')):
        for x in dati[sito]['righe']:
            motivo = ''
            if x['classe'] == 'Modulo / questionario':
                motivo = 'modulo o questionario: dati personali una volta compilato (il file vuoto non ne ha)'
            elif x['classe'] == 'Tempi di attesa e liste':
                motivo = 'liste e tempi di attesa: da verificare che non siano nominative'
            elif 'contratto' in norma(x['percorso']) or 'scheda anagrafica' in norma(x['percorso']) or 'documentazione' in norma(x['percorso']):
                motivo = 'contratto o scheda anagrafica: dati personali una volta compilato'
            if x['dati']:
                motivo = (motivo + '; ' if motivo else '') + x['dati']
            if motivo:
                dp.append(dict(sito=nome, nome=os.path.basename(x['percorso']), struttura=x['alias'], pagina=x['pagina'], motivo=motivo,
                               percorso=x['percorso'], proposta=x['decisione'], imp=x['imp'], clk=x['clk']))
    foglio('Dati personali', [('Sito', 'sito'), ('Documento', 'nome'), ('Struttura', 'struttura'), ('Pagina che lo richiama', 'pagina'),
                              ('Motivo', 'motivo'), ('Percorso', 'percorso'), ('Proposta', 'proposta'), ('Impressioni', 'imp'), ('Clic', 'clk')],
           dp, [8, 48, 34, 36, 62, 62, 10, 11, 8], colore_col=6)
    sf = []
    for sito, nome in (('aa', 'AA'), ('kos', 'KOS')):
        for x in dati[sito]['senza_file']:
            sf.append(dict(x, sito=nome))
    foglio('Senza file', [('Sito', 'sito'), ('Percorso (non esiste sul server)', 'percorso'), ('Impressioni', 'imp'), ('Clic', 'clk'), ('Posizione', 'pos'),
                          ('Ultimo giorno', 'ultimo'), ('Query principale', 'query'), ('Nell\'audit', 'audit'), ('Regole web', 'regole')],
           sf, [8, 90, 11, 8, 10, 12, 46, 11, 36])
    # riepilogo
    ris.column_dimensions['A'].width = 46
    for c in 'BCDEFGH':
        ris.column_dimensions[c].width = 15
    ris.append(['Classificazione dei PDF — foglio per la revisione (1/11/2026, dati Search Console 14/6/2025–23/10/2026)'])
    ris['A1'].font = Font(bold=True, size=13)
    ris.append(['Proposta: TIENI = indicizzabile con titolo nei metadati · ESCLUDI = noindex · DECIDI = serve una scelta. Nel foglio di ogni sito l\'ultima colonna è per la tua risposta.'])
    ris.append([])
    for sito, nome in (('aa', 'AA'), ('kos', 'KOS')):
        r = dati[sito]['righe']
        ris.append([nome, 'PDF', 'Impressioni', 'Clic', 'Titoli > %d car.' % LUNGHEZZA_TITOLO, 'Con duplicati', 'Dati personali'])
        for c in ris[ris.max_row]:
            c.font = Font(bold=True, color='FFFFFF')
            c.fill = intest
        for dec in ('TIENI', 'DECIDI', 'ESCLUDI'):
            s = [x for x in r if x['decisione'] == dec]
            ris.append(['  ' + dec, len(s), sum(x['imp'] for x in s), sum(x['clk'] for x in s), sum(1 for x in s if x['lung'] and x['lung'] > LUNGHEZZA_TITOLO),
                        sum(1 for x in s if x['dup']), sum(1 for x in s if x['dati'])])
            ris.cell(ris.max_row, 1).fill = PatternFill('solid', fgColor=colori[dec])
        ris.append(['  Totale', len(r), sum(x['imp'] for x in r), sum(x['clk'] for x in r), '', '', ''])
        ris.append([])
        cl = collections.Counter((x['classe'], x['decisione']) for x in r)
        ris.append(['  Per classe', 'PDF', 'Proposta'])
        for (c, d), n in sorted(cl.items(), key=lambda kv: (ORDINE[kv[0][1]], -kv[1])):
            ris.append(['  ' + c, n, d])
        ris.append([])
    wb.save(path)


def main():
    a = argparse.ArgumentParser()
    for k in ('inv-aa', 'gsc-aa', 'inv-kos', 'gsc-kos', 'out'):
        a.add_argument('--' + k, required=True)
    a.add_argument('--audit-aa')
    a.add_argument('--audit-kos')
    o = a.parse_args()
    dati = {}
    for sito, audit in (('aa', o.audit_aa), ('kos', o.audit_kos)):
        inv = leggi_csv(getattr(o, 'inv_' + sito))
        gsc = leggi_csv(getattr(o, 'gsc_' + sito))
        regole = ''
        d = os.path.join(RADICE, {'aa': 'anniazzurri', 'kos': 'kosgroup'}[sito], 'files', 'web-rules')
        for f in (os.listdir(d) if os.path.isdir(d) else []):
            regole += urllib.parse.unquote(open(os.path.join(d, f), encoding='utf-8').read()) + '\n'
        dati[sito] = dict(righe=elabora(sito, inv, gsc), senza_file=senza_file(sito, inv, gsc, audit, regole))
    scrivi(o.out, dati, (o.audit_aa, o.audit_kos))
    for sito in ('aa', 'kos'):
        r = dati[sito]['righe']
        c = collections.Counter(x['decisione'] for x in r)
        print('== %s: %d PDF — %s; senza file: %d' % (sito, len(r), ', '.join('%s %d' % kv for kv in c.items()), len(dati[sito]['senza_file'])))
    print('== Scritto: ' + o.out)


if __name__ == '__main__':
    main()
