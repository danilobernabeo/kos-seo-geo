<?php
/**
 * sostituisci-src.php — AA-17: cambia il file di un'immagine scritta nel campo content delle risorse (attributo src),
 * da un file di regole nel repository:
 *
 *   {"sostituzioni": [ {"da": "/assets/img/lombardia-m.png", "a": "/assets/img/lombardia-m.jpg"} ]}
 *
 * Confronta il src senza host, slash iniziale e parametri, e lo riscrive nella stessa forma (relativa o assoluta) in
 * cui era scritto. Il file nuovo deve esistere sul disco, altrimenti si ferma.
 *
 * Uso (in SSH, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/sostituisci-src.php /www <regole.json>            # prova
 *   php scripts/deploy/sostituisci-src.php /www <regole.json> --scrivi
 *   php scripts/deploy/sostituisci-src.php /www --ripristina=<ripristino.json> [--scrivi] [--forza]
 * Prima di scrivere: tabella <prefisso>site_content_bak_<AAAAMMGG_HHMM> (id, content) e ripristino.json in
 * ~/backup-kos-seo-geo/<data>-src/. Alla fine svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php sostituisci-src.php /www <regole.json> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$scrivi = isset($opt['scrivi']);

/* ---------------------------------------------------------------- ripristino */
if (isset($opt['ripristina'])) {
    $rip = json_decode((string) file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($rip)) { fwrite(STDERR, "File di ripristino non leggibile\n"); exit(1); }
    $fatte = 0; $fermi = 0;
    foreach ($rip['pagine'] as $p) {
        $r = $modx->getObject(modResource::class, (int) $p['id']);
        if (!$r) { echo "MANCANTE  {$p['id']}\n"; continue; }
        if (sha1((string) $r->get('content')) !== $p['sha1_dopo'] && !isset($opt['forza'])) { echo "FERMO     {$p['id']} modificata dopo (--forza per rimettere comunque)\n"; $fermi++; continue; }
        echo ($scrivi ? 'RIPRISTINO' : 'DA RIPRISTINARE') . " {$p['id']} {$p['uri']}\n";
        if ($scrivi) { $r->set('content', $p['prima']); $r->save(); $fatte++; }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo '== ' . ($scrivi ? "$fatte ripristinate" : 'prova') . ", $fermi fermi\n";
    exit(0);
}

$file = $pos[1] ?? '';
$sost = is_file($file) ? (json_decode((string) file_get_contents($file), true)['sostituzioni'] ?? null) : null;
if (!is_array($sost) || !$sost) { fwrite(STDERR, "Regole non leggibili: $file\n"); exit(1); }
$norm = fn($s) => '/' . ltrim((string) preg_replace(['#^https?://[^/]+#i', '/[?#].*$/'], '', html_entity_decode((string) $s)), '/');
$mappa = [];
foreach ($sost as $s) {
    $da = $norm($s['da']); $a = $norm($s['a']);
    if (!is_file($webroot . $a)) { fwrite(STDERR, "FERMO: il file nuovo non esiste sul disco: $a\n"); exit(1); }
    $mappa[$da] = $a;
}

$p = $modx->getOption('table_prefix', null, 'modx_');
$righe = $modx->pdo->query("SELECT id, uri FROM {$p}site_content WHERE deleted = 0 AND content LIKE '%<img%'")->fetchAll(PDO::FETCH_ASSOC);
$pagine = []; $conteggio = 0;
foreach ($righe as $riga) {
    $r = $modx->getObject(modResource::class, (int) $riga['id']);
    $prima = (string) $r->get('content');
    $n = 0;
    $dopo = preg_replace_callback('~(<img\b[^>]*?\bsrc\s*=\s*)(["\'])(.*?)\2~is', function ($m) use ($mappa, $norm, &$n) {
        $src = $norm($m[3]);
        if (!isset($mappa[$src])) return $m[0];
        // stessa forma dell'originale: relativa («assets/...»), assoluta («/assets/...») o con host
        $orig = $m[3];
        $nuovo = preg_match('#^https?://[^/]+#i', $orig, $h) ? $h[0] . $mappa[$src] : (str_starts_with($orig, '/') ? $mappa[$src] : ltrim($mappa[$src], '/'));
        $n++;
        return $m[1] . $m[2] . $nuovo . $m[2];
    }, $prima);
    if ($n === 0 || $dopo === $prima) continue;
    $conteggio += $n;
    echo ($scrivi ? 'SCRITTA  ' : 'DA FARE  ') . sprintf("%5d  %-45s %d immagini\n", $riga['id'], $riga['uri'], $n);
    $pagine[] = ['id' => (int) $riga['id'], 'uri' => $riga['uri'], 'prima' => $prima, 'dopo' => $dopo, 'sha1_dopo' => sha1($dopo), 'r' => $r];
}
echo '== ' . count($pagine) . " pagine, $conteggio immagini\n";
if (!$scrivi || !$pagine) { if (!$scrivi) echo "== PROVA: aggiungere --scrivi\n"; exit(0); }

$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-src';
mkdir($bk, 0775, true);
$tab = "{$p}site_content_bak_" . date('Ymd_Hi');
$ids = implode(',', array_map(fn($x) => $x['id'], $pagine));
$modx->pdo->exec("CREATE TABLE IF NOT EXISTS `$tab` AS SELECT id, content FROM {$p}site_content WHERE id IN ($ids)");
file_put_contents("$bk/ripristino.json", json_encode(['creato' => date('c'), 'tabella' => $tab,
    'pagine' => array_map(fn($x) => array_diff_key($x, ['r' => 1, 'dopo' => 1]), $pagine)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
foreach ($pagine as $x) { $x['r']->set('content', $x['dopo']); $x['r']->save(); }
$modx->cacheManager->refresh();
echo "== Backup: tabella $tab e $bk/ripristino.json — ripristino:\n   php scripts/deploy/sostituisci-src.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
