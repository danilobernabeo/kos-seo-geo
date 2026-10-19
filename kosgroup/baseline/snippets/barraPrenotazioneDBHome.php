<?php
/**
 * BARRA PRENOTAZIONE HOME - CON SELEZIONE MEDICO
 * Snippet MODX 3.x
 * 
 * Flusso: Struttura → Branca → Prestazione/Medico (filtri incrociati)
 * Tracking: GA4 cross-domain con _gl automatico (link <a>)
 *           + push dataLayer 'kos_widget_cerca' al clic su Cerca
 * 
 * FILTRI VISIBILITÀ (matrice):
 *  - Prestazione visibile SE: is_hidden = 0 OPPURE mop_bookability = 2 (differita)
 *  - Prestazione deve avere almeno 1 medico in kos_medici_prestazioni
 *  - Branca visibile solo se ha almeno 1 prestazione valida con medico
 *  - Struttura visibile SE: kos_strutture.is_hidden = 0
 */

try {
    // Strutture disponibili (solo non nascoste)
    $stmtStrutture = $modx->prepare("
        SELECT DISTINCT s.areaid, s.nome as centro_nome 
        FROM kos_branche b
        JOIN kos_strutture s ON b.areaid = s.areaid
        WHERE s.is_hidden = 0
        ORDER BY s.nome
    ");
    $stmtStrutture->execute();
    $strutture = $stmtStrutture->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($strutture)) {
        return '<div class="alert alert-warning">Nessuna struttura disponibile.</div>';
    }
    
    // Estrai gli areaid delle strutture pubblicate per filtrare le altre query
    $areaidPubblicati = array_column($strutture, 'areaid');
    $placeholders = implode(',', array_fill(0, count($areaidPubblicati), '?'));
    
    // Branche per struttura (solo quelle con almeno 1 prestazione prenotabile con medico)
    $stmtBranche = $modx->prepare("
        SELECT b.areaid, b.branca, b.branca_slug, b.centro_nome
        FROM kos_branche b
        WHERE b.areaid IN ($placeholders)
          AND EXISTS (
              SELECT 1 FROM kos_prestazioni p 
              JOIN kos_medici_prestazioni mp ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
              WHERE p.areaid = b.areaid 
                AND p.typology = b.branca 
                AND (p.is_hidden = 0 OR p.mop_bookability = 2)
          )
        ORDER BY b.centro_nome, b.branca
    ");
    $stmtBranche->execute($areaidPubblicati);
    $brancheDb = $stmtBranche->fetchAll(PDO::FETCH_ASSOC);
    
    // Prestazioni con almeno 1 medico (matrice: visibili + differite)
    $stmtPrestazioni = $modx->prepare("
        SELECT p.areaid, p.activity_id, p.title, p.typology 
        FROM kos_prestazioni p
        WHERE p.areaid IN ($placeholders)
          AND (p.is_hidden = 0 OR p.mop_bookability = 2)
          AND EXISTS (
              SELECT 1 FROM kos_medici_prestazioni mp 
              WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid
          )
        ORDER BY p.typology, p.title
    ");
    $stmtPrestazioni->execute($areaidPubblicati);
    $prestazioniDb = $stmtPrestazioni->fetchAll(PDO::FETCH_ASSOC);
    
    // Medici con branca (matrice: visibili + differite)
    $stmtMedici = $modx->prepare("
        SELECT DISTINCT m.areaid, m.resourceid, m.nome, m.note, p.typology
        FROM kos_medici m
        JOIN kos_medici_prestazioni mp ON m.resourceid = mp.resourceid AND m.areaid = mp.areaid
        JOIN kos_prestazioni p ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
        WHERE m.areaid IN ($placeholders)
          AND (p.is_hidden = 0 OR p.mop_bookability = 2)
        ORDER BY p.typology, m.nome
    ");
    $stmtMedici->execute($areaidPubblicati);
    $mediciDb = $stmtMedici->fetchAll(PDO::FETCH_ASSOC);
    
    // Mappature medico-prestazione (matrice: visibili + differite)
    $stmtMappature = $modx->prepare("
        SELECT mp.areaid, mp.resourceid, mp.activity_id, p.typology
        FROM kos_medici_prestazioni mp
        JOIN kos_prestazioni p ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
        WHERE mp.areaid IN ($placeholders)
          AND (p.is_hidden = 0 OR p.mop_bookability = 2)
    ");
    $stmtMappature->execute($areaidPubblicati);
    $mappatureDb = $stmtMappature->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizza dati per struttura
    $branchePerStruttura = [];
    foreach ($brancheDb as $b) {
        $branchePerStruttura[$b['areaid']][] = [
            'branca' => $b['branca'],
            'slug' => $b['branca_slug']
        ];
    }
    
    // Prestazioni per areaid|typology
    $prestazioniPerBranca = [];
    foreach ($prestazioniDb as $p) {
        $key = $p['areaid'] . '|' . $p['typology'];
        $prestazioniPerBranca[$key][] = [
            'id' => $p['activity_id'],
            'title' => $p['title']
        ];
    }
    
    // Medici per areaid|typology (deduplica)
    $mediciPerBranca = [];
    $mediciVisti = [];
    foreach ($mediciDb as $m) {
        $key = $m['areaid'] . '|' . $m['typology'];
        $dedupKey = $key . '|' . $m['resourceid'];
        if (!isset($mediciVisti[$dedupKey])) {
            $mediciPerBranca[$key][] = [
                'id' => $m['resourceid'],
                'nome' => $m['nome'],
                'note' => $m['note'] ?? ''
            ];
            $mediciVisti[$dedupKey] = true;
        }
    }
    
    // Mappature per filtri incrociati
    $medicoToPrestazioni = [];
    $prestazioneToMedici = [];
    
    foreach ($mappatureDb as $mp) {
        $keyMed = $mp['areaid'] . '|' . $mp['typology'] . '|' . $mp['resourceid'];
        $keyPrest = $mp['areaid'] . '|' . $mp['typology'] . '|' . $mp['activity_id'];
        
        if (!isset($medicoToPrestazioni[$keyMed])) {
            $medicoToPrestazioni[$keyMed] = [];
        }
        $medicoToPrestazioni[$keyMed][] = $mp['activity_id'];
        
        if (!isset($prestazioneToMedici[$keyPrest])) {
            $prestazioneToMedici[$keyPrest] = [];
        }
        $prestazioneToMedici[$keyPrest][] = $mp['resourceid'];
    }
    
    $dataJson = json_encode([
        'branche' => $branchePerStruttura,
        'prestazioni' => $prestazioniPerBranca,
        'medici' => $mediciPerBranca,
        'medico_to_prestazioni' => $medicoToPrestazioni,
        'prestazione_to_medici' => $prestazioneToMedici
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    $dataBase64 = base64_encode($dataJson);
    
} catch (PDOException $e) {
    return '<div class="alert alert-danger">Errore database: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

ob_start();
?>

<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

<div class="kos-booking-bar-home kos-booking-bar-home--dark py-4">
    <div class="container">
        <p><strong>Importante</strong>. Le prenotazioni devono essere effettuate <strong>a nome della persona che eseguirà l'esame</strong>, pertanto puoi prenotare anche per un familiare. Se hai una convenzione o un'assicurazione, ti invitiamo a contattare direttamente la struttura. Ricorda di portare con te le richieste di esame (ricette, prescrizioni) ed eventuali referti di visite ed esami eseguiti in precedenza.</p>

        <script>
        window.KOS_HOME_DATA_B64 = '<?php echo $dataBase64; ?>';
        </script>
        
        <form id="bookingFormHome" class="row g-3 align-items-start">
            <!-- RIGA 1: STRUTTURA + BRANCA -->
            <div class="col-12 col-xl-6">
                <label for="selectStrutturaHome" class="form-label">
                    <span class="step-number">1</span> Struttura
                </label>
                <?php 
                $numStrutture = count($strutture);
                $struttureText = $numStrutture === 1 ? 'Struttura disponibile' : 'Strutture disponibili';
                ?>
                <select id="selectStrutturaHome" name="struttura" class="form-select form-select-lg no-nice-select" placeholder="Seleziona una delle <?= $numStrutture ?> <?= $struttureText ?>" autocomplete="off" required>
                    <option value=""></option>
                    <?php foreach ($strutture as $s): ?>
                        <option value="<?= htmlspecialchars($s['areaid']) ?>" data-nome="<?= htmlspecialchars($s['centro_nome']) ?>">
                            <?= htmlspecialchars($s['centro_nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-xl-6">
                <label for="selectBrancaHome" class="form-label">
                    <span class="step-number">2</span> Specialità
                </label>
                <select id="selectBrancaHome" name="branca" class="form-select form-select-lg no-nice-select" placeholder="Seleziona prima la Struttura" autocomplete="off" required disabled>
                </select>
            </div>

            <!-- RIGA 2: PRESTAZIONE + MEDICO -->
            <div class="col-12 col-xl-6">
                <label for="selectPrestazioneHome" class="form-label">
                    <span class="step-number">3</span> Prestazione
                </label>
                <select id="selectPrestazioneHome" name="prestazione" class="form-select form-select-lg no-nice-select" placeholder="Seleziona prima la Specialità" autocomplete="off" required disabled>
                </select>
                <small class="hint-text d-block mt-1">Oppure scegli prima il medico →</small>
                <small id="infoPrestazioneAutoHome" class="auto-select-info fw-bold" style="display:none;"></small>
            </div>

            <div class="col-12 col-xl-6">
                <label for="selectMedicoHome" class="form-label">
                    <span class="step-number">4</span> Medico
                </label>
                <select id="selectMedicoHome" name="medico" class="form-select form-select-lg no-nice-select" placeholder="Seleziona prima la Specialità" autocomplete="off" required disabled>
                </select>
                <small class="hint-text d-block mt-1">← Oppure scegli prima la prestazione</small>
                <small id="infoMedicoAutoHome" class="auto-select-info fw-bold" style="display:none;"></small>
            </div>

            <!-- RIGA 3: BOTTONI -->
            <div class="col-12 mt-4">
                <div class="d-flex flex-column flex-xl-row gap-3 kos-bottoni-row">
                    <!-- Link <a> per permettere a GTM Conversion Linker di aggiungere _gl -->
                    <div class="kos-btn-cerca-col">
                        <a href="#" 
                           class="btn btn-warning btn-lg w-100 btn-prenota-kos disabled" 
                           id="btnCercaHome" 
                           role="button"
                           aria-disabled="true">
                            <i class="bi bi-search"></i> Cerca
                        </a>
                    </div>
                    <!-- Bottone Reset - nascosto inizialmente -->
                    <div class="kos-btn-reset-col" id="btnResetColHome" style="display: none;">
                        <button type="button" class="btn btn-outline-light btn-lg w-100" id="btnResetHome">
                            <i class="bi bi-arrow-counterclockwise"></i> Cambia scelta
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
(function() {
    'use strict';
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // Verifica che Tom Select sia caricato
        if (typeof TomSelect === 'undefined') {
            console.error('[KOS Home] Tom Select non caricato!');
            return;
        }
        
        let DATA;
        try {
            const jsonString = atob(window.KOS_HOME_DATA_B64);
            DATA = JSON.parse(jsonString);
        } catch(e) {
            console.error('[KOS Home] Errore caricamento dati:', e);
            return;
        }
        
        const CONFIG = {
            PORTAL_URL: 'https://app.tuotempo.com/mop/index.php',
            DB_NAME: 'tt_kosgroup_portale'
        };
        
        const selectStruttura = document.getElementById('selectStrutturaHome');
        const selectBranca = document.getElementById('selectBrancaHome');
        const selectPrestazione = document.getElementById('selectPrestazioneHome');
        const selectMedico = document.getElementById('selectMedicoHome');
        const btnCerca = document.getElementById('btnCercaHome');
        const btnReset = document.getElementById('btnResetHome');
        const btnResetCol = document.getElementById('btnResetColHome');
        const bottoniRow = document.querySelector('.kos-booking-bar-home .kos-bottoni-row');
        const infoPrestazioneAuto = document.getElementById('infoPrestazioneAutoHome');
        const infoMedicoAuto = document.getElementById('infoMedicoAutoHome');
        
        let currentAreaId = '';
        let currentBranca = '';
        let isUpdating = false;
        let isAutoSelection = false;
        
        // Event listener per bottone reset
        btnReset.addEventListener('click', resetSearch);
        
        // ====== INIZIALIZZA TOM SELECT ======
        
        // Tom Select per Struttura
        const tsStruttura = new TomSelect(selectStruttura, {
            create: false,
            plugins: {
                'dropdown_input': {}
            },
            placeholder: 'Seleziona una delle <?= $numStrutture ?> <?= $struttureText ?>',
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">Nessun risultato trovato</div>';
                }
            },
            onChange: function(value) {
                if (isUpdating) return;
                currentAreaId = value;
                resetAfterStruttura();
                if (currentAreaId) {
                    updateBranche();
                }
            },
            onInitialize: function() {
                this.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona una delle <?= $numStrutture ?> <?= $struttureText ?>');
                var dropdownInput = this.dropdown.querySelector('.dropdown-input');
                if (dropdownInput) {
                    dropdownInput.placeholder = 'Cerca una struttura';
                }
            },
            onDropdownClose: function() {
                this.blur();
            }
        });
        
        // Tom Select per Branca
        const tsBranca = new TomSelect(selectBranca, {
            create: false,
            plugins: {
                'dropdown_input': {}
            },
            placeholder: 'Seleziona prima la Struttura',
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">Nessun risultato trovato</div>';
                }
            },
            onChange: function(value) {
                if (isUpdating) return;
                currentBranca = value;
                resetAfterBranca();
                if (currentBranca) {
                    populateBothSelects();
                }
            },
            onInitialize: function() {
                this.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Struttura');
                var dropdownInput = this.dropdown.querySelector('.dropdown-input');
                if (dropdownInput) {
                    dropdownInput.placeholder = 'Cerca una specialità';
                }
            },
            onDropdownClose: function() {
                this.blur();
            }
        });
        tsBranca.disable();
        
        // Tom Select per Prestazione
        const tsPrestazione = new TomSelect(selectPrestazione, {
            create: false,
            plugins: {
                'dropdown_input': {}
            },
            placeholder: 'Seleziona prima la Specialità',
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">Nessun risultato trovato</div>';
                }
            },
            onChange: function(value) {
                if (isUpdating || isAutoSelection) {
                    isAutoSelection = false;
                    return;
                }
                if (value) {
                    filterMediciByPrestazione(value);
                }
                updateBookingLink();
            },
            onInitialize: function() {
                this.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
                var dropdownInput = this.dropdown.querySelector('.dropdown-input');
                if (dropdownInput) {
                    dropdownInput.placeholder = 'Cerca una prestazione';
                }
            },
            onDropdownClose: function() {
                this.blur();
            }
        });
        tsPrestazione.disable();
        
        // Tom Select per Medico
        const tsMedico = new TomSelect(selectMedico, {
            create: false,
            plugins: {
                'dropdown_input': {}
            },
            placeholder: 'Seleziona prima la Specialità',
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">Nessun risultato trovato</div>';
                }
            },
            onChange: function(value) {
                if (isUpdating || isAutoSelection) {
                    isAutoSelection = false;
                    return;
                }
                if (value) {
                    filterPrestazioniByMedico(value);
                }
                updateBookingLink();
            },
            onInitialize: function() {
                this.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
                var dropdownInput = this.dropdown.querySelector('.dropdown-input');
                if (dropdownInput) {
                    dropdownInput.placeholder = 'Cerca un medico';
                }
            },
            onDropdownClose: function() {
                this.blur();
            }
        });
        tsMedico.disable();
        
        // Click sul link
        btnCerca.addEventListener('click', handleBookingClick);
        
        // ====== RESET FUNCTIONS ======
        function resetAfterStruttura() {
            isUpdating = true;
            currentBranca = '';
            
            // Reset Branca
            tsBranca.clear();
            tsBranca.clearOptions();
            tsBranca.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Struttura');
            tsBranca.inputState();
            tsBranca.disable();
            
            // Reset Prestazione
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            tsPrestazione.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
            tsPrestazione.inputState();
            tsPrestazione.disable();
            
            // Reset Medico
            tsMedico.clear();
            tsMedico.clearOptions();
            tsMedico.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
            tsMedico.inputState();
            tsMedico.disable();
            
            disableButton();
            hideAutoSelectionInfo();
            hideResetButton();
            
            isUpdating = false;
        }
        
        function resetAfterBranca() {
            isUpdating = true;
            
            // Reset Prestazione
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            tsPrestazione.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
            tsPrestazione.inputState();
            tsPrestazione.disable();
            
            // Reset Medico
            tsMedico.clear();
            tsMedico.clearOptions();
            tsMedico.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
            tsMedico.inputState();
            tsMedico.disable();
            
            disableButton();
            hideAutoSelectionInfo();
            hideResetButton();
            
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
        
        function showResetButton() {
            btnResetCol.style.display = 'flex';
            if (bottoniRow) bottoniRow.classList.add('has-reset');
        }
        
        function hideResetButton() {
            btnResetCol.style.display = 'none';
            if (bottoniRow) bottoniRow.classList.remove('has-reset');
        }
        
        // ====== UPDATE BRANCHE ======
        function updateBranche() {
            if (!currentAreaId || !DATA.branche[currentAreaId]) {
                return;
            }
            
            const branche = DATA.branche[currentAreaId];
            const numBranche = branche.length;
            const brancheText = numBranche === 1 ? 'Specialità disponibile' : 'Specialità disponibili';
            
            tsBranca.clear();
            tsBranca.clearOptions();
            branche.forEach(b => {
                // Conta le prestazioni per questa branca
                const keyBranca = currentAreaId + '|' + b.branca;
                const prestazioni = DATA.prestazioni[keyBranca] || [];
                const numPrestazioni = prestazioni.length;
                const prestazioniText = numPrestazioni === 1 ? 'prestazione' : 'prestazioni';
                
                tsBranca.addOption({
                    value: b.branca,
                    text: capitalizeFirst(b.branca) + ' (' + numPrestazioni + ' ' + prestazioniText + ')'
                });
            });
            tsBranca.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona una delle ' + numBranche + ' ' + brancheText);
            var dropdownInputBranca = tsBranca.dropdown.querySelector('.dropdown-input');
            if (dropdownInputBranca) {
                dropdownInputBranca.placeholder = 'Cerca una specialità';
            }
            tsBranca.inputState();
            tsBranca.refreshState();
            tsBranca.enable();
        }
        
        // ====== POPULATE PRESTAZIONI E MEDICI ======
        function populateBothSelects() {
            if (!currentAreaId || !currentBranca) return;
            
            isUpdating = true;
            
            const keyBranca = currentAreaId + '|' + currentBranca;
            
            // Prestazioni
            const prestazioni = DATA.prestazioni[keyBranca] || [];
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            prestazioni.forEach(p => {
                tsPrestazione.addOption({
                    value: p.id,
                    text: capitalizeFirst(p.title)
                });
            });
            tsPrestazione.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona una prestazione');
            var dropdownInputPrest = tsPrestazione.dropdown.querySelector('.dropdown-input');
            if (dropdownInputPrest) {
                dropdownInputPrest.placeholder = 'Cerca una prestazione';
            }
            tsPrestazione.inputState();
            tsPrestazione.refreshState();
            tsPrestazione.enable();
            
            // Medici
            const medici = DATA.medici[keyBranca] || [];
            tsMedico.clear();
            tsMedico.clearOptions();
            medici.forEach(m => {
                tsMedico.addOption({
                    value: m.id,
                    text: m.nome
                });
            });
            tsMedico.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona un medico');
            var dropdownInputMed = tsMedico.dropdown.querySelector('.dropdown-input');
            if (dropdownInputMed) {
                dropdownInputMed.placeholder = 'Cerca un medico';
            }
            tsMedico.inputState();
            tsMedico.refreshState();
            tsMedico.enable();
            
            isUpdating = false;
        }
        
        // ====== FILTRI INCROCIATI ======
        function filterMediciByPrestazione(prestazioneId) {
            if (!currentAreaId || !currentBranca) return;
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
            const keyPrest = currentAreaId + '|' + currentBranca + '|' + prestazioneId;
            const mediciValidi = DATA.prestazione_to_medici[keyPrest] || [];
            
            const keyBranca = currentAreaId + '|' + currentBranca;
            const tuttiMedici = DATA.medici[keyBranca] || [];
            const mediciFiltrati = tuttiMedici.filter(m => mediciValidi.includes(m.id));
            
            // Salva valore precedente
            const previousValue = tsMedico.getValue();
            
            // Ricostruisci opzioni
            tsMedico.clear();
            tsMedico.clearOptions();
            mediciFiltrati.forEach(m => {
                tsMedico.addOption({
                    value: m.id,
                    text: m.nome
                });
            });
            
            // Ripristina valore precedente se ancora valido
            if (previousValue && mediciValidi.includes(previousValue)) {
                tsMedico.setValue(previousValue, true);
            }
            // Auto-selezione solo se uno solo E non c'era già un valore valido
            else if (mediciFiltrati.length === 1) {
                isAutoSelection = true;
                tsMedico.setValue(mediciFiltrati[0].id, true);
                if (previousValue !== mediciFiltrati[0].id && infoMedicoAuto) {
                    infoMedicoAuto.innerHTML = '✓ Unica combinazione disponibile: puoi avviare la ricerca';
                    infoMedicoAuto.style.display = 'block';
                }
            }
            
            if (mediciFiltrati.length === 0) {
                tsMedico.disable();
            } else {
                tsMedico.enable();
            }
            
            showResetButton();
            
            isUpdating = false;
            updateBookingLink();
        }
        
        function filterPrestazioniByMedico(medicoId) {
            if (!currentAreaId || !currentBranca) return;
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
            const keyMed = currentAreaId + '|' + currentBranca + '|' + medicoId;
            const prestazioniValide = DATA.medico_to_prestazioni[keyMed] || [];
            
            const keyBranca = currentAreaId + '|' + currentBranca;
            const tuttePrestazioni = DATA.prestazioni[keyBranca] || [];
            const prestazioniFiltrate = tuttePrestazioni.filter(p => prestazioniValide.includes(p.id));
            
            // Salva valore precedente
            const previousValue = tsPrestazione.getValue();
            
            // Ricostruisci opzioni
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            prestazioniFiltrate.forEach(p => {
                tsPrestazione.addOption({
                    value: p.id,
                    text: capitalizeFirst(p.title)
                });
            });
            
            // Ripristina valore precedente se ancora valido
            if (previousValue && prestazioniValide.includes(previousValue)) {
                tsPrestazione.setValue(previousValue, true);
            }
            // Auto-selezione solo se una sola E non c'era già un valore valido
            else if (prestazioniFiltrate.length === 1) {
                isAutoSelection = true;
                tsPrestazione.setValue(prestazioniFiltrate[0].id, true);
                if (previousValue !== prestazioniFiltrate[0].id && infoPrestazioneAuto) {
                    infoPrestazioneAuto.innerHTML = '✓ Unica combinazione disponibile: puoi avviare la ricerca';
                    infoPrestazioneAuto.style.display = 'block';
                }
            }
            
            if (prestazioniFiltrate.length === 0) {
                tsPrestazione.disable();
            } else {
                tsPrestazione.enable();
            }
            
            showResetButton();
            
            isUpdating = false;
            updateBookingLink();
        }
        
        // ====== RESET SEARCH ======
        function resetSearch() {
            if (!currentAreaId || !currentBranca) return;
            
            isUpdating = true;
            
            tsPrestazione.clear();
            tsMedico.clear();
            
            populateBothSelects();
            
            hideAutoSelectionInfo();
            hideResetButton();
            disableButton();
            
            isUpdating = false;
        }
        
        // ====== UPDATE BOOKING LINK ======
        function updateBookingLink() {
            const prestazioneId = tsPrestazione.getValue();
            const medicoId = tsMedico.getValue();
            
            if (!currentAreaId || !prestazioneId || !medicoId) {
                disableButton();
                return;
            }
            
            const url = new URL(CONFIG.PORTAL_URL);
            url.searchParams.set('dbName', CONFIG.DB_NAME);
            url.searchParams.set('activityid', prestazioneId);
            url.searchParams.set('areaid', currentAreaId);
            url.searchParams.set('resourceid', medicoId);
            url.searchParams.set('kos_referrer', window.location.pathname);
            
            btnCerca.href = url.toString();
            btnCerca.classList.remove('disabled');
            btnCerca.removeAttribute('aria-disabled');
            
            console.log('[KOS Home] Link aggiornato:', url.toString());
        }
        
        // ====== HANDLE BOOKING CLICK ======
        function handleBookingClick(e) {
            if (btnCerca.classList.contains('disabled')) {
                e.preventDefault();
                return false;
            }
            // Tracciamento del passaggio al portale (GTM: evento personalizzato kos_widget_cerca).
            // Push sincrono: non blocca la navigazione e non tocca l'href,
            // quindi la decorazione _gl del linker resta invariata.
            try {
                const optStruttura = selectStruttura.querySelector('option[value="' + CSS.escape(currentAreaId) + '"]');
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: 'kos_widget_cerca',
                    widget_tipo: 'prenota-online',
                    link_url: btnCerca.href.split('&_gl=')[0].split('?_gl=')[0],
                    widget_areaid: currentAreaId,
                    widget_struttura: optStruttura ? (optStruttura.dataset.nome || '') : '',
                    widget_branca: currentBranca,
                    widget_activityid: tsPrestazione.getValue(),
                    widget_resourceid: tsMedico.getValue()
                });
            } catch (err) {
                console.warn('[KOS Home] Push dataLayer non riuscito:', err);
            }
            // Non preveniamo il comportamento default:
            // il link naviga e GTM Conversion Linker aggiunge _gl
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
.kos-booking-bar-home--dark {
    background-color: #1A2031;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.kos-booking-bar-home--dark p {
    color: rgba(255,255,255,0.9);
}

.kos-booking-bar-home--dark p strong {
    color: #ffc107;
}

.kos-booking-bar-home--dark .form-label {
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

/* Hint text sotto le select */
.kos-booking-bar-home--dark .hint-text {
    font-size: 0.75rem;
    font-style: italic;
    color: rgba(255,255,255,0.7);
}

/* Messaggio auto-selezione */
.kos-booking-bar-home--dark .auto-select-info {
    color: #ffc107;
    font-size: 0.8rem;
    margin-top: 4px;
}

.kos-booking-bar-home--dark .btn {
    height: 48px;
    font-weight: 600;
}

/* Layout bottoni */
.kos-booking-bar-home--dark .kos-bottoni-row {
    align-items: stretch;
}

.kos-booking-bar-home--dark .kos-btn-reset-col,
.kos-booking-bar-home--dark .kos-btn-cerca-col {
    width: 100%;
}

@media (min-width: 1200px) {
    /* Cerca sempre largo come la select Prestazione (50%) */
    .kos-booking-bar-home--dark .kos-btn-cerca-col {
        flex: 0 0 auto;
        width: calc(50% - 0.5rem);
    }
    
    /* Quando Reset è visibile: entrambi stessa larghezza */
    .kos-booking-bar-home--dark .kos-bottoni-row.has-reset .kos-btn-reset-col,
    .kos-booking-bar-home--dark .kos-bottoni-row.has-reset .kos-btn-cerca-col {
        flex: 1 1 0;
        width: 50%;
        min-width: 0;
    }
}

/* Nascondi eventuali NiceSelect applicati dal tema */
.kos-booking-bar-home--dark .nice-select {
    display: none !important;
}

/* ====== TOM SELECT STYLING ====== */
.kos-booking-bar-home--dark .ts-wrapper {
    width: 100%;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-control {
    background-color: #ffffff;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 0.375rem;
    min-height: 30px !important;
    max-height: 30px !important;
    height: 30px !important;
    padding: 0 12px !important;
    font-size: 14px;
    color: #212529;
    cursor: pointer;
    display: flex !important;
    align-items: center !important;
    line-height: 30px;
    position: relative;
}

/* Nasconde input nel controllo quando usa dropdown_input */
.kos-booking-bar-home--dark .ts-wrapper.plugin-dropdown_input .ts-control > input {
    opacity: 0 !important;
    width: 0 !important;
    position: absolute !important;
}

/* Mostra placeholder usando pseudo-elemento quando non c'è selezione */
.kos-booking-bar-home--dark .ts-wrapper.plugin-dropdown_input:not(.has-items) .ts-control::before {
    content: attr(data-placeholder);
    color: #6c757d;
    font-size: 14px;
    pointer-events: none;
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
}

.kos-booking-bar-home--dark .ts-wrapper .ts-control .item {
    font-size: 14px;
    color: #212529;
    line-height: 30px;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-control:hover {
    border-color: rgba(255,255,255,0.5);
}

.kos-booking-bar-home--dark .ts-wrapper.focus .ts-control {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
    outline: none;
}

/* Input di ricerca nel dropdown */
.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .dropdown-input-wrap {
    padding: 8px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .dropdown-input {
    width: 100%;
    padding: 6px 10px;
    font-size: 14px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    outline: none;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .dropdown-input:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 2px rgba(255,193,7,0.25);
}

.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .dropdown-input::placeholder {
    color: #6c757d;
}

/* Dropdown */
.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown {
    background-color: #ffffff;
    border: 2px solid #ffc107;
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
    max-height: 300px;
    overflow-y: auto;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .option {
    font-size: 14px;
    color: #212529;
    padding: 8px 12px;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .option:hover,
.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .option.active {
    background-color: #ffc107;
    color: #1A2031;
}

.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .option.selected {
    background-color: #1A2031;
    color: #ffffff;
}

/* Messaggio nessun risultato */
.kos-booking-bar-home--dark .ts-wrapper .ts-dropdown .no-results {
    padding: 10px;
    text-align: center;
    color: #6c757d;
    font-size: 14px;
}

/* Cursore disabilitato */
.kos-booking-bar-home--dark .ts-wrapper.disabled .ts-control {
    cursor: not-allowed !important;
    opacity: 0.6;
}

.kos-booking-bar-home--dark .btn.disabled,
.kos-booking-bar-home--dark a.disabled {
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

/* Bottone Cerca (warning) */
.kos-booking-bar-home--dark .btn-warning {
    background-color: #ffc107;
    border-color: #ffc107 !important;
    color: #1A2031;
    font-weight: 600;
}

.kos-booking-bar-home--dark .btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00 !important;
    color: #1A2031;
}

.kos-booking-bar-home--dark .btn-warning.disabled {
    background-color: rgba(255,193,7,0.5);
    border-color: rgba(255,193,7,0.5) !important;
    color: rgba(26,32,49,0.7);
}

/* Bottone Reset (outline-light) */
.kos-booking-bar-home--dark .btn-outline-light {
    border: 2px solid rgba(255,255,255,0.7) !important;
    color: #ffffff;
    background-color: transparent;
}

.kos-booking-bar-home--dark .btn-outline-light:hover {
    background-color: rgba(255,255,255,0.1);
    border-color: #ffffff !important;
    color: #ffffff;
}

/* Step numbers - sfondo bianco, numero blu */
.kos-booking-bar-home--dark .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background-color: #ffffff;
    color: #1A2031;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: 700;
    margin-right: 6px;
}

/* Responsive */
@media (max-width: 991px) {
    .kos-booking-bar-home--dark .col-12 {
        margin-bottom: 0.75rem;
    }
    
    /* Nasconde messaggi "Oppure..." su mobile */
    .kos-booking-bar-home--dark .hint-text {
        display: none;
    }
}
</style>

<?php
return ob_get_clean();