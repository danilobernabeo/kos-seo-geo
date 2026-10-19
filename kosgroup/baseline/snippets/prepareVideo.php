<?php
// Formatta la data dal campo videoData (se esiste) o publishedon
if (isset($row['videoData']) && !empty($row['videoData'])) {
    $timestamp = strtotime($row['videoData']);
    $row['formatted_date'] = $timestamp !== false ? date('d-m-Y', $timestamp) : '';
} elseif (isset($row['publishedon'])) {
    $row['formatted_date'] = date('d-m-Y', $row['publishedon']);
} else {
    $row['formatted_date'] = '';
}

// FIX: verifica che $row['id'] esista prima di procedere
if (empty($row['id'])) {
    $row['categories_tags'] = '';
    return $row;
}

$resourceId = (int)$row['id'];
$tags = '';

// Query per ottenere i tag Tagger
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
    $modx->log(modX::LOG_LEVEL_ERROR, '[Snippet #31] DB error: ' . $e->getMessage());
}

// Se non ci sono tag, usa la categoria "nonno" come fallback
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