#!/usr/bin/env node
/**
 * KOS 15 — verifica del CSS critico: prima visualizzazione SENZA JavaScript con il foglio intero (com'è oggi) contro la
 * pagina con il solo CSS critico (foglio bloccato). Differenza in pixel della finestra; sopra 0,5% salva le immagini in diff/.
 * Le differenze residue attese sono i titoli con l'animazione del gradiente (stessa animazione, istante diverso).
 * Uso: node valida.mjs pagine.txt
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';
const urls = fs.readFileSync(process.argv[2], 'utf8').split('\n').filter(Boolean);
const critico = fs.readFileSync(new URL('critico.css', import.meta.url), 'utf8');
const VIEW = [{ w: 390, h: 844, m: true }, { w: 1366, h: 768 }, { w: 1920, h: 1080 }];
fs.mkdirSync('diff', { recursive: true });
const b = await chromium.launch(process.env.CH ? { executablePath: process.env.CH } : {});
const shot = async (url, v, solo) => {
  const ctx = await b.newContext({ viewport: { width: v.w, height: v.h }, isMobile: !!v.m, hasTouch: !!v.m, javaScriptEnabled: false });
  const p = await ctx.newPage();
  if (solo) {
    await p.route('**/assets/css/kos.min.css*', r => r.abort());
    await p.route(url, async r => {
      const resp = await r.fetch(); let html = await resp.text();
      const n0 = html.length;
      html = html.replace(/<link href="\/assets\/css\/kos\.min\.css[^"]*" rel="stylesheet" \/>/, `<style>${critico}</style>`);
      if (html.length === n0) console.error('link non trovato', url);
      await r.fulfill({ response: resp, body: html, headers: { ...resp.headers(), 'content-type': 'text/html; charset=utf-8' } });
    });
  }
  await p.goto(url, { waitUntil: 'load', timeout: 60000 });
  await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(700);
  const buf = await p.screenshot(); await ctx.close(); return PNG.sync.read(buf);
};
const ris = [];
const lavori = []; for (const u of urls) for (const v of VIEW) lavori.push([u, v]);
let i = 0;
await Promise.all(Array.from({ length: 5 }, async () => { while (i < lavori.length) { const [u, v] = lavori[i++]; try {
  const a = await shot(u, v, false), c = await shot(u, v, true);
  const { width, height } = a; const d = new PNG({ width, height });
  const n = (a.height === c.height && a.width === c.width) ? pixelmatch(a.data, c.data, d.data, width, height, { threshold: 0.1 }) : -1;
  const pct = n < 0 ? 100 : +(100 * n / (width * height)).toFixed(2);
  ris.push({ u, w: v.w, pct });
  if (pct > 0.5) { const nome = u.replace(/https:\/\/kosgroup.com\//, '').replace(/\W+/g, '_') + '_' + v.w; fs.writeFileSync(`diff/${nome}_d.png`, PNG.sync.write(d)); fs.writeFileSync(`diff/${nome}_c.png`, PNG.sync.write(c)); fs.writeFileSync(`diff/${nome}_a.png`, PNG.sync.write(a)); }
} catch (e) { ris.push({ u, w: v.w, err: e.message.split('\n')[0] }); } } }));
await b.close();
ris.sort((x, y) => (y.pct ?? 999) - (x.pct ?? 999));
fs.writeFileSync('validazione.json', JSON.stringify(ris, null, 1));
console.log('pagine-finestre', ris.length, 'sopra 0,5%', ris.filter(r => r.pct > 0.5).length, 'errori', ris.filter(r => r.err).length);
console.log(ris.slice(0, 15).map(r => `${r.pct ?? r.err} ${r.w} ${r.u}`).join('\n'));
