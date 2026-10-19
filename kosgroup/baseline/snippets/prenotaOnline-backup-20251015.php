<?php
/* --------------------------------------------------------------------------
 * 1. Richiamo API + Setup Tracking
 * ------------------------------------------------------------------------ */
$ist = $modx->resource->getTVValue('istanza');

// Dati centro per tracking
$centroAreaid = $ist ?: 'unknown';
$centroNome = $modx->resource->get('pagetitle') ?: 'Centro KOS';

// Debug info (rimuovi in produzione)
echo '<!-- DEBUG: Centro = ' . htmlspecialchars($centroNome) . ', Areaid = ' . htmlspecialchars($centroAreaid) . ' -->';

$ch = curl_init(
    'https://app.tuotempo.com/api/v3/tt_kosgroup_portale/activities?areaid=' . $ist
);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOSIGNAL       => 1,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FORBID_REUSE   => 1,
    CURLOPT_FRESH_CONNECT  => 1,
    CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_3,
]);
$string      = curl_exec($ch);
$curl_errno  = curl_errno($ch);
$curl_error  = curl_error($ch);
curl_close($ch);

if ($curl_errno > 0) {
    echo "cURL Error ($curl_errno): $curl_error\n";
    return;
}

/* --------------------------------------------------------------------------
 * 2. Raggruppo per branca
 * ------------------------------------------------------------------------ */
$arr    = json_decode($string, true);
$branca = [];
foreach ($arr['return']['results'] as $value) {
    $branca[$value['typologyTitle']][] = $value;
}
ksort($branca);

/* --------------------------------------------------------------------------
 * 3. Markup accordion + tabelle accessibili + TRACKING
 * ------------------------------------------------------------------------ */
echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up">
        <div class="container">
        <div class="col xbox-faq">';

/* ---- Alert tracking attivo
echo '<div class="alert alert-info mb-4" role="alert">';
echo '<strong> TRACKING ATTIVO</strong> - Widget di prenotazione con tracking completo KOS → TuoTempo. ';
echo 'Centro: <strong>' . htmlspecialchars($centroNome) . '</strong> ';
echo '(' . array_sum(array_map('count', $branca)) . ' prestazioni disponibili)';
echo '</div>'; -------------------------------------------- */

/* ---- JS di ricerca + TRACKING COMPLETO -------------------------------- */
// VERSIONE SEMPLIFICATA - prenotaOnline.php
// Sostituisci la sezione JavaScript con questo codice pulito

echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    
    //  WIDGET SEMPLIFICATO - Solo source attribution
    console.log(" Widget KOS con tracking semplificato");
    
    // Funzione ricerca prestazioni (INVARIATA)
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
    
    // Funzione SEMPLIFICATA per tracking widget
    function handleBookingClickProduzione(event) {
        event.preventDefault();
        
        var button = event.target;
        
        // Ottieni solo dati essenziali dal bottone
        var prestazioneId = button.dataset.prestazioneId || "unknown";
        var centroAreaid = button.dataset.centroAreaid || "unknown";
        
        // Genera session ID per cross-domain tracking
        var sessionId = "kos_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5);
        
        // DataLayer SEMPLIFICATO - Solo source attribution
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            "event": "booking_start",
            "source_type": "widget",
            "referrer_page": window.location.pathname,
            "button_type": "dynamic",
            "timestamp": new Date().toISOString()
        });
        
        // URL con kos_session per far funzionare il tracking
        var baseUrl = "https://app.tuotempo.com/mop/index.php";
        var params = new URLSearchParams({
            "dbName": "tt_kosgroup_portale",
            "activityid": prestazioneId,
            "areaid": centroAreaid,
            "kos_session": sessionId,
            "kos_referrer": encodeURIComponent(window.location.pathname)
        });
        
        var finalUrl = baseUrl + "?" + params.toString();
        
        // Log semplificato
        console.log(" Widget booking start:", {
            source_type: "widget",
            referrer_page: window.location.pathname,
            prestazione_id: prestazioneId,
            redirect_url: finalUrl
        });
        
        // Redirect immediato
        setTimeout(function() {
            window.location.href = finalUrl;
        }, 300);
    }
    
    //  ATTACHMENT FUNCTION con retry (INVARIATA)
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
        
        console.log("✅ Event listeners attaccati a " + attached + " bottoni");
        return attached;
    }
    
    //  RETRY MECHANISM (INVARIATO)
    var retryCount = 0;
    var maxRetries = 10; // 5 secondi totali
    
    function tryAttachListeners() {
        var attached = attachEventListeners();
        
        if (attached === 0 && retryCount < maxRetries) {
            retryCount++;
            console.log(" Retry " + retryCount + "/" + maxRetries + " - Bottoni non ancora caricati...");
            setTimeout(tryAttachListeners, 500);
        } else if (attached > 0) {
            console.log("✅ Widget SEMPLIFICATO attivo - " + attached + " bottoni");
            
            //  MUTATION OBSERVER per bottoni dinamici (INVARIATO)
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
                    console.log(" Nuovi bottoni rilevati, riattaccando listeners...");
                    attachEventListeners();
                }
            });
            
            // Osserva tutto il documento per nuovi bottoni
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
        } else {
            console.warn("⚠️ Nessun bottone .prenota-btn-produzione trovato dopo " + maxRetries + " tentativi");
        }
    }
    
    // Esponi funzioni globalmente
    window.cercaPrestazioneTabella = cercaPrestazioneTabella;
    window.handleBookingClickProduzione = handleBookingClickProduzione;
    window.attachEventListeners = attachEventListeners; // Per debug
    
    //  AVVIA ATTACHMENT
    tryAttachListeners();
    
    console.log(" Widget semplificato - Solo source attribution");
    console.log(" DataLayer: source_type + referrer_page");
    console.log(" URL: Solo activityid + areaid");
});
</script>';

foreach ($branca as $titoloBranca => $prestazioni) {

    /* –– Normalizzo il titolo per creare ID univoci e sicuri –– */
    $baseId = preg_replace('/[^a-zA-Z0-9]/', '', $titoloBranca);
    $panelId = 'panel_' . $baseId;
    $btnId   = 'btn_'   . $baseId;
    $inputId = 'input_' . $baseId;
    $tableId = 'table_' . $baseId;
    $tot     = count($prestazioni);

    /* ---- HEADER accordion ------------------------------------------------ */
    echo '
    <div class="faq-singola">

        <button class="faq-titolo accordion-button"
                type="button"
                id="'. $btnId .'"
                data-bs-toggle="collapse"
                data-bs-target="#'. $panelId .'"
                aria-controls="'. $panelId .'"
                aria-expanded="false">
            <span>'. ucwords(strtolower($titoloBranca)) .' <small>(' . $tot . ' prestazioni)</small></span>
        </button>

        <!-- REGIONE COLLASSABILE -->
        <div id="'. $panelId .'"
             class="faq-contenuto collapse"
             role="region"
             aria-labelledby="'. $btnId .'">';

    /* ---- Campo ricerca con label sr-only -------------------------------- */
    echo '
            <label for="'. $inputId .'" class="sr-only">
                Cerca una prestazione di '. htmlspecialchars($titoloBranca) .'
            </label>
            <input id="'. $inputId .'"
                   class="form-control text-2 m-2"
                   type="text"
                   placeholder="Cerca una prestazione…"
                   onkeyup="cercaPrestazioneTabella(\''. $inputId .'\', \''. $tableId .'\')">';

    /* ---- Tabella accessibile ------------------------------------------- */
    echo '
            <table id="'. $tableId .'"
                   class="table table-striped mt-0"
                   aria-label="Prestazioni di '. htmlspecialchars($titoloBranca) .' prenotabili online">

                <caption class="sr-only">
                    Elenco delle prestazioni di '. htmlspecialchars($titoloBranca) .' prenotabili online
                </caption>

                <thead>
                    <tr>
                        <th scope="col" class="text-left text-2 align-middle">Prestazione</th>
                        <th scope="col" class="text-center align-middle">Prenotazione</th>
                    </tr>
                </thead>

                <tbody aria-live="polite">';

    /* ---- Righe singole CON TRACKING ------------------------------------ */
    $row = 1;
    foreach ($prestazioni as $p) {

        $rowId   = 'row-' . $baseId . '-' . $row++;
        $titolo  = ucfirst(strtolower($p['activityTitle']));
        
        // Dati sicuri per tracking
        $safePrestazioneTitolo = htmlspecialchars($p['activityTitle'], ENT_QUOTES, 'UTF-8');
        $safeActivityId = htmlspecialchars($p['activityid'], ENT_QUOTES, 'UTF-8');
        $safeTipologia = htmlspecialchars($titoloBranca, ENT_QUOTES, 'UTF-8');
        $safeCentroAreaid = htmlspecialchars($centroAreaid, ENT_QUOTES, 'UTF-8');
        $safeCentroNome = htmlspecialchars($centroNome, ENT_QUOTES, 'UTF-8');
        $safeTitolo = htmlspecialchars($titolo, ENT_QUOTES, 'UTF-8');

        echo '
                    <tr>
                        <th scope="row" id="'. $rowId .'" class="align-middle" style="width:80%">
                            '. $safeTitolo .'
                        </th>
                        <td class="text-center align-middle" style="width:20%">
                            <a class="btn btn-primary prenota-btn-produzione"
                               href="#"
                               data-prestazione-titolo="'. $safePrestazioneTitolo .'"
                               data-prestazione-id="'. $safeActivityId .'"
                               data-tipologia="'. $safeTipologia .'"
                               data-centro-areaid="'. $safeCentroAreaid .'"
                               data-centro-nome="'. $safeCentroNome .'"
                               aria-describedby="'. $rowId .'"
                               aria-label="Prenota on-line la prestazione: '. $safeTitolo .'"
                               title="Prenota '. $safeTitolo .' - Tracking attivo">
                                Prenota
                            </a>
                        </td>
                    </tr>';
    }

    /* ---- Contatore risultati live (invisibile) ------------------------- */
    echo '
                    <tr class="sr-only">
                        <td colspan="2">
                            <span data-live-count>'. $tot .'</span> risultati visibili
                        </td>
                    </tr>

                </tbody>
            </table>
        </div> <!-- /faq-contenuto -->
    </div>';     // /faq-singola
}

/* ---------------------------------------------------------------------- */
echo '  </div></div></div>';   // chiusura container & accordion