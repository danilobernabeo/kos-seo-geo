<?php
/**
 * BARRA PRENOTAZIONE DA DATABASE - VERSIONE GA4 NATIVO
 * GA4 cross-domain nativo (parametro _gl) + tracking pagina referrer
 * Zero session ID custom, zero source_type custom
 * Snippet MODX 3.1
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

<div id="prenota-online" class="kos-booking-bar p-1 mt-4 bg-grigio" 
     data-centro-areaid="<?= htmlspecialchars($ist, ENT_QUOTES) ?>"
     data-centro-nome="<?= htmlspecialchars($centroNome, ENT_QUOTES) ?>">
    <div class="container p-2">
        <h2 class="h4 text-primary">Prenota online</h2>
        <p><strong>Importante</strong>. Le prenotazioni devono essere effettuate a nome della persona che eseguirà l'esame, pertanto puoi prenotare anche per un familiare. Se hai una convenzione o un'assicurazione, ti invitiamo a contattare direttamente la struttura. Ricorda di portare con te le richieste di esame (ricette, prescrizioni) ed eventuali referti di visite ed esami eseguiti in precedenza.</p>
        
        <script>
        window.KOS_DATA_DB_B64 = '<?= $dataBase64 ?>';
        </script>
        
        <form id="bookingForm" class="row g-3">
            <div class="col-md-12">
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

            <div class="col-md-6">
                <label for="selectPrestazione" class="form-label">
                    <span class="step-number">2</span> Seleziona Prestazione
                </label>
                <select id="selectPrestazione" name="prestazione" class="form-select form-select-lg" required disabled>
                    <option value="">-- Prima seleziona la Branca --</option>
                </select>
                <small id="infoPrestazioneAuto" class="text-success fw-bold" style="display:none;"></small>
                <small class="text-muted info-default-prest">Oppure scegli prima il medico →</small>
            </div>

            <div class="col-md-6">
                <label for="selectMedico" class="form-label">
                    <span class="step-number">2</span> Seleziona Medico
                </label>
                <select id="selectMedico" name="medico" class="form-select form-select-lg" required disabled>
                    <option value="">-- Prima seleziona la Branca --</option>
                </select>
                <small id="infoMedicoAuto" class="text-success fw-bold" style="display:none;"></small>
                <small class="text-muted info-default-med">← Oppure scegli prima la prestazione</small>
            </div>

            <div class="col-md-12">
                <button type="button" class="btn btn-secondary btn-lg w-100 mb-2" id="btnReset" style="display: none;">
                    <i class="bi bi-arrow-counterclockwise"></i> Cambia scelta prestazione o medico
                </button>
                <button type="submit" class="btn btn-primary btn-lg w-100 btn-prenota-kos" id="btnCerca" disabled>
                    <i class="bi bi-search"></i> Cerca
                </button>
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
        
        // POLLING FALLBACK per NiceSelect
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
                if (selectPrestazione.value) {
                    filterMediciByPrestazione(selectPrestazione.value);
                }
                checkFormComplete();
            }
            if (selectMedico.value !== lastMedico && !isUpdating) {
                lastMedico = selectMedico.value;
                if (selectMedico.value) {
                    filterPrestazioniByMedico(selectMedico.value);
                }
                checkFormComplete();
            }
        }, 500);
        
        bookingForm.addEventListener('submit', handleBookingSubmit);
        btnReset.addEventListener('click', resetSearch);
        
        function resetSelections() {
            isUpdating = true;
            
            selectPrestazione.innerHTML = '<option value="">-- Prima seleziona la Branca --</option>';
            selectPrestazione.disabled = true;
            selectMedico.innerHTML = '<option value="">-- Prima seleziona la Branca --</option>';
            selectMedico.disabled = true;
            btnCerca.disabled = true;
            btnReset.style.display = 'none';
            
            // Reset info auto-selezione
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
            // Mostra i testi di default
            const defaultPrest = document.querySelector('.info-default-prest');
            const defaultMed = document.querySelector('.info-default-med');
            if (defaultPrest) defaultPrest.style.display = '';
            if (defaultMed) defaultMed.style.display = '';
        }
        
        function populateBothSelects() {
            if (!currentBranca) return;
            
            if (!DATA.branche[currentBranca]) {
                console.error('Branca non trovata nei dati!');
                return;
            }
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
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
            
            // AUTO-SELEZIONE: se c'è una sola prestazione
            if (prestazioni.length === 1) {
                selectPrestazione.value = prestazioni[0].id;
                if (infoPrestazioneAuto) {
                    infoPrestazioneAuto.innerHTML = 'Unica prestazione: <strong>' + 
                        (prestazioni[0].title.charAt(0).toUpperCase() + prestazioni[0].title.slice(1).toLowerCase()) + '</strong>';
                    infoPrestazioneAuto.style.display = 'block';
                }
                const defaultPrest = document.querySelector('.info-default-prest');
                if (defaultPrest) defaultPrest.style.display = 'none';
                
                // Filtra i medici per questa prestazione
                filterMediciByPrestazione(prestazioni[0].id);
            }
            
            // AUTO-SELEZIONE: se c'è un solo medico
            if (medici.length === 1) {
                selectMedico.value = medici[0].id;
                if (infoMedicoAuto) {
                    infoMedicoAuto.innerHTML = 'Unico medico: <strong>' + medici[0].nome + '</strong>';
                    infoMedicoAuto.style.display = 'block';
                }
                const defaultMed = document.querySelector('.info-default-med');
                if (defaultMed) defaultMed.style.display = 'none';
                
                // Filtra le prestazioni per questo medico (se non già filtrate)
                if (prestazioni.length !== 1) {
                    filterPrestazioniByMedico(medici[0].id);
                }
            }
            
            updateNiceSelect(selectPrestazione);
            updateNiceSelect(selectMedico);
            
            checkFormComplete();
            
            isUpdating = false;
        }
        
        function filterMediciByPrestazione(prestazioneId) {
            if (!currentBranca) return;
            
            isUpdating = true;
            
            const key = currentBranca + '|' + prestazioneId;
            const mediciIds = DATA.prestazione_to_medici[key] || [];
            const brancaData = DATA.branche[currentBranca];
            const tuttiMedici = brancaData.medici || [];
            
            const mediciFiltrati = tuttiMedici.filter(m => mediciIds.includes(m.id));
            
            const currentValue = selectMedico.value;
            if (currentValue && !mediciIds.includes(currentValue)) {
                selectMedico.value = '';
            }
            
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
            
            if (currentValue && mediciIds.includes(currentValue)) {
                selectMedico.value = currentValue;
            }
            
            // AUTO-SELEZIONE: se c'è un solo medico dopo il filtro
            if (mediciFiltrati.length === 1) {
                selectMedico.value = mediciFiltrati[0].id;
                if (infoMedicoAuto) {
                    infoMedicoAuto.innerHTML = 'Unico medico: <strong>' + mediciFiltrati[0].nome + '</strong>';
                    infoMedicoAuto.style.display = 'block';
                }
                const defaultMed = document.querySelector('.info-default-med');
                if (defaultMed) defaultMed.style.display = 'none';
            }
            
            selectMedico.disabled = mediciFiltrati.length === 0;
            updateNiceSelect(selectMedico);
            
            btnReset.style.display = 'block';
            checkFormComplete();
            
            isUpdating = false;
        }
        
        function filterPrestazioniByMedico(medicoId) {
            if (!currentBranca) return;
            
            isUpdating = true;
            
            const key = currentBranca + '|' + medicoId;
            const prestazioniIds = DATA.medico_to_prestazioni[key] || [];
            const brancaData = DATA.branche[currentBranca];
            const tuttePrestazioni = brancaData.prestazioni || [];
            
            const prestazioniFiltrate = tuttePrestazioni.filter(p => prestazioniIds.includes(p.id));
            
            const currentValue = selectPrestazione.value;
            if (currentValue && !prestazioniIds.includes(currentValue)) {
                selectPrestazione.value = '';
            }
            
            selectPrestazione.innerHTML = '<option value="">-- Seleziona prestazione --</option>';
            prestazioniFiltrate.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.title.charAt(0).toUpperCase() + p.title.slice(1).toLowerCase();
                selectPrestazione.appendChild(option);
            });
            
            if (currentValue && prestazioniIds.includes(currentValue)) {
                selectPrestazione.value = currentValue;
            }
            
            // AUTO-SELEZIONE: se c'è una sola prestazione dopo il filtro
            if (prestazioniFiltrate.length === 1) {
                selectPrestazione.value = prestazioniFiltrate[0].id;
                if (infoPrestazioneAuto) {
                    infoPrestazioneAuto.innerHTML = 'Unica prestazione: <strong>' + 
                        (prestazioniFiltrate[0].title.charAt(0).toUpperCase() + prestazioniFiltrate[0].title.slice(1).toLowerCase()) + '</strong>';
                    infoPrestazioneAuto.style.display = 'block';
                }
                const defaultPrest = document.querySelector('.info-default-prest');
                if (defaultPrest) defaultPrest.style.display = 'none';
            }
            
            selectPrestazione.disabled = prestazioniFiltrate.length === 0;
            updateNiceSelect(selectPrestazione);
            
            btnReset.style.display = 'block';
            checkFormComplete();
            
            isUpdating = false;
        }
        
        function resetSearch() {
            if (!currentBranca) return;
            
            isUpdating = true;
            
            selectPrestazione.value = '';
            selectMedico.value = '';
            
            populateBothSelects();
            
            btnReset.style.display = 'none';
            btnCerca.disabled = true;
            
            isUpdating = false;
        }
        
        function checkFormComplete() {
            const hasBranca = selectBranca.value !== '';
            const hasPrestazione = selectPrestazione.value !== '';
            const hasMedico = selectMedico.value !== '';
            
            btnCerca.disabled = !(hasBranca && hasPrestazione && hasMedico);
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
        
        function handleBookingSubmit(e) {
            e.preventDefault();
            
            const branca = selectBranca.value;
            const prestazioneId = selectPrestazione.value;
            const medicoId = selectMedico.value;
            
            if (!branca || !prestazioneId || !medicoId) {
                alert('Seleziona branca, prestazione e medico');
                return false;
            }
            
            // Costruisce URL per TuoTempo
            // GA4 cross-domain aggiunge automaticamente il parametro _gl
            const url = new URL(CONFIG.PORTAL_URL);
            url.searchParams.set('dbName', CONFIG.DB_NAME);
            url.searchParams.set('activityid', prestazioneId);
            url.searchParams.set('areaid', CONFIG.CENTRO_AREAID);
            url.searchParams.set('resourceid', medicoId);
            url.searchParams.set('kos_referrer', window.location.pathname);
            
            // Log per debug
            console.log('✅ KOS Widget: Redirect a TuoTempo', {
                activityid: prestazioneId,
                areaid: CONFIG.CENTRO_AREAID,
                resourceid: medicoId,
                kos_referrer: window.location.pathname
            });
            
            window.location.href = url.toString();
            return false;
        }
    }
})();
</script>

<style>
.kos-booking-bar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.kos-booking-bar h2 {
    color: #0066cc;
    font-weight: 600;
    margin-bottom: 1rem;
}

.kos-booking-bar .form-label {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.95rem;
    color: #212529;
}

.kos-booking-bar .form-label small {
    display: block;
    font-weight: 400;
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.kos-booking-bar .form-select {
    display: block;
    width: 100%;
    margin-top: 0;
}

.kos-booking-bar .nice-select {
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    min-height: 48px;
    padding: 0.5rem 1rem;
    margin-top: 0;
}

.kos-booking-bar .nice-select .current {
    line-height: 1.5;
    vertical-align: middle;
    margin: 0;
    padding: 0;
}

.kos-booking-bar .nice-select .list {
    width: 100% !important;
    max-height: 300px;
    overflow-y: auto;
    overflow-x: hidden;
}

.kos-booking-bar .nice-select .list::-webkit-scrollbar {
    width: 8px;
}

.kos-booking-bar .nice-select .list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.kos-booking-bar .nice-select .list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.kos-booking-bar .nice-select .list::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.kos-booking-bar .btn {
    transform: none !important;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease !important;
    border: 1px solid !important;
}

.kos-booking-bar .btn:hover,
.kos-booking-bar .btn:active,
.kos-booking-bar .btn:focus {
    transform: none !important;
    border: 1px solid !important;
}

.kos-booking-bar .form-select:disabled,
.kos-booking-bar select:disabled {
    cursor: not-allowed !important;
}

.kos-booking-bar .nice-select.disabled,
.kos-booking-bar .nice-select.disabled .current,
.kos-booking-bar .nice-select[aria-disabled="true"],
.kos-booking-bar .nice-select:has(+ select:disabled) {
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

.kos-booking-bar .btn:disabled,
.kos-booking-bar button:disabled {
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

.kos-booking-bar .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background-color: var(--azzurro);
    color: white;
    border-radius: 50%;
    font-size: 0.85rem;
    font-weight: 600;
    margin-right: 8px;
    flex-shrink: 0;
}

/* Info auto-selezione */
#infoPrestazioneAuto,
#infoMedicoAuto {
    margin-top: 0.5rem;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .kos-booking-bar .row > div {
        margin-bottom: 1rem;
    }
}
</style>

<?php
return ob_get_clean();