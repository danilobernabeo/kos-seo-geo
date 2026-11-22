-- =====================================================================
-- kosgroup.com – schede dei centri: chiamate [[!...]] -> [[...]] nei CONTENUTI e nelle TV (22/11/2026)
-- Da lanciare sul DB di PRODUZIONE (i contenuti non passano dal flusso git). Eseguire in ordine.
-- Non toccati di proposito:
--   [[!galleryStruttura]]                    -> galleria in carico alla chat principale
--   [[!barraPrenotazioneDBmedicoPrestazione]] -> resta non in cache; lo snippet si mette in cache da solo (file per git)
--   [[!mostraFino]]                          -> dipende dalla data: deve restare non in cache
-- L'HTML prodotto non cambia: le stesse chiamate vengono eseguite una volta e poi servite dalla cache della pagina,
-- che MODX svuota a ogni salvataggio di una risorsa.
-- =====================================================================

-- 0. BACKUP delle righe interessate
CREATE TABLE modx_site_content_bak_20261122 AS
SELECT * FROM modx_site_content
WHERE deleted = 0 AND (template IN (2, 4) OR uri = 'it/chi-siamo/qualita-delle-cure/medici');

CREATE TABLE modx_site_tmplvar_contentvalues_bak_20261122 AS
SELECT v.* FROM modx_site_tmplvar_contentvalues v
JOIN modx_site_content c ON c.id = v.contentid
WHERE c.deleted = 0 AND (c.template IN (2, 4) OR c.uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND v.value LIKE '%[[!%';

SELECT (SELECT COUNT(*) FROM modx_site_content_bak_20261122) AS risorse_salvate,
       (SELECT COUNT(*) FROM modx_site_tmplvar_contentvalues_bak_20261122) AS valori_tv_salvati;

-- 1. ANTEPRIMA: quante righe cambieranno (atteso: contenuti circa 10-14, valori TV circa 48)
SELECT 'contenuti' AS dove, COUNT(*) AS righe FROM modx_site_content
WHERE deleted = 0 AND (template IN (2, 4) OR uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND content REGEXP '\\[\\[!(Wayfinder|pdoMenu|getResources|getMedici|myBreadcrumb)'
UNION ALL
SELECT 'valori TV', COUNT(*) FROM modx_site_tmplvar_contentvalues v
JOIN modx_site_content c ON c.id = v.contentid
WHERE c.deleted = 0 AND (c.template IN (2, 4) OR c.uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND v.value REGEXP '\\[\\[!(Wayfinder|pdoMenu|getResources|getMedici|myBreadcrumb)';

-- 2. AGGIORNAMENTO dei contenuti
-- ('[[!getMedici' copre anche '[[!getMediciStrutturaOrdered')
UPDATE modx_site_content
SET content = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(content,
      '[[!Wayfinder', '[[Wayfinder'),
      '[[!pdoMenu', '[[pdoMenu'),
      '[[!getResources', '[[getResources'),
      '[[!getMedici', '[[getMedici'),
      '[[!myBreadcrumb', '[[myBreadcrumb')
WHERE deleted = 0 AND (template IN (2, 4) OR uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND content REGEXP '\\[\\[!(Wayfinder|pdoMenu|getResources|getMedici|myBreadcrumb)';

-- 3. AGGIORNAMENTO dei valori TV (modale, modale2, modale3)
UPDATE modx_site_tmplvar_contentvalues v
JOIN modx_site_content c ON c.id = v.contentid
SET v.value = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(v.value,
      '[[!Wayfinder', '[[Wayfinder'),
      '[[!pdoMenu', '[[pdoMenu'),
      '[[!getResources', '[[getResources'),
      '[[!getMedici', '[[getMedici'),
      '[[!myBreadcrumb', '[[myBreadcrumb')
WHERE c.deleted = 0 AND (c.template IN (2, 4) OR c.uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND v.value REGEXP '\\[\\[!(Wayfinder|pdoMenu|getResources|getMedici|myBreadcrumb)';

-- 4. VERIFICA: devono restare solo galleryStruttura, barraPrenotazioneDBmedicoPrestazione, mostraFino
SELECT 'contenuti' AS dove, COUNT(*) AS righe_ancora_non_in_cache FROM modx_site_content
WHERE deleted = 0 AND (template IN (2, 4) OR uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND content REGEXP '\\[\\[!(Wayfinder|pdoMenu|getResources|getMedici|myBreadcrumb)'
UNION ALL
SELECT 'valori TV', COUNT(*) FROM modx_site_tmplvar_contentvalues v
JOIN modx_site_content c ON c.id = v.contentid
WHERE c.deleted = 0 AND (c.template IN (2, 4) OR c.uri = 'it/chi-siamo/qualita-delle-cure/medici')
  AND v.value REGEXP '\\[\\[!(Wayfinder|pdoMenu|getResources|getMedici|myBreadcrumb)';

-- 5. Svuotare la cache dal manager (Sito > Svuota cache).

-- RIPRISTINO (solo se serve):
-- UPDATE modx_site_content c JOIN modx_site_content_bak_20261122 b ON b.id = c.id SET c.content = b.content;
-- UPDATE modx_site_tmplvar_contentvalues v JOIN modx_site_tmplvar_contentvalues_bak_20261122 b ON b.id = v.id SET v.value = b.value;
