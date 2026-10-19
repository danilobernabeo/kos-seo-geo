import sys, json
s=open(sys.argv[1],encoding='utf-8-sig').read().replace('\r','').replace('\n','')
NC=24; i=0; n=len(s); rows=[]
def field(i):
    # returns (value, new_i) ; value None if empty unquoted
    if i<n and s[i]=='"':
        j=s.index('"',i+1); return s[i+1:j], j+1
    j=i
    while j<n and s[j] not in ';"': j+=1
    v=s[i:j]; return (v if v else None), j
while i<n:
    rec=[]
    for k in range(NC):
        if k==NC-1 and len(rows)>0:
            v,j=field(i)
            if v is not None and v.isdigit() and j<n and s[j]==';':
                v=None   # campo vuoto: il valore e' l'id della riga successiva
            else:
                i=j
            rec.append(v); continue
        v,i=field(i); rec.append(v)
        if k<NC-1:
            assert s[i]==';', (i, s[i-30:i+30]); i+=1
    rows.append(rec)
h=rows[0]; R=[dict(zip(h,r)) for r in rows[1:]]
json.dump(R,open(sys.argv[2],'w'),ensure_ascii=False,indent=0)
print(len(R))
