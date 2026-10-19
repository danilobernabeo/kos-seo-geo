<?php
/**
 * newsByStruttura  —  anniazzurri.it
 *
 * Gira sulla PAGINA STRUTTURA. Restituisce la clausola &where JSON
 * per getResources: solo le news la cui TV idStruttura contiene
 * l'ID della struttura corrente.
 *
 * NB: qui NON si applica l'esclusione soloStruttura. È il blocco
 *     che dà visibilità proprio a quelle news.
 *
 * Property opzionali:
 *   &id      ID struttura         (default: risorsa corrente)
 *   &tvName  nome della TV        (default: idStruttura)
 *   &field   colonna da filtrare  (default: modResource.id)
 */

$tvName = trim((string)$modx->getOption('tvName', $scriptProperties, 'idStruttura'));
$field  = trim((string)$modx->getOption('field',  $scriptProperties, 'modResource.id'));

$id = (int)$modx->getOption('id', $scriptProperties, 0);
if ($id <= 0 && $modx->resource instanceof modResource) {
    $id = (int)$modx->resource->get('id');
}
if ($id <= 0) {
    return json_encode([$field . ':IN' => [0]]);
}

$prefix = $modx->getOption('table_prefix');

/* Normalizzo il valore della TV prima del confronto:
 * - tolgo gli spazi
 * - converto eventuali virgole in ||  (tollera dati legacy)
 * - incapsulo tra || in modo che il match sia sempre su token interi
 * Così l'ID 5 non matcha mai 15, 25 o 51.
 */
$needle = '%||' . $id . '||%';

$sql = "SELECT tvr.contentid
        FROM {$prefix}site_tmplvar_contentvalues tvr
        JOIN {$prefix}site_tmplvars tv ON tv.id = tvr.tmplvarid
        WHERE tv.name = " . $modx->quote($tvName) . "
          AND CONCAT('||', REPLACE(REPLACE(tvr.value, ' ', ''), ',', '||'), '||')
              LIKE " . $modx->quote($needle);

$ids  = [];
$stmt = $modx->query($sql);
if ($stmt instanceof PDOStatement) {
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN, 0) as $rid) {
        $ids[] = (int)$rid;
    }
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, '[newsByStruttura] query fallita per struttura ' . $id);
}

// Fail-closed: nessuna news associata => nessun risultato,
// e con &wrapIfEmpty=0 la sezione non viene emessa affatto.
if (empty($ids)) {
    return json_encode([$field . ':IN' => [0]]);
}

return json_encode([$field . ':IN' => array_values(array_unique($ids))]);