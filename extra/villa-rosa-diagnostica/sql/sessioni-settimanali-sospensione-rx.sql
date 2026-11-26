-- Sessioni settimanali (lunedì-domenica) del sito villarosadiagnostica.it: 2026 contro le stesse settimane del 2025.
-- Serve a verificare se l'avviso di sospensione di RX, ortopanoramica, teleradiografia, Dentalscan e Cone Beam
-- (pubblicato il 19/8/2026, in vigore dal 31/8) coincide con un calo delle visite.
-- Fonte: ga4storico_vrd.storico_arrivo (sessioni vere per giorno, canale e pagina di atterraggio).
-- Colonne: totale, Organic Search e atterraggi sulle pagine di radiologia, per l'anno 2026 e per la settimana
-- corrispondente del 2025 (364 giorni prima, stesso giorno della settimana).
-- Filtro radiologia: pagina_atterraggio con radiolog, ortopan, cone, dental, opt o rx (da rileggere: «opt» e «rx» possono
-- prendere anche altre pagine).
WITH base AS (
  SELECT
    DATE_TRUNC(data_date, WEEK(MONDAY)) AS settimana,
    SUM(sessioni) AS totale,
    SUM(IF(canale = 'Organic Search', sessioni, 0)) AS organico,
    SUM(IF(REGEXP_CONTAINS(LOWER(pagina_atterraggio), r'radiolog|ortopan|cone|dental|opt|rx'), sessioni, 0)) AS radiologia
  FROM `kos-analytics-482819.ga4storico_vrd.storico_arrivo`
  WHERE data_date BETWEEN DATE '2025-06-30' AND DATE '2026-11-15'
  GROUP BY settimana
)
SELECT
  a.settimana AS settimana_2026,
  a.totale AS totale_2026,
  b.totale AS totale_2025,
  ROUND(100 * SAFE_DIVIDE(a.totale - b.totale, b.totale), 0) AS variazione_pct,
  a.organico AS organico_2026,
  b.organico AS organico_2025,
  a.radiologia AS radiologia_2026,
  b.radiologia AS radiologia_2025
FROM base a
LEFT JOIN base b ON b.settimana = DATE_SUB(a.settimana, INTERVAL 364 DAY)
WHERE a.settimana BETWEEN DATE '2026-06-29' AND DATE '2026-11-09'
ORDER BY a.settimana;
