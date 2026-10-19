<?php
/**
 * ecmPreparaDati — hook FormIt da inserire PRIMA di FormItSaveForm e email.
 *
 * Fa tre cose:
 *  1. riporta la data di nascita nel formato gg/mm/aaaa usato finora nelle
 *     estrazioni, visto che <input type="date"> invia aaaa-mm-gg;
 *  2. normalizza codice fiscale e partita IVA (maiuscole, spazi tolti);
 *  3. affianca agli id ECM la descrizione testuale, così la mail resta
 *     leggibile da una persona mentre l'estrazione continua a usare gli id.
 *
 * Le etichette vengono lette dai chunk delle opzioni: un'unica fonte,
 * niente tabella duplicata dentro al codice.
 */

$valori = $hook->getValues();

/* --- 1. data di nascita ------------------------------------------------ */
if (!empty($valori['birthdate']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valori['birthdate'], $m)) {
    $hook->setValue('birthdate', $m[3] . '/' . $m[2] . '/' . $m[1]);
    $hook->setValue('birthdate_iso', $valori['birthdate']); // comodo per l'export
}

/* --- 2. normalizzazioni ------------------------------------------------ */
foreach (array('fiscalcode', 'fcodice_fiscale') as $campo) {
    if (!empty($valori[$campo])) {
        $hook->setValue($campo, strtoupper(preg_replace('/\s+/', '', $valori[$campo])));
    }
}
foreach (array('fself_piva', 'fpartita_iva') as $campo) {
    if (!empty($valori[$campo])) {
        $hook->setValue($campo, preg_replace('/\D/', '', $valori[$campo]));
    }
}
if (!empty($valori['email'])) {
    $hook->setValue('email', strtolower(trim($valori['email'])));
}

/* --- 3. etichette ECM -------------------------------------------------- */
if (!function_exists('ecmEtichettaDaChunk')) {
    function ecmEtichettaDaChunk($modx, $nomeChunk, $id)
    {
        if ($id === '' || $id === null) return '';
        $chunk = $modx->getObject('modChunk', array('name' => $nomeChunk));
        if (!$chunk) {
            $modx->log(1 /* LOG_LEVEL_ERROR */, '[ecmPreparaDati] chunk mancante: ' . $nomeChunk);
            return '';
        }
        $sorgente = $chunk->getContent();
        $regex = '/<option value="' . preg_quote((string) $id, '/') . '"[^>]*>(.*?)<\/option>/s';
        if (preg_match($regex, $sorgente, $m)) {
            return html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8');
        }
        return '';
    }
}

$profId = isset($valori['ecm_prof_id']) ? $valori['ecm_prof_id'] : '';
$discId = isset($valori['ecm_disc_id']) ? $valori['ecm_disc_id'] : '';

$hook->setValue('ecm_prof_label', ecmEtichettaDaChunk($modx, 'ecmOptProfessioni', $profId));
$hook->setValue('ecm_disc_label', ecmEtichettaDaChunk($modx, 'ecmOptSpecializzazioni', $discId));

$statiOccupazionali = array(
    'D' => 'Dipendente',
    'L' => 'Libero professionista',
    'C' => 'Convenzionato',
    'P' => 'Privo di occupazione',
);
$st = isset($valori['status']) ? $valori['status'] : '';
$hook->setValue('status_label', isset($statiOccupazionali[$st]) ? $statiOccupazionali[$st] : '');

/* Nome del file allegato, utile nella mail e nel salvataggio. */
if (!empty($_FILES['allegato']['name'])) {
    $hook->setValue('allegato_nome', $_FILES['allegato']['name']);
}

/* --- 4. oggetto della notifica ----------------------------------------
   FormIt viene chiamato con emailUseFieldForSubject=1, quindi l'oggetto
   è il campo "subject" che impostiamo qui. Comporlo nell'hook, dove i
   valori inviati sono già noti, evita di far passare placeholder dentro
   la proprietà emailSubject.
---------------------------------------------------------------------- */
$titoloEvento = trim((string) $modx->getPlaceholder('ecmNomeEvento'));
if ($titoloEvento === '' && $modx->resource) {
    $titoloEvento = trim((string) $modx->resource->getTVValue('evento_titolo'));
    if ($titoloEvento === '') {
        $titoloEvento = (string) $modx->resource->get('pagetitle');
    }
}
$persona = trim(
    (isset($valori['surname']) ? $valori['surname'] : '') . ' ' .
    (isset($valori['name']) ? $valori['name'] : '')
);
$hook->setValue('subject', trim('Iscrizione: ' . $titoloEvento . ' - ' . $persona, ' -'));

return true;