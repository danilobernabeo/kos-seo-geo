<?php
/**
 * Approfondimenti V2 Snippet
 * 
 * Genera la sezione approfondimenti con 1 articolo grande + 2 nella sidebar con immagini
 * basata sulla TV approfondimentiIds
 */

$approfondimentiIds = $modx->resource->getTVValue('approfondimentiIds');

if (empty($approfondimentiIds)) {
    return '<!-- Nessun approfondimento specificato -->';
}

// Dividi gli ID
$ids = explode(',', $approfondimentiIds);
$ids = array_map('trim', $ids); // Rimuovi spazi
$ids = array_filter($ids); // Rimuovi elementi vuoti

if (count($ids) < 3) {
    return '<!-- Servono almeno 3 ID per gli approfondimenti V2 (trovati: ' . count($ids) . ') -->';
}

// Prendi solo i primi 3 ID (1 grande + 2 sidebar)
$ids = array_slice($ids, 0, 3);

// Ottieni le risorse
$resources = array();
foreach ($ids as $id) {
    $resource = $modx->getObject('modResource', (int)$id);
    if ($resource) {
        $resources[] = $resource;
    }
}

if (count($resources) < 3) {
    return '<!-- Non tutte le risorse degli approfondimenti sono state trovate -->';
}

// Genera l'HTML
$output = '<section class="blocchi-con-news order-lg-3 aos-init aos-animate" data-aos="fade-up">
    <div class="container">
        <div class="row">
            <div class="col-12">                                    
                <div class="titolo-sezione mb-0">
                    <h2>In primo piano</h2>
                </div>
            </div>
        </div>
        <div class="row titolo-sezione">';

// Articolo grande (primo della lista) - rimane identico
$mainArticle = $resources[0];
$mainTitle = $mainArticle->get('pagetitle');
$mainIntrotext = $mainArticle->get('introtext');
$mainUri = $mainArticle->get('uri');
$mainPublishedOn = $mainArticle->get('publishedon');
$mainFormattedDate = date('d-m-Y', strtotime($mainPublishedOn));
$mainImage = $mainArticle->getTVValue('infoImage');

// Ottieni categoria del main article con tag Tagger
$mainParentId = $mainArticle->get('parent');
$mainCategory = $modx->getObject('modResource', $mainParentId);
$mainCategoryTitle = '';

// Prova prima con i tag Tagger
try {
    $sql = "SELECT t.tag 
            FROM modx_tagger_tag_resources tr 
            JOIN modx_tagger_tags t ON tr.tag = t.id 
            WHERE tr.resource = ? AND t.group = 1";
    
    $stmt = $modx->prepare($sql);
    $stmt->execute(array($mainArticle->get('id')));
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($results)) {
        $mainCategoryTitle = implode(', ', $results);
    }
} catch (Exception $e) {
    // Ignora errori
}

// Se non ci sono tag, usa il "nonno" (parent del parent) come fallback
if (empty($mainCategoryTitle)) {
    if ($mainCategory) {
        $mainGrandParentId = $mainCategory->get('parent');
        $mainGrandParent = $modx->getObject('modResource', $mainGrandParentId);
        $mainCategoryTitle = $mainGrandParent ? $mainGrandParent->get('pagetitle') : $mainCategory->get('pagetitle');
    }
}

if (empty($mainImage)) {
    $mainImage = '/assets/img/placeholder.jpg';
}

$output .= '
            <div id="articolo-grande" class="news-singola col-12 col-md-8">
                <div class="news-media news-img">
                    <img class="d-none d-md-block" src="/' . ltrim($mainImage, '/') . '" alt="' . htmlspecialchars($mainTitle) . '">
                    <img class="d-block d-md-none" src="/' . ltrim($mainImage, '/') . '" alt="' . htmlspecialchars($mainTitle) . '">
                </div>
                <div class="articolo-grande-txt">
                    <div class="news-cats">
                        <span>' . htmlspecialchars($mainCategoryTitle) . '</span>
                    </div>
                    <h3>' . htmlspecialchars($mainTitle) . '</h3>
                    <p>' . htmlspecialchars($mainIntrotext) . '</p>
                    <div class="news-meta">
                        <div class="news-data">' . $mainFormattedDate . '</div>
                        <a class="news-cta btn-link btn-link-u leggi" href="/' . $mainUri . '">Leggi l\'articolo<img src="/assets/img/arrow-dx.svg" alt=""></a>
                    </div>
                </div>
            </div>';

// Sidebar con 2 articoli CON IMMAGINI (senza titolo "GLI ARTICOLI PIÙ LETTI")
$output .= '
            <div class="col-12 col-md-4 box-news">';

for ($i = 1; $i < 3; $i++) { // Solo 2 articoli (indici 1 e 2)
    $article = $resources[$i];
    $title = $article->get('pagetitle');
    $introtext = $article->get('introtext');
    $uri = $article->get('uri');
    $publishedOn = $article->get('publishedon');
    $formattedDate = date('d-m-Y', strtotime($publishedOn));
    
    // Ottieni categoria con tag Tagger
    $parentId = $article->get('parent');
    $category = $modx->getObject('modResource', $parentId);
    $categoryTitle = '';
    
    // Prova prima con i tag Tagger
    try {
        $sql = "SELECT t.tag 
                FROM modx_tagger_tag_resources tr 
                JOIN modx_tagger_tags t ON tr.tag = t.id 
                WHERE tr.resource = ? AND t.group = 1";
        
        $stmt = $modx->prepare($sql);
        $stmt->execute(array($article->get('id')));
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($results)) {
            $categoryTitle = implode(', ', $results);
        }
    } catch (Exception $e) {
        // Ignora errori
    }
    
    // Se non ci sono tag, usa il "nonno" (parent del parent) come fallback
    if (empty($categoryTitle)) {
        if ($category) {
            $grandParentId = $category->get('parent');
            $grandParent = $modx->getObject('modResource', $grandParentId);
            $categoryTitle = $grandParent ? $grandParent->get('pagetitle') : $category->get('pagetitle');
        }
    }
    
    // Ottieni immagine per la sidebar
    $sidebarImage = $article->getTVValue('infoImage');
    if (empty($sidebarImage)) {
        $sidebarImage = '/assets/img/placeholder.jpg';
    }
    
    $output .= '
                <div class="news-singola mb-4">
                    <div class="news-media news-img">
                        <a href="/' . $uri . '" title="' . htmlspecialchars($title) . '">
                            <img src="/' . ltrim($sidebarImage, '/') . '" alt="' . htmlspecialchars($title) . '">
                        </a>
                    </div>                                 
                    <div class="news-cats">
                        <span>' . htmlspecialchars($categoryTitle) . '</span>
                    </div>
                    <h3><a href="/' . $uri . '" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($title) . '</a></h3>
                    <p>' . htmlspecialchars($introtext) . '</p>
                    <div class="news-meta">
                        <div class="news-data">' . $formattedDate . '</div>
                        <a class="news-cta btn-link btn-link-u leggi" href="/' . $uri . '">Leggi l\'articolo<img src="/assets/img/arrow-dx.svg" alt=""></a>
                    </div>
                </div>';
}

$output .= '
            </div>
        </div>
    </div>
</section>';

return $output;