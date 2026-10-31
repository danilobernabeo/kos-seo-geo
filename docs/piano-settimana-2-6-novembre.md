# Piano della settimana 2-6 novembre: sprint 2 chiuso, sprint 3 avanti, RetailTune pronto

Documento interno (Danilo e Claude), 30 ottobre 2026, aggiornato la sera stessa (weekend, invii anticipati a RetailTune). Non va condiviso con KOS né con l'agenzia.

## Obiettivo

Venerdì 6/11 alle 13:00 l'ultimo rilascio in produzione. Entro venerdì sera:
- **sprint 2 chiuso**: tutte le voci in produzione, verifica completa superata, nota di consegna pronta (consegna formale
  il 13/11);
- **sprint 3**: in produzione tutto ciò che non dipende da dati altrui; il resto collaudato sullo stage e pronto a uscire;
- **RetailTune**: il 5/11 è una data di partenza concordata tra KOS e RetailTune, senza orario. Tutto ciò che si può mandare prima
  va mandato prima: prima parte lunedì 2/11, seconda parte (sedi) appena c'è l'export Yext corretto.

Dal 9/11 il tempo di Danilo va a RetailTune: in quella settimana nessun rilascio in produzione sugli elementi dello
sprint 1 (verifica di Cerved/ProWeb 9-14/11).

## Vincoli

| Data | Vincolo |
|---|---|
| 2-6/11 | Ultima finestra di rilascio per lo sprint 2 e per tutto ciò che tocca elementi dello sprint 1 (`aaSchema`, footer AA, head AA) |
| 3/11 sera | Proposta delle categorie GBP all'agenzia, per avere un riscontro alla call |
| 4/11 14:00 | Call breve con l'agenzia |
| 5/11 | Partenza ufficiale RetailTune (data, nessun orario) |
| 9-14/11 | Verifica sprint 1: niente rilasci su elementi dello sprint 1; i campi delle pagine KOS (title, description) si possono ancora toccare |
| 13/11 | Consegna formale sprint 2 |
| 16-20/11 | Verifica sprint 2 |
| 27/11 | Consegna sprint 3 |

## Punto di partenza (verificato dal repository il 30/10)

| Voce | Stato | Cosa manca |
|---|---|---|
| KOS 2 · Tempo di risposta | Head consolidati (26/10), cache di `docList` (27/10) | Misura per la nota; caricamento differito delle immagini fuori schermo (piano KOS-2 B) |
| KOS 8 · PDF | Link e cartelle corretti (27/10) | Verificato il 31/10: classificazione, titoli ed esclusione dall'indice non ancora fatti (nessuna regola `X-Robots-Tag`). Classificazione da chiedere a KOS nella call; poi `noindex, nofollow` via regole web, sitemap dei PDF per seguire la deindicizzazione |
| KOS 9, 12, 13 · Ente e briciole | In produzione (22/10) | Solo evidenza |
| KOS 21-23 · Title, description, H1 | Stato degli URL estratto (27/10) | Proposta dei testi (78 title, 301 description, 20 H1), validazione dell'agenzia, import; marchio « \| KOS» dal template (valutazione a parte) |
| KOS 24 · Strutture (linea C) | Libreria e TV in produzione, spente (28/10) | Correzioni Yext, export, import TV, attivazione |
| AA 1 · Sfondi | In produzione (27/10) | Solo evidenza |
| AA 3 · PDF | 97 redirect (27/10) | Come KOS 8 (verificato il 31/10). L'audit AA chiede `noindex, nofollow` su tutti i PDF |
| AA 6 · H2 | In produzione (27/10) | Solo evidenza |
| AA 20 · Title e description | 90 pagine dell'audit (28/10) | 6 title lunghi (agenzia); circa 90 pagine fuori audit, da validare tra Danilo e Claude |
| KOS 10 · Articoli | — | Article con autore ed editore nella libreria comune |
| KOS 7, AA 9 · Testi alternativi | Alt delle news AA (27/10) | Alt mancanti: landing RSA e OSS (AA), immagini KOS |
| KOS 11 · Medici | Confronto pagine/TuoTempo (29/10) | Parte tecnica ora; dati da IT/TuoTempo |
| AA 10 · Pagine provincia | — | Dopo l'import delle TV anagrafiche |
| AA 4 · Stabilità visiva | — | Misura e correzione |
| AA 2, 5, 11, 21 · Schede (linea C) | — | TV anagrafiche AA, import, nodo struttura, scheda contatti dal server |
| RetailTune | Specifiche del 23/10 non inviate | Pacchetto completo (sotto) |

## Il pacchetto per RetailTune, in due invii

**Primo invio, lunedì 2/11** (non dipende dall'export Yext): punti 2, 4, 5, 6, 7 e la proposta del punto 3, segnalata come
in attesa del riscontro dell'agenzia. **Secondo invio, mercoledì 4/11 o giovedì 5/11**: il punto 1 dall'export corretto e il
punto 3 definitivo.

1. **Sedi**: elenco dall'export Yext corretto, una riga per sede, con codice sede (quello attuale, invariato), marchio,
   società, URL canonico della scheda, tipologia, dati anagrafici. Le sedi da non importare (chiuse, uffici) indicate a parte.
2. **Tipologie**: elenco unico per i due marchi con codice stabile, etichetta, tipo schema.org e corrispondenza con i valori Yext.
3. **Categorie Google per codice**: principale e aggiuntive per gruppo di sedi (la stessa proposta mandata all'agenzia, con le
   eventuali correzioni della call).
4. **Attributi**: tipologia e area geografica subito; servizi delle residenze AA nella seconda fase.
5. **Specifiche dei widget**: pagine e filtri (mappa generale, pagine provincia AA, mappe per area KOS con `storeids`), domini
   di prova `stage.*`, categoria Cookiebot, riferimenti di stile, CNAME `mappe.*`.
6. **Corrispondenza dei campi** RetailTune → TV `str.*` per l'importazione notturna, con `str.codice` e `str.societa`.
7. **Domande aperte**: abbinamento per codice sede o ID profilo, codici delle categorie, campi dei link di prenotazione, API per
   l'importazione notturna, più tipologie per sede, combinazione dei filtri, ricerca per parole chiave, `storeids` da pagina.

## Fatto il 31/10

- Bozza del primo invio a RetailTune: `docs/retailtune/invio-1-configurazione.md` (decisioni di Danilo segnate [Da decidere]).
- Bozza per KOS e agenzia sulle schede Google (fasi, nomi, siti e UTM, categorie per gruppo, verificate nel selettore il 31/10): `docs/schede-google-passaggio-retailtune.md`. A KOS lunedì 9:00 (osservazioni entro le 15), all'agenzia lunedì sera o martedì mattina; le categorie escono dalla mail di martedì, che resta con i testi KOS 21-23 e i 6 title AA.

## Segnalato il 31/10: pagine provincia vuote su anniazzurri.it

11 risorse provincia (`rsa-in-provincia-di-` ascoli-piceno, asti, como, fermo, grosseto, imperia, pesaro-urbino,
reggio-emilia, roma, rovigo, vercelli) rispondono 200 con contenuto vuoto (template 0), fuori dalla sitemap: rilevato nella
chat sulle prestazioni. Oggi sono pagine vuote raggiungibili (soft 404). Subito: non pubblicate (404) finché non hanno
contenuto: **fatto il 31/10** con `aggiorna-campi.php` (campo `published`), stage e produzione; produzione: backup
`/paas/c0072/backup-kos-seo-geo/20261031-035209-campi` (ripristino `aggiorna-campi.php /www --ripristina <cartella> --scrivi`).
Controllo: 11 pagine in 404, 450 pagine della sitemap in 200 con un solo JSON-LD, nessun link verso le 11 da home, regioni,
`/residenze` e `llms.txt`. Poi: pagine vere con template provincia, testo, elenco strutturato (stessa forma di AA-10) e widget RetailTune
con provincia fissa (già nel primo invio). Non sono una voce d'audit: lavoro interno da valutare a parte, non nei
documenti condivisi finché Danilo non decide.

## Allineamento con la chat sulle prestazioni (istruzioni del 31/10)

Fonte: `ISTRUZIONI-ALTRA-CHAT.md` e `handoff-ttfb-kosgroup-anniazzurri.md` nella cartella `monitor` di Danilo. Dal 19 al
31/10 l'altra chat ha modificato in produzione elementi, impostazioni e regole web dei due siti. Finché il repository non
li contiene, un nostro rilascio li sovrascriverebbe (o, con la baseline vecchia, `installa-elementi.php` si fermerebbe).

| Passo | Chi | Quando | Cosa |
|---|---|---|---|
| 1 | D | **fatto il 31/10** | Esportazione degli elementi dalle due produzioni (`docs/guida-riallineamento-stage.md`, passo 4) e copia delle regole web KOS dal pannello; file nella cartella `monitor` |
| 2 | C | **fatto il 31/10** | Confronto con il repository (esito: nessun elemento del progetto cambiato in produzione; baseline aggiornate con 23 elementi KOS e 21 AA cambiati o nuovi, segreti esclusi; regole web del 31/10 in `<sito>/files/web-rules/produzione-2026-10-31.conf` per entrambi i siti): elementi del progetto cambiati in produzione (es. `breadcrumb`, `ogImage` KOS, head e footer AA) → modifiche integrate in `elements/`; tutti gli altri → nuova baseline; regole web aggiornate nel repository. Registro in `docs/elementi-sotto-progetto.md` |
| 3 | C | **fatto il 31/10** | Pacchetto `kos-ttfb-31-10` con i 5 file pronti dell'altra chat (`template informativo`, `listingNewsLast3`, `kosplayer-banner`, `listingComunicati`, chunk 209), più la rimozione di `[[*schemaJsonLd]]` dal template `informativo` (TV vuota su tutte le pagine dal 30/10). Pacchetto `aa-news` per `/news/` (template `news` e chunk `news-list` in cache), con la verifica di `newsImage` prima del rilascio |
| 4 | D | lunedì mattina | Riallineamento dei due stage dalla produzione (passi 1-3 della guida) e rilancio degli installer dei lavori extra |
| 5 | D | lunedì | `kos-ttfb-31-10` e `aa-news`: stage, collaudo, produzione, nella stessa finestra degli altri rilasci di lunedì |
| 6 | D | dopo ogni rilascio | Script ora nel repository, `scripts/collaudo/monitor/` (31/10): link relativi, og:image, URL annidati; query `verifica-*.sql` su produzione |
| 7 | D | quando si tocca GTM | Tag Web Vitals (`gtm-web-vitals/`) importato con «Unisci» nella stessa area di lavoro dei filtri per hostname dello stage, o in sequenza, per evitare conflitti di versione |

**Chiamate non in cache rimaste (censimento del 31/10 sugli export di produzione).** Candidate alla cache (nessuna
dipendenza da richiesta, sessione o ora del giorno; la cache di MODX è per pagina e si svuota a ogni salvataggio):
- AA, su tutte o quasi le pagine: `[[!Canonical]]` nei 26 head e in `convegnoAA`; `[[!Wayfinder]]` nei template
  `interna-menu-*`; `[[!CompanyInfo]]` nel footer; `[[!ContactInfo]]` nei `contactBox`; `[[!newsByStruttura]]` e
  `[[!getResources]]` in `rsa` e `interna-articolo`; `[[!docList]]` in 3 chunk (come KOS: in cache con `&cacheTtl`).
- KOS: `[[!contenutiStruttura]]` nei template `strutture`, `strutture-VDP` (ogni scheda struttura; `date()` solo per formattare
  la data di pubblicazione); `[[!Wayfinder]]` nei chunk `aree-di-intervento-*` e `menu-en`; `[[!pdoResources]]`,
  `[[!getResources]]`, `[[!pdoField]]`, `[[!pdoMenu]]` negli elenchi; `[[!DisplayResourceTags]]` nel template `video`;
  `[[!Canonical]]` in `head-formazione`, `head-strutture-test`; `[[!copyYears]]`.
Da lasciare non in cache: `switchCta*`, `switchCtaHoliday`, `dayLeft`, `currentMonth` (landing AA, dipendono dalla data),
`nverdeMenuItem` e `getMenuContact` (orari), `prenotaOnline` e `mostraPrestazioniPerBranca` (chiamate esterne in tempo
reale), `comunicatoRedirect` (reindirizza), FormIt e reCAPTCHA, `WAVEChecker`.
Proposta: pacchetti `kos-ttfb-2` (voce KOS-2, sprint 2) e `aa-ttfb` (miglioria, gli head AA sono elementi dello sprint 1:
uscita entro il 6/11 o dopo il 14/11), misura prima e dopo con `scripts/collaudo/monitor/misura-ttfb.ps1`.

Regola nuova (già in `CLAUDE.md`, da rispettare negli elementi del progetto): MODX esegue i tag anche dentro i commenti HTML;
per commentare un tag si usa `[[- ...]]`.

Decisioni aperte dall'altra chat: pagine provincia vuote (vedi sopra: oggi 404; Search Console 14/6/2025-23/10/2026, tabella
`searchconsole_aa.storico_url_impression`: 0 impressioni per tutte e 11, contro 214.341 delle 14 pagine provincia pubblicate,
quindi nessun 301 necessario; l'altra chat propone di completare le 4 con
strutture del gruppo, Roma, Grosseto, Imperia, Reggio Emilia, e 301 alla regione per le altre); paginazione di `/news/`
(fuori piano); email offuscate con entità HTML su kosgroup (nel dato strutturato restano in chiaro); Cloud Edge rimandato.

## Programma

Legenda: **D** = Danilo, **C** = Claude. Le stime di Danilo sono in ore effettive.

### Sabato 31/10 e domenica 1/11 — qualche ora per partire prima

Nessun rilascio in produzione nel weekend: solo preparazione e, se serve, stage.

| Quando | Chi | Attività |
|---|---|---|
| Sabato, 3-4 ore | C | Stato di partenza in produzione (PDF KOS 8 e AA 3, alt mancanti, tempo di risposta); bozza del primo invio a RetailTune (tipologie, attributi, specifiche dei widget, corrispondenza dei campi, domande aperte); bozza delle categorie GBP |
| Sabato | D | Revisione delle bozze RetailTune e categorie, a blocchi man mano che sono pronte |
| Domenica, 3-4 ore | C | Elenco delle domande per la call KOS (foglio dei punti aperti + errori attivi delle schede); proposta dei testi KOS 21-23; proposta AA 20 fuori audit |
| Domenica | D | Verifiche sugli errori attivi delle schede (punto 4 rimasto dal 30/10); validazione AA 20 fuori audit con C |

### Lunedì 2/11 — inviti, primo invio a RetailTune, primi pacchetti

| Quando | Chi | Attività |
|---|---|---|
| 8:30-9:00 | D | Invito a KOS per la call sulle correzioni Yext (proposta: martedì 9:00, 1 ora; se le due persone non possono, il primo orario utile entro mercoledì mattina e, nell'attesa, il foglio dei punti aperti con le risposte predefinite e la scadenza); account Meilisearch e progetto Google Cloud (15 minuti) |
| 9:00-10:00 | D | Stage su `stage.anniazzurri.it` e `stage.kosgroup.com`; filtri GTM per hostname |
| 9:00-12:00 | C | Ritocchi alle bozze del weekend; pacchetti AA 20 giro 2 e PDF (se servono) sullo stage; KOS-2 B sullo stage |
| 10:00-12:00 | D | Revisione finale e **primo invio a RetailTune** |
| 12:00-13:00 | D | Collaudo sullo stage dei pacchetti della mattina |
| 14:00-15:00 | D | Rilascio in produzione: AA 20 giro 2, PDF, KOS-2 B |
| 14:00-17:00 | C | Mail all'agenzia (categorie GBP + KOS 21-23 + 6 title lunghi AA) pronta; estensione dello script di verifica alle voci dello sprint 2 e 3 |
| 15:00-18:00 | D | Revisione della mail all'agenzia; KOS 11 (parte tecnica): decisioni sulle pagine dei medici da esporre subito |

### Martedì 3/11 — call KOS, invio all'agenzia

| Quando | Chi | Attività |
|---|---|---|
| 9:00-10:00 | D | **Call KOS** (se confermata): Danilo chiede, KOS risponde; le risposte vanno nel foglio durante la call |
| 10:00-12:00 | D | Correzioni in Yext dalle risposte (o assegnate a KOS con scadenza mercoledì 12:00) |
| 9:00-12:00 | C | KOS 10 (Article con autore ed editore) sullo stage; alt mancanti (KOS 7, AA 9): proposte |
| 12:00-13:00 | D | **Invio della mail all'agenzia** (categorie GBP, testi KOS 21-23, 6 title lunghi AA; validazione entro giovedì 5/11 12:00) |
| 14:00-17:00 | C | Dati dell'export Yext attuale nel formato del secondo invio, così il giorno dell'export corretto basta rilanciare; AA 4 misura della stabilità visiva |
| 14:00-16:00 | D | Validazione degli alt; collaudo di KOS 10 sullo stage |
| 16:00-17:00 | D | Rilascio in produzione: KOS 10, alt |
| 17:00-18:30 | D | Margine; risposte di RetailTune al primo invio |

### Mercoledì 4/11 — sprint 3, call agenzia, pacchetto RetailTune

| Quando | Chi | Attività |
|---|---|---|
| 9:00-12:00 | C | AA 4 correzione della stabilità visiva sullo stage; TV anagrafiche AA sullo stage |
| 9:00-11:00 | D | Controllo che le correzioni Yext siano fatte (scadenza 12:00); collaudo di AA 4 sullo stage |
| 11:00-12:00 | D | Preparazione della call con l'agenzia (riscontro sulle categorie, finestra di verifica, testi KOS) |
| 12:00-13:00 | D | **Export Yext corretto** (se le correzioni sono chiuse) |
| 14:00-14:30 | D | **Call con l'agenzia** |
| 13:00-17:00 | C | Dall'export: report delle differenze sulle TV KOS e confronto a tre (sito, Yext, Google); elenco sedi per RetailTune |
| 14:30-16:00 | D | Correzioni alle categorie dalla call |
| 16:00-17:00 | D | Rilascio in produzione: AA 4 |
| 17:00-18:30 | C+D | **Secondo invio a RetailTune** (sedi e categorie definitive), se l'export è arrivato; altrimenti giovedì |

### Giovedì 5/11 — partenza RetailTune, import dei dati

| Quando | Chi | Attività |
|---|---|---|
| mattina | D | Partenza RetailTune: risposte e richieste del fornitore; secondo invio se non è partito mercoledì |
| 9:00-12:00 | C | Import delle TV anagrafiche sullo stage (KOS `str.*`, AA nuove TV); KOS 24 attivazione sullo stage; AA 2, 5, 11, 21 e AA 10 sullo stage |
| 12:00-13:00 | D | Controllo delle validazioni dell'agenzia (KOS 21-23, title AA lunghi) |
| 14:00-16:00 | D | Collaudo sullo stage: schede KOS e AA, pagine provincia, confronto prima/dopo |
| 14:00-16:00 | C | Pacchetto KOS 21-23 (testi validati) e marchio « \| KOS» sullo stage |
| 16:00-17:30 | D | Rilascio in produzione: import TV, KOS 24, KOS 21-23 |
| 17:30-18:30 | C | Controllo in produzione dei rilasci del giorno (cache rigenerata, un solo JSON-LD, schede) |

### Venerdì 6/11 — ultimi rilasci, verifica, consegna pronta

| Quando | Chi | Attività |
|---|---|---|
| 9:00-11:00 | D | Rilascio in produzione: AA schede (2, 5, 11, 21) e pagine provincia (AA 10), se il collaudo di giovedì è passato |
| 11:00-13:00 | C+D | Correzioni dell'ultimo minuto; **13:00 ultimo rilascio** |
| 13:00-14:00 | D | Verifica completa in produzione (script esteso), conservata come contro-prova |
| 14:00-16:00 | C | Nota di consegna dello sprint 2 (impaginata) e stato dello sprint 3 |
| 16:00-18:00 | D | Margine per imprevisti; revisione della nota |

## Cosa può slittare e come si recupera

| Rischio | Effetto | Recupero |
|---|---|---|
| Le due persone di KOS non disponibili martedì | Correzioni Yext più tardi | Primo orario utile entro mercoledì mattina; nell'attesa il foglio con le risposte predefinite e la scadenza: senza risposta si applica il predefinito |
| Correzioni Yext non chiuse mercoledì | Import, KOS 24, schede AA e pagine provincia slittano | Linea C, fuori penale: KOS 24 e schede dal 16/11. AA 10 è sprint 3: uscita entro il 27/11 |
| Agenzia non valida i testi KOS 21-23 entro giovedì | KOS 21-23 non escono il 5/11 | Rilascio 9-12/11: tocca solo campi delle pagine KOS, nessun elemento dello sprint 1 |
| Rilascio che svuota la cache e lascia pagine senza JSON-LD (come San Lorenzo il 30/10) | KO nella verifica | Controllo dopo ogni rilascio; fino all'automatismo, verifica completa venerdì dopo le 13:00 |
| Partenza RetailTune con richieste nuove | Tempo di Danilo sottratto agli sprint | Le richieste nuove vanno nella settimana del 9/11 |
| KOS 11 (medici) senza dati da IT/TuoTempo | Solo parte tecnica | Dati entro il 20/11, uscita entro il 27/11 |

## Fuori da questa settimana

- Prova della ricerca (Meilisearch e Vertex): solo gli account il 2/11 e 30 minuti di giudizio; il resto gira nella sua chat.
- KOS-2 C (`<picture>`/WebP su KOS) e voci dello sprint 4: dopo il 16/11.
- Elenco degli errori attivi delle schede per KOS: dopo le verifiche di Danilo, con la call di martedì.
