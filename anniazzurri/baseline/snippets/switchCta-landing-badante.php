<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek  > 0 && $dayofweek < 6 ) {
     if( $now > 8 && $now < 18 ) {
             echo '<p><a class="mt-2 btn btn-primary" href="tel:+390236507408" target="_blank">Chiama per avere subito una consulenza gratuita!</a></p><p><b>Vuoi essere richiamato?</b></p><p><a class="mt-2 btn btn-primary" href="[[~[[*id]]? &scheme=`full`]]#contatti"><i class="bi bi-arrow-down-circle-fill"></i> Lasciaci i tuoi dati per farti contattare da un nostro consulente!</a></p>';
        }
        else {
             echo '<p><a class="mt-2 btn btn-primary" href="[[~[[*id]]? &scheme=`full`]]#contatti"><i class="bi bi-arrow-down-circle-fill"></i> Lasciaci i tuoi dati per avere subito una consulenza gratuita!</a></p>';
        }
     }
else {
         echo '<p><a class="mt-2 btn btn-primary" href="[[~[[*id]]? &scheme=`full`]]#contatti"><i class="bi bi-arrow-down-circle-fill"></i> Lasciaci i tuoi dati per avere subito una consulenza gratuita!</a><p>';
        }