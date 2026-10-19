<?php
/**
 * PhotoGallery Snippet per MODX 3.1 con Lazy Loading
 * 
 * Crea una fotogallery leggendo le immagini da una cartella specifica
 * basata sull'ID della risorsa corrente
 * 
 * Chiamata semplice: [[!PhotoGallery]]
 * Utilizza automaticamente il percorso /assets/img/news/{id-risorsa-corrente}/
 * 
 * Parametri:
 * &basePath - Percorso base delle immagini (default: /assets/img/news/)
 * &resourceId - ID della risorsa (default: ID risorsa corrente)
 * &altText - Testo alternativo per le immagini (default: "Immagine tratta dall'evento")
 * &allowedExtensions - Estensioni consentite (default: jpg,jpeg,png,gif,webp)
 * &lazyLoading - Abilita lazy loading (default: true)
 * &placeholderSrc - Immagine placeholder per lazy loading (default: vuoto)
 */

// Parametri dello snippet
$basePath = $modx->getOption('basePath', $scriptProperties, '/assets/img/news/');
$resourceId = $modx->getOption('resourceId', $scriptProperties, $modx->resource->get('id'));
$altText = $modx->getOption('altText', $scriptProperties, 'Immagine tratta dall\'evento');
$allowedExtensions = explode(',', $modx->getOption('allowedExtensions', $scriptProperties, 'jpg,jpeg,png,gif,webp'));
$lazyLoading = $modx->getOption('lazyLoading', $scriptProperties, true);
$placeholderSrc = $modx->getOption('placeholderSrc', $scriptProperties, '');

// Costruisci il percorso della cartella
$folderPath = MODX_BASE_PATH . trim($basePath, '/') . '/' . $resourceId . '/';
$webPath = rtrim($basePath, '/') . '/' . $resourceId . '/';

// Verifica se la cartella esiste
if (!is_dir($folderPath)) {
    return '<!-- Cartella non trovata: ' . $folderPath . ' -->';
}

// Leggi i file dalla cartella
$files = scandir($folderPath);
$images = [];

// Filtra solo le immagini valide
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $filePath = $folderPath . $file;
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Verifica se è un'immagine valida
    if (is_file($filePath) && in_array($extension, $allowedExtensions)) {
        // Ottieni le dimensioni dell'immagine
        $imageInfo = getimagesize($filePath);
        
        if ($imageInfo !== false) {
            $images[] = [
                'filename' => $file,
                'path' => $webPath . $file,
                'width' => $imageInfo[0],
                'height' => $imageInfo[1]
            ];
        }
    }
}

// Ordina le immagini per nome
usort($images, function($a, $b) {
    return strcmp($a['filename'], $b['filename']);
});

$totalImages = count($images);

// Se non ci sono immagini, restituisci messaggio vuoto
if ($totalImages === 0) {
    return '<!-- Nessuna immagine trovata nella cartella: ' . $folderPath . ' -->';
}

// Genera l'HTML
$output = '<div class="gallery-struttura">' . "\n";

foreach ($images as $index => $image) {
    $output .= '    <div class="gallery-foto" data-pswp-width="' . $image['width'] . '" data-pswp-src="' . $image['path'] . '" data-pswp-height="' . $image['height'] . '">' . "\n";
    
    // Costruisci gli attributi dell'immagine
    $imgAttributes = [];
    $imgAttributes[] = 'alt="' . $altText . '"';
    
    if ($lazyLoading) {
        // Usa loading="lazy" nativo
        $imgAttributes[] = 'loading="lazy"';
        
        // Se è fornito un placeholder, usalo per le prime immagini
        if (!empty($placeholderSrc) && $index > 0) {
            $imgAttributes[] = 'src="' . $placeholderSrc . '"';
            $imgAttributes[] = 'data-src="' . $image['path'] . '"';
        } else {
            $imgAttributes[] = 'src="' . $image['path'] . '"';
        }
    } else {
        $imgAttributes[] = 'src="' . $image['path'] . '"';
    }
    
    $output .= '        <img ' . implode(' ', $imgAttributes) . '>' . "\n";
    $output .= '    </div>' . "\n";
    $output .= '    ' . "\n";
}

$output .= '    <span class="gallery-tutte">' . "\n";
$output .= '        <img src="/assets/img/photo.png" width="24" height="24" alt="icona foto">' . "\n";
$output .= '        Vedi tutte <span class="foto-counter"><span class="foto-num">1</span>/<span class="foto-tot">' . $totalImages . '</span></span>' . "\n";
$output .= '    </span>' . "\n";
$output .= '</div>';

return $output;