<?php
$ch = curl_init('https://app.tuotempo.com/api/v3/tt_kosgroup_portale/resources');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

$string = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_errno > 0) {
    echo "cURL Error ($curl_errno): $curl_error\n";
} else {
    $arr = json_decode($string, true);
    
    // Calcola il numero di professionisti
    $totalMedici = count($arr['return']['results']);
    
    // Container principale
    echo '<div class="box-dettaglio-content">';
    
    // Intestazione con conteggio
    echo '<div class="col box-dettaglio-txt">';
    echo '<h3>I nostri medici <small>(' . $totalMedici . ' professionisti)</small></h3>';
    echo '</div>';
    
    // Input per la ricerca
echo '<div class="col-12 d-flex align-items-center mb-4">
		<label for="mediciInput" class="me-2 mb-0">Cerca:</label>
		<input class="form-control mb-0" 
		type="text" id="mediciInput" 
		name="mediciInput"
		onkeyup="mediciFunction()" 
		placeholder="Cerca un medico, specializzazione o un centro" 
		title="Cerca un medico">
	</div>';
    
    // Container per la griglia dei medici
    echo '<div class="row" id="mediciGrid">';
    
    foreach($arr['return']['results'] as $key => $value){
        // Estrai l'areaTitle dal primo elemento dell'array areas se esiste
        $areaTitle = '';
        if (!empty($value['areas']) && is_array($value['areas']) && isset($value['areas'][0]['areaTitle'])) {
            $areaTitle = $value['areas'][0]['areaTitle'];
        }

        // Gestione dei campi vuoti e conversione nome in maiuscolo
        $name = !empty($value['resourceName']) ? mb_strtoupper($value['resourceName']) : '-';
        $notes = !empty($value['notes']) ? $value['notes'] : '-';
        $areaTitle = !empty($areaTitle) ? $areaTitle : '-';

        echo '<div class="col-12 col-md-6 equipe-persona searchable">';
        echo '<div class="equipe-info">';
        echo '<span class="equipe-ruolo">' . htmlspecialchars($name) . '</span>';
        echo ' - ';
        echo '<span class="equipe-ruolo">' . htmlspecialchars($areaTitle) . '</span>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>'; // Chiude row
    
    // Script per la ricerca
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
}