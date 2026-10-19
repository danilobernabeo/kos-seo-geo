<?php
// Recupera la TV in modo sicuro
$tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => 'mapServiziStrutture']);

if (!$tv) {
    return '<!-- ERRORE: TV mapServiziStrutture non trovata -->';
}

$tvValue = trim($tv->getValue($modx->resource->get('id')));

if (empty($tvValue)) {
    return '<div class="row"><div class="col-12"><p>Nessuna struttura trovata</p></div></div>';
}

$params = [
    'resources'   => $tvValue,
    'tpl'         => '@INLINE 
        <div class="col-12 col-md-6 col-lg-4 struttura-card">
            <a href="[[~[[+id]]]]" title="[[+pagetitle]]" class="struttura-container">
                <div class="struttura-img">
                    <img src="/assets/img/strutture/[[+alias]].jpg" alt="[[+pagetitle]]">
                </div>
                <div class="struttura-content mb-3 mb-md-4">
                    <h4>[[+pagetitle]]</h4>
                    <p>[[+indirizzo_struttura]]</p>
                </div>
            </a>
        </div>
    ',
    'includeTVs'  => 'indirizzo_struttura',
    'processTVs'  => '1',
    'tvPrefix'    => '',
    'sortby'      => 'pagetitle',
    'sortdir'     => 'ASC',
    'limit'       => '0',
    'showHidden'  => '0'
];

$response = $modx->runSnippet('pdoResources', $params);

$output  = '<div class="row">';
$output .= !empty($response) 
    ? $response 
    : '<div class="col-12"><p>Nessuna struttura trovata</p></div>';
$output .= '</div>';

return $output;