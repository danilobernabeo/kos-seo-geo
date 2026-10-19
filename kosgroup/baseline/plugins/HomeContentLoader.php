<?php
$eventName = $modx->event->name;

if ($eventName == 'OnLoadWebDocument') {
    // Se siamo sulla risorsa it (253)
    if ($modx->resource->get('id') == 253) {
        $home = $modx->getObject('modResource', 1);
        if ($home) {
            $modx->resource->set('content', $home->get('content'));
            $modx->resource->set('template', $home->get('template'));

            // Opzionalmente, copia anche altri campi se necessario
            $modx->resource->set('pagetitle', $home->get('pagetitle'));
            $modx->resource->set('longtitle', $home->get('longtitle'));
            $modx->resource->set('description', $home->get('description'));
        }
    }
} elseif ($eventName == 'OnPageNotFound') {
    // Redirect dalla root a /it/
    if ($_SERVER['REQUEST_URI'] == '/') {
        $modx->sendRedirect('/it/', array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
    }
}