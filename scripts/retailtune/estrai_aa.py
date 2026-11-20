import re,glob,json,html
out={}
for f in sorted(glob.glob('aa/*.html')):
    s=open(f,encoding='utf-8',errors='ignore').read()
    s=re.sub(r'<script.*?</script>|<style.*?</style>','',s,flags=re.S)
    k=f.split('/')[-1][:-5]; i=s.find('Servizi sanitari')
    if i<0: out[k]=None; continue
    seg=s[i:]; j=re.search(r'<h2',seg[20:]); seg=seg[:j.start()+20] if j else seg[:20000]
    parts=re.split(r'<h3[^>]*>(.*?)</h3>',seg,flags=re.S); d={}
    for n in range(1,len(parts),2):
        name=' '.join(re.sub('<[^>]+>','',parts[n]).split())
        d[name]=[html.unescape(' '.join(re.sub('<[^>]+>','',x).split())) for x in re.findall(r'<li[^>]*>(.*?)</li>',parts[n+1],re.S)]
    if not d:
        # impaginazione senza h3: etichette in maiuscolo
        toks=[html.unescape(' '.join(t.split())) for t in re.split(r'<[^>]+>',seg)]; toks=[t for t in toks if t]
        cur=None
        for t in toks:
            u=t.upper()
            if u=='SERVIZI': cur='Servizi'; d[cur]=[]; continue
            if u=='CHI ACCOGLIAMO': cur='Chi accogliamo'; d[cur]=[]; continue
            if u.startswith('ÈQUIPE') or u.startswith('EQUIPE') or u.startswith('ÉQUIPE'): cur='Équipe'; d[cur]=[]; continue
            if cur: d[cur].append(t)
    out[k]=d or None
json.dump(out,open('aa-servizi.json','w'),ensure_ascii=False,indent=1)
print('senza servizi',[k for k,v in out.items() if not v])
print(out.get('residenza-anni-azzurri-villaggio-san-francesco'))
