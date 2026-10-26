#!/usr/bin/env node
/**
 * KOS-2 — misura dell'LCP da mobile e del peso delle immagini, pagina per pagina. SOLA LETTURA (solo richieste GET).
 * Serve a decidere se e dove convertire le immagini di kosgroup.com in <picture> WebP con srcset (snippet immagine).
 *
 * Uso (PowerShell, dalla cartella scripts/collaudo, dipendenze già installate per collaudo-visivo.mjs):
 *   node misura-lcp.mjs --base=https://kosgroup.com --pagine=..\..\pagine.txt
 * Opzioni:
 *   --ripetizioni=3     caricamenti per pagina (mediana di LCP, FCP, TTFB); default 3
 *   --profilo=mobile    mobile (default: 412×823, DPR 1,75, CPU 4×, rete 4G lenta come Lighthouse) | desktop | entrambi
 *   --uscita=lcp-<data> cartella del rapporto (default con data e ora)
 *   (stage: $env:AUTH = "kos:<password>" e --base=https://<stage>.modx.dev)
 * L'elenco delle pagine è lo stesso di collaudo-visivo.mjs (anche UTF-16, commenti con «#»).
 *
 * Per ogni pagina e profilo:
 *   - LCP, FCP, TTFB (mediana), elemento LCP (tag, classe, se immagine <img> o sfondo CSS, URL, formato, peso, larghezza
 *     del file e larghezza mostrata × DPR, loading, fetchpriority, srcset);
 *   - immagini scaricate: numero, peso totale, quante non WebP/AVIF, quante sovradimensionate (file più largo di 1,5 volte
 *     la larghezza mostrata × DPR), immagini fuori schermo senza caricamento differito.
 * Rapporto: <uscita>/rapporto.html (pagine ordinate per LCP), <uscita>/risultati.json, <uscita>/immagini.csv.
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
  console.error('Uso: node misura-lcp.mjs --base=https://kosgroup.com --pagine=pagine.txt [--ripetizioni=3] [--profilo=mobile|desktop|entrambi]');
  process.exit(1);
}
const RIP = Math.max(1, Number(opz.ripetizioni ?? 3));
const PROFILI = {
  mobile: { viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true,
            userAgent: devices['Pixel 5'].userAgent, cpu: 4,
            rete: { offline: false, latency: 150, downloadThroughput: 1.6 * 1024 * 1024 / 8, uploadThroughput: 750 * 1024 / 8 } },
  desktop: { viewport: { width: 1350, height: 940 }, deviceScaleFactor: 1, isMobile: false, hasTouch: false,
             userAgent: undefined, cpu: 1,
             rete: { offline: false, latency: 40, downloadThroughput: 10 * 1024 * 1024 / 8, uploadThroughput: 10 * 1024 * 1024 / 8 } },
};
const scelta = String(opz.profilo || 'mobile');
const profili = scelta === 'entrambi' ? ['mobile', 'desktop'] : [scelta];
if (profili.some(p => !PROFILI[p])) { console.error('Profilo non valido: ' + scelta); process.exit(1); }

const stamp = new Date().toISOString().slice(0, 16).replace(/[-:T]/g, '').replace(/^(\d{8})/, '$1-');
const OUT = path.resolve(String(opz.uscita || `lcp-${stamp}`));
fs.mkdirSync(OUT, { recursive: true });

const grezzo = fs.readFileSync(String(opz.pagine));
const testo = grezzo[0] === 0xff && grezzo[1] === 0xfe ? grezzo.toString('utf16le') : grezzo.toString('utf8');
const pagine = testo.replace(/^﻿/, '').split(/\r?\n/).map(r => r.replace(/#.*$/, '').trim()).filter(r => r.startsWith('/'));
if (!pagine.length) { console.error('Nessuna pagina nel file (righe che iniziano con «/»).'); process.exit(1); }

const httpCredentials = process.env.AUTH ? (([u, ...p]) => ({ username: u, password: p.join(':') }))(process.env.AUTH.split(':')) : undefined;

// Raccolta nel browser: LCP (ultimo candidato), FCP, TTFB, immagini visibili.
const INIT = () => {
  window.__lcp = null;
  try {
    new PerformanceObserver(l => { for (const e of l.getEntries()) window.__lcp = e; })
      .observe({ type: 'largest-contentful-paint', buffered: true });
  } catch (e) {}
};
const RACCOLTA = async () => {
  const dpr = window.devicePixelRatio || 1;
  const sel = el => {
    if (!el) return '';
    const cls = (el.getAttribute('class') || '').trim().split(/\s+/).filter(Boolean).slice(0, 3).join('.');
    return el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (cls ? '.' + cls : '');
  };
  const sfondo = el => {
    const m = (getComputedStyle(el).backgroundImage || '').match(/url\(["']?([^"')]+)["']?\)/);
    return m ? m[1] : '';
  };
  const e = window.__lcp;
  let lcp = null;
  if (e) {
    const el = e.element;
    const r = el ? el.getBoundingClientRect() : null;
    const img = el && el.tagName === 'IMG' ? el : null;
    lcp = {
      ms: Math.round(e.renderTime || e.loadTime || e.startTime),
      elemento: sel(el), genitore: el && el.parentElement ? sel(el.parentElement) : '',
      tipo: img ? 'img' : (e.url ? 'sfondo CSS' : 'testo'),
      url: e.url || (el ? sfondo(el) : ''),
      larghezzaFile: img ? img.naturalWidth : null,
      larghezzaMostrata: r ? Math.round(r.width) : null,
      larghezzaNecessaria: r ? Math.round(r.width * dpr) : null,
      loading: img ? (img.getAttribute('loading') || '') : '',
      fetchpriority: img ? (img.getAttribute('fetchpriority') || '') : '',
      srcset: img ? !!img.getAttribute('srcset') : false,
      inPicture: img ? !!img.closest('picture') : false,
    };
    if (!img && lcp.url) {   // sfondo CSS: larghezza del file letta caricando l'immagine (è già in memoria)
      lcp.larghezzaFile = await new Promise(ok => { const i = new Image(); i.onload = () => ok(i.naturalWidth); i.onerror = () => ok(null); i.src = lcp.url; });
    }
  }
  const nav = performance.getEntriesByType('navigation')[0];
  const fcp = performance.getEntriesByName('first-contentful-paint')[0];
  const vh = window.innerHeight;
  const immagini = [...document.images].filter(i => i.currentSrc).map(i => {
    const r = i.getBoundingClientRect();
    return { url: i.currentSrc, larghezzaFile: i.naturalWidth, larghezzaMostrata: Math.round(r.width),
             necessaria: Math.round(r.width * dpr), fuoriSchermo: r.top > vh * 1.5,
             loading: i.getAttribute('loading') || '', alt: i.hasAttribute('alt') };
  });
  return { lcp, ttfb: nav ? Math.round(nav.responseStart) : null, fcp: fcp ? Math.round(fcp.startTime) : null, immagini };
};

const mediana = a => { const v = a.filter(x => x != null).sort((x, y) => x - y); return v.length ? v[Math.floor((v.length - 1) / 2)] : null; };
const formato = (url, ct) => {
  const t = (ct || '').toLowerCase();
  if (t.includes('webp')) return 'webp'; if (t.includes('avif')) return 'avif';
  if (t.includes('png')) return 'png'; if (t.includes('jpeg') || t.includes('jpg')) return 'jpeg';
  if (t.includes('svg')) return 'svg'; if (t.includes('gif')) return 'gif';
  const m = (url.split('?')[0].match(/\.(\w+)$/) || [])[1];
  return (m || '?').toLowerCase().replace('jpg', 'jpeg');
};
const kb = b => b == null ? '' : (b / 1024).toFixed(0);

const browser = await chromium.launch();
const risultati = [];
const righeCsv = [['profilo', 'pagina', 'url', 'formato', 'kb', 'larghezza_file', 'larghezza_necessaria', 'sovradimensionata', 'fuori_schermo_senza_lazy', 'lcp'].join(';')];

for (const nomeProf of profili) {
  const P = PROFILI[nomeProf];
  for (const [n, pag] of pagine.entries()) {
    const misure = [];
    let dettagli = null;
    for (let k = 0; k < RIP; k++) {
      const ctx = await browser.newContext({ viewport: P.viewport, deviceScaleFactor: P.deviceScaleFactor, isMobile: P.isMobile,
        hasTouch: P.hasTouch, userAgent: P.userAgent, httpCredentials, locale: 'it-IT' });
      await ctx.addInitScript(INIT);
      const page = await ctx.newPage();
      const cdp = await ctx.newCDPSession(page);
      await cdp.send('Network.enable');
      await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
      await cdp.send('Network.emulateNetworkConditions', P.rete);
      await cdp.send('Emulation.setCPUThrottlingRate', { rate: P.cpu });
      const pesi = new Map();       // requestId → { url, ct }
      const scaricate = [];
      cdp.on('Network.responseReceived', ev => {
        if (ev.type === 'Image') pesi.set(ev.requestId, { url: ev.response.url, ct: ev.response.mimeType });
      });
      cdp.on('Network.loadingFinished', ev => {
        const r = pesi.get(ev.requestId);
        if (r) scaricate.push({ url: r.url, formato: formato(r.url, r.ct), byte: ev.encodedDataLength });
      });
      let stato = null, errore = '';
      try {
        const resp = await page.goto(BASE + pag, { waitUntil: 'load', timeout: 90000 });
        stato = resp ? resp.status() : null;
        await page.waitForTimeout(3000);
        // l'LCP si chiude alla prima interazione: lo leggiamo prima di scorrere
        const dati = await page.evaluate(RACCOLTA);
        misure.push({ lcp: dati.lcp ? dati.lcp.ms : null, fcp: dati.fcp, ttfb: dati.ttfb });
        if (k === RIP - 1) dettagli = { stato, ...dati, scaricate: [...scaricate] };
      } catch (e) { errore = String(e.message || e).split('\n')[0]; misure.push({}); }
      await ctx.close();
      if (errore && k === RIP - 1) dettagli = { stato, errore, immagini: [], scaricate: [] };
    }
    const d = dettagli || { immagini: [], scaricate: [] };
    const perUrl = new Map(d.scaricate.map(s => [s.url, s]));
    const lcpUrl = d.lcp && d.lcp.url ? new URL(d.lcp.url, BASE).href : '';
    const lcpFile = lcpUrl ? perUrl.get(lcpUrl) : null;
    const img = d.immagini.map(i => ({ ...i, ...(perUrl.get(i.url) || {}),
      sovradimensionata: i.larghezzaFile && i.necessaria ? i.larghezzaFile > 1.5 * i.necessaria : false }));
    const tot = d.scaricate.reduce((s, x) => s + (x.byte || 0), 0);
    const r = {
      profilo: nomeProf, pagina: pag, stato: d.stato, errore: d.errore || '',
      lcp: mediana(misure.map(m => m.lcp)), fcp: mediana(misure.map(m => m.fcp)), ttfb: mediana(misure.map(m => m.ttfb)),
      lcpElemento: d.lcp ? { ...d.lcp, formato: lcpFile ? lcpFile.formato : (lcpUrl ? formato(lcpUrl, '') : ''), byte: lcpFile ? lcpFile.byte : null } : null,
      immaginiNumero: d.scaricate.length, immaginiByte: tot,
      nonWebp: d.scaricate.filter(s => !['webp', 'avif', 'svg'].includes(s.formato)).length,
      nonWebpByte: d.scaricate.filter(s => !['webp', 'avif', 'svg'].includes(s.formato)).reduce((s, x) => s + (x.byte || 0), 0),
      sovradimensionate: img.filter(i => i.sovradimensionata).length,
      fuoriSchermoSenzaLazy: img.filter(i => i.fuoriSchermo && i.loading !== 'lazy').length,
    };
    risultati.push(r);
    for (const s of d.scaricate) {
      const i = img.find(x => x.url === s.url) || {};
      righeCsv.push([nomeProf, pag, s.url, s.formato, kb(s.byte), i.larghezzaFile ?? '', i.necessaria ?? '',
        i.sovradimensionata ? 'sì' : '', i.fuoriSchermo && i.loading !== 'lazy' ? 'sì' : '', s.url === lcpUrl ? 'LCP' : ''].join(';'));
    }
    console.log(`[${nomeProf} ${n + 1}/${pagine.length}] ${pag}  LCP ${r.lcp ?? '-'} ms  ${r.lcpElemento ? r.lcpElemento.tipo + ' ' + (r.lcpElemento.formato || '') : ''}  immagini ${kb(r.immaginiByte)} KB${r.errore ? '  ! ' + r.errore : ''}`);
  }
}
await browser.close();

fs.writeFileSync(path.join(OUT, 'risultati.json'), JSON.stringify({ base: BASE, ripetizioni: RIP, data: new Date().toISOString(), risultati }, null, 1));
fs.writeFileSync(path.join(OUT, 'immagini.csv'), '﻿' + righeCsv.join('\r\n'));

const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const colore = (ms, p) => ms == null ? '' : ms <= (p === 'mobile' ? 2500 : 2500) ? 'ok' : ms <= 4000 ? 'medio' : 'male';
const righe = [...risultati].sort((a, b) => (b.lcp ?? 0) - (a.lcp ?? 0)).map(r => {
  const e = r.lcpElemento || {};
  return `<tr><td>${esc(r.profilo)}</td><td><a href="${esc(BASE + r.pagina)}">${esc(r.pagina)}</a>${r.errore ? '<br><small>' + esc(r.errore) + '</small>' : ''}</td>
<td class="${colore(r.lcp, r.profilo)}">${r.lcp ?? ''}</td><td>${r.fcp ?? ''}</td><td>${r.ttfb ?? ''}</td>
<td>${esc(e.tipo || '')}<br><small>${esc(e.elemento || '')}</small></td>
<td><small>${esc((e.url || '').replace(BASE, ''))}</small></td><td>${esc(e.formato || '')}</td><td>${kb(e.byte)}</td>
<td>${e.larghezzaFile ?? ''} / ${e.larghezzaNecessaria ?? ''}</td><td>${esc([e.loading && 'loading=' + e.loading, e.fetchpriority && 'fetchpriority=' + e.fetchpriority, e.srcset && 'srcset', e.inPicture && 'picture'].filter(Boolean).join(' '))}</td>
<td>${r.immaginiNumero}</td><td>${kb(r.immaginiByte)}</td><td>${r.nonWebp} (${kb(r.nonWebpByte)} KB)</td><td>${r.sovradimensionate}</td><td>${r.fuoriSchermoSenzaLazy}</td></tr>`;
}).join('\n');
const sintesi = profili.map(p => {
  const rr = risultati.filter(r => r.profilo === p && r.lcp != null);
  const sopra = rr.filter(r => r.lcp > 2500).length;
  const tipi = {};
  for (const r of rr) { const t = r.lcpElemento ? r.lcpElemento.tipo : 'nessuno'; tipi[t] = (tipi[t] || 0) + 1; }
  return `<li><b>${p}</b>: ${rr.length} pagine, LCP mediano ${mediana(rr.map(r => r.lcp))} ms, sopra 2,5 s: ${sopra}; TTFB mediano ${mediana(rr.map(r => r.ttfb))} ms; elemento LCP: ${Object.entries(tipi).map(([t, n]) => esc(t) + ' ' + n).join(', ')}; immagini non WebP ${kb(rr.reduce((s, r) => s + r.nonWebpByte, 0))} KB in totale</li>`;
}).join('');
fs.writeFileSync(path.join(OUT, 'rapporto.html'), `<!doctype html><meta charset="utf-8"><title>LCP e immagini — ${esc(BASE)}</title>
<style>body{font:14px system-ui,sans-serif;margin:16px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:4px 6px;vertical-align:top}
th{background:#1f4e79;color:#fff;position:sticky;top:0}.ok{background:#d9f2d9}.medio{background:#fff2cc}.male{background:#f8d0d0}small{color:#555;word-break:break-all}</style>
<h1>LCP e immagini — ${esc(BASE)}</h1><p>${new Date().toLocaleString('it-IT')} · ${RIP} caricamenti per pagina (mediana) · mobile: 412×823, DPR 1,75, CPU 4×, 4G lenta (1,6 Mbit/s, 150 ms) · cache disattivata</p>
<ul>${sintesi}</ul>
<table><tr><th>Profilo</th><th>Pagina</th><th>LCP ms</th><th>FCP ms</th><th>TTFB ms</th><th>Elemento LCP</th><th>File LCP</th><th>Formato</th><th>KB</th><th>Larghezza file / necessaria</th><th>Attributi</th><th>Immagini</th><th>KB immagini</th><th>Non WebP</th><th>Sovradim.</th><th>Fuori schermo senza lazy</th></tr>
${righe}</table>`);
console.log(`\nRapporto: ${path.join(OUT, 'rapporto.html')}`);
