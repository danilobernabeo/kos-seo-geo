<?php
if ($modx->event->name == 'OnBeforeDocFormSave') {
    $alias = $resource->get('alias');
    if (!empty($alias)) {
        // Rimuovi accenti
        $alias = transliterator_transliterate('Any-Latin; Latin-ASCII', $alias);
        // Converti in minuscolo
        $alias = strtolower($alias);
        // Rimuovi caratteri speciali
        $alias = preg_replace('/[^a-z0-9_\-]/', '-', $alias);
        // Rimuovi trattini multipli
        $alias = preg_replace('/-+/', '-', $alias);
        // Rimuovi trattini iniziali e finali
        $alias = trim($alias, '-');
        
        $resource->set('alias', $alias);
    }
}