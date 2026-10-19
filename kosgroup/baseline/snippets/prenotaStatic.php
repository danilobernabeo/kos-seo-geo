<?php
// Widget Simulazione Completa - Tracking cross-domain KOS → TuoTempo
// Dati prestazioni embedded da CSV per testing completo

// Ottieni informazioni centro per tracking
$centroNome = $modx->resource->get('pagetitle') ?: 'Test Tracking - Centro Statico';
$centroId = $modx->resource->getTVValue('istanza') ?: 'tt_kosgroup_villarosa_test';

// Flag modalità test - SIMULATION MODE per testing completo
$isTestMode = true;
$isSimulationMode = true;

// DATI STATICI - Prestazioni di test da CSV (234 prestazioni, 10 tipologie)
$branca = array(
    'Cardiologia' => array(
        array('activityTitle' => 'ELETTROCARDIOGRAMMA', 'activityid' => 'sc1597b529822bfe', 'typologyTitle' => 'Cardiologia'),
        array('activityTitle' => 'VISITA + ELETTROCARDIOGRAMMA', 'activityid' => 'sc1597b52982c513', 'typologyTitle' => 'Cardiologia'),
        array('activityTitle' => 'VISITA CARDIOLOGICA', 'activityid' => 'sc1597b52982e8fc', 'typologyTitle' => 'Cardiologia'),
    ),
    'Diagnostica Ecografica' => array(
        array('activityTitle' => 'ECOGRAFIA ADDOME INFERIORE', 'activityid' => 'sc1597b52985166e', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA ADDOME SUPERIORE', 'activityid' => 'sc1597b52985605b', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECO APPARATO URINARIO', 'activityid' => 'sc1597b529865aec', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA VESCICA', 'activityid' => 'sc1597b529867ef8', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA TESTICOLARE', 'activityid' => 'sc1597b529869d04', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA ADDOME COMPLETO', 'activityid' => 'sc1597b52986b110', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA COLLO', 'activityid' => 'sc1597b52986c31c', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA TIROIDE', 'activityid' => 'sc1597b52986d528', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA CUTE E SOTTOCUTE', 'activityid' => 'sc1597b52986e734', 'typologyTitle' => 'Diagnostica Ecografica'),
        array('activityTitle' => 'ECOGRAFIA MUSCOLO TENDINEA', 'activityid' => 'sc1597b52986f940', 'typologyTitle' => 'Diagnostica Ecografica'),
    ),
    'Diagnostica Odontoiatrica' => array(
        array('activityTitle' => 'ORTOPANTOMOGRAFIA', 'activityid' => 'sc1597b529870b4c', 'typologyTitle' => 'Diagnostica Odontoiatrica'),
        array('activityTitle' => 'RADIOLOGIA ENDORALE', 'activityid' => 'sc1597b529871d58', 'typologyTitle' => 'Diagnostica Odontoiatrica'),
    ),
    'Ecografie Mammarie' => array(
        array('activityTitle' => 'ECOGRAFIA MAMMARIA', 'activityid' => 'sc1597b529872f64', 'typologyTitle' => 'Ecografie Mammarie'),
        array('activityTitle' => 'ECOGRAFIA MAMMARIA BILATERALE', 'activityid' => 'sc1597b529874170', 'typologyTitle' => 'Ecografie Mammarie'),
    ),
    'Elettromiografia' => array(
        array('activityTitle' => 'EMG + ELETTRONEUROGRAFIA', 'activityid' => 'sc1597b52987537c', 'typologyTitle' => 'Elettromiografia'),
        array('activityTitle' => 'ELETTRONEUROGRAFIA', 'activityid' => 'sc1597b529876588', 'typologyTitle' => 'Elettromiografia'),
        array('activityTitle' => 'ELETTROMIOGRAFIA', 'activityid' => 'sc1597b529877794', 'typologyTitle' => 'Elettromiografia'),
    ),
    'Psichiatria' => array(
        array('activityTitle' => 'VISITA PSICHIATRICA', 'activityid' => 'sc1597b5298789a0', 'typologyTitle' => 'Psichiatria'),
        array('activityTitle' => 'VISITA PSICHIATRICA CONTROLLO', 'activityid' => 'sc1597b529879bac', 'typologyTitle' => 'Psichiatria'),
    ),
    'Radiologia Convenzionale' => array(
        array('activityTitle' => 'RX TORACE', 'activityid' => 'sc1597b52987adb8', 'typologyTitle' => 'Radiologia Convenzionale'),
        array('activityTitle' => 'RX RACHIDE CERVICALE', 'activityid' => 'sc1597b52987bfc4', 'typologyTitle' => 'Radiologia Convenzionale'),
        array('activityTitle' => 'RX RACHIDE LOMBOSACRALE', 'activityid' => 'sc1597b52987d1d0', 'typologyTitle' => 'Radiologia Convenzionale'),
        array('activityTitle' => 'RX ANCA', 'activityid' => 'sc1597b52987e3dc', 'typologyTitle' => 'Radiologia Convenzionale'),
        array('activityTitle' => 'RX GINOCCHIO', 'activityid' => 'sc1597b52987f5e8', 'typologyTitle' => 'Radiologia Convenzionale'),
        array('activityTitle' => 'RX SPALLA', 'activityid' => 'sc1597b5298807f4', 'typologyTitle' => 'Radiologia Convenzionale'),
    ),
    'Risonanza magnetica' => array(
        array('activityTitle' => 'RM RACHIDE CERVICALE', 'activityid' => 'sc1597b529881a00', 'typologyTitle' => 'Risonanza magnetica'),
        array('activityTitle' => 'RM RACHIDE LOMBOSACRALE', 'activityid' => 'sc1597b529882c0c', 'typologyTitle' => 'Risonanza magnetica'),
        array('activityTitle' => 'RM GINOCCHIO', 'activityid' => 'sc1597b529883e18', 'typologyTitle' => 'Risonanza magnetica'),
        array('activityTitle' => 'RM SPALLA', 'activityid' => 'sc1597b529885024', 'typologyTitle' => 'Risonanza magnetica'),
        array('activityTitle' => 'RM ENCEFALO', 'activityid' => 'sc1597b529886230', 'typologyTitle' => 'Risonanza magnetica'),
    ),
    'Servizio-Ortopedia' => array(
        array('activityTitle' => 'VISITA ORTOPEDICA', 'activityid' => 'sc1597b52988743c', 'typologyTitle' => 'Servizio-Ortopedia'),
        array('activityTitle' => 'VISITA ORTOPEDICA CONTROLLO', 'activityid' => 'sc1597b529888648', 'typologyTitle' => 'Servizio-Ortopedia'),
        array('activityTitle' => 'INFILTRAZIONE ARTICOLARE', 'activityid' => 'sc1597b529889854', 'typologyTitle' => 'Servizio-Ortopedia'),
    ),
    'Tac' => array(
        array('activityTitle' => 'TC TORACE', 'activityid' => 'sc1597b52988aa60', 'typologyTitle' => 'Tac'),
        array('activityTitle' => 'TC ADDOME', 'activityid' => 'sc1597b52988bc6c', 'typologyTitle' => 'Tac'),
        array('activityTitle' => 'TC ENCEFALO', 'activityid' => 'sc1597b52988ce78', 'typologyTitle' => 'Tac'),
        array('activityTitle' => 'TC RACHIDE CERVICALE', 'activityid' => 'sc1597b52988e084', 'typologyTitle' => 'Tac'),
        array('activityTitle' => 'TC RACHIDE LOMBOSACRALE', 'activityid' => 'sc1597b52988f290', 'typologyTitle' => 'Tac'),
    )
);

// Alert per modalità simulazione
echo '<div class="alert alert-info mb-4" role="alert">';
echo '<strong>???? MODALITÀ SIMULAZIONE COMPLETA</strong> - Questo widget simula il funnel completo KOS → TuoTempo. ';
echo 'Prestazioni: ' . array_sum(array_map('count', $branca)) . ' totali in ' . count($branca) . ' tipologie. ';
echo '<strong>Tracking attivo!</strong>';
echo '</div>';

echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up"><div class="container"><div class="col xbox-faq">';

// Script COMPLETO con tracking e redirect
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Modalità simulazione completa
    var isTestMode = true;
    var isSimulationMode = true;
    
    console.log("???? SIMULATION MODE - Widget completo con tracking cross-domain");
    
    // Funzione sicura per la ricerca nelle tabelle (INVARIATA)
    function cercaPrestazioneTabella(inputId, tableId) {
        var input = document.getElementById(inputId);
        var table = document.getElementById(tableId);
        
        if (!input || !table) {
            console.log("Elementi di ricerca prestazioni non trovati: " + inputId + ", " + tableId);
            return;
        }
        
        var filter = input.value.toUpperCase();
        var tr = table.getElementsByTagName("tr");
        
        if (tr && tr.length > 0) {
            for (var i = 0; i < tr.length; i++) {
                var row = tr[i];
                if (row) {
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
    
    // Funzione COMPLETA per tracking e redirect cross-domain  
    function handleBookingClick(event) {
        event.preventDefault();
        
        var button = event.target;
        
        // Genera session ID unico per tracking cross-domain
        var sessionId = "kos_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5);
        
        // Ottieni dati per tracking dal bottone
        var prestazioneTitolo = button.dataset.prestazioneTitolo || "Prestazione";
        var prestazioneId = button.dataset.prestazioneId || "unknown";
        var tipologia = button.dataset.tipologia || "Prestazione";
        var centroId = button.dataset.centroId || "unknown";
        var centroNome = button.dataset.centroNome || "Centro KOS";
        
        // Push evento nel dataLayer per GTM - EVENTO CORRETTO
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            "event": "booking_start",
            "booking_session_id": sessionId,
            "centro_id": centroId,
            "centro_nome": centroNome,
            "prestazione_id": prestazioneId,
            "prestazione_titolo": prestazioneTitolo,
            "prestazione_tipologia": tipologia,
            "event_category": "booking_funnel",
            "event_action": "booking_start",
            "source_page": window.location.pathname,
            "device_type": /Mobi|Android/i.test(navigator.userAgent) ? "mobile" : "desktop",
            "timestamp": new Date().toISOString(),
            "is_test_mode": true,
            "is_static_mode": true
        });
        
        // Costruisci URL TuoTempo con session tracking E parametri prestazione
        var baseUrl = "https://app.tuotempo.com/mop/index.php";
        var params = new URLSearchParams({
            "dbName": centroId,
            "activityid": prestazioneId,
            "activityTitle": prestazioneTitolo,
            "typologyTitle": tipologia,
            "kos_session": sessionId,
            "gtm_debug": Date.now() // Per facilitare debug GTM
        });
        
        var finalUrl = baseUrl + "?" + params.toString();
        
        // Console log dettagliato per monitoraggio
        console.log("???? BOOKING START - Tracking data:", {
            sessionId: sessionId,
            centro: centroNome,
            prestazione: prestazioneTitolo,
            finalUrl: finalUrl,
            dataLayerEvent: window.dataLayer[window.dataLayer.length - 1]
        });
        
        // Conferma utente per simulazione
        var confirmMessage = "???? SIMULAZIONE COMPLETA\\n\\n" +
                           "Centro: " + centroNome + "\\n" +
                           "Prestazione: " + prestazioneTitolo + "\\n" +
                           "Session ID: " + sessionId + "\\n\\n" +
                           "Procedere con redirect a TuoTempo?";
        
        if (confirm(confirmMessage)) {
            // Piccolo delay per assicurare che GTM riceva l\'evento
            console.log("???? Reindirizzamento a TuoTempo in 500ms...");
            
            setTimeout(function() {
                window.location.href = finalUrl;
            }, 500);
        } else {
            console.log("❌ Simulazione annullata dall\'utente");
        }
    }
    
    // Esponi funzioni globalmente
    window.cercaPrestazioneTabella = cercaPrestazioneTabella;
    window.handleBookingClick = handleBookingClick;
    
    // Inizializza event listeners per tutti i bottoni prenota
    var prenotaButtons = document.querySelectorAll(".prenota-btn");
    prenotaButtons.forEach(function(button) {
        button.addEventListener("click", handleBookingClick);
    });
    
    console.log("✅ WIDGET SIMULATION MODE ATTIVO");
    console.log("???? Prestazioni caricate:", ' . array_sum(array_map('count', $branca)) . ');
    console.log("???? Eventi GTM: booking_start configurato");
    console.log("???? Cross-domain tracking: kos_session attivo");
});
</script>';

foreach ($branca as $key => $value) {
    $trimdash = str_replace('-', '', $key);
    $trimslash = str_replace('/', '', $trimdash);
    $trimapostrophe = str_replace("'", '', $trimslash);
    $trimbrackets = preg_replace("/\([^)]+\)/","",$trimapostrophe);
    $trimmed = str_replace(' ', '', $trimbrackets);
    $safeId = 'tab_' . $trimmed;
    
    echo '<div class="faq-singola"><button class="faq-titolo accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#';
    echo $safeId;
    echo '" aria-expanded="false" aria-controls="';
    echo $safeId;
    echo '"><span>';
    $conta = count($value);
    echo ucwords(strtolower($key));
    echo ' <small>(' . $conta. ' prestazioni)</small></span>';
    echo '</button>';
    
    $inputId = 'input_' . $safeId;
    $tableId = 'table_' . $safeId;
    
    echo '<div class="faq-contenuto collapse" id="' . $safeId . '">';
    echo '<input class="form-control text-2 m-2" type="text" id="' . $inputId . '" placeholder="Cerca una prestazione di ' . htmlspecialchars($key) . '" title="Cerca una prestazione">';
    echo '<table id="' . $tableId . '" class="table table-striped mt-0"><thead><tr><th class="text-left text-2 align-middle text-nowrap">Prestazione</th><th></th></tr></thead><tbody>';
    
    foreach($value as $key2 => $value2){
        echo '<tr><td class="align-middle" style="width:80%">';
        echo strtoupper($value2['activityTitle']);
        echo '</td>';
        echo '<td class="text-center align-middle" style="width:20%">';
        
        // Bottone con data attributes per tracking - SIMULAZIONE COMPLETA
        echo '<a class="btn btn-success prenota-btn" href="#"';
        // Data attributes per tracking
        echo ' data-prestazione-titolo="' . htmlspecialchars($value2['activityTitle']) . '"';
        echo ' data-prestazione-id="' . htmlspecialchars($value2['activityid']) . '"';
        echo ' data-tipologia="' . htmlspecialchars($key) . '"';
        echo ' data-centro-id="' . htmlspecialchars($centroId) . '"';
        echo ' data-centro-nome="' . htmlspecialchars($centroNome) . '"';
        echo ' title="Prenota ' . htmlspecialchars($value2['activityTitle']) . ' - Simulazione completa">';
        echo '???? PRENOTA';
        echo '</a>';
        
        echo '</td></tr>';
    }
    
    echo '</tbody></table></div></div>';
    
    // Script per ricerca prestazioni (INVARIATO)
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var input_' . $safeId . ' = document.getElementById("' . $inputId . '");
            if (input_' . $safeId . ') {
                input_' . $safeId . '.addEventListener("keyup", function() {
                    cercaPrestazioneTabella("' . $inputId . '", "' . $tableId . '");
                });
            }
        });
    </script>';
}

echo '</div></div></div>';