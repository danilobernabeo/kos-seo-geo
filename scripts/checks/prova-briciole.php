<?php
/**
 * prova-briciole.php — diagnostica delle briciole visibili di una risorsa (sola lettura).
 * Stampa la catena dei genitori (pubblicata, eliminata, nascosta dai menu, template, alias), l'impostazione
 * aa.breadcrumb_exclude e l'output dello snippet BreadCrumb con le stesse proprietà del chunk breadcrumb-stripe.
 * Uso: php scripts/checks/prova-briciole.php /www <id risorsa>
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
[$_, $webroot, $id] = $argv + [null, '', '0'];
require rtrim($webroot, '/') . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = MODX\Revolution\modX::getInstance();
$modx->initialize('web');
$res = $modx->getObject(MODX\Revolution\modResource::class, (int) $id);
if (!$res) exit("risorsa $id non trovata\n");
echo "aa.breadcrumb_exclude = " . $modx->getOption('aa.breadcrumb_exclude') . "\n";
echo "link_tag_scheme = " . $modx->getOption('link_tag_scheme') . "\n\n";
$r = $res;
while ($r) {
    $t = $modx->getObject(MODX\Revolution\modTemplate::class, $r->get('template'));
    printf("%5d  pub %d  del %d  hidemenu %d  ctx %s  tpl %s  class %s  alias %s  pagetitle «%s»  menutitle «%s»\n",
        $r->get('id'), $r->get('published'), $r->get('deleted'), $r->get('hidemenu'), $r->get('context_key'),
        $t ? $t->get('templatename') : '-', $r->get('class_key'), $r->get('alias'), $r->get('pagetitle'), $r->get('menutitle'));
    $p = (int) $r->get('parent');
    $r = $p ? $modx->getObject(MODX\Revolution\modResource::class, $p) : null;
}
$modx->resource = $res;
$modx->resourceIdentifier = $res->get('id');
$modx->elementCache = [];
$out = $modx->runSnippet('BreadCrumb', [
    'containerTpl' => '@INLINE <ol class="breadcrumb">[[+crumbs]]</ol>',
    'homeCrumbTpl' => '@INLINE <li class="breadcrumb-item"><a href="[[+link]]">[[+pagetitle]]</a></li>',
    'linkCrumbTpl' => 'breadcrumb-link',
    'currentCrumbTpl' => '@INLINE <li class="breadcrumb-item active">[[+pagetitle]]</li>',
    'exclude' => (string) $modx->getOption('aa.breadcrumb_exclude'),
]);
echo "\nBreadCrumb → " . var_export($out, true) . "\n";
$sn = $modx->getObject(MODX\Revolution\modSnippet::class, ['name' => 'BreadCrumb']);
if ($sn) echo "snippet BreadCrumb: id {$sn->get('id')}, statico " . (int) $sn->get('static') . ", " . strlen((string) $sn->get('snippet')) . " byte\n";
