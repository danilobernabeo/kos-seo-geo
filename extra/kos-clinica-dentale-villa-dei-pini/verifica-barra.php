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
/* Le due versioni passano dalla stessa normalizzazione di installa-elementi.php (fine riga LF, senza <?php iniziale,
 * trim): è il testo che finisce nel database. Senza, il file dell'archivio creato da git su Windows (core.autocrlf,
 * fine riga CRLF) darebbe un HTML diverso in ogni \r, pur con lo stesso codice. */
$norm = fn(string $s) => trim((string) preg_replace('/\?>\s*$/', '', (string) preg_replace('/^\s*<\?php\s*/', '', str_replace("\r\n", "\n", $s))));
$dbGrezzo = (string) $sn->get('snippet');
echo "== Snippet nel database: " . strlen($dbGrezzo) . ' byte, fine riga ' . (str_contains($dbGrezzo, "\r\n") ? 'CRLF' : 'LF')
   . (str_contains($dbGrezzo, '$brancaFiltro') ? ', GIÀ con &branca (il confronto non è più significativo)' : ', senza &branca') . "\n";
$FILE_DB = sys_get_temp_dir() . '/barra-db-' . getmypid() . '.php';
file_put_contents($FILE_DB, "<?php\n" . $norm($dbGrezzo));
$FILE_REPO_N = sys_get_temp_dir() . '/barra-repo-' . getmypid() . '.php';
file_put_contents($FILE_REPO_N, "<?php\n" . $norm((string) file_get_contents($FILE_REPO)));
$FILE_REPO = $FILE_REPO_N;

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
        echo "      primo byte diverso a $i: " . bin2hex(substr($prima, $i, 8)) . ' / ' . bin2hex(substr($dopo, $i, 8))
           . "  «" . addcslashes(substr($prima, max(0, $i - 40), 80), "\r\n") . "» / «" . addcslashes(substr($dopo, max(0, $i - 40), 80), "\r\n") . "»\n";
    }
}
echo "  schede confrontate: $n, diverse: $diverse\n";

/* ---------- 2. Villa dei Pini con &branca=`Odontoiatria` ---------- */
echo "\n== VILLA DEI PINI CIVITANOVA CON &branca=`Odontoiatria`\n";
$vdp = $modx->getObject(modResource::class, ['uri' => 'it/centri/villa-dei-pini-civitanova']);
if (!$vdp) { echo "  scheda non trovata\n"; exit(1); }
// Diagnosi: specialità dell'istanza di Villa dei Pini (come nella barra, senza filtro) e dove compare «odont» in kos_*
$ist = (string) $vdp->getTVValue('istanza');
echo "  istanza (TV) di Villa dei Pini: «{$ist}»\n";
$q1 = $modx->prepare("SELECT b.branca,
        (SELECT COUNT(*) FROM kos_prestazioni p WHERE p.areaid = b.areaid AND p.typology = b.branca) AS tutte,
        (SELECT COUNT(*) FROM kos_prestazioni p WHERE p.areaid = b.areaid AND p.typology = b.branca AND (p.is_hidden = 0 OR p.mop_bookability = 2)
            AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid)) AS nella_barra
    FROM kos_branche b WHERE b.areaid = ? ORDER BY b.branca");
$q1->execute([$ist]);
echo "  specialità dell'istanza (tutte le prestazioni / visibili nella barra):\n";
foreach ($q1->fetchAll(PDO::FETCH_ASSOC) as $b) printf("     %-45s %3d / %3d\n", $b['branca'], $b['tutte'], $b['nella_barra']);
$q2 = $modx->prepare("SELECT p.areaid, p.typology, COUNT(*) AS n,
        SUM(CASE WHEN (p.is_hidden = 0 OR p.mop_bookability = 2) AND EXISTS (SELECT 1 FROM kos_medici_prestazioni mp
            WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid) THEN 1 ELSE 0 END) AS nella_barra,
        (SELECT s.nome FROM kos_strutture s WHERE s.areaid = p.areaid LIMIT 1) AS struttura
    FROM kos_prestazioni p WHERE LOWER(p.typology) LIKE '%odont%' OR LOWER(p.title) LIKE '%odont%' OR LOWER(p.title) LIKE '%dentist%'
    GROUP BY p.areaid, p.typology ORDER BY p.areaid");
$q2->execute();
$righe = $q2->fetchAll(PDO::FETCH_ASSOC);
echo "  prestazioni odontoiatriche in tutte le istanze (kos_prestazioni): " . (count($righe) ? '' : 'nessuna') . "\n";
foreach ($righe as $r) printf("     %-40s %-30s %-30s %3d (barra %d)\n", $r['areaid'], $r['struttura'] ?? '-', $r['typology'], $r['n'], $r['nella_barra']);
foreach (['kos_strutture' => 'nome', 'kos_branche' => 'branca'] as $t => $col) {
    $q3 = $modx->prepare("SELECT * FROM $t WHERE LOWER($col) LIKE '%dent%' OR LOWER($col) LIKE '%odont%' LIMIT 20");
    $q3->execute();
    foreach ($q3->fetchAll(PDO::FETCH_ASSOC) as $r) echo "     $t: " . json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}
try {
    $agg = $modx->query("SELECT MAX(updated_at) FROM kos_prestazioni");
    if ($agg) echo "  ultimo aggiornamento di kos_prestazioni: " . ($agg->fetchColumn() ?: '?') . "\n";
} catch (\Throwable $e) { /* colonna assente: niente data */ }

$html = $esegui($FILE_REPO, $vdp, ['branca' => 'Odontoiatria']);
if (!preg_match("/KOS_DATA_DB_B64 = '([^']*)'/", $html, $m)) { echo "  nessun dato nella barra con &branca=`Odontoiatria`: " . strip_tags(substr($html, 0, 200)) . " — FERMO, da decidere con Danilo\n"; @unlink($FILE_DB); @unlink($FILE_REPO); exit(1); }
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
@unlink($FILE_REPO);
exit($diverse || $np !== 2 ? 1 : 0);
