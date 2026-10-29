<?php
/**
 * AA sprint 3 (tipo delle residenze) — SOLA LETTURA: raccoglie le carte dei servizi delle strutture di anniazzurri.it
 * per verificare l'accreditamento (RSA accreditata / a contratto / solo autorizzata).
 *
 * Cerca in <webroot>/assets/doc/strutture/<alias>/ i PDF con «carta» nel nome, li abbina alla pagina con lo stesso
 * alias ed estrae il testo sul cloud (pdftotext se c'è, altrimenti la libreria PHP in scripts/vendor/pdfparser):
 *   ~/aa-carte-servizi-<AAAAMMGG>.csv     (alias, pagina, pubblicata, file, KB, caratteri estratti, frasi chiave)
 *   ~/aa-carte-testi-<AAAAMMGG>.tar.gz    (un .txt per carta: <alias>__<file>.txt, pochi MB)
 * Con --pdf crea anche ~/aa-carte-servizi-<AAAAMMGG>.tar con i PDF originali (pesante, ~200 MB).
 *
 * Uso: php scripts/checks/aa-carte-servizi.php /www [--pdf]
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
$conPdf = in_array('--pdf', $argv, true);
ini_set('memory_limit', '1536M');
set_time_limit(0);
spl_autoload_register(function ($c) {
    if (str_starts_with($c, 'Smalot\\PdfParser\\')) {
        $f = __DIR__ . '/../vendor/pdfparser/src/' . str_replace('\\', '/', $c) . '.php';
        if (is_file($f)) require $f;
    }
});
$testi = sys_get_temp_dir() . '/aa-carte-testi-' . getmypid();
@mkdir($testi, 0775, true);
$estrai = function (string $f) use ($pdftotext): string {
    if ($pdftotext !== '') return (string) shell_exec(escapeshellarg($pdftotext) . ' -layout ' . escapeshellarg($f) . ' - 2>/dev/null');
    try { return (new \Smalot\PdfParser\Parser())->parseFile($f)->getText(); }
    catch (\Throwable $e) { return '#ERRORE ' . $e->getMessage(); }
};
$oggi = date('Ymd');
$home = rtrim((string) getenv('HOME'), '/');
$csv = fopen("$home/aa-carte-servizi-$oggi.csv", 'w');
fwrite($csv, "\xEF\xBB\xBF");
fputcsv($csv, ['alias', 'pagina', 'pubblicata', 'file', 'KB', 'caratteri estratti', 'frasi chiave'], ';');

$lista = []; $tot = 0; $senza = [];
foreach (glob("$base/*", GLOB_ONLYDIR) as $dir) {
    $alias = basename($dir);
    $res = $modx->getObject(modResource::class, ['alias' => $alias, 'deleted' => 0]);
    $carte = array_values(array_filter(glob("$dir/*.pdf") ?: [], fn($f) => stripos(basename($f), 'carta') !== false));
    if (!$carte) { $senza[] = $alias; continue; }
    foreach ($carte as $f) {
        $kb = (int) round(filesize($f) / 1024); $tot += $kb;
        $grezzo = $estrai($f);
        // alcuni PDF producono byte non UTF-8: con /u preg_replace restituirebbe null
        if (!mb_check_encoding($grezzo, 'UTF-8')) $grezzo = mb_convert_encoding($grezzo, 'UTF-8', 'UTF-8');
        file_put_contents("$testi/{$alias}__" . preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($f, '.pdf')) . '.txt', $grezzo);
        $t = preg_replace('/\s+/u', ' ', $grezzo) ?? preg_replace('/\s+/', ' ', $grezzo) ?? '';
        if (!preg_match_all('/[^.]{0,160}(accreditat|a contratto|contrattualizzat|convenzionat|autorizzat)[^.]{0,160}/iu', $t, $m)) $m = [[]];
        $frasi = implode(' || ', array_slice(array_unique($m[0]), 0, 6));
        fputcsv($csv, [$alias, $res ? $res->get('uri') : '(nessuna pagina)', $res ? (int) $res->get('published') : '', basename($f), $kb, mb_strlen($t), $frasi], ';');
        echo '  ' . str_pad($alias, 50) . ' ' . basename($f) . ' — ' . mb_strlen($t) . " caratteri\n";
        $lista[] = "$alias/" . basename($f);
    }
}
fclose($csv);
$tgz = "$home/aa-carte-testi-$oggi.tar.gz";
shell_exec('tar czf ' . escapeshellarg($tgz) . ' -C ' . escapeshellarg($testi) . ' . 2>&1');
shell_exec('rm -rf ' . escapeshellarg($testi));
$tar = "$home/aa-carte-servizi-$oggi.tar";
if ($conPdf) {
    @unlink($tar);
    $elenco = tempnam(sys_get_temp_dir(), 'carte');
    file_put_contents($elenco, implode("\n", $lista) . "\n");
    shell_exec('tar cf ' . escapeshellarg($tar) . ' -C ' . escapeshellarg($base) . ' -T ' . escapeshellarg($elenco) . ' 2>&1');
    unlink($elenco);
}
echo "== Carte dei servizi: " . count($lista) . " PDF, " . round($tot / 1024, 1) . " MB\n";
echo "== Cartelle senza carta: " . count($senza) . ($senza ? ' (' . implode(', ', array_slice($senza, 0, 15)) . (count($senza) > 15 ? ', …' : '') . ')' : '') . "\n";
echo "== Estrazione del testo: " . ($pdftotext ?: 'libreria PHP pdfparser') . "\n";
echo "== Scritti: ~/aa-carte-servizi-$oggi.csv e ~/aa-carte-testi-$oggi.tar.gz (" . round((int) @filesize($tgz) / 1048576, 1) . " MB)"
    . ($conPdf ? " e ~/aa-carte-servizi-$oggi.tar (" . round((int) @filesize($tar) / 1048576, 1) . " MB)" : '') . "\n";
