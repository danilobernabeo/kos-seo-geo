<?php
date_default_timezone_set('Europe/Rome');
$dayofweek = date('w');
$now = date('H:i');

$showPhone = false;

// Lun-Ven: 09-13 e 14-18
if ($dayofweek >= 1 && $dayofweek <= 5) {
    $showPhone = (($now >= '09:00' && $now <= '13:00') || ($now >= '14:00' && $now <= '18:00'));
}
// Sab-Dom: solo 09-13
elseif ($dayofweek == 6 || $dayofweek == 0) {
    $showPhone = ($now >= '09:00' && $now <= '13:00');
}

if ($showPhone) {
    return 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numtel]]">[[*numtel]]</a>:';
}

return 'e ti richiamiamo il prima possibile:';