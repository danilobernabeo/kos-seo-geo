#!/usr/bin/env python3
"""
AA-6 H2 duplicati — conversione dei titoli di anniazzurri.it.

1) Chunk (elementi statici del repository): parte dalla versione in anniazzurri/elements/ se esiste, altrimenti dalla
   baseline, e scrive in anniazzurri/elements/chunks/.
2) Contenuti delle risorse (schede residenza, centri diurni, regioni, province): legge l'esportazione di
   scripts/checks/aa-esporta-contenuti.php e scrive i nuovi contenuti in anniazzurri/dati/aa-6-contenuti/ con un
   manifest (id, impronta del contenuto di partenza) per scripts/deploy/aggiorna-contenuti.php.

Uso (dalla radice del repository):
    python3 scripts/titoli/converti-titoli.py <cartella esportazione aa-contenuti-...> [--prova]

Regole (stessa resa grafica: le classi Bootstrap .h2/.h3/.h4/.h5 riproducono l'aspetto dei titoli):
  R1  «Guarda meno / Guarda di più» dei pulsanti degli accordion: testo spostato in data-testo e mostrato dal CSS
      (.accordion-button-helper [data-testo]::after), aria-hidden → fuori dal testo dell'H2.
  R2  frase introduttiva (primo titolo del contenuto, h2/h3 più lungo di 60 caratteri) di residenze, centri diurni,
      regioni e province → paragrafo con la classe del livello (es. <p class="h3 text-primary">).
  R3  «Come raggiungerci» h3 → h2 con classe h3 (sezione della pagina).
  R4  form contatti: «Vuoi essere ricontattato?» (h3) e «Lasciaci i tuoi dati…» (h4) → paragrafi.
  R5  gallerie, video e podcast della residenza: titolo h3 → h2 con classe h3; titolo della finestra della galleria
      (h5.modal-title) → paragrafo.
  R6  newsletter nel footer: h2 → paragrafo.
"""
import hashlib
import json
import os
import re
import sys

SITO = 'anniazzurri'
BASE = os.path.join(SITO, 'baseline', 'chunks')
OUT = os.path.join(SITO, 'elements', 'chunks')
DATI = os.path.join(SITO, 'dati', 'aa-6-contenuti')
PROVA = '--prova' in sys.argv
ESPORT = next((a for a in sys.argv[1:] if not a.startswith('--')), None)

HELPER = re.compile(r'<span class="(show-less|show-more)">\s*([^<]*?)\s*</span>')


def r1(t):
    return HELPER.subn(lambda m: f'<span class="{m.group(1)}" data-testo="{m.group(2).replace("&nbsp;", " ")}" aria-hidden="true"></span>', t)


def cambia_tag(t, pattern, nuovo_tag, classe_extra=None, conta=0):
    """Sostituisce <hN class="X">testo</hN> (pattern su classe e testo) con <nuovo_tag class="[extra ]X">."""
    def s(m):
        cls = m.group(2)
        if classe_extra and classe_extra not in cls.split():
            cls = f'{classe_extra} {cls}'.strip()
        return f'<{nuovo_tag} class="{cls}"{m.group(3)}>{m.group(4)}</{nuovo_tag}>'
    return re.subn(pattern, s, t, count=conta, flags=re.S)


def regole_chunk(nome, t):
    n = {}
    t, n['R1'] = r1(t)
    if nome in ('form-contatti-hs', 'form-rsa'):
        t, a = cambia_tag(t, r'<(h3) class="([^"]*)"([^>]*)>(\s*Vuoi essere ricontattato\?\s*)</h3>', 'p', 'h3')
        t, b = cambia_tag(t, r'<(h4) class="([^"]*text-azure[^"]*)"([^>]*)>(\s*Lasciaci i tuoi dati.*?)</h4>', 'p', 'h4')
        n['R4'] = a + b
    if nome.startswith(('gal-', 'video-', 'podcast-')):
        t, a = cambia_tag(t, r'<(h3) class="([^"]*text-primary[^"]*)"([^>]*)>(.*?)</h3>', 'h2', 'h3', conta=1)
        t, b = cambia_tag(t, r'<(h5) class="([^"]*modal-title[^"]*)"([^>]*)>(.*?)</h5>', 'p', 'h5')
        n['R5'] = a + b
    if nome == 'footer':
        t, n['R6'] = cambia_tag(t, r'<(h2) class="([^"]*)"([^>]*)>(\s*Vuoi essere sempre aggiornato.*?)</h2>', 'p')
    return t, {k: v for k, v in n.items() if v}


def regole_contenuto(tpl, t):
    n = {}
    t, n['R1'] = r1(t)
    # R2: frase introduttiva = primo titolo del contenuto, h2 o h3, più lungo di 60 caratteri (una frase, non un titolo)
    primo = re.search(r'<(h[23]) class="([^"]*)"([^>]*)>(.*?)</\1>', t, re.S)
    if primo and not re.search(r'<h[1-6]\b', t[:primo.start()]):
        testo = re.sub(r'<[^>]+>|\[\[[^\]]*\]\]|\s+', ' ', primo.group(4)).strip()
        if len(testo) > 60 or (tpl == 'rsa' and primo.group(1) == 'h3'):   # residenza: l'h3 sotto l'H1 è sempre il sottotitolo
            livello = primo.group(1)
            cls = primo.group(2) if re.search(r'\bh[1-6]\b', primo.group(2)) else f'{livello} {primo.group(2)}'.strip()
            t = t[:primo.start()] + f'<p class="{cls}"{primo.group(3)}>{primo.group(4)}</p>' + t[primo.end():]
            n['R2'] = 1
    t, n['R3'] = cambia_tag(t, r'<(h3) class="([^"]*)"([^>]*)>(\s*Come raggiungerci\s*)</h3>', 'h2', 'h3')
    t, a = cambia_tag(t, r'<(h3) class="([^"]*)"([^>]*)>(\s*Vuoi essere ricontattato\?\s*)</h3>', 'p', 'h3')
    t, b = cambia_tag(t, r'<(h4) class="([^"]*text-azure[^"]*)"([^>]*)>(\s*Lasciaci i tuoi dati.*?)</h4>', 'p', 'h4')
    n['R4'] = a + b
    return t, {k: v for k, v in n.items() if v}


def main():
    tot = {}
    # 1) chunk
    nomi = sorted(os.path.splitext(f)[0] for f in os.listdir(BASE) if f.endswith('.html'))
    scritti = 0
    for nome in nomi:
        p = os.path.join(OUT, nome + '.html')
        if not os.path.exists(p):
            p = os.path.join(BASE, nome + '.html')
        t = open(p, encoding='utf-8', newline='').read()
        nuovo, n = regole_chunk(nome, t)
        if nuovo == t:
            continue
        scritti += 1
        for k, v in n.items():
            tot[k] = tot.get(k, 0) + v
        print(f'chunk {nome}: ' + ', '.join(f'{k}×{v}' for k, v in n.items()))
        if not PROVA:
            os.makedirs(OUT, exist_ok=True)
            open(os.path.join(OUT, nome + '.html'), 'w', encoding='utf-8', newline='').write(nuovo)
    print(f'== {scritti} chunk')

    # 2) contenuti
    if not ESPORT:
        print('(contenuti saltati: manca la cartella dell\'esportazione)')
        return
    indice = json.load(open(os.path.join(ESPORT, 'indice.json'), encoding='utf-8'))
    manifest = []
    for r in indice:
        f = os.path.join(ESPORT, 'risorse', f"{r['id']}.html")
        t = open(f, encoding='utf-8', newline='').read()
        nuovo, n = regole_contenuto(r['template'], t)
        if nuovo == t:
            continue
        for k, v in n.items():
            tot[k] = tot.get(k, 0) + v
        print(f"risorsa {r['id']} {r['pagetitle']} [{r['template']}]: " + ', '.join(f'{k}×{v}' for k, v in n.items()))
        manifest.append({'id': r['id'], 'pagetitle': r['pagetitle'], 'regole': n,
                         'sha1_prima': hashlib.sha1(t.encode('utf-8')).hexdigest(), 'file': f"{r['id']}.html"})
        if not PROVA:
            os.makedirs(DATI, exist_ok=True)
            open(os.path.join(DATI, f"{r['id']}.html"), 'w', encoding='utf-8', newline='').write(nuovo)
    if not PROVA:
        json.dump({'nome': 'AA-6 titoli nei contenuti', 'campo': 'content', 'risorse': manifest},
                  open(os.path.join(DATI, 'manifest.json'), 'w', encoding='utf-8', newline=''), ensure_ascii=False, indent=1)
    print(f'== {len(manifest)} contenuti; totale sostituzioni: ' + ', '.join(f'{k}×{v}' for k, v in sorted(tot.items())))


if __name__ == '__main__':
    main()
