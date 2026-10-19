<?php
/* --------------------------------------------------------------------------
 * 1. Richiamo API
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

/* ---- JS di ricerca unico (resta invariato) ----------------------------- */
echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    function cercaPrestazioneTabella(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if (!input || !table) return;

        const filter = input.value.toUpperCase();
        const rows   = table.getElementsByTagName("tr");
        let visibili = 0;

        for (let i = 0; i < rows.length; i++) {
            const cell = rows[i].getElementsByTagName("th")[0]; // ora è <th scope="row">
            if (!cell) continue;

            const match = cell.textContent.toUpperCase().indexOf(filter) > -1;
            rows[i].style.display = match ? "" : "none";
            if (match) visibili++;
        }

        // annuncio live dei risultati
        const live = table.querySelector("[data-live-count]");
        if (live) live.textContent = visibili;
    }
    window.cercaPrestazioneTabella = cercaPrestazioneTabella;
});
</script>';

foreach ($branca as $titoloBranca => $prestazioni) {

    /* –– Normalizzo il titolo per creare ID univoci e sicuri –– */
    $baseId = preg_replace('/[^a-zA-Z0-9]/', '', $titoloBranca); // es: Allergologia → Allergologia
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

    /* ---- Righe singole -------------------------------------------------- */
    $row = 1;
    foreach ($prestazioni as $p) {

        $rowId   = 'row-' . $baseId . '-' . $row++;
        $titolo  = ucfirst(strtolower($p['activityTitle']));
        $linkUrl = 'https://app.tuotempo.com/mop/index.php?dbName=tt_kosgroup_portale'
                 . '&activityid=' . $p['activityid']
                 . '&areaid='     . $ist;

        echo '
                    <tr>
                        <th scope="row" id="'. $rowId .'" class="align-middle" style="width:80%">
                            '. htmlspecialchars($titolo) .'
                        </th>
                        <td class="text-center align-middle" style="width:20%">
                            <a class="btn btn-primary"
                               href="'. $linkUrl .'"
                               target="_blank"
                               rel="noopener"
                               aria-describedby="'. $rowId .'"
                               aria-label="Prenota on-line la prestazione: '. htmlspecialchars($titolo) .'">
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