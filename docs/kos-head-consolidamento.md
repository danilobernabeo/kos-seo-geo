# kosgroup.com — consolidamento degli head (analisi, 24 ottobre 2026)

Analisi in sola lettura della baseline del 22/10. Il consolidamento si sviluppa e si collauda sullo stage; si rilascia
insieme alla voce 24 (JSON-LD delle schede e delle landing) e alla rimozione dello script Yext delle schede.

## Stato attuale

**21 template**, di cui 16 usano un chunk di head e 4 hanno l'head scritto dentro il template (`AccessibilityDashboard`,
`BaseTemplate`, `KOS_2025_Gallery`, `report-recruiting`); `comunicati` è vuoto.

| Chunk | Template | Particolarità |
|---|---|---|
| `head` | cure, convenzioni, generica (tranne 30-34) | riferimento; barra di ricerca Yext Answers sulle pagine 1, 3, 4, 983 |
| `head-paginazione` | news-media, generica (30-34) | niente Canonical (elenchi paginati), og:image fisso |
| `head-informativo` | informativo, progetti-speciali, tecnologie | `og:type` article, data di pubblicazione |
| `head-strutture` | strutture, strutture-VDP | script Yext Knowledge Tags (`idYext`), `ogImage`, CSS di lite-youtube su 190 e 227 |
| `head-medici` | medici | `og:description` dal ruolo; niente hreflang |
| `head-specializzazioni` | specializzazioni | come medici |
| `head-video` | video | `og:type` video.other; niente hreflang |
| `head-landing` | landing | icone Bootstrap da CDN; richiama `head-<id>` (JSON-LD scritto a mano) |
| `head-1065`, `-1108`, `-1114`, `-1118`, `-1133` | (da `head-landing`) | JSON-LD `MedicalClinic` scritti a mano, uno per landing |
| `head-en` | generica-en | hreflang dalla TV `translationId`, `og:locale` en_GB |
| `head-formazione` | form-iscrizione | applicazione di registrazione con CSS propri (`regset/vendor/...`) |
| `head-strutture-test` | strutture-test | **script di test del tracciamento con `debug: true` e `console.log`** |

Le parti comuni (charset, viewport, title, description, GTM, 10 fogli di stile, icone, og) sono ripetute in 11 chunk
con piccole varianti nate nel tempo, non per scelta.

## Problemi

**Tempo di risposta (KOS-2, parte applicativa).**
- `[[!Canonical]]` e `[[!++site_url]]` fuori cache in quasi tutti gli head: si eseguono a ogni visita anche con la pagina
  in cache. Il Canonical dipende solo dalla risorsa e si può mettere in cache; `site_url` è un'impostazione fissa.
- La barra di ricerca Yext sulle pagine 1, 3, 4, 983 contiene altri due `[[!++site_url]]`.

**SEO e internazionalizzazione.**
- **hreflang errato su tutte le pagine italiane**: dichiarano come traduzione inglese la risorsa 990 (la home inglese),
  qualunque sia la pagina; x-default sempre sulla home. Le pagine inglesi con `translationId` vuota producono un hreflang
  `it` con href vuoto. Medici, specializzazioni e video non hanno hreflang.
- `noindex` gestito dalla TV `noindex` solo in alcuni head (`head`, medici, specializzazioni, video): negli altri la TV
  non ha effetto.
- Description senza `:htmlent` in 4 head (virgolette nel testo rompono l'attributo).
- og:image con controllo di esistenza del file solo sulle strutture (`ogImage`); altrove `infoImage` senza controllo.
  Nessun head dichiara larghezza e altezza dell'immagine.

**Prestazioni del browser e cache.**
- `custom.css` con versioni scritte a mano diverse tra gli head (`?v=20260921b`, `20260915`, `20260628b`): il plugin
  `AssetVersion` (KOS-3) salta i link che hanno già un parametro, quindi chi ha la versione vecchia in cache (fino a
  180 giorni) non riceve le modifiche di `custom.css` sulle pagine con il numero fisso.
- `scroll-anchors.js` con `defer` in un head e senza negli altri (blocca il rendering).
- 10 fogli di stile separati in ogni pagina (minificazione e unione sono voci a parte dell'audit: KOS-47/48).

**Dato strutturato.**
- 5 JSON-LD scritti a mano nelle landing (`head-<id>`) e lo script Yext Knowledge Tags nelle schede: da sostituire con
  la libreria comune (`kosSchema`, voce 24), che oggi emette ente, sito e briciole.

**Da segnalare (fuori dagli head ma emerso qui).**
- **Barra di ricerca Yext Answers** (pagine 1, 3, 4, 983, risultati in `/it/risultati`): dipende da Yext; con la
  dismissione di Yext va sostituita (ricerca del sito o widget RetailTune). Da mettere nel piano della linea C.
- **`strutture-test`**: se un template di test è assegnato a pagine pubblicate, il tracciamento di prova scrive nella
  console del browser e modifica il `dataLayer` di GA4. Da verificare quali risorse lo usano.

## Proposta

Un solo chunk `head` con **parametri** per le poche differenze reali, chiamato da tutti i template del sito pubblico:

```
[[$head? &ogType=`article` &descrizioneDi=`introtext` &hreflang=`1`]]
```

Contenuto, in quest'ordine:
1. charset, viewport, title, description (con `:htmlent` e ripiego su `introtext` dove indicato), robots dalla TV
   `noindex` per tutti i template;
2. GTM;
3. canonical **in cache** (`[[Canonical]]`), tranne le pagine elenco paginate, che hanno il loro canonical (snippet
   `pdopagecanonical` o plugin già pronto per AA);
4. hreflang corretti: `it` sulla pagina stessa; `en` solo se esiste la traduzione (`translationId`); x-default sulla
   versione italiana; stessa logica, rovesciata, per `head-en`;
5. `base` con `[[++site_url]]` in cache;
6. fogli di stile **senza versione scritta a mano** (la mette `AssetVersion` dalla data del file), script con `defer`;
7. icone e manifest;
8. og e twitter con lo snippet comune `ogImage` (`&meta=`1``: dimensioni dal file; ripiego `kos-condivisione-social.jpg`),
   `og:type` e descrizione da parametro, `article:published_time` dove `ogType` è article;
9. punti di aggancio per gli extra di pagina **in cache**: CSS di lite-youtube (190, 227), icone Bootstrap delle landing.

Escono dagli head: lo script Yext Knowledge Tags (con la voce 24), i 5 JSON-LD delle landing (sostituiti da `kosSchema`,
che emette `#localbusiness` con i dati della scheda), la barra Yext Answers (quando c'è il sostituto).

Restano separati, perché sono applicazioni diverse: `head-formazione` (modulo di iscrizione con i suoi CSS; si
correggono solo canonical in cache e hreflang) e i 4 template con head interno (prima si verifica se hanno pagine
pubblicate).

## Collaudo

- confronto stage/produzione con `scripts/collaudo/` (pagine tipo di ogni template, 4 larghezze);
- controllo automatico degli head: un title, una description, un canonical assoluto `https://kosgroup.com/it/...`,
  hreflang coerenti e reciproci, robots secondo la TV, og completi, nessuna chiamata fuori cache residua;
- validatore schema.org e Rich Results sulle pagine con `#localbusiness`;
- misura del tempo di risposta a cache calda prima e dopo (per KOS-2).

## Domande aperte

1. hreflang: le pagine inglesi oltre alla home sono mantenute? La regola proposta dichiara la traduzione solo dove
   esiste; da confermare con l'agenzia SEO, come indicato nel passaggio di consegne.
2. Template `strutture-test`, `AccessibilityDashboard`, `BaseTemplate`, `KOS_2025_Gallery`, `report-recruiting`: quali
   risorse pubblicate li usano?
3. Barra di ricerca Yext Answers: con cosa la sostituiamo e quando (linea C)?
