<?php
/**
 * Diagnostica in SOLA LETTURA (sprint 1, link interni): risorse MODX pubblicate il cui indirizzo è reindirizzato da una
 * regola web (301 nginx). Il visitatore non le vede mai, ma gli elenchi (getResources/pdoResources) le mostrano e
 * generano link interni verso un redirect (scansione del 18/11: 6 approfondimenti negli elenchi paginati).
 *
 * Uso: php scripts/checks/kos-pagine-redirette.php /www kosgroup/files/web-rules/web-rules.conf
 * Legge le regole «location = /percorso { return 301 … }» e «location ^~ /prefisso/ { return 301 … }».
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
$regole = $argv[2] ?? '';
if (!is_file("$webroot/config.core.php") || !is_file($regole)) { fwrite(STDERR, "Uso: php kos-pagine-redirette.php /www <web-rules.conf>\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('web');

$conf = (string) file_get_contents($regole);
preg_match_all('/location\s+(=|\^~)\s+(\S+)\s*\{\s*return\s+30[18]\s+(\S+?);/', $conf, $m, PREG_SET_ORDER);
$esatte = []; $prefissi = [];
foreach ($m as [$_, $tipo, $percorso, $dest]) {
    if ($tipo === '=') $esatte[trim($percorso, '/')] = $dest; else $prefissi[trim($percorso, '/') . '/'] = $dest;
}
echo 'Regole lette: ' . count($esatte) . ' esatte, ' . count($prefissi) . " per prefisso\n";

$trovate = 0;
foreach ($modx->getIterator(modResource::class, ['published' => 1, 'deleted' => 0, 'context_key' => 'web']) as $r) {
    $uri = trim((string) $r->get('uri'), '/');
    if ($uri === '') continue;
    $dest = $esatte[$uri] ?? null;
    if ($dest === null) foreach ($prefissi as $pre => $d) if (str_starts_with($uri . '/', $pre) && $uri . '/' !== $pre) { $dest = $d; break; }
    if ($dest === null) continue;
    $trovate++;
    printf("%d · /%s → %s · «%s» · template %d · genitore %d · nascosta nei menu %d · ricercabile %d\n",
        $r->get('id'), $uri, $dest, $r->get('pagetitle'), $r->get('template'), $r->get('parent'),
        $r->get('hidemenu'), $r->get('searchable'));
}
echo "\nRisorse pubblicate con indirizzo reindirizzato: $trovate\n";
