<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now >= '09:00' && $now <= '18:00' )
          {
          echo 'o chiama il numero <a class="text-decoration-none" href="tel:+390172743175">0172 743175</a>';
          }
     else 
          {
             echo 'e ti richiamiamo il prima possibile:';
          }