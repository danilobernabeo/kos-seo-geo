<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek  > 0 && $dayofweek < 6 ) {
     if( $now > 8 && $now < 18 ) {
             echo 'o chiama il numero di telefono <a class="text-decoration-none" href="tel:+390236507408">02 36507408</a>';
        }
        else {
             echo 'e ti richiamiamo il prima possibile:';
        }
     }
else {
         echo 'e ti richiamiamo il prima possibile:';
        }