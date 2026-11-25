<?php
/**
 * Clinica Dentale Villa dei Pini (lavoro extra) — verifica della proprietà &branca dello snippet di progetto
 * barraPrenotazioneDBmedicoPrestazione. SOLA LETTURA: non scrive nel database, non usa né scrive la cache della barra.
 *
 * Uso (dalla cartella estratta dall'archivio del repository, radice del repository):
 *   php extra/kos-clinica-dentale-villa-dei-pini/verifica-barra.php /www
 *
 * 1. Su tutte le schede che richiamano la barra (oppure: --uri=it/centri/a,it/centri/b) esegue la versione installata
 *    (dal database) e quella del repository (kosgroup/elements/snippets/), senza &branca e con la cache spenta,
 *    e confronta l'HTML byte per byte: atteso IDENTICO ovunque.
 * 2. Villa dei Pini Civitanova con &branca=`Odontoiatria`: specialità, prestazioni e medici rimasti (attese 2 prestazioni).
 * Esce con 1 se una sola scheda differisce.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php verifica-barra.php /www [--uri=...]\n"); exit(1); }
$soloUri = [];
foreach ($argv as $a) if (str_starts_with($a, '--uri=')) $soloUri = array_filter(array_map('trim', explode(',', substr($a, 6))));

$REPO = dirname(__DIR__, 2);
$FILE_REPO = "$REPO/kosgroup/elements/snippets/barraPrenotazioneDBmedicoPrestazione.php";
if (!is_file($FILE_REPO)) { fwrite(STDERR, "Manca $FILE_REPO: lanciare dalla radice dell'archivio del repository.\n"); exit(1); }

require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modSnippet};
$modx = modX::getInstance();
$modx->initialize('web');
$modx->setOption('kosseogeo.barra_cache_ttl', 0);   // niente lettura né scrittura della cache della barra

$sn = $modx->getObject(modSnippet::class, ['name' => 'barraPrenotazioneDBmedicoPrestazione']);
if (!$sn) { fwrite(STDERR, "Snippet non trovato nel database.\n"); exit(1); }
$FILE_DB = sys_get_temp_dir() . '/barra-db-' . getmypid() . '.php';
file_put_contents($FILE_DB, "<?php\n" . preg_replace('/^\s*<\?php\s*/', '', (string) $sn->get('snippet')));

$esegui = function (string $file, modResource $r, array $scriptProperties) use ($modx) {
    $modx->resource = $r;
    return (string) (function () use ($file, $modx, $scriptProperties) { return include $file; })();
};

/* ---------- 1. confronto senza &branca ---------- */
$q = $modx->newQuery(modResource::class, ['deleted' => 0]);
if ($soloUri) $q->where(['uri:IN' => $soloUri]);
else $q->where(['content:LIKE' => '%barraPrenotazioneDBmedicoPrestazione%']);
$q->sortby('id');
$diverse = 0; $n = 0;
echo "== SENZA &branca: versione installata e versione del repository (cache spenta)\n";
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    if (preg_match('/barraPrenotazioneDBmedicoPrestazione\?[^\]]*&branca/', (string) $r->get('content'))) continue;
    $prima = $esegui($FILE_DB, $r, []);
    $dopo = $esegui($FILE_REPO, $r, []);
    $n++;
    $ok = $prima === $dopo;
    if (!$ok) $diverse++;
    printf("  %s  id %4d  %7d byte  sha1 %s  /%s\n", $ok ? 'IDENTICO' : 'DIVERSO ', $r->get('id'), strlen($dopo),
        substr(sha1($dopo), 0, 12), $r->get('uri'));
    if (!$ok) {
        $i = 0; $l = min(strlen($prima), strlen($dopo));
        while ($i < $l && $prima[$i] === $dopo[$i]) $i++;
        echo "      primo byte diverso a $i: «" . substr($prima, max(0, $i - 40), 80) . "» / «" . substr($dopo, max(0, $i - 40), 80) . "»\n";
    }
}
echo "  schede confrontate: $n, diverse: $diverse\n";

/* ---------- 2. Villa dei Pini con &branca=`Odontoiatria` ---------- */
echo "\n== VILLA DEI PINI CIVITANOVA CON &branca=`Odontoiatria`\n";
$vdp = $modx->getObject(modResource::class, ['uri' => 'it/centri/villa-dei-pini-civitanova']);
if (!$vdp) { echo "  scheda non trovata\n"; exit(1); }
$html = $esegui($FILE_REPO, $vdp, ['branca' => 'Odontoiatria']);
if (!preg_match("/KOS_DATA_DB_B64 = '([^']*)'/", $html, $m)) { echo "  nessun dato nella barra: " . strip_tags(substr($html, 0, 200)) . "\n"; exit(1); }
$dati = json_decode(base64_decode($m[1]), true);
foreach ($dati['branche'] as $branca => $d) {
    printf("  specialità «%s»: %d prestazioni, %d medici\n", $branca, count($d['prestazioni']), count($d['medici']));
    foreach ($d['prestazioni'] as $p) echo "     prestazione {$p['id']}  {$p['title']}\n";
    foreach ($d['medici'] as $p) echo "     medico {$p['id']}  {$p['nome']}\n";
}
$np = array_sum(array_map(fn($d) => count($d['prestazioni']), $dati['branche']));
echo "  opzioni nella tendina delle specialità: " . preg_match_all('/<option value="[^"]+">/', $html) . "\n";
echo $np === 2 ? "  OK: 2 prestazioni, come atteso\n" : "  ATTENZIONE: $np prestazioni (attese 2) — fermarsi e decidere con Danilo prima di installare\n";
@unlink($FILE_DB);
exit($diverse || $np !== 2 ? 1 : 0);
