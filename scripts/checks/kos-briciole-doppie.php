<?php
/**
 * KOS-12 — pagine con le briciole due volte: template che le mostrano già ([[$breadcrumb]]) e pagine di quei
 * template che le richiamano anche nel contenuto (myBreadcrumb, chunk breadcrumb, BreadCrumb, markup #breadcrumbs).
 * SOLA LETTURA.  Uso: php scripts/checks/kos-briciole-doppie.php /www
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-briciole-doppie.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;
$re = '/myBreadcrumb|\[\[\$breadcrumb|\[\[!?BreadCrumb|type=`briciole`|id="breadcrumbs"/i';

$tpl = [];
foreach ($db->query("SELECT id, templatename, content FROM {$p}site_templates")->fetchAll(PDO::FETCH_ASSOC) as $t) {
    $tpl[(int) $t['id']] = ['nome' => $t['templatename'], 'briciole' => (bool) preg_match($re, (string) $t['content'])];
}
$per = [];
$q = $db->query("SELECT id, template, uri, content FROM {$p}site_content WHERE published = 1 AND deleted = 0");
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    if (!preg_match_all($re, (string) $r['content'], $m)) continue;
    $t = $tpl[(int) $r['template']] ?? ['nome' => '?', 'briciole' => false];
    $per[$t['nome']][] = ['id' => $r['id'], 'uri' => $r['uri'], 'richiami' => implode(' ', array_unique($m[0])), 'doppie' => $t['briciole']];
}
echo "== Template che mostrano le briciole: " . implode(', ', array_column(array_filter($tpl, fn($t) => $t['briciole']), 'nome')) . "\n";
foreach ($per as $nome => $l) {
    $d = count(array_filter($l, fn($x) => $x['doppie']));
    echo "\n== $nome: " . count($l) . " pagine con briciole nel contenuto" . ($d ? ", $d DOPPIE (anche nel template)" : '') . "\n";
    foreach ($l as $x) if ($x['doppie']) echo "   {$x['id']}  /{$x['uri']}  ({$x['richiami']})\n";
}
