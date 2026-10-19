<?php
/**
 * Nome del plugin: PaginationCanonical
 * Descrizione: Gestisce canonical e title per pagine con paginazione pdoPage
 * Eventi: OnWebPagePrerender
 */

$eventName = $modx->event->name;

switch($eventName) {
    case 'OnWebPagePrerender':
        // ID della risorsa corrente
        $currentResourceId = $modx->resource->get('id');
        
        // Configura le pagine con paginazione
        $paginationPages = [
            8 => [  // ID della tua pagina News
                'title_format' => 'News - Pagina %d | Anni Azzurri'
            ]
            // Aggiungi altre pagine qui se necessario
            // 10 => ['title_format' => 'Eventi - Pagina %d | Anni Azzurri'],
        ];
        
        // Verifica se la pagina corrente ha la paginazione configurata
        if (array_key_exists($currentResourceId, $paginationPages)) {
            $output = &$modx->resource->_output;
            $page = (int)($_GET['page'] ?? 1);
            $pageConfig = $paginationPages[$currentResourceId];
            
            // Ottieni l'URL corrente completo
            $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
            $currentUrl = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            
            // Modifica/aggiunge il canonical
            $canonicalPattern = '/<link rel="canonical"[^>]*>/i';
            $canonicalTag = '<link rel="canonical" href="' . htmlspecialchars($currentUrl) . '">';
            
            if (preg_match($canonicalPattern, $output)) {
                // Sostituisce canonical esistente
                $output = preg_replace($canonicalPattern, $canonicalTag, $output);
            } else {
                // Aggiunge canonical prima della chiusura dell'head
                $output = preg_replace('/<\/head>/i', $canonicalTag . PHP_EOL . '</head>', $output);
            }
            
            // Modifica il title solo per pagine successive alla prima
            if ($page > 1) {
                $pageTitle = sprintf($pageConfig['title_format'], $page);
                $titlePattern = '/<title>(.*?)<\/title>/i';
                $titleTag = '<title>' . htmlspecialchars($pageTitle) . '</title>';
                $output = preg_replace($titlePattern, $titleTag, $output);
            }
        }
        break;
}

return;