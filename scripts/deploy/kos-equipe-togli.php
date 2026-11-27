<?php
/**
 * kos-equipe-togli.php — toglie dall'«Équipe medica multidisciplinare» di una scheda struttura di kosgroup.com i blocchi
 * statici (div.equipe-persona con il solo ruolo) dei ruoli indicati, nel campo content. Prova, backup, ripristino.
 * Nato il 27/11 per San Martino: le regole esatte di sostituisci-testo.php non trovavano i blocchi perché l'HTML servito
 * non è uguale al contenuto (un plugin aggiunge loading/decoding alle immagini), quindi qui si cerca per struttura.
 *
 * Uso (SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/kos-equipe-togli.php /www --scheda=it/centri/<alias> --ruoli=Cardiologo,Dietologo [--scrivi]
 *   php scripts/deploy/kos-equipe-togli.php /www --ripristina=<cartella di backup>/ripristino.json [--scrivi]
 * Ruoli con spazi: trattino basso al posto dello spazio (Chirurgo_Generale). FERMO se un ruolo non c'è o c'è più di una volta.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$opt = []; $pos = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
$scrivi = isset($opt['scrivi']);
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-equipe-togli.php /www --scheda=it/centri/<alias> --ruoli=A,B [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

if (isset($opt['ripristina'])) {
    $R = json_decode((string) @file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($R)) { fwrite(STDERR, "Ripristino non leggibile\n"); exit(1); }
    $r = $modx->getObject(modResource::class, (int) $R['id']);
    echo ($scrivi ? '== RIPRISTINO' : '== PROVA DI RIPRISTINO (aggiungere --scrivi)') . " risorsa {$R['id']}\n";
    if ($scrivi && $r) { $r->set('content', $R['content']); $r->save(); $modx->cacheManager->refresh(); echo "== Fatto, cache svuotata.\n"; }
    exit(0);
}

$uri = trim((string) ($opt['scheda'] ?? ''), '/');
$ruoli = array_filter(array_map(fn($x) => str_replace('_', ' ', trim($x)), explode(',', (string) ($opt['ruoli'] ?? ''))));
if ($uri === '' || !$ruoli) { fwrite(STDERR, "Indicare --scheda e --ruoli\n"); exit(1); }
$r = $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0]);
if (!$r) { echo "  ! /$uri non trovata — FERMO\n"; exit(2); }
$c = (string) $r->get('content'); $n = $c;
echo ($scrivi ? '== APPLICAZIONE' : '== PROVA (nessuna scrittura; aggiungere --scrivi)') . " — /$uri (id {$r->get('id')})\n";
foreach ($ruoli as $ruolo) {
    $re = '#<div class="col-12 col-md-6 equipe-persona">\s*<img\b[^>]*>\s*<div class="equipe-info">\s*<span class="equipe-ruolo">\s*'
        . preg_quote($ruolo, '#') . '\s*</span>\s*</div>\s*</div>[ \t]*(?:\r?\n[ \t]*)?#u';
    $k = preg_match_all($re, $n);
    if ($k !== 1) { echo "  ! «$ruolo»: trovato $k volte nel contenuto, atteso 1 — FERMO\n"; exit(2); }
    $n = preg_replace($re, '', $n, 1);
    echo "  - «$ruolo» tolto\n";
}
preg_match_all('#<span class="equipe-ruolo">\s*([^<]*?)\s*</span>#u', $n, $m);
echo "  = équipe dopo: " . implode(', ', $m[1]) . "\n";
if (!$scrivi) { echo "== Prova riuscita: aggiungere --scrivi\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-equipe';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode(['id' => (int) $r->get('id'), 'content' => $c], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$r->set('content', $n);
if (!$r->save()) { echo "  ! salvataggio non riuscito\n"; exit(2); }
$modx->cacheManager->refresh();
echo "== Fatto, cache svuotata. Ripristino: php scripts/deploy/kos-equipe-togli.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
