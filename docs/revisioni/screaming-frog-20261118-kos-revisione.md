# Screaming Frog 18/11/2026 · kosgroup.com · revisione indipendente dei due rapporti

Uso interno. Revisione del 18/11/2026 (Claude Fable 5.2) dei rapporti `screaming-frog-20261118-kos.md` (sprint 1-3) e `screaming-frog-20261118-kos-generale.md` (salute generale). Ogni numero è ricalcolato con script propri sui CSV della scansione del 18/11 e confrontato con quella del 15/11.

## Riepilogo

1. **I numeri dei due rapporti reggono quasi tutti.** Su 916 pagine HTML 200: un JSON-LD, un H1, canonical uguale all'URL, 0 errori di convalida, sitemap di 868 URL tutti 200 e indicizzabili, date tra 2016 e 2026. I tre bloccanti del 15/11 sono chiusi.
2. **Il rilievo I2 non è nuovo.** I tre link vuoti nelle «aree di intervento» c'erano già il 15/11, identici (`href=""`). La frase «il 15/11 queste voci non c'erano» è falsa. Resta da correggere, ma non è una regressione.
3. **Il rilievo I1 è corretto nei fatti, ma la correzione proposta è sbagliata.** Le carte «proprie» non hanno nessuna intestazione `Link`: solo le 15 copie la hanno. Cambiare esempio lasciando «intestazione `Link` canonical» creerebbe un errore nuovo. Va tenuta la carta Biemme e corretta la parola «indicizzabile».
4. **Sei affermazioni smentite o non trovate**, tutte minori: C6 «risolto» solo in parte (restano tre commenti interni nei sorgenti), `str.tipo` di San Pancrazio non è vuota, i link esterni rotti in HTML sono 9 su 7 pagine e non 4, le 11 pagine corte sono di professionisti e non istituzionali, gli 826 link «relativi» non sono `href="/"`, l'avviso `department` non è nella nota dello sprint 3.
5. **Ospedale San Pancrazio `MedicalClinic` non viola CLAUDE.md.** È un valore scritto nell'import, coerente con la categoria del sito (cliniche psichiatriche). È una domanda di dato per KOS, non un difetto da correggere.

## Affermazioni dei rapporti

Esito: C = confermata, S = smentita, N = non verificabile sui CSV.

### Rapporto sugli sprint

| Affermazione | Esito | Dato ricalcolato |
| --- | --- | --- |
| 144.677 triple su 916 pagine | C | 144.677 righe, 916 URL |
| Pagine HTML 200 914 → 916, nuove news MOC e `/villamargherita`, nessuna sparita | C | Identico |
| Scansione dal 17/11 sera, cookie alle 22:11, 0,5 URL/s | C | Cookie emesso 22:11:10 GMT; timestamp 23:11-04:15 ora locale; 8.801 URL in circa 5 ore |
| 0 errori di convalida (erano 308 pagine) | C | 0 righe con errori; 399 pagine con soli avvisi |
| I 99 `practicesAt` hanno `address` e `telephone` | C | 99 su 99. In tutto 406 riferimenti (258 `mentions`, 99 `practicesAt`, 49 `worksFor`), tutti con indirizzo e telefono uguali alla scheda |
| 0 foto di `/img/strutture/` con alt vuoto, 0 immagini senza `alt` | C | 0 e 0; `img_senza_alt` = 0 su 916 pagine |
| 61 schede con `#localbusiness` più 2 `#department`, su 63 | C | 62 nodi `#localbusiness` (61 schede e la landing), 2 `#department` |
| Villalba, Casorate, Mede: `MedicalClinic` con indirizzo e telefono, senza `geo`, orari, specialità | C | Identico |
| `brand` su 43 schede (22, 14, 4, 1, 1, 1) | C | Identico |
| C4: `description` con «0444 043322», `telephone` `+39044415851`; sorgente 8 volte e 1 | C | Identico. I due link `tel:` della pagina portano allo 0444 043322 |
| C4: `knowsAbout` 27 e 28 voci | C | 27 e 28 |
| C4: «avviso `department` documentato» | S | Nella nota dello sprint 3 del repository la parola compare solo come frammento `#department`. L'avviso non è citato |
| C5: 50 sorgenti, 42 briciole identiche, 8 senza | C | 42 e 8, stesse 8 pagine. Su tutte le pagine: 10 senza `BreadcrumbList` (le 8 più `/en/work-in-kos/why-work-at-kos` e Giornata mondiale OSS) |
| C6: «nessun sorgente su 50 contiene il commento» | S in parte | Il commento del chunk `head` non c'è più. Restano tre commenti interni: vedi N1 |
| C7: 6 link relativi in un articolo | C | 6 link, 1 pagina |
| C7: 9 orfane più `/villamargherita` | C | 10, coincide con le pagine senza link ipertestuali in entrata |
| C7: 2 medici senza specialità, 8 articoli senza `image` | C | Calorio e Ali; 7 `Article` e 1 `NewsArticle` |
| KOS 11: 150 = 99 + 51, `mainEntity` su tutte | C | 99 `#medico`, 51 `#person`, 150 `mainEntity` corretti. 49 `worksFor` verso `#localbusiness`, 2 verso l'ente |
| Villa Margherita: `Hospital`, 2 reparti, avviso su 3 pagine | C | 8 medici e 21 altri professionisti verso la sede |
| Date: nessuna prima del 2000, mai `dateModified` prima di `datePublished` | C | `dateModified` 2017-2026 su 897 pagine, `datePublished` 2016-2026 su 380, nessuna data futura |
| Sitemap 868, tutti 200 e indicizzabili | C | 868; nessun parametro; +2 rispetto al 15/11 |
| KOS 24: su 62 nodi indirizzo e telefono 62, coordinate e orari 59, email 52, specialità 52, servizi 52, catalogo 17 | C | Identico |
| Tipi: `MedicalClinic` 53, `DiagnosticLab`+`MedicalBusiness` 5, `Dentist` 2, `Hospital` 2 | C | Identico |
| Title: 3 senza marchio, 2 con «\| Kos», landing con «— KOS» | C | In più `/en` ha title «KOS Group» senza separatore |
| PDF: 603 tutti 200, 219 + 15 + 369 | C | Identico. Un PDF nuovo: `/assets/doc/kos-care-gdpr-informativa.pdf` (`noindex`), non citato |
| I1: carta Biemme con canonical verso Delta | C | Vedi «Valutazioni richieste» |
| Sprint 1: «gli altri 826 relativi sono `href="/"` e immagini» | S | Gli 826 sono link `href="#ancora"` (e 3 `href=""`) risolti da `<base href="https://kosgroup.com/">`. Le immagini relative sono 2.982 su 782 pagine |
| Sprint 1: nessun URL annidato; 404 solo `/ritiro-referti` e un'immagine | C | Identico |
| 60 URL bloccati da robots.txt | C | 42 `parla-con-noi`, 9 + 9 form di struttura |
| Hreflang: 38 pagine, nessun errore | C | 19 coppie reciproche, con sé stesse e `x-default`, tutte 200 |
| Tempi: mediana 0,12 s, p75 0,22 s, 5 oltre 1,5 s (15/11: 0,24, 1,18, 104) | C | Identico |
| «filtri Sitemap e livello di scansione vuoti» | S in parte | Il livello di scansione è compilato (vuoto solo sulle 10 orfane). Il rapporto generale lo usa |
| I2: «il 15/11 queste voci non c'erano» | S | C'erano: vedi «Valutazioni richieste» |
| I2: «Riabilitazione da dipendenze probabilmente non esiste» | S in parte | Esiste `/it/servizi-e-cure/psichiatria/dipendenze` (200), candidata naturale |
| «754 esportazioni» | N | Qui ci sono 98 CSV |

### Rapporto generale

| Affermazione | Esito | Dato ricalcolato |
| --- | --- | --- |
| 8.801 URL, 5.847 interni, 916 HTML 200 | C | Identico |
| Interni: 5.775 in 200, 10 in 301, 2 in 404, 60 bloccati, nessun 5xx | C | Identico |
| «10 redirect 301 (tutti voluti)» | C con riserva | I redirect sono voluti, ma 23 link interni li puntano ancora: vedi N2 |
| Fuori sitemap: 46 `?page=N` e 2 pagine nascoste | C | Identico |
| 0 title duplicati; description duplicate in 5 gruppi; 4 sotto 70; 1 oltre 160 | C | I 5 gruppi sono gli elenchi paginati (51 pagine). `/villamargherita` 197 caratteri |
| 50 immagini oltre 200 KB (47), 4.173 senza `width`/`height` | C | Nuove: 2 di Villa Margherita e la news MOC |
| Alt vuoto 274 → 225 | C | Identico |
| 42 esterni in 4xx, 2 in 5xx | C | 26 in 403, 14 in 404, 2 in 400 |
| «Solo 4 sono in pagine HTML, in articoli di approfondimento» | S | 9 link su 7 pagine HTML: 4 approfondimenti, 2 news, 1 scheda. Vedi N3 |
| `/it/ritiro-referti` ha 895 link in entrata | C | 933 link da 896 pagine (una è la pagina stessa) |
| HSTS, `nosniff`, `SAMEORIGIN`, referrer policy «su tutte le risposte» | C con riserva | Su 5.775 risposte 200 e sugli 8 redirect. Le 2 risposte 404 non le hanno |
| `no-store` e `PHPSESSID` «su tutte le 918 pagine HTML» | S lieve | 916 pagine 200, più la 404 `/ritiro-referti` |
| San Pancrazio: «TV `str.tipo` vuota» | S | In `strutture-yext-2026-11-06.json` è scritto `"str.tipo": "MedicalClinic"`. La TV in produzione non è verificabile da qui |
| 27 pagine sotto 100 parole: 15 convenzioni e «11 istituzionali brevi» | S in parte | 27 confermate: 15 convenzioni, 11 pagine di professionisti (89-98 parole), 1 `/it/villa-dei-pini` |
| PDF: «27 URL con caratteri non ASCII o spazi» | S in parte | 27 URL in tutto: 18 WebP, 6 JPEG, 3 PDF |
| Professionisti senza specialità «già nella nota dello sprint 3» | S | La nota dice «con specialità» senza eccezioni. Calorio e Ali non sono citati |
| 77 pagine a 10 clic o più, fino a 14 | C | 15 + 17 + 17 + 17 + 11 |
| `?page=N`: title con «\| Gruppo KOS», `og:url` sulla prima pagina (46) | C | 46 e 46 |
| og:image predefinita su 43 pagine di cure | C | 30 psichiatria e 13 riabilitazione. In tutto 127 pagine non paginate, compresi gli 8 articoli senza immagine |
| «Lorem ipsum» in 2 FAQ di KOS Academy | C | 2 occorrenze, 1 pagina |
| 1 PDF indicizzabile con titolo «Microsoft Word…» | C | Identico |
| Tabella di confronto 15/11 → 18/11 | C | Tutte le righe |

## Affermazioni delle note

| Nota | Citazione | Esito su tutte le pagine |
| --- | --- | --- |
| Sprint 1 | «un solo blocco JSON-LD, un solo canonical e un solo H1» | Vero su 916 pagine |
| Sprint 1, KOS 3 | `max-age=15552000`, CSS e JS con `?v=` | Vero: 4.224 immagini, 31 CSS e JS, tutti con `?v=`. Font non scansionati |
| Sprint 1, extra | Pagina inesistente → 404 diretto | Coerente: `/ritiro-referti` risponde 404 senza redirect. Un solo caso osservato |
| Sprint 1, KOS 19 | `Disallow` su `formId` | Coerente: 60 URL con `formId` bloccati |
| Sprint 1 (regola) | Link interni assoluti | Vero salvo 6 link in 1 articolo. Le 2.982 immagini con `src="assets/…"` dipendono da `<base>` |
| Sprint 1 (regola) | Nessun annidato `/it/…/it` | Vero: 0 |
| Sprint 2, KOS 9 | Ente unico, mai `sameAs` verso KOS Care | Vero: `https://kosgroup.com/#organization` su 916 pagine, `subOrganization` verso `https://anniazzurri.it/#organization`, 0 `sameAs` verso anniazzurri.it |
| Sprint 2, KOS 12-13 | Briciole JSON-LD uguali alle visibili | `BreadcrumbList` su 906 pagine, posizioni consecutive, ultimo elemento = URL, 864 `item` tutti 200. Visibili: solo il campione di 50 |
| Sprint 2, KOS 10 | Articoli e video | 234 `Article`, 146 `NewsArticle`, 21 `VideoObject`; campi uniformi; 8 senza `image`. Senza nodo solo i 2 progetti speciali |
| Sprint 2, KOS 24 | Nodo con `@id` = URL della scheda | Vero: 62 su 62, `url` = pagina, `parentOrganization` su tutti. CAP, province, telefoni, coordinate, orari plausibili |
| Sprint 2, KOS 24 | Campi del nodo | Non uniformi ma dichiarati: coordinate e orari 59, email 52, specialità 52, servizi 52, `sameAs` 44, catalogo 17 |
| Sprint 2, KOS 21-23 | Marchio una volta nel title | Mai due volte. 3 senza marchio, 2 «\| Kos», 46 paginate «\| Gruppo KOS» |
| Sprint 2, KOS 8 | PDF con `noindex` o indicizzabili | 369 `noindex`, 219 indicizzabili senza intestazione `Link`, 15 copie con `Link` canonical |
| Precisazioni | «nessun 1970», `dateModified` ≥ `datePublished` | Vero. 19 convenzioni senza `dateModified` |
| Precisazioni | «il riferimento riporta anche indirizzo e telefono… l'errore non compare più» | Vero: 258 `mentions` su 163 pagine, 0 errori |
| Precisazioni | «866 URL» al 16/11 | Vero al 15/11; oggi 868 |
| Precisazioni | Biemme «PDF indicizzabile (nessun `noindex`, intestazione `Link` canonical)» | Intestazioni vere, parola «indicizzabile» imprecisa: I1 |
| Precisazioni | `casa-villa-maura/contratto.pdf` con `noindex` | Vero |
| Precisazioni | Le 5 landing con lo stesso nodo della scheda | Non verificabile: le landing non sono nella scansione |
| Sprint 3, KOS 7 | Gallerie «nome – foto N di T», elenchi con il nome | Vero: 0 foto di struttura con alt vuoto |
| Sprint 3, KOS 7 | «338 segnaposto … decorativi» | Vero nel principio: `medico-thumb.svg` sempre `alt=""`. Oggi 412 occorrenze su 50 pagine |
| Sprint 3, KOS 7 | «la nostra storia: tutte le foto descritte» | Vero: i 12 file con alt vuoto sono icone e banner comuni |
| Sprint 3, KOS 11 | 150 pagine, 99 `IndividualPhysician` e 51 `Person` | Vero. `practicesAt` sempre verso un `#localbusiness` con `address`; indirizzo e telefono del medico uguali alla sede su 99 |
| Sprint 3, KOS 11 | «medici … con specialità» | 97 su 99. La nota non lo precisa |
| Sprint 3, KOS 11 | «due professionisti senza una sede» legati all'ente | Vero: Argentieri (ruolo «Psichiatra», `Person` per scelta) e Stronati |
| Sprint 3 | `#department` solo sulle unità di Villa Margherita | Vero: nodo completo solo sulle 2 unità; citati dalla landing |
| Sprint 3 | «inserimento nella sitemap … in definizione» | Superato: in sitemap. Da aggiornare |
| Sprint 3 | Nessun cenno all'avviso `department` | Avviso Google su 3 pagine. Da aggiungere |

## Valutazioni richieste

### I1 · Carta Biemme con canonical verso Delta

- **Fatto: confermato.** Biemme risponde 200, senza `X-Robots-Tag`, con `Link` canonical verso la carta di Delta. Era così anche il 15/11. Gamma, Selemar e Salus Villalba puntano alla stessa carta.
- **Dato che il rapporto non vede:** nessuno dei 219 PDF indicizzabili ha un'intestazione `Link`. La hanno solo le 15 copie. La nota del 6/11 dice «nessun noindex, intestazione Link canonical»: descrive una copia, e chi lancia `curl -sI` trova esattamente quello.
- **Peso:** basso. Le intestazioni citate sono vere. È sbagliata solo la parola «indicizzabile» nelle precisazioni.
- **Correzione proposta dal rapporto: da non seguire.** Un esempio di carta «propria» non mostra nessuna intestazione `Link`: la frase resterebbe falsa in un altro punto.
- **Correzione consigliata:** tenere Biemme e scrivere «copia della carta di Punto Prelievi Delta: nessun `noindex`, `Link` canonical verso l'originale, che è indicizzabile e non ha intestazioni». Decide Danilo.

### I2 · Link vuoti nelle aree di intervento

- **Non è nuovo.** Il 15/11 gli stessi tre link erano nel CSV `interni_reindirizzamento_(3xx)_link_in_entrata.csv`, stesso percorso `#aree-intervento`. Il testo delle pagine del 15/11 elenca già «Medicina dello Sport». Il sorgente del 15/11 di San Pancrazio contiene `<a href="" title="Riabilitazione da dipendenze" class="card-content">`.
- **Perché sembra nuovo:** il 15/11 mancava l'esportazione `pagine_con_tag_di_ancoraggio_vuoti.csv`.
- **Destinazioni probabili:** `/it/servizi-e-cure/ambulatori-e-medicina-specialistica/medicina-dello-sport` (già collegata da Myolab, Fidia e Villa Margherita Benevento) e `/it/servizi-e-cure/psichiatria/dipendenze`.
- **Peso:** piccolo, contenuto preesistente. Non va presentato come regressione.

### Ospedale San Pancrazio `MedicalClinic`

- **Non viola CLAUDE.md.** La regola chiede il sottotipo medico più specifico secondo l'autorizzazione. `MedicalClinic` è un sottotipo medico valido. `Hospital` non è più specifico: è un tipo diverso.
- **Non è una dimenticanza:** nell'import il valore è scritto. Anche Villa Pineta, che Yext marcava `Hospital`, è stata portata a `MedicalClinic`.
- **Coerenza con il sito:** la pagina `/it/strutture/ospedali` elenca solo Villa dei Pini Civitanova. San Pancrazio è tra le «cliniche psichiatriche».
- **Cosa resta:** una domanda a KOS sull'autorizzazione. Se diventa `Hospital`, lo stesso criterio va applicato alle altre case di cura (Villa Pineta, Villa dei Pini Firenze…). Nel rapporto generale va spostato da «Da correggere» a «Da verificare con KOS».

## Anomalie nuove

In ordine di gravità. Nessuna è bloccante.

### N1 · Commenti interni ancora visibili nel sorgente (C6 chiuso solo in parte)

- **Pagine:** 13 sorgenti su 50. Per estensione: le schede struttura e le pagine con lo stesso template, le 4 pagine con la barra di ricerca, la landing.
- **Esempi:**
  - `/it/centri/poliambulatorio-villalba`, `/it/ritiro-referti`: «Extra delle schede struttura: CSS di lite-youtube su 190 e 227. Il dato strutturato … kosSchema (voce 24): lo script Yext Knowledge Tags è stato tolto.»
  - `/it`, `/it/servizi-e-cure`, `/it/strutture`, `/en/services-and-care`: «Barra di ricerca Yext Answers (pagine 1, 3, 4, 983…). Da sostituire con la dismissione di Yext (linea C).»
  - `/villamargherita`: «Extra delle landing … i JSON-LD scritti a mano head-<id> non sono più richiamati.»
- **Evidenza:** `sorgente_pagina/`, ricerca dei commenti HTML. C'erano già il 15/11 (24 e 2 sorgenti su 70).
- **Causa probabile:** commenti HTML nei chunk `head-*`, non convertiti il 15/11 insieme a quello del chunk `head`.
- **Gravità:** nessun tag MODX eseguito. Espongono numeri di voce, id di risorsa e piani interni a chi apre il sorgente, compresa l'agenzia.
- **Correzione:** `[[- … ]]` come per il chunk `head`. Tocca l'head: dopo il 20/11 o come correzione concordata.

### N2 · Link interni verso redirect (23 link)

- **Pagine:** 5 elenchi `…/approfondimenti?page=16, 18, 20, 22, 25` con 18 link verso 6 URL di articoli in 301; 5 pagine di trattamenti di psichiatria con 5 link verso `/it/centri/beata-corte` (301 → `corte-orero`).
- **Esempi:** `…/approfondimenti/ritorno-alla-guida` → `…/ritorno-alla-guida-dopo-gca`; `…/ansia-come-riconoscere-i-sintomi` → `…/ansia-e-stress-come-si-manifestano`.
- **Evidenza:** `interni_reindirizzamento_(3xx)_link_in_entrata.csv`. Già presenti il 15/11.
- **Causa probabile:** risorse sostituite con redirect ma ancora pubblicate negli elenchi; riquadro «Beata Corte» scritto a mano.
- **Correzione:** togliere le 6 risorse dagli elenchi o collegare la destinazione; aggiornare il riquadro. Il rapporto generale li archivia come «tutti voluti».

### N3 · Due link esterni davvero rotti in pagine HTML

- **Pagine:** `/it/centri/santo-stefano-mede` → `https://www.asst-pavia.it/node/67` (404 dopo redirect); news 2024 «Bollini RosaArgento» → `bollinirosargento.it/news/…` (404).
- **Evidenza:** `esterni_errore_client_(4xx)_link_in_entrata.csv` e `reindirizzamento_(3xx)_link_in_entrata.csv`.
- **Nota:** gli altri 7 link in HTML sono 403 di siti che bloccano i crawler (salute.gov.it, hopkinsmedicine.org…), probabilmente falsi.
- **Correzione:** nuovo indirizzo o rimozione. La scheda di Mede è stata toccata il 16/11: conviene farlo con il resto.

### N4 · Link `#ancora` risolti verso la home per via di `<base href>`

- **Pagine:** 589 pagine, 837 link («LEGGI ARTICOLO» 403, «Trova struttura» 201, «Come accedere» 86, «Indicazioni» 63…).
- **Evidenza:** `interni_reindirizzamento_(3xx)_link_in_entrata.csv`, tipo «Relativo al percorso»; sorgente: `href="#strutture-correlate"` con `<base href="https://kosgroup.com/">`.
- **Effetto:** per un crawler sono link alla home in 301. Per l'utente dipende da `scroll-anchors.js`; senza JavaScript portano alla home.
- **Stato:** noto dal 15/11 come «falso positivo». Non lo è del tutto, ed è descritto male (`href="/"`).
- **Correzione:** miglioria, fuori dalle voci. Va valutata nel consolidamento degli head (togliere `<base>` o scrivere l'ancora con l'URL della pagina).

### N5 · Minori

- **Risposte 404 senza intestazioni di sicurezza:** le 2 risposte 404 non hanno HSTS, `nosniff`, `X-Frame-Options`, referrer policy. Probabile `add_header` senza `always`. Da vedere con Cloud Edge.
- **Un link `http://`** in `/it/news-e-media/news/2025/al-via-la-ventesima-edizione-del-concorso-letterario-storie-di-donne`.
- **Una foto di elenco con alt vuoto:** scheda «KOSì vicini» in `/it/news-e-media/news?page=5` (4 varianti). Unica tra le news: probabile titolo con carattere speciale.
- **34 immagini delle tecnologie con alt vuoto** in `…/tecnologia-e-innovazione`: aperto dal 15/11, non ripreso nei rapporti.
- **Nome del nodo diverso dall'H1:** «Villa dei Pini» (H1 «Villa dei Pini Firenze»), «Villa dei Pini Civitanova Marche» (H1 senza «Marche»).
- **`availableService` senza URL:** 17 voci, quasi tutte del Poliambulatorio Valdaso (servizi scritti a mano il 15/11). Non è un errore.
- **`/villamargherita`:** `dateModified` 21/09/2026 su una pagina pubblicata il 13/11; azione di prenotazione senza catalogo, come Parco di Veio.
- **PDF nuovo** `kos-care-gdpr-informativa.pdf`: `noindex`, collegato dalla landing.

## Metodo e limiti

- **Dati:** 98 CSV e 50 sorgenti del 18/11; CSV, 70 sorgenti e 63 testi del 15/11. Script pandas scritti per questa revisione.
- **Dato strutturato:** grafo ricostruito dalle triple per ogni pagina (soggetto, predicato, oggetto). I nodi citati per `@id` sono espansi da Screaming Frog: per i conteggi ho usato i nodi con `@id` della pagina stessa.
- **Sitemap:** ricostruita dai link «Sitemap XML» (868, tutti da `sitemap.xml`).
- **Limite di `tutti_link_in_entrata.csv`:** contiene solo link verso URL interni in 200 (156.367 righe). Per redirect, 404 ed esterni ho usato le esportazioni dedicate.
- **Briciole visibili:** solo i 50 sorgenti. L'estrazione su tutte le pagine manca ancora.
- **Non coperti:** le 5 landing `/it/landing/…`, robots.txt, llms.txt, bot AI, font, domini storici, PageSpeed, TV in produzione.
- **Repository:** letti `strutture-yext-2026-11-06.json` e il docx della nota dello sprint 2 per il testo di KOS 8.
- **Richieste ai siti:** nessuna. Nessun commit. Il docx di questo rapporto non è generato: il vincolo era un solo file.
