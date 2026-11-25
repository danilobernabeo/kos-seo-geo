#!/usr/bin/env node
/**
 * collaudo.mjs — Clinica Dentale Villa dei Pini (lavoro extra): controlla sul sito servito la scheda nuova, la landing,
 * Villa dei Pini Civitanova, l'elenco delle cliniche dentali, sitemap e llms.txt. Nessuna dipendenza (Node 18+).
 *
 * Uso (PowerShell, dalla radice del repository):
 *   $env:AUTH = "kos:<password dello stage>"     # solo per lo stage
 *   node extra/kos-clinica-dentale-villa-dei-pini/collaudo.mjs --base=https://stage.kosgroup.com
 *   node extra/kos-clinica-dentale-villa-dei-pini/collaudo.mjs --base=https://kosgroup.com
 *
 * Esiti: OK, KO (da correggere), AVV (da guardare: segnaposto sullo stage, difetti ereditati dal modello).
 * Scrive collaudo-clinica-dentale-<host>.csv nella cartella corrente. Una richiesta ogni 0,5 s.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const arg = Object.fromEntries(process.argv.slice(2).map(a => a.replace(/^--/, '').split(/=(.*)/s).slice(0, 2)));
if (!arg.base) { console.error('Uso: node collaudo.mjs --base=https://stage.kosgroup.com'); process.exit(1); }
const base = arg.base.replace(/\/$/, '');
const PROD = !/stage\.|modx\.dev/.test(base);
const C = JSON.parse(fs.readFileSync(path.join(path.dirname(fileURLToPath(import.meta.url)), 'config.json'), 'utf8'));
const ALIAS = C.campi.alias;
const URI = `it/centri/${ALIAS}`;
const auth = process.env.AUTH ? 'Basic ' + Buffer.from(process.env.AUTH.includes(':') ? process.env.AUTH : 'kos:' + process.env.AUTH).toString('base64') : null;
const pausa = ms => new Promise(r => setTimeout(r, ms));

async function scarica(url, metodo = 'GET') {
  await pausa(500);
  try {
    const res = await fetch(url, { method: metodo, redirect: 'manual', headers: { ...(auth && { Authorization: auth }), 'User-Agent': 'kos-seo-geo collaudo-clinica-dentale' } });
    if (res.status === 401) { console.error('FERMO: 401, password dello stage in $env:AUTH?'); process.exit(1); }
    return { stato: res.status, html: metodo === 'GET' ? await res.text() : '', lunghezza: Number(res.headers.get('content-length') || 0), tipo: res.headers.get('content-type') || '' };
  } catch (e) { return { stato: 0, html: '', errore: e.message }; }
}

const righe = [['pagina', 'controllo', 'esito', 'dettaglio']];
const conta = { OK: 0, KO: 0, AVV: 0 };
const esito = (pagina, controllo, stato, dettaglio = '') => {
  conta[stato]++;
  righe.push([pagina, controllo, stato, dettaglio]);
  console.log(`  ${stato.padEnd(3)} ${controllo}${dettaglio ? ' — ' + dettaglio : ''}`);
};
const verifica = (pagina, controllo, ok, dettaglio = '') => esito(pagina, controllo, ok ? 'OK' : 'KO', dettaglio);

const ent = s => String(s ?? '').replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(+n)).replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCodePoint(parseInt(n, 16)))
  .replace(/&agrave;/g, 'à').replace(/&egrave;/g, 'è').replace(/&eacute;/g, 'é').replace(/&Eacute;/g, 'É').replace(/&ograve;/g, 'ò').replace(/&ugrave;/g, 'ù').replace(/&igrave;/g, 'ì')
  .replace(/&rsquo;/g, '’').replace(/&nbsp;/g, ' ').replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&');
const testo = s => ent(String(s).replace(/<[^>]+>/g, ' ')).replace(/\s+/g, ' ').trim();
const meta = (html, nome) => ent((html.match(new RegExp(`<meta[^>]+name="${nome}"[^>]*>`, 'i'))?.[0].match(/content="([^"]*)"/i) || [])[1] ?? '');
const grafi = html => [...html.matchAll(/<script[^>]+application\/ld\+json[^>]*>([\s\S]*?)<\/script>/gi)].map(m => m[1]);
const titoli = html => [...html.matchAll(/<h([1-6])\b[^>]*>([\s\S]*?)<\/h\1>/gi)].map(m => `H${m[1]} ${testo(m[2])}`);

/* ------------------------------------------------------------------ scheda */
const pScheda = `/${URI}`;
console.log(`\n== Scheda ${base}${pScheda}`);
const s = await scarica(base + pScheda);
verifica(pScheda, 'HTTP 200 senza redirect', s.stato === 200, String(s.stato));
const h = s.html;
const title = ent((h.match(/<title>([\s\S]*?)<\/title>/i) || [])[1] ?? '').trim();
verifica(pScheda, 'title entro 65 con « | KOS» una volta', title.length <= 65 && title.split('| KOS').length === 2, `${title.length}: ${title}`);
verifica(pScheda, 'title come config.json', title === C.campi.longtitle);
const desc = meta(h, 'description');
verifica(pScheda, 'description entro 160 come config.json', desc.length <= 160 && desc === C.campi.description, `${desc.length}: ${desc}`);
verifica(pScheda, 'indicizzabile (niente noindex)', !/noindex/i.test(meta(h, 'robots')), meta(h, 'robots') || 'nessun meta robots');
const canonical = (h.match(/<link[^>]+rel="canonical"[^>]*href="([^"]*)"/i) || h.match(/<link[^>]+href="([^"]*)"[^>]*rel="canonical"/i) || [])[1] || '';
verifica(pScheda, 'canonical sulla scheda', canonical.endsWith(pScheda), canonical);
const t = titoli(h);
verifica(pScheda, 'un solo H1 = pagetitle', t.filter(x => x.startsWith('H1')).length === 1 && t.includes(`H1 ${C.campi.pagetitle}`), t.filter(x => x.startsWith('H1')).join(' | '));
for (const sez of ['H3 La struttura', 'H3 Aree di intervento', 'H3 Équipe medica multidisciplinare', 'H3 Come raggiungerci', 'H2 Prenota online']) {
  verifica(pScheda, `sezione «${sez}»`, t.includes(sez));
}
console.log('     titoli: ' + t.join(' · '));

// dato strutturato
const g = grafi(h);
verifica(pScheda, 'un solo JSON-LD', g.length === 1, String(g.length));
let nodi = [];
try { nodi = JSON.parse(g[0] || '{}')['@graph'] || []; } catch (e) { verifica(pScheda, 'JSON-LD valido', false, e.message); }
const canon = `https://kosgroup.com${pScheda}`;
const lb = nodi.find(n => n['@id'] === `${canon}#localbusiness`);
verifica(pScheda, 'nodo #localbusiness', !!lb, lb ? [].concat(lb['@type']).join(',') : 'assente');
if (lb) {
  verifica(pScheda, '@type Dentist', [].concat(lb['@type']).includes('Dentist'));
  verifica(pScheda, 'telefono', lb.telephone === C.tv['str.telefono'], lb.telephone);
  verifica(pScheda, 'indirizzo', lb.address?.streetAddress === C.tv['str.via'] && lb.address?.postalCode === C.tv['str.cap'] && lb.address?.addressLocality === C.tv['str.citta'], JSON.stringify(lb.address));
  const orari = (lb.openingHoursSpecification || []).map(o => `${[].concat(o.dayOfWeek).map(d => d.replace('https://schema.org/', '').slice(0, 2)).join(',')} ${o.opens}-${o.closes}`).join('; ');
  verifica(pScheda, 'orari lun-ven 8:30-19, sab 8:30-13', /Mo,Tu,We,Th,Fr 08:30-19:00/.test(orari) && /Sa 08:30-13:00/.test(orari), orari);
  verifica(pScheda, 'coordinate', !!lb.geo?.latitude && !!lb.geo?.longitude, JSON.stringify(lb.geo));
  verifica(pScheda, 'medicalSpecialty Dentistry', [].concat(lb.medicalSpecialty || []).includes('Dentistry'));
  verifica(pScheda, 'knowsAbout con Odontoiatria', (lb.knowsAbout || []).some(k => /odontoiatria$/.test(k.url || '')), (lb.knowsAbout || []).map(k => k.name).join(', '));
  esito(pScheda, 'hasMap (place_id della scheda Google)', lb.hasMap ? 'OK' : 'AVV', lb.hasMap || 'manca str.place_id: da aggiungere quando c\'è');
  esito(pScheda, 'image', lb.image ? 'OK' : 'AVV', lb.image?.url || 'nessuna foto nella galleria');
  // parentOrganization come le altre due cliniche
  const v = await scarica(`${base}/${C.modello_uri}`);
  const lbV = (JSON.parse(grafi(v.html)[0] || '{}')['@graph'] || []).find(n => String(n['@id']).endsWith('#localbusiness'));
  verifica(pScheda, 'parentOrganization come Villalba', JSON.stringify(lb.parentOrganization) === JSON.stringify(lbV?.parentOrganization), JSON.stringify(lb.parentOrganization));
}
const wp = nodi.find(n => [].concat(n['@type']).includes('WebPage'));
verifica(pScheda, 'WebPage → mainEntity #localbusiness', wp?.mainEntity?.['@id'] === `${canon}#localbusiness`);
const bl = nodi.find(n => [].concat(n['@type']).includes('BreadcrumbList'));
const briciole = (bl?.itemListElement || []).map(i => i.name);
const visibili = (h.match(/<nav[^>]*id="breadcrumbs"[\s\S]*?<\/nav>/i)?.[0].match(/<(?:a|span)\b[^>]*>([^<]*)<\/(?:a|span)>/gi) || []).map(testo).filter(x => x && x !== '|');
verifica(pScheda, 'BreadcrumbList Home › Strutture › Clinica Dentale Villa dei Pini', briciole.join(' › ') === 'Home › Strutture › Clinica Dentale Villa dei Pini', briciole.join(' › '));
verifica(pScheda, 'briciole visibili = BreadcrumbList', visibili.join(' › ') === briciole.join(' › '), visibili.join(' › '));

// segnaposto, link, chiamate
const segnaposto = [...h.matchAll(/\[DA COMPLETARE[^\]]*\]/g)].map(m => m[0]);
esito(pScheda, 'segnaposto da completare', segnaposto.length ? (PROD ? 'KO' : 'AVV') : 'OK', segnaposto.length ? `${segnaposto.length}: ${segnaposto.join(' | ')}` : 'nessuno');
verifica(pScheda, 'nessun tag MODX nell\'HTML', !/\[\[[^\]]{0,60}\]\]/.test(h));
const href = [...h.matchAll(/<a\b[^>]*\shref="([^"]*)"/gi)].map(m => ent(m[1]));
const nonAssoluti = [...new Set(href.filter(u => !/^(\/(?!\/)|#|https?:|tel:|mailto:|javascript:)/.test(u)))];
esito(pScheda, 'link interni assoluti', nonAssoluti.length ? 'AVV' : 'OK', nonAssoluti.length ? `${nonAssoluti.join(' ')} (dal modello, fuori da questo lavoro)` : '');
verifica(pScheda, 'nessun riferimento a Villalba fuori dai link voluti', !/Robusti|62100|1930310|19 30 310|710baa66/.test(h));

// prenotazione
verifica(pScheda, 'sezione #prenota-online', /id="prenota-online"/.test(h));
const b64 = (h.match(/KOS_DATA_DB_B64 = '([^']*)'/) || [])[1];
if (b64) {
  const d = JSON.parse(Buffer.from(b64, 'base64').toString('utf8'));
  const branche = Object.keys(d.branche);
  const np = branche.reduce((t, k) => t + d.branche[k].prestazioni.length, 0);
  verifica(pScheda, 'barra: solo Odontoiatria', branche.length === 1 && branche[0].toLowerCase() === C.barra_branca.toLowerCase(), branche.join(','));
  verifica(pScheda, `barra: ${C.barra_prestazioni_attese} prestazioni`, np === C.barra_prestazioni_attese, branche.map(k => d.branche[k].prestazioni.map(p => p.title).join(', ')).join(' / '));
} else verifica(pScheda, 'barra: dati presenti', false, 'KOS_DATA_DB_B64 assente');

// news
verifica(pScheda, 'news dell\'inaugurazione nel blocco «Le ultime news da …»', /Le ultime news da[\s\S]*villa-dei-pini-inaugurata-la-clinica-dentale-kos/.test(h));

// immagini: peso e alt
const imgs = [...h.matchAll(/<img\b[^>]*>/gi)].map(m => m[0]);
const senzaAlt = imgs.filter(i => !/\salt="/i.test(i));
verifica(pScheda, 'immagini con attributo alt', senzaAlt.length === 0, senzaAlt.slice(0, 3).join(' '));
const altVuoti = imgs.filter(i => /\salt=""/i.test(i) && /strutture\/|cache-img|\/bg-|\/footer\//.test(i));
esito(pScheda, 'alt vuoti sulle foto', altVuoti.length ? 'AVV' : 'OK', altVuoti.slice(0, 3).join(' '));
const fonti = new Set();
for (const m of h.matchAll(/(?:src|srcset|data-pswp-src|imagesrcset)="([^"]+)"/gi)) for (const p of m[1].split(',')) {
  const u = p.trim().split(/\s+/)[0];
  if (/^\/?assets\/.*\.(jpe?g|png|webp|gif)$/i.test(u) && /villa-dei-pini|clinica-dentale/i.test(u)) fonti.add(u.startsWith('/') ? u : '/' + u);
}
for (const u of fonti) {
  const r = await scarica(base + u, 'HEAD');
  verifica(pScheda, `peso ${u}`, r.stato === 200 && r.lunghezza <= 200000, `${r.stato}, ${r.lunghezza} byte`);
}
if (!fonti.size) esito(pScheda, 'foto della clinica', 'AVV', 'nessuna immagine della clinica nella pagina');

/* ------------------------------------------------------------------ landing */
const pLanding = `/${C.landing_uri}`;
console.log(`\n== Landing ${base}${pLanding}`);
const l = await scarica(base + pLanding);
if (l.stato !== 200) esito(pLanding, 'HTTP 200', 'AVV', `${l.stato} (assente su questo sito?)`);
else {
  const r = meta(l.html, 'robots');
  verifica(pLanding, 'noindex, follow', /noindex/i.test(r) && /(^|,\s*)follow/i.test(r) && !/nofollow/i.test(r), r || 'nessun meta robots');
  verifica(pLanding, 'niente JSON-LD scritto a mano (MedicalClinic)', !grafi(l.html).some(x => /MedicalClinic/.test(x)), `${grafi(l.html).length} blocchi JSON-LD`);
  const prenota = [...l.html.matchAll(/<a\b[^>]*>/gi)].map(m => m[0]).filter(a => /cd-prenota|data-cd-prenota-link/.test(a));
  verifica(pLanding, 'link «Prenota online» verso la scheda', prenota.length > 0 && prenota.every(a => a.includes(`href="${C.landing.url_prenota}"`)), `${prenota.length} link, ${prenota.filter(a => a.includes('href="#"')).length} con href="#"`);
  verifica(pLanding, 'prenotazione online attiva (CD_CONFIG)', l.html.includes(`"prenotaOnline": {"attivo": true, "url": "${C.landing.url_prenota}"}`));
}

/* ------------------------------------------------------------------ Villa dei Pini, elenco, sitemap, llms */
for (const [p, cosa] of [[`/${C.sede_uri}`, 'Villa dei Pini Civitanova'], [`/${C.elenco_uri}`, 'elenco cliniche dentali']]) {
  console.log(`\n== ${cosa} ${base}${p}`);
  const r = await scarica(base + p);
  verifica(p, 'HTTP 200', r.stato === 200, String(r.stato));
  verifica(p, 'link alla scheda', r.html.includes(`href="${pScheda}"`) || r.html.includes(`href="https://kosgroup.com${pScheda}"`));
  verifica(p, 'un solo JSON-LD', grafi(r.html).length === 1, String(grafi(r.html).length));
}
console.log(`\n== Sitemap e llms.txt`);
const sm = await scarica(`${base}/sitemap.xml`);
verifica('/sitemap.xml', 'scheda presente', sm.html.includes(`${URI}</loc>`));
verifica('/sitemap.xml', 'landing assente', !sm.html.includes(`${C.landing_uri}</loc>`));
if (PROD) {
  const ll = await scarica(`${base}/llms.txt`);
  verifica('/llms.txt', 'riga della clinica', ll.html.includes(`(https://kosgroup.com${pScheda})`));
}

const out = `collaudo-clinica-dentale-${new URL(base).host}.csv`;
fs.writeFileSync(out, '﻿' + righe.map(r => r.map(x => `"${String(x).replace(/"/g, '""')}"`).join(';')).join('\n'));
console.log(`\n== OK ${conta.OK}, KO ${conta.KO}, AVV ${conta.AVV} — dettaglio in ${out}`);
process.exit(conta.KO ? 1 : 0);
