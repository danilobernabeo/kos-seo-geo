<?php
/**
 * Risorse per template (pubblicate, ricercabili, totale) e chunk di head usato: SOLA LETTURA. Vale per entrambi i siti.
 * Uso: php scripts/checks/risorse-per-template.php /www [template,template,...]
 *      con l'elenco: mostra anche gli id e i titoli delle risorse pubblicate di quei template (es. i template di test)
 *      php scripts/checks/risorse-per-template.php /www --campione[=N] > pagine.txt
 *      campione per il controllo degli head (kos-head-controllo.py): le prime N pagine pubblicate di ogni template
 *      (default 2), le pagine 30-34 e le coppie italiano/inglese dichiarate con translationId
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php risorse-per-template.php /www [template,...]\n"); exit(1); }
$campione = 0;
foreach ($argv as $a) if (preg_match('/^--campione(?:=(\d+))?$/', $a, $m)) $campione = (int) ($m[1] ?? 2) ?: 2;
$dettaglio = $campione ? [] : array_filter(array_map('trim', explode(',', $argv[2] ?? '')));
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
if ($campione) {
    $righe = [];
    // percorso pubblico dal router di MODX (contesto, base_url, prefisso lingua), non dal solo campo uri
    $url = function (int $id) use ($modx): string {
        $r = $modx->getObject(\MODX\Revolution\modResource::class, $id);
        $u = $r ? (string) $modx->makeUrl($id, $r->get('context_key'), '', 'full') : '';
        $path = (string) parse_url($u, PHP_URL_PATH);
        return $path !== '' ? $path : '/';
    };
    $q = $modx->pdo->query("SELECT c.id, c.uri, t.templatename FROM {$p}site_content c JOIN {$p}site_templates t ON t.id = c.template
                            WHERE c.published = 1 AND c.deleted = 0 AND c.class_key NOT LIKE '%WebLink' AND c.class_key NOT LIKE '%SymLink'
                            ORDER BY t.templatename, c.id");
    $conta = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $forza = in_array((int) $r['id'], [1, 30, 31, 32, 33, 34, 990], true);
        if (!$forza && ($conta[$r['templatename']] = ($conta[$r['templatename']] ?? 0) + 1) > $campione) continue;
        $righe[$r['id']] = sprintf("%s  # %d %s", $url((int) $r['id']), $r['id'], $r['templatename']);
    }
    $tv = $modx->pdo->query("SELECT v.contentid, c.uri, t.templatename, v.value FROM {$p}site_tmplvar_contentvalues v
                             JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid AND tv.name = 'translationId'
                             JOIN {$p}site_content c ON c.id = v.contentid AND c.published = 1 AND c.deleted = 0
                             JOIN {$p}site_templates t ON t.id = c.template WHERE v.value <> ''");
    $uri = $modx->pdo->prepare("SELECT c.uri, t.templatename FROM {$p}site_content c JOIN {$p}site_templates t ON t.id = c.template
                                WHERE c.id = ? AND c.published = 1 AND c.deleted = 0");
    foreach ($tv->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $righe[$r['contentid']] = sprintf("%s  # %d %s", $url((int) $r['contentid']), $r['contentid'], $r['templatename']);
        $altra = (int) preg_replace('/\D/', '', (string) $r['value']);
        $uri->execute([$altra]);
        if ($x = $uri->fetch(PDO::FETCH_ASSOC)) $righe[$altra] = sprintf("%s  # %d %s", $url($altra), $altra, $x['templatename']);
    }
    echo "# campione per kos-head-controllo.py, " . date('d/m/Y H:i') . "\n" . implode("\n", $righe) . "\n";
    exit(0);
}
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
