<?php
/**
 * DisplayResourceTags
 * 
 * Mostra i tag Tagger per una risorsa specifica
 * 
 * Parametri:
 * &resourceId - ID della risorsa (default: risorsa corrente)
 * &groupId - ID del gruppo Tagger (default: 1)
 * &showFallback - Mostra fallback se non ci sono tag (default: true)
 * &wrapperClass - Classe CSS per il wrapper (default: news-cats)
 * &tagClass - Classe CSS per i tag (default: tag)
 */
$resourceId = $modx->getOption('resourceId', $scriptProperties, $modx->resource->get('id'));
$groupId = $modx->getOption('groupId', $scriptProperties, 1);
$showFallback = $modx->getOption('showFallback', $scriptProperties, true);
$wrapperClass = $modx->getOption('wrapperClass', $scriptProperties, 'news-cats');
$tagClass = $modx->getOption('tagClass', $scriptProperties, 'tag');

$tags = '';

// Query per ottenere i tag
try {
    $sql = "SELECT t.tag 
            FROM modx_tagger_tag_resources tr 
            JOIN modx_tagger_tags t ON tr.tag = t.id 
            WHERE tr.resource = ? AND t.group = ?";
    
    $stmt = $modx->prepare($sql);
    $stmt->execute(array($resourceId, $groupId));
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($results)) {
        foreach ($results as $result) {
            $tags .= '<span class="' . $tagClass . '">' . htmlspecialchars($result) . '</span>';
        }
    }
    
} catch (Exception $e) {
    // In caso di errore, usa il fallback se abilitato
}

// Se non ci sono tag e il fallback è abilitato, usa la categoria "nonno" (parent del parent)
if (empty($tags) && $showFallback) {
    $resource = $modx->getObject('modResource', $resourceId);
    if ($resource) {
        $parentId = $resource->get('parent');
        $parent = $modx->getObject('modResource', $parentId);
        if ($parent) {
            $grandParentId = $parent->get('parent');
            $grandParent = $modx->getObject('modResource', $grandParentId);
            $tags = $grandParent ? '<span class="' . $tagClass . '">' . htmlspecialchars($grandParent->get('pagetitle')) . '</span>' : '<span class="' . $tagClass . '">' . htmlspecialchars($parent->get('pagetitle')) . '</span>';
        }
    }
}

// Se non ci sono tag, non mostrare nulla
if (empty($tags)) {
    return '';
}

// Wrapper HTML
$output = '<div class="' . $wrapperClass . '">' . $tags . '</div>';

return $output;