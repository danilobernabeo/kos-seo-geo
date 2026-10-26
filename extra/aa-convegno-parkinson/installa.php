<?php
/**
 * Convegno «La malattia di Parkinson - Realtà e prospettive» su anniazzurri.it (lavoro extra, fuori progetto).
 *
 * Pagina dell'evento e pagina di iscrizione copiate dal Mantello del custode (risorsa 560 e
 * /iscrizione-convegno-il-mantello-del-custode): stessi template, genitore, impostazioni e TV, con
 * testi, immagini e destinatario delle iscrizioni del nuovo convegno. Le due coppie di pagine convivono.
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php installa.php /www                      prova: controlla tutto e mostra cosa cambierebbe, non scrive
 *   php installa.php /www --scrivi             installa le pagine NON pubblicate (anteprima dal manager)
 *   php installa.php /www --scrivi --pubblica  installa e pubblica (stage, o produzione ad approvazione)
 *   php installa.php /www --pubblica --scrivi  con le pagine già installate: le pubblica e basta
 *   php installa.php /www --aggiorna-file [--scrivi]  sostituisce i file di files/ cambiati (backup e ripristino)
 *   php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-convegno-parkinson/ripristino.json [--scrivi]
 *
 * Cosa fa (con manifest di ripristino):
 *   1. copia i file di files/ in /www (immagini e locandina; mai sovrascrive un file diverso);
 *   2. crea il chunk del programma (stesso schema di programma-560);
 *   3. crea la pagina dell'evento e quella di iscrizione copiando i modelli; date di pubblicazione e
 *      ritiro programmate NON vengono copiate;
 *   4. controlla che nessun campo o TV delle nuove pagine nomini ancora il Mantello del custode;
 *   5. svuota la cache.
 * Il carosello della home non si tocca da qui: il chunk heading-home2 è un elemento statico del
 * progetto (vedi slide-home.html e README).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$ALIAS = 'convegno-parkinson-realta-prospettive';
$ALIAS_FORM = 'iscrizione-convegno-parkinson-realta-prospettive';
$MODELLO_EVENTO = 560;
$MODELLO_FORM = 'iscrizione-convegno-il-mantello-del-custode';   // alias
$ALIAS_VECCHIO = 'il-mantello-del-custode';                       // nei percorsi delle immagini e della locandina
$CHUNK = 'programma-convegno-parkinson-realta-prospettive';
$CHUNK_MODELLO = 'programma-560';
$NOTIFICHE = 'manuela.devasini@anniazzurri.it';
// Tracce del modello che non devono restare nelle pagine nuove
$VIETATO = '/mantello|custode|hospice|beati coniugi|san faustino|11 novembre|ciavattini|bencardino/i';

$leggi = function (string $rel) use ($DIR): string {
    $p = "$DIR/$rel";
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return trim(str_replace("\r\n", "\n", (string) file_get_contents($p)));
};

// Campi della pagina evento (gli altri si copiano dalla 560)
$CAMPI_EVENTO = [
    'pagetitle'   => 'La malattia di Parkinson',
    'longtitle'   => 'Convegno La malattia di Parkinson: realtà e prospettive',
    'menutitle'   => '',
    'description' => 'Convegno ECM gratuito, 12 dicembre 2026 a Cinisello Balsamo (MI), Sala Pertini: '
                   . 'la malattia di Parkinson tra clinica, terapia e riabilitazione. 5,2 crediti ECM.',
    'introtext'   => 'Realtà e prospettive',
    'alias'       => $ALIAS,
    'content'     => $leggi('elementi/contenuto-evento.html'),
];
// TV della pagina evento: le immagini e la locandina si ricavano dal valore del modello
// sostituendo l'alias (stesso formato di percorso); la segreteria è scritta per intero.
$TV_EVENTO = [
    'imgHeader'       => fn(string $v) => str_replace($ALIAS_VECCHIO, $ALIAS, $v),
    'imgHeaderMobile' => fn(string $v) => str_replace($ALIAS_VECCHIO, $ALIAS, $v),
    'programma'       => fn(string $v) => str_replace($ALIAS_VECCHIO, $ALIAS, $v),
    'segreteria'      => fn(string $v) => $leggi('elementi/segreteria-evento.html'),
];
$CAMPI_FORM = [
    'pagetitle'   => 'Iscrizione convegno La malattia di Parkinson',
    'longtitle'   => '',
    'menutitle'   => '',
    'description' => 'Modulo di iscrizione al convegno ECM gratuito La malattia di Parkinson: realtà e prospettive, '
                   . '12 dicembre 2026, Cinisello Balsamo (MI).',
    'introtext'   => '',
    'alias'       => $ALIAS_FORM,
];
$TV_FORM = [
    'evento_titolo'      => fn(string $v) => 'La malattia di Parkinson',
    'evento_sottotitolo' => fn(string $v) => 'Realtà e prospettive',
    'evento_data_luogo'  => fn(string $v) => '12 dicembre 2026, ore 8:30 – 13:30. Sala Pertini, Comune di Cinisello Balsamo. '
                                            . 'Piazza Natale Confalonieri 3 - Cinisello Balsamo (MI)',
    'evento_notifiche'   => fn(string $v) => $NOTIFICHE,
    'evento_segreteria'  => fn(string $v) => "Direzione, Polo Geriatrico Riabilitativo, KOS Group - Cinisello Balsamo (MI)\n"
                                            . "manuela.devasini@anniazzurri.it - tel. 02 618371",
    'evento_pagamento'   => fn(string $v) => '0',
];

/* ---------- argomenti e MODX ---------- */

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$pubblica = in_array('--pubblica', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] [--pubblica] | --ripristina <file> [--scrivi]\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modChunk, modTemplateVar, modTemplateVarResource, modResourceGroupResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$errori = 0;
$ferma = function (string $msg) use (&$errori) { echo "  ! $msg — FERMO\n"; $errori++; };
$corto = fn(string $s, int $n = 110) => mb_strlen($s) > $n ? mb_substr(str_replace("\n", ' ', $s), 0, $n) . '…' : str_replace("\n", ' ', $s);

/* ---------- ripristino ---------- */

if ($fileRip !== null) {
    $r = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($r)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    foreach ($r['risorse_create'] ?? [] as $id) {
        $o = $modx->getObject(modResource::class, $id);
        echo "  - risorsa $id" . ($o ? " «{$o->get('pagetitle')}» (/{$o->get('uri')})" : ' (già assente)') . "\n";
        if ($scrivi && $o) $o->remove();               // rimuove anche valori TV e gruppi
    }
    foreach ($r['chunk_creati'] ?? [] as $n) {
        echo "  - chunk $n\n";
        if ($scrivi && ($o = $modx->getObject(modChunk::class, ['name' => $n]))) $o->remove();
    }
    foreach ($r['file_sostituiti'] ?? [] as $x) {
        echo "  ~ file /{$x['file']}: versione precedente\n";
        if ($scrivi) copy($x['backup'], "$webroot/{$x['file']}");
    }
    foreach ($r['file_creati'] as $f) {
        echo "  - file /$f\n";
        if ($scrivi) @unlink("$webroot/$f");
    }
    foreach (array_reverse($r['cartelle_create'] ?? []) as $d) {
        echo "  - cartella /$d (se vuota)\n";
        if ($scrivi) @rmdir("$webroot/$d");
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata.\n"; }
    exit(0);
}

/* ---------- solo file: sostituisce immagini e documenti cambiati (con backup) ---------- */

if (in_array('--aggiorna-file', $argv, true)) {
    echo ($scrivi ? "== AGGIORNAMENTO FILE" : "== PROVA AGGIORNAMENTO FILE (aggiungere --scrivi)") . "\n";
    $sorg = "$DIR/files";
    $daFare = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sorg, FilesystemIterator::SKIP_DOTS)) as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($sorg) + 1));
        $dest = "$webroot/$rel";
        if (!is_file($dest)) { echo "  + /$rel (nuovo)\n"; $daFare[$rel] = [$f->getPathname(), false]; }
        elseif (md5_file($dest) === md5_file($f->getPathname())) echo "  = /$rel invariato\n";
        else { echo "  ~ /$rel (sostituito, il precedente va nel backup)\n"; $daFare[$rel] = [$f->getPathname(), true]; }
    }
    if (!$daFare) { echo "== Niente da aggiornare.\n"; exit(0); }
    if (!$scrivi) { echo "== Prova riuscita. Rilanciare con --aggiorna-file --scrivi.\n"; exit(0); }
    $bk = rtrim((string) (getenv('HOME') ?: $DIR), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-' . 'convegno-parkinson' . '-file';
    $rip = ['file_creati' => [], 'file_sostituiti' => []];
    foreach ($daFare as $rel => [$src, $esiste]) {
        if (!is_dir(dirname("$webroot/$rel"))) { echo "  ! cartella /" . dirname($rel) . " mancante — saltato\n"; continue; }
        if ($esiste) {
            @mkdir(dirname("$bk/file/$rel"), 0775, true);
            copy("$webroot/$rel", "$bk/file/$rel");
            $rip['file_sostituiti'][] = ['file' => $rel, 'backup' => "$bk/file/$rel"];
        } else $rip['file_creati'][] = $rel;
        copy($src, "$webroot/$rel");
        @mkdir($bk, 0775, true);
        file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    $modx->cacheManager->refresh();      // le varianti di immagine si rigenerano (file più recente delle varianti)
    echo "== File aggiornati, cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
    exit(0);
}

/* ---------- solo pubblicazione di pagine già installate ---------- */

$giaEvento = $modx->getObject(modResource::class, ['alias' => $ALIAS, 'deleted' => 0, 'context_key' => 'web']);
$giaForm = $modx->getObject(modResource::class, ['alias' => $ALIAS_FORM, 'deleted' => 0, 'context_key' => 'web']);
foreach ([$ALIAS, $ALIAS_FORM] as $a) {
    if ($modx->getObject(modResource::class, ['alias' => $a, 'deleted' => 1])) {
        echo "! Nel cestino c'è una risorsa con alias $a: svuotare il cestino o ripristinarla prima di procedere.\n";
        exit(2);
    }
}
if ($giaEvento || $giaForm) {
    if (!($giaEvento && $giaForm)) {
        echo "! Installazione parziale: esiste solo una delle due pagine. Ripristinare dal manifest e reinstallare.\n";
        exit(2);
    }
    echo "== Pagine già installate: evento {$giaEvento->get('id')} (pubblicata {$giaEvento->get('published')}), "
       . "iscrizione {$giaForm->get('id')} (pubblicata {$giaForm->get('published')})\n";
    if (!$pubblica) { echo "   Niente da fare (per pubblicarle: --pubblica --scrivi).\n"; exit(0); }
    foreach ([$giaEvento, $giaForm] as $o) {
        if ($o->get('published')) { echo "  = {$o->get('id')} già pubblicata\n"; continue; }
        echo "  ~ pubblico {$o->get('id')} /{$o->get('uri')}\n";
        if ($scrivi) {
            $o->set('published', 1);
            $o->set('publishedon', time());
            $o->set('publishedby', 1);
            $o->save();
        }
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata.\n"; }
    else echo "== Prova: rilanciare con --scrivi.\n";
    exit(0);
}

/* ---------- installazione: controlli ---------- */

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)")
   . " — convegno La malattia di Parkinson" . ($pubblica ? ', pagine pubblicate' : ', pagine NON pubblicate') . "\n";

$modEvento = $modx->getObject(modResource::class, $MODELLO_EVENTO);
$modForm = $modx->getObject(modResource::class, ['alias' => $MODELLO_FORM, 'deleted' => 0, 'context_key' => 'web']);
if (!$modEvento) $ferma("modello evento $MODELLO_EVENTO non trovato");
if (!$modForm) $ferma("modello iscrizione /$MODELLO_FORM non trovato");
if ($errori) exit(2);

/** Campi copiati dal modello, più quelli indicati; date e autori azzerati. */
$GLOBALS['scarto_menuindex'] = 0;
$preparaCampi = function (modResource $m, array $campi) use ($pubblica, $modx): array {
    $d = $m->toArray('', true);
    foreach (['id', 'uri', 'createdon', 'createdby', 'editedon', 'editedby', 'publishedon', 'publishedby',
              'deleted', 'deletedon', 'deletedby'] as $k) unset($d[$k]);
    $d['pub_date'] = 0;            // il modello può avere un ritiro programmato a fine evento
    $d['unpub_date'] = 0;
    $d['uri_override'] = 0;
    $max = 0;                       // primo posto libero in fondo ai fratelli (i due modelli sono vicini)
    foreach ($modx->getIterator(modResource::class, ['parent' => $m->get('parent')]) as $f) $max = max($max, (int) $f->get('menuindex'));
    $d['menuindex'] = $max + 1 + $GLOBALS['scarto_menuindex']++;
    $d['published'] = $pubblica ? 1 : 0;
    return array_merge($d, $campi);
};

/** TV del modello: [nome => [valore modello, valore nuovo]]. */
$preparaTv = function (modResource $m, array $regole) use ($modx): array {
    $out = [];
    foreach ($modx->getIterator(modTemplateVarResource::class, ['contentid' => $m->get('id')]) as $tvr) {
        $tv = $modx->getObject(modTemplateVar::class, $tvr->get('tmplvarid'));
        if (!$tv) continue;
        $v = (string) $tvr->get('value');
        $out[$tv->get('name')] = [$v, isset($regole[$tv->get('name')]) ? (string) $regole[$tv->get('name')]($v) : $v];
    }
    foreach ($regole as $n => $f) if (!isset($out[$n])) $out[$n] = ['', (string) $f('')];   // TV vuota sul modello
    return $out;
};

$piani = [
    'evento' => [$modEvento, $preparaCampi($modEvento, $CAMPI_EVENTO), $preparaTv($modEvento, $TV_EVENTO)],
    'iscrizione' => [$modForm, $preparaCampi($modForm, $CAMPI_FORM), $preparaTv($modForm, $TV_FORM)],
];

foreach ($piani as $tipo => [$m, $campi, $tv]) {
    echo "\n-- Pagina $tipo: copia di {$m->get('id')} /{$m->get('uri')} (template {$m->get('template')}, genitore {$m->get('parent')})\n";
    if ($m->get('pub_date') || $m->get('unpub_date')) {
        echo "  i il modello ha date programmate (pubblica {$m->get('pub_date')}, ritira {$m->get('unpub_date')}): non copiate\n";
    }
    foreach (['pagetitle', 'longtitle', 'menutitle', 'description', 'introtext', 'alias'] as $k) {
        printf("  %-12s %s\n  %-12s → %s\n", $k, $corto((string) $m->get($k)), '', $corto((string) $campi[$k]));
    }
    if ($tipo === 'evento') printf("  %-12s %s\n", 'content', 'elementi/contenuto-evento.html');
    else {
        $cf = trim((string) $campi['content']);
        echo "  content      copiato dal modello: " . ($cf === '' ? '(vuoto)' : "\n" . $cf) . "\n";
        // proprietà nella chiamata allo snippet scavalcano le TV (destinatari, evento di riferimento)
        if (preg_match('/emailTo|emailCC|emailBCC|&evento\b|@/i', $cf, $mm)) $ferma("content dell'iscrizione contiene «{$mm[0]}»: le iscrizioni potrebbero andare altrove");
    }
    printf("  %-12s searchable %d, hidemenu %d, cacheable %d, richtext %d, menuindex %d\n", 'impostazioni',
        $campi['searchable'], $campi['hidemenu'], $campi['cacheable'], $campi['richtext'], $campi['menuindex']);
    foreach ($tv as $n => [$vecchio, $nuovo]) {
        echo $vecchio === $nuovo ? "  TV $n = " . $corto($nuovo) . "\n"
                                 : "  TV $n: " . $corto($vecchio) . "\n      → " . $corto($nuovo) . "\n";
    }
    // Nessuna traccia del Mantello nei campi e nelle TV delle pagine nuove
    foreach ($campi as $k => $v) if (is_string($v) && preg_match($VIETATO, $v, $mm)) $ferma("$tipo, campo $k contiene «{$mm[0]}»");
    foreach ($tv as $n => [, $v]) if (preg_match($VIETATO, $v, $mm)) $ferma("$tipo, TV $n contiene «{$mm[0]}»");
}

// La pagina di ringraziamento viene dal modello: deve essere generica
$redirect = (int) ($piani['iscrizione'][2]['evento_redirect'][1] ?? 0);
if ($redirect) {
    $r = $modx->getObject(modResource::class, $redirect);
    if (!$r) $ferma("evento_redirect punta alla risorsa $redirect, che non esiste");
    elseif (preg_match($VIETATO, $r->get('pagetitle') . ' ' . $r->get('content'), $mm)) $ferma("la pagina di ringraziamento $redirect nomina «{$mm[0]}»: serve una pagina generica o dedicata");
    else echo "\n  = ringraziamento dopo l'iscrizione: risorsa $redirect «{$r->get('pagetitle')}» (generica)\n";
}
$dest = $piani['iscrizione'][2]['evento_notifiche'][1];
if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) $ferma("destinatario iscrizioni non valido: $dest");

echo "\n-- Chunk\n";
$chunkTesto = $leggi("elementi/chunks/$CHUNK.html");
$c = $modx->getObject(modChunk::class, ['name' => $CHUNK]);
$cm = $modx->getObject(modChunk::class, ['name' => $CHUNK_MODELLO]);
if ($c) $ferma("chunk $CHUNK esiste già");
else echo "  + chunk $CHUNK (categoria del modello $CHUNK_MODELLO: " . ($cm ? (int) $cm->get('category') : 0) . ")\n";
if (preg_match($VIETATO, $chunkTesto, $mm)) $ferma("il chunk contiene «{$mm[0]}»");

echo "\n-- File statici\n";
$sorg = "$DIR/files";
$copie = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sorg, FilesystemIterator::SKIP_DOTS)) as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($sorg) + 1));
    $destF = "$webroot/$rel";
    if (is_file($destF)) {
        if (md5_file($destF) === md5_file($f->getPathname())) { echo "  = /$rel già presente\n"; continue; }
        $ferma("/$rel esiste con un contenuto diverso (non si sovrascrive)");
        continue;
    }
    echo "  + /$rel\n";
    $copie[$rel] = $f->getPathname();
}
// i valori delle TV immagine/locandina devono puntare a file che ci saranno
foreach (['imgHeader', 'imgHeaderMobile', 'programma'] as $n) {
    [$vecchio, $nuovo] = $piani['evento'][2][$n];
    $v = ltrim($nuovo, '/');
    if ($v === '') { $ferma("TV $n vuota sul modello: impostarla a mano"); continue; }
    // il nome del file del modello deve contenere l'alias del Mantello, altrimenti la sostituzione
    // non cambia niente e la pagina nuova userebbe testata o locandina del Mantello
    if ($nuovo === $vecchio || !str_contains($nuovo, $ALIAS)) { $ferma("TV $n = $nuovo: non è un file del nuovo convegno"); continue; }
    $trovato = is_file("$webroot/$v") || isset($copie[$v]);
    if (!$trovato) {          // la media source può avere un percorso base (es. assets/img/)
        foreach (array_keys($copie) as $rel) if (str_ends_with($rel, '/' . basename($v))) $trovato = true;
    }
    if (!$trovato) $ferma("TV $n = $v: file non presente in files/");
}

if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi" . ($pubblica ? ' --pubblica' : '') . ".\n"; exit(0); }

/* ---------- scrittura ---------- */

$stamp = date('Ymd-His');
$bk = rtrim((string) (getenv('HOME') ?: $DIR), '/') . "/backup-kos-seo-geo/$stamp-convegno-parkinson";
mkdir($bk, 0775, true);
$rip = ['risorse_create' => [], 'chunk_creati' => [], 'file_creati' => [], 'cartelle_create' => []];
$salvaRip = function () use (&$rip, $bk) {
    file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
};
echo "\n-- Scrittura (manifest in $bk)\n";

foreach ($copie as $rel => $src) {
    $parti = explode('/', dirname($rel));
    for ($i = 1; $i <= count($parti); $i++) {
        $sub = implode('/', array_slice($parti, 0, $i));
        if (!is_dir("$webroot/$sub")) { mkdir("$webroot/$sub", 0775); $rip['cartelle_create'][] = $sub; }
    }
    copy($src, "$webroot/$rel");
    $rip['file_creati'][] = $rel;
    echo "  + /$rel\n";
}
$salvaRip();

$c = $modx->newObject(modChunk::class);
$c->set('name', $CHUNK);
$c->set('category', $cm ? (int) $cm->get('category') : 0);
$c->set('description', 'Programma del convegno La malattia di Parkinson (extra/aa-convegno-parkinson)');
$c->setContent($chunkTesto);
if ($c->save()) { $rip['chunk_creati'][] = $CHUNK; echo "  + chunk $CHUNK\n"; }
else { echo "  ! chunk $CHUNK: salvataggio fallito\n"; $errori++; }
$salvaRip();

$nuovi = [];
foreach ($piani as $tipo => [$m, $campi, $tv]) {
    $o = $modx->newObject($m->get('class_key') ?: modResource::class);   // stessa classe del modello
    $o->fromArray($campi);
    $o->set('createdon', time());
    $o->set('createdby', 1);
    if ($campi['published']) { $o->set('publishedon', time()); $o->set('publishedby', 1); }
    if (!$o->save()) { echo "  ! pagina $tipo: salvataggio fallito\n"; $errori++; break; }
    if ((string) $o->get('uri') === '') {             // di norma lo calcola save() con friendly_urls attivo
        $o->set('uri', $o->getAliasPath($o->get('alias')));
        $o->save();
    }
    $rip['risorse_create'][] = (int) $o->get('id');
    $salvaRip();
    foreach ($tv as $n => [, $v]) {
        if ($v === '') continue;
        if (!$o->setTVValue($n, $v)) { echo "  ! pagina $tipo: TV $n non scritta\n"; $errori++; }
    }
    foreach ($modx->getIterator(modResourceGroupResource::class, ['document' => $m->get('id')]) as $g) {
        $ng = $modx->newObject(modResourceGroupResource::class);
        $ng->fromArray(['document_group' => $g->get('document_group'), 'document' => $o->get('id')]);
        $ng->save();
        echo "  + pagina $tipo: gruppo di risorse {$g->get('document_group')} (come il modello)\n";
    }
    $nuovi[$tipo] = $o;
    echo "  + pagina $tipo {$o->get('id')} /{$o->get('uri')}" . ($campi['published'] ? '' : ' (non pubblicata)') . "\n";
}

$modx->cacheManager->refresh();
echo "== Cache svuotata.\n";
if (!$pubblica && !$errori) echo "== Anteprima dal manager (pagine non pubblicate). Per pubblicare: php installa.php /www --pubblica --scrivi\n";
echo "== Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
