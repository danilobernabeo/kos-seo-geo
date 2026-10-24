<?php
/**
 * Mostra in SOLA LETTURA campi e TV di una risorsa (diagnosi rapida: template, date, immagini, alias).
 * Uso: php scripts/checks/aa-risorsa.php /www <id | alias>
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
$chi = $argv[2] ?? '';
if ($webroot === '' || $chi === '') { fwrite(STDERR, "Uso: php aa-risorsa.php /www <id|alias>\n"); exit(1); }
require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modTemplateVar, modTemplateVarResource, modTemplateVarTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');
$r = ctype_digit($chi) ? $modx->getObject(modResource::class, (int) $chi) : $modx->getObject(modResource::class, ['alias' => $chi]);
if (!$r) { fwrite(STDERR, "Risorsa non trovata: $chi\n"); exit(1); }
$tpl = $modx->getObject(modTemplate::class, $r->get('template'));
$d = fn($t) => $t ? date('Y-m-d H:i:s', is_numeric($t) ? (int) $t : strtotime((string) $t)) : '-';
printf("id %d  «%s»\nuri %s  template %s (%d)\npubblicata %d  ricercabile %d  in cache %d\ncreata %s  modificata %s  pubblicata il %s\n",
    $r->get('id'), $r->get('pagetitle'), $r->get('uri'), $tpl ? $tpl->get('templatename') : '(nessuno)', $r->get('template'),
    $r->get('published'), $r->get('searchable'), $r->get('cacheable'),
    $d($r->get('createdon')), $d($r->get('editedon')), $d($r->get('publishedon')));
echo "\nTV del template:\n";
foreach ($modx->getIterator(modTemplateVarTemplate::class, ['templateid' => $r->get('template')]) as $tt) {
    $tv = $modx->getObject(modTemplateVar::class, $tt->get('tmplvarid'));
    if (!$tv) continue;
    $v = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id'), 'contentid' => $r->get('id')]);
    $val = $v ? (string) $v->get('value') : '';
    printf("  %-24s %s%s\n", $tv->get('name'), $val === '' ? '(vuota)' : mb_substr($val, 0, 150),
        $val !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $val) ? (is_file($webroot . '/' . ltrim($val, '/')) ? '  [file presente]' : '  [FILE NON TROVATO]') : '');
}
