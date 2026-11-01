#!/usr/bin/env python3
"""
KOS-8 / AA-3 — Titoli dei PDF da tenere, canonical dei duplicati e conteggi, a partire dal foglio rivisto da Danilo (1/11).

Legge il foglio rivisto (classificazione-pdf-20261101.xlsx: AA, KOS, Dati personali, Senza file), applica il parere del
1/11 che manca (le 32 richieste di ricovero/domande di inserimento «valutare TIENI»), genera il titolo di ogni PDF TIENI
con il nome ufficiale della struttura (H1 della scheda), controlla lunghezza (max 65) e unicità, sceglie la copia che fa
testo tra i duplicati e riscrive il foglio con Riepilogo aggiornato. NON contiene testo dei PDF.

Uso (dalla radice del repository):
    py scripts/pdf/titoli-pdf.py --foglio classificazione-pdf-20261101.xlsx --inv-aa A.csv --inv-kos K.csv ^
        [--meta-aa meta-stato-aa.csv ...] [--meta-kos meta-stato-kos.csv ...] --out classificazione-pdf-20261101-v2.xlsx
Con --elenco-url <cartella> scrive anche gli elenchi di URL delle schede struttura da passare a scripts/checks/meta-stato.php
(per avere gli H1 reali: senza, il nome viene dal dato disponibile e la riga è segnata «nome da verificare»).
"""
import argparse, collections, csv, json, os, re, sys, unicodedata, urllib.parse

MAX = 65
RADICE = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))
ORDINE = {'TIENI': 0, 'ESCLUDI': 1}

# --- parere 1/11 sui moduli di accesso rimasti ESCLUDI «valutare TIENI» -------------------------------------------------
SUPERATE = {  # intestazioni ASL (oggi ATS) o scansioni del 2014-2017: restano ESCLUDI
    'questionario-di-ammissione-ASL-MI2.pdf': 'intestazione ASL Milano 2 (oggi ATS): modulo superato',
    'questionario-socio-anagrafico-di-ammissione-ASL-MI.pdf': 'scheda socio-anagrafica ASL Milano del 2014: modulo superato',
    'Domanda unica per l\'inserimento.pdf': 'scansione del 2017: modulo superato',
}
TITOLI_MANUALI = {  # percorso (finale) → titolo; ente e territorio nel titolo
    'Domanda-di-inserimento-unificata-presso-le-RSA-della-ATS-della-città-metropolitana-di-Milano.pdf': 'Domanda unificata di inserimento in RSA – ATS Milano',
    'Scheda-sanitaria-di-ingresso-presso-le-RSA-della-ATS-della-città-metropolitana-di-Milano.pdf': 'Scheda sanitaria di ingresso in RSA – ATS Milano',
    'domanda-unica-di-inserimento-in-RSA-ATS-Brianza.pdf': 'Domanda unica di inserimento in RSA – ATS Brianza',
    'questionario-di-ammissione-ASL-BG.pdf': 'Domanda di ingresso in RSA – ATS Bergamo',
    'questionario-di-ammissione-UOR-mirasole.pdf': 'Questionario di ammissione UOR – Anni Azzurri Mirasole',
    'questionario-di-ammissione-UOR-rezzato.pdf': 'Questionario di ammissione UOR – Anni Azzurri Rezzato',
    'questionario-di-ammissione-UOR-polo-geriatrico-riabilitativo.pdf': 'Questionario di ammissione UOR – Polo Geriatrico Riabilitativo',
    'questionario-di-ammissione-residenze-anni-azzurri.pdf': 'Questionario di ammissione – Residenze Anni Azzurri',
    'Analisi del Movimento Laboratorio L.A.M.PO..pdf': 'Analisi del movimento L.A.M.PO. – Istituto Santo Stefano',
    'Analisi del Movimento Laboratorio L.A.M.PO.pdf': 'Analisi del movimento L.A.M.PO. – Istituto Santo Stefano',
    'mod308-richiesta-cartella-persona-deceduta.pdf': 'Richiesta cartella clinica di defunto – Ospedale San Pancrazio',
    'aMod309-richiesta-cartella-clinica.pdf': 'Richiesta della cartella clinica – Ospedale San Pancrazio',
    'regolamento-comunita.pdf': 'Regolamento della comunità – Beata Corte',
    'pieghevole-home-care-academy.pdf': 'Pieghevole Home Care Academy – Anni Azzurri',
    'Pieghevole kosì vicini.pdf': 'Pieghevole «Kosì vicini» – Polo Geriatrico Riabilitativo Milano',
}
CORREZIONI = [  # (regex sul titolo del documento, sostituzione): accenti e apostrofi persi nei nomi dei file
    (r'\battivita\b', 'attività'), (r'\bpersonalita\b', 'personalità'), (r'\bd ansia\b', "d'ansia"), (r'\bdell umore\b', "dell'umore"),
    (r'\bdi un\b', 'di un'), (r'\bUnita\'', 'Unità'), (r'\buo\b', 'UO'), (r'\brsa\b', 'RSA'),
    (r'\bcsrr\b', 'CSRR'), (r'\bcdi\b', 'CDI'), (r'\banniazzurri\b', 'Anni Azzurri'), (r'\bd alzheimer\b', "d'Alzheimer"),
    (r'\bequipe\b', 'équipe'), (r'\bcheck up\b', 'check-up'), (r'\bparkinson\b', 'Parkinson'), (r'\balzheimer\b', 'Alzheimer'), (r'\bdi un\b', 'di un'),
    (r"\bdell\b(?!')", "dell'"), (r"\bl\b(?!')", "l'"),
]
MINUSC = {'di', 'del', 'della', 'dei', 'delle', 'degli', 'da', 'in', 'per', 'a', 'e', 'al', 'alla', 'sul', 'su', 'con', 'il', 'la', 'le', 'lo', 'un', 'una', 'd', 'dall'}


def leggi_csv(f):
    with open(f, encoding='utf-8-sig', newline='') as fh:
        return list(csv.DictReader(fh, delimiter=';'))


def sentence(s):
    s = re.sub(r'[-_]+', ' ', s)
    s = re.sub(r'\s+', ' ', s).strip()
    s = s[:1].upper() + s[1:]
    for a, b in CORREZIONI:
        s = re.sub(a, lambda m, b=b: (b[:1].upper() + b[1:]) if m.group(0)[:1].isupper() and not b[:1].isupper() else b, s, flags=re.I)
    s = re.sub(r"\b(dell|l)' ", r"\1'", s)
    return s[:1].upper() + s[1:]


def norma(s):
    s = unicodedata.normalize('NFKD', s.lower())
    return re.sub(r'[^a-z0-9]+', ' ', ''.join(c for c in s if not unicodedata.combining(c))).strip()


def alias_di(p):
    parti = p.strip('/').split('/')   # assets doc strutture <alias> file
    if len(parti) >= 5 and parti[2] in ('strutture', 'moduli'):
        return parti[3]
    return ''


def nomi(sito, metas):
    """alias → (nome, fonte). Priorità: H1 della scheda (meta-stato), poi dato locale."""
    out = {}
    if sito == 'kos':
        d = json.load(open(os.path.join(RADICE, 'kosgroup/dati/voce-24/mappatura-servizi-2026-10-25.json'), encoding='utf-8'))
        for s in d['schede']:
            if str(s.get('template', '')).startswith('strutture') and s.get('pagetitle'):
                out[s['uri'].strip('/').split('/')[-1]] = (s['pagetitle'].strip(), 'pagetitle della scheda (H1 da verificare)')
    else:
        for r in leggi_csv(os.path.join(RADICE, 'anniazzurri/dati/aa-20/meta-stato-20261027.csv')):
            a = urllib.parse.urlparse(r['url']).path.strip('/').split('/')[-1]
            if r['H1'].strip():
                out[a] = (r['H1'].strip(), 'H1 della scheda (27/10)')
    for f in metas:
        for r in leggi_csv(f):
            a = urllib.parse.urlparse(r['url']).path.strip('/').split('/')[-1]
            if r.get('http') == '200' and r['H1'].strip() and int(r.get('n H1') or 1) >= 1:
                out[a] = (r['H1'].strip(), 'H1 della scheda (letto da meta-stato)')
    return out


DOC_BREVE = {  # (cartella, file) → parte «documento» abbreviata a mano; il nome della struttura resta quello della scheda
    ('residenza-anni-azzurri-campi-bisenzio', 'Carta dei servizi Hospice San Giorgio a Colonica.pdf'): 'Carta dei servizi Hospice',
    ('residenza-anni-azzurri-mirasole', "Carta dei servizi Unita' Operativa Riabilitazione Generale Geriatrica.pdf"): 'Carta dei servizi Riabilitazione',
    ('residenza-anni-azzurri-rezzato', "Carta dei servizi Unita' Operativa Riabilitazione Generale Geriatrica.pdf"): 'Carta dei servizi Riabilitazione',
    ('residenza-anni-azzurri-pgr-cinisello', 'Carta dei servizi Dipartimento di Medicina Fisica e Riabilitativa.pdf'): 'Carta dei servizi Medicina Fisica',
    ('residenza-anni-azzurri-villaggio-san-francesco', 'Carta dei servizi RSA aperta.pdf'): 'Carta servizi RSA aperta',
    ('residenza-anni-azzurri-abitare-il-tempo', 'Richiesta di ricovero Riabilitazione Estensiva.pdf'): 'Richiesta ricovero riabilitazione',
    ('residenza-anni-azzurri-santa-maria-in-chienti', 'Modulo ingresso in convenzione.pdf'): 'Ingresso in convenzione',
    ('ospedale-san-pancrazio', 'centro-ambulatoriale-di-riabilitazione-neuromotoria.pdf'): 'Riabilitazione neuromotoria',
    ('villa-margherita-vicenza-psichiatria', 'ricovero-intensivo-per-pazienti-con-disturbi-d-ansia-e-umore.pdf'): "Ricovero: disturbi d'ansia e umore",
    ('villa-margherita-vicenza-psichiatria', 'ricovero-intensivo-per-pazienti-con-disturbo-della-personalita.pdf'): 'Ricovero: disturbo di personalità',
    ('villa-margherita-vicenza-psichiatria', 'storia-di-villa-margherita.pdf'): 'Storia di Villa Margherita',
    ('villa-margherita-vicenza-riabilitazione', 'storia-di-villa-margherita.pdf'): 'Storia di Villa Margherita',
    ('villa-dei-pini-civitanova', 'carta-dei-servizi-rsa-villa-margherita.pdf'): 'Carta servizi RSA Villa Margherita',
    ('centro-ambulatoriale-santo-stefano-camerino', 'user-manual.pdf'): 'User guide (overview)',
    ('centro-ambulatoriale-santo-stefano-camerino', 'user-manual-en.pdf'): 'User guide',
    ('centro-ambulatoriale-santo-stefano-camerino', 'guida-utente-eng.pdf'): 'User guide',
    ('centro-ambulatoriale-santo-stefano-jesi', 'user-manual.pdf'): 'User guide (overview)',
    ('centro-ambulatoriale-santo-stefano-jesi', 'user-manual-en.pdf'): 'User guide',
    ('centro-ambulatoriale-santo-stefano-jesi', 'guida-utente-eng.pdf'): 'User guide',
    ('centro-ambulatoriale-santo-stefano-macerata', 'user-manual.pdf'): 'User guide (overview)',
    ('centro-ambulatoriale-santo-stefano-macerata', 'user-manual-en.pdf'): 'User guide',
    ('centro-ambulatoriale-santo-stefano-macerata', 'guida-utente-eng.pdf'): 'User guide',
    ('residenza-anni-azzurri-beato-angelico', 'Carta dei servizi Centro Diurno Integrato.pdf'): 'Carta dei servizi Centro Diurno',
    ('residenza-anni-azzurri-il-poggione', 'Carta dei servizi Centro Diurno Integrato.pdf'): 'Carta dei servizi Centro Diurno',
    ('residenza-anni-azzurri-san-giuseppe', 'Carta dei servizi Centro Diurno Integrato.pdf'): 'Carta dei servizi Centro Diurno',
    ('centro-diurno-integrato-anni-azzurri-san-rocco', 'Carte dei servizi Centro Diurno Integrato.pdf'): 'Carta dei servizi Centro Diurno',
    ('residenza-anni-azzurri-san-rocco', 'Carte dei servizi Centro Diurno Integrato.pdf'): 'Carta dei servizi Centro Diurno',
    ('residenza-anni-azzurri-pgr-milano', 'Carta dei servizi Polo Geriatrico Riabilitativo 1.pdf'): 'Carta dei servizi, nucleo 1',
    ('residenza-anni-azzurri-pgr-milano', 'Carta dei servizi Polo Geriatrico Riabilitativo 2.pdf'): 'Carta dei servizi, nucleo 2',
    ('residenza-anni-azzurri-rezzato', 'Carta dei servizi Servizio di Cure Intermedie Residenziali.pdf'): 'Carta dei servizi Cure Intermedie',
    ('residenza-anni-azzurri-villa-reale', 'Carta dei servizi Servizio di Cure Intermedie Residenziali.pdf'): 'Carta dei servizi Cure Intermedie',
    ('residenza-anni-azzurri-dorica', 'Modulo richiesta ricovero ex Riabilitazione Estensiva.pdf'): 'Richiesta di ricovero in riabilitazione',
    ('residenza-anni-azzurri-san-sisto-2', 'Pieghevole informativo alloggi protetti.pdf'): 'Pieghevole alloggi protetti',
    ('', 'anniazzurri-a-casa-servizio-di-assistenza-domiciliare.pdf'): 'Anni Azzurri a casa: assistenza domiciliare',
    ('', 'le-10-cose-da-verificare-quando-scelgo-una-residenza.pdf'): '10 cose da verificare quando scelgo una residenza',
    ('centro-venerabile-marcucci', 'modulo-trasferimento-riabilitazione-intensiva.pdf'): 'Modulo trasferimento riabilitazione',
    ('centro-residenziale-santo-stefano-foligno', 'pieghevole-informativo-RSA.pdf'): 'Pieghevole informativo RSA',
    ('centro-residenziale-santo-stefano-foligno', 'pieghevole-informativo.pdf'): 'Pieghevole',
    ('poliambulatorio-villalba', 'leaflet-poliambulatorio-villalba-english.pdf'): 'Leaflet (English)',
    ('poliambulatorio-villa-pineta', 'attivita-poliambulatorio-villa-pineta-gaiato.pdf'): 'Attività, sede di Gaiato',
    ('poliambulatorio-villa-pineta', 'attivita-poliambulatorio-villa-pineta-modena.pdf'): 'Attività, sede di Modena',
    ('villa-pineta', 'attivita-poliambulatorio-villa-pineta-gaiato.pdf'): 'Attività del poliambulatorio, sede di Gaiato',
    ('centro-cardinal-ferrari', 'informazioni-utili-per-il-periodo-di-ricovero.pdf'): 'Informazioni utili per il ricovero',
}
ABBREVIAZIONI = [  # nome ufficiale → forma abbreviata, nell'ordine in cui si prova
    (r'^Residenza (Anni Azzurri)', r'\1'), (r'^Centro Ambulatoriale ', ''), (r'Polo Geriatrico Riabilitativo', 'PGR'),
    (r'^Centro Diurno Integrato ', 'CDI '), (r'^Centro Residenziale (Santo Stefano)', r'\1'),
    (r'^(Villa Margherita) Vicenza \((Psichiatria|Riabilitazione)\)', r'\1 \2'),
]


def componi(doc, nome, tronca=True):
    """«doc – nome» entro MAX caratteri. Restituisce (titolo, nota)."""
    t = '%s – %s' % (doc, nome) if nome else doc
    if len(t) <= MAX:
        return t, ''
    for a, b in ABBREVIAZIONI:
        n2 = re.sub(a, b, nome)
        if n2 != nome:
            nome = n2
            t = '%s – %s' % (doc, nome)
            if len(t) <= MAX:
                return t, 'nome abbreviato per stare in %d caratteri' % MAX
    if tronca:
        return t, 'TITOLO OLTRE %d CARATTERI (%d): da accorciare a mano' % (MAX, len(t))
    return t, ''


GUIDA = re.compile(r'user[ -]?manual|user[ -]?guide|guida[ -]utente|guide[ -]to[ -]services|guida[ -]ai[ -]servizi|patients[ -]and[ -]visitors', re.I)


def titolo_doc(classe, sotto, stem, percorso, lingua_en):
    if GUIDA.search(stem) and not re.search(r'locandina', stem, re.I):
        extra = [t for t in re.split(r'[-_ ]+', stem) if t.lower() not in ('guida', 'utente', 'user', 'manual', 'guide', 'eng', 'en', 'english', 'ai', 'servizi', 'to', 'services', 'patients', 'and', 'visitors')]
        sfx = (' ' + ' '.join(e.capitalize() for e in extra)) if extra else ''
        if re.search(r'patients[ -]and[ -]visitors', stem, re.I):
            return 'Guide for patients and visitors'
        return ('User guide' if lingua_en or re.search(r'user|guide|eng|english', stem, re.I) else 'Guida per gli utenti') + sfx
    if classe == 'Carta dei servizi':
        s = sentence(stem)
        s = re.sub(r'^Carte dei servizi', 'Carta dei servizi', s)
        return s
    if classe == 'Pieghevole':
        return sentence(stem) if not re.fullmatch(r'pieghevole[ -]informativo', stem, re.I) else 'Pieghevole informativo'
    if classe == 'Guida ai servizi della struttura':
        n = norma(stem)
        if re.search(r'user manual|user guide|guide to|guide for|patients and visitors', n) or lingua_en:
            return 'User guide'
        if re.fullmatch(r'guida utente|guida ai servizi|guida utente eng|guida accesso|guida', n) or n.startswith('guida utente'):
            return 'Guida per gli utenti'
        return sentence(stem)
    return sentence(stem)


TESTI = {}


def lingua_it_en(p):
    t = ' ' + TESTI.get(p, '').lower() + ' '
    en = sum(t.count(w) for w in (' the ', ' and ', ' of ', ' to ', ' your ', ' our ', ' with '))
    it = sum(t.count(w) for w in (' il ', ' di ', ' e ', ' della ', ' per ', ' con ', ' dei ', ' le '))
    return 'en' if en > it and en >= 3 else 'it'


def main():
    a = argparse.ArgumentParser()
    a.add_argument('--foglio', required=True)
    a.add_argument('--inv-aa', required=True)
    a.add_argument('--inv-kos', required=True)
    a.add_argument('--meta-aa', action='append', default=[])
    a.add_argument('--meta-kos', action='append', default=[])
    a.add_argument('--out', required=True)
    a.add_argument('--elenco-url')
    a.add_argument('--nome', action='append', default=[], help="ALIAS=H1 letto a mano dalla scheda (ripetibile)")
    o = a.parse_args()
    import openpyxl
    from openpyxl.styles import Alignment, Font, PatternFill
    from openpyxl.utils import get_column_letter
    wb = openpyxl.load_workbook(o.foglio)
    md5 = {}
    for f in (o.inv_aa, o.inv_kos):
        for r in leggi_csv(f):
            md5[r['percorso']] = r['md5']
            TESTI[r['percorso']] = r.get('testo_pag1_inizio', '')
    intest = [c.value for c in wb['AA'][1]]
    ix = {k: i for i, k in enumerate(intest)}
    dati = {}
    for sito, nome_f in (('aa', 'AA'), ('kos', 'KOS')):
        nm = nomi(sito, o.meta_aa if sito == 'aa' else o.meta_kos)
        for kv in o.nome:
            al_, n_ = kv.split('=', 1)
            nm[al_] = (n_, 'H1 della scheda (letto a mano)')
        omonimi = collections.defaultdict(list)
        for al, (n_, f_) in nm.items():
            omonimi[n_].append(al)
        for n_, als in omonimi.items():
            if len(als) > 1:   # stessa H1 su schede diverse (es. Villa Margherita Vicenza): si qualifica con la parte che distingue la cartella
                toks = [set(a_.split('-')) for a_ in als]
                comune = set.intersection(*toks)
                for a_, t_ in zip(als, toks):
                    q_ = ' '.join(w for w in a_.split('-') if w not in comune).capitalize()
                    nm[a_] = ('%s (%s)' % (n_, q_), nm[a_][1] + '; H1 uguale a un\'altra scheda: qualificata con la cartella')
        righe = []
        for r in wb[nome_f].iter_rows(min_row=2, values_only=True):
            x = dict(zip(intest, r))
            x['_md5'] = md5.get(x['Percorso'], '')
            righe.append(x)
        # 1. parere sui moduli di accesso rimasti ESCLUDI
        for x in righe:
            if x['Proposta'] == 'ESCLUDI' and 'valutare TIENI' in str(x['Note'] or ''):
                base = os.path.basename(x['Percorso'])
                if base in SUPERATE:
                    x['Note'] = (x['Note'] or '') + ' · Modulo di accesso: resta ESCLUDI (%s)' % SUPERATE[base]
                else:
                    x['Proposta'] = 'TIENI'
                    x['Note'] = (x['Note'] or '') + ' · Modulo di accesso a un servizio: passato a TIENI (regola del 1/11); modulo in bianco, da verificare a campione'
                    if x['Classe'] != 'Modulo / questionario':
                        x['Classe'] = 'Modulo / questionario'
        # 0. struttura uscita dal gruppo: i PDF sono già in 301 verso Corte Orero (regole web di produzione), non vengono serviti
        for x in righe:
            if x['Percorso'].startswith('/assets/doc/strutture/beata-corte/') and x['Proposta'] == 'TIENI':
                x['Proposta'] = 'ESCLUDI'
                x['Note'] = (x['Note'] or '') + ' · Struttura uscita dal gruppo: 301 già in produzione verso Corte Orero, il file non è servito'
        # 2. titoli
        for x in righe:
            x['_titolo_ok'] = ''
            if x['Proposta'] != 'TIENI':
                x['Titolo proposto (metadati del file)'] = ''
                x['Caratteri'] = ''
                continue
            p = x['Percorso']
            base = os.path.basename(p)
            stem = os.path.splitext(base)[0]
            alias = alias_di(p)
            nome, fonte = (nm.get(alias) if alias else None) or ('', '')
            note = []
            if x['Classe'] == 'Comunicato stampa':
                x['Titolo proposto (metadati del file)'] = 'Comunicato stampa – <argomento> – <mese anno>  [dalla risorsa MODX]'
                x['Caratteri'] = ''
                x['_nome'], x['_fonte'] = '', 'argomento e data dalla risorsa MODX del comunicato (TV comunicatoPdf)'
                continue
            if base in TITOLI_MANUALI:
                t, nota = TITOLI_MANUALI[base], 'titolo scritto a mano (ente e territorio)'
                nome, fonte = '', 'manuale'
            else:
                if alias and not nome:
                    nome, fonte = sentence(alias), 'ricavato dalla cartella: DA VERIFICARE'
                    note.append('nome struttura non letto dalla scheda')
                elif not alias and x['Classe'] != 'Modulo / questionario':
                    nome, fonte = ('Anni Azzurri' if sito == 'aa' else 'KOS'), 'documento senza struttura: marchio'
                en = bool(re.search(r'(^|[ -_])(en|eng)$|english|leaflet|guide to|user|patients|service[- ]charter', stem, re.I))
                nome = re.sub(r'^(Villa Margherita) Vicenza \((Psichiatria|Riabilitazione)\)', r'\1 \2', nome)  # forma fissa per le due schede omonime
                doc = DOC_BREVE.get((alias, base)) or titolo_doc(x['Classe'], x['Sottotipo'], stem, p, en or lingua_it_en(p) == 'en')
                if x['Classe'] == 'Carta dei servizi' and re.search(r'service[- ]charter', stem, re.I):
                    doc = 'Service charter'
                t, nota = componi(doc, nome)
            if nota:
                note.append(nota)
            x['Titolo proposto (metadati del file)'] = t
            x['Caratteri'] = len(t)
            x['_nome'], x['_fonte'] = nome, fonte
            x['_nota_titolo'] = '; '.join(note)
        # 3. duplicati: una copia fa testo
        gruppi = collections.defaultdict(list)
        for x in righe:
            if x['_md5'] and x['Proposta'] == 'TIENI':
                gruppi[x['_md5']].append(x)
        for lst in gruppi.values():
            if len(lst) < 2:
                continue
            lst.sort(key=lambda x: (-(x['Impressioni'] or 0), len(x['Percorso']), x['Percorso']))
            for x in lst[1:]:
                x['_canonical'] = lst[0]['Percorso']
        # 4. unicità dei titoli
        cont = collections.defaultdict(set)
        for x in righe:
            if x['Proposta'] == 'TIENI' and x['Caratteri']:
                cont[x['Titolo proposto (metadati del file)']].add(x['_md5'] or x['Percorso'])
        for x in righe:
            if x['Proposta'] == 'TIENI' and x['Caratteri'] and len(cont[x['Titolo proposto (metadati del file)']]) > 1:
                x['_nota_titolo'] = (x.get('_nota_titolo') or '') + '; TITOLO DUPLICATO tra PDF diversi'
        righe.sort(key=lambda x: (ORDINE[x['Proposta']], x['Classe'], x['Percorso']))
        dati[sito] = righe
        if o.elenco_url:
            os.makedirs(o.elenco_url, exist_ok=True)
            al = sorted({alias_di(x['Percorso']) for x in righe if x['Proposta'] == 'TIENI' and alias_di(x['Percorso'])})
            base_url = 'https://anniazzurri.it/' if sito == 'aa' else None
            with open(os.path.join(o.elenco_url, 'url-strutture-%s.txt' % sito), 'w', encoding='utf-8') as fh:
                if sito == 'aa':
                    fh.writelines(base_url + x + '\n' for x in al)
                else:
                    d = json.load(open(os.path.join(RADICE, 'kosgroup/dati/voce-24/mappatura-servizi-2026-10-25.json'), encoding='utf-8'))
                    uri = {s['uri'].strip('/').split('/')[-1]: s['uri'].strip('/') for s in d['schede'] if s.get('template') == 'strutture'}
                    fh.writelines('https://kosgroup.com/' + uri[x] + '\n' for x in al if x in uri)
    # ---- scrittura ----
    new = openpyxl.Workbook()
    ris = new.active
    ris.title = 'Riepilogo'
    fill = PatternFill('solid', fgColor='1F3864')
    colori = {'TIENI': 'E2F0D9', 'ESCLUDI': 'EDEDED'}
    extra = ['Nome struttura usato', 'Fonte del nome', 'Canonical verso', 'Note sul titolo', 'Rilascio di oggi']
    cols = [c for c in intest if c != 'Decisione di Danilo'] + extra + ['Decisione di Danilo']
    ATTESA = re.compile(r'il team|la nostra [eé]quipe|organizzazione|tirocin', re.I)
    for sito, nome_f in (('aa', 'AA'), ('kos', 'KOS')):
        ws = new.create_sheet(nome_f)
        ws.append(cols)
        for c in ws[1]:
            c.font = Font(bold=True, color='FFFFFF'); c.fill = fill; c.alignment = Alignment(wrap_text=True, vertical='top')
        for x in dati[sito]:
            att = x['Proposta'] == 'TIENI' and ATTESA.search(os.path.splitext(os.path.basename(x['Percorso']))[0])
            x['_rilascio'] = 'No: in attesa di KOS (call 3/11)' if att else ('Sì' if x['Proposta'] == 'TIENI' else 'Sì (solo noindex)')
            vals = []
            for c in cols:
                m = {'Nome struttura usato': '_nome', 'Fonte del nome': '_fonte', 'Canonical verso': '_canonical', 'Note sul titolo': '_nota_titolo', 'Rilascio di oggi': '_rilascio'}
                vals.append(x.get(m[c], '') if c in m else x.get(c))
            ws.append(vals)
            ws.cell(ws.max_row, 1).fill = PatternFill('solid', fgColor=colori[x['Proposta']])
        for i, w in enumerate([10, 24, 24, 34, 56, 9, 30, 62, 34, 36, 11, 8, 10, 40, 30, 8, 36, 22, 52, 34, 28, 52, 52, 26, 18], 1):
            ws.column_dimensions[get_column_letter(i)].width = w
        ws.freeze_panes = 'B2'; ws.auto_filter.ref = ws.dimensions
    for n in ('Dati personali', 'Senza file'):
        s = wb[n]; w2 = new.create_sheet(n)
        for r in s.iter_rows(values_only=True):
            w2.append(list(r))
        for c in w2[1]:
            c.font = Font(bold=True, color='FFFFFF'); c.fill = fill
        for i, col in enumerate(s.column_dimensions, 1):
            pass
        w2.freeze_panes = 'B2'; w2.auto_filter.ref = w2.dimensions
        for k, d in s.column_dimensions.items():
            w2.column_dimensions[k].width = d.width
    # aggiorna la proposta nel foglio Dati personali (percorso → decisione finale)
    fin = {x['Percorso']: x['Proposta'] for s in dati.values() for x in s}
    dp = new['Dati personali']
    hd = [c.value for c in dp[1]]
    for row in dp.iter_rows(min_row=2):
        p = row[hd.index('Percorso')].value
        if p in fin:
            row[hd.index('Proposta')].value = fin[p]
    ris.append(['Classificazione dei PDF — foglio con titoli e conteggi aggiornati (1/11/2026, dati Search Console 14/6/2025–23/10/2026)'])
    ris['A1'].font = Font(bold=True, size=13)
    ris.append(['Decisioni confermate da Danilo il 1/11 (parere nella colonna Note). TIENI = indicizzabile con titolo nei metadati · ESCLUDI = noindex. Titoli: max %d caratteri.' % MAX])
    ris.append([])
    for sito, nome_f in (('aa', 'AA'), ('kos', 'KOS')):
        r = dati[sito]
        ris.append([nome_f, 'PDF', 'Impressioni', 'Clic', 'Titoli > %d car.' % MAX, 'Canonical verso altro file', 'Esclusi dal rilascio di oggi'])
        for c in ris[ris.max_row]:
            c.font = Font(bold=True, color='FFFFFF'); c.fill = fill
        for d in ('TIENI', 'ESCLUDI'):
            s = [x for x in r if x['Proposta'] == d]
            ris.append(['  ' + d, len(s), sum(x['Impressioni'] or 0 for x in s), sum(x['Clic'] or 0 for x in s),
                        sum(1 for x in s if isinstance(x['Caratteri'], int) and x['Caratteri'] > MAX),
                        sum(1 for x in s if x.get('_canonical')), sum(1 for x in s if str(x.get('_rilascio', '')).startswith('No'))])
            ris.cell(ris.max_row, 1).fill = PatternFill('solid', fgColor=colori[d])
        ris.append(['  Totale', len(r), sum(x['Impressioni'] or 0 for x in r), sum(x['Clic'] or 0 for x in r)])
        ris.append([])
        cl = collections.Counter((x['Classe'], x['Proposta']) for x in r)
        ris.append(['  Per classe', 'PDF', 'Proposta'])
        for (c, d), n in sorted(cl.items(), key=lambda kv: (ORDINE[kv[0][1]], -kv[1])):
            ris.append(['  ' + c, n, d])
        ris.append([])
    ris.column_dimensions['A'].width = 46
    for c in 'BCDEFG':
        ris.column_dimensions[c].width = 16
    new.save(o.out)
    for sito in ('aa', 'kos'):
        r = dati[sito]
        t = [x for x in r if x['Proposta'] == 'TIENI']
        print('== %s: %d PDF — TIENI %d, ESCLUDI %d; canonical %d; titoli da rivedere %d (oltre %d car. %d, duplicati %d, nome non da scheda %d)' % (
            sito, len(r), len(t), len(r) - len(t), sum(1 for x in t if x.get('_canonical')),
            sum(1 for x in t if x.get('_nota_titolo')), MAX, sum(1 for x in t if isinstance(x['Caratteri'], int) and x['Caratteri'] > MAX),
            sum(1 for x in t if 'DUPLICATO' in (x.get('_nota_titolo') or '')), sum(1 for x in t if 'DA VERIFICARE' in str(x.get('_fonte') or '') or 'da verificare' in str(x.get('_fonte') or ''))))
    print('== Scritto: ' + o.out)


if __name__ == '__main__':
    main()
