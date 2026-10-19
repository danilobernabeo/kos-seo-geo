<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now >= '08:00' && $now <= '20:00' ) {
             echo '<a id="[[*numTel]]" href="tel:[[*numTel]]" title="Tariffa agevolata RSA [[*pagetitle]]" class="btn btn-outline-white">CHIAMA</a>';
        }
     else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-white">CONTATTATEMI</a>';
        }