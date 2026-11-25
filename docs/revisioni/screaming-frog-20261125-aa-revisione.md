# Screaming Frog 25/11/2026 · anniazzurri.it · revisione indipendente della nota

Uso interno. Revisione del 25/11/2026, ore 23:30 (Claude Fable 5.2), della nota `docs/verifiche/sf-anniazzurri-20261125.md`. Ogni numero è ricalcolato con script propri sui 100 CSV della scansione del 25/11 (18:23-23:08) e confrontato con i tre CSV del 23/11. Per l'anomalia 1 ho letto la storia git dei due file `aaSchema.php`, i manifest e `scripts/deploy/installa-elementi.php`. Richieste ad anniazzurri.it: 8 in tutto, alle 23:22, una al secondo, solo per confermare (5 pagine, due intestazioni, un'immagine). Nessuna richiesta allo stage, nessuna modifica. Il docx non è stato generato (vincolo: un solo file nuovo nel repository).

## Riepilogo

1. **L'anomalia 1 è confermata ed è ancora in produzione** (richiesta delle 23:22: `/residenza-anni-azzurri-san-luca` ha solo `WebPage`, `BreadcrumbList`, `WebSite`, `Organization`). La causa indicata dalla nota è giusta: il pacchetto del 24/11 ha installato `elements/snippets/aaSchema.php`, cioè la versione del 5/11 più la riga delle briciole.
2. **La correzione preparata è giusta e installabile.** `aa-schede/aaSchema.php` è la versione in produzione fino al 24/11 più le due sole modifiche di AA 8. La baseline del manifest è, byte per byte, il file installato il 24/11. Va rilasciata il 26/11: il termine dei rilasci era il 25/11 e la verifica parte il 30/11.
3. **La nota sottostima la regressione.** Con la versione vecchia sono tornati anche tre difetti già chiusi, che la nota non cita: 17 articoli con `dateModified` precedente a `datePublished`, briciole JSON-LD verso i contenitori con redirect (`/soluzioni/` e altri, almeno 36 pagine), `BreadcrumbList` e lingua italiana su `/home-en` e `/azzurramente`. La stessa correzione li chiude tutti; vanno aggiunti all'elenco «da ricontrollare».
4. **Perché l'installatore non si è fermato.** Il controllo della baseline si salta quando il contenuto del sito è l'ultimo installato dallo script. Il 24/11 la baseline (versione vecchia) era diversa dal sito, ma il sito era «nostro»: nessun avviso. Il controllo ferma le modifiche a mano, non un pacchetto costruito sul file sbagliato (N3).
5. **Nessun altro pacchetto dal 18/11 ha lo stesso errore**, né su AA né su kosgroup.com. Restano però tre manifest vecchi che installano ancora la copia vecchia di `aaSchema` senza baseline, e altre copie doppie a rischio: `head-rsa`, `contactBox`, `contactBox-villaggio-san-francesco` (AA), `head-extra-landing`, `head-extra-strutture` (KOS) (N4).
6. **Rilievo nuovo fuori dall'anomalia 1:** la pagina rinominata `/sos-caregiver-…` ha nel JSON-LD `Service` e `FAQPage` con `@id`, `url` e `serviceUrl` del vecchio indirizzo (ora 301), nome «Sportello Sociale», una domanda diversa dal testo visibile e un'`image` che risponde 404 (N2).
7. **Il resto della nota regge.** Confermati 466, 461, 454, 0 errori, title e description, canonical, tempi, anomalie 2, 3, 5, 6, 7, 8. Quattro correzioni minori (tabella).

## Anomalia 1: causa e correzione

### Causa nel repository

| Fatto | Evidenza |
| --- | --- |
| Due copie di `aaSchema` | `elements/snippets/aaSchema.php` (664 righe, ferma al 5/11) e `elements/aa-schede/aaSchema.php` (963 righe, 12 commit dall'8/11 al 18/11). Dall'8/11 tutti i pacchetti installano la seconda |
| Il commit `7e5d32a` (24/11 15:39) modifica la copia vecchia | `git show 7e5d32a --stat`: tocca `snippets/aaSchema.php`, non `aa-schede/aaSchema.php`. Il manifest `aa-8-residenze-20261124.json` puntava a `snippets/aaSchema.php` |
| La baseline del pacchetto era la versione vecchia | `aa-8-residenze-20261124/base-aaSchema.php` ha md5 `63c3e61d`, uguale a `snippets/aaSchema.php` prima del commit. In produzione c'era `6102c4fa` (versione delle schede) |
| L'installatore non ha segnalato nulla | `installa-elementi.php`, righe 193-199: si ferma solo se `$att !== $bl && $att !== $nuovo && !$nostra`. Il sito conteneva l'ultima versione installata dallo script (`$nostra` vero) |
| Il controllo dopo il rilascio ha guardato solo `/residenze` | Registro, 24/11: «Unica pagina che cambia: `/residenze`» |

### Cosa è sparito o tornato indietro (scansione del 25/11 contro il 23/11)

| Effetto | Pagine | Nella nota |
| --- | --- | --- |
| Nodo `#localbusiness` (con `brand`, orari, coordinate, telefono, `priceRange`) | 57 residenze | sì |
| Nodo `#department` | 8 reparti | sì |
| `ItemList` | 14 pagine provincia | sì |
| `Service` di RSA Aperta e cure domiciliari | 4 pagine (3 servizi) | sì |
| `mainEntity` della pagina | 84 → 1 (resta l'autrice) | no |
| `dateModified` precedente a `datePublished` | 17 articoli, 0 il 23/11. Esempio: `/igiene-personale-negli-anziani-i-nostri-consigli`, pubblicato 12/11/2024 8:00, modificato 11/11/2024 14:21. Era il rilievo C9, chiuso il 16/11 | **no** |
| `item` delle briciole verso il contenitore con redirect | `/cure-intermedie`: JSON-LD `https://anniazzurri.it/soluzioni/`, briciola visibile `/le-nostre-soluzioni` (richiesta delle 23:22). Stesso caso per i livelli «Le nostre soluzioni» (17 pagine), «Chi siamo» (8), «SOS famiglia» (7), «Tariffe e convenzioni» (4). Era il rilievo C4, chiuso il 16/11. I CSV non lo mostrano: l'estrazione prende solo i nomi | **no** |
| `/home-en` e `/azzurramente` | `BreadcrumbList` nuovo senza briciole visibili («Anni Azzurri › home_en», «Anni Azzurri › AzzurraMente»); su `/home-en` `inLanguage` «it-IT» con `<html lang="en">`. Eccezioni decise il 16-17/11 | **no** |
| Telefono nel grafo | 83 pagine: resta solo il numero verde dell'ente | no |
| Avvisi Google sulle strutture | 109 pagine con avvisi → 74. I 34 `priceRange` e gli 8 `department` non ci sono più perché mancano i nodi, non perché risolti | no |

### Correttezza della correzione

| Controllo | Esito | Evidenza |
| --- | --- | --- |
| La nuova `aa-schede/aaSchema.php` contiene tutto il grafo delle schede | C | `git diff 7e5d32a~1 HEAD -- anniazzurri/elements/aa-schede/aaSchema.php`: 2 blocchi, 6 righe (commento e condizione delle briciole). Il resto è `6102c4fa`, in produzione dal 18/11 al 24/11 |
| Contiene la correzione delle briciole di AA 8 | C | Riga 386: `$rid === $id \|\| !in_array($rid, $ESCLUDI, true)`, uguale a quella installata il 24/11 |
| Nulla della versione vecchia va perso | C | Le 10 righe presenti solo nella versione installata il 24/11 sono righe riscritte nella versione delle schede (briciole, lingua, date) |
| Sintassi | C | `php -l`: nessun errore |
| Baseline del manifest uguale alla produzione | C | `aa-8-correzione-schema-20261125/base-aaSchema.php` = `git show 7e5d32a:anniazzurri/elements/snippets/aaSchema.php` (md5 `f1ddeea5`). La produzione si comporta come quel file: `/residenze` a due livelli, schede senza nodo |
| Il manifest si installa senza `--forza` | C | Sito = baseline; in più il registro dello script riconosce il contenuto come suo |
| `/home-en`, `/azzurramente` | C | Tornano senza briciole (`&briciole=`0``) e in inglese (`&lingua=`en``): i parametri sono nei chunk, non toccati |
| `snippets/aaSchema.php` riportato a `63c3e61d` | C con riserva | Serve come baseline di `aa-schede.json`, `aa-date-articoli.json`, `aa-8-arrivo.json`. Resta però il file installato da tre manifest vecchi (N4) |
| Manifest del 24/11 riscritto | D | Ora `file` punta alla versione delle schede e `baseline` alla versione vecchia: non descrive più ciò che è stato installato. Meglio lasciarlo com'era, con la sola `nota` |

Da fare dopo il rilascio, oltre ai controlli della nota: date dei 17 articoli, `item` delle briciole su `/cure-intermedie` e `/kos-academy`, `/home-en` senza `BreadcrumbList` e con `inLanguage` «en», `/residenze` ancora a due livelli, cache riscaldata.

Via alternativa se il pacchetto desse problemi: il backup del 24/11 (`/paas/c0072/backup-kos-seo-geo/20261124-084528/`) contiene la versione delle schede; il suo `ripristino.json` riporta però indietro anche `breadcrumb-stripe`.

## Correzioni alla nota

Esito: C = confermata, D = da correggere, S = smentita, N = non verificabile sui CSV.

| Affermazione | Esito | Evidenza |
| --- | --- | --- |
| 7.852 URL | C | `codici_di_risposta_tutti.csv`: 7.852 righe, 7.333 interne |
| 466 pagine HTML, 461 con 200, 5 redirect http→https | C con riserva | `interni_html.csv`. Dei 5 redirect uno è un PDF (`KOS_Codice_Etico.pdf`) e uno è `www` → senza `www` |
| 454 indicizzabili; nessun 4xx/5xx interno | C | 454; interni 7.328 con 200 e 5 con 301 |
| Canonical uguale all'indirizzo sulle indicizzabili | C | 454 su 454; nessun canonical con parametri |
| Un blocco JSON-LD ovunque, tranne i 5 virtual tour | C | `jsonld_blocchi`: 456 a 1, 5 a 0 |
| 0 errori di dato strutturato | C | `dati_strutturati_tutti.csv`: 0 errori, 0 errori dei risultati avanzati |
| Title entro 65, description entro 160, nessun duplicato | C | Massimi 65 e 160; nessun duplicato di title, description, H1 tra le indicizzabili |
| Nessuna immagine sopra 200 KB | C | Massimo 199.910 byte su 6.369 immagini |
| Stessi codici, indicizzabilità, title, description, H1, canonical, meta robots | C | 0 differenze sulle 460 pagine comuni. H2-1 cambiato su 6 pagine (5 centri diurni, rilascio del 24/11; una news) |
| Mediana 0,17 s, 90° percentile 0,28 s; 23/11 1,11 s | C | 0,172 e 0,282; 1,111 |
| Anomalia 1, «`OpeningHoursSpecification` 65 → 0» | D | 65 → 1: resta l'orario dello Sportello sociale |
| Anomalia 1, «`MedicalBusiness` 44 → 0» | C, fuorviante | Il conteggio viene dall'estrazione con regex, che non vede i tipi scritti come elenco. I 44 sono voci degli `ItemList`; i nodi persi sono 57 residenze, 8 reparti, 14 elenchi |
| Anomalia 1, «tutto il grafo delle strutture è tornato allo stato di prima dello sprint 3» | D | Sono tornate indietro anche correzioni degli sprint 2 e 3 fuori dalle strutture (date, briciole, `/home-en`): vedi sopra |
| Anomalia 2, `/oss-in-lombardia-…` `noindex, follow` e fuori sitemap | C | Assente da scansione e sitemap; richiesta delle 23:22: `<meta name="robots" content="noindex, follow">` |
| Anomalia 3, rinomina in `/sos-caregiver-…`, 301 dal vecchio, 453 pagine | C, incompleta | 301 confermato; 453 link unici; in sitemap. Il dato strutturato della pagina è rimasto al vecchio indirizzo (N2). Rinomina delle 17:02 del 25/11 |
| Anomalia 4, 404 di inps.it e bollinirosargento.it | C | 2 soli 404 esterni (27 il 23/11: tolti Spreaker, app radio, kosgroup.com) |
| Anomalia 4, «Nuovi 403: allaboutcookies.org, due DOI Wiley» | S | Tutti e tre erano 403 anche il 23/11. L'unico 403 nuovo è `salute.gov.it/new/it/faq/faq-influenza-…` (prima con `?utm_source=chatgpt.com`). I 403 sono 8 (20 il 23/11: 12 indirizzi di salute.gov.it ora rispondono 200), i 401 sono 3 |
| Anomalia 5, 4 pagine con link `http://` | C | 7 link: 4 su `/privacy`, uno per ciascuna delle altre tre |
| Anomalia 6, 7 news con `utm_source=generator` | C | 8 iframe di open.spotify.com su 7 pagine |
| Anomalia 7, 150 link da PDF verso `http://anniazzurri.it/…` | C | 76 alla home, 68 a Parco Sempione, 2 + 2 + 2 |
| Anomalia 8, 2 pagine in sitemap senza link | C | `url_orfani_link_in_entrata.csv`: le due landing |
| 216 PDF `noindex` nella sitemap dei PDF | C | 216. I PDF `noindex` sono 218: fuori restano il modulo di San Rocco con l'apostrofo e un PDF nuovo (N6) |
| Nessun link relativo, `gclid`, stage, Yext, CDN delle icone | C | Tutte le ricerche a 0 su 461 pagine e 410 PDF |
| Avvisi: 74 `dateModified` su `ScholarlyArticle`, 1 su `ProfilePage`, 1 `identifier` | D | 151 avvisi su 74 pagine: mancano i 74 `image` su `ScholarlyArticle` e il `dateCreated` di `ProfilePage` |
| «Già corretta nel repository» | C | Commit `c3c9c50`; non ancora rilasciata alle 23:22 |

Non ripreso dalla nota, invariato dal 23/11: 20 pagine con `og:image` predefinita (stesso elenco), 5 articoli con link alla landing `noindex` `/alzheimer-cura-e-assistenza`, galleria del convegno con 51 parole, 235 immagini con `alt` vuoto (stessa composizione, già valutate decorative il 24/11).

## Rilievi nuovi

| # | Gravità | Anomalia | Evidenza | Causa | Correzione |
| --- | --- | --- | --- | --- | --- |
| N1 | Alta, stessa correzione dell'anomalia 1 | Tre difetti chiusi tornati in produzione: 17 articoli con `dateModified` prima di `datePublished`; briciole JSON-LD verso contenitori con redirect e diverse dalle visibili (almeno 36 pagine); `/home-en` e `/azzurramente` con `BreadcrumbList` senza briciole visibili e `/home-en` in «it-IT» | `estrazione_personalizzata_tutti.csv` delle due scansioni; sorgenti di `/cure-intermedie` e `/home-en` | Versione di `aaSchema` del 5/11 | Rilascio di `aa-8-correzione-schema-20261125`; aggiungere i tre punti ai controlli dopo il rilascio. Toccano AA 8 e AA 2, in verifica dal 30/11 |
| N2 | Media | `/sos-caregiver-orientamento-per-assistenza-anziani`: `Service` e `FAQPage` con `@id` `https://anniazzurri.it/sportello-sociale-orientamento-per-assistenza-anziani#service` e `#faq`, `url` e `serviceUrl` del vecchio indirizzo (301), `name` «Sportello Sociale». Prima domanda del `FAQPage` «Chi può rivolgersi allo Sportello Sociale?», in pagina «Chi può rivolgersi a SOS caregiver?». `image` del `Service` (`/assets/img/sportello-sociale-orientamento-per-assistenza-anziani.jpg`) risponde 404; la foto della pagina è `sportello-sociale-anziani.jpg` | Sorgente della pagina alle 23:22; `anniazzurri/elements/chunks/schema-525.html` | Chunk statico con gli indirizzi scritti a mano; rinomina dal manager | Aggiornare `schema-525` (indirizzi, nome, domanda, immagine esistente) o, meglio, portare lo Sportello in `aa.servizi` come proposto il 18/11 (N8), con `@id` dall'URL della risorsa. Aggiornare anche `scripts/collaudo/pagine-aa.txt`, `pagine-aa-8-correzioni.txt` e `verifica-sprint-1.mjs` (riga 638). Chiedere a KOS se il servizio ha cambiato nome |
| N3 | Media, processo | L'installatore non ferma un pacchetto costruito su una versione diversa da quella in produzione | `installa-elementi.php`, righe 193-199 e 254-260 (stessa logica per i file) | `$nostra` annulla il confronto con la baseline | Se il manifest ha una baseline e il sito è diverso da baseline e da file nuovo, fermarsi anche quando il contenuto è «nostro», con un messaggio distinto («il pacchetto parte da una versione che non è quella installata»); `--forza` per i casi voluti. Avrebbe fermato il rilascio del 24/11 |
| N4 | Media, processo | Copie doppie a rischio di confusione | Vedi tabella sotto | Cartelle `aa-schede/` e `voce-24-attivazione/` nate come pacchetti di attivazione e diventate le versioni in uso | Una sola copia per elemento in `elements/`; le versioni vecchie nelle cartelle dei pacchetti che le usano come baseline; i manifest superati marcati come tali |
| N5 | Bassa, processo | Il collaudo non vede questa regressione | `scripts/collaudo/verifica-sprint-1.mjs`: nessuna occorrenza di `localbusiness` | Controlli scritti per lo sprint 1 | Aggiungere quattro controlli: `#localbusiness` su una residenza, `#department` su un reparto, `ItemList` su una provincia, `Service` su `/rsa-aperta`. Lanciarlo dopo ogni rilascio di `aaSchema` o `seoSchema`, non sulla sola pagina toccata |
| N6 | Bassa | PDF nuovo: `/assets/doc/strutture/residenza-anni-azzurri-il-poggione/Locandina-incontro-persone-al-centro.pdf` (1,3 MB, `noindex`, senza titolo, fuori dalla sitemap dei PDF esclusi). La scheda di Il Poggione passa da 1.152 a 1.158 parole | `interni_pdf.csv` delle due scansioni | Caricato da KOS dopo il 23/11 | Nessuna sul `noindex` (regola dei PDF nuovi); rigenerare la sitemap dei PDF esclusi al prossimo giro |
| N7 | Bassa, documentazione | `docs/elementi-sotto-progetto.md` dice che `seoSchema` con la regola di AA 8 è «non rilasciato su kosgroup.com». È stato rilasciato il 24/11 dentro `kos-correzioni-20261124` (la baseline del pacchetto non ha la riga, il file sì), senza che il nome del pacchetto lo dica | `diff kosgroup/deploy/kos-correzioni-20261124/base-kosSchema.php shared/snippets/seoSchema.php`, riga 510 | Modifica comune entrata nel pacchetto successivo | Nessun effetto sul sito (`kos.breadcrumb_exclude` = «-»). Correggere la riga del documento |

### N4 · Copie doppie nel repository

| Elemento | Copia in uso | Copia vecchia | Rischio |
| --- | --- | --- | --- |
| `aaSchema` (AA) | `elements/aa-schede/aaSchema.php` | `elements/snippets/aaSchema.php`; terza copia in `baseline/snippets/` | `aa-8.json`, `aa-8-correzioni.json` e `aa-firma-autore.json` installano la copia vecchia senza baseline: rilanciati, ripetono la regressione senza alcun controllo. Anche il comando del README di `extra/aa-firma-autore` (riga 529) archivia la copia vecchia |
| `head-rsa` (AA) | `elements/aa-schede/head-rsa.html` | `elements/chunks/head-rsa.html`, con lo script Yext, modificata il 18/11 insieme all'altra | `aa-20-title.json`, `aa-og-image.json`, `aa-ttfb.json` la installano; la baseline non ferma (N3). Rimetterebbe Yext sulle 57 schede |
| `contactBox`, `contactBox-villaggio-san-francesco` (AA) | `elements/aa-schede/` | `elements/chunks/`, con il markup `data-yext-field` | `aa-ttfb.json` |
| `head-extra-landing`, `head-extra-strutture` (KOS) | `elements/voce-24-attivazione/` | `elements/chunks/` | `kos-24-attivazione.json` le usa come baseline; nessun manifest le installa. Rischio solo di modifica sul file sbagliato |

In produzione oggi `head-rsa` e `contactBox` sono le versioni giuste (nessuna stringa «yext» nel sorgente di San Luca, riquadro con `<p class="h5">`).

### Pacchetti dal 18/11 controllati

Per ogni manifest aggiunto dal 18/11 ho confrontato file installato e baseline. AA: `aa-chiusura-20261118`, `aa-13-22-css`, `aa-6-css`, `aa-spreaker`, `aa-icone-assolute`, `aa-app-radio`, `aa-flickity`: differenze di poche righe, coerenti con il titolo del pacchetto; unico caso anomalo `aa-8-residenze-20261124` (baseline di 664 righe contro le 963 in produzione). KOS: `kos-2-commenti-head-extra`, `kos-15-25-css`, `kos-2-centri-cache`, `kos-12-briciole-mobile`, `kos-correzioni-20261124`, `kos-contenuti-struttura`, `kos-markerclusterer`, `kos-link-frammenti`, `kos-barra-branca`, i tre `kos-15-css-critico`: nessun elemento installato da un file diverso da quello in uso. Solo `aaSchema` risulta installato da due percorsi diversi nel tempo. Nota: `kos-barra-branca-20261125` riusa la baseline del 22/11 di `kos-2-centri-cache`, quindi la sua baseline non è la produzione; innocuo, ma è lo stesso meccanismo di N3.

Limite: gli elementi senza baseline nei manifest (circa 30) hanno una sola copia e storia lineare in git; non ho modo di confrontarli con il contenuto reale dei siti.

## Controlli senza rilievi

Nessuna pagina HTML non indicizzabile in sitemap; nessuna catena di canonical; `og:url` uguale all'indirizzo su 456 pagine; `<img>` senza attributo `alt`: 0 su 461; nessun hreflang sul sito; nessuna data prima del 2000 né futura; nessun link interno a 404; redirect interni solo dai PDF; nessuna pagina nuova oltre alla rinominata, nessuna sparita oltre alle due della nota; PDF indicizzabili tutti con titolo (167); briciole JSON-LD e visibili con gli stessi nomi su 446 pagine su 455; delle 9 differenze, 6 sono spazi doppi e virgolette nell'estrazione (uguali al 23/11), 2 sono `/home-en` e `/azzurramente` (N1) e una è l'iscrizione al convegno (`noindex`, senza briciole visibili, già nota).

## Giudizio sulle priorità

- **Subito, 26/11 mattina:** rilascio di `aa-8-correzione-schema-20261125` su stage e produzione, poi controllo su un campione per tipo (residenza, reparto, provincia, servizio, articolo con data programmata, `/cure-intermedie`, `/home-en`, `/residenze`) e scaldacache. La regressione è in produzione da circa 32 ore e tocca voci in verifica dal 30/11.
- **Prima del 30/11:** N2 (pagina in sitemap con dato strutturato verso un 301 e un'immagine 404).
- **Prima del prossimo rilascio di qualsiasi pacchetto:** N3 e la parte di N4 su `aaSchema` e `head-rsa`. Sono le due modifiche che impediscono la ripetizione.
- **Poi:** N5, N6, N7 e la correzione del testo della nota (righe D e S della tabella).
