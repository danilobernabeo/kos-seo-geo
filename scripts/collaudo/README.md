# Collaudo visivo

Confronto stage/produzione a più larghezze con Playwright (Chromium), da lanciare sul PC: lo stage e la produzione non
sono raggiungibili dal cloud di lavoro. Dettaglio dei controlli in testa a `collaudo-visivo.mjs`.

Installazione (una volta, serve Node.js 18 o successivo):

    cd scripts/collaudo
    npm install
    npx playwright install chromium

Uso (PowerShell):

    $env:AUTH = "kos:<password dello stage>"
    node collaudo-visivo.mjs --stage=https://tcm7b0i8.modx.dev --prod=https://anniazzurri.it --pagine=pagine-aa.txt

Rapporto in `collaudo-<data>/rapporto.html`: tabella delle segnalazioni ordinata per gravità, poi per ogni pagina e
larghezza produzione, stage e differenze affiancate. La password non va scritta nei file: solo nella variabile `AUTH`
della sessione di PowerShell.
