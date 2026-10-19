<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek <= 5 )
     {
     if(( $now >= '09:00' && $now <= '12:30' ) || ( $now >= '14:30' && $now <= '18:00' ))
          {
          echo 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numtel]]">[[*numtel]]</a>';
          }
     else 
          {
          echo 'e ti richiamiamo il prima possibile:';
          }
     }

elseif( $dayofweek == 6 )
     {
     if( $now >= '09:00' && $now <= '12:00' )
          {
          echo 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numtel]]">[[*numtel]]</a>';
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