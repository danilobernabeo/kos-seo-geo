<?php
/**
 * cache-formato.php — KOS 2 / AA: formato della cache delle pagine di MODX (impostazione `cache_resource_format`, che
 * vale solo per la cache delle risorse; senza, MODX usa `cache_format`). Il resto della cache (impostazioni,
 * contesti, codice degli elementi) resta in PHP: si cambia solo ciò che pesa su OPcache.
 *
 * 0 = PHP (predefinito): la cache di ogni pagina è un file .php con tutto l'HTML, che PHP carica con include e che
 *     quindi occupa anche OPcache (memoria del cloud: 128 MB, piena il 24/11).
 * 1 = JSON: stessi contenuti letti con json_decode (stessa estensione .cache.php, xPDOFileCache), fuori da OPcache;
 *     il codice (snippet, plugin, core) resta in OPcache.
 *
 * Senza --scrivi stampa lo stato: valore attuale, numero e peso dei file di cache per formato.
 * Con --scrivi: copia del valore attuale in ~/backup-kos-seo-geo/<data>/cache_resource_format.json, imposta il valore nuovo,
 * svuota la cache di MODX due volte a 5 s di distanza (una richiesta in corso può riscrivere una pagina nel formato
 * vecchio). Da fare a traffico basso. Dopo, lanciare scalda-cache.php e rilanciare lo script senza --scrivi: il numero
 * di file «pagine JSON» deve avvicinarsi alle pagine della sitemap (un json_encode fallito, es. UTF-8 non valido,
 * lascia la pagina fuori cache senza errori visibili: controllare anche il log di MODX).
 * Il guadagno arriva solo dopo un riavvio di OPcache (opcache_reset è disattivata su MODX Cloud: lo fa il supporto).
 *
 * Uso:
 *   php scripts/deploy/cache-formato.php /www 1               (prova)
 *   php scripts/deploy/cache-formato.php /www 1 --scrivi
 *   php scripts/deploy/cache-formato.php /www --ripristina=~/backup-kos-seo-geo/<data>/cache_resource_format.json
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if (!is_file("$webroot/config.core.php")) exit("Uso: php cache-formato.php /www <0|1> [--scrivi] | --ripristina=<file>\n");
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
use MODX\Revolution\modSystemSetting;

$modx = modX::getInstance();
$modx->initialize('mgr');

$stato = function () use ($modx): void {
    $s = $modx->getObject(modSystemSetting::class, ['key' => 'cache_resource_format']);
    echo 'cache_resource_format nel database: ' . ($s ? var_export($s->get('value'), true) : '(assente: vale 0)') . "\n";
    $dir = MODX_CORE_PATH . 'cache';
    $n = [];
    if (is_dir($dir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
            if (!$f->isFile()) continue;
            $p = $f->getPathname();
            $tipo = str_contains($p, '/cache/resource/') ? 'pagine' : (str_contains($p, '/cache/includes/') ? 'codice elementi' : 'altro');
            $h = (string) @file_get_contents($p, false, null, 0, 5);
            $k = $tipo . ' ' . ($h === '<?php' ? 'PHP' : (($h[0] ?? '') === '{' || ($h[0] ?? '') === '[' ? 'JSON' : 'altro'));
            $n[$k][0] = ($n[$k][0] ?? 0) + 1;
            $n[$k][1] = ($n[$k][1] ?? 0) + $f->getSize();
        }
    }
    ksort($n);
    foreach ($n as $k => [$c, $b]) printf("  %-24s %6d file  %8.1f MB\n", $k, $c, $b / 1048576);
};

if (isset($opt['ripristina'])) {
    $file = str_replace('~', (string) getenv('HOME'), (string) $opt['ripristina']);
    $b = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    if (!is_array($b) || !array_key_exists('valore', $b)) exit("File di ripristino non trovato o non leggibile: $file\n");
    $s = $modx->getObject(modSystemSetting::class, ['key' => 'cache_resource_format']);
    if ($b['valore'] === null) { if ($s) $s->remove(); }
    else {
        if (!$s) { $s = $modx->newObject(modSystemSetting::class); $s->set('key', 'cache_resource_format'); $s->set('xtype', 'textfield'); $s->set('namespace', 'core'); $s->set('area', 'caching'); }
        $s->set('value', (string) $b['valore']);
        if (!$s->save()) exit("Salvataggio dell'impostazione non riuscito.\n");
    }
    $modx->cacheManager->refresh();
    sleep(5);                                                // una richiesta in corso può aver riscritto una pagina nel formato vecchio
    $modx->cacheManager->refresh();
    echo "Ripristinato cache_resource_format = " . var_export($b['valore'], true) . " e svuotata la cache.\n";
    $stato();
    exit(0);
}

$nuovo = $pos[1] ?? '';
if (!in_array($nuovo, ['0', '1'], true)) exit("Valore da impostare: 0 (PHP) o 1 (JSON)\n");
echo "== Prima\n";
$stato();
$s = $modx->getObject(modSystemSetting::class, ['key' => 'cache_resource_format']);
$vecchio = $s ? (string) $s->get('value') : null;
if ($vecchio === $nuovo) exit("\nGià impostato a $nuovo: niente da fare.\n");
if (!isset($opt['scrivi'])) exit("\nPROVA: imposterei cache_resource_format = $nuovo (ora " . var_export($vecchio, true) . ") e svuoterei la cache. Rilanciare con --scrivi.\n");

$dirB = getenv('HOME') . '/backup-kos-seo-geo/' . date('Ymd-His');
if (!is_dir($dirB) && !mkdir($dirB, 0700, true)) exit("Cartella di backup non creata: $dirB\n");
file_put_contents("$dirB/cache_resource_format.json", json_encode(['chiave' => 'cache_resource_format', 'valore' => $vecchio, 'data' => date('c')], JSON_PRETTY_PRINT));
if (!$s) { $s = $modx->newObject(modSystemSetting::class); $s->set('key', 'cache_resource_format'); $s->set('xtype', 'textfield'); $s->set('namespace', 'core'); $s->set('area', 'caching'); }
$s->set('value', $nuovo);
if (!$s->save()) exit("Salvataggio dell'impostazione non riuscito.\n");
$modx->cacheManager->refresh();
sleep(5);
$modx->cacheManager->refresh();
echo "\nImpostato cache_resource_format = $nuovo e svuotata la cache. Backup: $dirB/cache_resource_format.json\n"
   . "Ripristino: php scripts/deploy/cache-formato.php /www --ripristina=$dirB/cache_resource_format.json\n\n== Dopo\n";
$stato();
