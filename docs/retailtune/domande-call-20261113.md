# Call RetailTune di venerdì 13/11, ore 11: punti da porre

Uso interno (appunti per Danilo). Raccolti il 10-11/11; venerdì mattina si rilegge e si stampa l'elenco.

## Due istanze, un'anagrafica, tipologie

1. **Filtro per marchio:** un'unica anagrafica con il marchio come attributo. L'istanza anniazzurri.it ha il filtro «Anni Azzurri» fisso e invisibile, l'istanza kosgroup.com mostra tutte le strutture. Confermate?
2. **Tendina delle tipologie** (componente `rt-geo-controls__select`, come su Calligaris e Plenitude): supporta le voci a gruppi (area come intestazione, tipologie sotto)? In alternativa: nella tendina le sei aree (Riabilitazione, Salute mentale, Anziani, Ambulatori e diagnostica, Ospedali, Odontoiatria) e la tipologia di dettaglio nel pannello «Filtri». Su anniazzurri.it la tendina semplice basta: Residenze, Centri diurni, Hospice.
3. **Scelta multipla nella tendina:** solo singola? Vale anche per le aree.
4. **Pin per area:** sei colori, uno per area, invece di sanitarie e residenze.
5. **Filtri dipendenti dalla tipologia:** su kosgroup.com i servizi delle residenze compaiono solo se è scelta l'area «Anziani». Si può fare? Cosa fa il campo `visible` degli attributi (domanda 10 del primo invio)?
6. **Etichette per istanza:** stesso codice, etichetta diversa («Residenze» su anniazzurri.it, «Residenze per anziani» su kosgroup.com).
7. **Codici degli attributi con prefisso del marchio** (`aa-`, `kos-`): «riabilitazione» esiste in entrambi i marchi.
8. **Istanza kosgroup.com:** etichetta «Anni Azzurri» sulla scheda delle residenze, e «Vai alla scheda» verso la pagina di anniazzurri.it (campo `link`).
9. **Pagine delle aree di cura di kosgroup.com** (domanda 7 del primo invio): elenco di strutture passato dalla pagina o attributo dell'area di cura?

## Mappa e chiavi Google

10. **Da quale indirizzo parte la richiesta della mappa:** dalle nostre pagine o da un riquadro su `mappe.*`? Decide i referrer da autorizzare sulla chiave.
11. **Una chiave per sito o una sola?** Proposta: due chiavi nel nuovo progetto `kos-mappe` sulla fatturazione di KOS, solo Maps JavaScript API.
12. **Completamento dell'indirizzo** (prodotto RetailTune fatturato a parte): costo, ed è previsto nel contratto?
13. **Certificati di `mappe.*`:** attivati l'11/11, rinnovo automatico? Percorso dello script del widget, per la verifica.

## Dati e orari

14. **Orari aggiuntivi come attributi personalizzati:** conferma delle chiavi `orari_N_etichetta` e `orari_N_testo` (N da 2 a 4), più `orari_1_etichetta` per il blocco principale.
15. **Prestazioni:** chi le mantiene (inserite a mano o importate da un elenco di KOS) e con quale frequenza.
16. **Export del 6/11:** ricevuto e usabile per l'import preliminare entro il 26/11?

## Widget

17. **Elenco visibile senza consenso** (mappa in categoria «marketing» di Cookiebot): come si presenta nella proposta grafica?
18. **Contenitore su mobile:** l'elenco scorre con la pagina, non in un riquadro con scorrimento proprio.
19. **Riquadro a scomparsa vuoto su mobile** (CR Lab, componente `rt-accordion-menu`): è il componente standard? Corretto prima del nostro rilascio?

## Calendario (con KOS)

20. **Data del cambio** da concordare entro il 26/11: serve la scadenza del contratto Yext (la decide KOS). Proposta: cambio entro il 10/12 se import e widget sono pronti, altrimenti dopo il 7/1, per non avere la finestra di rientro di 10 giorni lavorativi durante le feste.

## Da correggere nel prossimo invio

- `/cosa-sono-le-rsa` non ha nessuna mappa: il punto «[Da decidere]» del primo invio era sbagliato.
