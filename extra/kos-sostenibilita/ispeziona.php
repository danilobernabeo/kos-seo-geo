<?php
/**
 * Pagina Sostenibilità di kosgroup.com (lavoro esterno al progetto SEO/GEO).
 * Ispezione in sola lettura della produzione prima di pubblicare i grafici HTML.
 * Uso:  php ispeziona.php /www   → stampa lo stato e salva i contenuti in ~/sostenibilita-ispezione/
 */
$webroot = rtrim($argv[1] ?? '/www', '/');
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$out = (getenv('HOME') ?: '.') . '/sostenibilita-ispezione';
@mkdir($out, 0775, true);
use MODX\Revolution\{modResource, modTemplate, modChunk, modSnippet, modTemplateVar, modContextSetting, modCategory};
use MODX\Revolution\Transport\modTransportPackage;

echo "== RISORSE CON I BOX DEI GRAFICI\n";
$q = $modx->newQuery(modResource::class);
$q->where(['id' => 54]);
$q->orCondition(['content:LIKE' => '%grafico-dipendenti%']);
$q->orCondition(['content:LIKE' => '%employee-chart%']);
$q->orCondition(['content:LIKE' => '%infografiche-container%']);
$q->orCondition(['content:LIKE' => '%box-infografica%']);
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    $t = $modx->getObject(modTemplate::class, $r->get('template'));
    $c = (string) $r->get('content');
    printf("  id %d  [%s]  %s  /%s  template %d %s  pubblicata %d\n", $r->get('id'), $r->get('context_key'),
        $r->get('pagetitle'), $r->get('uri'), $r->get('template'), $t ? $t->get('templatename') : '-', $r->get('published'));
    foreach (['grafico-dipendenti', 'employee-chart', 'infografiche-container', 'box-infografica', 'dip-gruppo.png',
              'empl-group.png', 'le-nostre-strutture', 'ricavi', 'revenue', 'customer-chart'] as $k) {
        $n = substr_count($c, $k);
        if ($n) echo "      $k: $n\n";
    }
    file_put_contents("$out/risorsa-{$r->get('id')}.html", $c);
    if ($t) file_put_contents("$out/template-{$t->get('templatename')}.html", (string) $t->get('content'));
}
echo "  contenuti salvati in $out/\n";

echo "\n== cultureKey PER CONTESTO\n";
foreach ($modx->getIterator(modContextSetting::class, ['key' => 'cultureKey']) as $s) {
    echo "  {$s->get('context_key')}: {$s->get('value')}\n";
}
echo "  sistema: " . $modx->getOption('cultureKey') . "\n";

echo "\n== MIGX\n";
foreach ($modx->getIterator(modTransportPackage::class, ['package_name:LIKE' => 'migx%']) as $p) {
    echo "  {$p->get('signature')}  installato " . ($p->get('installed') ?: 'no') . "\n";
}

echo "\n== ELEMENTI CON GLI STESSI NOMI DEL PACCHETTO (atteso: nessuno)\n";
$nomi = ['kosDipendenti_it', 'kosDipendenti_en', 'kosStrutture_it', 'kosStrutture_en', 'kosAttivita_it', 'kosAttivita_en', 'kosRicavi_it', 'kosRicavi_en'];
foreach ($nomi as $n) if ($modx->getCount(modChunk::class, ['name' => $n])) echo "  chunk $n ESISTE\n";
foreach (['kosChartDipendenti', 'kosChartRicavi'] as $n) if ($modx->getCount(modSnippet::class, ['name' => $n])) echo "  snippet $n ESISTE\n";
foreach (['chartDipendenti', 'chartRicavi'] as $n) if ($modx->getCount(modTemplateVar::class, ['name' => $n])) echo "  TV $n ESISTE\n";
echo "  categoria Sostenibilità: " . ($modx->getCount(modCategory::class, ['category' => 'Sostenibilità']) ? 'esiste' : 'assente') . "\n";

echo "\n== FILE STATICI\n";
foreach (['assets/css/kos-chart.css', 'assets/css/kos-strutture.css', 'assets/css/kos-ricavi.css', 'assets/js/kos-chart.js',
          'assets/js/kos-ricavi.js', 'assets/img/grafici/strutture-globo.png', 'assets/img/grafici/icone'] as $f) {
    echo '  ' . (file_exists("$webroot/$f") ? 'ESISTE ' : 'assente') . "  /$f\n";
}
echo "  /assets/img/grafici/: " . implode(' ', array_map('basename', glob("$webroot/assets/img/grafici/*"))) . "\n";
