-- =============================================================================
-- kosgroup.com - Estrazione per la mappatura strutture / aree / trattamenti / tecnologie
-- MODX 3.2.4 - MariaDB 10.2.54 - prefisso modx_
-- Tutte le query sono in sola lettura (solo SELECT).
--
-- Esecuzione in HeidiSQL: lanciare UNA query alla volta (selezionarla e premere
-- Ctrl+F9, "Esegui selezione"), poi sulla griglia dei risultati:
--   tasto destro > Esporta righe della griglia
--   Formato: CSV · Codifica: UTF-8 · Righe: Tutte · Separatore campi: ;
--   Racchiudi campi: " · Nome file: quello indicato in testa a ogni query
-- Controllare che il numero di righe esportate coincida con quello mostrato
-- nella barra di stato dopo l'esecuzione.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- Q1 · risorse.csv
-- Tutte le risorse sotto Strutture (id 167) e sotto Aree mediche (id 3),
-- a qualunque livello di profondità, con la loro posizione nell'albero.
-- La colonna "radice" dice da quale ramo arriva la risorsa (167 o 3).
-- -----------------------------------------------------------------------------
WITH RECURSIVE albero AS (
    SELECT id, parent, 0 AS livello, CAST(id AS CHAR(20)) AS radice
    FROM modx_site_content
    WHERE id IN (3, 167)
  UNION ALL
    SELECT c.id, c.parent, a.livello + 1, a.radice
    FROM modx_site_content c
    JOIN albero a ON c.parent = a.id
)
SELECT
    c.id,
    c.parent,
    a.radice,
    a.livello,
    c.context_key,
    c.template,
    t.templatename,
    c.class_key,
    c.published,
    c.deleted,
    c.hidemenu,
    c.isfolder,
    c.menuindex,
    c.pagetitle,
    c.longtitle,
    c.menutitle,
    c.alias,
    c.uri
FROM albero a
JOIN modx_site_content c ON c.id = a.id
LEFT JOIN modx_site_templates t ON t.id = c.template
ORDER BY a.radice, a.livello, c.parent, c.menuindex, c.id;


-- -----------------------------------------------------------------------------
-- Q2 · tv.csv
-- Catalogo di tutte le TV: nome, tipo di input, valori ammessi, valore
-- predefinito e template a cui sono assegnate. Serve a individuare tutte le TV
-- "map*" e i valori ammessi di tipo_struttura.
-- -----------------------------------------------------------------------------
SELECT
    tv.id,
    tv.name,
    tv.caption,
    tv.type,
    tv.elements,
    tv.default_text,
    cat.category AS categoria,
    (SELECT GROUP_CONCAT(tt.templateid ORDER BY tt.templateid SEPARATOR ',')
       FROM modx_site_tmplvar_templates tt
      WHERE tt.tmplvarid = tv.id) AS template_ids
FROM modx_site_tmplvars tv
LEFT JOIN modx_categories cat ON cat.id = tv.category
ORDER BY tv.name;


-- -----------------------------------------------------------------------------
-- Q3 · tv_valori.csv
-- Valori di TUTTE le TV delle risorse dei due rami, una riga per coppia
-- risorsa-TV. Formato "lungo": la ricomposizione per struttura la faccio io.
-- Nota: se una TV non ha valore salvato per una risorsa, la riga non c'è e vale
-- il predefinito (colonna default_text di Q2).
-- -----------------------------------------------------------------------------
WITH RECURSIVE albero AS (
    SELECT id FROM modx_site_content WHERE id IN (3, 167)
  UNION ALL
    SELECT c.id FROM modx_site_content c JOIN albero a ON c.parent = a.id
)
SELECT
    v.contentid AS resource_id,
    tv.name     AS tv,
    v.value
FROM albero a
JOIN modx_site_tmplvar_contentvalues v ON v.contentid = a.id
JOIN modx_site_tmplvars tv ON tv.id = v.tmplvarid
ORDER BY v.contentid, tv.name;


-- -----------------------------------------------------------------------------
-- Q4 · contenuti.csv
-- Campo content (HTML) delle risorse dei due rami: serve per le strutture che
-- elencano aree o prestazioni come elenco puntato nel testo.
-- -----------------------------------------------------------------------------
WITH RECURSIVE albero AS (
    SELECT id FROM modx_site_content WHERE id IN (3, 167)
  UNION ALL
    SELECT c.id FROM modx_site_content c JOIN albero a ON c.parent = a.id
)
SELECT
    c.id,
    c.pagetitle,
    c.introtext,
    c.content
FROM albero a
JOIN modx_site_content c ON c.id = a.id
ORDER BY c.id;


-- -----------------------------------------------------------------------------
-- Q5 · contesti.csv
-- Contesti e impostazioni di base: per capire come è organizzata la versione
-- inglese (/en) e ricostruire gli URL canonici (https://kosgroup.com/it/...).
-- -----------------------------------------------------------------------------
SELECT
    cs.context_key,
    cs.`key`,
    cs.value
FROM modx_context_setting cs
WHERE cs.`key` IN ('site_url', 'base_url', 'site_start', 'cultureKey', 'http_host', 'error_page')
ORDER BY cs.context_key, cs.`key`;
