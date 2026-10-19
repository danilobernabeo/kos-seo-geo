<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek  > 0 && $dayofweek < 6 ) {
     if( $now > 8 && $now < 18 ) {
             echo 'href="tel:+390236507408">CHIAMA ORA</a>';
        }
        else {
             echo 'href="[[~[[*id]]? &scheme=`full`]]#contatti">VOGLIO SAPERNE DI PIU</a>';
        }
     }
else {
         echo 'href="[[~[[*id]]? &scheme=`full`]]#contatti">VOGLIO SAPERNE DI PIU</a>';
        }