<?php
/**
 * Snippet per mostrare le prestazioni disponibili filtrate per branche specifiche
 * CON TRACKING COMPLETO KOS → TuoTempo
 * 
 * @param string $branche (opzionale) Elenco separato da virgole delle branche da mostrare
 */

// Recupera l'istanza dalla TV
$ist = $modx->resource->getTVValue('istanza');

// Dati centro per tracking
$centroAreaid = $ist ?: 'unknown';
$centroNome = $modx->resource->get('pagetitle') ?: 'Centro KOS';

// Prendi le branche da mostrare dal parametro dello snippet
$brancheDaMostrare = $modx->getOption('branche', $scriptProperties, '');

// Converti le branche in un array (senza normalizzazione per conservare il case originale)
$brancheArray = [];
if (!empty($brancheDaMostrare)) {
    $temp = explode(',', $brancheDaMostrare);
    foreach ($temp as $branca) {
        $brancheArray[] = trim($branca); // Mantiene il case originale
    }
}

// Chiamata API
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
    $arr = json_decode($string, true);
    $branca = array();
    
    foreach ($arr['return']['results'] as $value) {
        $branca[$value['typologyTitle']][] = $value;
    }
    
    // Applica il filtro per branche se specificato
    if (!empty($brancheArray)) {
        $brancaFiltrata = [];
        foreach ($branca as $key => $value) {
            // Confronto case-insensitive - normalizza entrambi i valori
            $keyNormalized = strtolower(trim($key));
            foreach ($brancheArray as $brancaFiltro) {
                if ($keyNormalized === strtolower(trim($brancaFiltro))) {
                    $brancaFiltrata[$key] = $value;
                    break;
                }
            }
        }
        $branca = $brancaFiltrata;
    }
    
    ksort($branca);
    echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up"><div class="container"><div class="col xbox-faq">';
    
    /* ---- TRACKING JAVASCRIPT + Ricerca -------------------------------- */
    echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    
    console.log("???? Snippet prestazioni con tracking KOS inizializzato");
    
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
    
    // Funzione TRACKING per bottoni prenotazione
    function handleBookingClickProduzione(event) {
        event.preventDefault();
        
        var button = event.target;
        
        // Ottieni dati dal bottone
        var prestazioneId = button.dataset.prestazioneId || "unknown";
        var centroAreaid = button.dataset.centroAreaid || "unknown";
        var prestazioneTitolo = button.dataset.prestazioneTitolo || "Prestazione";
        var tipologia = button.dataset.tipologia || "Prestazione";
        
        // Genera session ID per cross-domain tracking
        var sessionId = "kos_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5);
        
        // DataLayer tracking
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            "event": "kos_widget_booking_start",
            "source_type": "widget",
            "referrer_page": window.location.pathname,
            "button_type": "snippet_filtered",
            "prestazione_tipologia": tipologia,
            "prestazione_titolo": prestazioneTitolo,
            "centro_areaid": centroAreaid,
            "timestamp": new Date().toISOString()
        });
        
        // URL redirect con tracking
        var baseUrl = "https://app.tuotempo.com/mop/index.php";
        var params = new URLSearchParams({
            "dbName": "tt_kosgroup_portale",
            "activityid": prestazioneId,
            "areaid": centroAreaid,
            "kos_session": sessionId,
            "kos_referrer": encodeURIComponent(window.location.pathname)
        });
        
        var finalUrl = baseUrl + "?" + params.toString();
        
        console.log("???? Snippet booking start:", {
            source_type: "widget",
            button_type: "snippet_filtered",
            referrer_page: window.location.pathname,
            prestazione_id: prestazioneId,
            redirect_url: finalUrl
        });
        
        // Redirect con delay per GTM
        setTimeout(function() {
            window.location.href = finalUrl;
        }, 300);
    }
    
    // Attachment function per bottoni dinamici
    function attachEventListeners() {
        var prenotaButtons = document.querySelectorAll(".prenota-btn-produzione");
        var attached = 0;
        
        prenotaButtons.forEach(function(button) {
            // Rimuovi listener esistenti per evitare duplicati
            button.removeEventListener("click", handleBookingClickProduzione);
            // Aggiungi nuovo listener
            button.addEventListener("click", handleBookingClickProduzione);
            attached++;
        });
        
        console.log("✅ Event listeners attaccati a " + attached + " bottoni snippet");
        return attached;
    }
    
    // Retry mechanism per bottoni caricati dinamicamente
    var retryCount = 0;
    var maxRetries = 10;
    
    function tryAttachListeners() {
        var attached = attachEventListeners();
        
        if (attached === 0 && retryCount < maxRetries) {
            retryCount++;
            console.log("???? Retry " + retryCount + "/" + maxRetries + " - Bottoni snippet non ancora caricati...");
            setTimeout(tryAttachListeners, 500);
        } else if (attached > 0) {
            console.log("✅ Snippet tracking attivo - " + attached + " bottoni");
            
            // MutationObserver per nuovi bottoni
            var observer = new MutationObserver(function(mutations) {
                var needsReattach = false;
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        for (var i = 0; i < mutation.addedNodes.length; i++) {
                            var node = mutation.addedNodes[i];
                            if (node.nodeType === 1 && 
                                (node.classList.contains("prenota-btn-produzione") || 
                                 node.querySelector(".prenota-btn-produzione"))) {
                                needsReattach = true;
                                break;
                            }
                        }
                    }
                });
                
                if (needsReattach) {
                    console.log("???? Nuovi bottoni snippet rilevati, riattaccando listeners...");
                    attachEventListeners();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
        } else {
            console.warn("⚠️ Nessun bottone .prenota-btn-produzione trovato nel snippet");
        }
    }
    
    // Esponi funzioni globalmente
    window.cercaPrestazioneTabella = cercaPrestazioneTabella;
    window.handleBookingClickProduzione = handleBookingClickProduzione;
    
    // Avvia attachment con delay per accordion loading
    setTimeout(function() {
        tryAttachListeners();
    }, 500);
    
    console.log("???? Snippet prestazioni tracking completo inizializzato");
});
</script>';
    
    foreach ($branca as $key => $value) {
        $trimdash = str_replace('-', '', $key);
        $trimslash = str_replace('/', '', $trimdash);
        $trimapostrophe = str_replace("'", '', $trimslash);
        $trimbrackets = preg_replace("/\([^)]+\)/","",$trimapostrophe);
        $trimmed = str_replace(' ', '', $trimbrackets);
        $safeId = 'tab_' . $trimmed; // Prefisso per garantire un ID valido
        
        echo '<div class="faq-singola"><button class="faq-titolo accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#';
        echo $safeId;
        echo '" aria-expanded="false" aria-controls="';
        echo $safeId;
        echo '"><span>';
        $conta = count($value);
        echo ucwords(strtolower($key));
        echo ' <small>(' . $conta. ' prestazioni)</small></span>';
        echo '</button>';
        
        // Aggiungi ID più sicuri e univoci per input e tabella
        $inputId = 'input_' . $safeId;
        $tableId = 'table_' . $safeId;
        
        echo '<div class="faq-contenuto collapse" id="' . $safeId . '">';
        echo '<label for="' . $inputId . '" class="sr-only">Cerca una prestazione di ' . htmlspecialchars($key) . '</label>';
        echo '<input class="form-control text-2 m-2" type="text" id="' . $inputId . '" placeholder="Cerca una prestazione di ' . htmlspecialchars($key) . '" title="Cerca una prestazione" onkeyup="cercaPrestazioneTabella(\'' . $inputId . '\', \'' . $tableId . '\')">';
        echo '<table id="' . $tableId . '" class="table table-striped mt-0" aria-label="Prestazioni di ' . htmlspecialchars($key) . ' prenotabili online">';
        echo '<caption class="sr-only">Elenco delle prestazioni di ' . htmlspecialchars($key) . ' prenotabili online</caption>';
        echo '<thead><tr><th scope="col" class="text-left text-2 align-middle">Prestazione</th><th scope="col" class="text-center align-middle">Prenotazione</th></tr></thead><tbody>';
        
        $row = 1;
        foreach($value as $key2 => $value2){
            $rowId = 'row-' . $safeId . '-' . $row++;
            $titolo = ucwords(strtolower($value2['activityTitle']));
            
            // Dati sicuri per tracking
            $safePrestazioneTitolo = htmlspecialchars($value2['activityTitle'], ENT_QUOTES, 'UTF-8');
            $safeActivityId = htmlspecialchars($value2['activityid'], ENT_QUOTES, 'UTF-8');
            $safeTipologia = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            $safeCentroAreaid = htmlspecialchars($centroAreaid, ENT_QUOTES, 'UTF-8');
            $safeCentroNome = htmlspecialchars($centroNome, ENT_QUOTES, 'UTF-8');
            $safeTitolo = htmlspecialchars($titolo, ENT_QUOTES, 'UTF-8');
            
            echo '<tr>';
            echo '<th scope="row" id="' . $rowId . '" class="align-middle" style="width:80%">';
            echo $safeTitolo;
            echo '</th>';
            echo '<td class="text-center align-middle" style="width:20%">';
            echo '<a class="btn btn-primary prenota-btn-produzione"';
            echo ' href="#"';
            echo ' data-prestazione-titolo="' . $safePrestazioneTitolo . '"';
            echo ' data-prestazione-id="' . $safeActivityId . '"';
            echo ' data-tipologia="' . $safeTipologia . '"';
            echo ' data-centro-areaid="' . $safeCentroAreaid . '"';
            echo ' data-centro-nome="' . $safeCentroNome . '"';
            echo ' aria-describedby="' . $rowId . '"';
            echo ' aria-label="Prenota on-line la prestazione: ' . $safeTitolo . '"';
            echo ' title="Prenota ' . $safeTitolo . ' - Tracking attivo">';
            echo 'PRENOTA';
            echo '</a>';
            echo '</td></tr>';
        }
        
        echo '</tbody></table></div></div>';
    }
    
    echo '</div></div></div>';
}