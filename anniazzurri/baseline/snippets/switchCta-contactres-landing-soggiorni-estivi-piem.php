<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek < 5 ) {
     if( $now >= '09:00' && $now <= '17:00' ) {
             echo '<div class="card-text d-flex align-items-start xpb-3"><a id="800 131 851" href="tel:800131851" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a></div>';
        }
     else {
             echo '<div class="card-text d-flex align-items-start xpb-3"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-envelope"></i> SCRIVICI</a></div>';
        }
     }
elseif( $dayofweek == 5 ) {
     if( $now >= '09:00' && $now <= '15:00' ) {
             echo '<div class="card-text d-flex align-items-start xpb-3"><a id="800 131 851" href="tel:800131851" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a></div>';
        }
     else {
             echo '<div class="card-text d-flex align-items-start xpb-3"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-envelope"></i> SCRIVICI</a></div>';
        }
     }
else {
         echo '<div class="card-text d-flex align-items-start pb-3"><a id="crm.alessandra@anniazzurri.it" href="mailto:crm.alessandra@anniazzurri.it" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-envelope"></i> SCRIVICI</a></div>';
        }