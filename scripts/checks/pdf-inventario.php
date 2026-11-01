<?php
/**
 * KOS-8 / AA-3 — Inventario dei PDF di un sito (passo 1 del brief docs/pdf/brief-pdf.md). SOLA LETTURA:
 * non tocca né i file né il database; scrive solo in ~ (il CSV).
 *
 * Per ogni PDF sotto <webroot>/assets (e nella radice): percorso, dimensione, data, impronta md5 (per i documenti
 * identici in più cartelle), numero di pagine, titolo nei metadati (dizionario Info e XMP dc:title), caratteri e
 * inizio del testo della prima pagina (0 caratteri = probabile scansione), e dove il sito lo richiama:
 *   - link diretti: contenuto delle risorse, valori delle TV (es. comunicatoPdf), chunk e template;
 *   - elenchi automatici: pagine che richiamano docList (cartella = &input con [[*alias]] sostituito) o listLda
 *     (cartella assets/doc/strutture/<alias padre>/<alias pagina>/<anno>/), anche attraverso template e chunk annidati.
 *
 * Uso:   php scripts/checks/pdf-inventario.php /www aa|kos [--senza-modx]
 * Output: ~/pdf-inventario-<sito>-<AAAAMMGG>.csv (separatore «;», UTF-8 con BOM) e un riepilogo a video.
 *   --senza-modx  solo il file system (niente collegamenti alle pagine): per provarlo fuori dal cloud.
 * Estrazione: pdfinfo/pdftotext se presenti sul cloud, altrimenti la libreria PHP in scripts/vendor/pdfparser.
 *
 * ATTENZIONE: l'inizio del testo può contenere dati personali (liste d'attesa, moduli): il CSV non va nel repository.
 */
declare(strict_types=1);

use MODX\Revolution\modX;

const TESTO_MAX = 800;   // caratteri dell'inizio del testo della prima pagina nel CSV

/* ---------- riferimenti ai PDF nei testi (contenuti, TV, chunk, template) ---------- */

/** Percorsi «/assets/...pdf» citati in un testo, in chiaro (decodifica di JSON, entità HTML e %xx). */
function riferimenti(string $t): array {
    if (stripos($t, '.pdf') === false) return [];
    $t = str_replace('\\/', '/', $t);
    $t = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn($m) => mb_chr((int) hexdec($m[1]), 'UTF-8'), $t) ?? $t;
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $out = [];
    if (preg_match_all('#assets/[^"<>\\\\\r\n]*?\.pdf(?![A-Za-z0-9])#i', $t, $m)) {
        foreach ($m[0] as $r) {
            $r = substr($r, (int) strrpos($r, 'assets/'));          // «href='x.jpg' href='assets/…pdf'» → solo l'ultimo
            $out['/' . rawurldecode($r)] = true;
        }
    }
    return array_keys($out);
}

/** Il testo richiama (direttamente o tramite chunk annidati) lo snippet $lister? Con $input raccoglie i suoi &input. */
function richiamaLista(string $t, string $lister, array $chunks, array $visita = [], ?array &$input = null): bool {
    $trovato = false;
    if (preg_match_all('/\[\[!?' . $lister . '\b[^\]]*?&input=`([^`]*)`/i', $t, $m)) {
        $trovato = true;
        if ($input !== null) foreach ($m[1] as $i) $input[] = $i;
    }
    if (preg_match('/\[\[!?' . $lister . '\b/i', $t)) $trovato = true;
    if (preg_match_all('/\[\[!?\$([A-Za-z0-9_.\-]+)/', $t, $m)) {
        foreach (array_unique($m[1]) as $nome) {
            if (isset($visita[$nome]) || !isset($chunks[$nome])) continue;
            if (richiamaLista($chunks[$nome], $lister, $chunks, $visita + [$nome => true], $input)) $trovato = true;
        }
    }
    return $trovato;
}

/* ---------- lettura dei PDF ---------- */

function xmpTitolo(string $dati): string {
    if (!preg_match('#<dc:title\b[^>]*>(.*?)</dc:title>#s', $dati, $m)) return '';
    $t = preg_match('#<rdf:li[^>]*>(.*?)</rdf:li>#s', $m[1], $l) ? $l[1] : strip_tags($m[1]);
    return trim(html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function pulisci(string $s): string {
    if (!mb_check_encoding($s, 'UTF-8')) $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return trim($s);
}

/** @return array{pagine:string, titolo:string, testo:string, errore:string} */
function leggiPdf(string $f, string $pdfinfo, string $pdftotext, ?object $parser): array {
    $r = ['pagine' => '', 'titolo' => '', 'testo' => '', 'errore' => ''];
    if ($pdfinfo !== '' && $pdftotext !== '') {
        $righe = [];
        exec(escapeshellarg($pdfinfo) . ' -enc UTF-8 ' . escapeshellarg($f) . ' 2>&1', $righe, $rc);
        $info = implode("\n", $righe);
        if ($rc !== 0) $r['errore'] = 'pdfinfo: ' . pulisci(mb_substr($info, 0, 120));
        if (preg_match('/^Pages:\s*(\d+)/m', $info, $m)) $r['pagine'] = $m[1];
        if (preg_match('/^Title:[ \t]*(.*)$/m', $info, $m)) $r['titolo'] = trim($m[1]);
        if (preg_match('/^Encrypted:\s+yes/m', $info)) $r['errore'] = trim($r['errore'] . ' cifrato');
        if ($rc === 0) $r['testo'] = pulisci((string) shell_exec(escapeshellarg($pdftotext) . ' -f 1 -l 1 -enc UTF-8 ' . escapeshellarg($f) . ' - 2>/dev/null'));
        return $r;
    }
    try {
        $doc = $parser->parseFile($f);
        $d = $doc->getDetails();
        $t = $d['Title'] ?? '';
        $r['titolo'] = trim(is_array($t) ? implode(' ', $t) : (string) $t);
        $p = $doc->getPages();
        $r['pagine'] = (string) count($p);
        $r['testo'] = $p ? pulisci($p[0]->getText()) : '';
    } catch (\Throwable $e) {
        $r['errore'] = 'pdfparser: ' . pulisci(mb_substr($e->getMessage(), 0, 120));
    }
    return $r;
}

/* ---------- programma principale (non parte se il file è incluso da un test) ---------- */

if (PHP_SAPI !== 'cli' || realpath($argv[0] ?? '') !== realpath(__FILE__)) return;

$webroot = rtrim($argv[1] ?? '', '/');
$sito    = $argv[2] ?? '';
$senzaDb = in_array('--senza-modx', $argv, true);
if ($webroot === '' || !is_dir("$webroot/assets") || !in_array($sito, ['aa', 'kos'], true)) {
    fwrite(STDERR, "Uso: php pdf-inventario.php /www aa|kos [--senza-modx]\n"); exit(1);
}
ini_set('memory_limit', '1536M');
set_time_limit(0);
mb_internal_encoding('UTF-8');

/* 1. elenco dei PDF */
$files = []; $altri = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$webroot/assets", FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower($f->getExtension());
    $rel = substr($f->getPathname(), strlen($webroot));
    if ($ext === 'pdf') $files[$rel] = $f->getPathname();
    elseif (str_starts_with($rel, '/assets/doc/')) $altri[$ext ?: '(senza estensione)'] = ($altri[$ext ?: '(senza estensione)'] ?? 0) + 1;
}
foreach (glob("$webroot/*.{pdf,PDF}", GLOB_BRACE) ?: [] as $f) $files['/' . basename($f)] = $f;
uksort($files, 'strnatcasecmp');

/* 2. collegamenti dal database */
$link = []; $elenco = []; $elementi = []; $cartelle = []; $noModx = '';
if (!$senzaDb) {
    if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "config.core.php non trovato in $webroot\n"); exit(1); }
    require "$webroot/config.core.php";
    require MODX_CORE_PATH . 'vendor/autoload.php';
    $modx = modX::getInstance();
    $modx->initialize('mgr');
    $modx->connect();
    $db = $modx->pdo;
    $p = $modx->getOption('table_prefix', null, 'modx_');

    $chunks = []; $tpl = [];
    foreach ($db->query("SELECT name, snippet FROM {$p}site_htmlsnippets", PDO::FETCH_NUM) as [$n, $c]) $chunks[$n] = (string) $c;
    foreach ($db->query("SELECT id, templatename, content FROM {$p}site_templates", PDO::FETCH_NUM) as [$id, $n, $c]) $tpl[(int) $id] = [$n, (string) $c];
    $tvNome = [];
    foreach ($db->query("SELECT id, name FROM {$p}site_tmplvars", PDO::FETCH_NUM) as [$id, $n]) $tvNome[(int) $id] = $n;

    $res = [];
    foreach ($db->query("SELECT id, context_key, uri, alias, parent, published, template, content FROM {$p}site_content WHERE deleted = 0", PDO::FETCH_ASSOC) as $r) {
        $id = (int) $r['id'];
        $res[$id] = $r;
    }
    $etichetta = function (int $id) use (&$res): string {
        $r = $res[$id];
        return ($r['context_key'] !== 'web' ? "[{$r['context_key']}] " : '') . "$id /" . ltrim((string) $r['uri'], '/') . ($r['published'] ? '' : ' (non pubblicata)');
    };

    // link diretti: contenuto delle risorse, TV, chunk, template
    foreach ($res as $id => $r) foreach (riferimenti((string) $r['content']) as $f) $link[$f][$etichetta($id)] = true;
    foreach ($db->query("SELECT contentid, tmplvarid, value FROM {$p}site_tmplvar_contentvalues WHERE value LIKE '%.pdf%'", PDO::FETCH_NUM) as [$cid, $tid, $v]) {
        if (!isset($res[(int) $cid])) continue;
        foreach (riferimenti((string) $v) as $f) $link[$f][$etichetta((int) $cid) . ' [TV ' . ($tvNome[(int) $tid] ?? $tid) . ']'] = true;
    }
    foreach ($chunks as $n => $c) foreach (riferimenti($c) as $f) $elementi[$f]["chunk $n"] = true;
    foreach ($tpl as [$n, $c]) foreach (riferimenti($c) as $f) $elementi[$f]["template $n"] = true;

    // elenchi automatici: cartella → pagine che la elencano
    foreach ($res as $id => $r) {
        $testi = (string) $r['content'];
        if (isset($tpl[(int) $r['template']])) $testi .= "\n" . $tpl[(int) $r['template']][1];
        $input = [];
        if (richiamaLista($testi, 'docList', $chunks, [], $input)) {
            foreach (array_unique($input) as $i) {
                $dir = trim(str_replace('[[*alias]]', (string) $r['alias'], $i), '/');
                if (!str_contains($dir, '[[')) $cartelle[$dir][$etichetta($id)] = true;
            }
        }
        if (richiamaLista($testi, 'listLda', $chunks) && isset($res[(int) $r['parent']])) {
            $cartelle['assets/doc/strutture/' . $res[(int) $r['parent']]['alias'] . '/' . $r['alias']][$etichetta($id)] = true;   // + /<anno>
        }
    }
    unset($res, $chunks, $tpl);
} else {
    $noModx = ' (senza collegamenti alle pagine: --senza-modx)';
}

/* 3. lettura dei file */
$pdftotext = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
$pdfinfo   = trim((string) shell_exec('command -v pdfinfo 2>/dev/null'));
$parser = null;
if ($pdftotext === '' || $pdfinfo === '') {
    spl_autoload_register(function ($c) {
        if (str_starts_with($c, 'Smalot\\PdfParser\\')) {
            $f = __DIR__ . '/../vendor/pdfparser/src/' . str_replace('\\', '/', $c) . '.php';
            if (is_file($f)) require $f;
        }
    });
    $parser = new \Smalot\PdfParser\Parser();
}
$strumento = ($pdftotext !== '' && $pdfinfo !== '') ? 'pdfinfo + pdftotext' : 'libreria PHP pdfparser';

$home = rtrim((string) getenv('HOME'), '/');
$csvFile = "$home/pdf-inventario-$sito-" . date('Ymd') . '.csv';
$csv = fopen($csvFile, 'w');
fwrite($csv, "\xEF\xBB\xBF");
fputcsv($csv, ['percorso', 'byte', 'modificato', 'md5', 'pagine', 'titolo_info', 'titolo_xmp', 'testo_pag1_caratteri', 'testo_pag1_inizio',
               'n_pagine_link', 'pagine_link', 'n_pagine_elenco', 'pagine_elenco', 'altri_elementi', 'errore'], ';');

$tot = 0; $bytes = 0; $perCartella = []; $senzaTitolo = 0; $senzaTesto = 0; $errori = 0; $senzaCollegamenti = 0; $md5 = [];
foreach ($files as $rel => $path) {
    $dati = (string) file_get_contents($path);
    $hash = md5($dati);
    $l = leggiPdf($path, $pdfinfo, $pdftotext, $parser);
    $xmp = xmpTitolo(substr($dati, 0, 4000000));
    unset($dati);
    $dir = ltrim(dirname($rel), '/');
    $pl = array_keys($link[$rel] ?? []);
    $pe = array_keys($cartelle[$dir] ?? []);
    if (preg_match('#^assets/doc/strutture/([^/]+)/([^/]+)/[^/]+$#', $dir, $m)) $pe = array_merge($pe, array_keys($cartelle["assets/doc/strutture/{$m[1]}/{$m[2]}"] ?? []));
    $pe = array_values(array_unique($pe));
    $ae = array_keys($elementi[$rel] ?? []);
    fputcsv($csv, [$rel, filesize($path), date('Y-m-d', (int) filemtime($path)), $hash, $l['pagine'], $l['titolo'], $xmp,
                   mb_strlen($l['testo']), mb_substr($l['testo'], 0, TESTO_MAX),
                   count($pl), implode(' | ', $pl), count($pe), implode(' | ', $pe), implode(' | ', $ae), $l['errore']], ';');
    $tot++; $bytes += filesize($path); $md5[$hash][] = $rel;
    $k = implode('/', array_slice(explode('/', trim($rel, '/')), 0, 3));
    $perCartella[$k] = ($perCartella[$k] ?? 0) + 1;
    if ($l['titolo'] === '' && $xmp === '') $senzaTitolo++;
    if ($l['testo'] === '' && $l['errore'] === '') $senzaTesto++;
    if ($l['errore'] !== '') $errori++;
    if (!$pl && !$pe && !$ae) $senzaCollegamenti++;
}
fclose($csv);

/* 4. riepilogo */
ksort($perCartella);
$doppi = array_filter($md5, fn($v) => count($v) > 1);
echo "== Sito: $sito, webroot $webroot$noModx\n";
echo "== PDF: $tot, " . round($bytes / 1048576, 1) . " MB (estrazione: $strumento)\n";
foreach ($perCartella as $k => $n) echo sprintf("   %5d  /%s\n", $n, $k);
echo "== Senza titolo nei metadati (né Info né XMP): $senzaTitolo\n";
echo "== Senza testo nella prima pagina (scansioni?): $senzaTesto\n";
echo "== Con errore di lettura: $errori\n";
echo "== Identici (stesso md5) in più percorsi: " . count($doppi) . ' gruppi, ' . array_sum(array_map('count', $doppi)) . " file\n";
if (!$senzaDb) {
    echo "== Senza alcun collegamento (né link né elenco né chunk/template): $senzaCollegamenti\n";
    $mancanti = array_diff(array_merge(array_keys($link), array_keys($elementi)), array_keys($files));
    echo '== Link a PDF che non esistono sul server (controllo del riconoscimento dei link): ' . count($mancanti) . "\n";
    foreach (array_slice($mancanti, 0, 5) as $m) echo "   $m\n";
}
if ($altri) { ksort($altri); echo '== Altri file in assets/doc: ' . implode(', ', array_map(fn($e, $n) => "$e $n", array_keys($altri), $altri)) . "\n"; }
echo "== Scritto: $csvFile\n";
