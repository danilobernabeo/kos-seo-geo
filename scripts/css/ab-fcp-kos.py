"""Sprint 4: confronto A/B alternato della prima visualizzazione (FCP meno TTFB) su kosgroup.com, mobile 4G lenta e CPU 4x,
cache disattivata: «vecchio» = pagina di produzione con i 10 fogli originali rimessi nell head, «nuovo» = pagina com è.
Uso: python3 ab-fcp-kos.py <URL>. Terze parti (GTM, Cookiebot, HubSpot, Yext...) bloccate in entrambe le varianti."""
import asyncio, sys, statistics as st, re
from playwright.async_api import async_playwright
OLD='\n'.join(f'  <link href="/assets/css/{n}.css" rel="stylesheet" />' for n in ['bootstrap.min','all.min','fontawesome','default','nice-select','aos','swiper.min','photoswipe','style','custom'])
async def one(p, url, vecchio):
    b=await p.chromium.launch(executable_path='/usr/bin/google-chrome',args=['--no-sandbox'])
    ctx=await b.new_context(viewport={'width':412,'height':915}, is_mobile=True); pg=await ctx.new_page()
    cdp=await ctx.new_cdp_session(pg)
    await cdp.send('Network.enable'); await cdp.send('Network.setCacheDisabled',{'cacheDisabled':True})
    await cdp.send('Network.emulateNetworkConditions',{'offline':False,'latency':150,'downloadThroughput':1.6*1024*1024/8,'uploadThroughput':750*1024/8})
    await cdp.send('Emulation.setCPUThrottlingRate',{'rate':4})
    async def doc(route):
        r=await route.fetch(); h=await r.text()
        if vecchio: h=re.sub(r'<link href="/assets/css/kos\.min\.css[^>]*>', OLD, h)
        await route.fulfill(response=r, body=h)
    await pg.route(url, doc)
    await pg.route(re.compile(r'.*(googletagmanager|cookiebot|consent|clarity|hubspot|hs-|facebook|yext|sitescdn|acswapp).*'), lambda r: r.abort())
    await pg.goto(url, wait_until='load'); await pg.wait_for_timeout(1500)
    v=await pg.evaluate("[performance.getEntriesByName('first-contentful-paint')[0]?.startTime, performance.getEntriesByType('navigation')[0].responseStart]")
    await b.close(); return round(v[0]-v[1])
async def main():
    url=sys.argv[1]
    async with async_playwright() as p:
        res={'vecchio':[], 'nuovo':[]}
        for i in range(4):
            for k in res: res[k].append(await one(p,url,k=='vecchio'))
        print(url.split('.com')[1], {k:(st.median(v),v) for k,v in res.items()})
asyncio.run(main())
