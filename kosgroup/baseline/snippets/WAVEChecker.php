<?php
/**
 * WAVEChecker Snippet SEMPLIFICATO per MODX
 * Fix per errori database - Versione Base
 */

// Parametri - Lettura diretta da URL
$mode = isset($_GET['mode']) ? $_GET['mode'] : $modx->getOption('mode', $scriptProperties, 'dashboard');
$urlsParam = isset($_GET['urls']) ? $_GET['urls'] : $modx->getOption('urls', $scriptProperties, '');
$debug = isset($_GET['debug']) ? (int)$_GET['debug'] : (int)$modx->getOption('debug', $scriptProperties, 0);

// Debug mode
if ($debug) {
    $output = "<div style='background: #f0f0f0; padding: 15px; border: 2px solid #333; margin: 10px 0;'>";
    $output .= "<h4><i class='fas fa-bug'></i> DEBUG WAVEChecker</h4>";
    $output .= "<strong>Mode:</strong> " . htmlspecialchars($mode) . "<br>";
    $output .= "<strong>URLs:</strong> " . htmlspecialchars($urlsParam) . "<br>";
    $output .= "<strong>Script Properties:</strong><br>";
    foreach ($scriptProperties as $key => $value) {
        $output .= "  &bull; $key = " . htmlspecialchars($value) . "<br>";
    }
    $output .= "<strong>GET Parameters:</strong><br>";
    foreach ($_GET as $key => $value) {
        $output .= "  &bull; $key = " . htmlspecialchars($value) . "<br>";
    }
    $output .= "</div>";
    return $output;
}

// Verifica API Key
$apiKey = $modx->getOption('wave.api_key');
if (!$apiKey) {
    return '<div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h4><i class="fas fa-exclamation-triangle"></i> WAVE API Key Mancante</h4>
        <p><strong>Setup Richiesto:</strong></p>
        <ol>
            <li>Vai su <a href="https://wave.webaim.org/api/" target="_blank">https://wave.webaim.org/api/</a></li>
            <li>Crea account gratuito (100 test/mese)</li>
            <li>Copia la API key</li>
            <li>Manager MODX > Sistema > Impostazioni Sistema > Crea Nuova</li>
            <li><strong>Chiave:</strong> wave.api_key</li>
            <li><strong>Valore:</strong> [la tua API key WAVE]</li>
        </ol>
        <p><a href="https://wave.webaim.org/api/" target="_blank" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;"><i class="fas fa-key"></i> Ottieni API Key</a></p>
    </div>';
}

// Processa modalità
switch ($mode) {
    case 'check':
        return processCheck($urlsParam, $apiKey);
        
    case 'dashboard':
        return processDashboard();
        
    case 'report':
        return processReport();
        
    default:
        return '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <i class="fas fa-exclamation-triangle"></i> Modalità "' . htmlspecialchars($mode) . '" non riconosciuta.<br>
            <strong>Modalità disponibili:</strong> check, dashboard, report<br><br>
            <strong>Esempi URL:</strong><br>
            &bull; <a href="?mode=check&urls=' . urlencode($modx->getOption('site_url')) . '">?mode=check&urls=' . htmlspecialchars($modx->getOption('site_url')) . '</a><br>
            &bull; <a href="?mode=dashboard">?mode=dashboard</a><br>
            &bull; <a href="?mode=report">?mode=report</a>
        </div>';
}

/**
 * Processa verifica WAVE
 */
function processCheck($urlsParam, $apiKey) {
    global $modx;
    
    if (empty($urlsParam)) {
        return '<div style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <h4><i class="fas fa-search"></i> Test Rapido</h4>
            <p>Nessun URL specificato. Testa queste pagine:</p>
            <p>
                <a href="?mode=check&urls=' . urlencode($modx->getOption('site_url')) . '" style="background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; margin-right: 10px;"><i class="fas fa-home"></i> Homepage</a>
                <a href="?mode=check&urls=' . urlencode($modx->getOption('site_url') . 'contatti') . '" style="background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px;"><i class="fas fa-phone"></i> Contatti</a>
            </p>
        </div>';
    }
    
    $urls = array_map('trim', explode(',', $urlsParam));
    $results = [];
    $totalErrors = 0;
    $totalAlerts = 0;
    
    foreach ($urls as $url) {
        if (empty($url)) continue;
        
        $result = callWaveAPI($url, $apiKey);
        
        if ($result['success']) {
            $errors = isset($result['data']['categories']['error']['count']) ? (int)$result['data']['categories']['error']['count'] : 0;
            $alerts = isset($result['data']['categories']['alert']['count']) ? (int)$result['data']['categories']['alert']['count'] : 0;
            
            $totalErrors += $errors;
            $totalAlerts += $alerts;
            
            // Priorità
            $priority = 'bassa';
            $priorityColor = '#28a745';
            if ($errors >= 10) {
                $priority = 'critica';
                $priorityColor = '#dc3545';
            } elseif ($errors >= 5) {
                $priority = 'alta';
                $priorityColor = '#fd7e14';
            } elseif ($errors >= 1) {
                $priority = 'media';
                $priorityColor = '#ffc107';
            }
            
            $results[] = [
                'url' => $url,
                'errors' => $errors,
                'alerts' => $alerts,
                'priority' => $priority,
                'priorityColor' => $priorityColor,
                'success' => true
            ];
        } else {
            $results[] = [
                'url' => $url,
                'error' => $result['error'],
                'success' => false
            ];
        }
    }
    
    return generateReport($results, $totalErrors, $totalAlerts);
}

/**
 * Chiama API WAVE
 */
function callWaveAPI($url, $apiKey) {
    $apiUrl = "https://wave.webaim.org/api/request?key=" . urlencode($apiKey) . "&url=" . urlencode($url) . "&format=json";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'MODX WAVE Checker 1.0'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Errore connessione API WAVE. Verifica connessione internet.'
        ];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Errore parsing JSON: ' . json_last_error_msg()
        ];
    }
    
    if (isset($data['status']['error'])) {
        return [
            'success' => false,
            'error' => 'WAVE API Error: ' . $data['status']['error']
        ];
    }
    
    return [
        'success' => true,
        'data' => $data
    ];
}

/**
 * Genera report HTML
 */
function generateReport($results, $totalErrors, $totalAlerts) {
    global $modx;
    
    $totalPages = count($results);
    $criticalPages = 0;
    foreach ($results as $result) {
        if ($result['success'] && isset($result['priority']) && $result['priority'] === 'critica') {
            $criticalPages++;
        }
    }
    
    $compliance = $totalPages > 0 ? round((($totalPages - $criticalPages) / $totalPages) * 100) : 0;
    $complianceColor = $compliance >= 80 ? '#28a745' : ($compliance >= 60 ? '#ffc107' : '#dc3545');
    
    $output = '
    <style>
    .wave-report { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin: 20px 0; overflow: hidden; }
    .wave-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
    .wave-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; padding: 20px; background: #f8f9fa; }
    .stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid; }
    .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
    .stat-label { color: #6c757d; font-size: 0.9em; }
    .results-table { margin: 20px; }
    .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
    .table th { background: #f8f9fa; font-weight: 600; }
    .priority-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 500; color: white; }
    .url-cell { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .actions { background: #e9ecef; padding: 20px; margin: 20px; border-radius: 8px; }
    .btn { display: inline-block; padding: 8px 16px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    .btn:hover { background: #0056b3; }
    </style>
    
    <div class="wave-report">
        <div class="wave-header">
            <h2><i class="fas fa-universal-access"></i> Report Accessibilità WAVE</h2>
            <p>Monitoraggio conformità WCAG 2.1 - ' . htmlspecialchars($modx->getOption('site_name')) . '</p>
        </div>
        
        <div class="wave-stats">
            <div class="stat-card" style="border-left-color: #dc3545;">
                <div class="stat-number" style="color: #dc3545;">' . $totalErrors . '</div>
                <div class="stat-label">Errori Totali</div>
            </div>
            <div class="stat-card" style="border-left-color: #007bff;">
                <div class="stat-number" style="color: #007bff;">' . $totalPages . '</div>
                <div class="stat-label">Pagine Verificate</div>
            </div>
            <div class="stat-card" style="border-left-color: #fd7e14;">
                <div class="stat-number" style="color: #fd7e14;">' . $criticalPages . '</div>
                <div class="stat-label">Pagine Critiche</div>
            </div>
            <div class="stat-card" style="border-left-color: ' . $complianceColor . ';">
                <div class="stat-number" style="color: ' . $complianceColor . ';">' . $compliance . '%</div>
                <div class="stat-label">Conformità</div>
            </div>
        </div>
        
        <div class="results-table">
            <h4><i class="fas fa-list"></i> Dettaglio Risultati</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-file-alt"></i> Pagina</th>
                        <th><i class="fas fa-exclamation-triangle"></i> Errori</th>
                        <th><i class="fas fa-exclamation-circle"></i> Alert</th>
                        <th><i class="fas fa-flag"></i> Priorità</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($results as $result) {
        if ($result['success']) {
            $output .= '
                    <tr>
                        <td class="url-cell"><a href="' . htmlspecialchars($result['url']) . '" target="_blank">' . htmlspecialchars($result['url']) . '</a></td>
                        <td><strong>' . $result['errors'] . '</strong></td>
                        <td>' . $result['alerts'] . '</td>
                        <td><span class="priority-badge" style="background: ' . $result['priorityColor'] . ';">' . ucfirst($result['priority']) . '</span></td>
                    </tr>';
        } else {
            $output .= '
                    <tr>
                        <td class="url-cell">' . htmlspecialchars($result['url']) . '</td>
                        <td colspan="3" style="color: #dc3545;"><i class="fas fa-times"></i> ' . htmlspecialchars($result['error']) . '</td>
                    </tr>';
        }
    }
    
    $output .= '
                </tbody>
            </table>
        </div>';
    
    // Azioni
    if ($totalErrors > 0) {
        $output .= '
        <div class="actions">
            <h4><i class="fas fa-bolt"></i> AZIONI IMMEDIATE</h4>
            <ul>
                <li><i class="fas fa-palette"></i> Verificare contrasti colori (ratio minimo 4.5:1)</li>
                <li><i class="fas fa-image"></i> Aggiungere attributi alt a immagini mancanti</li>
                <li><i class="fas fa-edit"></i> Associare label a campi form</li>
                <li><i class="fas fa-heading"></i> Correggere gerarchia heading H1 > H2 > H3</li>';
        
        if ($criticalPages > 0) {
            $output .= '<li><i class="fas fa-exclamation-triangle"></i> <strong>' . $criticalPages . ' pagine critiche</strong> richiedono fix prioritario</li>';
        }
        
        $output .= '
            </ul>
        </div>';
    } else {
        $output .= '
        <div class="actions" style="background: #d4edda; border-left: 4px solid #28a745;">
            <h4 style="color: #155724;"><i class="fas fa-check-circle"></i> CONFORMITÀ ECCELLENTE</h4>
            <p style="color: #155724;">Tutte le pagine verificate sono conformi WCAG 2.1!</p>
        </div>';
    }
    
    // Footer
    $output .= '
        <div style="padding: 15px; background: #f8f9fa; text-align: center;">
            <a href="?mode=dashboard" class="btn"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="?mode=check" class="btn"><i class="fas fa-search"></i> Nuova Verifica</a>
            <a href="' . $modx->getOption('site_url') . 'manager/" class="btn" target="_blank"><i class="fas fa-cog"></i> Manager</a>
        </div>
        
        <div style="padding: 10px 20px; background: #e9ecef; font-size: 0.9em; color: #6c757d; text-align: center;">
            Powered by WAVE WebAIM API &bull; ' . date('d/m/Y H:i') . '
        </div>
    </div>';
    
    return $output;
}

/**
 * Dashboard placeholder
 */
function processDashboard() {
    return '<div style="background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">
        <h3><i class="fas fa-chart-line"></i> Dashboard Accessibilità</h3>
        <p>Funzionalità in sviluppo. Usa la modalità <strong>check</strong> per verifiche immediate.</p>
        <p>
            <a href="?mode=check&urls=' . urlencode($_SERVER['HTTP_HOST']) . '" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                <i class="fas fa-search"></i> Esegui Verifica
            </a>
        </p>
    </div>';
}

/**
 * Report placeholder  
 */
function processReport() {
    return '<div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">
        <h3><i class="fas fa-file-alt"></i> Report Settimanale</h3>
        <p>Funzionalità in sviluppo. Usa la modalità <strong>check</strong> per verifiche immediate.</p>
        <p>
            <a href="?mode=check" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                <i class="fas fa-search"></i> Verifica Ora
            </a>
        </p>
    </div>';
}