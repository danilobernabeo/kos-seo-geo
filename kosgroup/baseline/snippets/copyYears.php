<?php
$now = date("Y");
$startYear = $modx->getOption('startYear', $scriptProperties, $now);
$spacer = $modx->getOption('spacer', $scriptProperties, '-');
$years = ($now > $startYear) ? $startYear.$spacer.$now : $now;
return $years;