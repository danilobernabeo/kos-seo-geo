# Screaming Frog 18/11/2026 · anniazzurri.it · verifica degli sprint 1-3 e stato generale

Uso interno. Terza scansione completa di anniazzurri.it con la configurazione del 15/11. Crawl dalle 5:08 alle 9:37 del 18/11; «Analisi scansione» eseguita; esportazione alle 9:55. Si confronta con la scansione del 15/11 (`screaming-frog-20261115-aa.md`, `-aa-generale.md`) e con le note degli sprint 1-3, le precisazioni dello sprint 2 e le correzioni del 16-17/11 nel registro.

Dati: cartella `screaming-frog\anniazzurri\2026.11.18.09.55.06` sul PC di Danilo, con 737 esportazioni e la scansione salvata (`crawl.seospider`). Ho analizzato in questo ambiente 112 CSV e 88 sorgenti di pagina: le 57 residenze, le 8 schede reparto, le 4 pagine di servizio, home, `/home-en`, `/azzurramente`, `/residenze`, 4 pagine provincia, `/news/` e altre pagine campione. Gli script, in pandas, leggono le triple del dato strutturato (`contiene_dati_strutturati_report_dettagliato.csv`, 78.631 righe su 463 pagine).

## Riepilogo

1. **Sprint 1-3 confermati su tutte le 463 pagine HTML 200:**
   - un solo JSON-LD per pagina, anche su `/home-en` e `/azzurramente` (bloccante B2 del 15/11 chiuso);
   - 0 errori di convalida;
   - 57 residenze con nodo completo e riquadro contatti uguale al nodo (57 su 57);
   - 8 schede reparto e 5 nodi `Service` nuovi del 17/11;
   - 14 `ItemList` provinciali;
   - briciole visibili uguali al `BreadcrumbList` e senza livelli verso redirect;
   - nessun markup Yext rimasto.
2. **Rilievi del 15/11 chiusi:** B2, C1, C2 (salvo un PDF), C4, C6, C8, C9 e AA 17 sulle immagini delle pagine.
3. **Da correggere prima della verifica di Cerved/ProWeb sullo sprint 2 (AA 17), rettifica di Fable:**
   - `tetto-peso.php` usa la soglia 200 × 1024 = 204.800 byte. Sopra 200.000 byte, che è quanto misura in kB decimali la scheda Rete di Chrome, restano 15 immagini delle pagine: 3 WebP servite, tra cui `…villa-pineta-1500.webp` su 7 pagine, e 12 JPEG di riserva (N1). Si risolve rilanciando lo script con `--kb=195`.
   - Ci sono poi 8 immagini Open Graph sopra soglia, non scaricate dal browser. I 5 originali delle residenze sono anche l'`image` del nodo, quindi vanno ricompressi sul posto. Su `/home-en`, `og:image` e `og:url` puntano a un dominio di sviluppo esterno in http (R1).
4. **Ancora aperti dal 15/11, nessuno bloccante:**
   - link del piè di pagina verso redirect su 461 pagine (R3);
   - `utm_` e `telto:` (R4);
   - email offuscate nei contenuti di 18 residenze (R6).
5. **Nuovi:**
   - riquadri delle 8 schede reparto non generati dal nodo: email offuscata o assente e CAP diverso su San Rocco (R5 e N4);
   - 29 pagine raggiungibili solo dalla sitemap (R7): 19 articoli, 18 esclusi dal limite di 256 notizie di `/news/`, più 10 pagine di altro tipo;
   - `og:url` diverso dal canonical su 4 pagine (N2);
   - commenti interni nei sorgenti, tra cui quello della home con il codice d'audit (R8 e N6).
6. **Rettifiche di Fable accolte:** sono nella sezione «Correzioni dopo la revisione», che prevale sulle tabelle. R2 è ritirato: la retta di Parco di Veio è nel testo della scheda.

## Metodo e limiti

- **Confronto con il 15/11:**
  - pagine HTML 200 da 462 a 463, con un solo articolo nuovo (`/saturazione-ossigeno-negli-anziani`, 16/11) e nessuna pagina sparita;
  - indicizzabili 460, tutte in sitemap.
- **Orario della scansione:** le residenze del Veneto sono state lette alle 5:23-5:27, prima dell'aggiornamento delle rette fatto da Danilo il 18/11 mattina. Il `priceRange` vecchio di Quarto d'Altino e Sant'Anna nei CSV è quindi atteso; il valore nuovo è verificato a parte (registro, 18/11).
- **Briciole:** 6 differenze apparenti tra le due estrazioni sono artefatti (doppio spazio e virgolette nell'estrazione), non difetti.
- **Non coperti:**
  - PageSpeed e controlli mobile (colonne vuote);
  - accessibilità oltre i controlli statici;
  - robots.txt e llms.txt;
  - bot AI;
  - i 380 sorgenti non copiati.
- **Richieste ai siti:** nessuna oltre alla scansione.

## Correzioni dopo la revisione di Fable (18/11)

Revisione indipendente in `screaming-frog-20261118-aa-revisione.md`. Ho ricontrollato i punti principali (sorgente di Parco di Veio, `immagini_tutti.csv`, chunk `news-list`) e accolgo tutte le correzioni. Questa sezione prevale sul resto del rapporto.

- **Pagine:** le HTML 200 sono 468, contando i 5 virtual tour (canonical verso la scheda, nessun `noindex`, N9). Le 463 citate sono le pagine con JSON-LD, compresa la variante `?utm_`.
- **AA 17 (N1):** 15 immagini delle pagine tra 200.000 e 204.800 byte. Correzione: `tetto-peso.php --kb=195` sugli stessi elenchi e sugli originali Open Graph. Il registro del 17/11 va precisato con l'unità di misura.
- **R1:** si ricomprimono i 7 originali sul posto (5 residenze, 2 news), invece di cambiare `og:image`, perché sono anche l'`image` dei nodi e di 4 voci di elenco. Su `/home-en` vanno riscritti `og:image`, `og:url`, `og:title` e `og:description`: sono in italiano e parlano di «60 residenze in 9 Regioni».
- **R2 ritirato:** la scheda dice «è di 64,86 € al giorno» e il `priceRange` è coerente con la pagina; la mia verifica cercava solo «parte da». Resta da chiedere a KOS se la retta è unica, a priorità bassa.
- **R5 riformulato:** l'email è in chiaro solo su Biancospino. È offuscata nel riquadro di La Limonaia, La Maremma e dei 2 hospice, e nel contenuto degli appartamenti di Bergamo. Manca del tutto sui centri diurni San Giuseppe e San Rocco. La correzione è il riquadro generato da `aaContatto` con l'email in chiaro, insieme a N4. Lo stesso principio vale per orari e fax, che sono nel nodo ma non nel testo (punto 1 del rapporto generale del 15/11).
- **R7:** il chunk `news-list` ha `&limit=`256``, quindi ogni notizia nuova fa uscire la più vecchia dall'elenco. La correzione minima è `&limit=`0``; la paginazione resta fuori piano. Le altre 10 pagine orfane sono 5 convegni, 3 pagine di primo livello, `/speaker-per-un-giorno` e `/melograno-argento-vivo`: per ognuna serve un link dalla pagina madre oppure «Ricercabile» spento.
- **R3 con N3:** il link del piè di pagina verso `/notizie-ed-eventi` è l'icona «Ascolta Radio Anni Azzurri». La destinazione giusta è probabilmente `/radio-anni-azzurri`, da confermare con KOS. `/il-segreto-per-tenersi-in-forma` è nell'elenco news, non nel piè di pagina: è una notizia pubblicata che risponde 301.
- **Minori corretti:**
  - `utm_source=chatgpt.com` è in un solo articolo; gli altri 7 sono `utm_source=generator` di Spotify;
  - i `Service` nuovi del 17/11 sono 4 nodi, perché lo Sportello sociale c'era già.
- **Nuovi rilievi di Fable da fare prima della consegna del 27/11:**
  - N2, `og:url` uguale al canonical: home, `/news/`, galleria del convegno 2025, `/home-en`;
  - N5, l'avviso `department` atteso su 8 residenze, da dichiarare nella nota dello sprint 3.
- **Dopo:**
  - N6, commenti di `/azzurramente` sulla trappola antispam;
  - N7, cookie `PHPSESSID` senza `Secure` e `SameSite`;
  - N8, nodi `Service` non uniformi;
  - N9, virtual tour;
  - N10, link del logo KOS verso un 301.

## Chiusura del 18/11 (stage e produzione)

Tutti i rilievi correggibili lato sito sono chiusi in giornata, prima sullo stage e poi in produzione. Il dettaglio è nel registro delle decisioni del 18/11.

**Rilievi chiusi:**
- N1, R1: nessuna immagine sopra 200.000 byte;
- N2: `og:url` uguale al canonical, compreso `/home-en`;
- R3 con N3: link interni verso redirect, link «Radio» corretto;
- R4: `utm_` e `telto:`;
- R5 e N4: riquadri dei reparti generati dai dati della pagina;
- R6: email generiche in chiaro nelle residenze;
- R7: limite dell'elenco news;
- R8 e N6: commenti interni;
- N7: cookie di sessione;
- N8: `Service` dello Sportello e di RSA Aperta;
- N9: virtual tour in `noindex`;
- N10: logo KOS;
- minori: titolo «Contatti», telefoni con +39, PDF di Santa Maria in Chienti, notizia con redirect non pubblicata.

**Controllo in produzione** (`curl` sulle pagine campione, 18/11 alle 10:59):
- `og:url` uguale all'indirizzo della pagina, anche su home e `/news/`;
- 0 `<h5>Contatti`, 0 `telto`, 0 commenti di sezione, 0 riferimenti a `fandomlab`;
- CAP 20054 su San Rocco;
- `ContactPoint` sullo Sportello;
- email RSA Aperta in chiaro su Navigli;
- pulsante degli appartamenti verso `#contatti` della pagina;
- virtual tour con `X-Robots-Tag: noindex, follow`.

**Scansione rapida dopo le correzioni** (modalità elenco, 453 URL della sitemap, 12:09; cartella `screaming-frog\anniazzurri\2026.11.18.12.09.13`):
- 453 pagine 200 e indicizzabili, un solo JSON-LD ciascuna, 0 errori di convalida;
- `og:url` uguale al canonical su tutte, nessun riferimento a `fandomlab`;
- 0 `data-yext-field`, 0 link relativi;
- breadcrumb uguali tranne `/residenze` (noto).

Gli avvisi sono solo quelli attesi: `department` su 8 residenze, i 3 degli appartamenti, `priceRange` e orari, `ScholarlyArticle` e profilo dell'autrice.

Nota: la sitemap ha una cache pdoTools di un'ora, svuotata a mano alle 11:47 (registro).

**Restano aperti**, in attesa di KOS o da dichiarare nella nota:
- R2, la retta di Parco di Veio;
- l'email degli appartamenti;
- le 10 pagine orfane non di news;
- N5, l'avviso `department` atteso.

`/cure-domiciliari-private-milano` non era nella scansione: non ha link interni né `og:url`. Va guardata con le altre landing.

## Esito dei rilievi del 15/11

| Rilievo del 15/11 | Esito al 18/11 | Evidenza |
| --- | --- | --- |
| B1 · AA 17, 118 immagini sopra 200 KB | **Risolto per le immagini delle pagine.** Restano 8 immagini Open Graph (R1) | `immagini_oltre_200_kb.csv`: 8 righe, tutte di tipo «Immagine Open Graph» in `immagini_oltre_x_kb_inlink.csv` |
| B2 · `/home-en` con 3 JSON-LD scritti a mano | **Risolto**: un solo `@graph` (`WebPage`, `WebSite`, `Organization`), lo stesso su `/azzurramente` | `jsonld_blocchi` = 1 su 463 pagine; sorgenti |
| C1 · `phpThumb.com.pdf` nella sitemap dei PDF | **Risolto**: 216 PDF, nessun file del CMS | `sitemap_url_nella_sitemap.csv` |
| C2 · PDF con il titolo di Word | **Risolto in parte**: nessun «Microsoft Word». Resta `…/santa-maria-in-chienti/Organizzazione.pdf` con titolo «Carta Intestata KOS» | `interni_pdf.csv` |
| C3 · PDF indicizzabili diversi dal piano | Invariato e deciso (registro 16/11): 167 indicizzabili più 25 con canonical verso un altro PDF, 192 in tutto; 217 `noindex` | `interni_pdf.csv` |
| C4 · briciole verso contenitori con redirect (41 pagine) | **Risolto**: nessun `item` del `BreadcrumbList` né link di briciola verso un redirect | triple; `interni_reindirizzamento_(3xx)_link_in_entrata.csv` |
| C5 · link interni verso redirect | **Aperto** (R3) | idem |
| C6 · 3 pagine fuori sitemap e indicizzabili | **Risolto**: `/home-en` e `/azzurramente` in sitemap; iscrizione al convegno `noindex, follow` | `sitemap_url_non_presenti_nella_sitemap.csv` vuoto per le HTML |
| C7 · email offuscate nel contenuto di 18 residenze | **Aperto** (R6): 18 residenze con 2 email offuscate, 39 con una (quella del direttore, prevista) | sorgenti |
| C8 · schede non residenza senza nodo; markup Yext su Biancospino | **Risolto**: 8 `#department` con `parentOrganization` verso la residenza; 0 `data-yext-field` su tutte le pagine | triple; ricerca personalizzata |
| C9 · `dateModified` prima di `datePublished` (19 articoli) | **Risolto**: 0 casi | triple |
| C10 · `BreadcrumbList` senza briciole visibili sull'iscrizione al convegno | Invariato, ma la pagina ora è `noindex`: priorità bassa | estrazioni |
| C11 · `utm_` nel banner di Cinisello, `telto:` sullo Sportello sociale | **Aperto** (R4) | `tutti_i_link_in_uscita.csv`; 404 su `/telto:800131851` |

## Verifica delle affermazioni delle note

| Nota | Affermazione | Riscontro |
| --- | --- | --- |
| Sprint 1, AA 7 | Un solo canonical, assoluto | Canonical uguale all'URL su 463 pagine. L'unica eccezione è la variante `?utm_` (canonical pulito, corretto) |
| Sprint 1, AA 8 | `Organization` di KOS Care con `parentOrganization` verso KOS S.p.A., mai `sameAs` | Confermato; nessun `sameAs` verso kosgroup.com |
| Sprint 1, AA 18 | Un solo H1 | 463 su 463 (88 sorgenti: un `<h1>`) |
| Precisazioni sprint 2 | Nessuna data al 1° gennaio 1970 | Nessuna data prima del 2000 nel dato, salvo `dateCreated` 1997 di una pubblicazione dell'autrice, legittima |
| Precisazioni, AA 3 | 216 PDF esclusi, moduli con titolo «documento – struttura» | Confermato (eccezione in C2) |
| Precisazioni, AA 8 | Livello intermedio verso la pagina di arrivo | Confermato: su `/cure-intermedie`, «Le nostre soluzioni» → `/le-nostre-soluzioni` |
| Precisazioni, AA 17 | Mappe e varianti in correzione (chiuse il 17/11) | Confermato per le pagine; vedi R1 per le immagini Open Graph |
| Sprint 3, AA 2 | Script Yext tolto, markup convertito | 0 `yextapis.com`, 0 `data-yext-field`, nessuna stringa «yext» nelle 88 sorgenti |
| Sprint 3, AA 5 e 11 | Nodo struttura su 57 schede, `["MedicalBusiness","MedicalOrganization"]`, `brand`, `parentOrganization` | 57 su 57; `brand` 57; `priceRange` 24 |
| Sprint 3, AA 21 | Email della residenza in chiaro, dal nodo | 57 su 57: indirizzo, email (`mailto:` con `&#64;`) e telefono del riquadro uguali al nodo. Precisazione: R6 |
| Sprint 3, AA 10 | 14 pagine provincia con `ItemList` | 14 (Milano 11, Genova 6, Torino 5, Ancona e Modena 3, le altre 1-2) |
| Registro 17/11 | 8 reparti, `Service` per RSA Aperta e cure domiciliari | 8 `#department`. 5 `Service`: RSA Aperta, lo stesso `@id` su `/rsa-aperta` e `/rsa-aperta-lombardia` con 4 residenze come `provider`; 2 servizi di cure domiciliari con l'ente come `provider` e `areaServed`; Sportello sociale |

## Da correggere

| # | Voce | Anomalia | Pagine ed esempi | Correzione |
| --- | --- | --- | --- | --- |
| R1 | AA 17 | 8 immagini Open Graph sopra 200 KB. Il browser non le scarica, ma il filtro di Screaming Frog le conta, ed è lo strumento che Cerved/ProWeb può usare per la verifica. Una è su un dominio di sviluppo esterno in http | Originali di 5 residenze come `og:image` (Parco di Veio 338 KB, Castagnola 334, Villa San Vincenzo 268, Cit Turin 223, Villa Pineta 213); 2 news (230 e 210 KB); `/home-en` → `http://kos.fandomlab.com/dist/assets/grid-rsa.png` (269 KB) | `og:image` dalla variante 1200 (sotto 200 KB, come le pagine) oppure originali ricompressi con `tetto-peso.php`; su `/home-en` l'immagine di condivisione del sito |
| R2 (ritirato, vedi Correzioni) | AA 11, regola KOS del 18/11 | Il `priceRange` di Parco di Veio è «64,86 €», senza «Da», e la cifra non compare nel testo della pagina. Le altre 23 residenze con retta hanno la cifra nella pagina | `/residenza-anni-azzurri-parco-di-veio` | Verificare con KOS la retta del Lazio. Se confermata, scriverla nella scheda come «La retta giornaliera … parte da X €» (poi «Da X €»); altrimenti togliere il `priceRange` |
| R3 | Regole sui link | Link interni verso redirect | Piè di pagina: `/notizie-ed-eventi` su 461 pagine (catena 301 → `/news` → `/news/`), `/domande-frequenti` su 460. Contenuto: `/cure-domiciliari` (4 pagine), `/domande-frequenti` (2), `/soluzioni/` (2), `/sos-famiglia` (1), `/le-nostre-case-in-lombardia` (1); un link del piè di pagina a `/il-segreto-per-tenersi-in-forma` | Indirizzi finali nei chunk del piè di pagina e nei contenuti, con `[[~id]]` come su kosgroup.com |
| R4 | Regole sui link | `utm_` interno e link rotto | `/residenza-anni-azzurri-pgr-cinisello` → `/homecare-academy?utm_source=…` (banner); `/sportello-sociale-orientamento-per-assistenza-anziani` → `telto:800131851` (404) | Togliere i parametri; `tel:800131851` |
| R5 | AA 21, coerenza dato e pagina | Le 8 schede reparto hanno nel nodo l'email generica della residenza (per esempio La Limonaia → `residenzabeatoangelico@…`), ma la pagina non la mostra. Il riquadro ha solo un'email personale offuscata. Fa eccezione Biancospino | 5 centri diurni, 2 hospice, appartamenti di Bergamo | Email del nodo in chiaro nel riquadro (come nelle residenze), oppure togliere `email` dal nodo dei reparti |
| R6 | AA 21 | Seconda email offuscata nel contenuto | 18 residenze (RSA Aperta, «scrivere all'indirizzo email») | Come il 15/11: escluderle da EMO, o lasciarle e precisarlo nella nota |
| R7 | Architettura | 29 articoli in sitemap senza nessun link interno: non sono nell'elenco di `/news/`. Erano 28 il 15/11 e non erano stati segnalati; nuovo `la-valenza-terapeutica-del-bello-e-dell-arte` | `pagine_orfane.csv`, per esempio `/melograno-argento-vivo`, `/convegno-santena`, `/le-vostre-storie`, `/servizi-personalizzati` | Capire perché `/news/` li esclude (template o «nascondi dai menu»), poi rimetterli nell'elenco o decidere `noindex` per quelli superati. Fuori perimetro |
| R8 | Regole del progetto | Commento interno nel sorgente della home: «H1 della home (voce AA-18): fisso, fuori dal carosello…». Due note di sviluppo su `/assistenza-e-cure-domiciliari-in-regime-privato` | Home e una pagina di servizio | Commenti MODX `[[- ]]`, come su kosgroup.com il 18/11 |

## Minori

- **Titoli (AA 6):** `<h5>Contatti</h5>` nel riquadro di 66 pagine (57 residenze, 8 reparti e `/melograno-argento-vivo`; contate sulle 88 sorgenti copiate). Gerarchia H1 › H5 › H2, invariata dal 15/11.
- **Link relativi:** 3 pagine (`/rsa-aperta-lombardia`, `/giornata-nazionale-delloss`, `/il-mantello-del-custode-…`), invariate.
- **Telefoni nel contenuto:** `href="tel:035 0867487"`, `tel:055 199751` e `tel: 035 01151` (con spazio iniziale) su 4 pagine. Il telefono dell'ente nel nodo è «800 131 851», senza prefisso.
- **H1 uguali:** `/melograno-argento-vivo` e la scheda Melograno; convegno e iscrizione sul Parkinson.
- **Fonti esterne:** `utm_source=chatgpt.com` in 8 articoli; `href="http://` in 5 pagine; 51 link esterni in 4xx (22 podcast Spreaker dei direttori, 2 app della radio).
- **PDF con due indirizzi:** `Domanda unica per l'inserimento.pdf` di San Rocco, con `'` e con `%27`.
- **`/residenze`:** briciola visibile senza `BreadcrumbList` (noto).

## Stato generale (fuori dagli sprint)

- **Server:** tempo di risposta mediano 0,18 s (0,10 il 15/11). Dipende dall'ora: 0,17-0,28 s fino alle 8, 0,95 s alle 9 sulle ultime pagine. È misurato dal PC di Danilo, quindi non è un dato del server; da ricontrollare con il monitor TTFB se la tendenza resta.
- **Profondità:** tutto entro 3 clic. `/news/` resta la pagina più pesante (467 KB).
- **Title e description:** nessuno mancante o sopra soglia; un duplicato, la variante `?utm_`.
- **Le 10 azioni del rapporto generale del 15/11** restano valide. Di quelle, la 5 (nodo per centri diurni e hospice) e la 6 (`Service`) sono ora fatte nello sprint 3.

## Cosa risulta corretto

- 463 pagine HTML 200, 460 indicizzabili, tutte in sitemap; nessuna pagina non indicizzabile in sitemap.
- Un solo JSON-LD per pagina; 0 errori schema.org e Google. Gli avvisi sono solo facoltativi: `openingHoursSpecification` su Sestri Ponente, `image` su 71 `ScholarlyArticle`, profilo dell'autrice.
- Le 57 residenze hanno `@id` = canonical + `#localbusiness`, i tipi `["MedicalBusiness","MedicalOrganization"]`, `brand` e `parentOrganization` per `@id`. Il riquadro contatti è uguale al nodo su 57 schede.
- Le 8 schede reparto hanno `MedicalOrganization` (appartamenti `LocalBusiness`) con `parentOrganization` verso il `#localbusiness` della residenza.
- I `Service` hanno `provider` e `areaServed` come da convenzione; nessun `brand` sui servizi.
- Briciole: nessun livello verso un redirect; visibili uguali al JSON-LD.
- Date: `dateModified` mai prima di `datePublished`; nessuna data del 1970.
- PDF: nessun indicizzabile senza titolo; nessun file del CMS nelle sitemap.
- Ricerche nel codice: 0 per `gclid`, `gad_source`, stage, `modx.dev`, `[[`, `yextapis.com`, `data-yext-field`, «Lorem ipsum»; nessuna `<img>` senza `alt`.
