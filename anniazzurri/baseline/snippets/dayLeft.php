<?php
$today = time();
$daysofcurrentmonth = date('t');
$currentmonthnumber = date('n');
$currentyear = date('Y');
//$event = mktime(23,59,59,4,30,2021);
$event = mktime(23,59,59,$currentmonthnumber,$daysofcurrentmonth,$currentyear);

$countdown = round(($event - $today)/86400);

if($countdown > 1)
{
	echo "$countdown giorni";
} else {
	echo "oggi";	
}