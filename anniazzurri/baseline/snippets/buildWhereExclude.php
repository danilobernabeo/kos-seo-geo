<?php
/**
 * buildWhereExclude  —  anniazzurri.it
 *
 * Costruisce la clausola &where JSON per pdoPage / getPage.
 * Esclude dal listing news:
 *   1. la risorsa correntemente visualizzata (nei box "ultime news")
 *   2. le risorse con TV soloStruttura = 1
 *
 * NB: versione semplificata. Su kosgroup.com esiste una variante
 *     con filtro Tagger aggiuntivo — le due NON sono allineate.
 *
 * Property opzionali:
 *   &field    colonna da filtrare           (default: modResource.id)
 *   &tvName   nome della TV flag            (default: soloStruttura)
 *   &tvValue  valore che marca l'esclusione (default: 1)
 *   &parents  CSV di ID: limita il calcolo al sottoalbero
 */

$field   = $modx->getOption('field',   $scriptProperties, 'modResource.id');
$tvName  = trim((string)$modx->getOption('tvName',  $scriptProperties, 'soloStruttura'));
$tvValue =      (string)$modx->getOption('tvValue', $scriptProperties, '1');
$parents = trim((string)$modx->getOption('parents', $scriptProperties, ''));

$prefix     = $modx->getOption('table_prefix');
$excludeIds = [];

// 1. Risorsa corrente — guardia contro 404 / contesti senza risorsa
if ($modx->resource instanceof modResource) {
    $excludeIds[] = (int)$modx->resource->get('id');
}

// 2. Scope opzionale sul sottoalbero
$scopeSql  = '';
$parentIds = array_filter(array_map('intval', explode(',', $parents)));
if (!empty($parentIds)) {
    $scopeSql = ' AND c.parent IN (' . implode(',', $parentIds) . ')';
}

// 3. Risorse marcate dalla TV — una query, una colonna
if ($tvName !== '') {
    $sql = "SELECT tvr.contentid
            FROM {$prefix}site_tmplvar_contentvalues tvr
            JOIN {$prefix}site_tmplvars tv ON tv.id = tvr.tmplvarid
            JOIN {$prefix}site_content    c ON c.id = tvr.contentid
            WHERE tv.name   = " . $modx->quote($tvName) . "
              AND tvr.value = " . $modx->quote($tvValue) . $scopeSql;

    $stmt = $modx->query($sql);
    if ($stmt instanceof PDOStatement) {
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN, 0) as $id) {
            $excludeIds[] = (int)$id;
        }
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, '[buildWhereExclude] query TV fallita');
    }
}

// NOT IN (0) = non esclude nulla, ma evita il NOT IN () invalido
$excludeIds = array_values(array_unique($excludeIds)) ?: [0];

return json_encode([$field . ':NOT IN' => $excludeIds]);