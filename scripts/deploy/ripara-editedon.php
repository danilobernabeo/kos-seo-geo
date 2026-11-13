<?php
/**
 * ripara-editedon.php — ripara la data di modifica (editedon) delle risorse rovinata da aggiorna-campi.php fino al 13/11/2026.
 *
 * Il difetto: aggiorna-campi.php conservava editedon con (int) $res->get('editedon'), che in MODX 3 è la data formattata;
 * restava l'anno (es. 2025) e veniva scritto come timestamp → dateModified e <lastmod> al 1970-01-01.
 *
 * Uso (SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/ripara-editedon.php /www              # prova: elenca le risorse e la data proposta
 *   php scripts/deploy/ripara-editedon.php /www --scrivi     # applica (tabella di backup <prefisso>site_content_bak_editedon_<AAAAMMGG_HHMM>)
 *   php scripts/deploy/ripara-editedon.php /www --csv=~/editedon-originali.csv [--scrivi]
 *       date originali da un backup del database precedente al 5/11 (righe «id;editedon», editedon come intero Unix)
 * Ripristino: UPDATE <prefisso>site_content c JOIN <tabella di backup> b ON b.id = c.id SET c.editedon = b.editedon;
 *
 * Risorse rovinate: editedon tra 1 e 100000 (un anno usato come timestamp). Anno 1969 o 1970: in origine editedon era 0
 * (risorsa mai modificata dopo la creazione), e si rimette 0. Altrimenti, senza --csv: il momento in cui
 * aggiorna-campi.php ha modificato title/description della risorsa, letto dal nome dell'ultima tabella di backup
 * <prefisso>site_content_bak_AAAAMMGG_HHMM che contiene la risorsa (la pagina è stata davvero modificata in quel momento).
 * Se nessuna tabella la contiene: la data più recente tra publishedon e createdon. Alla fine svuota la cache
 * (le sitemap e il dato strutturato si rigenerano con le date corrette).
 */
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$csv = '';
foreach ($argv as $a) if (strpos($a, '--csv=') === 0) $csv = str_replace('~', getenv('HOME'), substr($a, 6));
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php ripara-editedon.php /www [--csv=file] [--scrivi]\n"); exit(1); }
define('MODX_API_MODE', true);
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new \MODX\Revolution\modX();
$modx->initialize('mgr');
$tab = $modx->getTableName(\MODX\Revolution\modResource::class);
$tabNudo = trim($tab, '`');

$rovinate = [];
foreach ($modx->query("SELECT id, editedon, publishedon, createdon, context_key, uri FROM $tab WHERE editedon BETWEEN 1 AND 100000 ORDER BY id") as $r) {
    $rovinate[(int) $r['id']] = $r;
}
echo '== Risorse con editedon rovinato: ' . count($rovinate) . "\n";
if (!$rovinate) exit(0);

$originali = [];
if ($csv !== '') {
    if (!is_file($csv)) { fwrite(STDERR, "File $csv non trovato\n"); exit(1); }
    foreach (file($csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $riga) {
        [$id, $ts] = array_map('trim', explode(';', $riga) + [1 => '']);
        if (ctype_digit($id) && ctype_digit($ts) && (int) $ts > 100000) $originali[(int) $id] = (int) $ts;
    }
    echo '== Date originali lette dal file: ' . count($originali) . "\n";
}

// tabelle di backup di aggiorna-campi.php: <tab>_bak_AAAAMMGG_HHMM
$bak = [];
$db = $modx->getOption('dbname');
$st = $modx->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ?");
$st->execute([$db, $tabNudo . '\_bak\_%']);
foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $t) {
    if (!preg_match('/_bak_(\d{8})_(\d{4})$/', $t, $m)) continue;
    $quando = DateTime::createFromFormat('YmdHi', $m[1] . $m[2], new DateTimeZone(date_default_timezone_get()));
    if (!$quando) continue;
    foreach ($modx->query("SELECT id FROM `$t`") as $r) {
        $id = (int) $r['id'];
        if (isset($rovinate[$id]) && (!isset($bak[$id]) || $bak[$id] < $quando->getTimestamp())) $bak[$id] = $quando->getTimestamp();
    }
}

$proposte = [];
$conta = ['file' => 0, 'mai modificata' => 0, 'backup' => 0, 'pubblicazione' => 0];
foreach ($rovinate as $id => $r) {
    if (isset($originali[$id])) { $ts = $originali[$id]; $fonte = 'file'; }
    elseif (in_array((int) $r['editedon'], [1969, 1970], true)) { $ts = 0; $fonte = 'mai modificata'; }
    elseif (isset($bak[$id])) { $ts = $bak[$id]; $fonte = 'backup'; }
    else { $ts = max((int) $r['publishedon'], (int) $r['createdon']); $fonte = 'pubblicazione'; }
    if ($ts !== 0 && $ts <= 100000) { echo "  ! $id {$r['uri']}: nessuna data utilizzabile, saltata\n"; continue; }
    $proposte[$id] = $ts; $conta[$fonte]++;
    printf("  %6d %-8s %-55s anno %s → %s (%s)\n", $id, $r['context_key'], mb_substr((string) $r['uri'], 0, 55), $r['editedon'], $ts ? date('Y-m-d H:i', $ts) : '0', $fonte);
}
printf("== Proposte: %d (dal file %d, mai modificate prima: 0 come in origine %d, dal momento della modifica dei campi %d, dalla pubblicazione %d)\n", count($proposte), $conta['file'], $conta['mai modificata'], $conta['backup'], $conta['pubblicazione']);

if (!$scrivi) { echo "== Prova: nessuna modifica. Rilanciare con --scrivi.\n"; exit(0); }
$bakTab = '`' . $tabNudo . '_bak_editedon_' . date('Ymd_Hi') . '`';
$ids = implode(',', array_keys($proposte));
if ($modx->exec("CREATE TABLE $bakTab AS SELECT id, editedon FROM $tab WHERE id IN ($ids)") === false) {
    fwrite(STDERR, "Creazione della tabella di backup fallita: nessuna modifica.\n"); exit(1);
}
$fatte = 0;
foreach ($proposte as $id => $ts) {
    if ($modx->exec("UPDATE $tab SET editedon = " . (int) $ts . " WHERE id = $id AND editedon BETWEEN 1 AND 100000") !== false) $fatte++;
}
$modx->cacheManager->refresh();
// la sitemap di anniazzurri.it (GoogleSiteMap) ha una propria cartella di cache, che refresh() non tocca
$modx->cacheManager->deleteTree(MODX_CORE_PATH . 'cache/googlesitemap/', ['deleteTop' => false, 'skipDirs' => false, 'extensions' => []]);
echo "== $fatte risorse aggiornate; cache svuotata (anche quella della sitemap). Backup: tabella $bakTab\n";
echo "   Ripristino: UPDATE $tab c JOIN $bakTab b ON b.id = c.id SET c.editedon = b.editedon;\n";
