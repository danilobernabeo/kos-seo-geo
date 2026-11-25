#!/usr/bin/env node
/**
 * KOS 15 — estrazione delle regole di /assets/css/kos.min.css che servono alla prima visualizzazione (sola lettura, GET).
 * Pagine caricate SENZA JavaScript (com'è la pagina al primo disegno, prima degli script) a 390×844, 1366×768 e 1920×1080:
 * elementi in ordine di documento fino al primo elemento visibile oltre 1,3 altezze di finestra, più quelli fissi o sticky.
 * Una regola si tiene se uno dei suoi selettori, senza pseudo-elementi e stati (:hover, ::before…), trova uno di quegli elementi.
 * Uso: node estrai.mjs pagine.txt  → tenuti.json (unione su tutte le pagine). Vedi costruisci.mjs.
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
const urls = fs.readFileSync(process.argv[2], 'utf8').split('\n').filter(Boolean);
const VIEW = [{ w: 390, h: 844, m: true }, { w: 1366, h: 768 }, { w: 1920, h: 1080 }];
const b = await chromium.launch(process.env.CH ? { executablePath: process.env.CH } : {});
const tenuti = {}; // chiave percorso regola -> Set selettori
let fatte = 0;
async function una(url, v) {
  const ctx = await b.newContext({ viewport: { width: v.w, height: v.h }, isMobile: !!v.m, hasTouch: !!v.m, deviceScaleFactor: 1, javaScriptEnabled: false });
  const p = await ctx.newPage();
  try {
    await p.goto(url, { waitUntil: 'load', timeout: 60000 });
    await p.waitForTimeout(1200);
    const r = await p.evaluate(({ h }) => {
      const sheet = [...document.styleSheets].find(s => (s.href || '').includes('kos.min.css'));
      if (!sheet) return { err: 'foglio non trovato' };
      // Elementi della parte iniziale, indipendentemente dall'ordine nel documento (25/11: con il taglio «in ordine di
      // documento» un elemento posizionato in basso ma presente presto nel codice fermava la raccolta, e la fascia in
      // evidenza di /it/news-e-media restava senza regole): visibili con il bordo alto entro 1,3 finestre, fissi o sticky,
      // e quelli nascosti (menu, pannelli) il cui primo antenato visibile sta nella parte iniziale.
      const all = [...document.querySelectorAll('body *')];
      const lim = h * 1.3;
      const vis = new Map();
      const visibile = el => { if (vis.has(el)) return vis.get(el); const rc = el.getBoundingClientRect(); const v = rc.width > 0 && rc.height > 0 ? rc.top + scrollY : null; vis.set(el, v); return v; };
      const S = [document.documentElement, document.body];
      for (const el of all) {
        const cs = getComputedStyle(el);
        if (cs.position === 'fixed' || cs.position === 'sticky') { S.push(el); continue; }
        let top = visibile(el), a = el;
        while (top === null && a.parentElement && a.parentElement !== document.body) { a = a.parentElement; top = visibile(a); }
        if (top !== null && top < lim) S.push(el);
      }
      const split = s => { const out = []; let d = 0, cur = ''; for (const c of s) { if (c === '(') d++; if (c === ')') d--; if (c === ',' && d === 0) { out.push(cur.trim()); cur = ''; } else cur += c; } if (cur.trim()) out.push(cur.trim()); return out; };
      const dyn = /::?(before|after|first-letter|first-line|placeholder|selection|marker|backdrop|-webkit-[\w-]+|-moz-[\w-]+|-ms-[\w-]+)(\([^)]*\))?|:(hover|focus|focus-visible|focus-within|active|visited|link|target|checked|disabled|enabled|invalid|valid|required|optional|placeholder-shown|indeterminate|default|empty)\b/gi;
      const keep = {};
      const walk = (rules, path) => {
        [...rules].forEach((r, i) => {
          const k = path + '/' + i;
          if (r.type === 1) {
            for (const sel of split(r.selectorText)) {
              let base = sel.replace(dyn, '').trim();
              if (base === '' || /[>+~]$/.test(base)) base = base + '*';
              let ok = false; try { ok = S.some(el => el.matches(base)); } catch (e) { ok = false; }
              if (ok) (keep[k] ||= []).push(sel);
            }
          } else if (r.cssRules) walk(r.cssRules, k);
        });
      };
      walk(sheet.cssRules, '');
      return { keep, n: S.length };
    }, { h: v.h });
    if (r.err) { console.error(url, r.err); return; }
    for (const [k, sels] of Object.entries(r.keep)) { (tenuti[k] ||= new Set()); sels.forEach(s => tenuti[k].add(s)); }
  } catch (e) { console.error('ERRORE', url, v.w, e.message.split('\n')[0]); }
  finally { await ctx.close(); fatte++; }
}
const lavori = []; for (const u of urls) for (const v of VIEW) lavori.push([u, v]);
const conc = +(process.env.CONC || 6); let idx = 0;
await Promise.all(Array.from({ length: conc }, async () => { while (idx < lavori.length) { const [u, v] = lavori[idx++]; await una(u, v); } }));
await b.close();
fs.writeFileSync('tenuti.json', JSON.stringify(Object.fromEntries(Object.entries(tenuti).map(([k, s]) => [k, [...s]]))));
console.log('esecuzioni', fatte, 'regole tenute', Object.keys(tenuti).length);
