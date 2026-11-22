<?php
/**
 * esporta-elemento.php — stampa il codice di un elemento MODX com'è sul sito (per confrontarlo con la baseline).
 * Uso: php scripts/checks/esporta-elemento.php /www snippet barraPrenotazioneDBmedicoPrestazione > /tmp/x.php
 * Tipi: snippet, chunk, template, plugin. Stampa su stderr editedon/descrizione se presenti.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
[$_, $webroot, $tipo, $nome] = $argv + [null, '', '', ''];
require rtrim($webroot, '/') . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = MODX\Revolution\modX::getInstance(); $modx->initialize('mgr');
$classi = ['snippet' => [MODX\Revolution\modSnippet::class, 'name'], 'chunk' => [MODX\Revolution\modChunk::class, 'name'],
           'template' => [MODX\Revolution\modTemplate::class, 'templatename'], 'plugin' => [MODX\Revolution\modPlugin::class, 'name']];
[$cl, $campo] = $classi[$tipo] ?? exit("tipo sconosciuto\n");
$o = $modx->getObject($cl, [$campo => $nome]);
if (!$o) { fwrite(STDERR, "non trovato\n"); exit(1); }
fwrite(STDERR, "id {$o->get('id')} statico " . (int) $o->get('static') . " file " . $o->get('static_file') . "\n");
echo $o->get($tipo === 'template' ? 'content' : ($tipo === 'chunk' ? 'snippet' : 'snippet'));
