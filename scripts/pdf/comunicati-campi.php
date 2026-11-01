<?php
/**
 * KOS-8 / AA-3 — Diagnostica in sola lettura: campi di data e variabili template dei comunicati stampa (kosgroup.com).
 * Uso: php comunicati-campi.php <webroot> [quanti=4]
 * Mostra per i primi comunicati (con PDF nella TV comunicatoPdf) le date di sistema e tutte le TV valorizzate.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$webroot = rtrim((string) ($argv[1] ?? ''), '/');
$n = (int) ($argv[2] ?? 4);
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$db = $modx->pdo;
$p = $modx->getOption('table_prefix', null, 'modx_');
$ids = $db->query("SELECT c.id FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars t ON t.id=v.tmplvarid AND t.name='comunicatoPdf'
                   JOIN {$p}site_content c ON c.id=v.contentid AND c.deleted=0 ORDER BY c.id LIMIT " . max(1, $n))->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $id) {
    $c = $db->query("SELECT id,pagetitle,longtitle,alias,template,publishedon,createdon,editedon,pub_date FROM {$p}site_content WHERE id=" . (int) $id)->fetch(PDO::FETCH_ASSOC);
    echo "== risorsa {$c['id']} · {$c['alias']} · template {$c['template']}\n  pagetitle: {$c['pagetitle']}\n  longtitle: {$c['longtitle']}\n";
    foreach (['publishedon', 'createdon', 'editedon', 'pub_date'] as $k) echo "  $k: " . ($c[$k] ? date('Y-m-d', (int) $c[$k]) : '-') . "\n";
    $tv = $db->query("SELECT t.name, LEFT(v.value,80) val FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars t ON t.id=v.tmplvarid WHERE v.contentid=" . (int) $id)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tv as $r) echo "  TV {$r['name']}: {$r['val']}\n";
}
