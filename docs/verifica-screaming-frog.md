# Verifica indipendente con Screaming Frog SEO Spider

Uso interno. Scansione di controllo dei due siti con uno strumento esterno, da incrociare con i collaudi del repository.
Una scansione per sito, dal computer di Danilo. kosgroup.com (circa 870 URL in sitemap) richiede la licenza;
anniazzurri.it sta nei 500 URL della versione gratuita.

## Quando

Dal 15/11, dopo la verifica di Cerved/ProWeb. Il 1/11 le richieste automatiche dei collaudi hanno fatto scattare la
protezione anti-bot di MODX Cloud sull'IP di Danilo («Security Check», 403): una scansione veloce durante la verifica
potrebbe bloccare anche le loro richieste. Prima di partire: home del sito aperta nel browser dallo stesso computer
(nessun 403).

## Impostazioni

Un sito per volta. Le voci di menu sono quelle della versione in inglese (Configuration).

### Velocità (Configuration › Speed)

- Max Threads: **1**
- Limit URL/s: **1,5** (circa 10 minuti per anniazzurri.it e 10-15 per kosgroup.com)

### Spider › Crawl

- Spunta: Internal, Canonicals, Pagination (rel next/prev), Hreflang, Meta Refresh, Follow Internal «nofollow».
- Togli: Images, Media, CSS, JavaScript, SWF, External links, PDF (non servono per questa verifica e alleggeriscono).
- Crawl Linked XML Sitemaps: **sì**, con «Auto Discover XML Sitemaps via robots.txt»; così la scansione trova anche le
  pagine non collegate dal menu e segnala quelle in sitemap ma orfane.

### Spider › Rendering

- **Text Only** (il JSON-LD è nel codice sorgente, emesso lato server: non serve eseguire JavaScript).

### Spider › Extraction › Structured Data

- Spunta **JSON-LD**, **Schema.org Validation** e **Google Rich Result Feature Validation**.
- Togli Microdata e RDFa (i siti non li usano: se ne compaiono, vanno segnalati).

### Spider › Advanced

- Respect Noindex, Respect Canonical: **no** (vogliamo vedere tutte le pagine e confrontare il canonical, non seguirlo).
- Always Follow Redirects: sì. Respect robots.txt: sì.

### Exclude (Configuration › Exclude), una regola per riga

kosgroup.com:

```
.*\.pdf$
.*/assets/.*
.*\?.*
https://kosgroup.com/it/news-e-media/comunicati-stampa/.+
https://kosgroup.com/it/risultati.*
```

anniazzurri.it:

```
.*\.pdf$
.*/assets/.*
.*\?.*
```

(i comunicati di kosgroup.com rimandano con 301 al loro PDF; la pagina dei risultati della ricerca Yext non va
scansionata; gli indirizzi con parametri sono esclusi tranne che per il controllo del canonical, fatto a parte).

### Custom › Extraction

Una riga per estrazione, tipo come indicato, «Extract Text» salvo dove scritto diverso.

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

Per le regex: in «Regex» Screaming Frog restituisce tutte le corrispondenze in colonne successive
(jsonld_tipi 1, 2, ...).

## Cosa esportare (nella cartella Download, con il nome del sito)

1. Internal › HTML (tutte le colonne): `<sito>-internal.csv`
2. Structured Data › Validation Errors e Validation Warnings: `<sito>-dati-strutturati.csv` (Bulk Export ›
   Structured Data › Validation Errors & Warnings)
3. Custom Extraction › tutte: `<sito>-estrazione.csv`
4. Response Codes › Client Error (4xx) e Redirection (3xx) con gli inlinks: Bulk Export › Response Codes ›
   Internal › Client Error (4xx) Inlinks e Redirection (3xx) Inlinks: `<sito>-errori.csv`, `<sito>-redirect.csv`
5. Canonicals › tutte: `<sito>-canonical.csv`
6. Hreflang › tutte (solo kosgroup.com): `kosgroup-hreflang.csv`
7. Sitemaps › Orphan URLs e Non-Indexable URLs in Sitemap: `<sito>-sitemap.csv`

## Cosa controlliamo poi (Claude, sui CSV)

- un solo blocco JSON-LD per pagina HTML (`jsonld_blocchi` = 1);
- errori di Schema.org e di Google per tipo di pagina; i soli avvisi facoltativi attesi sono documentati nelle note
  di consegna (es. `priceRange`);
- `@id` del `#localbusiness` uguali all'URL canonico della scheda; `#department` solo sulle unità di Villa Margherita;
  professionisti con `#medico` o `#person`;
- canonical: nessuna pagina indicizzabile con canonical verso un'altra pagina non prevista; nessun canonical con
  parametri;
- hreflang (kosgroup.com): coppie reciproche, nessun rimando a pagine 404 o reindirizzate;
- og:image: nessuna pagina con TV `infoImage` ancora con l'immagine predefinita (correzione del 12/11);
- link interni a 404 o a redirect da correggere; pagine in sitemap non indicizzabili; pagine orfane.

Confronto con i collaudi: `scripts/collaudo/verifica-sprint-1.mjs`, `collaudo-kos-11.mjs`, `collaudo-kos-24.mjs`.
