<?php
// Formatta la data (logica esistente)
if (isset($row['publishedon'])) {
    $row['formatted_date'] = date('d-m-Y', $row['publishedon']);
} else {
    $row['formatted_date'] = '';
}

// FIX: verifica che $row['id'] esista prima di procedere
if (empty($row['id'])) {
    $row['categories_tags'] = '';
    return $row;
}

$resourceId = (int)$row['id']; // cast a int per sicurezza
$tags = '';

try {
    $sql = "SELECT t.tag 
            FROM modx_tagger_tag_resources tr 
            JOIN modx_tagger_tags t ON tr.tag = t.id 
            WHERE tr.resource = ? AND t.group = 1";
    
    $stmt = $modx->prepare($sql);
    $stmt->execute(array($resourceId));
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($results)) {
        $cleanResults = array();
        foreach ($results as $result) {
            $cleanResults[] = htmlspecialchars($result);
        }
        $tags = '<span class="tag">' . implode(', ', $cleanResults) . '</span>';
    }
    
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[Snippet #27] DB error: ' . $e->getMessage());
}

// Fallback al "nonno" se nessun tag trovato
if (empty($tags)) {
    $resource = $modx->getObject('modResource', $resourceId);
    if ($resource) {
        $parentId = $resource->get('parent');
        $parent = $modx->getObject('modResource', $parentId);
        if ($parent) {
            $grandParentId = $parent->get('parent');
            $grandParent = $modx->getObject('modResource', $grandParentId);
            $tags = $grandParent
                ? '<span class="tag">' . htmlspecialchars($grandParent->get('pagetitle')) . '</span>'
                : '<span class="tag">' . htmlspecialchars($parent->get('pagetitle')) . '</span>';
        }
    }
}

$row['categories_tags'] = $tags;
return $row;