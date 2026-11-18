<?php
/**
 * kos-link-assoluti-contenuto.php — regola del progetto «link interni sempre assoluti»: nel campo content e nelle TV
 * delle risorse indicate trasforma href="it/…" e href="en/…" (relativi al percorso, risolti da <base href>) nel tag
 * MODX della pagina di arrivo, href="[[~id]]" (resta giusto se la pagina cambia indirizzo; link_tag_scheme = abs).
 * Se l'indirizzo non corrisponde a una risorsa pubblicata: href="/it/…". I frammenti di testo «#:~:text=…» si tolgono,
 * le ancore normali (#sezione) restano. Scansione del 18/11: 6 link nell'approfondimento sulla sclerosi multipla.
 *
 * Uso (in SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/kos-link-assoluti-contenuto.php /www it/news-e-media/approfondimenti/sclerosi-multipla-tra-ricerca-cura-e-riabilitazione            # prova
 *   php scripts/deploy/kos-link-assoluti-contenuto.php /www <uri>[,<uri>…] --scrivi
 *   php scripts/deploy/kos-link-assoluti-contenuto.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-link-assoluti/ripristino.json (content prima della modifica). Svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null; $uris = [];
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13);
    elseif (!str_starts_with($a, '--')) $uris = array_filter(array_map(fn($u) => trim($u, '/'), explode(',', $a)));
}
if (!is_file("$webroot/config.core.php") || (!$uris && !$ripFile)) { fwrite(STDERR, "Uso: php kos-link-assoluti-contenuto.php /www <uri,...> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('mgr');

if ($ripFile) {
    foreach (json_decode((string) file_get_contents($ripFile), true) as $v) {
        if (isset($v['tmplvarid'])) {
            $o = $modx->getObject(modTemplateVarResource::class, ['contentid' => (int) $v['id'], 'tmplvarid' => (int) $v['tmplvarid']]);
            echo "  {$v['id']} TV {$v['tmplvarid']}: ripristinata\n";
            if ($scrivi && $o) { $o->set('value', $v['valore']); $o->save(); }
            continue;
        }
        $r = $modx->getObject(modResource::class, (int) $v['id']);
        echo "  {$v['id']}: content ripristinato\n";
        if ($scrivi && $r) { $r->set('content', $v['content']); $r->save(); }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo $scrivi ? "== Ripristinato\n" : "== PROVA: aggiungere --scrivi\n";
    exit(0);
}

echo $scrivi ? "== APPLICAZIONE\n" : "== PROVA (nessuna scrittura; aggiungere --scrivi)\n";
$rip = []; $daFare = [];
$converti = function (string $testo, int &$n, array &$righe) use ($modx): string {
    return preg_replace_callback('/\bhref=(["\'])((?:it|en)\/[^"\'#]*)(#[^"\']*)?\1/i', function ($m) use ($modx, &$n, &$righe) {
        $n++;
        $uri = rtrim($m[2], '/');
        $frag = (isset($m[3]) && !str_starts_with($m[3], '#:~:')) ? $m[3] : '';
        $d = $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0, 'published' => 1]);
        $nuovo = $d ? '[[~' . $d->get('id') . ']]' . $frag : '/' . $uri . $frag;
        $righe[] = "      href=\"{$m[2]}" . ($m[3] ?? '') . "\" → href=\"$nuovo\"" . ($d ? '' : '  (pagina non trovata: solo barra)');
        return 'href=' . $m[1] . $nuovo . $m[1];
    }, $testo);
};
foreach ($uris as $uri) {
    $r = $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0]);
    if (!$r) { echo "  ! /$uri non trovata\n"; continue; }
    $id = (int) $r->get('id');
    $n = 0; $righe = [];
    $prima = (string) $r->get('content');
    $dopo = $converti($prima, $n, $righe);
    if ($n) { echo "  $id /$uri · content: $n link\n" . implode("\n", $righe) . "\n"; $rip[] = ['id' => $id, 'content' => $prima]; $daFare[] = [$r, 'content', $dopo]; }
    foreach ($modx->getCollection(modTemplateVarResource::class, ['contentid' => $id]) as $o) {
        $n = 0; $righe = [];
        $v = (string) $o->get('value');
        $nv = $converti($v, $n, $righe);
        if (!$n) continue;
        echo "  $id /$uri · TV {$o->get('tmplvarid')}: $n link\n" . implode("\n", $righe) . "\n";
        $rip[] = ['id' => $id, 'tmplvarid' => (int) $o->get('tmplvarid'), 'valore' => $v];
        $daFare[] = [$o, 'value', $nv];
    }
    if (!$daFare) echo "  = $id /$uri: nessun link relativo né nel contenuto né nelle TV\n";
}
if (!$scrivi) { echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
if (!$daFare) { echo "== Niente da fare\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-link-assoluti';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
foreach ($daFare as [$o, $campo, $val]) { $o->set($campo, $val); $o->save(); }
$modx->cacheManager->refresh();
echo "== Fatto (" . count($daFare) . "). Ripristino: php scripts/deploy/kos-link-assoluti-contenuto.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
