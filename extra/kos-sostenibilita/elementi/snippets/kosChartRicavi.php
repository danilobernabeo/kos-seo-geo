<?php
/**
 * Snippet kosChartRicavi — JSON per il grafico "Ricavi"
 * Uso: [[kosChartRicavi? &resource=`54`]]
 *   &tv        nome TV MIGX              (default: chartRicavi)
 *   &resource  ID risorsa con i dati     (default: risorsa corrente)
 *   &years     anni da mostrare          (default: 5)
 *   &yMax      valore minimo della scala (default: 900, in milioni)
 *   &debug     1 = mostra cosa legge (solo per diagnosi, con chiamata [[!...]])
 */
$tv     = $modx->getOption('tv', $scriptProperties, 'chartRicavi');
$resId  = (int) $modx->getOption('resource', $scriptProperties, $modx->resource->get('id'));
$years  = max(1, (int) $modx->getOption('years', $scriptProperties, 5));
$yMax   = (int) $modx->getOption('yMax', $scriptProperties, 900);

$res = ($resId === (int) $modx->resource->get('id')) ? $modx->resource : $modx->getObject(\MODX\Revolution\modResource::class, $resId);
if (!$res) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, "[kosChartRicavi] Risorsa $resId non trovata");
    return '{}';
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
return json_encode($out, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
