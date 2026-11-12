# Registro delle decisioni

Uso interno. Una riga per decisione: data, cosa si è deciso, chi o quale fonte, dove è applicata. Si aggiorna a fine sessione. Il piano (`docs/piano-sprint-1.md`) dice cosa fare; questo registro dice cosa è già deciso, così una nuova chat non lo rimette in discussione.

## Dato strutturato e schede

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 29/10/2026 | Residenze `["MedicalBusiness","MedicalOrganization"]`, `medicalSpecialty` Geriatric; tutte le 57 residenze aperte sono socio-sanitarie | Carte dei servizi | CLAUDE.md, `aaSchema` |
| 8/11/2026 | Medico come `IndividualPhysician` solo se una sua sede ha un nodo struttura con indirizzo; altrimenti `Person` con `jobTitle` e `worksFor` | Rich Results Test (LocalBusiness senza indirizzo non valido) | `kosSchema`, `docs/kos-11.md` |
| 8/11/2026 | Villa Margherita Vicenza: i 7 medici restano `Person` finché KOS non chiarisce il nodo della struttura | Danilo | `docs/kos-11.md` |
| 12/11/2026 | Villa Margherita Vicenza: Casa di Cura `Hospital` `#localbusiness` sulla landing `/villamargherita` (pagina permanente, eccezione alla forma `/it/…`); schede riabilitazione e psichiatria come `#department` (`MedicalOrganization`); medici `IndividualPhysician` verso la sede; `/it/villa-margherita` (Vicenza e Benevento) senza nodo; telefono 0444 15851 del profilo Google. Link del profilo Google e di RetailTune verso la landing | Danilo; descrizione della struttura | `kos.reparti`, `scripts/deploy/kos-reparti-villa-margherita.php`, `docs/kos-11.md` |
| 12/11/2026 | Villa Margherita Vicenza: nel dato strutturato telefono e orari del profilo Google (+39 0444 15851, lun-ven 8:00-17:30); la landing mostra anche il centralino accanto al numero della landing (0444 043322, lun-ven 9-17), aggiunto a mano nel manager. Nome dei `#department` dal longtitle senza «\| KOS» | Danilo | `shared/snippets/seoSchema.php`, landing `/villamargherita` |
| 8/11/2026 | Pagine provincia AA: `ItemList` con voci `MedicalBusiness` compatte, senza `@id`; `priceRange` breve («Da 106,00 €») | Requisiti del carosello locale di Google (beta, SEE) | `aaSchema` |
| 8/11/2026 | L'ente Anni Azzurri compare sempre tra le «Attività locali» del test: accettato, comportamento di Google | Rich Results Test | Nota di consegna sprint 3 |
| 9/11/2026 | Villa Pineta: telefono 0536 18031, email anziani.villapineta@kosgroup.com. Navigli, Santa Maria in Chienti, Abitare il Tempo, San Martino, Casa Serena, San Faustino: corretti i dati del sito | Risposta di KOS del 9/11 | `correzioni-sito.json`, TV (stage 9/11; produzione verificata il 12/11) |

## Rilasci

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 8/11/2026 | Sprint 3 rilasciato in produzione la sera dell'8/11, prima dell'avvio della verifica di Cerved/ProWeb del 9/11 | Danilo | `docs/rilascio-20261116.md` |
| 9/11/2026 | Hook e controllo dei commit rinviati a dopo il 16/11 (nessun cambiamento al repository durante la verifica) | Proposta del 9/11 | Questo registro |
| 10/11/2026 | CAR Macerata: guide per l'utente (italiano e inglese) e organigramma nuovi, raddrizzati e con OCR; tolti i 3 documenti Fisiosport (unità chiusa); carta dei servizi di agosto 2026 invariata. CDI San Giuseppe: carta di novembre 2026 nella pagina del centro diurno e al posto della copia vecchia nella pagina della RSA. In produzione il 10/11 | Richiesta di KOS del 10/11 | `kosgroup/dati/pdf/car-macerata-2026-11-10.json`, `anniazzurri/dati/pdf/cdi-san-giuseppe-2026-11-10.json` |
| 10/11/2026 | Locandina «Il mantello del custode» (anniazzurri.it): Bertolaso sostituito con «Dott. Mario Melazzini - Direttore Generale Welfare - Regione Lombardia», stesso percorso, metadati scritti. Resta `X-Robots-Tag: noindex` (regola `pdf-noindex.conf`, come già l'8/11). In produzione il 10/11, 2.614.243 byte | Richiesta urgente del 10/11 | `anniazzurri/dati/pdf/mantello-custode-2026-11-10.json` |
| 11/11/2026 | Pieghevole del Poliambulatorio Parco di Veio, versione definitiva ESE_3 («Psichiatria» corretto, civico 43): sostituito con metadati su stage e produzione l'11/11, 1.296.461 byte, senza `X-Robots-Tag` | Invio di KOS dell'11/11 | `kosgroup/dati/pdf/pieghevole-parco-di-veio-2026-11-11.json` |
| 10/11/2026 | Consegna dello sprint 3 venerdì 13/11 (in anticipo sul 27/11): verifiche la mattina, poi nota di consegna e invio, prima della call RetailTune delle 11. Bozza della nota preparata l'11-12/11; nessun rilascio il 12/11 | Danilo | `docs/piano-sprint-1.md` |

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
| 6/11/2026 | Export Yext delle strutture (import preliminare, R15) e linee guida di stile inviati a RetailTune. Calendario contrattuale dall'avvio del 5/11: ambiente di test e data del cambio entro il 26/11, attività RetailTune completate entro circa il 17/12 | Danilo; risposta ai requisiti R15-R20 | Mail del 6/11 |
| 10/11/2026 | RetailTune conferma il modello OVS, la condivisione, i filtri nell'indirizzo (nomi dei parametri nella documentazione del 16/11), nessuna richiesta di posizione all'apertura, niente recensioni né promozioni. Proposta grafica di kosgroup.com in call venerdì 13/11 alle 11; anniazzurri.it uguale con i colori del marchio | Risposta di RetailTune del 10/11 | `docs/retailtune/analisi-esempi-store-locator.md` |
| 10/11/2026 | Sulla chiave Google di KOS solo la Maps JavaScript API; niente Directions API (indicazioni via link al profilo Google Maps). Il completamento dell'indirizzo è un prodotto RetailTune fatturato a parte | Risposta di RetailTune del 10/11 | Idem |
| 10/11/2026 | Prestazioni: attributi della struttura in RetailTune con codice stabile, inseriti a mano o importati lato server da un elenco di KOS | Risposta di RetailTune del 10/11 | Idem |
| 10/11/2026 | Assistenza domiciliare: testo fuori dal widget nella pagina di anniazzurri.it, gestito da noi. Cookiebot: categoria «marketing» confermata; l'elenco visibile senza consenso è nella proposta del 13/11 | Risposta di RetailTune del 10/11 | Idem |
| 10/11/2026 | Orari: blocco principale più fino a tre aggiuntivi con etichetta e testo libero (esempio peggiore Villa dei Pini Civitanova, 4 blocchi); orari di visita delle residenze anche nel contenuto delle pagine, lato server, da TV alimentate via API da RetailTune; nel dato strutturato solo l'orario principale | Danilo | `docs/piano-sprint-1.md` (orari multipli) |
| 10/11/2026 | Orari aggiuntivi come attributi personalizzati RetailTune (il blocco istituzionale resta quello per Google): proposta di chiavi `orari_N_etichetta` e `orari_N_testo`, N da 2 a 4, testo libero, etichette da elenco chiuso (es. «Visite ai familiari»). Da confermare in call il 13/11 | Risposta di RetailTune del 10/11, proposta di Danilo | Mail a RetailTune del 10/11 |
| 11/11/2026 | Chiavi Google Maps del widget: nuovo progetto `kos-mappe` sulla fatturazione di KOS, sola Maps JavaScript API, budget mensile 30 € (avvisi 50% e 90% effettivi, 100% previsto). Due chiavi, `widget-retailtune-kosgroup` e `widget-retailtune-anniazzurri`, limitate alla Maps JavaScript API e ai referrer `https://<dominio>/*` e `https://*.<dominio>/*` (stage e `mappe.*` compresi, `*.modx.dev` esclusi). Tetto di 5.000 caricamenti di mappa al giorno. Chiavi mandate a RetailTune l'11/11 (completato); valori solo nella mail, mai nel repository. Le chiavi attuali delle mappe di AA restano fino alla sostituzione | Danilo | Questo registro |
| 11/11/2026 | Accessi: RetailTune collegata ai profili Google (sola lettura) e aggiunta su Apple Business Connect. Bing: nessun accesso da dare, RetailTune usa le API di Bing e unisce le schede a quelle alimentate finora da Yext, dopo la dismissione. Data del cambio, scadenza Yext e correzioni in Yext (Villa Pineta) competono a KOS. Attributi della seconda fase: elenchi già pronti dai dati Yext | Danilo | Questo registro |

## Dati e strumenti

| Data | Decisione | Fonte | Dove |
|---|---|---|---|
| 9/11/2026 | GA4 su BigQuery, progetto `kos-analytics-482819`: kosgroup.com `analytics_333083624` (dal 30/12/2025), anniazzurri.it `analytics_250584060` (dal 24/10/2026). Conservazione GA4: 14 mesi su entrambi | Danilo, BigQuery | Questo registro |
| 9/11/2026 | Plugin: installato `claude-code-setup` (Anthropic); scartati Agent Skills, Context7, Graphify, 21st MCP, Strix (non adatti o, per Strix, senza autorizzazione di KOS) | Valutazione del 9/11 | Questo registro |

## Aperte (in attesa di risposta)

- Poliambulatorio Parco di Veio: civico 41 sul profilo Google contro il 43 di sito, Yext, residenza e pieghevole ESE_3: da correggere sul profilo (KOS).
- Profilo Google di Villa Pineta ancora con 0536 1803620: chi lo aggiorna (Yext o importazione RetailTune).
- Via libera di KOS alla prova di Vertex AI Search e progetto Google Cloud da usare.
- CNAME RetailTune configurati dall'IT (verifica dell'11/11): `mappe.kosgroup.com` → `stores-kosgroup.retailtune.com`, `mappe.anniazzurri.it` → `stores-anniazzurri.retailtune.com` (TTL 6 h, nessun record CAA che limiti le CA). Certificati emessi da RetailTune l'11/11: verifica in browser OK sui due nomi (HTTPS valido, radice 200 vuota, server Apache). Resta da verificare lo script del widget quando RetailTune ne comunica il percorso.
- Al passaggio da Yext a RetailTune: aggiornare l'intestazione `Permissions-Policy` di kosgroup.com (`geolocation=(self "https://cdn.yextapis.com")`) per il widget RetailTune.
- Villa dei Pini Civitanova: nuovo direttore sanitario Roberta Tomassini, da scrivere nella TV «contatti struttura» (manager, produzione).
- Giornata di lavoro con KOS in presenza il 19/11: orari per struttura (principale, visite, prelievi, ritiro referti) e chi li aggiorna; significato degli orari 08:00-20:00 delle residenze.
