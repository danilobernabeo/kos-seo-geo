<?php
/**
 * Snippet kosSostenibilitaRisorse — carica CSS e JS dei grafici solo sulle pagine che li usano.
 * Uso: [[kosSostenibilitaRisorse]] in ogni chunk kosDipendenti_*, kosStrutture_*, kosAttivita_*, kosRicavi_*.
 * MODX registra ogni file una sola volta anche se la chiamata si ripete, e conserva la registrazione
 * nella cache della pagina. I CSS vanno prima di </head> (dopo custom.css), i JS prima di </body>.
 * Nessun ?v= scritto a mano: lo aggiunge il plugin AssetVersion con la data del file.
 */
foreach (['kos-chart.css', 'kos-strutture.css', 'kos-ricavi.css'] as $f) {
    $modx->regClientCSS('/assets/css/' . $f);
}
foreach (['kos-chart.js', 'kos-ricavi.js'] as $f) {
    $modx->regClientScript('/assets/js/' . $f);
}
return '';