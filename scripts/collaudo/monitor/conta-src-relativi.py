import re, sys, urllib.request, concurrent.futures as cf
def get(u):
    try:
        return urllib.request.urlopen(urllib.request.Request(u, headers={'User-Agent':'Mozilla/5.0 kos-seo-geo verifica'}), timeout=30).read().decode('utf-8','replace')
    except Exception as e: return ''
def urls(sm):
    x=get(sm); locs=re.findall(r'<loc>([^<]+)</loc>',x); out=[]
    for l in locs:
        if l.endswith('.xml'): out+=urls(l)
        else: out.append(l)
    return out
sito=sys.argv[1]; U=urls(sito+'/sitemap.xml'); print(sito,len(U),'pagine')
pat=re.compile(r'\b(src|srcset|poster|data-src)="(?!https?:|//|/|data:|#|\[\[)([^"]+)"')
tot={}
def one(u):
    h=get(u); h=re.sub(r'<!--.*?-->','',h,flags=re.S); return u,[m.group(2) for m in pat.finditer(h)]
with cf.ThreadPoolExecutor(6) as ex:
    for u,f in ex.map(one,U):
        if f: tot[u]=f
print('pagine con src relativi:',len(tot), 'occorrenze:',sum(len(v) for v in tot.values()))
for u,v in list(tot.items())[:15]: print(' ',u.replace(sito,''),len(v),v[:2])
