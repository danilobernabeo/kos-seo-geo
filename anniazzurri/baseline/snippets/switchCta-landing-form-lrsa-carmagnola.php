<?php
date_default_timezone_set('Europe/Rome');
$now = date('H:i');

// Messaggi riutilizzabili
$phoneMsg = 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numtel]]">[[*numtel]]</a>';
$callbackMsg = 'e ti richiamiamo il prima possibile:';

// Orario 08:30-19:00 (tutti i giorni)
if ($now >= '08:30' && $now <= '19:00') {
    return $phoneMsg;
}

return $callbackMsg;