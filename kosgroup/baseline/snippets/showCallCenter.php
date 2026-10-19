<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

// Ottieni l'ID della risorsa corrente
$resourceId = $modx->resource->get('id');

// Ottieni le fasce orarie dalla TV della risorsa corrente (formato JSON)
$timeRangesTV = $modx->resource->getTVValue('callCenterTimeRanges');
$timeRanges = !empty($timeRangesTV) ? json_decode($timeRangesTV, true) : null;

// Configurazione predefinita se non è specificata nella TV
if (empty($timeRanges) || !is_array($timeRanges)) {
    $timeRanges = [
        [1, 5, '08:00', '20:00'] // Default: Lun-Ven 8-20
    ];
}

// Verifica se il giorno e l'ora correnti rientrano nelle fasce orarie
$isAvailable = false;
foreach ($timeRanges as $range) {
    // Controlla che l'array abbia il formato corretto
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

// Se il call center è disponibile, mostra il contenuto personalizzato
if ($isAvailable) {
    // Ottieni il contenuto personalizzato dalla TV
    $customContent = $modx->resource->getTVValue('callCenterContent');
    
    // Se non c'è contenuto personalizzato, usa un blocco predefinito
    if (empty($customContent)) {
        return '
        <div class="call-center-block">
            <div class="header-btn-container">
                <div class="header-btn">
                    <a id="[[*telPrestazione]]" class="btn btn-primary" href="tel:[[*telPrestazione]]" title="Chiamaci">CHIAMACI</a>
                </div>
            </div>
        </div>';
    } else {
        // Altrimenti restituisci il contenuto personalizzato
        return $customContent;
    }
}

// Se il call center non è disponibile, non mostrare nulla
return '';