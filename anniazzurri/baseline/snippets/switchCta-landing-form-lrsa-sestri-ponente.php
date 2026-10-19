<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $now >= '09:30' && $now <= '17:30' )
     {
     echo 'o chiama il numero <a class="text-decoration-none" href="tel:[[*numtel]]">[[*numtel]]</a>';
     }
else 
     {
        echo 'e ti richiamiamo il prima possibile:';
     }