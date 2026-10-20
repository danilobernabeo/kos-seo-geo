<?php
/**
 * Snippet kosChartDipendenti — dati del grafico "I nostri dipendenti"
 * Uso (chiamate cacheable, senza !):
 *   [[kosChartDipendenti? &resource=`54`]]                              JSON per kos-chart.js
 *   [[kosChartDipendenti? &resource=`54` &output=`table` &lang=`it`]]   tabella HTML (righe e didascalia)
 *   &tv        nome TV MIGX                 (default: chartDipendenti)
 *   &resource  ID risorsa con i dati        (default: risorsa corrente)
 *   &years     anni da mostrare             (default: 5)
 *   &yMax      valore minimo della scala Y  (default: 15000)
 *   &output    json | table                 (default: json)
 *   &lang      it | en, per la tabella      (default: it)
 *   &debug     1 = mostra cosa legge (solo per diagnosi, con chiamata [[!...]])
 * "Tutti" = ITA + GER, calcolato qui: non va inserito a mano.
 * La tabella è nell'HTML servito: i numeri sono leggibili anche da chi non esegue JavaScript
 * (crawler, assistenti AI, lettori di schermo prima dell'avvio dello script).
 */
$tv     = $modx->getOption('tv', $scriptProperties, 'chartDipendenti');
$resId  = (int) $modx->getOption('resource', $scriptProperties, $modx->resource->get('id'));
$years  = max(1, (int) $modx->getOption('years', $scriptProperties, 5));
$yMax   = (int) $modx->getOption('yMax', $scriptProperties, 15000);
$output = $modx->getOption('output', $scriptProperties, 'json');
$lang   = $modx->getOption('lang', $scriptProperties, 'it') === 'en' ? 'en' : 'it';

$res = ($resId === (int) $modx->resource->get('id'))
    ? $modx->resource
    : $modx->getObject(\MODX\Revolution\modResource::class, $resId);
if (!$res) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, "[kosChartDipendenti] Risorsa $resId non trovata");
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

if ($output !== 'table') {
    return json_encode($out, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

// Tabella completa: totale del gruppo, Italia, Germania, donne e uomini per anno.
$T = $lang === 'en'
    ? ['cap' => 'KOS Group employees by year (at 31 December)', 'y' => 'Year', 'tot' => 'Total', 'ita' => 'Italy', 'ger' => 'Germany', 'w' => 'Female', 'm' => 'Male', 'sep' => ',']
    : ['cap' => 'Dipendenti del Gruppo KOS per anno (al 31 dicembre)', 'y' => 'Anno', 'tot' => 'Totale', 'ita' => 'Italia', 'ger' => 'Germania', 'w' => 'Donne', 'm' => 'Uomini', 'sep' => '.'];
$f = fn($v) => $v === null ? '–' : number_format($v, 0, '', $T['sep']);
$html = "<caption>{$T['cap']}</caption><tr><th scope=\"col\">{$T['y']}</th><th scope=\"col\">{$T['tot']}</th>"
      . "<th scope=\"col\">{$T['ita']}</th><th scope=\"col\">{$T['ger']}</th><th scope=\"col\">{$T['w']}</th><th scope=\"col\">{$T['m']}</th></tr>";
foreach ($out['years'] as $i => $y) {
    $s = $out['series'];
    $html .= "<tr><th scope=\"row\">$y</th><td>{$f($s['tutti']['total'][$i])}</td><td>{$f($s['ita']['total'][$i])}</td>"
           . "<td>{$f($s['ger']['total'][$i])}</td><td>{$f($s['tutti']['women'][$i])}</td><td>{$f($s['tutti']['men'][$i])}</td></tr>";
}
return $html;
