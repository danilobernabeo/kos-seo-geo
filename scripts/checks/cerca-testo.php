<?php
/**
 * Diagnostica in SOLA LETTURA: elenca le risorse (contenuto, introtext e valori delle TV) che contengono un testo.
 * Uso: php scripts/checks/cerca-testo.php /www <testo> [<testo> ...]
 * Esempio: php scripts/checks/cerca-testo.php /www hsa_acc beata-corte utm_source
 */
declare(strict_types=1);
$a = array_slice($argv, 1); $webroot = rtrim((string) array_shift($a), '/');
if (!is_file("$webroot/config.core.php") || !$a) { fwrite(STDERR, "Uso: php cerca-testo.php /www <testo> [...]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance(); $modx->initialize('web');
$pdo = $modx->getConnection()->pdo ?? $modx->pdo; $p = $modx->getOption('table_prefix');
foreach ($a as $t) {
    echo "== «$t»\n"; $like = '%' . $t . '%';
    $q = $pdo->prepare("SELECT id, uri, published, 'content' AS dove FROM {$p}site_content WHERE deleted = 0 AND (content LIKE ? OR introtext LIKE ?)
        UNION SELECT c.id, c.uri, c.published, CONCAT('TV ', v.name) FROM {$p}site_tmplvar_contentvalues tv
        JOIN {$p}site_tmplvars v ON v.id = tv.tmplvarid JOIN {$p}site_content c ON c.id = tv.contentid
        WHERE c.deleted = 0 AND tv.value LIKE ? ORDER BY 1");
    $q->execute([$like, $like, $like]);
    $n = 0;
    foreach ($q as $r) { $n++; printf("  %5d %s %-12s %s\n", $r['id'], $r['published'] ? 'pub' : 'non', $r['dove'], $r['uri']); }
    if (!$n) echo "  (nessuna risorsa)\n";
    $q = $pdo->prepare("SELECT name FROM {$p}site_htmlsnippets WHERE snippet LIKE ?"); $q->execute([$like]);
    foreach ($q as $r) echo "  chunk ", $r['name'], "\n";
}
