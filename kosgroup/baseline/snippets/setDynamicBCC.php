<?php
/**
 * Snippet: setDynamicBCC
 * Imposta dinamicamente i destinatari BCC in base alla specializzazione selezionata
 */

// Ottieni il valore della specializzazione dal form
$specializzazione = $hook->getValue('specializzazione');

// Log per debug
$modx->log(modX::LOG_LEVEL_ERROR, '[FormIt DEBUG] Specializzazione ricevuta: ' . $specializzazione);

// Definisci i destinatari BCC in base alla specializzazione
switch ($specializzazione) {
    case 'pavimento-pelvico':
        $bcc = 'enza.colla@kosgroup.com,nadia.santi@kosgroup.com,danilo.bernabeo@gmail.com,matteo.clausi@kosgroup.com';
        break;
    
    case 'onde-urto':
    case 'gastroenterologia':
    case 'info-generali':
        $bcc = 'sanpancrazio@kosgroup.com,danilo.bernabeo@gmail.com,matteo.clausi@kosgroup.com';
        break;
    
    default:
        $bcc = 'sanpancrazio@kosgroup.com,danilo.bernabeo@gmail.com,matteo.clausi@kosgroup.com';
        break;
}

// Log BCC impostato
$modx->log(modX::LOG_LEVEL_ERROR, '[FormIt DEBUG] BCC impostato: ' . $bcc);

// Imposta il BCC dinamicamente
$hook->setValue('emailBCC', $bcc);

// Log conferma
$modx->log(modX::LOG_LEVEL_ERROR, '[FormIt DEBUG] BCC applicato con successo');

return true;