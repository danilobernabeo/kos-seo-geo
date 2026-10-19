<?php
// Parametro per il dry run - può essere impostato quando si chiama lo snippet
$dryRun = $modx->getOption('dryRun', $scriptProperties, true); // Default a true per sicurezza

// Array per salvare i risultati dettagliati
$report = [];

// Query per ottenere le correlazioni
$query = "
SELECT 
    child140.id AS tecnologia_id,
    child140.pagetitle AS tecnologia_nome,
    GROUP_CONCAT(child167.id) AS risorse_correlate
FROM 
    modx_site_content child140
LEFT JOIN 
    modx_site_tmplvar_contentvalues tv 
    ON FIND_IN_SET(child140.id, tv.value)
LEFT JOIN 
    modx_site_content child167 
    ON child167.id = tv.contentid
WHERE 
    child140.parent = 140
    AND tv.tmplvarid = (
        SELECT id 
        FROM modx_site_tmplvars 
        WHERE name = 'mapTecnologie'
    )
    AND child167.parent = 167
GROUP BY 
    child140.id
ORDER BY 
    child140.pagetitle";

// Esegui la query
$result = $modx->query($query);

if (!$result) {
    return 'Error executing query';
}

// Ottieni l'ID della template variable mapServiziStrutture
$tvQuery = $modx->query("SELECT id FROM modx_site_tmplvars WHERE name = 'mapServiziStrutture'");
if (!$tvQuery) {
    return 'Error: cannot find mapServiziStrutture template variable';
}
$tvId = $tvQuery->fetch(PDO::FETCH_COLUMN);

// Contatori per il report
$updates = 0;
$inserts = 0;
$errors = 0;

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $tecnologiaId = $row['tecnologia_id'];
    $tecnologiaNome = $row['tecnologia_nome'];
    $strutture = $row['risorse_correlate'] ?: ''; // Se null, usa stringa vuota
    
    // Controlla il valore attuale
    $checkQuery = $modx->query("
        SELECT value 
        FROM modx_site_tmplvar_contentvalues 
        WHERE tmplvarid = {$tvId} 
        AND contentid = {$tecnologiaId}
    ");
    
    $currentValue = $checkQuery->fetch(PDO::FETCH_COLUMN);
    $action = $currentValue !== false ? 'UPDATE' : 'INSERT';
    
    $reportEntry = [
        'tecnologia_id' => $tecnologiaId,
        'tecnologia_nome' => $tecnologiaNome,
        'action' => $action,
        'old_value' => $currentValue,
        'new_value' => $strutture
    ];
    
    if (!$dryRun) {
        if ($action === 'UPDATE') {
            $updateQuery = $modx->query("
                UPDATE modx_site_tmplvar_contentvalues 
                SET value = '{$strutture}'
                WHERE tmplvarid = {$tvId} 
                AND contentid = {$tecnologiaId}
            ");
            if ($updateQuery) $updates++;
            else $errors++;
        } else {
            $updateQuery = $modx->query("
                INSERT INTO modx_site_tmplvar_contentvalues 
                (tmplvarid, contentid, value) 
                VALUES ({$tvId}, {$tecnologiaId}, '{$strutture}')
            ");
            if ($updateQuery) $inserts++;
            else $errors++;
        }
        $reportEntry['success'] = $updateQuery !== false;
    }
    
    $report[] = $reportEntry;
}

// Se non è un dry run, pulisci la cache
if (!$dryRun) {
    $modx->cacheManager->refresh();
}

// Prepara il report dettagliato
$output = $dryRun ? "DRY RUN - Nessuna modifica effettuata\n\n" : "ESECUZIONE REALE\n\n";
$output .= "Riepilogo modifiche previste:\n";
$output .= "- Updates pianificati: " . count(array_filter($report, function($r) { return $r['action'] === 'UPDATE'; })) . "\n";
$output .= "- Inserimenti pianificati: " . count(array_filter($report, function($r) { return $r['action'] === 'INSERT'; })) . "\n\n";

if (!$dryRun) {
    $output .= "Risultati effettivi:\n";
    $output .= "- Updates completati: {$updates}\n";
    $output .= "- Inserimenti completati: {$inserts}\n";
    $output .= "- Errori: {$errors}\n\n";
}

$output .= "Dettaglio modifiche:\n";
foreach ($report as $entry) {
    $output .= "-------------------\n";
    $output .= "Tecnologia: {$entry['tecnologia_nome']} (ID: {$entry['tecnologia_id']})\n";
    $output .= "Azione: {$entry['action']}\n";
    $output .= "Valore attuale: " . ($entry['old_value'] ?: 'nessuno') . "\n";
    $output .= "Nuovo valore: " . ($entry['new_value'] ?: 'nessuno') . "\n";
    if (!$dryRun) {
        $output .= "Esito: " . ($entry['success'] ? 'Successo' : 'Errore') . "\n";
    }
}

return nl2br($output);