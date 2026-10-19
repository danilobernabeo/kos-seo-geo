<?php
// Crea uno snippet chiamato 'CustomStructures'
$output = '';

// Ottieni le risorse di primo livello (figlie di id 4)
$level1 = $modx->getCollection('modResource', array(
    'parent' => 4,
    'published' => 1,
    'deleted' => 0
));

foreach ($level1 as $resource) {
    // Determina quale titolo usare per il primo livello
    $title = $resource->get('menutitle') ? $resource->get('menutitle') : $resource->get('pagetitle');
    
    // Aggiungi il titolo di primo livello
    $output .= '
    <div class="col box-dettaglio-txt col-12 mt-3">
        <h3><a href="' . $resource->get('uri') . '">' . $title . '</a></h3>
    </div>';
    
    // Cerca le strutture correlate (figlie di id 167)
    $children = $modx->getCollection('modResource', array(
        'parent' => 167,
        'published' => 1,
        'deleted' => 0
    ));
    
    $matchingChildren = array();
    foreach ($children as $child) {
        // Ottieni il valore della TV tipo_struttura per questa risorsa
        $tv = $child->getTVValue('tipo_struttura');
        
        // Controlla se il valore della TV contiene il nome della risorsa di primo livello
        // Assumiamo che tipo_struttura sia una lista multipla separata da ||
        $values = explode('||', $tv);
        if (in_array($title, $values)) {
            $matchingChildren[] = $child;
        }
    }
    
    // Se ci sono strutture correlate, aggiungile alla lista
    if (!empty($matchingChildren)) {
        $output .= '
        <div class="box-dettaglio-content mt-3 pb-5">
            <ul class="lista-con-icone lista-icone-check">';
        
        foreach ($matchingChildren as $child) {
            // Determina quale titolo usare per il figlio
            $childTitle = $child->get('menutitle') ? $child->get('menutitle') : $child->get('pagetitle');
            
            $output .= '
                <li><a href="' . $child->get('uri') . '">' . $childTitle . '</a></li>';
        }
        
        $output .= '
            </ul>
        </div>';
    }
}

return $output;