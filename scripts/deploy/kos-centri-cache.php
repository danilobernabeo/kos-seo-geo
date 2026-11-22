<?php
/**
 * kos-centri-cache.php — KOS 2 (tempo di risposta): chiamate non in cache [[!...]] messe in cache nel CONTENUTO e nelle
 * TV delle schede dei centri (template 2 «strutture» e 4 «strutture-VDP») e della pagina
 * it/chi-siamo/qualita-delle-cure/medici. Dalla SQL della chat prestazioni (monitor/centri-cache-20261122.sql), resa
 * ripetibile con prova, backup e ripristino; in più [[!galleryStruttura (elemento del progetto, uscita deterministica).
 *
 * Chiamate toccate: Wayfinder, pdoMenu, getResources, getMedici (anche getMediciStrutturaOrdered), myBreadcrumb,
 * galleryStruttura. NON toccate: barraPrenotazioneDBmedicoPrestazione (si mette in cache da sé, snippet nel pacchetto
 * kos-2-centri-cache), mostraFino (dipende dalla data). L'HTML prodotto non cambia: la chiamata gira una volta e la
 * pagina va nella cache di MODX, che si svuota a ogni salvataggio di risorsa.
 *
 * Uso (SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/kos-centri-cache.php /www                 # prova: righe e chiamate che cambierebbero
 *   php scripts/deploy/kos-centri-cache.php /www --scrivi        # tabelle *_bak_AAAAMMGG, ripristino.json, modifica, verifica
 *   php scripts/deploy/kos-centri-cache.php /www --ripristina=<cartella>/ripristino.json --scrivi
 * Backup: ~/backup-kos-seo-geo/<data>-centri-cache/ripristino.json e tabelle modx_site_content_bak_<data>c,
 * modx_site_tmplvar_contentvalues_bak_<data>c (solo righe interessate). Svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null;
foreach (array_slice($argv, 2) as $a) if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13);
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-centri-cache.php /www [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$C = "{$p}site_content"; $V = "{$p}site_tmplvar_contentvalues";

$NOMI = ['Wayfinder', 'pdoMenu', 'getResources', 'getMedici', 'myBreadcrumb', 'galleryStruttura'];
$re = '/\[\[!(' . implode('|', $NOMI) . ')/';
$ambito = "c.deleted = 0 AND (c.template IN (2, 4) OR c.uri = 'it/chi-siamo/qualita-delle-cure/medici')";
$like = '(' . implode(' OR ', array_map(fn($n) => "X LIKE '%[[!$n%'", $NOMI)) . ')';

if ($ripFile) {
    $righe = json_decode((string) file_get_contents($ripFile), true);
    $n = 0;
    foreach ($righe as $r) {
        if ($r['tabella'] === 'content') {
            $st = $modx->prepare("UPDATE $C SET content = ? WHERE id = ?");
        } else {
            $st = $modx->prepare("UPDATE $V SET value = ? WHERE id = ?");
        }
        if ($scrivi) $st->execute([$r['valore'], $r['id']]);
        $n++;
    }
    echo ($scrivi ? "Ripristinate" : "Da ripristinare (prova)") . ": $n righe\n";
    if ($scrivi) { $modx->cacheManager->refresh(); echo "Cache svuotata.\n"; }
    exit(0);
}

function righe(modX $modx, string $C, string $V, string $ambito, string $like): array {
    $out = [];
    $q = $modx->query("SELECT c.id, c.uri, c.content AS valore FROM $C c WHERE $ambito AND " . str_replace('X', 'c.content', $like));
    foreach ($q ?: [] as $r) $out[] = ['tabella' => 'content', 'id' => (int) $r['id'], 'risorsa' => (int) $r['id'], 'uri' => $r['uri'], 'valore' => $r['valore']];
    $q = $modx->query("SELECT v.id, v.contentid, c.uri, v.tmplvarid, v.value AS valore FROM $V v JOIN $C c ON c.id = v.contentid WHERE $ambito AND " . str_replace('X', 'v.value', $like));
    foreach ($q ?: [] as $r) $out[] = ['tabella' => 'tv', 'id' => (int) $r['id'], 'risorsa' => (int) $r['contentid'], 'tv' => (int) $r['tmplvarid'], 'uri' => $r['uri'], 'valore' => $r['valore']];
    return $out;
}

$righe = righe($modx, $C, $V, $ambito, $like);
$conta = ['content' => 0, 'tv' => 0]; $chiamate = array_fill_keys($NOMI, 0); $risorse = [];
foreach ($righe as $r) {
    $conta[$r['tabella']]++; $risorse[$r['uri']] = true;
    preg_match_all($re, $r['valore'], $m);
    foreach ($m[1] as $nome) $chiamate[$nome]++;
}
echo "== " . ($scrivi ? 'APPLICAZIONE' : 'PROVA') . " — chiamate [[!...]] in cache nelle schede dei centri\n";
echo "Righe: contenuti {$conta['content']}, valori TV {$conta['tv']}; risorse " . count($risorse) . "\n";
foreach ($chiamate as $n => $k) echo "  [[!$n: $k\n";
if (!$righe) { echo "Niente da fare.\n"; exit(0); }
if (!$scrivi) { echo "Prova: nessuna modifica. Rilanciare con --scrivi.\n"; exit(0); }

$data = date('Ymd');
$dir = getenv('HOME') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-centri-cache';
@mkdir($dir, 0775, true);
file_put_contents("$dir/ripristino.json", json_encode($righe, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$bc = "{$C}_bak_{$data}c"; $bv = "{$V}_bak_{$data}c";
$modx->exec("CREATE TABLE IF NOT EXISTS $bc AS SELECT c.* FROM $C c WHERE $ambito");
$modx->exec("CREATE TABLE IF NOT EXISTS $bv AS SELECT v.* FROM $V v JOIN $C c ON c.id = v.contentid WHERE $ambito AND " . str_replace('X', 'v.value', $like));

$stc = $modx->prepare("UPDATE $C SET content = ? WHERE id = ?");
$stv = $modx->prepare("UPDATE $V SET value = ? WHERE id = ?");
foreach ($righe as $r) {
    $nuovo = preg_replace($re, '[[$1', $r['valore']);
    ($r['tabella'] === 'content' ? $stc : $stv)->execute([$nuovo, $r['id']]);
}
$resto = righe($modx, $C, $V, $ambito, $like);
echo "Verifica: righe ancora con chiamate non in cache: " . count($resto) . " (atteso 0)\n";
$modx->cacheManager->refresh();
echo "== Cache svuotata. Backup: tabelle $bc e $bv; ripristino: $dir/ripristino.json\n";
