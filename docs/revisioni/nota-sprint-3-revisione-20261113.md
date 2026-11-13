# Revisione indipendente: Nota di consegna dello sprint 3

Uso interno. Revisione del 13/11/2026 su `docs/docx/Nota di consegna dello sprint 3.docx` (versione corretta dopo la prima revisione, commit bfbe12e). Modello del revisore: Claude Fable 5.2. Metodo: testo estratto con pandoc; confronto con registro, piano, `docs/kos-11.md`, `docs/aa-4.md`, `docs/kos-7-aa-9.md` e i CSV dei collaudi del 13/11; richieste GET alla produzione (una ogni 1,3 s): le 57 schede residenza, le 14 pagine provincia, Villa Margherita (landing, pagina del marchio, due unità), le 150 pagine dei professionisti, le pagine dei «Come verificare», le due sitemap; CLS ricalcolato su BigQuery; pagine ufficiali di Google (caroselli beta) e schema.org (release). Limiti: audit non nel repository (numeri di slide non controllati, «slide 33» non trovata nei documenti); Rich Results Test e PageSpeed non rieseguiti; il rapporto della prima revisione non è in `docs/revisioni/`, quindi le correzioni sono controllate sulle fonti, non sull'elenco dei rilievi.

## Problemi, in ordine di gravità

**1. Errore sul sito, visibile sulle pagine d'esempio della nota — `dateModified` al 1970.**
Francesco Perini e Andrea Mastriani (i due esempi di KOS 11) hanno `"dateModified":"1970-01-01T01:33:45+01:00"` nel nodo `WebPage`. Non è un caso isolato: 86 pagine di professionisti su 150; nelle sitemap `<lastmod>1970-01-01…` su 327 URL di 866 (kosgroup.com, home `/it` compresa) e 141 di 457 (anniazzurri.it); 6 schede residenza su 57.
Causa (letta nel codice, non provata sul database): `scripts/deploy/aggiorna-campi.php`, riga 166, conserva la data con `(int) $res->get('editedon')`, ma MODX 3 restituisce la stringa «2025-03-04 10:12:00»: resta l'anno (2025, 2026, 1969) e viene scritto come timestamp.
Correzione: `strtotime` al posto di `(int)`; ripristino di `editedon` dalle tabelle `_bak_`; poi rigenerare le sitemap. Tocca elementi dello sprint 2 in verifica dal 16/11: da sistemare prima dell'invio.

**2. Errore — AA 21, fax nel riquadro.** «indirizzo, telefono, fax ed email della residenza».
Il fax è visibile in 3 riquadri su 57 (Rezzato, San Lorenzo, Villaggio San Francesco); nel nodo struttura c'è su 48. `contactBox.html` non ha il campo `faxPhone`.
Correzione: togliere «fax» dalla riga 21 (resta nella 11) oppure aggiungerlo al chunk.

**3. Errore — AA 5, `brand`.** «Il nodo della residenza porta nome, immagine, il marchio Anni Azzurri (brand)».
8 schede su 57 non hanno `brand`: Borgomanero, Casa Serena, Idice, Il Poggione, Sestri Ponente, Valdaso, Vico Mercati, Villa Pineta (tutte «Residenza Anni Azzurri …»). `aaSchema.php`, riga 539: esce solo se la TV `str.marchio` contiene «anni azzurri».
Correzione: compilare la TV sulle 8 schede (o derivare il marchio dal sito); `parentOrganization` c'è su 57.

**4. Da precisare — AA 10, quante pagine sono idonee al carosello.** «sono idonee le pagine provincia con almeno tre residenze».
La regola è giusta (Google: «at least three `itemListElement.item`»), ma le pagine con almeno tre residenze sono 5 su 14: Milano 11, Genova 6, Torino 5, Ancona 3, Modena 3. Ne hanno 2 Bergamo, Bologna, Brescia, Cuneo, Firenze, Monza e Brianza, Novara, Venezia; Macerata 1. In tutto 45 residenze. Sintesi e tabella dicono «sulle 14 pagine provincia»: chi verifica può aspettarsi 14 caroselli.
Correzione: «idonee 5 pagine su 14 (Milano, Genova, Torino, Ancona, Modena)».

**5. Da precisare — AA 21, verifica con JavaScript disattivato.** «i contatti si vedono».
Vero per indirizzo, telefono ed email della residenza (57 su 57, uguali al nodo). Ma in tutti i 57 riquadri l'email del direttore passa ancora da EMO: senza JavaScript si legge «Turn on Javascript!».
Correzione: dirlo nella riga («l'email del direttore resta offuscata») o toglierla dall'offuscamento.

**6. Da verificare — Villa Margherita, pagina senza collegamenti.**
Verificato come scritto: `/villamargherita` ha il nodo `Hospital` `#localbusiness`, canonical su sé stessa, due `department`; le unità sono `MedicalOrganization` `#department` con `parentOrganization` verso la sede; Perini e Nordera `IndividualPhysician` con `practicesAt`; `/it/villa-margherita` senza nodo. Però la landing non è nella sitemap (il registro, riga del 12/11, dice «produzione 13/11») e nessuna delle pagine aperte la collega. La nota cita link e profilo Google, non la sitemap: aggiungerla. Inoltre: la `description` del nodo dice «Chiama lo 0444 043322» mentre `telephone` è +39 0444 15851; i `knowsAbout` dei due reparti coincidono per 26 voci su 27-28 (psichiatria elenca la riabilitazione delle mielolesioni).

**7. Imprecisione — AA 11.** «indirizzo, coordinate, telefono, email, fax, orari».
Orari assenti su Il Poggione e Sestri Ponente, fax su 9 schede. Aggiungere «dove presenti».

**8. Verificabilità.** Mancano gli URL: AA 21 (proposta: Villa Reale), AA 9 (`/grosseto-presentato-il-centro-diurno-maremma`, verificato: 5 foto), KOS 7 (`/it/centri/centro-cardinal-ferrari`, verificato: «… – foto 1 di 9»).

**9. Fonti da allineare (interne).** `docs/aa-4.md` riporta ancora 287 e 6.065 pagine viste (misura dell'8/11); il registro dice «7 medici» di Villa Margherita. L'esportazione GA4 su BigQuery si ferma al 10/11 (nessuna tabella dell'11 e del 12): per questo il «dopo» è di due giorni; da controllare che riprenda.

## Verificato come corretto

- Collaudi del 13/11: 57/57 schede; 150/150 professionisti, 99 `IndividualPhysician` e 51 `Person` (8/11: 91 e 59; gli 8 medici passati sono delle unità di Villa Margherita); 63/63 schede e landing.
- 57 schede in produzione: un solo JSON-LD, tipo `["MedicalBusiness","MedicalOrganization"]`, `Geriatric`, `parentOrganization` e immagine su tutte; `priceRange` su 24; nessuna stringa «yext» né microdati nel sorgente.
- 14 pagine provincia con `ItemList`; Genova 6 voci con nome, URL, immagine, `priceRange` su 5 (sempre sotto 12 caratteri).
- CLS, ricalcolato: home 0,157 (296) → 0,077 (122), 60,8% → 13,1%; altre pagine 0,077 (6.381 e 2.680), 19,4% → 18,1%.
- Correzioni della prima revisione riscontrabili: `IndividualPhysician` dalla versione 24.0 di schema.org (9/1/2024); 8 medici; «sei punti» = sei righe; carosello in beta per SEE, Turchia e Sudafrica, comparsa non garantita.
- 150 pagine di professionisti in produzione: un solo JSON-LD, `mainEntity` sul professionista; 99 medici con `practicesAt`, indirizzo e telefono, senza `jobTitle`; 51 `Person` con `jobTitle` e `worksFor`.
- Albanelli `IndividualPhysician` con `practicesAt`; Mastriani `Person` con `jobTitle` e `worksFor`; due `Person` legati all'ente (Argentieri, Stronati); refusi e ruoli segnalati presenti nel collaudo.
- Alt: Volpiano (direttrice), «La nostra storia» in italiano e inglese senza immagini prive di alt; 140 immagini su 20 pagine, 338 segnaposto, 10 e 18 in 3 news, 37 schede convertite come nei documenti.
- Nessuna proposta fuori piano né dettaglio interno nel testo.
