# Integrazione RetailTune: configurazione per l'avvio (primo invio)

Danilo Bernabeo · 2 novembre 2026 · BOZZA da rivedere: i punti segnati **[Da decidere]** sono per Danilo e non partono così

Per Ferruccio Sassi e Aldi Zeneli (RetailTune). Come chiesto nella risposta ai requisiti (R15), mandiamo prima della
partenza del 5 novembre la configurazione del widget e gli elenchi che non dipendono dall'export. L'export delle strutture
(import preliminare, R15) arriva con il secondo invio, appena chiusa la correzione delle anomalie in corso su Yext.

## 1. Siti, domini e chiavi

| | kosgroup.com | anniazzurri.it |
| --- | --- | --- |
| Marchio (chiave e credenziali dedicate, R7) | KOS | Anni Azzurri |
| Dominio di produzione | `https://kosgroup.com` (pagine sotto `/it`) | `https://anniazzurri.it` |
| Dominio di test | `https://stage.kosgroup.com` | `https://stage.anniazzurri.it` |
| Accesso al test | protetto da password (Basic Auth): le credenziali le mandiamo a parte | come a fianco |
| Nome di terzo livello per lo script (R1, I5) | **[Da decidere]** `stores.kosgroup.com` o `mappe.kosgroup.com` | **[Da decidere]** `stores.anniazzurri.it` o `mappe.anniazzurri.it` |

Chiediamo anche la credenziale di gruppo (R7) per l'API server-to-server: serve al confronto periodico tra i due siti e i
profili Google.

## 2. Consenso e Google Maps

- Piattaforma di gestione del consenso: **Cookiebot** su entrambi i siti.
- Categoria della mappa: **marketing** **[Da decidere: conferma di KOS]**.
- Google Maps: **[Da decidere]** chiave creata da RetailTune nel progetto dedicato (I1), con fatturazione sull'account di
  KOS, oppure riuso della chiave già presente su anniazzurri.it (`loadGoogleMaps` a false). Le mappe attuali delle pagine
  regione e provincia di anniazzurri.it vengono sostituite dal widget, quindi la chiave attuale non resta necessaria.

## 3. Tipologie di struttura

Un solo elenco per i due marchi. Il codice è stabile e coincide con il percorso delle pagine di tipologia di
kosgroup.com; l'etichetta è quella mostrata nel filtro. La colonna Yext serve per l'import preliminare.

| Codice | Etichetta | Marchio | Valore attuale in Yext |
| --- | --- | --- | --- |
| `centri-riabilitazione` | Centri di riabilitazione | KOS | Centri riabilitazione, Centri di riabilitazione |
| `ambulatori-di-riabilitazione` | Ambulatori di riabilitazione | KOS | Ambulatori di riabilitazione |
| `cliniche-psichiatriche` | Cliniche psichiatriche | KOS | Cliniche psichiatriche |
| `comunita-terapeutiche-psichiatriche` | Comunità terapeutiche psichiatriche | KOS | Comunità terapeutiche psichiatriche |
| `comunita-terapeutiche-riabilitative-per-minori` | Comunità terapeutiche riabilitative per minori | KOS | Comunità terapeutiche riabilitative per minori |
| `poliambulatori` | Poliambulatori | KOS | Poliambulatori |
| `punti-prelievo` | Punti prelievo | KOS | Punti prelievo, Laboratorio analisi |
| `ospedali` | Ospedali | KOS | Ospedali |
| `cliniche-dentali` | Cliniche dentali | KOS | Cliniche dentali |
| `hospice` | Hospice | KOS, Anni Azzurri | Hospice |
| `residenze-per-anziani` | Residenze per anziani | Anni Azzurri (mostrate anche su kosgroup.com) | Residenze per anziani |
| `centri-diurni` | Centri diurni | Anni Azzurri | — (oggi senza entità Yext: punti nuovi) |

Una struttura può avere più tipologie (es. Ospedale San Pancrazio: centro di riabilitazione e clinica psichiatrica).
**[Da decidere]** `assistenza-domiciliare` (Anni Azzurri a Casa): servizio con area servita e senza sede aperta al
pubblico; proposta: non è un punto della mappa, resta fuori dal widget.

## 4. Configurazione del widget pagina per pagina (R4, R15)

«Fisso» = il visitatore non lo cambia; «modificabile» = valore iniziale che il visitatore può cambiare. Su tutte le
pagine: ricerca per indirizzo attiva, contenitore con dimensioni definite dal sito (R6).

### anniazzurri.it

| Pagina | Tipologia | Area | Note |
| --- | --- | --- | --- |
| `/residenze` | `residenze-per-anziani`, modificabile (con `centri-diurni` e `hospice`) | tutta Italia, modificabile | mappa generale |
| `/rsa-in-lombardia`, `/rsa-in-piemonte`, `/rsa-in-liguria`, `/rsa-in-emilia-romagna`, `/rsa-in-veneto`, `/rsa-in-toscana` (6) | `residenze-per-anziani`, fissa | elenco delle province della regione, fisso (I7) | sostituisce la mappa attuale |
| `/rsa-a-milano-e-provincia` e le 13 pagine `/rsa-in-provincia-di-…` (14) | `residenze-per-anziani`, fissa | provincia, fissa | sostituisce la mappa attuale |
| Nuove pagine provincia (fino a 11, in preparazione: Ascoli Piceno, Asti, Como, Fermo, Grosseto, Imperia, Pesaro e Urbino, Reggio Emilia, Roma, Rovigo, Vercelli) | `residenze-per-anziani`, fissa | provincia, fissa | stessa configurazione delle pagine provincia esistenti |
| `/centri-diurni` | `centri-diurni`, fissa | tutta Italia | |
| `/hospice` | `hospice`, fissa | tutta Italia | |

Le pagine delle singole strutture mantengono la loro mappa di posizione: il widget non serve.
**[Da decidere]** `/cosa-sono-le-rsa` oggi ha una mappa di tutte le residenze: widget come `/residenze` o mappa tolta.

### kosgroup.com

| Pagina | Tipologia | Area | Note |
| --- | --- | --- | --- |
| `/it/strutture` | tutte, modificabile | tutta Italia, modificabile | mappa generale; oggi c'è la ricerca Yext |
| `/it/strutture/<tipologia>` (11 pagine, una per codice della tabella 3) | quella della pagina, fissa | tutta Italia, modificabile | |
| `/it/strutture/punti-prelievo/check-up-analisi` | `punti-prelievo`, fissa | tutta Italia, modificabile | |
| Pagine delle aree di cura (`/it/servizi-e-cure/…`) | — | — | **[Da decidere]** con l'attributo dell'area di cura (fase 2) o con l'elenco delle strutture passato dalla pagina: vedi domanda 7 |

## 5. Attributi

- **Fase 1 (import preliminare):** tipologia (tabella 3) e area geografica (regione, provincia, città dall'indirizzo).
- **Fase 2:** servizi delle residenze Anni Azzurri (servizi offerti, ospiti accolti) e aree di cura delle strutture KOS,
  con codici stabili (R12) definiti da noi. Gli elenchi arrivano con il secondo invio o subito dopo.

## 6. Dati che i siti leggono dall'API (R8)

Ogni sito importa ogni notte dalla Stores API (`list.json.php?allstore=true`, con `modified-since` nelle
sincronizzazioni orarie) nei campi delle pagine struttura. Corrispondenza prevista:

| Campo nel sito | Campo RetailTune | Note |
| --- | --- | --- |
| codice sede | **?** | lo stesso «codice negozio» dei profili Google: vedi domanda 1 |
| nome ufficiale | `name` | |
| indirizzo | `address_1`, `address_2`, `zipcode`, `city`, `province`, `country_code` | |
| coordinate | `lat`, `lon` | |
| telefono | `phone` | formato internazionale |
| email | **?** | vedi domanda 2 |
| orari | `hours` | vedi domanda 3 sulle fasce |
| orari speciali | `special` | |
| link al profilo Google Maps | `custom_fields` (R11) | nome della chiave da concordare |
| stato (aperta, chiusa, chiusa temporaneamente) | **?** | R10: nome del campo |
| tipologie | `type` | vedi domanda 4 |
| attributi | `attributes[].id` | |
| URL della scheda | `link` | deve coincidere con l'URL canonico della pagina |

## 7. Domande

1. **Codice sede.** I profili Google hanno un «codice negozio» che vogliamo mantenere invariato con la migrazione. In
   quale campo dell'anagrafica e dell'API si trova? L'abbinamento ai profili avviene per codice negozio o per ID del profilo?
2. **Email.** In quale campo dell'API è esposta l'email della struttura?
3. **Orari.** `hours` prevede due fasce al giorno (mattina e pomeriggio): è sufficiente per tutte le strutture, o ci sono
   casi con tre fasce? Come si rappresenta un'apertura continua di 24 ore?
4. **Più tipologie.** `type` è una stringa: come sono esposte le strutture con più tipologie?
5. **Categorie Google.** La mappatura delle categorie (R17) usa il codice della categoria Google (es. `gcid:...`) o
   l'etichetta italiana? In italiano esistono categorie diverse con la stessa etichetta.
6. **Link delle attività locali.** Prenotazione e appuntamenti: sono campi dell'anagrafica sincronizzati con i profili?
7. **Elenco di strutture da pagina.** Oltre ai filtri di R4, il widget accetta un elenco di strutture (id) passato dalla
   pagina? Serve alle pagine delle aree di cura di kosgroup.com.
8. **Combinazione dei filtri.** Più valori nello stesso filtro si sommano (unione) e filtri diversi si intersecano?
9. **Ricerca.** La ricerca del widget accetta parole oltre all'indirizzo (nome della struttura, attributi)?
10. **Visibilità degli attributi.** Che effetto ha `visible` sugli attributi nel widget e nell'API?
11. **UTM.** Il sito pubblicato nei profili Google può avere parametri aggiunti solo nella sincronizzazione (es.
    `utm_source=google&utm_medium=organic&utm_campaign=gbp`), mentre il campo `link` resta l'URL canonico senza parametri?
