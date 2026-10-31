-- =====================================================================
-- Web Vitals dei visitatori reali (evento web_vitals) – kosgroup.com
-- Per anniazzurri.it sostituire analytics_333083624 con analytics_250584060.
-- =====================================================================

-- 1. TTFB giorno per giorno: p75, quota di visite lente (> 1,8 s) e visite con connessione lenta (>= 900 ms: SYN ritrasmessi)
WITH wv AS (
  SELECT
    PARSE_DATE('%Y%m%d', event_date) AS giorno,
    device.category AS dispositivo,
    device.operating_system AS so,
    (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'metric_name') AS metrica,
    (SELECT COALESCE(value.int_value, CAST(value.double_value AS INT64)) FROM UNNEST(event_params) WHERE key = 'metric_value') AS valore,
    (SELECT COALESCE(value.int_value, CAST(value.double_value AS INT64)) FROM UNNEST(event_params) WHERE key = 'ttfb_connection') AS connessione,
    (SELECT COALESCE(value.int_value, CAST(value.double_value AS INT64)) FROM UNNEST(event_params) WHERE key = 'ttfb_request') AS server,
    (SELECT COALESCE(value.int_value, CAST(value.double_value AS INT64)) FROM UNNEST(event_params) WHERE key = 'ttfb_waiting') AS attesa,
    (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'page_location') AS pagina
  FROM `kos-analytics-482819.analytics_333083624.events_*`
  WHERE _TABLE_SUFFIX >= '20261101' AND event_name = 'web_vitals'
)
SELECT giorno, dispositivo,
       COUNT(*) AS visite,
       APPROX_QUANTILES(valore, 100)[OFFSET(75)] AS ttfb_p75_ms,
       ROUND(100 * COUNTIF(valore > 1800) / COUNT(*), 1) AS pct_lente,
       ROUND(100 * COUNTIF(connessione >= 900) / COUNT(*), 1) AS pct_connessione_lenta,
       APPROX_QUANTILES(server, 100)[OFFSET(75)] AS server_p75_ms,
       APPROX_QUANTILES(attesa, 100)[OFFSET(75)] AS attesa_redirect_p75_ms
FROM wv WHERE metrica = 'TTFB'
GROUP BY giorno, dispositivo ORDER BY giorno, dispositivo;

-- 2. Connessione lenta per sistema operativo (verifica: Windows senza timestamp TCP dietro NAT degli operatori)
-- (riusa la CTE wv della query 1)
-- SELECT so, COUNT(*) AS visite, ROUND(100 * COUNTIF(connessione >= 900) / COUNT(*), 1) AS pct_connessione_lenta
-- FROM wv WHERE metrica = 'TTFB' GROUP BY so HAVING visite > 100 ORDER BY pct_connessione_lenta DESC;

-- 3. Pagine con il TTFB peggiore (almeno 20 visite)
-- SELECT REGEXP_REPLACE(pagina, r'\?.*$', '') AS pagina, COUNT(*) AS visite,
--        APPROX_QUANTILES(valore, 100)[OFFSET(75)] AS ttfb_p75_ms, APPROX_QUANTILES(server, 100)[OFFSET(75)] AS server_p75_ms
-- FROM wv WHERE metrica = 'TTFB' GROUP BY pagina HAVING visite >= 20 ORDER BY ttfb_p75_ms DESC LIMIT 30;

-- 4. Riepilogo di tutte le metriche (soglie Google: TTFB 800/1800 ms, LCP 2500/4000, INP 200/500, CLS 0,1/0,25 = 100/250 qui)
-- SELECT metrica, dispositivo, COUNT(*) AS n, APPROX_QUANTILES(valore, 100)[OFFSET(75)] AS p75,
-- FROM wv GROUP BY metrica, dispositivo ORDER BY metrica, dispositivo;
