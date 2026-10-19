<?php
date_default_timezone_set('Europe/Rome');
$dayofweek = date('w');
$now = date('H:i');

// Messaggi riutilizzabili
$phoneMsg = 'o chiama il numero verde gratuito <a id="num-verde" href="tel:800131851">800 131 851</a>';
$callbackMsg = 'e ti richiamiamo il prima possibile:';

// Determina se mostrare il telefono
$showPhone = false;

// Lun-Gio (1-4): 09:00-17:00
if ($dayofweek >= 1 && $dayofweek <= 4) {
    $showPhone = ($now >= '09:00' && $now <= '17:00');
}
// Ven (5): 09:00-15:00
elseif ($dayofweek == 5) {
    $showPhone = ($now >= '09:00' && $now <= '15:00');
}
// Sab (6) e Dom (0): sempre chiuso

// Output
if ($showPhone) {
    return $phoneMsg;
}

return $callbackMsg;