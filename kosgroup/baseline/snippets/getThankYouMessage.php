<?php
// Recupera l'ID della landing page
$landingId = isset($modx->placeholders['fi.landing_id']) ? intval($modx->placeholders['fi.landing_id']) : 0;

// Se abbiamo un ID valido, recupera il messaggio di ringraziamento
if ($landingId > 0) {
    $resource = $modx->getObject('modResource', $landingId);
    if ($resource) {
        return $resource->getTVValue('messaggioRingraziamento');
    }
}

// Messaggio predefinito se non è possibile recuperare quello personalizzato
return '<h3>Grazie per averci contattato!</h3><p>Un nostro operatore ti ricontatterà al più presto.</p>';