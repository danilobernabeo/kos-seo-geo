# Diagnostica Villa Rosa: la marca «villa rosa» divisa tra tre pagine

21/11/2026 · Danilo Bernabeo · per la revisione nella chat principale

## Domanda

Le ricerche di marca («villa rosa», «villa rosa modena», «villa rosa diagnostica») compaiono su tre pagine di Google: il sito villarosadiagnostica.it, la scheda della Diagnostica su kosgroup.com (`/it/centri/villa-rosa-diagnostica`) e la scheda della clinica psichiatrica (`/it/centri/villa-rosa`). Quanti clic ha ciascuna nel tempo? Quanto dei clic persi dal sito ha guadagnato la scheda? Quanto resta non spiegato?

## Metodo e limiti

- Fonti: Search Console del sito (`searchconsole_vrd`, dall'8/7/2025) e di kosgroup.com (`gsc_kos`, ricerca web); query della marca = contengono «villa rosa» o «villarosa». Query anonimizzate escluse. Script: `sql/marca-tre-pagine.sql`.
- «Villa rosa» da sola è ambigua (omonimi, per esempio il Centro Medico Kos di Sassuolo): i conteggi di marca comprendono anche ricerche non riferibili alla Diagnostica.
- Le impressioni di una stessa ricerca si contano su ogni pagina che compare nella pagina dei risultati: non si sommano come domanda. I clic sì.
- La posizione del sito è la migliore tra le sue pagine (livello sito); quella delle schede è per pagina: confronto approssimato.
- Il confronto principale usa settembre-ottobre 2025 contro settembre-ottobre 2026. Storico precedente a luglio 2025 non disponibile per il sito.

## Clic di marca per mese e pagina

[verificato]

| Mese | Sito | Scheda Diagnostica | Clinica psichiatrica |
| --- | --- | --- | --- |
| Lug 2025 | 453 | 51 | 547 |
| Ago 2025 | 502 | 35 | 549 |
| Set 2025 | 686 | 67 | 608 |
| Ott 2025 | 739 | 72 | 588 |
| Nov 2025 | 622 | 100 | 497 |
| Dic 2025 | 416 | 67 | 429 |
| Gen 2026 | 453 | 135 | 551 |
| Feb 2026 | 527 | 137 | 496 |
| Mar 2026 | 468 | 151 | 456 |
| Apr 2026 | 414 | 214 | 548 |
| Mag 2026 | 350 | 186 | 584 |
| Giu 2026 | 284 | 159 | 551 |
| Lug 2026 | 247 | 156 | 507 |
| Ago 2026 | 200 | 112 | 397 |
| Set 2026 | 259 | 116 | 479 |
| Ott 2026 | 277 | 131 | 417 |

## Bilancio settembre-ottobre 2025 contro settembre-ottobre 2026 (clic al mese)

[derivato]

| Pagina | 2025 | 2026 | Differenza |
| --- | --- | --- | --- |
| Sito | 713 | 268 | -445 |
| Scheda Diagnostica | 70 | 124 | +54 |
| Clinica psichiatrica | 598 | 448 | -150 |
| Totale delle tre | 1.380 | 840 | -541 |

- La scheda recupera circa il 12% di quanto perde il sito. La clinica non guadagna: perde anch'essa, ma meno del sito (-25% contro -62%).
- Restano circa 390 clic al mese persi dal sito senza una pagina organica che li riceva. [derivato]
- Le ricerche con «diagnostica», «radiologia», «risonanza» o «tuotempo» (la marca riferibile alla Diagnostica): il sito passa da 155 a 63 clic al mese, la scheda da 38 a 42. Anche le ricerche esplicite sulla Diagnostica non finiscono sulla scheda in modo organico. [derivato]

## Posizione e tasso di clic per «villa rosa modena»

[verificato]

| | Sito | Scheda Diagnostica | Clinica |
| --- | --- | --- | --- |
| Posizione ott 2025 | 3,0 | 5,8 | 1,5 |
| Posizione feb 2026 | 3,0 | 2,9 | 1,7 |
| Posizione apr-lug 2026 | 3,8-4,8 | 2,2-2,4 | 1,7-1,9 |
| Posizione ott 2026 | 2,9 | 3,8 | 1,9 |
| Clic ottobre 2025 → ottobre 2026 | 507 → 217 | 42 → 87 | 474 → 320 |

- Dal marzo 2026 e fino all'estate Google mette la scheda su kosgroup.com sopra il sito per questa ricerca; a ottobre 2026 il sito torna sopra. La clinica è sempre nelle prime due posizioni.
- Il tasso di clic del sito su «villa rosa modena» passa dal 12,5% (ottobre 2025) al 6,7% (ottobre 2026), con un minimo del 4,5% a giugno 2026.
- Sulla sola ricerca «villa rosa»: le impressioni del sito calano da 709 (maggio 2026) a 109 (ottobre); quelle della clinica salgono da 649 (marzo 2026) a 3.856 (giugno) con i clic quasi fermi (12-46 al mese) e poi scendono a 822 (ottobre).

## Che cosa si può dire

- **Il calo di marca è un fatto del sito e non della clinica.** [derivato] La clinica perde il 25% dei clic, il sito il 62%.
- **Il passaggio alla scheda è piccolo.** [derivato] Il sito perde 445 clic al mese, la scheda ne guadagna 54.
- **Dove vanno gli altri circa 390 clic non si legge in Search Console.** Ipotesi da verificare con le schermate di Danilo: Google Ads (campagna di marca verso la scheda, circa 650 clic al mese da dicembre 2025, ma conta anche chi avrebbe comunque cercato), elementi della pagina dei risultati che rispondono senza clic (scheda Google con telefono e pulsante di prenotazione), altre fonti. [ipotesi]
- **Il primo gradino di dicembre 2025 non è solo stagionale per il sito:** la clinica recupera a gennaio (551), il sito no (453 contro 739 di ottobre). [derivato]

## Domande per la proposta (non conclusioni)

1. Quale pagina deve rispondere a «villa rosa modena» per la Diagnostica: il sito o la scheda su kosgroup.com? Oggi Google le alterna.
2. Quale forma del nome in titolo, intestazioni, dato strutturato (`name`, `alternateName`) e scheda Google: «Diagnostica Villa Rosa» o «Villa Rosa Diagnostica»? Il nome «Villa Rosa» da solo porta alla clinica psichiatrica.
3. Come distinguere nel dato strutturato le due strutture KOS con lo stesso marchio (due `#localbusiness` distinti, tipi e `alternateName` diversi, collegamento a KOS S.p.A. tramite `parentOrganization`)?
4. Che ruolo dare alla scheda su kosgroup.com dopo il restyling (istituzionale, con rimando al sito per la prenotazione) e come evitare che le due pagine competano per la stessa ricerca?
5. Dove mandare la campagna Ads di marca e il pulsante «Sito web» della scheda Google: alla scheda su kosgroup.com, come oggi, o al sito?
6. Serve una frase di disambiguazione tra Diagnostica e clinica psichiatrica nelle prime righe del sito e della scheda? (Nel piano marketing «Villa Rosa» è associata alla psichiatria.)

## Pagina dei risultati di Google (21/11/2026)

Verificata nel browser integrato, da computer, con l'account Google di Danilo e località a Modena: non anonima. Dettagli in `sintesi-fase-2.md`. [verificato]

- «villa rosa modena»: annuncio di kosgroup.com in cima, poi due schede di luogo (clinica 3,3/5 su 124 recensioni, Diagnostica 4,8/5 su 37), la risposta generata da Google che fonde le due strutture, poi i risultati naturali con la scheda della clinica per prima, la home del sito e la scheda della Diagnostica più in basso.
- «villa rosa diagnostica»: home del sito al primo posto, scheda su kosgroup.com al secondo. Il pulsante «Sito web» della scheda Google della Diagnostica porta alla scheda su kosgroup.com, non al sito.
- Scheda Google della Diagnostica (dati del profilo): una sola categoria («Centro diagnostico»), link di prenotazione e servizi vuoti, descrizione ferma al testo della carta dei servizi, social di KOS Riabilitazione e di KOS Group. Elenco completo in `sintesi-fase-2.md`.
- Risposta generata da Google sulla marca: tratta Villa Rosa come un'unica realtà con clinica e poliambulatorio.
- Il quadro è compatibile con la lettura «marca divisa» e con i clic assorbiti da annuncio e schede di luogo, che Search Console non conta come clic organici sulle pagine. [ipotesi]

## Verifiche ancora aperte

- Ricerca da telefono e in navigazione anonima.
- Data del cambio della voce di menu «Prenota Online» dal registro del manager MODX (a cura di Danilo).
