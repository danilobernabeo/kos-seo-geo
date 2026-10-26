<?php
/**
 * KOS voce 24 — branche prenotabili online (TuoTempo) per sede e pagine «Servizi e cure» del sito, per la tabella
 * branca → pagina. SOLA LETTURA.
 *
 * Uso:  php scripts/checks/kos-branche-prenotabili.php /www > ~/branche-prenotabili.json
 *
 * Stesse regole del catalogo nel dato strutturato (snippet kosSchema): scheda struttura pubblicata con la TV
 * di prenotazione (default `istanza`) compilata, sede non nascosta, prestazione non nascosta o prenotabile in
 * differita, almeno un medico.
 * Uscita: per ogni branca le sedi (scheda, uri) e il numero di prestazioni; l'elenco delle pagine pubblicate sotto
 * /it/servizi-e-cure (id, titolo, menutitle, uri, template, srv.schema).
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-branche-prenotabili.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p  = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

$tpl   = array_filter(array_map('trim', explode(',', (string) ($modx->getOption('kos.tpl_struttura') ?: 'strutture,strutture-VDP'))));
$in    = implode(',', array_fill(0, count($tpl), '?'));
$tvPren = (string) ($modx->getOption('kos.prenotazione_tv') ?: 'istanza');

$st = $db->prepare("SELECT c.id, c.pagetitle, c.uri, v.value AS area FROM {$p}site_content c
                    JOIN {$p}site_templates t ON t.id = c.template
                    JOIN {$p}site_tmplvar_contentvalues v ON v.contentid = c.id
                    JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid AND tv.name = ?
                    WHERE t.templatename IN ($in) AND c.published = 1 AND c.deleted = 0 AND TRIM(v.value) <> ''
                    ORDER BY c.uri");
$st->execute(array_merge([$tvPren], $tpl));
$schede = $st->fetchAll(PDO::FETCH_ASSOC);

$visibile = $db->prepare("SELECT 1 FROM kos_strutture WHERE areaid = ? AND is_hidden = 0 LIMIT 1");
$prest = $db->prepare("SELECT p.typology, COUNT(*) AS n FROM kos_prestazioni p
                       WHERE p.areaid = ? AND (p.is_hidden = 0 OR p.mop_bookability = 2)
                         AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid)
                       GROUP BY p.typology");
$branche = [];
$sedi = 0;
foreach ($schede as $s) {
    $area = trim((string) $s['area']);
    $visibile->execute([$area]);
    if (!$visibile->fetchColumn()) continue;
    $sedi++;
    $prest->execute([$area]);
    foreach ($prest->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $b = trim((string) $r['typology']);
        $branche[$b]['prestazioni'] = ($branche[$b]['prestazioni'] ?? 0) + (int) $r['n'];
        $branche[$b]['sedi'][] = ['id' => (int) $s['id'], 'scheda' => $s['pagetitle'], 'uri' => $s['uri'], 'prestazioni' => (int) $r['n']];
    }
}
ksort($branche);

$pag = $db->query("SELECT c.id, c.pagetitle, c.menutitle, c.uri, t.templatename,
                          (SELECT v.value FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid
                            WHERE v.contentid = c.id AND tv.name = 'srv.schema') AS srv_schema
                   FROM {$p}site_content c LEFT JOIN {$p}site_templates t ON t.id = c.template
                   WHERE c.published = 1 AND c.deleted = 0 AND c.uri LIKE 'it/servizi-e-cure/%'
                   ORDER BY c.uri")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['generato' => date('c'), 'sedi_con_prenotazione' => $sedi, 'branche' => $branche, 'pagine_servizi' => $pag],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
