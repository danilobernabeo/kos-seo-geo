<?php
/**
 * Snippet kosChartDipendenti — JSON per il grafico "I nostri dipendenti"
 * Uso: [[kosChartDipendenti? &resource=`54`]]   (chiamata cacheable, senza !)
 *   &tv        nome TV MIGX                 (default: chartDipendenti)
 *   &resource  ID risorsa con i dati        (default: risorsa corrente)
 *   &years     anni da mostrare             (default: 5)
 *   &yMax      valore minimo della scala Y  (default: 15000)
 *   &debug     1 = mostra cosa legge (solo per diagnosi, con chiamata [[!...]])
 * "Tutti" = ITA + GER, calcolato qui: non va inserito a mano.
 */
$tv     = $modx->getOption('tv', $scriptProperties, 'chartDipendenti');
$resId  = (int) $modx->getOption('resource', $scriptProperties, $modx->resource->get('id'));
$years  = max(1, (int) $modx->getOption('years', $scriptProperties, 5));
$yMax   = (int) $modx->getOption('yMax', $scriptProperties, 15000);

$res = ($resId === (int) $modx->resource->get('id'))
    ? $modx->resource
    : $modx->getObject(\MODX\Revolution\modResource::class, $resId);
if (!$res) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, "[kosChartDipendenti] Risorsa $resId non trovata");
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

$n   = fn($v) => ($v === '' || $v === null) ? null : (int) preg_replace('/\D/', '', (string) $v);
$map = ['total' => 'totale', 'women' => 'donne', 'men' => 'uomini'];
$out = ['yMax' => $yMax, 'yStep' => 3000, 'years' => [], 'series' => [
    'tutti' => ['label' => 'Tutti'], 'ita' => ['label' => 'ITA'], 'ger' => ['label' => 'GER'],
]];

foreach ($rows as $r) {
    $out['years'][] = (int) $r['anno'];
    foreach ($map as $k => $f) {
        $i = $n($r["{$f}_ita"] ?? null);
        $g = $n($r["{$f}_ger"] ?? null);
        $out['series']['ita'][$k][]   = $i;
        $out['series']['ger'][$k][]   = $g;
        $out['series']['tutti'][$k][] = ($i === null || $g === null) ? null : $i + $g;
    }
}
return json_encode($out, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
