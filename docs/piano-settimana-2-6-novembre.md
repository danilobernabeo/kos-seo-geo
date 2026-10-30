# Piano della settimana 2-6 novembre: sprint 2 chiuso, sprint 3 avanti, RetailTune pronto

Documento interno (Danilo e Claude), 30 ottobre 2026. Non va condiviso con KOS né con l'agenzia.

## Obiettivo

Venerdì 6/11 alle 13:00 l'ultimo rilascio in produzione. Entro venerdì sera:
- **sprint 2 chiuso**: tutte le voci in produzione, verifica completa superata, nota di consegna pronta (consegna formale
  il 13/11);
- **sprint 3**: in produzione tutto ciò che non dipende da dati altrui; il resto collaudato sullo stage e pronto a uscire;
- **RetailTune**: pacchetto consegnato prima del kickoff del 5/11 e import dei dati anagrafici fatto.

Dal 9/11 il tempo di Danilo va a RetailTune: in quella settimana nessun rilascio in produzione sugli elementi dello
sprint 1 (verifica di Cerved/ProWeb 9-14/11).

## Vincoli

| Data | Vincolo |
|---|---|
| 2-6/11 | Ultima finestra di rilascio per lo sprint 2 e per tutto ciò che tocca elementi dello sprint 1 (`aaSchema`, footer AA, head AA) |
| 3/11 sera | Proposta delle categorie GBP all'agenzia, per avere un riscontro alla call |
| 4/11 14:00 | Call breve con l'agenzia |
| 5/11 | Kickoff ufficiale RetailTune |
| 9-14/11 | Verifica sprint 1: niente rilasci su elementi dello sprint 1; i campi delle pagine KOS (title, description) si possono ancora toccare |
| 13/11 | Consegna formale sprint 2 |
| 16-20/11 | Verifica sprint 2 |
| 27/11 | Consegna sprint 3 |

## Punto di partenza (verificato dal repository il 30/10)

| Voce | Stato | Cosa manca |
|---|---|---|
| KOS 2 · Tempo di risposta | Head consolidati (26/10), cache di `docList` (27/10) | Misura per la nota; caricamento differito delle immagini fuori schermo (piano KOS-2 B) |
| KOS 8 · PDF | Link e cartelle corretti (27/10) | Classificazione, titoli, esclusione dall'indice: stato da controllare lunedì |
| KOS 9, 12, 13 · Ente e briciole | In produzione (22/10) | Solo evidenza |
| KOS 21-23 · Title, description, H1 | Stato degli URL estratto (27/10) | Proposta dei testi (78 title, 301 description, 20 H1), validazione dell'agenzia, import; marchio « \| KOS» dal template (valutazione a parte) |
| KOS 24 · Strutture (linea C) | Libreria e TV in produzione, spente (28/10) | Correzioni Yext, export, import TV, attivazione |
| AA 1 · Sfondi | In produzione (27/10) | Solo evidenza |
| AA 3 · PDF | 97 redirect (27/10) | Classificazione, titoli, esclusione dall'indice: stato da controllare lunedì |
| AA 6 · H2 | In produzione (27/10) | Solo evidenza |
| AA 20 · Title e description | 90 pagine dell'audit (28/10) | 6 title lunghi (agenzia); circa 90 pagine fuori audit, da validare tra Danilo e Claude |
| KOS 10 · Articoli | — | Article con autore ed editore nella libreria comune |
| KOS 7, AA 9 · Testi alternativi | Alt delle news AA (27/10) | Alt mancanti: landing RSA e OSS (AA), immagini KOS |
| KOS 11 · Medici | Confronto pagine/TuoTempo (29/10) | Parte tecnica ora; dati da IT/TuoTempo |
| AA 10 · Pagine provincia | — | Dopo l'import delle TV anagrafiche |
| AA 4 · Stabilità visiva | — | Misura e correzione |
| AA 2, 5, 11, 21 · Schede (linea C) | — | TV anagrafiche AA, import, nodo struttura, scheda contatti dal server |
| RetailTune | Specifiche del 23/10 non inviate | Pacchetto completo (sotto) |

## Il pacchetto per RetailTune (consegna mercoledì 4/11 sera)

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

## Programma

Legenda: **D** = Danilo, **C** = Claude. Le stime di Danilo sono in ore effettive.

### Lunedì 2/11 — preparazione

| Quando | Chi | Attività |
|---|---|---|
| 8:30-9:30 | C | Stato di partenza in produzione: PDF KOS 8 e AA 3 (classificazione, `X-Robots-Tag`, titoli), alt mancanti, misura del tempo di risposta. Elenco aggiornato delle cose da fare |
| 8:30-9:00 | D | Invito a KOS per la call sulle correzioni Yext (martedì mattina, 1 ora); account Meilisearch e progetto Google Cloud per la prova della ricerca (15 minuti) |
| 9:00-10:00 | D | Stage su `stage.anniazzurri.it` e `stage.kosgroup.com`; filtri GTM per hostname |
| 9:30-12:30 | C | Domande per la call KOS: foglio `punti-aperti-kos-yext-2026-10-29.xlsx` + errori attivi delle schede (specifica, sezione 5) in un solo elenco, ordinato per impatto, con la risposta predefinita |
| 10:00-11:00 | D | Verifiche sugli errori attivi delle schede (punto 4 rimasto dal 30/10), così vanno nella call |
| 9:30-13:00 | C | Proposta delle categorie GBP per l'agenzia (residenze, poi gruppi KOS), per codice, con la motivazione e i concorrenti |
| 14:00-17:00 | C | KOS 21-23: proposta dei testi (title, description, H1) con le regole del piano; AA 20: proposta per le circa 90 pagine fuori audit |
| 14:00-15:30 | D | Revisione della proposta delle categorie |
| 15:30-17:30 | D | Validazione AA 20 fuori audit (con C, a blocchi) |
| 17:00-18:30 | C | Estensione dello script di verifica alle voci dello sprint 2 e 3 (PDF, title, Article, alt) |

### Martedì 3/11 — call KOS, invio all'agenzia, primi rilasci

| Quando | Chi | Attività |
|---|---|---|
| 9:00-10:00 | D | **Call KOS**: Danilo chiede, KOS risponde; le risposte vanno nel foglio durante la call |
| 10:00-12:00 | D | Correzioni in Yext dalle risposte (o assegnate a KOS con scadenza mercoledì 12:00) |
| 9:00-12:00 | C | Pacchetti AA 20 giro 2 e, se servono, PDF (KOS 8, AA 3) sullo stage; KOS-2 B (caricamento differito) sullo stage |
| 12:00-13:00 | D | Collaudo sullo stage dei pacchetti della mattina |
| 14:00-15:00 | D | Rilascio in produzione: AA 20 giro 2, PDF, KOS-2 B |
| 14:00-16:00 | C | Mail all'agenzia: proposta categorie GBP + proposta KOS 21-23 + 6 title lunghi AA, con validazione richiesta entro giovedì 5/11 12:00 |
| 16:00-16:30 | D | Revisione e **invio della mail all'agenzia** |
| 14:00-18:30 | C | Pacchetto RetailTune: tipologie, specifiche dei widget, corrispondenza dei campi, domande aperte |
| 16:30-18:30 | D | KOS 11 (parte tecnica): decisioni sulle pagine dei medici da esporre subito |

### Mercoledì 4/11 — sprint 3, call agenzia, pacchetto RetailTune

| Quando | Chi | Attività |
|---|---|---|
| 9:00-12:00 | C | KOS 10 (Article con autore ed editore) e alt mancanti (KOS 7, AA 9) sullo stage; AA 4 misura e correzione della stabilità visiva |
| 9:00-11:00 | D | Proposte degli alt da validare; controllo che le correzioni Yext siano fatte (scadenza 12:00) |
| 11:00-12:00 | D | Preparazione della call con l'agenzia (riscontro sulle categorie, finestra di verifica, testi KOS) |
| 12:00-13:00 | D | **Export Yext corretto** (se le correzioni sono chiuse) |
| 14:00-14:30 | D | **Call con l'agenzia** |
| 13:00-17:00 | C | Dall'export: report delle differenze sulle TV KOS e confronto a tre (sito, Yext, Google); elenco sedi per RetailTune |
| 14:30-16:00 | D | Correzioni alle categorie dalla call; collaudo di KOS 10 e alt sullo stage |
| 16:00-17:00 | D | Rilascio in produzione: KOS 10, alt, AA 4 |
| 17:00-18:30 | C+D | **Invio del pacchetto a RetailTune** |

### Giovedì 5/11 — kickoff RetailTune, import dei dati

| Quando | Chi | Attività |
|---|---|---|
| mattina | D | **Kickoff RetailTune** |
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
| Correzioni Yext non chiuse mercoledì | Import, KOS 24, schede AA e pagine provincia slittano | Linea C, fuori penale: KOS 24 e schede dal 16/11. AA 10 è sprint 3: uscita entro il 27/11 |
| Agenzia non valida i testi KOS 21-23 entro giovedì | KOS 21-23 non escono il 5/11 | Rilascio 9-12/11: tocca solo campi delle pagine KOS, nessun elemento dello sprint 1 |
| Rilascio che svuota la cache e lascia pagine senza JSON-LD (come San Lorenzo il 30/10) | KO nella verifica | Controllo dopo ogni rilascio; fino all'automatismo, verifica completa venerdì dopo le 13:00 |
| Kickoff RetailTune con richieste nuove | Tempo di Danilo sottratto agli sprint | Le richieste nuove vanno nella settimana del 9/11 |
| KOS 11 (medici) senza dati da IT/TuoTempo | Solo parte tecnica | Dati entro il 20/11, uscita entro il 27/11 |

## Fuori da questa settimana

- Prova della ricerca (Meilisearch e Vertex): solo gli account il 2/11 e 30 minuti di giudizio; il resto gira nella sua chat.
- KOS-2 C (`<picture>`/WebP su KOS) e voci dello sprint 4: dopo il 16/11.
- Elenco degli errori attivi delle schede per KOS: dopo le verifiche di Danilo, con la call di martedì.
