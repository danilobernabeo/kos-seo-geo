"""Sprint 4: confronto dei fogli costruiti con quelli di produzione SENZA stage. Carica la pagina di produzione due
volte (fogli originali / fogli del repository serviti al posto di quelli di produzione con page.route) e confronta
stili calcolati, posizioni e schermata intera. Uso: python3 confronta-css-kos.py <URL> [larghezza]. Variabili: CHROME (eseguibile),
BI181 (cartella font di bootstrap-icons 1.8.1 da `npm pack bootstrap-icons@1.8.1`, solo se jsdelivr non è raggiungibile).
Le differenze sotto il pixel (es. 325px → 324.984px) vengono dall'arrotondamento delle percentuali nel file minificato."""
import sys, os, json, asyncio
RADICE=os.path.abspath(os.path.join(os.path.dirname(__file__),'..','..')), re
from playwright.async_api import async_playwright
KOS=open(os.path.join(RADICE,'kosgroup/files/assets/css/kos.min.css'),encoding='utf-8').read()
URL=sys.argv[1]; W=int(sys.argv[2]) if len(sys.argv)>2 else 1366
JS='''() => { const out=[]; const props=['font-family','font-size','font-weight','line-height','letter-spacing','display','width','height','margin-top','padding-top','color','background-color','text-transform','-webkit-line-clamp','position','top'];
 const els=[...document.querySelectorAll('body *')].slice(0,3000);
 els.forEach((e,i)=>{const cs=getComputedStyle(e); const r=e.getBoundingClientRect(); out.push([i,e.tagName+'.'+(e.className&&e.className.baseVal===undefined?e.className:'').toString().slice(0,40), props.map(p=>cs.getPropertyValue(p)).join('|'), Math.round(r.x)+','+Math.round(r.y)+','+Math.round(r.width)+','+Math.round(r.height)]);}); return out; }'''
async def run(swap):
    async with async_playwright() as p:
        b=await p.chromium.launch(executable_path=os.environ.get('CHROME') or None, args=['--no-sandbox'])
        ctx=await b.new_context(viewport={'width':W,'height':900})
        page=await ctx.new_page()
        async def bi(route):
            u=route.request.url; base=os.environ.get('BI181','bi181/package/font/')
            if u.endswith('.css'): await route.fulfill(status=200, content_type='text/css', body=open(base+'bootstrap-icons.css').read())
            else:
                f='fonts/bootstrap-icons.woff2' if '.woff2' in u else 'fonts/bootstrap-icons.woff'
                await route.fulfill(status=200, content_type='font/woff2', body=open(base+f,'rb').read())
        await page.route('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/**', bi)
        if swap:
            async def h(route):
                n=route.request.url.split('/assets/css/')[1].split('?')[0]
                await route.fulfill(status=200, content_type='text/css', body=KOS if n=='bootstrap.min.css' else '')
            await page.route(re.compile(r'.*/assets/css/(bootstrap\.min|all\.min|fontawesome|default|nice-select|aos|swiper\.min|photoswipe|style|custom)\.css.*'), h)
        await page.goto(URL, wait_until='load'); await page.wait_for_timeout(4000)
        await page.evaluate('document.fonts.ready')
        st=await page.evaluate(JS)
        await page.screenshot(path=('new' if swap else 'old')+'.png', full_page=True)
        await b.close(); return st
async def main():
    a=await run(False); b=await run(True)
    print('elementi',len(a),len(b)); n=0
    for x,y in zip(a,b):
        ny=y[2].replace(', "Open Sans Fallback"','').replace(', "LobstersBlush Fallback"','')
        y=[y[0],y[1],ny,y[3]]
        if x[2]!=y[2] or x[3]!=y[3]:
            n+=1
            if n<=25:
                pa=x[2].split('|'); pb=y[2].split('|')
                diffs=[(i,pa[i],pb[i]) for i in range(len(pa)) if pa[i]!=pb[i]]
                print(x[1], x[3],'->',y[3], diffs[:4])
    print('diversi',n)
    from PIL import Image, ImageChops
    A=Image.open('old.png').convert('RGB'); B=Image.open('new.png').convert('RGB')
    if A.size!=B.size: print('dimensioni', A.size, B.size)
    else:
        d=ImageChops.difference(A,B).convert('L').point(lambda v:255 if v>16 else 0); bb=d.getbbox()
        print('pixel diversi %.3f%%'%(100*sum(d.histogram()[255:])/(A.size[0]*A.size[1])), 'box',bb)
asyncio.run(main())
