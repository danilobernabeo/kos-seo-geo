<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek < 5 ) {
     if( $now >= '09:00' && $now <= '17:00' ) {
             echo 'chiamare il numero verde <b><a id="800 131 851" href="tel:800131851">800 131 851</a> oppure</b>';
        }
     else {
             echo '';
        }
     }
elseif( $dayofweek == 5 ) {
     if( $now >= '09:00' && $now <= '15:00' ) {
             echo 'chiamare il numero verde <b><a id="800 131 851" href="tel:800131851">800 131 851</a> oppure</b>';
        }
     else {
             echo '';
        }
     }
else {
         echo '';
        }