<?php
$currentPageId = $modx->resource->get('id');

// Schema dei link uguale a [[~id]] (impostazione link_tag_scheme, oggi "abs": /it/...)
$scheme = $modx->getOption('link_tag_scheme');

// Ottieni le risorse figlie dell'ID 280 che hanno idSedeLavoro uguale al currentPageId
$resources = $modx->getCollection('modResource', array(
    'parent' => 280,
    'published' => 1,
    'deleted' => 0
));

// Lista dei prefissi comuni nei cognomi italiani
$prefissiCognomi = array('de', 'del', 'della', 'dello', 'dei', 'degli', 'delle', 'da', 'dal', 'dalla', 'dallo', 'dai', 'dagli', 'dalle', 'di', 'lo', 'la', 'li', 'san', 'santa', 'santo');

// Funzione per estrarre correttamente il cognome
// (protetta: anche getMedici la definisce, e due definizioni nella stessa pagina darebbero errore)
if (!function_exists('estraiCognome')) {
function estraiCognome($nome, $prefissiCognomi) {
    $parole = explode(' ', strtolower($nome));
    $numParole = count($parole);
    
    // Se c'è solo una parola, è il cognome
    if ($numParole <= 1) {
        return $nome;
    }
    
    // Controlliamo se la penultima parola è un prefisso
    $indicePenultima = $numParole - 2;
    if ($indicePenultima >= 0 && in_array($parole[$indicePenultima], $prefissiCognomi)) {
        // Se la penultima parola è un prefisso, il cognome inizia da lì
        return $parole[$indicePenultima] . ' ' . $parole[$numParole - 1];
    }
    
    // Altrimenti prendiamo solo l'ultima parola
    return $parole[$numParole - 1];
}
}

// Converti la collection in array per poterla ordinare
$resourceArray = array();
foreach($resources as $resource) {
    // Verifica che la risorsa sia associata alla sede corrente
    $idSedeLavoro = $resource->getTVValue('idSedeLavoro');
    
    // Gestione di ID multipli separati da virgola
    $sediLavoro = explode(',', $idSedeLavoro);
    $sediLavoro = array_map('trim', $sediLavoro); // Rimuovi spazi bianchi
    
    // Verifica se l'ID della pagina corrente è tra le sedi di lavoro del medico
    if(!in_array($currentPageId, $sediLavoro)) {
        continue; // Salta questa risorsa se non appartiene alla sede corrente
    }
    
    $nomeCompleto = $resource->get('pagetitle');
    $cognome = estraiCognome($nomeCompleto, $prefissiCognomi);
    
    $resourceArray[] = array(
        'resource' => $resource,
        'cognome' => $cognome,
        'nomeCompleto' => $nomeCompleto
    );
}

// Ordina l'array per cognome
usort($resourceArray, function($a, $b) {
    return strcasecmp($a['cognome'], $b['cognome']);
});

// Costruisci l'output
$output = '';
foreach($resourceArray as $item) {
    $resource = $item['resource'];
    
    // Ottieni i valori delle TV
    $ruolo = $resource->getTVValue('ruolo');
    $infoImage = $resource->getTVValue('infoImage');
    
    // Usa l'immagine predefinita se infoImage è vuota; percorso sempre assoluto (/assets/...)
    $imageSrc = !empty($infoImage) ? '/' . ltrim($infoImage, '/') : '/assets/img/medico-thumb.svg';
    $pagetitle = $resource->get('pagetitle');
    $link = $modx->makeUrl($resource->get('id'), '', '', $scheme);
    
    $output .= '
    <div class="col-12 col-md-6 equipe-persona">
        <a href="' . $link . '" title="' . htmlspecialchars($pagetitle) . '">
            <img class="equipe-foto" src="' . $imageSrc . '" alt="' . htmlspecialchars($pagetitle) . '">
        </a>
        <div class="equipe-info">
            <span class="equipe-ruolo">
                <a href="' . $link . '" title="' . htmlspecialchars($pagetitle) . '">' . htmlspecialchars($pagetitle) . '</a>
            </span>
            <span class="equipe-nome">' . htmlspecialchars($ruolo) . '</span>
        </div>                                       
    </div>';
}

return $output;