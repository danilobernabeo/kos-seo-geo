<?php
/**
 * Toglie la velatura scura della testata anche dalla pagina del Mantello del custode (risorsa 560).
 *
 * Stessa regola CSS del convegno Parkinson, aggiunta nello <style> del chunk programma-560, che compare solo
 * in quella pagina. Il template interna-menu-form-registrazione (elemento del progetto) non si tocca.
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php mantello-senza-velatura.php /www                 prova: mostra cosa cambierebbe, non scrive
 *   php mantello-senza-velatura.php /www --scrivi        applica (backup del chunk)
 *   php mantello-senza-velatura.php /www --ripristina ~/backup-kos-seo-geo/<data>-mantello-velatura/programma-560.html [--scrivi]
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$CHUNK = 'programma-560';
$REGOLA = "  /* Testata: niente velatura scura della pagina (il titolo è già nell'immagine) */\n"
        . "  .hero-evento .overlay { background: none !important; }\n";

$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php mantello-senza-velatura.php /www [--scrivi] | --ripristina <file> [--scrivi]\n");
    exit(1);
}
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modChunk};
$modx = modX::getInstance();
$modx->initialize('mgr');

$c = $modx->getObject(modChunk::class, ['name' => $CHUNK]);
if (!$c) { echo "! chunk $CHUNK non trovato — FERMO\n"; exit(2); }
$att = (string) $c->getContent();

if ($fileRip !== null) {
    if (!is_file($fileRip)) { echo "! backup non trovato: $fileRip — FERMO\n"; exit(2); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . ": chunk $CHUNK dal backup\n";
    if ($scrivi) { $c->setContent((string) file_get_contents($fileRip)); $c->save(); $modx->cacheManager->refresh(); echo "== Fatto, cache svuotata.\n"; }
    exit(0);
}

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — testata del Mantello senza velatura\n";
if (str_contains($att, '.hero-evento .overlay')) { echo "  = la regola c'è già: niente da fare\n"; exit(0); }
if (substr_count($att, '</style>') !== 1) { echo "  ! nel chunk $CHUNK non c'è un solo </style> — FERMO\n"; exit(2); }
echo "  ~ chunk $CHUNK: aggiunta prima di </style>:\n$REGOLA";
if (!$scrivi) { echo "== Prova riuscita. Rilanciare con --scrivi.\n"; exit(0); }

$bk = rtrim((string) (getenv('HOME') ?: __DIR__), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-mantello-velatura';
mkdir($bk, 0775, true);
file_put_contents("$bk/$CHUNK.html", $att);
$c->setContent(str_replace('</style>', $REGOLA . '</style>', $att));
$c->save();
$modx->cacheManager->refresh();
echo "== Fatto, cache svuotata. Ripristino: php mantello-senza-velatura.php /www --ripristina $bk/$CHUNK.html --scrivi\n";
