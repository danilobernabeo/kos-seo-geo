<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek <= 5 )
     {
     if(( $now >= '08:30' && $now <= '17:00' ))
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