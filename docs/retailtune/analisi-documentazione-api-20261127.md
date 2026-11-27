# RetailTune: Stores API aggiornata (documentazione ricevuta il 27/11)

Uso interno. Documento letto per intero: «RetailTune APIs for Kosgroup documentation» (pagina HTML salvata da Danilo il
27/11, non nel repository; esempi con dati reali del 26/11). Base URL `https://stores-kosgroup.retailtune.com/endpoint/pdp/`,
Basic Auth utente `kosgroup_endpoint_user` (password fornita a parte: solo a Danilo, per l'importatore in
un'impostazione di sistema MODX; mai nel repository né in chiamate dal browser).

## Endpoint

| Endpoint | Metodo | Uso per noi |
| --- | --- | --- |
| `list.json.php` | GET, POST (`types`, `province`, `attributes`, `city`, `lang`, `modified-since`) | importazione notturna delle strutture nei siti (R8); `modified-since` per gli aggiornamenti orari |
| `list.all.json.zip.php` | GET, POST (`lang`) | stessa lista in zip (copia di sicurezza, confronto periodico) |
| `list.attribute.php` | GET, POST (`lang`) | elenco di servizi e gruppi (id numerico + `code` stabile) |
| `list.city.php`, `list.province.php`, `list.type.php` | GET, POST | elenchi per filtri e controlli |
| `index.php` | POST | struttura più vicina o per `storeid` (richiede comunque lat/lon) |
| `locator.php` | POST | strutture nel raggio, ordinate per distanza |

## Cosa cambia rispetto alla documentazione del 12/11

- **Tipologie: quattro** (`list.type.php`): `Ambulatori_e_medicina_specialistica`, `Riabilitazione`, `Psichiatria`,
  `Residenze_per_anziani`, cioè le quattro aree del menu «Servizi e cure» (proposta del 18/11). Il tipo di struttura
  scende negli attributi, in gruppi per area: «Tipo di struttura riabilitativa» (es. `tipo-ambulatori-di-riabilitazione`),
  «Tipo di struttura psichiatrica» (`tipo-cliniche-psichiatriche`), «Tipo di struttura per anziani»
  (`tipo-appartamenti-per-anziani`), `tipo-poliambulatori`. Le strutture con due aree hanno `type` con due valori separati
  da virgola. Coerente con quanto concordato.
- **Attributi** con `code` stabile (es. `nucleo-alzheimer`, `visita-ambulatorio-cardio-metabolico`), `groupId` e
  `groupLabel` («Visite specialistiche», «Esami e diagnostica», «Tipi di riabilitazione», «Disturbi trattati», «Chi
  accogliamo», «Servizi per anziani»): l'importatore usa il `code`, non l'id numerico. Nell'API gli attributi sono sempre
  in «oppure» anche fra gruppi diversi (nel widget «e» fra gruppi): per l'importazione non conta.
- **Campi nuovi:** `last-modified` per struttura; `reviews_summary` (media e numero delle recensioni Google);
  `custom_fields` `gbp_add_review_link` e `gbp_add_review_qr_code` (link e QR per lasciare una recensione, solo per le
  strutture con profilo collegato); `alternates` con un percorso per lingua (es. `it/italia/lombardia/milano/milano-v-san-luca`).
- **`link` vuoto:** non è più l'URL della scheda (R8) ma un campo del widget prodotto, «currently "" for Kosgroup».
- **Orari:** 24 ore = `00:00`-`23:59` (Villa dei Pini Firenze); apertura continua solo con `morningOpen`/`morningClose`;
  `special` per gli orari straordinari. Orari aggiuntivi in `orari_2_etichetta`/`orari_2_testo`.
- **Numero verde** ancora in `store.fax` (San Luca: `800131851`).

## Punti da chiarire con RetailTune

1. **URL della scheda.** Con `link` vuoto, da dove prende il widget il pulsante «Scheda» (kosgroup.com o anniazzurri.it)?
   Proposta: un campo personalizzato `scheda_url` con l'URL canonico, dalla nostra tabella codice → URL (già prevista al
   punto 9 del 12/11). Per l'importazione nei siti non serve: l'abbinamento è per `store.id`.
2. **`alternates`: pagine struttura di RetailTune.** I percorsi `it/italia/...` e `en/italy/...` indicano pagine struttura
   sul loro dominio. Decisione del 2/11: nessuna pagina struttura pubblicata dal widget. Se esistono, devono essere non
   indicizzabili (`noindex`) con canonical verso la scheda del sito, oppure non pubblicate: altrimenti fanno concorrenza
   alle schede nei risultati e negli assistenti AI (doppioni di nome, indirizzo e telefono).
3. **Campi concordati che non compaiono** nei `custom_fields`: URL di prenotazione (17 strutture), Marchio (separazione
   delle istanze), link al profilo Google Maps (R11, per «Indicazioni»), link «Richiedi informazioni» per struttura.
   Sono previsti? Con quali nomi?
4. **Strutture chiuse temporaneamente** (R10): `list.json.php` restituisce solo le aperte; una chiusura temporanea sparisce
   o resta con `status` diverso da `open`? (domanda del 12/11 ancora aperta)
5. **Qualità dei dati negli esempi (dati reali):** «utti i giorni…» in `orari_2_testo` di Villa dei Pini Firenze (manca
   la «T», già segnalato il 12/11); `address_1` «V. San Luca 4» abbreviato (sul sito «Via San Luca 4»): l'indirizzo va
   scritto per intero e uguale al sito, perché è quello che finisce nei profili Google.
6. **Lingua inglese:** per `lang=en` tradotti solo i nomi di attributi e gruppi, o anche descrizione e orari aggiuntivi?

## Come lo usiamo noi (lato siti)

- Importatore notturno server-to-server (mai chiamate dal browser: la Basic Auth esporrebbe la password), `list.json.php`
  completo di notte e `modified-since` di giorno; chiave `store.id` (opaca: `KCA00_SLC`, `KOS121`, `104`).
- Nei campi delle schede: nome, indirizzo, coordinate, telefono, numero verde, email, orari (+ aggiuntivi e speciali).
  Il dato strutturato continua a essere generato dalla libreria comune a partire dai campi della scheda.
- **`reviews_summary` NON va nel dato strutturato** (`aggregateRating`): Google non mostra stelle per le recensioni di
  un'organizzazione su sé stessa e quelle di Google non sono recensioni raccolte dal sito. Può servire solo come dato
  interno di monitoraggio.
- `gbp_add_review_link` / QR: utili per un pulsante «Lascia una recensione» nelle schede o per i materiali stampati in
  struttura (decisione di KOS/marketing).
- Prima dell'importatore: confronto una tantum API ↔ schede (nome, indirizzo, telefono, orari) con l'elenco delle
  differenze per KOS, come per Yext.

## Prima lettura dei dati (27/11, `list.json.php` e `list.attribute.php`, file fuori dal repository)

Credenziali della Stores API verificate da Danilo il 27/11 (risposta di `list.type.php` corretta).

- **128 strutture**, tutte `open`: 65 residenze, 17 riabilitazione, 17 ambulatori, 14 psichiatria, 15 con due o tre aree.
  **92 attributi** in 12 gruppi; i tipi di struttura sono nei gruppi 2, 5, 7, 9 (poliambulatori, punti prelievo,
  cliniche dentali, ospedali, hospice, centri diurni…), più `prenotabile-online` (gruppo 1).
- **Manca la Clinica Dentale Villa dei Pini** (Civitanova Marche, pubblicata sul sito il 27/11): da aggiungere.
- **Villalba ancora doppia:** `VPI00_VPM` «Poliambulatorio Villalba» e `Villalba` «Villalba», entrambe ambulatori
  (punto 1 del 12/11): ne va tenuta una.
- **Indirizzi abbreviati: 75 su 128** (`V.`, `V.le`…, es. «V. Bolzano 5», «V.le Don Giovanni Minzoni 29/A»), mentre i
  siti scrivono «Via», «Viale». Sono gli indirizzi che finiscono nei profili Google: vanno scritti per intero e uguali
  alle schede (coerenza di nome, indirizzo e telefono).
- **Dati mancanti:** 34 strutture senza email (tra cui i centri ambulatoriali Santo Stefano), 10 senza telefono e 12
  senza orari (Sestri Ponente, Il Poggione, Santo Stefano Casorate e Mede, i due hospice, i centri diurni, Anni Azzurri
  Senior Living): sono in gran parte le strutture senza profilo Google (le stesse 10 senza link per le recensioni).
- **Campi concordati assenti in tutte le 128:** `link` (URL della scheda), `media`, `description`; nei `custom_fields`
  solo `gbp_add_review_link`/`_qr_code` (118) e un solo `orari_2_*` (Villa dei Pini, con «utti i giorni»). Mancano
  prenotazione, marchio, link al profilo Maps, «Richiedi informazioni».
- **Entità HTML nei nomi degli attributi** («Comunit&#224; terapeutiche…»): l'importatore le decodifica.
- 113 strutture con recensioni Google (`reviews_summary`): solo monitoraggio interno, non dato strutturato.
