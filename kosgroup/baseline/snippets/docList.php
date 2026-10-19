<?php
/**
 * listFolderFiles — elenca i FILE (non le directory) di una cartella.
 *
 * &input      string  path relativo a MODX_BASE_PATH o assoluto interno   [required]
 * &extensions string  whitelist CSV, es. "pdf,doc,docx" (vuoto = tutte)
 * &tpl        string  chunk per la riga; se assente usa il markup inline
 * &cacheTtl   int     secondi di cache (0 = disattivata)
 * &emptyTpl   string  chunk mostrato quando non ci sono file
 *
 * NB: negli snippet MODX il codice è eval'd → niente "use" statements.
 *     LOG_LEVEL_WARN = 2 (literal, per non dipendere dal compat layer).
 */

const LFF_LOG_WARN = 2;

$input = trim((string) $modx->getOption('input', $scriptProperties, ''));
if ($input === '') {
    $modx->log(LFF_LOG_WARN, '[listFolderFiles] Parametro &input mancante.');
    return '';
}

/* ---------- normalizzazione path, separatori uniformi ---------- */
$norm     = static fn(string $p): string => rtrim(str_replace('\\', '/', $p), '/');
$baseReal = $norm((string) realpath(MODX_BASE_PATH));

$candidate = (strpos($norm($input), $baseReal) === 0)
    ? $input
    : $baseReal . '/' . ltrim($input, '/');

$absReal = realpath($candidate);
if ($absReal === false) {
    $modx->log(LFF_LOG_WARN, '[listFolderFiles] Path inesistente: ' . $candidate);
    return '';
}
$absReal = $norm($absReal);

/* hardening: confronto CON separatore in coda → niente false positivi su /villa-staging */
if (strpos($absReal . '/', $baseReal . '/') !== 0 || !is_dir($absReal)) {
    $modx->log(LFF_LOG_WARN, '[listFolderFiles] Path fuori da MODX_BASE_PATH: ' . $absReal);
    return '';
}

$webBase = trim(substr($absReal, strlen($baseReal)), '/');

/* ---------- opzioni ---------- */
$allowed = array_values(array_filter(array_map(
    static fn($e) => strtolower(trim($e)),
    explode(',', (string) $modx->getOption('extensions', $scriptProperties, ''))
)));

$tpl      = (string) $modx->getOption('tpl', $scriptProperties, '');
$emptyTpl = (string) $modx->getOption('emptyTpl', $scriptProperties, '');
$ttl      = (int)    $modx->getOption('cacheTtl', $scriptProperties, 0);

/* pagetitle risolto qui: non dipende dal secondo giro del parser */
$pagetitle = $modx->resource ? (string) $modx->resource->get('pagetitle') : '';

/* ---------- cache opzionale, invalidata dalla mtime della directory ---------- */
clearstatcache(true, $absReal);
$cacheKey = null;
if ($ttl > 0) {
    $cacheKey = 'listFolderFiles/' . md5(implode('|', [
        $absReal, (string) filemtime($absReal), implode(',', $allowed), $tpl, $pagetitle,
    ]));
    $hit = $modx->cacheManager->get($cacheKey);
    if ($hit !== null) {
        return $hit;
    }
}

/* ---------- scansione ---------- */
$items = [];

$it = new FilesystemIterator(
    $absReal,
    FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
);

foreach ($it as $info) {
    /** @var SplFileInfo $info */

    // il punto della domanda: solo file regolari
    if (!$info->isFile() || !$info->isReadable()) {
        continue;
    }

    $name = $info->getFilename();
    if ($name === '' || $name[0] === '.') {
        continue;
    }

    $ext = strtolower($info->getExtension());
    if ($allowed && !in_array($ext, $allowed, true)) {
        continue;
    }

    $stem        = $ext !== '' ? $info->getBasename('.' . $info->getExtension()) : $name;
    $displayName = ucfirst(str_replace(['-', '_'], ' ', $stem));

    /* encoding per-segmento: preserva gli slash, codifica spazi e accentate
       anche nelle directory intermedie */
    $segments = $webBase !== '' ? explode('/', $webBase) : [];
    $segments[] = $name;
    $href = MODX_BASE_URL . implode('/', array_map('rawurlencode', $segments));

    $bytes   = (int) $info->getSize();
    $sizeKB  = (int) round($bytes / 1024);           // 0 KB resta 0 KB: niente bugie
    $ariaLbl = trim(sprintf(
        'Scarica il documento %s %s (%s, %d KB)',
        $displayName, $pagetitle, strtoupper($ext !== '' ? $ext : 'FILE'), $sizeKB
    ));

    $props = [
        'name'     => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
        'href'     => htmlspecialchars($href,        ENT_QUOTES, 'UTF-8'),
        'aria'     => htmlspecialchars($ariaLbl,     ENT_QUOTES, 'UTF-8'),
        'filename' => htmlspecialchars($name,        ENT_QUOTES, 'UTF-8'),
        'ext'      => strtoupper($ext),
        'sizeKB'   => $sizeKB,
        'sizeRaw'  => $bytes,
        'idx'      => 0,
    ];

    /* lista piatta: nessuna chiave → nessuna collisione tra capitolato.pdf e capitolato.docx */
    $items[] = [
        'sort'  => $name,
        'props' => $props,
    ];
}

if (!$items) {
    $out = $emptyTpl ? $modx->getChunk($emptyTpl, ['path' => $webBase]) : '';
    if ($cacheKey) {
        $modx->cacheManager->set($cacheKey, $out, $ttl);
    }
    return $out;
}

/* ordinamento naturale, case-insensitive, sul NOME FILE */
usort($items, static fn(array $a, array $b) => strnatcasecmp($a['sort'], $b['sort']));

$rows = [];
foreach ($items as $i => $item) {
    $p = $item['props'];
    $p['idx'] = $i;

    $rows[] = $tpl ? $modx->getChunk($tpl, $p) : '
<div class="row documento pb-3">
  <div class="col-10 col-md-6 documento-nome d-flex align-items-center">
    <img src="' . MODX_BASE_URL . 'assets/img/document.svg"
         alt="" role="presentation" width="20" height="20" class="me-2">
    ' . $p['name'] . '
  </div>
  <div class="col-2 ps-0 col-md-6 documento-cta">
    <a href="' . $p['href'] . '" class="btn-link btn-link-u"
       target="_blank" rel="noopener"
       aria-label="' . $p['aria'] . '">
        Scarica documento
        <img src="' . MODX_BASE_URL . 'assets/img/download.svg"
             alt="" aria-hidden="true" width="20" height="20">
    </a>
  </div>
</div>';
}

$out = implode("\n", $rows);

if ($cacheKey) {
    $modx->cacheManager->set($cacheKey, $out, $ttl);
}

return $out;