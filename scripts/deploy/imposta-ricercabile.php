<?php
/**
 * imposta-ricercabile.php — «Ricercabile» (searchable) di un gruppo di risorse, con prova, backup e ripristino.
 * Su anniazzurri.it «Ricercabile» spento vuol dire fuori dalla sitemap e `noindex, follow` nell'head.
 *
 * Uso:
 *   php scripts/deploy/imposta-ricercabile.php /www --uri-inizia=lrsa- --valore=0            (prova)
 *   php scripts/deploy/imposta-ricercabile.php /www --uri-inizia=lrsa- --valore=0 --scrivi
 *   php scripts/deploy/imposta-ricercabile.php /www --ripristina=<cartella>/ripristino.json --scrivi
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$opt = []; $pos = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $a, $m)) $opt[$m[1]] = $m[2] ?? true; else $pos[] = $a;
}
$webroot = rtrim($pos[0] ?? '', '/');
if (!is_file("$webroot/config.core.php")) exit("Uso: php imposta-ricercabile.php /www --uri-inizia=<prefisso> --valore=0|1 [--scrivi]\n");
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$scrivi = isset($opt['scrivi']);

if (isset($opt['ripristina'])) {
    $r = json_decode((string) @file_get_contents((string) $opt['ripristina']), true);
    if (!is_array($r)) exit("File di ripristino non trovato o non leggibile\n");
    foreach ($r as $id => $v) {
        $o = $modx->getObject(modResource::class, (int) $id);
        echo "  " . ($o ? "#$id /{$o->get('uri')} → $v" : "#$id non trovata") . "\n";
        if ($scrivi && $o) { $o->set('searchable', (int) $v); $o->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    exit($scrivi ? "== Ripristinato, cache svuotata\n" : "== PROVA del ripristino\n");
}

$pref = (string) ($opt['uri-inizia'] ?? '');
$val = (string) ($opt['valore'] ?? '');
if ($pref === '' || !in_array($val, ['0', '1'], true)) exit("Servono --uri-inizia=<prefisso> e --valore=0|1\n");
$backup = []; $n = 0;
echo ($scrivi ? '== APPLICAZIONE' : '== PROVA') . " — Ricercabile = $val per le risorse con uri che inizia per «{$pref}»\n";
foreach ($modx->getIterator(modResource::class, ['uri:LIKE' => $pref . '%', 'deleted' => 0]) as $o) {
    $att = (int) $o->get('searchable');
    printf("  %4d /%-50s %d → %s%s\n", $o->get('id'), $o->get('uri'), $att, $val, $att === (int) $val ? ' (già)' : '');
    if ($att === (int) $val) continue;
    $backup[$o->get('id')] = $att; $n++;
    if ($scrivi) { $o->set('searchable', (int) $val); $o->save(); }
}
if (!$scrivi) exit("== PROVA: $n da cambiare. Rilanciare con --scrivi\n");
$dir = getenv('HOME') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-ricercabile';
@mkdir($dir, 0700, true);
file_put_contents("$dir/ripristino.json", json_encode($backup, JSON_PRETTY_PRINT));
$modx->cacheManager->refresh();
echo "== Fatto ($n), cache svuotata. Ripristino: php scripts/deploy/imposta-ricercabile.php /www --ripristina=$dir/ripristino.json --scrivi\n";
