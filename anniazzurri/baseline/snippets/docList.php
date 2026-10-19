<?php
// FIX 1: verifica che $input sia valorizzato
if (empty($input)) {
    $modx->log(modX::LOG_LEVEL_WARN, '[Snippet docList] Parametro $input mancante o vuoto.');
    return '';
}

// FIX 2: costruisci sempre il path assoluto
if (strpos($input, MODX_BASE_PATH) === false) {
    $absPath = rtrim(MODX_BASE_PATH, '/') . '/' . ltrim($input, '/');
} else {
    $absPath = $input;
}

// FIX 3: verifica che la directory esista prima di aprirla
if (!is_dir($absPath)) {
    $modx->log(modX::LOG_LEVEL_WARN, '[Snippet docList] Directory non trovata: ' . $absPath);
    return '';
}

$thelist = array();

if ($handle = opendir($absPath)) {
    while (false !== ($file = readdir($handle))) {
        // FIX 4: salta . e .. e file nascosti
        if ($file === '.' || $file === '..' || strpos($file, '.') === 0) {
            continue;
        }
        $filePath    = $absPath . '/' . $file;
        $filePathWeb = rtrim($input, '/') . '/' . $file; // path relativo per href
        $name        = pathinfo($file, PATHINFO_FILENAME);

        $thelist[] = '
<tr>
  <th scope="row" class="py-3">
    <i class="ki ki-terapie-scarica-file text-azure"></i>
  </th>
  <td class="py-3">' . htmlspecialchars($name) . '</td>
  <td class="text-end py-3">
    <a class="d-flex justify-content-center align-items-center"
       href="' . $filePathWeb . '"
       target="_blank" rel="noopener noreferrer">
      <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
      <span class="visually-hidden">Scarica ' . htmlspecialchars($name) . ' (PDF)</span>
      <span class="ps-2 d-none d-lg-inline-block font-weight-600 fs-6">Scarica Documento</span>
    </a>
  </td>
</tr>';
    }
    closedir($handle);
}

sort($thelist);

// FIX 5: usa return invece di echo
return implode("\n", $thelist);