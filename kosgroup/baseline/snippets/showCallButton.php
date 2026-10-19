<?php
/**
 * Snippet: showCallButton
 * Mostra un bottone "Chiama ora" solo negli orari specificati
 * 
 * Parametri:
 * &phoneNumber  - Numero di telefono (obbligatorio)
 * &timeRanges   - Fasce orarie in formato CSV: "startDay,endDay,startTime,endTime"
 *                 es: 1,5,08:30,18:00  (Lun-Ven dalle 08:30 alle 18:00)
 * &buttonText   - Testo del bottone (default: "Chiama ora")
 * &buttonClass  - Classi CSS aggiuntive (default: "btn-primary")
 */

date_default_timezone_set('CET');
$dayofweek = date('w');
$now       = date('H:i');

// Ottieni parametri
$phoneNumber    = $modx->getOption('phoneNumber',  $scriptProperties, '');
$timeRangesRaw  = $modx->getOption('timeRanges',   $scriptProperties, '');
$buttonText     = $modx->getOption('buttonText',   $scriptProperties, 'Chiama ora');
$buttonClass    = $modx->getOption('buttonClass',  $scriptProperties, 'btn-primary');

// Se non c'è numero di telefono, non mostrare nulla
if (empty($phoneNumber)) {
    return '';
}

// Parse time ranges — supporta sia JSON che formato CSV semplice
$timeRanges = null;

if (!empty($timeRangesRaw)) {
    // Prova prima il JSON standard (es. [[1,5,"08:30","18:00"]])
    $decoded = json_decode($timeRangesRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $timeRanges = $decoded;
    } else {
        // Fallback formato CSV: "startDay,endDay,startTime,endTime"
        // es: 1,5,08:30,18:00
        $parts = array_map('trim', explode(',', $timeRangesRaw));
        if (count($parts) === 4) {
            $timeRanges = [[
                (int)$parts[0],
                (int)$parts[1],
                $parts[2],
                $parts[3],
            ]];
        }
    }
}

// Configurazione predefinita se nessun parametro valido: Lun-Ven 08:00-20:00
if (empty($timeRanges) || !is_array($timeRanges)) {
    $timeRanges = [[1, 5, '08:00', '20:00']];
}

// Verifica se il momento attuale rientra in una delle fasce orarie
$isAvailable = false;
foreach ($timeRanges as $range) {
    if (count($range) >= 4) {
        list($startDay, $endDay, $startTime, $endTime) = $range;
        if ($dayofweek >= $startDay && $dayofweek <= $endDay) {
            if ($now >= $startTime && $now <= $endTime) {
                $isAvailable = true;
                break;
            }
        }
    }
}

// Se fuori orario non mostrare nulla
if (!$isAvailable) {
    return '';
}

// Output bottone
return '
<a href="tel:' . htmlspecialchars($phoneNumber) . '"
   class="btn ' . htmlspecialchars($buttonClass) . ' btn-call-now"
   title="' . htmlspecialchars($buttonText) . '">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
         class="bi bi-telephone-fill xme-2" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315
        1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75
        1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846
        1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
    </svg>
    ' . htmlspecialchars($buttonText) . '
</a>';