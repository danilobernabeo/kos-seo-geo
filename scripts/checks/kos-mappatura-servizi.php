<?php
/**
 * KOS voce 24 — associazioni struttura ↔ servizi e cure / tecnologie sul sito, per il confronto con i filtri
 * «Servizi e Cure» di Yext. SOLA LETTURA.
 *
 * Uso:  php scripts/checks/kos-mappatura-servizi.php /www > ~/mappatura-servizi.json
 *
 * Per ogni scheda struttura pubblicata (template kos.tpl_struttura, default strutture,strutture-VDP):
 *   - le pagine indicate nelle TV map* della scheda (mapAree*, mapInterventi*, mapTecnologie), con la TV di origine;
 *   - le pagine che la indicano nella loro TV mapServiziStrutture (associazione vista dal lato del servizio).
 * Le due direzioni sono gestite a mano e possono divergere: lo script le riporta entrambe.
 * Per ogni pagina collegata: id, titolo, menutitle, uri, template, pubblicata, sezione (primo livello sotto /it).
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-mappatura-servizi.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p  = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

$tpl = array_filter(array_map('trim', explode(',', (string) ($modx->getOption('kos.tpl_struttura') ?: 'strutture,strutture-VDP'))));
$in  = implode(',', array_fill(0, count($tpl), '?'));

$pagine = [];
$pagina = function (int $id) use ($db, $p, &$pagine) {
    if (!isset($pagine[$id])) {
        $st = $db->prepare("SELECT c.id, c.pagetitle, c.menutitle, c.uri, c.published, c.deleted, t.templatename
                            FROM {$p}site_content c LEFT JOIN {$p}site_templates t ON t.id = c.template WHERE c.id = ?");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($r) {
            $parti = explode('/', trim((string) $r['uri'], '/'));
            $r['sezione'] = $parti[1] ?? '';
            $r['pubblicata'] = (int) $r['published'] === 1 && (int) $r['deleted'] === 0;
            unset($r['published'], $r['deleted']);
        }
        $pagine[$id] = $r;
    }
    return $pagine[$id];
};
$ids = fn(string $v) => array_values(array_unique(array_filter(array_map('intval', preg_split('/[^\d]+/', $v)))));

$st = $db->prepare("SELECT c.id, c.pagetitle, c.uri, t.templatename FROM {$p}site_content c
                    JOIN {$p}site_templates t ON t.id = c.template
                    WHERE t.templatename IN ($in) AND c.published = 1 AND c.deleted = 0 ORDER BY c.uri");
$st->execute($tpl);
$schede = $st->fetchAll(PDO::FETCH_ASSOC);

$tvSt = $db->prepare("SELECT tv.name, v.value FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid
                      WHERE v.contentid = ? AND tv.name LIKE 'map%' AND tv.name <> 'mapServiziStrutture' AND v.value <> ''");
$yext = $db->prepare("SELECT v.value FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid
                      WHERE v.contentid = ? AND tv.name = 'idYext'");
// lato servizio: pagine con mapServiziStrutture valorizzata
$inv = [];
foreach ($db->query("SELECT v.contentid, v.value FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid
                     WHERE tv.name = 'mapServiziStrutture' AND v.value <> ''")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    foreach ($ids((string) $r['value']) as $sid) $inv[$sid][] = (int) $r['contentid'];
}

$out = [];
foreach ($schede as $s) {
    $id = (int) $s['id'];
    $tvSt->execute([$id]);
    $dalla = [];
    foreach ($tvSt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        foreach ($ids((string) $t['value']) as $pid) $dalla[] = ['tv' => $t['name']] + ($pagina($pid) ?? ['id' => $pid, 'mancante' => true]);
    }
    $verso = array_map(fn($pid) => $pagina($pid) ?? ['id' => $pid, 'mancante' => true], array_values(array_unique($inv[$id] ?? [])));
    $yext->execute([$id]);
    $out[] = ['id' => $id, 'pagetitle' => $s['pagetitle'], 'uri' => $s['uri'], 'template' => $s['templatename'],
              'idYext' => (string) ($yext->fetchColumn() ?: ''), 'dalla_scheda' => $dalla, 'dal_servizio' => $verso];
}
echo json_encode(['generato' => date('c'), 'schede' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
