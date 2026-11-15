<?php
/**
 * cache-per-template.php — diagnosi dei tempi di risposta: per ogni template, quante risorse pubblicate sono
 * «cacheable», e quali plugin girano a ogni richiesta (eventi eseguiti anche con la pagina in cache).
 * Solo lettura. Uso: php scripts/checks/cache-per-template.php /www
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php cache-per-template.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\modX;
$modx = modX::getInstance();
$modx->initialize('web');
$p = $modx->getOption('table_prefix');
echo "== Risorse pubblicate per template (cacheable / non cacheable)\n";
$q = $modx->query("SELECT t.templatename, SUM(r.cacheable=1) c1, SUM(r.cacheable=0) c0
  FROM {$p}site_content r LEFT JOIN {$p}site_templates t ON t.id=r.template
  WHERE r.published=1 AND r.deleted=0 GROUP BY t.templatename ORDER BY c0 DESC, c1 DESC");
foreach ($q->fetchAll(PDO::FETCH_NUM) as [$n, $c1, $c0]) printf("  %-28s %5d %5d\n", $n ?? '(nessuno)', $c1, $c0);
echo "== Plugin attivi sugli eventi di ogni richiesta\n";
$q = $modx->query("SELECT e.event, pl.name FROM {$p}site_plugin_events e JOIN {$p}site_plugins pl ON pl.id=e.pluginid
  WHERE pl.disabled=0 AND e.event IN ('OnHandleRequest','OnWebPageInit','OnLoadWebDocument','OnLoadWebPageCache','OnWebPagePrerender','OnWebPageComplete','OnParseDocument','OnBeforeSaveWebPageCache')
  ORDER BY e.event, e.priority");
foreach ($q->fetchAll(PDO::FETCH_NUM) as [$ev, $n]) printf("  %-26s %s\n", $ev, $n);
echo "== Impostazioni di cache\n";
foreach (['cache_disabled','cache_resource','cache_handler','session_enabled','anonymous_sessions'] as $k) printf("  %-20s %s\n", $k, var_export($modx->getOption($k), true));
