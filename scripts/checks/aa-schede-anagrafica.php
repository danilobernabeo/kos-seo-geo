<?php
/**
 * anniazzurri.it, SOLA LETTURA: fotografia dei dati anagrafici delle schede residenza come sono oggi sul sito, per il
 * confronto con l'export Yext (anniazzurri/dati/schede/strutture-yext-*.json) prima dell'import nelle TV str.*.
 *
 * Per ogni scheda dell'export Yext e per ogni risorsa con la TV data-yext-id valorizzata:
 *   - template, pubblicazione, TV del sito (data-yext-id, numTel, numTelRsaAperta, emailResidenza, emailDirettore,
 *     emailRsaAperta, nomeDirettore, mapsAddress, apertura);
 *   - quale chunk del box contatti richiama il contenuto ([[$contactBox]], [[$contactBox-[[*alias]]]] → nome risolto,
 *     esistenza del chunk), e se il chunk usa i campi Yext (data-yext-field) o dati scritti a mano;
 *   - telefoni, email e indirizzi scritti a mano nel contenuto e nel chunk dei contatti.
 *
 * Uso: php scripts/checks/aa-schede-anagrafica.php /www anniazzurri/dati/schede/strutture-yext-2026-11-06.json [--out=<file.json>]
 * Scrive il JSON (default ~/aa-schede-anagrafica-AAAAMMGG.json) e stampa un riepilogo. Non modifica nulla.
 */
declare(strict_types=1);

$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
[$webroot, $yextFile] = array_pad($pos, 2, '');
$webroot = rtrim($webroot, '/');
if (!is_file("$webroot/config.core.php") || !is_file($yextFile)) {
    fwrite(STDERR, "Uso: php aa-schede-anagrafica.php /www <strutture-yext.json> [--out=<file>]\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';

use MODX\Revolution\{modX, modResource, modChunk, modTemplateVar, modTemplateVarResource};

$modx = modX::getInstance();
$modx->initialize('web');

$yext = json_decode((string) file_get_contents($yextFile), true);
$uris = array_map(fn($r) => $r['uri'], $yext['risorse'] ?? []);

$tvNomi = ['data-yext-id', 'numTel', 'numTelRsaAperta', 'emailResidenza', 'emailDirettore', 'emailRsaAperta',
           'nomeDirettore', 'mapsAddress', 'apertura'];
$tvId = [];
foreach ($tvNomi as $n) { $tv = $modx->getObject(modTemplateVar::class, ['name' => $n]); if ($tv) $tvId[$n] = (int) $tv->get('id'); }

// risorse: quelle dell'export + quelle con data-yext-id valorizzata
$ids = [];
foreach ($uris as $u) {
    $r = $modx->getObject(modResource::class, ['uri:IN' => [$u, "$u/"], 'deleted' => 0]);
    if ($r) $ids[(int) $r->get('id')] = $u;
    else $ids['mancante:' . $u] = $u;
}
if (isset($tvId['data-yext-id'])) {
    $c = $modx->newQuery(modTemplateVarResource::class, ['tmplvarid' => $tvId['data-yext-id'], 'value:!=' => '']);
    foreach ($modx->getIterator(modTemplateVarResource::class, $c) as $v) {
        $id = (int) $v->get('contentid');
        if (!isset($ids[$id])) $ids[$id] = null;
    }
}

$telefoni = function (string $t): array {
    $t = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $t)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match_all('/(?<![\d])(?:\+39[\s.]?)?0\d{1,3}(?:[\s.\/-]?\d){5,9}(?![\d])/', $t, $m);
    $out = [];
    foreach ($m[0] as $x) { $n = preg_replace('/\D/', '', $x); if (str_starts_with($n, '39') && strlen($n) > 10) $n = substr($n, 2); $out[$n] = $x; }
    return $out;
};
$email = function (string $t): array {
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}/', $t, $m);
    return array_values(array_unique(array_map('strtolower', $m[0])));
};
$indirizzi = function (string $t): array {
    $t = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' | ', $t)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match_all('/\b(?:Via|V\.le|Viale|Piazza|P\.za|Corso|C\.so|Strada|Contrada|Località|Loc\.|Largo)\s[^|\n]{3,60}?\d+[\/A-Za-z0-9]*(?:[,\s]+\d{5})?(?:\s[A-ZÀ-Ü][\wÀ-ÿ\' ]{2,30})?/u', $t, $m);
    return array_values(array_unique(array_map('trim', $m[0])));
};

$schede = [];
foreach ($ids as $id => $uriAtteso) {
    if (is_string($id)) { $schede[] = ['uri' => $uriAtteso, 'errore' => 'risorsa non trovata']; continue; }
    $r = $modx->getObject(modResource::class, $id);
    if (!$r) continue;
    $tv = [];
    foreach ($tvId as $n => $tid) {
        $v = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tid, 'contentid' => $id]);
        $tv[$n] = $v ? trim((string) $v->get('value')) : '';
    }
    $content = (string) $r->get('content');
    preg_match_all('/\[\[\$([^?\]\s`]*(?:\[\[\*alias\]\])?[^?\]\s`]*)/', $content, $m);
    $chunkContatti = [];
    foreach (array_unique($m[1]) as $c) {
        if (stripos($c, 'contact') === false) continue;
        $nome = str_replace('[[*alias]]', (string) $r->get('alias'), $c);
        $ch = $modx->getObject(modChunk::class, ['name' => $nome]);
        $testo = $ch ? (string) $ch->get('snippet') : '';
        $chunkContatti[] = [
            'richiamo' => $c, 'chunk' => $nome, 'esiste' => (bool) $ch,
            'campi_yext' => $ch ? preg_match_all('/data-yext-field="([^"]+)"/', $testo, $mm) ? array_values(array_unique($mm[1])) : [] : [],
            'telefoni' => array_values($telefoni($testo)), 'email' => $email($testo), 'indirizzi' => $indirizzi($testo),
        ];
    }
    $schede[] = [
        'id' => $id, 'uri' => $r->get('uri'), 'uri_export' => $uriAtteso, 'pagetitle' => $r->get('pagetitle'),
        'template' => (int) $r->get('template'), 'published' => (int) $r->get('published'), 'alias' => $r->get('alias'),
        'tv' => $tv, 'chunk_contatti' => $chunkContatti,
        'contenuto' => ['telefoni' => array_values($telefoni($content)), 'email' => $email($content), 'indirizzi' => $indirizzi($content),
                        'campi_yext' => preg_match_all('/data-yext-field="([^"]+)"/', $content, $mm) ? array_values(array_unique($mm[1])) : []],
    ];
}

$out = $opt['out'] ?? (getenv('HOME') . '/aa-schede-anagrafica-' . date('Ymd') . '.json');
file_put_contents($out, json_encode(['generato' => date('c'), 'schede' => $schede], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$perChunk = [];
foreach ($schede as $s) foreach ($s['chunk_contatti'] ?? [] as $c) $perChunk[$c['chunk'] . ($c['esiste'] ? '' : ' (NON ESISTE)')][] = $s['id'];
echo "== " . count($schede) . " schede (" . count($uris) . " dall'export Yext)\n";
foreach ($schede as $s) if (isset($s['errore'])) echo "MANCANTE  {$s['uri']}: {$s['errore']}\n";
foreach ($schede as $s) if (!isset($s['errore']) && $s['uri_export'] === null) echo "EXTRA     {$s['id']} {$s['uri']}: data-yext-id {$s['tv']['data-yext-id']} ma non nell'export\n";
echo "== chunk dei contatti richiamati dai contenuti\n";
ksort($perChunk);
foreach ($perChunk as $c => $l) echo sprintf("  %-55s %3d  %s\n", $c, count($l), implode(',', array_slice($l, 0, 12)) . (count($l) > 12 ? ',…' : ''));
foreach ($schede as $s) if (!isset($s['errore']) && !$s['chunk_contatti']) echo "SENZA BOX  {$s['id']} {$s['uri']}\n";
echo "== Scritto $out\n";
