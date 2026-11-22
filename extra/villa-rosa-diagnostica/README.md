# Diagnostica Villa Rosa: analisi e restyling tecnico di villarosadiagnostica.it (fuori dal progetto SEO/GEO)

Lavoro esterno al progetto, commissionato a parte dal referente marketing di Danilo. Riguarda il sito
indipendente https://villarosadiagnostica.it (MODX Cloud, sviluppato e gestito da Danilo). Usa gli strumenti
del repository, ma non è una voce d'audit e non compare nella documentazione del progetto. Si applicano le
regole del dato strutturato del progetto (un solo `@graph`, `@id` stabili, `parentOrganization` verso
KOS S.p.A., mai `sameAs` tra entità diverse).

## Scopo

Partendo dai dati, arrivare a una proposta completa di restyling tecnico e di contenuti, in vista
dell'apertura della nuova palazzina della diagnostica (7/1/2027). La proposta deve rendere il sito più
efficace per l'utenza locale di Modena: ricerca su Google e nella mappa, assistenti AI, prenotazione e
contatto.

## Contesto (dal piano marketing «DIAGNOSTICA VILLA ROSA_piano apertura 2027», gennaio 2027)

- **Nuova sede.** Il polo diagnostico si sposta in una nuova palazzina adiacente alla vecchia (Via Fratelli
  Rosselli / via Asiago, Modena; la sede attuale è in Via Fratelli Rosselli 83, confermato da Danilo il 22/11), con ingresso indipendente e nuovo indirizzo. Una risonanza resta attiva
  nella vecchia sede. Il cantiere chiude il 30/11/2026 e l'apertura è prevista per il 7/1/2027.
- **Offerta.**
  - Novità: risonanza magnetica (Siemens 1,5 T, esami neurologici, muscoloscheletrici e «body») e TAC.
  - Già presenti: OPT e TC Cone Beam, RX, ecografie, ecocolordoppler ed elettromiografie.
  - Nuova offerta ambulatoriale.
  - Attività in convenzione SSN e privata.
  - Prenotazione online su TuoTempo, oltre a telefono e sportello.
- **Nome.** «Diagnostica Villa Rosa». Il dominio villarosadiagnostica.it resta lo stesso: secondo l'agenzia
  arrivano circa 1.640 visite organiche al mese (settembre 2026).
- **Punti di attenzione:**
  - «Villa Rosa» è associata alla psichiatria (c'è anche la clinica psichiatrica Villa Rosa di KOS).
  - Il pubblico percepito è oltre i 60 anni, mentre l'età media in città è di 48.
  - A Sassuolo c'è un omonimo che non fa parte del gruppo, il Centro Medico Kos.
- **Concorrenti indicati:** Hesperia (Gruppo Garofalo), Affidea Modena Medica, Ospedale Fogliani.
- **Calendario marketing:**
  - metà dicembre: mail ai contatti;
  - 7/1: apertura;
  - fine gennaio: inaugurazione;
  - fine gennaio-febbraio: campagne digitali e pubblicità tradizionale;
  - marzo-maggio: incontri con i medici di base e open day.

## Contenuto della cartella

| Percorso | Cosa |
|---|---|
| `piano-analisi.md` | piano dettagliato delle analisi, con le fonti, i metodi, i risultati attesi e le decisioni che ne dipendono |
| `sintesi-fase-1-abc.md` | fase 1: sito, contenuti, misurazione (analisi A, B, C) |
| `sintesi-fase-2.md` | fase 2: traffico, funnel di prenotazione, misurazione, verifiche tecniche (21/11/2026) |
| `sintesi-marca-tre-pagine.md` | la marca «villa rosa» divisa tra sito, scheda su kosgroup.com e clinica psichiatrica |
| `sql/` | interrogazioni BigQuery riproducibili |
| `docx/` | versione Word dei documenti |
| `dati/` | estrazioni e risultati delle analisi (senza dati personali) |

## Decisioni

| Data | Decisione | Fonte |
|---|---|---|
| 20/11/2026 | Progetto a parte per il referente marketing. Analisi completa e dettagliata, senza limite di tempo | Danilo |
| 20/11/2026 | Il dominio resta villarosadiagnostica.it, con il nuovo nome «Diagnostica Villa Rosa» | piano marketing |
| 20/11/2026 | Il marchio resta KOS. L'immagine grafica può differire, entro i limiti del marchio | Danilo |
| 20/11/2026 | GA4 e Search Console del sito vanno in BigQuery nel progetto `kos-analytics-482819`, località europe-west3, sigla `vrd`, seguendo `docs/riferimenti/google-cloud-kos.md` (per Search Console verificare prima se l'agenzia ha già l'esportazione in blocco) | questa chat |
| 20/11/2026 | Una sola scheda Google: niente scheda a parte per la risonanza che resta nella vecchia sede (tema ancora aperto lato struttura) | Danilo |
| 20/11/2026 | Prenotazioni (web e telefono) dal backend di TuoTempo, consultato da Danilo con il massimo dettaglio. Nel repository solo dati aggregati, senza dati personali | Danilo |
| 20/11/2026 | Foto e testi della nuova sede disponibili dopo la metà di dicembre: le pagine per il 7/1 si preparano con modelli e contenuti provvisori | Danilo |
| 20/11/2026 | Prezzi e tempi di attesa da chiedere alla struttura. Numero di telefono dedicato al sito difficile, Danilo chiede comunque | Danilo |

| 21/11/2026 | Flussi BigQuery attivi: GA4 (proprietà 317279845, dataset `analytics_317279845` atteso con la prima esportazione), Search Console in blocco `searchconsole_gsc_vrd` (dal 20/11), storici `searchconsole_vrd` (8/7/2025-19/11/2026) e `ga4storico_vrd` (1/6/2022-19/11/2026). Eccezione al nome: `searchconsole_gsc_vrd` | Danilo, questa chat |
| 21/11/2026 | Dominio `villarosa.it` (posta attiva, sito morto): decisione dopo la risposta del referente marketing | Danilo |
| 21/11/2026 | Le conclusioni sul calo di traffico restano ipotesi finché mancano le schermate dei risultati di Google, il registro del manager MODX e la verifica del consenso | Chat principale |
| 22/11/2026 | Indirizzo della sede attuale: Via Fratelli Rosselli 83 (il «Rossetti» era un errore) | Danilo |
| 22/11/2026 | Modifica a `scripts/ga4/storico-ga4.py` (opzione `--tabelle`, 5 tabelle opzionali) approvata; lo script resta del progetto principale, modifiche future da coordinare prima del commit | Chat principale |
| 22/11/2026 | Richiesta dati TuoTempo: tabella provenienza solo per provincia e senza prestazione; CSV fuori dal repository, solo tabelle elaborate; nessun passaggio con il DPO, Danilo informa il referente marketing | Chat principale |
| 22/11/2026 | La scelta della pagina principale per le query generiche (scheda kosgroup, sito Diagnostica o ruoli distinti) è di KOS/marketing: la proposta presenta le alternative. Richieste a Danilo al minimo fino al 26/11 (sprint 4) | Chat principale |

## Rilasci e ripristino

Da definire con la proposta. Valgono le regole del repository: installazione con script ripetibili (prova,
`--scrivi`, backup, ripristino), prima sul cloud di sviluppo e poi in produzione. Niente rilasci in
produzione il giorno prima di una consegna di sprint del progetto né durante le settimane di verifica di
Cerved/ProWeb.
