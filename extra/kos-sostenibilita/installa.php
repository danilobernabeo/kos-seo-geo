<?php
/**
 * Pagina Sostenibilità di kosgroup.com — grafici HTML al posto delle immagini (lavoro fuori progetto).
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php installa.php /www                         prova: controlla tutto, non scrive
 *   php installa.php /www --scrivi                applica (solo con tutte le conferme in dati.json)
 *   php installa.php /www --scrivi --dati-provvisori   applica anche con dati da confermare (SOLO STAGE)
 *   php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-sostenibilita/ripristino.json [--scrivi]
 *   php installa.php --test                       prova la sostituzione dei box sulle copie in baseline/ (senza MODX)
 *   ... --forza  procede anche se pagine o chunk sul sito sono cambiati rispetto alle copie in baseline/
 *
 * Cosa fa, nell'ordine (con backup di tutto ciò che modifica e manifest di ripristino):
 *   1. copia i file statici di files/ in /www (mai sovrascrive un file diverso);
 *   2. crea la categoria Sostenibilità, 3 snippet e 8 chunk; modifica i chunk grafico-dipendenti ed
 *      employee-chart, che aprono la riga dei box: resta l'apertura, al posto del box il nuovo chunk;
 *   3. crea le TV MIGX chartDipendenti e chartRicavi sul template della pagina IT e ne scrive i dati;
 *   4. nelle pagine 54 (IT) e 980 (EN) sostituisce i tre box Strutture, Attività, Ricavi con i chunk;
 *   5. svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$PAGINE = [
    54  => ['marker' => '[[$grafico-dipendenti]]', 'lingua' => 'it'],
    980 => ['marker' => '[[$employee-chart]]',     'lingua' => 'en'],
];
$CHUNK_MODIFICATI = ['grafico-dipendenti', 'employee-chart'];
$NUOVI = [
    'snippet' => ['kosChartDipendenti', 'kosChartRicavi', 'kosSostenibilitaRisorse'],
    'chunk'   => ['kosDipendenti_it', 'kosDipendenti_en', 'kosStrutture_it', 'kosStrutture_en',
                  'kosAttivita_it', 'kosAttivita_en', 'kosRicavi_it', 'kosRicavi_en'],
];
$CATEGORIA = 'Sostenibilità';
$TEMPLATE_DATI = 'generica';   // template della pagina 54: le TV servono solo lì

/* ---------- funzioni pure (provate con --test) ---------- */

/** Fine (offset dopo </div>) dell'elemento <div> che inizia in $pos, contando l'annidamento. */
function fineDiv(string $c, int $pos): ?int
{
    $prof = 0;
    $off = $pos;
    while (preg_match('#<div\b|</div\s*>#i', $c, $m, PREG_OFFSET_CAPTURE, $off)) {
        $prof += stripos($m[0][0], '</') === 0 ? -1 : 1;
        $off = $m[0][1] + strlen($m[0][0]);
        if ($prof === 0) return $off;
    }
    return null;
}

/** Sostituisce i 3 box che seguono $marker con le chiamate ai chunk. null se la struttura non è quella attesa. */
function sostituisciBox(string $c, string $marker, string $lingua): ?string
{
    $p = strpos($c, $marker);
    if ($p === false || strpos($c, $marker, $p + 1) !== false) return null;
    $pos = $p + strlen($marker);
    $inizio = null;
    for ($k = 0; $k < 3; $k++) {
        if (!preg_match('#\G(?:\s|<!--.*?-->)*#s', $c, $m, 0, $pos)) return null;
        $pos += strlen($m[0]);
        if (substr($c, $pos, 27) !== '<div class="box-infografica') return null;
        $inizio ??= $pos;
        $pos = fineDiv($c, $pos);
        if ($pos === null) return null;
    }
    $rientro = "\n\n                        ";
    $chiamate = implode($rientro, ["[[\$kosStrutture_{$lingua}]]", "[[\$kosAttivita_{$lingua}]]", "[[\$kosRicavi_{$lingua}]]"]);
    return substr($c, 0, $inizio) . $chiamate . substr($c, $pos);
}

$norm = fn(string $s): string => trim(str_replace("\r\n", "\n", $s));
$leggi = function (string $rel) use ($DIR): string {
    $p = $DIR . '/' . $rel;
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return (string) file_get_contents($p);
};

if (in_array('--test', $argv, true)) {
    foreach ($PAGINE as $id => $pg) {
        $prima = $leggi("baseline/risorsa-$id.html");
        $dopo = sostituisciBox($prima, $pg['marker'], $pg['lingua']);
        if ($dopo === null) { echo "risorsa $id: struttura non riconosciuta\n"; continue; }
        file_put_contents(sys_get_temp_dir() . "/risorsa-$id-dopo.html", $dopo);
        printf("risorsa %d: box-infografica %d → %d, byte %d → %d (%s)\n", $id,
            substr_count($prima, 'box-infografica'), substr_count($dopo, 'box-infografica'),
            strlen($prima), strlen($dopo), sys_get_temp_dir() . "/risorsa-$id-dopo.html");
    }
    exit(0);
}

/* ---------- argomenti e MODX ---------- */

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$forza = in_array('--forza', $argv, true);
$provvisori = in_array('--dati-provvisori', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] [--dati-provvisori] [--forza] | --ripristina <file> [--scrivi] | --test\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modChunk, modSnippet, modTemplate, modTemplateVar, modTemplateVarTemplate, modCategory};
$modx = modX::getInstance();
$modx->initialize('mgr');
$CLS = ['snippet' => modSnippet::class, 'chunk' => modChunk::class];
$errori = 0;
$ferma = function (string $msg) use (&$errori, $forza) {
    echo "  ! $msg" . ($forza ? " — procedo per --forza\n" : " — FERMO\n");
    if (!$forza) $errori++;
};

/* ---------- ripristino ---------- */

if ($fileRip !== null) {
    $r = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($r)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    foreach ($r['risorse'] as $x) {
        echo "  ~ risorsa {$x['id']}: contenuto originale\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, $x['id']))) { $o->set('content', file_get_contents($x['file'])); $o->save(); }
    }
    foreach ($r['tv'] as $nome) {
        echo "  - TV $nome (con collegamenti ai template e valori)\n";
        if ($scrivi && ($o = $modx->getObject(modTemplateVar::class, ['name' => $nome]))) $o->remove();
    }
    foreach ($r['chunk_modificati'] as $x) {
        echo "  ~ chunk {$x['name']}: contenuto originale\n";
        if ($scrivi && ($o = $modx->getObject(modChunk::class, ['name' => $x['name']]))) { $o->setContent(file_get_contents($x['file'])); $o->save(); }
    }
    foreach ($r['elementi_creati'] as $x) {
        echo "  - {$x['type']} {$x['name']}\n";
        if ($scrivi && ($o = $modx->getObject($CLS[$x['type']], ['name' => $x['name']]))) $o->remove();
    }
    if (!empty($r['categoria_creata'])) {
        echo "  - categoria $CATEGORIA\n";
        if ($scrivi && ($o = $modx->getObject(modCategory::class, ['category' => $CATEGORIA]))) $o->remove();
    }
    foreach ($r['file_creati'] as $f) {
        echo "  - file /$f\n";
        if ($scrivi) @unlink("$webroot/$f");
    }
    foreach (array_reverse($r['cartelle_create']) as $d) {
        echo "  - cartella /$d (se vuota)\n";
        if ($scrivi) @rmdir("$webroot/$d");
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata.\n"; }
    exit(0);
}

/* ---------- installazione ---------- */

$stamp = date('Ymd-His');
$bk = rtrim((string) (getenv('HOME') ?: $DIR), '/') . "/backup-kos-seo-geo/$stamp-sostenibilita";
$rip = ['risorse' => [], 'tv' => [], 'chunk_modificati' => [], 'elementi_creati' => [], 'categoria_creata' => false,
        'file_creati' => [], 'cartelle_create' => []];
$dati = json_decode($leggi('dati.json'), true);
$tvDef = json_decode($leggi('tv.json'), true);
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — grafici della pagina Sostenibilità\n";

echo "\n-- Dati da confermare con KOS\n";
$daConfermare = array_keys(array_filter($dati['conferme'], fn($v) => $v !== true));
foreach ($daConfermare as $d) echo "  ? $d\n";
if (!$daConfermare) echo "  = tutto confermato\n";
elseif ($scrivi && !$provvisori) { echo "  ! FERMO: in produzione si scrive solo con tutte le conferme (sullo stage: --dati-provvisori)\n"; $errori++; }

echo "\n-- Controlli sul sito\n";
$nuoviContenuti = [];
foreach ($PAGINE as $id => $pg) {
    $res = $modx->getObject(modResource::class, $id);
    if (!$res) { $ferma("risorsa $id non trovata"); continue; }
    $att = (string) $res->get('content');
    if ($norm($att) !== $norm($leggi("baseline/risorsa-$id.html"))) $ferma("risorsa $id: contenuto diverso dalla copia del 20/10 in baseline/");
    $dopo = sostituisciBox($att, $pg['marker'], $pg['lingua']);
    if ($dopo === null) { echo "  ! risorsa $id: i tre box dopo {$pg['marker']} non hanno la struttura attesa — FERMO\n"; $errori++; continue; }
    echo "  = risorsa $id ({$res->get('pagetitle')}): box riconosciuti, box-infografica "
       . substr_count($att, 'box-infografica') . " → " . substr_count($dopo, 'box-infografica') . "\n";
    $nuoviContenuti[$id] = [$res, $att, $dopo];
}
foreach ($CHUNK_MODIFICATI as $n) {
    $o = $modx->getObject(modChunk::class, ['name' => $n]);
    $nuovo = $norm($leggi("elementi/chunks/$n.html"));
    if (!$o) { $ferma("chunk $n non trovato"); continue; }
    $att = $norm((string) $o->getContent());
    if ($att === $nuovo) echo "  = chunk $n già aggiornato\n";
    elseif ($att !== $norm($leggi("baseline/$n.html"))) $ferma("chunk $n diverso dalla baseline del 20/10");
    else echo "  = chunk $n uguale alla baseline\n";
}
foreach ($NUOVI as $tipo => $nomi) foreach ($nomi as $n) {
    $o = $modx->getObject($CLS[$tipo], ['name' => $n]);
    $ext = $tipo === 'chunk' ? 'html' : 'php';
    if ($o && $norm((string) $o->getContent()) !== $norm(preg_replace('/^\s*<\?php\s*/', '', $leggi("elementi/{$tipo}s/$n.$ext")))) {
        $ferma("$tipo $n esiste già con un contenuto diverso");
    }
}
foreach (array_keys($tvDef) as $n) if ($modx->getObject(modTemplateVar::class, ['name' => $n])) $ferma("TV $n esiste già");
$tpl = $modx->getObject(modTemplate::class, ['templatename' => $TEMPLATE_DATI]);
if (!$tpl) { echo "  ! template $TEMPLATE_DATI non trovato — FERMO\n"; $errori++; }
if (!$modx->getCount(\MODX\Revolution\Transport\modTransportPackage::class, ['package_name:LIKE' => 'migx%', 'installed:!=' => null])) {
    echo "  ! MIGX non installato — FERMO\n"; $errori++;
}

echo "\n-- File statici\n";
$sorg = "$DIR/files";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sorg, FilesystemIterator::SKIP_DOTS));
$copie = [];
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($sorg) + 1);
    $dest = "$webroot/$rel";
    if (is_file($dest)) {
        if (md5_file($dest) === md5_file($f->getPathname())) { echo "  = /$rel già presente\n"; continue; }
        echo "  ! /$rel esiste con un contenuto diverso — FERMO (non si sovrascrive)\n"; $errori++; continue;
    }
    echo "  + /$rel\n";
    $copie[$rel] = $f->getPathname();
}

if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi" . ($daConfermare ? " (sullo stage anche --dati-provvisori)" : '') . ".\n"; exit(0); }

/* ---------- scrittura ---------- */
mkdir($bk, 0775, true);
$salvaRip = fn() => file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "\n-- Scrittura (backup in $bk)\n";

foreach ($copie as $rel => $src) {
    $d = dirname($rel);
    $parti = explode('/', $d);
    for ($i = 1; $i <= count($parti); $i++) {
        $sub = implode('/', array_slice($parti, 0, $i));
        if (!is_dir("$webroot/$sub")) { mkdir("$webroot/$sub", 0775); $rip['cartelle_create'][] = $sub; }
    }
    copy($src, "$webroot/$rel");
    $rip['file_creati'][] = $rel;
}
echo "  file copiati: " . count($copie) . "\n";
$salvaRip();

$cat = $modx->getObject(modCategory::class, ['category' => $CATEGORIA]);
if (!$cat) {
    $cat = $modx->newObject(modCategory::class);
    $cat->set('category', $CATEGORIA);
    $cat->save();
    $rip['categoria_creata'] = true;
}
$catId = (int) $cat->get('id');

foreach ($NUOVI as $tipo => $nomi) foreach ($nomi as $n) {
    $ext = $tipo === 'chunk' ? 'html' : 'php';
    $testo = $leggi("elementi/{$tipo}s/$n.$ext");
    if ($tipo === 'snippet') $testo = preg_replace('/^\s*<\?php\s*/', '', $testo);
    $o = $modx->getObject($CLS[$tipo], ['name' => $n]);
    if ($o) continue;                                          // già presente e identico (controllato sopra)
    $o = $modx->newObject($CLS[$tipo]);
    $o->set('name', $n);
    $o->set('category', $catId);
    $o->setContent(trim($testo));
    if ($o->save()) { $rip['elementi_creati'][] = ['type' => $tipo, 'name' => $n]; echo "  + $tipo $n\n"; }
    else { echo "  ! $tipo $n: salvataggio fallito\n"; $errori++; }
}
$salvaRip();

foreach ($CHUNK_MODIFICATI as $n) {
    $o = $modx->getObject(modChunk::class, ['name' => $n]);
    $nuovo = $leggi("elementi/chunks/$n.html");
    if ($norm((string) $o->getContent()) === $norm($nuovo)) continue;
    @mkdir("$bk/chunks", 0775, true);
    file_put_contents("$bk/chunks/$n.html", (string) $o->getContent());
    $o->setContent($nuovo);
    if ($o->save()) { $rip['chunk_modificati'][] = ['name' => $n, 'file' => "$bk/chunks/$n.html"]; echo "  ~ chunk $n\n"; }
    else { echo "  ! chunk $n: salvataggio fallito\n"; $errori++; }
}
$salvaRip();

foreach ($tvDef as $n => $def) {
    $tv = $modx->newObject(modTemplateVar::class);
    $tv->fromArray(['name' => $n, 'caption' => $def['caption'], 'type' => 'migx', 'category' => $catId,
                    'display' => 'default', 'default_text' => '', 'elements' => '']);
    $tv->set('input_properties', [
        'configs' => '', 'formtabs' => json_encode($def['formtabs'], JSON_UNESCAPED_UNICODE),
        'columns' => json_encode($def['columns'], JSON_UNESCAPED_UNICODE),
        'btntext' => '', 'previewurl' => '', 'jsonvarkey' => '', 'autoResourceFolders' => 'false',
    ]);
    if (!$tv->save()) { echo "  ! TV $n: salvataggio fallito\n"; $errori++; continue; }
    $rip['tv'][] = $n;
    $l = $modx->newObject(modTemplateVarTemplate::class);
    $l->fromArray(['tmplvarid' => $tv->get('id'), 'templateid' => $tpl->get('id'), 'rank' => 0], '', true, true);
    $l->save();
    $righe = [];
    foreach ($dati[$n] as $i => $r) $righe[] = ['MIGX_id' => (string) ($i + 1)] + $r;
    $tv->setValue(54, json_encode($righe, JSON_UNESCAPED_UNICODE));
    $tv->save();
    echo "  + TV $n (template $TEMPLATE_DATI, " . count($righe) . " righe sulla pagina 54)\n";
}
$salvaRip();

foreach ($nuoviContenuti as $id => [$res, $att, $dopo]) {
    file_put_contents("$bk/risorsa-$id.html", $att);
    $res->set('content', $dopo);
    if ($res->save()) { $rip['risorse'][] = ['id' => $id, 'file' => "$bk/risorsa-$id.html"]; echo "  ~ risorsa $id: tre box sostituiti dai chunk\n"; }
    else { echo "  ! risorsa $id: salvataggio fallito\n"; $errori++; }
}
$salvaRip();

$modx->cacheManager->refresh();
echo "== Cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
