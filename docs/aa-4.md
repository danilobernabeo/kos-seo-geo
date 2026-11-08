# AA 4 · Stabilità visiva (CLS)

Voce dell'audit Anni Azzurri (aprile 2026, slide 57-58, priorità alta): CLS desktop «da migliorare»; nel video
dell'agenzia quasi tutti gli elementi della pagina si spostano pochi secondi dopo il caricamento.

## Misura dell'8/11

Fonti: tag Web Vitals dei visitatori reali (GA4 → BigQuery, `analytics_250584060`, dal 1/11, con l'elemento che si
sposta in `debug_target`) e misura in Chrome sulle pagine di produzione (PerformanceObserver, 1920×1031).

| Pagine | Dispositivo | Visite | CLS p75 | Visite con CLS > 0,1 | di cui per la chat HubSpot |
| --- | --- | --- | --- | --- | --- |
| Home | desktop | 287 | 0,157 | 61,0% | 20,9% |
| Home | mobile | 131 | 0,001 | 13,7% | 1,5% |
| Altre pagine | desktop | 6.065 | 0,077 | 18,7% | 17,5% |
| Altre pagine | mobile | 9.301 | 0 | 2,5% | 1,0% |

Le cause sono due.

1. **Home: caroselli Flickity e barra di ricerca Yext.** Lo script di Flickity è in fondo alla pagina: finché non
   parte, le quattro slide della testata sono impilate (2.835 px invece di 567 a 1920×1031) e all'avvio tutta la pagina
   risale. In più la barra di ricerca Yext Answers viene scritta da JavaScript e allunga la sezione «Siamo qui per
   aiutarti» di 58 px. Spostamento misurato: 0,236 a 575 ms. È lo spostamento del video dell'agenzia.
2. **Tutte le pagine, desktop: finestra della chat HubSpot.** Circa 10 secondi dopo il caricamento la chat si apre
   da sola con il messaggio di benvenuto: il riquadro passa da 100×96 a 448×804 px e conta come spostamento (0,068 a
   1920×1031, di più sugli schermi più bassi). È la causa dell'85% delle visite desktop sopra 0,1 fuori dalla home.

Le schede residenza non hanno spostamenti propri (0,001 su Villa Reale).

## Correzione (sullo stage dall'8/11)

Pacchetto `anniazzurri/deploy/aa-4-cls.json`, solo CSS, in `/dist/css/custom.css` e `/dist/css/kos-seo-geo.css`
(sorgente `anniazzurri/files/css/kos-seo-geo.css`):

- prima che Flickity si avvii le celle dei caroselli stanno affiancate, quindi il carosello occupa già l'altezza
  finale (quella della cella più alta, come Flickity; nelle gallerie con `adaptiveHeight` quella della prima cella);
- 58 px riservati alla barra di ricerca Yext della home, da togliere con la sostituzione della ricerca (linea C).

Verifica prima del rilascio (Chrome, home di produzione con il CSS applicato e JavaScript spento, confrontata con la
pagina dopo l'avvio): posizioni delle sezioni identiche al pixel a 1366×768 e 390×844; senza il CSS la seconda sezione
parte a 2.539 px invece di 724.

## Chat HubSpot (da proporre a KOS)

L'apertura automatica è un'impostazione del chatflow in HubSpot, non del sito. Proposta: sul desktop mostrare il
messaggio di benvenuto come fumetto accanto al pulsante, senza aprire la finestra della conversazione, oppure aprirla
solo dopo un'interazione (scorrimento). Chi gestisce HubSpot per Anni Azzurri decide: cambia il comportamento della chat
visto dai visitatori.

## Collaudo

Dopo il rilascio: CLS dei visitatori reali dal tag Web Vitals (query sopra, per giorno) e misura in Chrome sulla home.
Il dato CrUX dell'origine si aggiorna sui 28 giorni precedenti.
