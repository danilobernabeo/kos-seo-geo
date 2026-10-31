-- =====================================================================
-- VERIFICA MODIFICHE anniazzurri.it (ottobre 2026) – da lanciare sul DB di PRODUZIONE
-- Ogni riga: controllo, valore trovato, valore atteso. Se trovato <> atteso: modifica persa.
-- =====================================================================
SELECT 'Impostazione link_tag_scheme' AS controllo,
       CAST((SELECT value FROM modx_system_settings WHERE `key` = 'link_tag_scheme') AS CHAR) AS trovato, 'abs' AS atteso
-- --- Menu e breadcrumb: ogni chiamata deve avere &scheme=`abs` ---
UNION ALL
SELECT 'Chunk con Wayfinder/pdoMenu/pdoCrumbs senza &scheme',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets
             WHERE snippet REGEXP '(Wayfinder|pdoMenu|pdoCrumbs)\\?(?![^\\]]*scheme=`abs`)') AS CHAR), '0'
UNION ALL
SELECT 'Template con Wayfinder/pdoMenu/pdoCrumbs senza &scheme',
       CAST((SELECT COUNT(*) FROM modx_site_templates
             WHERE content REGEXP '(Wayfinder|pdoMenu|pdoCrumbs)\\?(?![^\\]]*scheme=`abs`)') AS CHAR), '0'
UNION ALL
SELECT 'Risorse con Wayfinder/pdoMenu/pdoCrumbs senza &scheme',
       CAST((SELECT COUNT(*) FROM modx_site_content
             WHERE content REGEXP '(Wayfinder|pdoMenu|pdoCrumbs)\\?(?![^\\]]*scheme=`abs`)') AS CHAR), '0'
-- --- Link relativi ---
UNION ALL
SELECT 'Elementi con href="[[+uri / [[+alias / [[*alias / [[*uri senza barra',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets WHERE snippet REGEXP 'href=.\\[\\[[+*](uri|alias)')
          + (SELECT COUNT(*) FROM modx_site_templates WHERE content REGEXP 'href=.\\[\\[[+*](uri|alias)')
          + (SELECT COUNT(*) FROM modx_site_content WHERE content REGEXP 'href=.\\[\\[[+*](uri|alias)') AS CHAR), '0'
UNION ALL
SELECT 'Chunk con link relativi scritti a mano',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets
             WHERE snippet REGEXP 'href="(?!/|https?:|#|mailto:|tel:|javascript:|data:|\\?|\\[\\[|\\{|"|\\s)') AS CHAR), '0'
UNION ALL
SELECT 'Template con link relativi scritti a mano',
       CAST((SELECT COUNT(*) FROM modx_site_templates
             WHERE content REGEXP 'href="(?!/|https?:|#|mailto:|tel:|javascript:|data:|\\?|\\[\\[|\\{|"|\\s)') AS CHAR), '0'
UNION ALL
SELECT 'Risorse con link relativi scritti a mano',
       CAST((SELECT COUNT(*) FROM modx_site_content
             WHERE content REGEXP 'href="(?!/|https?:|#|mailto:|tel:|javascript:|data:|\\?|\\[\\[|\\{|"|\\s)') AS CHAR), '0'
UNION ALL
SELECT 'Valori TV con link relativi scritti a mano',
       CAST((SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues
             WHERE value REGEXP 'href="(?!/|https?:|#|mailto:|tel:|javascript:|data:|\\?|\\[\\[|\\{|"|\\s)') AS CHAR), '0'
UNION ALL
SELECT 'Link con spazio iniziale href=" https (chunk 284, 643)',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets WHERE snippet LIKE '%href=" https%') AS CHAR), '0'
-- --- Snippet e template corretti ---
UNION ALL
SELECT 'Snippet docList: link assoluti e codificati',
       IF(snippet LIKE '%rawurlencode%' AND snippet LIKE '%$relDir%', 'ok', 'MANCA'), 'ok'
FROM modx_site_snippets WHERE name = 'docList'
UNION ALL
SELECT 'Snippet ecmIscrizione: informativa con percorso assoluto',
       IF(snippet LIKE '%ltrim($informativa%', 'ok', 'MANCA'), 'ok'
FROM modx_site_snippets WHERE name = 'ecmIscrizione'
UNION ALL
SELECT 'Template 30: href="/[[*programma',
       IF(content LIKE '%href="/[[*programma%', 'ok', 'MANCA'), 'ok' FROM modx_site_templates WHERE id = 30
-- --- noindex legato a "Ricercabile" ---
UNION ALL
SELECT 'Chunk head con riga noindex/searchable',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets
             WHERE id IN (1,314,318,333,341,345,359,430,431,432,433,434,435,436,437,438,445,446,464,466,483,623,662,664,669,670)
               AND snippet LIKE '%[[*searchable:is=%') AS CHAR), '26'
UNION ALL
SELECT 'Template 1 e 57 con riga noindex/searchable',
       CAST((SELECT COUNT(*) FROM modx_site_templates WHERE id IN (1, 57) AND content LIKE '%[[*searchable:is=%') AS CHAR), '2'
UNION ALL
SELECT 'Template 37 recruiting: noindex fisso',
       IF(content LIKE '%noindex%', 'ok', 'MANCA'), 'ok' FROM modx_site_templates WHERE id = 37
-- --- Sitemap ---
UNION ALL
SELECT 'Risorsa sitemap.xml: pdoSitemap con filtro searchable',
       IF(content LIKE '%pdoSitemap%' AND content LIKE '%"searchable":1%' AND content LIKE '%showHidden%', 'ok', 'MANCA'), 'ok'
FROM modx_site_content WHERE uri = 'sitemap.xml'
UNION ALL
SELECT 'Risorsa sitemap.xml: vecchio GoogleSiteMap',
       IF(content LIKE '%GoogleSiteMap%', 'PRESENTE', 'ok'), 'ok' FROM modx_site_content WHERE uri = 'sitemap.xml';
