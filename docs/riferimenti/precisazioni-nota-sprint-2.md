# Precisazioni alla nota di consegna dello sprint 2

Danilo Bernabeo · 16 novembre 2026

Rileggendo la nota del 6 novembre prima della vostra verifica abbiamo trovato alcune imprecisioni di testo. Nessuna riguarda lo stato dei siti: gli interventi descritti sono in produzione come indicato. I conteggi della nota si riferiscono al 6 novembre (oggi, per esempio, le sitemap contano 866 URL su kosgroup.com e 457 su anniazzurri.it).

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
- **AA 17:** nessuna immagine sopra 200 KB (testata e schede in WebP; restano in PNG o JPG alcune immagini piccole).
- **KOS 8:** oltre alla carta dei servizi di Punto Prelievi Biemme, un documento escluso: `curl -sI` su https://kosgroup.com/assets/doc/strutture/casa-villa-maura/contratto.pdf, intestazione `X-Robots-Tag: noindex`.
- **Formulazioni:** «un assistente AI trova la prestazione» va letto come «può trovare»; «i motori e i sistemi AI ora le leggono» come «sono leggibili nell'HTML». La frase «l'obiettivo della voce è raggiunto in tutti» va intesa come la nostra valutazione, che resta vostra da verificare.
