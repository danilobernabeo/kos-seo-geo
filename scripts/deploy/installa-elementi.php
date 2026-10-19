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
$ripristino = ['nome' => 'ripristino ' . $stamp, 'settings' => [], 'elements' => []];
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

    if ($obj) {
        $att = $norm((string) $obj->getContent(), $e['type']);
        if ($att === $nuovo && !isset($e['disabled'])) {
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
        if (!$obj->save()) { echo "    ! salvataggio fallito\n"; $errori++; }
        else $registro[$e['type'] . ':' . $e['name']] = sha1($nuovo);
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
