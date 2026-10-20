<?php
/**
 * Pagina Sostenibilità: correzione dei difetti HTML già presenti nel contenuto (lavoro fuori progetto).
 *   54 (IT): «>» in più in fondo, ultima section e main__content non chiusi (il footer finiva dentro
 *            il contenuto principale), tre lettere accentate diventate «?» (più, comunità, professionalità);
 *   980 (EN): section dei grafici chiusa con il container ancora aperto e un </div> in più che chiudeva
 *            main__content a metà pagina; 37 percorsi relativi «assets/...» resi assoluti «/assets/...».
 * Le versioni prima/dopo sono in correzioni/: la pagina si scrive solo se è identica a «prima».
 *
 * Uso:  php correggi-html.php /www [--scrivi]                 prova / applica
 *       php correggi-html.php /www --ripristina [--scrivi]    rimette «prima» (solo se la pagina è ancora «dopo»)
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$indietro = in_array('--ripristina', $argv, true);
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php correggi-html.php /www [--scrivi] [--ripristina]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$norm = fn(string $s): string => trim(str_replace("\r\n", "\n", $s));

$bk = rtrim((string) (getenv('HOME') ?: __DIR__), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-sostenibilita-html';
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (aggiungere --scrivi)") . ($indietro ? " DEL RIPRISTINO" : '') . " — difetti HTML della pagina Sostenibilità\n";
$errori = 0;
foreach ([54, 980] as $id) {
    $res = $modx->getObject(\MODX\Revolution\modResource::class, $id);
    $prima = (string) file_get_contents(__DIR__ . "/correzioni/prima-$id.html");
    $dopo = (string) file_get_contents(__DIR__ . "/correzioni/dopo-$id.html");
    [$da, $a] = $indietro ? [$dopo, $prima] : [$prima, $dopo];
    $att = (string) $res->get('content');
    if ($norm($att) === $norm($a)) { echo "  = risorsa $id già a posto\n"; continue; }
    if ($norm($att) !== $norm($da)) { echo "  ! risorsa $id: contenuto diverso da quello atteso (modificata dopo il rilascio) — FERMO\n"; $errori++; continue; }
    echo "  ~ risorsa $id: " . ($indietro ? 'torna alla versione precedente' : 'corretta') . "\n";
    if ($scrivi) {
        @mkdir($bk, 0775, true);
        file_put_contents("$bk/risorsa-$id.html", $att);
        $res->set('content', $a);
        if (!$res->save()) { echo "    ! salvataggio fallito\n"; $errori++; }
    }
}
if ($scrivi && !$errori) { $modx->cacheManager->refresh(); echo "== Cache svuotata. Copia del contenuto sostituito in $bk/\n"; }
echo $errori ? "== $errori problemi.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
