<?php
// Query per contare tutte le posizioni aperte con title e custom_sector non nulli
$totalPositionsStmt = $modx->prepare("SELECT COUNT(*) FROM kos_jobs WHERE title IS NOT NULL AND custom_sector IS NOT NULL");
$totalPositionsStmt->execute();
$totalPositions = $totalPositionsStmt->fetchColumn();

// Query per estrarre `custom_sector` unici e non nulli
$stmt = $modx->prepare("SELECT DISTINCT custom_sector FROM kos_jobs WHERE custom_sector IS NOT NULL ORDER BY custom_sector ASC");
$stmt->execute();
$customSectors = $stmt->fetchAll(PDO::FETCH_COLUMN);

$tabButtonsHtml = '';
$tabContentHtml = '';
$index = 1;

// Parametri lingua da aggiungere agli URL
$langParams = 'lan=en&language=en';

foreach ($customSectors as $sector) {
    $sectorName = htmlspecialchars($sector);

    // Query per contare le posizioni aperte in ciascun settore con title non nullo
    $sectorCountStmt = $modx->prepare("SELECT COUNT(*) FROM kos_jobs WHERE custom_sector = :sector AND title IS NOT NULL");
    $sectorCountStmt->bindParam(':sector', $sector, PDO::PARAM_STR);
    $sectorCountStmt->execute();
    $sectorCount = $sectorCountStmt->fetchColumn();

    // Aggiunge ogni custom_sector come pulsante con il conteggio
    $tabButtonsHtml .= '
    <li class="nav-item" role="presentation">
        <button class="nav-link ' . ($index === 1 ? 'active' : '') . '" id="pos-lav-cat-' . $index . '-tab" data-bs-toggle="pill" data-bs-target="#pos-lav-cat-tab' . $index . '" type="button" role="tab" aria-controls="pos-lav-cat-tab' . $index . '" aria-selected="' . ($index === 1 ? 'true' : 'false') . '">' . $sectorName . ' (' . $sectorCount . ')</button>
    </li>';

    // Query per ottenere i dettagli delle posizioni per ogni settore
    $positionStmt = $modx->prepare("SELECT title, city, region, emp_type, url, url_apply, date FROM kos_jobs WHERE custom_sector = :sector AND title IS NOT NULL");
    $positionStmt->bindParam(':sector', $sector, PDO::PARAM_STR);
    $positionStmt->execute();
    $positions = $positionStmt->fetchAll(PDO::FETCH_ASSOC);

    // Costruisce il contenuto del tab pane per ogni settore
    $tabContentHtml .= '
    <div class="tab-pane ' . ($index === 1 ? 'active' : '') . '" id="pos-lav-cat-tab' . $index . '" role="tabpanel" aria-labelledby="pos-lav-cat-' . $index . '-tab" tabindex="0">
        <div class="elenco-posizioni scrollable-list">';
    
    if (empty($positions)) {
        $tabContentHtml .= '<p>Nessuna posizione disponibile per questo settore.</p>';
    } else {
        foreach ($positions as $position) {
            $date = DateTime::createFromFormat('d/m/Y H:i:s', $position['date']);
            $isNew = $date && (new DateTime())->diff($date)->days <= 7;

            // Costruisce gli URL con i parametri lingua
            $urlRead = $position['url'] . (strpos($position['url'], '?') !== false ? '&' : '?') . $langParams;
            $urlApply = $position['url_apply'] . (strpos($position['url_apply'], '?') !== false ? '&' : '?') . $langParams;

            $tabContentHtml .= '
                <div class="col single-posizione search-item">
                    <div class="titolo-badge" style="margin-bottom:0">
                        <span>' . strtoupper(htmlspecialchars($position['title'])) . '</span>
                        <div class="badge-info">';

            if ($isNew) {
                $tabContentHtml .= '<span class="badge rounded-pill text-bg-info">NUOVO</span>';
            }
            
            $tabContentHtml .= '
                            <span class="badge rounded-pill text-bg-secondary">
                                <img src="/assets/img/map-point.svg" alt=""> ' . htmlspecialchars($position['city']) . ' - ' . htmlspecialchars($position['region']) . '
                            </span>
                        </div>
                    </div>

                    <div class="altre-info" style="margin-top:0">
                        <div class="info-lavoro">
                            <span><img src="/assets/img/timer.svg" alt="">' . htmlspecialchars($position['emp_type']) . '</span>
                        </div>
                        <div class="cta-candidatura">
                            <a class="btn btn-secondary" href="' . htmlspecialchars($urlRead) . '" target="_blank">READ JOB AD</a>
                            <a class="btn btn-primary" href="' . htmlspecialchars($urlApply) . '" target="_blank">APPLY NOW</a>
                        </div>
                    </div>
                </div>';
        }
    }

    $tabContentHtml .= '
        </div>
    </div>';

    $index++;
}

// Output finale con il conteggio totale e i due blocchi di HTML
echo "<small>CURRENT OPENINGS ($totalPositions)</small></p>";
echo '
<ul class="nav nav-pills nav-rettangolare bk-grigio" id="filtri-pos-lav" role="tablist">' .
    $tabButtonsHtml . '
</ul>

<!-- Aggiunta barra di ricerca -->
<div class="row mb-4">
    <div class="col-12 d-flex align-items-center">
    	<label for="posizioniInput" class="me-2 mb-0">Search:</label>
    	<input class="form-control" 
		type="text" 
		id="posizioniInput" 
		name="posizioniInput" 
		placeholder="Search for a position by role, city, region or contract type" 
		title="Search for a position">
    </div>
</div>

<!-- Aggiunto div per nessun risultato -->
<div id="noResults" class="col-12 text-center" style="display: none;">
    <p>Nessuna corrispondenza trovata</p>
</div>

<div class="tab-content container">' .
    $tabContentHtml . '
</div>

<!-- Script di ricerca migliorato con controlli di sicurezza -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var searchInput = document.getElementById("posizioniInput");
    if (searchInput) {
        searchInput.addEventListener("keyup", cercaPosizioni);
    }
    
    function cercaPosizioni() {
        var input = document.getElementById("posizioniInput");
        var noResults = document.getElementById("noResults");
        
        if (!input || !noResults) {
            console.log("Elementi necessari non ancora caricati");
            return;
        }
        
        var filter = input.value.toUpperCase();
        var items = document.getElementsByClassName("search-item");
        var visibleItems = 0;
        
        if (items && items.length > 0) {
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (item) {
                    var txtValue = item.textContent || item.innerText;
                    
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        item.style.display = "";
                        visibleItems++;
                    } else {
                        item.style.display = "none";
                    }
                }
            }
        }
        
        if (noResults) {
            noResults.style.display = visibleItems === 0 ? "block" : "none";
        }
    }
    
    if (searchInput && searchInput.value.trim() !== "") {
        cercaPosizioni();
    }
});
</script>';