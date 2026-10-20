<?php
/**
 * VideoCarousel Snippet (Versione Flessibile)
 * 
 * Genera la sezione video carousel con numero variabile di video
 * basata sulla TV videoIds
 * 
 * Parametri opzionali:
 * &maxVideos=`6` - Numero massimo di video da mostrare (default: illimitato)
 * &minVideos=`1` - Numero minimo di video richiesti (default: 1)
 */

$videoIds = $modx->resource->getTVValue('videoIds');

// Parametri opzionali
$maxVideos = !empty($maxVideos) ? (int)$maxVideos : 0; // 0 = illimitato
$minVideos = !empty($minVideos) ? (int)$minVideos : 1;

if (empty($videoIds)) {
    return '<!-- Nessun video specificato -->';
}

// Dividi gli ID
$ids = explode(',', $videoIds);
$ids = array_map('trim', $ids); // Rimuovi spazi
$ids = array_filter($ids); // Rimuovi elementi vuoti

// Controlla il numero minimo
if (count($ids) < $minVideos) {
    return '<!-- Servono almeno ' . $minVideos . ' ID per i video (trovati: ' . count($ids) . ') -->';
}

// Limita al massimo se specificato
if ($maxVideos > 0 && count($ids) > $maxVideos) {
    $ids = array_slice($ids, 0, $maxVideos);
}

// Ottieni le risorse
$resources = array();
foreach ($ids as $id) {
    $resource = $modx->getObject('modResource', (int)$id);
    if ($resource) {
        $resources[] = $resource;
    }
}

if (count($resources) < $minVideos) {
    return '<!-- Non tutte le risorse video richieste sono state trovate (minimo: ' . $minVideos . ', trovate: ' . count($resources) . ') -->';
}

$totalVideos = count($resources);

// Genera l'HTML
$output = '<div class="container-fluid container-md">
    <div class="row">
        <div class="col-12">                                    
            <div class="titolo-sezione">
                <h2>Gli ultimi video in evidenza</h2>
                <a class="btn btn-secondary" href="/it/news-e-media/video">Vedi tutti i video</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 articoli-box">
            <div id="blocco-articoli-carosello" class="swiper swiper-initialized swiper-horizontal swiper-backface-hidden">
                <div class="swiper-wrapper" style="cursor: grab; transition-duration: 0ms; transform: translate3d(0px, 0px, 0px); transition-delay: 0ms;" id="swiper-wrapper-4c5ae591f4ececf4" aria-live="polite">';

// Genera ogni slide del video
for ($i = 0; $i < $totalVideos; $i++) {
    $video = $resources[$i];
    $title = $video->get('pagetitle');
    $uri = $video->get('uri');
    $videoImage = $video->getTVValue('infoImage');
    
    if (empty($videoImage)) {
        $videoImage = '/assets/img/placeholder.jpg';
    }
    
    // Ottieni categoria con tag Tagger
    $parentId = $video->get('parent');
    $category = $modx->getObject('modResource', $parentId);
    $categoryTitle = '';
    
    // Prova prima con i tag Tagger
    try {
        $sql = "SELECT t.tag 
                FROM modx_tagger_tag_resources tr 
                JOIN modx_tagger_tags t ON tr.tag = t.id 
                WHERE tr.resource = ? AND t.group = 1";
        
        $stmt = $modx->prepare($sql);
        $stmt->execute(array($video->get('id')));
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($results)) {
            $categoryTitle = strtoupper(implode(', ', $results));
        }
    } catch (Exception $e) {
        // Ignora errori
    }
    
    // Se non ci sono tag, usa il "nonno" (parent del parent) come fallback
    if (empty($categoryTitle)) {
        if ($category) {
            $grandParentId = $category->get('parent');
            $grandParent = $modx->getObject('modResource', $grandParentId);
            $categoryTitle = $grandParent ? strtoupper($grandParent->get('pagetitle')) : strtoupper($category->get('pagetitle'));
        } else {
            $categoryTitle = 'VIDEO';
        }
    }
    
    // Gestione dinamica delle classi CSS per Swiper
    $slideClass = '';
    if ($i === 0) {
        $slideClass = 'swiper-slide-active';
    } elseif ($i === 1) {
        $slideClass = 'swiper-slide-next';
    }
    // Le slide successive non hanno classi speciali iniziali
    
    $slideNumber = $i + 1;
    
    $output .= '
                    <div class="articolo-tassello swiper-slide ' . $slideClass . '" style="width: 622.5px; margin-right: 36px;" role="group" aria-label="' . $slideNumber . ' / ' . $totalVideos . '">
                        <div class="news-singola">
                            <a href="/' . $uri . '" aria-label="' . htmlspecialchars($title) . ' (video)">
                                <div class="news-media news-video">
                                    <img src="/' . ltrim($videoImage, '/') . '" alt="' . htmlspecialchars($title) . '">
                                </div>
                            </a>
                            <div class="news-contenuto-tesuale">
                                <div class="news-cats">
                                    <span>' . htmlspecialchars($categoryTitle) . '</span>
                                </div>
                                <h3>' . htmlspecialchars($title) . '</h3>
                            </div>
                        </div>
                    </div>';
}

$output .= '
                </div> <!--  swiper-wrapper -->
                <div class="swiper-controls">
                    <div class="swiper-button-prev" tabindex="0" role="button" aria-label="Previous slide" aria-controls="swiper-wrapper-4c5ae591f4ececf4"><img src="/assets/img/angle_left.svg" alt="Scorri a sinistra"></div>
                    <div class="swiper-button-next" tabindex="0" role="button" aria-label="Next slide" aria-controls="swiper-wrapper-4c5ae591f4ececf4"><img src="/assets/img/angle_right.svg" alt="Scorri a destra"></div>
                </div>
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
            </div><!-- swiper -->
        </div><!-- #blocco-articoli-carosello -->
    </div>
</div>';

return $output;