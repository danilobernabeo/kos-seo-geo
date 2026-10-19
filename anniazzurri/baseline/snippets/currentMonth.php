<?php
// set the default timezone to use. Available since PHP 5.1
date_default_timezone_set('UTC');
setlocale(LC_ALL, 'it_IT');

// Prints something like: Monday
$str = strtoupper(strftime("%B"));
echo $str;