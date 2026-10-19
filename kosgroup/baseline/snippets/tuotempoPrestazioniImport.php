<?php
$logFile = MODX_CORE_PATH . 'cache/logs/import_tuotempo.log';

function logTuotempo($logFile, $message) {
    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

try {
    logTuotempo($logFile, 'Inizio processo di importazione');
    
    $url = 'https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities';
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Errore cURL: ' . curl_error($ch));
    }
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    if (!isset($responseData['result']) || $responseData['result'] !== 'OK' || 
        !isset($responseData['return']['results']) || !is_array($responseData['return']['results'])) {
        // ERRORE REALE: va nell'error log MODX
        $modx->log(modX::LOG_LEVEL_ERROR, '[import_tuotempo] Risposta API non valida o dati mancanti');
        return "Risposta API non valida o dati mancanti";
    }
    
    $data = $responseData['return']['results'];
    logTuotempo($logFile, 'Numero totale elementi trovati: ' . count($data));
    
    $modx->exec("TRUNCATE TABLE prestazioni_tuotempo");
    
    $stmt = $modx->prepare("
        INSERT INTO prestazioni_tuotempo 
        (activityid, activityTitle, typologyTitle, mopNotice, mopBookability, deferredTarget) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $inserted = 0;
    $errors = 0;
    foreach ($data as $item) {
        try {
            $deferredTarget = null;
            if (isset($item['mopBookabilityOptions']) && 
                is_array($item['mopBookabilityOptions']) && 
                isset($item['mopBookabilityOptions']['deferredTarget'])) {
                $deferredTarget = $item['mopBookabilityOptions']['deferredTarget'];
            }
            
            $result = $stmt->execute([
                $item['activityid'],
                $item['activityTitle'],
                $item['typologyTitle'],
                $item['mopNotice'],
                $item['mopBookability'],
                $deferredTarget
            ]);
            
            if ($result) {
                $inserted++;
                if ($inserted % 100 === 0) {
                    logTuotempo($logFile, "Inseriti $inserted record...");
                }
            }
        } catch (Exception $e) {
            $errors++;
            // ERRORE REALE: va nell'error log MODX
            $modx->log(modX::LOG_LEVEL_ERROR, '[import_tuotempo] Errore inserimento record: ' . $e->getMessage());
            continue;
        }
    }
    
    $summary = "Processo completato. Inseriti: $inserted record. Errori: $errors.";
    logTuotempo($logFile, $summary);
    return "Importazione completata con successo. Inseriti $inserted record.";
    
} catch (Exception $e) {
    // ERRORE REALE: va nell'error log MODX
    $modx->log(modX::LOG_LEVEL_ERROR, '[import_tuotempo] Errore generale: ' . $e->getMessage());
    return "Si è verificato un errore: " . $e->getMessage();
}