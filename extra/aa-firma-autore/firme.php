<?php
/**
 * Firma degli approfondimenti: compila la TV aut.ref dagli esiti di decisioni.csv (lavoro extra aa-firma-autore).
 *
 * Uso (in SSH sul cloud, dalla cartella estratta dall'archivio):
 *   php firme.php /www                         prova: elenca cosa cambierebbe, non scrive
 *   php firme.php /www --scrivi                applica
 *   php firme.php /www --uri=<uri> [--scrivi]  una sola riga del CSV (es. un articolo passato da «da correggere» a «firma»)
 *   php firme.php /www --ripristina ~/backup-kos-seo-geo/<data>-firme/ripristino.json [--scrivi]
 *
 * Regole:
 *   - righe con decisione = firma → aut.ref = id della pagina autore (/autori/antonella-schiavino);
 *     le altre righe non si toccano: se hanno già una firma la prova lo segnala, non la toglie;
 *   - l'articolo si cerca per uri (chiave autoritativa del CSV) e deve avere il template con la TV aut.ref;
 *   - si scrive solo il valore della TV (tabella site_tmplvar_contentvalues): niente salvataggio della risorsa,
 *     quindi editedon (dateModified) resta la data dell'ultima modifica del contenuto;
 *   - prima di scrivere: tabella di backup <prefisso>site_tmplvar_contentvalues_bak_AAAAMMGG (regola del repository)
 *     e manifest di ripristino con i valori precedenti riga per riga;
 *   - alla fine si svuota la cache di articoli firmati e pagina autore (JSON-LD, firma e box sono in cache).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }

$DIR = __DIR__;
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$iRip = array_search('--ripristina', $argv, true);
$fileRip = $iRip !== false ? ($argv[$iRip + 1] ?? '') : null;
$soloUri = null;
foreach ($argv as $a) if (str_starts_with($a, '--uri=')) $soloUri = trim(substr($a, 6), '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) {
    fwrite(STDERR, "Uso: php firme.php /www [--uri=<uri>] [--scrivi] | --ripristina <file> [--scrivi]\n");
    exit(1);
}
$J = json_decode((string) file_get_contents("$DIR/autore-antonella-schiavino.json"), true);
$URI_AUTORE = (string) ($J['risorsa']['uri'] ?? '');
if ($URI_AUTORE === '') { fwrite(STDERR, "uri della pagina autore mancante nel JSON\n"); exit(1); }

require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarTemplate, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

$tvRef = $modx->getObject(modTemplateVar::class, ['name' => 'aut.ref']);
if (!$tvRef) { fwrite(STDERR, "TV aut.ref non installata: prima installa.php\n"); exit(1); }
$TV = (int) $tvRef->get('id');
$valore = function (int $id) use ($modx, $TV): ?string {
    $v = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $TV, 'contentid' => $id]);
    return $v ? (string) $v->get('value') : null;
};
/** Scrive (o toglie, con null) il valore della TV senza salvare la risorsa: editedon non cambia. */
$imposta = function (int $id, ?string $nuovo) use ($modx, $TV): void {
    $v = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $TV, 'contentid' => $id]);
    if ($nuovo === null || $nuovo === '') { if ($v) $v->remove(); return; }
    if (!$v) {
        $v = $modx->newObject(modTemplateVarResource::class);
        $v->fromArray(['tmplvarid' => $TV, 'contentid' => $id], '', true, true);
    }
    $v->set('value', $nuovo);
    $v->save();
};
$svuota = function (array $ids) use ($modx): void {
    // Contesto esplicito: da riga di comando il contesto corrente è mgr e clearCache() senza argomento non trova la cache web.
    foreach (array_unique($ids) as $id) if ($r = $modx->getObject(modResource::class, (int) $id)) $r->clearCache((string) $r->get('context_key'));
};

/* ---------- ripristino ---------- */

if ($fileRip !== null) {
    $r = json_decode((string) @file_get_contents($fileRip), true);
    if (!is_array($r) || !isset($r['valori'])) { fwrite(STDERR, "Manifest di ripristino non leggibile: $fileRip\n"); exit(1); }
    echo ($scrivi ? "== RIPRISTINO" : "== PROVA DI RIPRISTINO (aggiungere --scrivi)") . " da $fileRip\n";
    foreach ($r['valori'] as $id => $vecchio) {
        $ora = $valore((int) $id);
        echo "  ~ $id: aut.ref " . var_export($ora, true) . ' → ' . var_export($vecchio, true) . "\n";
        if ($scrivi) $imposta((int) $id, $vecchio);
    }
    if ($scrivi) { $svuota(array_merge(array_keys($r['valori']), $r['autori'] ?? [])); echo "== Cache svuotata.\n"; }
    exit(0);
}

/* ---------- piano ---------- */

echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — firme degli approfondimenti"
    . ($soloUri !== null ? " (solo $soloUri)" : '') . "\n";

$autore = $modx->getObject(modResource::class, ['uri' => $URI_AUTORE, 'context_key' => 'web', 'deleted' => 0]);
if (!$autore) { fwrite(STDERR, "Pagina autore /$URI_AUTORE non trovata: prima installa.php\n"); exit(1); }
$ID_AUTORE = (string) $autore->get('id');
echo "  pagina autore $ID_AUTORE /$URI_AUTORE" . ($autore->get('published') ? '' : ' (NON pubblicata: le firme non avranno effetto finché non si pubblica)') . "\n";
$templateConTv = [];
foreach ($modx->getIterator(modTemplateVarTemplate::class, ['tmplvarid' => $TV]) as $l) $templateConTv[] = (int) $l->get('templateid');

$fh = fopen("$DIR/decisioni.csv", 'r');
$intest = fgetcsv($fh, 0, ',', '"', '');
$righe = [];
while (($x = fgetcsv($fh, 0, ',', '"', '')) !== false) if (count($x) === count($intest)) $righe[] = array_combine($intest, $x);
fclose($fh);
$conta = array_count_values(array_column($righe, 'decisione'));
echo '  decisioni.csv: ' . count($righe) . ' righe (' . implode(', ', array_map(fn($k, $v) => "$k $v", array_keys($conta), $conta)) . ")\n";
if ($soloUri !== null) {
    $righe = array_values(array_filter($righe, fn($x) => trim($x['uri'], '/') === $soloUri));
    if (!$righe) { fwrite(STDERR, "uri $soloUri non presente in decisioni.csv\n"); exit(1); }
}

$errori = 0; $daScrivere = []; $avvisi = 0; $uguali = 0;
foreach ($righe as $x) {
    $uri = trim($x['uri'], '/');
    $r = $modx->getObject(modResource::class, ['uri' => $uri, 'context_key' => 'web', 'deleted' => 0])
      ?: $modx->getObject(modResource::class, ['uri' => "$uri/", 'context_key' => 'web', 'deleted' => 0]);
    $firma = $x['decisione'] === 'firma';
    if (!$r) {
        if ($firma) { echo "  ! $uri: risorsa non trovata — FERMO\n"; $errori++; }
        continue;
    }
    $id = (int) $r->get('id');
    $ora = $valore($id);
    if (!$firma) {
        if ($ora !== null && $ora !== '') { echo "  ? $id /$uri ({$x['decisione']}) ha già aut.ref = $ora: non la tocco, da verificare\n"; $avvisi++; }
        continue;
    }
    if (!in_array((int) $r->get('template'), $templateConTv, true)) { echo "  ! $id /$uri: template {$r->get('template')} senza la TV aut.ref — FERMO\n"; $errori++; continue; }
    if ($ora === $ID_AUTORE) { $uguali++; continue; }
    if ($ora !== null && $ora !== '') { echo "  ! $id /$uri: aut.ref vale già $ora (altro autore) — FERMO\n"; $errori++; continue; }
    $daScrivere[$id] = [$uri, $ora, (bool) $r->get('published')];
    printf("  + %d /%s%s\n", $id, $uri, $r->get('published') ? '' : ' (non pubblicato)');
}
echo "\n  da firmare " . count($daScrivere) . ", già firmati $uguali, avvisi $avvisi\n";

if ($errori) { echo "\n== $errori problemi: niente è stato scritto.\n"; exit(2); }
if (!$daScrivere) { echo "\n== Niente da scrivere.\n"; exit(0); }
if (!$scrivi) { echo "\n== Prova riuscita. Rilanciare con --scrivi.\n"; exit(0); }

/* ---------- scrittura ---------- */

$stamp = date('Ymd-His');
$bk = rtrim((string) (getenv('HOME') ?: $DIR), '/') . "/backup-kos-seo-geo/$stamp-firme";
mkdir($bk, 0775, true);

// Tabella di backup completa (regola del repository per gli UPDATE massivi).
$tab = trim($modx->getTableName(modTemplateVarResource::class), '`');
$bakTab = $tab . '_bak_' . date('Ymd');
if ($modx->query("SHOW TABLES LIKE " . $modx->quote($bakTab))->fetch()) $bakTab .= '_' . date('His');
if ($modx->exec("CREATE TABLE `$bakTab` AS SELECT * FROM `$tab`") === false) { echo "  ! tabella di backup $bakTab non creata — FERMO\n"; exit(2); }
echo "  = tabella di backup $bakTab (" . $modx->query("SELECT COUNT(*) FROM `$bakTab`")->fetchColumn() . " righe)\n";

$rip = ['nome' => "firme $stamp", 'tabella_backup' => $bakTab, 'autori' => [(int) $ID_AUTORE], 'valori' => []];
foreach ($daScrivere as $id => [$uri, $ora]) $rip['valori'][$id] = $ora;
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$prima = [];
foreach (array_keys($daScrivere) as $id) $prima[$id] = (string) $modx->getObject(modResource::class, $id)->get('editedon');
foreach ($daScrivere as $id => [$uri]) $imposta($id, $ID_AUTORE);
$svuota(array_merge(array_keys($daScrivere), [(int) $ID_AUTORE]));

// Controllo: valore scritto ed editedon invariato.
$ok = 0;
foreach (array_keys($daScrivere) as $id) {
    $r = $modx->getObject(modResource::class, $id);
    if ($valore($id) === $ID_AUTORE && (string) $r->get('editedon') === $prima[$id]) $ok++;
    else { echo "  ! $id: controllo fallito (aut.ref " . var_export($valore($id), true) . ", editedon {$prima[$id]} → {$r->get('editedon')})\n"; $errori++; }
}
echo "== Firmati $ok articoli su " . count($daScrivere) . ", editedon invariato; cache di articoli e pagina autore svuotata.\n";
echo "== Ripristino: php firme.php /www --ripristina $bk/ripristino.json --scrivi (tabella di backup: $bakTab)\n";
exit($errori ? 2 : 0);
