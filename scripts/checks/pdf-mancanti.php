<?php
/**
 * AA-3 / KOS-8 — PDF mancanti: dal log degli errori di nginx (404) alla proposta di intervento. SOLA LETTURA.
 *
 * Uso:  php scripts/checks/pdf-mancanti.php /www /log/nginx_error.log 2026/10/17
 * Output: riepilogo a video e ~/pdf-mancanti-<data>.csv
 *
 * Per ogni PDF richiesto e non trovato:
 *   - quante richieste dalla data indicata;
 *   - se la cartella esiste e quali PDF contiene di nome simile (maiuscole, spazi, trattini, anno diverso), o un file
 *     con lo stesso nome in un'altra cartella di assets/doc;
 *   - dove il sito lo richiama: contenuti delle risorse, chunk, template, valori delle TV (cerca il nome del file,
 *     anche codificato);
 *   - azione proposta:
 *       CORREGGI LINK  il sito lo richiama ed esiste un file simile → aggiornare il link (dove indicato)
 *       RIPRISTINA     il sito lo richiama e non c'è un file simile → recuperare il documento o togliere il link
 *       REDIRECT       il sito non lo richiama (link esterni o vecchi) ed esiste un file simile → 301
 *       NESSUNA        il sito non lo richiama e non c'è un file simile → il 404 è corretto
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
$log     = $argv[2] ?? '/log/nginx_error.log';
$da      = $argv[3] ?? '';
if ($webroot === '' || !is_file("$webroot/config.core.php") || !is_file($log) || !preg_match('#^\d{4}/\d{2}/\d{2}$#', $da)) {
    fwrite(STDERR, "Uso: php pdf-mancanti.php /www /log/nginx_error.log AAAA/MM/GG\n"); exit(1);
}

/* ---------- 1. PDF non trovati dal log ---------- */
$conta = [];
$fh = fopen($log, 'r');
while (($r = fgets($fh)) !== false) {
    if (strncmp($r, $da, 10) < 0 || !preg_match('#^\d{4}/\d{2}/\d{2}#', $r)) continue;
    if (strpos($r, 'No such file or directory') === false) continue;
    if (preg_match('#open\(\) "/c\d+/www([^"]+\.pdf)"#i', $r, $m)) $conta[$m[1]] = ($conta[$m[1]] ?? 0) + 1;
}
fclose($fh);
arsort($conta);
if (!$conta) { echo "Nessun PDF mancante dal $da.\n"; exit(0); }

/* ---------- 2. MODX in sola lettura ---------- */
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$modx->connect();
$db = $modx->pdo;

function dove(PDO $db, string $p, string $rel): array {
    // cartella + nome del file (es. «villa-a/Carta dei servizi.pdf»), in chiaro e codificato: il solo nome del file
    // darebbe falsi riscontri con i documenti omonimi delle altre strutture
    $nome = basename($rel);
    $cart = basename(dirname($rel));
    $vari = [];
    foreach ([$nome, rawurlencode($nome), str_replace(' ', '%20', $nome), htmlspecialchars($nome)] as $v) $vari[] = "$cart/$v";
    $vari = array_unique($vari);   // LIKE BINARY: maiuscole comprese, come i nomi dei file sul server
    $out = [];
    foreach ($vari as $v) {
        $like = '%' . $v . '%';
        foreach ([
            ["SELECT id, pagetitle FROM {$p}site_content WHERE deleted = 0 AND content LIKE BINARY ?", 'risorsa'],
            ["SELECT id, name FROM {$p}site_htmlsnippets WHERE snippet LIKE BINARY ?", 'chunk'],
            ["SELECT id, templatename FROM {$p}site_templates WHERE content LIKE BINARY ?", 'template'],
            ["SELECT contentid, tmplvarid FROM {$p}site_tmplvar_contentvalues WHERE value LIKE BINARY ?", 'TV della risorsa'],
        ] as [$sql, $tipo]) {
            $st = $db->prepare($sql);
            $st->execute([$like]);
            foreach ($st->fetchAll(PDO::FETCH_NUM) as $row) $out["$tipo {$row[0]}"] = "$tipo {$row[0]} ({$row[1]})";
        }
    }
    return array_values($out);
}

function norma(string $s): string {
    $s = strtolower(pathinfo($s, PATHINFO_FILENAME));
    $s = preg_replace('/(19|20)\d{2}/', '', $s);          // anno: «PARM 2024» ~ «PARM 2025»
    return preg_replace('/[^a-z0-9]+/', '', $s);
}

$tuttiDoc = null;   // indice dei PDF di assets/doc per nome normalizzato (creato alla prima richiesta)
function simili(string $webroot, string $rel, ?array &$tuttiDoc): array {
    $dir = dirname($webroot . $rel);
    $n = norma($rel);
    $c = [];
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.{pdf,PDF}', GLOB_BRACE) ?: [] as $f) {
            $m = norma($f);
            similar_text($n, $m, $pct);
            if ($m === $n || $pct >= 75) $c[substr($f, strlen($webroot))] = (int) round($m === $n ? 100 : $pct);
        }
    }
    if (!$c) {                                            // stesso nome in un'altra cartella
        if ($tuttiDoc === null) {
            $tuttiDoc = [];
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($webroot . '/assets/doc', FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) if (strtolower($f->getExtension()) === 'pdf') $tuttiDoc[norma($f->getFilename())][] = substr($f->getPathname(), strlen($webroot));
        }
        // solo se il nome è unico in tutto assets/doc: «Carta dei servizi» esiste in decine di cartelle e non dice nulla
        if (count($tuttiDoc[$n] ?? []) === 1) $c[$tuttiDoc[$n][0]] = 90;
    }
    arsort($c);
    return array_slice($c, 0, 3, true);
}

/* ---------- 3. analisi ---------- */
$csv = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/pdf-mancanti-' . date('Ymd-His') . '.csv';
$out = fopen($csv, 'w');
fputcsv($out, ['pdf', 'richieste', 'cartella esiste', 'file simili (somiglianza %)', 'richiamato da', 'azione']);
$azioni = [];
foreach ($conta as $rel => $n) {
    $nome = basename(rawurldecode($rel));
    $relDec = rawurldecode($rel);
    $sim = simili($webroot, $relDec, $tuttiDoc);
    $ref = dove($db, $p, $relDec);
    $azione = $ref ? ($sim ? 'CORREGGI LINK' : 'RIPRISTINA') : ($sim ? 'REDIRECT' : 'NESSUNA');
    $azioni[$azione] = ($azioni[$azione] ?? 0) + 1;
    $simTxt = implode(' | ', array_map(fn($f, $s) => "$f ($s)", array_keys($sim), $sim));
    fputcsv($out, [$relDec, $n, is_dir(dirname($webroot . $relDec)) ? 'sì' : 'no', $simTxt, implode(' | ', $ref), $azione]);
    if ($n >= 3 || $ref) printf("%4d  %-13s %s%s%s\n", $n, $azione, $relDec,
        $sim ? "\n                    → " . $simTxt : '', $ref ? "\n                    in: " . implode(', ', array_slice($ref, 0, 4)) : '');
}
fclose($out);
echo "\n== " . count($conta) . " PDF diversi, " . array_sum($conta) . " richieste dal $da. Azioni: "
    . implode(', ', array_map(fn($a, $k) => "$a $k", array_keys($azioni), $azioni)) . "\n";
echo "== Dettaglio completo: $csv\n";
