<?php
/**
 * ============================================================
 * SNIPPET: syncBase (v3 - Con eliminazione obsoleti)
 * ============================================================
 * - Importa SOLO dati visibili/prenotabili
 * - ELIMINA prestazioni/medici che non esistono più nell'API
 * 
 * Logica eliminazione:
 * 1. Prima della sync: segna tutti i record come "da verificare" (updated_at vecchio)
 * 2. Durante la sync: aggiorna updated_at dei record trovati
 * 3. Dopo la sync: elimina record con updated_at vecchio (non più nell'API)
 * ============================================================
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

define('KOS_API_BASE', 'https://app.tuotempo.com/api/v3');
define('KOS_PORTAL', 'tt_kosgroup_portale');

$filterAreaid = $modx->getOption('areaid', $scriptProperties, $_GET['areaid'] ?? null);
$dryRun = isset($_GET['dryrun']); // Se true, mostra cosa eliminerebbe senza farlo

$logMessages = [];
function addLog($msg, $type = 'info') {
    global $logMessages;
    $logMessages[] = ['time' => date('H:i:s'), 'msg' => $msg, 'type' => $type];
}

function sanitizeSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

function callTuoTempoAPI($endpoint, $params = []) {
    $url = KOS_API_BASE . '/' . KOS_PORTAL . '/' . $endpoint;
    if (!empty($params)) $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_3,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) throw new Exception("cURL: {$error}");
    if ($httpCode !== 200) throw new Exception("HTTP {$httpCode}");
    
    $data = json_decode($response, true);
    if (!$data) throw new Exception("JSON parse error");
    
    return $data;
}

// ============================================================
// ESECUZIONE
// ============================================================

$startTime = microtime(true);
$syncTimestamp = date('Y-m-d H:i:s'); // Timestamp unico per questa sync

$stats = [
    'strutture_processate' => 0,
    'prestazioni_inserite' => 0,
    'prestazioni_aggiornate' => 0,
    'prestazioni_scartate' => 0,
    'prestazioni_eliminate' => 0,
    'branche_inserite' => 0,
    'branche_eliminate' => 0,
    'medici_inseriti' => 0,
    'medici_aggiornati' => 0,
    'medici_scartati' => 0,
    'medici_eliminati' => 0,
    'sync_status_creati' => 0,
    'errors' => 0
];

$deletedItems = [
    'prestazioni' => [],
    'medici' => [],
    'branche' => []
];

try {
    addLog("=== INIZIO SYNC BASE v3 (con eliminazione obsoleti) ===", 'info');
    if ($dryRun) {
        addLog("⚠️ MODALITÀ DRY-RUN: nessuna eliminazione effettiva", 'warning');
    }
    
    // Recupera strutture
    $sql = "SELECT areaid, nome FROM kos_strutture WHERE IFNULL(is_hidden, 0) = 0";
    $params = [];
    
    if ($filterAreaid) {
        $sql .= " AND areaid = ?";
        $params[] = $filterAreaid;
    }
    $sql .= " ORDER BY nome";
    
    $stmt = $modx->prepare($sql);
    $stmt->execute($params);
    $strutture = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($strutture)) {
        throw new Exception("Nessuna struttura trovata. Esegui prima syncStrutture.");
    }
    
    $totalStrutture = count($strutture);
    addLog("Strutture da processare: {$totalStrutture}", 'info');
    
    foreach ($strutture as $idx => $struttura) {
        $areaid = $struttura['areaid'];
        $centroNome = $struttura['nome'];
        $progress = round((($idx + 1) / $totalStrutture) * 100);
        
        addLog("[{$progress}%] {$centroNome}", 'info');
        
        try {
            // ============================
            // SYNC PRESTAZIONI
            // ============================
            $response = callTuoTempoAPI('activities', ['areaid' => $areaid]);
            
            if ($response['result'] !== 'OK') {
                throw new Exception("API activities error");
            }
            
            $activities = $response['return']['results'] ?? [];
            $brancheViste = [];
            $prestazioniIds = []; // Track IDs trovati nell'API
            
            foreach ($activities as $act) {
                $activityId = $act['activityid'] ?? null;
                if (!$activityId) continue;
                
                $isHidden = ($act['isHidden'] ?? '0') == '1';
                $mopBookability = (int)($act['mopBookability'] ?? 1);
                
                // FILTRO: salta prestazioni nascoste o non prenotabili
                if ($isHidden || $mopBookability == 0) {
                    $stats['prestazioni_scartate']++;
                    continue;
                }
                
                $prestazioniIds[] = $activityId; // Traccia ID valido
                
                $typology = $act['typologyTitle'] ?? 'ALTRO';
                $typologyId = $act['typologyid'] ?? null;
                $brancaSlug = sanitizeSlug($typology);
                
                // Verifica se esiste già
                $stmtCheck = $modx->prepare("SELECT 1 FROM kos_prestazioni WHERE areaid = ? AND activity_id = ?");
                $stmtCheck->execute([$areaid, $activityId]);
                $exists = $stmtCheck->fetchColumn();
                
                // Inserisci/aggiorna prestazione
                $stmtPrest = $modx->prepare("
                    INSERT INTO kos_prestazioni 
                        (areaid, centro_nome, branca_slug, activity_id, title, typology, typologyid, price, duration, is_hidden, mop_bookability, updated_at)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?)
                    ON DUPLICATE KEY UPDATE
                        centro_nome = VALUES(centro_nome),
                        branca_slug = VALUES(branca_slug),
                        title = VALUES(title),
                        typology = VALUES(typology),
                        typologyid = VALUES(typologyid),
                        price = VALUES(price),
                        duration = VALUES(duration),
                        is_hidden = 0,
                        mop_bookability = 1,
                        updated_at = VALUES(updated_at)
                ");
                $stmtPrest->execute([
                    $areaid, $centroNome, $brancaSlug, $activityId,
                    $act['activityTitle'] ?? 'N/A', $typology, $typologyId,
                    $act['price'] ?? null, $act['duration'] ?? null,
                    $syncTimestamp
                ]);
                
                if ($exists) {
                    $stats['prestazioni_aggiornate']++;
                } else {
                    $stats['prestazioni_inserite']++;
                }
                
                // Traccia branche
                if (!isset($brancheViste[$brancaSlug])) {
                    $brancheViste[$brancaSlug] = ['branca' => $typology, 'typologyid' => $typologyId];
                }
            }
            
            // ELIMINA prestazioni obsolete (non più nell'API)
            if (!empty($prestazioniIds)) {
                $placeholders = implode(',', array_fill(0, count($prestazioniIds), '?'));
                
                // Prima trova quali eliminare
                $stmtFind = $modx->prepare("
                    SELECT activity_id, title FROM kos_prestazioni 
                    WHERE areaid = ? AND activity_id NOT IN ({$placeholders})
                ");
                $stmtFind->execute(array_merge([$areaid], $prestazioniIds));
                $toDelete = $stmtFind->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($toDelete as $del) {
                    $deletedItems['prestazioni'][] = "{$centroNome}: {$del['title']} ({$del['activity_id']})";
                }
                
                if (!$dryRun && count($toDelete) > 0) {
                    // Elimina mappature associate
                    $stmtDelMap = $modx->prepare("
                        DELETE FROM kos_medici_prestazioni 
                        WHERE areaid = ? AND activity_id NOT IN ({$placeholders})
                    ");
                    $stmtDelMap->execute(array_merge([$areaid], $prestazioniIds));
                    
                    // Elimina prestazioni
                    $stmtDel = $modx->prepare("
                        DELETE FROM kos_prestazioni 
                        WHERE areaid = ? AND activity_id NOT IN ({$placeholders})
                    ");
                    $stmtDel->execute(array_merge([$areaid], $prestazioniIds));
                    $stats['prestazioni_eliminate'] += count($toDelete);
                }
            }
            
            // Inserisci branche
            $brancheIds = [];
            foreach ($brancheViste as $slug => $info) {
                $brancheIds[] = $slug;
                $stmtBranca = $modx->prepare("
                    INSERT INTO kos_branche (areaid, centro_nome, branca, branca_slug, typologyid, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE centro_nome = VALUES(centro_nome), typologyid = VALUES(typologyid), updated_at = VALUES(updated_at)
                ");
                $stmtBranca->execute([$areaid, $centroNome, $info['branca'], $slug, $info['typologyid'], $syncTimestamp]);
                $stats['branche_inserite']++;
            }
            
            // Elimina branche obsolete
            if (!empty($brancheIds)) {
                $placeholders = implode(',', array_fill(0, count($brancheIds), '?'));
                
                $stmtFind = $modx->prepare("
                    SELECT branca FROM kos_branche 
                    WHERE areaid = ? AND branca_slug NOT IN ({$placeholders})
                ");
                $stmtFind->execute(array_merge([$areaid], $brancheIds));
                $toDeleteBranche = $stmtFind->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($toDeleteBranche as $b) {
                    $deletedItems['branche'][] = "{$centroNome}: {$b}";
                }
                
                if (!$dryRun && count($toDeleteBranche) > 0) {
                    $stmtDel = $modx->prepare("
                        DELETE FROM kos_branche 
                        WHERE areaid = ? AND branca_slug NOT IN ({$placeholders})
                    ");
                    $stmtDel->execute(array_merge([$areaid], $brancheIds));
                    $stats['branche_eliminate'] += count($toDeleteBranche);
                }
            }
            
            addLog("  Prestazioni: +{$stats['prestazioni_inserite']}, ↻{$stats['prestazioni_aggiornate']}, -{$stats['prestazioni_eliminate']}", 'success');
            
            // ============================
            // SYNC MEDICI
            // ============================
            $response = callTuoTempoAPI('resources', ['areaid' => $areaid]);
            
            if ($response['result'] !== 'OK') {
                throw new Exception("API resources error");
            }
            
            $resources = $response['return']['results'] ?? [];
            $mediciIds = [];
            
            foreach ($resources as $res) {
                $resourceId = $res['resourceid'] ?? $res['machid'] ?? null;
                if (!$resourceId) continue;
                
                $isHidden = ($res['isHidden'] ?? '0') == '1';
                
                // FILTRO: salta medici nascosti
                if ($isHidden) {
                    $stats['medici_scartati']++;
                    continue;
                }
                
                $mediciIds[] = $resourceId;
                
                $nome = $res['resourceName'] ?? $res['name'] ?? 'N/A';
                $memberName = $res['member_name'] ?? null;
                
                // Verifica se esiste
                $stmtCheck = $modx->prepare("SELECT 1 FROM kos_medici WHERE resourceid = ? AND areaid = ?");
                $stmtCheck->execute([$resourceId, $areaid]);
                $exists = $stmtCheck->fetchColumn();
                
                // Inserisci/aggiorna medico
                $stmtMedico = $modx->prepare("
                    INSERT INTO kos_medici (resourceid, areaid, nome, note, cv_link, face_img_link, is_hidden, member_name, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        nome = VALUES(nome), note = VALUES(note), cv_link = VALUES(cv_link),
                        face_img_link = VALUES(face_img_link), is_hidden = 0, member_name = VALUES(member_name), updated_at = VALUES(updated_at)
                ");
                $stmtMedico->execute([
                    $resourceId, $areaid, $nome,
                    $res['notes'] ?? null, $res['cv_link'] ?? null, $res['face_img_link'] ?? null, 
                    $memberName, $syncTimestamp
                ]);
                
                if ($exists) {
                    $stats['medici_aggiornati']++;
                } else {
                    $stats['medici_inseriti']++;
                }
                
                // Crea entry per sync incrementale
                $stmtSync = $modx->prepare("
                    INSERT INTO kos_sync_status (resourceid, areaid, resource_name, area_name, sync_status, created_at)
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                    ON DUPLICATE KEY UPDATE resource_name = VALUES(resource_name), area_name = VALUES(area_name)
                ");
                $stmtSync->execute([$resourceId, $areaid, $nome, $centroNome]);
                $stats['sync_status_creati']++;
            }
            
            // Elimina medici obsoleti
            if (!empty($mediciIds)) {
                $placeholders = implode(',', array_fill(0, count($mediciIds), '?'));
                
                $stmtFind = $modx->prepare("
                    SELECT resourceid, nome FROM kos_medici 
                    WHERE areaid = ? AND resourceid NOT IN ({$placeholders})
                ");
                $stmtFind->execute(array_merge([$areaid], $mediciIds));
                $toDelete = $stmtFind->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($toDelete as $del) {
                    $deletedItems['medici'][] = "{$centroNome}: {$del['nome']} ({$del['resourceid']})";
                }
                
                if (!$dryRun && count($toDelete) > 0) {
                    // Elimina mappature associate
                    $stmtDelMap = $modx->prepare("
                        DELETE FROM kos_medici_prestazioni 
                        WHERE areaid = ? AND resourceid NOT IN ({$placeholders})
                    ");
                    $stmtDelMap->execute(array_merge([$areaid], $mediciIds));
                    
                    // Elimina da sync_status
                    $stmtDelSync = $modx->prepare("
                        DELETE FROM kos_sync_status 
                        WHERE areaid = ? AND resourceid NOT IN ({$placeholders})
                    ");
                    $stmtDelSync->execute(array_merge([$areaid], $mediciIds));
                    
                    // Elimina medici
                    $stmtDel = $modx->prepare("
                        DELETE FROM kos_medici 
                        WHERE areaid = ? AND resourceid NOT IN ({$placeholders})
                    ");
                    $stmtDel->execute(array_merge([$areaid], $mediciIds));
                    $stats['medici_eliminati'] += count($toDelete);
                }
            }
            
            addLog("  Medici: +{$stats['medici_inseriti']}, ↻{$stats['medici_aggiornati']}, -{$stats['medici_eliminati']}", 'success');
            
            $stats['strutture_processate']++;
            usleep(300000);
            
        } catch (Exception $e) {
            $stats['errors']++;
            addLog("  ERRORE: " . $e->getMessage(), 'error');
        }
    }
    
    addLog("=== SYNC BASE COMPLETATO ===", 'success');
    
} catch (Exception $e) {
    addLog("ERRORE CRITICO: " . $e->getMessage(), 'error');
    $stats['errors']++;
}

$executionTime = round(microtime(true) - $startTime, 2);

// ============================================================
// OUTPUT HTML
// ============================================================
ob_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Sync Base TuoTempo</title>
    <style>
        body { font-family: system-ui; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #1976D2; }
        .card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; }
        .stat { text-align: center; padding: 12px 8px; background: #E3F2FD; border-radius: 8px; }
        .stat b { display: block; font-size: 1.5em; color: #1976D2; }
        .stat small { font-size: 0.75em; color: #666; }
        .stat.add { background: #E8F5E9; }
        .stat.add b { color: #4CAF50; }
        .stat.del { background: #FFEBEE; }
        .stat.del b { color: #F44336; }
        .stat.skip { background: #FFF3E0; }
        .stat.skip b { color: #FF9800; }
        .log { background: #263238; color: #ECEFF1; padding: 15px; border-radius: 8px; max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .log .success { color: #A5D6A7; }
        .log .error { color: #EF9A9A; }
        .log .warning { color: #FFE082; }
        .log .info { color: #81D4FA; }
        .deleted-list { background: #FFEBEE; padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto; font-size: 13px; }
        .deleted-list li { margin: 5px 0; color: #C62828; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; margin: 5px; font-weight: bold; }
        .btn:hover { opacity: 0.9; }
        .btn-blue { background: #1976D2; }
        .btn-orange { background: #FF9800; }
        .alert { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .alert-success { background: #E8F5E9; border-left: 4px solid #4CAF50; }
        .alert-warning { background: #FFF3E0; border-left: 4px solid #FF9800; }
    </style>
</head>
<body>

<h1>???? Sync Base v3 - Con Pulizia Obsoleti</h1>

<?php if ($dryRun): ?>
<div class="alert alert-warning">
    <strong>⚠️ MODALITÀ DRY-RUN</strong> - Nessuna eliminazione effettuata. Rimuovi <code>?dryrun</code> dall'URL per eseguire realmente.
</div>
<?php endif; ?>

<div class="alert alert-<?= $stats['errors'] === 0 ? 'success' : 'warning' ?>">
    <strong><?= $stats['errors'] === 0 ? '✅ Completato!' : '⚠️ Completato con errori' ?></strong>
    - Tempo: <?= $executionTime ?>s
</div>

<div class="card">
    <h3>???? Risultati</h3>
    <div class="stats">
        <div class="stat"><b><?= $stats['strutture_processate'] ?></b><small>Strutture</small></div>
        <div class="stat add"><b>+<?= $stats['prestazioni_inserite'] ?></b><small>Prest. Nuove</small></div>
        <div class="stat"><b>↻<?= $stats['prestazioni_aggiornate'] ?></b><small>Prest. Agg.</small></div>
        <div class="stat skip"><b><?= $stats['prestazioni_scartate'] ?></b><small>Prest. Skip</small></div>
        <div class="stat del"><b>-<?= $stats['prestazioni_eliminate'] ?></b><small>Prest. Elim.</small></div>
        <div class="stat add"><b>+<?= $stats['medici_inseriti'] ?></b><small>Medici Nuovi</small></div>
        <div class="stat"><b>↻<?= $stats['medici_aggiornati'] ?></b><small>Medici Agg.</small></div>
        <div class="stat skip"><b><?= $stats['medici_scartati'] ?></b><small>Medici Skip</small></div>
        <div class="stat del"><b>-<?= $stats['medici_eliminati'] ?></b><small>Medici Elim.</small></div>
    </div>
</div>

<?php 
$totalDeleted = count($deletedItems['prestazioni']) + count($deletedItems['medici']) + count($deletedItems['branche']);
if ($totalDeleted > 0): 
?>
<div class="card">
    <h3>????️ Elementi <?= $dryRun ? 'da eliminare' : 'eliminati' ?> (<?= $totalDeleted ?>)</h3>
    
    <?php if (!empty($deletedItems['prestazioni'])): ?>
    <h4>Prestazioni (<?= count($deletedItems['prestazioni']) ?>)</h4>
    <ul class="deleted-list">
        <?php foreach ($deletedItems['prestazioni'] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    
    <?php if (!empty($deletedItems['medici'])): ?>
    <h4>Medici (<?= count($deletedItems['medici']) ?>)</h4>
    <ul class="deleted-list">
        <?php foreach ($deletedItems['medici'] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    
    <?php if (!empty($deletedItems['branche'])): ?>
    <h4>Branche (<?= count($deletedItems['branche']) ?>)</h4>
    <ul class="deleted-list">
        <?php foreach ($deletedItems['branche'] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <h3>???? Log</h3>
    <div class="log">
        <?php foreach ($logMessages as $l): ?>
        <div class="<?= $l['type'] ?>">[<?= $l['time'] ?>] <?= htmlspecialchars($l['msg']) ?></div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h3>???? Azioni</h3>
    <?php if ($dryRun): ?>
    <a href="?<?= $filterAreaid ? "areaid={$filterAreaid}" : '' ?>" class="btn">???? Esegui Sync Reale</a>
    <?php endif; ?>
    <a href="?dryrun<?= $filterAreaid ? "&areaid={$filterAreaid}" : '' ?>" class="btn btn-orange">???? Dry-Run (anteprima)</a>
    <a href="/sync-mappatura.html?reset=1" class="btn btn-blue">➡️ Sync Mappatura</a>
</div>

</body>
</html>
<?php
return ob_get_clean();