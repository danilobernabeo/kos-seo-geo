<?php
/**
 * AA-17 (e KOS-2): porta sotto un tetto di peso (default 200 KB) le immagini di un elenco, nello stesso file e con lo
 * stesso URL, senza cambiare dimensioni in pixel né formato. Serve per le varianti dello srcset (assets/cache-img) e per
 * gli originali già ottimizzati che restano sopra soglia.
 *
 * Uso (in SSH sul cloud, dalla cartella dell'archivio del repository):
 *   php scripts/immagini/tetto-peso.php /www <elenco.txt> [--kb=200]            # prova: calcola, non scrive
 *   php scripts/immagini/tetto-peso.php /www <elenco.txt> [--kb=200] --scrivi
 *   php scripts/immagini/tetto-peso.php /www --ripristina=<cartella backup> [--scrivi]
 *
 * Elenco: un percorso per riga (/assets/... o URL del sito); righe con # ignorate.
 * Per formato, scala di compressione fino al primo risultato sotto il tetto (con un margine del 3%):
 *   JPEG qualità 78, 72, 66, 60, 54 (progressivo); WebP 74, 68, 62, 56, 50;
 *   PNG a tavolozza 256 e 128 colori con retinatura, poi 64 e 32 senza (le varianti PNG sono il ripiego dei browser
 *   senza WebP: oggi nessuno dei principali).
 * Se nessun gradino scende sotto il tetto si tiene il più leggero, purché più leggero dell'originale, e la riga è
 * segnata «SOPRA». Gli originali sostituiti fuori da assets/cache-img: le varianti già generate vengono «toccate»
 * (data di modifica attuale), altrimenti lo snippet immagine le rigenererebbe dall'originale con la compressione
 * normale. Backup in ~/backup-kos-seo-geo/tetto-<data>/ con manifest.json.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$opz = []; $pos = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([\w-]+)(?:=(.*))?$/', $a, $m)) $opz[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if ($webroot === '' || !is_dir($webroot)) { fwrite(STDERR, "Uso: php tetto-peso.php /www <elenco.txt> [--kb=200] [--scrivi]\n"); exit(1); }
$scrivi = !empty($opz['scrivi']);
$bkRoot = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo';

/* ---------- ripristino ---------- */
if (isset($opz['ripristina'])) {
    $dir = rtrim((string) $opz['ripristina'], '/');
    $man = json_decode((string) @file_get_contents("$dir/manifest.json"), true);
    if (!is_array($man) || empty($man['file'])) { fwrite(STDERR, "Manifest mancante o vuoto in $dir\n"); exit(1); }
    $n = 0;
    foreach ($man['file'] as $f) {
        $copia = $dir . $f['rel']; $dest = $webroot . $f['rel'];
        if (!is_file($copia)) { echo "  ! copia mancante: $copia\n"; continue; }
        echo "  ← {$f['rel']}\n";
        if ($scrivi) { copy($copia, $dest) ? $n++ : print("    ! copia fallita\n"); }
    }
    echo $scrivi ? "== Ripristinati $n file.\n" : "== PROVA: aggiungere --scrivi per ripristinare\n";
    exit(0);
}

if (!class_exists(\Imagick::class)) { fwrite(STDERR, "Imagick non disponibile.\n"); exit(1); }
$elenco = $pos[1] ?? '';
if (!is_file($elenco)) { fwrite(STDERR, "Elenco non trovato: $elenco\n"); exit(1); }
$kb = (int) ($opz['kb'] ?? 200);
$tetto = (int) round($kb * 1024 * 0.97);

$voci = [];
foreach (file($elenco, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $r) {
    $r = trim((string) preg_replace('/\s+#.*$/', '', $r));
    if ($r === '' || $r[0] === '#') continue;
    $p = preg_match('#^https?://#i', $r) ? (string) parse_url($r, PHP_URL_PATH) : $r;
    $p = '/' . ltrim(rawurldecode((string) preg_replace('/[?#].*$/', '', $p)), '/');
    $voci[$p] = true;
}
$voci = array_keys($voci);
// originali prima delle varianti: le varianti toccate dopo un originale vanno poi trattate anch'esse
usort($voci, fn($a, $b) => [str_contains($a, '/cache-img/'), $a] <=> [str_contains($b, '/cache-img/'), $b]);

/** Scrive $im in $dest nel formato $fmt al gradino $g; restituisce la dimensione. */
$scriviGradino = function (\Imagick $im, string $dest, string $fmt, array $g): int {
    $c = clone $im;
    $c->stripImage();
    switch ($fmt) {
        case 'jpg':
            $c->setImageFormat('jpeg');
            $c->setImageCompressionQuality($g['q']);
            $c->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
            $c->setSamplingFactors(['2x2', '1x1', '1x1']);
            break;
        case 'webp':
            $c->setImageFormat('webp');
            $c->setImageCompressionQuality($g['q']);
            $c->setOption('webp:method', '6');
            break;
        default: // png
            $c->quantizeImage($g['colori'], \Imagick::COLORSPACE_SRGB, 0, $g['retina'], false);
            $c->setImageFormat('png8');
            $c->setOption('png:compression-level', '9');
    }
    $c->writeImage($dest);
    $c->clear();
    clearstatcache(true, $dest);
    return (int) filesize($dest);
};
$scale = [
    'jpg'  => [['q' => 78], ['q' => 72], ['q' => 66], ['q' => 60], ['q' => 54]],
    'webp' => [['q' => 74], ['q' => 68], ['q' => 62], ['q' => 56], ['q' => 50]],
    'png'  => [['colori' => 256, 'retina' => true], ['colori' => 128, 'retina' => true],
               ['colori' => 64, 'retina' => false], ['colori' => 32, 'retina' => false]],
];
$etichetta = fn(string $fmt, array $g) => $fmt === 'png' ? "{$g['colori']} colori" . ($g['retina'] ? ' retinati' : '') : "qualità {$g['q']}";

$stamp = date('Ymd-His');
$bk = "$bkRoot/tetto-$stamp";
$manifest = ['creato' => date('c'), 'elenco' => $elenco, 'kb' => $kb, 'file' => []];
$tot = ['n' => 0, 'prima' => 0, 'dopo' => 0, 'sopra' => 0, 'mancanti' => 0, 'sotto' => 0, 'toccate' => 0, 'errori' => 0];
echo ($scrivi ? '== APPLICAZIONE' : '== PROVA (nessuna scrittura; aggiungere --scrivi)') . ' — ' . count($voci) . " immagini, tetto $kb KB\n";

foreach ($voci as $rel) {
    $file = $webroot . $rel;
    if (!is_file($file)) { echo "  ? $rel: non trovata\n"; $tot['mancanti']++; continue; }
    $prima = (int) filesize($file);
    if ($prima <= $kb * 1024) { $tot['sotto']++; continue; }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $fmt = $ext === 'jpeg' ? 'jpg' : $ext;
    if (!isset($scale[$fmt])) { echo "  ? $rel: formato non gestito\n"; $tot['mancanti']++; continue; }
    $tmp = sys_get_temp_dir() . '/tetto-' . getmypid() . '-' . md5($rel) . '.' . $ext;
    try {
        $im = new \Imagick($file);
        if ($im->getNumberImages() > 1) { $im = $im->coalesceImages(); $im->setIteratorIndex(0); $im = $im->getImage(); }
        $lati = $im->getImageWidth() . 'x' . $im->getImageHeight();
        $migliore = null;
        foreach ($scale[$fmt] as $g) {
            $dim = $scriviGradino($im, $tmp, $fmt, $g);
            if ($migliore === null || $dim < $migliore['dim']) {
                $migliore = ['dim' => $dim, 'g' => $g];
                @copy($tmp, "$tmp.migliore");
            }
            if ($dim <= $tetto) break;
        }
        $im->clear();
        @unlink($tmp);
        $dopo = $migliore['dim'];
        if ($dopo >= $prima) {
            @unlink("$tmp.migliore");
            printf("  SOPRA      %6d KB  %-11s %s (nessun gradino più leggero)\n", $prima / 1024, $lati, $rel);
            $tot['sopra']++;
            continue;
        }
        $sopra = $dopo > $kb * 1024;
        printf("  %-10s %6d → %4d KB  %-11s %s (%s)\n", $sopra ? 'SOPRA' : 'ridotta', $prima / 1024, $dopo / 1024, $lati, $rel, $etichetta($fmt, $migliore['g']));
        $tot['n']++; $tot['prima'] += $prima; $tot['dopo'] += $dopo; if ($sopra) $tot['sopra']++;
        if (!$scrivi) { @unlink("$tmp.migliore"); continue; }
        $copia = $bk . $rel;
        if (!is_dir(dirname($copia))) mkdir(dirname($copia), 0775, true);
        if (!is_file($copia) && !copy($file, $copia)) throw new \RuntimeException("backup fallito: $copia");
        if (!rename("$tmp.migliore", $file) && !(copy("$tmp.migliore", $file) && unlink("$tmp.migliore"))) throw new \RuntimeException("sostituzione fallita");
        @chmod($file, 0644);
        $manifest['file'][] = ['rel' => $rel, 'prima' => $prima, 'dopo' => $dopo];
        // originale fuori dalla cache: varianti già generate di nuovo «fresche», per non farle rigenerare
        if (!str_contains($rel, '/cache-img/') && str_starts_with($rel, '/assets/')) {
            $base = $webroot . '/assets/cache-img/' . preg_replace('/\.[^.\/]+$/', '', substr($rel, strlen('/assets/')));
            foreach (glob($base . '-*.{jpg,png,webp}', GLOB_BRACE) ?: [] as $v) {
                if (preg_match('/-\d+\.(jpg|png|webp)$/', $v)) { touch($v); $tot['toccate']++; }
            }
        }
    } catch (\Throwable $e) {
        echo "  ! $rel: " . $e->getMessage() . "\n"; $tot['errori']++;
        @unlink($tmp); @unlink("$tmp.migliore");
    }
}
if ($scrivi && $manifest['file']) {
    if (!is_dir($bk)) mkdir($bk, 0775, true);
    file_put_contents("$bk/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
printf("== %d ridotte: %.1f → %.1f MB; %d ancora sopra %d KB; %d già sotto; %d non trovate%s%s\n",
    $tot['n'], $tot['prima'] / 1048576, $tot['dopo'] / 1048576, $tot['sopra'], $kb, $tot['sotto'], $tot['mancanti'],
    $tot['toccate'] ? "; {$tot['toccate']} varianti toccate" : '', $tot['errori'] ? "; {$tot['errori']} errori" : '');
if ($scrivi && $manifest['file']) echo "== Backup in $bk — ripristino:\n   php scripts/immagini/tetto-peso.php $webroot --ripristina=$bk --scrivi\n";
exit($tot['errori'] ? 2 : 0);
