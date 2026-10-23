<?php
/**
 * Alt Text (audit AA slide 25) — estrae le immagini senza alt dal CSV di aa-immagini-news.php e le impacchetta,
 * per scrivere i testi alternativi. Sola lettura sul sito.
 *
 * Uso:  php scripts/checks/aa-alt-news-estrai.php /www ~/aa-immagini-news-<data>.csv
 * Output: ~/alt-news.tgz (alt-news.csv con le righe senza alt + le immagini, stesso percorso sotto assets/)
 */
declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '', '/');
$csv = $argv[2] ?? '';
if ($webroot === '' || !is_file($csv)) { fwrite(STDERR, "Uso: php aa-alt-news-estrai.php /www <csv>\n"); exit(1); }
$home = rtrim((string) (getenv('HOME') ?: '.'), '/');
$dir = "$home/alt-news";
if (is_dir($dir)) exec('rm -rf ' . escapeshellarg($dir));
mkdir($dir, 0775, true);

$in = fopen($csv, 'r');
$out = fopen("$dir/alt-news.csv", 'w');
fputcsv($out, fgetcsv($in));
$n = 0; $file = [];
while (($r = fgetcsv($in)) !== false) {
    $alt = trim((string) ($r[11] ?? ''));
    if ($alt !== '' && $alt !== '(manca)') continue;
    fputcsv($out, $r);
    $n++;
    $rel = ltrim((string) parse_url($r[4], PHP_URL_PATH), '/');
    $src = "$webroot/" . rawurldecode($rel);
    if (!is_file($src) || isset($file[$rel])) continue;
    $file[$rel] = true;
    @mkdir("$dir/" . dirname($rel), 0775, true);
    copy($src, "$dir/$rel");
}
fclose($out);
exec('cd ' . escapeshellarg($home) . ' && tar czf alt-news.tgz alt-news', $o, $rc);
echo "$n immagini senza alt (" . count($file) . " file). Pacchetto: $home/alt-news.tgz\n";
exit($rc);
