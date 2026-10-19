<?php
// Percorso del file CSV relativo alla root di MODX
$csvPath = MODX_BASE_PATH . 'assets/csv/lista-convenzioni.csv';

// Array per mappare i nomi delle colonne
$areeColumns = [
    'Ambulatori - Medicina Specialistica',
    'Riabilitazione',
    'Odontoiatria',
    'Ricovero - Interventi',
    'Psichiatria'
];

$formeColumns = [
    'Forma Diretta',
    'Forma Indiretta'
];

$output = '';

// Inizia il container principale
$output .= '<div class="box-dettaglio-content">';

// Conta il numero totale di convenzioni
$totalConvenzioni = 0;
if (($handle = fopen($csvPath, "r")) !== FALSE) {
    // Salta la riga di intestazione
    fgetcsv($handle, 0, ";");
    // Conta le righe non vuote
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        if (!empty($data[0])) {
            $totalConvenzioni++;
        }
    }
    fclose($handle);
}

// Aggiungi il titolo con il conteggio
$output .= '<div class="col box-dettaglio-txt">';
$output .= '<h3>Le convenzioni <small>(' . $totalConvenzioni . ' attive)</small></h3>';
$output .= '</div>';

// Aggiungi l'input per la ricerca
$output .= '<input class="form-control mb-4" type="text" id="searchInput" 
            onkeyup="searchFunction()" 
            placeholder="Cerca un ente, area medica o forma" 
            title="Cerca">';

// Inizia il container per la griglia
$output .= '<div class="row" id="searchGrid">';

// Riapri il file per leggere i dati
if (($handle = fopen($csvPath, "r")) !== FALSE) {
    // Salta la prima riga (intestazioni)
    $headers = fgetcsv($handle, 0, ";");
    
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        if (empty($data[0])) continue; // Salta righe vuote
        
        $ente = $data[0];
        
        // Raccogli le aree attive
        $aree = [];
        for ($i = 1; $i <= 5; $i++) {
            if (isset($data[$i]) && strtolower(trim($data[$i])) === 'x') {
                $aree[] = $areeColumns[$i-1];
            }
        }
        
        // Raccogli le forme attive
        $forme = [];
        for ($i = 6; $i <= 7; $i++) {
            if (isset($data[$i]) && strtolower(trim($data[$i])) === 'x') {
                $forme[] = $formeColumns[$i-6];
            }
        }
        
        // Genera l'HTML per questo elemento
        $output .= '<div class="col-12 col-md-6 equipe-persona searchable">
    <img class="equipe-foto" src="/assets/img/icona-convenzioni.svg" alt="' . htmlspecialchars($ente) . '">
    <div class="equipe-info">
        <span class="equipe-ruolo">' . htmlspecialchars($ente) . '</span>
        <span class="equipe-nome">' . htmlspecialchars(implode(', ', $aree)) . '</span>
        <span class="equipe-ruolo">' . htmlspecialchars(implode(', ', $forme)) . '</span>
    </div>
</div>' . PHP_EOL;
    }
    fclose($handle);
}

// Chiudi i container
$output .= '</div>'; // Chiude row

// Aggiungi lo script per la ricerca
$output .= '<script>
function searchFunction() {
    var input = document.getElementById("searchInput");
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

$output .= '</div>'; // Chiude box-dettaglio-content

return $output;