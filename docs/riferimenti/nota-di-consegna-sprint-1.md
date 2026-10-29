# Nota di consegna dello sprint 1

Oct 20, 2026 · @Danilo Bernabeo

## Sintesi

Le dieci voci d'audit dello sprint 1 sono chiuse: sei con interventi in produzione, quattro risultavano già a posto e sono verificate con evidenza. Il testo di sintesi di llms.txt riprende i contenuti istituzionali dei siti; eventuali revisioni si applicano in giornata.

| Sito | Voce d'audit | Intervento | Stato | Data |
| --- | --- | --- | --- | --- |
| kosgroup.com | 3 · Cache delle risorse statiche | Immagini 180 giorni, font 365 giorni, CSS e JS 180 giorni con versione automatica dei file | In produzione | 19-20 ottobre |
| kosgroup.com | 4 · Redirect dei domini storici | Redirect 301 già attivi su tutti e cinque i domini: verificati | Chiusa, già a posto | 19 ottobre |
| kosgroup.com | 6 · Accessibilità bot AI | Test con gli user agent e controllo del log sugli IP ufficiali | Chiusa, già a posto | 20 ottobre |
| kosgroup.com | 18 · llms.txt | File pubblicato nella radice, UTF-8 | In produzione | 19 ottobre |
| kosgroup.com | 19 · robots.txt | Crawl-delay rimosso, regole Disallow corrette | In produzione | 19 ottobre |
| anniazzurri.it | 7 · Canonical duplicato | Un solo canonical lato server: verificato | Chiusa, già a posto | 19 ottobre |
| anniazzurri.it | 8 · Ente e percorsi di navigazione | Un solo `@graph` JSON-LD per pagina: ente KOS Care e briciole complete | In produzione | 20 ottobre |
| anniazzurri.it | 14 · Cache delle risorse statiche | Come kosgroup.com (anticipata dallo sprint 4) | In produzione | 19-20 ottobre |
| anniazzurri.it | 18 · Titoli H1 mancanti | H1 su home e galleria del convegno 2025 | In produzione | 20 ottobre |
| anniazzurri.it | 19 · Accessibilità bot AI | Come kosgroup.com | Chiusa, già a posto | 20 ottobre |

## kosgroup.com

Su kosgroup.com quattro voci sono chiuse e una (6, bot AI) è nella sezione dedicata. Tutte le modifiche sono in produzione e passano dalle regole web di nginx o da file statici: nessun contenuto è stato toccato.

| Voce | Intervento | Come verificare |
| --- | --- | --- |
| 3 · Cache | Regole web: immagini e video 180 giorni, font 365 giorni, CSS e JS 180 giorni. Un plugin aggiunge a ogni CSS e JS locale `?v=` con la data del file, così una modifica raggiunge subito i visitatori anche con la cache lunga. | Intestazione `Cache-Control: max-age=15552000` su `https://kosgroup.com/assets/css/bootstrap.min.css`; nel sorgente di `https://kosgroup.com/it` i CSS e JS terminano con `?v=` e un numero. Le regole valgono per tutte le risorse servite dal sito; PageSpeed può ancora segnalare quelle di terze parti (Google Tag Manager, Cookiebot, mappe, video), la cui cache decide il fornitore. |
| 4 · Domini storici | Già a posto prima dell'avvio. Un solo redirect 301 verso la pagina corrispondente, su https, https://www e http: polodiagnostico.it → `/it/strutture/poliambulatori`, neomesia.com → `/it/strutture/cliniche-psichiatriche`, sstefano.it → `/it/strutture/centri-riabilitazione`, myolab.it → `/it/centri/myolab`, casadicuravillapini.it → `/it/centri/villa-dei-pini-civitanova`. | `curl -sIL https://polodiagnostico.it` (e gli altri): un 301, poi 200. Nell'audit (slide 53) il quinto dominio è scritto «casadicuravillalpini.it»: il dominio storico è casadicuravillapini.it, quello verificato. |
| 6 · Bot AI | Vedi «Accesso dei crawler AI». |  |
| 18 · llms.txt | File nel formato llmstxt.org: sintesi del gruppo e 79 link alle pagine principali (aree di cura, strutture, contatti), servito come `text/plain; charset=utf-8`. La sintesi riprende i contenuti istituzionali del sito. | `https://kosgroup.com/llms.txt` |
| 19 · robots.txt | Tolto il `Crawl-delay` per Bingbot. Le regole `Disallow` sui parametri `query` e `formId` ora iniziano con `/`, come richiedono lo standard (RFC 9309) e Google; nella forma precedente alcuni crawler potevano ignorarle. Un solo gruppo `User-agent: *`, che ammette tutti i crawler AI. | `https://kosgroup.com/robots.txt` |

## anniazzurri.it

Su anniazzurri.it il cambiamento principale è il dato strutturato (voce 8): ogni pagina emette un solo `@graph` dal server, al posto dei tre blocchi scritti a mano nel footer. La voce 7 risultava già risolta.

| Voce | Intervento | Come verificare |
| --- | --- | --- |
| 7 · Canonical duplicato | Già risolta prima dell'avvio. Nell'HTML servito c'è un solo canonical, assoluto, https, senza www, nella forma canonica del sito. Verificato su 8 pagine di tipo diverso. | Sorgente (Ctrl+U) di una residenza: un solo `rel="canonical"`. |
| 8 · Ente e percorsi di navigazione | Organization di KOS Care S.r.l. con `@id`, ragione sociale, partita IVA, logo PNG, telefono, profili social e `parentOrganization` verso KOS S.p.A. (mai `sameAs`). WebSite e WebPage collegati per `@id`. BreadcrumbList su tutte le pagine tranne la home, con tutti i livelli (Home › Regione › Provincia › Residenza), identica alle briciole visibili e all'esempio della slide 35. Collegato anche il dato dello Sportello sociale (Service e FAQ), prima non emesso. | Vico Mercati: `https://anniazzurri.it/residenza-anni-azzurri-vico-mercati` su validator.schema.org (0 errori, 0 avvisi) e Rich Results Test (Breadcrumb e Organizzazione validi). |
| 14 · Cache | Come kosgroup.com: immagini 180 giorni, font 365 giorni, CSS e JS 180 giorni con versione automatica `?v=`. Anticipata dallo sprint 4. | `Cache-Control: max-age=15552000` su `https://anniazzurri.it/dist/css/app.css`. |
| 18 · H1 mancanti | Home: H1 «RSA e case di riposo in Italia», il primo testo proposto dall'agenzia, sotto il carosello. Tolto un H1 nascosto aggiunto in passato, così ne resta uno solo. Galleria del convegno 2025: H1 con il titolo della pagina e un paragrafo introduttivo; aggiunti canonical e dato strutturato, che il suo template non aveva. | `https://anniazzurri.it/` e `https://anniazzurri.it/galleria-fotografica-convegno-anni-azzurri-2025`: un solo `<h1>`. |
| 19 · Bot AI | Vedi «Accesso dei crawler AI». |  |

Dal 29 ottobre anche lo Sportello sociale ha un solo blocco: Service e FAQ sono entrati nel grafo unico. Sulle pagine di cure domiciliari, sull'Albero dei ricordi e sulla galleria del convegno sono comparse le briciole visibili, uguali al `BreadcrumbList`.

## Accesso dei crawler AI (KOS-6, AA-19)

Nessuno dei due siti blocca i crawler AI: non li blocca il robots.txt, non li bloccano le regole del server, e il sistema anti-bot della piattaforma non ha mai fermato i loro IP ufficiali. Le verifiche sono del 20 ottobre 2026.

1. **robots.txt**: un solo gruppo `User-agent: *`, che non esclude nessun bot; nessun `Crawl-delay`.
2. **Risposta agli user agent**: GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-User, Claude-SearchBot, PerplexityBot, Perplexity-User e Bingbot su una pagina di ciascun sito. Tutte le 18 richieste rispondono 200, con la stessa pagina servita a un browser: 70.112 byte su anniazzurri.it, circa 88.300 su kosgroup.com.
3. **Log del server sugli IP ufficiali**: gli elenchi pubblicati da OpenAI, Anthropic e Perplexity, scaricati il giorno stesso, confrontati con l'intero log degli errori di nginx (kosgroup.com da agosto 2024, anniazzurri.it da maggio 2022).
4. **Controllo esterno**: uno strumento pubblico di AI visibility, che interroga il sito dai propri server con 33 user agent di motori, assistenti AI e crawler di addestramento, ha dato 100 su 100 a kosgroup.com (27 ottobre 2026) e ad anniazzurri.it (29 ottobre 2026): 33 crawler ammessi su 33 (11 di ricerca AI, 8 assistenti, 14 di addestramento), stessa pagina del browser, nessun noindex né X-Robots-Tag. Stesso risultato sui due siti.

| Bot (IP ufficiali) | kosgroup.com: righe nel log | anniazzurri.it: righe nel log | Sfide o ban anti-bot, limiti di frequenza |
| --- | --- | --- | --- |
| OpenAI GPTBot | 953 | 3.484 | 0 |
| OpenAI OAI-SearchBot | 2.816 | 1.960 | 0 |
| OpenAI ChatGPT-User | 11.492 | 5.367 | 0 |
| Anthropic (ClaudeBot, Claude-User, Claude-SearchBot) | 179 | 232 | 0 |
| PerplexityBot | 4.466 | 4.320 | 0 |
| Perplexity-User | 365 | 308 | 0 |

Le righe presenti non sono blocchi. Si dividono in tre gruppi:

- messaggi tecnici del server che non interrompono la richiesta (registrazione su syslog non riuscita, cache delle sessioni SSL piena);
- risposte 404 su file che non esistono più, soprattutto PDF e immagini di anniazzurri.it, da correggere nello sprint 2;
- timeout isolati: 30 in tutto, sui due siti e sull'intero periodo.

L'unico accesso negato è una richiesta di Anthropic a `/core/docs/changelog.txt` su kosgroup.com: è un file interno del CMS, chiuso di proposito.

Gli «Errore» su GPTBot e ClaudeBot nel test dell'audit Anni Azzurri, e gli errori di connessione nell'audit KosGroup, non trovano riscontro nei log. L'ipotesi più probabile è il sistema anti-bot della piattaforma, che sfida chi usa lo user agent di un bot da un IP non ufficiale. Il 17 ottobre ha bannato un IP che scansionava pagine reali di anniazzurri.it. Per chiudere il punto chiediamo all'agenzia lo strumento e l'IP usati per il test, così da ripeterlo o escludere quell'IP.

Da segnalare all'hosting, insieme alle richieste già inviate il 19 ottobre: rotazione del log degli errori (4,8 GB su kosgroup.com e 2,7 GB su anniazzurri.it), attivazione del log degli accessi, syslog non raggiungibile e cache delle sessioni SSL.

## Interventi in più rispetto all'audit

Durante lo sprint sono emersi cinque interventi fuori dalle voci d'audit, fatti senza costi aggiuntivi perché piccoli o necessari alle voci in corso.

| Sito | Intervento | Perché | Come verificare |
| --- | --- | --- | --- |
| kosgroup.com | Pagina «Pagina non trovata» come pagina d'errore | Una pagina inesistente rispondeva 301 verso `/it` e poi 200 (soft 404): ora risponde 404 direttamente | `curl -sI https://kosgroup.com/it/pagina-che-non-esiste` → 404 |
| Entrambi | `favicon.ico` e `apple-touch-icon` nella radice | Circa 56.000 richieste 404 su anniazzurri.it nelle ultime 200.000 righe del log | `https://anniazzurri.it/favicon.ico` e `/apple-touch-icon.png` → 200 |
| anniazzurri.it | robots.txt allineato a kosgroup.com e llms.txt con le residenze | Stesse regole sui risultati di ricerca interna; llms.txt nel perimetro del progetto anche se non è voce dell'audit AA | `https://anniazzurri.it/robots.txt`, `https://anniazzurri.it/llms.txt` |
| anniazzurri.it | Redirect 301 (prima 302 e 404) delle residenze chiuse La Margherita e Villalba verso la pagina della regione | URL ancora presenti su Yext e nei motori di ricerca | `curl -sI` sui due URL → 301 |
| Entrambi | [Convenzione degli identificativi del dato strutturato](https://claude.ai/code/artifact/b0618026-233e-4335-a5c0-78373cc84c0d) | Stessi `@id` su tutte le pagine e su entrambi i siti, base per gli sprint successivi | Scheda condivisa |

## Come verificare

Tutte le verifiche si fanno sugli URL pubblici, senza accessi al CMS. Il dato strutturato va controllato sull'HTML servito dal server, cioè quello che leggono i crawler AI, che non eseguono JavaScript.

Verifica completa del 29 ottobre in produzione, con uno script ripetibile su tutte le pagine delle sitemap (861 kosgroup.com, 449 anniazzurri.it): tutte le voci dello sprint 1 superate. Sulle 449 pagine di anniazzurri.it un solo `@graph`, un solo canonical, almeno un H1 e briciole JSON-LD uguali a quelle visibili; 12 user agent di bot AI e motori, 3 richieste ciascuno su 3 pagine per sito, tutte 200 tra 0,3 e 1,6 secondi. Rapporto della verifica e schermate disponibili su richiesta.

Nel DOM renderizzato, cioè dopo JavaScript, le schede delle singole strutture mostrano anche il blocco LocalBusiness iniettato dallo script Yext: è oggetto delle voci sul dato strutturato delle strutture (KOS 24, AA sprint 3), che lo sostituiscono con il dato emesso dal server. Il Rich Results Test su Vico Mercati rileva un solo Breadcrumb, quello completo.

- **Dato strutturato**: [validator.schema.org](https://validator.schema.org) e [Rich Results Test](https://search.google.com/test/rich-results) sugli URL campione. Per il Rich Results Test conviene la verifica sul codice (sorgente della pagina incollato), perché lo strumento esegue JavaScript e mostrerebbe anche markup che i crawler AI non vedono.
- **Intestazioni, redirect, 404**: `curl -sIL <URL>`, oppure gli strumenti per sviluppatori del browser (scheda Rete).
- **H1 e canonical**: sorgente della pagina (Ctrl+U), non l'ispezione degli elementi.

URL campione per il dato strutturato di anniazzurri.it:

| Tipo di pagina | URL |
| --- | --- |
| Home | `https://anniazzurri.it/` |
| Regione | `https://anniazzurri.it/rsa-in-lombardia` |
| Provincia | `https://anniazzurri.it/rsa-in-provincia-di-monza-e-brianza` |
| Residenza | `https://anniazzurri.it/residenza-anni-azzurri-vico-mercati` |
| Approfondimento | `https://anniazzurri.it/cosa-sono-le-rsa` |
| Sportello sociale | `https://anniazzurri.it/sportello-sociale-orientamento-per-assistenza-anziani` |

## Aperti e passaggi successivi

Nessuna voce dello sprint 1 resta aperta lato sviluppo. Restano alcune note per gli sprint successivi.

- **Testo di llms.txt (kosgroup.com e anniazzurri.it)**: frasi di sintesi ricavate dai contenuti istituzionali dei due siti; eventuali revisioni richieste da KOS o dall'agenzia si applicano in giornata.
- **Sprint 2**: ente e briciole di kosgroup.com con la stessa libreria e la stessa convenzione; due link a cartelle di documenti che rispondono 403 ai crawler, emersi dal log degli errori di kosgroup.com.
- **Sprint 3**: dato strutturato delle strutture (`#localbusiness`), al posto del blocco LocalBusiness iniettato dallo script Yext; blocchi JSON-LD residui delle due pagine di lavoro (fuori sitemap) dentro il grafo unico.
