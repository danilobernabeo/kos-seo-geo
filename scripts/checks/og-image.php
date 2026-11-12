<?php
/**
 * Diagnostica in SOLA LETTURA dell'og:image di una pagina: valore grezzo e reso della TV, risultato dello snippet ogImage
 * con la TV passata direttamente e con il parametro annidato come nel chunk head, esistenza del file.
 * Uso: php scripts/checks/og-image.php /www <uri della pagina, es. it/news-e-media/news/2026/...> [tv, default infoImage]
 */
declare(strict_types=1);
[$_, $webroot, $uri, $tv] = array_pad($argv, 4, '');
$webroot = rtrim($webroot, '/'); $tv = $tv ?: 'infoImage';
if (!is_file("$webroot/config.core.php") || $uri === '') { fwrite(STDERR, "Uso: php og-image.php /www <uri> [tv]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance(); $modx->initialize('web');
$r = $modx->getObject(modResource::class, ['uri' => trim($uri, '/')]);
if (!$r) { fwrite(STDERR, "Pagina non trovata: $uri\n"); exit(2); }
$modx->resource = $r; $modx->resourceIdentifier = $r->get('id');
$tvo = $modx->getObject(MODX\Revolution\modTemplateVar::class, ['name' => $tv]);
echo "pagina: ", $r->get('id'), " template ", $r->get('template'), "\n";
echo "TV $tv grezza: ", var_export($tvo ? $tvo->getValue($r->get('id')) : null, true), "\n";
echo "TV $tv getTVValue: ", var_export($r->getTVValue($tv), true), "\n";
$v = (string) $r->getTVValue($tv); $p = ltrim(rawurldecode(preg_replace('/[?#].*$/', '', $v)), '/');
echo "file: ", MODX_BASE_PATH . $p, " esiste: ", (is_file(MODX_BASE_PATH . $p) ? 'si' : 'no'), "\n";
echo "ogImage diretto: ", $modx->runSnippet('ogImage', ['tvs' => $tv, 'default' => 'assets/img/kos-condivisione-social.jpg']), "\n";
$chunk = $modx->newObject(MODX\Revolution\modChunk::class, ['name' => 'prova-og']);
$chunk->setCacheable(false);
$chunk->setContent('[[ogImage? &tvs=`[[+ogTv:default=`' . $tv . '`]]` &default=`assets/img/kos-condivisione-social.jpg`]]');
$out = $chunk->process([]);
$modx->getParser()->processElementTags('', $out, true, false, '[[', ']]', [], 10);
echo "ogImage come nel chunk head: ", $out, "\n";
