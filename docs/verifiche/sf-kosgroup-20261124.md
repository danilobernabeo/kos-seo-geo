# Scansione Screaming Frog completa di kosgroup.com (24/11/2026)

Uso interno. Configurazione `kos-completa` (creata il 23/11 da `aa-completa` più le ricerche KOS), 8.816 URL, pagine
HTML dalle 23:49 del 23/11 alle 04:51 del 24/11 (esportazioni alle 07:31); analisi della scansione eseguita prima delle
esportazioni. Esportazioni sul PC di Danilo in `screaming-frog\kosgroup\2026.11.24.07.31.59\` (fuori da git).
Confronto con la scansione del 18/11 (`2026.11.18.05.01.34`) e con le revisioni del 18/11 in `docs/revisioni/`.
Revisione indipendente (Fable) integrata: vedi l'ultima sezione.

## Esito in breve

Nessuna regressione su contenuto, indicizzazione e dato strutturato dopo i rilasci (sprint 1-4, KOS 12 del 23/11):
918 pagine HTML 200, tutte indicizzabili, nessun 4xx o 5xx interno, canonical uguale all'indirizzo su tutte, un solo
blocco JSON-LD su tutte e 918, nessun errore di dato strutturato (solo avvisi facoltativi), title entro 65 e description
entro 160 su tutte, nessun title duplicato, H1 unico e non vuoto, hreflang reciproci con `x-default` sulle 19 coppie
it/en, nessun duplicato. Ricerche nel codice a zero: resti di tag MODX, link allo stage, Yext (`yextapis.com`,
`data-yext-field`), `utm_`, `gclid`, `gad_source`, CSS vecchi; `kos.min.css` su tutte le 918 pagine.

Rispetto al 18/11: 3 news nuove (`parkinson-kos-al-senato…`, `salute-mentale-e-cuore…`,
`villa-dei-pini-inaugurata-la-clinica-dentale-kos`), una pagina di paginazione in meno (`approfondimenti?page=25`),
2 title cambiati (`/villamargherita`, afasia e disartria) e la description di `/villamargherita`, tipi medici in più su
5 schede; migliorati `/ritiro-referti` (da 404 a 301) e i redirect interni (da 6 a 0). Nessun PDF nuovo o tolto.

**Da approfondire: tempo di risposta** (anomalia 11). Oltre alla coda lenta dovuta alla cache fredda, è salito di circa
70 ms anche il tempo delle pagine già in cache (minimo 0,067 → 0,133 s; 10° percentile 0,098 → 0,167 s), su tutte le
fasce orarie e su tutti i tipi di pagina, mentre i PDF (serviti da nginx senza PHP) sono invariati (mediana 0,132 →
0,125 s). Su anniazzurri.it lo stesso scarto (0,10 → 0,17 s a cache calda). Possibile regressione di KOS 2 (voce già
verificata dall'agenzia) da spiegare prima della consegna dello sprint 4.

## Anomalie

| # | Pagine | Cosa | Causa | Correzione proposta | Perimetro |
|---|---|---|---|---|---|
| 1 | `/it/lavorare-in-kos/formazione-kos-academy` | Testo segnaposto «Lorem ipsum dolor…» nelle risposte delle FAQ 1 («Formazione continua») e 4 (attività formative ECM), nel codice servito ai crawler anche a risposta chiusa | Contenuto mai completato (già il 18/11 e nelle migliorie del 15/11) | Chiedere a KOS i due testi o togliere le due domande; con le FAQ complete, `FAQPage` dalla libreria nell'unico `@graph` | contenuto (KOS), fuori perimetro |
| 2 | 10 pagine in sitemap senza link interni (già il 18/11): 5 news di Villa dei Pini Civitanova (`avviata-la-chirurgia-oculistica-a-villa-pini`, `salute-della-donna-villa-dei-pini-rafforza-l-offerta`, `la-donna-il-centro-di-tutti-al-centro-di-tutto`, `a-villa-dei-pini-una-nuova-terapia-rigenerativa-…`, `controllo-del-peso-e-del-diabete-…`), 2 progetti speciali 2025 (`settimana-del-cervello-2025`, `giornata-mondiale-oss`), le landing `/it/villa-dei-pini` (38 parole) e `/it/parla-con-noi`, più `/villamargherita` | Orfane: nessuna pagina le collega | Le news non compaiono negli elenchi di `/it/news-e-media/news` (motivo da vedere nel manager); le landing sono pagine di campagna; `/villamargherita` è voluta (link della scheda Google, registro del 12/11) | Come per anniazzurri.it (decisione del 18/11): per ciascuna, o un link da una pagina pertinente (le 5 news dalla scheda di Villa dei Pini Civitanova o dall'elenco news) o «Ricercabile» spento (fuori sitemap e `noindex, follow`). Landing solo per annunci: fuori sitemap e `noindex`. Da decidere con Danilo/KOS | **25/11**: le 5 news hanno `soloStruttura` = 1 ed erano oltre i 12 contenuti del riquadro della scheda: ora la scheda li contiene tutti (9 visibili, «Mostra altri contenuti»); progetti speciali collegati da Danilo dal manager; `/it/parla-con-noi` falso positivo (link con `?formId=`); `/it/villa-dei-pini` e `/villamargherita` volute |
| 3 | 826 link da circa 590 pagine (più 8 immagini, vedi 14, e 1 link da PDF): «LEGGI ARTICOLO» (406 articoli), «Trova struttura» (201), «Come accedere» (86), «Indicazioni» (63), «Prenota ONLINE», «Cerca» | Link con il solo frammento (`href="#testo-articolo"`, `#blocco-mappa`…) o `href="#"`, che per il `<base href="https://kosgroup.com/">` i crawler risolvono in `https://kosgroup.com/` (301 verso `/it`) | `<base href>` più frammenti relativi nei template `informativo`, `strutture`, `tecnologie` e nei chunk delle schede. Il clic lo intercetta un gestore jQuery di `main.js` (riga 854) sui selettori `.sidebar-content a[href^="#"]`, `p a[href^="#"]`, `#intro-pagina-content a`, `#intro-pagina-foto a`; i link fuori da quei selettori (circa 15, es. `section#intro-pagina … a`) forse portano davvero in home: da provare nel browser. Già rilevato il 18/11 (punto N4, 837 link) | `href` con l'indirizzo della pagina più il frammento (`[[~[[*id]]? &scheme=`abs`]]#testo-articolo`), in cache, **insieme** al gestore di `main.js` (usare `this.hash` invece di `href^="#"`, oppure `scroll-margin-top` in CSS per lo scarto di 80 px della testata). «Cerca» e «Prenota ONLINE» (`<a id="btnCerca" href="#">`) restano link, apposta per il Conversion Linker di GTM. `<base href>` non si può togliere finché le immagini hanno `src` relativo (registro, 22/11) | regola dei link assoluti; dopo il 4/12, a consuntivo |
| 4 | `/it/centri/villa-margherita-vicenza-psichiatria`, `/it/centri/villa-margherita-vicenza-riabilitazione`, `/villamargherita` | Avviso Google Local Business: `department` di tipo `MedicalOrganization` dentro `Hospital` (Google lo vuole `LocalBusiness`) | Tipo scelto per i reparti nella stessa sede | Reparti con un sottotipo medico di attività locale (`MedicalClinic`, o `Hospital` secondo l'autorizzazione), stesso `@id` `#department` | dato strutturato delle strutture, avviso facoltativo |
| 5 | 8 articoli (`approfondimenti/assistenza-specializzata-per-le-demenze-senili`, `…/comorbilit-neurologica-nei-disturbi-psichiatrici`, `…/convegno-disturbi-alimentari`, `…/dipendenza-da-benzodiazepine`, `…/fisiatria`, `…/il-medico-risponde`, `…/test-di-valutazione-del-ragionamento-verbale-…`, `news/riolo-terme`) | `Article`/`NewsArticle` senza `image` (avviso Google Article) | Articoli vecchi senza immagine di copertina | Una copertina vera per ciascuno (il logo come riserva toglie l'avviso ma vale poco); lo stesso ripiego anche nell'elenco (anomalia 14) | KOS 10 (sprint 2), contenuto |
| 6 | 19 delle 61 pagine `/it/convenzioni/*` | `dateModified` assente nel `WebPage` | Probabilmente risorse mai modificate (`editedon` = 0, come nella riparazione del 13/11), non il template: le altre 42 convenzioni lo hanno | Nella libreria comune, ripiego `editedon` → `publishedon` → `createdon` per tutti i tipi di pagina | libreria comune (KOS 9, 12-13), minore |
| 7 | `/assets/doc/comunicati/2024/01-comitati-ospiti-aa.pdf` (indicizzabile, `Last-Modified` 31/12/2024) | Titolo dei metadati «Microsoft Word - 1 - COMITATI OSPITI_AA_31.01.24» | Sfuggito al giro dei metadati di KOS 8 (già segnalato il 18/11, revisione generale KOS riga 41): non è un PDF nuovo | Riscrivere i metadati con `pdf-metadati.php` (stage e produzione, dopo il 4/12 se la voce è sotto verifica). Tra i `noindex`, 47 hanno titolo «Microsoft Word…», 20 «Presentazione standard di PowerPoint», 3 il nome dello scanner: ininfluenti per la ricerca, utili per il controllo notturno proposto il 23/11 | KOS 8 (sprint 2, correzione) |
| 8 | `/it/centri/villa-san-giuseppe/storico-liste-di-attesa`, `/it/news-e-media/approfondimenti/informativa-tirocini-post-lauream-psicologia-2023-villa-dei-pini-firenze` | Indicizzabili, collegate, ma fuori sitemap | Escluse dalla sitemap senza `noindex` (incoerenza) | Decidere: in sitemap, oppure «Ricercabile» spento (l'informativa 2023 è scaduta) | sitemap, minore |
| 9 | 51 immagini sopra 200 KB (50 il 18/11), fino a 1,8 MB: 7 foto di medici (Brocani 1,8 MB, Beretta, Bakdounes…), `hero-bg-home.png` 1,3 MB, sfondi CdA e Collegio, galleria di Villa Sant'Alessandro; nuova `villa-dei-pini-inaugurata-la-clinica-dentale-kos.jpg` (200 KB). Anche derivati WebP in `cache-img` oltre la soglia (es. `martina-querci-2400.webp`, 288 KB) | Peso | Caricate senza compressione | Terzo giro sulle immagini (migliorie del 15/11, riga 20), con `tetto-peso.php` come per AA 17, applicato anche ai derivati | fuori perimetro (migliorie), a consuntivo |
| 10 | 5 link esterni in 404 da pagine (`asst-pavia.it/node/67` dalla scheda `/it/centri/santo-stefano-mede`; 2 voci del glossario AIFA, `handylex.org/stato/d270194.shtml`, `bollinirosargento.it/news/cerimonia-…` da articoli); 1 link `http://` (`biblioteca.comune.arco.tn.it`, news «Storie di donne» 2025); link a vecchi domini del gruppo in 301 (`anniazzurri.it` 5, `polodiagnostico.it` 3, `sstefano.it` 2, `centromedicoriabilitativo.it` 1); embed YouTube malformato `youtube.com/embed//Ebs9uhIeSIw` (303) in `/it/news-e-media/news/2026/diagnostica-per-immagini-a-modena…` | Link esterni rotti, non sicuri o con redirect | Siti di destinazione cambiati; errori di inserimento | Aggiornare o togliere i link (redazione); per i domini del gruppo, l'indirizzo finale. Gli altri 403 sono blocchi anti-robot (salute.gov.it, OUP, Johns Hopkins); i 28 dentro i PDF non si correggono dal sito | contenuto, fuori perimetro |
| 11 | Tutte le pagine HTML | Tempo di risposta: mediana 0,21 s, 75° percentile 1,13 s, 241 pagine oltre 1 s (102 il 18/11), 7 oltre 2 s (massimo 3,43 s, `reinserimento-sociale`), contro 0,12 s e 0,22 s del 18/11 | Due fenomeni. (a) Coda lenta: cache fredda dopo i salvataggi dal manager della sera del 23/11 e il rilascio di KOS 12; le pagine lente sono distribuite su tutta la scansione, con le convenzioni più colpite (mediana 1,29 s). (b) **Circa +70 ms su ogni pagina in cache**, anche sui tipi senza pagine lente (video, tecnologie, `/it/strutture`: 0,12 → 0,18 s), PDF invariati: lavoro PHP in più nato tra il 18 e il 24/11, stesso scarto su anniazzurri.it. Candidati: rilasci del 22-23/11 (foglio unico, KOS 12, AA 6), codice in `shared/`, un tag non in cache o un plugin eseguito a ogni richiesta, oppure l'hosting | (a) `scaldacache` dopo i salvataggi dal manager (come per anniazzurri.it). (b) Misurare sul server, a cache calda, il tempo di una pagina contro un file statico prima e dopo aver disattivato ciascun candidato sullo stage; controllare i plugin attivi e i tag `[[!…]]` toccati dal 18/11 | KOS 2 (verificata dall'agenzia): **da chiarire prima della consegna dello sprint 4** |
| 12 | 139 pagine con poco testo (sotto 200 parole): schede dei medici, 16 convenzioni (45-148 parole), tecnologie, `/it/ritiro-referti`, `/it/strutture`, `/it/servizi-e-cure` | Contenuto scarso | Pagine nate come schede brevi | Già nelle migliorie del 15/11 (riga 24, pagine povere); per i medici, biografia minima | contenuto, fuori perimetro |
| 13 | 172 pagine: 45 elenchi paginati, 44 servizi, 19 inglesi, 18 medici, 8 articoli, 1 convenzione | `og:image` uguale all'immagine di condivisione generica (`kos-condivisione-social.jpg`) | Pagine senza immagine propria | Immagine propria dove c'è (foto del medico, immagine del servizio) | minore, fuori perimetro |
| 14 | `news?page=15`, `approfondimenti?page=18, 19, 20, 22, 24` | `<img src="">` per gli 8 articoli senza copertina (anomalia 5): il browser richiede la pagina base come immagine | Il chunk dell'elenco non ha un ripiego per l'immagine vuota | Immagine di riserva (o nessun `<img>`) nel chunk dell'elenco, in cache | KOS 10, minore |
| 15 | 45 pagine di elenco paginate (`?page=N`) | Title con «\| Gruppo KOS» invece di «\| KOS» e refuso «Eventi e convegni- Pagina N»; `og:url` diverso dal canonical; senza marchio anche `/it/mappa-del-sito` e l'articolo sulla neurodivergenza (title uguale all'H1) | `head-paginazione` non allineato alle regole dei title | Allineare il title (marchio, spazio prima del trattino) e `og:url` al canonical nella testata delle paginazioni | title (sprint 1), minore |
| 16 | 76 pagine a livello 10 o più (massimo 14), di cui 69 approfondimenti | Profondità di scansione | Raggiungibili solo scorrendo la paginazione | Link per argomento (categorie o correlati) dagli articoli e dalle pagine di servizio | fuori perimetro, a consuntivo |

## Note (attese o già decise)

- **Ricerca `link_relativi` (902 pagine):** falso positivo. Gli unici `href` non assoluti sono gli indirizzi email
  offuscati in entità HTML (`mailto:` codificato) del piè di pagina e dei riquadri contatti, con codifica casuale che a
  volte lascia in chiaro i primi caratteri (`href="m&#97;&#x69;…`). Nuova regola nella guida (vedi
  `docs/verifica-screaming-frog.md`). Nessun link relativo al percorso fra i 154.837 link interni.
- **Estrazione `jsonld_tipi`:** sulle 14 schede con molti nodi `Organization` sembra mancare perché l'estrazione si
  ferma a 50 valori (le 14 ne hanno esattamente 50); il grafo è completo (verifica con `curl` su
  `punto-prelievi-gamma`: `WebPage`, nodo struttura, `BreadcrumbList`, `WebSite`, `Organization`).
- **`BreadcrumbList` assente su 10 pagine:** home it/en, hub (`/it/strutture`, `/it/servizi-e-cure`,
  `/en/services-and-care`), `perche-lavorare-in-kos` it/en e pagine fuori albero (`/villamargherita`, 2 progetti
  speciali): primo livello o senza briciole visibili, come previsto.
- **Description uguali:** solo sulle pagine di elenco paginate, con canonical proprio e `index, follow`: atteso.
- **89 pagine senza H2:** pagine legali, di patologia e di riabilitazione con il testo in un solo blocco; da valutare
  con i contenuti.
- **3xx esterni:** `youtube.com/@KOSGroupIT` (302, piè di pagina) e `procurement.kosgroup.com` (302 al login) sono
  normali, come i link di condivisione LinkedIn; `cdn.rawgit.com` (markerclusterer, 3 pagine con mappa) è un servizio
  dismesso che reindirizza: copia locale nel prossimo giro sui JS (fuori perimetro).
- **PDF:** 603, tutti 200; 219 indicizzabili (carte, pieghevoli, comunicati, come da `pdf-noindex.conf`), 369
  `noindex`, 15 con `Link rel="canonical"` verso la copia identica; fuori sitemap per scelta (sitemap solo HTML).
- **Avvisi del dato strutturato** (nessun errore): `priceRange`, `geo` e `openingHoursSpecification` sui nodi struttura
  e sui 99 `IndividualPhysician` (facoltativi); `duration` e `creator` sui 21 `VideoObject`.
- **Alt delle immagini:** `img_senza_alt` a zero su tutte le pagine.

## Revisione indipendente (24/11, Fable)

Ricalcolo completo dai CSV del 24/11 e del 18/11 e una ventina di richieste a kosgroup.com. Confermati i conteggi
dell'esito e delle anomalie e i due falsi positivi (email offuscate, limite di 50 valori). Correzioni integrate sopra:

- tempo di risposta: la cache fredda spiega solo la coda; l'aumento di circa 70 ms sulle pagine in cache, con i PDF
  invariati, è lavoro PHP in più (anomalia 11 e «Da approfondire» nell'esito);
- il PDF del comunicato non è nuovo ma sfuggito a KOS 8; conteggi dei titoli dei `noindex` corretti (anomalia 7);
- regola `link_relativi`: la prima correzione (`|&#`) non bastava per la codifica casuale;
- anomalia 3: gestore del clic in `main.js` (non `scroll-anchors.js`), da aggiornare insieme agli `href`; `btnCerca`
  resta link per GTM; problema già nel punto N4 del 18/11;
- codici: `Article` è KOS 10, `dateModified` della libreria comune; causa di `dateModified` assente sulle convenzioni
  (risorse, non template);
- dettagli: news orfane 5 (non 6), decisione su `/villamargherita` del 12/11, convenzioni povere 16 (45-148 parole),
  ripartizione delle 172 `og:image` generiche, `asst-pavia` dalla scheda di Mede, durata della scansione;
- aggiunte: `<img src="">` negli elenchi (14), title delle paginazioni (15), profondità (16), embed YouTube e domini
  del gruppo in 301 (10), derivati WebP oltre 200 KB (9), differenze dal 18/11 (esito).
