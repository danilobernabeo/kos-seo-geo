<?php
/**
 * AA-17 immagini pesanti (e AA-1): ottimizzazione delle immagini già presenti sul sito, con backup e ripristino.
 * Usa la libreria comune shared/lib/Immagini.php (Imagick).
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/immagini/ottimizza-immagini.php /www <elenco.txt | cartella> [opzioni]          # prova: non scrive
 *   php scripts/immagini/ottimizza-immagini.php /www <elenco.txt | cartella> [opzioni] --scrivi
 *   php scripts/immagini/ottimizza-immagini.php /www --ripristina ~/backup-kos-seo-geo/immagini-<data>
 *
 * Origine delle immagini:
 *   elenco.txt   una immagine per riga (URL o percorso /assets/...); facoltativo «;<lato massimo>» per la singola riga
 *                (es. icone dei virtual tour: /assets/virtual-tour/x/assets/avatar_icon.png;256). Righe con # ignorate.
 *   cartella     percorso relativo alla radice (es. assets/img/rsa): tutti i JPG/PNG, sottocartelle comprese.
 * Opzioni:
 *   --soglia=KB     tratta solo le immagini più pesanti di KB (default 0 con l'elenco, 200 con la cartella)
 *   --max=PX        lato massimo dell'originale (default 2400)
 *   --obiettivo=KB  peso da raggiungere (es. 200, soglia dell'audit): JPG con qualità più bassa (76, poi 70),
 *                   PNG a 256 colori se la differenza visiva è trascurabile
 *   --varianti      genera anche le varianti WebP e le larghezze per lo srcset (snippet `immagine`)
 *   --solo-varianti non tocca gli originali, genera solo le varianti
 *
 * Gli originali sostituiti vengono copiati in ~/backup-kos-seo-geo/immagini-<data>/ (stesso percorso relativo),
 * con un manifest.json; --ripristina li rimette al loro posto.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
require __DIR__ . '/../../shared/lib/Immagini.php';

$arg = array_slice($argv, 1);
$opz = []; $pos = [];
foreach ($arg as $a) {
    if (preg_match('/^--([\w-]+)(?:=(.*))?$/', $a, $m)) $opz[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if ($webroot === '' || !is_dir($webroot)) { fwrite(STDERR, "Uso: php ottimizza-immagini.php /www <elenco.txt|cartella> [--scrivi]\n"); exit(1); }
$bkRoot = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo';

/* ---------- ripristino ---------- */
if (isset($opz['ripristina'])) {
    $dir = rtrim((string) ($pos[1] ?? (is_string($opz['ripristina']) ? $opz['ripristina'] : '')), '/');
    $man = json_decode((string) @file_get_contents("$dir/manifest.json"), true);
    if (!is_array($man) || empty($man['file'])) { fwrite(STDERR, "Manifest mancante o vuoto in $dir\n"); exit(1); }
    $n = 0;
    foreach ($man['file'] as $f) {
        $copia = $dir . $f['rel'];
        $dest = rtrim($webroot, '/') . $f['rel'];
        if (!is_file($copia)) { echo "  ! copia mancante: $copia\n"; continue; }
        echo "  ← {$f['rel']}\n";
        if (!empty($opz['scrivi'])) { copy($copia, $dest) ? $n++ : print("    ! copia fallita\n"); }
    }
    echo empty($opz['scrivi']) ? "== PROVA: aggiungere --scrivi per ripristinare\n" : "== Ripristinati $n file. Le varianti in cache si rigenerano da sole (sono più vecchie dell'originale).\n";
    exit(0);
}

/* ---------- elenco delle immagini ---------- */
$I = new \KosSeoGeo\Immagini($webroot, ['max_lato' => (int) ($opz['max'] ?? 2400)]);
if (!$I->disponibile()) { fwrite(STDERR, "Imagick con supporto WebP non disponibile.\n"); exit(1); }
$origine = $pos[1] ?? '';
$voci = [];
if (is_file($origine)) {
    foreach (file($origine, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $riga) {
        $riga = trim($riga);
        if ($riga === '' || $riga[0] === '#') continue;
        [$u, $max] = array_pad(explode(';', $riga, 2), 2, null);
        $voci[] = ['src' => trim($u), 'max' => $max !== null && trim($max) !== '' ? (int) $max : null];
    }
    $soglia = (int) ($opz['soglia'] ?? 0);
} elseif (is_dir("$webroot/" . trim($origine, '/'))) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$webroot/" . trim($origine, '/'), FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || !in_array(strtolower($f->getExtension()), \KosSeoGeo\Immagini::ESTENSIONI, true)) continue;
        if (str_contains($f->getPathname(), '/cache-img/')) continue;
        $voci[] = ['src' => substr($f->getPathname(), strlen($webroot)), 'max' => null];
    }
    sort($voci);
    $soglia = (int) ($opz['soglia'] ?? 200);
} else {
    fwrite(STDERR, "Origine non trovata: $origine (file di elenco o cartella relativa a $webroot)\n"); exit(1);
}

$scrivi = !empty($opz['scrivi']);
$soloVar = !empty($opz['solo-varianti']);
$stamp = date('Ymd-His');
$bk = "$bkRoot/immagini-$stamp";
$manifest = ['creato' => date('c'), 'origine' => $origine, 'file' => []];
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — " . count($voci) . " immagini, soglia {$soglia} KB\n";

$tot = ['prima' => 0, 'dopo' => 0, 'n' => 0, 'mancanti' => 0, 'varianti' => 0, 'errori' => 0];
foreach ($voci as $v) {
    $file = $I->percorso($v['src']);
    if ($file === null) { echo "  ? {$v['src']}: non trovata o formato non gestito\n"; $tot['mancanti']++; continue; }
    if (filesize($file) < $soglia * 1024) continue;
    $rel = substr($file, strlen((string) realpath($webroot)));
    try {
        if (!$soloVar) {
            $r = $I->ottimizza($file, $scrivi ? $bk : null, $scrivi, $v['max'], isset($opz['obiettivo']) ? (int) $opz['obiettivo'] : null);
            printf("  %-11s %6d → %6d KB  %-22s %s%s\n", $r['azione'], $r['prima'] / 1024, $r['dopo'] / 1024, $r['lati'], $rel, $r['nota'] ? " ({$r['nota']})" : '');
            $tot['prima'] += $r['prima']; $tot['dopo'] += $r['dopo']; $tot['n']++;
            if ($scrivi && isset($r['backup'])) $manifest['file'][] = ['rel' => $rel, 'prima' => $r['prima'], 'dopo' => $r['dopo']];
        }
        if (!empty($opz['varianti']) || $soloVar) {
            $n = $scrivi ? count($I->varianti($file, true)) : 0;
            $tot['varianti'] += $n;
            if ($soloVar) printf("  varianti   %s%s\n", $rel, $scrivi ? " ($n larghezze)" : '');
        }
    } catch (\Throwable $e) {
        echo "  ! $rel: " . $e->getMessage() . "\n"; $tot['errori']++;
    }
}
if ($scrivi && $manifest['file']) {
    if (!is_dir($bk)) mkdir($bk, 0775, true);
    file_put_contents("$bk/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
printf("== %d immagini trattate: %.1f MB → %.1f MB (−%d%%)%s%s%s\n", $tot['n'], $tot['prima'] / 1048576, $tot['dopo'] / 1048576,
    $tot['prima'] ? round(100 - 100 * $tot['dopo'] / $tot['prima']) : 0,
    $tot['varianti'] ? ", {$tot['varianti']} larghezze di varianti" : '',
    $tot['mancanti'] ? ", {$tot['mancanti']} non trovate" : '',
    $tot['errori'] ? ", {$tot['errori']} errori" : '');
if ($scrivi && $manifest['file']) echo "== Originali salvati in $bk/ — ripristino:\n   php scripts/immagini/ottimizza-immagini.php $webroot --ripristina $bk --scrivi\n";
exit($tot['errori'] ? 2 : 0);
