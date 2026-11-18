<?php
/**
 * KOS voce 24 — prestazioni prenotabili online (TuoTempo) per scheda struttura, con la loro branca. SOLA LETTURA.
 * Serve a confermare le associazioni struttura ↔ servizio presenti solo sulla pagina del servizio (18/11): la
 * prestazione dice più della branca (es. «Elettromiografia» dentro la branca Neurologia).
 *
 * Uso:  php scripts/checks/kos-prestazioni-prenotabili.php /www > ~/prestazioni-prenotabili.json
 *
 * Stesse regole del catalogo nel dato strutturato (kosSchema e kos-branche-prenotabili.php): scheda pubblicata con la
 * TV di prenotazione compilata, sede non nascosta, prestazione non nascosta o prenotabile in differita, almeno un medico.
 * Esce ogni riga di kos_prestazioni così com'è (tutte le colonne), più scheda (id, titolo, uri).
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-prestazioni-prenotabili.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p  = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

$tpl    = array_filter(array_map('trim', explode(',', (string) ($modx->getOption('kos.tpl_struttura') ?: 'strutture,strutture-VDP'))));
$in     = implode(',', array_fill(0, count($tpl), '?'));
$tvPren = (string) ($modx->getOption('kos.prenotazione_tv') ?: 'istanza');

$st = $db->prepare("SELECT c.id, c.pagetitle, c.uri, v.value AS area FROM {$p}site_content c
                    JOIN {$p}site_templates t ON t.id = c.template
                    JOIN {$p}site_tmplvar_contentvalues v ON v.contentid = c.id
                    JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid AND tv.name = ?
                    WHERE t.templatename IN ($in) AND c.published = 1 AND c.deleted = 0 AND TRIM(v.value) <> ''
                    ORDER BY c.uri");
$st->execute(array_merge([$tvPren], $tpl));
$visibile = $db->prepare("SELECT 1 FROM kos_strutture WHERE areaid = ? AND is_hidden = 0 LIMIT 1");
$prest = $db->prepare("SELECT p.* FROM kos_prestazioni p
                       WHERE p.areaid = ? AND (p.is_hidden = 0 OR p.mop_bookability = 2)
                         AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid)
                       ORDER BY p.typology");
$out = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
    $area = trim((string) $s['area']);
    $visibile->execute([$area]);
    if (!$visibile->fetchColumn()) continue;
    $prest->execute([$area]);
    foreach ($prest->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[] = ['scheda_id' => (int) $s['id'], 'scheda' => $s['pagetitle'], 'uri' => $s['uri']] + $r;
    }
}
echo json_encode(['generato' => date('c'), 'righe' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
