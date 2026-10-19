<?php
/**
 * photoGalleryKOS2025 Snippet per MODX 3.1
 * 
 * Genera una fotogallery di 144 immagini compatibile con il template Porto Photography
 * Pattern layout: 1a e 12a immagine di ogni blocco GRANDI, tutte le altre PICCOLE
 * Supporta formati: AVIF, WebP, JPG
 * 
 * Uso: [[!photoGalleryKOS2025]]
 * 
 * Parametri opzionali:
 * &imagePath - Percorso cartella immagini (default: assets/img/kos2025/)
 * &totalImages - Numero totale immagini (default: 144)
 */

// Parametri
$imagePath = $modx->getOption('imagePath', $scriptProperties, 'assets/img/kos2025/');
$totalImages = (int)$modx->getOption('totalImages', $scriptProperties, 144);

// Assicurati che il percorso termini con /
$imagePath = rtrim($imagePath, '/') . '/';

// Path completi
$imageFolder = MODX_BASE_PATH . $imagePath;
$imageUrl = MODX_BASE_URL . $imagePath;

// Verifica che la cartella esista
if (!is_dir($imageFolder)) {
    return '<div class="alert alert-warning">Cartella immagini non trovata: ' . htmlspecialchars($imagePath) . '</div>';
}

// Ottieni tutte le immagini JPG dalla cartella
$images = glob($imageFolder . '*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);

if (empty($images)) {
    return '<div class="alert alert-warning">Nessuna immagine JPG trovata nella cartella.</div>';
}

// Ordina le immagini
sort($images);

// Limita al numero specificato
$images = array_slice($images, 0, $totalImages);
$imageCount = count($images);

// Funzione per generare i tag picture con formati multipli
function generatePictureTag($imagePath, $imageUrl, $altText = '') {
    $pathInfo = pathinfo($imagePath);
    $filename = $pathInfo['filename'];
    
    $avifUrl = $imageUrl . $filename . '.avif';
    $webpUrl = $imageUrl . $filename . '.webp';
    $jpgUrl = $imageUrl . $filename . '.jpg';
    
    return sprintf(
        '<picture>
            <source srcset="%s" type="image/avif">
            <source srcset="%s" type="image/webp">
            <img src="%s" alt="%s" class="img-fluid">
        </picture>',
        htmlspecialchars($avifUrl),
        htmlspecialchars($webpUrl),
        htmlspecialchars($jpgUrl),
        htmlspecialchars($altText)
    );
}

// Funzione per determinare le classi CSS in base alla posizione nel blocco
function getImageClasses($position) {
    // Posizione nel blocco di 14 (1-14)
    $pos = (($position - 1) % 14) + 1;
    
    // Pattern: solo posizione 1 e 12 sono GRANDI
    $largePositions = [1, 12];
    $isLarge = in_array($pos, $largePositions);
    
    $liClass = $isLarge ? 'col-lg-6' : 'col-md-6 col-lg-3';
    $sizeClass = $isLarge ? 'size-1' : 'size-2';
    
    return [
        'liClass' => $liClass,
        'sizeClass' => $sizeClass
    ];
}

// ========================================
// LIGHTBOX HTML
// ========================================

$lightboxHtml = '<div id="photographyLightbox" class="mfp-hide">
    <div class="thumb-gallery">
        <div class="owl-carousel owl-theme manual thumb-gallery-detail" id="thumbGalleryDetail">';

// Genera le immagini detail per il lightbox
foreach ($images as $index => $imagePath) {
    $imageName = pathinfo($imagePath, PATHINFO_FILENAME);
    $altText = 'Immagine ' . ($index + 1);
    
    $lightboxHtml .= sprintf('
            <div>
                <span class="img-thumbnail d-block">%s</span>
            </div>',
        generatePictureTag($imagePath, $imageUrl, $altText)
    );
}

$lightboxHtml .= '
        </div>
        <div class="owl-carousel owl-theme manual thumb-gallery-thumbs show-thumbs mt" id="thumbGalleryThumbs">';

// Genera le thumbnails per il lightbox
foreach ($images as $index => $imagePath) {
    $imageName = pathinfo($imagePath, PATHINFO_FILENAME);
    $altText = 'Thumbnail ' . ($index + 1);
    
    $lightboxHtml .= sprintf('
            <div>
                <span class="img-thumbnail d-block cur-pointer">%s</span>
            </div>',
        generatePictureTag($imagePath, $imageUrl, $altText)
    );
}

$lightboxHtml .= '
        </div>
    </div>
</div>';

// ========================================
// PORTFOLIO GRID HTML
// ========================================

$gridHtml = '<ul id="portfolioGrid" class="p-0" data-grid-sizer=".col-lg-3">';

foreach ($images as $index => $imagePath) {
    $imageName = pathinfo($imagePath, PATHINFO_FILENAME);
    
    // Ottieni le classi CSS in base alla posizione (1-indexed)
    $classes = getImageClasses($index + 1);
    
    // URL dell'immagine per il background (usa JPG)
    $bgUrl = $imageUrl . $imageName . '.jpg';
    
    $gridHtml .= sprintf('
    <li class="%s isotope-item p-0">
        <div class="portfolio-grid-item">
            <a href="#photographyLightbox" class="text-decoration-none popup-with-move-anim" data-index="%d">
                <div class="thumb-info">
                    <div class="thumb-info-wrapper %s m-0">
                        <span class="thumb-info-background" style="background-image: url(\'%s\');"></span>
                        <span class="thumb-info-plus"></span>
                    </div>
                </div>
            </a>
        </div>
    </li>',
        $classes['liClass'],
        $index,
        $classes['sizeClass'],
        htmlspecialchars($bgUrl)
    );
}

$gridHtml .= '
</ul>';

// ========================================
// OUTPUT FINALE
// ========================================

return $lightboxHtml . $gridHtml;