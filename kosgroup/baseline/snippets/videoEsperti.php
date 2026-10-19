<?php
/**
 * videoEsperti
 * Legge i video dalla cache e genera il carousel con filtri
 */

$cacheKey   = 'youtube_videos_esperti';
$cachePath  = 'youtube/';
$containerId = $modx->getOption('containerId', $scriptProperties, 'blocco-video-esperti');

// Leggi dalla cache
$videos = $modx->cacheManager->get($cacheKey, array(
    xPDO::OPT_CACHE_KEY => $cachePath,
));

// Se la cache è vuota esegui la sincronizzazione al volo
if (empty($videos)) {
    $modx->runSnippet('syncYoutubeVideos');
    $videos = $modx->cacheManager->get($cacheKey, array(
        xPDO::OPT_CACHE_KEY => $cachePath,
    ));
}

if (empty($videos)) {
    return '<p>Nessun video disponibile al momento.</p>';
}

// Specialità univoche per i bottoni filtro
$specialita = array_unique(array_column($videos, 'specialita'));
sort($specialita);

// Genera bottoni filtro
$filtriHtml = '<button class="video-filter-btn active" data-filter="tutti">Tutti</button>';
foreach ($specialita as $s) {
    $slug = strtolower(preg_replace('/\s+/', '-', $s));
    $filtriHtml .= '<button class="video-filter-btn" data-filter="' . $slug . '">'
                 . htmlspecialchars($s) . '</button>';
}

// Genera slide
$slidesHtml = '';
foreach ($videos as $v) {
    $slug   = strtolower(preg_replace('/\s+/', '-', $v['specialita']));
    $titolo = htmlspecialchars($v['titolo']);
    $medico = htmlspecialchars($v['medico']);
    $spec   = htmlspecialchars($v['specialita']);
    $vidId  = htmlspecialchars($v['id']);

    $medicoHtml = $medico ? '<p class="video-speaker"><small>' . $medico . '</small></p>' : '';

    $slidesHtml .= '<div class="articolo-tassello swiper-slide" data-specialita="' . $slug . '">'
                 . '<div class="news-singola">'
                 . '<div class="news-media news-video">'
                 . '<lite-youtube videoid="' . $vidId . '" playlabel="Riproduci video: ' . $titolo . '"></lite-youtube>'
                 . '</div>'
                 . '<div class="news-contenuto-tesuale">'
                 . '<div class="news-cats"><span>' . $spec . '</span></div>'
                 . '<h3>' . $titolo . '</h3>'
                 . $medicoHtml
                 . '</div>'
                 . '</div>'
                 . '</div>';
}

// Assembla output
$output  = '<div class="container-fluid container-md" id="parola-esperti">';
$output .= '<div class="row"><div class="col-12">';
$output .= '<div class="titolo-sezione">';
$output .= '<h2>Parola ai nostri esperti</h2>';
$output .= '<p style="margin-top:-10px;">Video-pillole di approfondimento su salute, prevenzione e benessere, a cura dei professionisti di Villa dei Pini.</p>';
$output .= '</div>';
$output .= '<div class="video-filtri">' . $filtriHtml . '</div>';
$output .= '</div></div>';
$output .= '<div class="row"><div class="col-12 articoli-box">';
$output .= '<div id="' . $containerId . '" class="swiper">';
$output .= '<div class="swiper-wrapper">' . $slidesHtml . '</div>';
$output .= '<div class="swiper-controls">';
$output .= '<div class="swiper-button-prev" tabindex="0" role="button" aria-label="Video precedente">';
$output .= '<img src="/assets/img/angle_left.svg" alt="Scorri a sinistra"></div>';
$output .= '<div class="swiper-button-next" tabindex="0" role="button" aria-label="Video successivo">';
$output .= '<img src="/assets/img/angle_right.svg" alt="Scorri a destra"></div>';
$output .= '</div>';
$output .= '</div>';
$output .= '</div></div>';
$output .= '</div>';

return $output;