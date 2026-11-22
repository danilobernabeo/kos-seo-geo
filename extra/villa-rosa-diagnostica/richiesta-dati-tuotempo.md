# Richiesta dati di prenotazione (TuoTempo) per Villa Rosa Diagnostica

Data: 22/11/2026. Uso interno del progetto (non per KOS). Serve a capire se il calo del traffico del sito è anche un calo di prenotazioni (analisi F).

## Regole

- Solo dati aggregati: nessun nome, codice fiscale, telefono, email, data di nascita, identificativo di paziente o di prenotazione.
- Per le colonne facoltative, se una combinazione ha meno di 5 prenotazioni, raggrupparla in «altro» (soglia anti-reidentificazione).
- I CSV estratti restano fuori dal repository (come gli xlsx); nel repository vanno solo tabelle elaborate. Danilo informa il referente marketing dell'uso dei dati aggregati.
- Un file CSV (separatore `;`, UTF-8) per ogni tabella; intestazioni come sotto.
- Periodo: da gennaio 2024 a novembre 2026 (novembre parziale, indicare l'ultimo giorno incluso). Se il gestionale non va così indietro, dal primo mese disponibile.
- Sede: solo Villa Rosa Diagnostica (non la casa di cura).

## Tabella 1 (indispensabile): prenotazioni per mese, prestazione e canale

| Colonna | Contenuto |
|---|---|
| `mese_prenotazione` | AAAA-MM del giorno in cui la prenotazione è stata fatta |
| `prestazione` | esame o visita come nel gestionale (es. risonanza magnetica, ecografia, elettromiografia...) |
| `canale` | come è stata presa: sito online, telefono, sportello, altro; se il gestionale distingue app, portali (MioDottore, Doctolib) o call center, tenerli separati |
| `regime` | convenzionato SSN / privato / assicurazione, se disponibile |
| `prenotazioni` | numero di prenotazioni fatte |
| `annullate` | di queste, quante annullate |
| `non_presentati` | di queste, quanti non presentati |

## Tabella 2 (indispensabile): sito online, origine

Solo per le prenotazioni dal sito, se il gestionale registra l'origine del link o l'agenda usata.

| Colonna | Contenuto |
|---|---|
| `mese_prenotazione` | AAAA-MM |
| `origine` | pagina o campagna di provenienza, se registrata (es. villarosadiagnostica.it, kosgroup.com, annuncio); altrimenti «non rilevata» |
| `prenotazioni` | numero |

## Tabella 3 (facoltativa): provenienza geografica

Solo a livello di provincia e senza prestazione: comune + esame + mese renderebbe i pazienti ri-identificabili anche con la soglia di 5.

| Colonna | Contenuto |
|---|---|
| `mese_prenotazione` | AAAA-MM |
| `provincia_residenza` | provincia di residenza (Modena, altre province emiliane, altre regioni, estero) |
| `prenotazioni` | numero |

## Tabella 4 (facoltativa): primo accesso e ritorno

| Colonna | Contenuto |
|---|---|
| `mese_prenotazione` | AAAA-MM |
| `tipo` | primo accesso al centro / già paziente (flag, non l'identità) |
| `prenotazioni` | numero |

## Domande per chi conosce il gestionale

1. Il canale «sito online» comprende anche le prenotazioni fatte dal pulsante di kosgroup.com? Si distinguono?
2. Dal 27/6/2025 (cambio del menu) e da dicembre 2025 sono cambiate agende, regole o disponibilità online?
3. Ci sono mesi con agende chiuse o sospese (es. cantiere, ferie, apparecchiature ferme)?
4. Le prenotazioni telefoniche di nuovi pazienti hanno una domanda tipo «come ci ha conosciuto»? Se sì, è registrata?

## Uso previsto

Confronto mese per mese con sessioni del sito, clic su prenota, chiamate dal profilo Google (Yext) e Ads, per dire se il calo di traffico di marca si è tradotto in meno prenotazioni o se queste sono passate ad altri canali.
