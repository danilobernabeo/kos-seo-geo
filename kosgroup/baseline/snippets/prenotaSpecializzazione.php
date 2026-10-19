<?php
$ist= $modx->resource->getTVValue('istanza');
    $ch = curl_init('https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities?typologyTitle=' . $ist);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    // $tplChunk = $modx->getOption('tpl', $scriptProperties, '');
    $string = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_errno > 0) {
       echo "cURL Error ($curl_errno): $curl_error\n";
    } else {
    $arr = json_decode($string,true);

    $branca = array();

    foreach ($arr['return']['results'] as $value ) {
        $branca[$value['typologyTitle']][] = $value;
    }

    ksort($branca);

    echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up"><div class="container"><div class="col xbox-faq">';
    foreach ($branca as $key => $value) {
        $trimdash = str_replace('-', '', $key);
        $trimslash = str_replace('/', '', $trimdash);
        $trimapostrophe = str_replace("'", '', $trimslash);
        $trimbrackets = preg_replace("/\([^)]+\)/","",$trimapostrophe);
        $trimmed = str_replace(' ', '', $trimbrackets);
        echo '<div class="faq-singola"><button class="faq-titolo accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#';
        echo $trimmed;
        echo '" aria-expanded="false" aria-controls="';
        echo $trimmed;
        echo '"><span>';
        $conta = count($value);
        echo ucwords(strtolower($key));
        echo ' <small>(' . $conta. ' prestazioni)</small></span>';
        echo '</button>';
        echo '<div class="faq-contenuto collapse" id="' . $trimmed . '"><input class="form-control text-2 m-2" type="text" id="' . $trimmed . 'Input" onkeyup="' . $trimmed . 'Function()" placeholder="Cerca una prestazione di ' . $key . '" title="Cerca una prestazione">';
        echo '<table id="' . $trimmed . '" class="table table-striped mt-0"><thead><tr><th class="text-left text-2 align-middle text-nowrap">Prestazione</th><th></th></tr></thead><tbody>';
        foreach($value as $key2 => $value2){
            echo '<tr><td class="align-middle" style="width:80%">';
            echo strtoupper($value2['activityTitle']);
            echo '</td>';
            echo '<td class="text-center align-middle" style="width:20%">';
            if ($value2['isHidden'] == 1) {
                echo '<a class="btn btn-secondary" href="tel:+39[[*telPrestazione]]"><i class="icon-phone"></i> TELEFONA</a>';
               } else {
                echo '<a class="btn btn-primary" href="https://app.tuotempo.com/mop/index.php?dbName=tt_kosgroup_portale&activityid=';
                echo $value2['activityid'];
                echo '&areaid=' . $ist;
                echo '" target="_blank" title="Prenota online">PRENOTA</a>';
            }
            echo '</td></tr>';
        }
    echo '</tbody></table></div></div>';
    echo '<script>function ' . $trimmed . 'Function() {  var input, filter, table, tr, td, i, txtValue;  input = document.getElementById("' . $trimmed . 'Input");  filter = input.value.toUpperCase();  table = document.getElementById("' . $trimmed . '");  tr = table.getElementsByTagName("tr"); for (i = 0; i < tr.length; i++) { td = tr[i].getElementsByTagName("td")[0]; if (td) { txtValue = td.textContent || td.innerText; if (txtValue.toUpperCase().indexOf(filter) > -1) { tr[i].style.display = ""; } else {        tr[i].style.display = "none"; } } }}</script>';
    }
    echo '</div></div></div>';
    }