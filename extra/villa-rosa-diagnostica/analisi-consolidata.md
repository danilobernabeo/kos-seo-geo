# Diagnostica Villa Rosa: documento di analisi (fasi 1-3, marca e prima lettura TuoTempo)

26/11/2026 · Danilo Bernabeo · bozza per la revisione nella chat principale

Riunisce in un solo documento le sintesi già prodotte (`sintesi-fase-1-abc.md`, `sintesi-fase-2.md`, `sintesi-marca-tre-pagine.md`, `sintesi-fase-3-d-n.md`, quest'ultima rivista da un controllo indipendente il 23/11) e aggiunge la prima lettura dei dati TuoTempo (23/11). Per ogni numero rimanda alla sintesi che ne contiene il metodo. **Non è ancora la proposta di restyling**: la proposta si scrive dopo le decisioni aperte (paragrafo 9).

Etichette: [verificato] letto da una fonte; [derivato] calcolato dai dati; [osservato] rilevato in una prova singola (SERP, assistenti AI, siti dei concorrenti), non generalizzabile; [dichiarato] detto da Danilo, non verificato; [ipotesi] da confermare; [non verificato] non controllato.

## 1. In sintesi

1. **Il sito perde traffico in due gradini.** Le sessioni passano da circa 3.000 al mese (ottobre 2025) a circa 1.600 (da settembre 2026), con −38%/−49% sullo stesso mese dell'anno prima. Il calo è reale, non un effetto della misura: i clic di Search Console seguono le sessioni. [derivato]
2. **Il calo viene soprattutto dalle ricerche con il nome «villa rosa»** (clic −62% sul sito) e dalle ricerche per esame (impressioni non di marca da 39.479 a 11.083). Le cause cercate sono fuori dal sito: il registro del manager MODX non mostra modifiche nei giorni dei due gradini. [derivato]
3. **La marca è divisa tra tre pagine** (sito, scheda della Diagnostica su kosgroup.com, scheda della clinica psichiatrica). La scheda recupera solo il 12% dei clic persi dal sito; circa 390 clic al mese non si ritrovano in nessuna pagina organica. [derivato]
4. **Il profilo Google non perde visibilità**: chiamate +18% e richieste di indicazioni +22% (ottobre 2025 → ottobre 2026), ma i clic sul sito dal profilo calano del 30%, e il link «Sito web» del profilo porta alla scheda kosgroup, non al sito. [derivato]
5. **Il sito non è il canale principale di prenotazione.** Nei dati TuoTempo degli ultimi 365 giorni il 97,4% degli appuntamenti è inserito da operatore, il 2,3% dal paziente online, lo 0,3% dal portale MioDottore. [derivato]
6. **Le prenotazioni complessive non crollano come il sito**: da circa 1.600 appuntamenti al mese (marzo-maggio 2026) a circa 1.000 (agosto-ottobre). Senza lo stesso periodo dell'anno prima non si può dire quanto sia stagionale. [derivato]
7. **Gli assistenti AI e Google trattano la Diagnostica in modo disomogeneo**: compare in alcune risposte, manca in altre (ChatGPT non la cita per la risonanza aperta), e le risposte riprendono i dati incoerenti del sito (due email, due telefoni). [osservato]
8. **Il sito ha problemi tecnici e di contenuto chiari e di basso costo**: nessun dato strutturato, H1 generico su 14 pagine, descrizioni sbagliate su 16, nessuna anteprima social, orari diversi nello stesso sito, nessun `robots.txt` né sitemap, prestazioni mobili mediocri (home 54/100). [verificato]
9. **La misura va sistemata prima del lancio del 7/1/2027**: nessun evento di prenotazione, telefono o email sul sito; il consenso è concesso di default e Clarity parte prima della scelta (tema di gruppo per il DPO). [verificato]
10. **La scelta decisiva non è tecnica**: quale pagina (sito o scheda kosgroup) deve rispondere alle ricerche generiche, con quale forma del nome. Spetta a KOS e marketing; la proposta presenterà le alternative.

## 2. Fonti e limiti

| Fonte | Periodo | Limiti principali |
|---|---|---|
| Scansione del sito (Screaming Frog, 20/11) | un giorno | HTML del server; sitemap e robots non letti dalla scansione (verificati a parte il 21/11) |
| GA4 storico (Data API) in `ga4storico_vrd` | 1/1/2024-21/11/2026 (alcune tabelle dal 2022) | città sempre «(not set)»; età e genere assenti; sessioni per pagina contano più volte le sessioni con più pagine |
| Search Console del sito | 8/7/2025-18/11/2026 | query anonimizzate escluse; niente storico prima di luglio 2025 |
| GA4 e Search Console di kosgroup.com | dal 30/12/2025 (GA4) | manca il confronto con l'anno precedente |
| Yext (entità 105 e KCA00_DEG) | gennaio 2025-ottobre 2026 | l'ultimo mese Search è anomalo (vedi 4) |
| Google Ads (verifica di Danilo con l'MCP, sola lettura) | dal 1/11/2025 | cronologia delle modifiche solo 30 giorni |
| TuoTempo, estrazione «Storico appuntamenti» (23/11) | appuntamenti dal 24/11/2025 al 22/11/2026 | solo confermati; vedi paragrafo 5 |
| SERP, assistenti AI, siti dei concorrenti | 21-23/11 | profilo di Danilo (Modena/Milano), desktop, una rilevazione per domanda |
| Registro del manager MODX (database) | 2017-2026 | non vede SFTP, nginx, DNS, Cloud Edge né kosgroup.com |

Non fatti: log del server e crawler AI, backlink, ricerca da telefono e in navigazione anonima, schede dei portali MioDottore e Doctolib aperte direttamente, accessibilità, volumi di ricerca esterni (Keyword Planner, Trends).

## 3. Traffico e ricerche del sito

- **Due gradini.** [derivato] Luglio-novembre 2025: da +5% a −8% sull'anno prima. Dicembre 2025-marzo 2026: da −17% a −29%. Luglio-ottobre 2026: da −38% a −49%. Il calo di dicembre è stagionale; il rimbalzo di gennaio 2026 è però più debole (+13% contro +32-40% negli anni precedenti).
- **Ricerche di marca e non di marca** (settembre-ottobre 2025 → 2026). [verificato] Con «villa rosa»: clic 1.425 → 536, tasso di clic dall'8,5% al 4,9%. Altre ricerche: impressioni 39.479 → 11.083, clic 435 → 155.
- **Pagine con più clic persi.** [verificato] Home 940 → 400; risonanza 277 → 114; TMS 185 → 23; «il centro» 122 → 10. Gli atterraggi (sessioni) confermano: home −48%, TMS −74%, radiologia convenzionale −85%, ecografia −72%, risonanza −39%; stabili psichiatria, neurologia e prenota-online. Il calo complessivo è −47%: pesano di più in valore assoluto home (−911) e TMS (−720).
- **Eventi datati, da non fondere.** [verificato i fatti, ipotesi i legami]
  1. 27/6/2025: nel registro MODX tre modifiche al chunk `header-menu` (quello che oggi porta la voce «Prenota Online» alla scheda kosgroup), dentro una giornata di lavoro più ampia (accessibilità e grafica). Le visualizzazioni di `/prenota-online` calano del 75%; le sessioni totali no.
  2. 18 e 25/11/2025: partono le campagne Ads «Villa Rosa Brand» e «Unbranded», con destinazione la scheda kosgroup. Con «villa rosa» le impressioni restano stabili e i clic calano.
  3. 7/1/2026: in un giorno cadono le impressioni di `/medici/`, `/prenota-online` e `/radiologia-convenzionale`, mentre quelle della home salgono: compatibile con la perdita dei link di sezione sotto il risultato di marca [ipotesi].
  4. Primavera-estate 2026 (secondo gradino): non spiegato dai fatti noti; la scheda della clinica guadagna impressioni su «villa rosa» (da 649 a 3.856 al mese tra marzo e giugno) con clic fermi, mentre il sito le perde.
- **Il sito non è stato modificato nei momenti dei gradini.** [verificato] Dopo il 1/7/2025 il registro mostra solo interventi minori (PDF, chunk informativi, avviso di sospensione del 19/8/2026).
- **Anomalie GA4.** [verificato] Visualizzazioni senza sessione in alcuni mesi (agosto 2024: 10.868 viste con 11 sessioni); salto della quota di sessioni con interazione a settembre 2023, compatibile con un cambio di misura: i due periodi non sono confrontabili.

Dettaglio: `sintesi-fase-2.md`.

## 4. La marca «villa rosa» e le tre pagine

[derivato, da `sintesi-marca-tre-pagine.md`; settembre-ottobre, clic al mese]

| Pagina | 2025 | 2026 | Differenza |
|---|---|---|---|
| Sito | 713 | 268 | −445 |
| Scheda Diagnostica su kosgroup.com | 70 | 124 | +54 |
| Clinica psichiatrica su kosgroup.com | 598 | 448 | −150 |
| Totale | 1.380 | 840 | −541 |

- Il calo è del sito (−62%) più che della clinica (−25%). La scheda assorbe circa il 12% di quanto perde il sito.
- Per «villa rosa modena» Google ha alternato le due pagine: da marzo a luglio 2026 la scheda kosgroup sta sopra il sito, a ottobre il sito torna sopra. La clinica è sempre nelle prime due posizioni.
- Pagina dei risultati (21/11, da computer, account di Danilo, [osservato]): annuncio di kosgroup in cima, due schede di luogo (clinica 3,3/5 su 124 recensioni; Diagnostica 4,8/5 su 37), una risposta generata che tratta le due strutture come una sola realtà, poi i risultati naturali.
- [ipotesi] I circa 390 clic al mese non recuperati possono essere in parte assorbiti da annuncio e schede di luogo, che Search Console non conta come clic organici; i dati non permettono di dirlo.

## 5. Prenotazioni (TuoTempo, 23/11/2026)

Fonte: export «Storico appuntamenti», 15.192 righe, uguale al totale della dashboard. Ogni riga è un appuntamento confermato, senza dati del paziente. File originale fuori dal repository; tabella aggregata in `dati/tuotempo-appuntamenti-mese-canale-20261123.csv`.

**Canale** [derivato]

| Canale | Appuntamenti | Quota |
|---|---|---|
| Operatore (telefono, sportello) | 14.790 | 97,4% |
| Online, dal paziente | 356 | 2,3% |
| Portale MioDottore | 46 | 0,3% |

- Le prenotazioni online dal paziente sono 13-46 al mese (massimo a maggio 2026; 15 e 19 a settembre e ottobre). Nello stesso periodo i clic in uscita verso TuoTempo dal sito sono 45: ordine di grandezza coerente.
- «Operatore» comprende le persone che hanno telefonato dopo aver visto sito o scheda: **dal file non si sa quante prenotazioni nascano da una visita web**.

**Andamento per mese di appuntamento** [derivato]

| Mese | Appuntamenti | Mese | Appuntamenti |
|---|---|---|---|
| Gen 2026 | 1.342 | Lug 2026 | 1.475 |
| Feb | 1.525 | Ago | 1.040 |
| Mar | 1.636 | Set | 994 |
| Apr | 1.615 | Ott | 970 |
| Mag | 1.601 | Nov (fino al 22) | 634 |
| Giu | 1.403 | | |

- Da marzo a ottobre il calo è −41%. Nella dashboard, il confronto con l'anno precedente è +27,7% (15.192 contro 11.899), ma la base di quel periodo non è confrontabile con certezza: l'estrazione contiene prenotazioni inserite solo dal 29/10/2025, con anticipi fino a 91 giorni; **novembre-dicembre 2025 sono quindi incompleti** e la crescita fino a gennaio è in buona parte un effetto dell'estrazione.
- Dashboard: disdette 7,2%, non presentati 6,7% (0% nel periodo precedente: probabile avvio della rilevazione), nuovi pazienti 84,9% (definizione del gestionale non nota).

**Anomalie e limiti**

- Il 45% degli appuntamenti (6.879) ha prestazione «-» e nessun gruppo; le risonanze compaiono solo da gennaio 2026 e radiografie e TAC risultano 0 da settembre 2026: l'analisi per esame non è affidabile finché non si chiarisce che cosa c'è dentro «-».
- [osservato] Gli appuntamenti non sono tutti negli orari del sito: 24 domeniche (460 appuntamenti, tutti di risonanza o senza gruppo) e appuntamenti con inizio fino alle 23:00 nei giorni feriali. Può essere un'apertura serale e festiva per la risonanza non riportata sul sito: da confermare con la struttura (se reale, è un'informazione utile per gli utenti).
- Anticipo mediano tra inserimento e appuntamento: 3 giorni (online 7, portale 5, operatore 3).
- Non si può stimare la perdita di prenotazioni dovuta al sito senza i dati dell'anno precedente.

## 6. Utenti, orari e pagine di atterraggio

- **Regione** (sessioni, settembre-ottobre 2025 → 2026). [derivato] Emilia-Romagna 2.424 → 1.624 (−33%), Lombardia 1.245 → 599 (−52%), Veneto 413 → 138 (−67%). L'Emilia-Romagna passa da circa il 39% a circa il 50% delle sessioni. Il traffico lombardo del 2025 (circa il 20%) è anomalo per un centro di Modena; Google Ads non lo spiega (nessuna campagna verso il sito nel 2025). [ipotesi] Può includere ricerche per omonimi o non locali: regione e canale non sono incrociati.
- **Città**: «(not set)» su tutte le sessioni; **età e genere**: nessun dato. Non analizzabili in questa proprietà.
- **Ora**: il calo è uniforme (circa −45/−50% a ogni ora), punta 9-17.
- **Link in uscita** (settembre-ottobre). [derivato] Verso `app.tuotempo.com` 500 (2024) → 102 → 45; verso `kosgroup.com` 2 → 458 → 229; somma 502 → 560 → 274 (−51%, in linea con le sessioni −47%). Il confronto con il 2024 va letto con la riserva che il profilo Google ha cambiato destinazione a fine febbraio 2025 [dichiarato].
- **Paid Search** nel sito: 62 sessioni nel 2025 e 44 nel 2026, con zero sessioni con interazione: tracciamento o traffico di bassa qualità, da spiegare prima di leggere le conversioni Ads.
- **Google Ads.** [verificato da Danilo] Le campagne Villa Rosa (Brand dal 18/11/2025, Unbranded dal 25/11/2025, raggio 30 km da Modena) mandano a `kosgroup.com/it/centri/villa-rosa-diagnostica`, mai al sito. Brand: 652 clic a settembre 2026, 555 a ottobre (10 e 47 conversioni); Unbranded: 97 e 84 clic, 0 conversioni. Budget modificato il 21/11/2026.

## 7. Presenza locale, concorrenti, assistenti AI

**Profilo Google e portali**

- Yext ha due entità distinte (105 Diagnostica, KCA00_DEG casa di cura). Diagnostica, ottobre 2025 → ottobre 2026: chiamate 509 → 600, indicazioni 195 → 237, clic sul sito 361 → 252. [derivato] Le impressioni su Search crollano a ottobre 2026 (1.417 contro 6.482) mentre Maps e le azioni no: [ipotesi] cambio di metrica o di attribuzione, oppure calo reale; da verificare prima di leggerlo.
- Profilo (21/11): categoria unica «Centro diagnostico»; telefono unico 059 306335; sito = scheda kosgroup; descrizione ferma alla carta dei servizi; campi vuoti: data di apertura, link di prenotazione, servizi, area di servizio. [verificato]
- Portali [osservato]: Micuro e disabili.com descrivono la sede come casa di cura; QSalute (3,8/5 su 15) mescola le recensioni; MioDottore e Doctolib da verificare. Il 4,8 di Google non si ritrova altrove.

**Concorrenti** [osservato, una rilevazione, desktop]

- Per le query per esame la Diagnostica compare ma in concorrenza con Fogliani, Affidea, Centro di Medicina, Alliance Medical e portali di prenotazione. Per «centro diagnostico modena» compare in prima posizione la scheda kosgroup e il sito non è nei primi dieci [ipotesi: da riverificare in modo non personalizzato].
- Nessuno dei tre siti letti (Fogliani, Affidea Modena Medica, Centro di Medicina) pubblica prezzi nelle pagine esaminate; Fogliani non prevede la prenotazione online per la risonanza. I prezzi compaiono nei portali.

**Assistenti AI** [osservato, 7 domande, una esecuzione ciascuna, sessioni non anonime]

- Risonanza aperta: presente in AI Mode, assente in ChatGPT, tra i centri in Gemini senza essere descritta come «aperta». Elettromiografia: presente in tutti e tre, prima in ChatGPT e Gemini. Gemini la dà a 4,8 stelle, la più alta tra i centri.
- Gli assistenti riprendono quasi alla lettera i dati del sito, compresa l'email del dominio `villarosa.it`; Gemini la definisce «accreditata» (non verificato). Primo segnale di traffico dal canale «AI Assistant»: 17 sessioni nel 2026, 0 nel 2025.

## 8. Sito, contenuti e misura

**Stato tecnico** [verificato, scansione 20/11 e prove 21/11]

- 39 pagine HTML indicizzabili (nel senso di non bloccate) e un 404 (`/antonella-rosi`, linkato da 3 pagine). `robots.txt`, `sitemap.xml` e `llms.txt` rispondono 404; indirizzi inesistenti rispondono 404 con pagina completa; `/index.php` risponde 200.
- Nessun JSON-LD, nessun `og:`, nessun canonical. 14 pagine di servizio hanno lo stesso H1; 16 pagine dei medici hanno come descrizione una sola parola; 2 pagine senza descrizione.
- Due pagine TMS in concorrenza (869 e 3.171 parole) e due pagine quasi omonime di ecocolordoppler. 21 pagine sotto 300 parole.
- PageSpeed mobile: home 54 (LCP 9,1 s, CLS 0,31), risonanza 72, pagina medico 63, prenota-online 80 (con un errore JavaScript). Primo byte 0,16-0,21 s: il ritardo è nel caricamento di stili, script e immagini.
- Nessun contenuto dipende da JavaScript; nessun modulo nel sito.

**Incongruenze nei contenuti** [verificato]: orari diversi tra testata, colonna laterale e Informazioni utili; cinque indirizzi email; avviso di sospensione di RX, ortopanoramica, Dentalscan e Cone Beam dal 31/8 «per i lavori» mentre il piano li dà come già presenti; due telefoni nei portali; nome del marchio in tre forme.

**Collegamento alla prenotazione** [verificato]: 135 link TuoTempo su `/prenota-online`, ma le pagine di risonanza, ecografia, RX e neuroradiologia non ne hanno; la voce di menu «Prenota Online» porta alla scheda kosgroup.

**Misura** [verificato]: nessun evento di prenotazione, telefono o email sul sito; sulla scheda kosgroup `click_tuotempo` esiste dal 15/10/2026. Il container concede le statistiche di default e la prova nel browser conferma che GA4 parte prima del banner; Clarity parte senza condizioni. Tema di gruppo per il DPO, nessuna modifica prima del parere; va annotato che correggerlo riduce le sessioni misurate.

**RetailTune e vincoli** [dai documenti del progetto]: i dati della sede devono nascere in un punto solo e arrivare uguali a scheda Google, sito e dato strutturato; comunicazione sanitaria solo informativa (prezzi e confronti da valutare); la casa di cura «Villa Rosa» è tra le strutture senza richiesta attiva di recensioni, quindi serve decidere come trattare le recensioni della Diagnostica, che condivide nome e indirizzo.

## 9. Che cosa ne segue per la proposta

Tutte [ipotesi] da validare, ordinate per scelta e non per sforzo.

1. **Ruolo delle pagine.** Alternative: (a) la scheda kosgroup resta il riferimento e il sito approfondisce per esame; (b) il sito torna a essere il riferimento e la scheda resta istituzionale; (c) ruoli distinti con collegamenti reciproci e dato strutturato coerente. I dati sono compatibili con tutte e tre; decide KOS/marketing.
2. **Obiettivo del sito.** Poiché la prenotazione online vale il 2,3%, il sito va progettato e misurato anche sulle telefonate, sulle richieste di contatto e sulla chiarezza delle informazioni (orari, preparazione, convenzione o privato), non solo sulle prenotazioni online.
3. **Prima del 7/1/2027.** Misura (eventi su TuoTempo, telefono ed email); contenuti incoerenti (orari, email, avviso di sospensione, nome); `robots.txt` e sitemap; dato strutturato con `@id` stabili; H1, descrizioni e anteprime social; pagina 404 e doppioni.
4. **Distinguere Diagnostica e clinica** nel nome, nel dato strutturato (due `#localbusiness`, `parentOrganization` verso KOS S.p.A.) e, se serve, con una frase di disambiguazione.
5. **Profilo Google**: data di apertura, link di prenotazione, servizi, descrizione, categorie secondarie e destinazione del link «Sito web».
6. **Pagine per esame** (risonanza, TAC, ecografie, elettromiografia) con collegamento diretto a TuoTempo per ciascuna; prezzi e tempi solo se KOS e le regole di comunicazione sanitaria lo consentono.
7. **Prestazioni mobili** (LCP, CLS, peso): intervento tecnico a basso rischio.

## 10. Decisioni e informazioni da chiedere

| Chi | Che cosa |
|---|---|
| KOS / marketing | Quale pagina risponde alle ricerche generiche; forma del nome («Diagnostica Villa Rosa» o «Villa Rosa Diagnostica»); destinazione delle campagne Ads di marca e del link «Sito web» del profilo; destino di `villarosa.it`; chi modifica la scheda Google (Danilo, Yext o RetailTune); TMS, psichiatria e psicoterapia restano nel sito? |
| Struttura | Orari ufficiali (uno solo) e un'email; orari serali e festivi della risonanza, se reali; data di riattivazione di RX, ortopanoramica, Dentalscan e Cone Beam; indirizzo, foto e testi della nuova sede dopo metà dicembre; se «accreditato» è corretto; chi approva i testi sanitari |
| DPO | Consenso nei container GTM (tema di gruppo) |
| Danilo | Altre estrazioni TuoTempo: stesso periodo dell'anno prima (o conferma che non esiste), contenuto di «-» e perché le categorie cambiano da settembre 2026, annullati e non presentati, definizione di «nuovo paziente»; interfaccia di Google Ads per la cronologia dei cambi; Google Signals attivo? |

## 11. Cosa manca

Volumi di ricerca esterni; ricerca da telefono e non personalizzata; schede dei portali; log e crawler AI; backlink; contenuto della scheda kosgroup tra giugno e luglio 2025; provenienza geografica da TuoTempo (provincia) e primo accesso o ritorno (tabelle 3 e 4 della richiesta, in corso da parte di Danilo); accessibilità; arrivo dell'esportazione GA4 `analytics_317279845`.
