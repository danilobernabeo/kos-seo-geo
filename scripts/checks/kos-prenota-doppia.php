<?php
/**
 * Barre di prenotazione nelle pagine: vecchia (chunk prenotaOnline*, snippet prenotaOnline, mostrata solo se la TV
 * istanza è compilata) e nuova (snippet barraPrenotazione*). Segnala le pagine che mostrano entrambe. SOLA LETTURA.
 * Uso: php scripts/checks/kos-prenota-doppia.php /www
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if ($webroot === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-prenota-doppia.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;
$tv = $db->query("SELECT id FROM {$p}site_tmplvars WHERE name = 'istanza'")->fetchColumn();
$ist = $db->prepare("SELECT value FROM {$p}site_tmplvar_contentvalues WHERE tmplvarid = ? AND contentid = ?");
$tpl = [];
foreach ($db->query("SELECT id, templatename, content FROM {$p}site_templates")->fetchAll(PDO::FETCH_ASSOC) as $t) $tpl[(int) $t['id']] = $t;
$q = $db->query("SELECT id, uri, template, published, content FROM {$p}site_content WHERE deleted = 0
                 AND (content LIKE '%prenotaOnline%' OR content LIKE '%barraPrenotazione%')");
$righe = [];
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $c = (string) $r['content'];
    $t = $tpl[(int) $r['template']] ?? ['templatename' => '-', 'content' => ''];
    preg_match_all('/\[\[\$(prenotaOnline[\w-]*)|\[\[!?(prenotaOnline\w*)/', $c, $m);
    $vecchia = array_values(array_unique(array_filter(array_merge($m[1], $m[2]))));
    if (!$vecchia || str_contains($c, 'window.CD_CONFIG') || preg_match('/prenotaOnline\s*:/', $c)) {
        if (!preg_match('/barraPrenotazione/i', $c)) continue;
    }
    preg_match_all('/\[\[!?(barraPrenotazione\w*)/i', $c . "\n" . $t['content'], $n);
    $nuova = array_values(array_unique($n[1]));
    $ist->execute([$tv, $r['id']]);
    $i = trim((string) $ist->fetchColumn());
    $condizionata = (bool) preg_match('/\[\[\*istanza:notempty=`\[\[\$prenotaOnline/', $c);
    $vecchiaVisibile = $vecchia && (!$condizionata || $i !== '');
    $righe[] = [$r['id'], $t['templatename'], $r['uri'], $r['published'] ? '' : ' (non pubblicata)',
                implode(',', $vecchia) ?: '-', $i === '' ? 'vuota' : "«$i»", $vecchiaVisibile ? 'SÌ' : 'no',
                implode(',', $nuova) ?: '-', $vecchiaVisibile && $nuova ? 'DOPPIA' : ''];
}
usort($righe, fn($a, $b) => [$b[8], $a[1], $a[2]] <=> [$a[8], $b[1], $b[2]]);
printf("%-5s %-17s %-62s %-26s %-14s %-9s %-34s %s\n", 'id', 'template', 'pagina', 'barra vecchia', 'istanza', 'visibile', 'barra nuova', '');
foreach ($righe as $x) printf("%-5s %-17s %-62s %-26s %-14s %-9s %-34s %s\n", $x[0], $x[1], '/' . $x[2] . $x[3], $x[4], mb_substr($x[5], 0, 14), $x[6], $x[7], $x[8]);
echo "== " . count($righe) . " pagine; con entrambe le barre visibili: " . count(array_filter($righe, fn($x) => $x[8])) . "\n";
