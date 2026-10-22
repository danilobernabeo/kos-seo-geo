<?php
/**
 * KOS-9, KOS-12, KOS-13 — ispezione in sola lettura dell'albero di kosgroup.com,
 * per progettare ente in homepage e briciole (visibili e JSON-LD).
 * Uso:  php kos-albero.php /www > kos-albero.txt
 */
$webroot = rtrim($argv[1] ?? '/www', '/');
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplate, modContext, modChunk};
$modx = modX::getInstance();
$modx->initialize('mgr');
$t = fn($s) => str_replace(["\n", "\t"], ' ', (string) $s);

echo "== IMPOSTAZIONI\n";
foreach (['site_url', 'site_start', 'error_page', 'link_tag_scheme', 'use_alias_path', 'container_suffix', 'cultureKey', 'site_name'] as $k) {
    echo "  $k = " . $modx->getOption($k) . "\n";
}
echo "  contesti: " . implode(', ', array_map(fn($c) => $c->get('key'), $modx->getCollection(modContext::class))) . "\n";

$tpl = [];
foreach ($modx->getIterator(modTemplate::class) as $x) $tpl[$x->get('id')] = $x;
$pub = ['published' => 1, 'deleted' => 0, 'context_key' => 'web'];

echo "\n== TEMPLATE: pagine pubblicate, briciole e script\n";
printf("  %-4s %-26s %5s  %-9s %-9s %-8s %s\n", 'id', 'nome', 'pag.', 'chunk bc', 'mybc tpl', 'scripts', 'mybc nel contenuto');
foreach ($tpl as $id => $x) {
    $n = $modx->getCount(modResource::class, $pub + ['template' => $id]);
    if (!$n) continue;
    $c = (string) $x->get('content');
    $my = $modx->getCount(modResource::class, $pub + ['template' => $id, 'content:LIKE' => '%myBreadcrumb%']);
    printf("  %-4d %-26s %5d  %-9s %-9s %-8s %d\n", $id, $x->get('templatename'), $n,
        strpos($c, '$breadcrumb') !== false ? 'sì' : '-', strpos($c, 'myBreadcrumb') !== false ? 'sì' : '-',
        preg_match('/\[\[\$scripts(-formazione)?\]\]/', $c) ? 'sì' : '-', $my);
}
echo "  pagine senza template: " . $modx->getCount(modResource::class, $pub + ['template' => 0]) . "\n";

echo "\n== RISORSE DI PRIMO E SECONDO LIVELLO\n";
$lista = function ($parent, $rientro) use ($modx, $pub, $tpl, &$lista, $t) {
    $q = $modx->newQuery(modResource::class, ['parent' => $parent, 'deleted' => 0]);
    $q->sortby('menuindex');
    foreach ($modx->getIterator(modResource::class, $q) as $r) {
        $figli = $modx->getCount(modResource::class, ['parent' => $r->get('id'), 'deleted' => 0]);
        $c = (string) $r->get('content');
        printf("  %s%-5d %-45s /%-50s tpl %-3d pub %d nasc %d figli %-4d %s%s%s\n", $rientro, $r->get('id'),
            mb_substr($t($r->get('pagetitle')), 0, 45), mb_substr($r->get('uri'), 0, 50), $r->get('template'),
            $r->get('published'), $r->get('hidemenu'), $figli,
            stripos($c, 'FirstChildRedirect') !== false ? 'FirstChildRedirect ' : '',
            $r->get('class_key') !== modResource::class && strpos($r->get('class_key'), 'modDocument') === false ? $r->get('class_key') . ' ' : '',
            $r->get('menutitle') ? 'menu: «' . $t($r->get('menutitle')) . '»' : '');
        if ($figli && strlen($rientro) < 4) $lista($r->get('id'), $rientro . '  ');
    }
};
$lista(0, '');

echo "\n== CONTENITORI (risorse con figli pubblicati), oltre il secondo livello\n";
$q = $modx->newQuery(modResource::class);
$q->select('parent, COUNT(*) AS n');
$q->where($pub);
$q->groupby('parent');
$q->prepare();
$q->stmt->execute();
foreach ($q->stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $p = $modx->getObject(modResource::class, (int) $row['parent']);
    if (!$p || substr_count(trim($p->get('uri'), '/'), '/') < 2) continue;
    $c = (string) $p->get('content');
    printf("  %-5d /%-60s figli %-4d pub %d nasc %d %s\n", $p->get('id'), $p->get('uri'), $row['n'], $p->get('published'),
        $p->get('hidemenu'), stripos($c, 'FirstChildRedirect') !== false ? 'FirstChildRedirect' : '');
}

echo "\n== CATENE DI ESEMPIO (2 pagine per template, dalla radice alla pagina)\n";
foreach ($tpl as $id => $x) {
    $q = $modx->newQuery(modResource::class, $pub + ['template' => $id]);
    $q->sortby('id', 'DESC');
    $q->limit(2);
    foreach ($modx->getIterator(modResource::class, $q) as $r) {
        echo "  [{$x->get('templatename')}] /{$r->get('uri')}\n";
        $catena = [];
        for ($a = $r; $a; $a = $a->getOne('Parent')) array_unshift($catena, $a);
        foreach ($catena as $a) {
            $c = (string) $a->get('content');
            printf("      %-5d %-40s pub %d nasc %d%s%s\n", $a->get('id'), mb_substr($t($a->get('pagetitle')), 0, 40),
                $a->get('published'), $a->get('hidemenu'),
                stripos($c, 'FirstChildRedirect') !== false ? ' FirstChildRedirect' : '',
                $a->get('menutitle') ? ' menu «' . $t($a->get('menutitle')) . '»' : '');
        }
    }
}

echo "\n== JSON-LD SCRITTO A MANO (chunk, template, contenuti)\n";
foreach ($modx->getIterator(modChunk::class, ['snippet:LIKE' => '%ld+json%']) as $ch) {
    $tipi = preg_match_all('/"@type"\s*:\s*"(\w+)"/', (string) $ch->get('snippet'), $m) ? implode(',', array_unique($m[1])) : '';
    $usi = $modx->getCount(modResource::class, ['content:LIKE' => '%$' . $ch->get('name') . '%']);
    $usiT = implode(',', array_map(fn($x) => $x->get('templatename'), array_filter($tpl, fn($x) => strpos((string) $x->get('content'), '$' . $ch->get('name')) !== false)));
    echo "  chunk {$ch->get('name')}: $tipi | in $usi contenuti" . ($usiT ? " | template $usiT" : '') . "\n";
}
foreach ($tpl as $x) if (strpos((string) $x->get('content'), 'ld+json') !== false) echo "  template {$x->get('templatename')}\n";
echo "  contenuti con ld+json: " . $modx->getCount(modResource::class, $pub + ['content:LIKE' => '%ld+json%']) . "\n";
echo "  contenuti con script Yext: " . $modx->getCount(modResource::class, $pub + ['content:LIKE' => '%yext%']) . "\n";

echo "\n== HOMEPAGE\n";
foreach ([1, 253, 990] as $id) {
    $r = $modx->getObject(modResource::class, $id);
    if ($r) echo "  $id «{$t($r->get('pagetitle'))}» /{$r->get('uri')} tpl {$r->get('template')} parent {$r->get('parent')} pub {$r->get('published')}\n";
}
