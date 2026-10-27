<?php
/**
 * News «Creiamo insieme le forme del nostro Natale: il progetto Anni Azzurri e MUBA» (lavoro extra, fuori progetto).
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php installa.php /www                     prova: controlla tutto e mostra cosa farebbe, non scrive
 *   php installa.php /www --scrivi            installa: news NON pubblicata (anteprima dal manager)
 *   php installa.php /www --scrivi --pubblica installa e pubblica (stage, o produzione ad approvazione)
 *   php installa.php /www --pubblica --scrivi con la news già installata: la pubblica e basta
 *   php installa.php /www --aggiorna [--scrivi]  riallinea file, template, snippet e contenuto della news
 *                                             alla versione del pacchetto (backup di tutto ciò che cambia)
 *   php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-news-muba/ripristino.json [--scrivi]
 *
 * Cosa installa:
 *   - file in /www/assets/news-muba/ (CSS, JS, sagome SVG dal totem, logo MUBA, foto e testata segnaposto);
 *   - template interna-articolo-muba (copia di interna-articolo con fondo azzurro, sagome, galleria e video),
 *     con le stesse TV di interna-articolo;
 *   - snippet newsMubaRisorse (CSS e JS solo su questa pagina) e newsMubaImmagine (testata con ripiego);
 *   - la news sotto la risorsa News (8), con il contenuto di elementi/contenuto.html.
 * Il template interna-articolo (elemento del progetto) non si tocca.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$TEMPLATE = 'interna-articolo-muba';
$TEMPLATE_MODELLO = 'interna-articolo';
$SNIPPET = ['newsMubaRisorse', 'newsMubaImmagine'];
$PARENT = 8;                                                     // News
$ALIAS = 'il-natale-prende-nuova-forma-anni-azzurri-muba';   // fisso: è la destinazione del QR code del totem (bit.ly/3TQ9ymn)
$NEWS = [
    'pagetitle'   => 'Creiamo insieme le forme del nostro Natale: il progetto Anni Azzurri e MUBA',
    'longtitle'   => '',
    'description' => 'Anni Azzurri con MUBA e REMIDA Milano: anziani e bambini creano decorazioni natalizie con materiali '
                   . 'di recupero. Le foto dei laboratori e tre videotutorial.',
    'introtext'   => 'Carta, cartone e altri materiali di recupero si trasformano in decorazioni natalizie grazie alla '
                   . 'creatività di anziani e bambini.',
];
$TV_NEWS = ['newsImage' => '/assets/news-muba/testata-segnaposto.jpg'];

$leggi = function (string $rel) use ($DIR): string {
    $p = "$DIR/$rel";
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return trim(str_replace("\r\n", "\n", (string) file_get_contents($p)));
};
$norm = fn(string $s): string => trim(str_replace("\r\n", "\n", $s));

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$pubblica = in_array('--pubblica', $argv, true);
$aggiorna = in_array('--aggiorna', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] [--pubblica] | --aggiorna [--scrivi] | --ripristina <file> [--scrivi]\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modTemplateVar, modTemplateVarTemplate, modSnippet};
$modx = modX::getInstance();
$modx->initialize('mgr');
$errori = 0;
$ferma = function (string $msg) use (&$errori) { echo "  ! $msg — FERMO\n"; $errori++; };
$bkDir = fn(string $tipo) => rtrim((string) (getenv('HOME') ?: $DIR), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . "-news-muba$tipo";
$salva = fn(string $bk, array $rip) => file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

/** File di files/ da copiare: [rel => [sorgente, stato]] con stato nuovo|uguale|diverso */
$elencoFile = function () use ($DIR, $webroot): array {
    $out = [];
    $sorg = "$DIR/files";
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sorg, FilesystemIterator::SKIP_DOTS)) as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($sorg) + 1));
        $d = "$webroot/$rel";
        $out[$rel] = [$f->getPathname(), !is_file($d) ? 'nuovo' : (md5_file($d) === md5_file($f->getPathname()) ? 'uguale' : 'diverso')];
    }
    ksort($out);
    return $out;
};
/** Crea le cartelle mancanti di $rel e restituisce quelle create */
$cartelle = function (string $rel) use ($webroot): array {
    $nuove = [];
    $parti = explode('/', dirname($rel));
    for ($i = 1; $i <= count($parti); $i++) {
        $sub = implode('/', array_slice($parti, 0, $i));
        if (!is_dir("$webroot/$sub")) { mkdir("$webroot/$sub", 0775); $nuove[] = $sub; }
    }
    return $nuove;
};

/* ---------- ripristino ---------- */

if ($fileRip !== null) {
    $r = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($r)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    foreach ($r['risorse_create'] ?? [] as $id) {
        echo "  - risorsa $id\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, $id))) $o->remove();
    }
    foreach ($r['risorse_modificate'] ?? [] as $x) {
        echo "  ~ risorsa {$x['id']}: contenuto e riassunto precedenti\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, $x['id']))) {
            $o->set('content', file_get_contents($x['content'])); $o->set('introtext', $x['introtext']); $o->save();
        }
    }
    foreach ($r['template_creati'] ?? [] as $n) {
        echo "  - template $n (con i collegamenti alle TV)\n";
        if ($scrivi && ($t = $modx->getObject(modTemplate::class, ['templatename' => $n]))) {
            foreach ($modx->getIterator(modTemplateVarTemplate::class, ['templateid' => $t->get('id')]) as $l) $l->remove();
            $t->remove();
        }
    }
    foreach ($r['elementi_modificati'] ?? [] as $x) {
        echo "  ~ {$x['tipo']} {$x['nome']}: versione precedente\n";
        if (!$scrivi) continue;
        $o = $x['tipo'] === 'template' ? $modx->getObject(modTemplate::class, ['templatename' => $x['nome']])
                                       : $modx->getObject(modSnippet::class, ['name' => $x['nome']]);
        if ($o) { $o->setContent(file_get_contents($x['backup'])); $o->save(); }
    }
    foreach ($r['snippet_creati'] ?? [] as $n) {
        echo "  - snippet $n\n";
        if ($scrivi && ($o = $modx->getObject(modSnippet::class, ['name' => $n]))) $o->remove();
    }
    foreach ($r['file_sostituiti'] ?? [] as $x) {
        echo "  ~ file /{$x['file']}: versione precedente\n";
        if ($scrivi) copy($x['backup'], "$webroot/{$x['file']}");
    }
    foreach ($r['file_creati'] ?? [] as $f) {
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

$news = $modx->getObject(modResource::class, ['alias' => $ALIAS, 'deleted' => 0, 'context_key' => 'web']);
$tpl = $modx->getObject(modTemplate::class, ['templatename' => $TEMPLATE]);

/* ---------- aggiornamento di un'installazione esistente ---------- */

if ($aggiorna) {
    echo ($scrivi ? "== AGGIORNAMENTO" : "== PROVA AGGIORNAMENTO (aggiungere --scrivi)") . " — news MUBA\n";
    if (!$news || !$tpl) { echo "  ! news o template non installati: usare l'installazione normale — FERMO\n"; exit(2); }
    $file = array_filter($elencoFile(), fn($x) => $x[1] !== 'uguale');
    foreach ($file as $rel => [, $stato]) echo "  " . ($stato === 'nuovo' ? '+' : '~') . " /$rel\n";
    $elementi = [];
    if ($norm((string) $tpl->getContent()) !== $leggi("elementi/templates/$TEMPLATE.html")) $elementi[] = ['template', $TEMPLATE, $tpl, "elementi/templates/$TEMPLATE.html"];
    foreach ($SNIPPET as $n) {
        $o = $modx->getObject(modSnippet::class, ['name' => $n]);
        $nuovo = trim(preg_replace('/^\s*<\?php\s*/', '', $leggi("elementi/snippets/$n.php")));
        if (!$o) { echo "  ! snippet $n mancante — FERMO\n"; exit(2); }
        if ($norm((string) $o->getContent()) !== $nuovo) $elementi[] = ['snippet', $n, $o, $nuovo];
    }
    foreach ($elementi as $x) echo "  ~ {$x[0]} {$x[1]}\n";
    $contenuto = $leggi('elementi/contenuto.html');
    $cambiaNews = $norm((string) $news->get('content')) !== $contenuto || (string) $news->get('introtext') !== $NEWS['introtext'];
    if ($cambiaNews) echo "  ~ news {$news->get('id')}: contenuto e riassunto dal pacchetto (le modifiche fatte dal manager si perdono, restano nel backup)\n";
    if (!$file && !$elementi && !$cambiaNews) { echo "== Tutto già allineato.\n"; exit(0); }
    if (!$scrivi) { echo "== Prova riuscita. Rilanciare con --aggiorna --scrivi.\n"; exit(0); }
    $bk = $bkDir('-agg'); mkdir($bk, 0775, true);
    $rip = ['file_creati' => [], 'file_sostituiti' => [], 'cartelle_create' => [], 'elementi_modificati' => [], 'risorse_modificate' => []];
    foreach ($file as $rel => [$src, $stato]) {
        if ($stato === 'diverso') {
            @mkdir(dirname("$bk/file/$rel"), 0775, true);
            copy("$webroot/$rel", "$bk/file/$rel");
            $rip['file_sostituiti'][] = ['file' => $rel, 'backup' => "$bk/file/$rel"];
        } else {
            $rip['cartelle_create'] = array_merge($rip['cartelle_create'], $cartelle($rel));
            $rip['file_creati'][] = $rel;
        }
        copy($src, "$webroot/$rel");
        $salva($bk, $rip);
    }
    foreach ($elementi as [$tipo, $nome, $o, $nuovo]) {
        file_put_contents("$bk/$tipo-$nome.txt", (string) $o->getContent());
        $o->setContent($tipo === 'template' ? $leggi($nuovo) : $nuovo);
        $o->save();
        $rip['elementi_modificati'][] = ['tipo' => $tipo, 'nome' => $nome, 'backup' => "$bk/$tipo-$nome.txt"];
        $salva($bk, $rip);
    }
    if ($cambiaNews) {
        file_put_contents("$bk/news-{$news->get('id')}.html", (string) $news->get('content'));
        $rip['risorse_modificate'][] = ['id' => (int) $news->get('id'), 'content' => "$bk/news-{$news->get('id')}.html", 'introtext' => (string) $news->get('introtext')];
        $news->set('content', $contenuto);
        $news->set('introtext', $NEWS['introtext']);
        $news->save();
        $salva($bk, $rip);
    }
    $modx->cacheManager->refresh();
    echo "== Aggiornato, cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
    exit(0);
}

/* ---------- solo pubblicazione ---------- */

if ($news) {
    echo "== News già installata: {$news->get('id')} /{$news->get('uri')} (pubblicata {$news->get('published')})\n";
    if (!$pubblica) { echo "   Per aggiornarla: --aggiorna; per pubblicarla: --pubblica --scrivi.\n"; exit(0); }
    if ($news->get('published')) { echo "   Già pubblicata.\n"; exit(0); }
    echo "  ~ pubblico {$news->get('id')}\n";
    if ($scrivi) {
        $news->set('published', 1); $news->set('publishedon', time()); $news->set('publishedby', 1); $news->save();
        $modx->cacheManager->refresh(); echo "== Pubblicata, cache svuotata.\n";
    } else echo "== Prova: rilanciare con --scrivi.\n";
    exit(0);
}

/* ---------- installazione: controlli ---------- */

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)")
   . " — news MUBA" . ($pubblica ? ', pubblicata' : ', NON pubblicata') . "\n";

$genitore = $modx->getObject(modResource::class, $PARENT);
$modello = $modx->getObject(modTemplate::class, ['templatename' => $TEMPLATE_MODELLO]);
if (!$genitore) $ferma("risorsa News ($PARENT) non trovata");
else echo "  = genitore $PARENT «{$genitore->get('pagetitle')}» /{$genitore->get('uri')}\n";
if (!$modello) $ferma("template $TEMPLATE_MODELLO non trovato");
if ($tpl) $ferma("template $TEMPLATE esiste già");
foreach ($SNIPPET as $n) if ($modx->getObject(modSnippet::class, ['name' => $n])) $ferma("snippet $n esiste già");
if ($modx->getObject(modResource::class, ['alias' => $ALIAS])) $ferma("una risorsa (anche nel cestino) usa già l'alias $ALIAS");
if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }

// news più recente con il template classico: impostazioni da copiare e TV da mostrare
$sorella = null;
foreach ($modx->getIterator(modResource::class, ['parent' => $PARENT, 'template' => $modello->get('id'), 'deleted' => 0]) as $o) {
    if (!$sorella || (int) $o->get('publishedon') > (int) $sorella->get('publishedon')) $sorella = $o;
}
$tvModello = [];
foreach ($modx->getIterator(modTemplateVarTemplate::class, ['templateid' => $modello->get('id')]) as $l) {
    $tv = $modx->getObject(modTemplateVar::class, $l->get('tmplvarid'));
    if ($tv) $tvModello[$tv->get('name')] = [(int) $tv->get('id'), (int) $l->get('rank')];
}
echo "  + template $TEMPLATE (categoria di $TEMPLATE_MODELLO), TV: " . implode(', ', array_keys($tvModello)) . "\n";
foreach ($SNIPPET as $n) echo "  + snippet $n\n";
echo "  + news /$ALIAS: «{$NEWS['pagetitle']}»\n";
if ($sorella) {
    printf("    impostazioni come la news più recente %d «%s»: searchable %d, hidemenu %d, cacheable %d\n",
        $sorella->get('id'), mb_substr((string) $sorella->get('pagetitle'), 0, 60), $sorella->get('searchable'), $sorella->get('hidemenu'), $sorella->get('cacheable'));
    foreach (array_keys($tvModello) as $n) {
        $v = (string) $sorella->getTVValue($n);
        echo "    TV $n sulla news di confronto: " . ($v === '' ? '(vuota)' : mb_substr($v, 0, 80)) . (isset($TV_NEWS[$n]) ? "  → qui: {$TV_NEWS[$n]}" : '  → qui: vuota') . "\n";
    }
}
echo "\n-- File statici\n";
$file = $elencoFile();
foreach ($file as $rel => [, $stato]) {
    if ($stato === 'diverso') $ferma("/$rel esiste con un contenuto diverso (usare --aggiorna)");
    else echo "  " . ($stato === 'nuovo' ? '+' : '=') . " /$rel\n";
}
if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi" . ($pubblica ? ' --pubblica' : '') . ".\n"; exit(0); }

/* ---------- scrittura ---------- */

$bk = $bkDir(''); mkdir($bk, 0775, true);
$rip = ['risorse_create' => [], 'template_creati' => [], 'snippet_creati' => [], 'file_creati' => [], 'cartelle_create' => []];
echo "\n-- Scrittura (manifest in $bk)\n";

foreach ($file as $rel => [$src, $stato]) {
    if ($stato !== 'nuovo') continue;
    $rip['cartelle_create'] = array_merge($rip['cartelle_create'], $cartelle($rel));
    copy($src, "$webroot/$rel");
    $rip['file_creati'][] = $rel;
}
echo "  + file copiati: " . count($rip['file_creati']) . "\n";
$salva($bk, $rip);

foreach ($SNIPPET as $n) {
    $o = $modx->newObject(modSnippet::class);
    $o->set('name', $n);
    $o->set('category', (int) $modello->get('category'));
    $o->set('description', 'News Anni Azzurri e MUBA (extra/aa-news-muba)');
    $o->setContent(trim(preg_replace('/^\s*<\?php\s*/', '', $leggi("elementi/snippets/$n.php"))));
    if ($o->save()) { $rip['snippet_creati'][] = $n; echo "  + snippet $n\n"; } else { echo "  ! snippet $n non salvato\n"; $errori++; }
}
$salva($bk, $rip);

$t = $modx->newObject(modTemplate::class);
$t->set('templatename', $TEMPLATE);
$t->set('description', 'News Anni Azzurri e MUBA: copia di interna-articolo con la grafica del totem (extra/aa-news-muba)');
$t->set('category', (int) $modello->get('category'));
$t->setContent($leggi("elementi/templates/$TEMPLATE.html"));
if (!$t->save()) { echo "  ! template non salvato — mi fermo\n"; exit(2); }
$rip['template_creati'][] = $TEMPLATE;
$salva($bk, $rip);
foreach ($tvModello as $n => [$idTv, $rank]) {
    $l = $modx->newObject(modTemplateVarTemplate::class);
    $l->fromArray(['tmplvarid' => $idTv, 'templateid' => $t->get('id'), 'rank' => $rank], '', true, true);
    $l->save();
}
echo "  + template $TEMPLATE ({$t->get('id')}) con " . count($tvModello) . " TV\n";

$o = $modx->newObject(modResource::class);
$o->fromArray(array_merge([
    'parent' => $PARENT, 'template' => (int) $t->get('id'), 'alias' => $ALIAS, 'context_key' => 'web',
    'published' => $pubblica ? 1 : 0, 'searchable' => $sorella ? (int) $sorella->get('searchable') : 1,
    'hidemenu' => $sorella ? (int) $sorella->get('hidemenu') : 0, 'cacheable' => 1, 'richtext' => 0,
    'content' => $leggi('elementi/contenuto.html'), 'createdon' => time(), 'createdby' => 1,
], $NEWS, $pubblica ? ['publishedon' => time(), 'publishedby' => 1] : []));
if (!$o->save()) { echo "  ! news non salvata\n"; $errori++; }
else {
    if ((string) $o->get('uri') === '') { $o->set('uri', $o->getAliasPath($o->get('alias'))); $o->save(); }
    $rip['risorse_create'][] = (int) $o->get('id');
    $salva($bk, $rip);
    foreach ($TV_NEWS as $n => $v) $o->setTVValue($n, $v);
    echo "  + news {$o->get('id')} /{$o->get('uri')}" . ($pubblica ? '' : ' (non pubblicata)') . "\n";
}

$modx->cacheManager->refresh();
echo "== Cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
