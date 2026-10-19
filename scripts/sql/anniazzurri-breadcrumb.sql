-- =============================================================================
-- anniazzurri.it - Gerarchia per le briciole di pane (BreadcrumbList)
-- Regione > Provincia > Residenza (slide 34-35 dell'audit Anni Azzurri)
-- Solo lettura. Eseguire sul database dell'istanza di anniazzurri.it.
-- Esportare da HeidiSQL direttamente in CSV UTF-8, senza passare da Excel.
-- =============================================================================

-- B1 · pagine regione, provincia e residenza con la loro posizione nell'albero
SELECT c.id, c.parent, p.pagetitle AS genitore, c.template, t.templatename,
       c.published, c.hidemenu, c.pagetitle, c.menutitle, c.longtitle, c.uri
FROM modx_site_content c
LEFT JOIN modx_site_content p ON p.id = c.parent
LEFT JOIN modx_site_templates t ON t.id = c.template
WHERE c.deleted = 0
  AND (c.uri LIKE 'rsa-%' OR c.uri LIKE 'residenza-%' OR c.uri LIKE 'hospice-%')
ORDER BY c.template, c.parent, c.uri;

-- B2 · TV delle residenze che possono indicare regione e provincia
--      (nomi delle TV e valori, per capire da dove ricostruire il percorso)
SELECT v.contentid, c.uri, tv.name AS tv, LEFT(v.value, 120) AS valore
FROM modx_site_tmplvar_contentvalues v
JOIN modx_site_tmplvars tv ON tv.id = v.tmplvarid
JOIN modx_site_content c ON c.id = v.contentid
WHERE c.deleted = 0
  AND c.uri LIKE 'residenza-%'
  AND (tv.name LIKE '%regione%' OR tv.name LIKE '%provincia%' OR tv.name LIKE '%citta%'
       OR tv.name LIKE '%comune%' OR tv.name LIKE '%map%' OR tv.name LIKE '%breadcrumb%')
ORDER BY c.uri, tv.name;

-- B3 · chunk e snippet che oggi producono le briciole (visibili e JSON-LD)
SELECT 'chunk' AS tipo, id, name FROM modx_site_htmlsnippets
 WHERE snippet LIKE '%BreadcrumbList%' OR name LIKE '%bread%' OR name LIKE '%briciol%'
UNION ALL
SELECT 'snippet', id, name FROM modx_site_snippets
 WHERE snippet LIKE '%BreadcrumbList%' OR name LIKE '%bread%' OR name LIKE '%briciol%'
UNION ALL
SELECT 'template', id, templatename FROM modx_site_templates
 WHERE content LIKE '%BreadcrumbList%' OR content LIKE '%breadcrumb%';
