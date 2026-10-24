<?php
/**
 * Risorse per template (pubblicate, ricercabili, totale) e chunk di head usato: SOLA LETTURA. Vale per entrambi i siti.
 * Uso: php scripts/checks/risorse-per-template.php /www [template,template,...]
 *      con l'elenco: mostra anche gli id e i titoli delle risorse pubblicate di quei template (es. i template di test)
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php risorse-per-template.php /www [template,...]\n"); exit(1); }
$dettaglio = array_filter(array_map('trim', explode(',', $argv[2] ?? '')));
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$modx->connect();
$p = $modx->getOption('table_prefix', null, 'modx_');
$sql = "SELECT t.id, t.templatename, t.content,
        SUM(c.published = 1 AND c.deleted = 0) pubblicate, SUM(c.published = 1 AND c.deleted = 0 AND c.searchable = 1) ricercabili,
        SUM(c.deleted = 0) totale
        FROM {$p}site_templates t LEFT JOIN {$p}site_content c ON c.template = t.id
        GROUP BY t.id, t.templatename, t.content ORDER BY pubblicate DESC, t.templatename";
printf("%-4s %-30s %5s %5s %5s  %s\n", 'id', 'template', 'pubbl', 'ricer', 'tot', 'head');
foreach ($modx->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    preg_match_all('/\[\[\$(head[\w-]*)/', (string) $r['content'], $m);
    $head = $m[1] ? implode(', ', array_unique($m[1])) : (stripos((string) $r['content'], '<head') !== false ? '(nel template)' : '-');
    printf("%-4d %-30s %5d %5d %5d  %s\n", $r['id'], $r['templatename'], $r['pubblicate'], $r['ricercabili'], $r['totale'], $head);
}
$st = $modx->pdo->prepare("SELECT c.id, c.pagetitle, c.uri, c.searchable FROM {$p}site_content c JOIN {$p}site_templates t ON t.id = c.template
                           WHERE t.templatename = ? AND c.published = 1 AND c.deleted = 0 ORDER BY c.id");
foreach ($dettaglio as $t) {
    $st->execute([$t]);
    echo "\n== $t\n";
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) printf("  %5d  %-50s /%s%s\n", $r['id'], mb_substr($r['pagetitle'], 0, 50), $r['uri'], $r['searchable'] ? '' : '  (non ricercabile)');
}
