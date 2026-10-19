<?php
date_default_timezone_set('Europe/Rome');

$dayOfWeek = (int) date('w');
$now       = date('H:i');

// ── Orari ────────────────────────────────────────────────────
$isPrenotazioni = (
    $dayOfWeek >= 1 && $dayOfWeek <= 5 &&
    $now >= '08:45' && $now < '16:30'
);

$isCentralino = (
    ( $dayOfWeek >= 1 && $dayOfWeek <= 5 &&
      ( ($now >= '07:30' && $now < '08:45') ||
        ($now >= '16:30' && $now < '20:30') ) ) ||
    ( $dayOfWeek === 6 &&
      $now >= '07:30' && $now < '20:30' ) ||
    ( $dayOfWeek === 0 &&
      $now >= '09:00' && $now < '20:00' )
);

// ── Parametri grafici ────────────────────────────────────────
$class           = array_key_exists('class',           $scriptProperties) ? $scriptProperties['class']           : 'text-white text-decoration-none fw-semibold';
$style           = array_key_exists('style',           $scriptProperties) ? $scriptProperties['style']           : 'font-size:1.1rem;';
$prefix          = array_key_exists('prefix',          $scriptProperties) ? $scriptProperties['prefix']          : 'Oppure chiama lo';
$suffix          = array_key_exists('suffix',          $scriptProperties) ? $scriptProperties['suffix']          : '';
$icon            = array_key_exists('icon',            $scriptProperties) ? $scriptProperties['icon']            : 'fas fa-phone-alt me-1';
$icon_right      = array_key_exists('icon_right',      $scriptProperties) ? $scriptProperties['icon_right']      : '';
$wrapper         = array_key_exists('wrapper',         $scriptProperties) ? (bool) $scriptProperties['wrapper']  : false;
$fallback        = array_key_exists('fallback',        $scriptProperties) ? (bool) $scriptProperties['fallback'] : false;
$fallback_prefix = array_key_exists('fallback_prefix', $scriptProperties) ? $scriptProperties['fallback_prefix'] : 'Oppure';
$fallback_label  = array_key_exists('fallback_label',  $scriptProperties) ? $scriptProperties['fallback_label']  : 'scrivici';

// ── Helper costruzione link ───────────────────────────────────
$buildLink = function($href, $label, $usePrefix, $forceNoIconRight = false) use ($class, $style, $icon, $icon_right, $prefix, $fallback_prefix, $suffix) {

    $suffixHtml    = $suffix ? ' <small>' . $suffix . '</small>' : '';
    $iconLeftHtml  = $icon  ? '<i class="' . $icon . '"></i> '  : '';
    $iconRightHtml = (!$forceNoIconRight && $icon_right) ? ' <i class="' . $icon_right . '"></i>' : '';
    $p             = $usePrefix === 'fallback' ? $fallback_prefix : $prefix;
    $prefixHtml    = $p !== '' ? trim($p) . '&nbsp;' : '';

    return '<span style="white-space:nowrap">'
         . $prefixHtml
         . '<a href="' . $href . '" class="' . $class . '" style="' . $style . '">'
         . $iconLeftHtml
         . '<strong>' . $label . '</strong>'
         . $suffixHtml
         . $iconRightHtml
         . '</a>'
         . '</span>';
};

// ── Helper costruzione output finale ─────────────────────────
$buildOutput = function($link, $isTelefono) use ($wrapper) {

    if ($wrapper) {
        $titolo = $isTelefono
            ? '<p class="h3 text-center mt-5">Hai domande o vuoi prenotare per telefono?</p>'
            : '<p class="h3 text-center mt-5">Hai domande?</p>';

        return $titolo . '
        <p class="h5 mb-3 text-center">
            ' . $link . '
            <br><br>oppure
        </p>
        <hr class="my-2">';
    }

    return $link;
};

// ── Output ───────────────────────────────────────────────────
if ($isPrenotazioni) {
    $link = $buildLink('tel:+390521820283', '0521&nbsp;820283', 'normal');
    return $buildOutput($link, true);
}

if ($isCentralino) {
    $link = $buildLink('tel:+390521820211', '0521&nbsp;820211', 'normal');
    return $buildOutput($link, true);
}

// ── Fallback al form ─────────────────────────────────────────
if ($fallback) {
    $link = $buildLink('#contatti', $fallback_label, 'fallback', true);
    return $buildOutput($link, false);
}

return '';