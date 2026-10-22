<?php
/**
 * KOS-12/13 — da dove escono le briciole visibili su una pagina (sola lettura).
 * Uso:  php kos-briciole-origine.php /www <uri> [<uri> ...]
 * Mostra template, righe del contenuto e dei TV che citano breadcrumb, e i chunk richiamati nel contenuto
 * che a loro volta le citano.
 */
$webroot = rtrim($argv[1] ?? '/www', '/');
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modChunk, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$cerca = fn($s) => preg_match_all('/.{0,60}readcrumb.{0,60}/is', (string) $s, $m) ? $m[0] : [];
foreach (array_slice($argv, 2) as $uri) {
    $r = $modx->getObject(modResource::class, ['uri:IN' => [trim($uri, '/'), trim($uri, '/') . '/'], 'context_key' => 'web']);
    if (!$r) { echo "$uri: risorsa non trovata\n\n"; continue; }
    $t = $r->getOne('Template');
    echo "$uri → risorsa {$r->get('id')}, template " . ($t ? $t->get('templatename') : '-') . ", class {$r->get('class_key')}\n";
    foreach ($cerca($r->get('content')) as $x) echo "  contenuto: " . trim(preg_replace('/\s+/', ' ', $x)) . "\n";
    foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $r->get('id')]) as $tv)
        foreach ($cerca($tv->get('value')) as $x) echo "  TV {$tv->get('tmplvarid')}: " . trim(preg_replace('/\s+/', ' ', $x)) . "\n";
    preg_match_all('/\[\[\$([\w.-]+)/', (string) $r->get('content') . ($t ? $t->get('content') : ''), $m);
    foreach (array_unique($m[1]) as $n) {
        $c = $modx->getObject(modChunk::class, ['name' => $n]);
        if ($c) foreach ($cerca($c->get('snippet')) as $x) echo "  chunk $n: " . trim(preg_replace('/\s+/', ' ', $x)) . "\n";
    }
    echo "\n";
}
