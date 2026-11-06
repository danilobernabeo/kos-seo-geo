#!/usr/bin/env node
/**
 * collaudo-kos-24.mjs — KOS-24: controlla sul sito servito il dato strutturato delle schede struttura e delle landing,
 * a partire dagli stessi file importati con imposta-tv.php.
 *
 * Uso (da scripts/collaudo, PowerShell):
 *   $env:AUTH = "<password dello stage>"        # utente «kos»; non serve in produzione
 *   node collaudo-kos-24.mjs --base=https://stage.kosgroup.com [--uscita=kos24-stage]
 *   node collaudo-kos-24.mjs --base=https://kosgroup.com --uscita=kos24-prod
 *
 * Schede (kosgroup/dati/voce-24/strutture-yext-2026-11-06.json): HTTP 200; un solo <script type="application/ld+json">
 * con JSON valido; nessuno script Yext; nodo {URL}#localbusiness con il tipo della TV str.tipo, indirizzo, telefono,
 * email e coordinate uguali alle TV; sameAs solo URL https; nessuna entità HTML («&#...;», «&amp;») nel JSON.
 * Landing (landing.json, aperte con /index.php?id=N): un solo JSON-LD, nessuna FAQPage, un solo BreadcrumbList,
 * nodo della struttura collegata (lnd.struttura) con i servizi di lnd.servizi.
 * Una richiesta ogni 0,6 s. Scrive <uscita>/collaudo-kos-24.csv e stampa il riepilogo.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const arg = Object.fromEntries(process.argv.slice(2).map(a => a.replace(/^--/, '').split(/=(.*)/s).slice(0, 2)));
if (!arg.base) { console.error('Uso: node collaudo-kos-24.mjs --base=https://stage.kosgroup.com [--uscita=cartella]'); process.exit(1); }
const base = arg.base.replace(/\/$/, '');
const uscita = arg.uscita || 'collaudo-kos-24';
const radice = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const dati = f => JSON.parse(fs.readFileSync(path.join(radice, 'kosgroup', 'dati', 'voce-24', f), 'utf8').replace(/^﻿/, ''));
const schede = dati(arg.strutture || 'strutture-yext-2026-11-06.json').risorse;
const correzioni = (() => { try { return dati('correzioni-sito.json'); } catch { return {}; } })();
const landing = dati('landing.json').risorse;
const auth = process.env.AUTH
  ? 'Basic ' + Buffer.from(process.env.AUTH.includes(':') ? process.env.AUTH : 'kos:' + process.env.AUTH).toString('base64')
  : null;
const pausa = ms => new Promise(r => setTimeout(r, ms));

async function scarica(url) {
  for (let t = 0; t < 3; t++) {
    try {
      const res = await fetch(url, { redirect: 'manual', headers: { ...(auth && { Authorization: auth }), 'User-Agent': 'kos-seo-geo collaudo-kos-24' } });
      if (res.status === 401) { console.error('FERMO: 401, password dello stage in $env:AUTH?'); process.exit(1); }
      return { stato: res.status, html: await res.text(), dove: res.headers.get('location') || '' };
    } catch (e) { await pausa(2000); }
  }
  return { stato: 0, html: '', dove: '' };
}

const tipi = n => [].concat(n?.['@type'] || []);
const percorso = u => { try { return new URL(u).pathname.replace(/\/$/, ''); } catch { return ''; } };
const soloCifre = s => String(s || '').replace(/[^\d+]/g, '');

function grafo(html, err) {
  const blocchi = [...html.matchAll(/<script[^>]+application\/ld\+json[^>]*>([\s\S]*?)<\/script>/gi)].map(m => m[1]);
  if (blocchi.length !== 1) err.push(`${blocchi.length} blocchi JSON-LD (atteso 1)`);
  if (/yext|knowledgetags/i.test(html.match(/<script[^>]*src=[^>]*>/gi)?.join(' ') || '')) err.push('script Yext presente');
  if (!blocchi.length) return [];
  const testo = blocchi[0];
  if (/&#\d+;|&#x[0-9a-f]+;|&amp;|&[a-z]+;/i.test(testo)) err.push('entità HTML nel JSON-LD');
  try {
    const j = JSON.parse(testo);
    return j['@graph'] || [j];
  } catch (e) { err.push('JSON-LD non valido: ' + e.message.slice(0, 60)); return []; }
}

const righe = [['tipo', 'pagina', 'esito', 'dettaglio']];
let ok = 0, ko = 0;

for (const s of schede) {
  const tv = { ...s.tv, ...(correzioni[s.uri] || {}) };
  const url = `${base}/${s.uri}`;
  const { stato, html } = await scarica(url);
  const err = [];
  if (stato !== 200) err.push('HTTP ' + stato);
  const g = stato === 200 ? grafo(html, err) : [];
  const n = g.find(x => String(x['@id'] || '').endsWith('#localbusiness') && percorso(x['@id']) === '/' + s.uri);
  if (stato === 200 && !n && tv['str.via']) err.push('nodo #localbusiness assente');
  if (n && !tv['str.via']) err.push('nodo #localbusiness senza indirizzo nelle TV');
  if (n) {
    const atteso = String(tv['str.tipo'] || '').split(',').map(x => x.trim()).filter(Boolean);
    // convenzione: un tipo che non è attività locale (DiagnosticLab) va affiancato a MedicalBusiness
    if (atteso.includes('DiagnosticLab') && !atteso.includes('MedicalBusiness')) atteso.push('MedicalBusiness');
    if (atteso.length && atteso.join(',') !== tipi(n).join(',')) err.push(`@type ${tipi(n).join(',')} ≠ ${atteso.join(',')}`);
    if (percorso(n.url) !== '/' + s.uri) err.push('url ≠ scheda');
    const a = n.address || {};
    if (tv['str.via'] && a.streetAddress !== tv['str.via']) err.push(`via «${a.streetAddress}»`);
    if (tv['str.cap'] && a.postalCode !== tv['str.cap']) err.push(`cap «${a.postalCode}»`);
    if (tv['str.citta'] && a.addressLocality !== tv['str.citta']) err.push(`città «${a.addressLocality}»`);
    if (tv['str.telefono'] && soloCifre(n.telephone) !== soloCifre(tv['str.telefono'])) err.push(`telefono «${n.telephone}»`);
    if (tv['str.email'] && n.email !== tv['str.email']) err.push(`email «${n.email}»`);
    if (tv['str.lat'] && Math.abs((n.geo?.latitude ?? 0) - Number(tv['str.lat'])) > 1e-6) err.push('latitudine');
    if (tv['str.lng'] && Math.abs((n.geo?.longitude ?? 0) - Number(tv['str.lng'])) > 1e-6) err.push('longitudine');
    for (const u of [].concat(n.sameAs || [])) if (!/^https:\/\/[^\s"<>]+$/.test(u)) err.push('sameAs non valido: ' + u);
    if (tv['str.orari'] && !n.openingHoursSpecification) err.push('orari assenti');
    if (!n.parentOrganization && !n.brand && !n.branchOf) err.push('nessun legame con il gruppo');
  }
  const esito = err.length ? 'KO' : 'OK';
  esito === 'OK' ? ok++ : ko++;
  righe.push(['scheda', '/' + s.uri, esito, err.join('; ')]);
  if (err.length) console.log(`KO  /${s.uri}: ${err.join('; ')}`);
  await pausa(Number(arg.pausa ?? 600));
}

for (const l of landing) {
  const url = `${base}/index.php?id=${l.id}`;
  const { stato, html } = await scarica(url);
  const err = [];
  if (stato !== 200) err.push('HTTP ' + stato);
  const g = stato === 200 ? grafo(html, err) : [];
  if (g.some(x => tipi(x).includes('FAQPage'))) err.push('FAQPage presente');
  const br = g.filter(x => tipi(x).includes('BreadcrumbList')).length;
  if (br > 1) err.push(`${br} BreadcrumbList`);
  const sUri = String(l.tv['lnd.struttura'] || '').replace(/^@uri:/, '');
  const n = g.find(x => String(x['@id'] || '').endsWith('#localbusiness') && percorso(x['@id']) === '/' + sUri);
  if (stato === 200 && !n) err.push('nodo della struttura ' + sUri + ' assente');
  const attesi = String(l.tv['lnd.servizi'] || '').split('\n').map(r => (r.split('|')[1] || '').trim().toLowerCase()).filter(Boolean);
  if (n) {
    const serv = [].concat(n.availableService || [], n.availableTest || [], n.knowsAbout || []);
    const nomi = new Set(serv.map(x => String(x.name || '').toLowerCase()));
    const mancano = attesi.filter(x => !nomi.has(x));
    if (mancano.length) err.push(`servizi mancanti ${mancano.length}/${attesi.length}: ${mancano.slice(0, 3).join(', ')}`);
  }
  const canonico = (html.match(/<link[^>]+rel=["']canonical["'][^>]*href=["']([^"']+)/i) || [])[1] || '';
  const esito = err.length ? 'KO' : 'OK';
  esito === 'OK' ? ok++ : ko++;
  righe.push(['landing', `${l.id} ${percorso(canonico)}`, esito, err.join('; ')]);
  if (err.length) console.log(`KO  landing ${l.id} ${percorso(canonico)}: ${err.join('; ')}`);
  await pausa(Number(arg.pausa ?? 600));
}

fs.mkdirSync(uscita, { recursive: true });
const csv = righe.map(r => r.map(c => /[;"\n]/.test(c) ? `"${String(c).replace(/"/g, '""')}"` : c).join(';')).join('\n');
fs.writeFileSync(path.join(uscita, 'collaudo-kos-24.csv'), '﻿' + csv);
console.log(`== ${schede.length} schede e ${landing.length} landing: OK ${ok}, KO ${ko} — ${path.join(uscita, 'collaudo-kos-24.csv')}`);
