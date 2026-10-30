#!/usr/bin/env node
/**
 * Verifica dello sprint 1 in produzione, voce per voce degli audit (KosGroup settembre 2026, Anni Azzurri aprile 2026).
 * SOLA LETTURA: solo richieste GET/HEAD, distanziate (whackabot di MODX Cloud sfida chi fa molte richieste in fretta).
 * Nessuna dipendenza: Node 18+ (fetch). Si lancia dal PC: la produzione è raggiungibile solo da lì.
 *
 * Uso (PowerShell, dalla cartella scripts/collaudo):
 *   node verifica-sprint-1.mjs                       # entrambi i siti, tutte le pagine delle sitemap
 *   node verifica-sprint-1.mjs --sito=aa --limite=50 # prova veloce
 * Opzioni: --sito=kos|aa|entrambi  --limite=N (pagine per sito)  --pausa=600 (ms tra le richieste)  --uscita=<cartella>
 *          --ripetizioni-bot=3  --senza-rendering (salta il controllo del DOM renderizzato con Playwright)
 *
 * Voci controllate (sprint 1):
 *   KOS-3 / AA-14  cache delle risorse statiche (Cache-Control per tipo di file, ?v= su CSS e JS locali di tutte le pagine)
 *   KOS-4          redirect 301 dai domini storici (un solo salto, poi 200; https, https://www, http, http://www)
 *   KOS-6 / AA-19  accesso dei crawler AI (robots.txt per user agent; risposta 200 e stessa pagina del browser)
 *   KOS-18         llms.txt (200, text/plain UTF-8, formato llmstxt.org, link tutti 200 senza redirect)
 *   KOS-19         robots.txt (niente Crawl-delay, Disallow/Allow che iniziano con «/», Sitemap assolute e raggiungibili);
 *                  stesso controllo su anniazzurri.it
 *   AA-7           canonical: uno solo per pagina, assoluto, https, senza www, uguale all'URL della pagina; nessun JS
 *                  locale che crea canonical lato client
 *   AA-8           dato strutturato: un solo blocco JSON-LD con @graph, JSON valido, ente KOS Care (#organization,
 *                  parentOrganization verso KOS, mai sameAs verso kosgroup.com), BreadcrumbList su tutte le pagine
 *                  tranne la home, @id {URL}#breadcrumb, livelli uguali alle briciole visibili
 *   AA-18          H1: uno per pagina su tutte le pagine della sitemap
 *   Igiene (informativa, entrambi): pagine della sitemap che non rispondono 200 diretto, noindex in sitemap,
 *                  blocchi JSON-LD per pagina su kosgroup.com.
 * Rapporto: <uscita>/rapporto.html, <uscita>/risultati.json, <uscita>/pagine.csv.
 */
import fs from 'node:fs';
import path from 'node:path';

const opz = Object.fromEntries(process.argv.slice(2).map(a => {
  const m = a.match(/^--([\w-]+)(?:=(.*))?$/);
  return m ? [m[1], m[2] ?? true] : [a, true];
}));
const SITI_SCELTI = String(opz.sito || 'entrambi');
const LIMITE = opz.limite ? Number(opz.limite) : Infinity;
const PAUSA = Number(opz.pausa ?? 600);
const ora = new Date();
const USCITA = String(opz.uscita || `verifica-sprint-1-${ora.toISOString().slice(0, 16).replace(/[-:T]/g, '')}`);
fs.mkdirSync(USCITA, { recursive: true });

const UA_BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36';
const BOT = {
  'GPTBot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)',
  'OAI-SearchBot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; OAI-SearchBot/1.3; +https://openai.com/searchbot)',
  'ChatGPT-User': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot',
  'ClaudeBot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
  'Claude-User': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)',
  'Claude-SearchBot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-SearchBot/1.0; +Claude-SearchBot@anthropic.com)',
  'PerplexityBot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)',
  'Perplexity-User': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)',
  'Googlebot': 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
  'Bingbot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/116.0.1938.76 Safari/537.36',
  'Gemini-Deep-Research': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Gemini-Deep-Research; +https://gemini.google/overview/deep-research/) Chrome/135.0.0.0 Safari/537.36',
  'Google-CloudVertexBot': 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Google-CloudVertexBot; +https://cloud.google.com/enterprise-search) Chrome/135.0.0.0 Safari/537.36',
};
const RIPETIZIONI_BOT = Number(opz['ripetizioni-bot'] ?? 3);   // richieste per bot e pagina: tempi e stabilità (timeout)
const LENTO_MS = 5000;
// token di robots.txt da controllare (anche quelli senza richieste proprie)
const TOKEN_ROBOTS = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'anthropic-ai',
  'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Googlebot', 'Bingbot', 'Applebot', 'Applebot-Extended', 'CCBot',
  'meta-externalagent', 'meta-externalfetcher', 'meta-webindexer', 'Amazonbot', 'Amzn-SearchBot', 'Amzn-User',
  'DuckAssistBot', 'MistralAI-User', 'MistralAI-Index', 'MistralAI-Training', 'YouBot', 'Google-Agent', 'GoogleOther',
  'Google-CloudVertexBot', 'Google-NotebookLM', 'AI2Bot', 'Diffbot', 'Timpibot', 'webzio', 'ImagesiftBot', 'Kagibot'];

const SITI = {
  kos: {
    nome: 'kosgroup.com', base: 'https://kosgroup.com', home: '/it',
    paginePerBot: ['/it', '/it/centri/casa-cima', '/en'],
    domini: ['polodiagnostico.it', 'neomesia.com', 'sstefano.it', 'myolab.it', 'casadicuravillapini.it'],
    cacheVoce: 'KOS-3', robotsVoce: 'KOS-19', botVoce: 'KOS-6',
  },
  aa: {
    nome: 'anniazzurri.it', base: 'https://anniazzurri.it', home: '/',
    paginePerBot: ['/', '/residenza-anni-azzurri-vico-mercati', '/rsa-in-lombardia'],
    cacheVoce: 'AA-14', robotsVoce: 'AA-19 (robots.txt)', botVoce: 'AA-19',
    org: 'https://anniazzurri.it/#organization', orgPadre: 'https://kosgroup.com/#organization',
  },
};

// solo per le prove dello script: --base-aa=http://127.0.0.1:8765 (il dato strutturato resta quello di anniazzurri.it)
if (opz['base-aa']) SITI.aa.base = String(opz['base-aa']).replace(/\/$/, '');
if (opz['base-kos']) SITI.kos.base = String(opz['base-kos']).replace(/\/$/, '');

/* ---------- utilità ---------- */
const attendi = ms => new Promise(r => setTimeout(r, ms));
let ultima = 0, richieste = 0;
async function chiedi(url, { ua = UA_BROWSER, metodo = 'GET', segui = false } = {}) {
  const attesa = ultima + PAUSA - Date.now();
  if (attesa > 0) await attendi(attesa);
  ultima = Date.now(); richieste++;
  for (let tentativo = 0; tentativo < 2; tentativo++) {
    const t0 = Date.now();
    try {
      const r = await fetch(url, { method: metodo, redirect: segui ? 'follow' : 'manual',
        headers: { 'User-Agent': ua, 'Accept': '*/*' }, signal: AbortSignal.timeout(30000) });
      const corpo = metodo === 'HEAD' ? '' : await r.text();
      if ([403, 429].includes(r.status) && ua === UA_BROWSER) {
        console.error(`\n!! ${r.status} su ${url}: possibile limite di MODX Cloud (whackabot). Fermo: attendere qualche minuto e rilanciare con --pausa=1500.`);
        salva(); process.exit(3);
      }
      return { url, status: r.status, headers: r.headers, corpo, ms: Date.now() - t0, location: r.headers.get('location') };
    } catch (e) {
      if (tentativo) return { url, status: 0, headers: new Headers(), corpo: '', ms: Date.now() - t0, errore: String(e.message || e) };
      await attendi(3000);
    }
  }
}
const entita = s => String(s ?? '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#0?39;|&apos;/g, "'")
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(+n))
  .replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCodePoint(parseInt(n, 16))).replace(/&agrave;/g, 'à').replace(/&egrave;/g, 'è')
  .replace(/&eacute;/g, 'é').replace(/&igrave;/g, 'ì').replace(/&ograve;/g, 'ò').replace(/&ugrave;/g, 'ù');
const testo = h => entita(String(h).replace(/<[^>]+>/g, ' ')).replace(/\s+/g, ' ').trim();
const maxAge = cc => { const m = /max-age=(\d+)/i.exec(cc || ''); return m ? Number(m[1]) : null; };
const assoluto = (u, base) => { try { return new URL(entita(u), base).href; } catch { return null; } };
const senzaFrammento = u => u.split('#')[0];
const normUrl = u => { try { const x = new URL(u); return x.origin + (x.pathname === '/' ? '/' : x.pathname.replace(/\/$/, '')) + x.search; } catch { return u; } };

const esiti = [];   // {sito, voce, titolo, esito: OK|KO|ATTENZIONE|INFO, dettaglio, evidenza: [], elenco: []}
function esito(sito, voce, titolo, ok, dettaglio, evidenza = [], elenco = [], livelloKo = 'KO') {
  const e = { sito, voce, titolo, esito: ok === true ? 'OK' : ok === null ? 'INFO' : livelloKo, dettaglio, evidenza, elenco };
  esiti.push(e);
  console.log(`  ${e.esito.padEnd(10)} ${voce.padEnd(18)} ${titolo}${dettaglio ? ' — ' + dettaglio : ''}`);
}
const righePagine = [];

/* ---------- controlli per sito ---------- */
async function sitemap(base, robotsTxt) {
  const indirizzi = [...robotsTxt.matchAll(/^\s*sitemap:\s*(\S+)/gim)].map(m => m[1]);
  if (!indirizzi.length) indirizzi.push(base + '/sitemap.xml');
  const pagine = new Set(); const visti = new Set(); const problemi = [];
  const coda = [...indirizzi];
  while (coda.length) {
    const u = coda.shift(); if (visti.has(u)) continue; visti.add(u);
    const r = await chiedi(u);
    if (r.status !== 200) { problemi.push(`${u} → ${r.status}`); continue; }
    const locs = [...r.corpo.matchAll(/<loc>\s*([^<\s]+)\s*<\/loc>/g)].map(m => entita(m[1]));
    if (/<sitemapindex/i.test(r.corpo)) coda.push(...locs); else locs.forEach(l => pagine.add(l));
  }
  return { indirizzi, pagine: [...pagine], problemi };
}

function regoleRobots(txt) {
  const gruppi = []; let corrente = null, ultimoUa = false;
  for (const riga of txt.split(/\r?\n/)) {
    const r = riga.replace(/#.*/, '').trim(); if (!r) continue;
    const m = /^([\w-]+)\s*:\s*(.*)$/.exec(r); if (!m) continue;
    const k = m[1].toLowerCase(), v = m[2].trim();
    if (k === 'user-agent') { if (!ultimoUa) { corrente = { ua: [], regole: [] }; gruppi.push(corrente); } corrente.ua.push(v.toLowerCase()); ultimoUa = true; }
    else { ultimoUa = false; if (corrente) corrente.regole.push([k, v]); }
  }
  return gruppi;
}
function gruppoPer(gruppi, token) {
  const t = token.toLowerCase();
  return gruppi.find(g => g.ua.includes(t)) || gruppi.find(g => g.ua.includes('*')) || null;
}

async function controllaRobots(s) {
  const r = await chiedi(s.base + '/robots.txt');
  const ev = [`GET ${s.base}/robots.txt → ${r.status} ${r.headers.get('content-type') || ''}`, r.corpo.trim()];
  if (r.status !== 200) { esito(s.nome, s.robotsVoce, 'robots.txt raggiungibile', false, `stato ${r.status}`, ev); return { corpo: '' }; }
  const crawl = /^\s*crawl-delay\s*:/im.test(r.corpo);
  esito(s.nome, s.robotsVoce, 'nessun Crawl-delay', !crawl, crawl ? 'presente' : '', ev);
  const gruppi = regoleRobots(r.corpo);
  const storte = gruppi.flatMap(g => g.regole).filter(([k, v]) => ['disallow', 'allow'].includes(k) && v !== '' && !v.startsWith('/'));
  esito(s.nome, s.robotsVoce, 'Disallow/Allow che iniziano con «/» (RFC 9309)', storte.length === 0,
    storte.length ? `${storte.length} regole: ${storte.map(x => x.join(': ')).join('; ')}` : `${gruppi.flatMap(g => g.regole).length} regole`);
  const sm = [...r.corpo.matchAll(/^\s*sitemap:\s*(\S+)/gim)].map(m => m[1]);
  const smKo = [];
  for (const u of sm) {
    if (!/^https:\/\//.test(u)) { smKo.push(`${u}: non assoluta o non https`); continue; }
    const x = await chiedi(u);
    if (x.status !== 200 || !/<(urlset|sitemapindex)/.test(x.corpo)) smKo.push(`${u} → ${x.status}`);
  }
  esito(s.nome, s.robotsVoce, 'Sitemap dichiarate, assolute e raggiungibili', sm.length > 0 && smKo.length === 0,
    sm.length ? (smKo.join('; ') || sm.join(', ')) : 'nessuna riga Sitemap');
  // crawler AI: nessun gruppo che li blocchi
  const bloccati = [];
  for (const t of TOKEN_ROBOTS) {
    const g = gruppoPer(gruppi, t);
    if (g && g.regole.some(([k, v]) => k === 'disallow' && v === '/')) bloccati.push(`${t} (gruppo ${g.ua.join(', ')})`);
  }
  esito(s.nome, s.botVoce, `robots.txt: nessun blocco totale per ${TOKEN_ROBOTS.length} crawler (AI e motori)`, bloccati.length === 0,
    bloccati.join('; ') || `gruppi: ${gruppi.map(g => g.ua.join('+')).join(' | ')}`);
  return r;
}

async function controllaBot(s) {
  const elenco = []; let ko = 0;
  for (const p of s.paginePerBot) {
    const rif = await chiedi(s.base + p);
    const titoloRif = (/<title>([\s\S]*?)<\/title>/i.exec(rif.corpo) || [])[1] || '';
    for (const [nome, ua] of Object.entries(BOT)) {
      const tempi = []; let r, okTutte = true;
      for (let i = 0; i < RIPETIZIONI_BOT; i++) {
        await attendi(Math.max(0, 1500 - PAUSA));
        r = await chiedi(s.base + p, { ua });
        tempi.push(r.ms);
        const tit = (/<title>([\s\S]*?)<\/title>/i.exec(r.corpo) || [])[1] || '';
        if (!(r.status === 200 && tit === titoloRif && Math.abs(r.corpo.length - rif.corpo.length) < rif.corpo.length * 0.05) || r.ms > LENTO_MS) okTutte = false;
      }
      const ld = (r.corpo.match(/application\/ld\+json/g) || []).length;
      if (!okTutte) ko++;
      tempi.sort((a, b) => a - b);
      elenco.push(`${okTutte ? 'OK' : 'KO'}  ${nome.padEnd(21)} ${p.padEnd(40)} ${r.status}  ${r.corpo.length} byte (browser ${rif.corpo.length})  JSON-LD ${ld}  tempi ${tempi.join('/')} ms`);
    }
  }
  esito(s.nome, s.botVoce, `${Object.keys(BOT).length} user agent su ${s.paginePerBot.length} pagine, ${RIPETIZIONI_BOT} richieste ciascuno: 200, stessa pagina del browser, risposta sotto ${LENTO_MS / 1000} s`,
    ko === 0, ko ? `${ko} risposte diverse` : '', [], elenco);
}

async function controllaDomini(s) {
  const elenco = []; let ko = 0;
  for (const d of s.domini) {
    for (const v of [`https://${d}/`, `https://www.${d}/`, `http://${d}/`, `http://www.${d}/`]) {
      const r1 = await chiedi(v);
      let catena = `${v} → ${r1.status}${r1.errore ? ' ' + r1.errore : ''}`; let ok = false;
      if ([301, 308].includes(r1.status) && r1.location) {
        const dest = assoluto(r1.location, v);
        const r2 = await chiedi(dest);
        catena += ` ${dest} → ${r2.status}`;
        if (r2.status !== 200 && r2.location) catena += ` ${assoluto(r2.location, dest)}`;
        ok = r2.status === 200 && dest.startsWith(SITI.kos.base + '/');
      }
      if (!ok) ko++;
      elenco.push(`${ok ? 'OK' : 'KO'}  ${catena}`);
    }
  }
  esito(s.nome, 'KOS-4', `redirect dei ${s.domini.length} domini storici: un solo 301 verso kosgroup.com, poi 200`, ko === 0,
    ko ? `${ko} varianti non conformi su ${elenco.length}` : `${elenco.length} varianti`, [], elenco);
  // pagine interne (i backlink puntano spesso lì): un 301 verso kosgroup.com, poi 200
  const interne = []; let koI = 0;
  for (const d of s.domini) for (const pth of ['/chi-siamo', '/contatti', '/index.php?id=2']) {
    const v = `https://${d}${pth}`; const r1 = await chiedi(v);
    let ok = false, riga = `${v} → ${r1.status}`;
    if ([301, 308].includes(r1.status) && r1.location) {
      const dest = assoluto(r1.location, v); const r2 = await chiedi(dest);
      riga += ` ${dest} → ${r2.status}`; ok = r2.status === 200 && dest.startsWith(SITI.kos.base + '/');
    }
    if (!ok) koI++; interne.push(`${ok ? 'OK' : 'KO'}  ${riga}`);
  }
  esito(s.nome, 'KOS-4', 'pagine interne dei domini storici: 301 verso kosgroup.com, poi 200', koI === 0, `${koI} su ${interne.length}`, [], interne, 'ATTENZIONE');
  // l'audit (slide 53) scrive «casadicuravillalpini.it»: il dominio reale è casadicuravillapini.it
  const ref = await chiedi('https://casadicuravillalpini.it/');
  esito(s.nome, 'KOS-4', 'refuso dell\'audit «casadicuravillalpini.it» (informativo)', null,
    ref.status ? `risponde ${ref.status}${ref.location ? ' → ' + ref.location : ''}` : `non risponde (${ref.errore || 'nessun DNS'}): il dominio storico è casadicuravillapini.it`);
}

async function controllaLlms(s) {
  const r = await chiedi(s.base + '/llms.txt');
  const ct = r.headers.get('content-type') || '';
  const ev = [`GET ${s.base}/llms.txt → ${r.status} content-type: ${ct}`];
  esito(s.nome, 'KOS-18', 'llms.txt: 200, text/plain; charset=utf-8', r.status === 200 && /text\/plain/i.test(ct) && /utf-8/i.test(ct), ct, ev);
  const righe = r.corpo.split(/\r?\n/);
  const h1 = righe[0]?.startsWith('# ');
  const sintesi = righe.some(x => x.startsWith('> '));
  const sezioni = righe.filter(x => x.startsWith('## ')).length;
  esito(s.nome, 'KOS-18', 'formato llmstxt.org (# titolo, > sintesi, sezioni ## con elenchi di link)', h1 && sintesi && sezioni > 0,
    `titolo ${h1 ? 'sì' : 'no'}, sintesi ${sintesi ? 'sì' : 'no'}, ${sezioni} sezioni`);
  const link = [...r.corpo.matchAll(/\]\(([^)\s]+)\)/g)].map(m => assoluto(m[1], s.base + '/'));
  const elenco = []; let ko = 0;
  for (const u of [...new Set(link)]) {
    const x = await chiedi(u);
    const ok = x.status === 200;
    if (!ok) { ko++; elenco.push(`${x.status}  ${u}${x.location ? ' → ' + x.location : ''}`); }
  }
  esito(s.nome, 'KOS-18', `link di llms.txt: 200 diretto (${link.length} link)`, ko === 0, ko ? `${ko} link con redirect o errore` : '', [], elenco, 'ATTENZIONE');
  const w = await chiedi('https://www.kosgroup.com/llms.txt');
  esito(s.nome, 'KOS-18', 'variante www di llms.txt: 301 verso https://kosgroup.com/llms.txt', [301, 308].includes(w.status) && assoluto(w.location || '', 'https://www.kosgroup.com/') === s.base + '/llms.txt',
    `${w.status}${w.location ? ' → ' + w.location : ''}`, [], [], 'ATTENZIONE');
}

async function controllaCache(s, pagineHtml) {
  // risorse locali raccolte dalle prime pagine; font dai CSS
  const risorse = new Map();
  for (const { url, corpo } of pagineHtml.slice(0, 6)) {
    for (const m of corpo.matchAll(/<(?:link|script|img|source)\b[^>]*?(?:href|src|srcset)="([^"]+)"/gi)) {
      for (const pezzo of m[1].split(',')) {
        const u = assoluto(pezzo.trim().split(/\s+/)[0], url);
        if (u && u.startsWith(s.base) && /\.(css|js|jpe?g|png|webp|avif|gif|svg|ico|mp4|woff2?|ttf|otf)(\?|$)/i.test(u)) risorse.set(u.split('?')[0] + (u.includes('?') ? '?' + u.split('?')[1] : ''), true);
      }
    }
  }
  const css = [...risorse.keys()].filter(u => /\.css(\?|$)/i.test(u));
  for (const u of css.slice(0, 6)) {
    const r = await chiedi(u);
    for (const m of r.corpo.matchAll(/url\(\s*['"]?([^'")]+\.(?:woff2?|ttf|otf|jpe?g|png|webp|svg)(?:\?[^'")]*)?)['"]?\s*\)/gi)) {
      const f = assoluto(m[1], u); if (f && f.startsWith(s.base)) risorse.set(f, true);
    }
  }
  const atteso = { css: 15552000, js: 15552000, img: 15552000, font: 31536000 };
  const tipo = u => /\.css/i.test(u) ? 'css' : /\.js/i.test(u) ? 'js' : /\.(woff2?|ttf|otf)/i.test(u) ? 'font' : 'img';
  const perTipo = { css: [], js: [], img: [], font: [] };
  const campione = Object.entries([...risorse.keys()].reduce((a, u) => ((a[tipo(u)] ||= []).push(u), a), {}))
    .flatMap(([, l]) => l.slice(0, 12));
  for (const u of campione) {
    const r = await chiedi(u, { metodo: 'HEAD' });
    perTipo[tipo(u)].push({ u, status: r.status, cc: r.headers.get('cache-control') || '(assente)', ma: maxAge(r.headers.get('cache-control')) });
  }
  for (const [t, l] of Object.entries(perTipo)) {
    if (!l.length) continue;
    const ko = l.filter(x => x.status !== 200 || x.ma === null || x.ma < atteso[t]);
    esito(s.nome, s.cacheVoce, `Cache-Control ${t} ≥ ${atteso[t]} s (${l.length} file)`, ko.length === 0,
      ko.length ? `${ko.length} sotto soglia` : `es. ${l[0].u.replace(s.base, '')}: ${l[0].cc}`, [],
      l.map(x => `${x.ma !== null && x.ma >= atteso[t] && x.status === 200 ? 'OK' : 'KO'}  ${x.status}  ${x.cc}  ${x.u}`));
  }
}

function analizzaPagina(s, url, r) {
  const h = r.corpo;
  const head = (/<head\b[\s\S]*?<\/head>/i.exec(h) || [''])[0];
  // commenti HTML esclusi: un <h1> o un canonical commentati non esistono per Google (es. convegni AA, 30/10)
  const hv = h.replace(/<!--[\s\S]*?-->/g, '');
  const can = [...hv.matchAll(/<link\b[^>]*rel=["']canonical["'][^>]*>/gi)].map(m => entita((/href=["']([^"']+)["']/i.exec(m[0]) || [])[1] || ''));
  const h1 = [...hv.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map(m => testo(m[1]));
  const robots = (/<meta\b[^>]*name=["']robots["'][^>]*content=["']([^"']*)["']/i.exec(head) || [])[1] || '';
  const ld = [...h.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)].map(m => m[1]);
  const locali = [...h.matchAll(/<(?:link|script)\b[^>]*(?:href|src)="([^"]+\.(?:css|js)(?:\?[^"]*)?)"/gi)]
    .map(m => assoluto(m[1], url)).filter(u => u && u.startsWith(s.base));
  const senzaV = locali.filter(u => !/[?&]v=\d+/.test(u));
  let grafi = [], jsonKo = 0;
  for (const b of ld) { try { const j = JSON.parse(b); grafi.push(j); } catch { jsonKo++; } }
  const crumbs = (() => {
    const ol = /<ol\b[^>]*class="[^"]*breadcrumb[^"]*"[^>]*>([\s\S]*?)<\/ol>/i.exec(h);
    return ol ? [...ol[1].matchAll(/<li\b[^>]*>([\s\S]*?)<\/li>/gi)].map(m => testo(m[1])) : null;
  })();
  return { url, status: r.status, location: r.location, can, h1, robots, ld: ld.length, jsonKo, grafi, senzaV, locali: locali.length, crumbs };
}

async function controllaPagine(s, sm) {
  const elenco = sm.pagine.slice(0, LIMITE);
  const analisi = []; const html = [];
  let n = 0;
  for (const u of elenco) {
    const r = await chiedi(u);
    const a = analizzaPagina(s, u, r);
    analisi.push(a);
    if (html.length < 6 && r.status === 200) html.push({ url: u, corpo: r.corpo });
    righePagine.push([s.nome, u, a.status, a.can.length, a.can[0] || '', a.h1.length, a.h1[0] || '', a.ld, a.robots, a.senzaV.length]);
    if (++n % 25 === 0) process.stdout.write(`    ${s.nome}: ${n}/${elenco.length} pagine\r`);
  }
  console.log(`    ${s.nome}: ${n} pagine analizzate (${sm.pagine.length} nella sitemap)`);
  const ok200 = analisi.filter(a => a.status === 200);

  // igiene
  const non200 = analisi.filter(a => a.status !== 200);
  esito(s.nome, 'Igiene', 'pagine della sitemap con 200 diretto (niente redirect né errori)', non200.length === 0,
    `${non200.length} su ${analisi.length}`, [], non200.map(a => `${a.status}  ${a.url}${a.location ? ' → ' + a.location : ''}`));
  const noindex = ok200.filter(a => /noindex/i.test(a.robots));
  esito(s.nome, 'Igiene', 'nessuna pagina noindex nella sitemap', noindex.length === 0, `${noindex.length}`, [], noindex.map(a => a.url));
  const senzaV = ok200.filter(a => a.senzaV.length);
  esito(s.nome, s.cacheVoce, '?v= su tutti i CSS e JS locali delle pagine', senzaV.length === 0,
    `${senzaV.length} pagine con file senza ?v=`, [], senzaV.slice(0, 50).map(a => `${a.url}: ${[...new Set(a.senzaV)].join(', ')}`), 'ATTENZIONE');

  // canonical (voce AA-7 su AA, informativa su KOS)
  const voceCan = s === SITI.aa ? 'AA-7' : 'Igiene';
  const canKo = [], canDiverso = [], conBarra = [];
  for (const a of ok200) {
    if (a.can.length !== 1) { canKo.push(`${a.url}: ${a.can.length} canonical`); continue; }
    const c = a.can[0];
    if (!c.startsWith(s.base + '/') && c !== s.base) { canKo.push(`${a.url}: canonical non assoluto o non nella forma del sito (${c})`); continue; }
    if (/^https:\/\/www\./.test(c)) canKo.push(`${a.url}: forma non canonica (${c})`);
    else if (normUrl(c) !== normUrl(a.url)) canDiverso.push(`${a.url} → canonical ${c}`);
    else if (c !== s.base + '/' && /\/$/.test(c)) conBarra.push(c);
  }
  // contenitori con la barra finale (es. /news/): la forma senza barra deve rispondere 301 verso il canonical
  const barraKo = [];
  for (const c of conBarra) {
    const r = await chiedi(c.replace(/\/$/, ''));
    const dest = r.location ? assoluto(r.location, c) : '';
    if (!([301, 308].includes(r.status) && dest === c)) barraKo.push(`${c.replace(/\/$/, '')} → ${r.status} ${dest}`);
  }
  if (conBarra.length) esito(s.nome, voceCan, `pagine con la barra finale (${conBarra.length}): la forma senza barra risponde 301 verso il canonical`,
    barraKo.length === 0, barraKo.length ? `${barraKo.length} duplicati` : conBarra.join(', '), [], barraKo);
  esito(s.nome, voceCan, 'un solo canonical per pagina, assoluto, https, senza www', canKo.length === 0,
    `${canKo.length} pagine non conformi su ${ok200.length}`, [], canKo);
  esito(s.nome, voceCan, 'canonical uguale all\'URL della sitemap', canDiverso.length === 0, `${canDiverso.length} pagine`, [], canDiverso, 'ATTENZIONE');

  // H1 (voce AA-18 su AA, informativa su KOS)
  const voceH1 = s === SITI.aa ? 'AA-18' : 'Igiene';
  const zero = ok200.filter(a => a.h1.length === 0), piu = ok200.filter(a => a.h1.length > 1);
  esito(s.nome, voceH1, 'almeno un H1 per pagina', zero.length === 0, `${zero.length} pagine senza H1`, [], zero.map(a => a.url));
  esito(s.nome, voceH1, 'non più di un H1 per pagina', piu.length === 0, `${piu.length} pagine`, [], piu.map(a => `${a.url}: ${a.h1.join(' | ')}`), 'ATTENZIONE');

  // dato strutturato
  if (s === SITI.aa) controllaDatoAA(s, ok200);
  else {
    const dist = ok200.reduce((m, a) => (m[a.ld] = (m[a.ld] || 0) + 1, m), {});
    const json = ok200.filter(a => a.jsonKo);
    esito(s.nome, 'Igiene', 'blocchi JSON-LD per pagina nell\'HTML servito (informativo)', null,
      Object.entries(dist).map(([k, v]) => `${k} blocchi: ${v} pagine`).join(', '), [],
      ok200.filter(a => a.ld !== 1).slice(0, 100).map(a => `${a.ld}  ${a.url}`));
    esito(s.nome, 'Igiene', 'JSON-LD sintatticamente valido', json.length === 0, `${json.length} pagine`, [], json.map(a => a.url));
  }
  return html;
}

function nodi(grafi) {
  return grafi.flatMap(g => Array.isArray(g) ? g : g['@graph'] ? g['@graph'] : [g]);
}
function controllaDatoAA(s, ok200) {
  const unoKo = ok200.filter(a => a.ld !== 1 || a.jsonKo || !a.grafi[0]?.['@graph']);
  esito(s.nome, 'AA-8', 'un solo blocco JSON-LD con @graph, JSON valido', unoKo.length === 0, `${unoKo.length} pagine non conformi su ${ok200.length}`, [],
    unoKo.map(a => `${a.url}: ${a.ld} blocchi${a.jsonKo ? ', JSON non valido' : ''}${a.grafi[0] && !a.grafi[0]['@graph'] ? ', senza @graph' : ''}`));

  const home = ok200.find(a => normUrl(a.url) === normUrl(s.base + '/'));
  if (home) {
    const org = nodi(home.grafi).find(n => n['@id'] === s.org);
    const ev = org ? [JSON.stringify(org, null, 1).slice(0, 1500)] : [];
    const campi = org ? ['name', 'legalName', 'vatID', 'logo', 'url', 'telephone'].filter(k => !org[k]) : ['nodo assente'];
    esito(s.nome, 'AA-8', `ente ${s.org} sulla home con name, legalName, vatID, logo, url, telephone`, campi.length === 0, campi.length ? `mancano: ${campi.join(', ')}` : org.legalName, ev);
    if (org) {
      const padre = [].concat(org.parentOrganization || []).some(p => p['@id'] === s.orgPadre);
      const sameAsKos = [].concat(org.sameAs || []).filter(x => /kosgroup\.com/.test(x));
      esito(s.nome, 'AA-8', 'parentOrganization verso KOS S.p.A., nessun sameAs verso kosgroup.com', padre && !sameAsKos.length,
        `${padre ? 'parentOrganization ok' : 'parentOrganization assente'}${sameAsKos.length ? ', sameAs: ' + sameAsKos.join(' ') : ''}`);
    }
  } else esito(s.nome, 'AA-8', 'home presente nella sitemap', false, 'home non trovata tra le pagine analizzate');

  const senzaRif = ok200.filter(a => !JSON.stringify(a.grafi).includes(s.org));
  esito(s.nome, 'AA-8', 'ogni pagina collega l\'ente (#organization)', senzaRif.length === 0, `${senzaRif.length} pagine`, [], senzaRif.map(a => a.url));

  const brKo = [], brDiverse = [];
  for (const a of ok200) {
    const isHome = normUrl(a.url) === normUrl(s.base + '/');
    const can = a.can[0] || a.url;
    const br = nodi(a.grafi).find(n => n['@type'] === 'BreadcrumbList');
    if (isHome) { if (br) brKo.push(`${a.url}: BreadcrumbList sulla home`); continue; }
    if (!br) { if ((a.crumbs || []).length >= 2) brKo.push(`${a.url}: BreadcrumbList assente (briciole visibili: ${a.crumbs.join(' › ')})`); continue; }
    if (br['@id'] !== senzaFrammento(can) + '#breadcrumb') brKo.push(`${a.url}: @id ${br['@id']} (atteso ${senzaFrammento(can)}#breadcrumb)`);
    const voci = [].concat(br.itemListElement || []).sort((x, y) => x.position - y.position);
    if (voci.some((v, i) => v.position !== i + 1)) brKo.push(`${a.url}: posizioni non consecutive`);
    const ultima = voci.at(-1); const idUltima = typeof ultima?.item === 'string' ? ultima.item : ultima?.item?.['@id'];
    if (idUltima && normUrl(idUltima) !== normUrl(can)) brKo.push(`${a.url}: ultimo livello ${idUltima} diverso dal canonical`);
    const nomi = voci.map(v => entita(v.name ?? v.item?.name ?? '').replace(/\s+/g, ' ').trim());
    if (a.crumbs === null) brDiverse.push(`${a.url}: briciole visibili assenti (JSON-LD: ${nomi.join(' › ')})`);
    else if (nomi.join(' › ') !== a.crumbs.join(' › ')) brDiverse.push(`${a.url}\n      visibili: ${a.crumbs.join(' › ')}\n      JSON-LD:  ${nomi.join(' › ')}`);
  }
  esito(s.nome, 'AA-8', 'BreadcrumbList su tutte le pagine tranne la home, @id {URL}#breadcrumb, ultimo livello = pagina', brKo.length === 0,
    `${brKo.length} problemi su ${ok200.length} pagine`, [], brKo);
  esito(s.nome, 'AA-8', 'briciole JSON-LD uguali a quelle visibili', brDiverse.length === 0, `${brDiverse.length} pagine diverse`, [], brDiverse);
}

async function controllaCanonicalJs(s, pagineHtml) {
  const js = new Set();
  for (const { url, corpo } of pagineHtml) for (const m of corpo.matchAll(/<script\b[^>]*src="([^"]+)"/gi)) {
    const u = assoluto(m[1], url); if (u && u.startsWith(s.base)) js.add(u);
  }
  const trovati = [];
  for (const u of js) { const r = await chiedi(u); if (/canonical/i.test(r.corpo)) trovati.push(u); }
  const inline = pagineHtml.filter(p => /<script\b(?![^>]*\bsrc=)[^>]*>[^<]*canonical/i.test(p.corpo)).map(p => p.url);
  esito(s.nome, 'AA-7', `nessuno script locale crea canonical lato client (${js.size} file JS e script inline)`, !trovati.length && !inline.length,
    [...trovati, ...inline].join(', '), [], [...trovati.map(u => `file: ${u}`), ...inline.map(u => `inline: ${u}`)], 'ATTENZIONE');
}

/* ---------- DOM renderizzato (come Google e il Rich Results Test, dopo JavaScript) ---------- */
async function controllaRenderizzato(s, pagine) {
  let chromium;
  try { ({ chromium } = await import('playwright')); }
  catch { try { ({ chromium } = (await import('node:module')).createRequire(import.meta.url)('playwright')); } catch {} }
  if (!chromium) { esito(s.nome, 'Rendering', 'DOM renderizzato', null, 'Playwright non disponibile: lanciare dalla cartella scripts/collaudo'); return;
  }
  let browser;
  try { browser = await chromium.launch(); }
  catch (e) { esito(s.nome, 'Rendering', 'DOM renderizzato', null, `browser non avviabile: ${String(e.message || e).split('\n')[0]}`); return; }
  const ctx = await browser.newContext({ userAgent: UA_BROWSER, viewport: { width: 1366, height: 900 } });
  const elenco = []; let ko = 0;
  for (const u of pagine) {
    const pg = await ctx.newPage();
    try {
      await pg.goto(u, { waitUntil: 'networkidle', timeout: 60000 });
      await pg.waitForTimeout(4000);
      const r = await pg.evaluate(() => ({
        can: [...document.querySelectorAll('link[rel="canonical"]')].map(l => l.href),
        ld: [...document.querySelectorAll('script[type="application/ld+json"]')].length,
        h1: [...document.querySelectorAll('h1')].map(h => h.textContent.trim().replace(/\s+/g, ' ')).filter(Boolean),
        robots: (document.querySelector('meta[name="robots"]') || {}).content || '',
      }));
      const ok = r.can.length === 1 && r.ld === 1 && r.h1.length === 1 && !/noindex/i.test(r.robots);
      if (!ok) ko++;
      elenco.push(`${ok ? 'OK' : 'KO'}  ${u}  canonical ${r.can.length}  JSON-LD ${r.ld}  H1 ${r.h1.length}${r.robots ? '  robots ' + r.robots : ''}${r.can.length > 1 ? '\n      ' + r.can.join(' | ') : ''}`);
    } catch (e) { ko++; elenco.push(`KO  ${u}  ${String(e.message || e).split('\n')[0]}`); }
    await pg.close();
    await attendi(PAUSA);
  }
  await browser.close();
  esito(s.nome, s === SITI.aa ? 'AA-7 / AA-8' : 'Rendering', `DOM renderizzato (dopo JavaScript) su ${pagine.length} pagine campione: 1 canonical, 1 JSON-LD, 1 H1`,
    ko === 0, ko ? `${ko} pagine` : '', [], elenco);
}
function campione(s, sm) {
  // una pagina per forma di URL (primo livello del percorso) più le pagine dell'audit, al massimo 25
  const fissi = s === SITI.aa
    ? ['/', '/news/', '/residenza-anni-azzurri-vico-mercati', '/residenza-anni-azzurri-navigli', '/rsa-in-lombardia', '/rsa-a-milano-e-provincia',
       '/come-vivono-gli-anziani-in-rsa', '/sportello-sociale-orientamento-per-assistenza-anziani', '/galleria-fotografica-convegno-anni-azzurri-2025',
       '/cure-e-assistenza-domiciliare', '/residenze', '/il-mondo-anni-azzurri']
    : ['/it', '/en', '/it/centri/casa-cima', '/it/centri/villa-rosa', '/it/chi-siamo/qualita-delle-cure/medici/stefano-albanelli',
       '/it/servizi-e-cure/riabilitazione/riabilitazione-mielolesioni', '/it/news-e-media/approfondimenti/come-bere-piu-acqua-durante-la-giornata',
       '/it/lavorare-in-kos/posizioni-aperte', '/it/strutture', '/it/prenota-online'];
  const scelti = new Set(fissi.map(p => s.base + p));
  const perForma = new Map();
  for (const u of sm.pagine) { const k = new URL(u).pathname.split('/').slice(0, 3).join('/'); if (!perForma.has(k)) perForma.set(k, u); }
  for (const u of perForma.values()) { if (scelti.size >= 25) break; scelti.add(u); }
  return [...scelti];
}

/* ---------- rapporto ---------- */
function salva() {
  fs.writeFileSync(path.join(USCITA, 'risultati.json'), JSON.stringify({ data: ora.toISOString(), richieste, esiti }, null, 1));
  const csv = [['sito', 'url', 'stato', 'n canonical', 'canonical', 'n h1', 'h1', 'n json-ld', 'robots', 'css/js senza ?v=']]
    .concat(righePagine).map(r => r.map(x => `"${String(x).replace(/"/g, '""')}"`).join(';')).join('\n');
  fs.writeFileSync(path.join(USCITA, 'pagine.csv'), '﻿' + csv);
  const e = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const col = { OK: '#d9f2d9', KO: '#f8d0d0', ATTENZIONE: '#fff2cc', INFO: '#e8eef7' };
  const cont = esiti.reduce((m, x) => (m[x.esito] = (m[x.esito] || 0) + 1, m), {});
  let h = `<!doctype html><meta charset="utf-8"><title>Verifica sprint 1</title><style>body{font:14px system-ui,sans-serif;margin:16px;max-width:1300px}
table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:4px 6px;vertical-align:top;text-align:left}th{background:#1f4e79;color:#fff}
details{margin:2px 0}pre{white-space:pre-wrap;font-size:12px;margin:4px 0;max-height:400px;overflow:auto;background:#f6f6f6;padding:6px}</style>
<h1>Verifica dello sprint 1 in produzione</h1><p>${ora.toLocaleString('it-IT')} · ${richieste} richieste · ${Object.entries(cont).map(([k, v]) => `${k} ${v}`).join(' · ')}</p>
<table><tr><th>Sito</th><th>Voce</th><th>Controllo</th><th>Esito</th><th>Dettaglio ed evidenza</th></tr>`;
  for (const x of esiti) {
    const det = (x.evidenza.length || x.elenco.length) ? `<details><summary>${e(x.dettaglio || 'dettaglio')}</summary><pre>${e([...x.evidenza, ...x.elenco].join('\n'))}</pre></details>` : e(x.dettaglio);
    h += `<tr><td>${e(x.sito)}</td><td>${e(x.voce)}</td><td>${e(x.titolo)}</td><td style="background:${col[x.esito]}">${x.esito}</td><td>${det}</td></tr>`;
  }
  fs.writeFileSync(path.join(USCITA, 'rapporto.html'), h + '</table>');
}

/* ---------- esecuzione ---------- */
const scelti = SITI_SCELTI === 'entrambi' ? ['kos', 'aa'] : [SITI_SCELTI];
for (const k of scelti) {
  const s = SITI[k];
  console.log(`\n== ${s.nome}`);
  const robots = await controllaRobots(s);
  const sm = await sitemap(s.base, robots.corpo || '');
  if (sm.problemi.length) esito(s.nome, 'Igiene', 'sitemap leggibili', false, sm.problemi.join('; '));
  if (k === 'kos') { await controllaLlms(s); await controllaDomini(s); }
  else {
    const r = await chiedi(s.base + '/llms.txt');
    esito(s.nome, 'Igiene', 'llms.txt presente, text/plain UTF-8 (non è voce AA)', r.status === 200 && /utf-8/i.test(r.headers.get('content-type') || ''), `${r.status} ${r.headers.get('content-type') || ''}`);
  }
  const html = await controllaPagine(s, sm);
  await controllaCache(s, html);
  if (k === 'aa') await controllaCanonicalJs(s, html);
  await controllaBot(s);
  if (!opz['senza-rendering']) await controllaRenderizzato(s, campione(s, sm));
  salva();
}
salva();
const cont = esiti.reduce((m, x) => (m[x.esito] = (m[x.esito] || 0) + 1, m), {});
console.log(`\n== Fine: ${Object.entries(cont).map(([k, v]) => `${k} ${v}`).join(', ')} — rapporto in ${path.resolve(USCITA, 'rapporto.html')}`);
