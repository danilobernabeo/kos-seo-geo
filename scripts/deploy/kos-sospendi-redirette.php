<?php
/**
 * kos-sospendi-redirette.php — rende «non pubblicate» le risorse il cui indirizzo è reindirizzato da una regola web
 * (diagnostica scripts/checks/kos-pagine-redirette.php del 18/11). Il visitatore non cambia esperienza (il 301 resta),
 * ma gli elenchi degli approfondimenti non le mostrano più e spariscono i link interni verso un redirect.
 * Non cancella nulla: contenuto e campi restano, si ripubblicano con il ripristino.
 *
 * Uso (in SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/kos-sospendi-redirette.php /www 237,308,421,460,468,489            # prova
 *   php scripts/deploy/kos-sospendi-redirette.php /www 237,308,421,460,468,489 --scrivi   # applica
 *   php scripts/deploy/kos-sospendi-redirette.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-redirette/ripristino.json (stato di pubblicazione prima della modifica).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null; $ids = [];
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13);
    elseif (preg_match('/^[0-9,]+$/', $a)) $ids = array_map('intval', array_filter(explode(',', $a)));
}
if (!is_file("$webroot/config.core.php") || (!$ids && !$ripFile)) { fwrite(STDERR, "Uso: php kos-sospendi-redirette.php /www <id,id,...> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

if ($ripFile) {
    foreach (json_decode((string) file_get_contents($ripFile), true) as $v) {
        $r = $modx->getObject(modResource::class, (int) $v['id']);
        echo "  {$v['id']}: pubblicata " . ($r ? $r->get('published') : '?') . " → {$v['published']}\n";
        if ($scrivi && $r) { $r->set('published', (int) $v['published']); $r->set('publishedon', (int) $v['publishedon']); $r->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo $scrivi ? "== Ripristinato\n" : "== PROVA: aggiungere --scrivi\n";
    exit(0);
}

$regole = is_file(__DIR__ . '/../../kosgroup/files/web-rules/web-rules.conf') ? (string) file_get_contents(__DIR__ . '/../../kosgroup/files/web-rules/web-rules.conf') : '';
echo $scrivi ? "== APPLICAZIONE\n" : "== PROVA (nessuna scrittura; aggiungere --scrivi)\n";
$rip = []; $fermo = 0; $daFare = [];
foreach ($ids as $id) {
    $r = $modx->getObject(modResource::class, $id);
    if (!$r) { echo "  ! $id non esiste — FERMO\n"; $fermo++; continue; }
    $uri = '/' . trim((string) $r->get('uri'), '/');
    $coperta = $regole === '' || preg_match('/location\s+=\s+' . preg_quote($uri, '/') . '\s*\{\s*return\s+30[18]/', $regole);
    if (!$coperta) { echo "  ! $id $uri non ha una regola 301 nel file delle regole — FERMO\n"; $fermo++; continue; }
    if (!$r->get('published')) { echo "  = $id $uri già non pubblicata\n"; continue; }
    echo "  $id $uri «{$r->get('pagetitle')}»: pubblicata 1 → 0\n";
    $rip[] = ['id' => $id, 'published' => 1, 'publishedon' => (int) $r->get('publishedon')];
    $daFare[] = $r;
}
if ($fermo) { echo "== FERMO su $fermo risorse: nessuna scrittura\n"; exit(2); }
if (!$scrivi) { echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
if (!$daFare) { echo "== Niente da fare\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-redirette';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT));
foreach ($daFare as $r) { $r->set('published', 0); $r->save(); }
$modx->cacheManager->refresh();
echo "== Fatto (" . count($daFare) . "). Ripristino: php scripts/deploy/kos-sospendi-redirette.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
