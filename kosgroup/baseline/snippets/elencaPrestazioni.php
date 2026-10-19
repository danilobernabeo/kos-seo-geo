<?php
$ist = $modx->resource->getTVValue('istanza');
$ch = curl_init('https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities?typologyTitle=' . $ist);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

$string = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_errno > 0) {
   echo "cURL Error ($curl_errno): $curl_error\n";
} else {
   $arr = json_decode($string, true);
   $articoli_congiunzioni = array('il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una', 'e', 'ed', 'o', 'di', 'a', 'da', 'in', 'con', 'su', 'per', 'tra', 'fra');
   
   echo '<ul class="lista-con-icone lista-icone-check">';
   foreach ($arr['return']['results'] as $value) {
       $prestazione = ucwords(strtolower($value['activityTitle']));
       $parole = explode(' ', $prestazione);
       
       foreach($parole as $key => $parola) {
           if(in_array(strtolower($parola), $articoli_congiunzioni)) {
               $parole[$key] = strtolower($parola);
           }
       }
       
       echo '<li>' . implode(' ', $parole) . '</li>';
   }
   echo '</ul>';
}