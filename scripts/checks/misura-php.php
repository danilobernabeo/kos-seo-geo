<?php
/**
 * misura-php.php — dove va il tempo PHP delle pagine in cache (KOS-2, sola lettura).
 *
 * 1. Plugin attivi con i loro eventi (quelli su OnHandleRequest, OnWebPageInit, OnLoadWebDocument,
 *    OnWebPagePrerender, OnWebPageComplete girano a ogni richiesta, anche con la pagina in cache).
 * 2. Tempo di risposta misurato dal server stesso (niente rete di mezzo): pagine e un file statico, 7 richieste
 *    ciascuno dopo una di riscaldamento, mediana di time_starttransfer.
 * 3. Per la prima pagina: ogni plugin di OnWebPagePrerender eseguito sull'HTML della pagina, 5 volte, mediana in ms.
 *    Lavora su una copia dell'HTML in memoria: non scrive nulla, non tocca la cache.
 *
 * Uso: php scripts/checks/misura-php.php /www https://kosgroup.com/it/strutture [altre pagine...]
 *      (sullo stage aggiungere --auth=kos:PASSWORD, letta da $AUTH: --auth=kos:$AUTH)
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);

$args = array_slice($argv, 1);
$auth = '';
foreach ($args as $i => $a) if (str_starts_with($a, '--auth=')) { $auth = substr($a, 7); unset($args[$i]); }
$args = array_values($args);
$webroot = rtrim($args[0] ?? '', '/');
$pagine = array_slice($args, 1);
if (!$webroot || !$pagine) exit("Uso: php misura-php.php /www <url pagina> [altre]\n");

require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modResource;
use MODX\Revolution\modSystemEvent;

$modx = modX::getInstance();
$modx->initialize('web');

$mediana = function (array $v): float { sort($v); $n = count($v); return $n ? ($n % 2 ? $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2) : 0.0; };

/* --- 1. Plugin attivi --- */
echo "== Plugin attivi e eventi\n";
$ogniRichiesta = ['OnHandleRequest', 'OnWebPageInit', 'OnLoadWebDocument', 'OnWebPagePrerender', 'OnWebPageComplete',
    'OnMODXInit', 'OnInitCulture', 'OnLoadWebPageCache', 'OnBeforeSaveWebPageCache', 'OnParseDocument'];
$perEvento = [];
foreach ($modx->getIterator(modPlugin::class, ['disabled' => 0]) as $p) {
    $ev = [];
    foreach ($modx->getIterator(modPluginEvent::class, ['pluginid' => $p->get('id')]) as $e) {
        $ev[] = $e->get('event') . ($e->get('priority') ? '(' . $e->get('priority') . ')' : '');
        if (in_array($e->get('event'), $ogniRichiesta, true)) $perEvento[$e->get('event')][] = $p->get('name');
    }
    $file = $p->get('static') ? ' statico ' . $p->get('static_file') : '';
    printf("  %-28s %5d byte%s\n    %s\n", $p->get('name'), strlen((string) $p->get('plugincode')), $file, implode(', ', $ev));
}
echo "\n== Eventi che girano a ogni richiesta\n";
foreach ($perEvento as $e => $nomi) echo "  $e: " . implode(', ', $nomi) . "\n";

/* --- 2. Tempi dal server --- */
$misura = function (string $url) use ($auth, $mediana): array {
    $t = [];
    for ($i = 0; $i < 8; $i++) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Cache-Control: no-cache'], CURLOPT_USERAGENT => 'kos-seo-geo misura-php']);
        if ($auth) curl_setopt($c, CURLOPT_USERPWD, $auth);
        $body = curl_exec($c);
        $codice = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
        $tt = curl_getinfo($c, CURLINFO_STARTTRANSFER_TIME);
        curl_close($c);
        if ($i > 0) $t[] = $tt;                                  // la prima scalda la cache
    }
    return [$codice, $mediana($t), min($t), max($t), is_string($body) ? $body : ''];
};
echo "\n== Tempo fino al primo byte, misurato dal server (mediana di 7, in ms)\n";
$base = rtrim((string) $modx->getOption('site_url'), '/');
$statici = [$base . '/robots.txt'];
$html0 = '';
foreach (array_merge($pagine, $statici) as $k => $u) {
    [$cod, $med, $min, $max, $body] = $misura($u);
    if ($k === 0) $html0 = $body;
    printf("  %3d  mediana %6.1f  min %6.1f  max %6.1f  %s\n", $cod, $med * 1000, $min * 1000, $max * 1000, $u);
}

/* --- 3. Plugin di OnWebPagePrerender sull'HTML della prima pagina --- */
echo "\n== Plugin di OnWebPagePrerender sull'HTML di {$pagine[0]} (mediana di 5, in ms)\n";
$uri = ltrim((string) parse_url($pagine[0], PHP_URL_PATH), '/');
$res = $modx->getObject(modResource::class, ['uri' => $uri, 'context_key' => 'web'])
    ?: $modx->getObject(modResource::class, ['uri' => $uri . '/', 'context_key' => 'web']);
if (!$res || $html0 === '') exit("  risorsa o HTML non trovati per «{$uri}»\n");
$modx->resource = $res;
$modx->resourceIdentifier = $res->get('id');
printf("  risorsa %d, template %d, HTML %d byte\n", $res->get('id'), $res->get('template'), strlen($html0));
foreach ($perEvento['OnWebPagePrerender'] ?? [] as $nome) {
    $p = $modx->getObject(modPlugin::class, ['name' => $nome]);
    $t = [];
    for ($i = 0; $i < 6; $i++) {
        $modx->resource->_output = $html0;
        $modx->event = new modSystemEvent();
        $modx->event->name = 'OnWebPagePrerender';
        $modx->event->activePlugin = $nome;
        $p->_processed = false;
        $t0 = hrtime(true);
        $p->process([]);
        if ($i > 0) $t[] = (hrtime(true) - $t0) / 1e6;
    }
    $diff = strlen((string) $modx->resource->_output) - strlen($html0);
    printf("  %-28s %7.1f ms  (HTML %+d byte)\n", $nome, $mediana($t), $diff);
}
echo "\nNota: i plugin degli altri eventi non si eseguono qui (possono reindirizzare o uscire): se il totale dei\n"
   . "Prerender non spiega la differenza pagina − file statico, si provano disattivandoli uno alla volta sullo stage.\n";
