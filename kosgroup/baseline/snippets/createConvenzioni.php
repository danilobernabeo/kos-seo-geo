<?php
// Impostazioni
$csvFile = MODX_ASSETS_PATH . 'csv/listaconvenzioni.csv';
$parentId = 7;
$templateId = 12;

// Contatori
$deletedPages = 0;
$createdPages = 0;
$errors = 0;
$logoFound = array();
$logoMissing = array();

// Funzione per sanitizzare il nome del file
function sanitizeFileName($string) {
    // Converti in minuscolo
    $string = strtolower($string);
    
    // Converti caratteri accentati
    $chars = array(
        'à' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 
        'ò' => 'o', 'ù' => 'u', 'À' => 'a', 'È' => 'e',
        'É' => 'e', 'Ì' => 'i', 'Ò' => 'o', 'Ù' => 'u'
    );
    $string = strtr($string, $chars);
    
    // Rimuovi caratteri speciali e sostituisci spazi con trattini
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

// Elimina le pagine esistenti
$existingPages = $modx->getCollection('modResource', array('parent' => $parentId));
foreach ($existingPages as $page) {
    if ($page->remove()) {
        $deletedPages++;
    } else {
        $errors++;
        $modx->log(modX::LOG_LEVEL_ERROR, 'Errore nell\'eliminazione della pagina con ID: ' . $page->get('id'));
    }
}

// Leggi il file CSV
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Salta l'intestazione
    $header = fgetcsv($handle, 0, ";");
    
    // Array per mappare le colonne delle aree cliniche
    $areeClinicheColumns = array(
        1 => "Ambulatori - Medicina Specialistica",
        2 => "Riabilitazione",
        3 => "Odontoiatria",
        4 => "Ricovero - Interventi",
        5 => "Psichiatria"
    );
    
    // Array per mappare le colonne dei tipi di convenzione
    $tipiConvenzioneColumns = array(
        6 => "Forma Diretta",
        7 => "Forma Indiretta"
    );
    
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        $enteNome = $data[0];
        if (empty($enteNome)) continue;
        
        // Raccogli aree cliniche
        $areeClinicheArray = array();
        foreach ($areeClinicheColumns as $index => $areaName) {
            if (!empty($data[$index]) && $data[$index] == 'x') {
                $areeClinicheArray[] = $areaName;
            }
        }
        
        // Raccogli tipi di convenzione
        $tipiConvenzioneArray = array();
        foreach ($tipiConvenzioneColumns as $index => $tipoName) {
            if (!empty($data[$index]) && $data[$index] == 'x') {
                $tipiConvenzioneArray[] = $tipoName;
            }
        }
        
        // Raccogli ID strutture
        $struttureIds = array();
        for ($i = 8; $i < count($data); $i++) {
            if (!empty($data[$i]) && $data[$i] == 'x') {
                $struttureIds[] = $header[$i];
            }
        }
        
        // Prepara l'alias che sarà usato sia per la pagina che per il logo
        $alias = sanitizeFileName($enteNome);
        
        // Prepara il nome del logo e verifica l'esistenza
        $logoFileName = 'assets/img/convenzioni/logo-' . $alias . '.jpg';
        $logoFullPath = MODX_BASE_PATH . $logoFileName;
        
        // Verifica se il file del logo esiste
        $logoExists = file_exists($logoFullPath);
        if ($logoExists) {
            $logoFound[] = $enteNome;
        } else {
            $logoMissing[] = $enteNome;
            $logoFileName = ''; // Se il logo non esiste, non lo salviamo nella TV
        }
        
        // Crea la risorsa
        $resource = $modx->newObject('modResource');
        $resource->fromArray(array(
            'pagetitle' => $enteNome,
            'alias' => $alias,
            'published' => 1,
            'parent' => $parentId,
            'template' => $templateId,
            'content' => ''
        ));
        
        if ($resource->save()) {
            // Ottieni l'ID della risorsa appena creata
            $resourceId = $resource->get('id');
            
            // Imposta i valori delle variabili di template
            $tvs = array(
                'areaClinica' => implode(', ', $areeClinicheArray),
                'tipoConvenzione' => implode(', ', $tipiConvenzioneArray),
                'mapServiziStrutture' => implode(',', $struttureIds),
                'infoImage' => $logoFileName
            );
            
            $tvSuccess = true;
            foreach ($tvs as $tvName => $tvValue) {
                // Ottieni l'oggetto TV
                $tv = $modx->getObject('modTemplateVar', array('name' => $tvName));
                if ($tv) {
                    // Salva il valore della TV per questa risorsa
                    $tv->setValue($resourceId, $tvValue);
                    if (!$tv->save()) {
                        $tvSuccess = false;
                        $errors++;
                        $modx->log(modX::LOG_LEVEL_ERROR, 'Errore nel salvare la TV ' . $tvName . ' per la risorsa: ' . $enteNome);
                    }
                } else {
                    $tvSuccess = false;
                    $errors++;
                    $modx->log(modX::LOG_LEVEL_ERROR, 'Template Variable non trovata: ' . $tvName);
                }
            }
            
            if ($tvSuccess) {
                $createdPages++;
            }
        } else {
            $errors++;
            $modx->log(modX::LOG_LEVEL_ERROR, 'Errore nel salvare la risorsa: ' . $enteNome);
        }
    }
    fclose($handle);
}

// Prepara il report dettagliato
$report = sprintf(
    "Importazione completata.\n" .
    "- Pagine eliminate: %d\n" .
    "- Pagine create: %d\n" .
    "- Errori: %d\n\n" .
    "Loghi trovati (%d):\n%s\n\n" .
    "Loghi mancanti (%d):\n%s",
    $deletedPages,
    $createdPages,
    $errors,
    count($logoFound),
    implode("\n", array_map(function($name) { return "- " . $name; }, $logoFound)),
    count($logoMissing),
    implode("\n", array_map(function($name) { return "- " . $name; }, $logoMissing))
);

return $report;