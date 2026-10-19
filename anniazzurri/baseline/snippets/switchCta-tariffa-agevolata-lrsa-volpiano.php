<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now >= '09:00' && $now <= '18:00' ) {
             echo '<a id="800 131 851" href="tel:[[*numTel]]" title="Tariffa agevolata RSA [[*pagetitle]]" class="btn btn-outline-white">CHIAMA</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-white">CONTATTATEMI</a>';
        }