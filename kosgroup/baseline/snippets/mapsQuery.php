<?php
$placeId = trim($modx->getOption('placeId', $scriptProperties, ''));
if ($placeId !== '') {
    return 'place_id:' . rawurlencode($placeId);
}
$q = $modx->getOption('address', $scriptProperties, '');
$q = html_entity_decode(strip_tags($q), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$q = preg_replace('/[\s\x{00A0}]+/u', ' ', $q);
return rawurlencode(trim($q));