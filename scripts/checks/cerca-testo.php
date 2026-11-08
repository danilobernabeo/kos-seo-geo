<?php
/**
 * cerca-testo.php — SOLA LETTURA: dove compare un testo nel database MODX (contenuti, introtext, valori delle TV, chunk,
 * template, snippet, plugin). Serve a trovare la sorgente di un elemento della pagina (es. un'immagine) quando non è
 * nel contenuto né negli elementi esportati.
 *
 * Uso: php scripts/checks/cerca-testo.php /www <testo>
 */
declare(strict_types=1);
[$webroot, $testo] = [rtrim($argv[1] ?? '', '/'), (string) ($argv[2] ?? '')];
if (!is_file("$webroot/config.core.php") || $testo === '') { fwrite(STDERR, "Uso: php cerca-testo.php /www <testo>\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$like = $modx->quote('%' . $testo . '%');
$q = [
    'contenuto'  => "SELECT id, uri AS nome FROM {$p}site_content WHERE content LIKE $like OR introtext LIKE $like",
    'tv'         => "SELECT v.contentid AS id, CONCAT(t.name, ' (risorsa ', v.contentid, ')') AS nome FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars t ON t.id = v.tmplvarid WHERE v.value LIKE $like",
    'tv default' => "SELECT id, name AS nome FROM {$p}site_tmplvars WHERE default_text LIKE $like OR elements LIKE $like",
    'chunk'      => "SELECT id, name AS nome FROM {$p}site_htmlsnippets WHERE snippet LIKE $like",
    'template'   => "SELECT id, templatename AS nome FROM {$p}site_templates WHERE content LIKE $like",
    'snippet'    => "SELECT id, name AS nome FROM {$p}site_snippets WHERE snippet LIKE $like",
    'plugin'     => "SELECT id, name AS nome FROM {$p}site_plugins WHERE plugincode LIKE $like",
];
$tot = 0;
foreach ($q as $tipo => $sql) {
    $st = $modx->query($sql);
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $r) { echo sprintf("%-11s %-6s %s\n", $tipo, $r['id'], $r['nome']); $tot++; }
}
echo "== $tot occorrenze di «$testo»\n";
