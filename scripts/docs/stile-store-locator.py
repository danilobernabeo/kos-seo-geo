import re, shutil, os, sys
from xml.sax.saxutils import escape as esc
S='/tmp/claude-0/-home-claude/42d4706d-8997-5d46-a2d5-386dfec791fc/scratchpad/ci'
T=S+'/t'
BLU='20467B'; ROSSO='D2072A'; GR='5F6B7A'
def r(t,b=False,i=False,col=None,sz=None,font=None):
    pr=''
    if font: pr+=f'<w:rFonts w:ascii="{font}" w:hAnsi="{font}"/>'
    if b: pr+='<w:b/>'
    if i: pr+='<w:i/>'
    if col: pr+=f'<w:color w:val="{col}"/>'
    if sz: pr+=f'<w:sz w:val="{sz}"/><w:szCs w:val="{sz}"/>'
    return f'<w:r><w:rPr>{pr}</w:rPr><w:t xml:space="preserve">{esc(t)}</w:t></w:r>'
def rich(s, base_sz=21, col=None):
    # **grassetto** e `codice`
    out=''
    for part in re.split(r'(\*\*[^*]+\*\*|`[^`]+`)', s):
        if not part: continue
        if part.startswith('**'): out+=r(part[2:-2],b=True,sz=base_sz,col=col)
        elif part.startswith('`'): out+=r(part[1:-1],sz=base_sz-2,font='Consolas',col=col or '1A2031')
        else: out+=r(part,sz=base_sz,col=col)
    return out
def p(s,after=120,before=0,keep=False,align=None,sz=21,col=None,ind=None):
    pp=f'<w:spacing w:before="{before}" w:after="{after}" w:line="276" w:lineRule="auto"/>'
    if keep: pp='<w:keepNext/>'+pp
    if ind: pp+=f'<w:ind w:left="{ind}"/>'
    if align: pp+=f'<w:jc w:val="{align}"/>'
    return f'<w:p><w:pPr>{pp}</w:pPr>{rich(s,sz,col)}</w:p>'
def h(s,lvl=1):
    sz={0:40,1:30,2:24}[lvl]; col=BLU
    bd=f'<w:pBdr><w:bottom w:val="single" w:sz="6" w:space="4" w:color="{ROSSO}"/></w:pBdr>' if lvl==1 else ''
    before={0:0,1:280,2:200}[lvl]
    return f'<w:p><w:pPr><w:keepNext/>{bd}<w:spacing w:before="{before}" w:after="120"/><w:outlineLvl w:val="{max(lvl-1,0)}"/></w:pPr>{r(s,b=(lvl!=0),col=col,sz=sz)}</w:p>'
def bullet(s,sz=21):
    return (f'<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="357"/></w:tabs><w:spacing w:before="0" w:after="80" w:line="276" w:lineRule="auto"/>'
            f'<w:ind w:left="357" w:hanging="357"/></w:pPr>{r("–",col=ROSSO,sz=sz)}<w:r><w:tab/></w:r>{rich(s,sz)}</w:p>')
def cell(content,w,fill=None,vcenter=True):
    sh=f'<w:shd w:val="clear" w:color="auto" w:fill="{fill}"/>' if fill else ''
    va='<w:vAlign w:val="center"/>' if vcenter else ''
    return f'<w:tc><w:tcPr><w:tcW w:w="{w}" w:type="dxa"/>{sh}{va}</w:tcPr>{content}</w:tc>'
def cp(s,b=False,sz=18,col=None,align=None):
    jc=f'<w:jc w:val="{align}"/>' if align else ''
    body=r(s,b=True,sz=sz,col=col) if b else rich(s,sz,col)
    return f'<w:p><w:pPr><w:spacing w:before="20" w:after="20"/>{jc}</w:pPr>{body}</w:p>'
def table(widths,head,rows,swatch_col=None):
    tw=sum(widths)
    grid=''.join(f'<w:gridCol w:w="{w}"/>' for w in widths)
    bd='<w:tblBorders><w:top w:val="single" w:sz="4" w:color="C9D2DB"/><w:bottom w:val="single" w:sz="4" w:color="C9D2DB"/><w:insideH w:val="single" w:sz="4" w:color="C9D2DB"/></w:tblBorders>'
    x=f'<w:tbl><w:tblPr><w:tblW w:w="{tw}" w:type="dxa"/>{bd}<w:tblLayout w:type="fixed"/><w:tblCellMar><w:left w:w="90" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>{grid}</w:tblGrid>'
    x+='<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>'+''.join(cell(cp(c,b=True,sz=17,col='FFFFFF'),w,fill=BLU) for c,w in zip(head,widths))+'</w:tr>'
    for row in rows:
        x+='<w:tr><w:trPr><w:cantSplit/></w:trPr>'
        for k,(c,w) in enumerate(zip(row,widths)):
            if swatch_col is not None and k==swatch_col:
                x+=cell(cp(''),w,fill=c.lstrip('#')).replace('<w:shd','<w:tcBorders><w:top w:val="single" w:sz="12" w:color="FFFFFF"/><w:left w:val="single" w:sz="4" w:color="C9D2DB"/><w:bottom w:val="single" w:sz="12" w:color="FFFFFF"/><w:right w:val="single" w:sz="4" w:color="C9D2DB"/></w:tcBorders><w:shd')
            else: x+=cell(cp(c),w)
        x+='</w:tr>'
    return x+'</w:tbl>'+p('',after=60)
def img(rid,cx_cm,cy_cm,n):
    cx=int(cx_cm*360000); cy=int(cy_cm*360000)
    return (f'<w:p><w:pPr><w:spacing w:before="60" w:after="160"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="{cx}" cy="{cy}"/><wp:docPr id="{n}" name="Logo {n}"/>'
            f'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="{n}" name="logo{n}.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            f'<pic:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>')
def pagebreak(): return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'

W=[1100,1500,2450,4580]  # 9630 = area utile
B=[]
B.append(p('',after=200))
B.append(h('Linee guida di stile per lo store locator',0))
B.append(p('kosgroup.com e anniazzurri.it · materiale per RetailTune',sz=26,col=GR,after=60))
B.append(p('Gruppo KOS · 6 novembre 2026 · a cura di Danilo Bernabeo',sz=19,col=GR,after=360))
B.append(h('Contenuto del documento'))
B.append(p('Il documento raccoglie i riferimenti di stile di **kosgroup.com** e **anniazzurri.it** per la personalizzazione grafica dello store locator e delle mappe: colori, tipografia, componenti (bottoni, card, campi), icone e spaziature. I valori sono quelli in uso sui due siti, ricavati dai fogli di stile pubblicati e dagli stili applicati alle pagine il 6 novembre 2026.'))
B.append(p('È diviso in tre parti: le indicazioni per la realizzazione, valide per entrambi i siti; i riferimenti di kosgroup.com; i riferimenti di anniazzurri.it. Gli store locator sono due, uno per sito, e ciascuno segue lo stile del sito che lo ospita: **kosgroup.com** ha un tono istituzionale e sobrio (bottoni squadrati, raggio 2 px, font Satoshi), **anniazzurri.it** è più morbido e caldo (bottoni a pillola, font Open Sans, blu e azzurri).'))
B.append(p('Il file allegato `stile-store-locator-kos.zip` contiene i loghi in SVG e PNG, un file di variabili CSS per sito e l\'elenco dei fogli di stile e dei font pubblicati.'))
B.append(h('Indicazioni per la realizzazione'))
B.append(p('Le indicazioni che seguono, come i colori suggeriti per segnaposto e schede nelle sezioni dei due siti, sono un punto di partenza e non un vincolo: siamo aperti alle vostre proposte, purché restino coerenti con i riferimenti di stile.'))
for s in [
 '**Font del sito.** Il widget usa il font della pagina che lo ospita (`font-family: inherit`) e non carica font propri: Satoshi e Open Sans sono già scaricati dai siti.',
 '**Colori come variabili.** Esponete i colori come variabili CSS sovrascrivibili, così possiamo correggerli dal nostro lato senza un nuovo rilascio.',
 '**Segnaposto (pin).** SVG nel colore primario del sito con un bordo bianco di 2 px per staccarlo dalla mappa; la struttura selezionata nel colore di accento. I gruppi di segnaposto (cluster) in colore primario con numero bianco.',
 '**Stile della mappa.** Mappa chiara e desaturata (grigi tenui), senza punti di interesse commerciali, in modo che risaltino solo le nostre strutture.',
 '**Schede e finestre informative.** Nome della struttura in grassetto, indirizzo, telefono, e un collegamento alla pagina della struttura sul nostro sito, come nelle mappe che usiamo oggi su anniazzurri.it.',
 '**Accessibilità.** Contrasto almeno AA (4,5:1 per il testo), focus visibile da tastiera su ricerca, filtri, elenco e segnaposto, testi alternativi sui segnaposto. I siti hanno già un widget di accessibilità (AccessiWay): il locator non deve interferire.',
 '**Prestazioni e consenso.** Mappa caricata solo quando entra nello schermo, spazio riservato in pagina per evitare spostamenti del layout. I siti usano Cookiebot: Google Maps e qualsiasi tracciamento vanno caricati nel rispetto del consenso.',
 '**SEO.** Il widget non deve inserire titoli H1, meta tag, canonical né dati strutturati JSON-LD: li genera il sito.',
]: B.append(bullet(s))

# ---------------- KOS
B.append(h('kosgroup.com'))
B.append(h('Colori',2))
B.append(table(W,['','Nome','Valore','Uso sul sito'],[
 ['#1A2031','Blu','#1A2031 · --blu','Testo e titoli'],
 ['#20467B','Blu logo','#20467B · --bluchiaro','Logo e dettagli istituzionali'],
 ['#115974','Azzurro','#115974 · --azzurro','Bottoni primari, link, testata'],
 ['#1B7F9D','Azzurro chiaro','#1B7F9D · --bs-primary','Bordo dei bottoni primari'],
 ['#2091B0','Ottanio','#2091B0','Accenti, icone, stati attivi'],
 ['#FFC107','Giallo «Prenota»','#FFC107','Solo bottone «Prenota», con testo #1A2031'],
 ['#D2072A','Rosso logo','#D2072A','Solo nel logo; #B10723 (--rosso) per errori'],
 ['#E5EBEF','Grigio 300','#E5EBEF · --grigio-300','Bordi di card e campi'],
 ['#F6F8FA','Grigio 100','#F6F8FA · --grigio-100','Fondi di sezione e footer'],
],swatch_col=0))
B.append(p('Proposta per lo store locator: segnaposto **#115974**, segnaposto selezionato **#2091B0**, testo **#1A2031**, bordi **#E5EBEF**, fondo dell\'elenco **#F6F8FA**.',sz=20))
B.append(h('Tipografia',2))
B.append(p('**Satoshi** in tre pesi: 400 (testo), 500 (titoli, menu), 700 (bottoni). File woff2 serviti dal sito in `/assets/fonts/`.'))
B.append(table([2400,1700,1700,3830],['Elemento','Dimensione','Peso','Note'],[
 ['Titolo pagina (H1)','36 / 44 px','500','spaziatura lettere -0,54 px'],
 ['Titolo sezione (H2)','24 / 32 px','500','spaziatura -0,48 px'],
 ['Titolo scheda (H3)','20 / 28 px','500',''],
 ['Testo','16 / 24 px','400','colore #1A2031'],
 ['Testo secondario, menu','14 px','400–500',''],
 ['Etichette','12 px','500','maiuscolo, spaziatura 0,12 px'],
 ['Bottoni','14 px','700','maiuscolo, spaziatura 0,4 px'],
]))
B.append(h('Componenti',2))
for s in [
 '**Bottone primario:** fondo #115974, testo bianco, padding 10 px, raggio 2 px; al passaggio del mouse si ingrandisce leggermente (`transform: scale(1.05)`).',
 '**Bottone secondario:** fondo bianco, bordo 1 px #E5EBEF, testo #1A2031.',
 '**Link testuali** («Clicca qui», «Leggi l\'articolo»): #115974, peso 500, sottolineati.',
 '**Card:** fondo bianco, bordo 1 px #E5EBEF, raggio 2 px, padding 16 px, nessuna ombra. **Campi e select:** bordo #E5EBEF, raggio 8 px.',
 '**Icone:** Font Awesome 5 Pro (light, regular, solid) e icone SVG lineari del sito (16–32 px, colore #1A2031 o #115974).',
 '**Spaziature:** scala da 8 px (8, 16, 24, 32, 48); breakpoint Bootstrap 5: 576, 768, 992, 1200, 1400 px.',
]: B.append(bullet(s))

# ---------------- AA
B.append(h('anniazzurri.it'))
B.append(img('rIdLogoAa',7.6,1.95,102))
B.append(h('Colori',2))
B.append(table(W,['','Nome','Valore','Uso sul sito'],[
 ['#003B77','Blu','#003B77 · --bs-primary','Titoli, bottoni primari, footer, testo delle card'],
 ['#001E3B','Blu notte','#001E3B · --bs-navy','Testo su fondi chiari'],
 ['#006E9C','Pavone','#006E9C · --bs-secondary','Colore secondario'],
 ['#005C85','Pavone fasce','#005C85 · .bg-peacock','Fasce e barra «Contattaci» come appaiono'],
 ['#0E5A91','Azzurro','#0E5A91 · .text-azure','Link «Visita la residenza», bottone azzurro'],
 ['#0098D7','Ceruleo','#0098D7 · --bs-cerulean','Accenti'],
 ['#C2E6F9','Cielo','#C2E6F9 · --bs-blue-sky','Fondi leggeri'],
 ['#F6FCFE','Ghiaccio','#F6FCFE · .bg-ice','Fondo della card «Contatti»'],
 ['#494949','Testo secondario','#494949 · --bs-brown','Paragrafi; testo base #212529'],
 ['#CE1531','Rosso logo','#CE1531','Solo nel logo'],
],swatch_col=0))
B.append(p('Proposta per lo store locator: segnaposto **#003B77**, segnaposto selezionato **#0098D7**, testo **#003B77** e **#494949**, fondo delle schede **#F6FCFE**.',sz=20))
B.append(h('Tipografia',2))
B.append(p('**Open Sans** in tre pesi: 400 (testo), 600 (titoli, link in evidenza), 700. File woff2 serviti dal sito in `/dist/assets/fonts/`. La radice è a 18 px (1 rem = 18 px) e i titoli usano la scala fluida di Bootstrap 5 (RFS). LobstersBlush, il corsivo delle firme nelle testimonianze, è decorativo: non usatelo nel locator. Le dimensioni in tabella sono misurate a 743 px di larghezza; su desktop i titoli crescono con la scala fluida.'))
B.append(table([2400,1700,1700,3830],['Elemento','Dimensione','Peso','Note'],[
 ['Titolo pagina (H1)','32–38 px','600','colore #003B77 (bianco sulle immagini)'],
 ['Titolo sezione (H2)','32 px','600',''],
 ['Titolo card (H3, H5)','21–22 px','600','colore #003B77'],
 ['Testo','16 px','400','interlinea 1,5; colore #494949 o #003B77'],
 ['Testo di servizio','13,5 px','400','briciole, barra superiore'],
 ['Bottoni','16 px','400','maiuscolo'],
]))
B.append(h('Componenti',2))
for s in [
 '**Bottoni a pillola:** raggio 30 px, bordo 2 px, maiuscolo, padding circa 16 × 32 px. Primario: fondo #003B77 e testo bianco. Outline: bordo e testo #003B77 su fondo trasparente, al passaggio del mouse diventa pieno. Variante azzurra: fondo #0E5A91.',
 '**Card:** raggio 8 px, senza bordo; la card «Contatti» ha fondo #F6FCFE e testo #003B77, padding 30 px.',
 '**Barra di ricerca:** a pillola, raggio 30 px, altezza 58 px, testo 18 px (oggi è la ricerca Yext in home).',
 '**Icone:** Bootstrap Icons, stile lineare, colore #0E5A91 (oggi nel box contatti: `bi-geo-alt`, `bi-envelope`, `bi-person`, `bi-telephone`; nei link: `bi-chevron-right`).',
 '**Mappe di oggi** (pagine regionali, es. /rsa-in-liguria): Google Maps con segnaposto standard; la finestra informativa mostra il nome in grassetto, l\'indirizzo e il link «VISITA LA RESIDENZA ›» in maiuscolo #0E5A91. Il nuovo locator le sostituirà e può partire da questa struttura.',
 '**Breakpoint:** Bootstrap 5: 576, 768, 992, 1200, 1400 px.',
]: B.append(bullet(s))

body=''.join(B)
x=open(T+'/word/document.xml').read()
i=x.index('<w:body>')+len('<w:body>'); j=x.index('<w:sectPr')
x=x[:i]+body+x[j:]
x=x.replace('w:top="1417"','w:top="2381"')
open(T+'/word/document.xml','w').write(x)
rels=open(T+'/word/_rels/document.xml.rels').read()
if 'rIdLogoKos' not in rels:
    rels=rels.replace('</Relationships>','<Relationship Id="rIdLogoKos" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo-kos.png"/><Relationship Id="rIdLogoAa" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo-aa.png"/></Relationships>')
    open(T+'/word/_rels/document.xml.rels','w').write(rels)
shutil.copy(S+'/logo-kos.png',T+'/word/media/logo-kos.png'); shutil.copy(S+'/logo-aa.png',T+'/word/media/logo-aa.png')
ct=open(T+'/[Content_Types].xml').read()
if 'Extension="png"' not in ct: ct=ct.replace('<Default Extension="xml"','<Default Extension="png" ContentType="image/png"/><Default Extension="xml"'); open(T+'/[Content_Types].xml','w').write(ct)
print('ok', len(body))
