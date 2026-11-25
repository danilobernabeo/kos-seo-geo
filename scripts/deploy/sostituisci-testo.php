<?php
/**
 * sostituisci-testo.php — sostituzioni di testo esatte nei campi di una o più risorse MODX (content, description,
 * introtext, longtitle, pagetitle), da un file di regole nel repository, con prova, backup e ripristino.
 *
 *   {"nome": "...", "risorse": [{"uri": "villamargherita", "campi": ["description", "content"],   (oppure "id": 1)
 *     "sostituzioni": [["tel:+390444043322", "tel:+39044415851"], ["0444 043322", "0444 15851"]]}]}
 * Le sostituzioni si applicano nell'ordine scritto (prima le forme più lunghe).
 * Nel testo nuovo «[[~uri:it/percorso]]» diventa il link MODX «[[~id]]» della risorsa con quell'uri (25/11).
 *
 * Uso (in SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/sostituisci-testo.php /www <regole.json>            # prova: occorrenze per campo
 *   php scripts/deploy/sostituisci-testo.php /www <regole.json> --scrivi
 *   php scripts/deploy/sostituisci-testo.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-testo/ripristino.json (campi prima della modifica). Svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null; $file = '';
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13); elseif (!str_starts_with($a, '--')) $file = $a;
}
if (!is_file("$webroot/config.core.php") || (!is_file($file) && !$ripFile)) { fwrite(STDERR, "Uso: php sostituisci-testo.php /www <regole.json> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource};
$modx = modX::getInstance();
$modx->initialize('mgr');
$ammessi = ['content', 'description', 'introtext', 'longtitle', 'pagetitle'];

if ($ripFile) {
    foreach (json_decode((string) file_get_contents($ripFile), true) as $v) {
        $r = $modx->getObject(modResource::class, (int) $v['id']);
        echo "  {$v['id']}: ripristino di " . implode(', ', array_keys($v['campi'])) . "\n";
        if ($scrivi && $r) { foreach ($v['campi'] as $c => $val) $r->set($c, $val); $r->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo $scrivi ? "== Ripristinato\n" : "== PROVA: aggiungere --scrivi\n";
    exit(0);
}

$d = json_decode((string) file_get_contents($file), true);
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — " . ($d['nome'] ?? $file) . "\n";
$rip = []; $daFare = [];
foreach ($d['risorse'] as $voce) {
    if (isset($voce['id'])) {                                // in alternativa all'uri (es. la home, uri vuoto)
        $r = $modx->getObject(modResource::class, ['id' => (int) $voce['id'], 'deleted' => 0]);
        $uri = $r ? (string) $r->get('uri') : '#' . (int) $voce['id'];
    } else {
        $uri = trim((string) $voce['uri'], '/');
        $r = $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0]);
    }
    if (!$r) { echo "  ! /$uri non trovata — FERMO\n"; exit(2); }
    $prima = []; $cambi = 0;
    foreach ($voce['campi'] as $c) {
        if (!in_array($c, $ammessi, true)) { echo "  ! campo $c non ammesso — FERMO\n"; exit(2); }
        $v = (string) $r->get($c); $nv = $v;
        foreach ($voce['sostituzioni'] as [$da, $a]) {
            // [[~uri:it/percorso]] nel testo nuovo diventa [[~id]] della risorsa (link interni come li scrive la redazione)
            $a = preg_replace_callback('/\[\[~uri:([^\]]+)\]\]/', function ($m) use ($modx) {
                $u = trim($m[1], '/');
                $d = $modx->getObject(modResource::class, ['uri:IN' => [$u, "$u/"], 'deleted' => 0]);
                if (!$d) { echo "  ! [[~uri:$u]]: risorsa non trovata — FERMO\n"; exit(2); }
                return '[[~' . $d->get('id') . ']]';
            }, (string) $a);
            $n = substr_count($nv, $da);
            if ($n) { echo "  {$r->get('id')} /$uri · $c: «$da» → «$a» ($n)\n"; $cambi += $n; }
            $nv = str_replace($da, $a, $nv);
        }
        if ($nv !== $v) { $prima[$c] = $v; $r->set($c, $nv); }
    }
    if (!$cambi) { echo "  = {$r->get('id')} /$uri: nessuna occorrenza\n"; continue; }
    $rip[] = ['id' => (int) $r->get('id'), 'campi' => $prima];
    $daFare[] = $r;
}
if (!$scrivi) { echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
if (!$daFare) { echo "== Niente da fare\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-testo';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
foreach ($daFare as $r) $r->save();
$modx->cacheManager->refresh();
echo "== Fatto (" . count($daFare) . "). Ripristino: php scripts/deploy/sostituisci-testo.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
