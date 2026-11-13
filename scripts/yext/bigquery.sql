-- Linea C - Statistiche dei profili Google da Yext Analytics (dal 2020-02). Creato il 12/11/2026.
-- Dataset nella stessa regione di searchconsole_kos (europe-west3).
-- Colonne senza NOT NULL: il caricamento dalla console con rilevamento automatico crea campi NULLABLE.
CREATE SCHEMA IF NOT EXISTS `kos-analytics-482819.yext_kos`
  OPTIONS (location = 'europe-west3',
           description = 'Statistiche dei profili Google esportate da Yext Analytics (Linea C), dal 2020-02, prima della dismissione di Yext');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.azioni` (
  data DATE, entita STRING, azione STRING, valore INT64)
PARTITION BY DATE_TRUNC(data, MONTH) CLUSTER BY entita
OPTIONS (description = 'LISTINGS_ACTIONS per giorno, entità e azione; righe a zero omesse');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.impressioni` (
  data DATE, entita STRING, origine STRING, dispositivo STRING, valore INT64)
PARTITION BY DATE_TRUNC(data, MONTH) CLUSTER BY entita
OPTIONS (description = 'LISTINGS_IMPRESSIONS per giorno, entità, origine (Maps/Search) e dispositivo; righe a zero omesse');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.ricerche` (
  mese DATE, entita STRING, ricerca STRING, valore INT64)
CLUSTER BY entita
OPTIONS (description = 'LISTINGS_GOOGLE_SEARCH_TERM_IMPRESSIONS per mese, entità e parola cercata');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.entita` (
  entita STRING, uid STRING, nome STRING, citta STRING, provincia STRING, chiusa BOOL)
OPTIONS (description = 'Entità Yext di tipo location: entita = codice negozio dei profili Google = store.id RetailTune');

-- Aggiornamento prima della dismissione: esportare dal giorno dopo l'ultimo caricato, poi
-- DELETE FROM ...azioni WHERE data >= '<inizio>'; (idem impressioni, ricerche per mese) e caricare in append.

-- Ricerche nelle barre Yext dei siti (esporta-ricerche-sito.ps1). Testo libero: può contenere dati personali.
CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.ricerche_sito` (
  data DATE, experience STRING, ricerca STRING, risultati BOOL, ricerche INT64, con_clic INT64)
PARTITION BY DATE_TRUNC(data, MONTH)
OPTIONS (description = 'Ricerche nelle barre Yext dei siti (kos-care-search = kosgroup.com, it-answer-anniazzurri = anniazzurri.it). Testo libero: può contenere dati personali, accesso ristretto');

-- Clic per ricerca nelle barre Yext dei siti (esporta-clic-ricerche.ps1).
CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.clic_ricerche` (
  mese DATE, experience STRING, ricerca STRING, entita STRING, tipo_clic STRING, clic INT64)
OPTIONS (description = 'Pagine aperte dai risultati della ricerca Yext dei siti, per mese e ricerca. Testo libero: accesso ristretto');
