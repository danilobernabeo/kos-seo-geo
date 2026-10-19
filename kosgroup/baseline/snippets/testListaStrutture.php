<?php
$debug = [];
$debug[] = "Inizio debug";

// ID della risorsa corrente
$currentResourceId = $modx->resource->get('id');
$debug[] = "ID risorsa corrente: " . $currentResourceId;

// Verifica esistenza TV
$tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => 'mapServiziStrutture']);
if (!$tv) {
    $debug[] = "TV non trovata";
    return implode("<br>", $debug);
}
$debug[] = "TV trovata con ID: " . $tv->get('id');

// Verifica valore TV
$tvValue = $tv->getValue($currentResourceId);
$debug[] = "Valore TV: " . ($tvValue ? $tvValue : "vuoto");

if (empty($tvValue)) {
    $debug[] = "TV vuota per questa risorsa";
    return implode("<br>", $debug);
}

// Verifica se la TV è associata al template
$template = $modx->resource->get('template');
$debug[] = "Template della risorsa: " . $template;

$templateVarTemplate = $modx->getObject(\MODX\Revolution\modTemplateVarTemplate::class, [
    'tmplvarid' => $tv->get('id'),
    'templateid' => $template
]);

if (!$templateVarTemplate) {
    $debug[] = "La TV non è associata a questo template";
    return implode("<br>", $debug);
}
$debug[] = "TV correttamente associata al template";

// Prova a fare un print_r del valore della TV
$debug[] = "Dump del valore TV: " . print_r($tvValue, true);

return implode("<br>", $debug);