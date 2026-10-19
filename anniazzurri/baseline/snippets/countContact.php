<?php
// Configurazione
require_once 'config.core.php'; // Carica MODX
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('web');

$encryptionKey = '[CHIAVE RIMOSSA DALLA BASELINE]'; // Recupera la chiave di crittografia
if (empty($encryptionKey)) {
    die("Chiave di crittografia non trovata.");
}

// Query per recuperare i dati crittografati
$query = $modx->newQuery('FormItForm');
$query->where([
    'form:IN' => ['form-266' , 'form-268' , 'form-269' , 'form-270' , 'form-286' , 'form-405'], // Sostituisci con gli ID dei form
    'date:>' => '2024-01-01 00:00:00',
    'date:<' => '2024-10-30 23:59:59',
]);
$query->select(['field', 'value']);

$forms = $modx->getCollection('FormItForm', $query);

// Decrittazione e conteggio
$emails = [];
foreach ($forms as $form) {
    $field = $form->get('field');
    $encryptedValue = $form->get('value');
    
    if ($field === 'email') {
        // Decrittazione
        $decryptedValue = openssl_decrypt(
            base64_decode($encryptedValue),
            'AES-256-CBC',
            $encryptionKey,
            0,
            substr(hash('sha256', $encryptionKey, true), 0, 16)
        );
        if ($decryptedValue) {
            $emails[] = $decryptedValue;
        }
    }
}

// Conta le email uniche
$uniqueEmails = array_unique($emails);
echo "Totale contatti unici: " . count($uniqueEmails);