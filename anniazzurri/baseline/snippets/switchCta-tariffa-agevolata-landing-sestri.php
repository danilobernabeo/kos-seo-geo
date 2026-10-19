<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek > 0 && $dayofweek < 6 ) {
     if( $now > 8 && $now < 17 ) {
             echo '<a href="tel:+39[[*numTel]]" title="Tariffa agevolata RSA [[*pagetitle]]" class="btn btn-outline-white">CHIAMA</a>';
        }
        else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-white">CONTATTATEMI</a>';
        }
     }
else {
         echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-white">CONTATTATEMI</a>';
        }