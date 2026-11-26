<?php
/**
 * Punto Prelievi nelle schede di kosgroup.com (lavoro extra `punti-prelievo`, fuori dal progetto SEO/GEO).
 * Sezione «Punto Prelievi» (H2, ancora #punto-prelievi) nelle schede Poliambulatorio San Martino (Bollate) e Centro
 * Medico Riabilitativo (Cinisello Balsamo), con orari dei prelievi e dei referti e box del pieghevole.
 *
 * Uso (in SSH sul cloud, dalla radice dell'archivio del repository):
 *   php extra/punti-prelievo/installa.php /www                    prova: mostra cosa cambia, non scrive
 *   php extra/punti-prelievo/installa.php /www --scrivi           applica, con backup (ripetibile: aggiorna la sezione)
 *   php extra/punti-prelievo/installa.php /www --ripristina <cartella>/ripristino.json [--scrivi]
 *   php extra/punti-prelievo/installa.php --test <scheda.html>... prova locale sull'HTML pubblico (senza MODX)
 *
 * Cosa tocca: SOLO il contenuto (o la TV di contenuto) delle due risorse in cui si trova il blocco degli orari della
 * struttura, più i file di files/ (pieghevoli in assets/doc, copertine in assets/img). Nessun template, chunk, snippet,
 * plugin, CSS o TV str.* (gli orari dei prelievi non sono quelli della struttura e non vanno nel suo dato strutturato).
 * La sezione sta tra i segni <!-- punto-prelievi:inizio --> e <!-- punto-prelievi:fine -->: al rilancio si sostituisce
 * quella, altrimenti si inserisce prima del blocco #box-dettagli che contiene #orari-struttura (deve esserci una volta).
 * Si ferma se la sezione costruita contiene segnaposto, tag MODX, link relativi o «//», un H1, o parole dell'altra sede.
 * Le varianti WebP delle copertine si generano dopo, con scripts/immagini/ottimizza-immagini.php (vedi README).
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$C = json_decode((string) file_get_contents("$DIR/config.json"), true);
if (!is_array($C)) { fwrite(STDERR, "config.json non leggibile\n"); exit(1); }

$scrivi = in_array('--scrivi', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
$iTest = array_search('--test', $argv, true);
$errori = 0;
$ferma = function (string $msg) use (&$errori) { echo "  ! $msg — FERMO\n"; $errori++; };

const INIZIO = '<!-- punto-prelievi:inizio';
const FINE = '<!-- punto-prelievi:fine -->';

/* ============================================================ FUNZIONI PURE */

/** Peso del PDF del pacchetto in MB con una cifra decimale e la virgola (es. «1,6»). */
function peso(string $file): string
{
    return str_replace('.', ',', sprintf('%.1f', filesize($file) / 1048576));
}

/** Sezione della sede, dai frammenti di elementi/ e dai valori di config.json. */
function sezione(array $sede, string $dir): string
{
    $t = str_replace("\r\n", "\n", (string) file_get_contents("$dir/elementi/sezione.html"));
    $stile = rtrim(str_replace("\r\n", "\n", (string) file_get_contents("$dir/elementi/stile.html")), "\n");
    $val = $sede + ['PESO' => peso("$dir/files/assets/doc/{$sede['PDF']}"), 'STILE' => $stile];
    foreach ($val as $k => $v) if (is_string($v)) $t = str_replace('{{' . $k . '}}', $v, $t);
    return rtrim($t, "\n");
}

/** Controlli sulla sezione costruita: elenco dei problemi. */
function controlla(string $s, array $sede): array
{
    $p = [];
    if (preg_match('#\{\{\w+\}\}#', $s, $m)) $p[] = "segnaposto rimasto {$m[0]}";
    if (str_contains($s, '[[')) $p[] = 'tag MODX nella sezione';
    if (preg_match('#<h1\b#i', $s)) $p[] = 'H1 nella sezione';
    if (substr_count($s, '<h2') !== 1) $p[] = 'la sezione deve avere un solo H2';
    foreach (['href', 'src', 'srcset'] as $a) {
        preg_match_all('#\b' . $a . '="([^"]*)"#', $s, $mm);
        foreach ($mm[1] as $v) foreach (preg_split('#\s*,\s*#', $v) as $u) {
            $u = preg_replace('#\s.*$#', '', trim($u));
            if ($u === '' || str_starts_with($u, '//') || !preg_match('#^(/|https://|tel:\+39\d{6,11}$)#', $u)) $p[] = "$a non assoluto o vuoto: «{$u}»";
        }
    }
    preg_match_all('#<img\b[^>]*>#i', $s, $imgs);
    foreach ($imgs[0] as $i) foreach (['alt="', 'width="', 'height="', 'loading="lazy"'] as $att)
        if (!str_contains($i, $att) || str_contains($i, 'alt=""')) $p[] = "immagine senza $att";
    foreach ($sede['parole_vietate'] as $w) if (stripos($s, $w) !== false) $p[] = "parola dell'altra sede: «{$w}»";
    foreach (['7:00 - 9:30', 'MeticaLab', $sede['REFERTI_SEDE']] as $w) if (!str_contains($s, $w)) $p[] = "manca «{$w}»";
    return $p;
}

/**
 * Applica la sezione alle parti della risorsa (['content' => ..., 'tv:<nome>' => ...]).
 * Restituisce [parti modificate, descrizione] oppure [null, motivo].
 */
function applica(array $parti, string $sez, string $prima): array
{
    foreach ($parti as $k => $p) {                       // rilancio: sostituisce la sezione esistente
        $i = strpos($p, INIZIO);
        if ($i === false) continue;
        $f = strpos($p, FINE, $i);
        if ($f === false) return [null, "$k: segno di inizio senza fine"];
        $parti[$k] = substr($p, 0, $i) . $sez . substr($p, $f + strlen(FINE));
        return [$parti, "$k: sezione esistente sostituita"];
    }
    $trovati = [];
    foreach ($parti as $k => $p) {
        if (preg_match_all('#' . $prima . '#i', $p, $m, PREG_OFFSET_CAPTURE)) foreach ($m[0] as $x) $trovati[] = [$k, $x[1]];
    }
    if (count($trovati) !== 1) return [null, 'blocco degli orari della struttura (#box-dettagli con #orari-struttura) trovato ' . count($trovati) . ' volte, atteso 1'];
    [$k, $pos] = $trovati[0];
    $p = $parti[$k];
    $rientro = preg_match('#([ \t]*)$#', substr($p, 0, $pos), $r) ? $r[1] : '';
    $parti[$k] = substr($p, 0, $pos) . $sez . "\n\n" . $rientro . substr($p, $pos);
    return [$parti, "$k: sezione inserita prima del blocco degli orari della struttura"];
}

/* ============================================================ PROVA LOCALE (senza MODX) */
if ($iTest !== false) {
    $file = array_slice($argv, $iTest + 1);
    foreach ($C['sedi'] as $n => $sede) {
        $html = $file[$n] ?? '';
        echo "== {$sede['uri']} su $html\n";
        $sez = sezione($sede, $DIR);
        foreach (controlla($sez, $sede) as $x) $ferma($x);
        if (!is_file($html)) { echo "  (nessun HTML: solo controlli sulla sezione)\n"; continue; }
        $parti = ['content' => (string) file_get_contents($html)];
        [$nuove, $msg] = applica($parti, $sez, $C['inserisci_prima_di']);
        if (!$nuove) { $ferma($msg); continue; }
        echo "  = $msg\n";
        [$ancora, $msg2] = applica($nuove, $sez, $C['inserisci_prima_di']);
        if ($ancora['content'] !== $nuove['content']) $ferma('il rilancio cambia ancora il contenuto');
        else echo "  = rilancio: nessuna differenza ($msg2)\n";
        $out = sys_get_temp_dir() . '/punto-prelievi-' . basename($sede['uri']) . '.html';
        file_put_contents($out, $nuove['content']);
        echo "  > $out\n";
    }
    echo $errori ? "\n== $errori problemi\n" : "\n== Nessun problema\n";
    exit($errori ? 2 : 0);
}

/* ============================================================ MODX */
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] | /www --ripristina <file> [--scrivi] | --test <scheda.html>...\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$cloud = basename(rtrim((string) (getenv('HOME') ?: ''), '/'));
$STAGE = in_array($cloud, $C['cloud_stage'], true);
$tvDi = function (modResource $r) use ($modx): array {   // nome => valore delle TV della risorsa
    $out = [];
    foreach ($modx->getCollection(modTemplateVarResource::class, ['contentid' => $r->get('id')]) as $v) {
        $tv = $modx->getObject(modTemplateVar::class, (int) $v->get('tmplvarid'));
        if ($tv) $out[(string) $tv->get('name')] = (string) $v->get('value');
    }
    return $out;
};

/* ------------------------------------------------------------ ripristino */
if ($fileRip !== null) {
    $R = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($R)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? '== RIPRISTINO' : '== PROVA DI RIPRISTINO (aggiungere --scrivi)') . " da $fileRip\n";
    foreach ($R['risorse'] as $x) {
        $o = $modx->getObject(modResource::class, (int) $x['id']);
        if (!$o) { echo "  ! risorsa {$x['id']} non trovata\n"; continue; }
        echo "  ~ risorsa {$x['id']} /{$o->get('uri')}: " . implode(', ', array_keys($x['parti'])) . "\n";
        if (!$scrivi) continue;
        foreach ($x['parti'] as $k => $f) {
            $v = (string) file_get_contents($f);
            if ($k === 'content') { $o->set('content', $v); $o->save(); }
            else $o->setTVValue(substr($k, 3), $v);
        }
    }
    foreach ($R['file_modificati'] as $x) { echo "  ~ /{$x['rel']}: versione precedente\n"; if ($scrivi) copy($x['backup'], "$webroot/{$x['rel']}"); }
    foreach ($R['file_creati'] as $rel) { echo "  - /$rel\n"; if ($scrivi) @unlink("$webroot/$rel"); }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata. Ripristino completato.\n"; }
    exit(0);
}

/* ------------------------------------------------------------ prova */
echo ($scrivi ? '== INSTALLAZIONE' : '== PROVA (nessuna scrittura; aggiungere --scrivi)') . " — Punto Prelievi su "
   . $modx->getOption('site_url') . " (cloud $cloud: " . ($STAGE ? 'stage' : 'PRODUZIONE') . ")\n";

// File del pacchetto
$src = "$DIR/files";
$copie = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($src) + 1));
    $dest = "$webroot/$rel";
    $stato = !is_file($dest) ? 'nuovo' : (md5_file($dest) === md5_file($f->getPathname()) ? 'uguale' : 'diverso');
    if (str_ends_with($rel, '.jpg') && $f->getSize() > 200 * 1024) $ferma("/$rel pesa più di 200 KB");
    echo "  " . ['nuovo' => '+', 'uguale' => '=', 'diverso' => '~'][$stato] . " /$rel (" . round($f->getSize() / 1024) . " KB)\n";
    if ($stato !== 'uguale') $copie[$rel] = $stato;
}

// Risorse
$lavori = [];
foreach ($C['sedi'] as $sede) {
    $r = $modx->getObject(modResource::class, ['uri' => $sede['uri'], 'context_key' => 'web']);
    if (!$r) { $ferma("risorsa /{$sede['uri']} non trovata"); continue; }
    echo "\n== /{$sede['uri']} (id {$r->get('id')}, template {$r->get('template')}, pubblicata " . ($r->get('published') ? 'sì' : 'no') . ")\n";
    $sez = sezione($sede, $DIR);
    foreach (controlla($sez, $sede) as $x) $ferma($x);
    $parti = ['content' => (string) $r->get('content')];
    foreach ($tvDi($r) as $n => $v) if (!str_starts_with($n, 'str.')) $parti["tv:$n"] = $v;   // le TV str.* non si toccano
    [$nuove, $msg] = applica($parti, $sez, $C['inserisci_prima_di']);
    if (!$nuove) { $ferma($msg); continue; }
    $cambiate = array_keys(array_filter($nuove, fn($v, $k) => $v !== $parti[$k], ARRAY_FILTER_USE_BOTH));
    echo "  = $msg" . ($cambiate ? '' : ' (nessuna differenza)') . "\n";
    if ($cambiate) $lavori[] = ['r' => $r, 'vecchie' => $parti, 'nuove' => $nuove, 'cambiate' => $cambiate];
    $prova = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/punto-prelievi-' . basename($sede['uri']) . '.html';
    file_put_contents($prova, $nuove[$cambiate[0] ?? 'content']);
    echo "  > risultato in $prova\n";
}

if ($errori) { echo "\n== $errori problemi (FERMO): niente è stato scritto.\n"; exit(2); }
if (!$copie && !$lavori) { echo "\n== Tutto già allineato.\n"; exit(0); }
if (!$scrivi) { echo "\n== Prova completata senza problemi. Per applicare: stesso comando con --scrivi\n"; exit(0); }

/* ------------------------------------------------------------ scrittura */
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-punti-prelievo';
@mkdir("$bk/files", 0775, true);
$R = ['risorse' => [], 'file_modificati' => [], 'file_creati' => []];
foreach ($copie as $rel => $stato) {
    if ($stato === 'diverso') {
        @mkdir(dirname("$bk/files/$rel"), 0775, true);
        copy("$webroot/$rel", "$bk/files/$rel");
        $R['file_modificati'][] = ['rel' => $rel, 'backup' => "$bk/files/$rel"];
    } else $R['file_creati'][] = $rel;
}
foreach ($lavori as $l) {
    $x = ['id' => (int) $l['r']->get('id'), 'parti' => []];
    foreach ($l['cambiate'] as $k) {
        $f = "$bk/risorsa-{$x['id']}-" . str_replace(':', '-', $k) . '.html';
        file_put_contents($f, $l['vecchie'][$k]);
        $x['parti'][$k] = $f;
    }
    $R['risorse'][] = $x;
}
file_put_contents("$bk/ripristino.json", json_encode($R, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "\n== Backup in $bk\n";

foreach ($copie as $rel => $stato) {
    @mkdir(dirname("$webroot/$rel"), 0775, true);
    if (!copy("$src/$rel", "$webroot/$rel")) { echo "  ! copia non riuscita: /$rel\n"; exit(2); }
    @chmod("$webroot/$rel", 0644);
    echo "  " . ($stato === 'nuovo' ? '+' : '~') . " /$rel\n";
}
foreach ($lavori as $l) {
    $r = $l['r'];
    foreach ($l['cambiate'] as $k) {
        if ($k === 'content') { $r->set('content', $l['nuove'][$k]); if (!$r->save()) { echo "  ! salvataggio fallito: {$r->get('id')}\n"; exit(2); } }
        elseif (!$r->setTVValue(substr($k, 3), $l['nuove'][$k])) { echo "  ! TV non scritta: $k\n"; exit(2); }
        echo "  ~ risorsa {$r->get('id')} /{$r->get('uri')}: $k\n";
    }
}
$modx->cacheManager->refresh();
echo "== Cache svuotata.\n== Ripristino: php extra/punti-prelievo/installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
