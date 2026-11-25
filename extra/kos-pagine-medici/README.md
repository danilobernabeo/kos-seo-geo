# Extra kos-pagine-medici

Lavoro extra (fuori dal progetto SEO/GEO): pagine medico mancanti su kosgroup.com e barra di prenotazione filtrata per
medico nelle pagine medico. Avvio previsto quando saranno disponibili le API TuoTempo (fine novembre 2026); niente
rilasci in produzione durante la verifica di Cerved/ProWeb (dal 30/11).

## Perché (dati GA4 di kosgroup.com, aggregati, consultati il 25/11/2026)

- Sessioni con atterraggio su una pagina medico (`/it/chi-siamo/qualita-delle-cure/medici/...`), 30/12/2025-24/11/2026:
  22.455; l'82% vede una sola pagina; solo il 7,9% arriva a una scheda struttura.
- Ottobre-novembre 2026 (da quando esiste l'evento `click_tuotempo`): 3.528 sessioni da pagina medico con 50 clic verso
  TuoTempo (1,4%), contro 43.826 sessioni da scheda struttura con 2.637 clic (6,0%). Chi arriva sul medico non risale
  alla struttura e non trova la prenotazione: la barra filtrata per medico porta la prenotazione dove l'utente atterra.
- Il dettaglio per struttura (es. Villa dei Pini) richiede l'abbinamento pagina medico → struttura: da fare con i dati
  del sito (TV della struttura) quando si avvia il lavoro.

## Fonti dei dati

- Elenco dei medici prenotabili: API TuoTempo (risorse per areaid); abbinamento per identificativo della risorsa, mai
  per nome o codice fiscale. Credenziali nelle impostazioni di sistema MODX.
- Foto, CV, descrizione: dai medici, tramite una scheda raccolta da KOS con il consenso alla pubblicazione (testo del
  consenso da far vedere al DPO). **Niente estrazione da MioDottore**: i termini (§5, §9) vietano di estrarre o
  riprodurre i contenuti; foto e testi sono del medico o di MioDottore; testi copiati sarebbero contenuto duplicato.

## Piano

1. Script di confronto (sola lettura): medici TuoTempo per areaid contro pagine medico esistenti → mancanti, pagine di
   medici non più prenotabili, differenze di specialità o struttura.
2. Modello della scheda medico precompilata dai dati TuoTempo, più bozza del consenso per il DPO.
3. Barra di prenotazione filtrata per medico: nuovo parametro dello snippet `barraPrenotazioneDBmedicoPrestazione`
   (come `&branca`). Lo snippet è un elemento del progetto: modifica da coordinare nel progetto (elementi-sotto-progetto).
   Medico su più sedi: la barra deve far scegliere la sede, non assumerne una.
4. Script di creazione pagine (prova, `--scrivi`, backup, ripristino) con template medico, dato strutturato dalla
   libreria comune (`IndividualPhysician`, `practicesAt` verso `#localbusiness`, un solo `@graph`).
5. Da decidere: pagine «minime» (nome, specialità, struttura, prestazioni, prenotazione) subito, eventualmente con
   `noindex` finché la scheda del medico non è completa.
