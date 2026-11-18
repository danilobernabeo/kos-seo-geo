<?php
/**
 * aa-contenuti-chiusura.php — correzioni nei contenuti di anniazzurri.it dalla scansione Screaming Frog del 18/11
 * (rapporto docs/revisioni/screaming-frog-20261118-aa.md, rilievi R3, R4, R6, R8, N2, N6 e minori), da un file di regole
 * JSON, su content e TV di tutte le risorse non cestinate e, per i soli commenti, sui chunk non statici.
 *
 * Regole (chiavi facoltative del JSON):
 *   "redirect": {"vecchio-uri": "uri-di-arrivo" | ""}  link verso un indirizzo che reindirizza → href="[[~id]]" della pagina
 *       di arrivo. Valore "" = il vecchio uri è una risorsa contenitore FirstChildRedirect: arrivo = prima figlia
 *       pubblicata (come lo snippet aaLinkArrivo). Riconosce href="/x", "x", "https://anniazzurri.it/x" (con o senza
 *       barra finale) e href="[[~id del vecchio]]".
 *   "relativi": true          href relativi al percorso (senza schema, barra, #, tag) → con la barra iniziale.
 *   "regex": [[modello, sostituzione], ...]   sostituzioni con espressione regolare (PCRE, delimitatore incluso).
 *   "tel_spazi": true         href="tel:035 01151" o "tel:02210861" → href="tel:+3903501151" (numeri nazionali senza +39).
 *   "email_chiaro": {"uri": "regex sugli uri", "locali": "regex della parte locale"}  email generiche nel contenuto
 *       fuori dall'offuscamento di EMO: «@» scritta &#64; e marcatori <!-- emo-exclude -->, come lo snippet aaContatto.
 *   "commenti": "regex"       commenti HTML che contengono il modello → commento MODX [[- …]] (anche nei chunk).
 *   "per_uri": {"uri": [[modello, sostituzione], ...]}  sostituzioni regex sul content di una pagina sola.
 *   "mostra_id": ["uri", ...]  stampa id e stato delle pagine (per i passi successivi).
 *
 * Uso (in SSH, dalla cartella dell'archivio):
 *   php scripts/deploy/aa-contenuti-chiusura.php /www anniazzurri/deploy/aa-contenuti-20261118.json            # prova
 *   php scripts/deploy/aa-contenuti-chiusura.php /www anniazzurri/deploy/aa-contenuti-20261118.json --scrivi
 *   php scripts/deploy/aa-contenuti-chiusura.php /www --ripristina=<ripristino.json> [--scrivi]
 * Backup: ~/backup-kos-seo-geo/<data>-aa-contenuti/ripristino.json (valori prima della modifica). Svuota la cache.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$ripFile = null; $file = '';
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--ripristina=')) $ripFile = substr($a, 13); elseif (!str_starts_with($a, '--')) $file = $a;
}
if (!is_file("$webroot/config.core.php") || (!is_file($file) && !$ripFile)) { fwrite(STDERR, "Uso: php aa-contenuti-chiusura.php /www <regole.json> [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVarResource, modChunk};
$modx = modX::getInstance();
$modx->initialize('mgr');

if ($ripFile) {
    $n = 0;
    foreach (json_decode((string) file_get_contents($ripFile), true) as $v) {
        $o = match ($v['tipo']) {
            'tv' => $modx->getObject(modTemplateVarResource::class, ['contentid' => $v['id'], 'tmplvarid' => $v['tv']]),
            'chunk' => $modx->getObject(modChunk::class, $v['id']),
            default => $modx->getObject(modResource::class, $v['id']),
        };
        $campo = ['tv' => 'value', 'chunk' => 'snippet'][$v['tipo']] ?? 'content';
        echo "  ← {$v['tipo']} {$v['id']}" . (isset($v['tv']) ? " TV {$v['tv']}" : '') . "\n";
        if ($scrivi && $o) { $o->set($campo, $v['valore']); $o->save(); $n++; }
    }
    if ($scrivi) $modx->cacheManager->refresh();
    echo $scrivi ? "== Ripristinati $n valori\n" : "== PROVA: aggiungere --scrivi\n";
    exit(0);
}

$R = json_decode((string) file_get_contents($file), true);
if (!is_array($R)) { fwrite(STDERR, "Regole non valide\n"); exit(1); }
echo ($scrivi ? '== APPLICAZIONE' : '== PROVA (nessuna scrittura; aggiungere --scrivi)') . ' — ' . ($R['nome'] ?? $file) . "\n";

$perUri = function (string $uri) use ($modx) {
    $uri = trim($uri, '/');
    return $modx->getObject(modResource::class, ['uri:IN' => [$uri, "$uri/"], 'deleted' => 0]);
};
foreach ($R['mostra_id'] ?? [] as $u) {
    $r = $perUri($u);
    echo $r ? "  · /$u = id {$r->get('id')}, " . ($r->get('published') ? 'pubblicata' : 'non pubblicata') . ", classe {$r->get('class_key')}\n" : "  · /$u: non trovata\n";
}
$arrivo = function (int $id) use ($modx): int {
    for ($g = 0; $g < 5; $g++) {
        $r = $modx->getObject(modResource::class, $id);
        if (!$r || stripos((string) $r->get('content'), 'FirstChildRedirect') === false) break;
        $c = $modx->newQuery(modResource::class);
        $c->where(['parent' => $id, 'published' => 1, 'deleted' => 0]);
        $c->sortby('menuindex', 'ASC'); $c->limit(1);
        $f = $modx->getObject(modResource::class, $c);
        if (!$f) break;
        $id = (int) $f->get('id');
    }
    return $id;
};

/* regole di redirect: [modello, sostituzione, etichetta] */
$regole = [];
foreach ($R['redirect'] ?? [] as $vecchio => $nuovo) {
    $vecchio = trim($vecchio, '/');
    $rv = $perUri($vecchio);
    if ($nuovo !== '') { $rn = $perUri($nuovo); $dest = $rn ? (int) $rn->get('id') : 0; }
    else $dest = $rv ? $arrivo((int) $rv->get('id')) : 0;
    if (!$dest || ($rv && $dest === (int) $rv->get('id'))) { echo "  ! /$vecchio: pagina di arrivo non trovata, regola saltata\n"; continue; }
    $q = preg_quote($vecchio, '#');
    $regole[] = ['#href=(["\'])(?:https?://(?:www\.)?anniazzurri\.it)?/?' . $q . '/?\1#i', 'href=$1[[~' . $dest . ']]$1', "/$vecchio → [[~$dest]]"];
    if ($rv) $regole[] = ['#href=(["\'])\[\[~' . (int) $rv->get('id') . '\]\]\1#', 'href=$1[[~' . $dest . ']]$1', "[[~{$rv->get('id')}]] → [[~$dest]]"];
}
foreach ($R['regex'] ?? [] as [$m, $s]) $regole[] = [$m, $s, $m];
if (!empty($R['relativi'])) $regole[] = ['#href=(["\'])(?!https?:|/|\#|mailto:|tel:|telto:|javascript:|\[\[|\{|www\.|[a-z0-9-]+\.(?:it|com|org|net|eu|gov|info)(?:/|\1))([a-z0-9][^"\']*)\1#i', 'href=$1/$2$1', 'link relativi → /…'];

$applica = function (string $testo, string $uri, bool $soloCommenti) use ($R, $regole): array {
    $note = [];
    $conta = function ($m, $s, $t, $et) use (&$note) {
        $n = 0; $out = preg_replace($m, $s, $t, -1, $n);
        if ($n) $note[] = "$et ($n)";
        return $out ?? $t;
    };
    if (!$soloCommenti) {
        foreach ($regole as [$m, $s, $et]) $testo = $conta($m, $s, $testo, $et);
        if (!empty($R['tel_spazi'])) {
            $n = 0;
            $testo = preg_replace_callback('#href=(["\'])tel:\s*(0[0-9 ]*[0-9])\1#', function ($m) use (&$n) {
                $n++; return 'href=' . $m[1] . 'tel:+39' . str_replace(' ', '', $m[2]) . $m[1];
            }, $testo);
            if ($n) $note[] = "tel senza prefisso → +39 ($n)";
        }
        if (!empty($R['email_chiaro']) && preg_match('#' . $R['email_chiaro']['uri'] . '#', $uri)) {
            $loc = $R['email_chiaro']['locali']; $n = 0;
            // ancore con l'email (mailto e testo) e poi email nude nel testo
            $testo = preg_replace_callback('#(?<!emo-exclude -->)<a\b[^>]*href=(["\'])mailto:(' . $loc . ')@anniazzurri\.it\1[^>]*>.*?</a>#is', function ($m) use (&$n) {
                $n++; return '<!-- emo-exclude -->' . preg_replace('#(' . preg_quote($m[2], '#') . ')@#i', '$1&#64;', $m[0]) . '<!-- /emo-exclude -->';
            }, $testo);
            $testo = preg_replace_callback('#(?<![\w.&;:-])(' . $loc . ')@anniazzurri\.it#i', function ($m) use (&$n) {
                $n++; return '<!-- emo-exclude -->' . $m[1] . '&#64;anniazzurri.it<!-- /emo-exclude -->';
            }, $testo);
            if ($n) $note[] = "email in chiaro ($n)";
        }
        foreach ($R['per_uri'][$uri] ?? [] as [$m, $s]) $testo = $conta($m, $s, $testo, "per pagina: $m");
    }
    if (!empty($R['commenti'])) {
        $n = 0;
        $testo = preg_replace_callback('#<!--(.*?)-->#s', function ($m) use ($R, &$n) {
            if (!preg_match('#' . $R['commenti'] . '#i', $m[1]) || str_contains($m[1], ']]')) return $m[0];
            $n++; return '[[- ' . trim((string) preg_replace('/\s+/', ' ', $m[1])) . ' ]]';
        }, $testo);
        if ($n) $note[] = "commenti → [[- ]] ($n)";
    }
    return [$testo, $note];
};

$rip = []; $fare = [];
$tutte = $modx->getIterator(modResource::class, ['deleted' => 0]);
foreach ($tutte as $r) {
    $id = (int) $r->get('id'); $uri = rtrim((string) $r->get('uri'), '/');
    [$nuovo, $note] = $applica((string) $r->get('content'), $uri, false);
    if ($note) { echo "  $id /$uri · content: " . implode('; ', $note) . "\n"; $rip[] = ['tipo' => 'content', 'id' => $id, 'valore' => $r->get('content')]; $fare[] = [$r, 'content', $nuovo]; }
}
foreach ($modx->getIterator(modTemplateVarResource::class) as $o) {
    $v = (string) $o->get('value');
    if ($v === '' || !preg_match('/href|@|<!--/', $v)) continue;
    $r = $modx->getObject(modResource::class, ['id' => $o->get('contentid'), 'deleted' => 0]);
    if (!$r) continue;
    $uri = rtrim((string) $r->get('uri'), '/');
    [$nuovo, $note] = $applica($v, $uri, false);
    if ($note) { echo "  {$r->get('id')} /$uri · TV {$o->get('tmplvarid')}: " . implode('; ', $note) . "\n"; $rip[] = ['tipo' => 'tv', 'id' => (int) $r->get('id'), 'tv' => (int) $o->get('tmplvarid'), 'valore' => $v]; $fare[] = [$o, 'value', $nuovo]; }
}
if (!empty($R['commenti'])) {
    foreach ($modx->getIterator(modChunk::class, ['static' => 0]) as $c) {
        [$nuovo, $note] = $applica((string) $c->get('snippet'), '', true);
        if ($note) { echo "  chunk {$c->get('name')}: " . implode('; ', $note) . "\n"; $rip[] = ['tipo' => 'chunk', 'id' => (int) $c->get('id'), 'valore' => $c->get('snippet')]; $fare[] = [$c, 'snippet', $nuovo]; }
    }
}
echo '== ' . count($fare) . " valori da cambiare\n";
if (!$scrivi || !$fare) { if (!$scrivi) echo "== PROVA: aggiungere --scrivi\n"; exit(0); }
$bk = rtrim((string) (getenv('HOME') ?: '.'), '/') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-aa-contenuti';
mkdir($bk, 0700, true);
file_put_contents("$bk/ripristino.json", json_encode($rip, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
foreach ($fare as [$o, $campo, $val]) { $o->set($campo, $val); $o->save(); }
$modx->cacheManager->refresh();
echo "== Fatto. Ripristino: php scripts/deploy/aa-contenuti-chiusura.php $webroot --ripristina=$bk/ripristino.json --scrivi\n";
