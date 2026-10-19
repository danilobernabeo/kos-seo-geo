<?php
/**
 * pdfInventoryCSV
 * 
 * Snippet per generare un CSV con i percorsi relativi dei PDF
 * 
 * Parametri:
 * - startDir: directory di partenza (default: assets/doc)
 * - outputFile: nome del file CSV di output (default: pdf_inventory.csv)
 */

// Parametri dello snippet
$startDir = $modx->getOption('startDir', $scriptProperties, 'assets/doc');
$outputFile = $modx->getOption('outputFile', $scriptProperties, 'pdf_inventory.csv');

// Percorso assoluto della directory di partenza
$basePath = MODX_BASE_PATH . $startDir;

// Funzione ricorsiva per trovare i PDF
function findPdfFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'pdf') {
            $files[] = [str_replace(MODX_BASE_PATH, '', $file->getPathname())];
        }
    }
    
    return $files;
}

try {
    // Trova tutti i PDF
    $pdfFiles = findPdfFiles($basePath);
    
    // Prepara il file CSV
    $outputPath = MODX_BASE_PATH . $outputFile;
    $fp = fopen($outputPath, 'w');
    
    // Scrivi l'intestazione
    fputcsv($fp, ['percorso_file']);
    
    // Scrivi i percorsi
    foreach ($pdfFiles as $file) {
        fputcsv($fp, $file);
    }
    
    fclose($fp);
    
    return "Inventario PDF creato con successo in: {$outputFile}<br>Trovati " . count($pdfFiles) . " file PDF.";
    
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[pdfInventoryCSV] ' . $e->getMessage());
    return 'Si è verificato un errore durante la generazione dell\'inventario.';
}