<?php
/**
 * aa-6-titoli.php — AA 6 (riscontro della verifica dello sprint 2, 23/11/2026): struttura dei titoli delle schede
 * residenza come nella slide 23 dell'audit. Frase introduttiva sotto l'H1 da <p class="h3 text-primary"> a H2; tutti i
 * titoli delle sezioni scendono di un livello (H2 → H3, H3 → H4, H4 → H5); «Contatti» (h5.card-title) diventa
 * paragrafo. La resa grafica non cambia: chi perde il livello prende la classe del livello di prima (.h2, .h3…),
 * salvo le intestazioni con regole proprie (card-header, accordion-header). I card-title prima H3 prendono anche
 * aa6-card-title (regola h3.card-title di custom.css). Le schede caricano custom.css e app.css, le landing lrsa-*,
 * che usano gli stessi chunk, app.css e kos-seo-geo.css: le regole CSS di AA 6 stanno nei fogli che servono a ciascuna.
 *
 * Ambito: risorse non cestinate con template «rsa» (contenuto) e i chunk della singola scheda richiamati dal contenuto
 * ([[$prefisso-[[*alias]]]] → prefisso-<alias>), più podcast-spreaker-casa-anni-azzurri e documents. I chunk comuni
 * (contactBox, form-contatti-hs, tariffa-*, banner, news) non si toccano, salvo contactBox-<alias> per «Contatti».
 * I chunk statici si salvano con l'API di MODX (scrive anche il file statico); il loro nuovo contenuto va anche in
 * <backup>/chunk/<nome>.html, da riportare nel repository (anniazzurri/elements/chunks/).
 *
 * Uso:
 *   php scripts/deploy/aa-6-titoli.php /www                  # prova: conteggi, chunk, esempio
 *   php scripts/deploy/aa-6-titoli.php /www --scrivi
 *   php scripts/deploy/aa-6-titoli.php /www --ripristina=<cartella>/ripristino.json --scrivi
 *   php scripts/deploy/aa-6-titoli.php /www --template=<nome>   # altro template (24/11: centri diurni), stessa regola;
 *       i chunk comuni già convertiti con le schede (podcast-spreaker-casa-anni-azzurri, documents) non si ritoccano
 *   php scripts/deploy/aa-6-titoli.php /www --trova=centro-diurno  # template delle risorse con quel testo nell'uri
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$webroot = rtrim($argv[1] ?? '', '/');
$scrivi = in_array('--scrivi', $argv, true);
$rip = null; $nomeTpl = 'rsa'; $trova = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--ripristina=')) $rip = substr($a, 13);
    if (str_starts_with($a, '--template=')) $nomeTpl = substr($a, 11);
    if (str_starts_with($a, '--trova=')) $trova = substr($a, 8);
}
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php aa-6-titoli.php /www [--scrivi]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
use MODX\Revolution\modResource;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modChunk;
$modx = modX::getInstance();
$modx->initialize('mgr');

if ($trova !== null) {
    foreach ($modx->getIterator(modResource::class, ['uri:LIKE' => '%' . $trova . '%', 'deleted' => 0]) as $r) {
        $t = $r->getOne('Template');
        printf("%5d /%-60s template %s (pubblicata %d)\n", $r->get('id'), $r->get('uri'), $t ? $t->get('templatename') : '-', $r->get('published'));
    }
    exit(0);
}

if ($rip) {
    if (!is_file($rip)) { fwrite(STDERR, "File di ripristino non trovato: $rip\n"); exit(1); }
    $righe = json_decode((string) file_get_contents($rip), true);
    if (!is_array($righe)) { fwrite(STDERR, "File di ripristino non leggibile: $rip\n"); exit(1); }
    foreach ($righe as $r) {
        $o = $r['tipo'] === 'risorsa' ? $modx->getObject(modResource::class, $r['id']) : $modx->getObject(modChunk::class, ['name' => $r['nome']]);
        if (!$o) { echo "  ! non trovato: " . ($r['nome'] ?? $r['id']) . "\n"; continue; }
        if ($scrivi) { $r['tipo'] === 'risorsa' ? $o->set('content', $r['valore']) : $o->setContent($r['valore']); $o->save(); }
    }
    echo ($scrivi ? 'Ripristinati ' : 'Da ripristinare (prova) ') . count($righe) . " elementi\n";
    if ($scrivi) { $modx->cacheManager->refresh(); echo "Cache svuotata.\n"; }
    exit(0);
}

$conta = ['spostati' => 0, 'classe_aggiunta' => 0, 'contatti_p' => 0, 'intro_h2' => 0];
/** Sposta di un livello H2-H4 conservando l'aspetto; «Contatti» h5.card-title → p. */
function trasforma(string $html, bool $contenuto, array &$conta): string {
    $html = preg_replace_callback('#<h5(\s[^>]*class="[^"]*\bcard-title\b[^"]*"[^>]*)>(.*?)</h5>#s', function ($m) use (&$conta) {
        $conta['contatti_p']++;
        $attr = preg_replace('#class="#', 'class="h5 ', $m[1], 1);
        return "<p$attr>{$m[2]}</p>";
    }, $html);
    $html = preg_replace_callback('#<(/?)h([2-4])(\s[^>]*)?>#', function ($m) use (&$conta) {
        $liv = (int) $m[2]; $nuovo = $liv + 1;
        if ($m[1] === '/') return "</h$nuovo>";
        $conta['spostati']++;
        $attr = $m[3] ?? '';
        $protette = '/\b(h[1-6]|display-\d|card-header|accordion-header)\b/';
        if (preg_match('/class="([^"]*)"/', $attr, $c)) {
            if (!preg_match($protette, $c[1])) {
                $agg = ($liv === 3 && str_contains($c[1], 'card-title')) ? 'h3 aa6-card-title' : "h$liv";
                $attr = str_replace('class="' . $c[1] . '"', 'class="' . trim($c[1] . " $agg") . '"', $attr);
                $conta['classe_aggiunta']++;
            }
        } else {
            $attr .= " class=\"h$liv\""; $conta['classe_aggiunta']++;
        }
        return "<h$nuovo$attr>";
    }, $html);
    if ($contenuto) {
        $html = preg_replace_callback('#<p class="h3 text-primary"([^>]*)>(.*?)</p>#s', function ($m) use (&$conta) {
            $conta['intro_h2']++;
            return "<h2 class=\"h3 text-primary\"{$m[1]}>{$m[2]}</h2>";
        }, $html, 1);
    }
    return $html;
}

$tpl = $modx->getObject(modTemplate::class, ['templatename' => $nomeTpl]);
if (!$tpl) { fwrite(STDERR, "template $nomeTpl non trovato\n"); exit(1); }
$risorse = $modx->getCollection(modResource::class, ['template' => $tpl->get('id'), 'deleted' => 0]);
/* I chunk comuni sono stati convertiti una volta sola, con le schede (23/11): non vanno riabbassati. */
$chunkNomi = $nomeTpl === 'rsa' ? ['podcast-spreaker-casa-anni-azzurri' => true, 'documents' => true] : [];
$giaFatti = ['podcast-spreaker-casa-anni-azzurri', 'documents'];
$modifiche = [];
foreach ($risorse as $r) {
    $alias = $r->get('alias'); $prima = (string) $r->get('content');
    preg_match_all('#\[\[\$([A-Za-z0-9_-]*)\[\[\*alias\]\]#', $prima, $m);
    foreach ($m[1] as $pref) if ($nomeTpl === 'rsa' || !in_array($pref . $alias, $giaFatti, true)) $chunkNomi[$pref . $alias] = true;
    $dopo = trasforma($prima, true, $conta);
    if ($dopo !== $prima) $modifiche[] = ['tipo' => 'risorsa', 'id' => $r->get('id'), 'nome' => $r->get('uri'), 'prima' => $prima, 'dopo' => $dopo];
}
$statici = [];
foreach (array_keys($chunkNomi) as $nome) {
    $c = $modx->getObject(modChunk::class, ['name' => $nome]);
    if (!$c) continue;
    $prima = (string) $c->getContent();
    $dopo = trasforma($prima, false, $conta);
    if ($dopo !== $prima) {
        $modifiche[] = ['tipo' => 'chunk', 'nome' => $nome, 'statico' => (bool) $c->get('static'), 'prima' => $prima, 'dopo' => $dopo];
        if ($c->get('static')) $statici[] = $nome;
    }
}
$nr = count(array_filter($modifiche, fn($x) => $x['tipo'] === 'risorsa'));
echo "== " . ($scrivi ? 'APPLICAZIONE' : 'PROVA') . " — AA 6 titoli (slide 23), template $nomeTpl\n";
echo "Risorse con template $nomeTpl: " . count($risorse) . "; contenuti da modificare: $nr\n";
foreach ($modifiche as $x) if ($x['tipo'] === 'chunk') echo "  chunk: {$x['nome']}\n";
foreach ($modifiche as $x) if ($x['tipo'] === 'risorsa' && $nomeTpl !== 'rsa') {
    preg_match_all('#<(h[1-6]|p class="h[1-6][^"]*")[^>]*>#', $x['dopo'], $t); echo "  esempio (/{$x['nome']}): " . implode(' ', array_slice($t[0], 0, 10)) . "\n"; break;
}
echo "Chunk da modificare: " . (count($modifiche) - $nr) . " (statici " . count($statici) . ")\n";
foreach ($conta as $k => $v) echo "  $k: $v\n";
foreach ($modifiche as $x) if ($x['tipo'] === 'risorsa' && str_contains($x['nome'], 'vico-mercati')) {
    preg_match_all('#<(h[1-6]|p class="h[1-6][^"]*")[^>]*>#', $x['dopo'], $t); echo "  esempio (Vico Mercati, contenuto): " . implode(' ', array_slice($t[0], 0, 12)) . "\n";
}
if (!$scrivi) { echo "Prova: nessuna modifica. Rilanciare con --scrivi.\n"; exit(0); }

$dir = getenv('HOME') . '/backup-kos-seo-geo/' . date('Ymd-His') . '-aa-6-titoli' . ($nomeTpl === 'rsa' ? '' : '-' . preg_replace('/[^a-z0-9-]/i', '', $nomeTpl));
@mkdir("$dir/chunk", 0775, true);
file_put_contents("$dir/ripristino.json", json_encode(array_map(fn($x) => ['tipo' => $x['tipo'], 'id' => $x['id'] ?? null, 'nome' => $x['nome'], 'valore' => $x['prima']], $modifiche), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
foreach ($modifiche as $x) {
    if ($x['tipo'] === 'risorsa') {
        $o = $modx->getObject(modResource::class, $x['id']); $o->set('content', $x['dopo']); $o->save();
    } else {
        $o = $modx->getObject(modChunk::class, ['name' => $x['nome']]); $o->setContent($x['dopo']); $o->save();
        file_put_contents("$dir/chunk/{$x['nome']}.html", $x['dopo']);
    }
}
$modx->cacheManager->refresh();
echo "== Fatto. Cache svuotata. Ripristino: $dir/ripristino.json; chunk modificati in $dir/chunk/ (da riportare nel repository)\n";
