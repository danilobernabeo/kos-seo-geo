#!/usr/bin/env node
/**
 * collaudo.mjs — Punto Prelievi (lavoro extra punti-prelievo): controlla le due schede sul sito servito.
 * Nessuna dipendenza (Node 18+).
 *
 * Uso (PowerShell, dalla radice del repository):
 *   $env:AUTH = "kos:<password dello stage>"     # solo per lo stage
 *   node extra/punti-prelievo/collaudo.mjs --base=https://stage.kosgroup.com --salva     PRIMA dell'installazione:
 *        salva il JSON-LD delle due schede in extra/punti-prelievo/collaudo/jsonld-<host>-<scheda>.json
 *   node extra/punti-prelievo/collaudo.mjs --base=https://stage.kosgroup.com             DOPO: controlli e confronto
 *
 * Controlli: 200; un solo H1; sezione #punto-prelievi con un solo H2 «Punto Prelievi»; orari e MeticaLab in testo HTML;
 * nessun link relativo o «//» e nessun tag MODX nella sezione; immagini con alt, width, height, loading="lazy";
 * PDF 200 application/pdf; copertina 200 e sotto 200 KB; varianti WebP 200 (AVV se mancano: si generano dopo);
 * nessuna parola dell'altra sede nella sezione; JSON-LD identico a quello salvato (dateModified a parte: cambia
 * perché la risorsa è stata salvata, segnalato come AVV). Esiti: OK, KO, AVV. Una richiesta ogni 0,5 s.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const DIR = path.dirname(fileURLToPath(import.meta.url));
const arg = Object.fromEntries(process.argv.slice(2).map(a => a.replace(/^--/, '').split(/=(.*)/s).slice(0, 2)));
if (!arg.base) { console.error('Uso: node collaudo.mjs --base=https://stage.kosgroup.com [--salva]'); process.exit(1); }
const base = arg.base.replace(/\/$/, '');
const host = new URL(base).host;
const C = JSON.parse(fs.readFileSync(path.join(DIR, 'config.json'), 'utf8'));
const auth = process.env.AUTH ? 'Basic ' + Buffer.from(process.env.AUTH.includes(':') ? process.env.AUTH : 'kos:' + process.env.AUTH).toString('base64') : null;
const pausa = ms => new Promise(r => setTimeout(r, ms));
const cartella = path.join(DIR, 'collaudo');
fs.mkdirSync(cartella, { recursive: true });

async function scarica(url, metodo = 'GET') {
  await pausa(500);
  try {
    const res = await fetch(url, { method: metodo, redirect: 'manual', cache: 'no-store', headers: { ...(auth && { Authorization: auth }), 'User-Agent': 'kos-seo-geo collaudo-punti-prelievo' } });
    if (res.status === 401) { console.error('FERMO: 401, password dello stage in $env:AUTH?'); process.exit(1); }
    const buf = metodo === 'GET' ? Buffer.from(await res.arrayBuffer()) : Buffer.alloc(0);
    return { stato: res.status, html: buf.toString('utf8'), byte: buf.length || Number(res.headers.get('content-length') || 0), tipo: res.headers.get('content-type') || '' };
  } catch (e) { return { stato: 0, html: '', byte: 0, tipo: '', errore: e.message }; }
}

const conta = { OK: 0, KO: 0, AVV: 0 };
const esito = (stato, controllo, dettaglio = '') => { conta[stato]++; console.log(`  ${stato.padEnd(3)} ${controllo}${dettaglio ? ' — ' + dettaglio : ''}`); };
const verifica = (ok, controllo, dettaglio = '') => esito(ok ? 'OK' : 'KO', controllo, dettaglio);
const testo = s => String(s).replace(/<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&rsquo;/g, '’').replace(/\s+/g, ' ').trim();
const grafi = html => [...html.matchAll(/<script[^>]+application\/ld\+json[^>]*>([\s\S]*?)<\/script>/gi)].map(m => m[1].trim());
const senzaData = j => j.replace(/"dateModified":"[^"]*"/g, '"dateModified":"…"');

for (const sede of C.sedi) {
  const url = `${base}/${sede.uri}`;
  const nome = path.basename(sede.uri);
  const fileLd = path.join(cartella, `jsonld-${host}-${nome}.json`);
  console.log(`\n== ${url}`);
  const p = await scarica(url);
  verifica(p.stato === 200, 'pagina 200', String(p.stato));
  if (p.stato !== 200) continue;
  const ld = grafi(p.html);
  if ('salva' in arg) {
    fs.writeFileSync(fileLd, JSON.stringify(ld, null, 1));
    esito(p.html.includes('id="punto-prelievi"') ? 'AVV' : 'OK', 'JSON-LD salvato', `${ld.length} blocchi in ${path.relative(process.cwd(), fileLd)}`
      + (p.html.includes('id="punto-prelievi"') ? ' (la sezione c\'è già: non è lo stato di prima)' : ''));
    continue;
  }
  verifica((p.html.match(/<h1\b/gi) || []).length === 1, 'un solo H1 nella pagina');
  const i = p.html.indexOf('<!-- punto-prelievi:inizio'), f = p.html.indexOf('<!-- punto-prelievi:fine -->');
  verifica(i >= 0 && f > i, 'sezione presente (segni di inizio e fine)');
  if (i < 0 || f < i) continue;
  const s = p.html.slice(i, f);
  verifica((s.match(/<h2\b/gi) || []).length === 1 && /<h2[^>]*>\s*Punto Prelievi\s*<\/h2>/.test(s), 'H2 «Punto Prelievi»');
  verifica(s.includes('id="punto-prelievi"') && (p.html.match(/id="punto-prelievi"/g) || []).length === 1, 'ancora #punto-prelievi unica');
  const t = testo(s);
  for (const w of ['dal lunedì al venerdì, dalle 7:00 alle 9:30', '7:00 - 9:30', 'MeticaLab', 'percorsi di prevenzione', sede.REFERTI_SEDE, 'online'])
    verifica(t.includes(w), `testo HTML: «${w}»`);
  for (const w of sede.parole_vietate) verifica(!t.includes(w) && !s.includes(w), `nessun «${w}» (altra sede)`);
  verifica(!s.includes('[['), 'nessun tag MODX non elaborato');
  const link = [...s.matchAll(/\b(?:href|src|srcset)="([^"]*)"/g)].flatMap(m => m[1].split(/\s*,\s*/).map(u => u.trim().split(/\s+/)[0]));
  const cattivi = link.filter(u => !u || u.startsWith('//') || !/^(\/|https:\/\/)/.test(u));
  verifica(!cattivi.length, 'link e immagini con percorso assoluto', cattivi.join(' '));
  for (const img of s.match(/<img\b[^>]*>/gi) || [])
    verifica(/\balt="[^"]+"/.test(img) && /\bwidth="\d+"/.test(img) && /\bheight="\d+"/.test(img) && /loading="lazy"/.test(img), 'immagine con alt, dimensioni e lazy', img.slice(0, 120));
  const pdf = `${base}/assets/doc/${sede.PDF}`;
  const rp = await scarica(pdf);
  verifica(rp.stato === 200 && /pdf/.test(rp.tipo) && rp.html.startsWith('%PDF'), 'PDF 200 application/pdf', `${rp.stato} ${rp.tipo} ${(rp.byte / 1048576).toFixed(2)} MB`);
  const peso = (rp.byte / 1048576).toFixed(1).replace('.', ',');
  verifica(s.includes(`PDF - ${peso}&nbsp;MB`), 'peso nel testo del pulsante', peso);
  const ri = await scarica(`${base}/assets/img/${sede.IMG}.jpg`);
  verifica(ri.stato === 200 && ri.byte < 200 * 1024, 'copertina 200, sotto 200 KB', `${ri.stato} ${Math.round(ri.byte / 1024)} KB`);
  for (const l of [480, 600]) {
    const rv = await scarica(`${base}/assets/cache-img/img/${sede.IMG}-${l}.webp`);
    esito(rv.stato === 200 ? 'OK' : 'AVV', `variante WebP ${l}`, rv.stato === 200 ? `${Math.round(rv.byte / 1024)} KB` : `${rv.stato}: generarla con ottimizza-immagini.php`);
  }
  if (!fs.existsSync(fileLd)) { esito('AVV', 'JSON-LD', `nessun salvataggio di prima (${path.basename(fileLd)}): lanciare --salva prima dell'installazione`); continue; }
  const prima = JSON.parse(fs.readFileSync(fileLd, 'utf8'));
  const uguale = JSON.stringify(prima) === JSON.stringify(ld);
  const ugualeSenzaData = JSON.stringify(prima.map(senzaData)) === JSON.stringify(ld.map(senzaData));
  if (uguale) esito('OK', 'JSON-LD identico a prima');
  else if (ugualeSenzaData) esito('AVV', 'JSON-LD identico a prima tranne dateModified', 'la risorsa è stata salvata: data di modifica aggiornata');
  else {
    esito('KO', 'JSON-LD diverso da prima');
    fs.writeFileSync(path.join(cartella, `jsonld-${host}-${nome}-dopo.json`), JSON.stringify(ld, null, 1));
  }
}
console.log(`\n== ${conta.OK} OK, ${conta.KO} KO, ${conta.AVV} AVV`);
console.log('URL da controllare:\n' + C.sedi.map(s => `  ${base}/${s.uri}#punto-prelievi`).join('\n'));
process.exit(conta.KO ? 2 : 0);
