<?php
/**
 * installa-elementi.php — installa su un sito MODX 3 gli elementi e le impostazioni
 * elencati in un manifest JSON del repository.
 *
 * Uso (in SSH sul cloud, dalla cartella in cui è stato estratto l'archivio del repository):
 *   php scripts/deploy/installa-elementi.php /www anniazzurri/deploy/aa-8.json            # prova: non scrive
 *   php scripts/deploy/installa-elementi.php /www anniazzurri/deploy/aa-8.json --scrivi   # applica
 *   ... --forza   installa anche se l'elemento sul sito è cambiato rispetto alla baseline
 *
 * Per ogni elemento:
 *   - se esiste, confronta il contenuto attuale con la baseline del repository e con l'ultima
 *     versione installata da questo script (registro ~/backup-kos-seo-geo/installati.json):
 *     se è diverso da entrambe qualcuno l'ha modificato a mano → si ferma (salvo --forza);
 *   - salva il contenuto attuale in ~/backup-kos-seo-geo/<data>/ prima di sovrascriverlo;
 *   - crea l'elemento se non esiste.
 * TV: chiave "tvs" con name, caption, description, type (text, textarea, number...), category, templates (nomi),
 * eventuali elements, default_text, input_properties. Una TV nuova si crea e si collega ai template; di una TV
 * esistente si aggiornano solo i campi diversi e si aggiungono i collegamenti mancanti (mai tolti). Il ripristino
 * elimina le TV create, dopo averne salvato i valori in <backup>/tv-<nome>.json.
 * File (librerie PHP, fogli di stile): chiave "files" con "file" (nel repository), "dest" (relativo a /www),
 * "baseline" facoltativa; il file attuale va nel backup prima di essere sostituito.
 * Alla fine svuota la cache e scrive ~/backup-kos-seo-geo/<data>/ripristino.json: per tornare indietro
 *   php scripts/deploy/installa-elementi.php /www ~/backup-kos-seo-geo/<data>/ripristino.json --scrivi
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Solo da riga di comando.\n"); exit(1); }
$webroot  = rtrim($argv[1] ?? '', '/');
$manifest = $argv[2] ?? '';
$scrivi   = in_array('--scrivi', $argv, true);
$forza    = in_array('--forza', $argv, true);
if ($webroot === '' || !is_file($manifest)) {
    fwrite(STDERR, "Uso: php installa-elementi.php /www <manifest.json> [--scrivi] [--forza]\n"); exit(1);
}
$base = getcwd();
$m = json_decode((string) file_get_contents($manifest), true);
if (!is_array($m)) { fwrite(STDERR, "Manifest non valido.\n"); exit(1); }

require $webroot . '/config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');

$CLASSI = [
    'snippet' => [\MODX\Revolution\modSnippet::class, 'name'],
    'chunk'   => [\MODX\Revolution\modChunk::class, 'name'],
    'plugin'  => [\MODX\Revolution\modPlugin::class, 'name'],
    'template'=> [\MODX\Revolution\modTemplate::class, 'templatename'],
];
$norm = static function (string $s, string $tipo): string {
    $s = str_replace("\r\n", "\n", $s);
    if ($tipo === 'snippet' || $tipo === 'plugin') {
        $s = preg_replace('/^\s*<\?php\s*/', '', $s);
        $s = preg_replace('/\?>\s*$/', '', $s);
    }
    return trim($s);
};
$leggi = static function (string $rel) use ($base): string {
    $p = $rel[0] === '/' ? $rel : $base . '/' . $rel;
    if (!is_file($p)) { fwrite(STDERR, "File mancante: $rel\n"); exit(1); }
    return (string) file_get_contents($p);
};

$stamp  = date('Ymd-His');
// Backup fuori dalla cartella dell'archivio, che viene sostituita a ogni installazione.
$bkRoot = rtrim((string) (getenv('HOME') ?: $base), '/') . '/backup-kos-seo-geo';
$bkDir  = $bkRoot . '/' . $stamp;
// Registro di ciò che questo script ha installato (impronta del contenuto per elemento):
// un elemento uguale all'ultima versione installata da noi non è una modifica di terzi.
$regFile  = $bkRoot . '/installati.json';
$registro = is_file($regFile) ? (json_decode((string) file_get_contents($regFile), true) ?: []) : [];
$ripristino = ['nome' => 'ripristino ' . $stamp, 'settings' => [], 'tvs' => [], 'elements' => [], 'files' => []];
$errori = 0;
echo ($scrivi ? "== APPLICAZIONE" : "== PROVA (nessuna scrittura; aggiungere --scrivi)") . " — " . ($m['nome'] ?? $manifest) . "\n";

/* ---------- impostazioni di sistema ---------- */
foreach ($m['settings'] ?? [] as $s) {
    $obj = $modx->getObject(\MODX\Revolution\modSystemSetting::class, ['key' => $s['key']]);
    if (!empty($s['elimina'])) {                       // usato dal manifest di ripristino
        echo "  - impostazione {$s['key']}" . ($obj ? '' : ' (già assente)') . "\n";
        if ($scrivi && $obj) $obj->remove();
        continue;
    }
    $att = $obj ? (string) $obj->get('value') : null;
    if ($att === (string) $s['value']) { echo "  = impostazione {$s['key']} già corretta\n"; continue; }
    echo "  " . ($obj ? "~" : "+") . " impostazione {$s['key']}: " . ($att === null ? '(nuova)' : "'$att'") . " → '{$s['value']}'\n";
    $ripristino['settings'][] = ['key' => $s['key'], 'value' => $att, 'elimina' => $att === null];
    if ($scrivi) {
        if (!$obj) {
            $obj = $modx->newObject(\MODX\Revolution\modSystemSetting::class);
            $obj->set('key', $s['key']);
            $obj->set('xtype', 'textfield');
            $obj->set('namespace', 'core');
            $obj->set('area', $s['area'] ?? 'aa');
        }
        $obj->set('value', (string) $s['value']);
        if (!$obj->save()) { echo "    ! salvataggio fallito\n"; $errori++; }
    }
}

/* ---------- TV ---------- */
$CLS_TV  = \MODX\Revolution\modTemplateVar::class;
$CLS_TVT = \MODX\Revolution\modTemplateVarTemplate::class;
$CLS_TVR = \MODX\Revolution\modTemplateVarResource::class;
$CAMPI_TV = ['caption', 'description', 'type', 'elements', 'default_text'];
foreach ($m['tvs'] ?? [] as $t) {
    $tv = $modx->getObject($CLS_TV, ['name' => $t['name']]);
    if (!empty($t['elimina'])) {                       // usato dal manifest di ripristino
        $n = $tv ? $modx->getCount($CLS_TVR, ['tmplvarid' => $tv->get('id')]) : 0;
        echo "  - TV {$t['name']}" . ($tv ? " ($n valori salvati nel backup)" : ' (già assente)') . "\n";
        if ($scrivi && $tv) {
            $valori = [];
            foreach ($modx->getIterator($CLS_TVR, ['tmplvarid' => $tv->get('id')]) as $v) $valori[(int) $v->get('contentid')] = $v->get('value');
            if (!is_dir($bkDir)) mkdir($bkDir, 0775, true);
            file_put_contents("$bkDir/tv-{$t['name']}.json", json_encode($valori, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $tv->remove();
        }
        continue;
    }
    if (!empty($t['ripristina']) && $tv) {             // ripristino dei campi di una TV che esisteva già
        echo "  ~ TV {$t['name']}: campi originali\n";
        if ($scrivi) { foreach ($t['ripristina'] as $k => $v) $tv->set($k, $v); $tv->save(); }
        continue;
    }
    $catId = 0;
    if (!empty($t['category'])) {
        $cat = $modx->getObject(\MODX\Revolution\modCategory::class, ['category' => $t['category']]);
        if (!$cat && $scrivi) { $cat = $modx->newObject(\MODX\Revolution\modCategory::class); $cat->set('category', $t['category']); $cat->save(); }
        $catId = $cat ? (int) $cat->get('id') : 0;
    }
    if (!$tv) {
        echo "  + TV {$t['name']} (nuova: {$t['type']}, " . implode(', ', $t['templates'] ?? []) . ")\n";
        $ripristino['tvs'][] = ['name' => $t['name'], 'elimina' => true];
        if ($scrivi) {
            $tv = $modx->newObject($CLS_TV);
            $tv->fromArray(['name' => $t['name'], 'category' => $catId, 'display' => 'default', 'rank' => (int) ($t['rank'] ?? 0)]
                + array_intersect_key($t + array_fill_keys($CAMPI_TV, ''), array_flip($CAMPI_TV)));
            $tv->set('input_properties', $t['input_properties'] ?? ['allowBlank' => 'true']);
            if (!$tv->save()) { echo "    ! salvataggio fallito\n"; $errori++; continue; }
        }
    } else {
        $diversi = [];
        foreach ($CAMPI_TV as $k) if (array_key_exists($k, $t) && (string) $tv->get($k) !== (string) $t[$k]) $diversi[$k] = $tv->get($k);
        if ($diversi) {
            echo "  ~ TV {$t['name']}: " . implode(', ', array_keys($diversi)) . "\n";
            $ripristino['tvs'][] = ['name' => $t['name'], 'ripristina' => $diversi];
            if ($scrivi) { foreach ($diversi as $k => $_) $tv->set($k, $t[$k]); $tv->save(); }
        } else {
            echo "  = TV {$t['name']} già presente\n";
        }
    }
    foreach ($t['templates'] ?? [] as $nomeTpl) {
        $tpl = $modx->getObject(\MODX\Revolution\modTemplate::class, ['templatename' => $nomeTpl]);
        if (!$tpl) { echo "    ! template $nomeTpl non trovato\n"; $errori++; continue; }
        if ($tv && $tv->get('id') && $modx->getObject($CLS_TVT, ['tmplvarid' => $tv->get('id'), 'templateid' => $tpl->get('id')])) continue;
        echo "    + collegata al template $nomeTpl\n";
        if ($scrivi && $tv) {
            $l = $modx->newObject($CLS_TVT);
            $l->fromArray(['tmplvarid' => $tv->get('id'), 'templateid' => $tpl->get('id'), 'rank' => (int) ($t['rank'] ?? 0)], '', true, true);
            $l->save();
        }
    }
}

/* ---------- elementi ---------- */
foreach ($m['elements'] ?? [] as $e) {
    [$cls, $campo] = $CLASSI[$e['type']] ?? [null, null];
    if (!$cls) { echo "  ! tipo sconosciuto {$e['type']}\n"; $errori++; continue; }
    $obj = $modx->getObject($cls, [$campo => $e['name']]);
    $etichetta = "{$e['type']} {$e['name']}";
    if (!empty($e['elimina'])) {                       // usato dal manifest di ripristino
        echo "  - $etichetta" . ($obj ? '' : ' (già assente)') . "\n";
        if ($scrivi && $obj) $obj->remove();
        continue;
    }
    $nuovo = $norm($leggi($e['file']), $e['type']);

    // Proprietà predefinite (es. &prefisso della libreria comune): si confrontano a parte.
    $propOk = true;
    if ($obj && isset($e['properties'])) {
        $att = [];
        foreach ((array) $obj->getProperties() as $k => $v) $att[$k] = is_array($v) ? ($v['value'] ?? '') : $v;
        foreach ($e['properties'] as $k => $v) if ((string) ($att[$k] ?? '') !== (string) $v) $propOk = false;
    }
    if ($obj) {
        $att = $norm((string) $obj->getContent(), $e['type']);
        if ($att === $nuovo && !isset($e['disabled']) && $propOk) {
            echo "  = $etichetta già aggiornato\n";
            $registro[$e['type'] . ':' . $e['name']] = sha1($nuovo);
            continue;
        }
        if (!empty($e['baseline'])) {
            $bl = $norm($leggi($e['baseline']), $e['type']);
            $nostra = ($registro[$e['type'] . ':' . $e['name']] ?? null) === sha1($att);
            if ($att !== $bl && $att !== $nuovo && !$nostra) {
                echo "  ! $etichetta: sul sito è diverso dalla baseline (modificato dopo l'istantanea)" . ($forza ? " — procedo per --forza\n" : " — FERMO. Integrare la modifica nel repository o usare --forza\n");
                if (!$forza) { $errori++; continue; }
            }
        }
        echo "  ~ $etichetta" . ($att === $nuovo ? '' : ' (contenuto)') . (isset($e['disabled']) ? " (disattivato → " . ($e['disabled'] ? 'sì' : 'no') . ")" : '') . "\n";
        $rel = $bkDir . '/' . $e['type'] . 's/' . $e['name'] . ($e['type'] === 'chunk' || $e['type'] === 'template' ? '.html' : '.php');
        $ripristino['elements'][] = ['type' => $e['type'], 'name' => $e['name'], 'file' => $rel] + (isset($e['disabled']) ? ['disabled' => (bool) $obj->get('disabled')] : []);
        if ($scrivi) {
            if (!is_dir(dirname($rel))) mkdir(dirname($rel), 0775, true);
            file_put_contents($rel, (string) $obj->getContent());
        }
    } else {
        echo "  + $etichetta (nuovo)\n";
        $ripristino['elements'][] = ['type' => $e['type'], 'name' => $e['name'], 'elimina' => true];
        if ($scrivi) {
            $obj = $modx->newObject($cls);
            $obj->set($campo, $e['name']);
            if (!empty($e['description'])) $obj->set('description', $e['description']);
        }
    }
    if ($scrivi) {
        $obj->setContent($nuovo);
        if (isset($e['disabled'])) $obj->set('disabled', (bool) $e['disabled']);
        if (isset($e['properties'])) $obj->setProperties($e['properties'], true);
        if (!$obj->save()) { echo "    ! salvataggio fallito\n"; $errori++; }
        else $registro[$e['type'] . ':' . $e['name']] = sha1($nuovo);
    }
    // Eventi dei plugin (solo aggiunta: quelli già collegati restano).
    if ($e['type'] === 'plugin' && !empty($e['events'])) {
        foreach ($e['events'] as $ev) {
            $pid = $obj && $obj->get('id') ? (int) $obj->get('id') : 0;
            $c = $pid ? $modx->getObject(\MODX\Revolution\modPluginEvent::class, ['pluginid' => $pid, 'event' => $ev]) : null;
            if ($c) continue;
            echo "    + evento $ev\n";
            if ($scrivi && $pid) {
                $c = $modx->newObject(\MODX\Revolution\modPluginEvent::class);
                $c->fromArray(['pluginid' => $pid, 'event' => $ev, 'priority' => 0, 'propertyset' => 0], '', true, true);
                if (!$c->save()) { echo "    ! collegamento evento fallito\n"; $errori++; }
            }
        }
    }
}

/* ---------- file (librerie, fogli di stile): "files": [{"file": "shared/lib/X.php", "dest": "core/components/x/X.php"}] ---------- */
foreach ($m['files'] ?? [] as $f) {
    $dest = $webroot . '/' . ltrim((string) $f['dest'], '/');
    if (str_contains((string) $f['dest'], '..')) { echo "  ! destinazione non valida: {$f['dest']}\n"; $errori++; continue; }
    $chiave = 'file:' . ltrim((string) $f['dest'], '/');
    if (!empty($f['elimina'])) {                       // usato dal manifest di ripristino
        echo "  - file {$f['dest']}" . (is_file($dest) ? '' : ' (già assente)') . "\n";
        if ($scrivi && is_file($dest)) { unlink($dest); unset($registro[$chiave]); }
        continue;
    }
    $nuovo = $leggi($f['file']);
    $att = is_file($dest) ? (string) file_get_contents($dest) : null;
    $nl = static fn(?string $x) => $x === null ? null : rtrim(str_replace("\r\n", "\n", $x));   // fine riga Windows/Unix
    if ($nl($att) === $nl($nuovo)) { echo "  = file {$f['dest']} già aggiornato\n"; $registro[$chiave] = sha1($nuovo); continue; }
    if ($att !== null && !empty($f['baseline'])) {
        $bl = $leggi($f['baseline']);
        $nostra = ($registro[$chiave] ?? null) === sha1($att);
        if ($nl($att) !== $nl($bl) && !$nostra) {
            echo "  ! file {$f['dest']}: sul sito è diverso dalla baseline" . ($forza ? " — procedo per --forza\n" : " — FERMO. Integrare la modifica nel repository o usare --forza\n");
            if (!$forza) { $errori++; continue; }
        }
    }
    echo "  " . ($att === null ? '+' : '~') . " file {$f['dest']}" . ($att === null ? ' (nuovo)' : '') . "\n";
    $copia = $bkDir . '/files/' . ltrim((string) $f['dest'], '/');
    $ripristino['files'][] = $att === null ? ['dest' => $f['dest'], 'elimina' => true] : ['dest' => $f['dest'], 'file' => $copia];
    if ($scrivi) {
        if ($att !== null) { if (!is_dir(dirname($copia))) mkdir(dirname($copia), 0775, true); file_put_contents($copia, $att); }
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        if (file_put_contents($dest, $nuovo) === false) { echo "    ! scrittura fallita\n"; $errori++; }
        else { @chmod($dest, 0644); $registro[$chiave] = sha1($nuovo); }
    }
}

if ($scrivi) {
    if (!is_dir($bkRoot)) mkdir($bkRoot, 0775, true);
    file_put_contents($regFile, json_encode($registro, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if (!is_dir($bkDir)) mkdir($bkDir, 0775, true);
    file_put_contents($bkDir . '/ripristino.json', json_encode($ripristino, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $modx->cacheManager->refresh();
    echo "== Cache svuotata. Backup e manifest di ripristino in $bkDir/\n";
}
echo $errori ? "== $errori problemi: vedi sopra.\n" : "== Nessun problema.\n";
exit($errori ? 2 : 0);
