<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');
if( $dayofweek >= 1 && $dayofweek < 5 ) {
     if( $now >= '09:00' && $now <= '17:00' ) {
             echo '<a id="800 131 851" href="tel:800131851" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600">CONTATTATEMI</a>';
        }
     }
elseif( $dayofweek == 5 ) {
     if( $now >= '09:00' && $now <= '15:00' ) {
             echo '<a id="800 131 851" href="tel:800131851" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600">CONTATTATEMI</a>';
        }
     }
else {
         echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600">CONTATTATEMI</a>';
        }