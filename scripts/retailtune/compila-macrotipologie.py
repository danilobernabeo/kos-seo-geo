"""Compila il file RetailTune KOS-macrotipologie con macrotipologie, tipi e filtri dal sito (20/11/2026)."""
import json, re, collections, unicodedata, copy
import openpyxl
from openpyxl.styles import PatternFill, Font, Alignment
from openpyxl.utils import get_column_letter

REPO = '/home/claude/kos-seo-geo'
V24 = REPO + '/kosgroup/dati/voce-24'

def slug(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    s = re.sub(r"[^a-z0-9]+", '-', s).strip('-')
    return s

# ---------- dati del sito ----------
tipi = json.load(open('tipi.json'))
TIPO_LABEL = {
 'poliambulatori': 'Poliambulatori', 'punti-prelievo': 'Punti prelievo', 'ospedali': 'Ospedali', 'cliniche-dentali': 'Cliniche dentali',
 'centri-riabilitazione': 'Centri di riabilitazione', 'ambulatori-di-riabilitazione': 'Ambulatori di riabilitazione',
 'cliniche-psichiatriche': 'Cliniche psichiatriche', 'comunita-terapeutiche-psichiatriche': 'Comunità terapeutiche psichiatriche',
 'comunita-terapeutiche-riabilitative-per-minori': 'Comunità terapeutiche riabilitative per minori',
 'residenze-per-anziani': 'Residenze per anziani', 'hospice': 'Hospice'}
TIPO_AREA = {'poliambulatori': 'AMB', 'punti-prelievo': 'AMB', 'ospedali': 'AMB', 'cliniche-dentali': 'AMB',
 'centri-riabilitazione': 'RIA', 'ambulatori-di-riabilitazione': 'RIA',
 'cliniche-psichiatriche': 'PSI', 'comunita-terapeutiche-psichiatriche': 'PSI', 'comunita-terapeutiche-riabilitative-per-minori': 'PSI',
 'residenze-per-anziani': 'ANZ', 'hospice': 'ANZ'}
AREA_LABEL = {'AMB': 'Ambulatori e medicina specialistica', 'RIA': 'Riabilitazione', 'PSI': 'Psichiatria', 'ANZ': 'Residenze per anziani'}

def key(u):
    u = u.replace('https://kosgroup.com', '').replace('https://anniazzurri.it/', 'aa:')
    return u.strip('/').replace('it/centri/', 'kos:')

site_types = collections.defaultdict(set)
for t, links in tipi.items():
    for l in links:
        site_types[key(l)].add(t)

# associazioni strutture-servizi (25/10 + confermate 18/11)
mp = json.load(open(V24 + '/mappatura-servizi-2026-10-25.json'))['schede']
assoc = collections.defaultdict(set)
for x in mp:
    for e in x['dalla_scheda']:
        assoc[key('/' + x['uri'])].add(e['uri'].strip('/'))
for f in ['mappatura-confermate-20261118.txt', 'mappatura-confermate-carte-20261118.txt']:
    for line in open(V24 + '/' + f, encoding='utf-8'):
        if line.startswith('/'):
            a, b = line.strip().split('|')[:2]
            assoc[key(a)].add(b.strip('/'))

# gruppi e voci
P = 'it/servizi-e-cure/'
VISITE = [('ambulatorio-cardio-metabolico', 'Ambulatorio cardio-metabolico'), ('cardiologia', 'Cardiologia'), ('chirurgia-generale', 'Chirurgia generale'),
 ('maxillofacciale', 'Chirurgia maxillofacciale'), ('chirurgia-vascolare', 'Chirurgia vascolare'), ('dermatologia', 'Dermatologia'), ('dietologia', 'Dietologia'),
 ('endocrinologia', 'Endocrinologia'), ('fisiatria', 'Fisiatria'), ('flebologia', 'Flebologia'), ('gastroenterologia', 'Gastroenterologia'), ('ginecologia', 'Ginecologia'),
 ('medicina-dello-sport', 'Medicina dello sport'), ('neurochirurgia', 'Neurochirurgia'), ('neurologia', 'Neurologia'), ('oculistica', 'Oculistica'),
 ('odontoiatria', 'Odontoiatria'), ('oncologia', 'Oncologia'), ('ortopedia', 'Ortopedia'), ('otorinolaringoiatria', 'Otorinolaringoiatria'), ('pneumologia', 'Pneumologia'),
 ('podologia', 'Podologia'), ('psicologia', 'Psicologia'), ('reumatologia', 'Reumatologia'), ('urologia-andrologia', 'Urologia e andrologia')]
ESAMI = [('cardiodiagnostica', 'Cardiodiagnostica'), ('diagnostica-ecografica', 'Diagnostica ecografica'), ('elettroencefalografia', 'Elettroencefalografia'),
 ('elettromiografia', 'Elettromiografia'), ('laboratorio-analisi', 'Laboratorio analisi'), ('moc', 'MOC'), ('radiologia-convenzionale', 'Radiologia convenzionale'),
 ('risonanza-magnetica', 'Risonanza magnetica'), ('senologia-mammografia', 'Senologia e mammografia'), ('tac', 'TAC'), ('tc-cone-beam', 'TC Cone Beam')]
RIAB = [('riabilitazione-ortopedica', 'Riabilitazione ortopedica'), ('riabilitazione-neurologica', 'Riabilitazione neurologica'), ('riabilitazione-pneumologica', 'Riabilitazione pneumologica'),
 ('riabilitazione-cardiologica', 'Riabilitazione cardiologica'), ('riabilitazione-in-eta-evolutiva', 'Riabilitazione in età evolutiva'),
 ('riabilitazione-urologica-e-del-pavimento-pelvico', 'Riabilitazione urologica e del pavimento pelvico'), ('riabilitazione-gravi-cerebrolesioni', 'Riabilitazione gravi cerebrolesioni'),
 ('assistenza-stati-vegetativi-persistenti', 'Assistenza stati vegetativi persistenti'), ('riabilitazione-estensiva-e-generale-geriatrica', 'Riabilitazione estensiva e generale geriatrica'),
 ('riabilitazione-mielolesioni', 'Riabilitazione mielolesioni'), ('parkinson-e-disordini-del-movimento', 'Parkinson e disordini del movimento'),
 ('assistenza-persone-con-disabilita', 'Assistenza persone con disabilità'), ('riabilitazione-dipendenze', 'Riabilitazione da dipendenze'), ('riabilitazione-metabolica', 'Riabilitazione metabolica')]
PSI = [('disturbi-della-personalita', 'Disturbi della personalità'), ('disturbi-d-ansia', "Disturbi d'ansia"), ('psicosi', 'Psicosi'),
 ('disturbi-dell-umore-e-depressione', "Disturbi dell'umore e depressione"), ('schizofrenia', 'Schizofrenia'),
 ('disturbi-nutrizione-e-alimentazione', "Disturbi della nutrizione e dell'alimentazione"), ('dipendenze', 'Dipendenze'), ('disturbo-ossessivo-compulsivo', 'Disturbo ossessivo compulsivo'),
 ('disturbi-neurosviluppo-adhd', 'Disturbi del neurosviluppo e ADHD'), ('patologie-psicogeriatriche', 'Psicogeriatria'), ('disturbi-del-sonno', 'Disturbi del sonno'),
 ('disturbo-post-traumatico-da-stress', 'Disturbo post traumatico da stress')]

def srv_voce(u):
    """pagina servizio -> (area, gruppo, voce)"""
    m = re.match(r'it/servizi-e-cure/(ambulatori-e-medicina-specialistica|riabilitazione|psichiatria)/([^/]+)$', u)
    if not m: return None
    a, s = m.groups()
    if a == 'riabilitazione' and s == 'medicina-dello-sport':
        a = 'ambulatori-e-medicina-specialistica'  # una sola voce, nelle visite (decisione del 20/11)
    if a == 'ambulatori-e-medicina-specialistica':
        for k, l in VISITE:
            if k == s: return ('AMB', 'Visite specialistiche', l)
        for k, l in ESAMI:
            if k == s: return ('AMB', 'Esami e diagnostica', l)
    if a == 'riabilitazione':
        for k, l in RIAB:
            if k == s: return ('RIA', 'Tipi di riabilitazione', l)
    if a == 'psichiatria':
        for k, l in PSI:
            if k == s: return ('PSI', 'Disturbi trattati', l)
    return ('?', a, s)

# servizi AA
aa = json.load(open('aa-servizi.json'))
AA_CHI = [('Persone autosufficienti', r'^autosufficienti'), ('Persone con demenza', r'demenza'), ('Persone con disabilità', r'^disabili'),
          ('Persone con disturbi psichiatrici', r'psichiatric'), ('Stati vegetativi e gravi insufficienze respiratorie', r'vegetativ|comatoso|respiratori')]
AA_NUC = [('Nucleo Alzheimer', r'alzheimer'), ('Nucleo demenze', r'demenz|declino|decadimento|^nucleo protetto$'),
          ('Nucleo stati vegetativi', r'stati vegetativi'), ('Nucleo alta complessità neurologica', r'alta complessit'), ('Nucleo disabili', r'nucleo disabili')]
AA_SRV = [('Riabilitazione', r'riabilitazione|fisioterapia respiratoria'), ('Assistenza domiciliare post dimissione', r'domiciliare'), ('RSA Aperta', r'rsa aperta'),
          ('Cure intermedie', r'cure intermedie'), ('Visite ed esami', r'polo diagnostico|poliambulatorio|visite|esami|diagnostica'), ('Centro dialisi', r'dialisi')]

def aa_voci(slug_):
    d = aa.get(slug_)
    out = set()
    if not d: return None
    sv = [x.lower() for x in d.get('Servizi', [])]; cv = [x.lower() for x in d.get('Chi accogliamo', [])]
    for lab, rx in AA_CHI:
        if any(re.search(rx, x) for x in cv): out.add(('ANZ', 'Chi accogliamo', lab))
    for lab, rx in AA_NUC:
        if any(re.search(rx, x) for x in sv if x.startswith('nucleo') or x.startswith('posti')): out.add(('ANZ', 'Nuclei specialistici', lab))
    for lab, rx in AA_SRV:
        if any(re.search(rx, x) for x in sv): out.add(('ANZ', 'Servizi', lab))
    return out

# voci citate nel testo della scheda ma assenti dal riquadro dei servizi (decisione di Danilo, 20/11)
EXTRA_AA = {'residenza-anni-azzurri-santa-maria-in-chienti': {('ANZ', 'Nuclei specialistici', 'Nucleo Alzheimer'), ('ANZ', 'Nuclei specialistici', 'Nucleo demenze')}}
NOTE_AA = {'residenza-anni-azzurri-santa-maria-in-chienti': 'Nucleo Alzheimer e Nucleo demenze dal testo della scheda («Nucleo Protetto destinato a ospiti malati di Alzheimer o con gravi forme di demenza»), non ancora nel riquadro dei servizi: segnalato alla redazione'}

# prenotazione online: sedi TuoTempo (26/10) + campo str.prenotazione
PRENOTA = {'kos:centro-cardinal-ferrari', 'kos:centro-medico-riabilitativo', 'kos:centro-santo-stefano-cagli', 'kos:myolab', 'kos:poliambulatorio-san-martino',
 'kos:poliambulatorio-santo-stefano', 'kos:poliambulatorio-valdaso', 'kos:poliambulatorio-villa-pineta', 'kos:poliambulatorio-villalba', 'kos:punto-prelievi-gamma',
 'kos:punto-prelievi-salus-villalba', 'kos:villa-dei-pini-civitanova', 'kos:villa-margherita-vicenza', 'kos:villa-pineta', 'kos:villa-rosa-diagnostica',
 'kos:villa-san-giuseppe', 'kos:villa-sant-alessandro', 'kos:ville-di-nozzano', 'kos:poliambulatorio-parco-di-veio'}

# ---------- abbinamento righe del file -> pagine ----------
OVR = {'Villa dei Pini Civitanova Marche': 'kos:villa-dei-pini-civitanova', 'Villa dei Pini': 'kos:villa-dei-pini-firenze',
       'Villa Margherita Vicenza': 'kos:villa-margherita-vicenza', 'Laboratorio Analisi Gamma': 'kos:punto-prelievi-gamma',
       'Villalba': 'DOPPIONE', 'Residenza Anni Azzurri Polo Geriatrico Riabilitativo Milano': 'aa:residenza-anni-azzurri-pgr-milano',
       'Residenza Anni Azzurri Polo Geriatrico Riabilitativo Cinisello': 'aa:residenza-anni-azzurri-pgr-cinisello',
       "Residenza Anni Azzurri Sant'Ambrogio": 'aa:residenza-anni-azzurri-santambrogio', "Residenza Anni Azzurri Tonengo D'Asti": 'aa:residenza-anni-azzurri-tonengo',
       'Residenza Anni Azzurri Villa Pineta': 'aa:residenza-anni-azzurri-villa-pineta', 'Villa Pineta': 'kos:villa-pineta'}
VMV = ['kos:villa-margherita-vicenza-riabilitazione', 'kos:villa-margherita-vicenza-psichiatria']

def match(nome):
    if nome in OVR: return OVR[nome]
    if nome.startswith('Residenza Anni Azzurri'): return 'aa:' + slug(nome)
    return 'kos:' + slug(nome)

def site_info(k):
    """tipi, servizi (voci) della struttura k"""
    ks = VMV if k == 'kos:villa-margherita-vicenza' else [k]
    t = set(); v = set(); unknown = set()
    for kk in ks:
        t |= site_types.get(kk, set())
        for u in assoc.get(kk, ()):
            r = srv_voce(u)
            if r and r[0] != '?': v.add(r)
            elif r: unknown.add(u)
    if k.startswith('aa:'):
        r = aa_voci(k[3:])
        if r: v |= r
        v |= EXTRA_AA.get(k[3:], set())
    return t, v, unknown

# ---------- colonne ----------
COLS = []  # (area, gruppo, voce)
COLS.append(('TUTTE', 'Prenotazione', 'Prenotabile online'))
def addg(area, gruppo, voci): COLS.extend((area, gruppo, x) for x in voci)
addg('AMB', 'Tipo di struttura', ['Poliambulatori', 'Punti prelievo', 'Ospedali', 'Cliniche dentali'])
addg('AMB', 'Visite specialistiche', [l for _, l in VISITE])
addg('AMB', 'Esami e diagnostica', [l for _, l in ESAMI])
addg('RIA', 'Tipo di struttura', ['Centri di riabilitazione', 'Ambulatori di riabilitazione'])
addg('RIA', 'Tipi di riabilitazione', [l for _, l in RIAB])
addg('PSI', 'Tipo di struttura', ['Cliniche psichiatriche', 'Comunità terapeutiche psichiatriche', 'Comunità terapeutiche riabilitative per minori'])
addg('PSI', 'Disturbi trattati', [l for _, l in PSI])
addg('ANZ', 'Tipo di struttura', ['Residenze per anziani', 'Centri diurni', 'Hospice', 'Appartamenti per anziani'])
addg('ANZ', 'Chi accogliamo', [l for l, _ in AA_CHI])
addg('ANZ', 'Nuclei specialistici', [l for l, _ in AA_NUC])
addg('ANZ', 'Servizi', [l for l, _ in AA_SRV])

def build_row(k, extra_tipo=None):
    t, v, unknown = site_info(k)
    marks = set()
    areas = set()
    for x in t:
        areas.add(TIPO_AREA[x]); marks.add((TIPO_AREA[x], 'Tipo di struttura', TIPO_LABEL[x]))
    if extra_tipo:
        areas.add('ANZ'); marks.add(('ANZ', 'Tipo di struttura', extra_tipo))
    for a, g, l in v:
        marks.add((a, g, l)); areas.add(a)
    if k in PRENOTA: marks.add(('TUTTE', 'Prenotazione', 'Prenotabile online'))
    return areas, marks, unknown

# ---------- scrittura ----------
wb = openpyxl.load_workbook('modello.xlsx')
ws = wb['KOS']
# pulizia intestazioni oltre K
for mr in list(ws.merged_cells.ranges):
    if mr.min_col >= 12: ws.unmerge_cells(str(mr))
for c in range(12, ws.max_column + 1):
    for r in (1, 2): ws.cell(r, c).value = None

GIALLO = PatternFill('solid', fgColor='FFF2CC'); ARANCIO = PatternFill('solid', fgColor='FCE4D6'); GRIGIO = PatternFill('solid', fgColor='D9D9D9'); AZZURRO = PatternFill('solid', fgColor='DDEBF7')
AREA_FILL = {'TUTTE': 'E7E6E6', 'AMB': 'DDEBF7', 'RIA': 'E2EFDA', 'PSI': 'E9E1F2', 'ANZ': 'FCE4D6'}
c0 = 12
ws.cell(1, c0).value = 'Macrotipologie (4 valori: Ambulatori e medicina specialistica, Riabilitazione, Psichiatria, Residenze per anziani). Una per colonna'
ws.merge_cells(start_row=1, start_column=c0, end_row=1, end_column=c0 + 3)
for i in range(4): ws.cell(2, c0 + i).value = f'Macrotipologia {i+1}'
start = c0 + 4
# intestazioni dei gruppi
col_of = {}
prev = None; gstart = start
for i, (a, g, l) in enumerate(COLS):
    c = start + i; col_of[(a, g, l)] = c
    ws.cell(2, c).value = l
    ws.cell(2, c).fill = PatternFill('solid', fgColor=AREA_FILL[a])
    if (a, g) != prev:
        if prev is not None and c - 1 > gstart: ws.merge_cells(start_row=1, start_column=gstart, end_row=1, end_column=c - 1)
        gstart = c; prev = (a, g)
        lab = ('Valido per tutte le macrotipologie' if a == 'TUTTE' else 'Macrotipologia ' + AREA_LABEL[a]) + '\n' + g + '\nInserire X'
        ws.cell(1, c).value = lab
    ws.cell(1, c).fill = PatternFill('solid', fgColor=AREA_FILL[a])
if start + len(COLS) - 1 > gstart: ws.merge_cells(start_row=1, start_column=gstart, end_row=1, end_column=start + len(COLS) - 1)
for c in range(c0, start + len(COLS)):
    ws.cell(1, c).alignment = Alignment(wrap_text=True, vertical='top'); ws.cell(2, c).alignment = Alignment(wrap_text=True, vertical='top', text_rotation=90)
    ws.cell(1, c).font = Font(bold=True); ws.column_dimensions[get_column_letter(c)].width = 4.5
for c in range(c0, c0 + 4): ws.column_dimensions[get_column_letter(c)].width = 22; ws.cell(2, c).alignment = Alignment(wrap_text=True)
ws.row_dimensions[1].height = 75; ws.row_dimensions[2].height = 190
NOTE = start + len(COLS)
ws.cell(2, NOTE).value = 'Note (Danilo Bernabeo, 20/11)'; ws.column_dimensions[get_column_letter(NOTE)].width = 60
ws.cell(2, NOTE).font = Font(bold=True)

ORDER = ['AMB', 'RIA', 'PSI', 'ANZ']
log = []; seen = set()
rows_keys = {}
def write(r, k, extra_tipo=None, note=''):
    rows_keys[r] = [k]
    areas, marks, unknown = build_row(k, extra_tipo)
    for i, a in enumerate([x for x in ORDER if x in areas]): ws.cell(r, c0 + i).value = AREA_LABEL[a]
    for m in marks:
        if m in col_of: ws.cell(r, col_of[m]).value = 'X'
        else: log.append(('voce senza colonna', k, m))
    for u in unknown: log.append(('servizio non usato nei filtri', k, u))
    if note: ws.cell(r, NOTE).value = note
    return areas, marks

rows = [r for r in range(3, ws.max_row + 1) if ws.cell(r, 4).value]
for r in rows:
    nome = ws.cell(r, 4).value; tipo_rt = ws.cell(r, 3).value
    k = match(nome)
    if k == 'DOPPIONE':
        ws.cell(r, NOTE).value = ('Struttura contenitore di Poliambulatorio Villalba e Clinica Dentale Villalba (stessa sede): da tenere, '
            'serve alle schede Google (recensioni sul contenitore e sulle due strutture contenute). Proposta: non visibile nel widget '
            '(nessuna pagina sul sito, pin sovrapposto alle due strutture), quindi senza macrotipologie né filtri. Da confermare con RetailTune')
        for c in range(1, NOTE): ws.cell(r, c).fill = AZZURRO
        continue
    known = k in site_types or k == 'kos:villa-margherita-vicenza' or (k.startswith('aa:') and k[3:] in aa)
    if not known:
        ws.cell(r, NOTE).value = {'Residenza Anni Azzurri La Margherita': 'Struttura chiusa, da eliminare', 'Casa Sanfré': 'Struttura uscita dal perimetro, da eliminare'}.get(nome, 'Struttura non presente sul sito (nessuna pagina nelle tipologie): da confermare se è ancora aperta')
        for c in range(1, NOTE): ws.cell(r, c).fill = GRIGIO
        log.append(('non sul sito', nome, k)); continue
    seen.add(k)
    if k == 'kos:villa-margherita-vicenza': seen.update(VMV)
    note = []
    t, _, _ = site_info(k)
    site_tipi = sorted(TIPO_LABEL[x] for x in t)
    rt = sorted(TIPO_LABEL.get(x.lower().replace('_', '-').replace('laboratorio-analisi', 'punti-prelievo'), x) for x in str(tipo_rt).split(','))
    if site_tipi and site_tipi != rt: note.append('Tipo sul sito: ' + ', '.join(site_tipi) + ' (nel file: ' + ', '.join(rt) + ')')
    if k.startswith('aa:') and not aa.get(k[3:]): note.append('La scheda del sito non ha la sezione dei servizi: filtri da completare')
    if k.startswith('aa:') and k[3:] in NOTE_AA: note.append(NOTE_AA[k[3:]])
    if k == 'kos:villa-margherita-vicenza': note.append('Unisce le due schede del sito (riabilitazione e psichiatria); pagina principale /villamargherita')
    write(r, k, 'Residenze per anziani' if k.startswith('aa:') else None, '; '.join(note))

# righe da aggiungere
add = []
for t, links in tipi.items():
    for l in links:
        k = key(l)
        if k not in seen and not (k in VMV):
            add.append((k, None))
rep = json.load(open(REPO + '/anniazzurri/dati/schede/reparti-2026-11-17.json'))['risorse']
REP_TIPO = lambda u: 'Centri diurni' if u.startswith('centro-diurno') else ('Hospice' if u.startswith('hospice') else 'Appartamenti per anziani')
rep_by = {('aa:' + x['uri']): x for x in rep}
for k in list(dict.fromkeys(k for k, _ in add)):
    pass
added = []
r = max(rows) + 1
nid = max(int(ws.cell(x, 1).value) for x in rows) + 1
kos_res = {x['uri'].split('/')[-1]: x['tv'] for x in json.load(open(V24 + '/strutture-yext-2026-11-06.json'))['risorse']}
kos_res['santo-stefano-casorate'] = {'str.nome': 'Santo Stefano Casorate', 'str.via': 'Via Volontari del Sangue, 1', 'str.cap': '27022', 'str.citta': 'Casorate Primo', 'str.provincia': 'PV'}
kos_res['santo-stefano-mede'] = {'str.nome': 'Santo Stefano Mede', 'str.via': 'Viale dei Mille, 23', 'str.cap': '27035', 'str.citta': 'Mede', 'str.provincia': 'PV'}
RT_TIPO = {'centri-riabilitazione': 'Centri_riabilitazione', 'Hospice': 'Hospice', 'Centri diurni': 'Centri_diurni', 'Appartamenti per anziani': 'Appartamenti_per_anziani'}
def put_base(r, nome, via, citta, prov, cap, tipo):
    vals = {1: str(nid + len(added)), 2: '', 3: tipo, 4: nome, 5: via, 7: citta, 9: prov, 10: 'IT', 11: cap}
    for c, v in vals.items(): ws.cell(r, c).value = v
for k, _ in add:
    if k.startswith('aa:residenza-anni-azzurri-villa-olga') or k.startswith('aa:residenza-anni-azzurri-villalba'):
        log.append(('residenza solo nella pagina KOS, non tra le schede AA: non aggiunta', k, '')); continue
    if k.startswith('aa:hospice'):
        rr = rep_by.get(k)
        tv = rr['tv'] if rr else {}
        nome = 'Hospice ' + ' '.join(w if w in ('a','di') else w.capitalize() for w in k[3:].split('-')[1:])
        put_base(r, nome, tv.get('str.via', ''), tv.get('str.citta', ''), tv.get('str.provincia', ''), tv.get('str.cap', ''), 'Hospice')
        write(r, k, 'Hospice', 'Riga aggiunta: hospice presente sul sito' + ('' if rr else '; indirizzo da completare'))
    else:
        tv = kos_res.get(k[4:], {})
        nome = tv.get('str.nome') or ' '.join(w.capitalize() for w in k[4:].split('-'))
        put_base(r, nome, tv.get('str.via', ''), tv.get('str.citta', ''), tv.get('str.provincia', ''), tv.get('str.cap', ''), ','.join(RT_TIPO.get(x, x) for x in sorted(site_types[k])))
        write(r, k, None, 'Riga aggiunta: struttura presente sul sito' + ('' if tv else '; indirizzo da completare'))
    for c in range(1, NOTE): ws.cell(r, c).fill = GIALLO
    added.append(k); r += 1
for x in rep:
    k = 'aa:' + x['uri']
    if x['uri'].startswith('hospice'): continue
    tv = x['tv']; tp = REP_TIPO(x['uri'])
    nome = ' '.join(w.capitalize() for w in x['uri'].split('-'))
    if tp == 'Appartamenti per anziani': nome = 'Anni Azzurri Senior Living'
    nome = nome.replace('Anni Azzurri', 'Anni Azzurri')
    put_base(r, nome, tv.get('str.via', ''), tv.get('str.citta', ''), tv.get('str.provincia', ''), tv.get('str.cap', ''), RT_TIPO[tp])
    ws.cell(r, c0).value = AREA_LABEL['ANZ']; ws.cell(r, col_of[('ANZ', 'Tipo di struttura', tp)]).value = 'X'
    ws.cell(r, NOTE).value = 'Riga aggiunta: ' + {'Centri diurni':'centro diurno presente','Appartamenti per anziani':'appartamenti presenti'}[tp] + ' sul sito, reparto della ' + tv.get('str.sede', '').replace('-', ' ')
    for c in range(1, NOTE): ws.cell(r, c).fill = GIALLO
    added.append(k); r += 1
ws.freeze_panes = 'E3'

# foglio delle voci
wv = wb.create_sheet('Voci e codici')
wv.append(['Valido per', 'Gruppo', 'Voce', 'Codice proposto', 'Strutture con X'])
cnt = collections.Counter()
for rr in range(3, ws.max_row + 1):
    for m, c in col_of.items():
        if ws.cell(rr, c).value == 'X': cnt[m] += 1
PREF = {'Prenotazione': '', 'Tipo di struttura': 'tipo-', 'Visite specialistiche': 'visita-', 'Esami e diagnostica': 'esame-', 'Tipi di riabilitazione': 'riab-',
        'Disturbi trattati': 'disturbo-', 'Chi accogliamo': 'ospiti-', 'Nuclei specialistici': 'nucleo-', 'Servizi': 'servizio-'}
def code(g, l):
    c = slug(l)
    for p in ('riabilitazione-', 'nucleo-', 'persone-'):
        if c.startswith(p) and c != p.strip('-'): c = c[len(p):]
    return PREF[g] + c
for a in ['AMB', 'RIA', 'PSI', 'ANZ']:
    wv.append(['Tendina', 'Macrotipologia', AREA_LABEL[a], 'area-' + slug(AREA_LABEL[a]), sum(1 for rr in range(3, ws.max_row + 1) if AREA_LABEL[a] in [ws.cell(rr, c0 + i).value for i in range(4)])])
for (a, g, l) in COLS:
    wv.append(['Tutte le macrotipologie' if a == 'TUTTE' else AREA_LABEL[a], g, l, code(g, l), cnt[(a, g, l)]])
for c, w in zip('ABCDE', (34, 26, 48, 48, 16)): wv.column_dimensions[c].width = w
for cell in wv[1]: cell.font = Font(bold=True)
wb.save('KOS-macrotipologie-compilato.xlsx')
json.dump({'log': log, 'aggiunte': added}, open('log.json', 'w'), ensure_ascii=False, indent=1)
print('colonne filtri', len(COLS), 'righe aggiunte', len(added))
for x in log: print(x)
zero = [m for m in COLS if cnt[m] == 0]
print('voci senza strutture', zero)

# foglio di lettura
wl = wb.create_sheet('Leggimi', 0)
testo = [
 'File compilato da Danilo Bernabeo il 20/11/2026 con i dati dei siti kosgroup.com e anniazzurri.it (pagine delle tipologie, associazioni strutture-servizi, schede delle residenze).',
 '',
 'Struttura',
 '- Un solo foglio per tutte le strutture dei due siti. Macrotipologia = tendina di kosgroup.com. Per anniazzurri.it la tendina è il gruppo «Tipo di struttura» della macrotipologia Residenze per anziani.',
 '- Ogni gruppo di filtri vale solo per la sua macrotipologia (intestazione della riga 1 e colore della colonna). «Prenotabile online» vale per tutte.',
 '- Il foglio «Servizi Google» elenca, per ogni struttura, i servizi personalizzati da caricare sulla scheda Google: nome, descrizione (max 300 caratteri) e categoria attuale della scheda a cui legarli. Le categorie verranno riviste dopo la pubblicazione; i servizi si adegueranno.',
 '- Il foglio «Voci e codici» elenca gruppi, voci, codici proposti (da usare come parametri nell\'indirizzo) e numero di strutture.',
 '',
 'Regole',
 '- Una struttura appartiene a una macrotipologia se ha un tipo di quell\'area oppure offre almeno un servizio di quell\'area (es. Centro Cardinal Ferrari anche in Ambulatori per cardiologia e neurologia).',
 '- Tipi e servizi sono quelli pubblicati sul sito al 20/11/2026; nella colonna Note le differenze con la colonna «tipo» del file.',
 '- «Medicina dello sport» è una sola voce, nelle Visite specialistiche.',
 '- Nucleo Alzheimer e Nucleo demenze sono separati, come sulle schede delle residenze.',
 '- Prenotabile online: strutture con prestazioni su TuoTempo o con link di prenotazione nella scheda.',
 '',
 'Colori delle righe',
 '- Giallo: righe aggiunte (strutture presenti sul sito e assenti nel file).',
 '- Grigio: strutture non presenti sul sito, da eliminare (La Margherita chiusa, Casa Sanfré fuori perimetro).',
 '- Azzurro: struttura contenitore (Villalba), da tenere per le schede Google ma non visibile nel widget.',
]
for t in testo: wl.append([t])
wl.column_dimensions['A'].width = 150
for row in wl.iter_rows():
    for c in row:
        if c.value in ('Struttura', 'Regole', 'Colori delle righe'): c.font = Font(bold=True)
wb.save('KOS-macrotipologie-compilato.xlsx')

# ---------- foglio «Servizi Google» (servizi personalizzati delle schede) ----------
desc = json.load(open('srv/descrizioni.json'))
DESC_KOS = json.load(open('srv/descrizioni-servizi.json'))
def pulisci(d):
    if not d: return ''
    frasi = re.split(r'(?<=[.!?])\s+', d.strip())
    frasi = [f for f in frasi if not re.match(r'(?i)(scopri|prenota|contatta|chiama|vieni)', f)]
    t = ' '.join(frasi).strip()
    t = re.sub(r'\b(del|dal) Gruppo KOS\b|\bKos\b', '', t).replace('  ', ' ').strip(' :,')
    return t[:300]
DESC_EXTRA = {
 'Assistenza persone con disabilità': 'Percorsi riabilitativi e assistenziali per persone con disabilità, con progetto individuale e équipe multiprofessionale.',
 'Riabilitazione da dipendenze': 'Percorsi riabilitativi per persone con dipendenze, in collaborazione con i servizi del territorio.'}
NOME_PSI = lambda l: 'Cura ' + ({'Psicosi': 'delle psicosi', 'Schizofrenia': 'della schizofrenia', 'Dipendenze': 'delle dipendenze', 'Psicogeriatria': 'dei disturbi psicogeriatrici'}.get(l) or ('dei ' + l[0].lower() + l[1:] if l.startswith('Disturbi') else 'del ' + l[0].lower() + l[1:]))
DESC_AA = {
 'Nucleo Alzheimer': 'Nucleo protetto dedicato alle persone con malattia di Alzheimer, con spazi sicuri e attività di stimolazione cognitiva.',
 'Nucleo demenze': 'Nucleo protetto per persone con demenza, con assistenza dedicata e attività di stimolazione cognitiva.',
 'Nucleo stati vegetativi': 'Nucleo dedicato all’assistenza di persone in stato vegetativo, con assistenza sanitaria continua.',
 'Nucleo alta complessità neurologica': 'Posti dedicati a persone con patologie neurologiche ad alta complessità assistenziale.',
 'Nucleo disabili': 'Nucleo dedicato all’accoglienza di persone con disabilità fisiche e psichiche.',
 'Riabilitazione': 'Trattamenti di riabilitazione e fisioterapia per il recupero e il mantenimento dell’autonomia degli ospiti.',
 'Assistenza domiciliare post dimissione': 'Assistenza a domicilio dopo la dimissione, per accompagnare il rientro a casa.',
 'RSA Aperta': 'Servizi sociosanitari a domicilio per anziani non autosufficienti e persone con demenza, secondo la misura regionale RSA Aperta.',
 'Cure intermedie': 'Posti di cure intermedie per il recupero dopo un ricovero ospedaliero, prima del rientro a casa.',
 'Visite ed esami': 'Visite specialistiche ed esami diagnostici nella struttura.',
 'Centro dialisi': 'Centro dialisi interno alla struttura.',
 'Ricoveri temporanei e di sollievo': 'Ricoveri per periodi brevi, anche di sollievo per le famiglie o dopo un ricovero ospedaliero.',
 'Palestra e fisioterapia': 'Palestra attrezzata e fisioterapia per mantenere la mobilità e l’autonomia.'}
cat = {}
for f in ['gbp-aa.xlsx', 'gbp-ca.xlsx', 'gbp-extra.xlsx', 'gbp-poliambulatori.xlsx', 'gbp-psichiatria.xlsx', 'gbp-riabilitazione.xlsx']:
    for row in openpyxl.load_workbook(f, read_only=True).active.iter_rows(min_row=2, values_only=True):
        if row[2]: cat[row[2]] = (row[17] or '', row[18] or '')
        if row[3]: cat['nome:' + row[3]] = (row[17] or '', row[18] or '')
wsg = wb.create_sheet('Servizi Google', 3)
wsg.append(['codice_negozio', 'Struttura', 'Categoria principale attuale (Google)', 'Categorie aggiuntive attuali', 'Servizio (nome sulla scheda)', 'Descrizione (max 300 caratteri)', 'Pagina del sito'])
SERV_GROUPS = {'Visite specialistiche', 'Esami e diagnostica', 'Tipi di riabilitazione', 'Disturbi trattati', 'Nuclei specialistici', 'Servizi'}
nserv = 0
for rr in range(3, ws.max_row + 1):
    cod = ws.cell(rr, 2).value; nome = ws.cell(rr, 4).value
    if not nome or ws.cell(rr, 1).fill == GRIGIO: continue
    voci = []
    for (a, g, l), c in col_of.items():
        if g in SERV_GROUPS and ws.cell(rr, c).value == 'X': voci.append((a, g, l))
    # servizi delle residenze presenti in quasi tutte (non filtri, ma utili sulla scheda)
    k = None
    for x in rows_keys.get(rr, []): k = x
    if k and k.startswith('aa:'):
        d = aa.get(k[3:]) or {}
        sv = [x.lower() for x in d.get('Servizi', [])]
        if any(re.search(r'ricoveri (temporanei|estivi)', x) for x in sv): voci.append(('ANZ', 'Servizi', 'Ricoveri temporanei e di sollievo'))
        if any('palestra' in x for x in sv): voci.append(('ANZ', 'Servizi', 'Palestra e fisioterapia'))
    c1, c2 = cat.get(cod) or cat.get('nome:' + str(nome), ('', ''))
    for a, g, l in voci:
        if a == 'ANZ': nm, ds, url = l, DESC_AA.get(l, ''), ''
        else:
            info = desc.get(l, {}); url = info.get('url', '')
            nm = NOME_PSI(l) if g == 'Disturbi trattati' else l
            ds = DESC_KOS[l]
        wsg.append([cod, nome, c1, c2, nm, ds, url]); nserv += 1
for c, w in zip('ABCDEFG', (14, 46, 26, 40, 44, 90, 60)): wsg.column_dimensions[c].width = w
for cell in wsg[1]: cell.font = Font(bold=True)
wsg.freeze_panes = 'A2'
wb.save('KOS-macrotipologie-compilato.xlsx')
print('servizi Google', nserv)
