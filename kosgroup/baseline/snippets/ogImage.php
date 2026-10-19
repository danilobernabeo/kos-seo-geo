<?php
/**
 * ogImage - URL assoluto e codificato dell'immagine per og:image.
 *
 * Prova in ordine, e restituisce la prima che esiste davvero sul server:
 *   1. le TV indicate in &tvs (separate da virgola)
 *   2. i percorsi indicati in &legacy (separati da |):
 *        - un file:     assets/img/header/[[*alias]].jpg
 *        - una cartella (termina con /): la prima immagine in ordine alfabetico,
 *          la stessa che la galleria mostra per prima
 *   3. &default (se manca: assets/img/kos-condivisione-social.jpg)
 *
 * Esempio (schede strutture):
 * [[ogImage? &tvs=`infoImage` &legacy=`assets/img/header/[[*alias]].jpg|assets/img/strutture/[[*alias]]/` &default=`assets/img/kos-condivisione-social.jpg`]]
 */
$tvs     = array_filter(array_map('trim', explode(',', (string) $modx->getOption('tvs', $scriptProperties, ''))));
$legacy  = array_filter(array_map('trim', explode('|', (string) $modx->getOption('legacy', $scriptProperties, ''))));
$default = trim((string) $modx->getOption('default', $scriptProperties, ''));
if ($default === '') $default = 'assets/img/kos-condivisione-social.jpg';

$base = rtrim($modx->getOption('site_url'), '/') . '/';
$root = rtrim($modx->getOption('base_path'), '/') . '/';
$host = parse_url($base, PHP_URL_HOST);
$enc  = function ($p) { return implode('/', array_map('rawurlencode', explode('/', $p))); };

$candidates = [];
if ($modx->resource) foreach ($tvs as $tv) $candidates[] = trim((string) $modx->resource->getTVValue($tv));
$candidates = array_merge($candidates, $legacy);

foreach ($candidates as $p) {
    if ($p === '') continue;
    if (preg_match('#^https?://#i', $p)) {
        $u = parse_url($p);
        if (!empty($u['host']) && strcasecmp($u['host'], $host) !== 0) return $p;   // immagine esterna: com'e'
        $p = $u['path'] ?? '';
    }
    $p = ltrim(rawurldecode($p), '/');
    if ($p === '') continue;

    if (substr($p, -1) === '/') {                       // cartella: prima immagine
        $files = glob($root . $p . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        sort($files);
        if ($files) return $base . $enc($p . basename($files[0]));
        continue;
    }
    if (is_file($root . $p)) return $base . $enc($p);
}
return $base . $enc(ltrim($default, '/'));