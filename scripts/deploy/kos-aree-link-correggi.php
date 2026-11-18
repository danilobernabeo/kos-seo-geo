<?php
/**
 * kos-aree-link-correggi.php — sostituisce o toglie, nelle liste di id delle TV mapAree* delle schede kosgroup.com,
 * le voci che puntano a pagine cestinate (link vuoto verso la home; scansione del 18/11, diagnostica
 * scripts/checks/kos-aree-link.php). Tocca solo l'id indicato, lascia le altre voci e il loro ordine.
 *
 * Uso (in SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/kos-aree-link-correggi.php /www            # prova
 *   php scripts/deploy/kos-aree-link-correggi.php /www --scrivi   # applica, backup, svuota la cache
 *   php scripts/deploy/kos-aree-link-correggi.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-aree-link/ripristino.json (valori delle TV prima della modifica).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null;
foreach ($argv as $a) if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13);
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-aree-link-correggi.php /www [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

// scheda (uri) · TV · id da togliere · uri della pagina che lo sostituisce ('' = solo togliere)
$regole = [
    ['it/centri/centro-ambulatoriale-santo-stefano-filottrano', 'mapAreeRiabilitazione', 90, 'it/servizi-e-cure/ambulatori-e-medicina-specialistica/medicina-dello-sport'],
    ['it/centri/centro-ambulatoriale-santo-stefano-san-severino', 'mapAreeRiabilitazione', 90, 'it/servizi-e-cure/ambulatori-e-medicina-specialistica/medicina-dello-sport'],
    ['it/centri/ospedale-san-pancrazio', 'mapAreeRiabilitazione', 273, 'it/servizi-e-cure/psichiatria/dipendenze'],
    ['it/centri/istituto-santo-stefano', 'mapAreeRiabilitazione', 92, ''],
];

$risorsa = fn(string $uri) => $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0, 'published' => 1]);
$valore = function (int $tvId, int $rid) use ($modx): ?modTemplateVarResource {
    return $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tvId, 'contentid' => $rid]);
};

if ($ripFile !== null) {
    $rip = json_decode((string) file_get_contents($ripFile), true);
    foreach ($rip as $v) {
        $o = $valore((int) $v['tmplvarid'], (int) $v['contentid']);
        echo "  risorsa {$v['contentid']} TV {$v['tmplvarid']}: '" . ($o ? $o->get('value') : '') . "' → '{$v['value']}'\n";
        if ($scrivi && $o) { $o->set('value', $v['value']); $o->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo $scrivi ? "== Ripristinato\n" : "== PROVA: aggiungere --scrivi\n";
    exit(0);
}

echo $scrivi ? "== APPLICAZIONE\n" : "== PROVA (nessuna scrittura; aggiungere --scrivi)\n";
$fermo = 0; $daSalvare = []; $ripristino = [];
foreach ($regole as [$uriScheda, $nomeTv, $daId, $aUri]) {
    $s = $risorsa($uriScheda);
    $tv = $modx->getObject(modTemplateVar::class, ['name' => $nomeTv]);
    if (!$s || !$tv) { echo "  ! $uriScheda o TV $nomeTv non trovata — FERMO\n"; $fermo++; continue; }
    $o = $valore((int) $tv->get('id'), (int) $s->get('id'));
    $prima = $o ? (string) $o->get('value') : '';
    $sep = str_contains($prima, '||') ? '||' : ',';
    $ids = array_values(array_filter(array_map('trim', preg_split('/[,|]+/', $prima)), 'strlen'));
    if (!in_array((string) $daId, $ids, true)) { echo "  = $uriScheda: id $daId non c'è più in $nomeTv ('$prima'), niente da fare\n"; continue; }
    $nuovo = null;
    if ($aUri !== '') {
        $dest = $risorsa($aUri);
        if (!$dest) { echo "  ! $uriScheda: pagina di destinazione /$aUri non trovata o non pubblicata — FERMO\n"; $fermo++; continue; }
        $nuovo = (string) $dest->get('id');
    }
    $dopo = [];
    foreach ($ids as $i) {
        if ($i === (string) $daId) { if ($nuovo !== null && !in_array($nuovo, $dopo, true) && !in_array($nuovo, $ids, true)) $dopo[] = $nuovo; continue; }
        $dopo[] = $i;
    }
    $dopoStr = implode($sep, $dopo);
    echo "  {$s->get('id')} $uriScheda · $nomeTv: '$prima' → '$dopoStr'" . ($nuovo ? " (id $daId → $nuovo /$aUri)" : " (id $daId tolto)") . "\n";
    $ripristino[] = ['contentid' => (int) $s->get('id'), 'tmplvarid' => (int) $tv->get('id'), 'value' => $prima];
    $daSalvare[] = [$o, $dopoStr];
}
if ($fermo) { echo "== FERMO su $fermo voci: nessuna scrittura\n"; exit(2); }
if (!$scrivi) { echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
if (!$daSalvare) { echo "== Niente da scrivere\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-aree-link';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode($ripristino, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
foreach ($daSalvare as [$o, $v]) { $o->set('value', $v); $o->save(); }
$modx->cacheManager->refresh();
echo "== Fatto (" . count($daSalvare) . " TV). Ripristino: php scripts/deploy/kos-aree-link-correggi.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
