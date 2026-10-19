<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');
if( $dayofweek >= 1 && $dayofweek <= 6 && $now >= '08:30' && $now <= '18:00' ) {
    echo '<p class="mt-1 mb-3"><b>Preferisci parlare con noi?</b> Chiama il numero <b><a href="tel:[[*numtel]]">[[*numtel]]</a></b></p>';
} else {
    echo '';
}