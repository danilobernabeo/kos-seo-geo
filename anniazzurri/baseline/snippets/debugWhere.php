<?php
$out  = "db:   " . $modx->getOption('dbname') . "\n";
$out .= "host: " . $modx->getOption('host') . "\n";
$out .= "prefix: " . $modx->getOption('table_prefix') . "\n\n";

$queries = [
    '0. conteggio totale contentvalues'
        => "SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues",
    '1. righe della risorsa 555'
        => "SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues WHERE contentid = 555",
    '2. TV soloStruttura esiste?'
        => "SELECT COUNT(*) FROM modx_site_tmplvars WHERE name = 'soloStruttura'",
    '3. risorsa 555 in site_content'
        => "SELECT COUNT(*) FROM modx_site_content WHERE id = 555",
    '4. join tmplvars'
        => "SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues tvr
            JOIN modx_site_tmplvars tv ON tv.id = tvr.tmplvarid
            WHERE tv.name = 'soloStruttura' AND tvr.value = '1'",
    '5. join completa'
        => "SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues tvr
            JOIN modx_site_tmplvars tv ON tv.id = tvr.tmplvarid
            JOIN modx_site_content    c ON c.id = tvr.contentid
            WHERE tv.name = 'soloStruttura' AND tvr.value = '1'",
];

foreach ($queries as $label => $sql) {
    $stmt = $modx->query($sql);
    $val  = ($stmt instanceof PDOStatement) ? $stmt->fetchColumn() : 'ERRORE';
    $out .= str_pad($label, 40) . ' => ' . $val . "\n";
}

return '<pre>' . htmlspecialchars($out) . '</pre>';