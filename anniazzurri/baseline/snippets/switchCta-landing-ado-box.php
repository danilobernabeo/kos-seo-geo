<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek  > 0 && $dayofweek < 6 ) {
     if( $now > 8 && $now < 18 ) {
             echo '<a class="font-weight-600 text-decoration-none" href="tel:+390236507408"><i class="bi bi-telephone"></i> 02 36507408</a>';
        }
        else {
             echo '<a class="font-weight-600 text-decoration-none" href="[[~[[*id]]? &scheme=`full`]]#contatti"><i class="bi bi-arrow-down-circle-fill"></i> Lasciaci i tuoi dati e ti chiamiamo noi</a>';
        }
     }
else {
         echo '<a class="font-weight-600 text-decoration-none" href="[[~[[*id]]? &scheme=`full`]]#contatti"><i class="bi bi-arrow-down-circle-fill"></i> Lasciaci i tuoi dati e ti chiamiamo noi</a>';
        }