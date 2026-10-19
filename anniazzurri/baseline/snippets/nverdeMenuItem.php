<?php
date_default_timezone_set('Europe/Rome');
$dayofweek = date('w');
$now = date('H:i');
$today = date('d-m');

// Giorni di chiusura totale
$closedDays = [
    '25-12', // Natale
    '01-01', // Capodanno
    '02-01', // 2 gennaio
    '06-01'  // Epifania
];

// Giorni con orario ridotto (09:00-13:00)
$reducedHoursDays = [
    '24-12', // Vigilia di Natale
    '31-12'  // San Silvestro
];

// HTML del pulsante
$phoneMenuItem = '<li class="nav-item px-4 d-flex align-items-center justify-content-center bg-primary text-uppercase col-6 col-md-auto">
    <a href="tel:800131851" class="text-white d-flex align-items-center font-weight-600"><img src="/assets/icon-phone.png" alt="icona smartphone" class="me-2">Chiamaci</a>
</li>';

// Determina se mostrare il telefono
$showPhone = false;

// PRIORITÀ 1: Giorni di chiusura totale
if (in_array($today, $closedDays)) {
    $showPhone = false;
}
// PRIORITÀ 2: Giorni con orario ridotto (09:00-13:00)
elseif (in_array($today, $reducedHoursDays)) {
    $showPhone = ($now >= '09:00' && $now <= '13:00');
}
// PRIORITÀ 3: Regole settimanali standard
else {
    // Lun-Gio (1-4): 09:00-17:00
    if ($dayofweek >= 1 && $dayofweek <= 4) {
        $showPhone = ($now >= '09:00' && $now <= '17:00');
    }
    // Ven (5): 09:00-15:00
    elseif ($dayofweek == 5) {
        $showPhone = ($now >= '09:00' && $now <= '15:00');
    }
    // Sab (6) e Dom (0): sempre chiuso
}

// Output
if ($showPhone) {
    return $phoneMenuItem;
}

return ''; // Non mostra nulla