<?php
/**
 * ecmRichiestoSe — validatore personalizzato per FormIt 5.x / MODX 3.x
 *
 * Rende un campo obbligatorio solo quando un altro campo del form ha
 * (o non ha) certi valori. Serve perché FormIt non ha obbligatorietà
 * condizionale nativa: senza questo, tutte le regole "se scegli X allora
 * devi compilare Y" esistono solo in JavaScript e un POST diretto le aggira.
 *
 * Uso in &validate, sintassi FormIt per i parametri:
 *
 *   ecm_disc_id:ecmRichiestoSe=^ecm_prof_id!0;1000^
 *      obbligatorio se ecm_prof_id è valorizzato e diverso da 0 e 1000
 *
 *   org_reclutatore:ecmRichiestoSe=^reclutato=Si^
 *      obbligatorio se reclutato vale esattamente "Si"
 *
 *   org_id:ecmRichiestoSe=^status=D^
 *
 * Grammatica del parametro:
 *   campo=v1;v2   obbligatorio se il valore di "campo" è uno fra v1, v2
 *   campo!v1;v2   obbligatorio se "campo" è compilato e NON è fra v1, v2
 *
 * Il messaggio può essere personalizzato aggiungendo | e il testo:
 *   ecm_disc_id:ecmRichiestoSe=^ecm_prof_id!0;1000|Indica la disciplina^
 */

$messaggio = 'Questo campo è obbligatorio.';

$param = (string) $param;
if (strpos($param, '|') !== false) {
    list($param, $messaggio) = explode('|', $param, 2);
}

$negato = (strpos($param, '!') !== false);
$separatore = $negato ? '!' : '=';

if (strpos($param, $separatore) === false) {
    // Parametro malformato: non blocchiamo l'invio, ma lo segnaliamo nel log.
    $modx->log(1 /* LOG_LEVEL_ERROR */, '[ecmRichiestoSe] parametro non valido: ' . $param);
    return true;
}

list($campoDaGuardare, $listaValori) = explode($separatore, $param, 2);
$campoDaGuardare = trim($campoDaGuardare);
$valori = array_map('trim', explode(';', $listaValori));

$osservato = isset($_POST[$campoDaGuardare]) ? trim((string) $_POST[$campoDaGuardare]) : '';

if ($negato) {
    $obbligatorio = ($osservato !== '' && !in_array($osservato, $valori, true));
} else {
    $obbligatorio = in_array($osservato, $valori, true);
}

if (!$obbligatorio) {
    return true;
}

$compilato = is_array($value)
    ? (count(array_filter($value, 'strlen')) > 0)
    : (trim((string) $value) !== '');

return $compilato ? true : $messaggio;