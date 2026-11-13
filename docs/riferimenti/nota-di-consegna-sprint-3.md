# Nota di consegna dello sprint 3

13/11/2026 · Danilo Bernabeo

## Sintesi

Tutte le voci dello sprint 3 sono in produzione dall'8 novembre, in anticipo sulla consegna del 27 novembre. Le 57 residenze di anniazzurri.it hanno ora il dato strutturato e i contatti generati dal server, senza lo script Yext. Su kosgroup.com i professionisti sanitari sono descritti nel grafo unico e collegati alle strutture; il 13 novembre si aggiunge Villa Margherita Vicenza.

| Sito | Voce d'audit | Intervento | Stato | Data |
| --- | --- | --- | --- | --- |
| anniazzurri.it | 2 · Dati strutturati JavaScript | Dato delle residenze nell'HTML del server, script Yext tolto dalle schede | In produzione | 8 novembre |
| anniazzurri.it | 4 · CLS | Caroselli con l'altezza finale prima dell'avvio, spazio riservato alla barra di ricerca | In produzione | 8 novembre, ritocco 12 novembre |
| anniazzurri.it | 5 · Singola residenza: Organisation | Residenza collegata all'ente KOS Care, con marchio e immagine | In produzione | 8 novembre |
| anniazzurri.it | 9 · Alt text | Testi alternativi su foto delle news, direttrice di Volpiano, icone decorative | In produzione | 8 novembre |
| anniazzurri.it | 10 · Pagina provincia | Elenco delle residenze (ItemList) sulle 14 pagine provincia; idonee al carosello le 5 con almeno tre residenze | In produzione | 8 novembre |
| anniazzurri.it | 11 · Singola residenza: Local Business | Nodo struttura su 57 schede | In produzione | 8 novembre |
| anniazzurri.it | 21 · Scheda contatti | Riquadro contatti generato dal server dagli stessi dati del nodo struttura | In produzione | 8 novembre |
| kosgroup.com | 7 · Alt text | Pattern nel codice per gallerie, elenchi e testate; 140 immagini descritte nei contenuti | In produzione | 8 novembre |
| kosgroup.com | 11 · Singolo medico | 150 professionisti: medici IndividualPhysician, altri professionisti Person | In produzione | 8 novembre; Villa Margherita 13 novembre |

## anniazzurri.it

Ogni residenza è ora descritta da un solo nodo `{URL scheda}#localbusiness`, emesso dal server e collegato all'ente; le pagine provincia elencano le residenze con il loro URL. Prima il dato della residenza arrivava dallo script Yext, in JavaScript: i crawler AI che non lo eseguono non vedevano né la struttura né i contatti.

| Voce | Intervento | Come verificare |
| --- | --- | --- |
| 2 · Dati strutturati JavaScript | Dato strutturato e contatti delle residenze emessi dal server dalla libreria comune. Script Yext Knowledge Tags tolto dalla testata delle schede; markup Yext nei contenuti di 37 schede convertito. Il blocco iniettato da Yext, segnalato come errore atteso nella verifica dello sprint 2, non c'è più. | Sorgente di https://anniazzurri.it/residenza-anni-azzurri-villa-reale: un solo JSON-LD con `@graph`, nessuno script Yext Knowledge Tags (la barra di ricerca Yext resta fino alla sostituzione). |
| 5 · Singola residenza: Organisation | Il nodo della residenza porta nome, immagine, il marchio Anni Azzurri (`brand`) e `parentOrganization` verso l'ente `https://anniazzurri.it/#organization` (KOS Care S.r.l.). | Stessa pagina, nodo `#localbusiness`. |
| 11 · Singola residenza: Local Business | Nodo struttura su 57 schede: indirizzo, coordinate, telefono, email, specialità `Geriatric`, fax e orari dove presenti; `priceRange` sulle 24 schede che indicano la retta. Dati dall'esportazione Yext del 6 novembre, con le correzioni confermate da KOS il 9 novembre. | Rich Results Test su https://anniazzurri.it/residenza-anni-azzurri-rezzato: Attività locali, con il solo avviso facoltativo su priceRange dove la retta non è indicata. |
| 21 · Scheda contatti | Riquadro contatti scritto nell'HTML dal server, dagli stessi dati del nodo struttura: indirizzo, telefono ed email della residenza. L'email della residenza non passa dall'offuscamento JavaScript, quindi resta leggibile dai crawler; l'email del direttore resta offuscata. | https://anniazzurri.it/residenza-anni-azzurri-villa-reale con JavaScript disattivato: indirizzo, telefono ed email della residenza si vedono. |
| 10 · Pagina provincia | Sulle 14 pagine provincia un `ItemList` delle residenze nel formato del carosello di attività locali di Google: nome, URL e immagine di ogni residenza, fascia di prezzo dove la retta è indicata. | Rich Results Test su https://anniazzurri.it/rsa-in-provincia-di-genova: `ItemList` di 6 residenze valido. |
| 4 · CLS | Caroselli con l'altezza finale già prima dell'avvio dello script, spazio riservato alla barra di ricerca. Dettaglio e misure nella sezione dedicata. | PageSpeed Insights o pannello Prestazioni del browser sulla home da computer: nessuno spostamento della testata al caricamento. |
| 9 · Alt text | Dopo il lavoro sulle immagini di sfondo (voce 1, sprint 2) restavano 10 immagini senza alt e 18 foto con alt vuoto in 3 news: descritte o marcate come decorative (icone). | Sorgente di https://anniazzurri.it/grosseto-presentato-il-centro-diurno-maremma: 5 foto con alt descrittivo. |

## Stabilità visiva (AA 4)

Sulla home da computer il CLS dei visitatori reali è sceso da 0,157 a 0,077 (75° percentile), sotto la soglia «buono» di 0,1. Le pagine viste con uno spostamento sopra 0,1 sono passate dal 61% al 13%. Fonte: misura Web Vitals dei visitatori reali (GA4 su BigQuery), prima e dopo il rilascio dell'8 novembre sera. Il «dopo» copre due giorni: è un dato preliminare, da confermare con il mese di dicembre.

| Pagine | Dispositivo | CLS p75 prima (1-8/11) | CLS p75 dopo (9-10/11) | Pagine viste sopra 0,1 prima → dopo |
| --- | --- | --- | --- | --- |
| Home | computer | 0,157 (296 pagine viste) | 0,077 (122 pagine viste) | 61% → 13% |
| Altre pagine | computer | 0,077 (6.381 pagine viste) | 0,077 (2.680 pagine viste) | 19% → 18% |
| Tutte | telefono | 0 | 0 | — |

Le cause misurate erano due:

- **Home:** prima dell'avvio dello script dei caroselli le slide della testata erano impilate (2.835 px invece di 567 a 1920×1031), e all'avvio tutta la pagina risaliva. In più la barra di ricerca, scritta da JavaScript, allungava di 58 px la sezione «Siamo qui per aiutarti». È lo spostamento del video dell'audit. Correzione solo CSS: i caroselli occupano subito l'altezza finale, 58 px riservati alla barra. Verifica al pixel a 1366×768 e 390×844.
- **Tutte le pagine da computer:** circa 8 secondi dopo il caricamento si apre da sola la finestra della chat HubSpot (da 100×96 a 448×804 px). È la causa dell'85% delle pagine viste sopra 0,1 fuori dalla home, e spiega perché le altre pagine restano a 0,077. Non è un elemento del sito ma un'impostazione della chat in HubSpot: con KOS è in corso fino al 13 dicembre un confronto tra apertura automatica e solo messaggio di benvenuto, che misura anche l'effetto sui contatti.

Il 12 novembre il carosello della home è stato ritoccato: tutte le slide hanno la stessa altezza e il testo alla stessa posizione, senza effetti sul CLS. Il dato CrUX di Google, calcolato sui 28 giorni precedenti, rifletterà il miglioramento per intero da inizio dicembre.

## kosgroup.com

Su kosgroup.com le immagini senza alt erano poche; il problema erano le foto di contenuto con alt vuoto, cioè la mancanza dei pattern che l'audit chiede. Ora gli alt delle immagini ricorrenti nascono dal codice, e restano corretti anche sulle pagine che verranno pubblicate.

| Voce | Intervento | Come verificare |
| --- | --- | --- |
| 7 · Alt text | Pattern nel codice: gallerie delle strutture «nome della struttura – foto N di T», foto negli elenchi con il nome della struttura, testate di articoli, news, eventi e tecnologie con il titolo della pagina (prima l'alt era sempre vuoto), immagine di fondo pagina con il titolo (prima un testo rotto). Nei contenuti: 140 immagini su 20 pagine istituzionali in italiano e inglese, con testi scritti guardando le foto e validati; 338 segnaposto dei medici nei riquadri delle schede marcati come decorativi (alt vuoto). Restano con alt vuoto le immagini decorative o già descritte dal testo accanto. | Sorgente di https://kosgroup.com/it/centri/centro-cardinal-ferrari: foto della galleria con alt «Centro Cardinal Ferrari – foto 1 di 9»; https://kosgroup.com/it/chi-siamo/gruppo-kos/la-nostra-storia e la versione inglese: tutte le foto descritte. |
| 11 · Singolo medico | Su 150 pagine pubblicate di professionisti: medici `IndividualPhysician` `{URL}#medico` con specialità, indirizzo e telefono della sede e `practicesAt` verso la struttura; altri professionisti sanitari `Person` `{URL}#person` con `jobTitle` e `worksFor`. Dettaglio nella sezione dedicata. | Rich Results Test su https://kosgroup.com/it/chi-siamo/qualita-delle-cure/medici/stefano-albanelli (l'esempio dell'audit): Breadcrumb, Attività locali, Società. Un professionista non medico: https://kosgroup.com/it/chi-siamo/qualita-delle-cure/medici/andrea-mastriani (`Person` con `worksFor`). |

## Professionisti e Villa Margherita Vicenza (KOS 11)

Ogni pagina di professionista ha come entità principale (`mainEntity`) il nodo della persona, nell'unico grafo della pagina, collegato alla struttura in cui lavora con lo stesso identificativo del nodo della scheda. Collaudo in produzione del 13 novembre, dopo Villa Margherita: 150 pagine su 150 corrette, 99 `IndividualPhysician` e 51 `Person`.

- **Medico o altro professionista** si decide dal ruolo scritto nella pagina. Un ruolo non riconosciuto resta `Person`: descrivere come medico chi non lo è sarebbe un dato falso.
- **Specialità** dal ruolo, con la stessa corrispondenza delle schede struttura (per esempio Ortopedico → `Musculoskeletal`, Neurologo → `Neurologic`, Fisiatra → `Physiotherapy`).
- **Fonti:** titolo della pagina, ruolo, foto, struttura di appartenenza e descrizione. Un campo vuoto non esce, nessun dato inventato.

Villa Margherita Vicenza è una Casa di Cura con un solo profilo Google e due unità con pagina propria (riabilitazione e psichiatria). Dal 13 novembre la Casa di Cura è il nodo `Hospital` della pagina https://kosgroup.com/villamargherita, e le due unità sono suoi reparti (`#department`, `MedicalOrganization` con `parentOrganization` verso la sede). Gli 8 medici delle unità, prima `Person` perché la struttura non aveva un nodo, diventano `IndividualPhysician` con `practicesAt` verso la Casa di Cura. La pagina del marchio https://kosgroup.com/it/villa-margherita, che presenta le sedi di Vicenza e Benevento, resta senza nodo struttura.

Verifica del 13 novembre in produzione, sul sorgente: https://kosgroup.com/villamargherita porta il nodo della Casa di Cura e i due reparti; la pagina di un medico delle unità (https://kosgroup.com/it/chi-siamo/qualita-delle-cure/medici/francesco-perini) ha `IndividualPhysician` con `practicesAt` verso la Casa di Cura. Collegamenti dalle altre pagine del sito, inserimento nella sitemap e indirizzo del profilo Google sono in definizione con KOS e non cambiano il dato strutturato.

Restano `Person` legati all'ente due professionisti senza una sede: uno per scelta (Comitato Scientifico), l'altro in attesa che KOS indichi la struttura di riferimento.

## Scostamenti dalle proposte dell'audit

In sei punti la forma scelta differisce dall'esempio dell'audit; per ciascuno il motivo e la fonte.

| Voce | Proposta dell'audit | Scelta | Motivo |
| --- | --- | --- | --- |
| AA 11 | `LocalBusiness` per la residenza (slide 30) | `["MedicalBusiness", "MedicalOrganization"]` con specialità `Geriatric` | `MedicalBusiness` è un sottotipo di `LocalBusiness`, quindi la richiesta è soddisfatta; `MedicalOrganization` porta la specialità. Su schema.org non esiste un tipo «casa di riposo». Tutte le 57 residenze sono autorizzate come unità socio-sanitarie (verifica sulle carte dei servizi), quindi il tipo è lo stesso per tutte. |
| AA 10 | Prova su 1-2 pagine provincia (slide 33) | Tutte le 14 pagine provincia | Il markup è generato dalla stessa libreria delle schede, dagli stessi dati: non c'è una versione a mano da provare. Il carosello è una funzione di Google in prova (Spazio economico europeo, Turchia e Sudafrica) e richiede almeno tre elementi: sono idonee le 5 pagine provincia con almeno tre residenze (Milano, Genova, Torino, Ancona, Modena); le altre 9 hanno l'elenco ma non il carosello. La comparsa non è garantita ([Google](https://developers.google.com/search/docs/appearance/structured-data/carousels-beta)). |
| KOS 11 | `Physician` (slide 27) | `IndividualPhysician` | Dalla versione 24 schema.org distingue il singolo medico (`IndividualPhysician`) dallo studio (`PhysiciansOffice`); `Physician` vale per entrambi ([schema.org](https://schema.org/IndividualPhysician)). |
| KOS 11 | `worksFor` con la struttura scritta per esteso | `practicesAt` verso il nodo `#localbusiness` della struttura | `worksFor` è una proprietà di `Person`, non di `IndividualPhysician`. Il riferimento lega il medico alla stessa entità della scheda e del profilo Google. I professionisti non medici restano `Person` con `worksFor`. |
| KOS 11 | Frammento `#physician`, `jobTitle` sul medico | `#medico`; specialità in `medicalSpecialty`, niente `jobTitle` | Convenzione degli identificativi consegnata con lo sprint 1; `jobTitle` non è una proprietà di `IndividualPhysician`. Indirizzo e telefono della sede sul medico, perché Google tratta `IndividualPhysician` come attività locale. |
| KOS 11 | — | Villa Margherita Vicenza: nodo struttura sulla pagina https://kosgroup.com/villamargherita, non nella forma `/it/…` | È la pagina permanente della Casa di Cura, l'unica che la descrive per intero; l'indirizzo breve è il suo canonical. Le pagine delle unità descrivono solo il reparto. |

## Come verificare

I collaudi controllano campo per campo il dato strutturato di ogni pagina contro i dati di partenza, sull'HTML servito dal server.

| Collaudo | Pagine | Esito | Data |
| --- | --- | --- | --- |
| Schede residenza anniazzurri.it | 57 | 57 OK | 13 novembre |
| Professionisti kosgroup.com | 150 | 150 OK (99 medici, 51 altri professionisti) | 13 novembre |
| Schede struttura e landing kosgroup.com | 63 | 63 OK | 13 novembre |
| Villa Margherita Vicenza (landing e medici) | verifica sul sorgente | nodo `Hospital`, 2 reparti, medici `IndividualPhysician` | 13 novembre |

Come nei due sprint precedenti, il dato strutturato va controllato sull'HTML servito dal server, quello che leggono i crawler AI: sorgente della pagina (Ctrl+U), validator.schema.org o Rich Results Test sul codice incollato. Rapporti dei collaudi disponibili su richiesta.

## Aperti e passaggi successivi

Le voci dello sprint 3 sono chiuse lato sviluppo. Restano alcune note per KOS.

- **Chat HubSpot (AA 4):** è la causa residua del CLS da computer fuori dalla home. Il confronto tra le due impostazioni si chiude il 13 dicembre; la scelta finale è di chi gestisce HubSpot.
- **Ruoli dei professionisti (KOS 11):** refusi visibili sulle pagine («Chiururgo generale», «Chirrugo vascolare») e ruoli scritti come disciplina («Cardiologia», «Radiologia»), da correggere nei contenuti. Un professionista attende l'indicazione della struttura di riferimento.
- **Dati anagrafici delle residenze:** vengono dall'esportazione Yext del 6 novembre con le correzioni di KOS del 9 novembre. Con il passaggio a RetailTune la fonte diventa la nuova piattaforma, che alimenterà gli stessi campi delle schede.
- **Stabilità visiva nei dati di Google:** il dato CrUX di dicembre sarà il primo a coprire un mese intero dopo il rilascio.
