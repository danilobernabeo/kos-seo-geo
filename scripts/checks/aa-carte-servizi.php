<?php
/**
 * AA sprint 3 (tipo delle residenze) — SOLA LETTURA: raccoglie le carte dei servizi delle strutture di anniazzurri.it
 * per verificare l'accreditamento (RSA accreditata / a contratto / solo autorizzata).
 *
 * Cerca in <webroot>/assets/doc/strutture/<alias>/ i PDF con «carta» nel nome, abbina la cartella alla pagina
 * pubblicata con lo stesso alias e crea un archivio con le sole carte da scaricare:
 *   ~/aa-carte-servizi-<AAAAMMGG>.tar   (PDF, con il percorso <alias>/<file>)
 *   ~/aa-carte-servizi-<AAAAMMGG>.csv   (alias, pagina, pubblicata, file, dimensione)
 * Se sul cloud c'è pdftotext, aggiunge al CSV le frasi su accreditamento e contratto.
 *
 * Uso: php scripts/checks/aa-carte-servizi.php /www
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php aa-carte-servizi.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('web');

$base = "$webroot/assets/doc/strutture";
if (!is_dir($base)) { fwrite(STDERR, "Cartella $base assente\n"); exit(2); }
$pdftotext = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
$oggi = date('Ymd');
$home = rtrim((string) getenv('HOME'), '/');
$csv = fopen("$home/aa-carte-servizi-$oggi.csv", 'w');
fwrite($csv, "\xEF\xBB\xBF");
fputcsv($csv, ['alias', 'pagina', 'pubblicata', 'file', 'KB', 'frasi (solo con pdftotext)'], ';');

$lista = []; $tot = 0; $senza = [];
foreach (glob("$base/*", GLOB_ONLYDIR) as $dir) {
    $alias = basename($dir);
    $res = $modx->getObject(modResource::class, ['alias' => $alias, 'deleted' => 0]);
    $carte = array_values(array_filter(glob("$dir/*.pdf") ?: [], fn($f) => stripos(basename($f), 'carta') !== false));
    if (!$carte) { $senza[] = $alias; continue; }
    foreach ($carte as $f) {
        $kb = (int) round(filesize($f) / 1024); $tot += $kb;
        $frasi = '';
        if ($pdftotext !== '') {
            $t = (string) shell_exec(escapeshellarg($pdftotext) . ' -layout ' . escapeshellarg($f) . ' - 2>/dev/null');
            $t = preg_replace('/\s+/u', ' ', $t);
            preg_match_all('/[^.]{0,160}(accreditat|a contratto|contrattualizzat|convenzionat|autorizzat)[^.]{0,160}/iu', $t, $m);
            $frasi = implode(' || ', array_slice(array_unique($m[0]), 0, 6));
        }
        fputcsv($csv, [$alias, $res ? $res->get('uri') : '(nessuna pagina)', $res ? (int) $res->get('published') : '', basename($f), $kb, $frasi], ';');
        $lista[] = "$alias/" . basename($f);
    }
}
fclose($csv);
$tar = "$home/aa-carte-servizi-$oggi.tar";
@unlink($tar);
$elenco = tempnam(sys_get_temp_dir(), 'carte');
file_put_contents($elenco, implode("\n", $lista) . "\n");
shell_exec('tar cf ' . escapeshellarg($tar) . ' -C ' . escapeshellarg($base) . ' -T ' . escapeshellarg($elenco) . ' 2>&1');
unlink($elenco);
echo "== Carte dei servizi: " . count($lista) . " PDF, " . round($tot / 1024, 1) . " MB\n";
echo "== Cartelle senza carta: " . count($senza) . ($senza ? ' (' . implode(', ', array_slice($senza, 0, 15)) . (count($senza) > 15 ? ', …' : '') . ')' : '') . "\n";
echo "== pdftotext sul cloud: " . ($pdftotext ?: 'assente (frasi da estrarre in locale)') . "\n";
echo "== Scritti: ~/aa-carte-servizi-$oggi.csv e ~/aa-carte-servizi-$oggi.tar (" . round((int) @filesize($tar) / 1048576, 1) . " MB)\n";
