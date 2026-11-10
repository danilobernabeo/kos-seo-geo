# Registro delle decisioni

Uso interno. Una riga per decisione: data, cosa si è deciso, chi o quale fonte, dove è applicata. Si aggiorna a fine sessione. Il piano (`docs/piano-sprint-1.md`) dice cosa fare; questo registro dice cosa è già deciso, così una nuova chat non lo rimette in discussione.

## Dato strutturato e schede

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 29/10/2026 | Residenze `["MedicalBusiness","MedicalOrganization"]`, `medicalSpecialty` Geriatric; tutte le 57 residenze aperte sono socio-sanitarie | Carte dei servizi | CLAUDE.md, `aaSchema` |
| 8/11/2026 | Medico come `IndividualPhysician` solo se una sua sede ha un nodo struttura con indirizzo; altrimenti `Person` con `jobTitle` e `worksFor` | Rich Results Test (LocalBusiness senza indirizzo non valido) | `kosSchema`, `docs/kos-11.md` |
| 8/11/2026 | Villa Margherita Vicenza: i 7 medici restano `Person` finché KOS non chiarisce il nodo della struttura | Danilo | `docs/kos-11.md` |
| 8/11/2026 | Pagine provincia AA: `ItemList` con voci `MedicalBusiness` compatte, senza `@id`; `priceRange` breve («Da 106,00 €») | Requisiti del carosello locale di Google (beta, SEE) | `aaSchema` |
| 8/11/2026 | L'ente Anni Azzurri compare sempre tra le «Attività locali» del test: accettato, comportamento di Google | Rich Results Test | Nota di consegna sprint 3 |
| 9/11/2026 | Villa Pineta: telefono 0536 18031, email anziani.villapineta@kosgroup.com. Navigli, Santa Maria in Chienti, Abitare il Tempo, San Martino, Casa Serena, San Faustino: corretti i dati del sito | Risposta di KOS del 9/11 | `correzioni-sito.json`, TV (stage 9/11) |

## Rilasci

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 8/11/2026 | Sprint 3 rilasciato in produzione la sera dell'8/11, prima dell'avvio della verifica di Cerved/ProWeb del 9/11 | Danilo | `docs/rilascio-20261116.md` |
| 9/11/2026 | Hook e controllo dei commit rinviati a dopo il 16/11 (nessun cambiamento al repository durante la verifica) | Proposta del 9/11 | Questo registro |

## Sostituzione della ricerca Yext (Linea C)

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 9/11/2026 | Proposta: Vertex AI Search (Google, account di fatturazione KOS già attivo). Site Search 360 piano B. Meilisearch scartato (niente radici italiane, regole limitate) | Analisi del 9/11 | Documento «Sostituzione della ricerca Yext» |
| 9/11/2026 | Prova di 14 giorni: Vertex contro la ricerca Yext di oggi, 300 ricerche reali ripulite dai dati personali, sulle pagine pubbliche di produzione (lo stage è protetto da password) | Danilo | Idem |
| 9/11/2026 | Risposte AI solo su anniazzurri.it e solo per domande pratiche, con filtro nostro su sintomi, diagnosi, farmaci e terapie («alzheimer» e «ricovero temporaneo» non vanno bloccati) ed elenco chiuso di pagine; spente su kosgroup.com | Analisi delle ricerche | Idem |
| 9/11/2026 | Nel documento per KOS nessuna stima minima in ore dell'integrazione: «pochi giorni di lavoro», il preventivo arriva dopo la prova | Danilo | Idem |
| 9/11/2026 | Dati di riferimento: anniazzurri.it 17 visite su 100 con contatto se c'è ricerca, contro 2,9 (GA4, 12 mesi); kosgroup.com 4,9 contro 2,9 (BigQuery, 30/12/2025-7/11/2026). Le ricerche (Yext) e le visite (GA4) non sono confrontabili tra loro | GA4 e BigQuery | Idem |

## Store locator RetailTune (Linea C)

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 2/11/2026 | Due istanze separate, filtri preimpostati per pagina, nessuna pagina struttura di RetailTune, consenso con Cookiebot, dati via API | Primo invio a RetailTune | `docs/retailtune/invio-1-configurazione.md` |
| 9/11/2026 | Modello di riferimento: OVS (elenco, pannello di dettaglio, rimando alla nostra scheda). Spunti da Plenitude (tendina tipologie, sezioni nella pagina, condivisione con WhatsApp) e CR Lab (prestazioni a scomparsa) | Call di Danilo con il referente | `docs/retailtune/analisi-esempi-store-locator.md` |
| 9/11/2026 | «Indicazioni» = link al profilo Google Maps, niente percorso calcolato nel widget (API a pagamento) | Danilo | Idem |
| 9/11/2026 | Filtri attivi sempre visibili (OVS non li mostra: da evitare) | Danilo | Idem |
| 9/11/2026 | `/cosa-sono-le-rsa` non ha nessuna mappa: il punto «[Da decidere]» del primo invio era sbagliato | Verifica sulla pagina | Da correggere nel prossimo invio |

| 10/11/2026 | RetailTune conferma il modello OVS, la condivisione, i filtri nell'indirizzo (nomi dei parametri nella documentazione del 16/11), nessuna richiesta di posizione all'apertura, niente recensioni né promozioni. Proposta grafica di kosgroup.com in call venerdì 13/11 alle 11; anniazzurri.it uguale con i colori del marchio | Risposta di RetailTune del 10/11 | `docs/retailtune/analisi-esempi-store-locator.md` |
| 10/11/2026 | Sulla chiave Google di KOS solo la Maps JavaScript API; niente Directions API (indicazioni via link al profilo Google Maps). Il completamento dell'indirizzo è un prodotto RetailTune fatturato a parte | Risposta di RetailTune del 10/11 | Idem |
| 10/11/2026 | Prestazioni: attributi della struttura in RetailTune con codice stabile, inseriti a mano o importati lato server da un elenco di KOS | Risposta di RetailTune del 10/11 | Idem |
| 10/11/2026 | Assistenza domiciliare: testo fuori dal widget nella pagina di anniazzurri.it, gestito da noi. Cookiebot: categoria «marketing» confermata; l'elenco visibile senza consenso è nella proposta del 13/11 | Risposta di RetailTune del 10/11 | Idem |

## Dati e strumenti

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 9/11/2026 | GA4 su BigQuery, progetto `kos-analytics-482819`: kosgroup.com `analytics_333083624` (dal 30/12/2025), anniazzurri.it `analytics_250584060` (dal 24/10/2026). Conservazione GA4: 14 mesi su entrambi | Danilo, BigQuery | Questo registro |
| 9/11/2026 | Plugin: installato `claude-code-setup` (Anthropic); scartati Agent Skills, Context7, Graphify, 21st MCP, Strix (non adatti o, per Strix, senza autorizzazione di KOS) | Valutazione del 9/11 | Questo registro |

## Aperte (in attesa di risposta)

- Pieghevole Parco di Veio: la versione ESE_2 del 10/11 corregge «AMPIO AMPIO»; «Psichiatira» è diventato «Psichiatra» invece di «Psichiatria». Civico 43 contro 41 di Yext e del sito, da confermare con KOS.
- Profilo Google di Villa Pineta ancora con 0536 1803620: chi lo aggiorna (Yext o importazione RetailTune).
- Via libera di KOS alla prova di Vertex AI Search e progetto Google Cloud da usare.
