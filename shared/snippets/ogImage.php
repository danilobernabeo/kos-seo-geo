<?php
/**
 * ogImage — immagine per la condivisione sui social (og:image), comune a kosgroup.com e anniazzurri.it.
 * Nato su kosgroup.com (ottobre 2026); versione comune con le opzioni &meta e &versione, compatibile con la precedente.
 *
 * Chiamata SEMPRE in cache (senza «!»): il risultato fa parte della cache della pagina.
 *
 * Prova in ordine e usa la prima immagine che esiste davvero sul server:
 *   1. le TV indicate in &tvs (separate da virgola)
 *   2. i percorsi indicati in &legacy (separati da |):
 *        - un file:     assets/img/header/[[*alias]].jpg
 *        - una cartella (termina con /): la prima immagine in ordine alfabetico
 *   3. &default (se manca: impostazione di sistema seo.og_image_default, poi assets/img/kos-condivisione-social.jpg)
 *
 * &meta=`1`      restituisce i tag completi: og:image, og:image:width, og:image:height, og:image:type (dimensioni lette
 *                dal file, non scritte a mano); senza &meta restituisce solo l'URL (uso storico: content="[[ogImage?...]]")
 * &versione=`1`  aggiunge ?v=<data di modifica del file>, così i social rileggono l'immagine quando cambia
 *
 * Esempi:
 *   <meta property="og:image" content="[[ogImage? &tvs=`infoImage` &legacy=`assets/img/header/[[*alias]].jpg`]]">
 *   [[ogImage? &tvs=`newsImage` &default=`assets/img/anni-azzurri-condivisione-social.jpg` &meta=`1` &versione=`1`]]
 */
$tvs      = array_filter(array_map('trim', explode(',', (string) $modx->getOption('tvs', $scriptProperties, ''))));
$legacy   = array_filter(array_map('trim', explode('|', (string) $modx->getOption('legacy', $scriptProperties, ''))));
$default  = trim((string) $modx->getOption('default', $scriptProperties, ''));
$meta     = !empty($modx->getOption('meta', $scriptProperties, ''));
$versione = !empty($modx->getOption('versione', $scriptProperties, ''));
if ($default === '') $default = (string) $modx->getOption('seo.og_image_default', null, 'assets/img/kos-condivisione-social.jpg');

$base = rtrim($modx->getOption('site_url'), '/') . '/';
$root = rtrim($modx->getOption('base_path'), '/') . '/';
$host = parse_url($base, PHP_URL_HOST);
$nudo = fn($h) => preg_replace('/^www\./i', '', (string) $h);
$enc  = fn($p) => implode('/', array_map('rawurlencode', explode('/', $p)));
$e    = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$candidates = [];
if ($modx->resource) foreach ($tvs as $tv) $candidates[] = trim((string) $modx->resource->getTVValue($tv));
$candidates = array_merge($candidates, $legacy, [$default]);

$trovato = null;     // percorso relativo del file trovato
$esterna = null;     // URL esterno usato così com'è
foreach ($candidates as $p) {
    if ($p === '') continue;
    if (preg_match('#^https?://#i', $p)) {
        $u = parse_url($p);
        if (!empty($u['host']) && strcasecmp($nudo($u['host']), $nudo($host)) !== 0) { $esterna = $p; break; }
        $p = $u['path'] ?? '';
    }
    $p = ltrim(rawurldecode(preg_replace('/[?#].*$/', '', $p)), '/');
    if ($p === '' || str_contains($p, '..')) continue;
    if (substr($p, -1) === '/') {                       // cartella: prima immagine
        $files = glob($root . $p . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        sort($files);
        if ($files) { $trovato = $p . basename($files[0]); break; }
        continue;
    }
    if (is_file($root . $p)) { $trovato = $p; break; }
}

if ($esterna !== null) {
    return $meta ? '<meta property="og:image" content="' . $e($esterna) . '">' : $esterna;
}
if ($trovato === null) $trovato = ltrim($default, '/');   // anche se il file manca: meglio di nessun valore
$file = $root . $trovato;
$url = $base . $enc($trovato) . ($versione && is_file($file) ? '?v=' . filemtime($file) : '');
if (!$meta) return $url;

$out = '<meta property="og:image" content="' . $e($url) . '">';
$dim = is_file($file) ? @getimagesize($file) : false;
if ($dim) {
    $out .= "\n    " . '<meta property="og:image:width" content="' . (int) $dim[0] . '">'
          . "\n    " . '<meta property="og:image:height" content="' . (int) $dim[1] . '">'
          . (!empty($dim['mime']) ? "\n    " . '<meta property="og:image:type" content="' . $e($dim['mime']) . '">' : '');
}
return $out;
