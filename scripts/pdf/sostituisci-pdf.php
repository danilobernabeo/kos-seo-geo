<?php
/**
 * AA-3 / KOS-8 — Sostituzione di PDF esistenti con file nuovi forniti da KOS, allo stesso percorso (i link delle pagine
 * non cambiano), con backup e ripristino. I metadati (titolo, oggetto, autore, lingua) si scrivono subito dopo con
 * pdf-metadati.php, usando lo stesso file di abbinamento come piano.
 *
 * Uso:   php sostituisci-pdf.php <webroot> <abbinamento.json> <cartella dei file nuovi> [--scrivi] [--nuovi] [--backup=<cartella>]
 *        php sostituisci-pdf.php <webroot> --ripristina=<cartella di backup> [--scrivi] [--forza]
 *   abbinamento: {"pdf":[{"s":"<file relativo alla cartella dei nuovi>","p":"/assets/doc/...pdf","t":"..","d":"..","a":"..","l":".."}],
 *                 "sospesi":[...], "rimuovi":[{"p":"/assets/doc/...pdf","motivo":".."}]}
 *   (le voci in «sospesi» si elencano e non si toccano; quelle in «rimuovi» si tolgono dal sito, con backup)
 *   Senza --scrivi è una PROVA: per ogni voce mostra file attuale (dimensione, data, md5) → nuovo (dimensione, pagine se
 *   leggibili), e si FERMA se il sorgente manca, non è un PDF, è cifrato, o se il percorso di destinazione non esiste
 *   (un percorso nuovo non è richiamato da nessuna pagina: solo con "nuovo": true nella voce o --nuovi; nelle cartelle
 *   assets/doc/strutture/<alias>/ lo elenca da sé lo snippet docList della pagina). File identici: «GIÀ UGUALE», saltati.
 *   --scrivi: copia gli attuali in <backup> (default ~/backup-kos-seo-geo/AAAAMMGG-HHMMSS-pdf-sostituiti) con
 *   ripristino.json (md5 prima/dopo), poi scrive ogni nuovo file in modo atomico (temporaneo + rename), permessi 0644.
 *   --ripristina: rimette gli originali, solo se il file sul sito è ancora quello scritto da qui (salvo --forza); i file
 *   che prima non esistevano vengono rimossi.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
$scrivi = isset($opt['scrivi']);
if ($webroot === '' || !is_dir($webroot)) {
    fwrite(STDERR, "Uso: php sostituisci-pdf.php <webroot> <abbinamento.json> <cartella nuovi> [--scrivi] [--nuovi] [--backup=cartella]\n"
        . "     php sostituisci-pdf.php <webroot> --ripristina=<cartella backup> [--scrivi] [--forza]\n");
    exit(1);
}
$scriviAtomico = function (string $src, string $dest): void {
    $dir = dirname($dest);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException("cartella non creata: $dir");
    $tmp = $dest . '.tmp-' . getmypid();
    if (!copy($src, $tmp)) throw new RuntimeException("copia non riuscita: $dest");
    chmod($tmp, 0644);
    if (md5_file($tmp) !== md5_file($src)) { unlink($tmp); throw new RuntimeException("copia diversa dal sorgente: $dest"); }
    rename($tmp, $dest);
};

/* ---------------------------------- ripristino ---------------------------------- */
if (isset($opt['ripristina'])) {
    $cart = rtrim((string) $opt['ripristina'], '/');
    $man = json_decode((string) @file_get_contents("$cart/ripristino.json"), true);
    if (!is_array($man)) { fwrite(STDERR, "ripristino.json non leggibile in $cart\n"); exit(1); }
    $ok = 0; $salt = 0;
    foreach ($man['file'] as $f) {
        $dest = $webroot . $f['p'];
        if (is_file($dest) && md5_file($dest) !== $f['md5_dopo'] && !isset($opt['forza'])) {
            // pdf-metadati.php modifica il file dopo la sostituzione: va ripristinato prima con il suo --ripristina
            echo "DIVERSO DA QUANTO SCRITTO (ripristinare prima i metadati, o --forza): {$f['p']}\n"; $salt++; continue;
        }
        if ($f['md5_prima'] === null) {
            echo ($scrivi ? 'RIMOSSO    ' : 'RIMUOVEREI ') . "{$f['p']}\n";
            if ($scrivi && is_file($dest)) unlink($dest);
        } else {
            $src = "$cart/file{$f['p']}";
            if (!is_file($src) || md5_file($src) !== $f['md5_prima']) { echo "BACKUP MANCANTE O ALTERATO: {$f['p']}\n"; $salt++; continue; }
            echo ($scrivi ? 'RIPRISTINATO ' : 'RIPRISTINEREI ') . "{$f['p']}\n";
            if ($scrivi) $scriviAtomico($src, $dest);
        }
        $ok++;
    }
    echo "== Ripristino: $ok file" . ($scrivi ? '' : ' (prova: aggiungi --scrivi)') . ", $salt saltati\n";
    exit($salt ? 2 : 0);
}

/* ---------------------------------- sostituzione ---------------------------------- */
$abb = json_decode((string) @file_get_contents($pos[1] ?? ''), true);
$nuovi = rtrim($pos[2] ?? '', '/');
if (!is_array($abb) || !isset($abb['pdf']) || $nuovi === '' || !is_dir($nuovi)) { fwrite(STDERR, "Abbinamento JSON o cartella dei file nuovi non validi.\n"); exit(1); }

$pagine = function (string $file): string {
    $b = (string) file_get_contents($file);
    // il nodo radice delle pagine ha il /Count più alto (i nodi intermedi contano solo i loro figli); oggetti compressi: «?»
    preg_match_all('~/Type\s*/Pages\b[^>]*?/Count\s+(\d+)|/Count\s+(\d+)[^>]*?/Type\s*/Pages\b~s', $b, $m);
    $n = array_map('intval', array_filter(array_merge($m[1], $m[2]), 'strlen'));
    return $n ? (string) max($n) : '?';
};
$umano = fn(int $n): string => number_format($n / 1048576, 2, ',', '.') . ' MB';

$fermi = 0; $daFare = []; $daTogliere = [];
foreach ($abb['pdf'] as $v) {
    $src = "$nuovi/{$v['s']}";
    $dest = $webroot . $v['p'];
    echo "\n{$v['p']}\n    da: {$v['s']}\n";
    if (!is_file($src)) { echo "    FERMO: sorgente mancante\n"; $fermi++; continue; }
    $testa = (string) file_get_contents($src, false, null, 0, 1024);
    $coda = (string) file_get_contents($src, false, null, max(0, filesize($src) - 2048));
    if (!str_starts_with($testa, '%PDF-')) { echo "    FERMO: il sorgente non è un PDF\n"; $fermi++; continue; }
    if (str_contains($coda, '/Encrypt')) { echo "    FERMO: PDF cifrato\n"; $fermi++; continue; }
    $esiste = is_file($dest);
    if (!$esiste && empty($v['nuovo']) && !isset($opt['nuovi'])) { echo "    FERMO: il percorso non esiste sul sito (nessuna pagina lo richiama; \"nuovo\": true nella voce, o --nuovi)\n"; $fermi++; continue; }
    if ($esiste && md5_file($dest) === md5_file($src)) { echo "    GIÀ UGUALE\n"; continue; }
    echo '    attuale: ' . ($esiste ? $umano(filesize($dest)) . ', ' . date('d/m/Y H:i', filemtime($dest)) . ', ' . $pagine($dest) . ' pagine, md5 ' . md5_file($dest) : '(nuovo percorso)') . "\n";
    echo '    nuovo:   ' . $umano(filesize($src)) . ', ' . $pagine($src) . ' pagine, ' . substr($testa, 0, 8) . "\n";
    $daFare[] = [$v, $src, $dest, $esiste];
}
foreach ($abb['rimuovi'] ?? [] as $v) {
    $dest = $webroot . $v['p'];
    echo "\n{$v['p']}\n    da RIMUOVERE: " . ($v['motivo'] ?? '') . "\n";
    if (!is_file($dest)) { echo "    GIÀ ASSENTE\n"; continue; }
    if (!str_ends_with(strtolower($dest), '.pdf')) { echo "    FERMO: non è un PDF\n"; $fermi++; continue; }
    echo '    attuale: ' . $umano(filesize($dest)) . ', ' . date('d/m/Y H:i', filemtime($dest)) . ', md5 ' . md5_file($dest) . "\n";
    $daTogliere[] = [$v, $dest];
}
foreach ($abb['sospesi'] ?? [] as $v) echo "\nSOSPESO    {$v['s']}\n    " . ($v['motivo'] ?? $v['sospeso'] ?? '') . "\n";
foreach ($abb['esclusi'] ?? [] as $v) echo "\nESCLUSO    {$v['s']}\n    {$v['motivo']}\n";

echo "\n== " . count($daFare) . ' da sostituire, ' . count($daTogliere) . ' da rimuovere, ' . $fermi . ' fermi, ' . count($abb['sospesi'] ?? []) . " sospesi\n";
if ($fermi) { echo "Nessuna scrittura: correggere i FERMO.\n"; exit(2); }
if (!$scrivi) { echo "PROVA: nessun file scritto (aggiungi --scrivi).\n"; exit(0); }
if (!$daFare && !$daTogliere) exit(0);

$home = getenv('HOME') ?: '/tmp';
$bak = rtrim((string) ($opt['backup'] ?? "$home/backup-kos-seo-geo/" . date('Ymd-His') . '-pdf-sostituiti'), '/');
$man = ['data' => date('c'), 'webroot' => $webroot, 'file' => []];
foreach ($daFare as [$v, $src, $dest, $esiste]) {
    $prima = null;
    if ($esiste) {
        $prima = md5_file($dest);
        $scriviAtomico($dest, "$bak/file{$v['p']}");
    }
    $man['file'][] = ['p' => $v['p'], 's' => $v['s'], 'md5_prima' => $prima, 'md5_dopo' => md5_file($src)];
}
foreach ($daTogliere as [$v, $dest]) {
    $scriviAtomico($dest, "$bak/file{$v['p']}");
    $man['file'][] = ['p' => $v['p'], 's' => null, 'md5_prima' => md5_file($dest), 'md5_dopo' => null];
}
if (!is_dir($bak)) mkdir($bak, 0755, true);
file_put_contents("$bak/ripristino.json", json_encode($man, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
foreach ($daFare as [$v, $src, $dest]) {
    $scriviAtomico($src, $dest);
    echo "SOSTITUITO {$v['p']}\n";
}
foreach ($daTogliere as [$v, $dest]) {
    unlink($dest);
    echo "RIMOSSO    {$v['p']}\n";
}
echo "== Backup: $bak\n   Ripristino: php " . basename(__FILE__) . " $webroot --ripristina=$bak --scrivi\n";
echo "   Poi i metadati: php pdf-metadati.php $webroot " . ($pos[1]) . " (prova), e con --scrivi\n";
