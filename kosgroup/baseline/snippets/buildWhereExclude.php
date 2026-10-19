<?php
$excludeIds = [(int)$modx->resource->get('id')];

// Escludi risorse con soloStruttura = 1
$tv = $modx->getObject('modTemplateVar', ['name' => 'soloStruttura']);
if ($tv) {
    $c = $modx->newQuery('modTemplateVarResource');
    $c->where(['tmplvarid' => $tv->get('id'), 'value' => '1']);
    foreach ($modx->getCollection('modTemplateVarResource', $c) as $tvRes) {
        $excludeIds[] = (int)$tvRes->get('contentid');
    }
}
$excludeIds = array_unique($excludeIds);

// --- Filtro per tag Tagger (opzionale) via SQL diretto ---
$tagName = $modx->getOption('tagName', $scriptProperties, '');

if (!empty($tagName)) {
    $prefix = $modx->getOption('table_prefix'); // es. "modx_"

    // Trova l'ID del tag per nome
    $stmt = $modx->query("
        SELECT id FROM {$prefix}tagger_tags
        WHERE tag = " . $modx->quote($tagName) . "
        LIMIT 1
    ");

    if (!$stmt || !($tagRow = $stmt->fetch(PDO::FETCH_ASSOC))) {
        return '{"id:IN":[0]}'; // tag non trovato
    }

    $tagId = (int)$tagRow['id'];

    // Trova tutte le risorse associate a quel tag
    $stmt2 = $modx->query("
        SELECT resource FROM {$prefix}tagger_tag_resources
        WHERE tag = {$tagId}
    ");

    $includeIds = [];
    if ($stmt2) {
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $rid = (int)$row['resource'];
            if (!in_array($rid, $excludeIds)) {
                $includeIds[] = $rid;
            }
        }
    }

    if (empty($includeIds)) {
        return '{"id:IN":[0]}';
    }

    return json_encode(['id:IN' => $includeIds]);
}

// Comportamento default (nessun tagName)
return '{"id:NOT IN":[' . implode(',', $excludeIds) . ']}';