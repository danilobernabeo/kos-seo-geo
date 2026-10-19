<?php
/**
 * LISTA PRENOTAZIONE - Singola branca, link diretto TuoTempo per ogni prestazione
 * Snippet MODX 3.1
 *
 * PARAMETRI SNIPPET (con valori di default):
 * ------------------------------------------
 *  &branca       Valore esatto del campo `branca` in kos_branche   (default: 'TAC')
 *  &portalUrl    URL base portale TuoTempo                          (default: 'https://app.tuotempo.com/mop/index.php')
 *  &labelButton  Testo del bottone per ogni riga                    (default: 'Prenota →')
 *
 * ESEMPIO DI CHIAMATA IN MODX (Cardinal Ferrari – TAC):
 * [[barraPrenotazioneList? &branca=`TAC`]]
 *
 * NOTE:
 *  - areaid viene sempre letto dalla TV `istanza` della pagina corrente.
 *  - dbName è fisso: tt_kosgroup_portale
 *  - resourceid viene letto dal DB per ciascuna prestazione (kos_medici
 *    via kos_medici_prestazioni). Se una prestazione ha più risorse associate
 *    viene usata la prima (MIN su resourceid).
 *  - kos_referrer viene appeso in JS (window.location.pathname).
 *  - GTM dataLayer push incluso al click di ogni bottone.
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

// ── Build items ───────────────────────────────────────────────────────────────
// kos_referrer NON viene aggiunto qui: viene appeso in JS con window.location.pathname
$items = [];
foreach ($prestazioni as $p) {
    $titolo = ucwords(strtolower($p['title']));
    $url    = $portalUrl
            . '?dbName='     . urlencode($dbName)
            . '&activityid=' . urlencode($p['activity_id'])
            . '&areaid='     . urlencode($areaid)
            . '&resourceid=' . urlencode($p['resourceid']);

    $items[] = [
        'title'      => $titolo,
        'url'        => $url,
        'activityid' => htmlspecialchars($p['activity_id']),
        'resourceid' => htmlspecialchars($p['resourceid']),
    ];
}

// ── Variabili di output ───────────────────────────────────────────────────────
$labelButtonEsc = htmlspecialchars($labelButton);
$areaIdEsc      = htmlspecialchars($areaid);
$brancaEsc      = htmlspecialchars($brancaFiltro);
$centroNomeEsc  = htmlspecialchars($centroNome);

ob_start();
?>

<div class="tac-list">

    <?php foreach ($items as $item): ?>
    <div class="tac-item">
        <span class="tac-item__name"><?= $item['title'] ?></span>
        <a href="<?= htmlspecialchars($item['url']) ?>"
           class="btn btn-primary tac-item__cta"
           data-activityid="<?= $item['activityid'] ?>"
           data-resourceid="<?= $item['resourceid'] ?>"
           data-areaid="<?= $areaIdEsc ?>"
           data-branca="<?= $brancaEsc ?>"
           data-prestazione="<?= htmlspecialchars(strtolower($item['title'])) ?>"
           title="Prenota <?= $item['title'] ?> al <?= $centroNomeEsc ?>"
           onclick="kosListTrack(this)">
            <?= $labelButtonEsc ?>
        </a>
    </div>
    <?php endforeach; ?>

</div><!-- /tac-list -->

<script>
// Appende kos_referrer a tutti i link della lista al caricamento pagina
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tac-list .tac-item__cta').forEach(function (a) {
        a.href += '&kos_referrer=' + encodeURIComponent(window.location.pathname);
    });
});

function kosListTrack(el) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event          : 'booking_start',
        source_type    : 'list_struttura',
        branca         : el.dataset.branca,
        referrer_page  : window.location.pathname,
        centro_areaid  : el.dataset.areaid,
        prestazione_id : el.dataset.activityid,
        medico_id      : el.dataset.resourceid
    });
}
</script>

<?php
return ob_get_clean();