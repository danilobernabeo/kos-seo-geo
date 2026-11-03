#!/usr/bin/env node
/**
 * verifica-meta.mjs — controlla sul sito servito i campi scritti da aggiorna-campi.php (title, description, H1 da
 * pagetitle, introtext), pagina per pagina, a partire dallo stesso file JSON dell'import.
 *
 * Uso (da scripts/collaudo, PowerShell):
 *   $env:AUTH = "<password dello stage>"        # utente «kos»; non serve in produzione
 *   node verifica-meta.mjs --base=https://stage.kosgroup.com --campi=..\..\kosgroup\dati\kos-21-23\kos-21-23-campi-2026-11-01.json --uscita=kos2123-stage [--noindex=uri1,uri2]
 *
 * Per ogni risorsa: HTTP 200 senza redirect; <title> uguale al longtitle nuovo; « | KOS» al massimo una volta nel
 * <title>; meta description uguale alla nuova; con pagetitle cambiato, H1 e ultima voce del BreadcrumbList uguali al
 * nuovo; introtext nuovo presente nel testo; --noindex: meta robots noindex. Una richiesta ogni 0,8 s (anti-bot).
 * Scrive <uscita>/verifica-meta.csv e stampa il riepilogo (OK / KO per controllo).
 */
import fs from 'node:fs';
import path from 'node:path';

const arg = Object.fromEntries(process.argv.slice(2).map(a => a.replace(/^--/, '').split(/=(.*)/s).slice(0, 2)));
if (!arg.base || !arg.campi) { console.error('Uso: node verifica-meta.mjs --base=https://... --campi=<file.json> [--uscita=cartella] [--noindex=uri,...]'); process.exit(1); }
const base = arg.base.replace(/\/$/, '');
const uscita = arg.uscita || 'verifica-meta';
const noindex = new Set((arg.noindex || '').split(',').filter(Boolean).map(u => u.replace(/^\/|\/$/g, '')));
const auth = process.env.AUTH
  ? 'Basic ' + Buffer.from(process.env.AUTH.includes(':') ? process.env.AUTH : 'kos:' + process.env.AUTH).toString('base64')
  : null;
const man = JSON.parse(fs.readFileSync(arg.campi, 'utf8').replace(/^﻿/, ''));
const URI_HOME = { 1: 'it', 385: 'en' };

// entità HTML: numeriche, lettere accentate (&egrave; → è, per composizione Unicode) e le più comuni nei testi
const DIACR = { grave: '̀', acute: '́', circ: '̂', tilde: '̃', uml: '̈', ring: '̊', cedil: '̧' };
const NOMI = { quot: '"', apos: "'", lt: '<', gt: '>', nbsp: ' ', rsquo: '’', lsquo: '‘', rdquo: '”', ldquo: '“', laquo: '«', raquo: '»',
  ndash: '–', mdash: '—', hellip: '…', euro: '€', deg: '°', middot: '·', bull: '•', copy: '©', reg: '®', szlig: 'ß' };
const ent = s => s.replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(+n)).replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCodePoint(parseInt(n, 16)))
  .replace(/&([a-zA-Z])(grave|acute|circ|tilde|uml|ring|cedil);/g, (_, l, d) => (l + DIACR[d]).normalize('NFC'))
  .replace(/&([a-z]+);/g, (m, n) => NOMI[n] ?? m).replace(/&amp;/g, '&');
const norm = s => ent(String(s ?? '')).replace(/[’‘]/g, "'").replace(/\s+/g, ' ').trim();
const attesa = ms => new Promise(r => setTimeout(r, ms));

const righe = [['id', 'url', 'stato', 'controllo', 'esito', 'atteso', 'trovato']];
const conta = {};
const esito = (r, url, stato, controllo, ok, atteso = '', trovato = '') => {
  conta[controllo] ??= { OK: 0, KO: 0 };
  conta[controllo][ok ? 'OK' : 'KO']++;
  righe.push([r.id, url, stato, controllo, ok ? 'OK' : 'KO', atteso, trovato]);
  if (!ok) console.log(`  KO ${controllo}: ${url}\n     atteso «${atteso}»\n     trovato «${trovato}»`);
};

let n = 0;
for (const r of man.risorse) {
  const uri = (r.uri ?? URI_HOME[r.id] ?? '').replace(/^\/|\/$/g, '');
  const url = `${base}/${uri}`;
  let stato = 0, html = '';
  try {
    const res = await fetch(url, { redirect: 'manual', headers: { ...(auth && { Authorization: auth }), 'User-Agent': 'kos-seo-geo verifica-meta' } });
    stato = res.status;
    html = await res.text();
  } catch (e) { stato = 'errore ' + e.message; }
  esito(r, url, stato, 'HTTP 200', stato === 200, '200', String(stato));
  if (stato === 401) { console.error('FERMO: 401, password dello stage in $env:AUTH?'); process.exit(1); }
  if (stato === 200) {
    const titolo = norm((html.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1]);
    const desc = norm((html.match(/<meta\s+name=["']description["']\s+content=["']([^"']*)["']/i) || html.match(/<meta\s+content=["']([^"']*)["']\s+name=["']description["']/i) || [])[1]);
    const c = r.campi;
    esito(r, url, stato, 'un solo « | KOS» nel title', (titolo.match(/\| KOS/gi) || []).length <= 1, '≤ 1', titolo);
    if (c.longtitle) esito(r, url, stato, 'title', titolo === norm(c.longtitle.dopo), norm(c.longtitle.dopo), titolo);
    if (c.description) esito(r, url, stato, 'description', desc === norm(c.description.dopo), norm(c.description.dopo), desc);
    if (c.pagetitle) {
      const h1 = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map(m => norm(m[1].replace(/<[^>]+>/g, '')));
      esito(r, url, stato, 'H1 (pagetitle)', h1.includes(norm(c.pagetitle.dopo)), norm(c.pagetitle.dopo), h1.join(' / '));
      const bl = [...html.matchAll(/"@type"\s*:\s*"ListItem"[^}]*?"name"\s*:\s*"((?:[^"\\]|\\.)*)"/g)].map(m => norm(JSON.parse(`"${m[1]}"`)));
      esito(r, url, stato, 'BreadcrumbList (pagetitle)', bl.at(-1) === norm(c.pagetitle.dopo), norm(c.pagetitle.dopo), bl.at(-1) ?? '(assente)');
    }
    if (c.introtext) esito(r, url, stato, 'introtext', norm(html.replace(/<[^>]+>/g, ' ')).includes(norm(c.introtext.dopo)), norm(c.introtext.dopo), '');
    if (noindex.has(uri)) esito(r, url, stato, 'noindex', /<meta\s+name=["']robots["'][^>]*noindex/i.test(html), 'noindex', '');
  }
  if (++n % 50 === 0) console.log(`… ${n}/${man.risorse.length}`);
  await attesa(800);
}

fs.mkdirSync(uscita, { recursive: true });
const csv = righe.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(';')).join('\r\n');
fs.writeFileSync(path.join(uscita, 'verifica-meta.csv'), '﻿' + csv);
console.log(`\n== ${man.risorse.length} pagine, ${base}`);
for (const [k, v] of Object.entries(conta)) console.log(`   ${k.padEnd(30)} OK ${v.OK}  KO ${v.KO}`);
console.log(`   dettaglio: ${path.join(uscita, 'verifica-meta.csv')}`);
