<?php
/* ==========================================================================
 * WIDGET PRENOTAZIONE KOS - VERSIONE FINALE GA4 NATIVO + KOS_REFERRER
 * GA4 cross-domain nativo (parametro _gl) + tracking pagina referrer
 * Zero session ID custom, zero source_type custom
 * ========================================================================== */

/* --------------------------------------------------------------------------
 * 1. Richiamo API TuoTempo
 * ------------------------------------------------------------------------ */
$ist = $modx->resource->getTVValue('istanza');

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
 * 3. Markup accordion + tabelle accessibili
 * ------------------------------------------------------------------------ */
echo '<div class="accordion-faq-approfondimenti" data-aos="fade-up">
        <div class="container">
        <div class="col xbox-faq">';

/* --------------------------------------------------------------------------
 * JavaScript per ricerca (UX) + Gestione kos_referrer
 * ------------------------------------------------------------------------ */
echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    
    /* ====== GESTIONE KOS_REFERRER ====== */
    // Cattura la pagina corrente per tracking in GA4
    var currentPagePath = window.location.pathname;
    
    // Funzione per aggiungere kos_referrer ai link di prenotazione
    function addReferrerToBookingLinks() {
        var bookingLinks = document.querySelectorAll(".btn-prenota-kos");
        
        bookingLinks.forEach(function(link) {
            var href = link.getAttribute("href");
            if (!href) return;
            
            // Aggiungi kos_referrer se non già presente
            if (href.indexOf("kos_referrer=") === -1) {
                var separator = href.indexOf("?") > -1 ? "&" : "?";
                var encodedPath = encodeURIComponent(currentPagePath);
                link.setAttribute("href", href + separator + "kos_referrer=" + encodedPath);
            }
        });
        
        console.log("✅ KOS referrer tracking attivo:", currentPagePath);
    }
    
    // Attiva tracking referrer
    addReferrerToBookingLinks();
    
    /* ====== FUNZIONE RICERCA PRESTAZIONI (solo UX, nessun tracking) ====== */
    function cercaPrestazioneTabella(inputId, tableId) {
        var input = document.getElementById(inputId);
        var table = document.getElementById(tableId);
        if (!input || !table) return;

        var filter = input.value.toUpperCase();
        var rows   = table.getElementsByTagName("tr");
        var visibili = 0;

        for (var i = 0; i < rows.length; i++) {
            var cell = rows[i].getElementsByTagName("th")[0];
            if (!cell) continue;

            var match = cell.textContent.toUpperCase().indexOf(filter) > -1;
            rows[i].style.display = match ? "" : "none";
            if (match) visibili++;
        }

        var live = table.querySelector("[data-live-count]");
        if (live) live.textContent = visibili;
    }
    
    window.cercaPrestazioneTabella = cercaPrestazioneTabella;
});
</script>';

/* --------------------------------------------------------------------------
 * 4. Loop accordion per branche
 * ------------------------------------------------------------------------ */
foreach ($branca as $titoloBranca => $prestazioni) {

    // ID univoci per accordion
    $baseId  = preg_replace('/[^a-zA-Z0-9]/', '', $titoloBranca);
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

        <div id="'. $panelId .'"
             class="faq-contenuto collapse"
             role="region"
             aria-labelledby="'. $btnId .'">';

    /* ---- Campo ricerca --------------------------------------------------- */
    echo '
            <label for="'. $inputId .'" class="sr-only">
                Cerca una prestazione di '. htmlspecialchars($titoloBranca) .'
            </label>
            <input id="'. $inputId .'"
                   class="form-control text-2 m-2"
                   type="text"
                   placeholder="Cerca una prestazione…"
                   onkeyup="cercaPrestazioneTabella(\''. $inputId .'\', \''. $tableId .'\')">';

    /* ---- Tabella prestazioni --------------------------------------------- */
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

    /* ---- Righe singole --------------------------------------------------- */
    $row = 1;
    foreach ($prestazioni as $p) {

        $rowId   = 'row-' . $baseId . '-' . $row++;
        $titolo  = ucfirst(strtolower($p['activityTitle']));
        
        // URL con parametri base - JavaScript aggiungerà kos_referrer
        // GA4 aggiungerà automaticamente _gl per cross-domain
        $linkUrl = 'https://app.tuotempo.com/mop/index.php?dbName=tt_kosgroup_portale'
                 . '&activityid=' . $p['activityid']
                 . '&areaid='     . $ist;

        echo '
                    <tr>
                        <th scope="row" id="'. $rowId .'" class="align-middle" style="width:80%">
                            '. htmlspecialchars($titolo) .'
                        </th>
                        <td class="text-center align-middle" style="width:20%">
                            <a class="btn btn-primary btn-prenota-kos"
                               href="'. $linkUrl .'"
                               aria-describedby="'. $rowId .'"
                               aria-label="Prenota on-line la prestazione: '. htmlspecialchars($titolo) .'">
                                Prenota
                            </a>
                        </td>
                    </tr>';
    }

    /* ---- Contatore risultati live ---------------------------------------- */
    echo '
                    <tr class="sr-only">
                        <td colspan="2">
                            <span data-live-count>'. $tot .'</span> risultati visibili
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>';
}

/* --------------------------------------------------------------------------
 * Chiusura container
 * ------------------------------------------------------------------------ */
echo '  </div></div></div>';