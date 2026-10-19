<?php
/**
 * syncYoutubeVideos
 * Recupera i video dalle playlist YouTube e li salva in cache MODX.
 * 
 * Il titolo YouTube deve essere nella forma:
 * "Titolo video | Dott. Nome Cognome"
 * oppure (caso non uniforme):
 * "Titolo video Dott. Nome Cognome"
 */

$apiKey    = '[CHIAVE RIMOSSA DALLA BASELINE]'; // sostituisci con la chiave rigenerata
$cacheKey  = 'youtube_videos_esperti';
$cachePath = 'youtube/';

$playlists = array(
    'PLzzx4jrDVm0vi_rQnzMmP1jpw6QnMaZsw' => 'Oculistica',
    'PLzzx4jrDVm0tQb9gbR_9ihoOczvdct9hI' => 'Ortopedia',
    'PLzzx4jrDVm0v_n7KZXawzXNThOVvenAyU' => 'Cardiologia',
    'PLOlbWG8R5MWU' => 'Gastroenterologia',
    'PLK3lUPOFzNdg' => 'Chirurgia Generale',
);

$allVideos = array();

foreach ($playlists as $playlistId => $specialita) {

    $url = 'https://www.googleapis.com/youtube/v3/playlistItems'
         . '?part=snippet'
         . '&playlistId=' . $playlistId
         . '&maxResults=50'
         . '&key=' . $apiKey;

    $response = @file_get_contents($url);
    if (!$response) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[syncYoutubeVideos] Impossibile contattare API YouTube per playlist: ' . $playlistId);
        continue;
    }

    $data = json_decode($response, true);

    if (!empty($data['error'])) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[syncYoutubeVideos] Errore API YouTube: ' . $data['error']['message']);
        continue;
    }

    if (empty($data['items'])) continue;

    foreach ($data['items'] as $item) {
        $snippet = $item['snippet'];
        $videoId = isset($snippet['resourceId']['videoId']) ? $snippet['resourceId']['videoId'] : '';
        if (!$videoId) continue;

        // --- Estrai titolo e nome del medico ---
        $titoloCompleto = $snippet['title'];
        $medico = '';

        if (strpos($titoloCompleto, '|') !== false) {
            // Caso standard: "Titolo video | Dott. Nome Cognome"
            $parti  = explode('|', $titoloCompleto, 2);
            $titolo = trim($parti[0]);
            $medico = trim($parti[1]);
        } elseif (preg_match('/^(.*?)\s+([Dd]ott\.?\s+.+)$/', $titoloCompleto, $m)) {
            // Caso senza separatore: "Titolo video Dott. Nome Cognome"
            $titolo = trim($m[1]);
            $medico = trim($m[2]);
        } else {
            // Nessun medico trovato nel titolo
            $titolo = $titoloCompleto;
        }

        // --- Thumbnail: prendi la migliore disponibile ---
        $thumbnail = '';
        $thumbPriority = array('maxres', 'standard', 'high', 'medium', 'default');
        foreach ($thumbPriority as $size) {
            if (!empty($snippet['thumbnails'][$size]['url'])) {
                $thumbnail = $snippet['thumbnails'][$size]['url'];
                break;
            }
        }

        $allVideos[] = array(
            'id'         => $videoId,
            'titolo'     => $titolo,
            'medico'     => $medico,
            'specialita' => $specialita,
            'thumbnail'  => $thumbnail,
            'pubblicato' => isset($snippet['publishedAt']) ? $snippet['publishedAt'] : '',
        );
    }
}

if (empty($allVideos)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[syncYoutubeVideos] Nessun video recuperato. Verificare API key e playlistId.');
    return 'Errore: nessun video recuperato. Controlla il log di MODX.';
}

// Salva in cache MODX (6 ore = 21600 secondi)
$modx->cacheManager->set($cacheKey, $allVideos, 21600, array(
    xPDO::OPT_CACHE_KEY => $cachePath,
));

return 'Sincronizzati ' . count($allVideos) . ' video (' . implode(', ', array_count_values(array_column($allVideos, 'specialita'))) . ').';