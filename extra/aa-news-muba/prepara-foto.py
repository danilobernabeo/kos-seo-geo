#!/usr/bin/env python3
"""
Galleria della news Anni Azzurri e MUBA: dalle foto originali alle immagini del sito e al blocco HTML.

Uso, dalla cartella del pacchetto (serve Pillow: pip install Pillow):
    python prepara-foto.py            prepara solo le foto nuove o cambiate
    python prepara-foto.py --forza    rifà tutte le immagini

Legge foto.json e foto-sorgenti/<cartella>/ (originali, fuori da git). Per ogni foto scrive in
files/assets/news-muba/foto/:
    <slug>-1600.jpg   lato lungo 1600 px: visore e ripiego per i browser senza WebP
    <slug>-1600.webp  stessa misura in WebP
    <slug>-800.webp   lato lungo 800 px: mosaico e smartphone
    <slug>-q.webp     miniatura quadrata 160 px per la striscia del visore
Rotazione applicata, colori in sRGB, nessun metadato (le originali della fotocamera hanno la posizione GPS).
Toglie dalla cartella i file che non servono più e riscrive in elementi/contenuto.html il blocco fra
<!-- galleria:inizio --> e <!-- galleria:fine -->: mosaico e dati del visore.
"""
import io, json, re, sys, html, pathlib
from PIL import Image, ImageOps, ImageCms

DIR = pathlib.Path(__file__).resolve().parent
CONF = json.loads((DIR / 'foto.json').read_text(encoding='utf-8'))
SORG = DIR / 'foto-sorgenti'
OUT = DIR / 'files' / 'assets' / 'news-muba' / 'foto'
URL = '/assets/news-muba/foto/'
CONTENUTO = DIR / 'elementi' / 'contenuto.html'
FORZA = '--forza' in sys.argv
ESTENSIONI = {'.jpg', '.jpeg', '.png', '.webp'}
ESCLUSE = set(CONF.get('escluse', []))   # scatti quasi identici a un altro: restano in foto-sorgenti, non vanno sul sito
SRGB = ImageCms.createProfile('sRGB')


def data_scatto(p):
    try:
        ex = Image.open(p).getexif()
        return ex.get_ifd(0x8769).get(36867) or ex.get(306)
    except Exception:
        return None


def chiave_nome(p):
    # «RSA Navigli.jpg» prima di «RSA Navigli 1.jpg», poi in ordine numerico
    m = re.search(r'(\d+)\s*$', p.stem)
    return (p.stem[:m.start()].strip().lower() if m else p.stem.lower(), int(m.group(1)) if m else -1)


def apri(p):
    im = Image.open(p)
    im = ImageOps.exif_transpose(im)
    icc = im.info.get('icc_profile')
    if icc:
        try:
            sorg = ImageCms.ImageCmsProfile(io.BytesIO(icc))
            if 'srgb' not in (ImageCms.getProfileDescription(sorg) or '').lower():
                im = ImageCms.profileToProfile(im.convert('RGB'), sorg, SRGB, outputMode='RGB')
        except Exception:
            pass
    return im.convert('RGB')


def lato_lungo(im, l):
    w, h = im.size
    if max(w, h) <= l:
        return im.copy()
    r = l / max(w, h)
    return im.resize((round(w * r), round(h * r)), Image.LANCZOS)


# ---------- elenco delle foto, nell'ordine di visualizzazione ----------
foto = []
for gi, g in enumerate(CONF['gruppi']):
    file = []
    for c in g['cartelle']:
        d = SORG / c
        if not d.is_dir():
            sys.exit(f'Cartella mancante: {d}')
        file += [p for p in d.iterdir() if p.suffix.lower() in ESTENSIONI and p.name not in ESCLUSE]
    date = {p: data_scatto(p) for p in file}
    if all(date.values()):
        file.sort(key=lambda p: date[p])
    else:
        file.sort(key=chiave_nome)
    for n, p in enumerate(file, 1):
        foto.append({'p': p, 'g': gi, 'slug': f"{g['slug']}-{n:02d}"})

nomi = [f['p'].name for f in foto]
for chiave in [CONF['mosaico']['grande'], *CONF['mosaico']['iniziali'].values(), *CONF.get('fuori_mosaico', [])]:
    if chiave not in nomi:
        sys.exit(f'foto.json cita «{chiave}», che non è in foto-sorgenti/')
senza_alt = [n for n in nomi if not CONF['alt'].get(n)]
if senza_alt:
    print('ATTENZIONE, testo alternativo mancante (uso un testo generico):', ', '.join(senza_alt))

# ---------- immagini ----------
OUT.mkdir(parents=True, exist_ok=True)
attesi = set()
for f in foto:
    p, s = f['p'], f['slug']
    nomi_out = [f'{s}-1600.jpg', f'{s}-1600.webp', f'{s}-800.webp', f'{s}-q.webp']
    attesi.update(nomi_out)
    fatti = all((OUT / n).exists() and (OUT / n).stat().st_mtime >= p.stat().st_mtime for n in nomi_out)
    if fatti and not FORZA:
        with Image.open(OUT / f'{s}-1600.jpg') as g16, Image.open(OUT / f'{s}-800.webp') as g8:
            f['w'], f['h'], f['w8'] = *g16.size, g8.size[0]
        continue
    im = apri(p)
    g = lato_lungo(im, 1600)
    g.save(OUT / f'{s}-1600.jpg', 'JPEG', quality=82, optimize=True, progressive=True)
    g.save(OUT / f'{s}-1600.webp', 'WEBP', quality=80, method=6)
    m = lato_lungo(im, 800)
    m.save(OUT / f'{s}-800.webp', 'WEBP', quality=78, method=6)
    ImageOps.fit(im, (160, 160), Image.LANCZOS, centering=(0.5, 0.4)).save(OUT / f'{s}-q.webp', 'WEBP', quality=75, method=6)
    f['w'], f['h'], f['w8'] = *g.size, m.size[0]
    print(f'  {p.parent.name}/{p.name} -> {s} ({g.size[0]}x{g.size[1]})')

for vecchio in OUT.iterdir():
    if vecchio.name not in attesi:
        vecchio.unlink()
        print('  tolto', vecchio.name)

# ---------- blocco HTML ----------
e = lambda t: html.escape(str(t), quote=True)
per_nome = {f['p'].name: i for i, f in enumerate(foto)}
gruppi = CONF['gruppi']
fuori = set(CONF.get('fuori_mosaico', []))


def picture(f, sizes):
    s = f['slug']
    return (f'<picture><source type="image/webp" srcset="{URL}{s}-800.webp {f["w8"]}w, {URL}{s}-1600.webp {f["w"]}w" sizes="{sizes}">'
            f'<img src="{URL}{s}-1600.jpg" alt="" width="{f["w"]}" height="{f["h"]}" loading="lazy" decoding="async"></picture>')


TESSERE = [  # posizione, formato richiesto, sizes (misure dal layout: mosaico da 1200 px, versione ridotta sotto)
    ('grande', None, '(min-width: 1400px) 860px, (min-width: 1200px) 740px, calc(100vw - 24px)'),
    ('orizzontale-1', 'o', '(min-width: 1400px) 420px, (min-width: 1200px) 360px, 1px'),
    ('verticale-1', 'v', '(min-width: 1400px) 420px, (min-width: 1200px) 360px, calc(50vw - 20px)'),
    ('verticale-2', 'v', '(min-width: 1400px) 200px, (min-width: 1200px) 170px, calc(50vw - 20px)'),
    ('orizzontale-2', 'o', '(min-width: 1400px) 640px, (min-width: 1200px) 550px, 1px'),
]
righe = []
for pos, formato, sizes in TESSERE:
    nome = CONF['mosaico']['grande'] if pos == 'grande' else CONF['mosaico']['iniziali'][pos]
    i = per_nome[nome]; f = foto[i]; g = gruppi[f['g']]
    vert = f['h'] > f['w']
    if formato and (formato == 'v') != vert:
        sys.exit(f'«{nome}» non è {"verticale" if formato == "v" else "orizzontale"}: non va nella casella {pos}')
    alt = CONF['alt'].get(nome) or f"Laboratorio al MUBA con la {g['nome']}"
    righe.append(f'<button type="button" class="muba-tessera muba-tessera-{pos}" data-tessera="{pos}" data-indice="{i}" '
                 f'data-formato="{"v" if vert else "o"}" aria-label="{e(alt)} ({e(g["etichetta"])}): apri la galleria">{picture(f, sizes)}'
                 f'<span class="muba-tessera-nome" aria-hidden="true">{e(g["etichetta"])}</span></button>')

parole = {1: 'Una', 2: 'Due', 3: 'Tre', 4: 'Quattro', 5: 'Cinque', 6: 'Sei', 7: 'Sette'}
invito = CONF['mosaico'].get('invito') or f"{parole.get(len(gruppi), len(gruppi))} residenze, un solo laboratorio"
righe.append(f'<div class="muba-mosaico-invito"><p class="muba-mosaico-frase">{e(invito)}</p>'
             f'<button type="button" class="btn muba-btn" data-visore-apri>Guarda tutte le {len(foto)} foto</button></div>')

dati = {
    'base': URL,
    # 'c': collegamenti della didascalia, uno per struttura (un gruppo può riunire più strutture)
    'gruppi': [{'e': g['etichetta'], 'n': g['nome'], 'l': g['luogo'],
                'c': [{'n': x['nome'], 'u': x['url']} for x in g['collegamenti']]} for g in gruppi],
    'foto': [{'s': f['slug'], 'g': f['g'], 'w': f['w'], 'h': f['h'], 'w8': f['w8'],
              'a': CONF['alt'].get(f['p'].name) or f"Laboratorio al MUBA con la {gruppi[f['g']]['nome']}",
              'm': 0 if (f['p'].name in fuori or f['p'].name == CONF['mosaico']['grande']) else 1} for f in foto],
}
js = json.dumps(dati, ensure_ascii=False, separators=(',', ':')).replace('</', '<\\/')
blocco = ('<!-- galleria:inizio (scritto da prepara-foto.py: non modificare a mano, cambiare foto.json) -->\n'
          '<div class="muba-mosaico" data-muba-mosaico>\n' + '\n'.join(righe) + '\n</div>\n'
          f'<script type="application/json" id="muba-galleria-dati">{js}</script>\n'
          '<!-- galleria:fine -->')
if '[[' in blocco or ']]' in blocco:
    sys.exit('Il blocco contiene «[[» o «]]», che MODX leggerebbe come tag: cambiare i testi')

c = CONTENUTO.read_text(encoding='utf-8')
nuovo, n = re.subn(r'<!-- galleria:inizio.*?<!-- galleria:fine -->', lambda m: blocco, c, flags=re.S)
if n != 1:
    sys.exit('In contenuto.html mancano i segnaposto <!-- galleria:inizio --> ... <!-- galleria:fine -->')
if nuovo != c:
    CONTENUTO.write_text(nuovo, encoding='utf-8')
print(f'{len(foto)} foto in {len(gruppi)} gruppi; contenuto.html {"aggiornato" if nuovo != c else "invariato"}')
