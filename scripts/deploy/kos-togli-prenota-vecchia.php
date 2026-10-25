<?php
/**
 * KOS — toglie la vecchia barra di prenotazione online dal contenuto delle SCHEDE STRUTTURA (template
 * kos.tpl_struttura, default strutture e strutture-VDP). Decisione di Danilo del 25/10: pagine dei servizi e landing
 * restano come sono; dove si attiva la prenotazione si inserisce la barra nuova (barraPrenotazione*).
 *
 * Richiami tolti (solo questi, nel campo content delle risorse):
 *   [[*istanza:notempty=`[[$prenotaOnline]]`]]   e le varianti con i chunk prenotaOnline-car, prenotaOnlineTelevisita
 *   [[!prenotaOnline]] / [[prenotaOnline]]       richiamo diretto dello snippet
 * Le configurazioni JavaScript che contengono la parola «prenotaOnline» (landing con window.CD_CONFIG) non si toccano.
 * I chunk e lo snippet restano: si possono archiviare dopo, quando nessuna pagina li usa.
 *
 * Uso (SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/kos-togli-prenota-vecchia.php /www            # prova: elenca le pagine, segnala dove la barra si vede oggi
 *   php scripts/deploy/kos-togli-prenota-vecchia.php /www --scrivi   # applica
 * Backup: tabella <prefisso>site_content_bak_AAAAMMGG (righe toccate) e ~/backup-kos-seo-geo/<data>-prenota/contenuti.json.
 * Ripristino: php scripts/deploy/kos-togli-prenota-vecchia.php /www --ripristina=~/backup-kos-seo-geo/<data>-prenota/contenuti.json --scrivi
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi  = in_array('--scrivi', $argv, true);
$rip     = '';
foreach ($argv as $a) if (str_starts_with($a, '--ripristina=')) $rip = substr($a, 13);
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-togli-prenota-vecchia.php /www [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$p  = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;

if ($rip !== '') {
    $rip = preg_replace('#^~#', (string) getenv('HOME'), $rip);
    $dati = json_decode((string) @file_get_contents($rip), true);
    if (!is_array($dati)) { fwrite(STDERR, "File di ripristino non leggibile: $rip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DEL RIPRISTINO") . ": " . count($dati) . " pagine\n";
    foreach ($dati as $id => $c) {
        $r = $modx->getObject(modResource::class, (int) $id);
        echo "  " . ($r ? "~ $id {$r->get('pagetitle')}" : "! $id non trovata") . "\n";
        if ($scrivi && $r) { $r->set('content', $c); $r->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    exit(0);
}

$RE = [
    '/[ \t]*\[\[\*istanza:notempty=`\[\[\$prenotaOnline(?:-car|Televisita)?\]\]`\]\][ \t]*\R?/',
    '/[ \t]*\[\[!?prenotaOnline\]\][ \t]*\R?/',
];
$tvIst = $db->query("SELECT id FROM {$p}site_tmplvars WHERE name = 'istanza'")->fetchColumn();
$ist = $db->prepare("SELECT value FROM {$p}site_tmplvar_contentvalues WHERE tmplvarid = ? AND contentid = ?");
// Solo le schede struttura (decisione del 25/10): pagine dei servizi e landing restano come sono.
$tpl = array_filter(array_map('trim', explode(',', (string) ($modx->getOption('kos.tpl_struttura') ?: 'strutture,strutture-VDP'))));
$q = $db->prepare("SELECT c.id, c.pagetitle, c.uri, c.published, c.content, t.templatename FROM {$p}site_content c
                   JOIN {$p}site_templates t ON t.id = c.template WHERE c.deleted = 0 AND c.content LIKE '%prenotaOnline%'
                   AND t.templatename IN (" . implode(',', array_fill(0, count($tpl), '?')) . ") ORDER BY t.templatename, c.uri");
$q->execute(array_values($tpl));
$modifiche = [];
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — vecchia barra di prenotazione tolta dal contenuto\n";
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $nuovo = preg_replace($RE, '', (string) $r['content'], -1, $n);
    if (!$n) continue;
    $ist->execute([$tvIst, $r['id']]);
    $i = trim((string) $ist->fetchColumn());
    $diretto = (bool) preg_match('/\[\[!?prenotaOnline\]\]/', (string) $r['content']);
    $vista = $diretto || $i !== '';
    printf("  %5d %-12s /%s%s — %d richiami%s\n", $r['id'], $r['templatename'] ?? '-', $r['uri'], $r['published'] ? '' : ' (non pubblicata)',
        $n, $vista ? ' — OGGI VISIBILE' . ($diretto ? ' (richiamo diretto)' : " (istanza «" . mb_substr($i, 0, 30) . "»)") : '');
    $modifiche[(int) $r['id']] = ['vecchio' => (string) $r['content'], 'nuovo' => $nuovo];
}
echo "== " . count($modifiche) . " pagine da pulire\n";

if ($scrivi && $modifiche) {
    $stamp = date('Ymd-His');
    $bak = $p . 'site_content_bak_' . date('Ymd');
    $ids = implode(',', array_keys($modifiche));
    $db->exec("CREATE TABLE IF NOT EXISTS `$bak` AS SELECT * FROM {$p}site_content WHERE 1 = 0");
    $db->exec("INSERT INTO `$bak` SELECT * FROM {$p}site_content WHERE id IN ($ids)");
    $dir = rtrim((string) getenv('HOME'), '/') . "/backup-kos-seo-geo/$stamp-prenota";
    mkdir($dir, 0775, true);
    file_put_contents("$dir/contenuti.json", json_encode(array_map(fn($m) => $m['vecchio'], $modifiche), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    foreach ($modifiche as $id => $m) {
        $r = $modx->getObject(modResource::class, $id);
        $r->set('content', $m['nuovo']);
        if (!$r->save()) echo "  ! $id: salvataggio fallito\n";
    }
    $modx->cacheManager->refresh();
    echo "== Scritto. Backup: tabella $bak e $dir/contenuti.json. Cache svuotata.\n";
}
