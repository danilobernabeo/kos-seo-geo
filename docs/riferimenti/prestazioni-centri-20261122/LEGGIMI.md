# Schede dei centri kosgroup – tempo di generazione (chat prestazioni, 22/11/2026)

Base: `kosgroup/baseline/snippets/barraPrenotazioneDBmedicoPrestazione.php` del repository (lo snippet non è in
`kosgroup/elements/`: nessuna modifica del progetto dopo il 31/10). Il template `strutture` (id 2) e `strutture-VDP`
(id 4) NON cambiano: hanno già tutte le chiamate in cache.

## Perché
Web Vitals dei visitatori reali (31/10–21/11): le schede `/it/centri/...` sono circa un terzo delle visite di kosgroup e
hanno tempo server mediano 750–1.000 ms, contro 180–260 ms del resto del sito. È lenta quasi ogni visita: il costo viene
da chiamate `[[!...]]` nel contenuto delle risorse, rieseguite a ogni visita (query sul DB di produzione del 22/11):

| Chiamata non in cache | Dove | Schede | Intervento |
|---|---|---|---|
| `Wayfinder` | TV `modale` / `modale2` / `modale3` | 45 / 2 / 1 | in cache – SQL sui contenuti (fuori git) |
| `galleryStruttura` (glob + getimagesize di tutte le foto) | contenuto | 29 | **nessuno**: galleria in carico alla chat principale (suggerito: chiamata in cache) |
| `barraPrenotazioneDBmedicoPrestazione` (5 query su `kos_*`, JSON 7–77 KB) | contenuto | 19 | **questo file**: cache interna di 1 ora |
| `getMediciStrutturaOrdered` (tutti i medici del parent 280 + TV di ciascuno) | contenuto | 5 | in cache – SQL |
| `Wayfinder`, `pdoMenu`, `getResources` | contenuto | 3 / 2 / 2 | in cache – SQL |
| `getMedici`, `myBreadcrumb` | contenuto di /it/chi-siamo/qualita-delle-cure/medici | 1 | in cache – SQL |
| `mostraFino` | contenuto | 1 | nessuno (dipende dalla data) |

## Cosa cambia in `barraPrenotazioneDBmedicoPrestazione` (id 167)
- In testa: se l'HTML della barra di quella scheda è nella cache di MODX (chiave `kos_barra/<id>-<hash>`, partizione
  `default`), lo restituisce senza query. In fondo: dopo averlo generato lo salva per `kosseogeo.barra_cache_ttl`
  secondi (impostazione facoltativa, predefinito 3600; `0` = comportamento di prima).
- Si salvano solo le barre riuscite, non i messaggi "Struttura non configurata", "Nessuna prestazione disponibile",
  "Errore database".
- "Svuota cache" del manager le rigenera subito (partizione default).
- La chiamata nelle schede resta `[[!barraPrenotazioneDBmedicoPrestazione]]`: dopo un aggiornamento delle tabelle
  `kos_*` le prestazioni e i medici nuovi compaiono al più entro un'ora (o subito, svuotando la cache).
- Nient'altro cambia: HTML, JavaScript, CSS, tracciamento `kos_widget_cerca` identici al byte.

## Cosa NON cambia (condizioni della chat principale)
- JSON-LD delle schede (libreria comune, #localbusiness, professionisti): nessun elemento che lo produce è toccato;
  le chiamate messe in cache producono lo stesso HTML.
- Galleria in testa alla scheda: non toccata (né `galleryStruttura` né la sua chiamata).
- Template `strutture`, `head-strutture`, `head-extra-strutture`: non toccati.

## Misura
`monitor\misura-centri.ps1` (5 misure per pagina, mediana) prima e dopo, sullo stesso PC e linea.
Atteso dopo SQL + snippet: schede con barra e lista medici (villa-dei-pini-civitanova, myolab, villa-margherita-vicenza-
riabilitazione, villa-rosa-diagnostica, centro-medico-riabilitativo) da ~1,0–1,4 s a circa il livello di
/it/ritiro-referti (+ il costo residuo della galleria non in cache sulle 29 schede che la hanno).
Nei Web Vitals (BigQuery, una settimana dopo): tempo server p75 della sezione centri da 764 ms verso 300–400 ms.

## Rilascio
Entro giovedì 26/11 oppure dopo il 4/12 (verifica sprint 3 con KOS 11 dal 30/11 al 4/12).
Ordine consigliato: 1) misura prima; 2) SQL `monitor\centri-cache-20261122.sql` in produzione (contenuti, con backup);
3) rilascio di questo snippet con il flusso git; 4) svuota cache; 5) misura dopo; 6) collaudi KOS 24 e KOS 11 della
chat principale sul JSON-LD.
