<?php
/**
 * Risorse che possono finire nella sitemap di pdoSitemap con un indirizzo sbagliato: weblink e symlink pubblicati e
 * ricercabili, risorse senza uri. SOLA LETTURA.
 * Uso: php scripts/checks/sitemap-intrusi.php /www
 * Correzione (anniazzurri.it): spegnere «Ricercabile» sulle risorse elencate, come per le altre esclusioni dalla sitemap.
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php sitemap-intrusi.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$r = $modx->pdo->query("SELECT c.id, c.context_key, c.class_key, c.uri, c.parent, c.pagetitle, c.searchable, c.hidemenu,
        LEFT(REPLACE(c.content, '\n', ' '), 80) AS contenuto, FROM_UNIXTIME(GREATEST(c.editedon, c.publishedon, c.createdon)) AS data
    FROM {$p}site_content c
    WHERE c.published = 1 AND c.deleted = 0 AND c.searchable = 1
      AND (c.class_key LIKE '%WebLink' OR c.class_key LIKE '%SymLink' OR c.uri = '' OR c.uri IS NULL)
    ORDER BY c.context_key, c.id")->fetchAll(PDO::FETCH_ASSOC);
echo "== Risorse pubblicate e ricercabili che sono weblink/symlink o senza uri: " . count($r) . "\n";
foreach ($r as $x) {
    printf("  %5d  %-4s %-10s padre %-5d hidemenu %d  %s\n        uri «%s»  contenuto «%s»  (%s)\n",
        $x['id'], $x['context_key'], substr(strrchr('\\' . $x['class_key'], '\\'), 1), $x['parent'], $x['hidemenu'],
        $x['pagetitle'], $x['uri'], trim((string) $x['contenuto']), $x['data']);
}
