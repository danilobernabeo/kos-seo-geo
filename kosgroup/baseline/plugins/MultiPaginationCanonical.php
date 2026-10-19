<?php
// Nome del plugin: MultiPaginationCanonical
// Eventi: OnWebPagePrerender

$eventName = $modx->event->name;

switch($eventName) {
    case 'OnWebPagePrerender':

        // Ottieni l'ID della risorsa corrente
        $currentResourceId = $modx->resource->get('id');
        
        // Configura le pagine con paginazione e i rispettivi titoli
        $paginationPages = [
            30 => [
                'section' => 'News',
                'title_format' => 'News - Pagina %d | Gruppo KOS' 
            ],
            31 => [
                'section' => 'Approfondimenti',
                'title_format' => 'Approfondimenti - Pagina %d | Gruppo KOS' 
            ],
            32 => [
                'section' => 'Eventi e convegni',
                'title_format' => 'Eventi e convegni- Pagina %d | Gruppo KOS'
            ],
            34=> [
                'section' => 'Video',
                'title_format' => 'Video - Pagina %d | Gruppo KOS'
            ],
            33 => [
                'section' => 'Comunicati stampa',
                'title_format' => 'Comunicati stampa - Pagina %d | Gruppo KOS'
            ]
            // Aggiungi altre pagine qui secondo necessità
        ];
        
        // Verifica se la pagina corrente è in configurazione
        if (array_key_exists($currentResourceId, $paginationPages)) {
            $output = &$modx->resource->_output;
            $page = (int)($_GET['page'] ?? 1);
            $pageConfig = $paginationPages[$currentResourceId];
            
            // URL corrente completo
            $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
            $pageUrl = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            
            // Modifica il canonical
            $pattern = '/<link rel="canonical"[^>]*>/i';
            $replacement = '<link rel="canonical" href="'.$pageUrl.'">';
            $output = preg_replace($pattern, $replacement, $output);
            
            // Se non trova un canonical esistente, lo aggiunge nell'head
            if (!preg_match($pattern, $output)) {
                $headPattern = '/<\/head>/i';
                $canonicalTag = '<link rel="canonical" href="'.$pageUrl.'">' . PHP_EOL . '</head>';
                $output = preg_replace($headPattern, $canonicalTag, $output);
            }
            
            // Modifica il title solo se siamo in una pagina di paginazione successiva alla prima
            if ($page > 1) {
                $pageTitle = sprintf($pageConfig['title_format'], $page);
                $pattern = '/<title>(.*?)<\/title>/i';
                $replacement = '<title>' . $pageTitle . '</title>';
                $output = preg_replace($pattern, $replacement, $output);
            }
            
            // Opzionale: Aggiunta meta robots index per le pagine di paginazione (esclusa la prima)
            if ($page > 1) {
                $robotsPattern = '/<meta name="robots"[^>]*>/i';
                $robotsReplacement = '    <meta name="robots" content="index, follow">';
                
                if (preg_match($robotsPattern, $output)) {
                    // Sostituisce meta robots esistente
                    $output = preg_replace($robotsPattern, $robotsReplacement, $output);
                } else {
                    // Aggiunge meta robots dopo il tag title
                    $titlePattern = '/<\/title>/i';
                    $output = preg_replace($titlePattern, '</title>' . PHP_EOL . $robotsReplacement, $output);
                }
            }
        }
        break;
}
return;