<?php
/**
 * briciole-escluse.php — risorse elencate in <prefisso>.breadcrumb_exclude (sola lettura): id, uri, template,
 * pubblicata, ricercabile, figli. Serve a vedere quali pagine cambierebbero se una pagina esclusa mostrasse sé
 * stessa quando è la pagina corrente (AA 8, `/residenze`).
 *
 * Uso: php scripts/checks/briciole-escluse.php /www aa
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
[$_, $webroot, $pref] = $argv + [null, '', 'aa'];
if (!is_file(rtrim((string) $webroot, '/') . '/config.core.php')) exit("Uso: php briciole-escluse.php /www <aa|kos>\n");
require rtrim($webroot, '/') . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('web');
$val = (string) $modx->getOption($pref . '.breadcrumb_exclude');
echo "$pref.breadcrumb_exclude = $val\n";
foreach (array_filter(array_map('intval', explode(',', $val))) as $id) {
    $r = $modx->getObject(modResource::class, $id);
    if (!$r) { echo sprintf("%5d (non esiste)\n", $id); continue; }
    $t = $r->getOne('Template');
    printf("%5d /%-45s tpl %-28s pubbl %d  ricerc %d  menu-nascosta %d  figli %d  %s\n", $id, $r->get('uri'),
        $t ? $t->get('templatename') : '-', $r->get('published'), $r->get('searchable'), $r->get('hidemenu'),
        $modx->getCount(modResource::class, ['parent' => $id]), $r->get('class_key') === 'MODX\Revolution\modDocument' ? '' : $r->get('class_key'));
}
