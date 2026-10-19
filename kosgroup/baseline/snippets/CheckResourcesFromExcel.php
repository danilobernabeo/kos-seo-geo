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
       'longtitle_differences' => 0,
       'description_differences' => 0,
       'title_differences' => 0
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
           $differences = [];
           
           if ($resource->get('longtitle') !== $title) {
               $stats['longtitle_differences']++;
               $differences[] = "Longtitle: \nAttuale=\"{$resource->get('longtitle')}\"\nNuovo=\"{$title}\"";
           }
           
           if ($resource->get('description') !== $metaDesc) {
               $stats['description_differences']++;
               $differences[] = "Description: \nAttuale=\"{$resource->get('description')}\"\nNuovo=\"{$metaDesc}\"";
           }
           
           if ($resource->get('pagetitle') !== $h1) {
               $stats['title_differences']++;
               $differences[] = "Title/H1: \nTitle attuale=\"{$resource->get('pagetitle')}\"\nH1 nuovo=\"{$h1}\"";
           }
           
           if (!empty($differences)) {
               $output[] = "\nRisorsa: {$alias} (ID: {$resource->get('id')})\n" . implode("\n", $differences) . "\n";
           }
           
       } else {
           $stats['not_found']++;
           $output[] = "Risorsa non trovata per alias: {$alias}";
       }
   }

   // Separa le risorse non trovate dalle differenze
   $notFoundResources = [];
   $differences = [];
   
   foreach ($output as $line) {
       if (strpos($line, "Risorsa non trovata per alias:") !== false) {
           $notFoundResources[] = $line;
       } else {
           $differences[] = $line;
       }
   }

   // Prepara il report
   $report = "
   STATISTICHE:
   - Risorse trovate: {$stats['found']}
   - Risorse non trovate: {$stats['not_found']}
   - Differenze nei Longtitle: {$stats['longtitle_differences']}
   - Differenze nelle Description: {$stats['description_differences']}
   - Differenze Title/H1: {$stats['title_differences']}

   RISORSE NON TROVATE:
   " . implode("\n", $notFoundResources) . "

   DETTAGLI DELLE DIFFERENZE TROVATE:
   " . implode("\n", $differences);

   return nl2br($report);

} catch (Exception $e) {
   $modx->log(modX::LOG_LEVEL_ERROR, 'Errore: ' . $e->getMessage());
   return 'Si è verificato un errore: ' . $e->getMessage();
}