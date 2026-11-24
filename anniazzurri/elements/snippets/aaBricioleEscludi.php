<?php
/**
 * aaBricioleEscludi — elenco aa.breadcrumb_exclude senza la pagina corrente (AA 8, 24/11).
 * Le pagine dell'elenco si saltano come livelli intermedi delle briciole, ma quando sono la pagina corrente si
 * mostrano (es. /residenze: «Anni Azzurri › Residenze»). Stessa regola di aaSchema per il BreadcrumbList.
 * Uso nel chunk breadcrumb-stripe, in cache: &exclude=`[[aaBricioleEscludi]]`
 */
$id = $modx->resource ? (int) $modx->resource->get('id') : 0;
$ids = array_filter(array_map('intval', explode(',', (string) $modx->getOption('aa.breadcrumb_exclude'))));
return implode(',', array_values(array_filter($ids, function ($x) use ($id) { return $x !== $id; })));
