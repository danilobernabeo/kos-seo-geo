<?php
// Recupera il valore della TV (stringa separata da ||)
$tvValue = $modx->resource->getTVValue('tipo_struttura');

// Usa explode per separare i valori su '||'
$tvArray = explode('||', $tvValue);

// Ora puoi stampare i valori separati
foreach ($tvArray as $value) {
    echo '<p>' . $value . '</p>';
}