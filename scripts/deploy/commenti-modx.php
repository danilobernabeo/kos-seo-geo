<?php
/**
 * commenti-modx.php — tag MODX dentro i commenti HTML del campo content. MODX esegue i tag anche dentro <!-- -->: un
 * [[Wayfinder]] «commentato» costruisce comunque il menu a ogni generazione della pagina, senza mostrarlo (costo sul
 * tempo di risposta). La regola del progetto è disattivarli con il tag di commento di MODX [[- ...]].
 *
 *   <!-- [[Wayfinder? &startId=`67`]] -->          →   [[- Wayfinder? &startId=`67`]]
 *   <!-- <p>[[Wayfinder? &startId=`84`]]</p> -->   →   [[- Wayfinder? &startId=`84`]]
 * Solo commenti che contengono un unico tag e nient'altro di significativo (eventuale <p> attorno): gli altri sono elencati
 * come «da vedere a mano» e non toccati.
 *
 * Uso (in SSH, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/commenti-modx.php /www [--template=provincia]            # prova
 *   php scripts/deploy/commenti-modx.php /www [--template=provincia] --scrivi
 *   php scripts/deploy/commenti-modx.php /www --ripristina=<file ripristino.json> [--scrivi]
 * Prima di scrivere: tabella <prefisso>site_content_bak_<AAAAMMGG_HHMM> (id, content) e ripristino.json in
 * ~/backup-kos-seo-geo/<data>-yext-contenuti/. Ripristino solo delle pagine non modificate dopo (salvo --forza).
 * Alla fine svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$pos = []; $opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php commenti-modx.php /www [--template=provincia] [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');
$scrivi = isset($opt['scrivi']);
$stamp = date('Ymd-His');
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . "/backup-kos-seo-geo/$stamp-commenti-modx";

$converti = function (string $t): array {
    $n = 0;
    $nuovo = preg_replace_callback('~<!--\s*(?:<p>\s*)?\[\[(?!-)([^\[\]]+)\]\](?:\s*</p>)?\s*-->~s',
        function ($m) use (&$n) { $n++; return '[[- ' . trim($m[1]) . ']]'; }, $t);
    return [$nuovo, $n];
};
$restanti = function (string $t): int {
    return preg_match_all('~<!--(?:(?!-->).)*?\[\[(?!-)(?:(?!-->).)*?-->~s', $t);
};

/* ---------------------------------------------------------------- ripristino */
if (isset($opt['ripristina'])) {
    $rip = json_decode((string) file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($rip)) { fwrite(STDERR, "File di ripristino non leggibile\n"); exit(1); }
    $fatte = 0; $fermi = 0;
    foreach ($rip['pagine'] as $p) {
        $r = $modx->getObject(modResource::class, (int) $p['id']);
        if (!$r) { echo "MANCANTE  {$p['id']}\n"; continue; }
        if (sha1((string) $r->get('content')) !== $p['sha1_dopo'] && !isset($opt['forza'])) { echo "FERMO     {$p['id']} modificata dopo la correzione (--forza per rimettere comunque)\n"; $fermi++; continue; }
        echo ($scrivi ? 'RIPRISTINO' : 'DA RIPRISTINARE') . " {$p['id']} {$r->get('uri')}\n";
        if ($scrivi) { $r->set('content', $p['prima']); $r->save(); $fatte++; }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo "== " . ($scrivi ? "$fatte ripristinate" : 'prova') . ", $fermi fermi\n";
    exit(0);
}

/* ---------------------------------------------------------------- conversione */
$tplNomi = array_filter(array_map('trim', explode(',', (string) ($opt['template'] ?? 'provincia'))));
$tplId = [];
foreach ($tplNomi as $n) if ($t = $modx->getObject(modTemplate::class, ['templatename' => $n])) $tplId[] = (int) $t->get('id');
if (!$tplId) { fwrite(STDERR, "Template non trovati: " . implode(',', $tplNomi) . "\n"); exit(1); }

$q = $modx->newQuery(modResource::class, ['template:IN' => $tplId, 'deleted' => 0, 'content:LIKE' => '%<!--%[[%']);
$pagine = []; $campi = 0;
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    [$nuovo, $n] = $converti((string) $r->get('content'));
    $resta = $restanti($nuovo);
    if (!$n && !$resta) continue;
    echo sprintf("%-4d %-50s %d tag disattivati%s\n", $r->get('id'), $r->get('uri'), $n, $resta ? "  DA VEDERE A MANO: $resta commenti con tag" : '');
    if (!$n) continue;
    $pagine[] = [$r, $nuovo]; $campi += $n;
}
echo "== " . count($pagine) . " pagine, $campi tag disattivati (template " . implode(',', $tplNomi) . ")\n";
if (!$scrivi) { echo "Prova: nessuna scrittura. Rilanciare con --scrivi.\n"; exit(0); }

@mkdir($bk, 0700, true);
$pref = $modx->getOption('table_prefix', null, 'modx_');
$tab = $pref . 'site_content_bak_' . date('Ymd_Hi');
$ids = implode(',', array_map(fn($p) => (int) $p[0]->get('id'), $pagine));
if ($ids !== '') {
    $modx->exec("CREATE TABLE IF NOT EXISTS `$tab` AS SELECT id, content FROM `{$pref}site_content` WHERE id IN ($ids)");
}
$rip = ['creato' => date('c'), 'tabella' => $tab, 'pagine' => []];
foreach ($pagine as [$r, $nuovo]) {
    $prima = (string) $r->get('content');
    $r->set('content', $nuovo);
    if (!$r->save()) { echo "ERRORE salvataggio {$r->get('id')}\n"; continue; }
    $rip['pagine'][] = ['id' => (int) $r->get('id'), 'uri' => $r->get('uri'), 'prima' => $prima, 'sha1_dopo' => sha1($nuovo)];
}
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
$modx->cacheManager->refresh();
echo "== Scritte " . count($rip['pagine']) . " pagine, cache svuotata. Backup: tabella $tab e $bk/ripristino.json\n";
echo "   Ripristino: php scripts/deploy/commenti-modx.php /www --ripristina=$bk/ripristino.json --scrivi\n";
