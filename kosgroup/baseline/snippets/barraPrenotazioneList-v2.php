<?php
/**
 * LISTA PRENOTAZIONE CON ACCORDION - Singola branca, raggruppata per distretto
 * Snippet MODX 3.1
 *
 * PARAMETRI SNIPPET (con valori di default):
 * ------------------------------------------
 *  &branca       Valore esatto del campo `branca` in kos_branche   (default: 'TAC')
 *  &portalUrl    URL base portale TuoTempo                          (default: 'https://app.tuotempo.com/mop/index.php')
 *  &labelButton  Testo del bottone per ogni riga                    (default: 'Prenota →')
 *
 * ESEMPIO DI CHIAMATA IN MODX (Cardinal Ferrari – TAC):
 * [[!barraPrenotazioneList? &branca=`TAC`]]
 *
 * NOTE:
 *  - areaid viene sempre letto dalla TV `istanza` della pagina corrente.
 *  - dbName è fisso: tt_kosgroup_portale
 *  - resourceid viene letto dal DB per ciascuna prestazione (kos_medici
 *    via kos_medici_prestazioni). Se una prestazione ha più risorse associate
 *    viene usata la prima (MIN su resourceid).
 *  - kos_referrer viene appeso in JS (window.location.pathname).
 *  - GTM dataLayer push incluso al click di ogni bottone.
 *  - Le prestazioni vengono raggruppate automaticamente per distretto corporeo
 *    e presentate in formato accordion.
 */

// ── Parametri snippet con fallback ───────────────────────────────────────────
$brancaFiltro = $modx->getOption('branca',      $scriptProperties, 'TAC');
$portalUrl    = $modx->getOption('portalUrl',   $scriptProperties, 'https://app.tuotempo.com/mop/index.php');
$labelButton  = $modx->getOption('labelButton', $scriptProperties, 'Prenota →');

// ── Variabili fisse ───────────────────────────────────────────────────────────
$dbName = 'tt_kosgroup_portale'; // fisso per tutti i centri KOS

// ── Dati dalla pagina corrente ────────────────────────────────────────────────
$areaid     = $modx->resource->getTVValue('istanza');
$centroNome = $modx->resource->get('introtext');

if (empty($areaid)) {
    return '<div class="alert alert-warning">Struttura non configurata (TV istanza mancante).</div>';
}

// ── Query: prestazioni della branca + resourceid dal DB ──────────────────────
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
    $modx->log(modX::LOG_LEVEL_ERROR, '[barraPrenotazioneList] DB error: ' . $e->getMessage());
    return '<div class="alert alert-danger">Errore nel caricamento delle prestazioni.</div>';
}

// ── Funzione: estrai distretto dal titolo ────────────────────────────────────
function extractDistretto($title) {
    // Distretti noti, ordinati dal più lungo al più corto per match corretto
    $distretti = [
        'BACINO E ARTICOLAZIONI SACRO-ILIACHE',
        'FEMORE E ARTIC.COXO-FEMORALE',
        'GOMITO E AVAMBRACCIO',
        'GINOCCHIO E GAMBA',
        'CAVIGLIA E PIEDE',
        'SPALLA E BRACCIO',
        'CRANIO/ENCEFALO',
        'POLSO E MANO',
        'RACHIDE',
        'ADDOME',
        'TORACE',
    ];

    // Normalizza: rimuovi prefisso TC/TAC
    $normalized = preg_replace('/^(TC|TAC)\s+/i', '', trim($title));

    // Cerca il distretto nel titolo normalizzato
    foreach ($distretti as $d) {
        if (stripos($normalized, $d) === 0) {
            return $d;
        }
    }

    // Fallback: ADDOME per varianti come "ADDOME SUP.", "ADDOME SUPERIORE"
    if (stripos($normalized, 'ADDOME') !== false) {
        return 'ADDOME';
    }

    // Fallback finale: usa il titolo intero come gruppo
    return strtoupper($normalized);
}

// ── Funzione: label leggibile del distretto ──────────────────────────────────
function formatDistretto($distretto) {
    $labels = [
        'ADDOME'                                => 'Addome',
        'BACINO E ARTICOLAZIONI SACRO-ILIACHE'  => 'Bacino e Articolazioni Sacro-Iliache',
        'CAVIGLIA E PIEDE'                      => 'Caviglia e Piede',
        'CRANIO/ENCEFALO'                       => 'Cranio / Encefalo',
        'FEMORE E ARTIC.COXO-FEMORALE'         => 'Femore e Articolazione Coxo-Femorale',
        'GINOCCHIO E GAMBA'                     => 'Ginocchio e Gamba',
        'GOMITO E AVAMBRACCIO'                  => 'Gomito e Avambraccio',
        'POLSO E MANO'                          => 'Polso e Mano',
        'RACHIDE'                               => 'Rachide (Colonna Vertebrale)',
        'SPALLA E BRACCIO'                      => 'Spalla e Braccio',
        'TORACE'                                => 'Torace',
    ];

    return $labels[$distretto] ?? ucwords(strtolower($distretto));
}

// ── Funzione: titolo leggibile (rimuove solo prefisso TC/TAC) ────────────────
function cleanTitle($title) {
    $clean = preg_replace('/^(TC|TAC)\s+/i', '', trim($title));
    return strtoupper(trim($clean));
}

// ── Raggruppa per distretto ──────────────────────────────────────────────────
$gruppi = [];
foreach ($prestazioni as $p) {
    $distretto = extractDistretto($p['title']);
    
    $url = $portalUrl
         . '?dbName='     . urlencode($dbName)
         . '&activityid=' . urlencode($p['activity_id'])
         . '&areaid='     . urlencode($areaid)
         . '&resourceid=' . urlencode($p['resourceid']);

    $gruppi[$distretto][] = [
        'title'      => ucwords(strtolower($p['title'])),
        'clean'      => cleanTitle($p['title']),
        'url'        => $url,
        'activityid' => htmlspecialchars($p['activity_id']),
        'resourceid' => htmlspecialchars($p['resourceid']),
    ];
}

// Ordina i gruppi alfabeticamente
ksort($gruppi);

// ── Variabili di output ───────────────────────────────────────────────────────
$labelButtonEsc = htmlspecialchars($labelButton);
$areaIdEsc      = htmlspecialchars($areaid);
$brancaEsc      = htmlspecialchars($brancaFiltro);
$centroNomeEsc  = htmlspecialchars($centroNome);
$totalePrestazioni = count($prestazioni);
$totaleGruppi      = count($gruppi);

ob_start();
?>

<div class="kos-accordion" data-branca="<?= $brancaEsc ?>">

    <?php $idx = 0; foreach ($gruppi as $distretto => $items): 
        $distrettoLabel = formatDistretto($distretto);
        $distrettoSlug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $distretto));
        $countItems     = count($items);
        $isSingle       = ($countItems === 1);
    ?>

    <div class="kos-accordion__group" id="gruppo-<?= $distrettoSlug ?>">

        <button class="kos-accordion__header" 
                aria-expanded="false" 
                aria-controls="panel-<?= $distrettoSlug ?>"
                data-gruppo="<?= $distrettoSlug ?>">
            <span class="kos-accordion__title"><?= htmlspecialchars($distrettoLabel) ?></span>
            <span class="kos-accordion__meta"><?= $countItems ?> prestazion<?= $countItems === 1 ? 'e' : 'i' ?></span>
            <svg class="kos-accordion__chevron" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div class="kos-accordion__panel" id="panel-<?= $distrettoSlug ?>" role="region">
            <?php foreach ($items as $item): ?>
            <div class="kos-accordion__item">
                <span class="kos-accordion__item-name"><?= $item['clean'] ?></span>
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   class="btn btn-primary kos-accordion__cta"
                   data-activityid="<?= $item['activityid'] ?>"
                   data-resourceid="<?= $item['resourceid'] ?>"
                   data-areaid="<?= $areaIdEsc ?>"
                   data-branca="<?= $brancaEsc ?>"
                   data-distretto="<?= htmlspecialchars($distretto) ?>"
                   data-prestazione="<?= htmlspecialchars(strtolower($item['title'])) ?>"
                   title="Prenota <?= $item['title'] ?> al <?= $centroNomeEsc ?>"
                   onclick="kosListTrack(this)">
                    <?= $labelButtonEsc ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <?php $idx++; endforeach; ?>

</div><!-- /kos-accordion -->

<!-- ── CSS Accordion ─────────────────────────────────────────────────────── -->
<style>
.kos-accordion {
    max-width: 900px;
    margin: 0 auto;
}

/* Header del gruppo */
.kos-accordion__header {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 16px 20px;
    margin: 0;
    background: #f5f7fa;
    border: 1px solid #e0e4e8;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    font-family: inherit;
    margin-bottom: 4px;
}

.kos-accordion__header:hover {
    background: #edf0f5;
    border-color: #1a5a7a;
}

.kos-accordion__header[aria-expanded="true"] {
    background: #1a5a7a;
    color: white;
    border-color: #1a5a7a;
    border-radius: 8px 8px 0 0;
    margin-bottom: 0;
}

.kos-accordion__title {
    font-size: 16px;
    font-weight: 600;
    flex: 1;
}

.kos-accordion__meta {
    font-size: 13px;
    opacity: 0.7;
    margin-right: 12px;
}

.kos-accordion__chevron {
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.kos-accordion__header[aria-expanded="true"] .kos-accordion__chevron {
    transform: rotate(180deg);
}

/* Pannello contenuto */
.kos-accordion__panel {
    display: none;
    border: 1px solid #e0e4e8;
    border-top: none;
    border-radius: 0 0 8px 8px;
    overflow: hidden;
    margin-bottom: 4px;
}

.kos-accordion__panel.is-open {
    display: block;
}

/* Singola prestazione nel pannello */
.kos-accordion__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #d0d4d8;
}

.kos-accordion__item:last-child {
    border-bottom: none;
}

.kos-accordion__item:hover {
    background: #fafbfc;
}

.kos-accordion__item-name {
    font-size: 15px;
    color: #333;
    flex: 1;
    padding-right: 16px;
}

/* Bottone CTA */
.kos-accordion__cta {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 18px;
    background: #c9a227;
    color: white;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 6px;
    white-space: nowrap;
    transition: background 0.2s;
}

.kos-accordion__cta:hover {
    background: #b8911f;
    color: white;
    text-decoration: none;
}

/* ── Responsive ───────────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .kos-accordion__header {
        padding: 14px 16px;
    }

    .kos-accordion__meta {
        display: none;
    }

    .kos-accordion__item {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px 16px;
    }

    .kos-accordion__item-name {
        padding-right: 0;
    }

    .kos-accordion__cta {
        text-align: center;
        justify-content: center;
    }
}
</style>

<!-- ── JS Accordion + Tracking ───────────────────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Accordion toggle ──────────────────────────────────────────────────
    document.querySelectorAll('.kos-accordion__header').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            var panelId  = this.getAttribute('aria-controls');
            var panel    = document.getElementById(panelId);

            // Chiudi tutti gli altri
            document.querySelectorAll('.kos-accordion__header').forEach(function (otherBtn) {
                if (otherBtn !== btn) {
                    otherBtn.setAttribute('aria-expanded', 'false');
                    var otherId = otherBtn.getAttribute('aria-controls');
                    var otherPanel = document.getElementById(otherId);
                    if (otherPanel) otherPanel.classList.remove('is-open');
                }
            });

            // Toggle corrente
            this.setAttribute('aria-expanded', !expanded);
            panel.classList.toggle('is-open', !expanded);
        });
    });

    // ── Append kos_referrer a tutti i link ─────────────────────────────────
    document.querySelectorAll('.kos-accordion__cta').forEach(function (a) {
        a.href += '&kos_referrer=' + encodeURIComponent(window.location.pathname);
    });

});

// ── GTM Tracking ──────────────────────────────────────────────────────────
function kosListTrack(el) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event          : 'booking_start',
        source_type    : 'list_struttura',
        branca         : el.dataset.branca,
        distretto      : el.dataset.distretto,
        referrer_page  : window.location.pathname,
        centro_areaid  : el.dataset.areaid,
        prestazione_id : el.dataset.activityid,
        medico_id      : el.dataset.resourceid
    });
}
</script>

<?php
return ob_get_clean();