#!/usr/bin/env node
/**
 * KOS 15 — prova completa CON JavaScript: pagina com'è (A) contro HTML trasformato come nel pacchetto (B: CSS critico
 * nell'head, preload del foglio, foglio intero prima degli script in fondo al body). CLS fino a 4 s dopo il load, FCP e LCP
 * meno TTFB. Terze parti bloccate. Entrambe le varianti passano dallo stesso intercettore (stessi tempi di consegna dell'HTML).
 * Uso: node cls.mjs pagine.txt [--lento]   (--lento: rete 1,6 Mbit/s, 150 ms, CPU 4×)
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
const urls = fs.readFileSync(process.argv[2], 'utf8').split('\n').filter(Boolean);
const lento = process.argv.includes('--lento');
const critico = fs.readFileSync(new URL('critico.css', import.meta.url), 'utf8');
const VIEW = process.env.SOLO_MOBILE ? [{ w: 390, h: 844, m: true }] : [{ w: 390, h: 844, m: true }, { w: 1366, h: 768 }];
const b = await chromium.launch(process.env.CH ? { executablePath: process.env.CH } : {});
const dim = b => { // larghezza e altezza da JPEG, PNG o WebP (solo per la prova: lo snippet kosDimInfoImage usa getimagesize)
  if (b[0] === 0x89 && b[1] === 0x50) return [b.readUInt32BE(16), b.readUInt32BE(20)];
  if (b.toString('ascii', 0, 4) === 'RIFF') { const t = b.toString('ascii', 12, 16); if (t === 'VP8X') return [1 + b.readUIntLE(24, 3), 1 + b.readUIntLE(27, 3)]; if (t === 'VP8 ') return [b.readUInt16LE(26) & 0x3fff, b.readUInt16LE(28) & 0x3fff]; if (t === 'VP8L') { const n = b.readUInt32LE(21); return [1 + (n & 0x3fff), 1 + ((n >> 14) & 0x3fff)]; } }
  let i = 2; while (i < b.length) { if (b[i] !== 0xff) return null; const m = b[i + 1], l = b.readUInt16BE(i + 2); if (m >= 0xc0 && m <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(m)) return [b.readUInt16BE(i + 7), b.readUInt16BE(i + 5)]; i += 2 + l; } return null; };
const conDimensioni = async html => {
  const m = html.match(/(<div id="intro-pagina-img">[\s\S]{0,800}?<img src=")([^"]+)(" alt="[^"]*")/);
  if (!m || /width=/.test(m[0])) return html;
  try { const r = await fetch(new URL(m[2], 'https://kosgroup.com/')); const d = dim(Buffer.from(await r.arrayBuffer())); if (d) return html.replace(m[0], `${m[1]}${m[2]}${m[3]} width="${d[0]}" height="${d[1]}"`); } catch (e) {}
  return html;
};
const trasforma = html => {
  const m = html.match(/<link href="(\/assets\/css\/kos\.min\.css[^"]*)" rel="stylesheet" \/>/);
  if (!m) return null;
  const font = (process.env.FONT || 'satoshi-regular,satoshi-medium,satoshi-bold').split(',').map(f => `<link rel="preload" href="/assets/fonts/${f}.woff2" as="font" type="font/woff2" crossorigin>`).join('');
  html = html.replace(m[0], `${font}<style>${critico}</style>\n  <link rel="preload" href="${m[1]}" as="style">`);
  html = html.replace(/(<script src="\/assets\/js\/jquery\.min\.js)/, `<link rel="stylesheet" href="${m[1]}">\n$1`);
  return html;
};
const misura = async (url, v, nuovo) => {
  const ctx = await b.newContext({ viewport: { width: v.w, height: v.h }, isMobile: !!v.m, hasTouch: !!v.m });
  const p = await ctx.newPage();
  await p.addInitScript(() => { window.__cls = 0; window.__sh = [];
    new PerformanceObserver(l => { for (const e of l.getEntries()) if (!e.hadRecentInput) { window.__cls += e.value; window.__sh.push([e.value, (e.sources||[]).map(s => s.node && (s.node.id || s.node.className || s.node.nodeName)).join('|')]); } }).observe({ type: 'layout-shift', buffered: true });
    window.__lcp = 0; new PerformanceObserver(l => { const e = l.getEntries().pop(); window.__lcp = e.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true }); });
  if (lento) { const c = await ctx.newCDPSession(p); await c.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 1.6e6 / 8, uploadThroughput: 750e3 / 8 }); await c.send('Emulation.setCPUThrottlingRate', { rate: 4 }); }
  await p.route('**/*', r => { const u = r.request().url(); if (/googletagmanager|google-analytics|cookiebot|acsbapp|hubspot|sitescdn|doubleclick|facebook/.test(u)) return r.abort(); return r.continue(); });
  await p.route(url, async r => { const resp = await r.fetch(); const tx = await resp.text(); const h = nuovo ? await conDimensioni(trasforma(tx)) : tx; await r.fulfill({ response: resp, body: h ?? '', headers: { ...resp.headers(), 'content-type': 'text/html; charset=utf-8' } }); });
  await p.goto(url, { waitUntil: 'load', timeout: 90000 }); await p.waitForTimeout(4000);
  const r = await p.evaluate(() => ({ cls: window.__cls, sh: window.__sh, lcp: window.__lcp, fcp: (performance.getEntriesByName('first-contentful-paint')[0] || {}).startTime, ttfb: performance.getEntriesByType('navigation')[0].responseStart }));
  await ctx.close(); return r;
};
const out = [];
for (const u of urls) for (const v of VIEW) {
  try { const a = await misura(u, v, false), c = await misura(u, v, true);
    out.push({ u: u.replace('https://kosgroup.com', ''), w: v.w, clsA: +a.cls.toFixed(3), clsB: +c.cls.toFixed(3), fcpA: Math.round(a.fcp - a.ttfb), fcpB: Math.round(c.fcp - c.ttfb), lcpA: Math.round(a.lcp - a.ttfb), lcpB: Math.round(c.lcp - c.ttfb), shB: c.sh.slice(0, 3) });
  } catch (e) { out.push({ u, w: v.w, err: e.message.split('\n')[0] }); }
}
await b.close();
fs.writeFileSync(lento ? 'cls-lento.json' : 'cls.json', JSON.stringify(out, null, 1));
for (const r of out) console.log(r.err ? `ERR ${r.u}` : `${r.w} CLS ${r.clsA}→${r.clsB} FCP-TTFB ${r.fcpA}→${r.fcpB} LCP-TTFB ${r.lcpA}→${r.lcpB} ${r.u.slice(0, 70)} ${r.clsB > r.clsA + 0.01 ? JSON.stringify(r.shB) : ''}`);
