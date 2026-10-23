<?php
/**
 * photoGalleryAA2025 Snippet per MODX 3.1
 * 
 * Genera una fotogallery di N immagini compatibile con il template Porto Photography
 * Pattern layout: 1a e 12a immagine di ogni blocco GRANDI, tutte le altre PICCOLE
 * Supporta formati: AVIF, WebP, JPG
 * 
 * Uso: [[!photoGalleryAA2025]]
 * 
 * Parametri opzionali:
 * &imagePath - Percorso cartella immagini (default: assets/img/aa2025/)
 * &totalImages - Numero totale immagini (default: 144)
 */

// Parametri
// AA-1 / AA-9: testo alternativo dal titolo della pagina («<titolo>, foto N»); miniature duplicate con alt vuoto
$altBase = $modx->getOption('alt', $scriptProperties, $modx->resource ? $modx->resource->get('pagetitle') : 'Anni Azzurri');
$imagePath = $modx->getOption('imagePath', $scriptProperties, 'assets/img/aa2025/');
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

/**
 * Genera il tag <picture> con AVIF, WebP e JPG
 * con lazy loading dell'img
 */
function generatePictureTag($imagePath, $imageUrl, $altText = '', $classe = 'img-fluid') {
    $pathInfo = pathinfo($imagePath);
    $filename = $pathInfo['filename'];
    
    $avifUrl = $imageUrl . $filename . '.avif';
    $webpUrl = $imageUrl . $filename . '.webp';
    $jpgUrl  = $imageUrl . $filename . '.jpg';
    
    return sprintf(
        '<picture>
            <source srcset="%s" type="image/avif">
            <source srcset="%s" type="image/webp">
            <img src="%s" alt="%s" class="%s" loading="lazy" decoding="async">
        </picture>',
        htmlspecialchars($avifUrl),
        htmlspecialchars($webpUrl),
        htmlspecialchars($jpgUrl),
        htmlspecialchars($altText),
        htmlspecialchars($classe)
    );
}

/**
 * Determina le classi CSS in base alla posizione nel blocco di 14
 * Posizione nel blocco: 1-14
 * Solo 1 e 12 sono "grandi"
 */
function getImageClasses($position) {
    // Posizione nel blocco di 14 (1-14)
    $pos = (($position - 1) % 14) + 1;
    
    // Pattern: solo posizione 1 e 12 sono GRANDI
    $largePositions = [1, 12];
    $isLarge = in_array($pos, $largePositions);
    
    $liClass   = $isLarge ? 'col-lg-6' : 'col-md-6 col-lg-3';
    $sizeClass = $isLarge ? 'size-1' : 'size-2';
    
    return [
        'liClass'   => $liClass,
        'sizeClass' => $sizeClass
    ];
}

// ========================================
// LIGHTBOX HTML
// ========================================

$lightboxHtml = '<div id="photographyLightbox" class="mfp-hide">
    <div class="thumb-gallery">
        <div class="owl-carousel owl-theme manual thumb-gallery-detail" id="thumbGalleryDetail">';

// Immagini detail per il lightbox
foreach ($images as $index => $imgPath) {
    $altText = $altBase . ', foto ' . ($index + 1);
    
    $lightboxHtml .= sprintf('
            <div>
                <span class="img-thumbnail d-block">%s</span>
            </div>',
        generatePictureTag($imgPath, $imageUrl, $altText)
    );
}

$lightboxHtml .= '
        </div>
        <div class="owl-carousel owl-theme manual thumb-gallery-thumbs show-thumbs mt" id="thumbGalleryThumbs">';

// Thumbnails per il lightbox
foreach ($images as $index => $imgPath) {
    $altText = '';   // miniatura della stessa foto: decorativa
    
    $lightboxHtml .= sprintf('
            <div>
                <span class="img-thumbnail d-block cur-pointer">%s</span>
            </div>',
        generatePictureTag($imgPath, $imageUrl, $altText)
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

foreach ($images as $index => $imgPath) {
    $imageName = pathinfo($imgPath, PATHINFO_FILENAME);
    
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
                        <span class="thumb-info-background">%s</span>
                        <span class="thumb-info-plus"></span>
                    </div>
                </div>
            </a>
        </div>
    </li>',
        $classes['liClass'],
        $index,
        $classes['sizeClass'],
        // AA-1: miniatura come <picture> (AVIF/WebP/JPG già presenti) al posto dello sfondo caricato via JavaScript
        generatePictureTag($imgPath, $imageUrl, $altBase . ', foto ' . ($index + 1), 'kos-sfondo')
    );
}

$gridHtml .= '
</ul>';

// ========================================
// SCRIPT LAZY LOAD BACKGROUND-IMAGE
// ========================================

$lazyScript = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
    var lazyBgElems = document.querySelectorAll('.thumb-info-background[data-bg]');
    if (!lazyBgElems.length) return;

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    var bg = el.getAttribute('data-bg');
                    if (bg) {
                        el.style.backgroundImage = "url('" + bg + "')";
                        el.removeAttribute('data-bg');
                    }
                    obs.unobserve(el);
                }
            });
        }, { rootMargin: '200px 0px' });

        lazyBgElems.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        // Fallback per browser vecchi: carica subito tutte le immagini
        lazyBgElems.forEach(function (el) {
            var bg = el.getAttribute('data-bg');
            if (bg) {
                el.style.backgroundImage = "url('" + bg + "')";
                el.removeAttribute('data-bg');
            }
        });
    }
});
</script>
HTML;

// ========================================
// OUTPUT FINALE
// ========================================

return $lightboxHtml . $gridHtml . $lazyScript;