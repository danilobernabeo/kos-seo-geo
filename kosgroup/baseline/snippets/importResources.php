<?php
try {
    if (!$modx->user->hasSessionContext('mgr')) throw new Exception('Access denied');

    $parentId = $modx->getOption('parentId', $scriptProperties, 0);
    $templateId = $modx->getOption('templateId', $scriptProperties, 1);
    $jsonFile = MODX_ASSETS_PATH . 'exports/resources_export_' . date('Y-m-d') . '.json';
    
    if (!file_exists($jsonFile)) throw new Exception('File non trovato: ' . $jsonFile);

    $resources = json_decode(file_get_contents($jsonFile), true);
    if (!$resources) throw new Exception('Errore nel parsing del JSON');

    $sourceImagesPath = MODX_ASSETS_PATH . 'exports/images/';
    $targetImagesPath = MODX_ASSETS_PATH . 'img/approfondimenti/';
    if (!is_dir($targetImagesPath)) mkdir($targetImagesPath, 0777, true);

    $imported = 0;
    
    foreach ($resources as $data) {
        if (!empty($data['newsImage'])) {
            $sourcePath = $sourceImagesPath . basename($data['newsImage']);
            $targetPath = $targetImagesPath . basename($data['newsImage']);
            if (file_exists($sourcePath)) {
                copy($sourcePath, $targetPath);
                $data['newsImage'] = 'assets/img/approfondimenti/' . basename($data['newsImage']);
            }
        }

        $resource = $modx->newObject('modResource');
        $resource->fromArray([
            'pagetitle' => $data['pagetitle'],
            'longtitle' => $data['longtitle'],
            'description' => $data['description'],
            'alias' => $data['alias'],
            'published' => 1,
            'parent' => $parentId,
            'template' => $templateId,
            'publishedon' => strtotime($data['published_date']),
            'content' => $data['content'],
            'introtext' => $data['introtext'],
        ]);
        
        if ($resource->save() && !empty($data['newsImage'])) {
            $tv = $modx->getObject('modTemplateVar', ['name' => 'infoImage']);
            if ($tv) {
                $tvr = $modx->newObject('modTemplateVarResource');
                $tvr->set('tmplvarid', $tv->get('id'));
                $tvr->set('contentid', $resource->get('id'));
                $tvr->set('value', $data['newsImage']);
                $tvr->save();
            }
        }
        $imported++;
    }

    return "Importate $imported risorse";

} catch (Exception $e) {
    return 'Errore: ' . $e->getMessage();
}