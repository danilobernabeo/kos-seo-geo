<?php
// Crea questo snippet come 'getMenuChunk'
$resourceId = $modx->resource->get('id');

$menuMap = [
    // IDs per menu-landing-sant-alessandro
    '817' => 'menu-landing-sant-alessandro',
    '819' => 'menu-landing-sant-alessandro',
    '820' => 'menu-landing-sant-alessandro',
    '925' => 'menu-landing-sant-alessandro',
    '926' => 'menu-landing-sant-alessandro',
    '807' => 'menu-landing-recruiting',
    '967' => 'menu-landing-san-pancrazio',
    '1065' => 'menu-landing-centro-cardinal-ferrari',
    '1157' => 'menu-landing-recruiting',
    // '823' => 'menu-landing-secondo-gruppo',
    // Aggiungi altri mapping secondo necessità
];

// Determina quale chunk utilizzare
$chunkName = isset($menuMap[$resourceId]) ? $menuMap[$resourceId] : 'menu-landing';

// Restituisci il chunk
return $modx->getChunk($chunkName);