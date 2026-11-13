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

  Per le regex Screaming Frog restituisce tutte le corrispondenze in colonne successive (jsonld_tipi 1, 2, ...).

## Differenze per configurazione

- **Completa:** in Spider › Crawl spunta anche Images, CSS, JavaScript, PDF, External Links (gli esterni solo per lo
  stato: «Crawl» degli esterni no). Rendering: Text Only.
- **Leggera:** togli Images, Media, CSS, JavaScript, SWF, PDF, External Links. Rendering: Text Only.
- **JavaScript:** Mode › List con l'elenco delle pagine campione (`scripts/collaudo/` o i campioni della verifica
  generale); Spider › Rendering **JavaScript**, «Store HTML» e «Store Rendered HTML»; poi View Source mostra il confronto
  tra HTML del server e HTML dopo JavaScript. Controllo: nessun JSON-LD, canonical o contenuto aggiunto solo da
  JavaScript.

## Pianificazione

File › Scheduling: una pianificazione per sito e cadenza (domenica, kosgroup.com e poi anniazzurri.it a distanza di
qualche ora), con la configurazione salvata, «Run in headless mode», salvataggio della scansione ed esportazioni
automatiche (elenco sotto) in `C:\Users\danil\Documents\screaming-frog\<sito>\<AAAAMMGG>-<cadenza>\`. Il computer deve
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
