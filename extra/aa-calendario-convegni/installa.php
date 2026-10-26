<?php
/**
 * Calendario dei convegni su /convegni-e-webinar di anniazzurri.it (lavoro extra, fuori progetto).
 *
 * Al posto del blocco scritto a mano sotto «Calendario esperienze formative» mette [[!aaCalendarioConvegni]]:
 * una card orizzontale per ogni convegno in programma (immagine a sinistra, data e luogo, titolo, sottotitolo,
 * pulsante), in ordine di data; i convegni passati spariscono da soli.
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php installa.php /www                 prova: controlla tutto e mostra cosa cambierebbe, non scrive
 *   php installa.php /www --scrivi        installa
 *   php installa.php /www --ripristina ~/backup-kos-seo-geo/<data>-calendario-convegni/ripristino.json [--scrivi]
 *   php installa.php --test               prova la sostituzione del blocco su baseline/convegni-e-webinar.html (senza MODX)
 *
 * Cosa fa (con backup e manifest di ripristino):
 *   1. copia i file di files/ in /www (mai sovrascrive un file diverso);
 *   2. crea lo snippet aaCalendarioConvegni e il chunk aaCalendarioConvegniCard;
 *   3. crea le TV evento_data, evento_luogo, evento_immagine sul template delle pagine evento
 *      (quello delle pagine in eventi.json) e le compila per gli eventi di eventi.json;
 *      il riassunto (introtext) degli eventi si scrive solo se è vuoto;
 *   4. nella pagina convegni-e-webinar sostituisce il blocco <div class="row"> che segue il titolo
 *      «Calendario esperienze formative» con la chiamata allo snippet;
 *   5. svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$PAGINA = 'convegni-e-webinar';                                   // alias
$TITOLO = '#<h2\b[^>]*>\s*Calendario esperienze formative\s*</h2>#i';
$CHIAMATA = '[[!aaCalendarioConvegni]]';
$SNIPPET = 'aaCalendarioConvegni';
$CHUNK = 'aaCalendarioConvegniCard';
$TV_CATEGORIA_DA = 'evento_titolo';                               // le nuove TV vanno nella sua categoria (KOSAcademy)
$TV_SORGENTE_DA = 'imgHeader';                                    // la TV immagine usa la sua media source
$TV = [
    'evento_data'     => ['caption' => 'Data del convegno (calendario)', 'type' => 'date',
                          'description' => 'Ordina il calendario di /convegni-e-webinar; dal giorno dopo il convegno non compare più. Vuota = non in calendario.',
                          'input_properties' => ['allowBlank' => 'true', 'hideTime' => 'true', 'startDay' => '1']],
    'evento_luogo'    => ['caption' => 'Luogo (calendario)', 'type' => 'text',
                          'description' => 'Es. «Sala Pertini, Cinisello Balsamo (MI)»: accanto alla data nella card del calendario.',
                          'input_properties' => ['allowBlank' => 'true']],
    'evento_immagine' => ['caption' => 'Immagine della card (calendario)', 'type' => 'image',
                          'description' => 'Immagine 16:9, 980x551 (come le news), con titolo leggibile anche piccola.',
                          'input_properties' => ['allowBlank' => 'true']],
];

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

/** [nuovo contenuto, blocco tolto] oppure null se la struttura non è quella attesa. */
function sostituisciCalendario(string $c, string $titolo, string $chiamata): ?array
{
    if (preg_match_all($titolo, $c, $mm, PREG_OFFSET_CAPTURE) !== 1) return null;
    $pos = $mm[0][0][1] + strlen($mm[0][0][0]);
    if (!preg_match('#\G\s*#', $c, $m, 0, $pos)) return null;
    $pos += strlen($m[0]);
    if (!preg_match('#\G<div\s+class="row"\s*>#i', $c, $m, 0, $pos)) return null;
    $fine = fineDiv($c, $pos);
    if ($fine === null) return null;
    return [substr($c, 0, $pos) . $chiamata . substr($c, $fine), substr($c, $pos, $fine - $pos)];
}

$leggi = function (string $rel) use ($DIR): string {
    $p = "$DIR/$rel";
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return (string) file_get_contents($p);
};
$testo = fn(string $html) => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));

if (in_array('--test', $argv, true)) {
    $r = sostituisciCalendario($leggi('baseline/convegni-e-webinar.html'), $TITOLO, $CHIAMATA);
    if (!$r) { echo "struttura non riconosciuta\n"; exit(2); }
    echo "tolto: " . $testo($r[1]) . "\n\ndopo:\n" . $r[0] . "\n";
    exit(0);
}

/* ---------- argomenti e MODX ---------- */

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php installa.php /www [--scrivi] | --ripristina <file> [--scrivi] | --test\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modChunk, modSnippet, modTemplateVar, modTemplateVarTemplate, modTemplateVarResource};
use MODX\Revolution\Sources\modMediaSourceElement;
$modx = modX::getInstance();
$modx->initialize('mgr');
$errori = 0;
$ferma = function (string $msg) use (&$errori) { echo "  ! $msg — FERMO\n"; $errori++; };
$risorsa = fn(string $alias) => $modx->getObject(modResource::class, ['alias' => $alias, 'deleted' => 0, 'context_key' => 'web']);

/* ---------- ripristino ---------- */

if ($fileRip !== null) {
    $r = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($r)) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    foreach ($r['contenuti'] as $x) {
        if (!is_file($x['file'])) { echo "  ! backup mancante: {$x['file']} — FERMO\n"; exit(2); }
    }
    foreach ($r['contenuti'] as $x) {
        echo "  ~ risorsa {$x['id']}: contenuto originale\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, $x['id']))) { $o->set('content', file_get_contents($x['file'])); $o->save(); }
    }
    foreach ($r['riassunti'] as $id => $vecchio) {
        echo "  ~ risorsa $id: riassunto «{$vecchio}»\n";
        if ($scrivi && ($o = $modx->getObject(modResource::class, (int) $id))) { $o->set('introtext', $vecchio); $o->save(); }
    }
    foreach ($r['tv'] as $nome) {
        echo "  - TV $nome (con valori e collegamenti)\n";
        if ($scrivi && ($tv = $modx->getObject(modTemplateVar::class, ['name' => $nome]))) {
            foreach ([modTemplateVarResource::class, modTemplateVarTemplate::class] as $cl) {
                foreach ($modx->getIterator($cl, ['tmplvarid' => $tv->get('id')]) as $o) $o->remove();
            }
            foreach ($modx->getIterator(modMediaSourceElement::class, ['object' => $tv->get('id'), 'object_class' => modTemplateVar::class]) as $o) $o->remove();
            $tv->remove();
        }
    }
    foreach ($r['elementi'] as $x) {
        echo "  - {$x['type']} {$x['name']}\n";
        $cl = $x['type'] === 'snippet' ? modSnippet::class : modChunk::class;
        if ($scrivi && ($o = $modx->getObject($cl, ['name' => $x['name']]))) $o->remove();
    }
    foreach ($r['file_creati'] as $f) {
        echo "  - file /$f\n";
        if ($scrivi) @unlink("$webroot/$f");
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Cache svuotata.\n"; }
    exit(0);
}

/* ---------- installazione: controlli ---------- */

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — calendario dei convegni\n";

echo "\n-- Pagina /$PAGINA\n";
$pagina = $risorsa($PAGINA);
$nuovoContenuto = null;
if (!$pagina) $ferma("pagina $PAGINA non trovata");
else {
    $att = (string) $pagina->get('content');
    if (str_contains($att, $CHIAMATA)) $ferma("la pagina contiene già $CHIAMATA (già installato?)");
    else {
        $r = sostituisciCalendario($att, $TITOLO, $CHIAMATA);
        if (!$r) $ferma("dopo «Calendario esperienze formative» non c'è un <div class=\"row\"> riconoscibile");
        else {
            [$nuovoContenuto, $tolto] = $r;
            echo "  = risorsa {$pagina->get('id')} (template {$pagina->get('template')}): blocco riconosciuto (" . strlen($tolto) . " byte)\n";
            echo "    tolto:  " . mb_substr($testo($tolto), 0, 220) . "\n";
            echo "    al suo posto: $CHIAMATA\n";
        }
    }
}

echo "\n-- Eventi (eventi.json)\n";
$eventi = json_decode($leggi('eventi.json'), true);
if (!is_array($eventi) || !$eventi) { fwrite(STDERR, "eventi.json non valido\n"); exit(1); }
$template = null;
$risEventi = [];
foreach ($eventi as $alias => $v) {
    $o = $risorsa($alias);
    if (!$o) { $ferma("pagina evento /$alias non trovata"); continue; }
    $template ??= (int) $o->get('template');
    if ((int) $o->get('template') !== $template) $ferma("/$alias ha il template {$o->get('template')}, le altre $template");
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v['evento_data'] ?? '')) $ferma("/$alias: evento_data non nel formato AAAA-MM-GG hh:mm:ss");
    $intro = (string) $o->get('introtext');
    $notaIntro = $intro === '' ? "riassunto → «{$v['introtext']}»"
        : ($intro === $v['introtext'] ? 'riassunto già uguale' : "riassunto lasciato com'è: «{$intro}» (eventi.json: «{$v['introtext']}»)");
    printf("  = %d /%s%s: %s · %s · %s\n    %s\n", $o->get('id'), $alias, $o->get('published') ? '' : ' (NON pubblicata)',
        $v['evento_data'], $v['evento_luogo'], $v['evento_immagine'], $notaIntro);
    $risEventi[$alias] = $o;
}

echo "\n-- TV ed elementi\n";
$catTv = $modx->getObject(modTemplateVar::class, ['name' => $TV_CATEGORIA_DA]);
$catId = $catTv ? (int) $catTv->get('category') : 0;
$tvSorgente = $modx->getObject(modTemplateVar::class, ['name' => $TV_SORGENTE_DA]);
$sorgenti = $tvSorgente ? iterator_to_array($modx->getIterator(modMediaSourceElement::class, ['object' => $tvSorgente->get('id'), 'object_class' => modTemplateVar::class]), false) : [];
foreach ($TV as $n => $d) {
    if ($modx->getObject(modTemplateVar::class, ['name' => $n])) $ferma("TV $n esiste già");
    else echo "  + TV $n ({$d['type']}) sul template $template, categoria $catId\n";
}
echo "    media source della TV immagine copiata da $TV_SORGENTE_DA: " . (count($sorgenti) ? count($sorgenti) . ' collegamento/i' : 'nessuno (sorgente predefinita)') . "\n";
if ($modx->getObject(modSnippet::class, ['name' => $SNIPPET])) $ferma("snippet $SNIPPET esiste già"); else echo "  + snippet $SNIPPET\n";
if ($modx->getObject(modChunk::class, ['name' => $CHUNK])) $ferma("chunk $CHUNK esiste già"); else echo "  + chunk $CHUNK\n";
echo "  i snippet immagine " . ($modx->getObject(modSnippet::class, ['name' => 'immagine']) ? 'presente: card con <picture> e varianti' : "assente: card con <img> semplice") . "\n";

echo "\n-- File statici\n";
$sorg = "$DIR/files";
$copie = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sorg, FilesystemIterator::SKIP_DOTS)) as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($sorg) + 1));
    if (is_file("$webroot/$rel")) {
        if (md5_file("$webroot/$rel") === md5_file($f->getPathname())) { echo "  = /$rel già presente\n"; continue; }
        $ferma("/$rel esiste con un contenuto diverso (non si sovrascrive)");
        continue;
    }
    echo "  + /$rel\n";
    $copie[$rel] = $f->getPathname();
}
foreach ($eventi as $alias => $v) {
    $rel = ltrim($v['evento_immagine'], '/');
    if (!is_file("$webroot/$rel") && !isset($copie[$rel])) $ferma("/$alias: immagine /$rel non trovata");
}

if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi.\n"; exit(0); }

/* ---------- scrittura ---------- */

$stamp = date('Ymd-His');
$bk = rtrim((string) (getenv('HOME') ?: $DIR), '/') . "/backup-kos-seo-geo/$stamp-calendario-convegni";
mkdir($bk, 0775, true);
$rip = ['contenuti' => [], 'riassunti' => [], 'tv' => [], 'elementi' => [], 'file_creati' => []];
$salvaRip = function () use (&$rip, $bk) {
    file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
};
echo "\n-- Scrittura (backup in $bk)\n";

foreach ($copie as $rel => $src) {
    if (!is_dir(dirname("$webroot/$rel"))) { echo "  ! cartella /" . dirname($rel) . " mancante\n"; $errori++; continue; }
    copy($src, "$webroot/$rel");
    $rip['file_creati'][] = $rel;
    echo "  + /$rel\n";
}
$salvaRip();

foreach ([[$SNIPPET, modSnippet::class, 'snippet', "elementi/snippets/$SNIPPET.php"], [$CHUNK, modChunk::class, 'chunk', "elementi/chunks/$CHUNK.html"]] as [$nome, $cl, $tipo, $file]) {
    $o = $modx->newObject($cl);
    $o->set('name', $nome);
    $o->set('category', $catId);
    $o->set('description', 'Calendario dei convegni (extra/aa-calendario-convegni)');
    $o->setContent(trim(preg_replace('/^\s*<\?php\s*/', '', $leggi($file))));
    if ($o->save()) { $rip['elementi'][] = ['type' => $tipo, 'name' => $nome]; echo "  + $tipo $nome\n"; }
    else { echo "  ! $tipo $nome: salvataggio fallito\n"; $errori++; }
}
$salvaRip();

foreach ($TV as $n => $d) {
    $tv = $modx->newObject(modTemplateVar::class);
    $tv->fromArray(['name' => $n, 'caption' => $d['caption'], 'description' => $d['description'], 'type' => $d['type'],
                    'category' => $catId, 'display' => 'default', 'default_text' => '', 'elements' => '']);
    $tv->set('input_properties', $d['input_properties']);
    $tv->set('output_properties', []);
    if (!$tv->save()) { echo "  ! TV $n: salvataggio fallito\n"; $errori++; continue; }
    $rip['tv'][] = $n;
    $salvaRip();
    $l = $modx->newObject(modTemplateVarTemplate::class);
    $l->fromArray(['tmplvarid' => $tv->get('id'), 'templateid' => $template, 'rank' => 0], '', true, true);
    $l->save();
    if ($d['type'] === 'image') {
        foreach ($sorgenti as $s) {
            $ns = $modx->newObject(modMediaSourceElement::class);
            $ns->fromArray(['source' => $s->get('source'), 'object_class' => modTemplateVar::class,
                            'object' => $tv->get('id'), 'context_key' => $s->get('context_key')], '', true, true);
            $ns->save();
        }
    }
    foreach ($eventi as $alias => $v) $tv->setValue($risEventi[$alias]->get('id'), $v[$n]);
    $tv->save();
    echo "  + TV $n (" . count($eventi) . " valori)\n";
}

foreach ($eventi as $alias => $v) {
    $o = $risEventi[$alias];
    if ((string) $o->get('introtext') !== '') continue;
    $rip['riassunti'][(string) $o->get('id')] = '';
    $o->set('introtext', $v['introtext']);
    $o->save();
    echo "  ~ /$alias: riassunto «{$v['introtext']}»\n";
}
$salvaRip();

file_put_contents("$bk/risorsa-{$pagina->get('id')}.html", (string) $pagina->get('content'));
$pagina->set('content', $nuovoContenuto);
if ($pagina->save()) { $rip['contenuti'][] = ['id' => (int) $pagina->get('id'), 'file' => "$bk/risorsa-{$pagina->get('id')}.html"]; echo "  ~ /$PAGINA: blocco sostituito da $CHIAMATA\n"; }
else { echo "  ! /$PAGINA: salvataggio fallito\n"; $errori++; }
$salvaRip();

$modx->cacheManager->refresh();
echo "== Cache svuotata. Ripristino: php installa.php /www --ripristina $bk/ripristino.json --scrivi\n";
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
