# Google Cloud di KOS: progetti, dataset e flussi (uso interno)

Stato al 20/11/2026. Questa è la mappa da leggere prima di attivare o modificare un flusso di dati: vale per il progetto e per i lavori extra. Le decisioni e i dettagli sono nel registro delle decisioni, alle date indicate.

## Account e progetti

| Progetto | A cosa serve | Note |
| --- | --- | --- |
| `kos-analytics-482819` | magazzino dei dati di analisi: GA4, Search Console, Google Ads, Yext, tabelle di corrispondenza | località di tutti i dataset: **europe-west3** (Francoforte) |
| `kos-ricerca` | prova della ricerca con Vertex AI Search (linea C) | archivi e app nella regione `eu`; Cloud Run non ancora attivo; termini sulla protezione dei dati da confermare (scheda del DPO) |
| `import-gsc` (dell'agenzia Cerved/ProWeb) | esportazione in blocco di Search Console dei due siti | europe-west6. KOS ha accesso in lettura (BigQuery Data Viewer, account marketing) |

- Gli account Google usati sono marketing.kos2019@gmail.com («KOS group») e quello di Danilo.
- La fatturazione di `kos-ricerca` è sull'account «Kos Care».
- I segreti (chiavi, token) stanno fuori dal repository; le chiavi della prova Vertex sono sul PC di Danilo, in `kos-ricerca-chiavi`.

## Dataset in `kos-analytics-482819`

| Dataset | Contenuto | Come arriva | Dal |
| --- | --- | --- | --- |
| `analytics_333083624` | GA4 di kosgroup.com: solo eventi, giornaliero | collegamento GA4 → BigQuery | 30/12/2025 |
| `analytics_250584060` | GA4 di anniazzurri.it: solo eventi, giornaliero | collegamento GA4 → BigQuery | 24/10/2026 |
| `gsc_kos`, `gsc_aa` | Search Console, esportazione in blocco | copia giornaliera con BigQuery Data Transfer dai dataset dell'agenzia in `import-gsc` (europe-west6 → europe-west3), decisa il 16/11 | 16/3/2025 |
| `searchconsole_kos`, `searchconsole_aa` | Search Console dall'API (`storico_site_totali`, `storico_site_impression`, `storico_url_impression`) | `scripts/gsc/storico-search-console.py` (OAuth del progetto) | 14/6/2025-23/10/2026, ora solo archivio |
| `google_ads_kos` | Google Ads, account 8545219698 | Data Transfer di Google Ads | attivo |
| `yext_kos` | export Yext: entità, ricerche nelle barre dei siti, clic, impressioni, azioni | caricamenti dalla console (colonne senza NOT NULL) | 2025-2026 |
| `kos_lookup` | corrispondenze di campagne, aree e URL | tabelle mantenute a mano | — |

Da usare anche: `chrome-ux-report.materialized.country_summary` (dataset pubblico) per le prestazioni reali per origine.

## Regole dei flussi

- **Località:** ogni dataset nuovo va in **europe-west3**, nello stesso progetto. Una query non può unire dataset di regioni diverse: per questo i dati di Search Console dell'agenzia si copiano.
- **GA4:**
  - si esportano solo gli eventi, con esportazione giornaliera;
  - l'esportazione dei dati utente è disattivata per minimizzazione (decisione del 24/10);
  - la conservazione in GA4 è di 14 mesi;
  - il collegamento non recupera lo storico: per i mesi precedenti si usa l'API di GA4.
- **Search Console:**
  - una proprietà ammette **una sola** esportazione in blocco;
  - se l'agenzia la usa già (in `import-gsc`), non se ne crea un'altra: si chiede l'accesso in lettura e si copia con Data Transfer, come per i due siti;
  - se non è usata, l'esportazione si fa direttamente verso `kos-analytics-482819`.
- **Nomi dei dataset:**
  - `analytics_<id proprietà>` per GA4, nome dato da Google;
  - `gsc_<sigla>` per l'esportazione di Search Console;
  - `<fonte>_<sigla>` per il resto. Ad esempio, per Diagnostica Villa Rosa la sigla è `vrd`.
- **Ambienti di prova:** in GA4 di kosgroup.com ci sono anche gli eventi di `*.modx.dev` e degli stage; nelle query si filtra per `device.web_info.hostname`.
- **Dati personali:**
  - le ricerche Yext (`yext_kos.ricerche_sito`) sono testo libero: si leggono solo come conteggi per parola chiave;
  - le esportazioni con dati personali (ad esempio TuoTempo) si aggregano sul PC di Danilo prima di entrare nel progetto o nel repository.
- **Costi:** le query della console si fanno con limiti di byte quando le tabelle sono grandi, perché l'esportazione di Search Console pesa circa 6,6 GB.

## Per un nuovo sito (esempio: villarosadiagnostica.it)

1. **GA4:** nella proprietà vai su *Amministrazione → Collegamenti ai prodotti → BigQuery* e imposta:
   - progetto `kos-analytics-482819`;
   - località europe-west3;
   - esportazione giornaliera, solo eventi, dati utente disattivati.
2. **Storico GA4:** scaricarlo dall'API e caricarlo in `ga4storico_<sigla>`.
3. **Search Console:** controlla in *Impostazioni → Esportazione collettiva dei dati* se un'esportazione esiste già.
   - Se esiste, è dell'agenzia: si chiede l'accesso e si copia in `gsc_<sigla>`.
   - Se non esiste, la crei verso `kos-analytics-482819`, nel dataset `gsc_<sigla>`, località europe-west3.
4. **Storico di Search Console:** si scarica con `scripts/gsc/storico-search-console.py` in `searchconsole_<sigla>`.
5. **Registro:** si annota il flusso in questo documento e nel registro (o nel README del lavoro extra).
