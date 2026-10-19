<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if( $dayofweek >= 1 && $dayofweek <= 5 )
     {
     if(( $now >= '09:00' && $now <= '12:30' ) || ( $now >= '14:30' && $now <= '18:00' ))
          {
          echo '<div class="card-text d-flex align-items-start pb-3"><i class="text-azure bi bi-telephone"></i><div class="content-right ps-2"><a class="text-decoration-none" data-yext-field="phone" data-yext-id="[[*data-yext-id]]"></a></div></div><div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
          }
     else 
          {
          echo '<div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
          }
     }

elseif( $dayofweek == 6 )
     {
     if( $now >= '09:00' && $now <= '12:00' )
          {
          echo '<div class="card-text d-flex align-items-start pb-3"><i class="text-azure bi bi-telephone"></i><div class="content-right ps-2"><a class="text-decoration-none" data-yext-field="phone" data-yext-id="[[*data-yext-id]]"></a></div></div><div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
          }
     else
          {
          echo '<div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
          }
     }

else
  {
  echo '<div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
  }