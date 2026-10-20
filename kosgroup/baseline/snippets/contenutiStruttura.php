<?php
$id = $modx->getOption('id', $scriptProperties, $modx->resource->get('id'));

// Abilita debug se necessario
$debug = $modx->getOption('debug', $scriptProperties, false);
$debugInfo = array();

// Ottieni l'ID della struttura corrente
$currentResource = $modx->getObject('modResource', $id);
if (!$currentResource) return '';

// Definisci le categorie di contenuti da cercare
$contentParents = array(30, 31, 32, 34); // News, Approfondimenti, Eventi, Video

// Preparazione della query per trovare le TV
$tvname = 'idStruttura';
$tv = $modx->getObject('modTemplateVar', array('name' => $tvname));
if (!$tv) {
    if ($debug) $debugInfo[] = "TV '$tvname' non trovata";
    return $debug ? implode('<br>', $debugInfo) : '';
}

$tvid = $tv->get('id');
if ($debug) $debugInfo[] = "ID della TV: $tvid";

// Risorse che hanno la TV idStruttura con l'ID della struttura corrente
// (valori come "218" oppure "216,228,234": FIND_IN_SET riconosce solo l'ID intero)
$c = $modx->newQuery('modTemplateVarResource');
$c->where(array('tmplvarid' => $tvid));
$c->where('FIND_IN_SET(' . (int) $id . ', REPLACE(`modTemplateVarResource`.`value`, " ", "")) > 0');

// Ottieni gli ID delle risorse con la TV impostata
$tvResources = $modx->getCollection('modTemplateVarResource', $c);
if (empty($tvResources)) {
    if ($debug) $debugInfo[] = "Nessuna risorsa trovata con TV '$tvname' contenente l'ID $id";
    return $debug ? implode('<br>', $debugInfo) : '';
}

$resourceIds = array();
foreach ($tvResources as $tvResource) {
    $resourceIds[] = $tvResource->get('contentid');
}

if ($debug) $debugInfo[] = "Trovate " . count($resourceIds) . " risorse con TV correlata: " . implode(',', $resourceIds);

// Ottieni tutte le possibili sottocartelle (come le cartelle anno)
$subfolders = array();
foreach ($contentParents as $parentId) {
    $subfoldersQuery = $modx->newQuery('modResource');
    $subfoldersQuery->where(array(
        'parent' => $parentId,
    ));
    $subfoldersCollection = $modx->getCollection('modResource', $subfoldersQuery);
    foreach ($subfoldersCollection as $subfolder) {
        $subfolders[] = $subfolder->get('id');
    }
}

if ($debug) $debugInfo[] = "Sottocartelle trovate: " . implode(',', $subfolders);
$allParents = array_merge($contentParents, $subfolders);

// Recupera le risorse che appartengono alle categorie e hanno la TV corretta
$resourceQuery = $modx->newQuery('modResource');
$resourceQuery->where(array(
    'id:IN' => $resourceIds,
    'published' => 1,
    'deleted' => 0,
));

// Aggiungi le condizioni per il parent
$resourceQuery->where(array(
    'parent:IN' => $allParents
));

// Ordina per data di pubblicazione e limita i risultati
$resourceQuery->sortby('publishedon', 'DESC');
$resourceQuery->limit(12);

// Esegui la query
$resources = $modx->getCollection('modResource', $resourceQuery);

if ($debug) $debugInfo[] = "Risorse finali trovate: " . count($resources);

// Se non ci sono risultati, non mostrare nulla
if (empty($resources)) {
    if ($debug) $debugInfo[] = "Nessuna risorsa coincide con tutti i criteri";
    return $debug ? implode('<br>', $debugInfo) : '';
}

// Prepara l'output
$output = '<section class="blocchi-con-news aos-init aos-animate mb-5" data-aos="fade-up" role="region" aria-label="Ultime news">
    <div class="container">
        <div class="row">
            <div class="col-12">                                    
                            <div class="titolo-sezione">
                                <h2>Le ultime news da <span>[[*pagetitle]]</span></h2>
                                <a class="btn btn-secondary" href="[[~5]]" title="News e media">Vai a news &amp; media</a>
                            </div>
            </div>
        </div>
        <div class="row box-news">';

// Itera sui risultati
foreach ($resources as $resource) {
    // Ottieni la variabile di template infoImage
    $infoImage = $resource->getTVValue('infoImage');
    if (empty($infoImage)) {
        $infoImage = '/assets/img/placeholder.jpg'; // Immagine predefinita se manca
    }
    
    // Formatta la data di pubblicazione
    $pubDate = $resource->get('publishedon');
    $formattedDate = date('d/m/Y', strtotime($pubDate));
    
    // Ottieni il titolo del genitore (categoria)
    $parentId = $resource->get('parent');
    $categoryId = $parentId;
    
    // Se il parent è una sottocartella (es. anno), risali alla categoria principale
    if (!in_array($parentId, $contentParents)) {
        $parentResource = $modx->getObject('modResource', $parentId);
        if ($parentResource) {
            $grandParentId = $parentResource->get('parent');
            if (in_array($grandParentId, $contentParents)) {
                $categoryId = $grandParentId;
            }
        }
    }
    
    // Ottieni il nome della categoria
    $categoryResource = $modx->getObject('modResource', $categoryId);
    $categoryTitle = $categoryResource ? $categoryResource->get('pagetitle') : '';
    
    // Determina il testo della CTA in base alla categoria
    $ctaText = 'Leggi l\'articolo';
    $ctaClass = 'leggi';
    
    // Se la categoria è Video (id=34), cambia il testo in "Guarda"
    if ($categoryId == 34) {
        $ctaText = 'Guarda il video';
        $ctaClass = 'guarda';
    }
    
    // Prepara l'output per questa risorsa
    $output .= '
    <div class="news-singola col-12 col-md-4 mb-5">                                    
        <div class="news-media news-img">
            <a href="/' . $resource->get('uri') . '" title="' . $resource->get('pagetitle') . '">
                <img src="/' . ltrim($infoImage, '/') . '" alt="' . $resource->get('pagetitle') . '">
            </a>
        </div>
        <div class="news-cats">
            <span>' . $categoryTitle . '</span>
        </div>
        <h3><a href="/' . $resource->get('uri') . '" title="' . $resource->get('pagetitle') . '">' . $resource->get('pagetitle') . '</a></h3>
        <p>' . $resource->get('introtext') . '</p>
        <div class="news-meta">
            <div class="news-data">' . $formattedDate . '</div>
            <a class="news-cta btn-link btn-link-u ' . $ctaClass . '" href="/' . $resource->get('uri') . '" title="' . $resource->get('pagetitle') . '">
                ' . $ctaText . '<img src="/assets/img/arrow-dx.svg" alt="">
            </a>
        </div>
    </div>';
}

$output .= '        </div>
    </div>
</section>
';

if ($debug) {
    $output .= '<div style="margin-top:20px;padding:10px;background:#f5f5f5;border:1px solid #ccc;"><h3>Debug Info</h3>' . implode('<br>', $debugInfo) . '</div>';
}

return $output;