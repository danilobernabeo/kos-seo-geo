<?php
date_default_timezone_set('CET');
$dayofweek = date('w');
$now = date('H:i');

     if( $now > 7 && $now < 20 ) {
             echo '<div class="card-text d-flex align-items-start pb-3"><i class="text-azure bi bi-telephone"></i><div class="content-right ps-2"><a class="text-decoration-none" data-yext-field="phone" data-yext-id="[[*data-yext-id]]"></a></div></div>';
        }
        else {
             echo '&nbsp;';
        }