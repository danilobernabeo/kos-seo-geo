<?php
date_default_timezone_set('Europe/Rome');
$dayofweek = date('w');
$now = date('H:i');

// Blocchi HTML riutilizzabili
$phoneBlock = '<div class="card-text d-flex align-items-start pb-3">
    <i class="text-azure bi bi-telephone"></i>
    <div class="content-right ps-2">
        <a class="text-decoration-none" href="tel:[[*numTel]]">[[*numTel]]</a>
    </div>
</div>';

$contactBtn = '<div class="text-center">
    <a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a>
</div>';

// Determina se mostrare il telefono
$showPhone = false;

// Lun-Ven (1-5): 09-13 e 14-18
if ($dayofweek >= 1 && $dayofweek <= 5) {
    $showPhone = (($now >= '09:00' && $now <= '13:00') || ($now >= '14:00' && $now <= '18:00'));
}
// Sab (6) e Dom (0): solo 09-13
elseif ($dayofweek == 6 || $dayofweek == 0) {
    $showPhone = ($now >= '09:00' && $now <= '13:00');
}

// Output
if ($showPhone) {
    return $phoneBlock . $contactBtn;
}

return $contactBtn;