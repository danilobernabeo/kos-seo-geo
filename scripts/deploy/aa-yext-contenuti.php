<?php
/**
 * aa-yext-contenuti.php — anniazzurri.it, schede residenza (sprint 3): nel campo content delle pagine sostituisce il
 * markup Yext Knowledge Tags (elementi vuoti con data-yext-field, riempiti nel browser dallo script Yext) con le chiamate
 * allo snippet aaContatto, che scrive il dato dal server dalle TV str.*. Stessa regola di
 * scripts/yext/converti-markup-yext.py (usata per i chunk).
 *
 *   <a data-yext-field="email" data-yext-id="[[*data-yext-id]]"></a>   →   [[aaContatto? &campo=`email`]]
 *   <span class="x" data-yext-field="name" data-yext-id="[[*data-yext-id]]"></span> → <span class="x">[[aaContatto? &campo=`name`]]</span>
 *
 * Uso (in SSH, dalla cartella dell'archivio del repository):
 *   php scripts/deploy/aa-yext-contenuti.php /www [--template=rsa]            # prova: elenca pagine e campi
 *   php scripts/deploy/aa-yext-contenuti.php /www [--template=rsa] --scrivi
 *   php scripts/deploy/aa-yext-contenuti.php /www --ripristina=<file ripristino.json> [--scrivi]
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
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php aa-yext-contenuti.php /www [--template=rsa] [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate};
$modx = modX::getInstance();
$modx->initialize('mgr');
$scrivi = isset($opt['scrivi']);
$stamp = date('Ymd-His');
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . "/backup-kos-seo-geo/$stamp-yext-contenuti";

$converti = function (string $t): array {
    $n = 0;
    $nuovo = preg_replace_callback(
        '~<(a|span)\b([^>]*?)\sdata-yext-field="(\w+)"\s+data-yext-id="\[\[\*data-yext-id\]\]"([^>]*)>\s*</\1>~s',
        function ($m) use (&$n) {
            $n++;
            $attr = $m[2] . $m[4];
            $classe = preg_match('/\bclass="([^"]*)"/', $attr, $c) ? trim($c[1]) : '';
            if ($m[1] === 'a') return '[[aaContatto? &campo=`' . $m[3] . '`' . ($classe !== '' ? ' &classe=`' . $classe . '`' : '') . ']]';
            $chiamata = '[[aaContatto? &campo=`' . $m[3] . '`]]';
            return $classe !== '' ? '<span class="' . $classe . '">' . $chiamata . '</span>' : $chiamata;
        }, $t);
    return [$nuovo, $n];
};

/* ---------------------------------------------------------------- ripristino */
if (isset($opt['ripristina'])) {
    $rip = json_decode((string) file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($rip)) { fwrite(STDERR, "File di ripristino non leggibile\n"); exit(1); }
    $fatte = 0; $fermi = 0;
    foreach ($rip['pagine'] as $p) {
        $r = $modx->getObject(modResource::class, (int) $p['id']);
        if (!$r) { echo "MANCANTE  {$p['id']}\n"; continue; }
        if (sha1((string) $r->get('content')) !== $p['sha1_dopo'] && !isset($opt['forza'])) { echo "FERMO     {$p['id']} modificata dopo la conversione (--forza per rimettere comunque)\n"; $fermi++; continue; }
        echo ($scrivi ? 'RIPRISTINO' : 'DA RIPRISTINARE') . " {$p['id']} {$r->get('uri')}\n";
        if ($scrivi) { $r->set('content', $p['prima']); $r->save(); $fatte++; }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo "== " . ($scrivi ? "$fatte ripristinate" : 'prova') . ", $fermi fermi\n";
    exit(0);
}

/* ---------------------------------------------------------------- conversione */
$tplNomi = array_filter(array_map('trim', explode(',', (string) ($opt['template'] ?? 'rsa'))));
$tplId = [];
foreach ($tplNomi as $n) if ($t = $modx->getObject(modTemplate::class, ['templatename' => $n])) $tplId[] = (int) $t->get('id');
if (!$tplId) { fwrite(STDERR, "Template non trovati: " . implode(',', $tplNomi) . "\n"); exit(1); }

$q = $modx->newQuery(modResource::class, ['template:IN' => $tplId, 'deleted' => 0, 'content:LIKE' => '%data-yext-field%']);
$pagine = []; $campi = 0;
foreach ($modx->getIterator(modResource::class, $q) as $r) {
    [$nuovo, $n] = $converti((string) $r->get('content'));
    $resta = substr_count($nuovo, 'data-yext-field');
    preg_match_all('/data-yext-field="(\w+)"/', (string) $r->get('content'), $m);
    echo sprintf("%-4d %-55s %2d campi (%s)%s\n", $r->get('id'), $r->get('uri'), $n, implode(',', array_unique($m[1])),
        $resta ? "  ATTENZIONE: $resta campi Yext non riconosciuti, restano" : '');
    $pagine[] = [$r, $nuovo]; $campi += $n;
}
echo "== " . count($pagine) . " pagine, $campi campi (template " . implode(',', $tplNomi) . ")\n";
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
echo "   Ripristino: php scripts/deploy/aa-yext-contenuti.php /www --ripristina=$bk/ripristino.json --scrivi\n";
