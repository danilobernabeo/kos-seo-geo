<?php
/**
 * scalda-cache.php — dopo uno svuotamento della cache MODX (ogni rilascio), richiede una volta tutte le pagine della
 * sitemap così il primo visitatore, e il primo crawler, trovano la pagina già in cache (0,2-0,3 s invece di circa 1 s).
 * Misura del 15/11: prima richiesta dopo lo svuotamento 1,05-1,24 s, seconda 0,24-0,29 s, su articoli e schede.
 *
 * Uso (sul cloud, anche in SSH; richieste al sito pubblico, una alla volta):
 *   php scripts/deploy/scalda-cache.php https://kosgroup.com/sitemap.xml
 *   php scripts/deploy/scalda-cache.php https://anniazzurri.it/sitemap.xml --pausa=0.5
 *   ... --utente=kos --password-env=AUTH   per lo stage con Basic Auth (password letta da una variabile d'ambiente)
 *   ... --max=50                            solo le prime N pagine (prova)
 * Pausa predefinita 1 s tra le richieste (le protezioni di MODX Cloud bloccano i ritmi alti). Le sitemap indice
 * (<sitemapindex>) vengono seguite. Stampa a fine giro: pagine, errori, tempi mediano e massimo, le 10 più lente.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$sitemap = $pos[0] ?? '';
if (!preg_match('#^https?://#', $sitemap)) { fwrite(STDERR, "Uso: php scalda-cache.php <URL sitemap> [--pausa=1] [--max=N]\n"); exit(1); }
$pausa = max(0.2, (float) ($opt['pausa'] ?? 1));
$max = (int) ($opt['max'] ?? 0);
$auth = null;
if (isset($opt['utente'])) {
    $pw = getenv((string) ($opt['password-env'] ?? 'AUTH'));
    if ($pw === false || $pw === '') { fwrite(STDERR, "Variabile d'ambiente della password vuota.\n"); exit(1); }
    $auth = $opt['utente'] . ':' . $pw;
}
$get = function (string $u, bool $corpo) use ($auth): array {
    $c = curl_init($u);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'kos-seo-geo scalda-cache', CURLOPT_ENCODING => '', CURLOPT_NOBODY => false]);
    if ($auth) curl_setopt($c, CURLOPT_USERPWD, $auth);
    $b = curl_exec($c);
    $r = [(int) curl_getinfo($c, CURLINFO_HTTP_CODE), (float) curl_getinfo($c, CURLINFO_STARTTRANSFER_TIME), $corpo ? (string) $b : ''];
    curl_close($c);
    return $r;
};
$urls = []; $coda = [$sitemap]; $viste = [];
while ($coda) {
    $s = array_shift($coda);
    if (isset($viste[$s])) continue;
    $viste[$s] = 1;
    [$cod, , $xml] = $get($s, true);
    if ($cod !== 200) { fwrite(STDERR, "Sitemap $s: HTTP $cod\n"); continue; }
    preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#', $xml, $m);
    $locs = array_map(fn($x) => html_entity_decode($x, ENT_QUOTES | ENT_XML1), $m[1]);
    if (stripos($xml, '<sitemapindex') !== false) { array_push($coda, ...$locs); continue; }
    foreach ($locs as $l) if (!preg_match('/\.(pdf|jpe?g|png|webp|gif|svg|docx?|xlsx?)$/i', $l)) $urls[$l] = 1;
}
$urls = array_keys($urls);
if ($max > 0) $urls = array_slice($urls, 0, $max);
$n = count($urls);
echo "== $n pagine da " . $sitemap . ", una ogni {$pausa} s (circa " . round($n * ($pausa + 0.5) / 60) . " minuti)\n";
$tempi = []; $err = [];
foreach ($urls as $i => $u) {
    [$cod, $t] = $get($u, false);
    $tempi[$u] = $t;
    if ($cod !== 200) $err[] = "$cod $u";
    if (($i + 1) % 100 === 0) echo "  " . ($i + 1) . "/$n\n";
    usleep((int) ($pausa * 1e6));
}
$v = array_values($tempi); sort($v);
printf("== Fatto: %d pagine, %d non 200; tempo alla prima risposta mediano %.2f s, massimo %.2f s\n",
    $n, count($err), $v ? $v[intdiv(count($v), 2)] : 0, $v ? end($v) : 0);
foreach (array_slice($err, 0, 20) as $e) echo "  ! $e\n";
arsort($tempi);
echo "== Le 10 più lente\n";
foreach (array_slice($tempi, 0, 10, true) as $u => $t) printf("  %.2f  %s\n", $t, $u);
