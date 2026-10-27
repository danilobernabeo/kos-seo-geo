#!/usr/bin/env python3
"""
Anteprima locale della news (senza MODX): template + contenuto, con Bootstrap 5.1.3 al posto del CSS del sito
e intestazione/piè di pagina semplificati. Serve a giudicare grafica, sagome e leggibilità, non il sito intero.

Uso: python3 anteprima/costruisci.py <cartella bootstrap dist> <cartella di uscita>
"""
import re, sys, pathlib, shutil

DIR = pathlib.Path(__file__).resolve().parent.parent
bs, out = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
out.mkdir(parents=True, exist_ok=True)
(out / 'assets').mkdir(exist_ok=True)
if (out / 'assets' / 'news-muba').exists(): shutil.rmtree(out / 'assets' / 'news-muba')
shutil.copytree(DIR / 'files' / 'assets' / 'news-muba', out / 'assets' / 'news-muba')

t = (DIR / 'elementi' / 'templates' / 'interna-articolo-muba.html').read_text()
contenuto = (DIR / 'elementi' / 'contenuto.html').read_text()
campi = {
    'pagetitle': 'Creiamo insieme le forme del nostro Natale: il progetto Anni Azzurri e MUBA',
    'introtext': 'Carta, cartone e altri materiali di recupero si trasformano in decorazioni natalizie grazie alla creatività di anziani e bambini.',
}
head = f'''<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anteprima news MUBA</title>
<link rel="stylesheet" href="/bs/css/bootstrap.min.css"><style>[stripe-share] img{{width:28px;height:28px;background:#224382;border-radius:50%;font-size:0}}</style>
<style>body{{font-family:"Open Sans",system-ui,sans-serif}} .rounded-5{{border-radius:2rem!important}}
.anteprima-barra{{background:#fff;padding:1rem 0;border-bottom:1px solid #ddd;font-weight:700;color:#003d7c}}
footer.anteprima{{background:#3d1a52;color:#fff;padding:3rem 0}}</style>
<link rel="stylesheet" href="/assets/news-muba/news-muba.css"></head>'''
t = t.replace('[[$head-news]]', head)
t = t.replace('[[newsMubaRisorse]]', '')
t = re.sub(r'\[\[\$GTM_body\]\]|\[\[\$menu-sticky\]\]|\[\[\$menu-side-navbar\]\]', '', t)
t = t.replace('[[$menu-navbar]]', '<div class="anteprima-barra"><div class="container">Anni Azzurri · KOS group (intestazione del sito)</div></div>')
t = t.replace('[[$breadcrumb-stripe]]', '<div class="container pt-3 small" style="color:#1d3366">Anni Azzurri / News / Creiamo insieme le forme del nostro Natale</div>')
t = re.sub(r'\[\[newsMubaImmagine\?.*?&priorita=`1`\]\]', '<img src="/assets/news-muba/testata-prova.jpg" alt="" class="img-fluid rounded-5" width="980" height="551">', t)
t = t.replace('[[*content]]', contenuto)
# foto della galleria: in anteprima un <img> semplice, come il ripiego di newsMubaImmagine senza lo snippet immagine
t = re.sub(r'\[\[newsMubaImmagine\? &src=`([^`]+)` &alt=`([^`]*)` &class=`([^`]*)`[^\]]*\]\]',
           r'<img src="\1" alt="\2" class="\3" loading="lazy">', t)
for k, v in campi.items(): t = t.replace(f'[[*{k}]]', v)
t = re.sub(r'\[\[\*publishedon[^\]]*\]\]', '28 ottobre 2026', t)
t = t.replace('[[~8]]', '#')
t = re.sub(r'\[\[!getResources\?.*?DESC"\}`\s*\]\]', '<div class="container py-5"><h2 class="h3" style="color:#224382">Altre notizie (elenco del sito)</h2></div>', t, flags=re.S)
t = t.replace('[[$footer]]', '<footer class="anteprima"><div class="container">Piè di pagina del sito</div></footer>')
t = t.replace('[[$footer-script]]', '<script src="/bs/js/bootstrap.bundle.min.js"></script><script src="/assets/news-muba/news-muba.js"></script>')
t = t.replace('<script async src="https://widget.spreaker.com/widgets.js"></script>', '')
resti = re.findall(r'\[\[[^\]]*\]\]', t)
if resti: print('tag MODX non sostituiti:', resti)
(out / 'index.html').write_text(t)
if not (out / 'bs').exists(): (out / 'bs').symlink_to(bs)
print(out / 'index.html')
