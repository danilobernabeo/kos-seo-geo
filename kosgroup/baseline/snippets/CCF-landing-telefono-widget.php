<?php
date_default_timezone_set('Europe/Rome');
$dayOfWeek = date('w');
$now       = date('H:i');

$isOrarioUfficio = (
    $dayOfWeek >= 1 && $dayOfWeek <= 5 &&
    $now >= '09:00' && $now <= '16:30'
);

if ($isOrarioUfficio) {
    return '
    <p class="text-center mt-4" style="font-size:.95rem;">
        Preferisci prenotare per telefono?
        <strong><a href="tel:+390521820211">0521 820211</a></strong>
        - attivo dal Lunedì al Venerdì.
    </p>';
}

return '';