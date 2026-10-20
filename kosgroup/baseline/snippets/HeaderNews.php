<?php
/**
 * HeaderNews Snippet
 *
 * Genera la sezione header con la news in evidenza basata sulla TV headerNewsId.
 *
 * Priorità immagine:
 *   1. TV headerNewsImg della risorsa corrente (override opzionale)
 *   2. TV infoImage della news in evidenza
 *   3. Placeholder
 *
 * Proprietà opzionali:
 *   &placeholder=`/assets/img/placeholder.jpg`
 */

$placeholder = $modx->getOption('placeholder', $scriptProperties, '/assets/img/placeholder.jpg');

// Helper di escaping per testo e attributi
$e = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$headerNewsId = (int) $modx->resource->getTVValue('headerNewsId');
if ($headerNewsId <= 0) {
    return '<!-- Nessuna news header specificata -->';
}

// Solo risorse pubblicate e non cestinate
$newsResource = $modx->getObject(\MODX\Revolution\modResource::class, [
    'id'        => $headerNewsId,
    'published' => 1,
    'deleted'   => 0,
]);
if (!$newsResource) {
    return '<!-- News header ID ' . $headerNewsId . ' non trovata o non pubblicata -->';
}

// Dati della news
$title     = $newsResource->get('pagetitle');
$introtext = $newsResource->get('introtext');
// Link nello stesso formato di [[~id]] (impostazione link_tag_scheme, oggi "abs": /it/...)
$url       = $modx->makeUrl($newsResource->get('id'), '', '', $modx->getOption('link_tag_scheme'));

// publishedon può arrivare come timestamp o come stringa datetime: gestiamo entrambi
$publishedOn   = $newsResource->get('publishedon');
$ts            = is_numeric($publishedOn) ? (int) $publishedOn : strtotime((string) $publishedOn);
$formattedDate = $ts ? date('d-m-Y', $ts) : '';

// Immagine: override della pagina → immagine della news → placeholder
$headerImage = trim((string) $modx->resource->getTVValue('headerNewsImg'));
if ($headerImage === '') {
    $headerImage = trim((string) $newsResource->getTVValue('infoImage'));
}
if ($headerImage === '') {
    $headerImage = $placeholder;
}

// Percorso assoluto (/assets/...), salvo URL esterni
if (!preg_match('#^https?://#i', $headerImage)) {
    $headerImage = '/' . ltrim($headerImage, '/');
}

$img = $e($headerImage);

// Output
$output = '<section id="articolo-evidenza" class="order-lg-1 container-fluid px-0">
    <div id="articolo-evidenza-img">
        <picture>
            <source media="(min-width:769px)" srcset="' . $img . '">
            <source media="(max-width:768px)" srcset="' . $img . '">
            <img src="' . $img . '" alt="' . $e($title) . '">
        </picture>
        <div class="image-overlay d-none d-lg-block"></div>
    </div>

    <div class="container" id="articolo-evidenza-info">
        <div id="articolo-evidenza-txt" class="col-lg-6 px-0">
            <p class="d-lg-none">IN EVIDENZA</p>
            <h2>' . $e($title) . '</h2>
            <p class="text-white d-none d-sm-block">' . $e($introtext) . '</p>
        </div>
        <div id="articolo-evidenza-btns" class="px-0">
            ' . ($formattedDate !== '' ? '<small>' . $formattedDate . '</small>' : '') . '
            <a class="btn btn-primary" href="' . $e($url) . '">SCOPRI DI PIÙ</a>
        </div>
    </div>
</section>';

return $output;