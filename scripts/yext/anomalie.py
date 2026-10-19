import json, re, openpyxl, warnings
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter
from openpyxl.worksheet.datavalidation import DataValidation
warnings.filterwarnings('ignore')
import os
SP=os.environ.get('KOS_SCRATCH','./')
R=json.load(open(SP+'aa_in/residenze.json'))
wb0=openpyxl.load_workbook(SP+'yext_in/yext.xlsx',read_only=True)
rows=list(wb0.worksheets[0].iter_rows(values_only=True)); h=rows[0]
Y={str(r[h.index('ID entità')]):dict(zip(h,r)) for r in rows[1:]}
g=lambda e,k: '' if e.get(k) is None else str(e.get(k)).strip()

# ---------- Email direttori ----------
E=[]
res=[r for r in R if r['templatename']=='rsa']
for r in res:
    e=Y.get((r['yext_id'] or '').strip())
    if not e: continue
    a=(r['email_direttore'] or '').strip().lower(); b=g(e,'Email Direttore').lower()
    if g(e,'ID entità')=='KCA08202': continue
    if any(x[0]==g(e,'ID entità') for x in E): continue
    if a and a!=b: E.append((g(e,'ID entità'),r['pagetitle'],r['nome_direttore'] and r['nome_direttore'].strip() or '',a,b))

NE=len(E)
# ---------- Da correggere su Yext ----------
Y_ROWS=[
# marchio, id, struttura, campo, valore attuale Yext, problema, azione, priorità
("KOS","54 entità (foglio \"Link CTA\")","Quasi tutte le strutture KOS","Secondary CTA > Link","URL su 7ydxba3m.modx.dev (ambiente di sviluppo), es. https://7ydxba3m.modx.dev/it/centri/casa-villa-maura","Il campo \"URL del sito web\" è corretto; il problema è solo nel campo CTA secondario (senza etichetta, tipo Altro), che punta all'ambiente di sviluppo. Probabilmente residuo di un import; non risulta usato da Google, ma non deve passare in RetailTune","Svuotare il campo (o sostituirlo con l'URL di produzione del foglio Link CTA). In alternativa: escluderlo dall'import RetailTune","Bassa"),
("KOS","Temporary 1","Poliambulatorio Villa Pineta","Primary CTA > Link","https://7ydxba3m.modx.dev/it/centri/poliambulatorio-villa-pineta","Come sopra, sul CTA principale (etichetta \"Poliambulatorio Villa Pineta\"); URL del sito web corretto","Come sopra","Bassa"),
("KOS","VPI00_VPM + Villalba","Poliambulatorio Villalba","Entità","Due entità con lo stesso sito web /it/centri/poliambulatorio-villalba","Doppione: RetailTune importerebbe due punti","Tenerne una (il sito usa \"Villalba\") e chiudere l'altra; sulla superstite: nome \"Poliambulatorio Villalba\", email poliambulatorio.villalba@kosgroup.com, tipologia Poliambulatori","Alta"),
("KOS","1009","Punto Prelievi Delta","Sito web, email, tipologia","Sito web vuoto, email vuota, tipologia vuota","Senza sito web l'entità non si abbina alla scheda","Sito web https://kosgroup.com/it/centri/punto-prelievi-delta; email delta@kosgroup.com; tipologia Punti prelievo","Alta"),
("KOS","KCA00_TU1","Corte Carcare","Tipologia","Comunità terapeutiche psichiatriche","Diversa dal sito","Comunità terapeutiche riabilitative per minori","Media"),
("KOS","KCA00_TU2","Corte Orero","Tipologia","Comunità terapeutiche psichiatriche","Diversa dal sito","Comunità terapeutiche riabilitative per minori","Media"),
("KOS","KCA00_OSP","Ospedale San Pancrazio","Tipologia","Centri riabilitazione","Sul sito anche Cliniche psichiatriche","Aggiungere Cliniche psichiatriche","Media"),
("KOS","KCA00_OVZ","Villa Azzurra","Tipologia, email","Tipologia: Cliniche psichiatriche; email: due indirizzi nello stesso campo","Sul sito anche Punti prelievo (punto prelievi aperto nel 2026); il campo email ne accetta uno","Aggiungere Punti prelievo; un solo indirizzo email (l'altro in un campo dedicato)","Media"),
("KOS","KCA08202","Villa Pineta","Tipologia","Centri riabilitazione","Sul sito anche Poliambulatori, ma il Poliambulatorio Villa Pineta ha già scheda ed entità proprie","Nessuna su Yext: proposta di togliere Poliambulatori dalla scheda del sito (da confermare)","Bassa"),
("KOS","JES00_JES","Myolab","Tipologia","Centri riabilitazione","Sul sito: Ambulatori di riabilitazione","Confermare quale è corretta","Media"),
("KOS","101","Clinica Dentale Porto Potenza Picena","Indirizzo","Via Aprutina, 194","Sul sito: Via Aprutina, 217 (stesso civico del Poliambulatorio Santo Stefano)","Confermare il civico","Media"),
("KOS","VPI00_DLS","Punto Prelievi Salus Villalba","Telefono","0733 1930310","Sul sito: 0733 184841","Confermare il numero","Media"),
("KOS","PDUrbino","Punto Prelievi Selemar","Email","polodiagnosticourbino@kosgroup.com","Sul sito: selemar@kosgroup.com","Confermare l'indirizzo","Media"),
("KOS","KCA00_OVJ","Villa Jolanda","Telefono","0731 780684","Sul sito: 0731 7806841 (una cifra in più)","Confermare il numero","Media"),
("KOS","KCA00_ASS","Centro Ambulatoriale Santo Stefano San Severino","Città","Taccoli","Taccoli è una frazione: Google e RetailTune cercano per comune","Città: San Severino Marche; Taccoli nella riga indirizzo","Media"),
("KOS","FID00_FIS, JES00_JES, VPI00_DLB, KCA00_OVP","Fidia, Myolab, Punto Prelievi Biemme, Villa dei Pini Firenze","ID di Google Place","Vuoto (presente invece il CID di Google)","Le entità sono collegate al profilo Google (c'è il CID) ma manca il Place ID, che serve alla mappa delle schede sul sito e all'abbinamento in RetailTune","Inserire il Place ID (si ricava dal Place ID Finder di Google cercando la struttura). In alternativa lo ricava Danilo dal CID","Bassa"),
("KOS","108, 106, 104, 105","Centro Medico Riabilitativo, Venerabile Marcucci, Villa Fastiggi, Villa Rosa Diagnostica","Email","Vuota","Sul sito l'email c'è","Aggiungere: centromedicoriabilitativo@, venerabilemarcucci@, villafastiggi@, diagnostica.villarosa@ (kosgroup.com)","Bassa"),
("KOS","1162064197903061649","Poliambulatorio Valdaso","Email, tipologia","Vuote","Sul sito: poliambulatorio.valdaso@anniazzurri.it, Poliambulatori","Aggiungere","Bassa"),
("KOS","KCA00_OVP","Villa dei Pini Firenze","Nome","Villa dei Pini","Sul sito: Villa dei Pini Firenze (esiste anche Villa dei Pini Civitanova)","Valutare il nome con la città, per distinguerle","Bassa"),
("AA","KCA00_BTS","Residenza Anni Azzurri La Margherita","Entità","Attiva; sito web in redirect verso /rsa-in-liguria","La residenza non esiste più (confermato 19/10)","Chiudere l'entità (non viene importata in RetailTune)","Alta"),
("AA","KCA00_VLB","Residenza Anni Azzurri Villalba","Entità","Già chiusa; sito web in 404","La residenza non esiste più (confermato 19/10)","Nessuna: già chiusa, non viene importata. Togliere il link se resta visibile","Bassa"),
("AA","102","Residenza Anni Azzurri Villa Pineta","Tipologia, email, telefono","Tipologia vuota, email vuota, telefono 0536 1803620","Entità incompleta; il sito oggi mostra i dati della clinica (KCA08202), telefono 0536 18031 come la carta dei servizi","Tipologia Residenze per anziani; email della residenza; confermare il telefono (0536 18031 se il centralino è unico)","Alta"),
("AA","KCA00_PGM","Residenza Anni Azzurri Polo Geriatrico Riabilitativo Milano","Sito web","https://anniazzurri.it/polo-geriatrico-riabilitativo-milano","Indirizzo in redirect 301","https://anniazzurri.it/residenza-anni-azzurri-pgr-milano","Media"),
("AA","KCA00_PGC","Residenza Anni Azzurri Polo Geriatrico Riabilitativo Cinisello","Sito web","https://anniazzurri.it/polo-geriatrico-riabilitativo-cinisello","Indirizzo in redirect 301","https://anniazzurri.it/residenza-anni-azzurri-pgr-cinisello","Media"),
("AA","KCA00_SEP","Residenza Anni Azzurri Sestri Ponente","Orari","Vuoti","Orari mancanti","Inserire gli orari","Media"),
("AA","2081992527790767769","Residenza Anni Azzurri Il Poggione","Orari","Vuoti","Orari mancanti","Inserire gli orari","Media"),
("AA","KCA00_DU2","Residenza Anni Azzurri Ducale 2 - 3","Nome","Residenza Anni Azzurri Ducale 2 - 3","Sul sito: Ducale 2-3","Allineare la grafia","Bassa"),
("AA",str(NE)+" entità","Vedi foglio \"Email direttori AA\"","Email Direttore","Diversa dal sito","Non si sa quale sia aggiornata","Indicare nel foglio dedicato l'indirizzo corretto","Media"),
]
# ---------- Decisioni ----------
D_ROWS=[
("KOS/AA","Centri diurni e hospice Anni Azzurri","4 centri diurni senza entità Yext (San Giuseppe, La Limonaia, Maremma, San Rocco); Biancospino usa l'entità di Il Giardino, l'Hospice Beati Coniugi Martin quella del PGR Milano","Punti separati su RetailTune con tipologia \"Centri diurni\" / \"Hospice\", stesso indirizzo della residenza, link alla propria pagina. I punti senza entità nascono direttamente su RetailTune"),
("KOS","Villa Margherita Vicenza (VMA00_OAR)","Un'entità Yext, due schede sul sito (riabilitazione e psichiatria); sito web Yext = https://kosgroup.com/it/villa-margherita","Un solo punto con link alla pagina di presentazione /it/villa-margherita (se resta) e tipologie Centri di riabilitazione + Cliniche psichiatriche + Comunità terapeutiche psichiatriche; telefono ed email della struttura (oggi diversi tra le due schede)"),
("KOS","Fisioclinics (KCA00_DPE) e Casa Sanfré (KCA00_SRF)","Nessuna scheda su kosgroup.com; link a polodiagnostico.it e neomesia.com (domini storici da reindirizzare). Fisioclinics risulta chiusa","Fisioclinics: non migrare se chiusa. Casa Sanfré: se attiva, serve una scheda sul sito (o un link alternativo) prima dell'import"),
("KOS","Sedi condivise","Più schede allo stesso indirizzo: Ascoli Piceno (2), Civitanova Marche (3), Macerata (3), Porto Potenza Picena (2)","Punti distinti sovrapposti sulla mappa, se RetailTune li gestisce; domanda inviata a RetailTune"),
("KOS","Strutture senza entità Yext","Santo Stefano Casorate, Santo Stefano Mede, Poliambulatorio Villa Pineta (Temporary 1), Villa Armonia (Temporary 7), Poliambulatorio Parco di Veio (oggi con l'entità della residenza AA)","Nuovi punti creati direttamente su RetailTune (già deciso)"),
("AA","Email dei direttori",str(NE)+" residenze con email diversa tra sito e Yext; in Yext anche il dominio non più in uso sstefano.it","Compilare il foglio \"Email direttori AA\"; il valore scelto va su Yext e da lì nel sito e su RetailTune"),
]
# ---------- Sul sito (a cura di Danilo) ----------
S_ROWS=[
("AA","Villa Pineta (pagina 90)","TV data-yext-id da KCA08202 a 102","Dopo la correzione dell'entità 102 su Yext"),
("AA","La Margherita, Villalba","Redirect 301 verso la pagina regionale (oggi La Margherita 302, Villalba 404)","Sprint 1"),
("AA","Mestre (pagina 81)","Tolto \"contenitore\": URL senza slash, la forma con slash risponde 301","Fatto 19/10"),
("AA","Casa Serena, Cit Turin, Il Poggione, PGR Milano, landing Poggione","Spazio iniziale nell'email direttore (TV)","Con l'import"),
("AA","Casteldonino","Due numeri nella TV telefono","Con l'import"),
("AA","San Sisto 2","Email personale nella TV email residenza (Yext: residenzasansisto2@)","Con l'import"),
("KOS","Laboratorio Analisi Gamma, Poliambulatorio Valdaso","idYext corrotto da Excel nella TV: 2067293087438676369 e 1162064197903061649","Da verificare nel manager"),
("KOS","Jesi, Tolentino, Venerabile Marcucci, Istituto Santo Stefano, Biemme, Selemar","Telefono nella TV con uno 0 in più dopo +39 (Yext corretto)","Con l'import da Yext"),
("KOS","Villa dei Pini Civitanova","Due numeri nella TV telefono","Con l'import"),
("KOS","Casa Sanremo","Città \"San Remo\" nella TV (Yext: Sanremo)","Con l'import"),
("KOS","Venerabile Marcucci, Villa Armonia, Villa Pineta","Sigla provincia mancante nell'indirizzo","Con l'import"),
("KOS","Mappature","Riferimenti a risorse eliminate (Beata Corte, Medicina dello Sport, ecc.) nelle TV map*","Sprint 2"),
]
# ---------- scrittura ----------
F='Arial'
H=Font(name=F,bold=True,color='FFFFFF'); HF=PatternFill('solid',fgColor='1F4E79')
N=Font(name=F,size=10); B=Font(name=F,size=10,bold=True)
IN=PatternFill('solid',fgColor='FFF2CC')
PR={'Alta':PatternFill('solid',fgColor='F8CBAD'),'Media':PatternFill('solid',fgColor='FFE699'),'Bassa':PatternFill('solid',fgColor='E2EFDA')}
th=Side(style='thin',color='BFBFBF'); BD=Border(left=th,right=th,top=th,bottom=th)
WR=Alignment(wrap_text=True,vertical='top')
wb=openpyxl.Workbook()
def sheet(title,head,data,widths,esito=None,prio_col=None):
    ws=wb.create_sheet(title)
    ws.append(head)
    for c in ws[1]: c.font=H; c.fill=HF; c.alignment=Alignment(wrap_text=True,vertical='center'); c.border=BD
    for row in data: ws.append(list(row))
    for row in ws.iter_rows(min_row=2):
        for c in row: c.font=N; c.alignment=WR; c.border=BD
    for i,w in enumerate(widths,1): ws.column_dimensions[get_column_letter(i)].width=w
    if prio_col:
        for r in range(2,ws.max_row+1):
            c=ws.cell(r,prio_col); c.fill=PR.get(c.value,PatternFill()); c.font=B
    if esito:
        for col in esito:
            for r in range(2,ws.max_row+1): ws.cell(r,col).fill=IN
    ws.freeze_panes='A2'; ws.auto_filter.ref=ws.dimensions
    return ws
ws=wb.active; ws.title='Leggimi'
L=[("Anomalie Yext da correggere prima della migrazione a RetailTune",B),
("kosgroup.com e anniazzurri.it — export Yext del 15 ottobre 2026 confrontato con i due siti (19 ottobre 2026)",N),
("",N),
("Perché",B),
("Dall'export Yext corretto si importano i dati anagrafici nei due siti (2-6 novembre) e in RetailTune. Ogni errore lasciato su Yext passa nei siti, nella mappa e nei profili Google.",N),
("Scadenza: correzioni su Yext entro venerdì 30 ottobre 2026.",B),
("",N),
("Fogli",B),
("Da correggere su Yext — interventi sulle entità, per priorità (Alta: blocca l'abbinamento o mostra un dato errato).",N),
("Decisioni — scelte da confermare prima dell'import, con la proposta.",N),
("Link CTA — 55 link dei campi CTA (non dell'URL del sito web, che è corretto) che puntano all'ambiente di sviluppo, con il link di produzione.",N),
("Email direttori AA — "+str(NE)+" residenze con indirizzo diverso tra sito e Yext: indicare quello corretto.",N),
("Sul sito — correzioni a cura di Danilo Bernabeo, per conoscenza.",N),
("",N),
("Come compilare",B),
("Scrivere solo nelle celle gialle (colonne Esito / Note KOS / Email corretta). Esito: Fatto, Non necessario, Da discutere.",N),
("Esempio: riga Corte Carcare → Esito \"Fatto\", Note KOS \"tipologia aggiornata il 22/10\".",N),
]
for t,f in L:
    ws.append([t]); ws.cell(ws.max_row,1).font=f; ws.cell(ws.max_row,1).alignment=Alignment(wrap_text=True)
ws.column_dimensions['A'].width=120; ws.cell(1,1).font=Font(name=F,size=14,bold=True)
CTA_IDS="KCA00_MAU, JES00_JES, KCA00_AJE, KCA00_ASB, KCA00_MIO, KCA00_OVA, KCA00_OVZ, KCA00_OVJ, KCA00_DEG, KCA00_OV1, KCA00_OCF, KCA00_APS, KCA00_APP, KCA00_OSP, KCA00_APO, KCA00_OSS, KCA00_CIM, KCA00_PER, KCA00_OSG, KCA00_AFA, KCA00_OAC, KCA00_SAM, KCA00_SAS, KCA00_AFI, KCA00_ORE, VPI00_VPS, VPI00_DLB, VMA00_OAR, KCA00_ACM, FID00_FIS, KCA00_ACA, KCA00_ASS, KCA00_ATO, KCA00_OFO, KCA00_OBE, KCA00_TU1, KCA00_SRM, KCA00_TU2, VMA00_OBN, KCA00_OVP, KCA00_AMA, KCA00_AMC, VPI00_VPM, Temporary 7, KCA08202, Temporary 1, PDUrbino, 104, 100, 101, 105, 107, 106, 108"
FATTI={'1009':'Corretto da Danilo Bernabeo il 19/10','KCA00_PGM':'Corretto da Danilo Bernabeo il 19/10','KCA00_PGC':'Corretto da Danilo Bernabeo il 19/10'}
Yd=[]
for i,r in enumerate(sorted(Y_ROWS,key=lambda r:({'Alta':0,'Media':1,'Bassa':2}[r[7]],r[0]))):
    Yd.append((i+1,)+r+(('Fatto',FATTI[r[1]]) if r[1] in FATTI else ('','')))
w1=sheet('Da correggere su Yext',['N.','Marchio','Entità Yext','Struttura','Campo','Valore attuale su Yext','Problema','Azione richiesta','Priorità','Esito','Note KOS'],Yd,[5,8,18,32,16,34,40,46,9,14,28],esito=(10,11),prio_col=9)
dv=DataValidation(type='list',formula1='"Fatto,Non necessario,Da discutere"',allow_blank=True); w1.add_data_validation(dv); dv.add(f'J2:J{w1.max_row}')
w2=sheet('Decisioni',['N.','Marchio','Tema','Situazione','Proposta','Esito','Note KOS'],[(i+1,)+r+('','') for i,r in enumerate(D_ROWS)],[5,8,30,55,60,14,30],esito=(6,7))
dv2=DataValidation(type='list',formula1='"Approvata,Da discutere,Altro (vedi note)"',allow_blank=True); w2.add_data_validation(dv2); dv2.add(f'F2:F{w2.max_row}')
w3=sheet('Email direttori AA',['Entità Yext','Residenza','Direttore (sito)','Email sul sito','Email su Yext','Email corretta'],sorted(E,key=lambda x:x[1]),[20,44,24,38,38,38],esito=(6,))
C=[]
for k,e in Y.items():
    for f in ('Primary CTA > Link','Secondary CTA > Link'):
        v=g(e,f)
        if 'modx.dev' in v:
            C.append((k,g(e,'Nome'),f.split(' >')[0],v,re.sub(r'https://[a-z0-9]+\.modx\.dev','https://kosgroup.com',v),''))
C.sort(key=lambda x:x[1])
sheet('Link CTA',['Entità Yext','Nome','Pulsante','Link attuale (sviluppo)','Link corretto proposto','Esito'],C,[20,44,12,62,58,14],esito=(6,))
w4=sheet('Sul sito',['Marchio','Strutture','Correzione','Quando'],S_ROWS,[8,48,70,22])
out=SP+'out/anomalie-yext-kos-anniazzurri.xlsx'; wb.save(out); print(out,len(Y_ROWS),len(D_ROWS),len(E),len(S_ROWS))
