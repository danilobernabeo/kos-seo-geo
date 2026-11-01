#!/usr/bin/env node
/**
 * Confronto di due misure di misura-lcp.mjs (es. produzione prima / stage dopo KOS-2 B). SOLA LETTURA.
 *   node confronta-lcp.mjs lcp-prod\risultati.json lcp-stage\risultati.json [--soglia=10]
 * Per pagina e profilo: LCP prima → dopo, differenza, elemento LCP, immagini fuori schermo senza lazy, KB di immagini.
 * PEGGIORATA se l'LCP cresce più della soglia percentuale (default 10%) e di almeno 300 ms (rumore di una misura).
 * Codice di uscita 1 se almeno una pagina è peggiorata.
 */
import fs from 'node:fs';

const [fa, fb] = process.argv.slice(2).filter(a => !a.startsWith('--'));
const soglia = Number((process.argv.find(a => a.startsWith('--soglia=')) || '--soglia=10').split('=')[1]);
if (!fa || !fb) { console.error('Uso: node confronta-lcp.mjs prima/risultati.json dopo/risultati.json [--soglia=10]'); process.exit(1); }
const leggi = f => JSON.parse(fs.readFileSync(f, 'utf8'));
const A = leggi(fa), B = leggi(fb);
const chiave = r => r.profilo + ' ' + r.pagina;
const mappa = new Map(A.risultati.map(r => [chiave(r), r]));
const mediana = a => { const v = a.filter(x => x != null).sort((x, y) => x - y); return v.length ? v[Math.floor((v.length - 1) / 2)] : null; };
let peggiorate = 0;
const righe = [];
for (const b of B.risultati) {
  const a = mappa.get(chiave(b));
  if (!a || a.lcp == null || b.lcp == null) continue;
  const d = b.lcp - a.lcp, pct = Math.round(d / a.lcp * 100);
  const peggio = d > 300 && pct > soglia;
  if (peggio) peggiorate++;
  const el = r => r.lcpElemento ? `${r.lcpElemento.tipo}${r.lcpElemento.loading ? ' lazy!' : ''}` : '-';
  righe.push({ peggio, testo: `${peggio ? 'PEGGIORATA' : '          '}  ${b.profilo.padEnd(7)} ${String(a.lcp).padStart(6)} → ${String(b.lcp).padStart(6)} ms (${pct >= 0 ? '+' : ''}${pct}%)  ` +
    `fuori schermo senza lazy ${a.fuoriSchermoSenzaLazy} → ${b.fuoriSchermoSenzaLazy}  immagini ${Math.round(a.immaginiByte / 1024)} → ${Math.round(b.immaginiByte / 1024)} KB  ` +
    `LCP ${el(a)} → ${el(b)}  ${b.pagina}` });
}
righe.sort((x, y) => (y.peggio - x.peggio));
for (const r of righe) console.log(r.testo);
for (const p of [...new Set(B.risultati.map(r => r.profilo))]) {
  const aa = A.risultati.filter(r => r.profilo === p), bb = B.risultati.filter(r => r.profilo === p);
  const somma = (rr, k) => rr.reduce((s, r) => s + (r[k] || 0), 0);
  console.log(`\n${p}: LCP mediano ${mediana(aa.map(r => r.lcp))} → ${mediana(bb.map(r => r.lcp))} ms; fuori schermo senza lazy ${somma(aa, 'fuoriSchermoSenzaLazy')} → ${somma(bb, 'fuoriSchermoSenzaLazy')};` +
    ` immagini ${(somma(aa, 'immaginiByte') / 1048576).toFixed(1)} → ${(somma(bb, 'immaginiByte') / 1048576).toFixed(1)} MB`);
}
console.log(`\nPagine peggiorate (oltre ${soglia}% e 300 ms): ${peggiorate}`);
process.exit(peggiorate ? 1 : 0);
