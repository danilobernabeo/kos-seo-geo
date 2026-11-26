/**
 * N4 percorsi relativi (26/11): collaudo del plugin PercorsiAssoluti in due fasi sullo stesso sito.
 *
 *   node percorsi-assoluti.mjs --base=https://stage.kosgroup.com --pagine=pagine-kos-percorsi.txt --fase=prima
 *   (accendere kosseogeo.percorsi_assoluti = 1, svuotare la cache)
 *   node percorsi-assoluti.mjs --base=https://stage.kosgroup.com --pagine=pagine-kos-percorsi.txt --fase=dopo
 *
 * «prima» salva l'HTML in percorsi-<host>/prima/; «dopo» lo scarica di nuovo e per ogni pagina controlla:
 *   - EQUIVALENTE: togliendo da entrambe le versioni la «/» davanti ai valori degli attributi URL, l'HTML è identico,
 *     cioè l'unica differenza è la barra aggiunta (che con <base href> sulla radice non cambia l'indirizzo);
 *   - relativi rimasti dopo (deve essere 0) e numero di valori «//» (deve restare uguale: nessun doppio slash nuovo).
 * L'HTML si confronta con le entità numeriche decodificate (offuscamento casuale di email e link a ogni richiesta).
 * Esito FERMO se una pagina non è equivalente o compare un «//» nuovo. La password dello stage solo in $env:AUTH.
 */
import { mkdirSync, readFileSync, writeFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';

const arg = Object.fromEntries(process.argv.slice(2).map(a => a.replace(/^--/, '').split(/=(.*)/s).slice(0, 2)));
if (!arg.base || !arg.pagine || !['prima', 'dopo'].includes(arg.fase)) {
  console.error('Uso: node percorsi-assoluti.mjs --base=https://... --pagine=elenco.txt --fase=prima|dopo'); process.exit(1);
}
const base = arg.base.replace(/\/$/, '');
const auth = process.env.AUTH
  ? 'Basic ' + Buffer.from(process.env.AUTH.includes(':') ? process.env.AUTH : 'kos:' + process.env.AUTH).toString('base64')
  : null;
const pagine = readFileSync(arg.pagine, 'utf8').replace(/^﻿/, '').split(/\r?\n/)
  .map(r => r.replace(/#.*$/, '').trim()).filter(Boolean);
const dir = `percorsi-${new URL(base).host}`;
const nome = p => (p.replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '') || 'home') + '.html';

// Il sito offusca email e alcuni link con entità numeriche scelte a caso a ogni richiesta (&#x6d; / &#109; / m):
// si confronta l'HTML con le entità numeriche decodificate, così contano solo le differenze vere.
const decodifica = h => h.replace(/&#x([0-9a-f]+);/gi, (_, x) => String.fromCodePoint(parseInt(x, 16)))
  .replace(/&#(\d+);/g, (_, d) => String.fromCodePoint(+d));
const ATTR = /(\s(?:src|href|poster|action|data-src|srcset|data-srcset)\s*=\s*)(["'])(.*?)\2/gis;
const senzaBlocchi = h => h.replace(/<!--[\s\S]*?-->|<(style|template|textarea)\b[^>]*>[\s\S]*?<\/\1\s*>|(<script\b[^>]*>)[\s\S]*?<\/script\s*>/gi,
  (m, _b, s) => s ? s + '</script>' : '');
const valori = h => [...senzaBlocchi(h).matchAll(ATTR)].flatMap(m =>
  /srcset/i.test(m[1]) ? m[3].split(/,\s*/).map(x => x.trim()) : [m[3].trim()]);
const relativo = v => /^[A-Za-z0-9_]/.test(v) && !/^[A-Za-z][A-Za-z0-9+.\-]*:/.test(v);
const normalizza = h => h.replace(ATTR, (m, a, q, v) => a + q + v.replace(/(^|,\s*)\/(?=[A-Za-z0-9_])/g, '$1') + q);

async function scarica(p) {
  const r = await fetch(base + p, { headers: { ...(auth && { Authorization: auth }), 'User-Agent': 'kos-seo-geo percorsi-assoluti' } });
  if (r.status === 401) { console.error('FERMO: 401, password dello stage in $env:AUTH?'); process.exit(1); }
  return { stato: r.status, html: await r.text() };
}

mkdirSync(join(dir, arg.fase), { recursive: true });
let fermo = 0;
for (const p of pagine) {
  const { stato, html } = await scarica(p);
  writeFileSync(join(dir, arg.fase, nome(p)), html);
  if (arg.fase === 'prima') {
    console.log(`${stato} ${p}: relativi ${valori(decodifica(html)).filter(relativo).length}`);
    continue;
  }
  const f = join(dir, 'prima', nome(p));
  if (!existsSync(f)) { console.log(`?? ${p}: manca la fase prima`); continue; }
  const prima = decodifica(readFileSync(f, 'utf8'));
  const dopo = decodifica(html);
  const vp = valori(prima), vd = valori(dopo);
  const relP = vp.filter(relativo).length, relD = vd.filter(relativo).length;
  const dsP = vp.filter(v => v.startsWith('//')).length, dsD = vd.filter(v => v.startsWith('//')).length;
  const eq = normalizza(prima) === normalizza(dopo);
  const ok = eq && dsD === dsP;
  if (!ok) fermo++;
  let nota = '';
  if (!eq) {
    const a = normalizza(prima), b = normalizza(dopo);
    let i = 0; while (i < a.length && a[i] === b[i]) i++;
    nota = `\n     prima: ${JSON.stringify(a.slice(Math.max(0, i - 60), i + 60))}\n     dopo : ${JSON.stringify(b.slice(Math.max(0, i - 60), i + 60))}`;
  }
  console.log(`${ok ? 'OK   ' : 'FERMO'} ${stato} ${p}: relativi ${relP} -> ${relD}, "//" ${dsP} -> ${dsD}, ${eq ? 'equivalente' : 'DIVERSO'}${nota}`);
}
if (arg.fase === 'dopo') console.log(fermo ? `\nFERMO: ${fermo} pagine da guardare` : '\nTutte le pagine equivalenti, nessun doppio slash nuovo.');
