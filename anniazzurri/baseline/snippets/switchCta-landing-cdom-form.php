<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');
if( $dayofweek >= 1 && $dayofweek <= 5 ) {
     if( $now >= '09:00' && $now <= '17:30' ) {
             echo 'o chiama il numero di telefono <a class="text-decoration-none" href="tel:+390236507408">02 36507408</a>';
        }
     else {
             echo 'e ti richiamiamo il prima possibile:';
        }
     }
else {
         echo 'e ti richiamiamo il prima possibile:';
        }