# Tag Web Vitals (TTFB, LCP, INP, CLS) – kosgroup.com e anniazzurri.it

Misura i tempi dei **visitatori reali** e li manda a GA4 (evento `web_vitals`), quindi a BigQuery. Serve a verificare
in pochi giorni l'effetto degli interventi sul TTFB, senza aspettare i dati mensili CrUX.

## Cosa contiene ogni file di importazione
| Elemento | Nome | Note |
|---|---|---|
| Tag HTML personalizzato | `WV - Libreria Web Vitals (TTFB, LCP, INP, CLS)` | libreria Google `web-vitals` 4.2.4 (build attribution, ~12 KB) incorporata: nessun CDN esterno, nessun cookie, nessuna richiesta al server del sito |
| Tag evento GA4 | `GA4 - Web Vitals` | evento `web_vitals`, ID di misurazione `{{GA4 ID}}` (variabile già esistente nel container) |
| Attivatore | `WV - Page View <sito>` | visualizzazione di pagina, solo sull'hostname del sito (il container kosgroup gira anche sul portale TuoTempo, che resta escluso) |
| Attivatore | `WV - web_vitals` | evento personalizzato `web_vitals` sullo stesso hostname |
| 11 variabili | `DLV - WV ...` | livello dati |

Parametri dell'evento GA4: `metric_name` (TTFB, LCP, INP, CLS), `metric_value` (ms; CLS ×1000), `metric_rating`
(good / needs-improvement / poor), `metric_id`, `navigation_type`, `debug_target` (elemento LCP, elemento dell'interazione INP,
elemento che si sposta CLS) e, solo per TTFB, la scomposizione: `ttfb_waiting` (redirect e attesa iniziale), `ttfb_cache`,
`ttfb_dns`, `ttfb_connection` (connessione TCP + TLS: qui si vedono i ritardi di 1-3 s dei SYN scartati), `ttfb_request`
(risposta del server).

Volume: 4 eventi per pagina vista (circa +600.000 eventi/mese su kosgroup, +250.000 su anniazzurri): ben sotto i limiti
di GA4 e dell'esportazione BigQuery (1 milione di eventi al giorno per proprietà).
Consenso: il tag GA4 segue le stesse impostazioni degli altri tag GA4 del container (Consent Mode di Cookiebot).

Testato in Chromium: la libreria invia correttamente TTFB (con scomposizione), LCP, INP e CLS al dataLayer.

## Come importarlo (per ciascun container)
1. Tag Manager → container → **Amministrazione → Importa container**.
2. File: `GTM-5HCR54JT_web-vitals_import.json` (kosgroup) oppure `GTM-THMLNFX_web-vitals_import.json` (anniazzurri).
3. Area di lavoro: **esistente** (quella in uso) o una nuova.
4. Opzione: **Unisci** → **Rinomina tag, attivatori e variabili in conflitto** (non "Sovrascrivi").
5. Controlla l'anteprima delle modifiche: devono essere **solo aggiunte** (2 tag, 2 attivatori, 11 variabili), nessuna modifica o eliminazione.
6. **Anteprima** (Tag Assistant) sul sito: aprendo una pagina deve partire `WV - Libreria...`; cambiando scheda o chiudendo la pagina
   devono comparire gli eventi `web_vitals` e partire `GA4 - Web Vitals`. In GA4 → DebugView si vedono gli eventi con i parametri.
7. **Pubblica**.

## Facoltativo in GA4
Per vedere i dati anche nell'interfaccia GA4 (non serve per BigQuery): Amministrazione → Definizioni personalizzate →
dimensioni con ambito evento `metric_name`, `metric_rating`, `debug_target`; metriche personalizzate `metric_value` (millisecondi),
`ttfb_connection`, `ttfb_request`.

## Analisi
Query in `web-vitals-bigquery.sql` (i dati compaiono in BigQuery dal giorno successivo alla pubblicazione).
