<?php
date_default_timezone_set('Europe/Rome');
$today = date('d-m');

$closedDays = [
    '24-12',
    '25-12'
];

$contactBtn = '<div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';

if (in_array($today, $closedDays)) {
    return $contactBtn;
}

// Prende l'alias e sostituisce 'lrsa' con 'landing'
$alias = $modx->resource->get('alias');
$snippetAlias = str_replace('lrsa', 'landing', $alias);
$snippetName = 'switchCta-contactbox-' . $snippetAlias;

// Cattura l'output dell'echo
ob_start();
$result = $modx->runSnippet($snippetName);
$buffered = ob_get_clean();

if (!empty($result)) {
    return $result;
}

if (!empty($buffered)) {
    return $buffered;
}

return $contactBtn;