<?php
/**
 * Parco di Veio (lavoro extra): banner del Poliambulatorio Parco di Veio (kosgroup.com) nella pagina della residenza
 * su anniazzurri.it, dopo la presentazione della struttura.
 *
 * Uso sul cloud di anniazzurri.it, dalla cartella del pacchetto:
 *   php banner.php /www                    prova: mostra cosa cambierebbe
 *   php banner.php /www --scrivi           scrive, con backup
 *   php banner.php /www --ripristina <cartella backup>/ripristino.json [--scrivi]
 *
 * Cosa fa:
 *   - copia aa/banner/files/assets/img/banner/poliambulatorio-parco-di-veio.jpg in /www/assets/img/banner/ (copertina
 *     del pieghevole; le varianti WebP le crea lo snippet `immagine`);
 *   - crea o aggiorna il chunk `banner-poliambulatorio-parco-di-veio` dal file aa/banner/<nome>.html;
 *   - nel campo content della risorsa con alias `residenza-anni-azzurri-parco-di-veio` inserisce la chiamata al chunk
 *     subito prima del primo <div class="accordion…">, cioè dopo il blocco di presentazione. Se la chiamata c'è già,
 *     non tocca il contenuto. Se il punto d'inserimento non si trova, si ferma.
 * Backup in ~/backup-kos-seo-geo/<data>-banner-pdv/ con ripristino.json.
 */
use MODX\Revolution\{modX, modResource, modChunk, modSnippet};

$args = array_slice($argv, 1);
$scrivi = in_array('--scrivi', $args, true);
$webroot = rtrim($args[0] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) exit("Uso: php banner.php /www [--scrivi] | --ripristina <file> [--scrivi]\n");

$DIR = __DIR__;
$CHUNK = 'banner-poliambulatorio-parco-di-veio';
$ALIAS = 'residenza-anni-azzurri-parco-di-veio';
$IMG = 'assets/img/banner/poliambulatorio-parco-di-veio.jpg';
$CHIAMATA = '[[$' . $CHUNK . ']]';

require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = modX::getInstance();
$modx->initialize('mgr');

$risorsa = function () use ($modx, $ALIAS) {
    $c = $modx->newQuery(modResource::class, ['alias' => $ALIAS, 'context_key' => 'web', 'deleted' => 0]);
    $tutte = $modx->getCollection(modResource::class, $c);
    return count($tutte) === 1 ? reset($tutte) : (count($tutte) ? false : null);
};

/* ---------- ripristino ---------- */
$i = array_search('--ripristina', $args, true);
if ($i !== false) {
    $json = $args[$i + 1] ?? '';
    $r = json_decode((string) @file_get_contents($json), true);
    if (!$r) exit("File di ripristino illeggibile: $json\n");
    $bk = dirname($json);
    echo $scrivi ? "== RIPRISTINO\n" : "== PROVA RIPRISTINO (aggiungere --scrivi)\n";
    if (isset($r['content'])) {
        $res = $modx->getObject(modResource::class, $r['content']['id']);
        echo "  < content della risorsa {$r['content']['id']}\n";
        if ($scrivi && $res) { $res->set('content', file_get_contents("$bk/content.html")); $res->save(); }
    }
    if (isset($r['chunk'])) {
        $o = $modx->getObject(modChunk::class, ['name' => $r['chunk']['name']]);
        if ($r['chunk']['nuovo']) { echo "  - chunk {$r['chunk']['name']} (creato dal pacchetto)\n"; if ($scrivi && $o) $o->remove(); }
        else { echo "  < chunk {$r['chunk']['name']}\n"; if ($scrivi && $o) { $o->setContent(file_get_contents("$bk/chunk.html")); $o->save(); } }
    }
    if (isset($r['immagine'])) {
        if ($r['immagine']['nuova']) { echo "  - /{$r['immagine']['file']} (aggiunta dal pacchetto)\n"; if ($scrivi) @unlink("$webroot/{$r['immagine']['file']}"); }
        else { echo "  < /{$r['immagine']['file']}\n"; if ($scrivi) copy("$bk/immagine.jpg", "$webroot/{$r['immagine']['file']}"); }
    }
    if ($scrivi) { $modx->cacheManager->refresh(); echo "== Ripristinato, cache svuotata.\n"; } else echo "== Prova riuscita.\n";
    exit;
}

/* ---------- prova ---------- */
echo $scrivi ? "== SCRITTURA\n" : "== PROVA (nessuna scrittura: aggiungere --scrivi)\n";
$fermi = 0;

if (!$modx->getObject(modSnippet::class, ['name' => 'immagine'])) { echo "  FERMO: snippet `immagine` non trovato\n"; $fermi++; }

$srcImg = "$DIR/aa/banner/files/$IMG";
$dstImg = "$webroot/$IMG";
$imgNuova = !is_file($dstImg);
$imgCambia = $imgNuova || md5_file($srcImg) !== md5_file($dstImg);
echo '  immagine /' . $IMG . ': ' . ($imgNuova ? 'NUOVA' : ($imgCambia ? 'DA SOSTITUIRE' : 'già uguale')) . ' (' . round(filesize($srcImg) / 1024) . " KB)\n";

$testoChunk = file_get_contents("$DIR/aa/banner/$CHUNK.html");
$chunk = $modx->getObject(modChunk::class, ['name' => $CHUNK]);
$chunkCambia = !$chunk || $chunk->getContent() !== $testoChunk;
echo "  chunk $CHUNK: " . (!$chunk ? 'NUOVO' : ($chunkCambia ? 'DA AGGIORNARE' : 'già uguale')) . "\n";
if ($chunk && $chunk->get('static')) { echo "  FERMO: il chunk esiste ed è statico, non lo sovrascrivo\n"; $fermi++; }

$res = $risorsa();
$nuovoContent = null;
if ($res === null) { echo "  FERMO: nessuna risorsa con alias $ALIAS\n"; $fermi++; }
elseif ($res === false) { echo "  FERMO: più risorse con alias $ALIAS\n"; $fermi++; }
else {
    $content = (string) $res->get('content');
    echo "  risorsa {$res->get('id')} «{$res->get('pagetitle')}» (template {$res->get('template')})\n";
    if (strpos($content, $CHIAMATA) !== false) {
        echo "  content: chiamata al chunk già presente, nessuna modifica\n";
    } elseif (preg_match('~<div[^>]*\bclass="accordion\b~', $content, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1];
        $nuovoContent = substr($content, 0, $pos) . $CHIAMATA . "\n\n" . substr($content, $pos);
        $prima = preg_replace('/\s+/', ' ', substr($content, max(0, $pos - 120), 120));
        echo "  content: inserimento prima di «" . $m[0][0] . "» (posizione $pos su " . strlen($content) . ")\n";
        echo "           testo che precede: …" . trim($prima) . "\n";
    } else {
        echo "  FERMO: nel content non trovo <div class=\"accordion\": punto d'inserimento da definire\n"; $fermi++;
    }
}

if ($fermi) exit("== $fermi FERMO: nessuna scrittura.\n");
if (!$scrivi) exit("== Prova riuscita.\n");
if (!$imgCambia && !$chunkCambia && $nuovoContent === null) exit("== Niente da fare.\n");

/* ---------- scrittura ---------- */
$bk = rtrim((string) (getenv('HOME') ?: $DIR), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-banner-pdv';
mkdir($bk, 0775, true);
$rip = ['data' => date('c')];
if ($imgCambia) {
    if (!$imgNuova) copy($dstImg, "$bk/immagine.jpg");
    $rip['immagine'] = ['file' => $IMG, 'nuova' => $imgNuova];
    @mkdir(dirname($dstImg), 0775, true);
    copy($srcImg, $dstImg); chmod($dstImg, 0644);
    echo "  + /$IMG\n";
}
if ($chunkCambia) {
    if ($chunk) file_put_contents("$bk/chunk.html", $chunk->getContent());
    $rip['chunk'] = ['name' => $CHUNK, 'nuovo' => !$chunk];
    if (!$chunk) {
        $chunk = $modx->newObject(modChunk::class);
        $chunk->set('name', $CHUNK);
        $chunk->set('description', 'Lavoro extra kos-aa-parco-di-veio: banner del Poliambulatorio Parco di Veio nella pagina della residenza');
    }
    $chunk->setContent($testoChunk);
    $chunk->save();
    echo "  + chunk $CHUNK\n";
}
if ($nuovoContent !== null) {
    file_put_contents("$bk/content.html", $res->get('content'));
    $rip['content'] = ['id' => $res->get('id')];
    $res->set('content', $nuovoContent);
    $res->save();
    echo "  + content della risorsa {$res->get('id')}\n";
}
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$modx->cacheManager->refresh();
echo "== Fatto, cache svuotata. Ripristino: php banner.php $webroot --ripristina $bk/ripristino.json --scrivi\n";
