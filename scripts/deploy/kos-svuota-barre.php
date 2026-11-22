<?php
/**
 * kos-svuota-barre.php — KOS 2: cancella dalla cache di MODX l'HTML delle barre di prenotazione delle schede
 * (chiavi kos_barra/*, salvate dallo snippet barraPrenotazioneDBmedicoPrestazione per kosseogeo.barra_cache_ttl
 * secondi). Da lanciare in crontab subito dopo l'aggiornamento delle tabelle kos_*, così prestazioni, medici e
 * abbinamenti nuovi compaiono alla prima visita invece che entro un'ora. Non tocca la cache delle pagine.
 *
 * Uso: php kos-svuota-barre.php /www            (stampa quante barre ha cancellato)
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-svuota-barre.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$dir = rtrim($modx->getOption('core_path'), '/') . '/cache/default/kos_barra';
$n = 0;
if (is_dir($dir)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                 RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        if ($f->isDir()) { @rmdir($f->getPathname()); } elseif (@unlink($f->getPathname())) { $n++; }
    }
}
echo date('Y-m-d H:i:s') . " barre di prenotazione cancellate dalla cache: $n\n";
