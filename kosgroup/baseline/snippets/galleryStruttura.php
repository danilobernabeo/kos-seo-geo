<?php
// Lettura del percorso della cartella dal parametro dello snippet
$directory = $modx->getOption('path', $scriptProperties, '');

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

// Inizio del codice HTML
$output = '<div class="gallery-struttura">';

// Ciclo su ogni immagine trovata nella cartella
foreach ($images as $image) {
    // Ottiene le dimensioni dell'immagine
    list($width, $height) = getimagesize($image);
    $relativePath = $directory . basename($image);

    // Aggiunge il codice HTML per ciascuna immagine
    $output .= '<div class="gallery-foto" data-pswp-width="' . $width . '" data-pswp-height="' . $height . '" data-pswp-src="' . $relativePath . '">';
    $output .= '<img src="' . $relativePath . '" alt=""/>';
    $output .= '</div>';
}

// Aggiunta dell'elemento per il conteggio delle immagini
$output .= '<span class="gallery-tutte">';
$output .= '<img src="assets/img/photo.png" width="24" height="24" alt="icona foto"/>Vedi tutte <span class="foto-counter"><span class="foto-num">1</span>/<span class="foto-tot">' . $totalImages . '</span></span>';
$output .= '</span>';

// Fine del codice HTML
$output .= '</div>';

// Restituzione dell'output
return $output;