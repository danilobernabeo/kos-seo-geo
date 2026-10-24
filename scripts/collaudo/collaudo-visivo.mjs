#!/usr/bin/env node
/**
 * Collaudo visivo: confronta lo stage con la produzione, pagina per pagina, a più larghezze, e controlla sullo stage
 * i difetti che si vedono solo nel browser. Uso dal PC (lo stage e la produzione non sono raggiungibili dal cloud).
 *
 * Installazione (una volta, nella cartella scripts/collaudo):
 *   npm install
 *   npx playwright install chromium
 *
 * Uso (PowerShell):
 *   $env:AUTH = "kos:<password dello stage>"
 *   node collaudo-visivo.mjs --stage=https://tcm7b0i8.modx.dev --prod=https://anniazzurri.it --pagine=pagine-aa.txt
 * Opzioni:
 *   --larghezze=390,768,1366,1920   larghezze in px (default); l'altezza della finestra è quella tipica del dispositivo
 *   --solo-stage                    niente confronto con la produzione (solo controlli)
 *   --soglia=0.5                    % di pixel diversi oltre la quale la pagina si segnala come «cambiata»
 *   --uscita=collaudo-<data>        cartella del rapporto (default con data e ora)
 *   --tempi=5                       misura anche il tempo di risposta (TTFB): per ogni pagina una richiesta di
 *                                   riscaldamento e N misure alternate stage/produzione, mediana in ms (0 = no)
 *   --solo-tempi                    solo la misura dei tempi, senza browser
 * L'elenco delle pagine accetta commenti in fondo alla riga («/it/strutture  # 3 generica»), anche in UTF-16 (output
 * di Windows PowerShell 5): va bene il file di risorse-per-template.php --campione.
 *
 * Controlli sullo stage, per ogni pagina e larghezza:
 *   - stato HTTP, errori JavaScript e della console, risorse dello stesso sito non trovate (4xx/5xx);
 *   - immagini rotte (caricate senza dimensioni), immagini deformate (proporzioni diverse da quelle del file, salvo
 *     object-fit cover/contain), immagini senza attributo alt;
 *   - scorrimento orizzontale (contenuto più largo della finestra), numero di H1;
 *   - quante immagini arrivano in WebP.
 * Confronto: screenshot dell'intera pagina sullo stage e in produzione, differenza in pixel e immagine delle differenze
 * (in rosso). Le animazioni sono fermate; caroselli e contenuti che cambiano a ogni caricamento possono dare differenze
 * che non sono difetti: il rapporto mette le tre immagini affiancate per giudicare.
 * Rapporto: <uscita>/rapporto.html (pagine ordinate per gravità) e <uscita>/risultati.json.
 */
import { chromium } from 'playwright';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';
import fs from 'node:fs';
import path from 'node:path';

const opz = Object.fromEntries(process.argv.slice(2).map(a => {
  const m = a.match(/^--([\w-]+)(?:=(.*))?$/);
  return m ? [m[1], m[2] ?? true] : [a, true];
}));
const STAGE = String(opz.stage || '').replace(/\/$/, '');
const PROD = opz['solo-stage'] ? '' : String(opz.prod || '').replace(/\/$/, '');
if (!STAGE || !opz.pagine) {
  console.error('Uso: node collaudo-visivo.mjs --stage=https://... --prod=https://... --pagine=pagine-aa.txt [--solo-stage]');
  process.exit(1);
}
const LARGHEZZE = String(opz.larghezze || '390,768,1366,1920').split(',').map(Number);
const ALTEZZA = { 390: 844, 768: 1024, 1366: 768, 1920: 1080 };
const SOGLIA = Number(opz.soglia ?? 0.5);
const ora = new Date();
const stamp = ora.toISOString().slice(0, 16).replace(/[-:T]/g, '').replace(/^(\d{8})/, '$1-');
const OUT = path.resolve(String(opz.uscita || `collaudo-${stamp}`));
fs.mkdirSync(path.join(OUT, 'img'), { recursive: true });

const grezzo = fs.readFileSync(String(opz.pagine));
const testoPagine = grezzo[0] === 0xff && grezzo[1] === 0xfe ? grezzo.toString('utf16le') : grezzo.toString('utf8');
const pagine = testoPagine.replace(/^\uFEFF/, '').split(/\r?\n/).map(s => s.split('#')[0].trim()).filter(Boolean)
  .map(s => s.startsWith('/') ? s : '/' + s);
const TEMPI = Number(opz.tempi ?? 0);
const [utente, password] = (process.env.AUTH || '').split(/:(.*)/s);
const credenziali = utente ? { username: utente, password: password || '' } : undefined;

const FERMA = `*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}
  html{scroll-behavior:auto!important}
  #CybotCookiebotDialog,#CybotCookiebotDialogBodyUnderlay,#CookiebotWidget{display:none!important}`;   // banner dei cookie solo in produzione

function nomeFile(p, w, tipo) {
  const base = (p === '/' ? 'home' : p.replace(/^\/|\/$/g, '').replace(/[^\w-]+/g, '_')).slice(0, 80);
  return `${base}-${w}-${tipo}.png`;
}

async function apri(browser, base, p, w, conCredenziali) {
  const ctx = await browser.newContext({
    viewport: { width: w, height: ALTEZZA[w] || 900 },
    deviceScaleFactor: 1,
    httpCredentials: conCredenziali ? credenziali : undefined,
    isMobile: w < 500, hasTouch: w < 500,
    locale: 'it-IT',
  });
  const page = await ctx.newPage();
  const log = { console: [], js: [], risorse: [] };
  const origine = new URL(base).host;
  page.on('console', m => { if (m.type() === 'error') log.console.push(m.text().slice(0, 300)); });
  page.on('pageerror', e => log.js.push(String(e.message || e).slice(0, 300)));
  page.on('response', r => {
    try {
      const u = new URL(r.url());
      if (u.host === origine && r.status() >= 400) log.risorse.push(`${r.status()} ${u.pathname}`);
    } catch { /* URL non valido */ }
  });
  let stato = 0;
  try {
    // «load» e poi rete ferma al massimo 10 s: alcune pagine (mappe, reCAPTCHA, video) non smettono mai di caricare
    const r = await page.goto(base + p, { waitUntil: 'load', timeout: 60000 });
    stato = r ? r.status() : 0;
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  } catch (e) {
    log.js.push('caricamento: ' + String(e.message || e).split('\n')[0]);
  }
  await page.addStyleTag({ content: FERMA }).catch(() => {});
  // scorre fino in fondo per far caricare le immagini in differita, poi torna su
  await page.evaluate(async () => {
    const passo = Math.max(300, Math.floor(window.innerHeight * 0.8));
    for (let y = 0; y < document.documentElement.scrollHeight; y += passo) {
      window.scrollTo(0, y);
      await new Promise(r => setTimeout(r, 120));
    }
    window.scrollTo(0, 0);
  }).catch(() => {});
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.evaluate(() => Promise.all([...document.images].filter(i => !i.complete)
    .map(i => new Promise(r => { i.onload = i.onerror = r; setTimeout(r, 5000); })))).catch(() => {});
  return { ctx, page, stato, log };
}

async function controlli(page) {
  return page.evaluate(() => {
    const vis = el => {
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 2 && r.height > 2;
    };
    const imgs = [...document.images];
    const rotte = [], deformate = [], senzaAlt = [];
    let webp = 0, viste = 0;
    for (const i of imgs) {
      const src = (i.currentSrc || i.src || '').replace(location.origin, '');
      if (!i.hasAttribute('alt')) senzaAlt.push(src);
      // rotta: caricamento finito senza dimensioni (anche se per questo occupa 0 px); escluse le nascoste dal CSS
      if (i.complete && i.naturalWidth === 0 && src && getComputedStyle(i).display !== 'none'
          && !i.closest('[hidden]')) { rotte.push(src); continue; }
      if (!vis(i)) continue;
      viste++;
      if (/\.webp(\?|$)/i.test(i.currentSrc || '')) webp++;
      const fit = getComputedStyle(i).objectFit;
      if (fit === 'cover' || fit === 'contain' || !i.naturalWidth) continue;
      // dimensioni del contenuto, senza padding e bordi (es. icone con pb-2)
      const r = i.getBoundingClientRect(), cs = getComputedStyle(i), px = v => parseFloat(v) || 0;
      const cw = r.width - px(cs.paddingLeft) - px(cs.paddingRight) - px(cs.borderLeftWidth) - px(cs.borderRightWidth);
      const ch = r.height - px(cs.paddingTop) - px(cs.paddingBottom) - px(cs.borderTopWidth) - px(cs.borderBottomWidth);
      if (cw < 3 || ch < 3) continue;
      const a = cw / ch, n = i.naturalWidth / i.naturalHeight;
      if (Math.abs(a / n - 1) > 0.04) deformate.push(`${src} (mostrata ${Math.round(cw)}×${Math.round(ch)}, file ${i.naturalWidth}×${i.naturalHeight})`);
    }
    const sw = document.documentElement.scrollWidth, vw = window.innerWidth;
    let largo = '';
    if (sw > vw + 1) {
      const el = [...document.querySelectorAll('body *')].find(e => vis(e) && e.getBoundingClientRect().right > vw + 1);
      largo = el ? `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}${el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : ''}` : '';
    }
    return {
      h1: document.querySelectorAll('h1').length,
      immagini: viste, webp, rotte, deformate, senzaAlt: [...new Set(senzaAlt)].slice(0, 20),
      scorrimento: sw > vw + 1 ? `${sw}px su ${vw}px${largo ? ' (es. ' + largo + ')' : ''}` : '',
      titolo: document.title,
    };
  });
}

function confronta(fa, fb, fd) {
  const a = PNG.sync.read(fs.readFileSync(fa)), b = PNG.sync.read(fs.readFileSync(fb));
  const w = Math.min(a.width, b.width), h = Math.min(a.height, b.height);
  const taglia = img => {
    if (img.width === w && img.height === h) return img;
    const o = new PNG({ width: w, height: h });
    PNG.bitblt(img, o, 0, 0, w, h, 0, 0);
    return o;
  };
  const A = taglia(a), B = taglia(b), D = new PNG({ width: w, height: h });
  const n = pixelmatch(A.data, B.data, D.data, w, h, { threshold: 0.15, alpha: 0.25 });
  fs.writeFileSync(fd, PNG.sync.write(D));
  // prima riga diversa: dice dove comincia la differenza (sotto quella riga tutto può essere solo spostato)
  let prima = -1;
  for (let y = 0; y < h && prima < 0; y++) for (let x = 0; x < w; x++) {
    const o = (y * w + x) * 4;
    if (D.data[o] > 200 && D.data[o + 1] < 80 && D.data[o + 2] < 80) { prima = y; break; }
  }
  return { diff: +(100 * n / (w * h)).toFixed(2), altezze: [a.height, b.height], primaDiff: prima };
}

// ---- tempo di risposta (TTFB): fetch di Node, si ferma alla ricezione delle intestazioni ----
async function ttfb(url, conCredenziali) {
  const h = { 'User-Agent': 'Mozilla/5.0 collaudo-kos-seo-geo', 'Accept': 'text/html' };
  if (conCredenziali && credenziali) h.Authorization = 'Basic ' + Buffer.from(`${utente}:${password || ''}`).toString('base64');
  const t0 = performance.now();
  try {
    const r = await fetch(url, { headers: h, redirect: 'manual' });
    const t = performance.now() - t0;
    await r.arrayBuffer().catch(() => {});
    return { ms: t, stato: r.status };
  } catch (e) { return { ms: NaN, stato: 0 }; }
}
const mediana = a => { const b = a.filter(Number.isFinite).sort((x, y) => x - y); return b.length ? Math.round(b[Math.floor((b.length - 1) / 2)]) : null; };
const tempi = [];
if (TEMPI > 0) {
  console.log(`Tempo di risposta: ${pagine.length} pagine, ${TEMPI} misure per sito dopo una di riscaldamento`);
  for (const p of pagine) {
    const s = [], q = [];
    await ttfb(STAGE + p, true); if (PROD) await ttfb(PROD + p, false);         // riscaldamento (cache di MODX)
    let statoS = 0, statoP = 0;
    for (let i = 0; i < TEMPI; i++) {
      const a = await ttfb(STAGE + p, true); s.push(a.ms); statoS = a.stato;
      if (PROD) { const b = await ttfb(PROD + p, false); q.push(b.ms); statoP = b.stato; }
    }
    const t = { pagina: p, stage: mediana(s), prod: PROD ? mediana(q) : null, statoS, statoP };
    tempi.push(t);
    console.log(`  ${p}  stage ${t.stage} ms${PROD ? `, produzione ${t.prod} ms` : ''}${statoS !== 200 ? ` (stato ${statoS})` : ''}`);
  }
  fs.writeFileSync(path.join(OUT, 'tempi.json'), JSON.stringify(tempi, null, 1));
}
if (opz['solo-tempi'] !== undefined) {
  const e = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  fs.writeFileSync(path.join(OUT, 'rapporto.html'), `<!doctype html><html lang="it"><head><meta charset="utf-8"><title>Tempi ${e(stamp)}</title></head><body style="font-family:system-ui,sans-serif">${tabellaTempi(e)}</body></html>`);
  console.log(`\nRapporto: ${path.join(OUT, 'rapporto.html')}`);
  process.exit(0);
}
function tabellaTempi(e) {
  if (!tempi.length) return '';
  const ms = v => v == null ? '' : v + ' ms';
  return `<h2>Tempo di risposta (TTFB, mediana di ${TEMPI} misure a cache calda)</h2>
<p>Misurato dal PC: comprende la rete. Stage e produzione sono cloud diversi: il confronto utile è la stessa pagina prima e
dopo il rilascio sullo stesso cloud; qui serve a scoprire pagine anomale.</p>
<table><tr><th>Pagina</th><th>Stage</th>${PROD ? '<th>Produzione</th>' : ''}</tr>
${tempi.map(t => `<tr><td>${e(t.pagina)}</td><td>${ms(t.stage)}${t.statoS !== 200 ? ' (' + t.statoS + ')' : ''}</td>${PROD ? `<td>${ms(t.prod)}${t.statoP !== 200 ? ' (' + t.statoP + ')' : ''}</td>` : ''}</tr>`).join('')}
<tr><th>Mediana</th><th>${ms(mediana(tempi.map(t => t.stage)))}</th>${PROD ? `<th>${ms(mediana(tempi.map(t => t.prod)))}</th>` : ''}</tr></table>`;
}

const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
const risultati = [];
let k = 0;
const tot = pagine.length * LARGHEZZE.length;
for (const p of pagine) {
  for (const w of LARGHEZZE) {
    k++;
    const r = { pagina: p, larghezza: w, problemi: [] };
    const s = await apri(browser, STAGE, p, w, true);
    r.stato = s.stato;
    Object.assign(r, await controlli(s.page).catch(e => ({ errore: String(e) })));
    r.js = s.log.js; r.console = s.log.console; r.risorse = [...new Set(s.log.risorse)];
    const fs_ = path.join(OUT, 'img', nomeFile(p, w, 'stage'));
    await s.page.screenshot({ path: fs_, fullPage: true }).catch(e => r.problemi.push('screenshot: ' + e.message));
    await s.ctx.close();
    r.img = { stage: 'img/' + path.basename(fs_) };
    if (PROD) {
      const q = await apri(browser, PROD, p, w, false);
      r.statoProd = q.stato;
      // gli stessi controlli in produzione: ciò che c'è già là non è un effetto delle modifiche
      const cp = await controlli(q.page).catch(() => ({}));
      const gia = (a, b) => (a || []).filter(x => (b || []).some(y => y.split(' (')[0] === x.split(' (')[0]));
      r.giaInProd = {
        js: gia(r.js, q.log.js), deformate: gia(r.deformate, cp.deformate), senzaAlt: gia(r.senzaAlt, cp.senzaAlt),
        rotte: gia(r.rotte, cp.rotte), scorrimento: r.scorrimento && cp.scorrimento ? r.scorrimento : '',
      };
      for (const k of ['js', 'deformate', 'senzaAlt', 'rotte']) r[k] = (r[k] || []).filter(x => !r.giaInProd[k].includes(x));
      if (r.giaInProd.scorrimento) r.scorrimento = '';
      const fp = path.join(OUT, 'img', nomeFile(p, w, 'prod'));
      await q.page.screenshot({ path: fp, fullPage: true }).catch(() => {});
      await q.ctx.close();
      if (fs.existsSync(fp) && fs.existsSync(fs_)) {
        const fd = path.join(OUT, 'img', nomeFile(p, w, 'diff'));
        Object.assign(r, confronta(fp, fs_, fd));
        r.img.prod = 'img/' + path.basename(fp); r.img.diff = 'img/' + path.basename(fd);
      }
    }
    // gravità
    if (r.stato !== 200) r.problemi.push(`stato HTTP ${r.stato}`);
    if (r.js?.length) r.problemi.push(`${r.js.length} errori JavaScript`);
    if (r.risorse?.length) r.problemi.push(`${r.risorse.length} risorse non trovate`);
    if (r.rotte?.length) r.problemi.push(`${r.rotte.length} immagini rotte`);
    if (r.deformate?.length) r.problemi.push(`${r.deformate.length} immagini deformate`);
    if (r.scorrimento) r.problemi.push('scorrimento orizzontale');
    if (r.h1 !== 1) r.problemi.push(`${r.h1} H1`);
    if (r.senzaAlt?.length) r.problemi.push(`${r.senzaAlt.length} immagini senza alt`);
    if (r.altezze && Math.abs(r.altezze[0] - r.altezze[1]) > 40) r.problemi.push(`altezza della pagina ${r.altezze[0]} → ${r.altezze[1]} px`);
    if (r.diff > SOGLIA) r.problemi.push(`${r.diff}% di pixel diversi dalla produzione${r.primaDiff >= 0 ? ` (da y=${r.primaDiff} px)` : ''}`);
    if (r.giaInProd) {
      const g = r.giaInProd, t = [];
      if (g.js.length) t.push(`${g.js.length} errori JS`); if (g.deformate.length) t.push(`${g.deformate.length} deformate`);
      if (g.senzaAlt.length) t.push(`${g.senzaAlt.length} senza alt`); if (g.rotte.length) t.push(`${g.rotte.length} rotte`);
      if (g.scorrimento) t.push('scorrimento orizzontale');
      r.giaTesto = t.join(', ');
    }
    r.peso = (r.stato !== 200 || r.js?.length || r.rotte?.length || r.deformate?.length || r.scorrimento ? 100 : 0)
      + (r.risorse?.length ? 50 : 0) + Math.min(r.diff || 0, 40) + (r.altezze ? Math.min(Math.abs(r.altezze[0] - r.altezze[1]) / 50, 20) : 0);
    risultati.push(r);
    console.log(`[${k}/${tot}] ${w}px ${p}  ${r.problemi.length ? '⚠ ' + r.problemi.join('; ') : 'ok'}`);
  }
}
await browser.close();

fs.writeFileSync(path.join(OUT, 'risultati.json'), JSON.stringify(risultati, null, 1));
const e = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const lista = (t, a) => a?.length ? `<p><b>${t}</b></p><ul>${a.map(x => `<li><code>${e(x)}</code></li>`).join('')}</ul>` : '';
const ordinati = [...risultati].sort((a, b) => b.peso - a.peso);
const html = `<!doctype html><html lang="it"><head><meta charset="utf-8"><title>Collaudo visivo ${e(stamp)}</title>
<style>body{font-family:system-ui,sans-serif;margin:16px;color:#1b1b1b}h2{font-size:17px;margin:28px 0 6px}
.ok{color:#1a7f37}.ko{color:#b42318}.riga{display:flex;gap:8px;align-items:flex-start;overflow-x:auto}
.riga figure{margin:0;flex:0 0 auto}.riga img{width:300px;border:1px solid #ccc;display:block}
figcaption{font-size:12px;color:#555}code{font-size:12px}table{border-collapse:collapse}td,th{border-bottom:1px solid #ddd;padding:4px 8px;font-size:13px;text-align:left}
details{margin-bottom:10px}</style></head><body>
<h1>Collaudo visivo — ${e(STAGE)}${PROD ? ' rispetto a ' + e(PROD) : ''}</h1>
<p>${pagine.length} pagine × ${LARGHEZZE.join(', ')} px; ${risultati.filter(r => r.problemi.length).length} combinazioni con segnalazioni.
Le immagini sono cliccabili (dimensione piena). In rosso, nella terza colonna, i pixel diversi dalla produzione.</p>
${tabellaTempi(e)}
<h2>Pagine</h2>
<table><tr><th>Pagina</th><th>px</th><th>Segnalazioni (solo stage)</th><th>Già in produzione</th><th>Immagini (WebP)</th></tr>
${ordinati.map((r, i) => `<tr><td><a href="#r${i}">${e(r.pagina)}</a></td><td>${r.larghezza}</td><td class="${r.problemi.length ? 'ko' : 'ok'}">${e(r.problemi.join('; ') || 'ok')}</td><td>${e(r.giaTesto || '')}</td><td>${r.immagini ?? ''} (${r.webp ?? ''})</td></tr>`).join('')}</table>
${ordinati.map((r, i) => `<details id="r${i}" ${r.peso >= 20 ? 'open' : ''}><summary><h2 style="display:inline">${e(r.pagina)} — ${r.larghezza}px</h2>
<span class="${r.problemi.length ? 'ko' : 'ok'}"> ${e(r.problemi.join('; ') || 'ok')}</span></summary>
<p><a href="${e(STAGE + r.pagina)}">stage</a>${PROD ? ` · <a href="${e(PROD + r.pagina)}">produzione</a>` : ''} · «${e(r.titolo)}»</p>
<div class="riga">${PROD && r.img.prod ? `<figure><a href="${r.img.prod}"><img src="${r.img.prod}" loading="lazy" alt=""></a><figcaption>produzione</figcaption></figure>` : ''}
<figure><a href="${r.img.stage}"><img src="${r.img.stage}" loading="lazy" alt=""></a><figcaption>stage</figcaption></figure>
${r.img.diff ? `<figure><a href="${r.img.diff}"><img src="${r.img.diff}" loading="lazy" alt=""></a><figcaption>differenze ${r.diff}%</figcaption></figure>` : ''}</div>
${lista('Errori JavaScript', r.js)}${lista('Errori della console', r.console)}${lista('Risorse non trovate', r.risorse)}
${lista('Immagini rotte', r.rotte)}${lista('Immagini deformate', r.deformate)}${lista('Immagini senza alt', r.senzaAlt)}
${r.scorrimento ? `<p><b>Scorrimento orizzontale:</b> ${e(r.scorrimento)}</p>` : ''}
${r.giaInProd ? lista('Già in produzione — errori JavaScript', r.giaInProd.js) + lista('Già in produzione — immagini deformate', r.giaInProd.deformate) + lista('Già in produzione — immagini senza alt', r.giaInProd.senzaAlt) + lista('Già in produzione — immagini rotte', r.giaInProd.rotte) + (r.giaInProd.scorrimento ? `<p><b>Già in produzione — scorrimento orizzontale:</b> ${e(r.giaInProd.scorrimento)}</p>` : '') : ''}</details>`).join('\n')}
</body></html>`;
fs.writeFileSync(path.join(OUT, 'rapporto.html'), html);
console.log(`\nRapporto: ${path.join(OUT, 'rapporto.html')}`);
