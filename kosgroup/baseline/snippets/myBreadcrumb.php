<?php
$crumbs = [];
$currentResourceId = $modx->resource->get('id');
$resource = $modx->resource;

// Schema dei link uguale a [[~id]] (impostazione link_tag_scheme, oggi "abs": /it/...)
$scheme = $modx->getOption('link_tag_scheme');

// Calcola dinamicamente l'ID della risorsa radice da escludere
// Risale tutto l'albero per trovare la risorsa di primo livello (it o en)
$excludeResourceId = null;
$tempResource = $modx->resource;
$topLevelResource = null;

while ($tempResource) {
    $parentId = $tempResource->get('parent');
    // Se il parent è 0 (root) o 1 (home), questa è la risorsa da escludere
    if ($parentId == 0 || $parentId == 1) {
        $topLevelResource = $tempResource->get('id');
    }
    $tempResource = $tempResource->getOne('Parent');
}

$excludeResourceId = $topLevelResource;

// Costruisce i breadcrumb
$resource = $modx->resource;
while ($resource) {
    // Ottieni le proprietà della risorsa
    $resourceId = $resource->get('id');
    $hidemenu = $resource->get('hidemenu');
    $pagetitle = $resource->get('pagetitle');
    $link = $modx->makeUrl($resourceId, '', '', $scheme);

    // Salta questa iterazione se l'ID è quello da escludere
    if ($resourceId == $excludeResourceId) {
        $resource = $resource->getOne('Parent');
        continue;
    }

    // Verifica se la risorsa è quella corrente
    $isCurrent = ($resourceId == $currentResourceId);

    // Costruisce il breadcrumb: titolo senza link se nascosto, con link altrimenti
    if ($hidemenu) {
        $crumbs[] = '<span>' . $pagetitle . '</span>';
    } else {
        // Aggiungi una classe solo se è la risorsa corrente
        $class = $isCurrent ? 'class="bc-attivo"' : '';
        $crumbs[] = '<a href="' . $link . '" ' . $class . '>' . $pagetitle . '</a>';
    }

    // Vai alla risorsa genitore
    $resource = $resource->getOne('Parent');
}

// Inverti l'array per visualizzare i breadcrumb nell'ordine giusto
$crumbs = array_reverse($crumbs);

// Aggiungi separatore e contenitore
return '<div class="col" id="breadcrumbs">' . implode('<span>|</span>', $crumbs) . '</div>';