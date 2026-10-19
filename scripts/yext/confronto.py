import json, re, collections, openpyxl, warnings
warnings.filterwarnings('ignore')
R=json.load(open('aa_in/residenze.json'))
wb=openpyxl.load_workbook('yext_in/yext.xlsx',read_only=True)
rows=list(wb.worksheets[0].iter_rows(values_only=True)); h=rows[0]
Y=[dict(zip(h,r)) for r in rows[1:]]
def g(r,k): v=r.get(k); return '' if v is None else str(v).strip()
def dig(x): return re.sub(r'\D','',x or '')
yid={g(y,'ID entità'):y for y in Y}
yurl={g(y,'URL del sito web > URL').rstrip('/'):y for y in Y if g(y,'URL del sito web > URL')}
res=[r for r in R if r['templatename'] in ('rsa','centro-diurno')]
used=set()
print('PAGINE', len(res))
for r in res:
    url='https://anniazzurri.it/'+(r['uri'] or '').rstrip('/')
    y=yid.get((r['yext_id'] or '').strip()); yu=yurl.get(url)
    pb=[]
    if not r['yext_id']: pb.append('TV data-yext-id vuota')
    elif not y: pb.append(f"id {r['yext_id']} non esiste in Yext")
    if y and yu and y is not yu: pb.append(f"id punta a {g(y,'Nome')}, ma l'entita con questo URL e' {g(yu,'ID entità')} {g(yu,'Nome')}")
    if y and not yu: pb.append(f"URL Yext dell'entita = {g(y,'URL del sito web > URL') or '(vuoto)'}")
    if (r['uri'] or '').endswith('/'): pb.append(f"uri con slash finale: {r['uri']}")
    e=y or yu
    if e:
        used.add(g(e,'ID entità'))
        t1,t2=dig(r['num_tel']),dig(g(e,'Telefono principale > Telefono'))
        if t1 and t2 and not t2.endswith(t1) and not t1.endswith(t2[-len(t1):]): pb.append(f"telefono sito {r['num_tel']} / Yext {g(e,'Telefono principale > Telefono')}")
        ed1,ed2=(r['email_direttore'] or '').strip().lower(), g(e,'Email Direttore').lower()
        if ed1 and ed1!=ed2: pb.append(f"email direttore sito {ed1} / Yext {ed2 or '(vuota)'}")
        er1,er2=(r['email_residenza'] or '').strip().lower(), g(e,'E-mail').lower()
        if er1 and er1!=er2: pb.append(f"email residenza sito {er1} / Yext {er2 or '(vuota)'}")
        if (r['email_direttore'] or '')!=(r['email_direttore'] or '').strip(): pb.append('spazio iniziale in email direttore (TV)')
        if r['pagetitle'].lower().replace("'",'')!=g(e,'Nome').lower().replace("'",''): pb.append(f"nome sito «{r['pagetitle']}» / Yext «{g(e,'Nome')}»")
    if pb: print(f"[{r['id']}] {r['alias']} ({r['templatename']}, {r['yext_id']}):\n   - "+"\n   - ".join(pb))
print('\nENTITA AA YEXT SENZA PAGINA RSA/CENTRO DIURNO')
for y in Y:
    if g(y,'Riferimento al brand > Nome')=='Anni Azzurri' or g(y,'Cartella')=='Anni Azzurri':
        if g(y,'ID entità') not in used: print('  ',g(y,'ID entità'),g(y,'Nome'),g(y,'URL del sito web > URL'))
print('\nID YEXT USATI DA PIU PAGINE (tutte, comprese landing)')
c=collections.defaultdict(list)
for r in R:
    if r['yext_id'] and r['yext_id'] in yid: c[r['yext_id']].append(f"{r['id']}:{r['alias']}")
for k,v in c.items():
    if len(v)>1: print('  ',k,v)
print('\nLANDING con id Yext: telefono TV diverso da Yext')
for r in R:
    if 'landing' in r['templatename'] and r['yext_id'] in yid:
        y=yid[r['yext_id']]; t1,t2=dig(r['num_tel']),dig(g(y,'Telefono principale > Telefono'))
        if t1 and t2 and t1[-6:]!=t2[-6:]: print('  ',r['alias'],r['num_tel'],'/',g(y,'Telefono principale > Telefono'))
