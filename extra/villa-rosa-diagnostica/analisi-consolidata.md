# Diagnostica Villa Rosa: documento di analisi (fasi 1-3, marca e prima lettura TuoTempo)

26/11/2026 · Danilo Bernabeo · bozza per la revisione nella chat principale. Rivista da un controllo indipendente il 26/11 (numeri ricalcolati su BigQuery e sul file TuoTempo; correzioni applicate)

Riunisce in un solo documento le sintesi già prodotte (`sintesi-fase-1-abc.md`, `sintesi-fase-2.md`, `sintesi-marca-tre-pagine.md`, `sintesi-fase-3-d-n.md`, quest'ultima rivista da un controllo indipendente il 23/11) e aggiunge la prima lettura dei dati TuoTempo (23/11). Per ogni numero rimanda alla sintesi che ne contiene il metodo. **Non è ancora la proposta di restyling**: la proposta si scrive dopo le decisioni aperte (paragrafo 9).

Etichette: [verificato] letto da una fonte; [derivato] calcolato dai dati; [osservato] rilevato in una prova singola (pagine dei risultati di Google, assistenti AI, siti dei concorrenti), non generalizzabile; [dichiarato] detto da Danilo, non verificato; [ipotesi] da confermare; [non verificato] non controllato.

## 1. In sintesi

1. **Il sito perde traffico in due gradini.** Le sessioni passano da circa 3.000 al mese (ottobre 2025) a circa 1.600 (da settembre 2026), con −38%/−49% sullo stesso mese dell'anno prima. Il calo è reale, non un effetto della misura: i clic di Search Console seguono le sessioni. [derivato]
2. **Il calo si legge soprattutto nelle ricerche con il nome «villa rosa»** (clic −62% sul sito, sulla parte di clic con ricerca visibile, che è meno della metà del totale) e nelle ricerche per esame (impressioni non di marca da 39.479 a 11.083). [derivato] Nel registro del manager MODX non si trovano modifiche del manager nei giorni dei gradini (il registro non vede SFTP, regole nginx, DNS né kosgroup.com).
3. **La marca è divisa tra tre pagine** (sito, scheda della Diagnostica su kosgroup.com, scheda della clinica psichiatrica). La scheda recupera solo il 12% dei clic persi dal sito; circa 390 clic al mese non si ritrovano in nessuna pagina organica. [derivato]
   **Ipotesi principale** sul calo di marca: l'effetto della campagna Ads di marca, attiva dal 18/11/2025 con destinazione la scheda kosgroup (555-652 clic al mese a settembre-ottobre 2026). L'annuncio sta sopra il sito e i circa 390 clic mancanti sono meno dei clic della campagna; al primo gradino (dicembre 2025) le impressioni di marca restano stabili mentre i clic calano; tra settembre-ottobre 2025 e 2026 il tasso di clic organico scende dall'8,5% al 4,9% (le impressioni di marca calano invece del 35%). Non spiega da sola il secondo gradino (le campagne sono stabili da febbraio 2026) né il calo delle ricerche per esame. [ipotesi] Verifiche in 4 e in 11.
4. **Il profilo Google genera più telefonate e indicazioni** (chiamate +18%, indicazioni +22%, ottobre 2025 → ottobre 2026), mentre i clic sul «Sito web» calano del 30%; il link «Sito web» porta alla scheda kosgroup, non al sito. [derivato] Le impressioni su Search del profilo crollano a ottobre 2026 (−78%) e non si sa se sia un cambio di misura: la lettura «il profilo non perde visibilità» è un'[ipotesi].
5. **La prenotazione online autonoma vale il 2,3% degli appuntamenti confermati.** Negli ultimi 365 giorni il 97,4% non ha un autore indicato nel file (lo chiamo «operatore» per ipotesi), il 2,3% è del paziente online, lo 0,3% del portale MioDottore. [derivato] Il file non dice come siano nati gli altri appuntamenti: se il sito sia il canale principale della domanda resta [ipotesi].
6. **Gli appuntamenti calano quanto le sessioni, ma buona parte del calo è offerta sospesa.** Da marzo a ottobre 2026 gli appuntamenti fanno −41% (1.636 → 970) e le sessioni −40% (2.685 → 1.612). Le radiografie passano da 264-359 al mese a 0 da settembre (sospensione annunciata sul sito dal 31/8), mentre la risonanza tiene (404 → 410). Senza lo stesso periodo dell'anno prima non si può dire quanto sia stagionale. [derivato]
7. **Gli assistenti AI e Google trattano la Diagnostica in modo disomogeneo**: compare in alcune risposte, manca in altre (ChatGPT non la cita per la risonanza aperta), e le risposte riprendono i dati incoerenti del sito (due email, due telefoni). [osservato]
8. **Il sito ha problemi tecnici e di contenuto chiari e di basso costo**: nessun dato strutturato, H1 generico su 14 pagine, descrizioni sbagliate su 16, nessuna anteprima social, orari diversi nello stesso sito, nessun `robots.txt` né sitemap, prestazioni mobili mediocri (home 54/100). [verificato]
9. **La misura va sistemata prima del lancio del 7/1/2027**: nessun evento di prenotazione, telefono o email sul sito; il consenso è concesso di default e Clarity parte prima della scelta (tema di gruppo per il DPO). [verificato]
10. **La scelta decisiva non è tecnica**: quale pagina (sito o scheda kosgroup) deve rispondere alle ricerche generiche. Spetta a KOS e marketing; la proposta presenterà le alternative. Il nome «Diagnostica Villa Rosa» è già deciso (20/11); resta da allineare il titolo della scheda kosgroup.

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
| Pagine dei risultati di Google, assistenti AI, siti dei concorrenti | 21-23/11 | un solo profilo Google, da computer, una rilevazione per domanda |
| Registro del manager MODX (database) | 2017-2026 | non vede SFTP, nginx, DNS, Cloud Edge né kosgroup.com |

Non fatti: log del server e crawler AI, backlink, ricerca da telefono e in navigazione anonima, schede dei portali MioDottore e Doctolib aperte direttamente, accessibilità, volumi di ricerca esterni (Keyword Planner, Trends).

## 3. Traffico e ricerche del sito

- **Due gradini.** [derivato] Luglio-novembre 2025: da +5% a −8% sull'anno prima. Dicembre 2025-marzo 2026: da −17% a −29%. Luglio-ottobre 2026: da −38% a −49%. Il calo di dicembre è stagionale; il rimbalzo di gennaio 2026 è però più debole (+13% contro +32-40% negli anni precedenti).
- **Ricerche di marca e non di marca** (settembre-ottobre 2025 → 2026). [verificato] Con «villa rosa»: clic 1.425 → 536, tasso di clic dall'8,5% al 4,9%. Altre ricerche: impressioni 39.479 → 11.083, clic 435 → 155.
- **Copertura della scomposizione.** [derivato] I clic totali del sito a settembre-ottobre sono 4.183 → 2.180 (−48%); la parte con ricerca visibile (non anonimizzata) è 1.860 → 691 (−63%), cioè il 44% e poi il 32% del totale. Le percentuali di marca e le cifre dei paragrafi 3 e 4 valgono su questa parte, che si restringe.
- **Pagine con più clic persi.** [verificato] Home 940 → 400; risonanza 277 → 114; TMS 185 → 23; «il centro» 122 → 10. Gli atterraggi (sessioni) confermano: home −48%, TMS −74%, radiologia convenzionale −85%, ecografia −72%, risonanza −39%; stabili psichiatria, neurologia e prenota-online. Il calo complessivo è −47%: pesano di più in valore assoluto home (−911) e TMS (−720).
- **Eventi datati, da non fondere.** [verificato i fatti, ipotesi i legami]
  1. 27/6/2025: nel registro MODX tre modifiche al chunk `header-menu` (quello che oggi porta la voce «Prenota Online» alla scheda kosgroup), dentro una giornata di lavoro più ampia (accessibilità e grafica). Le visualizzazioni di `/prenota-online` calano del 75%, ma il calo comincia il 26/6, il giorno prima della modifica registrata; le sessioni totali no.
  2. 18 e 25/11/2025: partono le campagne Ads «Villa Rosa Brand» e «Unbranded», con destinazione la scheda kosgroup. Con «villa rosa» le impressioni restano stabili e i clic calano.
  3. 7/1/2026: in un giorno cadono le impressioni di `/medici/`, `/prenota-online` e `/radiologia-convenzionale`, mentre quelle della home salgono: compatibile con la perdita dei link di sezione sotto il risultato di marca [ipotesi].
  4. Primavera-estate 2026 (secondo gradino; sessioni −18% ad aprile, −28% a maggio, −30% a giugno sull'anno prima): non spiegato dai fatti noti; la scheda della clinica guadagna impressioni su «villa rosa» (da 649 a 3.856 al mese tra marzo e giugno) con clic fermi, mentre il sito le perde.
- **Il manager MODX non ha modificato il sito nei momenti dei gradini.** [verificato sul registro del manager; non vede SFTP, nginx, DNS, Cloud Edge] Dopo il 1/7/2025 il registro mostra solo interventi minori (PDF, chunk informativi, avviso di sospensione del 19/8/2026).
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
- **Ipotesi principale: la campagna Ads di marca.** [ipotesi] Dal 18/11/2025 un annuncio di kosgroup sta sopra i risultati naturali per «villa rosa modena» e manda alla scheda (555-652 clic al mese a settembre-ottobre 2026). I circa 390 clic al mese non recuperati dalla scheda (ordine di grandezza, sulla parte con ricerca visibile) sono meno dei clic della campagna, ma la campagna conta anche persone che avrebbero cliccato comunque. Al primo gradino (dicembre 2025) le impressioni di marca restano stabili e i clic calano, compatibile con un annuncio sopra il risultato naturale. La campagna non spiega da sola il secondo gradino (stabile da febbraio 2026) né le ricerche per esame. Verifica proposta: report «Organico e a pagamento» di Google Ads o una pausa di prova della campagna di marca, da decidere con il marketing.
- Altre cause possibili, da escludere: schede di luogo e risposta generata da Google. Possono essere in parte assorbiti da annuncio e schede di luogo, che Search Console non conta come clic organici; i dati non permettono di dirlo.

## 5. Prenotazioni (TuoTempo, 23/11/2026)

Fonte: export «Storico appuntamenti», 15.192 righe, uguale al totale della dashboard. Ogni riga è un appuntamento confermato, senza dati del paziente. File originale fuori dal repository; tabella aggregata in `dati/tuotempo-appuntamenti-mese-canale-20261123.csv`.

**Canale** [derivato]

| Canale | Appuntamenti | Quota |
|---|---|---|
| Operatore (telefono, sportello) | 14.790 | 97,4% |
| Online, dal paziente | 356 | 2,3% |
| Portale MioDottore | 46 | 0,3% |

- Le prenotazioni online dal paziente sono 13-46 al mese (massimo a maggio 2026; 15 e 19 a settembre e ottobre). Nello stesso periodo (settembre-ottobre 2026) i clic in uscita dal sito verso TuoTempo sono 45 e sulla scheda kosgroup l'evento `click_tuotempo` conta 154 clic in 17 giorni di ottobre e 209 in 19 giorni di novembre: i clic complessivi superano di molto le prenotazioni online (34 nei due mesi).
- Nelle 14.790 righe sono vuoti sia «Tipo di utente» sia «Inserito da»: «operatore» è la mia interpretazione del campo vuoto (può includere telefono, sportello, CUP, farmacie, importazioni da altri sistemi). **Dal file non si sa come sia nata la maggior parte degli appuntamenti né quanti nascano da una visita web.** Inoltre la quota online dipende da cosa è prenotabile online: 179 delle 356 prenotazioni online sono radiografie, oggi sospese.

**Andamento per mese di appuntamento** [derivato]

| Mese | Appuntamenti | Mese | Appuntamenti |
|---|---|---|---|
| Gen 2026 | 1.342 | Lug 2026 | 1.475 |
| Feb | 1.525 | Ago | 1.040 |
| Mar | 1.636 | Set | 994 |
| Apr | 1.615 | Ott | 970 |
| Mag | 1.601 | Nov (fino al 22) | 634 |
| Giu | 1.403 | | |

- Da marzo a ottobre il calo è −41%. Per la dashboard [dichiarato da Danilo, non verificato] il confronto con l'anno precedente è +27,7% (15.192 contro 11.899): **non va usato** finché non si conferma che il perimetro dei due periodi è lo stesso (le risonanze compaiono nell'estrazione solo da gennaio 2026 e sono il 25% degli appuntamenti: l'anno prima potrebbero esserci meno agende).
- **Novembre 2025 è parziale** (l'estrazione parte dal 24/11, 5 giorni feriali, con un volume giornaliero normale); **dicembre 2025 è completo** ed è basso davvero (circa 40 appuntamenti feriali al giorno). La crescita verso gennaio coincide con la comparsa delle risonanze (0 → 277), il raddoppio degli appuntamenti senza prestazione (270 → 556) e un cambio di risorsa tra febbraio e marzo: [ipotesi] nuove agende caricate in TuoTempo da gennaio; da chiedere.
- Dashboard [dichiarato da Danilo, schermata]: disdette 7,2%, non presentati 6,7% (0% nel periodo precedente: probabile avvio della rilevazione), nuovi pazienti 84,9% (definizione del gestionale non nota).

**Anomalie e limiti**

- Il 45% degli appuntamenti (6.879) ha prestazione «-» e nessun gruppo; le risonanze compaiono solo da gennaio 2026; radiografie e TAC risultano 0 da settembre 2026, coerente con la sospensione annunciata sul sito dal 31/8 (RX, ortopanoramica, Dentalscan, Cone Beam). L'analisi per esame non è affidabile finché non si chiarisce che cosa c'è dentro «-» (718 appuntamenti a marzo, 429 a ottobre).
- [derivato] Alcuni appuntamenti stanno fuori dagli orari del sito: 24 domeniche in 52 (460 appuntamenti, di risonanza o senza gruppo, non continuative e assenti da giugno ad agosto) e 127 appuntamenti feriali con inizio dalle 20:00 in poi (uno solo alle 23:00, forse un errore di inserimento). [ipotesi] Può essere un'apertura serale e festiva per la risonanza non riportata sul sito: da confermare con la struttura (se reale, è un'informazione utile per gli utenti).
- Anticipo mediano tra inserimento e appuntamento: 3 giorni (online 7, portale 5, operatore 3).
- Non si può stimare la perdita di prenotazioni dovuta al sito senza i dati dell'anno precedente.

## 6. Utenti, orari e pagine di atterraggio

- **Regione** (sessioni, settembre-ottobre 2025 → 2026). [derivato] Emilia-Romagna 2.424 → 1.624 (−33%), Lombardia 1.245 → 599 (−52%), Veneto 413 → 138 (−67%). L'Emilia-Romagna passa da circa il 39% a circa il 50% delle sessioni. Il traffico lombardo del 2025 (circa il 20%) è anomalo per un centro di Modena; Google Ads non lo spiega (nessuna campagna verso il sito nel 2025). [ipotesi] Può includere ricerche per omonimi o non locali, oppure traffico automatico: regione e canale non sono incrociati, e la quota di sessioni con interazione (stabile) è un indicatore debole.
- **Città**: «(not set)» su tutte le sessioni; **età e genere**: nessun dato. Non analizzabili in questa proprietà.
- **Ora**: il calo è uniforme (circa −45/−50% a ogni ora), punta 9-17.
- **Link in uscita** (settembre-ottobre). [derivato] Verso `app.tuotempo.com` 500 (2024) → 102 → 45; verso `kosgroup.com` 2 → 458 → 229; somma 502 → 560 → 274 (−51%, in linea con le sessioni −47%). Il confronto con il 2024 va letto con la riserva che il profilo Google ha cambiato destinazione a fine febbraio 2025 [dichiarato].
- **Paid Search** nel sito: 62 sessioni a settembre-ottobre 2025 e 44 nel 2026 (482 e 242 sull'anno intero; 252 nel 2024), sempre con zero sessioni con interazione: tracciamento o traffico di bassa qualità, da spiegare prima di leggere le conversioni Ads. Le sessioni Paid Search esistono anche nel 2024, quando le campagne Villa Rosa non c'erano (i dati Ads partono dal 1/11/2025): la loro origine è da chiarire.
- **Google Ads.** [verificato da Danilo] Le campagne Villa Rosa (Brand dal 18/11/2025, Unbranded dal 25/11/2025, raggio 30 km da Modena) mandano a `kosgroup.com/it/centri/villa-rosa-diagnostica`, mai al sito. Brand: 652 clic a settembre 2026, 555 a ottobre (10 e 47 conversioni secondo la verifica di Danilo; 7 e 41 nelle tabelle di statistiche base di BigQuery: definizioni di conversione diverse, da chiarire); Unbranded: 97 e 84 clic, 0 conversioni. Budget modificato il 21/11/2026.

## 7. Presenza locale, concorrenti, assistenti AI

**Profilo Google e portali**

- Yext ha due entità distinte (105 Diagnostica, KCA00_DEG casa di cura). Diagnostica, ottobre 2025 → ottobre 2026: chiamate 509 → 600, indicazioni 195 → 237, clic sul sito 361 → 252. [derivato] Le impressioni su Search crollano a ottobre 2026 (1.417 contro 6.482) mentre Maps e le azioni no: [ipotesi] cambio di metrica o di attribuzione, oppure calo reale; da verificare prima di leggerlo.
- Profilo (21/11): categoria unica «Centro diagnostico»; telefono unico 059 306335; sito = scheda kosgroup; descrizione ferma alla carta dei servizi; campi vuoti: data di apertura, link di prenotazione, servizi, area di servizio. [verificato]
- Portali [osservato]: Micuro e disabili.com descrivono la sede come casa di cura; QSalute (3,8/5 su 15) mescola le recensioni; MioDottore e Doctolib da verificare. Il 4,8 di Google non si ritrova altrove.

**Concorrenti** [osservato, una rilevazione, da computer]

- Per le query per esame la Diagnostica compare ma in concorrenza con Fogliani, Affidea, Centro di Medicina, Alliance Medical e portali di prenotazione. Per «centro diagnostico modena» compare in prima posizione la scheda kosgroup e il sito non è nei primi dieci [ipotesi: da riverificare in modo non personalizzato].
- Nessuno dei tre siti letti (Fogliani, Affidea Modena Medica, Centro di Medicina) pubblica prezzi nelle pagine esaminate; Fogliani non prevede la prenotazione online per la risonanza. I prezzi compaiono nei portali.

**Assistenti AI** [osservato, 7 domande, una esecuzione ciascuna, sessioni non anonime]

- Risonanza aperta: presente in AI Mode, assente in ChatGPT, tra i centri in Gemini senza essere descritta come «aperta». Elettromiografia: presente in tutti e tre, prima in ChatGPT e Gemini. Gemini la dà a 4,8 stelle, la più alta tra i centri.
- Gli assistenti riprendono quasi alla lettera i dati del sito, compresa l'email del dominio `villarosa.it`; Gemini la definisce «accreditata» (non verificato). Primo segnale di traffico dal canale «AI Assistant»: 16 sessioni a settembre-ottobre 2026 (38 nel 2026 fino al 21/11), 0 nel 2025.

## 8. Sito, contenuti e misura

**Stato tecnico** [verificato, scansione 20/11 e prove 21/11]

- 39 pagine HTML indicizzabili (nel senso di non bloccate) e un 404 (la pagina di una professionista, linkata da 3 pagine). `robots.txt`, `sitemap.xml` e `llms.txt` rispondono 404; indirizzi inesistenti rispondono 404 con pagina completa; `/index.php` risponde 200.
- Nessun JSON-LD, nessun `og:`, nessun canonical. 14 pagine di servizio hanno lo stesso H1; 16 pagine dei medici hanno come descrizione una sola parola; 2 pagine senza descrizione.
- Due pagine TMS in concorrenza (869 e 3.171 parole) e due pagine quasi omonime di ecocolordoppler. 21 pagine sotto 300 parole.
- PageSpeed mobile: home 54 (LCP 9,1 s, CLS 0,31), risonanza 72, pagina medico 63, prenota-online 80 (con un errore JavaScript). Primo byte 0,16-0,21 s: il ritardo è nel caricamento di stili, script e immagini.
- Nessun contenuto dipende da JavaScript; nessun modulo nel sito.

**Incongruenze nei contenuti** [verificato]: orari diversi tra testata, colonna laterale e Informazioni utili; cinque indirizzi email; avviso di sospensione di RX, ortopanoramica, Dentalscan e Cone Beam dal 31/8 «per i lavori» mentre il piano li dà come già presenti; tre numeri di telefono sul sito (059 303034, 059 306335, 059 9763143 per la TMS) e telefoni diversi nei portali; nome del marchio in tre forme (il nome è deciso, «Diagnostica Villa Rosa»: da allineare anche il titolo della scheda kosgroup).

**Collegamento alla prenotazione** [verificato]: 135 link TuoTempo su `/prenota-online`, ma le pagine di risonanza, ecografia, RX e neuroradiologia non ne hanno; la voce di menu «Prenota Online» porta alla scheda kosgroup.

**Misura** [verificato]: nessun evento di prenotazione, telefono o email sul sito; sulla scheda kosgroup `click_tuotempo` esiste dal 15/10/2026. Il contenitore GTM concede le statistiche di default e la prova nel browser conferma che GA4 parte prima del banner; Clarity parte senza condizioni (una sola prova, un solo browser; se Clarity applichi un proprio consenso senza cookie non è verificato). Tema di gruppo per il responsabile della protezione dei dati (DPO), nessuna modifica prima del parere; va annotato che correggerlo riduce le sessioni misurate.

**RetailTune e vincoli** [dai documenti del progetto]: i dati della sede devono nascere in un punto solo e arrivare uguali a scheda Google, sito e dato strutturato; **cambio di indirizzo del 7/1/2027:** nome, indirizzo e telefono devono essere uguali su profilo Google, portali, sito, scheda kosgroup e dato strutturato, in piena migrazione da Yext a RetailTune: va deciso chi aggiorna il profilo e i portali quel giorno; comunicazione sanitaria solo informativa (prezzi e confronti da valutare); la casa di cura «Villa Rosa» è tra le strutture senza richiesta attiva di recensioni, quindi serve decidere come trattare le recensioni della Diagnostica, che condivide nome e indirizzo.

## 9. Che cosa ne segue per la proposta

Tutte [ipotesi] da validare.

### 9a. Da fare comunque (non dipende dalle decisioni)

1. **Misura.** Eventi su TuoTempo, telefono ed email; annotare che la correzione del consenso riduce le sessioni misurate; verificare il consenso con il responsabile della protezione dei dati.
2. **Contenuti incoerenti.** Orari (uno solo), email, telefoni, avviso di sospensione, allineamento del nome (titolo della scheda kosgroup incluso).
3. **File tecnici e metadati.** `robots.txt` e sitemap; H1, descrizioni e anteprime social; pagina 404 e doppioni (TMS, ecocolordoppler).
4. **Prestazioni mobili** (LCP, CLS, peso): intervento tecnico a basso rischio.
5. **Dato strutturato.** Il sito descrive la Diagnostica con lo stesso `@id` della scheda kosgroup (`https://kosgroup.com/it/centri/villa-rosa-diagnostica#localbusiness`), non con un nodo nuovo; la clinica psichiatrica resta un `#localbusiness` distinto. La società che gestisce la Diagnostica (ragione sociale e partita IVA) è un dato da verificare nella carta dei servizi o nei dati societari, non una scelta: la struttura rimanda al nodo `https://kosgroup.com/it/chi-siamo/governance/dati-societari#<partita IVA>`, e il legame con KOS S.p.A. resta `parentOrganization`, come nel README. Se serve, una frase di disambiguazione tra Diagnostica e clinica nelle prime righe del sito e della scheda.
6. **Obiettivo del sito.** Progettarlo e misurarlo anche sulle telefonate, sulle richieste di contatto e sulla chiarezza delle informazioni (orari, preparazione, convenzione o privato), non solo sulle prenotazioni online. Lo sostengono: le chiamate dal profilo Google crescono (509 → 600 al mese), il sito non ha eventi su telefono ed email, la prenotazione online autonoma è limitata (2,3%; vedi i limiti del paragrafo 5).
7. **Cambio di indirizzo del 7/1/2027.** Un unico dato di nome, indirizzo e telefono su profilo Google (data di apertura, servizi, descrizione, categorie secondarie), portali, sito, scheda kosgroup e dato strutturato; decidere chi aggiorna il profilo quel giorno (Danilo, Yext o RetailTune) nella migrazione verso RetailTune.

### 9b. Dipende dalle decisioni di KOS e marketing

1. **Ruolo delle pagine.** Alternative: (a) la scheda kosgroup resta il riferimento e il sito approfondisce per esame; (b) il sito torna a essere il riferimento e la scheda resta istituzionale; (c) ruoli distinti con collegamenti reciproci e dato strutturato coerente. I dati sono compatibili con tutte e tre.
2. **Dove vive la prenotazione.** Una sola strada, e di conseguenza il destino di `/prenota-online`, dei suoi 135 link TuoTempo e dei pulsanti della home.
3. **Pagine per esame** (risonanza, TAC, ecografie, elettromiografia) con collegamento diretto a TuoTempo per ciascuna, dopo la decisione precedente; prezzi e tempi solo se KOS e le regole di comunicazione sanitaria lo consentono.
4. **Destinazione del link «Sito web» del profilo Google e delle campagne Ads di marca**, oggi entrambi verso la scheda kosgroup. La prova di pausa della campagna di marca (paragrafo 4) è una decisione del marketing.

## 10. Decisioni e informazioni da chiedere

| Chi | Che cosa |
|---|---|
| KOS / marketing | Dove vive la prenotazione (una sola strada) e che cosa fare di `/prenota-online`, dei suoi 135 link TuoTempo e delle campagne Ads che oggi puntano alla scheda; quale pagina risponde alle ricerche generiche; destinazione delle campagne Ads di marca e del link «Sito web» del profilo; pausa di prova della campagna di marca; destino di `villarosa.it`; chi aggiorna il profilo Google e i portali il 7/1 (Danilo, Yext o RetailTune); TMS, psichiatria e psicoterapia restano nel sito? |
| Progetto (Danilo) | Verificare società e partita IVA che gestisce la Diagnostica (carta dei servizi, dati societari); allineare il titolo della scheda kosgroup al nome «Diagnostica Villa Rosa» |
| Struttura | Orari ufficiali (uno solo) e un'email; orari serali e festivi della risonanza, se reali; data di riattivazione di RX, ortopanoramica, Dentalscan e Cone Beam; indirizzo, foto e testi della nuova sede dopo metà dicembre; se «accreditato» è corretto; chi approva i testi sanitari |
| Responsabile protezione dati (DPO) | Consenso nei contenitori GTM (tema di gruppo) |
| Danilo | Altre estrazioni TuoTempo: stesso periodo dell'anno prima (o conferma che non esiste), che cosa significa il campo vuoto di «Tipo di utente» (telefono, sportello, CUP, altro), contenuto di «-», quali agende sono entrate in TuoTempo e da quando (risonanze da gennaio 2026), annullati e non presentati, definizione di «nuovo paziente»; interfaccia di Google Ads per la cronologia dei cambi; Google Signals attivo? |

## 11. Cosa manca

Verifiche sulle cause del calo: report «Organico e a pagamento» di Google Ads o pausa di prova della campagna di marca; aggiornamenti di Google (core update, AI Overviews e AI Mode in Italia) nelle date dei gradini (27/6/2025, 18/11/2025, 7/1/2026, primavera 2026); volumi di ricerca esterni; ricerca da telefono e non personalizzata; schede dei portali; log e crawler AI; backlink; contenuto della scheda kosgroup tra giugno e luglio 2025; provenienza geografica da TuoTempo (provincia) e primo accesso o ritorno (tabelle 3 e 4 della richiesta, in corso da parte di Danilo); accessibilità; arrivo dell'esportazione GA4 `analytics_317279845`.
