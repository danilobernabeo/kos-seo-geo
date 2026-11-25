#!/usr/bin/env node
/**
 * KOS 15 — diagnosi di uno spostamento: elenca gli spostamenti (istante, valore, elementi con posizione e altezza prima/dopo)
 * di una pagina a 390 px, com'è (argomento a) o trasformata come nel pacchetto (b). Uso: node traccia.mjs <url> b
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
const critico = fs.readFileSync(new URL('critico.css', import.meta.url), 'utf8');
const dim = b => { // larghezza e altezza da JPEG, PNG o WebP (solo per la prova: lo snippet kosDimInfoImage usa getimagesize)
  if (b[0] === 0x89 && b[1] === 0x50) return [b.readUInt32BE(16), b.readUInt32BE(20)];
  if (b.toString('ascii', 0, 4) === 'RIFF') { const t = b.toString('ascii', 12, 16); if (t === 'VP8X') return [1 + b.readUIntLE(24, 3), 1 + b.readUIntLE(27, 3)]; if (t === 'VP8 ') return [b.readUInt16LE(26) & 0x3fff, b.readUInt16LE(28) & 0x3fff]; if (t === 'VP8L') { const n = b.readUInt32LE(21); return [1 + (n & 0x3fff), 1 + ((n >> 14) & 0x3fff)]; } }
  let i = 2; while (i < b.length) { if (b[i] !== 0xff) return null; const m = b[i + 1], l = b.readUInt16BE(i + 2); if (m >= 0xc0 && m <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(m)) return [b.readUInt16BE(i + 7), b.readUInt16BE(i + 5)]; i += 2 + l; } return null; };
const conDim = async html => {
  const m = html.match(/(<div id="intro-pagina-img">[\s\S]{0,800}?<img src=")([^"]+)(" alt="[^"]*")/);
  if (!m || /width=/.test(m[0])) return html;
  try { const r = await fetch(new URL(m[2], 'https://kosgroup.com/')); const d = dim(Buffer.from(await r.arrayBuffer())); if (d) return html.replace(m[0], `${m[1]}${m[2]}${m[3]} width="${d[0]}" height="${d[1]}"`); } catch (e) {}
  return html;
};
const url = process.argv[2], nuovo = process.argv[3] === 'b';
const b = await chromium.launch(process.env.CH ? { executablePath: process.env.CH } : {});
const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
const p = await ctx.newPage();
await p.addInitScript(() => { window.__e = []; new PerformanceObserver(l => { for (const e of l.getEntries()) window.__e.push([Math.round(e.startTime), +e.value.toFixed(3), (e.sources || []).map(s => (s.node && (s.node.className || s.node.nodeName)) + ' ' + JSON.stringify([Math.round(s.previousRect.y), Math.round(s.currentRect.y), Math.round(s.previousRect.height), Math.round(s.currentRect.height)])).join(' | ')]); }).observe({ type: 'layout-shift', buffered: true }); });
await p.route('**/*', r => /googletagmanager|cookiebot|acsbapp|hubspot|sitescdn|google-analytics/.test(r.request().url()) ? r.abort() : r.continue());
await p.route(url, async r => { const resp = await r.fetch(); let h = await resp.text(); if (nuovo) { const m = h.match(/<link href="(\/assets\/css\/kos\.min\.css[^"]*)" rel="stylesheet" \/>/); h = h.replace(m[0], ['regular','medium','bold'].map(f=>`<link rel="preload" href="/assets/fonts/satoshi-${f}.woff2" as="font" type="font/woff2" crossorigin>`).join('') + `<style>${critico}</style><link rel="preload" href="${m[1]}" as="style">`).replace(/(<script src="\/assets\/js\/jquery\.min\.js)/, `<link rel="stylesheet" href="${m[1]}" onload="performance.mark('css')">\n$1`); } h = await conDim(h); await r.fulfill({ response: resp, body: h, headers: { ...resp.headers(), 'content-type': 'text/html; charset=utf-8' } }); });
const t = []; p.on('requestfinished', rq => { if (/woff2|kos\.min|jquery|main\.js/.test(rq.url())) t.push([rq.url().split('/').pop().split('?')[0], Math.round(rq.timing().responseEnd)]); });
await p.goto(url, { waitUntil: 'load' }); await p.waitForTimeout(3000);
console.log(JSON.stringify(await p.evaluate(() => ({ fcp: Math.round(performance.getEntriesByName('first-contentful-paint')[0].startTime), css: (performance.getEntriesByName('css')[0] || {}).startTime, e: window.__e })), null, 0));
await b.close();
