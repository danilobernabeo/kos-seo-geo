<?php
/**
 * confronta-risorse.php — campi e TV di alcune risorse affiancati (sola lettura). Serve a capire perché una pagina
 * non compare in un elenco (contenitore, template, nascosta dai menu, date, TV di filtro).
 * Stampa solo i campi e le TV che differiscono tra le risorse, più parent, template e date.
 *
 * Uso: php scripts/checks/confronta-risorse.php /www <uri1> <uri2> ...
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$webroot = rtrim((string) ($argv[1] ?? ''), '/');
$uris = array_slice($argv, 2);
if (!is_file("$webroot/config.core.php") || !$uris) exit("Uso: php confronta-risorse.php /www <uri> <uri> ...\n");
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVarResource, modTemplateVar};
$modx = modX::getInstance();
$modx->initialize('mgr');
$campi = ['parent', 'template', 'published', 'publishedon', 'pub_date', 'createdon', 'editedon', 'hidemenu', 'searchable',
          'menuindex', 'isfolder', 'class_key', 'context_key', 'show_in_tree', 'deleted'];
$righe = []; $tvNomi = [];
foreach ($uris as $u) {
    $u = trim($u, '/');
    $r = $modx->getObject(modResource::class, ['uri:IN' => [$u, "$u/"]]);
    if (!$r) { echo "non trovata: $u\n"; continue; }
    $x = ['_id' => $r->get('id'), '_uri' => $u];
    foreach ($campi as $c) {
        $v = $r->get($c);
        if (in_array($c, ['publishedon', 'pub_date', 'createdon', 'editedon'], true)) $v = $v ? date('Y-m-d H:i', is_numeric($v) ? (int) $v : strtotime((string) $v)) : '0';
        $x[$c] = (string) $v;
    }
    $p = $modx->getObject(modResource::class, (int) $r->get('parent'));
    $x['parent'] .= $p ? ' ' . $p->get('uri') : '';
    foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $r->get('id')]) as $tv) {
        $id = (int) $tv->get('tmplvarid');
        $tvNomi[$id] ??= ($t = $modx->getObject(modTemplateVar::class, $id)) ? $t->get('name') : "tv$id";
        $x['tv.' . $tvNomi[$id]] = mb_substr(preg_replace('/\s+/', ' ', (string) $tv->get('value')), 0, 60);
    }
    $righe[] = $x;
}
if (!$righe) exit(1);
$chiavi = [];
foreach ($righe as $x) $chiavi += array_flip(array_keys($x));
foreach (array_keys($chiavi) as $k) {
    $val = array_map(fn($x) => $x[$k] ?? '—', $righe);
    if (!in_array($k, ['_id', '_uri', 'parent', 'template', 'publishedon'], true) && count(array_unique($val)) === 1) continue;
    printf("%-26s %s\n", $k, implode(' | ', $val));
}
