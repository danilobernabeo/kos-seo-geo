# Verifica periodica con Screaming Frog SEO Spider

Uso interno. Scansione di controllo dei due siti con uno strumento esterno, da incrociare con i collaudi del repository.
Obiettivo: nessuna sorpresa come quelle della scansione SEOZoom del 12/11 (link interni con `utm_` e parametri Google
Ads, cartella pubblicata vuota, link verso pagine con redirect). Dal computer di Danilo, con la licenza (kosgroup.com
supera i 500 URL della versione gratuita).

## Cadenza

| | Completa (mensile) | Leggera (settimanale) | JavaScript (mensile) |
|---|---|---|---|
| Quando | prima domenica del mese; la prima il 15/11 | ogni domenica, dalla seconda | dopo la completa |
| Cosa | pagine, PDF, immagini, CSS/JS, link esterni (solo stato), ricerche nel codice, dato strutturato | pagine HTML e sitemap, ricerche nel codice, dato strutturato | circa 30 pagine (2-3 per tipo), HTML del server contro HTML dopo JavaScript |
| Velocità | 0,5 URL/s | 0,5 URL/s | 0,5 URL/s |
| Durata kosgroup.com (stima) | 3-5 ore | circa 30 minuti | qualche minuto |
| Durata anniazzurri.it (stima) | 2-3 ore | circa 15 minuti | qualche minuto |
| Confronto | con la completa del mese prima | con la leggera della settimana prima | — |
| Configurazione salvata | `kos-completa.seospiderconfig`, `aa-completa.seospiderconfig` | `kos-leggera…`, `aa-leggera…` | `kos-js…`, `aa-js…` |

Un sito per volta, prima kosgroup.com e poi anniazzurri.it. Velocità: la protezione anti-bot di MODX Cloud è scattata a
1,7 richieste/s (SEOZoom, 12/11) e il 1/11 con i collaudi; a 0,5 URL/s il margine è ampio. Niente scansioni durante le
settimane di verifica di Cerved/ProWeb.

Prima di partire: home del sito aperta nel browser dallo stesso computer (nessun «Security Check»). Se compare, o se la
scansione si riempie di 403: pausa, riavvio del router per un nuovo IP (se dinamico), controllo della home, ripresa.

## Impostazioni comuni (tutte le configurazioni)

Le voci di menu sono quelle della versione in inglese.

- **Configuration › System › Storage Mode:** Database (serve per il confronto tra scansioni e per riprendere una
  scansione interrotta).
- **Configuration › Speed:** Max Threads **1**, Limit URL/s **0,5**.
- **Configuration › Spider › Crawl:**
  - spunta Internal, Canonicals, Pagination (rel next/prev), Hreflang, Meta Refresh, Follow Internal «nofollow»;
  - Crawl Linked XML Sitemaps **sì**, con «Auto Discover XML Sitemaps via robots.txt» (trova le pagine non collegate dal
    menu e segnala quelle in sitemap ma orfane o non indicizzabili).
- **Configuration › Spider › Extraction › Structured Data:** JSON-LD, Schema.org Validation, Google Rich Result Feature
  Validation; togli Microdata e RDFa (i siti non li usano: se ne compaiono, vanno segnalati). Spunta anche «Store HTML».
- **Configuration › Spider › Advanced:** Respect Noindex e Respect Canonical **no** (vogliamo vedere tutte le pagine e
  confrontare il canonical); Always Follow Redirects sì; Respect robots.txt sì.
- **Configuration › Spider › Preferences:** dimensione massima delle immagini **200 KB** (soglia dell'audit); lunghezze di
  title e description come nell'audit (title 65 caratteri, description 160).
- **Configuration › Exclude:** solo la pagina dei risultati della ricerca Yext di kosgroup.com
  (`https://kosgroup.com/it/risultati.*`). Gli indirizzi con parametri **non** si escludono: i link interni con `utm_` o
  parametri Google Ads devono comparire. Gli elenchi paginati (`?page=N`) sono pochi e utili (canonical).
- **Configuration › Custom › Search** (cerca nell'HTML di ogni pagina; ogni riga «Contains»):

  | Nome | Testo | Cosa trova |
  |---|---|---|
  | utm | `utm_` | link interni con parametri di campagna |
  | gclid | `gclid=` | link interni con parametri Google Ads |
  | gad | `gad_source=` | idem |
  | stage | `stage.kosgroup.com` e `stage.anniazzurri.it` (due righe) | link verso gli ambienti di prova |
  | modxdev | `modx.dev` | link verso il vecchio ambiente di sviluppo (CTA Yext) |
  | tag-modx | `[[` | tag MODX non eseguiti nell'HTML |
  | pagetitle | `pagetitle]]` | resti di tag rotti (come il vecchio alt «*pagetitle]]» del fondo pagina) |
  | yext | `yextapis.com` | script o widget Yext rimasti (da segnalare fino alla dismissione, poi errore) |
  | yext-campi | `data-yext-field` | campi riempiti da Yext nel browser |
  | http | `href="http://` | link non https |
  | lorem | `Lorem ipsum` | testo segnaposto |
  | css-vecchi (KOS) | `/assets/css/style.css` | fogli singoli rimasti dopo l'unione in `kos.min.css` (KOS 15/25, 22/11): 0 attesi |
  | css-bundle (KOS, «Does Not Contain») | `kos.min.css` | pagine senza il foglio unico: 0 attese |
  | bi-cdn (AA) | `cdn.jsdelivr.net/npm/bootstrap-icons` | icone ancora dal CDN invece della copia locale (AA 12): 0 attese |

- **Configuration › Custom › Extraction** (una riga per estrazione; «Extract Text» salvo dove scritto diverso):

  | Nome | Tipo | Espressione |
  |---|---|---|
  | jsonld_blocchi | XPath, Function Value | `count(//script[@type="application/ld+json"])` |
  | jsonld_tipi | Regex | `"@type":\s*"([^"]+)"` |
  | jsonld_mainEntity | Regex | `"mainEntity":\s*\{\s*"@id":\s*"([^"]+)"` |
  | jsonld_localbusiness | Regex | `"@id":\s*"([^"]+#localbusiness)"` |
  | jsonld_department | Regex | `"@id":\s*"([^"]+#department)"` |
  | jsonld_medico | Regex | `"@id":\s*"([^"]+#(?:medico|person))"` |
  | og_image | XPath, Extract Inner HTML | `//meta[@property="og:image"]/@content` |
  | robots_meta | XPath | `//meta[@name="robots"]/@content` |
  | jsonld_dateModified | Regex | `"dateModified":\s*"([^"]+)"` |
  | jsonld_datePublished | Regex | `"datePublished":\s*"([^"]+)"` |
  | jsonld_brand | Regex | `"brand":\s*\{[^}]*"name":\s*"([^"]+)"` |
  | jsonld_telephone | Regex | `"telephone":\s*"([^"]+)"` |
  | canonical | XPath | `//link[@rel="canonical"]/@href` |

  | briciole_visibili | XPath | `//*[contains(@class,"breadcrumb-item")]` |
  | jsonld_briciole | Regex | `"@type":\s*"ListItem"[^{}]*?"name":\s*"([^"]+)"` |
  | webp_sorgenti | XPath, Function Value | `count(//source[@type="image/webp"])` |
  | img_senza_alt | XPath, Function Value | `count(//img[not(@alt)])` |
  | img_lazy | XPath, Function Value | `count(//img[@loading="lazy"])` |
  | email_offuscate | XPath, Function Value | `count(//*[contains(text(),"Turn on Javascript")])` |
  | jsonld_itemlist | Regex | `"@type":\s*"ItemList"` |

  Per le regex Screaming Frog restituisce tutte le corrispondenze in colonne successive (jsonld_tipi 1, 2, ...).

- **Custom › Search, in più** (opzione «Regex»): `link_relativi` con `href="(?!https?:|/|#|mailto:|tel:|javascript:|(?:[mailto:]|&#x?[0-9a-fA-F]+;){7})`
  (link interni non assoluti, regola del progetto). L'ultima alternativa esclude gli indirizzi email offuscati in
  entità HTML con codifica casuale (a volte i primi caratteri restano in chiaro, es. `m&#97;&#x69;…`), che altrimenti
  risultano su quasi tutte le pagine di kosgroup.com (scansione e revisione del 24/11). Da aggiornare anche nei profili
  `aa-completa` e `kos-completa` salvati sul PC.
- **Spider › Extraction:** spunta anche **HTTP Headers** (per `X-Robots-Tag` e `Link rel="canonical"` dei PDF) e le
  proprietà dei **PDF** (titolo, oggetto, autore: i titoli riscritti nei metadati con lo sprint 2). I nomi esatti delle
  caselle possono variare con la versione di Screaming Frog.

## Differenze per configurazione

- **Completa:** in Spider › Crawl spunta anche Images, CSS, JavaScript, PDF, External Links (gli esterni solo per lo
  stato: «Crawl» degli esterni no). Rendering: Text Only.
- **Leggera:** togli Images, Media, CSS, JavaScript, SWF, PDF, External Links. Rendering: Text Only.
- **JavaScript:** Mode › List con l'elenco delle pagine campione (`scripts/collaudo/` o i campioni della verifica
  generale); Spider › Rendering **JavaScript**, «Store HTML» e «Store Rendered HTML»; poi View Source mostra il confronto
  tra HTML del server e HTML dopo JavaScript. Controllo: nessun JSON-LD, canonical o contenuto aggiunto solo da
  JavaScript.

## Controlli in modalità elenco (15/11, una volta)

Due scansioni brevi in Mode › List, con le impostazioni comuni e la velocità di 0,5 URL/s:

- `docs/revisioni/screaming-frog/controlli-lista.txt`: robots.txt, llms.txt, favicon e apple-touch-icon, sitemap, una
  pagina inesistente per sito (deve dare 404, mai 200), gli URL annidati `/it/.../it` di kosgroup.com (404), le forme con
  www e http (301 verso la forma canonica). Esporta Internal › All: `lista.csv`.
- `docs/revisioni/screaming-frog/campione-js.txt` (28 pagine dei due siti, una o due per tipo): è il campione della
  configurazione JavaScript. Facoltativo nella stessa scansione: Configuration › API Access › PageSpeed Insights con una
  chiave API di Google, strategia mobile, per LCP e CLS di laboratorio e dati CrUX per pagina (voci KOS 2 e AA 4).

## Cosa la scansione non copre

Rich Results Test (solo a campione, a mano), misure dei visitatori reali (CLS e LCP da GA4 su BigQuery e CrUX),
accesso dei bot AI con i loro user agent (`scripts/collaudo/verifica-sprint-1.mjs`), contenuto delle schede contro i
dati di partenza (collaudi `collaudo-kos-11.mjs`, `collaudo-kos-24.mjs`).

## Copertura delle voci degli sprint 1-3

| Voci | Come si verificano nella scansione |
|---|---|
| Accesso dei crawler AI, robots.txt (KOS 6, 19; AA 19) | lista: robots.txt; user agent con lo script di verifica |
| 404, URL annidati, redirect www/http (sprint 1) | lista; Response Codes della completa |
| Canonical (AA 7, paginazione) | Canonicals, estrazione `canonical`, pagine `?page=N` |
| Ente, briciole (KOS 9, 12-13; AA 8) | Structured Data, `jsonld_tipi`, `briciole_visibili` contro `jsonld_briciole` |
| Tempo di risposta e immagini (KOS 2; AA 17) | Response Time, Images over 200 KB, `webp_sorgenti`, `img_lazy`; PSI sul campione |
| PDF (KOS 8; AA 3) | PDF, HTTP Headers (`X-Robots-Tag`, `Link`), proprietà dei PDF, `sitemap-pdf-noindex.xml` |
| Title, description, H1, H2 (KOS 21-23; AA 6, 20) | Page Titles, Meta Description, H1, H2 (duplicati) |
| Immagini di sfondo e alt (AA 1, 9; KOS 7) | Images › Missing Alt Text, `img_senza_alt` |
| Dato strutturato delle strutture e delle residenze (KOS 24; AA 2, 5, 11) | Structured Data, `jsonld_localbusiness`, `jsonld_brand`, `jsonld_telephone`, `jsonld_dateModified`; ricerca `yextapis.com` |
| Pagine provincia (AA 10) | `jsonld_itemlist` sulle 14 pagine |
| Scheda contatti (AA 21) | `email_offuscate` sulle schede (atteso: solo l'email del direttore) |
| Professionisti, Villa Margherita (KOS 11) | `jsonld_medico`, `jsonld_department`, `jsonld_mainEntity` |
| Link assoluti, parametri, resti di tag (regole del progetto) | ricerche `link_relativi`, `utm`, `gclid`, `tag-modx` |
| CLS (AA 4) | PSI sul campione; misura reale da GA4 |

## Pianificazione

File › Scheduling: una pianificazione per sito e cadenza (domenica, kosgroup.com e poi anniazzurri.it a distanza di
qualche ora), con la configurazione salvata, «Run in headless mode», salvataggio della scansione ed esportazioni
automatiche (elenco sotto) in `C:\Users\danil\progetti\kos-seo-geo\screaming-frog\<sito>\<AAAAMMGG>-<cadenza>\` (accanto al repository, fuori da git). Il computer deve
essere acceso e collegato.

## Cosa esportare (nella cartella della scansione)

1. Internal › HTML (tutte le colonne): `internal.csv`
2. Bulk Export › Structured Data › Validation Errors & Warnings: `dati-strutturati.csv`
3. Custom Extraction › tutte: `estrazione.csv`
4. Custom Search › tutte (Bulk Export › Custom Search con gli inlinks): `ricerche.csv`
5. Bulk Export › Response Codes › Internal › Client Error (4xx) Inlinks e Redirection (3xx) Inlinks: `errori.csv`,
   `redirect.csv`
6. Canonicals › tutte: `canonical.csv`
7. Hreflang › tutte (solo kosgroup.com): `hreflang.csv`
8. Sitemaps › Orphan URLs e Non-Indexable URLs in Sitemap: `sitemap.csv`
9. Directives › tutte: `direttive.csv`
10. Solo nella completa: Images › Missing Alt Text, Over 200 KB (con gli inlinks): `immagini.csv`; PDF › tutte:
    `pdf.csv`; External › Client Error (4xx) con gli inlinks: `esterni-rotti.csv`
11. Dalla seconda scansione: Mode › Compare con la precedente, export di Added, Removed, Changed: `confronto.csv`

## Cosa controlliamo poi (Claude, sui CSV)

- un solo blocco JSON-LD per pagina HTML (`jsonld_blocchi` = 1); errori di Schema.org e di Google per tipo di pagina
  (i soli avvisi facoltativi attesi sono nelle note di consegna, es. `priceRange`);
- `@id` del `#localbusiness` uguali all'URL canonico della scheda; `#department` solo sulle unità di Villa Margherita;
  professionisti con `#medico` o `#person`;
- canonical: nessuna pagina indicizzabile con canonical verso un'altra pagina non prevista; nessun canonical con
  parametri;
- hreflang (kosgroup.com): coppie reciproche, nessun rimando a pagine 404 o reindirizzate;
- og:image: nessuna pagina con TV `infoImage` ancora con l'immagine predefinita (correzione del 12/11);
- ogni riga delle ricerche nel codice, con la pagina che la contiene;
- link interni a 404 o a redirect da correggere; pagine in sitemap non indicizzabili; pagine orfane; pagine 200 senza
  contenuto (parole sotto una soglia, come la cartella «strumenti» del 12/11);
- immagini sopra 200 KB e foto senza alt; PDF con noindex o titolo mancante non previsti; link esterni rotti;
- nel confronto: pagine nuove (pubblicate da KOS senza passare dal progetto), sparite, con title, canonical, H1 o
  dato strutturato cambiati.

Esito: una nota breve con le sole anomalie, ciascuna con pagina, causa e correzione proposta. Confronto con i collaudi:
`scripts/collaudo/verifica-sprint-1.mjs`, `collaudo-kos-11.mjs`, `collaudo-kos-24.mjs`.

## Analisi dei risultati con il revisore (Claude Code)

Dopo la scansione completa, in Claude Code dalla cartella del repository (modello diverso da quello che ha scritto le
note, `/model`), con le esportazioni nella cartella della scansione:

```
Leggi docs/verifica-screaming-frog.md (sezione «Cosa controlliamo poi») e docs/revisioni/prompt-revisione-nota.md.
Analizza le esportazioni di Screaming Frog in <cartella> per kosgroup.com e anniazzurri.it e confrontale con le note di
consegna degli sprint 1, 2 e 3 (docs/riferimenti/nota-di-consegna-sprint-1.md, docs/docx/Nota di consegna dello sprint 2.docx,
docs/riferimenti/nota-di-consegna-sprint-3.md, docs/riferimenti/precisazioni-nota-sprint-2.md), usando anche lista.csv e la
tabella «Copertura delle voci degli sprint 1-3»: ogni affermazione verificabile sulle pagine deve risultare vera su tutte
le pagine del tipo, non solo sugli esempi. Controlla in più: date (dateModified, datePublished, lastmod) plausibili e mai
anteriori al 2000; campi del dato strutturato presenti in modo uniforme per tipo di pagina (brand, telefono, orari,
specialità); canonical uguale all'URL; nessuna pagina in sitemap con redirect, 404 o noindex. Non fare richieste ai siti
oltre a quelle necessarie per confermare un'anomalia (al massimo una al secondo). Scrivi il rapporto in
docs/revisioni/screaming-frog-<AAAAMMGG>.md: anomalie in ordine di gravità, con numero di pagine, esempi, causa
probabile e correzione; poi ciò che risulta corretto.
```
