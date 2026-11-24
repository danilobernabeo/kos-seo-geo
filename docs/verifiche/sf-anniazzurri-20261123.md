# Scansione Screaming Frog completa di anniazzurri.it (23/11/2026)

Uso interno. Configurazione `aa-completa` (0,5 URL/s), 7.849 URL, analisi della scansione eseguita prima delle
esportazioni. Esportazioni sul PC di Danilo in `screaming-frog\anniazzurri\2026.11.23.23.33.22\` (fuori da git).
Confronto con la scansione del 18/11 (`2026.11.18.12.09.13`).

## Esito in breve

Nessuna regressione dei rilasci (sprint 1-4, AA 6 del 23/11): 462 pagine HTML 200, 455 indicizzabili, nessun 4xx/5xx
interno, canonical uguale all'indirizzo su tutte le indicizzabili, un solo blocco JSON-LD su tutte le pagine del sito
(le 5 senza sono i virtual tour in iframe, `noindex`), nessun errore di dato strutturato (solo avvisi facoltativi), title
entro 65 e description entro 160 su tutte le indicizzabili, nessun duplicato, nessun resto di tag MODX, link allo stage,
Yext o CDN delle icone, nessun link interno con `gclid`/`gad_source`. Rispetto al 18/11: stessi codici, stessa
indicizzabilità, stessi canonical, H1 e blocchi JSON-LD; 7 title cambiati (le correzioni del 23/11). Tempo di risposta
delle pagine HTML: mediana 1,11 s (90° percentile 1,49 s) contro 0,10 s del 18/11: vedi anomalia 10.

## Anomalie

| # | Pagine | Cosa | Causa | Correzione proposta | Perimetro |
|---|---|---|---|---|---|
| 1 | 47 pagine con podcast del direttore (24 in 404: 23 residenze e il CDI San Rocco; 23 in 301; più 2 news in 301) | Link di riserva del widget Spreaker (`https://www.spreaker.com/user/16783839/[[*podcastDirettore]]`) | Il percorso `/user/<id>/<slug>` risponde 301 con gli slug vecchi e 404 con quelli nel formato nuovo `slug--id` e con i due valori malformati (Il Giardino, Santa Maria in Chienti). Il link è il testo di riserva del widget: i visitatori vedono il player, i crawler il link | Nei chunk `podcast-spreaker-casa-anni-azzurri` e `podcast-spreaker-news`: `href` costruito da `podcastEpisodeID` (numerico e coerente su tutte le 47 pagine), formato da provare a mano su un episodio vecchio e uno nuovo (`/episode/<id>` o `/episode/<slug>--<id>`) | elemento di AA 6 (sprint 2): **corretto in produzione il 24/11** (`/episode/<podcastEpisodeID>`, 47 episodi verificati) |
| 2 | `/validation-therapy-comunicare-con-chi-ha-la-demenza` (anche in home e `/news/`) | Immagine di copertina 241 KB (oltre la soglia di 200 KB di AA 17), anche nella copia in `cache-img` | Articolo nuovo, immagine caricata senza compressione | Sostituire l'originale con una versione compressa (≤ 150 KB); la cache si rigenera | AA 17 (sprint 2), contenuto |
| 3 | `/lutto-anticipatorio-cose-e-come-affrontarlo`, `/sensi-di-colpa-dopo-il-ricovero-di-un-genitore-in-rsa` | Link interni relativi (`href="da-figlio-a-caregiver-cosa-cambia-davvero"`, `href="come-comunicare-a-un-anziano-che-andra-in-casa-di-riposo"`) | Articoli nuovi scritti a mano | Rendere assoluti (`/da-figlio-…`); avvisare la redazione | regola del progetto, contenuto |
| 4 | `/vaccino-pneumococco-per-anziani-a-chi-serve-e-quando` | Due link esterni con `?utm_source=chatgpt.com` (epicentro.iss.it, salute.gov.it; il secondo anche 403) | Testo copiato da ChatGPT | Togliere il parametro; sostituire il link al ministero se resta 403 | contenuto |
| 5 | 7 articoli e pagine (`/il-gruppo-kos`, `/home-en`, …), `/il-sito-web-di-anni-azzurri-e-accessibile-alle-persone-con-disabilita`, `/privacy`, 2 news | Link a `https://kosgroup.com/` e `/ita/` (redirect), `https://www.kosgroup.com/it/privacy`, `polodiagnostico.it/...` (dominio storico, due indirizzi) | Link scritti prima della migrazione | Puntare a `https://kosgroup.com/it` e alla scheda del Centro Medico Riabilitativo | contenuto, minore |
| 6 | 24 pagine (province, home EN) | Script `https://unpkg.com/flickity@2/...` con redirect (versione non fissata) | Chunk delle mappe/caroselli | Fissare la versione o copia locale (come per tom-select) | prestazioni, dopo il 4/12 |
| 7 | 3 pagine in sitemap senza link interni | `/servizi-personalizzati`, `/riabilitazione-specializzata-post-ictus` (landing di campagna, già in attesa di KOS), `/oss-in-lombardia-corso-ial-e-assunzioni` (news, già senza link il 18/11) | Landing fuori menu; la news non compare nell'elenco `/news/` nemmeno con `&limit=1000` | Decisione di KOS sulle landing; per la news controllare contenitore, pubblicazione e `hidemenu` della risorsa | noto |
| 8 | `/alzheimer-cura-e-assistenza` | Landing `noindex` (template contatti, campagne) collegata da 5 articoli, tra cui `/alzheimer-sintomi-e-cura` | Link inseriti dalla redazione | Togliere il link da `/alzheimer-sintomi-e-cura` e far puntare gli altri articoli lì, oppure decidere se la landing deve essere indicizzata | contenuto |
| 9 | Link esterni rotti | 404 certi: app della radio (Play Store, App Store) su `/radio-anni-azzurri` e una news, `kosgroup.com/il-gruppo-kos-pubblica-…`, bollinirosargento.it e inps.it (dopo un redirect). 401/403 (piemontetu, sceltasociale, ondaosservatorio, salute.gov.it, karger.com): probabili blocchi dei bot | Siti esterni cambiati | Aggiornare o togliere i 404 (sostenibilità: pagina Sostenibilità di kosgroup.com); i 401/403 solo dopo una prova dal browser | contenuto |

| 10 | Tutte le pagine HTML | Tempo di risposta mediano 1,11 s (18/11: 0,10 s; verifica del pomeriggio dal PC: 0,17 s) | Cache svuotata dai salvataggi dal manager delle 16:55-16:58 (correzione dei 7 title): pagine scansionate tra le 17:42 e le 18:05, bimodali (circa 170 sotto 0,6 s, soprattutto residenze e menu già visitati; circa 290 tra 1 e 2 s, news e articoli); le pagine scansionate dopo le 20 a 0,12-0,15 s; a mezzanotte la cache è di nuovo calda | Lo scaldacache notturno non copre i salvataggi di giorno: valutare uno scaldacache lanciato dopo il salvataggio (plugin su OnDocFormSave che avvia lo script in background, sotto 1 richiesta/s) o lo svuotamento della sola cache della risorsa salvata | prestazioni |

| 11 | `/residenze` | Briciole visibili con la sola voce «Anni Azzurri» e nessun `BreadcrumbList` | Pagina corrente esclusa (`aa.breadcrumb_exclude`) | Mostrare la pagina corrente e il `BreadcrumbList` a due livelli | AA 8 |
| 12 | `/iscrizione-convegno-parkinson-realta-prospettive` (`noindex`) | JSON-LD con 3 livelli (contenitore «iscrizione eventi»), nessuna briciola visibile | Contenitore non escluso nel JSON-LD | Contenitore in `aa.breadcrumb_exclude`, stesso percorso nei due punti | AA 8, bassa |
| 13 | Circa 205 immagini con `alt=""` che sono foto | Galleria del convegno (183 file, copie con alt vuoto accanto a quelle con alt), news MUBA (15), testata di `/albero-dei-ricordi` (2), foto dell'autrice (6) | Copie duplicate e contenuti caricati senza alt | Alt descrittivo sulle foto; `aria-hidden` o niente alt sulle copie duplicate | AA 9 |
| 14 | 20 pagine (es. `/hospice`, `/cure-intermedie`, `/riabilitazione-motoria`) | og:image predefinita, invariata dal 18/11, su pagine che hanno una foto propria | TV `infoImage` vuota | Compilare la TV | bassa |
| 15 | Home | Tre icone con `src="../assets/..."` | Template | Percorsi assoluti `/assets/...` | regola del progetto, bassa |
| 16 | Centri diurni | Primo H2 «Attività proposte», nessun H2 introduttivo (le residenze e gli hospice lo hanno) | Template dei centri diurni fuori dall'intervento AA 6 | Valutare lo stesso schema delle residenze | fuori perimetro |

Revisione indipendente (Fable, 24/11 notte): confermate le righe 2, 3, 4, 6; corrette le righe 1, 5, 7, 8, 9, 10 (testo sopra già aggiornato); aggiunte le righe 11-16. Confermati anche `@id` dei `#localbusiness` uguali al canonical sulle 57 residenze, date plausibili, `ItemList` sulle 14 pagine provincia, nessuna pagina HTML non indicizzabile in sitemap, struttura H1/H2 delle 57 residenze dopo AA 6.

## Note (attese o già decise)

- 216 PDF `noindex` nella sitemap: è `sitemap-pdf-noindex.xml`, voluta per seguire la deindicizzazione (AA 3).
- 167 PDF indicizzabili non in sitemap: nessuna sitemap per i PDF indicizzabili (scelta; eventuale miglioria).
- Link `http://anniazzurri.it/...` solo dentro i PDF (carte dei servizi): rispondono 301, nessun intervento.
- Avvisi Google (facoltativi): `priceRange` sulle strutture (già nella nota), `image` e `dateModified` sulle
  `ScholarlyArticle` citate, `department` di tipo non `LocalBusiness` (scelta di modellazione); orari mancanti su Il
  Poggione, Sestri Ponente e alloggi di Bergamo; `identifier` dell'autrice non di tipo testo (`/autori/antonella-schiavino`).
- `/home-en` e `/azzurramente` senza briciole (eccezioni del 16/11); `/residenze` con la sola voce «Anni Azzurri».
- Pagina a basso contenuto: `/galleria-fotografica-convegno-anni-azzurri-2025` (51 parole, galleria).
- 1 PDF `noindex` fuori dalla sitemap dei PDF esclusi (`centro-diurno-integrato-anni-azzurri-san-rocco/Domanda unica per l'inserimento.pdf`, forse per l'apostrofo).
- Nel confronto con il 18/11: H2-1 cambiato su 62 pagine (AA 6); 2 articoli nuovi (`/lutto-anticipatorio-…`, `/terapia-della-reminiscenza-…`).
