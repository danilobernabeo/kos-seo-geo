<?php
/**
 * kos-link-assoluti-contenuto.php — regola del progetto «link interni sempre assoluti (/...)»: nel campo content delle
 * risorse indicate trasforma href="it/…" e href="en/…" (relativi al percorso, risolti da <base href>) in href="/it/…".
 * Scansione del 18/11: 6 link nell'approfondimento sulla sclerosi multipla.
 *
 * Uso (in SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/kos-link-assoluti-contenuto.php /www it/news-e-media/approfondimenti/sclerosi-multipla-tra-ricerca-cura-e-riabilitazione            # prova
 *   php scripts/deploy/kos-link-assoluti-contenuto.php /www <uri>[,<uri>…] --scrivi
 *   php scripts/deploy/kos-link-assoluti-contenuto.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-link-assoluti/ripristino.json (content prima della modifica). Svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null; $uris = [];
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13);
    elseif (!str_starts_with($a, '--')) $uris = array_filter(array_map(fn($u) => trim($u, '/'), explode(',', $a)));
}
if (!is_file("$webroot/config.core.php") || (!$uris && !$ripFile)) { fwrite(STDERR, "Uso: php kos-link-assoluti-contenuto.php /www <uri,...> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

if ($ripFile) {
    foreach (json_decode((string) file_get_contents($ripFile), true) as $v) {
        $r = $modx->getObject(modResource::class, (int) $v['id']);
        echo "  {$v['id']}: content ripristinato\n";
        if ($scrivi && $r) { $r->set('content', $v['content']); $r->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo $scrivi ? "== Ripristinato\n" : "== PROVA: aggiungere --scrivi\n";
    exit(0);
}

echo $scrivi ? "== APPLICAZIONE\n" : "== PROVA (nessuna scrittura; aggiungere --scrivi)\n";
$rip = []; $daFare = [];
foreach ($uris as $uri) {
    $r = $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0]);
    if (!$r) { echo "  ! /$uri non trovata\n"; continue; }
    $prima = (string) $r->get('content');
    $n = 0;
    $dopo = preg_replace_callback('/\bhref=(["\'])((?:it|en)\/[^"\']*)\1/i', function ($m) use (&$n) { $n++; return 'href=' . $m[1] . '/' . $m[2] . $m[1]; }, $prima);
    if (!$n) { echo "  = {$r->get('id')} /$uri: nessun link relativo\n"; continue; }
    preg_match_all('/\bhref=["\']\/((?:it|en)\/[^"\']*)/i', $dopo, $mm);
    echo "  {$r->get('id')} /$uri: $n link resi assoluti\n";
    foreach (array_unique($mm[1]) as $h) if (str_contains($prima, "\"$h\"") || str_contains($prima, "'$h'")) echo "      /$h\n";
    $rip[] = ['id' => (int) $r->get('id'), 'content' => $prima];
    $daFare[] = [$r, $dopo];
}
if (!$scrivi) { echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
if (!$daFare) { echo "== Niente da fare\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-link-assoluti';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
foreach ($daFare as [$r, $c]) { $r->set('content', $c); $r->save(); }
$modx->cacheManager->refresh();
echo "== Fatto (" . count($daFare) . "). Ripristino: php scripts/deploy/kos-link-assoluti-contenuto.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
