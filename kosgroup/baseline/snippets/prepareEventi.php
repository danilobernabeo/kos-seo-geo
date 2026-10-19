<?php
$row['formatted_date'] = date('d-m-Y', strtotime($row['tv.eventoData']));
return $row;