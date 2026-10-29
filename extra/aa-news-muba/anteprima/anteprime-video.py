#!/usr/bin/env python3
"""
Anteprime dei tre videotutorial (albero, stella, pallina) come la parte bassa del totem «Un Natale alla portata di
tutti»: fondo celeste, filo bianco ondulato a cui è appesa la decorazione fotografica, palla bianca grande dietro e
palline sul suo bordo (celeste chiaro pieno, oppure «invertite»: bianche sul celeste, celesti sul bianco).
Scrive files/assets/news-muba/video-<nome>.webp, 1280 x 720. La cornice blu e il pulsante play sono in news-muba.css.

Uso: python3 anteprima/anteprime-video.py <PDF Illustrator del totem>     (servono pdfimages di poppler e Pillow)
Il PDF contiene ogni decorazione come immagine RGB più maschera di trasparenza (smask), nell'ordine di
`pdfimages -list`: 0/1 pallina, 2/3 albero, 5/6 stella. Posizione e combinazione delle palline: VAR, in fondo.
"""
import math, subprocess, sys, tempfile, pathlib
from PIL import Image, ImageDraw, ImageFilter, ImageChops

DIR = pathlib.Path(__file__).resolve().parent.parent
OUT = DIR / 'files' / 'assets' / 'news-muba'
W, H, S = 1280, 720, 2                                     # S: sovracampionamento per bordi puliti
COPPIE = {'pallina': (0, 1), 'albero': (2, 3), 'stella': (5, 6)}
SOG = {'albero': (0.887, 400), 'stella': (0.48, 470), 'pallina': (0.517, 420)}   # punto del cordino (% larghezza), altezza
DEC = {}
with tempfile.TemporaryDirectory() as t:
    subprocess.run(['pdfimages', '-png', '-f', '1', '-l', '1', sys.argv[1], f'{t}/x'], check=True)
    for nome, (i, m) in COPPIE.items():
        im = Image.open(f'{t}/x-{i:03d}.png').convert('RGB')
        im.putalpha(Image.open(f'{t}/x-{m:03d}.png').convert('L'))
        DEC[nome] = im.crop(im.getchannel('A').point(lambda v: 255 if v > 10 else 0).getbbox())

AZZ=(153,221,227); BIANCO=(255,255,255); CHIARO=(192,234,238)
def bez(p0,p1,p2,p3,n=200):
    return [((1-t)**3*p0[0]+3*(1-t)**2*t*p1[0]+3*(1-t)*t*t*p2[0]+t**3*p3[0],(1-t)**3*p0[1]+3*(1-t)**2*t*p1[1]+3*(1-t)*t*t*p2[1]+t**3*p3[1]) for t in [i/n for i in range(n+1)]]
FILO=bez((-20,120),(300,30),(560,40),(760,95))+bez((760,95),(960,150),(1150,120),(1300,60))
y_filo=lambda x: min(FILO,key=lambda p:abs(p[0]-x))[1]
def maschera(cx,cy,r):
    m=Image.new('L',(W*S,H*S),0); ImageDraw.Draw(m).ellipse([(cx-r)*S,(cy-r)*S,(cx+r)*S,(cy+r)*S],fill=255); return m
def rendi(nome,palline,liberi,out):
    corda,h=SOG[nome]
    o=DEC[nome]; w=round(o.width*h/o.height); o=o.resize((w*S,h*S),Image.LANCZOS)
    cxo=640+(corda-0.5)*w if nome=='albero' else 640
    x0=cxo-corda*w; y0=y_filo(cxo)-6
    C=(640, y0+h*0.62+95); R=300
    im=Image.new('RGB',(W*S,H*S),AZZ)
    for (x,y,r) in liberi: im.paste(CHIARO,mask=maschera(x,y,r))      # palline chiare sullo sfondo
    im.paste(BIANCO,mask=maschera(*C,R))                                # palla bianca grande
    for (tipo,ang,dist,r) in palline:                                   # palline sul bordo, in ordine
        a=math.radians(ang); x=C[0]+(R+dist)*math.cos(a); y=C[1]-(R+dist)*math.sin(a)
        m=maschera(x,y,r)
        if tipo=='chiaro': im.paste(CHIARO,mask=m)
        else:  # invertita come nel totem: bianca sul celeste, celeste sul bianco o sul chiaro
            sotto_az=ImageChops.difference(im,Image.new('RGB',im.size,AZZ)).convert('L').point(lambda v:255 if v<8 else 0)
            im.paste(AZZ,mask=m); im.paste(BIANCO,mask=ImageChops.multiply(m,sotto_az))
    d=ImageDraw.Draw(im); d.line([(x*S,y*S) for x,y in FILO],fill=BIANCO,width=7*S,joint='curve')
    ombra=Image.new('RGBA',o.size,(0,0,0,0)); ombra.putalpha(o.getchannel('A').point(lambda v:int(v*0.22))); ombra=ombra.filter(ImageFilter.GaussianBlur(14*S))
    base=im.convert('RGBA'); base.alpha_composite(ombra,(int(x0*S),int((y0+14)*S))); base.alpha_composite(o,(int(x0*S),int(y0*S)))
    base.convert('RGB').resize((W,H),Image.LANCZOS).save(out,'WEBP',quality=80,method=6)
# (tipo, angolo sul bordo in gradi, distanza dal bordo: + fuori / - dentro, raggio); l'angolo in basso a destra resta libero per il play
VAR={
 'albero': ([('chiaro',212,-20,105),('inv',196,70,46),('inv',5,10,72)],   [(150,230,55),(1150,200,40)]),
 'stella': ([('inv',165,0,78),('chiaro',200,30,52),('chiaro',25,-15,92),('inv',40,45,40)], [(1180,260,38),(110,560,46)]),
 'pallina':([('inv',195,5,88),('inv',150,60,36),('chiaro',-2,30,100),('inv',14,95,40)], [(130,240,48),(1170,170,34)]),
}
for nome, (p, l) in VAR.items():
    rendi(nome, p, l, OUT / f'video-{nome}.webp')
    print(OUT / f'video-{nome}.webp')
