<?php
/**
 * BARRA PRENOTAZIONE DA DATABASE - CON TOM SELECT (ricerca)
 * Snippet MODX 3.1
 * Tom Select sostituisce NiceSelect per avere ricerca nelle select
 * Tracking: push dataLayer 'kos_widget_cerca' (widget_tipo = struttura) al clic su Cerca
 * 
 * FILTRI VISIBILITÀ (matrice):
 *  - Prestazione visibile SE: is_hidden = 0 OPPURE mop_bookability = 2 (differita)
 *  - Prestazione deve avere almeno 1 medico in kos_medici_prestazioni
 *  - Branca visibile solo se ha almeno 1 prestazione valida con medico
 */

$ist = $modx->resource->getTVValue('istanza');
$centroNome = $modx->resource->get('pagetitle') ?: 'Centro KOS';

if (empty($ist)) {
    return '<div class="alert alert-warning">Struttura non configurata.</div>';
}

try {
    // Nome della struttura come in kos_strutture (stesso formato del widget home e della dashboard).
    // Usato solo per il tracciamento; se manca si ricade sul titolo della pagina.
    $stmtNome = $modx->prepare("SELECT nome FROM kos_strutture WHERE areaid = ? LIMIT 1");
    $stmtNome->execute([$ist]);
    $centroNomeDb = $stmtNome->fetchColumn() ?: $centroNome;

    // Branche che hanno almeno 1 prestazione prenotabile con medico associato
    $stmtBranche = $modx->prepare("
        SELECT DISTINCT b.branca, b.branca_slug 
        FROM kos_branche b
        WHERE b.areaid = ? 
          AND EXISTS (
              SELECT 1 FROM kos_prestazioni p 
              JOIN kos_medici_prestazioni mp ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
              WHERE p.areaid = b.areaid 
                AND p.typology = b.branca 
                AND (p.is_hidden = 0 OR p.mop_bookability = 2)
          )
        ORDER BY b.branca
    ");
    $stmtBranche->execute([$ist]);
    $brancheDb = $stmtBranche->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($brancheDb)) {
        return '<div class="alert alert-warning">Nessuna prestazione disponibile.</div>';
    }
    
    // Prestazioni con almeno 1 medico (matrice: visibili + differite)
    $stmtPrestazioni = $modx->prepare("
        SELECT p.activity_id, p.title, p.typology 
        FROM kos_prestazioni p
        WHERE p.areaid = ? 
          AND (p.is_hidden = 0 OR p.mop_bookability = 2)
          AND EXISTS (
              SELECT 1 FROM kos_medici_prestazioni mp 
              WHERE mp.activity_id = p.activity_id AND mp.areaid = p.areaid
          )
        ORDER BY p.typology, p.title
    ");
    $stmtPrestazioni->execute([$ist]);
    $prestazioniDb = $stmtPrestazioni->fetchAll(PDO::FETCH_ASSOC);
    
    // Medici DISTINTI per typology (matrice: visibili + differite)
    $stmtMedici = $modx->prepare("
        SELECT DISTINCT m.resourceid, m.nome, m.note, p.typology
        FROM kos_medici m
        JOIN kos_medici_prestazioni mp ON m.resourceid = mp.resourceid AND m.areaid = mp.areaid
        JOIN kos_prestazioni p ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
        WHERE m.areaid = ?
          AND (p.is_hidden = 0 OR p.mop_bookability = 2)
        ORDER BY p.typology, m.nome
    ");
    $stmtMedici->execute([$ist]);
    $mediciDb = $stmtMedici->fetchAll(PDO::FETCH_ASSOC);
    
    // Mappatura: medico + branca -> prestazioni (matrice: visibili + differite)
    $stmtMedPrest = $modx->prepare("
        SELECT mp.resourceid, mp.activity_id, p.typology
        FROM kos_medici_prestazioni mp
        JOIN kos_prestazioni p ON mp.activity_id = p.activity_id AND mp.areaid = p.areaid
        WHERE mp.areaid = ?
          AND (p.is_hidden = 0 OR p.mop_bookability = 2)
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
                'nome' => $m['nome']
            ];
            $mediciAggiunti[$key] = true;
        }
    }
    
    // Mappature bidirezionali per filtri incrociati
    $medicoToPrestazioni = [];
    $prestazioneToMedici = [];
    
    foreach ($mappingDb as $mp) {
        $keyMedico = $mp['typology'] . '|' . $mp['resourceid'];
        $keyPrestazione = $mp['typology'] . '|' . $mp['activity_id'];
        
        if (!isset($medicoToPrestazioni[$keyMedico])) {
            $medicoToPrestazioni[$keyMedico] = [];
        }
        if (!in_array($mp['activity_id'], $medicoToPrestazioni[$keyMedico])) {
            $medicoToPrestazioni[$keyMedico][] = $mp['activity_id'];
        }
        
        if (!isset($prestazioneToMedici[$keyPrestazione])) {
            $prestazioneToMedici[$keyPrestazione] = [];
        }
        if (!in_array($mp['resourceid'], $prestazioneToMedici[$keyPrestazione])) {
            $prestazioneToMedici[$keyPrestazione][] = $mp['resourceid'];
        }
    }
    
    $dataJson = json_encode([
        'branche' => $branche,
        'medico_to_prestazioni' => $medicoToPrestazioni,
        'prestazione_to_medici' => $prestazioneToMedici
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    $dataBase64 = base64_encode($dataJson);
    
} catch (PDOException $e) {
    return '<div class="alert alert-danger">Errore database.</div>';
}

ob_start();
?>

<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

<div id="prenota-online" class="kos-booking-bar kos-booking-bar--dark py-4 mt-4" 
     data-centro-areaid="<?= htmlspecialchars($ist) ?>"
     data-centro-nome="<?= htmlspecialchars($centroNome) ?>"
     data-centro-nome-db="<?= htmlspecialchars($centroNomeDb) ?>">
    <div class="container">
        <h2 class="h4">Prenota online</h2>
        <p><strong>Importante</strong>. Le prenotazioni devono essere effettuate <strong>a nome della persona che eseguirà l'esame</strong>, pertanto puoi prenotare anche per un familiare. Se hai una convenzione o un'assicurazione, ti invitiamo a contattare direttamente la struttura. Ricorda di portare con te le richieste di esame (ricette, prescrizioni) ed eventuali referti di visite ed esami eseguiti in precedenza.</p>
        
        <script>
        window.KOS_DATA_DB_B64 = '<?= $dataBase64 ?>';
        </script>
        
        <form id="bookingForm" class="row g-3">
            <div class="col-12">
                <label for="selectBranca" class="form-label">
                    <span class="step-number">1</span> Seleziona la Specialità Medica *
                </label>
                <?php 
                $numBranche = count($brancheDb);
                $brancheText = $numBranche === 1 ? 'Specialità' : 'Specialità';
                ?>
                <select id="selectBranca" name="branca" class="form-select form-select-lg no-nice-select" placeholder="Seleziona una delle <?= $numBranche ?> <?= $brancheText ?>" autocomplete="off" required aria-label="Seleziona la Specialità Medica">
                    <option value=""></option>
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
                <select id="selectPrestazione" name="prestazione" class="form-select form-select-lg no-nice-select" placeholder="Seleziona prima la Specialità" autocomplete="off" required disabled aria-label="Seleziona Prestazione">
                </select>
                <small class="hint-text">Oppure scegli prima il medico →</small>
                <small id="infoPrestazioneAuto" class="auto-select-info fw-bold d-block" style="display:none;"></small>
            </div>

            <div class="col-12 col-xl-6">
                <label for="selectMedico" class="form-label">
                    <span class="step-number">3</span> Seleziona Medico
                </label>
                <select id="selectMedico" name="medico" class="form-select form-select-lg no-nice-select" placeholder="Seleziona prima la Specialità" autocomplete="off" required disabled aria-label="Seleziona Medico">
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
        var bookingBar = document.querySelector('.kos-booking-bar');
        if (!bookingBar) return;
        
        // ====== DISTRUGGI NICESELECT SE PRESENTE ======
        if (window.jQuery && typeof jQuery.fn.niceSelect === 'function') {
            bookingBar.querySelectorAll('select').forEach(function(sel) {
                try {
                    jQuery(sel).niceSelect('destroy');
                } catch(e) {}
            });
        }
        
        var CONFIG = {
            PORTAL_URL: 'https://app.tuotempo.com/mop/index.php',
            DB_NAME: 'tt_kosgroup_portale',
            CENTRO_AREAID: bookingBar.dataset.centroAreaid,
            CENTRO_NOME: bookingBar.dataset.centroNome,
            CENTRO_NOME_DB: bookingBar.dataset.centroNomeDb
        };
        
        var DATA;
        try {
            var jsonString = atob(window.KOS_DATA_DB_B64);
            DATA = JSON.parse(jsonString);
        } catch(e) {
            console.error('Errore parsing dati:', e);
            return;
        }
        
        var selectBranca = document.getElementById('selectBranca');
        var selectPrestazione = document.getElementById('selectPrestazione');
        var selectMedico = document.getElementById('selectMedico');
        var btnCerca = document.getElementById('btnCerca');
        var btnReset = document.getElementById('btnReset');
        var bookingForm = document.getElementById('bookingForm');
        var infoPrestazioneAuto = document.getElementById('infoPrestazioneAuto');
        var infoMedicoAuto = document.getElementById('infoMedicoAuto');
        
        if (!selectBranca || !selectPrestazione || !selectMedico || !btnCerca || !btnReset || !bookingForm) return;
        
        // ====== VERIFICA TOM SELECT ======
        if (typeof TomSelect === 'undefined') {
            console.error('[KOS] Tom Select non caricato! Verifica che il CDN sia raggiungibile.');
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning mt-3';
            alertDiv.innerHTML = 'Errore caricamento componenti. Ricarica la pagina o <a href="javascript:location.reload()">clicca qui</a>.';
            bookingForm.prepend(alertDiv);
            return;
        }
        
        var currentBranca = '';
        var isUpdating = false;
        var isAutoSelection = false;
        
        // ====== INIZIALIZZA TOM SELECT ======
        
       // Tom Select per Branca
        var tsBranca = new TomSelect(selectBranca, {
            create: false,
            plugins: {
                'dropdown_input': {}
            },
            placeholder: 'Seleziona una delle <?= $numBranche ?> <?= $brancheText ?>',
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">Nessun risultato trovato</div>';
                }
            },
            onChange: function(value) {
                if (isUpdating) return;
                currentBranca = value;
                resetSelections();
                if (currentBranca) {
                    populateBothSelects();
                }
            },
            onInitialize: function() {
                if (this.control_input) {
                    this.control_input.placeholder = 'Seleziona una delle <?= $numBranche ?> <?= $brancheText ?>';
                }
                this.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona una delle <?= $numBranche ?> <?= $brancheText ?>');
                var dropdownInput = this.dropdown.querySelector('.dropdown-input');
                if (dropdownInput) {
                    dropdownInput.placeholder = 'Cerca una specialità';
                }
            },
            onDropdownClose: function() {
                this.blur();
            }
        });
        tsBranca.control.setAttribute('aria-label', 'Seleziona la Specialità Medica'); // ← AGGIUNTO
        
        // Tom Select per Prestazione
        var tsPrestazione = new TomSelect(selectPrestazione, {
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
                if (this.control_input) {
                    this.control_input.placeholder = 'Seleziona prima la Specialità';
                }
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
        tsPrestazione.control.setAttribute('aria-label', 'Seleziona Prestazione'); // ← AGGIUNTO
        tsPrestazione.disable();
        
        // Tom Select per Medico
        var tsMedico = new TomSelect(selectMedico, {
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
                if (this.control_input) {
                    this.control_input.placeholder = 'Seleziona prima la Specialità';
                }
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
        tsMedico.control.setAttribute('aria-label', 'Seleziona Medico'); // ← AGGIUNTO
        tsMedico.disable();
        
        // Event listeners
        bookingForm.addEventListener('submit', function(e) { e.preventDefault(); });
        btnReset.addEventListener('click', resetSearch);
        btnCerca.addEventListener('click', handleBookingClick);
        
        function resetSelections() {
            isUpdating = true;
            
            // Reset Prestazione
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            tsPrestazione.settings.placeholder = 'Seleziona prima la Specialità';
            if (tsPrestazione.control_input) {
                tsPrestazione.control_input.placeholder = 'Seleziona prima la Specialità';
            }
            tsPrestazione.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
            tsPrestazione.inputState();
            tsPrestazione.disable();
            
            // Reset Medico
            tsMedico.clear();
            tsMedico.clearOptions();
            tsMedico.settings.placeholder = 'Seleziona prima la Specialità';
            if (tsMedico.control_input) {
                tsMedico.control_input.placeholder = 'Seleziona prima la Specialità';
            }
            tsMedico.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona prima la Specialità');
            tsMedico.inputState();
            tsMedico.disable();
            
            disableButton();
            btnReset.style.display = 'none';
            hideAutoSelectionInfo();
            
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
            
            var brancaData = DATA.branche[currentBranca];
            
            // Popola Prestazioni
            var prestazioni = brancaData.prestazioni || [];
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            prestazioni.forEach(function(p) {
                tsPrestazione.addOption({
                    value: p.id,
                    text: p.title.charAt(0).toUpperCase() + p.title.slice(1).toLowerCase()
                });
            });
            tsPrestazione.settings.placeholder = 'Seleziona una prestazione';
            if (tsPrestazione.control_input) {
                tsPrestazione.control_input.placeholder = 'Seleziona una prestazione';
                tsPrestazione.control_input.setAttribute('placeholder', 'Seleziona una prestazione');
            }
            var dropdownInputPrest = tsPrestazione.dropdown.querySelector('.dropdown-input');
            if (dropdownInputPrest) {
                dropdownInputPrest.placeholder = 'Cerca una prestazione';
            }
            tsPrestazione.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona una prestazione');
            tsPrestazione.inputState();
            tsPrestazione.refreshState();
            tsPrestazione.enable();
            
            // Popola Medici
            var medici = brancaData.medici || [];
            tsMedico.clear();
            tsMedico.clearOptions();
            medici.forEach(function(m) {
                tsMedico.addOption({
                    value: m.id,
                    text: m.nome
                });
            });
            tsMedico.settings.placeholder = 'Seleziona un medico';
            if (tsMedico.control_input) {
                tsMedico.control_input.placeholder = 'Seleziona un medico';
                tsMedico.control_input.setAttribute('placeholder', 'Seleziona un medico');
            }
            var dropdownInputMed = tsMedico.dropdown.querySelector('.dropdown-input');
            if (dropdownInputMed) {
                dropdownInputMed.placeholder = 'Cerca un medico';
            }
            tsMedico.wrapper.querySelector('.ts-control').setAttribute('data-placeholder', 'Seleziona un medico');
            tsMedico.inputState();
            tsMedico.refreshState();
            tsMedico.enable();
            
            isUpdating = false;
        }
        
        function filterMediciByPrestazione(prestazioneId) {
            if (!currentBranca) return;
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
            var key = currentBranca + '|' + prestazioneId;
            var mediciIds = DATA.prestazione_to_medici[key] || [];
            var brancaData = DATA.branche[currentBranca];
            var tuttiMedici = brancaData.medici || [];
            
            var mediciFiltrati = tuttiMedici.filter(function(m) {
                return mediciIds.includes(m.id);
            });
            
            // Salva valore precedente
            var previousValue = tsMedico.getValue();
            
            // Ricostruisci opzioni
            tsMedico.clear();
            tsMedico.clearOptions();
            mediciFiltrati.forEach(function(m) {
                tsMedico.addOption({
                    value: m.id,
                    text: m.nome
                });
            });
            
            // Ripristina valore precedente se ancora valido
            if (previousValue && mediciIds.includes(previousValue)) {
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
            
            btnReset.style.display = 'block';
            
            isUpdating = false;
            updateBookingLink();
        }
        
        function filterPrestazioniByMedico(medicoId) {
            if (!currentBranca) return;
            
            isUpdating = true;
            hideAutoSelectionInfo();
            
            var key = currentBranca + '|' + medicoId;
            var prestazioniIds = DATA.medico_to_prestazioni[key] || [];
            var brancaData = DATA.branche[currentBranca];
            var tuttePrestazioni = brancaData.prestazioni || [];
            
            var prestazioniFiltrate = tuttePrestazioni.filter(function(p) {
                return prestazioniIds.includes(p.id);
            });
            
            // Salva valore precedente
            var previousValue = tsPrestazione.getValue();
            
            // Ricostruisci opzioni
            tsPrestazione.clear();
            tsPrestazione.clearOptions();
            prestazioniFiltrate.forEach(function(p) {
                tsPrestazione.addOption({
                    value: p.id,
                    text: p.title.charAt(0).toUpperCase() + p.title.slice(1).toLowerCase()
                });
            });
            
            // Ripristina valore precedente se ancora valido
            if (previousValue && prestazioniIds.includes(previousValue)) {
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
            
            btnReset.style.display = 'block';
            
            isUpdating = false;
            updateBookingLink();
        }
        
        function resetSearch() {
            if (!currentBranca) return;
            
            isUpdating = true;
            
            tsPrestazione.clear();
            tsMedico.clear();
            
            populateBothSelects();
            
            btnReset.style.display = 'none';
            disableButton();
            hideAutoSelectionInfo();
            
            isUpdating = false;
        }
        
        function updateBookingLink() {
            var branca = tsBranca.getValue();
            var prestazioneId = tsPrestazione.getValue();
            var medicoId = tsMedico.getValue();
            
            if (branca && prestazioneId && medicoId) {
                var url = new URL(CONFIG.PORTAL_URL);
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
            var branca = tsBranca.getValue();
            var prestazioneId = tsPrestazione.getValue();
            var medicoId = tsMedico.getValue();
            
            if (!branca || !prestazioneId || !medicoId) {
                e.preventDefault();
                alert('Seleziona specialità, prestazione e medico');
                return false;
            }
            
            if (btnCerca.href === '#' || btnCerca.classList.contains('disabled')) {
                e.preventDefault();
                return false;
            }
            
            // Tracciamento del passaggio al portale (GTM: evento personalizzato kos_widget_cerca).
            // Stesso formato del widget della home; push sincrono che non tocca l'href,
            // quindi la decorazione _gl del linker resta invariata.
            try {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: 'kos_widget_cerca',
                    widget_tipo: 'struttura',
                    link_url: btnCerca.href.split('&_gl=')[0].split('?_gl=')[0],
                    widget_areaid: CONFIG.CENTRO_AREAID,
                    widget_struttura: CONFIG.CENTRO_NOME_DB || CONFIG.CENTRO_NOME || '',
                    widget_branca: branca,
                    widget_activityid: prestazioneId,
                    widget_resourceid: medicoId
                });
            } catch (err) {
                console.warn('[KOS] Push dataLayer non riuscito:', err);
            }
            
            console.log('[KOS] Redirect a TuoTempo', {
                href: btnCerca.href,
                areaid: CONFIG.CENTRO_AREAID,
                activityid: prestazioneId,
                resourceid: medicoId
            });
            
            return true;
        }
    }
})();
</script>

<style>
/* ====== VERSIONE ALTO CONTRASTO CON TOM SELECT ====== */
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

/* Nascondi eventuali NiceSelect applicati dal tema */
.kos-booking-bar--dark .nice-select {
    display: none !important;
}

/* ====== TOM SELECT STYLING - VARIANTE DROPDOWN INPUT ====== */
.kos-booking-bar--dark .ts-wrapper {
    width: 100%;
}

.kos-booking-bar--dark .ts-wrapper .ts-control {
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
.kos-booking-bar--dark .ts-wrapper.plugin-dropdown_input .ts-control > input {
    opacity: 0 !important;
    width: 0 !important;
    position: absolute !important;
}

/* Mostra placeholder usando pseudo-elemento quando non c'è selezione */
.kos-booking-bar--dark .ts-wrapper.plugin-dropdown_input:not(.has-items) .ts-control::before {
    content: attr(data-placeholder);
    color: #6c757d;
    font-size: 14px;
    pointer-events: none;
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
}

.kos-booking-bar--dark .ts-wrapper .ts-control .item {
    font-size: 14px;
    color: #212529;
    line-height: 30px;
}

.kos-booking-bar--dark .ts-wrapper .ts-control:hover {
    border-color: rgba(255,255,255,0.5);
}

.kos-booking-bar--dark .ts-wrapper.focus .ts-control {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
    outline: none;
}

/* Input di ricerca nel dropdown */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .dropdown-input-wrap {
    padding: 8px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown .dropdown-input {
    width: 100%;
    padding: 6px 10px;
    font-size: 14px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    outline: none;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown .dropdown-input:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 2px rgba(255,193,7,0.25);
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown .dropdown-input::placeholder {
    color: #6c757d;
}

/* Dropdown */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .option {
    font-size: 14px;
    color: #212529;
}

/* Messaggio nessun risultato */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .no-results {
    padding: 10px;
    text-align: center;
    color: #6c757d;
    font-size: 14px;
}

/* Dropdown */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown {
    background-color: #ffffff;
    border: 2px solid #ffc107;
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-height: 300px;
    overflow-y: auto;
}

/* Opzioni nel dropdown */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .option {
    padding: 0.6rem 1rem;
    color: #212529;
    cursor: pointer;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown .option:hover,
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .option.active {
    background-color: #ffc107;
    color: #1A2031;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown .option.selected {
    background-color: #1A2031;
    color: #ffffff;
}

/* Stato disabled */
.kos-booking-bar--dark .ts-wrapper.disabled .ts-control {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #e9ecef;
}

/* Scrollbar personalizzata */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar {
    width: 8px;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar-thumb {
    background: #1A2031;
    border-radius: 4px;
}

.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar-thumb:hover {
    background: #0d1018;
}

/* ====== BOTTONI ====== */
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

/* Disabled buttons */
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

/* Responsive */
@media (max-width: 768px) {
    .kos-booking-bar--dark .row > div {
        margin-bottom: 1rem;
    }
}
</style>

<?php
return ob_get_clean();