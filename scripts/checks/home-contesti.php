<?php
/**
 * Pagina iniziale di ogni contesto (impostazione site_start) e risorse con un dato id: id, contesto, template, classe,
 * uri, pubblicata, description. SOLA LETTURA. Serve a capire quale risorsa serve /en e /it (KOS 21-23, 3/11).
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/checks/home-contesti.php /www [id ...]
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php home-contesti.php /www [id ...]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

$riga = $db->prepare("SELECT c.id, c.context_key, c.template, t.templatename, c.class_key, c.uri, c.published, c.searchable,
                             c.pagetitle, c.description, c.content
                      FROM {$p}site_content c LEFT JOIN {$p}site_templates t ON t.id = c.template WHERE c.id = ?");
$stampa = function (int $id, string $perche) use ($riga) {
    $riga->execute([$id]);
    $r = $riga->fetch(PDO::FETCH_ASSOC);
    if (!$r) { echo "  $perche: risorsa $id non trovata\n"; return; }
    $cont = in_array($r['class_key'], ['MODX\\Revolution\\modSymLink', 'MODX\\Revolution\\modWebLink', 'modSymLink', 'modWebLink'], true)
        ? ' → ' . trim((string) $r['content']) : '';
    echo "  $perche: id {$r['id']} [{$r['context_key']}] template " . ($r['templatename'] ?? $r['template']) . ", {$r['class_key']}$cont\n"
       . "      uri «{$r['uri']}», pubblicata {$r['published']}, ricercabile {$r['searchable']}, pagetitle «{$r['pagetitle']}»\n"
       . "      description «" . mb_substr((string) $r['description'], 0, 120) . "»\n";
};

echo "== Pagine iniziali dei contesti\n";
foreach ($db->query("SELECT `key` FROM {$p}context WHERE `key` <> 'mgr'")->fetchAll(PDO::FETCH_COLUMN) as $ctx) {
    $s = $db->prepare("SELECT value FROM {$p}context_setting WHERE context_key = ? AND `key` = 'site_start'");
    $s->execute([$ctx]);
    $start = (int) ($s->fetchColumn() ?: $modx->getOption('site_start'));
    $stampa($start, "contesto $ctx (site_start)");
}
$ids = array_map('intval', array_slice($argv, 2));
if ($ids) { echo "== Risorse richieste\n"; foreach ($ids as $id) $stampa($id, 'richiesta'); }
