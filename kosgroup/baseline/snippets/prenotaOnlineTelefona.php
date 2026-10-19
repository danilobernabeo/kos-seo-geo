<?php
$ist= $modx->resource->getTVValue('istanza');
$ch = curl_init('https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities?areaid=' . $ist);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);
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
    
    // Aggiungi lo script principale per la ricerca sicura
    echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up"><div class="container"><div class="col xbox-faq">';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Funzione sicura per la ricerca nelle tabelle
        function cercaPrestazioneTabella(inputId, tableId) {
            var input = document.getElementById(inputId);
            var table = document.getElementById(tableId);
            
            // Verifica che gli elementi esistano prima di procedere
            if (!input || !table) {
                console.log("Elementi di ricerca prestazioni non trovati: " + inputId + ", " + tableId);
                return; // Esci dalla funzione se gli elementi non esistono
            }
            
            var filter = input.value.toUpperCase();
            var tr = table.getElementsByTagName("tr");
            
            // Verifica che tr sia una collezione valida
            if (tr && tr.length > 0) {
                for (var i = 0; i < tr.length; i++) {
                    var row = tr[i];
                    if (row) {
                        // Ottieni la cella con il testo (se esiste)
                        var td = row.getElementsByTagName("td")[0];
                        if (td) {
                            var txtValue = td.textContent || td.innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        }
                    }
                }
            }
        }
        
        // Esponi la funzione globalmente per gli input che la utilizzano
        window.cercaPrestazioneTabella = cercaPrestazioneTabella;
    });
    </script>';
    
    foreach ($branca as $key => $value) {
        $trimdash = str_replace('-', '', $key);
        $trimslash = str_replace('/', '', $trimdash);
        $trimapostrophe = str_replace("'", '', $trimslash);
        $trimbrackets = preg_replace("/\([^)]+\)/","",$trimapostrophe);
        $trimmed = str_replace(' ', '', $trimbrackets);
        
        // Crea ID univoci per evitare conflitti
        $safeAccordionId = 'acc_' . $trimmed;
        $safeInputId = 'input_' . $trimmed;
        $safeTableId = 'table_' . $trimmed;
        
        echo '<div class="faq-singola"><button class="faq-titolo accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#';
        echo $safeAccordionId;
        echo '" aria-expanded="false" aria-controls="';
        echo $safeAccordionId;
        echo '"><span>';
        $conta = count($value);
        echo ucwords(strtolower($key));
        echo ' <small>(' . $conta. ' prestazioni)</small></span>';
        echo '</button>';
        
        echo '<div class="faq-contenuto collapse" id="' . $safeAccordionId . '">';
        echo '<input class="form-control text-2 m-2" type="text" id="' . $safeInputId . '" placeholder="Cerca una prestazione di ' . htmlspecialchars($key) . '" title="Cerca una prestazione">';
        echo '<table id="' . $safeTableId . '" class="table table-striped mt-0"><thead><tr><th class="text-left text-2 align-middle text-nowrap">Prestazione</th><th></th></tr></thead><tbody>';
        
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
        
        // Aggiungi script per collegare l'input alla funzione di ricerca sicura
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var input_' . $trimmed . ' = document.getElementById("' . $safeInputId . '");
                if (input_' . $trimmed . ') {
                    input_' . $trimmed . '.addEventListener("keyup", function() {
                        cercaPrestazioneTabella("' . $safeInputId . '", "' . $safeTableId . '");
                    });
                }
            });
        </script>';
    }
    
    echo '</div></div></div>';
}