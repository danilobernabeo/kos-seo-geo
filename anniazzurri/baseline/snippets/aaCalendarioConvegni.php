<?php
/**
 * aaCalendarioConvegni — calendario dei convegni in programma (pagina /convegni-e-webinar, lavoro extra).
 *
 *   [[!aaCalendarioConvegni]]      (non in cache: i convegni passati devono sparire da soli)
 *
 * Elenca le pagine pubblicate con la TV evento_data compilata e con data da oggi in poi (il giorno
 * dell'evento compreso), in ordine di data. Una card per evento, dal chunk &tpl, una per riga.
 * TV lette: evento_data (data), evento_luogo (testo), evento_immagine (immagine 16:9, es. 980x551);
 * sottotitolo dal riassunto (introtext) della pagina.
 *
 * &tpl    chunk della card (default aaCalendarioConvegniCard)
 * &limit  numero massimo di eventi (default 6)
 * &vuoto  testo se non ci sono convegni in programma
 *
 * Niente «use» in testa: MODX esegue lo snippet dentro una funzione.
 */
$tpl = $modx->getOption('tpl', $scriptProperties, 'aaCalendarioConvegniCard');
$limit = (int) $modx->getOption('limit', $scriptProperties, 6);
$vuoto = $modx->getOption('vuoto', $scriptProperties,
    'Al momento non ci sono convegni in programma. Lasciaci i tuoi dati qui sotto: ti avviseremo dei prossimi.');
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

/** valori grezzi di una TV per risorsa: [id risorsa => valore] */
$valori = function (string $nome) use ($modx): array {
    $tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => $nome]);
    if (!$tv) return [];
    $out = [];
    foreach ($modx->getIterator(\MODX\Revolution\modTemplateVarResource::class, ['tmplvarid' => $tv->get('id')]) as $r) {
        $out[(int) $r->get('contentid')] = trim((string) $r->get('value'));
    }
    return $out;
};

$oggi = date('Y-m-d');
$date = array_filter($valori('evento_data'), fn($v) => $v !== '' && substr($v, 0, 10) >= $oggi);
asort($date);                                                     // formato Y-m-d H:i:s: ordine alfabetico = cronologico
$luoghi = $valori('evento_luogo');
$immagini = $valori('evento_immagine');
$mesi = [1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto',
         'settembre', 'ottobre', 'novembre', 'dicembre'];
$conImmagine = (bool) $modx->getObject(\MODX\Revolution\modSnippet::class, ['name' => 'immagine']);
$schema = $modx->getOption('link_tag_scheme', null, 'abs');

$card = [];
foreach ($date as $id => $data) {
    if (count($card) >= $limit) break;
    $r = $modx->getObject(\MODX\Revolution\modResource::class, ['id' => $id, 'published' => 1, 'deleted' => 0]);
    if (!$r) continue;
    $t = strtotime($data);
    $src = $immagini[$id] ?? '';
    $img = '';
    if ($src !== '') {
        $img = $conImmagine
            ? $modx->runSnippet('immagine', ['src' => $src, 'alt' => 'Convegno ' . $r->get('pagetitle'),
                'class' => 'aa-cal-img rounded-3', 'sizes' => '(min-width: 992px) 300px, (min-width: 768px) 40vw, 100vw'])
            : '<img class="aa-cal-img rounded-3" src="' . $e('/' . ltrim($src, '/')) . '" alt="' . $e('Convegno ' . $r->get('pagetitle'))
              . '" width="980" height="551" loading="lazy">';
    }
    $card[] = $modx->getChunk($tpl, [
        'id'          => $id,
        'url'         => $modx->makeUrl($id, '', '', $schema),
        'pagetitle'   => $e($r->get('pagetitle')),
        'sottotitolo' => $e($r->get('introtext')),
        'data'        => date('j', $t) . ' ' . $mesi[(int) date('n', $t)] . ' ' . date('Y', $t),
        'data_iso'    => date('Y-m-d', $t),
        'luogo'       => $e($luoghi[$id] ?? ''),
        'immagine'    => $img,
    ]);
}

if (!$card) return '<p class="mb-4">' . $e($vuoto) . '</p>';
return '<style>.aa-calendario .aa-cal-img{display:block;width:100%;height:auto}</style>'
     . '<div class="row aa-calendario">' . implode("\n", $card) . '</div>';