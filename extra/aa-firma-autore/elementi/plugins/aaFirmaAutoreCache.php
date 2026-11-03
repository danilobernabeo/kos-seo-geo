<?php
/**
 * aaFirmaAutoreCache — anniazzurri.it (extra/aa-firma-autore)
 * Eventi: OnDocFormSave, OnDocPublished, OnDocUnPublished, OnResourceDelete, OnResourceUndelete
 *
 * Il JSON-LD (aaSchema), la firma e il box autore sono in cache con la pagina. Quando cambia una pagina
 * autore si svuota la cache dei soli articoli che la citano in aut.ref (nodo Person, firma, box); quando
 * cambia un articolo che ha la TV aut.ref si svuota la cache delle pagine autore (elenco «Articoli firmati»
 * e hasPart della ProfilePage). Utile soprattutto quando si salva senza «Svuota la cache» o da script:
 * con la casella spuntata MODX svuota già tutto.
 *
 * Template delle pagine autore: impostazione aa.tpl_profile (nomi separati da virgola), come aaSchema.
 */
$CLS_RES = \MODX\Revolution\modResource::class;
$CLS_TV  = \MODX\Revolution\modTemplateVar::class;
$CLS_TVR = \MODX\Revolution\modTemplateVarResource::class;
$CLS_TVT = \MODX\Revolution\modTemplateVarTemplate::class;
$CLS_TPL = \MODX\Revolution\modTemplate::class;

$res = $resource ?? null;
if (!$res instanceof $CLS_RES) {
    $id = (int) ($id ?? $docid ?? 0);
    $res = $id ? $modx->getObject($CLS_RES, $id) : null;
}
if (!$res) return;

$tvRef = $modx->getObject($CLS_TV, ['name' => 'aut.ref']);
if (!$tvRef) return;

$nomiProfilo = array_filter(array_map('trim', explode(',', (string) ($modx->getOption('aa.tpl_profile') ?: 'autore'))));
$tplProfilo = [];
foreach ($modx->getIterator($CLS_TPL, ['templatename:IN' => $nomiProfilo]) as $t) $tplProfilo[] = (int) $t->get('id');
if (!$tplProfilo) return;

$daSvuotare = [];
if (in_array((int) $res->get('template'), $tplProfilo, true)) {
    // Pagina autore: gli articoli che la firmano (anche non pubblicati: la firma può tornare con loro).
    foreach ($modx->getIterator($CLS_TVR, ['tmplvarid' => $tvRef->get('id'), 'value' => (string) $res->get('id')]) as $v) {
        $daSvuotare[] = (int) $v->get('contentid');
    }
} elseif ($modx->getCount($CLS_TVT, ['tmplvarid' => $tvRef->get('id'), 'templateid' => (int) $res->get('template')])) {
    // Articolo (template con aut.ref): tutte le pagine autore, anche quella da cui la firma è stata tolta.
    $q = $modx->newQuery($CLS_RES, ['template:IN' => $tplProfilo]);
    $q->select('id');
    foreach ($modx->getIterator($CLS_RES, $q) as $a) $daSvuotare[] = (int) $a->get('id');
} else {
    return;
}
$daSvuotare[] = (int) $res->get('id');

$n = 0;
foreach (array_unique($daSvuotare) as $rid) {
    // Contesto esplicito: senza argomento clearCache() usa il contesto corrente (mgr nel manager) e non trova la cache web.
    if ($r = $modx->getObject($CLS_RES, $rid)) { $r->clearCache((string) $r->get('context_key')); $n++; }
}
$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO,
    "[aaFirmaAutoreCache] {$modx->event->name} su {$res->get('id')}: cache svuotata per $n risorse");
return;
