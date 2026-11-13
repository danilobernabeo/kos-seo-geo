-- Linea C - Statistiche dei profili Google da Yext Analytics (dal 2020-02). Creato il 12/11/2026.
-- Dataset nella stessa regione di searchconsole_kos (europe-west3).
CREATE SCHEMA IF NOT EXISTS `kos-analytics-482819.yext_kos`
  OPTIONS (location = 'europe-west3',
           description = 'Statistiche dei profili Google esportate da Yext Analytics (Linea C), dal 2020-02, prima della dismissione di Yext');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.azioni` (
  data DATE NOT NULL, entita STRING NOT NULL, azione STRING NOT NULL, valore INT64 NOT NULL)
PARTITION BY DATE_TRUNC(data, MONTH) CLUSTER BY entita
OPTIONS (description = 'LISTINGS_ACTIONS per giorno, entità e azione; righe a zero omesse');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.impressioni` (
  data DATE NOT NULL, entita STRING NOT NULL, origine STRING NOT NULL, dispositivo STRING NOT NULL, valore INT64 NOT NULL)
PARTITION BY DATE_TRUNC(data, MONTH) CLUSTER BY entita
OPTIONS (description = 'LISTINGS_IMPRESSIONS per giorno, entità, origine (Maps/Search) e dispositivo; righe a zero omesse');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.ricerche` (
  mese DATE NOT NULL, entita STRING NOT NULL, ricerca STRING NOT NULL, valore INT64 NOT NULL)
CLUSTER BY entita
OPTIONS (description = 'LISTINGS_GOOGLE_SEARCH_TERM_IMPRESSIONS per mese, entità e parola cercata');

CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.entita` (
  entita STRING NOT NULL, uid STRING, nome STRING, citta STRING, provincia STRING, chiusa BOOL)
OPTIONS (description = 'Entità Yext di tipo location: entita = codice negozio dei profili Google = store.id RetailTune');

-- Aggiornamento prima della dismissione: esportare dal giorno dopo l'ultimo caricato, poi
-- DELETE FROM ...azioni WHERE data >= '<inizio>'; (idem impressioni, ricerche per mese) e caricare in append.

-- Ricerche nelle barre Yext dei siti (esporta-ricerche-sito.ps1). Testo libero: può contenere dati personali.
CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.ricerche_sito` (
  data DATE NOT NULL, experience STRING NOT NULL, ricerca STRING, risultati BOOL, ricerche INT64 NOT NULL, con_clic INT64)
PARTITION BY DATE_TRUNC(data, MONTH)
OPTIONS (description = 'Ricerche nelle barre Yext dei siti (kos-care-search = kosgroup.com, it-answer-anniazzurri = anniazzurri.it). Testo libero: può contenere dati personali, accesso ristretto');

-- Clic per ricerca nelle barre Yext dei siti (esporta-clic-ricerche.ps1).
CREATE TABLE IF NOT EXISTS `kos-analytics-482819.yext_kos.clic_ricerche` (
  mese DATE NOT NULL, experience STRING NOT NULL, ricerca STRING, risultato STRING, tipo_clic STRING, clic INT64 NOT NULL)
OPTIONS (description = 'Pagine aperte dai risultati della ricerca Yext dei siti, per mese e ricerca. Testo libero: accesso ristretto');
