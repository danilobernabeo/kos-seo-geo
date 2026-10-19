<?php
/* Il singolo comunicato non è una pagina: rimanda al PDF, o all'elenco se il PDF manca. */
$pdf = trim((string) $modx->resource->getTVValue('comunicatoPdf'));
if ($pdf !== '' && !preg_match('#^https?://#i', $pdf)) {
    $pdf = rtrim($modx->getOption('site_url'), '/') . '/' . ltrim($pdf, '/');
}
$url = $pdf !== '' ? $pdf : $modx->makeUrl(33, '', '', 'full');
$modx->sendRedirect($url, ['responseCode' => 'HTTP/1.1 301 Moved Permanently']);