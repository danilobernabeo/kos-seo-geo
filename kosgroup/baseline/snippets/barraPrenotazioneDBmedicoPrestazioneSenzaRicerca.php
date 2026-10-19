<?php
/**
 * BARRA PRENOTAZIONE DA DATABASE - Flusso flessibile con Reset
 * Snippet MODX 3.1
 * FIX: evita loop quando auto-selezione medico/prestazione unici
 */

$ist = $modx->resource->getTVValue('istanza');
$centroNome = $modx->resource->get('pagetitle') ?: 'Centro KOS';

if (empty($ist)) {
    return '<div class="alert alert-warning">Struttura non configurata.</div>';
}

try {
    $stmtBranche = $modx->prepare("
        SELECT DISTINCT branca, branca_slug 
        FROM kos_branche 
        WHERE areaid = ? 
        ORDER BY branca
    ");
    $stmtBranche->execute([$ist]);
    $brancheDb = $stmtBranche->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($brancheDb)) {
        return '<div class="alert alert-warning">Nessuna prestazione disponibile.</div>';
    }
    
    // Prestazioni raggruppate per typology
    $stmtPrestazioni = $modx->prepare("
        SELECT activity_id, title, typology 
        FROM kos_prestazioni 
        WHERE areaid = ? 
        ORDER BY typology, title
    ");
    $stmtPrestazioni->execute([$ist]);
    $prestazioniDb = $stmtPrestazioni->fetchAll(PDO::FETCH_ASSOC);
    
    // Medici DISTINTI per typology (branca)
    $stmtMedici = $modx->prepare("
        SELECT DISTINCT m.resourceid, m.nome, m.note, p.typology
        FROM kos_medici m
        JOIN kos_medici_prestazioni mp ON m.resourceid = mp.resourceid AND m.areaid = mp.areaid
        JOIN kos_prestazioni p ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
        WHERE m.areaid = ?
        ORDER BY p.typology, m.nome
    ");
    $stmtMedici->execute([$ist]);
    $mediciDb = $stmtMedici->fetchAll(PDO::FETCH_ASSOC);
    
    // Mappatura: medico + branca -> prestazioni che eroga
    $stmtMedPrest = $modx->prepare("
        SELECT mp.resourceid, mp.activity_id, p.typology
        FROM kos_medici_prestazioni mp
        JOIN kos_prestazioni p ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
        WHERE mp.areaid = ?
    ");
    $stmtMedPrest->execute([$ist]);
    $mappingDb = $stmtMedPrest->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizza dati per JavaScript
    $branche = [];
    
    // Aggiungi prestazioni
    foreach ($prestazioniDb as $p) {
        if (!isset($branche[$p['typology']])) {
            $branche[$p['typology']] = ['prestazioni' => [], 'medici' => []];
        }
        $branche[$p['typology']]['prestazioni'][] = [
            'id' => $p['activity_id'],
            'title' => $p['title']
        ];
    }
    
    // Aggiungi medici (deduplica per evitare ripetizioni)
    $mediciAggiunti = [];
    foreach ($mediciDb as $m) {
        $key = $m['typology'] . '|' . $m['resourceid'];
        if (!isset($mediciAggiunti[$key])) {
            if (!isset($branche[$m['typology']])) {
                $branche[$m['typology']] = ['prestazioni' => [], 'medici' => []];
            }
            $branche[$m['typology']]['medici'][] = [
                'id' => $m['resourceid'],
                'nome' => $m['nome'],
                'note' => $m['note'] ?? ''
            ];
            $mediciAggiunti[$key] = true;
        }
    }
    
    // Mappature per filtri incrociati
    $medicoToPrestazioni = [];
    $prestazioneTOMedici = [];
    
    foreach ($mappingDb as $mp) {
        $keyMed = $mp['typology'] . '|' . $mp['resourceid'];
        $keyPrest = $mp['typology'] . '|' . $mp['activity_id'];
        
        if (!isset($medicoToPrestazioni[$keyMed])) {
            $medicoToPrestazioni[$keyMed] = [];
        }
        $medicoToPrestazioni[$keyMed][] = $mp['activity_id'];
        
        if (!isset($prestazioneTOMedici[$keyPrest])) {
            $prestazioneTOMedici[$keyPrest] = [];
        }
        $prestazioneTOMedici[$keyPrest][] = $mp['resourceid'];
    }
    
    $dataJson = json_encode([
        'branche' => $branche,
        'medico_to_prestazioni' => $medicoToPrestazioni,
        'prestazione_to_medici' => $prestazioneTOMedici
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    $dataBase64 = base64_encode($dataJson);
    
} catch (PDOException $e) {
    return '<div class="alert alert-danger">Errore database: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

ob_start();
?>

<div id="prenota-online" class="kos-booking-bar kos-booking-bar--dark p-1 mt-4" 
     data-centro-areaid="<?= htmlspecialchars($ist, ENT_QUOTES) ?>"
     data-centro-nome="<?= htmlspecialchars($centroNome, ENT_QUOTES) ?>">
    <div class="container p-2">
        <h2 class="h4">Prenota online</h2>
        <p><strong>Importante</strong>. Le prenotazioni devono essere effettuate <strong>a nome della persona che eseguirà l'esame</strong>, pertanto puoi prenotare anche per un familiare. Se hai una convenzione o un'assicurazione, ti invitiamo a contattare direttamente la struttura. Ricorda di portare con te le richieste di esame (ricette, prescrizioni) ed eventuali referti di visite ed esami eseguiti in precedenza.</p>
        
        <script>
        window.KOS_DATA_DB_B64 = '<?= $dataBase64 ?>';
        </script>
        
        <form id="bookingForm" class="row g-3">
            <div class="col-12">
                <label for="selectBranca" class="form-label">
                    <span class="step-number">1</span> Seleziona la Branca Medica *
                </label>
                <select id="selectBranca" name="branca" class="form-select form-select-lg" required>
                    <?php 
                    $numBranche = count($brancheDb);
                    $brancheText = $numBranche === 1 ? 'Branca disponibile' : 'Branche disponibili';
                    ?>
                    <option value="">-- Seleziona una delle <?= $numBranche ?> <?= $brancheText ?> --</option>
                    <?php foreach ($brancheDb as $b): 
                        $numPrestazioni = isset($branche[$b['branca']]['prestazioni']) ? count($branche[$b['branca']]['prestazioni']) : 0;
                        $prestazioniText = $numPrestazioni === 1 ? 'prestazione' : 'prestazioni';
                    ?>
                        <option value="<?= htmlspecialchars($b['branca']) ?>">
                            <?= ucwords(strtolower(htmlspecialchars($b['branca']))) ?> (<?= $numPrestazioni ?> <?= $prestazioniText ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-xl-6">
                <label for="selectPrestazione" class="form-label">
                    <span class="step-number">2</span> Seleziona Prestazione
                </label>
                <select id="selectPrestazione" name="prestazione" class="form-select form-select-lg" required disabled>
                    <option value="">-- Prima seleziona la Branca --</option>
                </select>
                <small class="hint-text">Oppure scegli prima il medico →</small>
                <small id="infoPrestazioneAuto" class="auto-select-info fw-bold d-block" style="display:none;"></small>
            </div>

            <div class="col-12 col-xl-6">
                <label for="selectMedico" class="form-label">
                    <span class="step-number">3</span> Seleziona Medico
                </label>
                <select id="selectMedico" name="medico" class="form-select form-select-lg" required disabled>
                    <option value="">-- Prima seleziona la Branca --</option>
                </select>
                <small class="hint-text">← Oppure scegli prima la prestazione</small>
                <small id="infoMedicoAuto" class="auto-select-info fw-bold d-block" style="display:none;"></small>
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-outline-light btn-lg w-100 mb-2" id="btnReset" style="display: none;">
                    <i class="bi bi-arrow-counterclockwise"></i> Cambia scelta prestazione o medico
                </button>
                <!-- Link <a> per permettere a GTM Conversion Linker di aggiungere _gl -->
                <a href="#" 
                   class="btn btn-warning btn-lg w-100 btn-prenota-kos disabled" 
                   id="btnCerca" 
                   role="button"
                   aria-disabled="true">
                    <i class="bi bi-search"></i> Cerca
                </a>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        const bookingBar = document.querySelector('.kos-booking-bar');
        if (!bookingBar) return;
        
        const CONFIG = {
            PORTAL_URL: 'https://app.tuotempo.com/mop/index.php',
            DB_NAME: 'tt_kosgroup_portale',
            CENTRO_AREAID: bookingBar.dataset.centroAreaid,
            CENTRO_NOME: bookingBar.dataset.centroNome
        };
        
        let DATA;
        try {
            const jsonString = atob(window.KOS_DATA_DB_B64);
            DATA = JSON.parse(jsonString);
        } catch(e) {
            console.error('Errore parsing dati:', e);
            return;
        }
        
        const selectBranca = document.getElementById('selectBranca');
        const selectPrestazione = document.getElementById('selectPrestazione');
        const selectMedico = document.getElementById('selectMedico');
        const btnCerca = document.getElementById('btnCerca');
        const btnReset = document.getElementById('btnReset');
        const bookingForm = document.getElementById('bookingForm');
        const infoPrestazioneAuto = document.getElementById('infoPrestazioneAuto');
        const infoMedicoAuto = document.getElementById('infoMedicoAuto');
        
        if (!selectBranca || !selectPrestazione || !selectMedico || !btnCerca || !btnReset || !bookingForm) return;
        
        let currentBranca = '';
        let isUpdating = false;
        let isAutoSelection = false; // Flag per evitare loop durante auto-selezione
        
        // POLLING FALLBACK - variabili accessibili
        let lastBranca = '', lastPrestazione = '', lastMedico = '';
        
        setInterval(function() {
            if (selectBranca.value !== lastBranca) {
                lastBranca = selectBranca.value;
                currentBranca = selectBranca.value;
                resetSelections();
                if (currentBranca) {
                    populateBothSelects();
                }
            }
            if (selectPrestazione.value !== lastPrestazione && !isUpdating) {
                lastPrestazione = selectPrestazione.value;
                if (selectPrestazione.value && !isAutoSelection) {
                    filterMediciByPrestazione(selectPrestazione.value);
                }
                isAutoSelection = false;
                updateBookingLink();
            }
            if (selectMedico.value !== lastMedico && !isUpdating) {
                lastMedico = selectMedico.value;
                if (selectMedico.value && !isAutoSelection) {
                    filterPrestazioniByMedico(selectMedico.value);
                }
                isAutoSelection = false;
                updateBookingLink();
            }
        }, 500);
        
        bookingForm.addEventListener('submit', function(e) { e.preventDefault(); });
        btnReset.addEventListener('click', resetSearch);
        btnCerca.addEventListener('click', handleBookingClick);
        
        function resetSelections() {
            isUpdating = true;
            
            selectPrestazione.innerHTML = '<option value="">-- Prima seleziona la Branca --</option>';
            selectPrestazione.disabled = true;
            selectMedico.innerHTML = '<option value="">-- Prima seleziona la Branca --</option>';
            selectMedico.disabled = true;
            disableButton();
            btnReset.style.display = 'none';
            hideAutoSelectionInfo();
            
            updateNiceSelect(selectPrestazione);
            updateNiceSelect(selectMedico);
            
            isUpdating = false;
        }
        
        function hideAutoSelectionInfo() {
            if (infoPrestazioneAuto) {
                infoPrestazioneAuto.style.display = 'none';
                infoPrestazioneAuto.innerHTML = '';
            }
            if (infoMedicoAuto) {
                infoMedicoAuto.style.display = 'none';
                infoMedicoAuto.innerHTML = '';
            }
        }
        
        function disableButton() {
            btnCerca.href = '#';
            btnCerca.classList.add('disabled');
            btnCerca.setAttribute('aria-disabled', 'true');
        }
        
        function populateBothSelects() {
            if (!currentBranca) return;
            
            if (!DATA.branche[currentBranca]) {
                console.error('Branca non trovata nei dati!');
                return;
            }
            
            isUpdating = true;
            
            const brancaData = DATA.branche[currentBranca];
            
            // Popola Prestazioni
            const prestazioni = brancaData.prestazioni || [];
            selectPrestazione.innerHTML = '<option value="">-- Seleziona prestazione --</option>';
            prestazioni.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.title.charAt(0).toUpperCase() + p.title.slice(1).toLowerCase();
                selectPrestazione.appendChild(option);
            });
            selectPrestazione.disabled = false;
            
            // Popola Medici
            const medici = brancaData.medici || [];
            selectMedico.innerHTML = '<option value="">-- Seleziona medico --</option>';
            medici.forEach(m => {
                const option = document.createElement('option');
                option.value = m.id;
                option.textContent = m.nome;
                if (m.note) {
                    option.textContent += ' - ' + m.note.substring(0, 50);
                }
                selectMedico.appendChild(option);
            });
            selectMedico.disabled = false;
            
            updateNiceSelect(selectPrestazione);
            updateNiceSelect(selectMedico);
            
            isUpdating = false;
        }
        
        function filterMediciByPrestazione(prestazioneId) {
            if (!currentBranca) return;
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
            const key = currentBranca + '|' + prestazioneId;
            const mediciIds = DATA.prestazione_to_medici[key] || [];
            const brancaData = DATA.branche[currentBranca];
            const tuttiMedici = brancaData.medici || [];
            
            const mediciFiltrati = tuttiMedici.filter(m => mediciIds.includes(m.id));
            
            // Salva valore precedente prima di ricostruire la lista
            const previousValue = selectMedico.value;
            
            selectMedico.innerHTML = '<option value="">-- Seleziona medico --</option>';
            mediciFiltrati.forEach(m => {
                const option = document.createElement('option');
                option.value = m.id;
                option.textContent = m.nome;
                if (m.note) {
                    option.textContent += ' - ' + m.note.substring(0, 50);
                }
                selectMedico.appendChild(option);
            });
            
            // Ripristina valore precedente se ancora valido
            if (previousValue && mediciIds.includes(previousValue)) {
                selectMedico.value = previousValue;
                lastMedico = previousValue;
            }
            // Auto-selezione solo se uno solo E non c'era già un valore valido
            else if (mediciFiltrati.length === 1) {
                isAutoSelection = true; // Evita loop nel polling
                selectMedico.value = mediciFiltrati[0].id;
                lastMedico = mediciFiltrati[0].id; // Aggiorna tracking polling
                // Mostra messaggio solo se il valore è cambiato (non era già selezionato)
                if (previousValue !== mediciFiltrati[0].id && infoMedicoAuto) {
                    infoMedicoAuto.innerHTML = '✓ Unica combinazione disponibile: puoi avviare la ricerca';
                    infoMedicoAuto.style.display = 'block';
                }
            }
            
            selectMedico.disabled = mediciFiltrati.length === 0;
            updateNiceSelect(selectMedico);
            
            btnReset.style.display = 'block';
            
            isUpdating = false;
            updateBookingLink();
        }
        
        function filterPrestazioniByMedico(medicoId) {
            if (!currentBranca) return;
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
            const key = currentBranca + '|' + medicoId;
            const prestazioniIds = DATA.medico_to_prestazioni[key] || [];
            const brancaData = DATA.branche[currentBranca];
            const tuttePrestazioni = brancaData.prestazioni || [];
            
            const prestazioniFiltrate = tuttePrestazioni.filter(p => prestazioniIds.includes(p.id));
            
            // Salva valore precedente prima di ricostruire la lista
            const previousValue = selectPrestazione.value;
            
            selectPrestazione.innerHTML = '<option value="">-- Seleziona prestazione --</option>';
            prestazioniFiltrate.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.title.charAt(0).toUpperCase() + p.title.slice(1).toLowerCase();
                selectPrestazione.appendChild(option);
            });
            
            // Ripristina valore precedente se ancora valido
            if (previousValue && prestazioniIds.includes(previousValue)) {
                selectPrestazione.value = previousValue;
                lastPrestazione = previousValue;
            }
            // Auto-selezione solo se una sola E non c'era già un valore valido
            else if (prestazioniFiltrate.length === 1) {
                isAutoSelection = true; // Evita loop nel polling
                selectPrestazione.value = prestazioniFiltrate[0].id;
                lastPrestazione = prestazioniFiltrate[0].id; // Aggiorna tracking polling
                // Mostra messaggio solo se il valore è cambiato (non era già selezionato)
                if (previousValue !== prestazioniFiltrate[0].id && infoPrestazioneAuto) {
                    infoPrestazioneAuto.innerHTML = '✓ Unica combinazione disponibile: puoi avviare la ricerca';
                    infoPrestazioneAuto.style.display = 'block';
                }
            }
            
            selectPrestazione.disabled = prestazioniFiltrate.length === 0;
            updateNiceSelect(selectPrestazione);
            
            btnReset.style.display = 'block';
            
            isUpdating = false;
            updateBookingLink();
        }
        
        function resetSearch() {
            if (!currentBranca) return;
            
            isUpdating = true;
            
            // Reset valori polling
            lastPrestazione = '';
            lastMedico = '';
            
            selectPrestazione.value = '';
            selectMedico.value = '';
            
            populateBothSelects();
            
            btnReset.style.display = 'none';
            disableButton();
            hideAutoSelectionInfo();
            
            isUpdating = false;
        }
        
        function updateBookingLink() {
            const branca = selectBranca.value;
            const prestazioneId = selectPrestazione.value;
            const medicoId = selectMedico.value;
            
            if (branca && prestazioneId && medicoId) {
                const url = new URL(CONFIG.PORTAL_URL);
                url.searchParams.set('dbName', CONFIG.DB_NAME);
                url.searchParams.set('activityid', prestazioneId);
                url.searchParams.set('areaid', CONFIG.CENTRO_AREAID);
                url.searchParams.set('resourceid', medicoId);
                url.searchParams.set('kos_referrer', window.location.pathname);
                
                btnCerca.href = url.toString();
                btnCerca.classList.remove('disabled');
                btnCerca.setAttribute('aria-disabled', 'false');
                
                console.log('[KOS] Link aggiornato:', url.toString());
            } else {
                disableButton();
            }
        }
        
        function handleBookingClick(e) {
            const branca = selectBranca.value;
            const prestazioneId = selectPrestazione.value;
            const medicoId = selectMedico.value;
            
            if (!branca || !prestazioneId || !medicoId) {
                e.preventDefault();
                alert('Seleziona branca, prestazione e medico');
                return false;
            }
            
            if (btnCerca.href === '#' || btnCerca.classList.contains('disabled')) {
                e.preventDefault();
                return false;
            }
            
            // Push to dataLayer
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'booking_start',
                source_type: 'widget_struttura',
                referrer_page: window.location.pathname,
                centro_areaid: CONFIG.CENTRO_AREAID,
                prestazione_id: prestazioneId,
                medico_id: medicoId
            });
            
            console.log('[KOS] Redirect a TuoTempo', {
                href: btnCerca.href,
                areaid: CONFIG.CENTRO_AREAID,
                activityid: prestazioneId,
                resourceid: medicoId
            });
            
            // Lascia proseguire il click - Conversion Linker aggiungerà _gl
            return true;
        }
        
        function updateNiceSelect(el) {
            if (window.jQuery && typeof jQuery(el).niceSelect === 'function') {
                try {
                    jQuery(el).niceSelect('update');
                    
                    if (el.disabled) {
                        jQuery(el).next('.nice-select').css('cursor', 'not-allowed');
                    } else {
                        jQuery(el).next('.nice-select').css('cursor', 'pointer');
                    }
                } catch(e) {
                    jQuery(el).niceSelect('destroy');
                    jQuery(el).niceSelect();
                }
            }
        }
        
        function capitalizeFirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
        }
    }
})();
</script>

<style>
/* ====== VERSIONE ALTO CONTRASTO ====== */
.kos-booking-bar--dark {
    background-color: #1A2031;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    border-radius: 8px;
}

.kos-booking-bar--dark h2 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 1rem;
}

.kos-booking-bar--dark p {
    color: rgba(255,255,255,0.9);
}

.kos-booking-bar--dark p strong {
    color: #ffc107;
}

.kos-booking-bar--dark .form-label {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.95rem;
    color: #ffffff;
}

.kos-booking-bar--dark .form-label small {
    display: block;
    font-weight: 400;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.7);
    margin-top: 0.25rem;
}

/* Hint text sotto le select */
.kos-booking-bar--dark .hint-text {
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
    display: block;
    margin-top: 0.25rem;
}

/* Messaggio auto-selezione */
.kos-booking-bar--dark .auto-select-info {
    color: #ffc107;
    font-size: 0.8rem;
    margin-top: 4px;
}

/* Step numbers - sfondo bianco, numero blu */
.kos-booking-bar--dark .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    background-color: #ffffff;
    color: #1A2031;
    border-radius: 50%;
    font-size: 0.85rem;
    font-weight: 700;
    margin-right: 8px;
    flex-shrink: 0;
}

/* Select - mantieni sfondo bianco per leggibilità */
.kos-booking-bar--dark .form-select {
    display: block;
    width: 100%;
    margin-top: 0;
    background-color: #ffffff;
    border: 2px solid rgba(255,255,255,0.3);
}

.kos-booking-bar--dark .form-select:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
}

/* NiceSelect styling */
.kos-booking-bar--dark .nice-select {
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    min-height: 48px;
    padding: 0.5rem 1rem;
    margin-top: 0;
    background-color: #ffffff;
    border: 2px solid rgba(255,255,255,0.3);
}

.kos-booking-bar--dark .nice-select:focus,
.kos-booking-bar--dark .nice-select.open {
    border-color: #ffc107;
}

.kos-booking-bar--dark .nice-select .current {
    line-height: 1.5;
    vertical-align: middle;
    margin: 0;
    padding: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
    display: block;
    color: #212529;
}

.kos-booking-bar--dark .nice-select .list {
    width: 100% !important;
    max-height: 300px;
    overflow-y: auto;
    overflow-x: hidden;
    background-color: #ffffff;
}

.kos-booking-bar--dark .nice-select .list .option {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
    display: block;
    color: #212529;
}

.kos-booking-bar--dark .nice-select .list .option:hover,
.kos-booking-bar--dark .nice-select .list .option.focus {
    background-color: #ffc107;
    color: #1A2031;
}

.kos-booking-bar--dark .nice-select .list .option.selected {
    background-color: #1A2031;
    color: #ffffff;
}

.kos-booking-bar--dark .nice-select .list::-webkit-scrollbar {
    width: 8px;
}

.kos-booking-bar--dark .nice-select .list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.kos-booking-bar--dark .nice-select .list::-webkit-scrollbar-thumb {
    background: #1A2031;
    border-radius: 4px;
}

.kos-booking-bar--dark .nice-select .list::-webkit-scrollbar-thumb:hover {
    background: #0d1018;
}

/* Bottoni */
.kos-booking-bar--dark .btn {
    transform: none !important;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease !important;
}

.kos-booking-bar--dark .btn:hover,
.kos-booking-bar--dark .btn:active,
.kos-booking-bar--dark .btn:focus {
    transform: none !important;
}

/* Bottone Cerca (warning) */
.kos-booking-bar--dark .btn-warning {
    background-color: #ffc107;
    border-color: #ffc107 !important;
    color: #1A2031;
    font-weight: 600;
}

.kos-booking-bar--dark .btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00 !important;
    color: #1A2031;
}

.kos-booking-bar--dark .btn-warning.disabled {
    background-color: rgba(255,193,7,0.5);
    border-color: rgba(255,193,7,0.5) !important;
    color: rgba(26,32,49,0.7);
}

/* Bottone Reset (outline-light) */
.kos-booking-bar--dark .btn-outline-light {
    border: 2px solid rgba(255,255,255,0.7) !important;
    color: #ffffff;
    background-color: transparent;
}

.kos-booking-bar--dark .btn-outline-light:hover {
    background-color: rgba(255,255,255,0.1);
    border-color: #ffffff !important;
    color: #ffffff;
}

/* Stati disabled */
.kos-booking-bar--dark .form-select:disabled,
.kos-booking-bar--dark select:disabled {
    cursor: not-allowed !important;
    opacity: 0.6;
}

.kos-booking-bar--dark .nice-select.disabled,
.kos-booking-bar--dark .nice-select.disabled .current,
.kos-booking-bar--dark .nice-select[aria-disabled="true"],
.kos-booking-bar--dark .nice-select:has(+ select:disabled) {
    cursor: not-allowed !important;
    pointer-events: auto !important;
    opacity: 0.6;
}

.kos-booking-bar--dark .btn.disabled,
.kos-booking-bar--dark a.disabled {
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

.kos-booking-bar--dark .btn:disabled,
.kos-booking-bar--dark button:disabled {
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

@media (max-width: 768px) {
    .kos-booking-bar--dark .row > div {
        margin-bottom: 1rem;
    }
}
</style>

<?php
return ob_get_clean();