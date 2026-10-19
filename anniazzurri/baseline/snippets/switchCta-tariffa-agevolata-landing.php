<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now > 7 && $now < 20 ) {
             echo '<a href="tel:+39[[*numTel]]" title="Tariffa agevolata RSA [[*pagetitle]]" class="btn btn-outline-white">CHIAMA</a>';
        }
        else {
             echo '<a href="[[~[[*id]]? &scheme=`full`]]#contatti" title="Tariffa agevolata RSA [[*pagetitle]]" class="btn btn-outline-white">MI INTERESSA</a>';
        }