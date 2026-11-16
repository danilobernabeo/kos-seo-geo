# Precisazioni alla nota di consegna dello sprint 2

Danilo Bernabeo · 16 novembre 2026

Rileggendo la nota del 6 novembre prima della vostra verifica abbiamo trovato alcune imprecisioni di testo. Nessuna riguarda lo stato dei siti: gli interventi descritti sono in produzione come indicato. I conteggi della nota si riferiscono al 6 novembre (oggi, per esempio, le sitemap contano 866 URL su kosgroup.com e 457 su anniazzurri.it).

## Data di ultima modifica delle pagine (corretta il 13 novembre)

Dal 3 al 13 novembre la data di ultima modifica di 470 pagine (328 su kosgroup.com, 142 su anniazzurri.it) risultava 1° gennaio 1970, nel dato strutturato (`dateModified`) e nelle sitemap (`<lastmod>`). La causa era lo script con cui abbiamo aggiornato title e description (voci KOS 21-23 e AA 20): doveva conservare la data di modifica delle pagine e invece la azzerava. Lo script è corretto e le date sono ripristinate: le pagine mai modificate in precedenza tornano alla data di pubblicazione, le altre hanno la data dell'aggiornamento di title e description. Se nelle vostre scansioni compare ancora il 1970, è una copia precedente al 13 novembre.

## Riferimenti alle strutture negli articoli (corretto il 15 novembre)

Fino al 15 novembre gli articoli di kosgroup.com che citano una struttura (`mentions`) la indicavano con tipo, nome e URL ma senza indirizzo. La convalida di Google delle attività locali tratta questi riferimenti come attività a sé e segnalava l'errore «address obbligatorio» (Rich Results Test, sezione Attività locali), su 160 articoli e video. Dal 15 novembre il riferimento riporta anche indirizzo e telefono della struttura, gli stessi della sua scheda. Se nelle vostre scansioni compare ancora l'errore, è una copia precedente al 15 novembre.

## Correzioni del 16 novembre (anniazzurri.it)

Dalla scansione completa del 15 novembre, prima della verifica:

- **AA 3 · PDF:** la sitemap dei PDF esclusi (`https://anniazzurri.it/sitemap-pdf-noindex.xml`) elencava anche un file di una libreria del CMS, ora tolto (216 PDF). Cinque moduli di richiesta ricovero (Abitare il Tempo, Dorica) avevano ancora il titolo del file Word perché la loro struttura interna era danneggiata e non si potevano aggiornare: sono stati ricostruiti (stesso contenuto, stesse pagine) e hanno ora il titolo «documento – struttura». Rispetto al piano del 3 novembre sono comparsi 2 PDF nuovi (Anni Azzurri Senior Living, pieghevole dell'Hospice Beati Coniugi Martin, in `noindex` per la regola generale) e 2 PDF indicizzabili non sono più collegati da nessuna pagina.
- **Articoli:** con la pubblicazione programmata la data di modifica poteva risultare precedente a quella di pubblicazione (19 articoli); ora `dateModified` non è mai anteriore a `datePublished`.
- **Sitemap e «Ricercabile»:** `https://anniazzurri.it/home-en` e `https://anniazzurri.it/azzurramente` sono entrate nella sitemap; la pagina di iscrizione al convegno sul Parkinson è ora in `noindex, follow` e fuori sitemap.
- **AA 8 · briciole (in correzione questa settimana):** su 41 pagine il livello intermedio delle briciole (visibili e `BreadcrumbList`) punta all'indirizzo di un contenitore che reindirizza alla prima pagina figlia (es. `/soluzioni/`, `/chi-siamo/`). Nomi e livelli sono corretti; l'indirizzo diventerà quello di arrivo.

## Schede struttura senza nodo (KOS 24)

La nota non lo diceva: 3 schede su 61 non hanno ancora il nodo della struttura, perché l'anagrafica importata da Yext non aveva un indirizzo univoco e la regola del progetto è «nodo struttura solo se c'è l'indirizzo». Sono https://kosgroup.com/it/centri/poliambulatorio-villalba (due entità Yext sulla stessa scheda), https://kosgroup.com/it/centri/santo-stefano-casorate e https://kosgroup.com/it/centri/santo-stefano-mede (nessuna entità Yext). Le pagine hanno comunque `WebPage` e briciole. Le completiamo con l'indirizzo pubblicato sulla pagina e ne diamo conto nella nota dello sprint 3.

## Scostamento aggiunto (AA 3 · PDF)

Negli Scostamenti mancava la riga sui PDF di anniazzurri.it: gli scostamenti sono quindi sei.

| Voce | Proposta dell'audit | Scelta | Motivo |
| --- | --- | --- | --- |
| AA 3 | `noindex, nofollow` su tutti i PDF; dopo la deindicizzazione blocco in robots.txt e `rel="nofollow"` sui link ai PDF | 408 PDF classificati uno per uno: 192 indicizzabili (carte dei servizi, pieghevoli, guide), 216 esclusi con `X-Robots-Tag: noindex`; nessun blocco in robots.txt | Carte dei servizi e pieghevoli sono la fonte più completa su servizi, tariffe e modalità di accesso di ogni residenza, e sono cercati per nome: escluderli toglierebbe risposte utili a motori e assistenti AI. Un blocco in robots.txt impedirebbe ai motori di leggere il `noindex` dei documenti esclusi. |

## Correzioni al testo

| Punto | Nella nota | Testo corretto |
| --- | --- | --- |
| KOS 24, landing | «Le 5 landing puntano alla scheda della struttura invece di descriverla di nuovo» | Le 5 landing riportano lo stesso nodo della scheda, con lo stesso `@id`: un'entità sola, non una seconda descrizione. I JSON-LD scritti a mano sono stati tolti. |
| AA 3 | «Il 4/11 sostituite 21 carte dei servizi» | Il 4/11 sostituite 20 carte dei servizi e pubblicato un pieghevole nuovo (Hospice Beati Coniugi Martin). |
| KOS 2 | «79 immagini pesanti ottimizzate» | 73 immagini pesanti ottimizzate, in due giri. |
| Verifica di anniazzurri.it | «del 6 novembre (8:30)» | del 6 novembre mattina (avvio alle 7:29); esiti invariati. |
| KOS 24, campi del nodo | «specialità» tra i campi del nodo struttura | specialità dove la struttura ne ha una mappata (non tutte le schede la riportano). |
| FAQPage (Scostamenti, KOS 24) | Motivo: blocchi scritti a mano, separati dal grafo | In più: dal maggio 2026 Google non mostra più il risultato FAQ nella Ricerca; le domande e risposte restano nel testo visibile. |

## Come verificare: indicazioni più precise

- **AA 6:** nessun «Guarda meno / Guarda di più» dentro un titolo (i titoli come «Guarda il video della residenza» sono legittimi).
- **AA 17:** le 101 immagini dell'allegato sono state ridotte da 51,4 a 13,2 MB e testata e schede sono servite in WebP. Restano sopra 200 KB 8 mappe regionali della pagina Residenze e alcune varianti WebP delle larghezze maggiori (fino a 1600 px, per gli schermi grandi): sono in correzione.
- **KOS 8:** oltre alla carta dei servizi di Punto Prelievi Biemme, un documento escluso: `curl -sI` su https://kosgroup.com/assets/doc/strutture/casa-villa-maura/contratto.pdf, intestazione `X-Robots-Tag: noindex`.
