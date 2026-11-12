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

## Correzione (in produzione dall'8/11 sera)

Pacchetto `anniazzurri/deploy/aa-4-cls.json`, solo CSS, in `/dist/css/custom.css` e `/dist/css/kos-seo-geo.css`
(sorgente `anniazzurri/files/css/kos-seo-geo.css`):

- prima che Flickity si avvii le celle dei caroselli stanno affiancate, quindi il carosello occupa già l'altezza
  finale (quella della cella più alta, come Flickity; nelle gallerie con `adaptiveHeight` quella della prima cella);
- 58 px riservati alla barra di ricerca Yext della home, da togliere con la sostituzione della ricerca (linea C).

Verifica prima del rilascio (Chrome, home di produzione con il CSS applicato e JavaScript spento, confrontata con la
pagina dopo l'avvio): posizioni delle sezioni identiche al pixel a 1366×768 e 390×844; senza il CSS la seconda sezione
parte a 2.539 px invece di 724.

## Carosello della home: celle di altezza diversa (12/11)

Flickity dà al riquadro l'altezza della cella più alta misurata all'avvio, ma ogni slide è alta quanto il suo testo:
le slide con meno testo restavano più basse e sotto si vedeva una striscia vuota. Misura in produzione il 12/11:
telefono 375×812 celle da 365 a 397 px in un riquadro da 376 (misurato prima del caricamento del font); 1366×768 celle
da 422 a 512 px in un riquadro da 512. Correzione in `kos-seo-geo.css` (pacchetto `aa-4-cls`): ogni cella occupa almeno
tutto il riquadro (`min-height: 100%`, flex) e l'immagine la riempie; il testo resta in alto (`align-items: flex-start`),
alla stessa altezza di prima: centrato nella cella allungata cambiava posizione da una slide all'altra (prima prova sullo
stage, 12/11). Nel chunk le immagini delle slide successive alla prima hanno `&differita=`0``: con il caricamento
differito la seconda slide compariva senza immagine. La regola sta anche in `custom.css`, l'unico CSS caricato dalla home.
Occhiello e titolo alla stessa altezza in tutte le slide (blocco di testo in alto, sul desktop a `max(81px, 14vh)`):
centrato, si spostava con la lunghezza del titolo (1920: occhiello da 136 a 163 px; dopo, 144 in tutte). Provata nel browser sulla produzione: tutte le
celle 397 px sul telefono e 512 a 1366. Nessun effetto sul CLS: l'altezza del riquadro non cambia.

## Chat HubSpot: misura dell'11/11

Prova nel browser sulla produzione (home e Villa Reale), più dati dei visitatori reali dall'1 al 9/11 (BigQuery).

Comportamento attuale (chatflow «mostra il benvenuto, poi apri la chat al trigger»):
- dopo 1-2,5 s compare il fumetto di benvenuto (da 100×96 a 282×253 px);
- dopo 8-9,5 s si apre la finestra intera, 448 px di larghezza e fino a 804 di altezza;
- si riapre a ogni pagina finché il visitatore non la chiude. Chiusa, resta chiusa per 24 ore (cookie
  `hs-messages-hide-welcome-message`).

| Schermo | CLS dalla chat | Altezza coperta | Area coperta |
| --- | --- | --- | --- |
| 1920×1080 (calcolo) | – | 74% | 17% |
| 1366×768 | 0,123 (fumetto 0,009, finestra 0,114) | 96% | 31% |
| 743×1027 (finestra stretta) | 0,276 | 78% | 47% |

A 1366×768 la finestra copre le voci di menu «SOS Famiglia», «Tariffe e convenzioni» e «Chi siamo», oltre alla
colonna destra del contenuto. Senza la chat il CLS è 0,004 sulla home e 0 sulla scheda.

Impostazioni della chatflow «KOS_Welcome Chatbot» (verificate da Danilo in HubSpot l'11/11), con un trigger comune a
tutti i dispositivi, «tempo sulla pagina» di 7 s:
- desktop e tablet: «Mostra il messaggio di benvenuto, quindi apri il widget della chat al momento dell'attivazione»;
- mobile: «Mostra solo l'utilità di avvio della chat quando si verifica la condizione di attivazione», cioè solo il
  pulsante, senza fumetto.

Volumi (pannello della chatflow):
- luglio-settembre 2026: 859 conversazioni avviate, 496 completate (58%);
- ottobre: 191 avviate, 111 completate (58%).

Mobile: HubSpot prevede solo il fumetto o il solo pulsante, nessuna apertura automatica (guida della chatflow). La prova da mobile non è conclusiva, perché la chiusura del test desktop resta valida 24 ore. Lo confermano
però i dati reali: la chat è l'elemento che si sposta nell'1% delle pagine viste da mobile, contro il 61-65% da
desktop.

Dati reali da desktop (pagine diverse dalla home), 9/11:
- p75 0,076;
- 17,3% delle visite sopra 0,1, di cui 16,1 punti per la chat;
- spostamento mediano dovuto alla chat 0,06.

Home da desktop, 9/11 (55 visite):
- p75 0,096;
- 23,6% delle visite sopra 0,1, di cui 21,8 punti per la chat.

## Chat HubSpot (da proporre a KOS)

L'apertura automatica è un'impostazione del chatflow in HubSpot, non del sito. Proposta: sul desktop mostrare il
messaggio di benvenuto come fumetto accanto al pulsante, senza aprire la finestra della conversazione, oppure aprirla
solo dopo un'interazione (scorrimento). Chi gestisce HubSpot per Anni Azzurri decide: cambia il comportamento della chat
visto dai visitatori.

## Collaudo

Dopo il rilascio: CLS dei visitatori reali dal tag Web Vitals (query sopra, per giorno) e misura in Chrome sulla home.
Il dato CrUX dell'origine si aggiorna sui 28 giorni precedenti.

## Test A/B dell'apertura automatica (12/11-13/12)

Via libera del referente l'11/11. Strumento: tag `scripts/collaudo/monitor/gtm-chat-hubspot/` (GA4, BigQuery, Clarity).

| Fase | Periodo | Impostazione da computer (HubSpot, stato predefinito Desktop) |
|---|---|---|
| A | dalla pubblicazione del tag (12/11) al 20/11 | attuale: «Mostra il messaggio di benvenuto, quindi apri il widget della chat al momento dell'attivazione» (7 s) |
| B | 21/11-6/12 | «Mostra l'utilità di avvio della chat al caricamento della pagina, quindi mostra il messaggio di benvenuto quando si verifica la condizione di attivazione» |
| A | 7/12-13/12 | di nuovo l'attuale |

A e B hanno 16 giorni ciascuna e la fase B sta in mezzo alle due A: l'andamento del periodo (avvicinamento a dicembre,
ponte del 7-8/12, che cade in A) si divide fra le due impostazioni. Il telefono, che non cambia, fa da controllo.
Cambi: venerdì 20/11 sera o sabato 21/11 mattina (A→B), lunedì 7/12 mattina (B→A); annotare l'ora esatta qui sotto.
Analisi il 14/12.

**Regola di decisione** (fissata prima del test):
- contatti totali da computer (moduli, telefono, email, conversazioni) per 100 sessioni stabili in B rispetto ad A,
  calo entro il 15% al netto della variazione da telefono: B diventa l'impostazione definitiva;
- calo oltre il 15%: si prova l'apertura automatica dopo lo scorrimento del 50% della pagina;
- conversazioni in chat in calo ma contatti totali stabili: nessuna perdita, B resta;
- indicatori del disturbo (chiusure entro 5 s senza uso, ritorni rapidi in Clarity, interazione e letture fino in
  fondo in GA4) a supporto della decisione.

| Evento | Data e ora |
|---|---|
| Pubblicazione del tag (inizio A) | 12/11/2026 07:24, versione 78 del container GTM-THMLNFX |
| Cambio A→B | |
| Cambio B→A | |
