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
    <a href="tel:+390521820211" class="text-white text-decoration-none" style="font-size:.95rem;">
        <i class="fas fa-phone-alt me-1"></i> Oppure chiama lo 0521 820211
    </a>';
}

return '';