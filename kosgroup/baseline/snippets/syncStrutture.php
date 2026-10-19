<?php
/**
 * ============================================================
 * SNIPPET: syncStruttureManuale
 * ============================================================
 * Importa strutture da JSON copiato manualmente dal browser
 * 
 * L'API /areas richiede sessione browser, quindi:
 * 1. Apri il link API in una nuova tab
 * 2. Copia tutto il JSON
 * 3. Incolla nel textarea
 * 4. Clicca Importa
 * 
 * Snippet MODX: [[!syncStruttureManuale]]
 * ============================================================
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$apiUrl = 'https://app.tuotempo.com/api/v3/tt_kosgroup_portale/areas';

// Processa POST se presente
$result = null;
$stats = ['inserted' => 0, 'updated' => 0, 'errors' => 0, 'total' => 0];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['json_data'])) {
    $jsonData = trim($_POST['json_data']);
    
    // Prova a decodificare
    $data = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $result = 'error';
        $messages[] = "❌ Errore JSON: " . json_last_error_msg();
    } elseif (!isset($data['return']['results'])) {
        $result = 'error';
        $messages[] = "❌ Formato JSON non valido. Assicurati di copiare la risposta completa dell'API.";
    } else {
        $areas = $data['return']['results'];
        $stats['total'] = count($areas);
        
        if (empty($areas)) {
            $result = 'error';
            $messages[] = "❌ Nessuna struttura trovata nel JSON.";
        } else {
            // Processa ogni struttura
            foreach ($areas as $area) {
                $areaid = $area['areaid'] ?? null;
                if (!$areaid) {
                    $stats['errors']++;
                    continue;
                }
                
                $nome = $area['areaTitle'] ?? 'N/A';
                $citta = $area['city'] ?? null;
                $indirizzo = $area['address'] ?? null;
                $telefono = $area['landline'] ?? $area['mobile'] ?? null;
                $isHidden = ($area['isHidden'] ?? '0') == '1' ? 1 : 0;
                $memberName = $area['member_name'] ?? null;
                
                try {
                    // Verifica se esiste
                    $stmtCheck = $modx->prepare("SELECT id FROM kos_strutture WHERE areaid = ?");
                    $stmtCheck->execute([$areaid]);
                    $exists = $stmtCheck->fetch();
                    
                    if ($exists) {
                        // UPDATE
                        $sql = "UPDATE kos_strutture SET 
                                    nome = ?, citta = ?, indirizzo = ?, telefono = ?, 
                                    is_hidden = ?, member_name = ?, updated_at = NOW()
                                WHERE areaid = ?";
                        $stmt = $modx->prepare($sql);
                        $stmt->execute([$nome, $citta, $indirizzo, $telefono, $isHidden, $memberName, $areaid]);
                        $stats['updated']++;
                        $messages[] = "↻ Aggiornata: $nome";
                    } else {
                        // INSERT
                        $sql = "INSERT INTO kos_strutture 
                                    (areaid, nome, citta, indirizzo, telefono, is_hidden, member_name, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                        $stmt = $modx->prepare($sql);
                        $stmt->execute([$areaid, $nome, $citta, $indirizzo, $telefono, $isHidden, $memberName]);
                        $stats['inserted']++;
                        $messages[] = "✚ Inserita: $nome";
                    }
                    
                } catch (PDOException $e) {
                    $stats['errors']++;
                    $messages[] = "❌ Errore $nome: " . $e->getMessage();
                }
            }
            
            $result = ($stats['errors'] === 0) ? 'success' : 'warning';
        }
    }
}

// Conta strutture attuali nel DB
$currentCount = 0;
try {
    $stmt = $modx->query("SELECT COUNT(*) FROM kos_strutture");
    $currentCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

ob_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Strutture TuoTempo</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px; 
            margin: 20px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #1976D2; margin-bottom: 10px; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin: 15px 0;
            padding: 15px;
            background: #FAFAFA;
            border-radius: 8px;
        }
        .step-number {
            background: #1976D2;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-content { flex: 1; }
        .step-content h3 { margin: 0 0 8px 0; }
        .step-content p { margin: 0; color: #666; }
        
        .api-link {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px 0;
        }
        .api-link:hover { opacity: 0.9; }
        
        textarea {
            width: 100%;
            height: 200px;
            font-family: monospace;
            font-size: 12px;
            padding: 12px;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            resize: vertical;
        }
        textarea:focus {
            outline: none;
            border-color: #1976D2;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover { background: #43A047; }
        .btn:disabled { background: #CCC; cursor: not-allowed; }
        .btn-secondary { background: #1976D2; }
        .btn-secondary:hover { background: #1565C0; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-success { background: #E8F5E9; border-left: 4px solid #4CAF50; }
        .alert-error { background: #FFEBEE; border-left: 4px solid #F44336; }
        .alert-warning { background: #FFF3E0; border-left: 4px solid #FF9800; }
        .alert-info { background: #E3F2FD; border-left: 4px solid #2196F3; }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat {
            text-align: center;
            padding: 15px;
            background: #F5F5F5;
            border-radius: 8px;
        }
        .stat-value { font-size: 2em; font-weight: bold; color: #1976D2; }
        .stat-label { color: #666; font-size: 0.9em; }
        
        .log-container {
            background: #263238;
            color: #ECEFF1;
            padding: 15px;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 13px;
        }
        .log-container div { margin: 3px 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #EEE; }
        th { background: #F5F5F5; }
        .hidden-row { color: #999; }
    </style>
</head>
<body>

<h1>???? Sync Strutture TuoTempo</h1>
<p style="color:#666;">Importazione manuale delle strutture dal portale TuoTempo</p>

<?php if ($result === 'success'): ?>
<div class="alert alert-success">
    <strong>✅ Importazione completata con successo!</strong>
</div>
<?php elseif ($result === 'error'): ?>
<div class="alert alert-error">
    <strong>❌ Errore durante l'importazione</strong>
</div>
<?php elseif ($result === 'warning'): ?>
<div class="alert alert-warning">
    <strong>⚠️ Importazione completata con alcuni errori</strong>
</div>
<?php endif; ?>

<?php if ($result): ?>
<div class="card">
    <h2>???? Risultati Importazione</h2>
    <div class="stats">
        <div class="stat">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Totali</div>
        </div>
        <div class="stat" style="background:#E8F5E9;">
            <div class="stat-value" style="color:#4CAF50;"><?= $stats['inserted'] ?></div>
            <div class="stat-label">Inserite</div>
        </div>
        <div class="stat" style="background:#FFF3E0;">
            <div class="stat-value" style="color:#FF9800;"><?= $stats['updated'] ?></div>
            <div class="stat-label">Aggiornate</div>
        </div>
        <?php if ($stats['errors'] > 0): ?>
        <div class="stat" style="background:#FFEBEE;">
            <div class="stat-value" style="color:#F44336;"><?= $stats['errors'] ?></div>
            <div class="stat-label">Errori</div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($messages)): ?>
    <div class="log-container">
        <?php foreach ($messages as $msg): ?>
        <div><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="/sync-base.html" class="btn btn-secondary">➡️ Procedi con Sync Base</a>
        <a href="/sync-strutture.html" class="btn" style="background:#607D8B;">???? Importa altre</a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2>???? Istruzioni</h2>
    
    <div class="step">
        <div class="step-number">1</div>
        <div class="step-content">
            <h3>Apri l'API in una nuova tab</h3>
            <p>Clicca il pulsante qui sotto per aprire l'endpoint API in una nuova finestra del browser:</p>
            <a href="<?= $apiUrl ?>" target="_blank" class="api-link">???? Apri API Areas</a>
        </div>
    </div>
    
    <div class="step">
        <div class="step-number">2</div>
        <div class="step-content">
            <h3>Copia il JSON</h3>
            <p>Nella nuova tab vedrai il JSON. Seleziona tutto (<strong>Ctrl+A</strong>) e copia (<strong>Ctrl+C</strong>).</p>
        </div>
    </div>
    
    <div class="step">
        <div class="step-number">3</div>
        <div class="step-content">
            <h3>Incolla qui sotto e importa</h3>
            <form method="POST">
                <textarea name="json_data" placeholder='Incolla qui il JSON copiato (es: {"result":"OK","return":{"results":[...]}})' required><?= isset($_POST['json_data']) ? htmlspecialchars($_POST['json_data']) : '' ?></textarea>
                <button type="submit" class="btn">???? Importa Strutture</button>
            </form>
        </div>
    </div>
</div>

<?php
// Mostra strutture attuali nel DB
try {
    $stmt = $modx->query("SELECT areaid, nome, citta, is_hidden, updated_at FROM kos_strutture ORDER BY nome");
    $strutture = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($strutture)):
?>
<div class="card">
    <h2>???? Strutture nel Database (<?= count($strutture) ?>)</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Città</th>
                <th>Stato</th>
                <th>Aggiornato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($strutture as $s): ?>
            <tr class="<?= $s['is_hidden'] ? 'hidden-row' : '' ?>">
                <td><?= htmlspecialchars($s['nome']) ?></td>
                <td><?= htmlspecialchars($s['citta'] ?? '-') ?></td>
                <td><?= $s['is_hidden'] ? '???? Nascosta' : '✅ Attiva' ?></td>
                <td><?= date('d/m/Y H:i', strtotime($s['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php 
    endif;
} catch (Exception $e) {}
?>

<div class="card" style="background: #E3F2FD;">
    <h3>???? Perché serve l'importazione manuale?</h3>
    <p>L'endpoint <code>/areas</code> di TuoTempo richiede una sessione browser valida. 
    Le chiamate dirette dal server (cURL) restituiscono errore 403.</p>
    <p>Questo passaggio è necessario <strong>solo per le strutture</strong>. 
    Gli altri endpoint (prestazioni, medici) funzionano normalmente.</p>
</div>

</body>
</html>
<?php
return ob_get_clean();