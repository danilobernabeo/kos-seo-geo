<?php
/**
 * docList - elenco dei documenti di una cartella (es. assets/doc/strutture/[[*alias]]/)
 *
 * &input  cartella relativa alla root del sito (o percorso assoluto sul server)
 *
 * Modifiche 21/10/2026:
 *  - href assoluto ("/assets/doc/...") invece che relativo: niente URL annidati
 *    per i crawler che ignorano <base href>
 *  - nomi file codificati nell'URL (spazi -> %20, accenti, apostrofi)
 *  - funziona anche se &input e' un percorso assoluto del server
 *  - salta sottocartelle; ordina per nome file prima di generare l'HTML
 */

// 1. $input obbligatorio
$input = trim((string) $modx->getOption('input', $scriptProperties, isset($input) ? $input : ''));
if ($input === '') {
    $modx->log(modX::LOG_LEVEL_WARN, '[Snippet docList] Parametro $input mancante o vuoto.');
    return '';
}

// 2. Percorso relativo alla root del sito (per l'URL) e assoluto (per il filesystem)
$basePath = rtrim(MODX_BASE_PATH, '/') . '/';
if (strpos($input, $basePath) === 0) {
    $relDir = substr($input, strlen($basePath));
} else {
    $relDir = $input;
}
$relDir  = trim($relDir, '/');
$absPath = $basePath . $relDir;

// 3. La cartella deve esistere
if (!is_dir($absPath)) {
    $modx->log(modX::LOG_LEVEL_WARN, '[Snippet docList] Directory non trovata: ' . $absPath);
    return '';
}

// 4. Legge i file (salta . .. file nascosti e sottocartelle)
$files = array();
if ($handle = opendir($absPath)) {
    while (false !== ($file = readdir($handle))) {
        if ($file === '' || $file[0] === '.') continue;
        if (!is_file($absPath . '/' . $file)) continue;
        $files[] = $file;
    }
    closedir($handle);
}
if (!$files) return '';
natcasesort($files);

// 5. URL assoluto e codificato: /assets/doc/strutture/x/Carta%20dei%20servizi.pdf
$enc = function ($path) {
    return implode('/', array_map('rawurlencode', explode('/', $path)));
};

$thelist = array();
foreach ($files as $file) {
    $href = '/' . $enc($relDir . '/' . $file);
    $name = pathinfo($file, PATHINFO_FILENAME);
    $ext  = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
    $nameHtml = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    $thelist[] = '
<tr>
  <th scope="row" class="py-3">
    <i class="ki ki-terapie-scarica-file text-azure"></i>
  </th>
  <td class="py-3">' . $nameHtml . '</td>
  <td class="text-end py-3">
    <a class="d-flex justify-content-center align-items-center"
       href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"
       target="_blank" rel="noopener noreferrer">
      <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
      <span class="visually-hidden">Scarica ' . $nameHtml . ($ext !== '' ? ' (' . $ext . ')' : '') . '</span>
      <span class="ps-2 d-none d-lg-inline-block font-weight-600 fs-6">Scarica Documento</span>
    </a>
  </td>
</tr>';
}

return implode("\n", $thelist);