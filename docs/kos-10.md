# KOS-10 — Dato strutturato degli articoli (sprint 2, kosgroup.com)

Voce 10 dell'audit KosGroup (settembre 2026): «Mancano i dati strutturati Article sugli articoli del sito». Gli
articoli escono nell'unico `@graph` JSON-LD della pagina, emesso dal server dalla libreria comune
`shared/snippets/seoSchema.php` (snippet `kosSchema`, già richiamato in cache dal chunk `scripts`): nessun template,
chunk o head cambia. Pacchetto `kosgroup/deploy/kos-10-article.json` (snippet e 10 impostazioni di sistema).

## Stato prima dell'intervento (1/11)

- `kos.tpl_article` = `-` (pacchetto KOS-9/12/13): tutte le pagine di kosgroup.com escono come `WebPage`, nessun
  `Article`. Il template `informativo` non stampa più la TV `schemaJsonLd` (pacchetto `kos-ttfb-31-10`), gli head non
  hanno JSON-LD scritto a mano: un solo `@graph` per pagina.
- Nessun autore sugli articoli KOS: non esiste una TV o un campo autore; il box «Articolo scritto da» del template
  `informativo` mostra sempre «Redazione Gruppo KOS» e la data di pubblicazione.
- Dati disponibili: `pagetitle` (H1), `longtitle` (title con « | KOS»), `description`, `introtext`, `publishedon`,
  `editedon`, TV `infoImage` (immagine della testata), TV `idStruttura` (strutture collegate, id separati da virgola,
  la stessa che popola i contenuti della scheda struttura), TV `embedCode` e `videoData` sul template `video`.

## Cosa esce

| Pagine | Nodi |
|---|---|
| Template `informativo` sotto News (cartella 30) | `NewsArticle`, `WebPage`, `BreadcrumbList`, `WebSite`, `Organization` |
| Template `informativo` sotto Approfondimenti (31) ed Eventi e convegni (32) | `Article`, gli stessi nodi |
| Template `informativo` fuori da quelle cartelle | `WebPage` come oggi |
| Template `video` con codice YouTube valido | `WebPage` con `mainEntity` → `VideoObject`, `BreadcrumbList`, `WebSite`, `Organization` |
| Template `progetti-speciali` | `WebPage` come oggi (pagine composte, non articoli) |

Articolo: `@id` `{URL}#article`, `headline` = `pagetitle` (l'H1; il `longtitle` porta il marchio del title),
`description` (o `introtext` senza tag), `image` da `infoImage` con larghezza e altezza lette dal file (assente se il
file non c'è), `datePublished` = `publishedon`, `dateModified` = `editedon` (mai prima della pubblicazione),
`articleSection` = nome della cartella, `author` e `publisher` = `https://kosgroup.com/#organization` con nome e URL,
`mainEntityOfPage` e `isPartOf` → `{URL}#webpage`, che è `isPartOf` → `#website`; `mentions` → `#localbusiness` delle
strutture in `idStruttura`, solo se il nodo della struttura esiste (voce 24 accesa, scheda con indirizzo).

Video: `@id` `{URL}#video`, `name`, `description`, `thumbnailUrl` (`infoImage` se esiste, poi la miniatura YouTube),
`uploadDate` da `videoData` (data scelta dalla redazione, fuso di Roma) o `publishedon`, `embedUrl`, `publisher`,
`mentions` come sopra.

## Scelte motivate

- **Autore = ente** finché KOS non firma gli articoli: la pagina mostra «Redazione Gruppo KOS», e `author` deve
  coincidere con l'autore visibile (Google, linee guida del dato strutturato Article). Niente persona inventata per la
  redazione. La libreria è pronta per le firme: con una TV `aut.ref` (id della pagina autore) l'autore diventa la
  `Person` `#person`, o l'`IndividualPhysician` `#medico` se la pagina è la scheda di un medico (template `medici`), con
  `practicesAt` verso la struttura della TV `idSedeLavoro`.
- **`NewsArticle` per le news**, `Article` per approfondimenti ed eventi: Google accetta `Article`, `NewsArticle` e
  `BlogPosting` per lo stesso risultato; il tipo più specifico solo dove il contenuto è una notizia.
- **`WebPage`, non `MedicalWebPage`**, sugli approfondimenti: senza un revisore clinico reale non si dichiara
  `lastReviewed`/`reviewedBy` (strategia AEO/GEO, piano dello sprint).
- **`VideoObject` sulle pagine video**: la pagina è il video incorporato in testa, senza testo d'articolo né autore
  visibile; per Google il dato del video richiede `name`, `thumbnailUrl` e `uploadDate`.
- **`mentions`, non `about`**, verso le strutture: un articolo collegato a una struttura ne parla, ma il tema è la
  patologia, la tecnologia o l'evento.

## Installazione (stage, poi produzione)

Prova (nessuna scrittura) e applicazione con lo schema di rilascio del `CLAUDE.md`; nessuna dipendenza da altri
pacchetti. Verifica sul cloud, in sola lettura: `php scripts/checks/kos-articoli.php /www [id]` (pagine per sezione,
immagini mancanti, strutture collegate, JSON-LD nel contenuto, `@graph` di una risorsa). Prova locale senza MODX:
`php scripts/test/seo-schema-simulazione.php` (con `--vocabolario=` il controllo di tipi e proprietà su schema.org).

## Collaudo

- Un solo blocco JSON-LD su un articolo per sezione (es. 427 approfondimento, 494 news, 105 evento), un video (792),
  un progetto speciale (887) e una pagina non articolo (home, una struttura): grafi invariati sulle pagine non articolo.
- Rich Results Test (scheda «Codice», lo stage è protetto da password) e validator.schema.org sull'HTML servito:
  articolo valido senza avvisi su `author`, `image`, date; video valido.
- Data visibile nel box dell'articolo = giorno di `datePublished`.

## Rilasci

| Data | Ambiente | Manifest di ripristino |
|---|---|---|
| | stage | |
| | produzione | |
