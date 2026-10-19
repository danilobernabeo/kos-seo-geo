<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

if ($now >= '09:30' && $now <= '17:30') {
    echo '<div class="card-text d-flex align-items-start pb-3"><i class="text-azure bi bi-telephone"></i><div class="content-right ps-2"><a class="text-decoration-none" href="tel:[[*numTel]]">[[*numTel]]</a></div></div><div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
} else {
    echo '<div class="text-center"><a href="[[~[[*id]]? &scheme=`full`]]#contatti" class="btn btn-outline-primary">Vuoi essere ricontattato?</a></div>';
}