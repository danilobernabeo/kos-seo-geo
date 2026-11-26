<?php
/**
 * SOLA LETTURA: come esce in pagina una TV immagine/file per una risorsa, passo per passo.
 * Valore salvato, prepareOutput (sorgente media), renderOutput (quello che stampa [[*tv]]),
 * e le basi della sorgente (url, urlAbsolute) calcolate da MODX nel contesto della risorsa.
 * Serve a capire perché infoImage esce assets/... senza barra (link relativi, 26/11).
 *
 * Uso: php scripts/checks/tv-uscita.php /www [id risorsa|0] [nome TV, default infoImage]
 * Con id 0 o assente prende da sola una risorsa con valore «assets/...» e una con valore «/...».
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
$id = (int) ($argv[2] ?? 0);
$nome = $argv[3] ?? 'infoImage';
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php tv-uscita.php /www [id] [tv]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
use MODX\Revolution\modResource;
use MODX\Revolution\modTemplateVar;
$modx = modX::getInstance();
$modx->initialize('web');
$tv = $modx->getObject(modTemplateVar::class, ['name' => $nome]);
if (!$tv) { fwrite(STDERR, "TV non trovata\n"); exit(1); }
$ids = [$id];
if (!$id) {
    $p = $modx->getOption('table_prefix', null, 'modx_');
    $ids = [];
    foreach (["assets/%", "/%"] as $like) {
        $st = $modx->prepare("SELECT contentid FROM {$p}site_tmplvar_contentvalues WHERE tmplvarid = ? AND value LIKE ? ORDER BY contentid LIMIT 1");
        $st->execute([$tv->get('id'), $like]);
        if ($r = $st->fetchColumn()) $ids[] = (int) $r;
    }
}
foreach ($ids as $id) {
    $res = $modx->getObject(modResource::class, $id);
    if (!$res) { echo "risorsa $id non trovata\n"; continue; }
    $modx->resource = $res;
    $grezzo = $tv->getValue($id);
    echo "risorsa $id (contesto {$res->get('context_key')}), TV $nome tipo {$tv->get('type')}\n";
    echo "  valore salvato : " . var_export($grezzo, true) . "\n";
    echo "  prepareOutput  : " . var_export($tv->prepareOutput($grezzo, $id), true) . "\n";
    echo "  renderOutput   : " . var_export($tv->renderOutput($id), true) . "\n";
    $src = $tv->getSource($res->get('context_key'));
    if ($src) {
        $src->initialize();
        $b = $src->getBases('');
        echo "  sorgente       : {$src->get('name')} (" . get_class($src) . ")\n";
        echo "  url / urlAbsolute : " . var_export($b['url'] ?? null, true) . ' / ' . var_export($b['urlAbsolute'] ?? null, true) . "\n";
        echo "  getObjectUrl(valore) : " . var_export($src->getObjectUrl((string) $grezzo), true) . "\n";
    } else {
        echo "  sorgente       : nessuna per il contesto\n";
    }
    echo "  base_url contesto/sistema : " . var_export($modx->getOption('base_url'), true) . "\n";
}
