<?php
/**
 * Diagnostica in SOLA LETTURA: valore grezzo e valore restituito da getTVValue di una o più TV di una risorsa, più il
 * valore di un'impostazione di sistema.
 *
 * Uso: php scripts/checks/tv-valore.php /www <uri o id> <tv[,tv...]> [impostazione]
 *   php scripts/checks/tv-valore.php /www it/centri/istituto-santo-stefano mapTecnologie,mapAreeRiabilitazione kos.struttura_tv_tecnologie
 */
declare(strict_types=1);
[$_, $webroot, $chi, $tvs] = array_pad($argv, 4, '');
$imp = $argv[4] ?? '';
$webroot = rtrim($webroot, '/');
if (!is_file("$webroot/config.core.php") || $chi === '' || $tvs === '') { fwrite(STDERR, "Uso: php tv-valore.php /www <uri|id> <tv,...> [impostazione]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('web');
$r = ctype_digit($chi) ? $modx->getObject(modResource::class, (int) $chi)
                       : $modx->getObject(modResource::class, ['uri:IN' => [trim($chi, '/'), trim($chi, '/') . '/']]);
if (!$r) { echo "Risorsa non trovata: $chi\n"; exit(2); }
echo "Risorsa {$r->get('id')} «{$r->get('pagetitle')}» template {$r->get('template')}\n";
foreach (explode(',', $tvs) as $nome) {
    $nome = trim($nome);
    $tv = $modx->getObject(modTemplateVar::class, ['name' => $nome]);
    if (!$tv) { echo "  TV $nome: non esiste\n"; continue; }
    $grezzo = $modx->getObject(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id'), 'contentid' => $r->get('id')]);
    $assegnata = (bool) $modx->getCount(\MODX\Revolution\modTemplateVarTemplate::class, ['tmplvarid' => $tv->get('id'), 'templateid' => $r->get('template')]);
    echo "  TV $nome (tipo {$tv->get('type')}, output {$tv->get('display')}, assegnata al template: " . ($assegnata ? 'sì' : 'NO') . ")\n";
    echo '    grezzo:      ' . var_export($grezzo ? $grezzo->get('value') : null, true) . "\n";
    echo '    getTVValue:  ' . var_export($r->getTVValue($nome), true) . "\n";
}
if ($imp !== '') echo "Impostazione $imp = " . var_export($modx->getOption($imp), true) . "\n";
