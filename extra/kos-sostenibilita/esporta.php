<?php
/**
 * Riporta nel repository le modifiche fatte sullo stage alla pagina Sostenibilità.
 * Uso sul cloud:  php esporta.php /www   → ~/sostenibilita-stage.tgz con la stessa struttura di questa cartella
 * (elementi/, files/assets/, dati-stage.json con i valori attuali delle TV della pagina 54).
 */
$webroot = rtrim($argv[1] ?? '/www', '/');
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modChunk, modSnippet, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$home = getenv('HOME') ?: '.';
$out = "$home/sostenibilita-stage";
exec('rm -rf ' . escapeshellarg($out));
$el = [
    'snippets' => [modSnippet::class, ['kosChartDipendenti', 'kosChartRicavi', 'kosSostenibilitaRisorse'], 'php'],
    'chunks'   => [modChunk::class, ['kosDipendenti_it', 'kosDipendenti_en', 'kosStrutture_it', 'kosStrutture_en',
                   'kosAttivita_it', 'kosAttivita_en', 'kosRicavi_it', 'kosRicavi_en', 'grafico-dipendenti', 'employee-chart'], 'html'],
];
foreach ($el as $dir => [$cls, $nomi, $ext]) {
    @mkdir("$out/elementi/$dir", 0775, true);
    foreach ($nomi as $n) {
        $o = $modx->getObject($cls, ['name' => $n]);
        if (!$o) { echo "  ! $n assente\n"; continue; }
        $c = (string) $o->getContent();
        file_put_contents("$out/elementi/$dir/$n.$ext", ($ext === 'php' ? "<?php\n" : '') . $c . "\n");
    }
}
foreach (['assets/css/kos-chart.css', 'assets/css/kos-strutture.css', 'assets/css/kos-ricavi.css', 'assets/js/kos-chart.js',
          'assets/js/kos-ricavi.js', 'assets/img/grafici/strutture-globo.png'] as $f) {
    @mkdir(dirname("$out/files/$f"), 0775, true);
    copy("$webroot/$f", "$out/files/$f");
}
@mkdir("$out/files/assets/img/grafici/icone", 0775, true);
foreach (glob("$webroot/assets/img/grafici/icone/*") as $f) copy($f, "$out/files/assets/img/grafici/icone/" . basename($f));
$r = $modx->getObject(modResource::class, 54);
$tv = [];
foreach (['chartDipendenti', 'chartRicavi'] as $n) $tv[$n] = json_decode((string) $r->getTVValue($n), true);
file_put_contents("$out/dati-stage.json", json_encode($tv, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
exec('cd ' . escapeshellarg($home) . ' && tar czf sostenibilita-stage.tgz sostenibilita-stage');
echo "== $home/sostenibilita-stage.tgz\n";
