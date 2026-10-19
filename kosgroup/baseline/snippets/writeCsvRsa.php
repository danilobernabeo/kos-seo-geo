<?php
// Definisci il percorso del file JSON di input
$jsonFilePath = MODX_BASE_PATH . 'assets/js/rsa.js';
// Definisci il percorso del file CSV di output
$csvFilePath = MODX_BASE_PATH . 'assets/csv/strutture.csv';
// Definisci il dominio per le immagini
$domainUrl = 'https://7ydxba3m.modx.dev';

$output = '';

if (file_exists($jsonFilePath)) {
    // Leggi il contenuto del file JSON
    $jsonContent = file_get_contents($jsonFilePath);
    
    // Decodifica il JSON
    $structures = json_decode($jsonContent, true);
    
    if ($structures && is_array($structures)) {
        // Prepara l'array per il CSV
        $csvData = [];
        
        // Aggiungi l'intestazione
        $csvData[] = ['Nome', 'URL del sito web > URL', 'foto > URL', 'foto > Testo alternativo'];
        
        // Aggiungi i dati di ogni struttura
        foreach ($structures as $structure) {
            // Modifica il path dell'immagine
            $imgPath = str_replace('/assets/img/strutture/', '/assets/img/yext/', $structure['img']);
            $fullImgUrl = $domainUrl . $imgPath;
            
            $csvData[] = [
                $structure['nome_struttura'],
                $structure['link_sito'],
                $fullImgUrl,
                $structure['nome_struttura']
            ];
            
            // Genera anche l'output HTML usando il nuovo path dell'immagine
            $output .= '
            <div class="col-12 col-md-4 col-lg-3 struttura-card">
                <a href="' . $structure['link_sito'] . '" title="' . htmlspecialchars($structure['nome_struttura']) . '" target="_blank" class="struttura-container">
                    <div class="struttura-img">
                        <img src="' . $fullImgUrl . '" alt="' . htmlspecialchars($structure['nome_struttura']) . '">
                    </div>
                    <div class="struttura-content mb-3 mb-md-4">
                        <h4>' . htmlspecialchars($structure['nome_struttura']) . '</h4>
                        <p>' . htmlspecialchars($structure['indirizzo']) . '</p>
                    </div>
                </a>
            </div>';
        }
        
        // Crea la directory se non esiste
        $csvDir = MODX_BASE_PATH . 'assets/csv';
        if (!is_dir($csvDir)) {
            mkdir($csvDir, 0755, true);
        }
        
        // Apri il file CSV in modalità scrittura
        $fp = fopen($csvFilePath, 'w');
        
        // Imposta il BOM per UTF-8
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Scrivi i dati nel file CSV con separatore punto e virgola
        foreach ($csvData as $fields) {
            fputcsv($fp, $fields, ';');
        }
        
        // Chiudi il file
        fclose($fp);
    }
}

return $output;