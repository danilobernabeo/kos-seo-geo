<?php
// Ottieni il base_path di MODX
$base_path = $modx->getOption('base_path');

// Ottieni tutte le risorse figlie
$resources = $modx->getCollection('modResource', array(
    'parent' => 280
));

foreach($resources as $resource) {
    $alias = $resource->get('alias');
    $image_path = $base_path . 'assets/img/medici/' . $alias . '.jpg';
    
    // Verifica se il file esiste
    if(file_exists($image_path)) {
        // Ottieni la TV
        $tv = $modx->getObject('modTemplateVar', array('name' => 'infoImage'));
        if($tv) {
            // Imposta il valore della TV
            $tv->setValue($resource->get('id'), '/assets/img/medici/' . $alias . '.jpg');
            $tv->save();
            echo "Immagine trovata e TV aggiornata per: " . $resource->get('pagetitle') . "<br>";
        }
    } else {
        echo "Immagine non trovata per: " . $resource->get('pagetitle') . " (" . $image_path . ")<br>";
    }
}

return 'Processo completato';