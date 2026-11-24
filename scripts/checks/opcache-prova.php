<?php
/**
 * opcache-prova.php — quali file di un cloud MODX sono in OPcache (KOS-2, sola lettura). Va eseguito da PHP-FPM, non
 * da riga di comando (in CLI OPcache è spenta): si copia per pochi minuti nella webroot con un nome casuale, si apre
 * con curl e si cancella subito.
 *
 * Gruppi controllati con opcache_is_script_cached (non tra le funzioni disattivate su MODX Cloud):
 *   - codice di MODX (core/src, core/vendor: i file caricati da ogni richiesta);
 *   - cache di MODX (core/cache: impostazioni, contesti, codice degli elementi, pagine).
 * Stampa per gruppo: file, in OPcache, fuori; e i primi file fuori. Non scrive nulla.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
if (!function_exists('opcache_is_script_cached')) exit("opcache_is_script_cached non disponibile\n");
$root = __DIR__;
require $root . '/config.core.php';
$core = rtrim(MODX_CORE_PATH, '/');
$gruppi = [
    'core/src (MODX)'            => "$core/src",
    'core/vendor (librerie)'     => "$core/vendor",
    'core/cache/system_settings' => "$core/cache/system_settings",
    'core/cache/context_settings'=> "$core/cache/context_settings",
    'core/cache/includes (elementi)' => "$core/cache/includes",
    'core/cache/resource (pagine)'   => "$core/cache/resource",
    'core/components'            => "$core/components",
];
echo "PHP " . PHP_VERSION . ", SAPI " . PHP_SAPI . ", utente " . (get_current_user()) . ", " . date('c') . "\n\n";
foreach ($gruppi as $nome => $dir) {
    if (!is_dir($dir)) { printf("%-32s cartella assente\n", $nome); continue; }
    $tot = $si = 0; $fuori = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
        if (!$f->isFile() || substr($f->getFilename(), -4) !== '.php') continue;
        $tot++;
        if (opcache_is_script_cached($f->getPathname())) $si++; elseif (count($fuori) < 8) $fuori[] = substr($f->getPathname(), strlen($core) + 1);
    }
    printf("%-32s %6d file  %6d in OPcache  %6d fuori\n", $nome, $tot, $si, $tot - $si);
    foreach ($fuori as $x) echo "    fuori: $x\n";
}
/* Campione più vicino a una pagina: avvio di MODX (contesto web) e lettura dalla cache della risorsa ?id= (predefinita
 * la home), poi i file inclusi da questa richiesta: quelli fuori OPcache sono stati compilati adesso. */
require $core . '/vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('web');
$id = max(1, (int) ($_GET['id'] ?? $modx->getOption('site_start', null, 1)));
$modx->cacheManager->get('web/resources/' . $id, [\xPDO\xPDO::OPT_CACHE_KEY => $modx->getOption('cache_resource_key', null, 'resource')]);
$inc = get_included_files();
$si = 0; $fuori = [];
foreach ($inc as $x) { if (opcache_is_script_cached($x)) $si++; else $fuori[] = str_replace($core . '/', '', $x); }
printf("\nAvvio di MODX e cache della risorsa %d: %d file inclusi, %d in OPcache, %d fuori (compilati a ogni richiesta)\n", $id, count($inc), $si, count($inc) - $si);
foreach (array_slice($fuori, 0, 40) as $x) echo "    fuori: $x\n";
if (count($fuori) > 40) echo "    ... e altri " . (count($fuori) - 40) . "\n";
echo "\nNota: in core/src e core/vendor «fuori» comprende anche i file che nessuna richiesta usa; conta il campione qui sopra.\n";
