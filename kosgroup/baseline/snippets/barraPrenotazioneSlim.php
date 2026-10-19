<?php
/**
 * BARRA PRENOTAZIONE SLIM - Singola branca, solo prestazioni
 * Snippet MODX 3.1
 *
 * PARAMETRI SNIPPET (con valori di default):
 * ------------------------------------------
 *  &branca       Valore esatto del campo `branca` in kos_branche   (default: 'TAC')
 *  &dbName       Nome database TuoTempo                             (default: 'tt_kosgroup_portale')
 *  &portalUrl    URL base portale TuoTempo                          (default: 'https://app.tuotempo.com/mop/index.php')
 *  &titoloWidget Titolo H2 mostrato nel widget                      (default: 'Prenota online')
 *  &labelSelect  Testo label della select prestazioni               (default: 'Seleziona la prestazione')
 *  &labelButton  Testo del bottone                                  (default: 'Cerca disponibilità')
 *
 * ESEMPIO DI CHIAMATA IN MODX (Cardinal Ferrari – TAC):
 * [[barraPrenotazioneSlim?
 *   &branca=`TAC`
 *   &dbName=`tt_kosgroup_portale`
 * ]]
 *
 * NOTE:
 *  - areaid viene sempre letto dalla TV `istanza` della pagina corrente.
 *  - resourceid viene letto dal DB per ciascuna prestazione (tabella kos_medici
 *    via kos_medici_prestazioni). Se una prestazione ha più risorse associate
 *    viene usata la prima in ordine alfabetico per nome medico.
 *  - Il link apre nella stessa finestra (_self).
 *  - GTM dataLayer push incluso al click.
 */

// ── Parametri snippet con fallback ───────────────────────────────────────────
$brancaFiltro  = $modx->getOption('branca',       $scriptProperties, 'TAC');
$dbName       = 'tt_kosgroup_portale'; // fisso per tutti i centri KOS
$portalUrl     = $modx->getOption('portalUrl',    $scriptProperties, 'https://app.tuotempo.com/mop/index.php');
$titoloWidget  = $modx->getOption('titoloWidget', $scriptProperties, 'Prenota online');
$labelSelect   = $modx->getOption('labelSelect',  $scriptProperties, 'Seleziona la prestazione');
$labelButton   = $modx->getOption('labelButton',  $scriptProperties, 'Cerca disponibilità');

// ── Lettura areaid dalla TV `istanza` ─────────────────────────────────────────
$areaid = $modx->resource->getTVValue('istanza');

if (empty($areaid)) {
    return '<div class="alert alert-warning">Struttura non configurata (TV istanza mancante).</div>';
}

// ── Query: prestazioni della branca + resourceid dal DB ──────────────────────
// Per ogni prestazione recuperiamo il primo resourceid disponibile in
// kos_medici_prestazioni → kos_medici (MIN su resourceid per determinismo).
try {
    $stmt = $modx->prepare("
        SELECT
            p.activity_id,
            p.title,
            MIN(m.resourceid) AS resourceid
        FROM kos_prestazioni p
        INNER JOIN kos_branche b
            ON  b.areaid  = p.areaid
            AND b.branca  = p.typology
        INNER JOIN kos_medici_prestazioni mp
            ON  mp.activity_id = p.activity_id
            AND mp.areaid      = p.areaid
        INNER JOIN kos_medici m
            ON  m.resourceid = mp.resourceid
            AND m.areaid     = mp.areaid
        WHERE p.areaid = :areaid
          AND b.branca = :branca
        GROUP BY p.activity_id, p.title
        ORDER BY p.title ASC
    ");
    $stmt->execute([
        ':areaid' => $areaid,
        ':branca' => $brancaFiltro,
    ]);
    $prestazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($prestazioni)) {
        return '<div class="alert alert-warning">Nessuna prestazione disponibile per la branca "' 
               . htmlspecialchars($brancaFiltro) . '".</div>';
    }

} catch (PDOException $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[barraPrenotazioneSlim] DB error: ' . $e->getMessage());
    return '<div class="alert alert-danger">Errore nel caricamento delle prestazioni.</div>';
}

// ── Build JSON per JS: id, titolo e resourceid per ciascuna prestazione ───────
$prestazioniJs = [];
foreach ($prestazioni as $p) {
    $prestazioniJs[] = [
        'id'         => $p['activity_id'],
        'title'      => ucfirst(strtolower($p['title'])),
        'resourceid' => $p['resourceid'],
    ];
}
$dataBase64 = base64_encode(json_encode($prestazioniJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// ── Testo placeholder select (es. "TAC – 12 prestazioni disponibili") ─────────
$numPrestazioni = count($prestazioni);
$suffissoPl     = $numPrestazioni === 1 ? 'prestazione' : 'prestazioni';
$placeholderTxt = ucfirst(strtolower(htmlspecialchars($brancaFiltro))) . ' (' . $numPrestazioni . ' ' . $suffissoPl . ')';

// ── Unique ID per supportare più istanze nella stessa pagina ──────────────────
$uid = 'kos_slim_' . substr(md5($areaid . $brancaFiltro), 0, 8);

ob_start();
?>

<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

<div id="<?= $uid ?>"
     class="kos-booking-bar kos-booking-bar--dark kos-booking-slim py-4 mt-4"
     data-areaid="<?= htmlspecialchars($areaid) ?>"
     data-dbname="<?= htmlspecialchars($dbName) ?>"
     data-portal-url="<?= htmlspecialchars($portalUrl) ?>"
     data-branca="<?= htmlspecialchars($brancaFiltro) ?>">

    <div class="container">
        <h2 class="h4"><?= htmlspecialchars($titoloWidget) ?></h2>
        <p>
            <strong>Importante.</strong> Le prenotazioni devono essere effettuate 
            <strong>a nome della persona che eseguirà l'esame</strong>, pertanto puoi prenotare anche per un familiare.
            Se hai una convenzione o un'assicurazione, ti invitiamo a contattare direttamente la struttura.
            Ricorda di portare con te le richieste di esame (ricette, prescrizioni) ed eventuali referti precedenti.
        </p>

        <!-- Dati prestazioni (id + resourceid per ciascuna) codificati in base64 -->
        <script>
        (function() {
            window['<?= $uid ?>_DATA'] = JSON.parse(atob('<?= $dataBase64 ?>'));
        })();
        </script>

        <form id="<?= $uid ?>_form" class="row g-3" novalidate>

            <!-- SELECT PRESTAZIONE -->
            <div class="col-12">
                <label for="<?= $uid ?>_selectPrestazione" class="form-label">
                    <span class="step-number">1</span>
                    <?= htmlspecialchars($labelSelect) ?> *
                </label>
                <select id="<?= $uid ?>_selectPrestazione"
                        name="prestazione"
                        class="form-select form-select-lg"
                        data-placeholder="<?= $placeholderTxt ?>"
                        autocomplete="off"
                        required>
                    <option value=""></option>
                    <?php foreach ($prestazioni as $p): ?>
                        <option value="<?= htmlspecialchars($p['activity_id']) ?>">
                            <?= ucfirst(strtolower(htmlspecialchars($p['title']))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- BOTTONE CERCA -->
            <div class="col-12">
                <a href="#"
                   class="btn btn-warning btn-lg w-100 btn-prenota-kos disabled"
                   id="<?= $uid ?>_btnCerca"
                   role="button"
                   aria-disabled="true">
                    <i class="bi bi-search"></i>
                    <?= htmlspecialchars($labelButton) ?>
                </a>
            </div>

        </form>
    </div><!-- /.container -->
</div><!-- /.kos-booking-slim -->

<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
(function () {
    'use strict';

    var UID    = '<?= $uid ?>';
    var widget = document.getElementById(UID);
    if (!widget) return;

    var CONFIG = {
        portalUrl : widget.dataset.portalUrl,
        dbName    : widget.dataset.dbname,
        areaid    : widget.dataset.areaid,
        branca    : widget.dataset.branca
    };

    // Mappa activity_id → resourceid, costruita dal JSON passato via PHP
    var DATA = window[UID + '_DATA'] || [];
    var resourceidMap = {};
    DATA.forEach(function (p) {
        resourceidMap[p.id] = p.resourceid;
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {

        // Verifica Tom Select
        if (typeof TomSelect === 'undefined') {
            console.error('[KOS Slim] Tom Select non disponibile.');
            var warn = document.createElement('div');
            warn.className = 'alert alert-warning mt-2';
            warn.textContent = 'Errore caricamento componenti. Ricarica la pagina.';
            widget.querySelector('.container').prepend(warn);
            return;
        }

        var rawSelect = document.getElementById(UID + '_selectPrestazione');
        var btnCerca  = document.getElementById(UID + '_btnCerca');
        if (!rawSelect || !btnCerca) return;

        // Distruggi eventuale NiceSelect
        if (window.jQuery && typeof jQuery.fn.niceSelect === 'function') {
            try { jQuery(rawSelect).niceSelect('destroy'); } catch (e) {}
        }

        // Placeholder dal data-attribute del <select> originale
        var placeholderText = rawSelect.dataset.placeholder || 'Seleziona una prestazione';

        // ── Tom Select ────────────────────────────────────────────────────────
        var tsSelect = new TomSelect(rawSelect, {
            create: false,
            plugins: { 'dropdown_input': {} },
            placeholder: placeholderText,
            render: {
                no_results: function () {
                    return '<div class="no-results">Nessun risultato trovato</div>';
                }
            },
            onInitialize: function () {
                // Placeholder nel controllo chiuso (via pseudo-elemento CSS)
                var ctrl = this.wrapper.querySelector('.ts-control');
                if (ctrl) ctrl.setAttribute('data-placeholder', placeholderText);

                // Placeholder nel campo di ricerca interno al dropdown
                var dropInput = this.dropdown.querySelector('.dropdown-input');
                if (dropInput) dropInput.placeholder = 'Cerca una prestazione...';
            },
            onChange: function (value) {
                updateButton(value);
            },
            onDropdownClose: function () {
                this.blur();
            }
        });

        // ── Aggiorna bottone usando il resourceid specifico della prestazione ─
        function updateButton(activityId) {
            if (!activityId) {
                disableButton();
                return;
            }

            var resourceid = resourceidMap[activityId] || '';
            if (!resourceid) {
                console.warn('[KOS Slim] Nessun resourceid per activityId:', activityId);
                disableButton();
                return;
            }

            var url = new URL(CONFIG.portalUrl);
            url.searchParams.set('dbName',       CONFIG.dbName);
            url.searchParams.set('activityid',   activityId);
            url.searchParams.set('areaid',        CONFIG.areaid);
            url.searchParams.set('resourceid',    resourceid);
            url.searchParams.set('kos_referrer',  window.location.pathname);

            btnCerca.href = url.toString();
            btnCerca.classList.remove('disabled');
            btnCerca.setAttribute('aria-disabled', 'false');

            console.log('[KOS Slim] Link aggiornato:', url.toString());
        }

        function disableButton() {
            btnCerca.href = '#';
            btnCerca.classList.add('disabled');
            btnCerca.setAttribute('aria-disabled', 'true');
        }

        // ── Click: GTM push + navigazione nella stessa finestra ──────────────
        btnCerca.addEventListener('click', function (e) {
            var activityId = tsSelect.getValue();

            if (!activityId || btnCerca.classList.contains('disabled')) {
                e.preventDefault();
                return false;
            }

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event          : 'booking_start',
                source_type    : 'widget_slim',
                branca         : CONFIG.branca,
                referrer_page  : window.location.pathname,
                centro_areaid  : CONFIG.areaid,
                prestazione_id : activityId,
                medico_id      : resourceidMap[activityId] || ''
            });

            return true; // navigazione stessa finestra
        });

        // Previeni submit form accidentale
        var form = document.getElementById(UID + '_form');
        if (form) form.addEventListener('submit', function (e) { e.preventDefault(); });
    }

})();
</script>

<style>
/* ── Base ──────────────────────────────────────────────────────────── */
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
.kos-booking-bar--dark .nice-select { display: none !important; }

/* ── Tom Select ────────────────────────────────────────────────────── */
.kos-booking-bar--dark .ts-wrapper { width: 100%; }

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
/* Nasconde l'input inline quando usa plugin dropdown_input */
.kos-booking-bar--dark .ts-wrapper.plugin-dropdown_input .ts-control > input {
    opacity: 0 !important;
    width: 0 !important;
    position: absolute !important;
}
/* Placeholder via pseudo-elemento quando nessun item è selezionato */
.kos-booking-bar--dark .ts-wrapper.plugin-dropdown_input:not(.has-items) .ts-control::before {
    content: attr(data-placeholder);
    color: #6c757d;
    font-size: 14px;
    pointer-events: none;
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: calc(100% - 40px); /* lascia spazio alla freccia dropdown */
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
/* Campo di ricerca nel dropdown */
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
.kos-booking-bar--dark .ts-wrapper .ts-dropdown {
    background-color: #ffffff;
    border: 2px solid #ffc107;
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-height: 300px;
    overflow-y: auto;
}
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .option {
    padding: 0.6rem 1rem;
    font-size: 14px;
    color: #212529;
    cursor: pointer;
}
.kos-booking-bar--dark .ts-wrapper .ts-dropdown .no-results {
    padding: 10px;
    text-align: center;
    color: #6c757d;
    font-size: 14px;
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
.kos-booking-bar--dark .ts-wrapper.disabled .ts-control {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #e9ecef;
}
/* Scrollbar */
.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar { width: 8px; }
.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1; border-radius: 4px;
}
.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar-thumb {
    background: #1A2031; border-radius: 4px;
}
.kos-booking-bar--dark .ts-wrapper .ts-dropdown::-webkit-scrollbar-thumb:hover {
    background: #0d1018;
}

/* ── Bottone ───────────────────────────────────────────────────────── */
.kos-booking-bar--dark .btn {
    transform: none !important;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease !important;
}
.kos-booking-bar--dark .btn:hover,
.kos-booking-bar--dark .btn:active,
.kos-booking-bar--dark .btn:focus { transform: none !important; }

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
.kos-booking-bar--dark .btn.disabled,
.kos-booking-bar--dark a.disabled {
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

/* ── Responsive ────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .kos-booking-bar--dark .row > div { margin-bottom: 1rem; }
}
</style>

<?php
return ob_get_clean();