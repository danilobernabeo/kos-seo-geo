-- =====================================================================
-- VERIFICA MODIFICHE kosgroup.com (ottobre 2026) – da lanciare sul DB di PRODUZIONE
-- Ogni riga: controllo, valore trovato, valore atteso. Se trovato <> atteso: modifica persa.
-- =====================================================================
SELECT 'Impostazione link_tag_scheme' AS controllo,
       CAST((SELECT value FROM modx_system_settings WHERE `key` = 'link_tag_scheme') AS CHAR) AS trovato, 'abs' AS atteso
UNION ALL
SELECT 'Plugin Tagger attivo su OnPageNotFound',
       CAST((SELECT COUNT(*) FROM modx_site_plugin_events pe JOIN modx_site_plugins p ON p.id = pe.pluginid
             WHERE p.name = 'Tagger' AND pe.event = 'OnPageNotFound') AS CHAR), '0'
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
UNION ALL
SELECT 'Valori TV con Wayfinder/pdoMenu/pdoCrumbs senza &scheme',
       CAST((SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues
             WHERE value REGEXP '(Wayfinder|pdoMenu|pdoCrumbs)\\?(?![^\\]]*scheme=`abs`)') AS CHAR), '0'
UNION ALL
SELECT 'Default TV con Wayfinder/pdoMenu/pdoCrumbs senza &scheme',
       CAST((SELECT COUNT(*) FROM modx_site_tmplvars
             WHERE default_text REGEXP '(Wayfinder|pdoMenu|pdoCrumbs)\\?(?![^\\]]*scheme=`abs`)') AS CHAR), '0'
-- --- Link relativi ---
UNION ALL
SELECT 'Elementi con href="[[+uri senza barra iniziale',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets WHERE snippet REGEXP 'href=.\\[\\[\\+uri')
          + (SELECT COUNT(*) FROM modx_site_templates WHERE content REGEXP 'href=.\\[\\[\\+uri')
          + (SELECT COUNT(*) FROM modx_site_content WHERE content REGEXP 'href=.\\[\\[\\+uri')
          + (SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues WHERE value REGEXP 'href=.\\[\\[\\+uri') AS CHAR), '0'
UNION ALL
SELECT 'Elementi con href="it/ o href="en/ (relativi scritti a mano)',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets WHERE snippet REGEXP 'href="(it|en)/')
          + (SELECT COUNT(*) FROM modx_site_templates WHERE content REGEXP 'href="(it|en)/')
          + (SELECT COUNT(*) FROM modx_site_content WHERE content REGEXP 'href="(it|en)/') AS CHAR), '0'
-- --- Snippet corretti ---
UNION ALL
SELECT CONCAT('Snippet ', name, ': makeUrl con link_tag_scheme'),
       IF(snippet LIKE '%link_tag_scheme%', 'ok', 'MANCA'), 'ok'
FROM modx_site_snippets WHERE name IN ('HeaderNews', 'getMedici', 'getMediciStrutturaOrdered')
UNION ALL
SELECT 'Snippet myBreadcrumb: delega a kosSchema (briciole)',
       IF(snippet LIKE '%kosSchema%', 'ok', 'CAMBIATO'), 'ok' FROM modx_site_snippets WHERE name = 'myBreadcrumb'
UNION ALL
SELECT CONCAT('Snippet ', name, ': link con barra iniziale'),
       IF(snippet LIKE '%href="/%', 'ok', 'MANCA'), 'ok'
FROM modx_site_snippets WHERE name IN ('Approfondimenti2', 'CustomSitemap', 'VideoCarousel', 'CustomStructures', 'contenutiStruttura')
UNION ALL
SELECT 'Snippet contenutiStruttura: filtro FIND_IN_SET',
       IF(snippet LIKE '%FIND_IN_SET%', 'ok', 'MANCA'), 'ok' FROM modx_site_snippets WHERE name = 'contenutiStruttura'
UNION ALL
SELECT 'Snippet nuovi presenti (ogImage, comunicatoRedirect)',
       CAST((SELECT COUNT(*) FROM modx_site_snippets WHERE name IN ('ogImage', 'comunicatoRedirect')) AS CHAR), '2'
-- --- Open Graph ---
UNION ALL
SELECT 'Chunk head nuovi presenti (head-medici, head-video, head-specializzazioni)',
       CAST((SELECT COUNT(*) FROM modx_site_htmlsnippets WHERE name IN ('head-medici', 'head-video', 'head-specializzazioni')) AS CHAR), '3'
-- og:image: dal 31/10 i tag non sono piu' scritti nei chunk head (riorganizzazione SEO):
-- si verificano sul sito con controlla-og-image.ps1
UNION ALL
SELECT CONCAT('Template ', id, ' (', templatename, '): head dedicato'),
       IF(content LIKE CONCAT('%', CASE id WHEN 8 THEN '$head-medici' WHEN 10 THEN '$head-video' WHEN 3 THEN '$head-specializzazioni' END, '%'), 'ok', 'MANCA'), 'ok'
FROM modx_site_templates WHERE id IN (3, 8, 10)
UNION ALL
SELECT 'Template comunicati (9): comunicatoRedirect',
       IF(content LIKE '%comunicatoRedirect%', 'ok', 'MANCA'), 'ok' FROM modx_site_templates WHERE id = 9
UNION ALL
SELECT 'Valori TV con segnaposto placehold.co',
       CAST((SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues WHERE value LIKE '%placehold.co%') AS CHAR), '0';
