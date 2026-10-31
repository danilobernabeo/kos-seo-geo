<?php
/**
 * newsMubaImmagine — immagine di testata della news Anni Azzurri e MUBA.
 *
 * Passa tutto allo snippet `immagine` del progetto (picture, WebP, srcset) se è installato; altrimenti,
 * per esempio in produzione prima del rilascio della libreria immagini, scrive un <img> semplice.
 * Stessi parametri di `immagine`.
 */
$src = trim((string) ($src ?? ''));
if ($src === '') return '';
if ($modx->getObject(\MODX\Revolution\modSnippet::class, ['name' => 'immagine'])) {
    return $modx->runSnippet('immagine', $scriptProperties);
}
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$file = MODX_BASE_PATH . ltrim(parse_url($src, PHP_URL_PATH) ?? '', '/');
$dim = is_file($file) ? @getimagesize($file) : false;
return '<img src="' . $e('/' . ltrim($src, '/')) . '" alt="' . $e($alt ?? '') . '"'
    . (!empty($class) ? ' class="' . $e($class) . '"' : '')
    . ($dim ? ' width="' . $dim[0] . '" height="' . $dim[1] . '"' : '')
    . (!empty($priorita) ? ' fetchpriority="high"' : ' loading="lazy"') . '>';