<?php
/**
 * ImmaginiCaricamento — AA-17 immagini pesanti: ottimizza le immagini appena caricate dal gestore file di MODX
 * (anche dall'editor) e ne crea le varianti WebP per lo snippet `immagine`.
 * Evento: OnFileManagerUpload. Libreria: core/components/kosseogeo/Immagini.php.
 *
 * Per ogni JPG/PNG della cartella di destinazione modificato negli ultimi 5 minuti (il nome salvato può differire da
 * quello caricato per la traslitterazione): lato massimo kosseogeo.img_max_lato (default 2400), ricompressione,
 * varianti. L'originale caricato non viene conservato: chi carica ha già il file sul proprio computer.
 */
if ($modx->event->name !== 'OnFileManagerUpload') return;
$lib = MODX_CORE_PATH . 'components/kosseogeo/Immagini.php';
if (!is_file($lib)) return;
require_once $lib;
$img = new \KosSeoGeo\Immagini(MODX_BASE_PATH, [
    'cache'    => $modx->getOption('kosseogeo.img_cache', null, 'assets/cache-img'),
    'max_lato' => (int) $modx->getOption('kosseogeo.img_max_lato', null, 2400),
    'q_jpg'    => (int) $modx->getOption('kosseogeo.img_q_jpg', null, 82),
    'q_webp'   => (int) $modx->getOption('kosseogeo.img_q_webp', null, 80),
]);
if (!$img->disponibile()) return;

$source = $modx->event->params['source'] ?? null;
$dir = (string) ($modx->event->params['directory'] ?? '');
if (!is_object($source) || !method_exists($source, 'getBasePath')) return;
$base = (string) $source->getBasePath();
if ($base !== '' && $base[0] !== '/') $base = MODX_BASE_PATH . $base;
$cartella = realpath(rtrim($base, '/') . '/' . trim($dir, '/'));
if ($cartella === false || !str_starts_with($cartella, rtrim((string) realpath(MODX_BASE_PATH), '/') . '/')) return;

$soglia = time() - 300;
foreach (glob($cartella . '/*.{jpg,jpeg,JPG,JPEG,png,PNG}', GLOB_BRACE) ?: [] as $f) {
    if (filemtime($f) < $soglia) continue;
    $file = $img->percorso(substr($f, strlen(rtrim((string) realpath(MODX_BASE_PATH), '/'))));
    if ($file === null) continue;
    try {
        $r = $img->ottimizza($file, null, true);
        $img->varianti($file);
        $modx->log(modX::LOG_LEVEL_INFO, sprintf('[ImmaginiCaricamento] %s: %s %s, %d → %d KB',
            $file, $r['azione'], $r['lati'], $r['prima'] / 1024, $r['dopo'] / 1024));
    } catch (\Throwable $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[ImmaginiCaricamento] ' . $file . ': ' . $e->getMessage());
    }
}
