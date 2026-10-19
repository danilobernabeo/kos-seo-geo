<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek < 5 )
     {
     if( $now >= '09:00' && $now <= '17:00' )
          {
          echo 'o chiama il numero verde gratuito <a class="text-decoration-none" href="tel:800131851">800 131 851</a>';
          }
     else 
          {
             echo 'e ti richiamiamo il prima possibile:';
          }
     }

elseif( $dayofweek == 5 )
     {
     if( $now >= '09:00' && $now <= '15:00' )
          {
          echo 'o chiama il numero verde gratuito <a class="text-decoration-none" href="tel:800131851">800 131 851</a>';
          }
     else
          {
          echo 'e ti richiamiamo il prima possibile:';
          }
     }

else
     {
     echo 'e ti richiamiamo il prima possibile:';
     }