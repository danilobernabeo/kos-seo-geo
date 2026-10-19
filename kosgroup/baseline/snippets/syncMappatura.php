<?php
/**
 * SYNC MAPPATURA - Versione funzionante
 * Basata sul debug che funziona
 */

$batchSize = 53;
$output = "";
$stats = ['processati' => 0, 'mappature' => 0, 'errori' => 0];

// Statistiche iniziali
$stmt = $modx->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN sync_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN sync_status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM kos_sync_status
");
$globalStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Prendi batch di medici pending
$stmt = $modx->query("
    SELECT resourceid, areaid, resource_name 
    FROM kos_sync_status 
    WHERE sync_status = 'pending' 
    ORDER BY created_at ASC 
    LIMIT {$batchSize}
");
$medici = $stmt->fetchAll(PDO::FETCH_ASSOC);

$logs = [];

if (empty($medici)) {
    $logs[] = ['msg' => 'Nessun medico in coda - Sync completata!', 'type' => 'success'];
} else {
    $logs[] = ['msg' => 'Processo ' . count($medici) . ' medici...', 'type' => 'info'];
    
    foreach ($medici as $medico) {
        $resourceId = $medico['resourceid'];
        $areaid = $medico['areaid'];
        $nome = $medico['resource_name'];
        
        try {
            // Chiama API
            $url = "https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities?areaid=" . urlencode($areaid) . "&resourceid=" . urlencode($resourceId);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) throw new Exception("cURL: {$error}");
            if ($httpCode !== 200) throw new Exception("HTTP {$httpCode}");
            
            $data = json_decode($response, true);
            if (!$data || $data['result'] !== 'OK') throw new Exception("API error");
            
            $activities = $data['return']['results'] ?? [];
            $countMappature = 0;
            $prestazioniIds = [];
            
            foreach ($activities as $act) {
                $activityId = $act['activityid'] ?? null;
                if (!$activityId) continue;
                
                // Salta nascoste/non prenotabili
                if (($act['isHidden'] ?? '0') == '1') continue;
                if ((int)($act['mopBookability'] ?? 1) == 0) continue;
                
                $prestazioniIds[] = $activityId;
                
                // Inserisci mappatura
                $stmtIns = $modx->prepare("
                    INSERT INTO kos_medici_prestazioni (resourceid, activity_id, areaid, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE updated_at = NOW()
                ");
                $stmtIns->execute([$resourceId, $activityId, $areaid]);
                $countMappature++;
            }
            
            // Elimina mappature obsolete
            $countEliminate = 0;
            if (!empty($prestazioniIds)) {
                $placeholders = implode(',', array_fill(0, count($prestazioniIds), '?'));
                $stmtDel = $modx->prepare("
                    DELETE FROM kos_medici_prestazioni 
                    WHERE resourceid = ? AND areaid = ? AND activity_id NOT IN ({$placeholders})
                ");
                $stmtDel->execute(array_merge([$resourceId, $areaid], $prestazioniIds));
                $countEliminate = $stmtDel->rowCount();
            }
            
            // Aggiorna stato a completed
            $stmtUpd = $modx->prepare("UPDATE kos_sync_status SET sync_status = 'completed', last_sync = NOW() WHERE resourceid = ? AND areaid = ?");
            $stmtUpd->execute([$resourceId, $areaid]);
            
            $stats['processati']++;
            $stats['mappature'] += $countMappature;
            
            $logs[] = ['msg' => "{$nome}: +{$countMappature} mappature" . ($countEliminate > 0 ? ", -{$countEliminate} eliminate" : ""), 'type' => 'success'];
            
            usleep(200000); // 200ms delay
            
        } catch (Exception $e) {
            $stats['errori']++;
            $stmtErr = $modx->prepare("UPDATE kos_sync_status SET sync_status = 'error', last_sync = NOW() WHERE resourceid = ? AND areaid = ?");
            $stmtErr->execute([$resourceId, $areaid]);
            $logs[] = ['msg' => "{$nome}: ERRORE - " . $e->getMessage(), 'type' => 'error'];
        }
    }
}

// Ricalcola statistiche
$stmt = $modx->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN sync_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN sync_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN sync_status = 'error' THEN 1 ELSE 0 END) as errors
    FROM kos_sync_status
");
$finalStats = $stmt->fetch(PDO::FETCH_ASSOC);

$percent = $finalStats['total'] > 0 ? round(($finalStats['completed'] / $finalStats['total']) * 100, 1) : 0;

// Output HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sync Mappatura</title>
    <style>
        body { font-family: system-ui; max-width: 800px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #1976D2; }
        .card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .progress { background: #e0e0e0; border-radius: 10px; height: 30px; overflow: hidden; }
        .progress-bar { height: 100%; background: #4CAF50; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 15px 0; }
        .stat { text-align: center; padding: 15px; background: #E3F2FD; border-radius: 8px; }
        .stat b { display: block; font-size: 1.5em; color: #1976D2; }
        .stat.ok b { color: #4CAF50; }
        .stat.warn b { color: #FF9800; }
        .stat.err b { color: #F44336; }
        .log { background: #263238; color: #ECEFF1; padding: 15px; border-radius: 8px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .log .success { color: #A5D6A7; }
        .log .error { color: #EF9A9A; }
        .log .info { color: #81D4FA; }
        .btn { display: inline-block; padding: 15px 30px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; }
        .alert-ok { background: #E8F5E9; padding: 15px; border-radius: 8px; border-left: 4px solid #4CAF50; }
        .alert-warn { background: #FFF3E0; padding: 15px; border-radius: 8px; border-left: 4px solid #FF9800; }
    </style>
</head>
<body>
<h1>???? Sync Mappatura</h1>

<div class="card">
    <h3>???? Progresso: ' . $percent . '%</h3>
    <div class="progress"><div class="progress-bar" style="width:' . $percent . '%"></div></div>
    <div class="stats">
        <div class="stat"><b>' . $finalStats['total'] . '</b>Totale</div>
        <div class="stat ok"><b>' . $finalStats['completed'] . '</b>Completati</div>
        <div class="stat warn"><b>' . $finalStats['pending'] . '</b>In coda</div>
        <div class="stat err"><b>' . $finalStats['errors'] . '</b>Errori</div>
    </div>
</div>

<div class="card">
    <h3>⚡ Batch eseguito</h3>
    <p>Medici processati: <strong>' . $stats['processati'] . '</strong> | Mappature: <strong>' . $stats['mappature'] . '</strong></p>
</div>

<div class="card">
    <h3>???? Log</h3>
    <div class="log">';

foreach ($logs as $l) {
    $html .= '<div class="' . $l['type'] . '">' . htmlspecialchars($l['msg']) . '</div>';
}

$html .= '</div>
</div>

<div class="card">';

if ($finalStats['pending'] > 0) {
    $html .= '<div class="alert-warn"><strong>⏳ ' . $finalStats['pending'] . ' medici in attesa</strong></div>
    <p style="margin-top:15px">Ricarica la pagina (F5) per processare altri 30 medici:</p>
    <a href="" class="btn">???? Processa altro batch</a>';
} else {
    $html .= '<div class="alert-ok"><strong>✅ Sync completata!</strong> Tutti i medici processati.</div>';
}

$html .= '</div>
</body>
</html>';

return $html;