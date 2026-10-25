<?php
/**
 * Cerca un testo nel contenuto delle risorse (e, con --tutto, anche in template, chunk e valori delle TV). SOLA LETTURA.
 * Uso: php scripts/checks/cerca-contenuto.php /www prenotaOnline [--tutto]
 * Il testo si passa senza virgolette, apici o parentesi quadre (la riga di comando via PowerShell e ssh li altera):
 * basta una parola distintiva, es. il nome del chunk.
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
$cerca   = (string) ($argv[2] ?? '');
$tutto   = in_array('--tutto', $argv, true);
if ($webroot === '' || $cerca === '' || !is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php cerca-contenuto.php /www testo [--tutto]\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$p = $modx->getOption('table_prefix', null, 'modx_');
$db = $modx->pdo;
$riga = function (string $t) use ($cerca): string {
    foreach (preg_split('/\R/', $t) as $l) if (stripos($l, $cerca) !== false) return trim(mb_substr(trim($l), 0, 140));
    return '';
};
$st = $db->prepare("SELECT c.id, c.uri, c.published, t.templatename, c.content FROM {$p}site_content c
                    LEFT JOIN {$p}site_templates t ON t.id = c.template WHERE c.deleted = 0 AND c.content LIKE ? ORDER BY t.templatename, c.uri");
$st->execute(['%' . $cerca . '%']);
$r = $st->fetchAll(PDO::FETCH_ASSOC);
echo "== Contenuto delle risorse: " . count($r) . " con «$cerca»\n";
foreach ($r as $x) printf("  %5d  %-18s %s/%s%s\n        %s\n", $x['id'], $x['templatename'] ?? '-', '', $x['uri'],
    $x['published'] ? '' : '  (non pubblicata)', $riga((string) $x['content']));
if ($tutto) {
    foreach (['template' => "SELECT templatename n, content t FROM {$p}site_templates WHERE content LIKE ?",
              'chunk' => "SELECT name n, snippet t FROM {$p}site_htmlsnippets WHERE snippet LIKE ?",
              'TV' => "SELECT CONCAT(v.contentid, ' ', tv.name) n, v.value t FROM {$p}site_tmplvar_contentvalues v JOIN {$p}site_tmplvars tv ON tv.id = v.tmplvarid WHERE v.value LIKE ?"] as $tipo => $sql) {
        $s = $db->prepare($sql);
        $s->execute(['%' . $cerca . '%']);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $x) echo "  $tipo {$x['n']}: " . $riga((string) $x['t']) . "\n";
    }
}
