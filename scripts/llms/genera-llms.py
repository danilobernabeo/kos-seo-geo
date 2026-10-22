"""Genera le bozze di llms.txt dei due siti dai dati estratti (non versionati).

Uso: python genera-llms.py <mappatura-kosgroup.xlsx> <residenze-aa.json> <export-yext.xlsx> <cartella-repo>
I testi descrittivi sono da validare con KOS prima della pubblicazione.
"""
import sys, re, json, collections, warnings, openpyxl
warnings.filterwarnings('ignore')
MAP, RES, YEXT, REPO = sys.argv[1:5]

# ---------------- dati ----------------
wb = openpyxl.load_workbook(MAP, read_only=True)
rows = list(wb['Strutture'].iter_rows(values_only=True)); h = rows[0]
K = [dict(zip(h, r)) for r in rows[1:]]
K = [k for k in K if k['Stato'] == 'pubblicata']

R = json.load(open(RES, encoding='utf-8'))
wy = openpyxl.load_workbook(YEXT, read_only=True)
yr = list(wy.worksheets[0].iter_rows(values_only=True)); yh = yr[0]
Y = {str(r[yh.index('ID entità')]): dict(zip(yh, r)) for r in yr[1:]}

def città(r):
    e = Y.get((r['yext_id'] or '').strip())
    c = bello((e.get('Indirizzo > Città') or '').strip()) if e else ''
    m = re.search(r'provincia di (.+)$', r['parent_titolo'] or '') or re.search(r'RSA a (\w+) e provincia', r['parent_titolo'] or '')
    sig = SIGLA.get(m.group(1).strip()) if m else ''
    return f"{c} ({sig})" if c and sig else c

SIGLA = {'Bologna': 'BO', 'Modena': 'MO', 'Reggio Emilia': 'RE', 'Roma': 'RM', 'Genova': 'GE', 'Imperia': 'IM',
         'Milano': 'MI', 'Monza e Brianza': 'MB', 'Bergamo': 'BG', 'Brescia': 'BS', 'Como': 'CO', 'Ancona': 'AN',
         'Ascoli Piceno': 'AP', 'Fermo': 'FM', 'Macerata': 'MC', 'Pesaro-Urbino': 'PU', 'Torino': 'TO', 'Cuneo': 'CN',
         'Novara': 'NO', 'Vercelli': 'VC', 'Asti': 'AT', 'Firenze': 'FI', 'Grosseto': 'GR', 'Venezia': 'VE', 'Rovigo': 'RO'}
MINUSC = {'In', 'Di', 'Del', 'Sul', 'Della', 'Dei', 'D'}
def bello(c):
    return ' '.join(w if i == 0 or w not in MINUSC else w.lower() for i, w in enumerate(c.split(' '))).replace(" D'", " d'")

# ---------------- kosgroup.com ----------------
SING = {'Cliniche psichiatriche': 'clinica psichiatrica', 'Centri di riabilitazione': 'centro di riabilitazione',
        'Ambulatori di riabilitazione': 'ambulatorio di riabilitazione', 'Poliambulatori': 'poliambulatorio',
        'Comunità terapeutiche psichiatriche': 'comunità terapeutica psichiatrica',
        'Comunità terapeutiche riabilitative per minori': 'comunità terapeutica riabilitativa per minori',
        'Punti prelievo': 'punto prelievi', 'Ospedali': 'ospedale', 'Cliniche dentali': 'clinica dentale',
        'Residenze per anziani': 'residenza per anziani', 'Hospice': 'hospice'}
B = 'https://kosgroup.com'
out = []
out += ['# KOS', '',
        '> KOS S.p.A. è un gruppo italiano di servizi sanitari e socio-sanitari: centri di riabilitazione, '
        'cliniche e comunità psichiatriche, ambulatori, poliambulatori e punti prelievo. Le residenze per anziani '
        'del gruppo sono presentate sul sito anniazzurri.it (KOS Care S.r.l.).', '',
        'Questo file elenca le pagine principali di kosgroup.com per aiutare i sistemi di intelligenza artificiale '
        'a trovare informazioni aggiornate su strutture, servizi e contatti. La scheda di ogni struttura riporta '
        'indirizzo, telefono, servizi e modalità di accesso.', '']
out += ['## Servizi e cure', '',
        f'- [Riabilitazione]({B}/it/servizi-e-cure/riabilitazione): aree e trattamenti di riabilitazione',
        f'- [Psichiatria]({B}/it/servizi-e-cure/psichiatria): percorsi di cura psichiatrici',
        f'- [Ambulatori e medicina specialistica]({B}/it/servizi-e-cure/ambulatori-e-medicina-specialistica): visite ed esami specialistici', '']
out += ['## Strutture per tipologia', '',
        f'- [Centri di riabilitazione]({B}/it/strutture/centri-riabilitazione)',
        f'- [Ambulatori di riabilitazione]({B}/it/strutture/ambulatori-di-riabilitazione)',
        f'- [Cliniche psichiatriche]({B}/it/strutture/cliniche-psichiatriche)', '']
out += ['## Strutture', '']
byreg = collections.defaultdict(list)
REGN = {'Emilia Romagna': 'Emilia-Romagna'}
for k in K: byreg[REGN.get(k['Regione (TV)'], k['Regione (TV)']) or 'Altro'].append(k)
for reg in sorted(byreg):
    out.append(f'### {reg}'); out.append('')
    for k in sorted(byreg[reg], key=lambda x: x['Nome (pagetitle)']):
        tip = ', '.join(SING.get(t.strip(), t.strip().lower()) for t in re.split(r'[|,]', k['Tipologie'] or '') if t.strip())
        loc = bello(k['Città'] or '')
        loc = re.sub(r'\s*\(([^)]*)$', r', \1', loc)  # parentesi non chiusa nel dato (es. «San Severino Marche (Taccoli»)
        sig = k['Sigla da indirizzo'] or k['Sigla da provincia'] or ''
        out.append(f"- [{k['Nome (pagetitle)']}]({k['URL canonico']}): {tip}{', ' if tip else ''}{loc}{f' ({sig})' if sig else ''}")
    out.append('')
out += ['## Chi siamo', '',
        f'- [Visione, valori e missione]({B}/it/chi-siamo/gruppo-kos/visione-valori-missione)',
        f'- [La nostra storia]({B}/it/chi-siamo/gruppo-kos/la-nostra-storia)',
        f'- [Il management]({B}/it/chi-siamo/governance/il-management)',
        f'- [Cura delle persone]({B}/it/chi-siamo/qualita-delle-cure/cura-delle-persone)', '',
        '## Lavorare in KOS', '',
        f'- [Perché lavorare in KOS]({B}/it/lavorare-in-kos/perche-lavorare-in-kos)',
        f'- [Formazione KOS Academy]({B}/it/lavorare-in-kos/formazione-kos-academy)', '',
        '## Optional', '',
        f'- [Approfondimenti]({B}/it/news-e-media/approfondimenti): articoli divulgativi su patologie e percorsi di cura',
        f'- [News]({B}/it/news-e-media/news)',
        f'- [English version]({B}/en)',
        '- [Anni Azzurri](https://anniazzurri.it/): residenze per anziani del gruppo KOS', '']
open(f'{REPO}/kosgroup/files/llms.txt', 'w', encoding='utf-8').write('\n'.join(out))

# ---------------- anniazzurri.it ----------------
A = 'https://anniazzurri.it'
REG = {'Le RSA in Lombardia': ('Lombardia', 'rsa-in-lombardia'), 'Le RSA Anni Azzurri in Piemonte': ('Piemonte', 'rsa-in-piemonte'),
       'Le RSA Anni Azzurri in Liguria': ('Liguria', 'rsa-in-liguria'), 'Le RSA Anni Azzurri nelle Marche': ('Marche', 'rsa-nelle-marche'),
       'Le RSA Anni Azzurri in Emilia-Romagna': ('Emilia-Romagna', 'rsa-in-emilia-romagna'), 'RSA Anni Azzurri in Veneto': ('Veneto', 'rsa-in-veneto'),
       'RSA Anni Azzurri in Toscana': ('Toscana', 'rsa-in-toscana'), 'Le RSA nel Lazio': ('Lazio', 'rsa-nel-lazio')}
res = [r for r in R if r['templatename'] == 'rsa' and r['alias'].startswith('residenza-anni-azzurri') and r['published'] == '1']
o = ['# Anni Azzurri', '',
     '> Anni Azzurri è la rete di residenze sanitarie assistenziali (RSA) per anziani del gruppo KOS, gestita da KOS Care S.r.l. '
     f'Conta {len(res)} residenze in {len(REG)} regioni italiane, oltre a centri diurni, un hospice e servizi di assistenza domiciliare.', '',
     'Questo file elenca le pagine principali di anniazzurri.it per aiutare i sistemi di intelligenza artificiale a trovare '
     'informazioni aggiornate. La pagina di ogni residenza riporta indirizzo, contatti, servizi, tipologie di ospiti accolti e modalità di accesso.', '',
     '## Trovare una residenza', '',
     f'- [Tutte le residenze]({A}/residenze)']
PREP = {'Lazio': 'nel Lazio', 'Marche': 'nelle Marche'}
for t, (n, al) in sorted(REG.items(), key=lambda x: x[1][0]):
    o.append(f"- [RSA {PREP.get(n, 'in ' + n)}]({A}/{al})")
o += ['', '## Residenze per anziani', '']
byr = collections.defaultdict(list)
for r in res: byr[REG.get(r['nonno_titolo'], (r['nonno_titolo'], ''))[0]].append(r)
for n in sorted(byr):
    o += [f'### {n}', '']
    for r in sorted(byr[n], key=lambda x: x['pagetitle']):
        c = città(r)
        o.append(f"- [{r['pagetitle']}]({A}/{r['uri'].rstrip('/')}): RSA{', ' + c if c else ''}")
    o.append('')
cd = [r for r in R if r['templatename'] == 'centro-diurno' or r['alias'].startswith('hospice')]
o += ['## Centri diurni e hospice', '']
for r in sorted(cd, key=lambda x: x['pagetitle']):
    o.append(f"- [{r['pagetitle']}]({A}/{r['uri'].rstrip('/')})")
o += ['', '## Servizi', '',
      f'- [Cure e assistenza domiciliare]({A}/cure-e-assistenza-domiciliare)',
      f'- [Cure domiciliari con il Servizio Sanitario]({A}/assistenza-e-cure-domiciliari-gratuite-con-il-servizio-sanitario)',
      f'- [Cure domiciliari in regime privato]({A}/assistenza-e-cure-domiciliari-in-regime-privato)',
      f'- [RSA Aperta]({A}/rsa-aperta)', '',
      '## Guide per le famiglie', '',
      f'- [Cosa sono le RSA]({A}/cosa-sono-le-rsa)',
      f'- [Differenza tra RSA e casa di riposo]({A}/differenza-tra-rsa-e-casa-di-riposo)',
      f'- [Tipologie di strutture per anziani]({A}/quali-sono-le-tipologie-di-strutture-per-anziani)',
      f'- [Accesso in RSA: chi può richiederlo e come funziona]({A}/accesso-in-rsa-chi-puo-richiederlo-e-come-funziona)',
      f'- [Quanto costa una casa di riposo]({A}/quanto-costa-una-casa-di-riposo)',
      f'- [Differenza tra RSA e hospice]({A}/rsa-e-hospice-differenza)',
      f'- [Cosa portare in RSA]({A}/cosa-portare-in-rsa)', '',
      '## Optional', '',
      f'- [News e approfondimenti]({A}/news/)',
      '- [KOS](https://kosgroup.com/it): il gruppo di cui fa parte Anni Azzurri', '']
open(f'{REPO}/anniazzurri/files/llms.txt', 'w', encoding='utf-8').write('\n'.join(o))
print('ok', len(K), len(res), len(cd))
