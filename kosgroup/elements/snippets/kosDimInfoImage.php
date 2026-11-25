<?php
/**
 * kosDimInfoImage — KOS 15 (25/11/2026): restituisce ` width="W" height="H"` dell'immagine della TV infoImage della risorsa,
 * letti dal file, per l'immagine di testata dei template informativo e tecnologie. Con le dimensioni scritte il browser
 * riserva lo spazio prima che l'immagine arrivi (img{max-width:100%;height:auto} nel foglio): senza, con la prima
 * visualizzazione anticipata dal CSS critico, titolo e riquadri sotto la foto scendevano all'arrivo dell'immagine (CLS
 * fino a 0,26 nelle prove da mobile). Nessun parametro (si usa dentro il filtro :notempty della TV, dove i backtick di
 * una proprietà non si possono annidare). Chiamata in cache. Immagine esterna, file mancante o non leggibile: stringa vuota.
 */
$res = $modx->resource;
if (!$res) return '';
$src = trim((string) $res->getTVValue('infoImage'));
if ($src === '' || preg_match('#^(?:[a-z]+:)?//#i', $src)) return '';
$percorso = rawurldecode((string) parse_url($src, PHP_URL_PATH));
if ($percorso === '' || strpos($percorso, '..') !== false) return '';
$file = rtrim(MODX_BASE_PATH, '/') . '/' . ltrim($percorso, '/');
if (!is_file($file)) return '';
$d = @getimagesize($file);
if (!$d || empty($d[0]) || empty($d[1])) return '';
return ' width="' . (int) $d[0] . '" height="' . (int) $d[1] . '"';
