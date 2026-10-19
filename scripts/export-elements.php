<?php
/**
 * export-elements.php — istantanea in sola lettura degli elementi MODX 3 di un sito.
 *
 * Esporta template, chunk, snippet, plugin (con eventi) e definizioni delle TV
 * in file, più un manifest.json con i metadati (id, categoria, statico, proprietà).
 * Non scrive nulla nel database.
 *
 * Uso (in SSH sul cloud MODX):
 *   php export-elements.php [percorso_webroot] [cartella_di_uscita]
 *   php export-elements.php /www /tmp/export-elements
 *
 * Poi:  cd /tmp && tar czf export-elements.tgz export-elements
 * e copiare il contenuto in <sito>/baseline/ nel repository.
 */

declare(strict_types=1);

$webroot = rtrim($argv[1] ?? '/www', '/');
$outDir  = rtrim($argv[2] ?? '/tmp/export-elements', '/');

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Da eseguire solo da riga di comando.\n");
    exit(1);
}

$configCore = $webroot . '/config.core.php';
if (!is_file($configCore)) {
    fwrite(STDERR, "config.core.php non trovato in $webroot\n");
    exit(1);
}
require $configCore;
require MODX_CORE_PATH . 'vendor/autoload.php';

$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');

/* ---------- utilità ---------- */
$safe = static function (string $name): string {
    $n = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    return trim((string) $n, '_') ?: 'senza_nome';
};

$categories = [];
foreach ($modx->getIterator(\MODX\Revolution\modCategory::class) as $c) {
    $categories[(int) $c->get('id')] = ['name' => $c->get('category'), 'parent' => (int) $c->get('parent')];
}
$catPath = static function (int $id) use (&$categories): string {
    $parts = [];
    $guard = 0;
    while ($id > 0 && isset($categories[$id]) && $guard++ < 20) {
        array_unshift($parts, $categories[$id]['name']);
        $id = $categories[$id]['parent'];
    }
    return implode(' / ', $parts);
};

$write = static function (string $path, string $content): void {
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    file_put_contents($path, $content);
};

$manifest = ['exported_at' => date('c'), 'site_url' => $modx->getOption('site_url'), 'elements' => []];

/* ---------- elementi con contenuto ---------- */
$types = [
    'templates' => [\MODX\Revolution\modTemplate::class, 'templatename', 'content', '.html'],
    'chunks'    => [\MODX\Revolution\modChunk::class,    'name',         'snippet', '.html'],
    'snippets'  => [\MODX\Revolution\modSnippet::class,  'name',         'snippet', '.php'],
    'plugins'   => [\MODX\Revolution\modPlugin::class,   'name',         'plugincode', '.php'],
];

foreach ($types as $folder => [$class, $nameField, $contentField, $ext]) {
    foreach ($modx->getIterator($class) as $el) {
        $name    = (string) $el->get($nameField);
        $content = (string) $el->get($contentField);
        if ($ext === '.php' && strpos(ltrim($content), '<?php') !== 0) {
            $content = "<?php\n" . $content;
        }
        $file = $folder . '/' . $safe($name) . $ext;
        $write($outDir . '/' . $file, $content);

        $meta = [
            'type'        => $folder,
            'id'          => (int) $el->get('id'),
            'name'        => $name,
            'file'        => $file,
            'category'    => $catPath((int) $el->get('category')),
            'description' => (string) $el->get('description'),
            'static'      => (bool) $el->get('static'),
            'static_file' => (string) $el->get('static_file'),
            'source'      => (int) $el->get('source'),
            'properties'  => $el->get('properties'),
        ];
        if ($folder === 'plugins') {
            $meta['disabled'] = (bool) $el->get('disabled');
            $meta['events']   = [];
            $evs = $modx->getCollection(\MODX\Revolution\modPluginEvent::class, ['pluginid' => $el->get('id')]);
            foreach ($evs as $ev) {
                $meta['events'][] = ['event' => $ev->get('event'), 'priority' => (int) $ev->get('priority')];
            }
        }
        $manifest['elements'][] = $meta;
    }
}

/* ---------- definizioni delle TV ---------- */
foreach ($modx->getIterator(\MODX\Revolution\modTemplateVar::class) as $tv) {
    $templates = [];
    foreach ($modx->getCollection(\MODX\Revolution\modTemplateVarTemplate::class, ['tmplvarid' => $tv->get('id')]) as $tt) {
        $templates[] = (int) $tt->get('templateid');
    }
    sort($templates);
    $def = [
        'id'             => (int) $tv->get('id'),
        'name'           => $tv->get('name'),
        'caption'        => $tv->get('caption'),
        'description'    => $tv->get('description'),
        'category'       => $catPath((int) $tv->get('category')),
        'type'           => $tv->get('type'),
        'elements'       => $tv->get('elements'),
        'default_text'   => $tv->get('default_text'),
        'display'        => $tv->get('display'),
        'input_properties'  => $tv->get('input_properties'),
        'output_properties' => $tv->get('output_properties'),
        'templates'      => $templates,
    ];
    $file = 'tvs/' . $safe((string) $tv->get('name')) . '.json';
    $write($outDir . '/' . $file, json_encode($def, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    $manifest['elements'][] = ['type' => 'tvs', 'id' => $def['id'], 'name' => $def['name'], 'file' => $file, 'category' => $def['category']];
}

/* ---------- impostazioni di sistema rilevanti (senza segreti) ---------- */
$keys = ['site_url', 'site_start', 'error_page', 'friendly_urls', 'use_alias_path', 'container_suffix',
         'cache_resource', 'cache_default', 'link_tag_scheme', 'cultureKey', 'manager_language'];
$settings = [];
foreach ($keys as $k) {
    $settings[$k] = $modx->getOption($k);
}
$write($outDir . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

usort($manifest['elements'], static fn($a, $b) => [$a['type'], $a['name']] <=> [$b['type'], $b['name']]);
$write($outDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

$counts = array_count_values(array_column($manifest['elements'], 'type'));
foreach ($counts as $t => $n) {
    echo str_pad($t, 10) . " $n\n";
}
echo "Esportato in $outDir\n";
