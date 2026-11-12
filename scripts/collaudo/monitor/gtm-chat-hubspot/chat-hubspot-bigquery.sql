-- AA-4 · Test della chat HubSpot da computer (fasi A e B). Proprietà GA4 anniazzurri.it (analytics_250584060).
-- Fasi (ora italiana): A = apertura automatica (impostazione attuale), B = solo pulsante e messaggio di benvenuto.
-- Aggiornare gli istanti di passaggio con l'ora effettiva del cambio in HubSpot (registro in docs/aa-4.md).
DECLARE inizio  TIMESTAMP DEFAULT TIMESTAMP('2026-11-12 12:00:00', 'Europe/Rome');  -- pubblicazione del tag
DECLARE a_b     TIMESTAMP DEFAULT TIMESTAMP('2026-11-21 00:00:00', 'Europe/Rome');  -- A -> B
DECLARE b_a     TIMESTAMP DEFAULT TIMESTAMP('2026-12-07 09:00:00', 'Europe/Rome');  -- B -> A
DECLARE fine    TIMESTAMP DEFAULT TIMESTAMP('2026-12-14 00:00:00', 'Europe/Rome');

WITH ev AS (
  SELECT
    TIMESTAMP_MICROS(event_timestamp) AS ts,
    device.category AS disp,
    event_name,
    CONCAT(user_pseudo_id, '-', CAST((SELECT value.int_value FROM UNNEST(event_params) WHERE key = 'ga_session_id') AS STRING)) AS sessione,
    (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'chat_azione') AS azione,
    (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'chat_modo') AS modo,
    (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'chat_interazione') AS usata,
    SAFE_CAST((SELECT COALESCE(value.string_value, CAST(value.int_value AS STRING)) FROM UNNEST(event_params) WHERE key = 'chat_secondi_aperta') AS INT64) AS sec_aperta,
    (SELECT value.string_value FROM UNNEST(event_params) WHERE key = 'session_engaged') AS engaged,
    (SELECT value.int_value FROM UNNEST(event_params) WHERE key = 'percent_scrolled') AS scroll
  FROM `kos-analytics-482819.analytics_250584060.events_*`
  WHERE _TABLE_SUFFIX BETWEEN '20261112' AND '20261213'
),
f AS (
  SELECT *, CASE WHEN ts < inizio OR ts >= fine THEN NULL WHEN ts < a_b OR ts >= b_a THEN 'A' ELSE 'B' END AS fase
  FROM ev WHERE disp IN ('desktop', 'mobile')
)
SELECT
  fase, disp,
  COUNT(DISTINCT sessione) AS sessioni,
  COUNT(DISTINCT IF(engaged = '1', sessione, NULL)) AS sessioni_con_interazione,
  COUNTIF(event_name = 'page_view') AS pagine_viste,
  COUNTIF(event_name = 'scroll' AND scroll = 90) AS letture_fino_in_fondo,
  -- chat
  COUNTIF(azione = 'aperta' AND modo = 'automatica') AS aperture_automatiche,
  COUNTIF(azione = 'aperta' AND modo = 'utente') AS aperture_utente,
  COUNTIF(azione = 'chiusa' AND modo = 'automatica' AND usata = 'no') AS chiusure_auto_senza_uso,
  COUNTIF(azione = 'chiusa' AND modo = 'automatica' AND usata = 'no' AND sec_aperta <= 5) AS chiusure_auto_entro_5s,
  COUNTIF(azione = 'conversazione') AS conversazioni,
  -- contatti
  COUNTIF(event_name = 'generate_lead') AS moduli,
  COUNTIF(event_name = 'click_phone') AS telefono,
  COUNTIF(event_name = 'click_mail') AS email,
  COUNTIF(event_name IN ('generate_lead', 'click_phone', 'click_mail') OR azione = 'conversazione') AS contatti_totali,
  ROUND(100 * COUNTIF(event_name IN ('generate_lead', 'click_phone', 'click_mail') OR azione = 'conversazione')
        / NULLIF(COUNT(DISTINCT sessione), 0), 2) AS contatti_per_100_sessioni
FROM f
WHERE fase IS NOT NULL
GROUP BY fase, disp
ORDER BY disp, fase;
