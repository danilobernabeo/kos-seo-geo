<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

// Leggi la variabile di template JSON dalla risorsa corrente
$timeRangesTV = $modx->resource->getTVValue('callCenterTimeRanges');

// Configurazione predefinita utilizzata se non è stato trovato un JSON valido
$defaultConfig = [
    [1, 5, '08:00', '20:00'] // Lun-Ven 8-20
];

// Se la TV contiene un JSON valido, usalo come configurazione
$timeRanges = $defaultConfig;
if (!empty($timeRangesTV)) {
    try {
        $parsedRanges = json_decode($timeRangesTV, true);
        if (is_array($parsedRanges) && !empty($parsedRanges)) {
            $timeRanges = $parsedRanges;
        }
    } catch (Exception $e) {
        // Gestione dell'errore silente - usiamo il default se il JSON non è valido
    }
}

// Blocco per il pulsante "CHIAMACI"
$chiamaciBlock = '
<div class="header-btn-container">
    <div class="header-btn">
        <a id="[[*telPrestazione]]" class="btn btn-primary" href="tel:[[*telPrestazione]]" title="Chiamaci">CHIAMACI</a>
    </div>
</div>';

// Blocco per il pulsante "CONTATTACI"
$contattaciBlock = '
<div class="header-btn-container">
    <div class="header-btn">
        <a class="btn btn-primary" 
           href="#contatti" 
           onclick="scrollToAnchor(event, \'contatti\')" 
           title="Contattaci">CONTATTACI</a>
    </div>
</div>';

// Verifica se il giorno e l'ora attuali rientrano negli orari configurati
$isAvailable = false;
foreach ($timeRanges as $range) {
    // Assicurati che l'array abbia almeno 4 elementi
    if (count($range) >= 4) {
        list($startDay, $endDay, $startTime, $endTime) = $range;
        
        // Verifica se il giorno corrente è nell'intervallo
        if ($dayofweek >= $startDay && $dayofweek <= $endDay) {
            // Verifica se l'ora corrente è nell'intervallo
            if ($now >= $startTime && $now <= $endTime) {
                $isAvailable = true;
                break;
            }
        }
    }
}

// Mostra il blocco appropriato
if ($isAvailable) {
    echo $chiamaciBlock;
} else {
    echo $contattaciBlock;
}