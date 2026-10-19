<?php
// Ottieni le risorse figlie dell'ID 280
$resources = $modx->getCollection('modResource', array(
    'parent' => 280,
    'published' => 1,
    'deleted' => 0
));

// Lista dei prefissi comuni nei cognomi italiani
$prefissiCognomi = array('de', 'del', 'della', 'dello', 'dei', 'degli', 'delle', 'da', 'dal', 'dalla', 'dallo', 'dai', 'dagli', 'dalle', 'di', 'lo', 'la', 'li', 'san', 'santa', 'santo');

// Funzione per estrarre correttamente il cognome
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

// Converti la collection in array per poterla ordinare
$resourceArray = array();
foreach($resources as $resource) {
    $nomeCompleto = $resource->get('pagetitle');
    $cognome = estraiCognome($nomeCompleto, $prefissiCognomi);
    
    $resourceArray[] = array(
        'resource' => $resource,
        'cognome' => $cognome
    );
}

// Ordina l'array per cognome
usort($resourceArray, function($a, $b) {
    return strcasecmp($a['cognome'], $b['cognome']);
});

// Calcola il numero totale di professionisti
$totalMedici = count($resourceArray);

// Container principale
echo '<div class="box-dettaglio-content">';
echo '<div class="col box-dettaglio-txt">';
echo '<h3>I nostri medici <small>(' . $totalMedici . ' professionisti)</small></h3>';
echo '</div>';
echo '<div class="col-12 d-flex align-items-center mb-4">
		<label for="mediciInput" class="me-2 mb-0">Cerca:</label>
		<input class="form-control mb-0" 
		type="text" id="mediciInput" 
		name="mediciInput"
		onkeyup="mediciFunction()" 
		placeholder="Cerca un medico, specializzazione o un centro" 
		title="Cerca un medico">
	</div>';
echo '<div class="row" id="mediciGrid">';

foreach($resourceArray as $item) {
    $resource = $item['resource'];
    
    // Ottieni i valori delle TV
    $ruolo = $resource->getTVValue('ruolo');
    $idSedeLavoro = $resource->getTVValue('idSedeLavoro');
    
    // Gestione dei campi vuoti e conversione nome in maiuscolo
    $name = !empty($resource->get('pagetitle')) ? mb_strtoupper($resource->get('pagetitle')) : '-';
    $notes = !empty($ruolo) ? $ruolo : '-';
    
    echo '<div class="col-12 col-md-6 equipe-persona searchable">';
    echo '<img class="equipe-foto" src="/assets/img/medico-thumb.svg" alt="' . htmlspecialchars($name) . '">';
    echo '<div class="equipe-info">';
    echo '<span class="equipe-ruolo"><a href="' . $modx->makeUrl($resource->get('id')) . '">' . htmlspecialchars($name) . '</a></span>';
    echo '<span class="equipe-nome">' . htmlspecialchars($notes) . '</span>';
    
    // Gestione sedi di lavoro multiple
    if(!empty($idSedeLavoro)) {
        // Divide la stringa degli ID in un array
        $sediIds = explode(',', $idSedeLavoro);
        $sediIds = array_map('trim', $sediIds);
        
        foreach($sediIds as $sedeId) {
            // Ottieni la risorsa della struttura
            $sedeResource = $modx->getObject('modResource', $sedeId);
            if($sedeResource) {
                // Verifica se la struttura è figlia della risorsa con ID 167
                $parent = $sedeResource->get('parent');
                if($parent == 167 || $sedeResource->get('id') == 167) {
                    $sedeName = $sedeResource->get('pagetitle');
                    echo '<span class="equipe-ruolo mb-1"><a href="' . $modx->makeUrl($sedeId) . '">' . htmlspecialchars($sedeName) . '</a></span>';
                }
            }
        }
    }
    
    echo '</div>';
    echo '</div>';
}

echo '</div>'; // Chiude row

// Script per la ricerca (invariato)
echo '<script>
function mediciFunction() {
    var input = document.getElementById("mediciInput");
    var filter = input.value.toUpperCase();
    var items = document.getElementsByClassName("equipe-persona");
    
    for (var i = 0; i < items.length; i++) {
        var info = items[i].getElementsByClassName("equipe-info")[0];
        var txtValue = info.textContent || info.innerText;
        
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}
</script>';
echo '</div>'; // Chiude box-dettaglio-content