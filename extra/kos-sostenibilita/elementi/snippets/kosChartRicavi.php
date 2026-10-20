<?php
/**
 * Snippet kosChartRicavi — dati del grafico "Ricavi"
 * Uso (chiamate cacheable, senza !):
 *   [[kosChartRicavi? &resource=`54`]]                              JSON per kos-ricavi.js
 *   [[kosChartRicavi? &resource=`54` &output=`table` &lang=`it`]]   tabella HTML (righe e didascalia)
 *   &tv        nome TV MIGX              (default: chartRicavi)
 *   &resource  ID risorsa con i dati     (default: risorsa corrente)
 *   &years     anni da mostrare          (default: 5)
 *   &yMax      valore minimo della scala (default: 900, in milioni)
 *   &output    json | table              (default: json)
 *   &lang      it | en, per la tabella   (default: it)
 *   &debug     1 = mostra cosa legge (solo per diagnosi, con chiamata [[!...]])
 * La tabella è nell'HTML servito: i valori sono leggibili anche da chi non esegue JavaScript.
 */
$tv     = $modx->getOption('tv', $scriptProperties, 'chartRicavi');
$resId  = (int) $modx->getOption('resource', $scriptProperties, $modx->resource->get('id'));
$years  = max(1, (int) $modx->getOption('years', $scriptProperties, 5));
$yMax   = (int) $modx->getOption('yMax', $scriptProperties, 900);
$output = $modx->getOption('output', $scriptProperties, 'json');
$lang   = $modx->getOption('lang', $scriptProperties, 'it') === 'en' ? 'en' : 'it';

$res = ($resId === (int) $modx->resource->get('id')) ? $modx->resource : $modx->getObject(\MODX\Revolution\modResource::class, $resId);
if (!$res) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, "[kosChartRicavi] Risorsa $resId non trovata");
    return $output === 'table' ? '' : '{}';
}
if ($modx->getOption('debug', $scriptProperties, false)) {
    return 'DEBUG tv=' . $tv . ' | risorsa=' . $res->get('id') . ' (' . $res->get('pagetitle') . ', contesto ' . $res->get('context_key') . ')'
         . ' | valore TV=' . var_export($res->getTVValue($tv), true);
}

$rows = json_decode($res->getTVValue($tv) ?: '[]', true) ?: [];
$rows = array_filter($rows, fn($r) => !empty($r['anno']));
usort($rows, fn($a, $b) => (int) $a['anno'] <=> (int) $b['anno']);
$rows = array_slice($rows, -$years);

$out = ['yMax' => $yMax, 'yStep' => 150, 'years' => [], 'values' => []];
foreach ($rows as $r) {
    $v = trim((string) ($r['valore'] ?? ''));
    $out['years'][]  = (int) $r['anno'];
    // accetta "812", "812,5" o "812.5" (milioni di euro)
    $out['values'][] = ($v === '') ? null : (float) str_replace(',', '.', preg_replace('/[^\d,.]/', '', $v));
}

if ($output !== 'table') {
    return json_encode($out, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

$T = $lang === 'en'
    ? ['cap' => 'KOS Group revenues by year (EUR million)', 'y' => 'Year', 'col' => 'Revenues']
    : ['cap' => 'Ricavi del Gruppo KOS per anno (milioni di euro)', 'y' => 'Anno', 'col' => 'Ricavi'];
$f = function ($v) use ($lang) {
    $dec = floor($v) == $v ? 0 : 1;
    return $lang === 'en' ? '€' . number_format($v, $dec, '.', ',') . 'M' : number_format($v, $dec, ',', '.') . 'M €';
};
$html = "<caption>{$T['cap']}</caption><tr><th scope=\"col\">{$T['y']}</th><th scope=\"col\">{$T['col']}</th></tr>";
foreach ($out['years'] as $i => $y) {
    if ($out['values'][$i] === null) continue;
    $html .= "<tr><th scope=\"row\">$y</th><td>{$f($out['values'][$i])}</td></tr>";
}
return $html;
