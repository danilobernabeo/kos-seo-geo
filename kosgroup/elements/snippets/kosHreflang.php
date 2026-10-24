<?php
/**
 * kosHreflang — link hreflang tra la pagina italiana e la sua traduzione inglese (kosgroup.com, consolidamento head).
 * Chiamata in cache dal chunk head: [[kosHreflang? &lingua=`it`]]  (head-en: &lingua=`en`)
 *
 * Regola: i link si scrivono solo se la traduzione esiste davvero ed è pubblicata.
 *   - traduzione = TV &tv (default translationId) della pagina; se vuota, la pagina dell'altra lingua che punta a questa
 *     con la stessa TV (relazione dichiarata da un solo lato, come oggi sulle pagine inglesi);
 *   - home: 1 ↔ 990 anche senza TV (&homeIt, &homeEn);
 *   - x-default = versione italiana.
 * Prima: tutte le pagine italiane dichiaravano come traduzione la home inglese (990), e le inglesi senza traduzione
 * un hreflang «it» con href vuoto.
 */
$tvName = (string) $modx->getOption('tv', $scriptProperties, 'translationId');
$lingua = $modx->getOption('lingua', $scriptProperties, 'it') === 'en' ? 'en' : 'it';
$homeIt = (int) $modx->getOption('homeIt', $scriptProperties, 1);
$homeEn = (int) $modx->getOption('homeEn', $scriptProperties, 990);
$res = $modx->resource;
if (!$res) return '';
$id = (int) $res->get('id');

$pubblicata = function (int $rid) use ($modx): bool {
    if ($rid <= 0) return false;
    $r = $modx->getObject(\MODX\Revolution\modResource::class, $rid);
    return $r && $r->get('published') && !$r->get('deleted');
};

$altra = (int) preg_replace('/\D/', '', (string) $res->getTVValue($tvName));
if (!$pubblicata($altra)) {
    $altra = 0;
    $tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => $tvName]);
    if ($tv) {
        $q = $modx->newQuery(\MODX\Revolution\modTemplateVarResource::class, ['tmplvarid' => $tv->get('id'), 'value' => (string) $id]);
        $q->select('contentid');
        foreach ($modx->getIterator(\MODX\Revolution\modTemplateVarResource::class, $q) as $v) {
            if ($pubblicata((int) $v->get('contentid'))) { $altra = (int) $v->get('contentid'); break; }
        }
    }
}
if (!$altra && $lingua === 'it' && $id === $homeIt && $pubblicata($homeEn)) $altra = $homeEn;
if (!$altra && $lingua === 'en' && $id === $homeEn && $pubblicata($homeIt)) $altra = $homeIt;
if (!$altra) return '';

$it = $lingua === 'it' ? $id : $altra;
$en = $lingua === 'en' ? $id : $altra;
$url = fn(int $rid) => htmlspecialchars($modx->makeUrl($rid, '', '', 'full'), ENT_QUOTES, 'UTF-8');
return '<link rel="alternate" hreflang="it" href="' . $url($it) . '" />' . "\n  "
     . '<link rel="alternate" hreflang="en" href="' . $url($en) . '" />' . "\n  "
     . '<link rel="alternate" hreflang="x-default" href="' . $url($it) . '" />';
