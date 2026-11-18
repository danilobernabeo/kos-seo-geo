<?php
/**
 * Diagnostica in SOLA LETTURA (KOS, sprint 1 link interni): voci delle «aree di intervento» delle schede con link vuoto.
 *
 * I chunk aree-di-intervento-*-struttura stampano le risorse elencate nelle TV mapAree* con href="[[~id]]": se la
 * risorsa elencata non è pubblicata, è cestinata o non ha un URL, il link esce vuoto e il browser lo risolve verso la
 * home (scansione del 18/11: Filottrano, San Severino, Ospedale San Pancrazio).
 *
 * Per ogni risorsa con almeno una TV mapAree* valorizzata controlla ogni id elencato: esiste, pubblicata, cestinata,
 * contesto, uri, URL generato da makeUrl. Stampa solo le voci con problemi, più i candidati con lo stesso titolo
 * pubblicati (per la correzione).
 *
 * Uso: php scripts/checks/kos-aree-link.php /www
 */
declare(strict_types=1);
$webroot = rtrim($argv[1] ?? '', '/');
if (!is_file("$webroot/config.core.php")) { fwrite(STDERR, "Uso: php kos-aree-link.php /www\n"); exit(1); }
require "$webroot/config.core.php";
require MODX_CORE_PATH . 'vendor/autoload.php';
use MODX\Revolution\{modX, modResource, modTemplateVar, modTemplateVarResource};
$modx = modX::getInstance();
$modx->initialize('web');

$tvs = $modx->getCollection(modTemplateVar::class, ['name:LIKE' => 'mapAree%']);
$problemi = 0; $voci = 0; $schede = [];
foreach ($tvs as $tv) {
    foreach ($modx->getCollection(modTemplateVarResource::class, ['tmplvarid' => $tv->get('id')]) as $val) {
        $ids = array_filter(array_map('intval', preg_split('/[,|]+/', (string) $val->get('value'))));
        if (!$ids) continue;
        $scheda = $modx->getObject(modResource::class, $val->get('contentid'));
        if (!$scheda || !$scheda->get('published') || $scheda->get('deleted')) continue;
        $schede[$scheda->get('id')] = true;
        foreach ($ids as $id) {
            $voci++;
            $r = $modx->getObject(modResource::class, $id);
            $url = $r ? $modx->makeUrl($id, '', '', 'abs') : '';
            $stato = !$r ? 'NON ESISTE'
                : (!$r->get('published') ? 'non pubblicata' : ($r->get('deleted') ? 'cestinata' : ($url === '' ? 'URL vuoto' : '')));
            if ($stato === '') continue;
            $problemi++;
            echo "{$scheda->get('uri')} (risorsa {$scheda->get('id')}) · TV {$tv->get('name')} · id $id";
            if ($r) echo " «{$r->get('pagetitle')}» classe {$r->get('class_key')} contesto {$r->get('context_key')} uri '{$r->get('uri')}' nascosta {$r->get('hidemenu')}";
            echo " → $stato\n";
            if ($r) {
                $cand = $modx->getCollection(modResource::class, [
                    'pagetitle' => $r->get('pagetitle'), 'published' => 1, 'deleted' => 0, 'id:!=' => $id,
                    'context_key' => $scheda->get('context_key'),
                ]);
                foreach ($cand as $c) echo "    candidato: id {$c->get('id')} /{$c->get('uri')} (genitore {$c->get('parent')})\n";
                if (!$cand) echo "    nessun candidato pubblicato con lo stesso titolo\n";
            }
        }
    }
}
echo "\nSchede pubblicate con TV mapAree*: " . count($schede) . "; voci controllate: $voci; voci con problemi: $problemi\n";
