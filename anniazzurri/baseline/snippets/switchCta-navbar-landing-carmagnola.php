<?php
date_default_timezone_set('Europe/Rome');
$dayofweek = date('w');
$now = date('H:i');

// Blocchi HTML riutilizzabili
$phoneBtn = '<a id="num-verde" href="tel:800131851" class="btn btn-azure text-white py-2 px-3 font-weight-600">
    <i class="bi bi-telephone"></i> CHIAMACI
</a>';

$contactBtn = '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600">
    TI CHIAMIAMO NOI
</a>';

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
    return $phoneBtn;
}

return $contactBtn;