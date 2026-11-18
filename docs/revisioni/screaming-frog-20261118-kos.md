# Screaming Frog 18/11/2026 · kosgroup.com · verifica degli sprint 1-3

Uso interno. Seconda scansione completa di kosgroup.com con la configurazione `kos-completa`. È stata avviata la sera del 17/11 (cookie di sessione del crawler emessi alle 22:11), è conclusa entro le 4:58 del 18/11 ed esportata alle 5:01, con 0,5 URL/s. Si confronta con la scansione del 15/11 (`docs/revisioni/screaming-frog-20261115-kos.md`) e con le note di consegna degli sprint 1-3 e le precisazioni dello sprint 2.

Dati: cartella `screaming-frog\kosgroup\2026.11.18.05.01.34` sul PC di Danilo: 754 esportazioni e la scansione salvata. Ho analizzato in questo ambiente 98 CSV e 50 sorgenti di pagina a campione. Gli script sono in pandas e leggono le triple del dato strutturato (`contiene_dati_strutturati_report_dettagliato.csv`, 144.677 righe su 916 pagine).

## Riepilogo

1. **I tre bloccanti del 15/11 sono risolti in produzione:**
   - nessun errore di convalida del dato strutturato (erano 308 pagine con «address obbligatorio»);
   - nessuna foto delle strutture con alt vuoto negli elenchi della riabilitazione (erano 49 su 12 pagine);
   - nessuna immagine senza attributo `alt` (erano 17).
2. **Base degli sprint 1-3 confermata su tutte le 916 pagine HTML 200:**
   - un solo blocco JSON-LD per pagina, H1 presente, canonical uguale all'URL;
   - nessuna data anteriore al 2000 e nessun 1970;
   - hreflang reciproci;
   - sitemap di 868 URL, tutti 200 e indicizzabili;
   - nessun parametro `utm_`/`gclid`, tag MODX, link allo stage o a `modx.dev`.
3. **Rilievi del 15/11 chiusi dopo quella scansione:**
   - le 3 schede senza nodo (Villalba, Santo Stefano Casorate e Mede) ora lo hanno;
   - `/villamargherita` è nella sitemap;
   - il commento del chunk `head` non esce più.
4. **Imprecisione nelle precisazioni dello sprint 2, già inviate, da valutare:** l'esempio KOS 8 («la carta dei servizi di Punto Prelievi Biemme, PDF indicizzabile») porta un canonical verso la carta di Punto Prelievi Delta. Per Screaming Frog, e per Google, è un PDF «trasformato in canonical», non indicizzabile in quanto tale. Vedi I1.
5. **Rilievi ancora aperti, nessuno bloccante:**
   - `brand` sulle schede, in attesa della conferma di KOS sui marchi dismessi;
   - descrizione del nodo di Villa Margherita con un numero diverso dal telefono;
   - 6 link relativi in un articolo;
   - la landing `/villamargherita` non è collegata da nessuna pagina;
   - 3 voci delle «aree di intervento» con link vuoto verso la home, già presenti il 15/11 e sfuggite a quella revisione (I2);
   - commenti interni ancora nell'head di 13 sorgenti su 50 (C6, rettifica di Fable).

## Metodo e limiti

- **Confronto:** pagine HTML 200 914 → 916. Nuove: la news «Villa Pineta: nuovo servizio MOC…» e `/villamargherita`, che il 15/11 non era raggiungibile dal crawler e ora lo è dalla sitemap. Nessuna pagina sparita.
- **Sitemap:** l'«analisi della scansione» di Screaming Frog non è stata eseguita, quindi i filtri Sitemap e «livello di scansione» sono vuoti. L'elenco della sitemap è ricostruito dai link di tipo «Sitemap XML» (868 destinazioni, `tutti_link_in_entrata.csv`). Le pagine orfane sono quelle del rapporto `pagine_orfane.csv` (10).
- **Breadcrumb:** l'estrazione `briciole_visibili` non c'è ancora nella configurazione (rilievo C5 del 15/11). Ho controllato 50 sorgenti a campione con l'XPath giusto (`nav#breadcrumbs`): 42 con nomi e link identici tra visibili e `BreadcrumbList`, 8 senza nessuno dei due (home IT/EN, `/it/servizi-e-cure`, `/en/services-and-care`, `/it/strutture`, `/it/lavorare-in-kos/perche-lavorare-in-kos`, Settimana del cervello, `/villamargherita`).
- **Non coperti:** PageSpeed (nessuna chiave API nella configurazione), robots.txt e llms.txt, bot AI, le 5 landing `/it/landing/…` (non collegate), metadati dei PDF oltre al titolo.
- **Richieste ai siti:** nessuna oltre alla scansione.

## Esito delle anomalie del 15/11

| Rilievo del 15/11 | Esito al 18/11 | Evidenza |
| --- | --- | --- |
| B1 · 308 pagine con «address obbligatorio» (148 professionisti, 160 articoli e video) | **Risolto**: 0 errori Google e schema.org. Sui riferimenti restano solo avvisi facoltativi (`priceRange`, orari, `geo`) | `report_riepilogo_errori_dati_strutturati.csv`: nessuna riga «Errore»; i 99 `practicesAt` hanno `address` e `telephone` |
| B2 · KOS 7, 49 foto delle strutture con `alt=""` su 12 pagine di riabilitazione; 17 immagini senza `alt` | **Risolto**: 0 foto di `/img/strutture/` con alt vuoto, 0 immagini senza attributo `alt`, colonna `img_senza_alt` a 0 su tutte le pagine | `link_in_entrata_a_immagini_senza_testo_alt.csv`; sorgente di `…/riabilitazione-neurologica`: `alt="Centro Ambulatoriale Santo Stefano Ascoli Piceno"` |
| C1 · 3 schede senza nodo | **Risolto**: 61 schede con `#localbusiness` più 2 unità `#department`, su 63 | triple; Villalba, Casorate e Mede `MedicalClinic` con indirizzo e telefono, senza `geo`, orari e specialità |
| C2 · `brand` su 43 schede | **Aperto, previsto**: invariato (Santo Stefano Riabilitazione 22, Neomesia 14, Polo Diagnostico 4, Myolab, Fidia, Villa dei Pini). Il file per svuotare 40 schede è pronto (`marchi-dismessi-2026-11-15.json`) e attende la conferma di KOS (registro 16/11) | triple |
| C3 · og:image predefinita sulle cure di psichiatria | Invariato, resta come miglioria | — |
| C4 · Villa Margherita | **In parte.** Fatto: sitemap; avviso `department` documentato. Aperto: `description` del nodo `Hospital` con «Chiama lo 0444 043322» e `telephone` `+39044415851`; nessun link da altre pagine (orfana, in sitemap); `knowsAbout` dei reparti quasi uguali (27 e 28 voci) | triple; `pagine_orfane.csv`; sorgente della landing: 0444 043322 ripetuto 8 volte, 044415851 una |
| C5 · estrazione `briciole_visibili` vuota | Aperto (configurazione): controllo a campione, vedi Metodo | — |
| C6 · tag MODX nel commento del chunk `head` | **Risolto in parte**: il commento del chunk `head` non c'è più e nessun tag MODX è eseguito. Restano però commenti HTML interni in 13 sorgenti su 50: «Extra delle schede struttura … (#localbusiness) è nel @graph unico» (8), «Barra di ricerca Yext Answers … dismissione di Yext (linea C)» (4), «Extra delle landing … kosSchema (voce 24)» (1) | sorgenti a campione (rettifica di Fable) |
| C7 · 6 link relativi in un articolo | Aperto: stessi 6 link in `…/sclerosi-multipla-tra-ricerca-cura-e-riabilitazione` | `relativo_al_percorso_link_in_uscita.csv`, tipo «Link ipertestuale» |
| C7 · 9 pagine in sitemap senza link interni | Aperto: le stesse 9 più `/villamargherita` | `pagine_orfane.csv` |
| C7 · 2 medici senza `medicalSpecialty` | Invariato (Calorio, Ali) | triple |
| C7 · 8 articoli senza `image` | Invariato (avviso Google Article) | report errori |

## Verifica delle affermazioni delle note

### Sprint 3 (nota del 13/11)

| Affermazione | Esito |
| --- | --- |
| KOS 7: gallerie «nome della struttura – foto N di T» | Vero: `alt="Centro Cardinal Ferrari – foto 1 di 9"` |
| KOS 7: foto negli elenchi con il nome della struttura | Vero dal 15/11 sera su tutte le pagine (vedi B2) |
| KOS 7: 338 segnaposto dei medici marcati come decorativi (alt vuoto) | Vero: nessun segnaposto senza `alt`; `medico-thumb.svg` con `alt=""` su 50 pagine |
| KOS 7: «la nostra storia», tutte le foto descritte | Vero: le 14 immagini con `alt=""` sono icone, frecce e il banner comune |
| KOS 11: 150 professionisti, 99 `IndividualPhysician` e 51 `Person` | Vero (150 = 99 + 51). Tutti i medici hanno `practicesAt` verso un `#localbusiness` con indirizzo; i `Person` hanno `worksFor` e `jobTitle`; tutte le pagine hanno `mainEntity` |
| KOS 11: «medici con specialità» | 97 su 99: Calorio e Ali senza `medicalSpecialty` (ruolo senza corrispondenza); la nota non lo dice |
| KOS 11: Rich Results Test su `…/stefano-albanelli` senza errori | Ora vero (prima del 15/11 no) |
| Correzione del 15/11 (riferimenti con indirizzo e telefono, 308 pagine senza errori) | Vero |
| Villa Margherita: nodo `Hospital`, 2 reparti `MedicalOrganization` con `parentOrganization`, medici `IndividualPhysician` con `practicesAt` | Vero. Avviso Google facoltativo su `department` (tipo consigliato `LocalBusiness`) su 3 pagine: la nota non lo cita |
| «Inserimento nella sitemap … in definizione» | Superato: in sitemap dal 17/11 |

### Sprint 2 (nota del 6/11 e precisazioni del 16/11)

| Affermazione | Esito |
| --- | --- |
| Date al 1970 corrette il 13/11 | Vero: nessuna data anteriore al 2000, nessun `dateModified` prima di `datePublished` |
| Articoli: `mentions` con indirizzo e telefono dal 15/11 | Vero (0 errori su 160 articoli e video) |
| «oggi le sitemap contano 866 URL su kosgroup.com» | Era vero il 16/11; ora 868 (`/villamargherita` e la news MOC del 17/11), tutti 200 e indicizzabili |
| KOS 24: nodo struttura «con indirizzo, telefono, email, coordinate, orari, specialità dove mappata, servizi…» | Su 62 nodi (61 schede e la landing): indirizzo e telefono 62, coordinate e orari 59, email 52, specialità 52, servizi 52, catalogo prenotabile 17. Mancano coordinate e orari alle 3 schede del 16/11, coerente con «nessun dato inventato» |
| KOS 24: tipi | `MedicalClinic` 53, `DiagnosticLab`+`MedicalBusiness` 5, `Dentist` 2, `Hospital` 2 (Villa dei Pini Civitanova e `/villamargherita`). Ospedale San Pancrazio è `MedicalClinic` per scelta dell'import (`str.tipo` scritta, sito che lo elenca tra le cliniche psichiatriche): domanda per KOS, non difetto |
| KOS 21-23: marchio una volta nel title | Vero. Restano 3 title senza marchio (mappa del sito, 2 articoli) e 2 con «| Kos» (check-up), già noti. La landing `/villamargherita` usa «— KOS» |
| KOS 8: PDF | 603 collegati, tutti 200: 219 indicizzabili, 15 copie con canonical, 369 `noindex` |
| **KOS 8, precisazioni: «la carta dei servizi di Punto Prelievi Biemme, che è un PDF indicizzabile (nessun `noindex`, intestazione `Link` canonical)»** | **Impreciso**: vedi I1 |
| KOS 8, precisazioni: `casa-villa-maura/contratto.pdf` con `X-Robots-Tag: noindex` | Vero |

### Sprint 1

| Punto | Esito |
| --- | --- |
| Link interni assoluti | Vero salvo i 6 link dell'articolo sulla sclerosi multipla (C7). Gli altri «relativi» sono ancore `href="#…"` risolte verso la home da `<base href>` (837 link su 589 pagine) e immagini: per l'utente funzionano, ma Screaming Frog e i crawler le leggono come link alla home |
| Nessun URL annidato `/it/.../it`, nessun 200 su pagine inesistenti | Nessun annidato trovato; 404 interni solo `/ritiro-referti` (link dentro un PDF) e un'immagine mobile della Settimana del cervello, come il 15/11 |
| Robots: form con `?formId=` bloccati | 60 URL bloccati, come previsto |
| Hreflang | 38 pagine con hreflang, nessun errore su link di ritorno, 200 o canonical |
| Prestazioni | Tempo di risposta HTML: mediana 0,12 s (0,24 il 15/11), 75° percentile 0,22 s (1,18), 5 pagine oltre 1,5 s (104). Effetto della cache scaldata prima della scansione: da non presentare come miglioramento strutturale |

## Rilievi

### I1 · Esempio KOS 8 delle precisazioni dello sprint 2 (da valutare prima che Cerved/ProWeb lo provi)

- **Fatto:** `https://kosgroup.com/assets/doc/strutture/punto-prelievi-biemme/carta-dei-servizi.pdf` risponde 200 senza `noindex`, ma con `Link: <https://kosgroup.com/assets/doc/strutture/punto-prelievi-delta/carta-dei-servizi.pdf>; rel="canonical"`. Screaming Frog lo classifica «Non indicizzabile – trasformato in canonical», come le altre 14 copie.
- **Perché conta:** chi verifica con un crawler o con l'URL Inspection di Search Console trova un PDF non indicizzabile in proprio, mentre il testo dice «indicizzabile». Il comportamento è corretto, perché le copie puntano all'originale: è l'esempio che è scelto male.
- **Peso:** basso. Solo le 15 copie hanno l'intestazione `Link`; i 219 PDF indicizzabili non ne hanno (quindi «intestazione `Link` canonical» non descrive un PDF indicizzabile tipico).
- **Proposta (rivista con Fable):** tenere Biemme come esempio e correggere solo la descrizione: «copia della carta di Punto Prelievi Delta, nessun `noindex`, intestazione `Link` con canonical verso l'originale». Rettifica in una riga solo se Cerved/ProWeb non ha ancora verificato KOS 8. Decide Danilo.

### I2 · Tre voci delle «aree di intervento» con link vuoto (verso la home), già presenti il 15/11

- **Pagine:** `/it/centri/centro-ambulatoriale-santo-stefano-filottrano` e `…-san-severino` («Medicina dello Sport»), `/it/centri/ospedale-san-pancrazio` («Riabilitazione da dipendenze»).
- **Evidenza:** `pagine_con_tag_di_ancoraggio_vuoti.csv`: link relativo che si risolve in `https://kosgroup.com/` (301 verso `/it`), nel riquadro `#aree-intervento`. Le stesse tre righe sono nel CSV dei redirect del 15/11 (`reindirizzamento_(3xx)_link_in_entrata.csv`): sfuggite a quella revisione, non nuove.
- **Causa probabile:** voci dell'elenco della scheda senza pagina collegata (`href=""`). Destinazioni naturali: `…/ambulatori-e-medicina-specialistica/medicina-dello-sport` e `/it/servizi-e-cure/psichiatria/dipendenze`, entrambe esistenti.
- **Causa accertata (diagnostica del 18/11):** gli id nelle TV `mapAreeRiabilitazione` puntavano a pagine cestinate (90, 273), più una quarta non vista dal crawler (Istituto Santo Stefano, 92).
- **Corretto il 18/11** stage e produzione (`kos-aree-link-correggi.php`): 90 → 64, 273 → 94, 92 tolta; diagnostica: 0 voci con problemi su 347.

### I3 · Villa Margherita: aperti dal 15/11

- `description` del nodo `Hospital`: «Chiama lo 0444 043322» contro `telephone` `+39044415851`. La pagina mostra lo 0444 043322 8 volte e il centralino una volta.
- Nessuna pagina del sito collega `/villamargherita`: arriva solo dalla sitemap. `/it/villa-margherita` punta ancora alle due unità.
- Title con «— KOS» e description di 197 caratteri, scritti per la campagna.
- Nota sprint 3: «Collegamenti dalle altre pagine del sito … in definizione con KOS». Da tenere così nella nota, non è un difetto nostro.

## Conclusione per la nota dello sprint 3

Le affermazioni della nota sui siti sono vere sulla scansione del 18/11. La nota va aggiornata dove dice «inserimento nella sitemap in definizione», perché la landing è in sitemap dal 17/11. Da aggiungere alla nota, o alle note sui contenuti, i due medici senza specialità e l'avviso facoltativo su `department`. Il rilievo I1 riguarda le precisazioni dello sprint 2, non la nota dello sprint 3. I2 e i commenti interni (C6) sono piccoli e si possono correggere prima dell'invio.

## Revisione indipendente

Verifica di Fable del 18/11: `docs/revisioni/screaming-frog-20261118-kos-revisione.md`. Rettifiche già riportate sopra: I2 non nuovo, C6 risolto solo in parte, I1 ridimensionato, San Pancrazio non difetto, spiegazione dei link «relativi», note dello sprint 3 senza avviso `department` e medici senza specialità.
