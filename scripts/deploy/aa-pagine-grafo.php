<?php
/**
 * aa-pagine-grafo.php — anniazzurri.it, sprint 3 (regola «un solo @graph per pagina, niente JSON-LD scritto a mano»):
 * per le pagine con head proprio (/home-en id 135, /azzurramente id 410):
 *   - toglie dal campo content i blocchi <script type="application/ld+json"> scritti a mano;
 *   - aggiunge prima di </head> del loro template (o del contenuto, per le pagine senza template) la chiamata ad aaSchema (con le opzioni della pagina), se il template
 *     non la contiene già, direttamente o in un chunk richiamato con [[$nome]].
 *
 * Uso (in SSH, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/aa-pagine-grafo.php /www            # prova
 *   php scripts/deploy/aa-pagine-grafo.php /www --scrivi
 *   php scripts/deploy/aa-pagine-grafo.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-grafo/ripristino.json (contenuti e template prima della modifica). Svuota la cache.
 * Dopo la scrittura il template è modificato nel database: esportarlo in anniazzurri/elements/templates/ (README).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php aa-pagine-grafo.php /www [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modChunk};
$modx = modX::getInstance();
$modx->initialize('mgr');
$scrivi = isset($opt['scrivi']);

// pagina => chiamata ad aaSchema
$PAGINE = [
    135 => '[[aaSchema? &lingua=`en` &briciole=`0`]]',   // /home-en: pagina inglese, nessuna briciola visibile
    410 => '[[aaSchema? &briciole=`0`]]',                 // /azzurramente: nessuna briciola visibile
];

if (isset($opt['ripristina'])) {
    $rip = json_decode((string) file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($rip)) { fwrite(STDERR, "File di ripristino non leggibile\n"); exit(1); }
    foreach ($rip['risorse'] ?? [] as $x) {
        $r = $modx->getObject(modResource::class, (int) $x['id']);
        echo ($scrivi ? 'RIPRISTINO' : 'DA RIPRISTINARE') . " risorsa {$x['id']}\n";
        if ($scrivi && $r) { $r->set('content', $x['prima']); $r->save(); }
    }
    foreach ($rip['template'] ?? [] as $x) {
        $t = $modx->getObject(modTemplate::class, (int) $x['id']);
        echo ($scrivi ? 'RIPRISTINO' : 'DA RIPRISTINARE') . " template {$x['nome']}\n";
        if ($scrivi && $t) { $t->set('content', $x['prima']); $t->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    exit(0);
}

$haSchema = function (string $html) use ($modx): bool {
    if (stripos($html, 'aaSchema') !== false) return true;
    if (preg_match_all('/\[\[\$([\w.-]+)/', $html, $m)) {
        foreach (array_unique($m[1]) as $nome) {
            $c = $modx->getObject(modChunk::class, ['name' => $nome]);
            if ($c && stripos((string) $c->get('snippet'), 'aaSchema') !== false) return true;
        }
    }
    return false;
};
$rip = ['creato' => date('c'), 'risorse' => [], 'template' => []];
$fatti = [];
foreach ($PAGINE as $id => $chiamata) {
    $r = $modx->getObject(modResource::class, $id);
    if (!$r) { echo "MANCANTE  risorsa $id\n"; continue; }
    $uri = $r->get('uri');
    $prima = (string) $r->get('content');
    $n = 0;
    $dopo = preg_replace('~\s*<script\b[^>]*type\s*=\s*["\']application/ld\+json["\'][^>]*>.*?</script>~is', '', $prima, -1, $n);
    echo sprintf("%-6d %-16s blocchi JSON-LD nel contenuto: %d\n", $id, $uri, $n);
    $t = (int) $r->get('template') ? $r->getOne('Template') : null;
    if (!$t) {
        // pagina senza template: tutto l'HTML, head compreso, sta nel contenuto
        if ($haSchema($dopo)) echo "       senza template: aaSchema già nel contenuto\n";
        elseif (stripos($dopo, '</head>') === false) echo "       senza template e senza </head> nel contenuto: FERMO\n";
        else { $dopo = preg_replace('~</head>~i', "  $chiamata\n</head>", $dopo, 1); echo "       senza template: aggiungo $chiamata prima di </head> nel contenuto\n"; }
    }
    if ($dopo !== $prima) {
        $rip['risorse'][] = ['id' => $id, 'prima' => $prima];
        if ($scrivi) { $r->set('content', $dopo); $r->save(); }
    }
    if (!$t) continue;
    $nome = $t->get('templatename');
    $usato = (int) $modx->getCount(modResource::class, ['template' => $t->get('id'), 'deleted' => 0]);
    $tc = (string) $t->get('content');
    if ($haSchema($tc)) { echo "       template «$nome» ($usato pagine): aaSchema già presente\n"; continue; }
    if (isset($fatti[$t->get('id')])) { echo "       template «$nome»: già modificato in questo giro\n"; continue; }
    if (stripos($tc, '</head>') === false) { echo "       template «$nome»: nessun </head>, FERMO\n"; continue; }
    if ($usato > 1) echo "       ATTENZIONE: il template «$nome» è usato da $usato pagine, la chiamata vale per tutte\n";
    echo "       template «$nome» ($usato pagine): aggiungo $chiamata prima di </head>\n";
    $rip['template'][] = ['id' => (int) $t->get('id'), 'nome' => $nome, 'prima' => $tc];
    $fatti[$t->get('id')] = true;
    if ($scrivi) { $t->set('content', preg_replace('~</head>~i', "  $chiamata\n</head>", $tc, 1)); $t->save(); }
}
if (!$scrivi) { echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-grafo';
mkdir($bk, 0775, true);
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$modx->cacheManager->refresh();
echo "== Fatto. Ripristino: php scripts/deploy/aa-pagine-grafo.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
