# CSS critico di kosgroup.com (KOS 15)

Il foglio unico `/assets/css/kos.min.css` (KOS 25) non blocca più la prima visualizzazione:

- nell'head, chunk `kos-css-critico`: le regole che servono alla parte iniziale delle pagine (circa 90 kB, 18 kB compressi),
  generate da questi script; poi preload del foglio intero e dei tre pesi di Satoshi;
- in fondo al body, chunk `scripts`: il foglio intero prima degli script. Blocca solo ciò che segue (gli script), che
  così partono con il foglio applicato, come prima;
- snippet `kosDimInfoImage` nei template `informativo` e `tecnologie`: dimensioni dell'immagine di testata, per riservarne
  lo spazio.

Rigenerare (quando cambia `kos.min.css` o la parte iniziale di un template), dalla cartella, dopo `npm install`:

```
node estrai.mjs pagine-20261125.txt     # sola lettura, tutte le pagine della sitemap a 390, 1366 e 1920 px, ~30 min
node costruisci.mjs                     # scrive kosgroup/elements/chunks/kos-css-critico.html
node valida.mjs <pagine>                # senza JS: foglio intero contro solo critico (differenza in pixel)
node cls.mjs campione.txt [--lento]     # con JS: CLS, FCP e LCP prima e dopo
```

Installazione: `kosgroup/deploy/kos-15-css-critico-20261125.json` con `scripts/deploy/installa-elementi.php`.
