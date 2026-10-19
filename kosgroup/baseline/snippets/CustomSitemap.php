<?php
// Crea uno snippet chiamato 'CustomSitemap'
$parentId = $modx->getOption('parent', $scriptProperties, 3);
$output = '';

// Ottieni le risorse di primo livello
$level1 = $modx->getCollection('modResource', array(
    'parent' => $parentId,
    'published' => 1,
    'deleted' => 0
));

foreach ($level1 as $resource) {
    // Aggiungi il titolo di primo livello
    $output .= '
    <div class="col box-dettaglio-txt col-12 mt-3">
        <h3><a href="' . $resource->get('uri') . '">' . $resource->get('pagetitle') . '</a></h3>
    </div>';
    
    // Cerca i figli di questa risorsa
    $children = $modx->getCollection('modResource', array(
        'parent' => $resource->get('id'),
        'published' => 1,
        'deleted' => 0
    ));
    
    // Se ci sono figli, aggiungili alla lista
    if (count($children) > 0) {
        $output .= '
        <div class="box-dettaglio-content mt-3 pb-5">
            <ul class="lista-con-icone lista-icone-check">';
        
        foreach ($children as $child) {
            $output .= '
                <li><a href="' . $child->get('uri') . '">' . $child->get('pagetitle') . '</a></li>';
        }
        
        $output .= '
            </ul>
        </div>';
    }
}

return $output;