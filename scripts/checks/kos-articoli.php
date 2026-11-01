<?php
/**
 * KOS-10 — articoli e video di kosgroup.com: inventario e prova del dato strutturato, in SOLA LETTURA.
 *
 * Uso (dalla cartella in cui è estratto l'archivio del repository):
 *   php scripts/checks/kos-articoli.php /www                 # inventario per template e sezione, con le anomalie
 *   php scripts/checks/kos-articoli.php /www 892             # in più: il @graph che kosSchema produce per la risorsa 892
 *   php scripts/checks/kos-articoli.php /www --elenco        # in più: id e URI delle pagine fuori dalle sezioni
 *
 * Inventario delle pagine pubblicate dei template informativo, video e progetti-speciali: sezione (cartella di
 * kos.article_sezioni tra gli antenati, o Video 34), immagine (TV infoImage) presente ed esistente, strutture collegate
 * (TV idStruttura), JSON-LD scritto a mano nel contenuto, publishedon mancante, editedon precedente a publishedon,
 * codice YouTube dei video. La prova del @graph esegue lo snippet come lo esegue la pagina, senza scrivere la cache.
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-articoli.php /www [id] [--elenco]\n"); exit(1); }
$prova = 0; $elenco = false;
foreach (array_slice($argv, 2) as $a) { if (ctype_digit($a)) $prova = (int) $a; if ($a === '--elenco') $elenco = true; }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate};
$modx = modX::getInstance();
$modx->initialize('web');

$sez = [];
foreach (array_filter(array_map('trim', explode(',', (string) $modx->getOption('kos.article_sezioni', null, '')))) as $v) {
    [$id, $tipo] = array_map('trim', explode(':', $v, 2)) + [1 => ''];
    $sez[(int) $id] = $tipo;
}
$sez[34] = $sez[34] ?? 'VideoObject';
echo "== Impostazioni: kos.tpl_article «" . $modx->getOption('kos.tpl_article') . "», kos.article_sezioni «" . $modx->getOption('kos.article_sezioni')
   . "», kos.tpl_video «" . $modx->getOption('kos.tpl_video') . "», kos.struttura_attiva «" . $modx->getOption('kos.struttura_attiva') . "»\n";
foreach ($sez as $id => $tipo) {
    $r = $modx->getObject(modResource::class, $id);
    echo "   sezione $id: " . ($r ? $r->get('pagetitle') . ' (' . $r->get('uri') . ')' : 'ASSENTE') . " → $tipo\n";
}

$sezioneDi = function (modResource $r) use ($modx, $sez): int {
    $rid = (int) $r->get('parent');
    for ($i = 0; $rid > 0 && $i < 50; $i++) {
        if (isset($sez[$rid])) return $rid;
        $p = $modx->getObject(modResource::class, $rid);
        if (!$p) break;
        $rid = (int) $p->get('parent');
    }
    return 0;
};
$ts = fn($v) => is_numeric($v) ? (int) $v : (int) strtotime((string) $v);

foreach (['informativo', 'video', 'progetti-speciali'] as $nome) {
    $t = $modx->getObject(modTemplate::class, ['templatename' => $nome]);
    if (!$t) { echo "== Template $nome assente\n"; continue; }
    $conta = []; $anomalie = []; $fuori = [];
    foreach ($modx->getIterator(modResource::class, ['template' => $t->get('id'), 'published' => 1, 'deleted' => 0]) as $r) {
        $s = $sezioneDi($r);
        $conta[$s] = ($conta[$s] ?? 0) + 1;
        $id = (int) $r->get('id');
        if (!$s) $fuori[] = "$id " . $r->get('uri');
        $img = trim((string) $r->getTVValue('infoImage'));
        $f = ltrim(rawurldecode((string) preg_replace('/[?#].*$/', '', $img)), '/');
        if ($img === '') $anomalie['senza infoImage'][] = $id;
        elseif (!preg_match('#^https?://#', $img) && !is_file(MODX_BASE_PATH . $f)) $anomalie['infoImage con file mancante'][] = $id;
        elseif (($d = @getimagesize(MODX_BASE_PATH . $f)) && $d[0] * $d[1] < 50000) $anomalie['immagine sotto 50.000 pixel'][] = $id;
        if (trim((string) $r->getTVValue('idStruttura')) !== '') $anomalie['(info) con strutture collegate'][] = $id;
        if (stripos((string) $r->get('content'), 'ld+json') !== false) $anomalie['JSON-LD scritto a mano nel contenuto'][] = $id;
        if (!$ts($r->get('publishedon'))) $anomalie['publishedon vuoto (si usa createdon)'][] = $id;
        elseif ($ts($r->get('editedon')) && $ts($r->get('editedon')) < $ts($r->get('publishedon'))) $anomalie['(info) editedon prima di publishedon'][] = $id;
        if ($nome === 'video' && !preg_match('~^([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])|(?:youtu\.be/|/embed/|[?&]v=)[A-Za-z0-9_-]{11}~', trim((string) $r->getTVValue('embedCode'))))
            $anomalie['codice YouTube non valido (resta WebPage)'][] = $id;
        if (mb_strlen((string) $r->get('pagetitle')) > 110) $anomalie['pagetitle oltre 110 caratteri'][] = $id;
    }
    echo "== Template $nome: " . array_sum($conta) . " pagine pubblicate\n";
    ksort($conta);
    foreach ($conta as $s => $n) echo "   " . ($s ? "sezione $s ({$sez[$s]})" : 'fuori dalle sezioni (WebPage)') . ": $n\n";
    foreach ($anomalie as $k => $ids) echo "   $k: " . count($ids) . ' (' . implode(',', array_slice($ids, 0, 15)) . (count($ids) > 15 ? ',…' : '') . ")\n";
    if ($elenco) foreach ($fuori as $f) echo "     fuori: $f\n";
}

if ($prova) {
    $r = $modx->getObject(modResource::class, $prova);
    if (!$r) { echo "== Risorsa $prova assente\n"; exit(2); }
    $modx->resource = $r;
    $out = (string) $modx->runSnippet('kosSchema', ['pretty' => 1]);
    echo "== @graph di kosSchema per la risorsa $prova (" . $r->get('uri') . "), blocchi JSON-LD: " . substr_count($out, 'application/ld+json') . "\n";
    $j = json_decode((string) preg_replace('#^.*?<script type="application/ld\+json">(.*)</script>.*$#s', '$1', $out), true);
    echo $j ? json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n" : "JSON NON VALIDO:\n$out\n";
}
