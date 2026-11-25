#!/usr/bin/env node
/**
 * KOS 15 — costruisce il chunk del CSS critico di kosgroup.com (kosgroup/elements/chunks/kos-css-critico.html) dalle
 * regole tenute da estrai.mjs (tenuti.json): regole di /assets/css/kos.min.css nell'ordine del foglio, solo i selettori
 * usati, con @media/@supports che le contengono, tutte le @font-face, le @keyframes usate; url resi assoluti dal dominio
 * (il CSS sta nell'HTML, e con <base href> i percorsi relativi del foglio non varrebbero più); minificato con lightningcss.
 *
 * Uso (dalla cartella scripts/css/critico, dopo npm install):
 *   node estrai.mjs pagine.txt          # tenuti.json (sola lettura del sito: GET)
 *   node costruisci.mjs                 # scrive il chunk; --base=https://kosgroup.com (pagina da cui leggere il foglio)
 *   node valida.mjs validazione.txt     # prima visualizzazione senza JS: foglio intero contro solo critico
 *   node cls.mjs campione.txt [--lento] # con JS: CLS, FCP, LCP prima e dopo (HTML trasformato come nel pacchetto)
 * Da rifare quando cambia kos.min.css (scripts/css/costruisci-css.mjs) o un template cambia la parte iniziale delle pagine.
 */
import { chromium } from 'playwright';
import { transform } from 'lightningcss';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const qui = path.dirname(fileURLToPath(import.meta.url));
const opz = Object.fromEntries(process.argv.slice(2).map(a => { const m = a.match(/^--([\w-]+)(?:=(.*))?$/); return m ? [m[1], m[2] ?? true] : [a, true]; }));
const base = String(opz.base || 'https://kosgroup.com').replace(/\/$/, '');
const tenuti = JSON.parse(fs.readFileSync(path.join(qui, 'tenuti.json'), 'utf8'));
const b = await chromium.launch(process.env.CH ? { executablePath: process.env.CH } : {});
const p = await b.newPage();
await p.goto(base + '/it/privacy', { waitUntil: 'load' });
const css = await p.evaluate((tenuti) => {
  const sheet = [...document.styleSheets].find(s => (s.href || '').includes('kos.min.css'));
  const baseHref = sheet.href;
  const fixUrl = t => t.replace(/url\((['"]?)([^'")]+)\1\)/g, (m, q, u) => {
    if (/^(data:|https?:|\/)/.test(u)) return m;
    const a = new URL(u, baseHref); return `url("${a.pathname}${a.search}${a.hash}")`;
  });
  const animUsate = new Set();
  const walk = (rules, pth) => {
    const parts = [];
    [...rules].forEach((r, i) => {
      const k = pth + '/' + i;
      if (r.type === 1) {
        if (tenuti[k]) {
          (r.style.animationName || '').split(',').forEach(x => x.trim() && animUsate.add(x.trim()));
          parts.push(`${tenuti[k].join(',')}{${r.style.cssText}}`);
        }
      } else if (r.type === 5) parts.push(r.cssText);
      else if (r.type === 4) { const inner = walk(r.cssRules, k); if (inner) parts.push(`@media ${r.media.mediaText}{${inner}}`); }
      else if (r.type === 12) { const inner = walk(r.cssRules, k); if (inner) parts.push(`@supports ${r.conditionText}{${inner}}`); }
      else if (r.type === 7) { kf.push(r.cssText); parts.push(`\u0000KF${kf.length - 1}\u0000`); }
    });
    return parts.join('');
  };
  const kf = [];
  const txt = walk(sheet.cssRules, '').replace(/\u0000KF(\d+)\u0000/g, (m, i) => { const t = kf[+i]; const n = (t.match(/@(?:-webkit-)?keyframes\s+([\w-]+)/) || [])[1]; return animUsate.has(n) ? t : ''; });
  return fixUrl(txt);
}, tenuti);
const href = await p.evaluate(() => [...document.styleSheets].find(s => (s.href || '').includes('kos.min.css')).href);
await b.close();
let min = transform({ filename: 'critico.css', code: Buffer.from(css), minify: true }).code.toString();
if (min.includes('[[') || min.includes(']]')) throw new Error('il CSS contiene [[ o ]]: MODX lo leggerebbe come tag');
// Il plugin ObfuscateEmail cambia in entità HTML tutto ciò che somiglia a un indirizzo email (caratteri «atom» RFC 2822,
// tra cui { } | ~, attorno a una @): nel CSS in linea «border-box}@media» diventava entità e la regola si rompeva (25/11,
// CLS 1 in produzione). Con un a capo prima di ogni @ non c'è più nulla di attaccato a sinistra della @.
min = min.replace(/([^\n])@/g, '$1\n@');
const atom = "[-!#$%&'*+/=?^_`{|}~0-9A-Za-z]+", meta = atom + '(?:\\.' + atom + ')*';
if (new RegExp(meta + '@' + meta).test(min)) throw new Error('il CSS contiene ancora testo che ObfuscateEmail scambierebbe per un indirizzo email');
const chunk = `<style>${min}</style>\n`;
const dest = path.join(qui, '../../../kosgroup/elements/chunks/kos-css-critico.html');
fs.writeFileSync(dest, chunk);
fs.writeFileSync(path.join(qui, 'critico.css'), min);
console.log(`foglio ${href}\nregole ${Object.keys(tenuti).length}, ${Buffer.byteLength(min)} byte → ${path.relative(process.cwd(), dest)}`);
