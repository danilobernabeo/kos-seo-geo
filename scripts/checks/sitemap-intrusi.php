<?php
/**
 * Risorse che possono finire nella sitemap di pdoSitemap con un indirizzo sbagliato: weblink e symlink pubblicati e
 * ricercabili, risorse senza uri. SOLA LETTURA.
 * Uso: php scripts/checks/sitemap-intrusi.php /www [termine ...]
 *   termine numerico = timestamp unix (lastmod della sitemap: risorse con editedon, createdon o publishedon uguale);
 *   termine di testo = parte dell'uri. In più: risorse pubblicate e ricercabili fuori dal contesto web, con il site_url.
 * Correzione (anniazzurri.it): spegnere «Ricercabile» sulle risorse elencate, come per le altre esclusioni dalla sitemap.
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php sitemap-intrusi.php /www [termine ...]\n"); exit(1); }
$termini = array_slice($argv, 2);
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

$riga = function (array $x): void {
    printf("  %5d  %-8s %-10s padre %-5d pubbl %d ricerc %d  %s\n        uri «%s»  contenuto «%s»  edit %s\n",
        $x['id'], $x['context_key'], substr(strrchr('\\' . $x['class_key'], '\\'), 1), $x['parent'], $x['published'],
        $x['searchable'], $x['pagetitle'], $x['uri'], trim((string) $x['contenuto']), $x['data']);
};
$campi = "c.id, c.context_key, c.class_key, c.uri, c.parent, c.pagetitle, c.published, c.searchable,
          LEFT(REPLACE(c.content, '\\n', ' '), 80) AS contenuto, FROM_UNIXTIME(c.editedon) AS data";
$ctx = $modx->pdo->query("SELECT c.context_key, COUNT(*) n, MAX(s.value) site_url FROM {$p}site_content c
    LEFT JOIN {$p}context_setting s ON s.context_key = c.context_key AND s.`key` = 'site_url'
    WHERE c.published = 1 AND c.deleted = 0 AND c.searchable = 1 GROUP BY c.context_key")->fetchAll(PDO::FETCH_ASSOC);
echo "== Risorse pubblicate e ricercabili per contesto\n";
foreach ($ctx as $x) printf("  %-10s %5d  site_url «%s»\n", $x['context_key'], $x['n'], $x['site_url'] ?? '');
$fuori = $modx->pdo->query("SELECT $campi FROM {$p}site_content c WHERE c.published = 1 AND c.deleted = 0 AND c.searchable = 1
    AND c.context_key NOT IN ('web', 'mgr') ORDER BY c.context_key, c.id LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
echo "== Fuori dal contesto web (prime 50): " . count($fuori) . "\n";
foreach ($fuori as $x) $riga($x);
foreach ($termini as $t) {
    if (ctype_digit($t)) {
        $st = $modx->pdo->prepare("SELECT $campi FROM {$p}site_content c WHERE ? IN (c.editedon, c.createdon, c.publishedon)");
        $st->execute([(int) $t]);
    } else {
        $st = $modx->pdo->prepare("SELECT $campi FROM {$p}site_content c WHERE c.uri LIKE ? OR c.content LIKE ?");
        $st->execute(['%' . $t . '%', '%sstefano.it/' . $t . '%']);
    }
    $r2 = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "== «$t»: " . count($r2) . "\n";
    foreach ($r2 as $x) $riga($x);
}
// Chiamate a pdoSitemap (risorse, chunk, template), per intero
echo "== Chiamate a pdoSitemap\n";
foreach (["SELECT CONCAT('risorsa ', id, ' ', uri) n, content t FROM {$p}site_content WHERE deleted = 0 AND content LIKE '%pdoSitemap%'",
          "SELECT CONCAT('chunk ', name) n, snippet t FROM {$p}site_htmlsnippets WHERE snippet LIKE '%pdoSitemap%'",
          "SELECT CONCAT('template ', templatename) n, content t FROM {$p}site_templates WHERE content LIKE '%pdoSitemap%'"] as $sql) {
    foreach ($modx->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $x) echo "-- {$x['n']}\n{$x['t']}\n";
}
