<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now >= '08:00' && $now <= '20:00' )
          {
          echo 'o chiama il numero <a class="text-decoration-none" href="tel:+39[[*numtel]]">[[*numtel]]</a>';
          }
     else 
          {
             echo 'e ti richiamiamo il prima possibile:';
          }