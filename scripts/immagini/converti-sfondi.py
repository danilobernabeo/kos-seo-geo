#!/usr/bin/env python3
"""
AA-1 immagini di sfondo → <img>: genera in anniazzurri/elements/ le versioni convertite dei chunk e dei template
della baseline che caricano immagini di contenuto con style="background-image: url(...)".

Uso (dalla radice del repository):
    python3 scripts/immagini/converti-sfondi.py anniazzurri            # scrive gli elementi e stampa il resoconto
    python3 scripts/immagini/converti-sfondi.py anniazzurri --prova    # solo resoconto

Per ogni elemento con lo sfondo nell'attributo style:
  - toglie la dichiarazione background(-image): url(...) dallo style (e lo style se resta vuoto);
  - aggiunge la classe position-relative se manca (l'immagine si posiziona dentro l'elemento);
  - inserisce come primo figlio [[immagine? &src=`...` &alt=`...` &class=`kos-sfondo` &sizes=`...`]]
    (CSS .kos-sfondo: posizione assoluta, object-fit cover, stesso aspetto dello sfondo).
Nelle gallerie (chunk gal-*) anche le <img> del carosello mobile passano allo snippet, con alt.
Testi alternativi: titolo della pagina per testate e gallerie, nome della residenza per le schede negli elenchi
(placeholder Wayfinder/pdo o testo della scheda), nome ricavato dal file come ultima risorsa.
Esclusi: sfondi nei blocchi <style> (BaseTemplate), percorsi relativi '../' (chunk di esempio), immagini decorative.
"""
import os
import re
import sys

SITO = sys.argv[1] if len(sys.argv) > 1 else 'anniazzurri'
PROVA = '--prova' in sys.argv
BASE = os.path.join(SITO, 'baseline')
OUT = os.path.join(SITO, 'elements')

TAG = re.compile(r'<(div|span|section|a|figure)\b([^>]*?)style="([^"]*?background(?:-image)?\s*:\s*url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)[^"]*)"([^>]*)>', re.I)
DECL = re.compile(r'background(?:-image)?\s*:\s*url\([^)]*\)[^;"]*;?\s*', re.I)
IMG = re.compile(r'<img\b([^>]*?)\bsrc="([^"]+)"([^>]*?)/?>', re.I)


def nome_da_file(url):
    n = os.path.splitext(os.path.basename(url))[0]
    n = re.sub(r'[-_]+', ' ', n).strip()
    n = re.sub(r'\b(v\d+|\d+|slider|slide|img|foto)\b', '', n, flags=re.I).strip()
    return ' '.join(w.capitalize() if w.islower() and len(w) > 2 else w for w in n.split())


def alt_per(nome, url, contesto_dopo, tag_attr):
    m = re.search(r'<img\b[^>]*?src="' + re.escape(url) + r'"[^>]*?\balt="([^"]+)"', contesto_dopo)   # primo alt (come il browser)
    if m:                                             # stessa immagine già presente come <img> con alt
        return m.group(1)
    if nome.startswith('heading-home'):               # slide: titolo della slide
        for m in re.finditer(r'<(h[1-3]|p)\b[^>]*>(.*?)</\1>', contesto_dopo, re.S):
            t = re.sub(r'<[^>]+>|&nbsp;|\s+', ' ', m.group(2)).strip()
            if len(t) > 3 and '[[' not in t:
                return t
    if '[[+wf.' in url:
        return '[[+wf.pagetitle]]'
    if '[[+tv.' in url or '[[+' in url:
        return '[[+pagetitle]]'
    if nome.startswith(('heading', 'header', 'news-top')) or 'heading' in tag_attr or nome.startswith('gal-'):
        return '[[*pagetitle]]'
    m = re.search(r'<span class="font-size-600">(.*?)</span>', contesto_dopo, re.S)
    if m and '[[' not in m.group(1):
        return re.sub(r'\s+', ' ', m.group(1)).strip()
    if '[[*' in url:
        return '[[*pagetitle]]'
    if re.search(r'residenza-|centro-diurno', url, re.I):   # nome del file parlante (es. terrazza-residenza-...)
        return nome_da_file(url)
    # riquadro con titolo: il titolo vicino descrive l'immagine; altrimenti immagine decorativa (alt vuoto)
    m = re.search(r'<(h[1-4])\b[^>]*>(.*?)</\1>', contesto_dopo[:600], re.S)
    if m:
        t = re.sub(r'<[^>]+>|&nbsp;|\s+', ' ', m.group(2)).strip()
        if len(t) > 3 and '[[' not in t:
            return t
    return ''


def sizes_per(classi, nome, tag_attr):
    if 'heading' in tag_attr or nome.startswith(('heading', 'header')) or 'position-relative' in classi and 'ratio' not in classi:
        return '100vw'
    if re.search(r'\bimage\b', classi):              # schede residenza negli elenchi
        return '(min-width: 768px) 35vw, 100vw'
    if re.search(r'\bdiv[123]\b', classi) or 'rounded-3' in classi:   # anteprime galleria desktop
        return '(min-width: 992px) 40vw, 100vw'
    if 'ratio' in classi:
        return '(min-width: 992px) 50vw, 100vw'
    return '100vw'


def converti(nome, testo):
    note = []
    numeri = {}                                       # galleria: stesso numero per la stessa foto (desktop e mobile)

    def numero(src):
        m = re.search(r'/(\d+)\.(jpe?g|png)$', src, re.I)
        if src not in numeri:
            numeri[src] = int(m.group(1)) if m else len(numeri) + 1
        return numeri[src]

    def sost(m):
        tag, prima, stile, url, dopo = m.group(1), m.group(2), m.group(3), m.group(4).strip(), m.group(5)
        if url.startswith('../') or url.startswith('data:'):
            note.append(f'saltato {url}')
            return m.group(0)
        attr = prima + dopo
        nuovo_stile = DECL.sub('', stile).strip()
        resto = f'{prima}style="{nuovo_stile}"{dopo}' if nuovo_stile else (prima.rstrip() + dopo)
        cm = re.search(r'class="([^"]*)"', resto)
        classi = cm.group(1) if cm else ''
        if not re.search(r'\bposition-(relative|absolute|fixed)\b', classi):
            if cm:
                resto = resto.replace(f'class="{classi}"', f'class="{classi} position-relative"', 1)
            else:
                resto = ' class="position-relative"' + resto
        dopo_testo = testo[m.end():m.end() + 900]
        if nome.startswith('gal-'):
            alt = f'[[*pagetitle]], foto {numero(url)}'
        else:
            alt = alt_per(nome, url, dopo_testo, attr)
        # priorità solo alla prima immagine della testata (le altre slide di un carosello restano lazy)
        prio = '1' if ('heading' in attr or nome.startswith(('heading', 'header'))) and not any('(priorità)' in x for x in note) else ''
        chiamata = (f'[[immagine? &src=`{url}` &alt=`{alt}` &class=`kos-sfondo` &sizes=`{sizes_per(classi, nome, attr)}`'
                    + (f' &priorita=`{prio}`' if prio else '') + ']]')
        note.append(f'sfondo {url} → alt «{alt}»' + (' (priorità)' if prio else ''))
        resto = re.sub(r'\s+$', '', resto)
        return f'<{tag}{resto if resto.startswith(" ") else " " + resto.lstrip()}>{chiamata}'

    out = TAG.sub(sost, testo)

    # Foto delle regioni salvate in PNG (schede «Lombardia», «Piemonte»...): <img> → snippet, servite in WebP
    def sost_regione(m):
        attr = (m.group(1) + m.group(3)).strip()
        src = m.group(2)
        reg = re.search(r'/assets/img/([a-z-]+?)(-m)?\.png$', src).group(1)
        nome_reg = {'emilia-romagna': 'Emilia-Romagna'}.get(reg, reg.capitalize())
        cls = re.search(r'class="([^"]*)"', attr)
        note.append(f'foto regione {src}')
        return (f'[[immagine? &src=`{src}` &alt=`RSA Anni Azzurri in {nome_reg}`'
                + (f' &class=`{cls.group(1)}`' if cls else '')
                + f" &sizes=`{'100vw' if m.group(2).endswith('-m.png') else '(min-width: 768px) 33vw, 100vw'}`]]")
    out = re.sub(r'<img\b([^>]*?)\bsrc="(/assets/img/(?:lombardia|piemonte|liguria|marche|emilia-romagna|veneto|toscana|lazio)(?:-m)?\.png)"([^>]*?)/?>',
                 sost_regione, out)
    if nome.startswith('gal-'):
        def sost_img(m):
            src = m.group(2)
            if '[[' in src and not src.startswith('/assets/'):
                return m.group(0)
            attr = (m.group(1) + m.group(3)).strip()
            cls = re.search(r'class="([^"]*)"', attr)
            note.append(f'img {src}')
            return (f'[[immagine? &src=`{src}` &alt=`[[*pagetitle]], foto {numero(src)}`'
                    + (f' &class=`{cls.group(1)}`' if cls else '') + ' &sizes=`naturale`]]')
        out = IMG.sub(sost_img, out)
    return out, note


def main():
    tot = 0
    for cartella in ('chunks', 'templates'):
        for f in sorted(os.listdir(os.path.join(BASE, cartella))):
            # si parte dalla versione del repository se l'elemento è già stato modificato dal progetto
            p = os.path.join(OUT, cartella, f)
            if not os.path.exists(p):
                p = os.path.join(BASE, cartella, f)
            testo = open(p, encoding='utf-8', newline='').read()
            regioni = re.search(r'src="/assets/img/(?:lombardia|piemonte|liguria|marche|emilia-romagna|veneto|toscana|lazio)(?:-m)?\.png"', testo)
            if not (TAG.search(testo) or regioni) or ('[[immagine?' in testo and not regioni):
                continue
            nome = os.path.splitext(f)[0]
            nuovo, note = converti(nome, testo)
            if nuovo == testo:
                continue
            tot += 1
            print(f'{cartella[:-1]} {nome}: ' + '; '.join(note[:4]) + (f' (+{len(note) - 4})' if len(note) > 4 else ''))
            if not PROVA:
                os.makedirs(os.path.join(OUT, cartella), exist_ok=True)
                with open(os.path.join(OUT, cartella, f), 'w', encoding='utf-8', newline='') as w:
                    w.write(nuovo)
    print(f'== {tot} elementi convertiti' + (' (prova, nessun file scritto)' if PROVA else f' in {OUT}/'))


if __name__ == '__main__':
    main()
