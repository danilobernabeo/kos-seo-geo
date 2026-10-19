<?php
$tipo = $modx->getOption('tipo', $scriptProperties, '');
if (empty($tipo)) return '';

// Preparare il valore per la ricerca regexp
$tipo = preg_quote($tipo); // escaping dei caratteri speciali
$searchPattern = "(^|\\|\\|)" . $tipo . "(\\|\\||$)";

return $modx->runSnippet('pdoMenu', array(
    'parents' => 167,
    'level' => 1,
    '&includeTVs'  => `indirizzo_struttura`,
    'tplOuter' => 'servizi-centri-outer',
    'tpl' => 'servizi-centri-row',
    'where' => '{"TVValue.value:REGEXP":"' . $searchPattern . '"}',
    'leftJoin' => '{
        "TVValue": {
            "class": "modTemplateVarResource",
            "alias": "TVValue",
            "on": "modResource.id = TVValue.contentid AND TVValue.tmplvarid = (SELECT id FROM modx_site_tmplvars WHERE name = \'tipo_struttura\')"
        }
    }',
    'sortby' => 'menuindex',
    'sortdir' => 'ASC',
    'showHidden' => 0,
    'showUnpublished' => 0
));