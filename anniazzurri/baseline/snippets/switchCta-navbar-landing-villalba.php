<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now >= '08:00' && $now <= '20:00' ) {
             echo '<a id="[[*numtel]]" href="tel:+39[[*numtel]]" class="btn btn-azure text-white py-2 px-3 font-weight-600"><i class="bi bi-telephone"></i> CHIAMACI</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-azure text-white py-2 px-3 font-weight-600">CONTATTATEMI</a>';
        }