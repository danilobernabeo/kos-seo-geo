<?php
/**
 * galleryStruttura — galleria fotografica della scheda struttura (cartella assets/img/strutture/<alias>/), richiamata
 * dal contenuto delle risorse: [[galleryStruttura? &path=`assets/img/strutture/villa-dei-pini-civitanova/`]]
 *
 * KOS-2 B (dalla baseline del 31/10): la prima foto è l'immagine principale della scheda (elemento LCP, es. Villa dei
 * Pini Civitanova): resta caricata subito, con fetchpriority="high"; le altre (fino a 99 per scheda) hanno
 * loading="lazy" decoding="async". Indirizzi delle immagini assoluti (/assets/...). Il resto è invariato.
 * &priorita=`0` toglie fetchpriority alla prima foto (gallerie che non stanno in testa alla pagina).
 */
// Lettura del percorso della cartella dal parametro dello snippet
$directory = $modx->getOption('path', $scriptProperties, '');
$priorita = (string) $modx->getOption('priorita', $scriptProperties, '1') !== '0';
// Foto caricate subito: la griglia desktop ne mostra 5 nella prima schermata (collaudo stage 1/11). &subito=`n` per cambiare.
$subito = max(1, (int) $modx->getOption('subito', $scriptProperties, 5));

// Verifica se il percorso è stato fornito
if (empty($directory)) {
    return 'Errore: specificare il parametro "path" per il percorso della cartella.';
}

// Assicurarsi che il percorso termini con uno slash
if (substr($directory, -1) !== '/') {
    $directory .= '/';
}

// Creazione di un array di tutti i file immagine nella cartella
$images = glob($directory . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

// Conteggio totale delle immagini
$totalImages = count($images);

// Indirizzo assoluto dal percorso (relativo alla radice del sito, come prima)
$web = '/' . ltrim(preg_replace('#^\./#', '', $directory), '/');

// Inizio del codice HTML
$output = '<div class="gallery-struttura">';

// Ciclo su ogni immagine trovata nella cartella
foreach ($images as $i => $image) {
    // Ottiene le dimensioni dell'immagine
    list($width, $height) = getimagesize($image);
    $relativePath = $web . basename($image);
    $caricamento = $i === 0
        ? ($priorita ? ' fetchpriority="high"' : '')
        : ($i < $subito ? '' : ' loading="lazy" decoding="async"');

    // Aggiunge il codice HTML per ciascuna immagine
    $output .= '<div class="gallery-foto" data-pswp-width="' . $width . '" data-pswp-height="' . $height . '" data-pswp-src="' . $relativePath . '">';
    $output .= '<img src="' . $relativePath . '" alt=""' . $caricamento . '/>';
    $output .= '</div>';
}

// Aggiunta dell'elemento per il conteggio delle immagini
$output .= '<span class="gallery-tutte">';
$output .= '<img src="/assets/img/photo.png" width="24" height="24" alt="icona foto"/>Vedi tutte <span class="foto-counter"><span class="foto-num">1</span>/<span class="foto-tot">' . $totalImages . '</span></span>';
$output .= '</span>';

// Fine del codice HTML
$output .= '</div>';

// Restituzione dell'output
return $output;
