<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');
if( $dayofweek >= 1 && $dayofweek < 5 ) {
     if( $now >= '09:00' && $now <= '17:30' ) {
             echo '<a id="0236507408" href="tel:+390236507408" class="btn btn-primary text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-primary text-white py-2 px-3 font-weight-600">CONTATTACI</a>';
        }
     }
elseif( $dayofweek == 5 ) {
     if( $now >= '09:00' && $now <= '17:30' ) {
             echo '<a id="0236507408" href="tel:+390236507408" class="btn btn-primary text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-primary text-white py-2 px-3 font-weight-600">CONTATTACI</a>';
        }
     }
else {
         echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-primary text-white py-2 px-3 font-weight-600">CONTATTACI</a>';
        }