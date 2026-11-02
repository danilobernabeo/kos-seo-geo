#!/usr/bin/env python3
"""
KOS-8 / AA-3 — Dal foglio finale (titoli-pdf.py) al piano di rilascio dei PDF.

Scrive, per ciascun sito:
  <sito>/dati/pdf/piano-pdf.json            piano per scripts/pdf/pdf-metadati.php (percorso, titolo, tipo): nessun testo dei PDF
  <sito>/files/web-rules/pdf-noindex.conf   SEZIONE MARCATA delle regole web: redirect dei PDF, X-Robots-Tag noindex di
                                            default (indicizzabili solo i PDF dell'elenco) e Link rel="canonical" dei duplicati
  <sito>/dati/pdf/url-strutture.txt         (con --url-strutture) elenco delle schede struttura per scripts/checks/meta-stato.php

Uso (dalla radice del repository):
    py scripts/pdf/genera-piano-pdf.py --foglio classificazione-pdf-20261101-v2.xlsx
Il foglio contiene i titoli, le righe «Rilascio di oggi» e «Canonical verso»; qui si aggiungono solo i redirect (tabella REDIRECT).
"""
import argparse, datetime, json, os, re, sys, urllib.parse

RADICE = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))
SITI = {'aa': ('anniazzurri', 'https://anniazzurri.it', 'AA'), 'kos': ('kosgroup', 'https://kosgroup.com', 'KOS')}

# Redirect dei PDF del foglio «Senza file» con più di 500 impressioni (2/11/2026). Regola del brief: 301 al documento
# equivalente (stesso tipo, stessa struttura, versione nuova) oppure alla pagina; mai verso un documento di contenuto diverso.
# (percorso richiesto, destinazione, motivo, attivo). Già in produzione: AA pgr-cinisello/Pieghevole informativo,
# villa-olga/Carta dei servizi, parco-di-veio/PARS 2022, villa-reale/PARM 2024; KOS villa-jolanda e villa-azzurra/scheda-prenotazione-ricovero.
D = '/assets/doc/strutture/'
REDIRECT = {
    'aa': [
        (D + 'residenza-anni-azzurri-villalba/Carta dei servizi.pdf', 'https://anniazzurri.it/rsa-nelle-marche',
         'residenza chiusa il 19/10: la pagina rimanda a /rsa-nelle-marche e non esiste una carta equivalente', True),
        (D + 'residenza-anni-azzurri-parco-di-veio/PARM 2024.pdf', 'https://anniazzurri.it/assets/doc/strutture/residenza-anni-azzurri-parco-di-veio/PARM 2025.pdf',
         'stesso documento (PARM) della stessa residenza, edizione 2025 (come PARS 2022 → PARS 2025)', True),
        (D + 'residenza-anni-azzurri-biarella/Carta dei servizi.pdf', 'https://anniazzurri.it/residenza-anni-azzurri-biarella',
         'la cartella non ha più documenti: alla pagina della residenza. ATTIVARE SOLO se la pagina risponde 200 (curl -I)', False),
    ],
    'kos': [
        (D + 'villa-rosa/informazioni-per-il-ricovero.pdf', 'https://kosgroup.com/it/centri/villa-rosa',
         'nessun documento equivalente (il modulo DSM e la carta dei servizi hanno altro contenuto): alla scheda della struttura', True),
        (D + 'poliambulatorio-villalba/piano-annuale-per-la-gestione-del-rischio-sanitario-2025.pdf', 'https://kosgroup.com/assets/doc/strutture/poliambulatorio-villalba/pars-2025.pdf',
         'stesso documento (Piano annuale per la gestione del rischio sanitario 2025 = PARS 2025) rinominato', True),
        (D + 'villa-pineta/carta-dei-servizi-centro-di-riabilitazione.pdf', 'https://kosgroup.com/assets/doc/strutture/villa-pineta/carta-dei-servizi.pdf',
         'carta dei servizi di Villa Pineta Riabilitazione, edizione del 14/10/2026 con il nome nuovo', True),
        (D + 'poliambulatorio-villa-pineta/prestazioni-poliambulatorio-villa-pineta-modena.pdf', 'https://kosgroup.com/assets/doc/strutture/poliambulatorio-villa-pineta/attivita-poliambulatorio-villa-pineta-modena.pdf',
         'stesso documento (prestazioni della sede di Modena) rinominato «attività»', True),
        (D + 'ville-di-nozzano/disturbi-dellumore-e-di-personalita.pdf', 'https://kosgroup.com/assets/doc/strutture/ville-di-nozzano/disturbi-dell-umore-e-di-personalita.pdf',
         'stesso documento rinominato (dellumore → dell-umore)', True),
        (D + 'centro-residenziale-santo-stefano-foligno/sintesi-clinica-urs.pdf', 'https://kosgroup.com/it/centri/centro-residenziale-santo-stefano-foligno',
         'nessun documento equivalente (le sintesi cliniche RSA e SUAP sono per altri percorsi): alla scheda della struttura', True),
    ],
}


# Modelli per nome file (senza distinzione di maiuscole, sul $uri): i tipi da tenere restano indicizzabili anche se caricati con un nome nuovo.
MODELLI = [
    ('carta dei servizi / carta degli alloggi', r'/[^/]*cart[ae][ _-]+(dei|degli)[ _-]+(servizi|alloggi)[^/]*\.pdf$'),
    ('pieghevole / leaflet', r'/[^/]*(pieghevole|leaflet)[^/]*\.pdf$'),
    ('guida utente / user manual / guida ai servizi', r'/[^/]*(guida[ _-]+(utente|ai[ _-]+servizi)|user[ _-]*(manual|guide))[^/]*\.pdf$'),
    ('preparazioni', r'/preparazion[ie]'),
    ('pieghevole Anni Azzurri Senior Living (caricato il 2/11 come «Anni Azzurri Senior Living.pdf»)', r'/anni[ _-]+azzurri[ _-]+senior[ _-]+living[^/]*\.pdf$'),
]


def qr(rx):
    return '"' + rx + '"'


def q(s):
    if '$' in s or '"' in s or '\\' in s:
        raise SystemExit('Carattere non ammesso in una regola nginx: ' + s)
    return '"' + s + '"'


def url_encoded(base, percorso):
    return base + '/'.join(urllib.parse.quote(seg, safe="()'!*~-_.,@+=:&") for seg in percorso.split('/'))


def header_sicurezza(sito):
    d = SITI[sito][0]
    out = []
    for riga in open(os.path.join(RADICE, d, 'files/web-rules/produzione-2026-10-31.conf'), encoding='utf-8'):
        if riga.startswith('add_header '):
            out.append(riga.rstrip('\n'))
    return out


# Cartelle di documenti spostate dopo l'inventario del 1/11 (il foglio ha ancora il vecchio percorso). Il 301 del vecchio PDF
# NON si genera qui: è già nelle regole web fuori sezione (anniazzurri/files/web-rules/aa-senior-living.conf).
SPOSTATI = [
    ('/assets/doc/strutture/alloggi-protetti/', '/assets/doc/strutture/appartamenti-anziani-bergamo/'),   # AA-20, risorsa 367, 1/11
]


def rimappa(p):
    for vecchio, nuovo in SPOSTATI:
        if p and p.startswith(vecchio):
            return nuovo + p[len(vecchio):]
    return p


esclusi_modello = {}


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--foglio', required=True)
    o = a.parse_args()
    import openpyxl
    wb = openpyxl.load_workbook(o.foglio, read_only=True)
    oggi = datetime.date.today().isoformat()
    for sito, (cart, base, nome_f) in SITI.items():
        ws = wb[nome_f]
        rows = ws.iter_rows(values_only=True)
        h = next(rows)
        ix = {k: i for i, k in enumerate(h)}
        voci, tieni, canon, attesa, comunicati_cartella = [], [], [], [], []
        esclusi = 0
        lista_esclusi = []
        for r in rows:
            p = rimappa(r[ix['Percorso']])
            if not p:
                continue
            if r[ix['Proposta']] != 'TIENI':
                esclusi += 1
                lista_esclusi.append(p)
                continue
            tieni.append(p)
            ril = str(r[ix['Rilascio di oggi']] or '')
            tit = r[ix['Titolo proposto (metadati del file)']]
            if ril.startswith('No'):
                attesa.append(p)
                continue
            if r[ix['Classe']] == 'Comunicato stampa':
                voci.append({'p': p, 'tipo': 'comunicato'})
                continue
            if tit:
                voci.append({'p': p, 't': tit})
            c = rimappa(r[ix['Canonical verso']])
            if c:
                canon.append((p, c))
        os.makedirs(os.path.join(RADICE, cart, 'dati', 'pdf'), exist_ok=True)
        with open(os.path.join(RADICE, cart, 'dati', 'pdf', 'piano-pdf.json'), 'w', encoding='utf-8') as fh:
            json.dump({'sito': sito, 'generato': oggi, 'pdf': voci}, fh, ensure_ascii=False, indent=1)
        # ---- regole web ----
        L = []
        L.append('# ===== INIZIO SEZIONE PDF (KOS-8 / AA-3) — generata il %s da scripts/pdf/genera-piano-pdf.py: non modificare a mano, si rigenera =====' % oggi)
        L.append('# Gestita dalla chat dei PDF. Da incollare nelle regole web del pannello MODX Cloud PRIMA del blocco «location / {».')
        L.append('# Tre parti: 1) redirect di singoli PDF; 2) un solo blocco per tutti i PDF: X-Robots-Tag noindex di default, indicizzabili solo i PDF')
        L.append('# dell\'elenco (%d), Link rel="canonical" per le %d copie identiche; 3) niente robots.txt: Google deve poter rileggere l\'intestazione.' % (len(tieni), len(canon)))
        L.append('# Ripristino: togliere tutto da INIZIO a FINE; i PDF tornano come prima (tutti indicizzabili, senza intestazioni).')
        L.append('')
        L.append('# --- 1. Redirect di singoli PDF (location esatte, vincono sul blocco regex) ---')
        for p, dest, motivo, attivo in REDIRECT[sito]:
            pre = '' if attivo else '#'
            L.append('# %s' % motivo)
            L.append('%slocation = %s { return 301 %s; }' % (pre, q(p), q(dest)))
        L.append('')
        L.append('# --- 2. Tutti i PDF: noindex tranne quelli in elenco; canonical per i duplicati ---')
        L.append('# Le add_header dentro una location annullano quelle del server: si ripetono qui gli header di sicurezza del sito.')
        if sito == 'aa':
            L.append('# ATTENZIONE: nelle regole di anniazzurri.it esiste già un blocco «location ~* \\.pdf$ { ... expires 1d; }» (sezione «Cache del browser»).')
            L.append('# Con due blocchi regex uguali nginx usa solo il primo: va CANCELLATO quel blocco e al suo posto vale questo (che mantiene expires 1d).')
        L.append('location ~* \\.pdf$ {')
        L.append('    if ($redirect_to) { return 301 $redirect_to; }')
        for hd in header_sicurezza(sito):
            L.append('    ' + hd)
        if sito == 'aa':
            L.append('    expires 1d;')
        L.append('    set $pdf_robots "noindex";')
        L.append('    set $pdf_link "";')
        if sito == 'kos':
            L.append('    # comunicati stampa: tutta la cartella è indicizzabile (decisione del 1/11)')
            L.append('    if ($uri ~ "^/assets/doc/comunicati/") { set $pdf_robots ""; }')
        L.append('    # --- per nome file (senza distinzione di maiuscole): i tipi da tenere restano indicizzabili anche con un nome nuovo ---')
        for nome, rx in MODELLI:
            L.append('    # %s' % nome)
            L.append('    if ($uri ~* %s) { set $pdf_robots ""; }' % qr(rx))
        eccez = [e for e in sorted(lista_esclusi) if any(re.search(rx, e, re.I) for _, rx in MODELLI) or (sito == 'kos' and e.startswith('/assets/doc/comunicati/'))]
        if eccez:
            L.append('    # --- classificati ESCLUDI ma con un nome che coincide con un modello: noindex esplicito ---')
            for e in eccez:
                L.append('    if ($uri = %s) { set $pdf_robots "noindex"; }' % q(e))
        esclusi_modello[sito] = eccez
        L.append('    # --- PDF indicizzabili (carte dei servizi, pieghevoli, guide, schede di servizio, moduli di accesso) ---')
        com = re.compile(r'^/assets/doc/comunicati/')
        for p in sorted(tieni):
            if sito == 'kos' and com.match(p):
                continue
            L.append('    if ($uri = %s) { set $pdf_robots ""; }' % q(p))
        if canon:
            L.append('    # --- copie identiche: una sola fa testo, le altre puntano a quella ---')
            for p, c in sorted(canon):
                L.append('    if ($uri = %s) { set $pdf_link "<%s>; rel=\\"canonical\\""; }' % (q(p), url_encoded(base, c)))
        L.append('    add_header X-Robots-Tag $pdf_robots;')
        L.append('    add_header Link $pdf_link;')
        L.append('    try_files $uri $uri/ @modx-rewrite;')
        L.append('}')
        if sito == 'kos':
            L.append('')
            L.append('# --- 3. Curriculum caricati dal modulo di candidatura (dati personali): fuori dai motori, ma raggiungibili ---')
            L.append('# I file restano aperti (la pagina riservata li apre con il link diretto): solo X-Robots-Tag noindex, nofollow, per ogni tipo di file.')
            L.append('# Il prefisso ^~ vince sui blocchi con espressione regolare. Con try_files uguale a quello predefinito, l\'elenco della cartella resta 403 come oggi.')
            L.append('location ^~ /assets/doc/cv/ {')
            L.append('    if ($redirect_to) { return 301 $redirect_to; }')
            for hd in header_sicurezza(sito):
                L.append('    ' + hd)
            L.append('    add_header X-Robots-Tag "noindex, nofollow" always;')
            L.append('    try_files $uri $uri/ @modx-rewrite;')
            L.append('}')
        L.append('# ===== FINE SEZIONE PDF (KOS-8 / AA-3) =====')
        with open(os.path.join(RADICE, cart, 'files', 'web-rules', 'pdf-noindex.conf'), 'w', encoding='utf-8') as fh:
            fh.write('\n'.join(L) + '\n')
        # variante per lo stage: lì $redirect_to (regola di produzione sul dominio) non esiste e nginx rifiuterebbe la variabile
        LS = [l for l in L if '$redirect_to' not in l]
        LS[0] = LS[0].replace('INIZIO SEZIONE PDF', 'INIZIO SEZIONE PDF — VARIANTE STAGE (senza $redirect_to)')
        with open(os.path.join(RADICE, cart, 'files', 'web-rules', 'pdf-noindex-stage.conf'), 'w', encoding='utf-8') as fh:
            fh.write('\n'.join(LS) + '\n')
        print('== %s: indicizzabili %d (di cui in attesa di KOS %d, comunicati %d), esclusi %d, canonical %d, redirect %d (attivi %d); piano: %d PDF da riscrivere' % (
            sito, len(tieni), len(attesa), sum(1 for v in voci if v.get('tipo')), esclusi, len(canon), len(REDIRECT[sito]),
            sum(1 for r in REDIRECT[sito] if r[3]), len(voci)))
        print('   ESCLUDI che coincidono con i modelli per nome: %d' % len(esclusi_modello[sito]))
        for e in esclusi_modello[sito]:
            print('     ' + e)


if __name__ == '__main__':
    main()
