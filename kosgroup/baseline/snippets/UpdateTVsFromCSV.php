<?php
/**
 * UpdateTVsFromCSV - Versione robusta
 * 
 * Importa dati da un file CSV nelle Template Variables per risorse con template 'strutture'
 * sotto la risorsa padre con ID 167
 * 
 * Rileva automaticamente l'encoding e il delimitatore del file CSV
 */

// Percorso al file CSV (modifica secondo la tua installazione)
$csvPath = MODX_BASE_PATH . 'assets/import/strutture.csv';

// Verifica che il file esista
if (!file_exists($csvPath)) {
    return 'File CSV non trovato: ' . $csvPath;
}

// Leggi il contenuto del file
$fileContent = file_get_contents($csvPath);

// Rimuovi eventuali BOM (Byte Order Mark)
$bom = pack('H*', 'EFBBBF');
$fileContent = preg_replace("/^$bom/", '', $fileContent);

// Prova a rilevare il delimitatore
$possibleDelimiters = [';', ',', "\t", '|'];
$detectedDelimiter = ';'; // Default a punto e virgola
$maxFields = 0;

// Per ciascun possibile delimitatore, vedi quanti campi riesce a separare nella prima riga
foreach ($possibleDelimiters as $delimiter) {
    $firstLine = substr($fileContent, 0, strpos($fileContent, "\n"));
    $fields = str_getcsv($firstLine, $delimiter);
    
    if (count($fields) > $maxFields) {
        $maxFields = count($fields);
        $detectedDelimiter = $delimiter;
    }
}

$modx->log(modX::LOG_LEVEL_ERROR, 'Delimitatore rilevato: ' . $detectedDelimiter);

// Processa il CSV
$lines = explode("\n", $fileContent);
$headers = str_getcsv(array_shift($lines), $detectedDelimiter);

// Pulisci le intestazioni
$cleanHeaders = array_map(function($header) {
    return trim($header);
}, $headers);

$modx->log(modX::LOG_LEVEL_ERROR, 'Intestazioni trovate: ' . print_r($cleanHeaders, true));

// Trova gli indici delle colonne di interesse (case insensitive)
$requiredColumns = ['nome', 'idyext', 'regione', 'provincia'];
$columnIndexes = [];

foreach ($cleanHeaders as $index => $header) {
    $headerLower = strtolower($header);
    if (in_array($headerLower, $requiredColumns)) {
        $columnIndexes[$headerLower] = $index;
    }
}

// Verifica che tutte le colonne richieste siano presenti
$missingColumns = array_diff($requiredColumns, array_keys($columnIndexes));
if (!empty($missingColumns)) {
    return 'Colonne mancanti nel CSV: ' . implode(', ', $missingColumns) . 
           '. Intestazioni trovate: ' . implode(', ', $cleanHeaders);
}

// Ottieni tutte le risorse con template 'strutture' che sono figlie della risorsa 167
$template = $modx->getObject('modTemplate', ['templatename' => 'strutture']);
if (!$template) {
    return 'Template "strutture" non trovato';
}
$templateId = $template->get('id');

$resources = $modx->getCollection('modResource', [
    'parent' => 167,
    'template' => $templateId
]);

if (empty($resources)) {
    return 'Nessuna risorsa trovata con template "strutture" sotto il parent 167';
}

// Crea un array con le risorse indicizzate per pagetitle
$resourceMap = [];
foreach ($resources as $resource) {
    $resourceMap[strtolower(trim($resource->get('pagetitle')))] = $resource;
}

// Contatori per il report
$updatedCount = 0;
$notFoundCount = 0;
$notFoundResources = [];

// Processo ogni riga del CSV
foreach ($lines as $line) {
    if (empty(trim($line))) continue; // Salta linee vuote
    
    $data = str_getcsv($line, $detectedDelimiter);
    if (count($data) < count($cleanHeaders)) continue; // Salta righe malformate
    
    // Ottieni i valori
    $nome = trim($data[$columnIndexes['nome']]);
    $idYext = trim($data[$columnIndexes['idyext']]);
    $regione = trim($data[$columnIndexes['regione']]);
    $provincia = trim($data[$columnIndexes['provincia']]);
    
    if (empty($nome)) continue; // Salta righe senza nome
    
    // Cerca la risorsa corrispondente
    $resourceKey = strtolower($nome);
    if (isset($resourceMap[$resourceKey])) {
        $resource = $resourceMap[$resourceKey];
        
        // Aggiorna le template variables
        $resource->setTVValue('regione', $regione);
        $resource->setTVValue('provincia', $provincia);
        $resource->setTVValue('idYext', $idYext);
        
        // Salva le modifiche
        if ($resource->save()) {
            $updatedCount++;
        }
    } else {
        $notFoundCount++;
        $notFoundResources[] = $nome;
    }
}

// Restituisci un report
$output = "Aggiornamento completato.\n";
$output .= "- Risorse aggiornate: $updatedCount\n";
$output .= "- Risorse non trovate: $notFoundCount\n";

if ($notFoundCount > 0) {
    $output .= "Risorse non trovate:\n";
    $output .= implode(", ", $notFoundResources);
}

return $output;