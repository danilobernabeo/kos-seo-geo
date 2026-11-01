#!/usr/bin/env node
/**
 * KOS-2 B — collaudo del caricamento differito delle immagini. SOLA LETTURA (solo richieste GET).
 *
 * Uso (PowerShell, dalla cartella scripts/collaudo, dipendenze già installate per collaudo-visivo.mjs):
 *   $env:AUTH = "kos:<password dello stage>"
 *   node conta-lazy.mjs --base=https://stage.kosgroup.com --pagine=pagine-kos-2b.txt
 *   node conta-lazy.mjs --base=https://kosgroup.com --pagine=pagine-kos-2b.txt          (produzione, per il «prima»)
 * Opzioni:
 *   --profilo=entrambi  mobile (412×823, DPR 1,75) | desktop (1350×940) | entrambi (default)
 *   --uscita=lazy-<data> cartella del rapporto (default con data e ora)
 * Nessun rallentamento di rete o CPU (serve a contare, non a misurare l'LCP: per quello misura-lcp.mjs).
 *
 * Per ogni pagina e profilo, prima di scorrere:
 *   - immagini fuori schermo (sotto 1,5 altezze di finestra, stessa regola di misura-lcp.mjs) scaricate senza
 *     loading="lazy": numero e KB  → l'obiettivo di KOS-2 B (27/10: 595 caricamenti, circa 13,5 MB sulle 65 pagine);
 *   - KO   elemento LCP con loading="lazy";
 *   - AVV  immagini visibili nella prima schermata con loading="lazy" (caricate in ritardo);
 *   - sfondi precaricati (<link rel="preload" as="image">): KO se il file si scarica due volte (URL diverso da quello
 *     del CSS), AVV se precaricato ma non usato dalla pagina o se Chrome segnala «preloaded but not used»;
 *     INFO se bg-pattern.jpg è usato come sfondo senza precaricamento.
 * Poi scorre la pagina fino in fondo e aspetta: KO per ogni immagine visibile con loading="lazy" che non si è
 * caricata («immagine che non compare»). Le immagini nascoste (display:none, slide fuori vista) non contano.
 *
 * Uscita: <uscita>/risultati.json, <uscita>/immagini.csv (una riga per immagine segnalata), riepilogo a video.
 * Codice di uscita 1 se c'è almeno un KO.
 */
import { chromium, devices } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const opz = Object.fromEntries(process.argv.slice(2).map(a => {
  const m = a.match(/^--([\w-]+)(?:=(.*))?$/);
  return m ? [m[1], m[2] ?? true] : [a, true];
}));
const BASE = String(opz.base || '').replace(/\/$/, '');
if (!BASE || !opz.pagine) {
  console.error('Uso: node conta-lazy.mjs --base=https://stage.kosgroup.com --pagine=pagine-kos-2b.txt [--profilo=mobile|desktop|entrambi]');
  process.exit(1);
}
const PROFILI = {
  mobile: { viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true, userAgent: devices['Pixel 5'].userAgent },
  desktop: { viewport: { width: 1350, height: 940 }, deviceScaleFactor: 1, isMobile: false, hasTouch: false, userAgent: undefined },
};
const scelta = String(opz.profilo || 'entrambi');
const profili = scelta === 'entrambi' ? ['mobile', 'desktop'] : [scelta];
if (profili.some(p => !PROFILI[p])) { console.error('Profilo non valido: ' + scelta); process.exit(1); }

const stamp = new Date().toISOString().slice(0, 16).replace(/[-:T]/g, '').replace(/^(\d{8})/, '$1-');
const OUT = path.resolve(String(opz.uscita || `lazy-${stamp}`));
fs.mkdirSync(OUT, { recursive: true });

const grezzo = fs.readFileSync(String(opz.pagine));
const testo = grezzo[0] === 0xff && grezzo[1] === 0xfe ? grezzo.toString('utf16le') : grezzo.toString('utf8');
const pagine = testo.replace(/^﻿/, '').split(/\r?\n/).map(r => r.replace(/#.*$/, '').trim()).filter(r => r.startsWith('/'));
if (!pagine.length) { console.error('Nessuna pagina nel file (righe che iniziano con «/»).'); process.exit(1); }
// $env:AUTH = password dello stage (utente «kos», come nei comandi curl) oppure «utente:password»
const httpCredentials = process.env.AUTH
  ? (process.env.AUTH.includes(':')
      ? (([u, ...p]) => ({ username: u, password: p.join(':') }))(process.env.AUTH.split(':'))
      : { username: 'kos', password: process.env.AUTH })
  : undefined;

const INIT = () => {
  window.__lcp = null;
  try { new PerformanceObserver(l => { for (const e of l.getEntries()) window.__lcp = e; }).observe({ type: 'largest-contentful-paint', buffered: true }); } catch (e) {}
};
// Fotografia delle immagini nel browser (fase: 'carico' prima di scorrere, 'scorso' dopo).
const FOTO = () => {
  const vh = window.innerHeight, vw = window.innerWidth;
  const contenitore = el => {
    const c = el.closest('.gallery-struttura, footer, header, [class*="swiper"], [class*="splide"], [class*="carousel"], [class*="slick"], section[id], div[id]');
    if (!c) return '';
    const cls = (c.getAttribute('class') || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).join('.');
    return c.tagName.toLowerCase() + (c.id ? '#' + c.id : '') + (cls ? '.' + cls : '');
  };
  const visibile = el => {
    const r = el.getBoundingClientRect();
    if (r.width < 2 || r.height < 2) return false;
    const s = getComputedStyle(el);
    if (s.visibility === 'hidden' || s.display === 'none') return false;
    // fuori da un contenitore che taglia (slide di un carosello): il centro deve stare nel contenitore
    for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
      const ps = getComputedStyle(p);
      if (/(hidden|clip|auto|scroll)/.test(ps.overflowX + ps.overflowY)) {
        const q = p.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        if (cx < q.left || cx > q.right || cy < q.top || cy > q.bottom) return false;
      }
    }
    return true;
  };
  const sy = window.scrollY;
  const lcpEl = window.__lcp ? window.__lcp.element : null;
  const immagini = [...document.images].map((i, n) => {
    const r = i.getBoundingClientRect();
    const top = r.top + sy;
    return {
      n, src: i.currentSrc || i.getAttribute('src') || '', loading: i.getAttribute('loading') || '',
      fetchpriority: i.getAttribute('fetchpriority') || '', top: Math.round(top), larghezza: Math.round(r.width),
      visibile: visibile(i), sopraPiega: top < vh && r.bottom > -sy && r.left < vw && r.right > 0,
      fuoriSchermo: top > vh * 1.5, caricata: i.complete && i.naturalWidth > 0, lcp: i === lcpEl,
      contenitore: contenitore(i),
    };
  });
  const lcp = window.__lcp ? {
    tag: lcpEl ? lcpEl.tagName.toLowerCase() : '', url: window.__lcp.url || '',
    loading: lcpEl && lcpEl.tagName === 'IMG' ? (lcpEl.getAttribute('loading') || '') : '',
    ms: Math.round(window.__lcp.renderTime || window.__lcp.loadTime || window.__lcp.startTime),
  } : null;
  const preload = [...document.querySelectorAll('link[rel="preload"][as="image"]')].map(l => l.href);
  const sfondi = new Set();
  for (const el of document.querySelectorAll('body *')) {
    const b = getComputedStyle(el).backgroundImage;
    if (b && b !== 'none') for (const m of b.matchAll(/url\(["']?([^"')]+)["']?\)/g)) sfondi.add(new URL(m[1], location.href).href);
  }
  return { immagini, lcp, preload, sfondi: [...sfondi] };
};
const SCORRI = async () => {
  const passo = Math.round(window.innerHeight * 0.8);
  for (let k = 0; k < 80; k++) {
    const prima = window.scrollY;
    window.scrollBy(0, passo);
    await new Promise(r => setTimeout(r, 250));
    if (window.scrollY === prima) break;
  }
};

const kb = b => (b / 1024).toFixed(0);
const browser = await chromium.launch();
const risultati = [];
const righe = [['profilo', 'pagina', 'esito', 'motivo', 'immagine', 'kb', 'loading', 'contenitore', 'top_px'].join(';')];
let ko = 0;

for (const nomeProf of profili) {
  const P = PROFILI[nomeProf];
  for (const [n, pag] of pagine.entries()) {
    const ctx = await browser.newContext({ viewport: P.viewport, deviceScaleFactor: P.deviceScaleFactor, isMobile: P.isMobile,
      hasTouch: P.hasTouch, userAgent: P.userAgent, httpCredentials, locale: 'it-IT' });
    await ctx.addInitScript(INIT);
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page);
    await cdp.send('Network.enable');
    await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
    const tipo = new Map(), byte = new Map(), richieste = new Map();
    cdp.on('Network.requestWillBeSent', ev => { const u = ev.request.url.split('#')[0]; richieste.set(u, (richieste.get(u) || 0) + 1); });
    cdp.on('Network.responseReceived', ev => { if (ev.type === 'Image') tipo.set(ev.requestId, ev.response.url); });
    cdp.on('Network.loadingFinished', ev => { const u = tipo.get(ev.requestId); if (u) byte.set(u, (byte.get(u) || 0) + ev.encodedDataLength); });
    const avvisiConsole = [];
    page.on('console', m => { if (/preload/i.test(m.text())) avvisiConsole.push(m.text().slice(0, 200)); });

    const r = { profilo: nomeProf, pagina: pag, stato: null, errore: '', segnalazioni: [] };
    const segnala = (esito, motivo, i = {}) => {
      r.segnalazioni.push({ esito, motivo, src: i.src || '', loading: i.loading || '', contenitore: i.contenitore || '' });
      righe.push([nomeProf, pag, esito, motivo, i.src || '', i.src && byte.has(i.src) ? kb(byte.get(i.src)) : '', i.loading || '', i.contenitore || '', i.top ?? ''].map(x => String(x).replace(/;/g, ',')).join(';'));
      if (esito === 'KO') ko++;
    };
    try {
      const resp = await page.goto(BASE + pag, { waitUntil: 'load', timeout: 90000 });
      r.stato = resp ? resp.status() : null;
      if (r.stato !== 200) segnala('KO', `pagina non servita (HTTP ${r.stato}): password dello stage in $env:AUTH?`, {});
      await page.waitForTimeout(2000);
      const a = await page.evaluate(FOTO);
      const scaricate = new Set(byte.keys());

      // fuori schermo senza lazy (scaricate al caricamento)
      const fuori = a.immagini.filter(i => i.fuoriSchermo && i.loading !== 'lazy' && scaricate.has(i.src));
      r.fuoriSchermoSenzaLazy = fuori.length;
      r.fuoriSchermoSenzaLazyKB = Math.round(fuori.reduce((s, i) => s + (byte.get(i.src) || 0), 0) / 1024);
      for (const i of fuori) segnala('INFO', 'fuori schermo senza lazy', i);
      r.immaginiScaricate = byte.size;
      r.immaginiKB = Math.round([...byte.values()].reduce((s, x) => s + x, 0) / 1024);
      r.lcp = a.lcp;
      const lcpImg = a.immagini.find(i => i.lcp);
      if (lcpImg && lcpImg.loading === 'lazy') segnala('KO', 'elemento LCP con loading=lazy', lcpImg);
      for (const i of a.immagini.filter(i => i.sopraPiega && i.visibile && i.loading === 'lazy' && !i.lcp)) segnala('AVV', 'lazy nella prima schermata', i);

      // precaricamenti
      r.preload = a.preload;
      for (const u of a.preload) {
        const volte = richieste.get(u) || 0;
        if (!a.sfondi.includes(u) && !a.immagini.some(i => i.src === u)) segnala('AVV', 'preload non usato dalla pagina (CSS diverso o elemento assente)', { src: u });
        if (volte > 1) segnala('KO', `preload scaricato ${volte} volte`, { src: u });
      }
      for (const u of a.sfondi.filter(u => /bg-pattern/.test(u) && !a.preload.includes(u))) segnala('INFO', 'sfondo bg-pattern senza preload', { src: u });
      for (const t of avvisiConsole) segnala('AVV', 'console: ' + t);

      // scorrimento fino in fondo: le immagini lazy devono comparire
      await page.evaluate(SCORRI);
      await page.waitForTimeout(1500);
      const b = await page.evaluate(FOTO);
      const mancanti = b.immagini.filter(i => i.visibile && !i.caricata && i.src);
      for (const i of mancanti) segnala(i.loading === 'lazy' ? 'KO' : 'INFO', i.loading === 'lazy' ? 'lazy non caricata dopo lo scorrimento' : 'immagine non caricata (anche senza lazy)', i);
      r.lazy = b.immagini.filter(i => i.loading === 'lazy').length;
      r.immaginiDopoScorrimento = byte.size;
    } catch (e) { r.errore = String(e.message || e).split('\n')[0]; }
    await ctx.close();
    risultati.push(r);
    const conta = es => r.segnalazioni.filter(s => s.esito === es).length;
    console.log(`[${nomeProf} ${n + 1}/${pagine.length}] ${pag}  ${r.stato ?? '-'}  fuori schermo senza lazy ${r.fuoriSchermoSenzaLazy ?? '-'} (${r.fuoriSchermoSenzaLazyKB ?? '-'} KB)` +
      `  lazy ${r.lazy ?? '-'}  LCP ${r.lcp ? r.lcp.tag + (r.lcp.loading ? ' loading=' + r.lcp.loading : '') : '-'}` +
      `  preload ${(r.preload || []).length}  KO ${conta('KO')}  AVV ${conta('AVV')}${r.errore ? '  ! ' + r.errore : ''}`);
    for (const s of r.segnalazioni.filter(s => s.esito !== 'INFO')) console.log(`    ${s.esito}  ${s.motivo}  ${s.src.replace(BASE, '')}  ${s.contenitore}`);
  }
}
await browser.close();

fs.writeFileSync(path.join(OUT, 'risultati.json'), JSON.stringify({ base: BASE, data: new Date().toISOString(), risultati }, null, 1));
fs.writeFileSync(path.join(OUT, 'immagini.csv'), '﻿' + righe.join('\r\n'));
for (const p of profili) {
  const rr = risultati.filter(r => r.profilo === p && !r.errore);
  const tot = rr.reduce((s, r) => s + (r.fuoriSchermoSenzaLazy || 0), 0);
  const totKB = rr.reduce((s, r) => s + (r.fuoriSchermoSenzaLazyKB || 0), 0);
  const img = rr.reduce((s, r) => s + (r.immaginiKB || 0), 0);
  console.log(`\n${p}: ${rr.length} pagine; fuori schermo senza lazy ${tot} caricamenti, ${(totKB / 1024).toFixed(1)} MB su ${(img / 1024).toFixed(1)} MB di immagini al caricamento;` +
    ` KO ${rr.reduce((s, r) => s + r.segnalazioni.filter(x => x.esito === 'KO').length, 0)}, AVV ${rr.reduce((s, r) => s + r.segnalazioni.filter(x => x.esito === 'AVV').length, 0)}`);
}
console.log(`\nRisultati: ${OUT}`);
process.exit(ko ? 1 : 0);
