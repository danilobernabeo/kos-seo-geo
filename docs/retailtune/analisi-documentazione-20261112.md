# RetailTune: analisi della documentazione dell'11-12/11 e risposte proposte

Uso interno. Documenti ricevuti il 12/11 (non nel repository): «Struttura dati delle strutture» (11/11),
«Configurazione del widget store locator» (12/11), «Kosgroup Stores API» (docs.api.retailtune.com/api-kosgroup).
RetailTune chiede le risposte dopo la proposta grafica di venerdì 13/11.

## Cosa cambia rispetto al primo invio (2/11)

- **Chiave della struttura:** `store.id` = ID entità Yext = codice negozio dei profili Google (120 su 120). Risponde alla
  domanda 1 del primo invio.
- **Email:** in `store.email` (domanda 2). **Più tipologie:** in `store.type` separate da virgola (domanda 4).
- **Numero verde** in `store.fax` (nome del campo fuorviante, resta così).
- **Orari aggiuntivi:** già attivi come `custom_fields` `orari_2_etichetta` / `orari_2_testo` (esempio reale: Villa dei
  Pini Firenze, KCA00_OVP). Nel testo d'esempio manca la «T» iniziale («utti i giorni…»): da segnalare.
- **Filtri nell'indirizzo:** `tipologia`, `regione`, `provincia`, `citta`, `servizi`.
- **Tipologie:** dieci (non le dodici del primo invio): mancano Hospice e Centri diurni, che sono attributi del gruppo 1
  (`hospice`, `centro_diurno`); «Punti prelievo» si chiama `Laboratorio_analisi`.

## Risposte proposte al capitolo 7 della «Struttura dati»

| Punto | Proposta |
|---|---|
| 1. Tipologie | Approvate, con: etichetta «Punti prelievo» per `Laboratorio_analisi` (come la pagina `/it/strutture/punti-prelievo`); etichetta degli orari «Accoglienza e informazioni» per Centri riabilitazione, Ospedali e Comunità per minori (strutture con ricovero). Le cinque assegnazioni: Villa Pineta, Valdaso e Parco di Veio ok. **La Margherita (`KCA00_BTS`) non esiste più** (confermato da Danilo il 19/10): non va caricata. **Villalba:** la residenza AA `KCA00_VLB` è chiusa; il Poliambulatorio Villalba di KOS ha due entità Yext (`VPI00_VPM` e un doppione) per una sola scheda: ne va tenuta una sola. Da chiarire quale delle due hanno classificato come Poliambulatorio. |
| 2. Servizi | Gruppi e nomi approvati. |
| 3. Prestazioni dei poliambulatori | Sì: elenco per struttura preparato da noi dalle pagine di kosgroup.com, validato da KOS. |
| 4. Email | Le 57 residenze hanno sul sito una casella generica (dati verificati con KOS il 9/11): mandiamo la tabella per nome struttura. |
| 5. Orari di Sestri Ponente e Il Poggione | Mancano anche sul sito: da raccogliere nella giornata con KOS del 19/11. |
| 6. Campi aggiuntivi | Sì a URL di prenotazione (17) e Marchio (campo aggiuntivo, serve anche a separare le istanze). «Richiedi informazioni»: non il messaggio Yext, ma un link nostro per struttura (scheda con ancora ai contatti). Specialità e anno di apertura: no. |
| 7. Fax e social | Confermato: restano fuori. |

## Punti da chiarire

1. **Istanza anniazzurri.it:** l'oggetto di configurazione non ha un filtro per marchio. La chiave del sito è «vincolata
   al dominio e al marchio»: la chiave di anniazzurri.it restituisce solo le strutture Anni Azzurri? Su quale dato
   (campo Marchio del capitolo 5)?
2. **Hospice e centri diurni:** come attributi va bene per i filtri, ma l'Hospice Beati Coniugi Martin e i centri
   diurni con pagina propria su anniazzurri.it non sono entità Yext: non compaiono sulla mappa. Proposta: aggiungerli
   come strutture (indirizzo, link alla loro pagina) o accettare il rimando alla residenza.
3. **Identificativo dei servizi:** il widget e i parametri nell'indirizzo usano l'id numerico (`servizi=16,17`), la
   «Struttura dati» presenta il `code` come identificativo stabile. Si può usare il `code` anche nell'indirizzo
   (`servizi=nucleo_alzheimer`)? È leggibile e non dipende dall'ordine di creazione.
4. **Combinazione dei servizi:** nel widget «oppure» nello stesso gruppo ed «e» fra gruppi; nella Stores API sempre
   «oppure». Per l'importazione sui siti non conta, ma va tenuto presente.
5. **Stato delle strutture:** `list.json.php` restituisce solo le strutture aperte. Una struttura chiusa
   temporaneamente sparisce dalla risposta o resta con `status` diverso da `open`? Serve per R10 (schede dei siti).
6. **Esempi con `www.anniazzurri.it`:** gli indirizzi canonici sono senza `www`.
7. **FAQ per struttura di Yext (54 strutture):** vanno esportate da Yext prima della dismissione, a prescindere dallo
   store locator: sono contenuto utile alle schede dei siti (GEO).

8. **Strutture senza entità Yext** (quindi assenti dall'import): Santo Stefano Casorate e Santo Stefano Mede (schede su
   kosgroup.com), oltre a hospice e centri diurni (punto 2). Vanno aggiunte in piattaforma: le sedi «presenti su Google
   ma non su Yext» annunciate da RetailTune potrebbero coprirne una parte.
9. **Link delle schede** (`store.link`, oggi vuoto): non usare l'URL Yext dove è sbagliato. Villa Margherita Vicenza
   punta a `/it/villa-margherita` (due schede, riabilitazione e psichiatria, una sola entità); Poliambulatorio Parco di
   Veio puntava ad anniazzurri.it, va `https://kosgroup.com/it/centri/poliambulatorio-parco-di-veio`. Mandiamo noi la
   tabella codice → URL canonico.
10. **Profili Google:** sono nell'organizzazione Google di Yext; prima della dismissione la proprietà va trasferita a un
    account di KOS (annotato nella voce 24 il 25/10). Confermare con RetailTune chi lo fa e quando.

## Lato nostro

- Credenziali: la password arrivata il 12/11 è quella della documentazione (utente `kosgroup`). Quella della Stores API
  (utente `kosgroup_endpoint_user`) va ancora chiesta. Le password solo a Danilo, mai nel repository: per l'importatore
  in un'impostazione di sistema MODX.
- `store.link` e `store.media` vuoti fino alla fine dell'import: l'importatore usa `store.id` come chiave.
