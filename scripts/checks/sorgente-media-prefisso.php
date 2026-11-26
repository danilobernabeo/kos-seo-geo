<?php
/**
 * Controllo in SOLA LETTURA: perché le immagini delle TV (es. infoImage) escono in pagina senza barra
 * iniziale (assets/img/...) e cosa succede se si cambia la sorgente media.
 *
 * MODX 3 (modTemplateVar::prepareOutput, modMediaSource::getBases) antepone al valore di una TV di tipo
 * image/file l'URL della sua sorgente: base_url del contesto + baseUrl se baseUrlRelative = Sì. Con la
 * sorgente «Filesystem» di serie (baseUrl vuoto, relativo) dovrebbe già uscire /assets/...; se esce
 * assets/... la TV non passa dalla sorgente (tipo testo, nessuna associazione per il contesto, snippet
 * che legge il valore grezzo) oppure il contesto ha base_url vuoto. Lo script mostra:
 *
 *  1. sorgente, contesti con base_url, TV immagine/file/migx o usate in src/url()/content, con tipo e
 *     sorgente per contesto;
 *  2. valori salvati di quelle TV: quanti iniziano con "/", con "http", con "assets/", altro;
 *  3. elementi e contenuti che mettono "/", [[++site_url]] o [[++base_url]] davanti a quelle TV
 *     (si romperebbero se la TV iniziasse a uscire con la barra) ed elementi PHP che le leggono.
 *
 * Stampa nomi di elementi e di TV, numeri di risorse; nessun dato personale.
 * Uso: php scripts/checks/sorgente-media-prefisso.php /www [nome sorgente, default Filesystem]
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
$nomeSorgente = $argv[2] ?? 'Filesystem';
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php sorgente-media-prefisso.php /www [sorgente]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('web');
$p = $modx->getOption('table_prefix', null, 'modx_');
$q = function (string $sql, array $par = []) use ($modx) {
    $st = $modx->prepare($sql);
    if (!$st || !$st->execute($par)) { fwrite(STDERR, "Query non riuscita: $sql\n"); exit(1); }
    return $st->fetchAll(PDO::FETCH_ASSOC);
};

// --- 1. sorgente e TV ------------------------------------------------------------------------
$src = $q("SELECT id, name, properties FROM {$p}media_sources WHERE name = ?", [$nomeSorgente]);
if (!$src) { fwrite(STDERR, "Sorgente «$nomeSorgente» non trovata\n"); exit(1); }
$srcId = (int) $src[0]['id'];
$prop = @unserialize($src[0]['properties']) ?: [];
$val = fn($k) => is_array($prop[$k] ?? null) ? var_export($prop[$k]['value'] ?? '', true) : var_export($prop[$k] ?? '(predefinito)', true);
$default = (int) $modx->getOption('default_media_source', null, 1);
echo "== Sorgente «$nomeSorgente» id $srcId (predefinita di sistema: $default)\n";
foreach (['basePath', 'basePathRelative', 'baseUrl', 'baseUrlRelative'] as $k) echo "   $k = {$val($k)}\n";

// TV usate come immagine negli elementi: src="[[*nome", src="[[+tv.nome", url([[*nome ... (qualsiasi tipo)
$usateInSrc = [];
foreach (["SELECT snippet AS t FROM {$p}site_htmlsnippets", "SELECT content AS t FROM {$p}site_templates"] as $sql) {
    foreach ($q($sql) as $r) {
        if (preg_match_all('#(?:src=["\']|url\(["\']?|content=["\'])\s*\[\[[*+](?:tv\.)?([A-Za-z0-9_\-]+)#', (string) $r['t'], $m)) {
            foreach ($m[1] as $n) $usateInSrc[$n] = true;
        }
    }
}
$tvs = $q("SELECT id, name, type FROM {$p}site_tmplvars ORDER BY name");
$assoc = $q("SELECT object AS tv, context_key, source FROM {$p}media_sources_elements WHERE object_class LIKE '%modTemplateVar'");
$perTv = [];
foreach ($assoc as $a) $perTv[(int) $a['tv']][$a['context_key']] = (int) $a['source'];

// contesti con risorse e loro base_url (la sorgente relativa antepone base_url del contesto a baseUrl)
$ctxBase = [];
foreach ($q("SELECT context_key, COUNT(*) AS n FROM {$p}site_content WHERE deleted = 0 GROUP BY context_key") as $c) {
    $b = $q("SELECT value FROM {$p}context_setting WHERE context_key = ? AND `key` = 'base_url'", [$c['context_key']]);
    $ctxBase[$c['context_key']] = ['n' => (int) $c['n'], 'base_url' => $b ? $b[0]['value'] : '(sistema: ' . MODX_BASE_URL . ')'];
}
echo "\n== Contesti con risorse e base_url\n";
foreach ($ctxBase as $k => $c) printf("   %-10s %6d risorse  base_url = %s\n", $k, $c['n'], var_export($c['base_url'], true));
echo "   manipulatable_url_tv_output_types = " . var_export($modx->getOption('manipulatable_url_tv_output_types', null, 'image,file'), true) . "\n";

$coinvolte = [];
echo "\n== TV immagine/file/migx o usate in src/url()/content: tipo e sorgente per contesto\n";
echo "   (MODX antepone l'URL della sorgente solo ai tipi image/file e solo se la TV ha una sorgente per il contesto della risorsa;\n";
echo "    senza associazione per quel contesto il valore esce così com'è)\n";
foreach ($tvs as $t) {
    if (!in_array($t['type'], ['image', 'file', 'migx'], true) && !isset($usateInSrc[$t['name']])) continue;
    $ctx = $perTv[(int) $t['id']] ?? [];
    $descr = $ctx ? implode(', ', array_map(fn($c, $s) => "$c:$s", array_keys($ctx), $ctx)) : 'nessuna';
    $manca = array_diff(array_keys($ctxBase), array_keys($ctx));
    $nota = in_array($t['type'], ['image', 'file'], true)
        ? ($manca ? 'senza sorgente per: ' . implode(',', $manca) : '')
        : 'tipo ' . $t['type'] . ': valore stampato com\'è';
    printf("   %-28s %-8s %-3s sorgenti %-24s %s\n", $t['name'], $t['type'], isset($usateInSrc[$t['name']]) ? 'src' : '', $descr, $nota);
    if (in_array($srcId, $ctx, true) || isset($usateInSrc[$t['name']])) $coinvolte[$t['name']] = (int) $t['id'];
}

// --- 2. valori salvati -------------------------------------------------------------------------
echo "\n== Valori salvati delle TV coinvolte (risorse)\n";
printf("   %-28s %8s %8s %8s %8s %8s\n", 'TV', 'totale', '/...', 'http', 'assets/', 'altro');
foreach ($coinvolte as $nome => $id) {
    $r = $q("SELECT
        COUNT(*) AS tot,
        SUM(value LIKE '/%') AS barra,
        SUM(value LIKE 'http%') AS http,
        SUM(value LIKE 'assets/%') AS assets
      FROM {$p}site_tmplvar_contentvalues WHERE tmplvarid = ? AND value <> ''", [$id])[0];
    $altro = (int) $r['tot'] - (int) $r['barra'] - (int) $r['http'] - (int) $r['assets'];
    printf("   %-28s %8d %8d %8d %8d %8d\n", $nome, $r['tot'], $r['barra'], $r['http'], $r['assets'], $altro);
}
echo "   (i valori «/...» uscirebbero //... se la TV passasse dalla sorgente: da correggere prima)\n";

// --- 3. usi negli elementi e nei contenuti ----------------------------------------------------
$nomi = array_map(fn($n) => preg_quote($n, '#'), array_keys($coinvolte));
if (!$nomi) { echo "\nNessuna TV coinvolta.\n"; exit(0); }
$alt = implode('|', $nomi);
$tag = "\\[\\[[*+](?:tv\\.)?(?:$alt)\\b";                          // [[*infoImage  [[+tv.infoImage  [[+infoImage
$rischio = [
    'barra davanti'   => "#/$tag#",
    'site_url davanti' => "#\\[\\[\\+\\+site_url\\]\\]\\s*$tag#",
    'base_url davanti' => "#\\[\\[\\+\\+base_url\\]\\]\\s*$tag#",
];
$php = "#(getTVValue|renderOutput|getValue|getTemplateVar(?:Output)?)\\s*\\(\\s*['\"]?(?:$alt)\\b#";
$fonti = [
    'chunk'    => "SELECT name, snippet AS testo FROM {$p}site_htmlsnippets",
    'template' => "SELECT templatename AS name, content AS testo FROM {$p}site_templates",
    'snippet'  => "SELECT name, snippet AS testo FROM {$p}site_snippets",
    'plugin'   => "SELECT name, plugincode AS testo FROM {$p}site_plugins",
    'risorsa'  => "SELECT CONCAT(id, ' ', uri) AS name, content AS testo FROM {$p}site_content WHERE deleted = 0",
];
echo "\n== Elementi che mettono qualcosa davanti a una TV coinvolta (si romperebbero se la TV uscisse con la barra)\n";
$trovati = 0; $daGuardare = [];
foreach ($fonti as $tipo => $sql) {
    foreach ($q($sql) as $r) {
        $t = (string) $r['testo'];
        foreach ($rischio as $etichetta => $re) {
            if (preg_match_all($re, $t, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$s, $off]) {
                    $riga = substr_count(substr($t, 0, $off), "\n") + 1;
                    echo "   $tipo «{$r['name']}» riga $riga ($etichetta): " . substr($t, max(0, $off - 20), 80) . "\n";
                    $trovati++;
                }
            }
        }
        if ($tipo !== 'risorsa' && preg_match($php, $t)) $daGuardare[] = "$tipo «{$r['name']}»";
    }
}
if (!$trovati) echo "   nessuno\n";
echo "\n== Elementi PHP che leggono una TV coinvolta (controllare a mano come compongono il percorso)\n";
echo $daGuardare ? '   ' . implode("\n   ", array_unique($daGuardare)) . "\n" : "   nessuno\n";
