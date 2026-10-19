<?php
$resources = $modx->getCollection('modResource', array('parent' => 286));
$output = array();

foreach ($resources as $resource) {
    $output[] = array(
        'id' => $resource->get('id'),
        'titolo' => $resource->get('pagetitle'),
        'contenuto' => $resource->get('content'),
        'lunghezza_testo' => strlen($resource->get('content')),
        'data_creazione' => strtotime($resource->get('createdon'))
    );
}

$json = json_encode($output, JSON_PRETTY_PRINT);
file_put_contents('assets/exports/approfondimenti.json', $json);