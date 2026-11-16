<?php
/**
 * aaLinkArrivo — URL (forma di link_tag_scheme, «/uri») della pagina a cui porta davvero una risorsa: se la risorsa è un
 * contenitore con FirstChildRedirect, la sua prima figlia pubblicata per menuindex (come fa lo snippet), fino a 5 livelli.
 * Usato dalle briciole visibili (chunk breadcrumb-link) perché il livello intermedio non punti a un redirect (AA-8,
 * Screaming Frog 15/11: 41 pagine). La stessa regola è in aaSchema per il BreadcrumbList.
 * Uso: [[aaLinkArrivo? &id=`[[+id]]`]]
 */
use MODX\Revolution\modResource;
$id = (int) ($id ?? 0);
if ($id <= 0) return '';
for ($giri = 0; $giri < 5; $giri++) {
    $r = $modx->getObject(modResource::class, $id);
    if (!$r || stripos((string) $r->get('content'), 'FirstChildRedirect') === false) break;
    $c = $modx->newQuery(modResource::class);
    $c->where(['parent' => $id, 'published' => 1, 'deleted' => 0]);
    $c->sortby('menuindex', 'ASC');
    $c->limit(1);
    $figlia = $modx->getObject(modResource::class, $c);
    if (!$figlia) break;
    $id = (int) $figlia->get('id');
}
return $modx->makeUrl($id, '', '', $modx->getOption('link_tag_scheme', null, 'abs'));
