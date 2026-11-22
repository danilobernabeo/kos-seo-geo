#!/usr/bin/env node
/**
 * Sprint 4 (KOS 15 e 25, AA 13 e 22) — costruzione dei fogli di stile minificati dei due siti.
 *
 * Uso (dalla radice del repository, una volta `npm install` in scripts/css):
 *   node scripts/css/costruisci-css.mjs            # costruisce e stampa i pesi
 *   node scripts/css/costruisci-css.mjs --verifica # come sopra, e si ferma se un'uscita nel repository non è aggiornata
 *
 * Sorgenti (si modificano solo queste; le uscite si rigenerano):
 *   kosgroup/files/css-sorgenti/*.css  fogli di produzione del 22/11 (assets/css), nell'ordine dell'head
 *   anniazzurri/files/css/*.css         app.css e bootstrap-icons.min.css (produzione 22/11), custom.css e kos-seo-geo.css
 *                                       (sorgenti del progetto)
 * Uscite:
 *   kosgroup/files/assets/css/kos.min.css   un solo foglio al posto dei 10 dell'head (KOS 15, 25); fontawesome.css
 *                                           escluso perché duplica all.min.css (stessa versione 5.5.0; KOS 16)
 *   anniazzurri/files/dist/css/{app,custom,kos-seo-geo}.css  stessi nomi e stesso ordine negli head, minificati (AA 22);
 *                                           in app.css font-display: swap per Open Sans e LobstersBlush, con facce
 *                                           di ripiego a metriche corrette per limitare lo spostamento (AA 13); l'@import
 *                                           di Bootstrap Icons da jsdelivr sostituito dalla copia locale (AA 12)
 * I percorsi relativi (url(../webfonts/...), url(../assets/fonts/...)) restano validi: le uscite stanno nella stessa
 * cartella delle sorgenti in produzione.
 */
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';
import { transform } from 'lightningcss';

const RADICE = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const VERIFICA = process.argv.includes('--verifica');
const r = (p) => path.join(RADICE, p);

// Metriche delle facce di ripiego (calcolate con fontTools dai woff2 di produzione contro Liberation Sans, metrica
// identica ad Arial; larghezza media su una frase italiana). Vedi docs/piano-sprint-4.md.
const RIPIEGO = [
  { famiglia: 'Open Sans Fallback', peso: 400, locali: ['Arial', 'ArialMT', 'Liberation Sans', 'Helvetica'],
    size: '103.32%', ascent: '103.45%', descent: '28.36%', gap: '0%' },
  { famiglia: 'Open Sans Fallback', peso: 600, locali: ['Arial Bold', 'Arial-BoldMT', 'Liberation Sans Bold', 'Helvetica Bold'],
    size: '99.68%', ascent: '107.23%', descent: '29.39%', gap: '0%' },
  { famiglia: 'Open Sans Fallback', peso: 700, locali: ['Arial Bold', 'Arial-BoldMT', 'Liberation Sans Bold', 'Helvetica Bold'],
    size: '103.64%', ascent: '103.13%', descent: '28.27%', gap: '0%' },
  { famiglia: 'LobstersBlush Fallback', peso: 700, locali: ['Arial Bold', 'Arial-BoldMT', 'Liberation Sans Bold', 'Helvetica Bold'],
    size: '83.23%', ascent: '123.40%', descent: '55.87%', gap: '2.52%' },
];
const facceRipiego = RIPIEGO.map(f => `@font-face{font-family:"${f.famiglia}";src:${f.locali.map(l => `local("${l}")`).join(',')};` +
  `font-weight:${f.peso};font-style:normal;size-adjust:${f.size};ascent-override:${f.ascent};descent-override:${f.descent};line-gap-override:${f.gap}}`).join('\n');

/** AA 13: font-display e pile di caratteri con il ripiego subito dopo il carattere web. */
function fontAnniAzzurri(css) {
  let n = { display: 0, pile: 0 };
  css = css.replace(/@font-face\s*\{[^}]*\}/g, (blocco) => {
    if (!/font-family:\s*"?(Open Sans|LobstersBlush)"?\s*;/.test(blocco)) return blocco; // icone: resta block
    n.display++;
    return /font-display:/.test(blocco) ? blocco.replace(/font-display:\s*[\w-]+/, 'font-display: swap')
      : blocco.replace(/\}$/, '  font-display: swap;\n}');
  });
  css = css.replace(/(font-family|--bs-body-font-family)(\s*:\s*)([^;}]*)/g, (tutto, prop, sep, valore) => {
    if (/Fallback/.test(valore)) return tutto;
    const nuovo = valore
      .replace(/("Open Sans"|\bOpen Sans\b)/, '"Open Sans", "Open Sans Fallback"')
      .replace(/"LobstersBlush"/, '"LobstersBlush", "LobstersBlush Fallback"');
    if (nuovo !== valore) n.pile++;
    return prop + sep + nuovo;
  });
  // le facce di ripiego dopo eventuali @import (che devono stare in testa)
  const m = css.match(/^(\s*(?:@charset[^;]*;\s*)?(?:@import[^;]*;\s*)*)/);
  return { css: m[1] + facceRipiego + '\n' + css.slice(m[1].length), n };
}

// Browser di riferimento: niente sintassi moderna che i browser più vecchi ignorerebbero (es. @media (width<=991px)).
const v = (M, m = 0) => (M << 16) | (m << 8);
const TARGETS = { chrome: v(80), edge: v(80), firefox: v(78), safari: v(12), ios_saf: v(12), samsung: v(12), opera: v(67) };

/** Errori di battitura che il browser scarta dichiarazione per dichiarazione, mentre il minificatore scarterebbe
 *  la regola intera o li leggerebbe diversamente: si correggono in modo da ottenere lo stesso effetto del browser.
 *  «display: block; !important;» → il browser applica display:block (non importante) e scarta «!important». */
function correzioni(nome, css) {
  let n = 0;
  css = css.replace(/;\s*!important\s*;+/g, () => { n++; return ';'; }).replace(/;\s*;+/g, () => { n++; return ';'; });
  if (n) console.log(`  ${nome}: ${n} punti e virgola o «!important» isolati corretti come li legge il browser`);
  return css;
}

function minifica(nome, codice) {
  const out = transform({ filename: nome, code: Buffer.from(correzioni(nome, codice)), minify: true, errorRecovery: true, targets: TARGETS });
  for (const w of out.warnings) console.log(`  avviso ${nome}: ${w.message}`);
  return out.code.toString();
}

const USCITE = [];
function scrivi(rel, contenuto, sorgentiByte) {
  const p = r(rel);
  const prima = fs.existsSync(p) ? fs.readFileSync(p, 'utf8') : null;
  if (VERIFICA && prima !== contenuto) { console.error(`NON AGGIORNATO: ${rel}`); process.exitCode = 1; }
  fs.mkdirSync(path.dirname(p), { recursive: true });
  fs.writeFileSync(p, contenuto);
  const gz = (b) => zlib.gzipSync(b, { level: 9 }).length;
  USCITE.push({ rel, byte: Buffer.byteLength(contenuto), gz: gz(Buffer.from(contenuto)), sorgentiByte: sorgentiByte.byte, sorgentiGz: sorgentiByte.gz });
}
const pesi = (testi) => ({ byte: testi.reduce((s, t) => s + Buffer.byteLength(t), 0), gz: testi.reduce((s, t) => s + zlib.gzipSync(Buffer.from(t), { level: 9 }).length, 0) });

// --- kosgroup.com: un foglio unico, nell'ordine dell'head ---------------------------------------------------------
const KOS_ORDINE = ['bootstrap.min', 'all.min', /* fontawesome: duplicato di all.min */ 'default', 'nice-select', 'aos',
  'swiper.min', 'photoswipe', 'style', 'custom'];
const kosSorgenti = KOS_ORDINE.map(n => fs.readFileSync(r(`kosgroup/files/css-sorgenti/${n}.css`), 'utf8'));
const kosTutte = [...kosSorgenti, fs.readFileSync(r('kosgroup/files/css-sorgenti/fontawesome.css'), 'utf8')];
const kosParti = KOS_ORDINE.map((n, i) => minifica(`${n}.css`, kosSorgenti[i]));
const kosIntestazione = '/*! kos.min.css — fogli di assets/css uniti e minificati (progetto SEO/GEO, sprint 4). Sorgenti: ' +
  KOS_ORDINE.map(n => n + '.css').join(', ') + '. Font Awesome Pro 5.5.0 (licenza commerciale), Bootstrap (MIT), Swiper (MIT), PhotoSwipe (MIT), AOS (MIT). */\n';
scrivi('kosgroup/files/assets/css/kos.min.css', kosIntestazione + kosParti.join('\n') + '\n', pesi(kosTutte));

// --- anniazzurri.it: stessi file, minificati -----------------------------------------------------------------------
for (const n of ['app', 'custom', 'kos-seo-geo']) {
  let sorg = fs.readFileSync(r(`anniazzurri/files/css/${n}.css`), 'utf8');
  const orig = sorg;
  if (n === 'app') {
    // AA 12: l'@import di Bootstrap Icons 1.8.1 da jsdelivr è una richiesta bloccante in catena verso un altro dominio;
    // al suo posto la copia locale già pubblicata in /dist/css (v1.11.3, stessi codici, font in /dist/css/fonts).
    const imp = /@import url\("https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap-icons@[\d.]+\/font\/bootstrap-icons\.css"\);\s*/;
    if (!imp.test(sorg)) { console.error('app.css: @import di bootstrap-icons non trovato'); process.exit(1); }
    sorg = fs.readFileSync(r('anniazzurri/files/css/bootstrap-icons.min.css'), 'utf8') + '\n' + sorg.replace(imp, '');
    const f = fontAnniAzzurri(sorg);
    sorg = f.css;
    console.log(`app.css: font-display swap su ${f.n.display} facce, ripiego in ${f.n.pile} dichiarazioni`);
    if (f.n.display !== 4) { console.error('Attese 4 facce (3 Open Sans, 1 LobstersBlush)'); process.exit(1); }
  }
  scrivi(`anniazzurri/files/dist/css/${n}.css`, `/*! ${n}.css minificato dalla sorgente anniazzurri/files/css/${n}.css (progetto SEO/GEO) */\n` + minifica(`${n}.css`, sorg) + '\n', pesi([orig]));
}

for (const u of USCITE) {
  console.log(`${u.rel}: ${u.sorgentiByte} → ${u.byte} byte; compressi ${u.sorgentiGz} → ${u.gz}`);
}
