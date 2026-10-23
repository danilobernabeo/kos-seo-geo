<?php
/**
 * immagine — markup <picture> (WebP + formato originale, srcset, width/height, alt) per un'immagine del sito.
 * AA-1 immagini di sfondo → <img>; AA-17 immagini pesanti. Libreria: core/components/kosseogeo/Immagini.php.
 *
 * Chiamata SEMPRE in cache (senza «!»): le varianti si generano solo quando la cache della pagina si rigenera.
 *   [[immagine? &src=`/assets/img/rsa/[[*alias]].jpg` &alt=`[[*pagetitle]]` &priorita=`1`
 *       &sizes=`100vw` &class=`object-fit-cover w-100 h-100`]]
 *
 * &src         URL o percorso dell'immagine (/assets/..., assets/..., https://anniazzurri.it/assets/...)
 * &alt         testo alternativo (vuoto solo per immagini decorative)
 * &sizes       attributo sizes (default 100vw)
 * &class       classi dell'<img>; &classePicture classi del <picture>
 * &attributi   attributi aggiuntivi dell'<img> (es. style="...")
 * &priorita    1 = immagine principale sopra la piega: fetchpriority="high", niente lazy
 */
$lib = MODX_CORE_PATH . 'components/kosseogeo/Immagini.php';
$src = trim((string) ($src ?? ''));
if ($src === '') return '';
$alt = (string) ($alt ?? '');
if (!is_file($lib)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[immagine] libreria mancante: ' . $lib);
    $e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    return '<img src="' . $e('/' . ltrim($src, '/')) . '" alt="' . $e($alt) . '" loading="lazy">';
}
require_once $lib;
static $img = null;
$img ??= new \KosSeoGeo\Immagini(MODX_BASE_PATH, [
    'cache'     => $modx->getOption('kosseogeo.img_cache', null, 'assets/cache-img'),
    'max_lato'  => (int) $modx->getOption('kosseogeo.img_max_lato', null, 2400),
    'q_jpg'     => (int) $modx->getOption('kosseogeo.img_q_jpg', null, 82),
    'q_webp'    => (int) $modx->getOption('kosseogeo.img_q_webp', null, 80),
]);
return $img->picture($src, [
    'alt'            => $alt,
    'sizes'          => $sizes ?? '100vw',
    'class'          => $class ?? '',
    'classe_picture' => $classePicture ?? '',
    'attributi'      => $attributi ?? '',
    'priorita'       => !empty($priorita),
]);
