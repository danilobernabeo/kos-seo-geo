-- Extra villa-rosa-diagnostica: la marca «villa rosa» divisa tra tre pagine (21/11/2026)
-- Fonti: searchconsole_vrd.storico_site_impression (sito, dall'8/7/2025) e
--        gsc_kos.searchdata_url_impression (kosgroup.com, dal 16/3/2025).
-- Marca = query che contengono «villa rosa» o «villarosa» (include omonimi); query anonimizzate escluse.
WITH u AS (
 SELECT data_date, 'sito_vrd' AS pagina, query, impressions, clicks
 FROM `kos-analytics-482819.searchconsole_vrd.storico_site_impression` WHERE query IS NOT NULL
 UNION ALL
 SELECT data_date,
  CASE WHEN url LIKE '%/it/centri/villa-rosa-diagnostica%' THEN 'scheda_diagnostica' ELSE 'clinica_psichiatrica' END,
  query, impressions, clicks
 FROM `kos-analytics-482819.gsc_kos.searchdata_url_impression`
 WHERE query IS NOT NULL AND search_type='WEB'
   AND (url LIKE '%/it/centri/villa-rosa-diagnostica%' OR url LIKE '%/it/centri/villa-rosa' OR url LIKE '%/it/centri/villa-rosa/%')
), b AS (
 SELECT FORMAT_DATE('%Y-%m', data_date) m, pagina, impressions, clicks,
  (LOWER(query) LIKE '%diagnost%' OR LOWER(query) LIKE '%radiolog%' OR LOWER(query) LIKE '%risonanza%' OR LOWER(query) LIKE '%tuotempo%') AS diag
 FROM u
 WHERE (LOWER(query) LIKE '%villa rosa%' OR LOWER(query) LIKE '%villarosa%')
   AND data_date>='2025-07-01' AND data_date<'2026-11-01')
SELECT m, pagina, SUM(clicks) clic_marca, SUM(impressions) imp_marca, SUM(IF(diag,clicks,0)) clic_marca_diagnostica
FROM b GROUP BY m, pagina ORDER BY pagina, m;
