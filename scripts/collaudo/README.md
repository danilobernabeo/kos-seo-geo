# Collaudo visivo

Confronto stage/produzione a più larghezze con Playwright (Chromium), da lanciare sul PC: lo stage e la produzione non
sono raggiungibili dal cloud di lavoro. Dettaglio dei controlli in testa a `collaudo-visivo.mjs`.

Installazione (una volta, serve Node.js 18 o successivo):

    cd scripts/collaudo
    npm install
    npx playwright install chromium

Uso (PowerShell):

    $env:AUTH = "kos:<password dello stage>"
    node collaudo-visivo.mjs --stage=https://stage.anniazzurri.it --prod=https://anniazzurri.it --pagine=pagine-aa.txt

Rapporto in `collaudo-<data>/rapporto.html`: tabella delle segnalazioni ordinata per gravità, poi per ogni pagina e
larghezza produzione, stage e differenze affiancate. La password non va scritta nei file: solo nella variabile `AUTH`
della sessione di PowerShell.

Tempo di risposta (TTFB) nello stesso giro: `--tempi=5` (mediana di 5 misure a cache calda per sito, tabella in testa al
rapporto e `tempi.json`); solo i tempi, senza browser: `--tempi=5 --solo-tempi`. L'elenco delle pagine può essere
l'output di `php scripts/checks/risorse-per-template.php /www --campione` (commenti in fondo alla riga, UTF-16 accettato).

## Caricamento differito delle immagini (KOS-2 B)

`conta-lazy.mjs` conta, pagina per pagina e a mobile e desktop, le immagini fuori schermo scaricate senza
`loading="lazy"` (numero e KB), e segnala: KO se l'elemento LCP è in lazy, se un'immagine lazy visibile non compare
dopo aver scorso la pagina fino in fondo o se uno sfondo precaricato si scarica due volte; AVV per le immagini lazy
nella prima schermata e per i preload non usati. Nessun rallentamento: serve a contare, non a misurare l'LCP.

    $env:AUTH = "kos:<password dello stage>"
    node conta-lazy.mjs --base=https://stage.kosgroup.com --pagine=pagine-kos-2b.txt
    node conta-lazy.mjs --base=https://kosgroup.com --pagine=pagine-kos-2b.txt

LCP prima/dopo: `misura-lcp.mjs` sulla produzione e sullo stage con lo stesso elenco, poi
`node confronta-lcp.mjs <cartella produzione>\risultati.json <cartella stage>\risultati.json` (PEGGIORATA oltre il 10% e
300 ms).
