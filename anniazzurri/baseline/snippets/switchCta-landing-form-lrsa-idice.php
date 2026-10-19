<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek <= 6 )
     {
     if( $now >= '09:00' && $now <= '19:00' )
          {
          echo 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numTel]]">[[*numTel]]</a>';
          }
     else 
          {
             echo 'e ti richiamiamo il prima possibile:';
          }
     }
else
     {
     if( $now >= '09:00' && $now <= '13:00' )
          {
          echo 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numTel]]">[[*numTel]]</a>';
          }
     else
          {
          echo 'e ti richiamiamo il prima possibile:';
          }
     }