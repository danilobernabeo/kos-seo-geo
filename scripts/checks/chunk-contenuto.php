<?php
/**
 * Diagnostica in SOLA LETTURA: stampa il contenuto grezzo di un chunk (es. per riportare nel repository una modifica
 * fatta a mano dal manager prima di rilasciare una nuova versione dell'elemento).
 *
 * Uso: php scripts/checks/chunk-contenuto.php /www <nome chunk>
 */
declare(strict_types=1);
[$_, $webroot, $nome] = array_pad($argv, 3, '');
$webroot = rtrim($webroot, '/');
if (!is_file("$webroot/config.core.php") || $nome === '') { fwrite(STDERR, "Uso: php chunk-contenuto.php /www <nome chunk>\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modChunk};
$modx = modX::getInstance();
$modx->initialize('web');
$c = $modx->getObject(modChunk::class, ['name' => $nome]);
if (!$c) { fwrite(STDERR, "Chunk non trovato: $nome\n"); exit(2); }
echo $c->getContent();
