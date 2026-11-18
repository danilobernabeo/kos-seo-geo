# Screaming Frog 18/11/2026 · anniazzurri.it · revisione indipendente del rapporto

Uso interno. Revisione del 18/11/2026 (Claude Fable 5.2) del rapporto `screaming-frog-20261118-aa.md`. Ogni numero è ricalcolato con script propri sui 112 CSV e sulle 88 sorgenti della scansione del 18/11, e confrontato con la scansione del 15/11. Nessuna richiesta ai siti: tutto è allo stato della scansione (5:08-9:43 ora italiana). Il docx non è stato generato (vincolo: un solo file nuovo nel repository).

## Riepilogo

1. **I numeri del rapporto reggono quasi tutti.** Confermati: 57 nodi residenza, 8 reparti, 14 `ItemList` (45 voci), 24 `priceRange`, 29 orfane, 461 e 460 link nel piè di pagina, 18 residenze con 2 email offuscate, 66 `h5`, 167 + 25 PDF, 0 errori di convalida, un JSON-LD su 463 pagine. I rilievi B2, C1, C2, C4, C6, C8 e C9 del 15/11 sono chiusi.
2. **R2 è sbagliato nei fatti.** La cifra «64,86 €» è nel testo di Parco di Veio («La retta giornaliera … è di **64,86 €** al giorno»). Il `priceRange` è fedele alla pagina. Manca il «Da» perché il testo dice «è di», non «parte da». Non c'è nulla da correggere per le linee guida Google; resta solo da chiedere a KOS se la retta è fissa.
3. **AA 17 non è chiusa per chi misura in kB decimali.** Lo script `tetto-peso.php` usa la soglia 200 × 1024 = 204.800 byte, la stessa di Screaming Frog. Sopra 200.000 byte restano 24 immagini, non 8: 15 sono immagini delle pagine (3 WebP servite davvero, 12 JPEG di riserva) e 9 sono Open Graph. La scheda Rete di Chrome mostra i kB decimali: su `/rsa-in-emilia-romagna` l'agenzia può leggere 203 kB. È il rischio vero per la verifica, più delle 8 immagini Open Graph.
4. **R1 è corretto ma incompleto.** Le 5 foto pesanti delle residenze sono anche l'`image` del nodo `#localbusiness` (e di 4 voci di `ItemList`): conviene ricomprimere gli originali sul posto, non cambiare `og:image`. Su `/home-en` è sbagliato anche `og:url` (`http://kos.fandomlab.com/dist/templates/home.html`), con titolo e descrizione in italiano e numeri vecchi.
5. **R5 è impreciso.** Solo 2 schede reparto su 8 hanno nel nodo un'email che la pagina non mostra. Su 4 l'email del nodo è nel riquadro ma offuscata, su una è nel contenuto offuscata, su Biancospino è in chiaro. Il problema è AA 21 (crawler senza JavaScript), non il dato invisibile.
6. **R7: la causa è il limite dell'elenco.** `/news/` mostra 1 + 256 notizie (`&limit=`256`` nel chunk `news-list`). Gli articoli sono 273: i 18 più vecchi (tutti del 2022) escono dall'elenco, uno per ogni articolo nuovo. Le orfane non sono «29 articoli»: sono 19 articoli e 10 pagine di altro tipo.
7. **Rilievi nuovi:** 10, nessuno bloccante. I principali: `og:url` sbagliato su 4 pagine, link «Radio» del piè di pagina che porta alle news, riquadri dei reparti diversi dal nodo (CAP di San Rocco), avviso `department` su 8 residenze non dichiarato.

## Correzioni al rapporto

Esito: C = confermata, D = da correggere, S = smentita, N = non verificabile sui CSV.

### Numeri

| Affermazione | Esito | Evidenza |
| --- | --- | --- |
| 463 pagine HTML 200 | D | `interni_tutti.csv`: 468 HTML con risposta 200. 463 sono le pagine con JSON-LD (462 pagine più la variante `?utm_`); le altre 5 sono i virtual tour (`/assets/virtual-tour/…/index.html`), non citati. Il 15/11 erano 467 e 462 |
| 78.631 triple su 463 pagine | C | `contiene_dati_strutturati_report_dettagliato.csv`: 78.631 righe, 463 URL |
| 460 indicizzabili, tutte in sitemap | C | 460 su 460 in `sitemap.xml`, tutte 200; nessuna non indicizzabile in sitemap |
| Una pagina nuova, nessuna sparita | C | Nuova `/saturazione-ossigeno-negli-anziani` |
| 57 nodi residenza completi | C | 57 `@id` = URL + `#localbusiness`, tipi doppi, `Geriatric`, `brand` e `parentOrganization` per `@id`; indirizzo, coordinate, telefono `+39…`, email, immagine su 57; orari 55, fax 48, `department` 8 |
| Riquadro uguale al nodo, 57 su 57 | C | Sorgenti: email, telefono, via, CAP e comune coincidono su 57 |
| 8 reparti | C | 8 `#department`: 7 `MedicalOrganization`, 1 `LocalBusiness`; `parentOrganization` verso la residenza, che li elenca in `department` |
| 5 `Service` «nuovi del 17/11» | D | 5 nodi su 5 pagine, 4 `@id` distinti. Nuovi sono 4 nodi (3 servizi); quello dello Sportello sociale c'era già il 15/11, con una forma diversa (vedi N8) |
| 14 `ItemList` (Milano 11, Genova 6, Torino 5, Ancona e Modena 3) | C | 14 liste, 45 voci, `numberOfItems` coerente; ogni voce uguale alla scheda su nome, indirizzo, coordinate, telefono, immagine e `priceRange` |
| 24 `priceRange` | C | 24 residenze; 41 occorrenze contando le 17 voci di elenco |
| 29 pagine orfane, 28 il 15/11 | C | `pagine_orfane.csv`: 29, tutte da sitemap; nuova `la-valenza-terapeutica-del-bello-e-dell-arte` |
| «29 articoli» | S | 19 `Article` (18 del 2022, uno del 27/7/2026) e 10 pagine senza `Article`: 5 convegni (tra cui `/convegno-santena`), `/le-vostre-storie`, `/servizi-personalizzati`, `/riabilitazione-specializzata-post-ictus`, `/melograno-argento-vivo`, `/speaker-per-un-giorno` |
| 8 immagini sopra 200 KB, tutte Open Graph | C con riserva | `immagini_oltre_200_kb.csv`: 8, soglia 204.800 byte. Sopra 200.000 byte sono 24 (vedi N1) |
| 461 e 460 link nel piè di pagina | C | `/notizie-ed-eventi` 461 pagine, `/domande-frequenti` 460 (manca `/home-en`) |
| «Un link del piè di pagina a `/il-segreto-per-tenersi-in-forma`» | D | Il link è nell'elenco di `/news/` (riga 3808 del sorgente, il piè di pagina inizia alla 3946): è una notizia pubblicata che risponde 301 verso `/news` |
| 18 residenze con 2 email offuscate, 39 con una | C | 18 e 39. La seconda è `rsaaperta.mi@…` su 11 schede e l'email generica della residenza, già in chiaro nel riquadro, su 7 |
| 66 `h5` «Contatti» | C | 66 sorgenti su 88 (57 + 8 + `/melograno-argento-vivo`). Tonengo ha un secondo `h5` |
| 167 + 25 PDF indicizzabili, 217 `noindex` | C | `interni_pdf.csv`: 409 PDF, tutti 200; 25 canonical verso PDF indicizzabili con risposta 200 |
| 216 PDF nella sitemap dei PDF, nessun file del CMS | C | 216, tutti sotto `/assets/`. Il 217° `noindex` è la copia con `'` del modulo di San Rocco |
| Tempo mediano 0,18 s; 0,95 s alle 9 | C | Mediana 0,183 s; 29 pagine tra le 9 e le 10 con mediana 0,95 s |
| Tutto entro 3 clic; `/news/` 467 KB | C | Livelli 0-3; 478.287 byte |
| Un solo H1; nessuna `<img>` senza `alt` | C | 463 su 463; `img_senza_alt` = 0 su 463 |
| 51 link esterni in 4xx, 22 Spreaker, 2 app | C | 51 (28 con 404, 20 con 403, 3 con 401); 14 sono `salute.gov.it` |
| `utm_source=chatgpt.com` in 8 articoli | S | Un solo articolo (`/vaccino-pneumococco-per-anziani-a-chi-serve-e-quando`, 2 link). Le altre 7 pagine hanno `utm_source=generator` negli iframe di Spotify |
| `href="http://` in 5 pagine; link relativi in 3 | C | 5 e 3 |
| 737 esportazioni | N | Qui 112 CSV |

### Rilievi del 15/11 dati per risolti

| Rilievo | Esito | Evidenza |
| --- | --- | --- |
| B2 · `/home-en` e `/azzurramente` | C | Un `@graph` per pagina; `WebPage`, `WebSite`, `Organization`; `inLanguage` «en» su `/home-en`. Restano i tag Open Graph (N2) |
| C1 · file del CMS in sitemap | C | Vedi sopra |
| C2 · titoli di Word | C | Nessun «Microsoft Word»; resta `…/santa-maria-in-chienti/Organizzazione.pdf` («Carta Intestata KOS») |
| C4 · briciole verso redirect | C con riserva | Nessun `item` verso un 3xx tra gli indirizzi scansionati. `https://anniazzurri.it/iscrizione-eventi/` (pagina di iscrizione al convegno, `noindex`) non è stato scansionato: stato non verificabile |
| C6 · pagine fuori sitemap | C | `/home-en` e `/azzurramente` in sitemap; iscrizione al convegno `noindex, follow` |
| C8 · reparti e Yext | C | 8 `#department`; 0 `data-yext-field`; nessuna stringa «yext» nelle 88 sorgenti |
| C9 · date | C | 0 casi su 273 articoli; nessuna data futura; la più vecchia è il `dateCreated` 1997 della pubblicazione dell'autrice |
| AA 17 «risolta per le immagini delle pagine» | D | Vero con la soglia di 204.800 byte. Vedi N1 |

### Rilievi R1-R8

| Rilievo | Esito | Evidenza e correzione |
| --- | --- | --- |
| R1 · 8 immagini Open Graph | C, incompleto | I 5 originali delle residenze sono anche `image` del nodo `#localbusiness` e di 4 voci di `ItemList` (Castagnola, Villa San Vincenzo, Cit Turin, Villa Pineta). Su `/home-en` va corretto anche `og:url`. Vedi «Valutazioni richieste» |
| R2 · `priceRange` di Parco di Veio «non visibile» | S | Sorgente, riga 592: «è di **64,86 €** al giorno». La cifra che non coincideva alla lettera era Quarto d'Altino («78.5 Euro» contro «Da 78,5 €»), già corretta il 18/11 |
| R3 · link verso redirect | C | Numeri esatti. Attenzione alla correzione: vedi N3 |
| R4 · `utm_` e `telto:` | C | Un link con `utm_` verso `/homecare-academy`; `telto:800131851` dà 404 |
| R5 · email dei reparti | D | Vedi «Valutazioni richieste» |
| R6 · seconda email offuscata | C | 18 residenze |
| R7 · orfane | D | Causa trovata, composizione diversa. Vedi «Valutazioni richieste» |
| R8 · commenti interni | C, incompleto | Home: 1 commento con «voce AA-18». Cure domiciliari private: 8 commenti, non 2. In più `/azzurramente` (N6), `/iscrizione-convegno…` («allegato F»), `/melograno-argento-vivo` (blocco contatti vuoto commentato), `/kos-academy` |

## Valutazioni richieste

### R1 · Immagini Open Graph e `image` dei nodi

- **Rischio per la verifica:** basso per le 8 immagini Open Graph. Il browser non le scarica; le vede solo chi usa il filtro di Screaming Frog. Il rischio maggiore è N1.
- **Dato strutturato:** l'`image` dei 57 nodi è l'originale `/assets/img/rsa/<residenza>.jpg`, lo stesso file di `og:image`. 5 originali superano la soglia (213-338 KB). Google non pone limiti di peso sull'immagine del dato strutturato, chiede solo che sia scansionabile e abbastanza grande: nessun difetto.
- **Correzione consigliata:** ricomprimere sul posto i 7 originali (5 residenze, 2 news) con `tetto-peso.php`. L'indirizzo resta uguale e si sistemano insieme `og:image`, nodo e voci di elenco. Passare `og:image` alla variante 1200 lascerebbe gli originali pesanti nel dato strutturato e due immagini diverse per la stessa residenza.
- **`/home-en`:** `og:image` e `og:url` puntano a `kos.fandomlab.com` in http; `og:title` «RSA Anni Azzurri» e `og:description` «60 residenze per anziani in 9 Regioni» sono in italiano e superati (57 residenze, 8 regioni). Vanno riscritti tutti e quattro.

### R2 · `priceRange` di Parco di Veio

- **Fatto:** la cifra è visibile. Il dato strutturato è coerente con la pagina e con le linee guida Google (testo libero sotto 100 caratteri, contenuto visibile).
- **Regola KOS del 18/11:** «Da X €» quando il testo dice «parte da». Qui il testo dice «è di»: aggiungere «Da» sarebbe un'informazione non scritta in pagina. La forma attuale è la più corretta.
- **Cosa resta:** chiedere a KOS se 64,86 € è la retta unica o la più bassa. Solo nel secondo caso si cambia il testo in «parte da», e il «Da» arriva dalla libreria. Priorità bassa.

### R5 · Email dei reparti

| Scheda | Email del nodo | Nella pagina |
| --- | --- | --- |
| Biancospino | generica di Il Giardino | In chiaro nel riquadro |
| La Limonaia, La Maremma | generica della residenza | Nel riquadro, offuscata |
| Hospice Beati Coniugi Martin | propria dell'hospice | Nel riquadro, offuscata |
| Hospice San Giorgio a Colonica | generica di Campi Bisenzio | Nel riquadro, offuscata |
| Appartamenti di Bergamo | `residenzasansisto@…` | Nel contenuto, offuscata; il riquadro ha solo l'indirizzo |
| Centro diurno San Giuseppe, centro diurno San Rocco | generica della residenza | Assente: la pagina mostra solo un'email personale offuscata |

- **Linee guida Google:** chiedono che il dato strutturato descriva contenuto visibile a chi legge. Un'email offuscata da EMO è visibile con JavaScript, quindi 5 schede sono in regola. Fuori regola, in senso stretto, sono 2 schede.
- **Correzione giusta:** la prima proposta del rapporto, email del nodo in chiaro nel riquadro, generato da `aaContatto` come su Biancospino. Risolve insieme AA 21 e N4. Togliere `email` dal nodo è lecito ma fa perdere un dato utile.
- **Coerenza del criterio:** lo stesso principio vale per orari (55 residenze) e fax (45 su 48), nel nodo e non nel testo della scheda. Era il primo punto del rapporto generale del 15/11 e il rapporto del 18/11 non lo riprende. R5 non è più grave di quello.
- **Da verificare:** gli appartamenti hanno come `parentOrganization` San Sisto 2 (stesso indirizzo, civico 9) ma l'email di San Sisto. Il dato viene dalla pagina; va confermato con KOS.

### R7 · Le 29 orfane

- **Causa per 18 dei 19 articoli:** `anniazzurri/elements/chunks/news-list.html` ha `&limit=`256`` con `&offset=`1``; il template `news` mostra la prima notizia a parte. L'elenco contiene 256 card e collega 254 dei 273 articoli, dal 22/12/2022 in poi. Fuori restano tutti gli articoli pubblicati prima (18, da febbraio a dicembre 2022). Ogni articolo nuovo ne fa uscire uno: il 16/11 è entrato `/saturazione-ossigeno-negli-anziani` ed è uscito quello del 21/12/2022. Non c'entrano template né «nascondi dai menu».
- **Eccezione:** `/oss-in-lombardia-corso-ial-e-assunzioni` (27/7/2026) è fuori dall'elenco per un'altra via, probabilmente il filtro `buildWhereExclude`. Da controllare nel manager.
- **Uno spazio sprecato:** `/il-segreto-per-tenersi-in-forma` è nell'elenco ma risponde 301 verso `/news`.
- **Correzione immediata:** `&limit=`0``, già indicata nel piano interno («a costo zero»). La pagina cresce di circa 30 KB. La paginazione resta la soluzione vera, fuori piano.
- **Le altre 10 pagine:** non dipendono dall'elenco. Sono pagine senza link dai menu né dai contenuti (5 convegni passati, 3 pagine di primo livello, `/speaker-per-un-giorno` e una copia della scheda Melograno). Per ognuna: un link dalla pagina madre oppure «Ricercabile» spento. `/melograno-argento-vivo` ha lo stesso H1 della scheda Melograno.

## Rilievi nuovi

| # | Gravità | Anomalia | Evidenza | Correzione |
| --- | --- | --- | --- | --- |
| N1 | Alta per la verifica di AA 17 | 15 immagini delle pagine tra 200.000 e 204.800 byte, sotto la soglia binaria e sopra quella decimale. 3 WebP servite: `…villa-pineta-1500.webp` (7 pagine), `rsa-in-emilia-romagna-1400.webp`, `rsa-in-provincia-di-bologna-1400.webp`. 12 JPEG di riserva, per esempio `…tonengo-1200.jpg`. In più l'originale Open Graph dell'Hospice San Giorgio (204.574 byte) | `immagini_tutti.csv`: 24 sopra 200.000 byte, 8 sopra 204.800. `scripts/immagini/tetto-peso.php`, righe 58 e 118: `$kb * 1024` | Rilanciare con `--kb=195` (199.680 byte, tetto di lavoro al 97%) sugli stessi elenchi più gli originali Open Graph. Il registro del 17/11 («nessuna sopra 200 KB») va precisato con l'unità |
| N2 | Media | `og:url` diverso dal canonical su 4 pagine: home → `/home`; `/news/` → `/news` (301); `/galleria-fotografica-convegno-anni-azzurri-2025` con doppia barra; `/home-en` → dominio esterno | `social_tutti.csv`. Causa: `[[!++site_url]][[*alias]]` nei chunk `head-*`, anche non in cache | `og:url` uguale al canonical, da un tag in cache |
| N3 | Media | Il link del piè di pagina verso `/notizie-ed-eventi` è l'icona «Ascolta Radio Anni Azzurri» e arriva a `/news/`. Applicare R3 alla lettera fisserebbe una destinazione sbagliata | Sorgente di `/news/`, riga 4145; `/radio-anni-azzurri` risponde 200 | Destinazione `/radio-anni-azzurri`, da confermare con KOS |
| N4 | Media | I riquadri di 7 reparti non sono generati dal nodo. Centro diurno San Rocco: CAP 20090 nel riquadro, 20054 nel nodo. «V.» al posto di «Via» su 6 schede. `href="tel:02210861"` senza prefisso (Hospice Martin) e `tel:055 199751` con spazio (San Giorgio) | Sorgenti delle 8 schede | Riquadro da `aaContatto` come Biancospino; insieme a R5 |
| N5 | Bassa | Avviso nuovo dal 17/11 su 8 residenze: «`department` is recommended to be of type `LocalBusiness`». Il riferimento è un `@id` senza tipo. Il rapporto elenca gli avvisi e lo omette, con i 33 `priceRange` attesi e i 3 degli appartamenti | `avvertenze_di_convalida_report_dettagliato.csv`: 5 + 3 pagine | Dichiararlo nella nota dello sprint 3 come avviso atteso, oppure aggiungere `@type` e `name` al riferimento |
| N6 | Bassa, sicurezza | `/azzurramente` espone nei commenti HTML il funzionamento della trappola antispam del modulo e della validazione del consenso | Sorgente di `/azzurramente` | Commenti MODX `[[- ]]`, insieme a R8 |
| N7 | Bassa, sicurezza | Cookie `PHPSESSID` senza `Secure` né `SameSite` su 466 pagine. La risposta 404 non porta HSTS, `nosniff`, `X-Frame-Options`, `Referrer-Policy` | `tutti_gli_header_http_di_risposta.csv` | `session_cookie_secure` e `session_cookie_samesite` in MODX; `always` sulle intestazioni nelle regole web. Da valutare con la revisione per Cloud Edge |
| N8 | Bassa | Nodi `Service` non uniformi. Sportello sociale: `servicePhone` come testo «800131851», `dayOfWeek` «Thursday» senza indirizzo schema.org, orario senza `opens` e `closes`; sulla stessa pagina un `FAQPage`. RSA Aperta: stesso `@id` su due pagine con `description` diversa | Triple di `/sportello-sociale-…`, `/rsa-aperta`, `/rsa-aperta-lombardia` | Sportello dentro `aa.servizi`; una sola descrizione per `@id` |
| N9 | Bassa | I 5 virtual tour sono HTML 200 indicizzabili solo per via del canonical verso la scheda: 7 parole, nessun `noindex`, nessun dato strutturato. Il canonical tra pagine diverse è solo un suggerimento | `interni_tutti.csv`, «Trasformato in canonical»: 31 = 25 PDF + 5 tour + la variante `?utm_` | `X-Robots-Tag: noindex` sulla cartella, dalle regole web. Fuori perimetro |
| N10 | Bassa | Il logo KOS del piè di pagina porta a `https://kosgroup.com/` (301) su 461 pagine. Un articolo collega una pagina 404 di kosgroup.com | `esterni_tutti.csv` | `https://kosgroup.com/it`; correggere il link dell'articolo |

Controlli senza rilievi: nessun `X-Robots-Tag` sulle 482 risposte HTML (solo sui 217 PDF); intestazione `Link` solo sui 25 PDF; nessun `@id` fuori convenzione (462 `#webpage`, 458 `#breadcrumb`, 273 `#article`, 57 `#localbusiness`, 14 `#itemlist`, 8 `#department`, 4 `#service`, enti e sito); nessun `@id` con tipi diversi; ente uguale su tutte le pagine; nessun `sameAs` verso kosgroup.com; nessun hreflang; canonical uguale all'URL su 462 pagine. Le voci di `ItemList` senza `@id` e con il solo tipo `MedicalBusiness` sono una scelta registrata l'8/11.

## Giudizio sulle priorità

- **Da alzare:** AA 17. Il rapporto la presenta come quasi chiusa e mette davanti le immagini Open Graph. Prima della verifica di Cerved/ProWeb va fatto N1 (15 immagini servite alle pagine), poi R1 sugli originali. È un rilancio di uno script esistente.
- **Da abbassare:** R2 esce dall'elenco. R5 scende a «completare AA 21 sui reparti» e si fa con N4, in un intervento solo.
- **Giuste:** R3, R4, R6 e R8 come «aperti, non bloccanti». R3 va fatto insieme a N3, non prima.
- **R7:** resta fuori perimetro, ma la correzione minima (`&limit=`0``) costa una riga ed evita che ogni nuova notizia renda orfana una vecchia. Conviene farla subito.
- **Da aggiungere prima della consegna dello sprint 3 (27/11):** N2 e i tag Open Graph di `/home-en`, perché la pagina è stata dichiarata chiusa il 17/11; N5 nella nota di consegna.
- **Dopo:** N6-N10.
- **Rilascio:** N1 e R1 toccano immagini delle schede, elementi dello sprint 2 in verifica questa settimana. Sono correzioni concordabili, ma vanno annunciate a Danilo prima del rilancio.
