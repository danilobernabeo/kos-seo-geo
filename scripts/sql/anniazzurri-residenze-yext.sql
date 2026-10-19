-- anniazzurri.it: pagine delle residenze con id Yext e dati anagrafici in TV.
-- Serve al confronto sito / export Yext (anomalie da correggere prima di RetailTune).
-- MariaDB 10.2, prefisso modx_. Esportare il risultato da HeidiSQL in CSV UTF-8,
-- senza aprirlo in Excel.

SELECT
  r.id,
  r.pagetitle,
  r.longtitle,
  r.menutitle,
  r.alias,
  r.uri,
  r.published,
  r.deleted,
  r.hidemenu,
  r.searchable,
  r.template,
  t.templatename,
  r.parent,
  p.pagetitle  AS parent_titolo,
  p.alias      AS parent_alias,
  gp.id        AS nonno_id,
  gp.pagetitle AS nonno_titolo,
  MAX(CASE WHEN tv.name = 'data-yext-id'   THEN tvv.value END) AS yext_id,
  MAX(CASE WHEN tv.name = 'mapsAddress'    THEN tvv.value END) AS maps_address,
  MAX(CASE WHEN tv.name = 'numTel'         THEN tvv.value END) AS num_tel,
  MAX(CASE WHEN tv.name = 'emailResidenza' THEN tvv.value END) AS email_residenza,
  MAX(CASE WHEN tv.name = 'emailDirettore' THEN tvv.value END) AS email_direttore,
  MAX(CASE WHEN tv.name = 'nomeDirettore'  THEN tvv.value END) AS nome_direttore,
  MAX(CASE WHEN tv.name = 'apertura'       THEN tvv.value END) AS apertura
FROM modx_site_content r
LEFT JOIN modx_site_templates t ON t.id = r.template
LEFT JOIN modx_site_content p   ON p.id = r.parent
LEFT JOIN modx_site_content gp  ON gp.id = p.parent
LEFT JOIN modx_site_tmplvar_contentvalues tvv ON tvv.contentid = r.id
LEFT JOIN modx_site_tmplvars tv ON tv.id = tvv.tmplvarid
  AND tv.name IN ('data-yext-id','mapsAddress','numTel','emailResidenza',
                  'emailDirettore','nomeDirettore','apertura')
WHERE r.deleted = 0
GROUP BY r.id
HAVING yext_id IS NOT NULL AND TRIM(yext_id) <> ''
    OR r.alias LIKE 'residenza-anni-azzurri%'
    OR r.alias LIKE 'polo-geriatrico%'
    OR r.alias LIKE 'centro-diurno%'
ORDER BY r.alias;
