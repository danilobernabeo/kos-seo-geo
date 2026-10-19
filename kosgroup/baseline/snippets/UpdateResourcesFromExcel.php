<?php
if (!isset($_FILES['csv_file'])) {
    return 'Per favore carica un file CSV';
}

try {
    // Funzione per estrarre l'alias dall'URL
    function getAliasFromUrl($url) {
        $parts = explode('/', rtrim($url, '/'));
        return end($parts);
    }

    // Leggi il file CSV con punto e virgola come separatore e gestione UTF-8
    $file = $_FILES['csv_file']['tmp_name'];
    $rows = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Leggi il BOM UTF-8 se presente
        fgets($handle, 4) !== "\xEF\xBB\xBF" && rewind($handle);
        
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            // Converti ogni elemento dell'array in UTF-8
            $data = array_map(function($item) {
                return mb_convert_encoding($item, 'UTF-8', 'Windows-1252');
            }, $data);
            $rows[] = $data;
        }
        fclose($handle);
    }
    
    // Rimuovi l'intestazione
    array_shift($rows);

    $output = [];
    $stats = [
        'found' => 0,
        'not_found' => 0,
        'updated' => 0,
        'longtitle_updated' => 0,
        'description_updated' => 0,
        'title_updated' => 0
    ];

    foreach ($rows as $row) {
        $url = $row[0];
        $title = $row[1];
        $metaDesc = $row[2];
        $h1 = $row[3];
        
        $alias = getAliasFromUrl($url);
        
        // Cerca la risorsa per alias
        $resource = $modx->getObject('modResource', ['alias' => $alias, 'deleted' => 0]);
        
        if ($resource) {
            $stats['found']++;
            $updates = [];
            $isUpdated = false;
            
            if ($resource->get('longtitle') !== $title) {
                $resource->set('longtitle', $title);
                $stats['longtitle_updated']++;
                $updates[] = "Longtitle aggiornato";
                $isUpdated = true;
            }
            
            if ($resource->get('description') !== $metaDesc) {
                $resource->set('description', $metaDesc);
                $stats['description_updated']++;
                $updates[] = "Description aggiornata";
                $isUpdated = true;
            }
            
            if ($resource->get('pagetitle') !== $h1) {
                $resource->set('pagetitle', $h1);
                $stats['title_updated']++;
                $updates[] = "Title aggiornato con H1";
                $isUpdated = true;
            }
            
            if ($isUpdated) {
                if ($resource->save()) {
                    $stats['updated']++;
                    $output[] = "\nRisorsa aggiornata: {$alias} (ID: {$resource->get('id')})\n" . implode("\n", $updates) . "\n";
                } else {
                    $output[] = "\nErrore nell'aggiornamento della risorsa: {$alias} (ID: {$resource->get('id')})\n";
                }
            }
            
        } else {
            $stats['not_found']++;
            $output[] = "Risorsa non trovata per alias: {$alias}";
        }
    }

    // Separa le risorse non trovate dagli aggiornamenti
    $notFoundResources = [];
    $updatedResources = [];
    
    foreach ($output as $line) {
        if (strpos($line, "Risorsa non trovata per alias:") !== false) {
            $notFoundResources[] = $line;
        } else {
            $updatedResources[] = $line;
        }
    }

    // Prepara il report
    $report = "
    STATISTICHE:
    - Risorse trovate: {$stats['found']}
    - Risorse non trovate: {$stats['not_found']}
    - Risorse aggiornate: {$stats['updated']}
    - Longtitle aggiornati: {$stats['longtitle_updated']}
    - Description aggiornate: {$stats['description_updated']}
    - Title aggiornati: {$stats['title_updated']}

    RISORSE NON TROVATE:
    " . implode("\n", $notFoundResources) . "

    DETTAGLI DEGLI AGGIORNAMENTI EFFETTUATI:
    " . implode("\n", $updatedResources);

    return nl2br($report);

} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Errore: ' . $e->getMessage());
    return 'Si è verificato un errore: ' . $e->getMessage();
}