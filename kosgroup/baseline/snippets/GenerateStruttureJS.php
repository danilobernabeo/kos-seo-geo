<?php
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $outputPath = MODX_BASE_PATH . 'assets/js/strutture.js';
    
    // Otteniamo gli ID di tutte le TV necessarie
    $tvRegione = $modx->getObject('modTemplateVar', ['name' => 'regione']);
    $tvIndirizzo = $modx->getObject('modTemplateVar', ['name' => 'indirizzo_struttura']);
    $tvTipoStruttura = $modx->getObject('modTemplateVar', ['name' => 'tipo_struttura']);
    $tvMapServizi = $modx->getObject('modTemplateVar', ['name' => 'mapServiziStrutture']);
    
    if (!$tvRegione || !$tvIndirizzo || !$tvTipoStruttura || !$tvMapServizi) {
        throw new Exception('Una o più TV non trovate');
    }
    
    $tvRegioneId = $tvRegione->get('id');
    $tvIndirizzoId = $tvIndirizzo->get('id');
    $tvTipoStrutturaId = $tvTipoStruttura->get('id');
    $tvMapServiziId = $tvMapServizi->get('id');
    
    // Query base per le strutture
    $query = $modx->newQuery('modResource');
    $query->where([
        'parent' => 167,
        'published' => 1,
        'deleted' => 0
    ]);
    $query->select(['id', 'pagetitle', 'alias']);
    
    $struttureData = [];
    $strutture = $modx->getIterator('modResource', $query);
    
    foreach ($strutture as $struttura) {
        $strutturaId = $struttura->get('id');
        
        // Otteniamo i valori delle TV per questa struttura
        $tvQuery = $modx->newQuery('modTemplateVarResource');
        $tvQuery->where([
            'contentid' => $strutturaId,
            'tmplvarid:IN' => [$tvRegioneId, $tvIndirizzoId, $tvTipoStrutturaId]
        ]);
        $tvQuery->select(['tmplvarid', 'value']);
        
        $tvValues = [];
        $tvResults = $modx->getIterator('modTemplateVarResource', $tvQuery);
        foreach ($tvResults as $tvr) {
            $tvValues[$tvr->get('tmplvarid')] = $tvr->get('value');
        }
        
        // Gestione del tipo struttura con separatore ||
        $tipiStruttura = !empty($tvValues[$tvTipoStrutturaId]) 
            ? explode('||', $tvValues[$tvTipoStrutturaId]) 
            : [];
        $tipiStruttura = array_map('trim', $tipiStruttura);
        $tipiStruttura = array_filter($tipiStruttura);
        
        // Query per ottenere i servizi associati
        $servicesQuery = $modx->newQuery('modResource');
        $servicesQuery->select(['pagetitle']);
        $servicesQuery->innerJoin('modTemplateVarResource', 'TVMap', 
            "TVMap.contentid = modResource.id AND TVMap.tmplvarid = {$tvMapServiziId}");
        $servicesQuery->where([
            'parent:IN' => [14, 15, 16],
            'published' => 1,
            'deleted' => 0,
            'TVMap.value:LIKE' => "%{$strutturaId}%"
        ]);
        
        $servizi = [];
        $stmt = $servicesQuery->prepare();
        if ($stmt && $stmt->execute()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $servizi[] = $row['pagetitle'];
            }
        }
        
        $strutturaObj = [
            'riferimento' => $strutturaId,
            'nome' => $struttura->get('pagetitle'),
            'immagine' => '/assets/img/strutture/' . $struttura->get('alias') . '.jpg',
            'regione' => $tvValues[$tvRegioneId] ?? '',
            'indirizzo' => $tvValues[$tvIndirizzoId] ?? '',
            'struttura' => $tipiStruttura,
            'servizi' => $servizi
        ];
        
        $struttureData[] = $strutturaObj;
        
        // Pulizia memoria
        unset($tvValues, $tvResults, $tipiStruttura, $servizi, $stmt);
    }
    
    // Genera il file
    $jsContent = "const strutture = " . json_encode($struttureData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";";
    file_put_contents($outputPath, $jsContent);
    
    return 'File generato con successo - Versione completa';
    
} catch (Exception $e) {
    return 'Errore: ' . $e->getMessage() . "\nLine: " . $e->getLine();
}