"""Slide della home del convegno Parkinson dal banner ricevuto il 12/11 (materiali/banner-home-ricevuto-20261112.jpg).

Toglie titolo e loghi, sposta tutta la foto a destra di 330 px (testa e filo (fuori dalla colonna del testo HTML: 300-950 px a 1920, 160-940 a
1366), rimette il logo Anni Azzurri | KOS ridotto in basso a destra, aggiunge a sinistra la sfumatura blu per il testo
bianco. Uscita 1900x570 (proporzione della slide a 1920x1031: 1905x567), JPEG progressivo.
Uso: python3 -I slide-home.py <banner ricevuto> <uscita.jpg> [<uscita-mobile.jpg>]
"""
import sys
import numpy as np
from PIL import Image, ImageFilter

src, out = sys.argv[1], sys.argv[2]
a = np.asarray(Image.open(src).convert('RGB')).astype(float)
H, W, _ = a.shape                                   # 600 x 1920

def riempi(img, x0, x1, y0, y1):
    """Cancella un rettangolo interpolando riga per riga tra le colonne appena fuori (sfondo uniforme)."""
    sx = img[y0:y1, x0 - 6:x0 - 1].mean(axis=1); dx_ = img[y0:y1, x1 + 1:x1 + 6].mean(axis=1)
    sx = np.asarray(Image.fromarray(sx[:, None].astype(np.uint8)).filter(ImageFilter.GaussianBlur(4)))[:, 0].astype(float)
    dx_ = np.asarray(Image.fromarray(dx_[:, None].astype(np.uint8)).filter(ImageFilter.GaussianBlur(4)))[:, 0].astype(float)
    t = np.linspace(0, 1, x1 - x0)[None, :, None]
    fill = sx[:, None, :] * (1 - t) + dx_[:, None, :] * t
    # bordi sfumati in alto e in basso (18 px) per non lasciare righe
    r = np.ones(y1 - y0); k = min(18, (y1 - y0) // 2)
    r[:k] = np.linspace(0, 1, k); r[-k:] = np.linspace(1, 0, k)
    img[y0:y1, x0:x1] = img[y0:y1, x0:x1] * (1 - r[:, None, None]) + fill * r[:, None, None]

orig = a.copy()
pulita = a.copy()
riempi(pulita, 90, 820, 255, 435)      # titolo
riempi(pulita, 1228, 1722, 214, 410)   # logo

# tutta la foto spostata a destra (niente giunte); a sinistra si ripete la prima colonna (sfondo uniforme)
dx = 330
tela = np.empty_like(pulita)
tela[:, dx:] = pulita[:, :-dx]
tela[:, :dx] = pulita[:, :1]
bg = pulita
a = orig

# logo Anni Azzurri | KOS (1241-1709 x 245-376): alfa dalla distanza dallo sfondo, ridotto al 60%
x0, x1, y0, y1 = 1236, 1714, 240, 381
pz = a[y0:y1, x0:x1]; pb = bg[y0:y1, x0:x1]
al = np.clip((np.linalg.norm(pz - pb, axis=2) - 14) / 80, 0, 1)
col = np.where(al[..., None] > 0.05, pb + (pz - pb) / np.maximum(al[..., None], 0.05), pz)
rgba = np.dstack([np.clip(col, 0, 255), al * 255]).astype(np.uint8)
s = 0.6
logo = Image.fromarray(rgba, 'RGBA').resize((round((x1 - x0) * s), round((y1 - y0) * s)), Image.LANCZOS)
lx, ly = 1560, 465
L = np.asarray(logo).astype(float)
la = L[..., 3:] / 255
reg = tela[ly:ly + L.shape[0], lx:lx + L.shape[1]]
tela[ly:ly + L.shape[0], lx:lx + L.shape[1]] = reg * (1 - la) + L[..., :3] * la

# sfumatura blu a sinistra per il testo bianco
blu = np.array([30, 62, 122], float)
x = np.arange(W) / 1250.0
t = np.clip(1 - x, 0, 1); t = t * t * (3 - 2 * t) * 0.95
tela = tela * (1 - t[None, :, None]) + blu * t[None, :, None]

img = Image.fromarray(np.clip(tela, 0, 255).astype(np.uint8)).crop((10, 15, 1910, 585))
img.save(out, 'JPEG', quality=80, optimize=True, progressive=True, subsampling='4:2:0')
print(out, img.size)

# versione per i telefoni (&srcMobile): la slide è quasi quadrata (375x365) e il testo occupa tutta la larghezza;
# quadrato attorno alla testa, velatura blu più forte a sinistra perché il testo bianco resti leggibile
if len(sys.argv) > 3:
    q = np.asarray(img.crop((975, 0, 1545, 570)).resize((740, 740), Image.LANCZOS)).astype(float)
    xs = np.linspace(0, 1, 740)
    v = (0.88 - 0.38 * xs)[None, :, None]
    q = q * (1 - v) + blu * v
    Image.fromarray(np.clip(q, 0, 255).astype(np.uint8)).save(sys.argv[3], 'JPEG', quality=80, optimize=True, progressive=True)
    print(sys.argv[3], (740, 740))
