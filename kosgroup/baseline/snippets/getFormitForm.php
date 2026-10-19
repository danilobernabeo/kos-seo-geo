<?php
/**
 * Snippet to get formit forms by name
 * usage:
 * [[!getFormitForm? &name=`Name of form`]]
 */
$id = $modx->resource->get('id');
$page = $modx->getObject('modResource', $id);
$skey = $page->getTVValue('skey');
if( !isset($_GET['key']) ) {
  $modx->sendUnauthorizedPage();
}
$akey = $_GET['key'];
if( $akey != $skey ) {
  $modx->sendUnauthorizedPage();
}

$baseUrl = $modx->getOption('site_url');

use Sterc\FormIt\Model\FormItForm;
// Load formit class
$formit = $modx->getService('formit', 'FormIt', $modx->getOption('formit.core_path', null, $modx->getOption('core_path') . 'components/formit/') . 'model/formit/');
// Load PDO Tools 
$pdo = $modx->getService('pdoTools');
// set snippet props
$formName = $modx->getOption('name', $scriptProperties, null);
// xpdo query to get forms
$q = $modx->newQuery(FormItForm::class);
$q->where([
    'form' => $formName,
]);
$total = $modx->getCount(FormItForm::class, $q);
$forms = $modx->getCollection(FormItForm::class, $q);
$i = 0;

// Recupera la directory fisica dove vengono salvati i CV
$cvDir = $modx->getOption('base_path') . 'assets/doc/cv/';

// Ottieni tutti i file nella directory CV
$allFiles = [];
if (is_dir($cvDir)) {
    if ($dh = opendir($cvDir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != "." && $file != "..") {
                $allFiles[] = $file;
            }
        }
        closedir($dh);
    }
}

$oss = []; // Inizializza l'array oss

if (!empty($forms)) {
    // Set our inner tpl var
    $dataOutput = '';
    // Loop through each object and tpl up
    foreach ($forms as $form) {
        $fields = $form->toArray();
        // Check if data is encrypted
        if ($form->get('encrypted')) {
            // Form is encrypted, use the objects decrypt method to decyrpt
            $fields['values'] = $form->decrypt($form->get('values'));
        }
        // $fields['values'] contains the JSON array to access pass to json_decode() ie;
        $values = json_decode($fields['values'], true);
        $fields['fields'] = '';
        foreach ($values as $key => $value) {
            // Convirti array in stringa per evitare errori
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $fields['fields'] .= "<strong>$key:</strong> $value<br>";
        }
        // Format the date string
        $fields['date'] = date('Y-m-d', $fields['date']);
        
        // Inizializza l'elemento dell'array oss per questo indice
        $oss[$i] = [];
        
        // Verifica e assegna i valori solo se esistono
        $oss[$i]["region"] = isset($values['region']) ? (is_array($values['region']) ? '' : $values['region']) : '';
        $oss[$i]["date"] = $fields['date'];
        $oss[$i]["surname"] = isset($values['surname']) ? (is_array($values['surname']) ? '' : $values['surname']) : '';
        $oss[$i]["name"] = isset($values['name']) ? (is_array($values['name']) ? '' : $values['name']) : '';
        $oss[$i]["email"] = isset($values['email']) ? (is_array($values['email']) ? '' : $values['email']) : '';
        $oss[$i]["cellulare"] = isset($values['cellulare']) ? (is_array($values['cellulare']) ? '' : $values['cellulare']) : '';
        
        // Gestione migliorata del percorso CV
        // Verifica che uploadcv esista e sia un array non vuoto
        if (isset($values['uploadcv']) && is_array($values['uploadcv']) && !empty($values['uploadcv'])) {
            try {
                // Ottieni l'elemento corretto dall'array uploadcv
                $uploadcvArray = array_values($values['uploadcv']);
                
                if (count($uploadcvArray) >= 3) {
                    $uploadcvValue = $uploadcvArray[count($uploadcvArray) - 3];
                    
                    // Assicurati che non sia un array
                    if (!is_array($uploadcvValue)) {
                        $originalFileName = str_replace('/www/assets/components/formit/tmp/', 'assets/doc/cv/', $uploadcvValue);
                        
                        // Estrai il nome del file senza il percorso
                        $fileName = basename($originalFileName);
                        
                        // Separa l'hash dal nome del file (assumendo il formato hash-nomeFile)
                        $fileNameParts = explode('-', $fileName, 2);
                        
                        if (count($fileNameParts) == 2) {
                            // Prendi solo il nome del file senza l'hash
                            $actualFileName = $fileNameParts[1];
                            
                            // Cerca nel filesystem un file che finisce con lo stesso nome
                            $foundFile = null;
                            foreach ($allFiles as $file) {
                                // Separa anche questo file per ottenere il nome senza l'hash
                                $fileParts = explode('-', $file, 2);
                                if (count($fileParts) == 2) {
                                    // Normalizza i nomi per il confronto (rimuovi parentesi e spazi)
                                    $normalizedActualName = preg_replace('/[^a-zA-Z0-9]/', '', $actualFileName);
                                    $normalizedFileName = preg_replace('/[^a-zA-Z0-9]/', '', $fileParts[1]);
                                    
                                    // Confronta i nomi normalizzati
                                    if (strcasecmp($normalizedFileName, $normalizedActualName) == 0) {
                                        $foundFile = $file;
                                        break;
                                    }
                                }
                            }
                            
                            if ($foundFile) {
                                $oss[$i]["cv"] = '/assets/doc/cv/' . $foundFile;
                            } else {
                                // Se non troviamo corrispondenze, usiamo il nome originale
                                $oss[$i]["cv"] = '/' . $originalFileName;
                            }
                        } else {
                            // Se il nome del file non è nel formato previsto
                            $oss[$i]["cv"] = '/' . $originalFileName;
                        }
                    } else {
                        // Se uploadcvValue è un array, cerchiamo di estrarre informazioni utili
                        $oss[$i]["cv"] = '';
                        
                        // Prova ad estrarre il nome del file se presente nell'array
                        if (isset($uploadcvValue['name'])) {
                            // Cerca un file che corrisponda
                            $fileNameToMatch = basename($uploadcvValue['name']);
                            foreach ($allFiles as $file) {
                                $fileParts = explode('-', $file, 2);
                                if (count($fileParts) == 2 && strcasecmp($fileParts[1], $fileNameToMatch) == 0) {
                                    $foundFile = $file;
                                    $oss[$i]["cv"] = '/assets/doc/cv/' . $foundFile;
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $oss[$i]["cv"] = ''; // Valore predefinito se non ci sono abbastanza elementi nell'array
                }
            } catch (Exception $e) {
                $oss[$i]["cv"] = '';
            }
        } else {
            $oss[$i]["cv"] = ''; // Valore predefinito se uploadcv non esiste o non è un array
        }
        
        $i = $i + 1;
    }
    
    // Solo se l'array oss ha elementi
    if (!empty($oss) && isset($oss[0]) && isset($oss[0]['email'])) {
        $tempArr = array_unique(array_column($oss, 'email'));
        $newarr = array_intersect_key($oss, $tempArr);
        $count = sizeof($newarr);
        $newarr = array_values($newarr);
        $x = 0;
        echo "<header class='card-header'><h2 class='card-title'>Report [[*tipoReport]] ($count candidati)</h2></header>";
        echo "<div class='card-body'><table class='table table-bordered table-striped mb-0' id='datatable-default'><thead><tr><th>Data</th><th>Cognome</th><th>Nome</th><th>email</th><th>Cellulare</th><th>CV</th></tr></thead>";
        echo "<tbody>";
        
        while($x < $count) {
            echo "<tr>";
            echo "<td>" . $newarr[$x]['date'] . "</td>";
            echo "<td>" . $newarr[$x]['surname'] . "</td>";
            echo "<td>" . $newarr[$x]['name'] . "</td>";
            echo "<td>" . $newarr[$x]['email'] . "</td>";
            echo "<td>" . $newarr[$x]['cellulare'] . "</td>";
            echo "<td>";
            if(!empty($newarr[$x]['cv'])) {
                // Assicurati che non ci siano doppie barre nel percorso finale
                $cvPath = ltrim($newarr[$x]['cv'], '/');  // Rimuovi eventuali slash iniziali
                $cvUrl = $baseUrl . $cvPath;  // Combina l'URL base con il percorso del file
                echo "<a type='button' class='mb-1 mt-1 mr-1 btn btn-primary' href='" . $cvUrl . "' target='_blank'>Scarica CV</a>";
            }
            echo "</td>";
            echo "</tr>";
            $x++;
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>Nessun candidato trovato</div>";
    }
} else {
    echo "<div class='alert alert-warning'>Nessun form trovato</div>";
}
return;