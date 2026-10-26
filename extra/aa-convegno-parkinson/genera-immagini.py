#!/usr/bin/env python3
"""
Immagini web del convegno «La malattia di Parkinson» a partire da:
  visual-convegno.jpg                                        foto fornita (filo rosa, testa a destra)
  KOS_CONVEGNO PARKINSON_Anni Azzurri_locandinaA3_02.pdf     locandina A3 (da qui logo e titolo, in Helvetica Neue)

Come la locandina: foto specchiata (testa a sinistra) e filo virato al rosso della locandina.
Uscita in files/assets/img/ (nomi come quelli del Mantello del custode) e copia della locandina
in files/assets/doc/.

Uso:  python3 genera-immagini.py          (servono Pillow, numpy, pdftoppm)
"""
import pathlib, shutil, subprocess, tempfile
import numpy as np
from PIL import Image, ImageChops, ImageFilter

Image.MAX_IMAGE_PIXELS = None
DIR = pathlib.Path(__file__).resolve().parent
OUT = DIR / "files" / "assets" / "img"
ALIAS = "convegno-parkinson-realta-prospettive"
LOCANDINA = "KOS_CONVEGNO PARKINSON_Anni Azzurri_locandinaA3_02.pdf"
BLU = (24, 62, 122)          # #183e7a, blu del logo Anni Azzurri
ROSSO = (206, 21, 49)        # #ce1531, rosso del logo KOS


def foto_rossa():
    im = Image.open(DIR / "visual-convegno.jpg").convert("RGB")
    im = im.resize((3120, 2080), Image.LANCZOS).transpose(Image.FLIP_LEFT_RIGHT)
    hsv = np.asarray(im.convert("HSV")).astype(np.float32)
    h, s, v = hsv[..., 0], hsv[..., 1], hsv[..., 2]
    # peso del filo: 0 sullo sfondo grigio (s ~ 10), 1 sul filo (s > 90)
    w = np.clip((s - 35) / 55, 0, 1)
    gradi = h * 360 / 255
    w *= np.clip((gradi - 285) / 20, 0, 1)                  # solo i rosa-magenta (285-360°): niente aloni verdi
    s2 = np.clip(s * 1.45, 0, 255)
    v2 = v * 0.93
    # rosa (337°) -> rosso (5°) come la locandina; lo spostamento si pesa prima del modulo,
    # altrimenti la media tra 350° e 10° darebbe 180° (aloni azzurri)
    h_nuova = (h + w * 28 * 255 / 360) % 255
    out = np.stack([h_nuova, s * (1 - w) + s2 * w, v * (1 - w) + v2 * w], -1)
    return Image.fromarray(out.astype(np.uint8), "HSV").convert("RGB")


def blocco_titolo():
    """Logo + titolo dalla locandina, come RGBA (colori originali, sfondo trasparente)."""
    with tempfile.TemporaryDirectory() as t:
        subprocess.run(["pdftoppm", "-r", "600", "-f", "1", "-l", "1", "-png",
                        str(DIR / LOCANDINA), f"{t}/p"], check=True)
        pag = Image.open(next(pathlib.Path(t).glob("p*.png"))).convert("RGB")
    W, H = pag.size
    c = pag.crop((int(W * 0.53), int(H * 0.19), int(W * 0.92), int(H * 0.41)))
    a = np.asarray(c).astype(np.int32)
    r, b = a[..., 0], a[..., 2]
    # il fondo è grigio neutro (b - r < 10): l'opacità si ricava dalla tinta, che nei bordi
    # antialiasing scala con la copertura; blu del testo b - r ~ 100, rosso del logo r - b ~ 155
    a_blu = np.clip((b - r - 10) / 88, 0, 1)
    a_ros = np.clip((r - b - 10) / 140, 0, 1)
    alpha = np.maximum(a_blu, a_ros)
    col = np.where((a_ros > a_blu)[..., None], np.array(ROSSO), np.array(BLU))
    rgba = np.dstack([col, alpha * 255]).astype(np.uint8)
    img = Image.fromarray(rgba, "RGBA")
    return img.crop(img.getbbox())


def sfondo_esteso(foto, W, H, scala, x, y):
    """Tela WxH: foto scalata e posizionata, bordi fusi in un fondo grigio uniforme."""
    fw, fh = int(foto.width * scala), int(foto.height * scala)
    f = foto.resize((fw, fh), Image.LANCZOS)
    arr = np.asarray(f).astype(np.float32)
    bordo = np.concatenate([arr[:12].reshape(-1, 3), arr[-12:].reshape(-1, 3),
                            arr[:, :12].reshape(-1, 3), arr[:, -12:].reshape(-1, 3)])
    fondo = tuple(int(c) for c in np.median(bordo, axis=0))
    tela = Image.new("RGB", (W, H), fondo)
    m = Image.new("L", (fw, fh), 0)
    sf = max(8, int(min(fw, fh) * 0.12))
    m.paste(255, (sf, sf, fw - sf, fh - sf))
    m = m.filter(ImageFilter.GaussianBlur(sf / 2))
    # il filo resta sempre pieno: la sfumatura dei bordi lo schiariva dove arriva vicino al margine della foto
    sat = np.asarray(f.convert("HSV"))[..., 1]
    filo = Image.fromarray(((sat > 70) * 255).astype(np.uint8), "L").filter(ImageFilter.MaxFilter(5)).filter(ImageFilter.GaussianBlur(1.5))
    m = ImageChops.lighter(m, filo)
    tela.paste(f, (x, y), m)
    return tela


def sfumatura_blu(img, fino_a, opacita=0.88):
    """Velatura blu da sinistra (per il testo bianco HTML del carosello)."""
    W, H = img.size
    xs = np.linspace(0, 1, W, dtype=np.float32)
    a = np.clip(1 - xs / fino_a, 0, 1) ** 1.1 * opacita
    a = np.repeat(a[None, :], H, 0)[..., None]
    arr = np.asarray(img).astype(np.float32)
    arr = arr * (1 - a) + np.array(BLU, np.float32) * a
    return Image.fromarray(arr.astype(np.uint8))


def metti(img, blocco, larghezza, x, y):
    b = blocco.resize((larghezza, int(blocco.height * larghezza / blocco.width)), Image.LANCZOS)
    img.paste(b, (x, y), b)
    return b.size


def salva(img, nome, q=82):
    OUT.mkdir(parents=True, exist_ok=True)
    p = OUT / nome
    img.save(p, "JPEG", quality=q, optimize=True, progressive=True)
    print(f"{p.relative_to(DIR)}  {img.width}x{img.height}  {p.stat().st_size // 1024} KB")


foto = foto_rossa()          # 3120x2080, testa a sinistra (x ~ 0.17-0.45), filo in alto a destra
blocco = blocco_titolo()
# contenuto utile della foto (testa + filo) in coordinate relative: x 0.16-0.73, y 0.07-0.97

# Testata desktop 2640x810: zona sempre visibile x 450-2190 (a 800 px di finestra si tagliano i lati)
img = sfondo_esteso(foto, 2640, 810, 0.40, 300, -15)          # foto 1248x832
metti(img, blocco, 760, 1390, 105)
salva(img, f"{ALIAS}-header.jpg")

# Testata mobile 828x1000 (sotto la velatura scura della pagina, più forte a sinistra)
img = sfondo_esteso(foto, 828, 1000, 0.34, -140, -50)          # foto 1061x707
metti(img, blocco, 530, 268, 560)
salva(img, f"{ALIAS}-header-mb.jpg")

# Condivisione social 1200x630 (og:image letto da assets/img/<alias>.jpg)
img = sfondo_esteso(foto, 1200, 630, 0.32, -110, -10)          # foto 998x666
metti(img, blocco, 560, 590, 150)
salva(img, f"{ALIAS}.jpg")

# Card del calendario /convegni-e-webinar 980x551 (come il-mantello-del-custode-header-top.jpg),
# scritta nella cartella del lavoro aa-calendario-convegni, che la installa
img = sfondo_esteso(foto, 980, 551, 0.27, -95, -8)            # foto 842x562
metti(img, blocco, 455, 480, 135)
card = DIR.parent / "aa-calendario-convegni" / "files" / "assets" / "img"
card.mkdir(parents=True, exist_ok=True)
img.save(card / f"{ALIAS}-card.jpg", "JPEG", quality=82, optimize=True, progressive=True)
print(f"../aa-calendario-convegni/files/assets/img/{ALIAS}-card.jpg  980x551")

# Carosello della home 1900x900: testo HTML bianco a sinistra (x 306-1060), fascia visibile y 153-747
img = sfondo_esteso(foto.transpose(Image.FLIP_LEFT_RIGHT), 1900, 900, 0.36, 760, 70)   # testa a destra, come la foto fornita
img = sfumatura_blu(img, 0.78)
salva(img, f"banner-news-{ALIAS}.jpg")

# Logo del Comune (patrocinio) per la colonna laterale: stessa altezza di logo-kos-academy.png (200 px, a 2x),
# fondo trasparente; stemma e scritta presi dalla locandina
with tempfile.TemporaryDirectory() as t:
    subprocess.run(["pdftoppm", "-r", "600", "-f", "1", "-l", "1", "-png", str(DIR / LOCANDINA), f"{t}/p"], check=True)
    pag = Image.open(next(pathlib.Path(t).glob("p*.png"))).convert("RGB")
W, H = pag.size
a = np.asarray(pag.crop((int(W * 0.05), int(H * 0.86) + 260, int(W * 0.05) + 1180, int(H * 0.86) + 1030))).astype(np.float32)
alpha = np.clip((255 - a.min(axis=2)) / 255 * 1.15, 0, 1)[..., None]
col = np.where(alpha > 0.01, (a - 255 * (1 - alpha)) / np.maximum(alpha, 0.01), 0)
logo = Image.fromarray(np.dstack([np.clip(col, 0, 255), alpha * 255]).astype(np.uint8), "RGBA")
logo = logo.crop(logo.getbbox())
logo = logo.resize((round(logo.width * 200 / logo.height), 200), Image.LANCZOS).quantize(256, method=Image.Quantize.FASTOCTREE)
logo.save(OUT / "logo-comune-cinisello-balsamo.png", optimize=True)
print(f"files/assets/img/logo-comune-cinisello-balsamo.png  {logo.width}x{logo.height}")

doc = DIR / "files" / "assets" / "doc"
doc.mkdir(parents=True, exist_ok=True)
shutil.copyfile(DIR / LOCANDINA, doc / f"{ALIAS}-locandina.pdf")
print(f"files/assets/doc/{ALIAS}-locandina.pdf")
