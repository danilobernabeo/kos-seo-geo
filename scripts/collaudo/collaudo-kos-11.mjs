#!/usr/bin/env node
/**
 * collaudo-kos-11.mjs — KOS-11: dato strutturato delle pagine dei professionisti (template «medici») sul sito servito.
 * Elenco delle pagine dall'export di scripts/checks/kos-medici-pagine.php (kosgroup/dati/medici/pagine-medici-*.csv,
 * solo le pubblicate).
 *
 * Uso (da scripts/collaudo, PowerShell):
 *   $env:AUTH = "<password dello stage>"        # utente «kos»; non serve in produzione
 *   node collaudo-kos-11.mjs --base=https://stage.kosgroup.com --uscita=kos11-stage
 *   node collaudo-kos-11.mjs --base=https://kosgroup.com --uscita=kos11-prod
 *
 * Per ogni pagina: HTTP 200; un solo JSON-LD valido senza entità HTML; WebPage con mainEntity verso il nodo del
 * professionista; nodo IndividualPhysician ({URL}#medico, address e practicesAt della sede) oppure Person
 * ({URL}#person, jobTitle = ruolo della pagina, worksFor); name = titolo della pagina;
 * image se la pagina ha la foto. Scrive <uscita>/collaudo-kos-11.csv con la classificazione (tipo, specialità,
 * strutture) da far rivedere a KOS, e stampa il riepilogo.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const arg = Object.fromEntries(process.argv.slice(2).map(a => a.replace(/^--/, '').split(/=(.*)/s).slice(0, 2)));
if (!arg.base) { console.error('Uso: node collaudo-kos-11.mjs --base=https://stage.kosgroup.com [--uscita=cartella]'); process.exit(1); }
const base = arg.base.replace(/\/$/, '');
const uscita = arg.uscita || 'collaudo-kos-11';
const radice = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const cartella = path.join(radice, 'kosgroup', 'dati', 'medici');
const file = arg.pagine || fs.readdirSync(cartella).filter(f => /^pagine-medici-\d+\.csv$/.test(f)).sort().pop();
const testoCsv = fs.readFileSync(path.join(cartella, file), 'utf8').replace(/^﻿/, '');

function leggiCsv(t) {   // CSV con «;» e campi tra virgolette
  const righe = []; let riga = [], campo = '', q = false;
  for (let i = 0; i < t.length; i++) {
    const c = t[i];
    if (q) { if (c === '"' && t[i + 1] === '"') { campo += '"'; i++; } else if (c === '"') q = false; else campo += c; }
    else if (c === '"') q = true;
    else if (c === ';') { riga.push(campo); campo = ''; }
    else if (c === '\n') { riga.push(campo.replace(/\r$/, '')); righe.push(riga); riga = []; campo = ''; }
    else campo += c;
  }
  if (campo || riga.length) { riga.push(campo); righe.push(riga); }
  const [testa, ...corpo] = righe;
  return corpo.filter(r => r.length > 1).map(r => Object.fromEntries(testa.map((k, i) => [k, r[i] ?? ''])));
}
const pagine = leggiCsv(testoCsv).filter(p => p.publicato === '1');
const auth = process.env.AUTH
  ? 'Basic ' + Buffer.from(process.env.AUTH.includes(':') ? process.env.AUTH : 'kos:' + process.env.AUTH).toString('base64')
  : null;
const pausa = ms => new Promise(r => setTimeout(r, ms));

async function scarica(url) {
  for (let t = 0; t < 3; t++) {
    try {
      const res = await fetch(url, { redirect: 'manual', headers: { ...(auth && { Authorization: auth }), 'User-Agent': 'kos-seo-geo collaudo-kos-11' } });
      if (res.status === 401) { console.error('FERMO: 401, password dello stage in $env:AUTH?'); process.exit(1); }
      return { stato: res.status, html: await res.text() };
    } catch { await pausa(2000); }
  }
  return { stato: 0, html: '' };
}
const tipi = n => [].concat(n?.['@type'] || []);
const percorso = u => { try { return new URL(u).pathname.replace(/\/$/, ''); } catch { return ''; } };

const righe = [['pagina', 'esito', 'ruolo', 'tipo', 'specialita', 'strutture', 'dettaglio']];
let ok = 0, ko = 0; const conta = {};
for (const p of pagine) {
  const { stato, html } = await scarica(`${base}/${p.uri}`);
  const err = []; let n = null;
  if (stato !== 200) err.push('HTTP ' + stato);
  else {
    const blocchi = [...html.matchAll(/<script[^>]+application\/ld\+json[^>]*>([\s\S]*?)<\/script>/gi)].map(m => m[1]);
    if (blocchi.length !== 1) err.push(`${blocchi.length} blocchi JSON-LD`);
    if (blocchi.length && /&#\d+;|&#x[0-9a-f]+;|&amp;|&[a-z]+;/i.test(blocchi[0])) err.push('entità HTML nel JSON-LD');
    let g = [];
    try { const j = JSON.parse(blocchi[0] || '{}'); g = j['@graph'] || [j]; } catch (e) { err.push('JSON-LD non valido'); }
    const wp = g.find(x => String(x['@id'] || '').endsWith('#webpage'));
    n = g.find(x => /#(medico|person)$/.test(String(x['@id'] || '')) && percorso(x['@id']) === '/' + p.uri);
    if (!n) err.push('nodo del professionista assente');
    else {
      if (wp?.mainEntity?.['@id'] !== n['@id']) err.push('mainEntity della pagina ≠ professionista');
      if (n.name !== p.pagetitle.trim()) err.push(`name «${n.name}»`);
      if (p.infoImage && !n.image?.url) err.push('immagine assente');
      const t = tipi(n).join(',');
      if (t === 'IndividualPhysician') {
        if (!n['@id'].endsWith('#medico')) err.push('@id non #medico');
        if (n.jobTitle || n.worksFor) err.push('proprietà di Person su IndividualPhysician');
        if (!(n.practicesAt || []).length) err.push('practicesAt vuoto');
        if (!n.address?.streetAddress) err.push('address assente (Google: attività locale senza indirizzo)');
      } else if (t === 'Person') {
        if (!n['@id'].endsWith('#person')) err.push('@id non #person');
        if (n.practicesAt) err.push('practicesAt su Person');
        if ((n.jobTitle || '') !== p.ruolo.trim()) err.push(`jobTitle «${n.jobTitle}»`);
        if (!n.worksFor) err.push('worksFor assente');
      } else err.push('tipo ' + t);
    }
  }
  const tipo = n ? tipi(n).join(',') : '';
  const strutture = [].concat(n?.practicesAt || n?.worksFor || []).map(s => s.name || s['@id']).join(' | ');
  conta[tipo || '-'] = (conta[tipo || '-'] || 0) + 1;
  const esito = err.length ? 'KO' : 'OK'; esito === 'OK' ? ok++ : ko++;
  righe.push(['/' + p.uri, esito, p.ruolo.trim(), tipo, [].concat(n?.medicalSpecialty || []).join(', '), strutture, err.join('; ')]);
  if (err.length) console.log(`KO  /${p.uri}: ${err.join('; ')}`);
  await pausa(Number(arg.pausa ?? 600));
}
fs.mkdirSync(uscita, { recursive: true });
const csv = righe.map(r => r.map(c => /[;"\n]/.test(c) ? `"${String(c).replace(/"/g, '""')}"` : c).join(';')).join('\n');
fs.writeFileSync(path.join(uscita, 'collaudo-kos-11.csv'), '﻿' + csv);
console.log(`== ${pagine.length} pagine (${file}): OK ${ok}, KO ${ko} — tipi ${JSON.stringify(conta)} — ${path.join(uscita, 'collaudo-kos-11.csv')}`);
