# Villa Rosa Diagnostica: sintesi parziale fase 3 (analisi D–N)

Data: 22/11/2026. Stato: **bozza parziale, non ancora rivista**. Etichette: [verificato] letto da una fonte, [derivato] calcolato dai dati, [ipotesi] da confermare.

## Cosa è stato fatto e cosa no

| Analisi | Stato |
|---|---|
| D domanda locale | parziale: dati Search Console e SERP; Keyword Planner e Trends non disponibili |
| E utenti | non fatta: serve estendere lo script GA4 (città, età, ora, pagina di arrivo) e farlo girare a Danilo |
| F prenotazioni | non fatta: servono dati aggregati TuoTempo estratti da Danilo |
| G presenza locale | parziale: dati Yext/profilo Google; portali (MioDottore, Micuro) non ancora controllati |
| H concorrenti | parziale: SERP di 6 query; siti dei concorrenti non ancora analizzati |
| I assistenti AI | non fatta |
| L contenuti | fatta sulla scansione del 20/11 |
| M, N | non fatte (solo i vincoli già noti dal progetto) |

## G. Profilo Google (dati Yext, entità 105 Diagnostica e KCA00_DEG Villa Rosa clinica)

- [verificato] Yext ha due entità distinte a Modena: «Villa Rosa Diagnostica» (105) e «Villa Rosa» (KCA00_DEG, la casa di cura).
- [derivato] Diagnostica, azioni dal profilo: chiamate («Tap to Call») 509 a ottobre 2025, 600 a ottobre 2026 (+18%), con un massimo di 1.015 ad aprile 2026; richieste di indicazioni 195 → 237 (+22%); **clic sul sito 361 → 252 (−30%)**.
- [derivato] Impressioni su Maps stabili o in crescita (395 → 610 a ottobre); impressioni su Search 5.757 a settembre 2025, 5.582 a settembre 2026, quindi stabili fino a settembre.
- [derivato] Gli ultimi due mesi (ottobre 2026 impressioni Search 1.417, novembre parziale) crollano in modo simile su entrambe le entità: [ipotesi] ritardo o cambio di fornitura del dato, da verificare prima di leggerlo come calo reale.
- Lettura [ipotesi]: il profilo Google non perde visibilità, anzi genera più telefonate; ciò che cala è il passaggio dal profilo al sito. Coerente con il calo di clic di marca sul sito. Le chiamate non dicono se diventano prenotazioni (serve F).
- La clinica (KCA00_DEG) ha un andamento diverso: chiamate in calo lento (1.067 a gennaio 2025 → 613 a ottobre 2026), clic sul sito 432 → 198 ad ottobre su ottobre.
- Non verificato: coerenza di nome, indirizzo e telefono sui portali; indirizzo «Rosselli» (sito, Google, kosgroup) o «Rossetti» (README e piano marketing).

## H. Concorrenti nelle SERP di Modena (browser di Danilo, personalizzato, desktop)

Query e primi risultati organici rilevanti (host):

| Query | Villa Rosa nei risultati | Concorrenti ricorrenti |
|---|---|---|
| elettromiografia modena | villarosadiagnostica.it in 2ª posizione organica; blocco locale con scheda «Da Villa Rosa Diagnostica» | cdcfogliani.it, poliambulatoriopcm.it, MioDottore, Hesperia, Doctolib |
| radiografia modena | kosgroup.com 3ª, villarosadiagnostica.it 8ª | Fogliani, centrodimedicina.com, ptmodo.it, Policlinico, Affidea |
| risonanza magnetica aperta modena | kosgroup.com 7ª, villarosadiagnostica.it 8ª; scheda «Villa Rosa» nel blocco locale | centrodimedicina.com, Carpi, Fogliani, Affidea |
| centro diagnostico modena | **kosgroup.com 1ª**, villarosadiagnostica.it non nei primi 10 | Affidea, Alliance Medical, Laboratorio Test, Centro Analisi Modenese |
| cone beam modena | villarosadiagnostica.it 2ª e 10ª, kosgroup.com 6ª | Clinica Cantoni, Affidea, studi odontoiatrici |
| risonanza magnetica / tac / ecografia modena | rilevate nella fase precedente | vedi sintesi-fase-2 |

- [verificato] Nelle query generiche («centro diagnostico») compare la scheda di kosgroup.com, non il sito della Diagnostica: la scheda di gruppo sta già lavorando come pagina principale per la domanda generica.
- [verificato] Sulle query per esame la Diagnostica è presente ma in concorrenza con almeno 4–5 attori fissi (Fogliani, Affidea, Centro di Medicina, Alliance Medical/Centro Alfa) e con i portali di prenotazione (MioDottore, Doctolib, elty).
- [verificato] Per «cone beam» la Diagnostica occupa due posizioni: è il tema dove è più forte.
- Limite: SERP personalizzate (account di Danilo, sede Modena), una sola rilevazione, solo desktop.

## L. Contenuti del sito (scansione del 20/11/2026, 40 URL)

- [verificato] Una sola pagina prenotazione (`/prenota-online`, 1.098 parole) con 135 link TuoTempo, ma solo 3 link interni in entrata: la pagina che porta le prenotazioni è poco collegata.
- [verificato] Pagine esame ben fornite: risonanza (1.107 parole), odontoiatrica (719), TMS (869 e una seconda versione con 3.171 parole e un solo link in entrata, probabile duplicato da unificare).
- [verificato] Pagine sottili: 21 pagine sotto 300 parole (una è il 404), tra cui ecocolordoppler (265), la maggior parte dei profili medici (da 49 a 223 parole) e la psichiatria/psicoterapia (173 e 212).
- [verificato] `/antonella-rosi` risponde 404 ma è in sitemap e linkata da 3 pagine.
- [verificato] Due pagine quasi omonime: `/ecocolordoppler` e `/ecocolordoppler-vascolare`; `/medici/` con la barra finale.
- [verificato] Meta description a 15 caratteri su gran parte delle pagine (valore predefinito), assente su una; title di 31–80 caratteri.
- [verificato] Nessuna pagina dedicata a TAC, ecografia per distretto, né agli esami annunciati per la nuova sede. [ipotesi] Va confrontata con l'elenco esami del piano marketing prima di proporre le nuove pagine.

## D. Domanda locale (parziale)

- Dati Search Console già in sintesi-fase-2 e sintesi-marca-tre-pagine: la domanda di marca è la parte più grande e quella che cala; la domanda per esame (risonanza, elettromiografia, cone beam) tiene meno bene dopo aprile 2026.
- Mancano volumi di ricerca esterni; non si stimano.

## M, N: vincoli già noti

- [verificato] Il dato strutturato resta un unico `@graph` emesso lato server dalla libreria comune; la scheda kosgroup resta pagina principale per Google; nessun rilascio in produzione prima della consegna dello sprint 4.
- Da chiedere a KOS: chi gestisce il profilo Google, destino di villarosa.it, uso dei dati TuoTempo, regole su pubblicità sanitaria e consenso.

## Prossimi passi

1. Estensione dello script GA4 (E) e richiesta dei dati TuoTempo aggregati (F): serve Danilo.
2. Portali e coerenza dei dati di sede (G), siti dei concorrenti (H), assistenti AI (I).
3. Revisione indipendente della bozza, poi il riepilogo per la chat principale.
