#!/usr/bin/env python3
"""
KOS-8 / AA-3 — Piano di deindicizzazione dei PDF esclusi (noindex dal 1/11/2026).

Applica ai percorsi dei PDF la stessa logica della sezione delle regole web (<sito>/files/web-rules/pdf-noindex.conf:
noindex di default, eccezioni per nome file e per elenco, noindex espliciti, redirect esatti, canonical) e la incrocia con
lo storico di Search Console (impressioni e clic per URL), così da sapere quali PDF esclusi sono ancora visibili su Google.

Ingressi
  --gsc <file.json|csv>       righe sito, url, imp, clic, imp_90, ultima (risultato della query in BigQuery; vedi sotto)
  --inventario-<sito> <file>  facoltativo: elenco dei PDF presenti sul server (un percorso per riga, relativo alla webroot,
                              es. ./assets/doc/x.pdf), da `find . -iname '*.pdf'` in /www; senza, si lavora sui soli URL di Search Console
Uscite (in --uscita)
  deindicizzare-<sito>.csv    tutti i PDF con lo stato (noindex / indicizzabile / canonical / redirect / curriculum), presenza
                              sul server se nota, impressioni, clic, priorità
  sitemap-pdf-noindex.xml     per sito, in <uscita>/<sito>/: i PDF in noindex presenti sul server (o, senza inventario, visti in
                              Search Console), da inviare in Search Console e Bing per far rileggere presto l'intestazione
Priorità: A = impressioni negli ultimi 90 giorni ≥ 100 o clic ≥ 10 (candidati allo strumento Rimozioni);
          B = visti in Search Console; C = mai visti (si lasciano alla scansione normale, ma vanno comunque in sitemap).

Query in BigQuery (progetto kos-analytics-482819), stessa forma per searchconsole_kos:
  SELECT 'aa' sito, url, SUM(impressions) imp, SUM(clicks) clic,
         SUM(IF(data_date >= DATE_SUB(<ultima data>, INTERVAL 90 DAY), impressions, 0)) imp_90, MAX(data_date) ultima
  FROM `kos-analytics-482819.searchconsole_aa.storico_url_impression` WHERE LOWER(url) LIKE '%.pdf%' GROUP BY url
"""
import argparse, csv, datetime, json, os, re, sys, urllib.parse
from xml.sax.saxutils import escape

RADICE = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))
SITI = {'aa': ('anniazzurri', 'https://anniazzurri.it'), 'kos': ('kosgroup', 'https://kosgroup.com')}


def leggi_regole(sito):
    """Regole della sezione PDF, nell'ordine in cui nginx le valuta dentro la location dei PDF."""
    testo = open(os.path.join(RADICE, SITI[sito][0], 'files', 'web-rules', 'pdf-noindex.conf'), encoding='utf-8').read()
    redirect = {m.group(1): m.group(2) for m in re.finditer(r'^location = "([^"]+)" \{ return 301 "([^"]+)"', testo, re.M)}
    # redirect dei PDF spostati (file a parte, solo AA)
    altro = os.path.join(RADICE, SITI[sito][0], 'files', 'web-rules', 'aa-3-pdf-redirect.conf')
    if sito == 'aa' and os.path.exists(altro):
        for m in re.finditer(r'location = "([^"]+)" \{\s*return 301 (\S+);', open(altro, encoding='utf-8').read()):
            redirect.setdefault(m.group(1), urllib.parse.unquote(m.group(2)))
    # redirect delle regole di produzione (ultima istantanea <sito>/files/web-rules/produzione-*.conf): location esatte e
    # prefissi ^~ con return 301, scritti anche su più righe (es. cartella di Beata Corte verso Corte Orero)
    cart = os.path.join(RADICE, SITI[sito][0], 'files', 'web-rules')
    prod = sorted(x for x in os.listdir(cart) if x.startswith('produzione-') and x.endswith('.conf'))
    prefissi = {}
    if prod:
        tp = open(os.path.join(cart, prod[-1]), encoding='utf-8').read()
        for m in re.finditer(r'location\s+(=|\^~)\s+"?([^"\s{]+)"?\s*\{\s*return\s+30[18]\s+"?([^";\s]+)"?\s*;', tp):
            op, p, dest = m.groups()
            if op == '=':
                redirect.setdefault(p, dest)
            else:
                prefissi[p] = dest
    redirect['__prefissi__'] = prefissi
    regole = []
    for m in re.finditer(r'if \(\$uri (=|~\*|~) "([^"]+)"\) \{ set \$pdf_(robots|link) "((?:[^"\\]|\\.)*)"; \}', testo):
        op, val, cosa, valore = m.groups()
        regole.append((op, val, cosa, valore))
    return redirect, regole


def stato(percorso, redirect, regole):
    if percorso.startswith('/assets/doc/cv/'):
        return 'curriculum', ''
    if percorso in redirect:
        return 'redirect', redirect[percorso]
    for pref, dest in redirect.get('__prefissi__', {}).items():
        if percorso.startswith(pref):
            return 'redirect', dest
    robots, link = 'noindex', ''
    for op, val, cosa, valore in regole:
        if op == '=':
            ok = percorso == val
        else:
            ok = re.search(val, percorso, re.I if op == '~*' else 0) is not None
        if ok:
            if cosa == 'robots':
                robots = valore
            else:
                link = valore
    if robots == 'noindex':
        return 'noindex', ''
    if link:
        return 'canonical', re.sub(r'^<([^>]+)>.*$', r'\1', link)
    return 'indicizzabile', ''


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--gsc', required=True)
    ap.add_argument('--inventario-aa')
    ap.add_argument('--inventario-kos')
    ap.add_argument('--uscita', default='deindicizza-pdf')
    a = ap.parse_args()

    # Search Console: JSON del risultato di BigQuery (rows/f/v) oppure CSV con intestazione
    righe = []
    if a.gsc.endswith('.json'):
        d = json.load(open(a.gsc, encoding='utf-8'))
        for r in d['rows']:
            v = [c['v'] for c in r['f']]
            righe.append(dict(sito=v[0], url=v[1], imp=int(v[2]), clic=int(v[3]), imp_90=int(v[4]), ultima=v[5]))
    else:
        for r in csv.DictReader(open(a.gsc, encoding='utf-8-sig')):
            righe.append(dict(sito=r['sito'], url=r['url'], imp=int(r['imp']), clic=int(r['clic']), imp_90=int(r['imp_90']), ultima=r['ultima']))

    os.makedirs(a.uscita, exist_ok=True)
    riepilogo = {}
    for sito, (cartella, base) in SITI.items():
        redirect, regole = leggi_regole(sito)
        gsc = {}
        for r in righe:
            if r['sito'] != sito:
                continue
            u = urllib.parse.urlsplit(r['url'])
            p = urllib.parse.unquote(u.path)
            g = gsc.setdefault(p, dict(imp=0, clic=0, imp_90=0, ultima='', varianti=set()))
            g['imp'] += r['imp']; g['clic'] += r['clic']; g['imp_90'] += r['imp_90']
            g['ultima'] = max(g['ultima'], r['ultima']); g['varianti'].add(u.netloc)
        inv_file = getattr(a, f'inventario_{sito}')
        inventario = None
        if inv_file:
            inventario = set()
            for riga in open(inv_file, encoding='utf-8', errors='replace'):
                p = riga.strip().split('\t')[0]
                if p.startswith('/www/'):
                    p = p[4:]
                elif p.startswith('./'):
                    p = p[1:]
                if p.lower().endswith('.pdf'):
                    inventario.add('/' + p.lstrip('/'))
        percorsi = set(gsc) | (inventario or set())
        out = []
        for p in sorted(percorsi):
            st, dest = stato(p, redirect, regole)
            g = gsc.get(p, dict(imp=0, clic=0, imp_90=0, ultima='', varianti=set()))
            presente = '' if inventario is None else ('sì' if p in inventario else 'no')
            if st == 'noindex':
                prio = 'A' if (g['imp_90'] >= 100 or g['clic'] >= 10) else ('B' if g['imp'] else 'C')
            else:
                prio = ''
            out.append(dict(stato=st, priorita=prio, percorso=p, url=base + urllib.parse.quote(p), presente=presente,
                            impressioni=g['imp'], clic=g['clic'], impressioni_90gg=g['imp_90'], ultima_impressione=g['ultima'],
                            destinazione=dest, host_visti=' '.join(sorted(g['varianti']))))
        prio_ord = {'A': 0, 'B': 1, 'C': 2, '': 3}
        out.sort(key=lambda r: (r['stato'] != 'noindex', prio_ord[r['priorita']], -r['impressioni_90gg'], -r['impressioni']))
        with open(os.path.join(a.uscita, f'deindicizzare-{sito}.csv'), 'w', newline='', encoding='utf-8-sig') as f:
            w = csv.DictWriter(f, fieldnames=list(out[0].keys()), delimiter=';')
            w.writeheader(); w.writerows(out)
        # sitemap: solo PDF in noindex presenti sul server (senza inventario: quelli visti in Search Console)
        in_sitemap = [r for r in out if r['stato'] == 'noindex' and (r['presente'] == 'sì' if inventario is not None else r['impressioni'] > 0)]
        os.makedirs(os.path.join(a.uscita, sito), exist_ok=True)
        oggi = datetime.date.today().isoformat()
        with open(os.path.join(a.uscita, sito, 'sitemap-pdf-noindex.xml'), 'w', encoding='utf-8') as f:
            f.write('<?xml version="1.0" encoding="UTF-8"?>\n<!-- KOS-8 / AA-3: PDF esclusi dall\'indice (X-Robots-Tag: noindex), '
                    'elencati solo per farli riscansionare. Da togliere a deindicizzazione completata. -->\n'
                    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n')
            for r in in_sitemap:
                f.write(f'  <url><loc>{escape(r["url"])}</loc><lastmod>{oggi}</lastmod></url>\n')
            f.write('</urlset>\n')
        conta = {}
        for r in out:
            k = r['stato'] + (f' {r["priorita"]}' if r['priorita'] else '')
            c = conta.setdefault(k, [0, 0, 0])
            c[0] += 1; c[1] += r['impressioni_90gg']; c[2] += r['clic']
        riepilogo[sito] = dict(conta=conta, sitemap=len(in_sitemap), inventario=inventario is not None)
    for sito, r in riepilogo.items():
        print(f'== {sito}: sitemap {r["sitemap"]} URL ({"da inventario" if r["inventario"] else "solo URL di Search Console"})')
        for k in sorted(r['conta']):
            n, i90, cl = r['conta'][k]
            print(f'   {k:18} {n:5} PDF   impressioni 90 gg {i90:8}   clic storici {cl:6}')


if __name__ == '__main__':
    main()
