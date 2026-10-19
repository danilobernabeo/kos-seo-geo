<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek < 5 )
     {
     if( $now >= '09:00' && $now <= '17:00' )
          {
          echo ' chiamare il numero verde gratuito <b><a href="tel:800131851">800 131 851</a></b> oppure ';
          }
     else 
          {
             echo ' ';
          }
     }

elseif( $dayofweek == 5 )
     {
     if( $now >= '09:00' && $now <= '15:00' )
          {
          echo ' chiamare il numero verde gratuito <b><a href="tel:800131851">800 131 851</a></b> oppure ';
          }
     else
          {
          echo ' ';
          }
     }

else
     {
     echo ' ';
     }