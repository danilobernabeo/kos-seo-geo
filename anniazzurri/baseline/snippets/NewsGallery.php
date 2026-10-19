<?php
/** NewsGallery
 * Legge le immagini da assets/img/news/{id}/ e stampa una griglia Bootstrap.
 *
 * Parametri:
 *  - id (int)                : ID della news. Default: [[*id]]
 *  - baseFolder (string)     : Cartella base. Default: assets/img/news/
 *  - colClass (string)       : Classi colonna. Default: col-12 col-md-6
 *  - imgClass (string)       : Classi img. Default: img-fluid rounded-5
 *  - rowClass (string)       : Classi wrapper row. Default: row g-2 py-4
 *  - exts (string)           : Estensioni consentite. Default: jpg,jpeg,png,gif,webp
 *  - altFromFilename (int)   : 1 = genera alt dal filename; 0 = alt vuoto. Default: 0
 *  - limit (int)             : Numero max immagini (0 = nessun limite). Default: 0
 */

$id  = (int)($modx->getOption('id', $scriptProperties, (int)$modx->resource->get('id')));
$baseFolder = rtrim($modx->getOption('baseFolder', $scriptProperties, 'assets/img/news/'), '/') . '/';
$colClass   = $modx->getOption('colClass', $scriptProperties, 'col-12 col-md-6');
$imgClass   = $modx->getOption('imgClass', $scriptProperties, 'img-fluid rounded-5');
$rowClass   = $modx->getOption('rowClass', $scriptProperties, 'row g-2 py-4');
$exts       = strtolower($modx->getOption('exts', $scriptProperties, 'jpg,jpeg,png,gif,webp'));
$altFrom    = (int)$modx->getOption('altFromFilename', $scriptProperties, 0);
$limit      = (int)$modx->getOption('limit', $scriptProperties, 0);

// Sanitizza id ed evita path traversal
if ($id <= 0) return '';

$relDir  = $baseFolder . $id . '/';
$absDir  = MODX_BASE_PATH . $relDir;
$baseUrl = MODX_BASE_URL . $relDir;

if (!is_dir($absDir)) {
    // Nessuna cartella → nessun output (o ritorna commento HTML se preferisci)
    return '';
}

// Costruisce il pattern GLOB con estensioni
$extArr = array_filter(array_map('trim', explode(',', $exts)));
$brace  = '{' . implode(',', array_map(function($e){ return '*.' . $e; }, $extArr)) . '}';

// Leggi file e ordina “naturalmente”
$files = glob($absDir . $brace, GLOB_BRACE);
if (!$files) return '';

natsort($files);
$files = array_values($files);

// Applica limit se richiesto
if ($limit > 0) {
    $files = array_slice($files, 0, $limit);
}

$out = [];
$out[] = '<div class="' . htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') . '">';

foreach ($files as $path) {
    $filename = basename($path);
    $src = $baseUrl . rawurlencode($filename);

    // Alt
    if ($altFrom === 1) {
        $nameNoExt = preg_replace('/\.[^.]+$/', '', $filename);
        $alt = trim(preg_replace('/[\-_]+/',' ', $nameNoExt));
        // normalizza: la-radio-on-the-road → "La radio on the road"
        $alt = mb_convert_case($alt, MB_CASE_TITLE, 'UTF-8');
    } else {
        $alt = '';
    }

    $out[] = '  <div class="' . htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') . '"><img class="' . htmlspecialchars($imgClass, ENT_QUOTES, 'UTF-8') . '" src="' . $src . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"></div>';
}

$out[] = '</div>';

return implode("\n", $out);