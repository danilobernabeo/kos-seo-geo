# Tag di misura della chat HubSpot (AA-4) – anniazzurri.it

Misura come i visitatori reagiscono alla chat HubSpot durante il test dell'apertura automatica da computer
(fasi A e B, calendario e regola di decisione in `docs/aa-4.md`). Manda a GA4 (quindi a BigQuery) l'evento
`chat_hubspot` e a Microsoft Clarity eventi e tag di sessione per filtrare le registrazioni.

## Cosa contiene `GTM-THMLNFX_chat-hubspot_import.json`
| Elemento | Nome | Note |
|---|---|---|
| Tag HTML personalizzato | `CH - Misura chat HubSpot (AA-4)` | codice in `chat-hubspot.html`; nessun cookie, nessuna richiesta ad altri server |
| Tag evento GA4 | `GA4 - Chat HubSpot` | evento `chat_hubspot`, ID `{{GA4 ID}}` (variabile già esistente) |
| Attivatori | `CH - Page View anniazzurri.it`, `CH - chat_hubspot` | solo sull'hostname di produzione |
| 5 variabili | `DLV - CH …` | livello dati |

Parametri dell'evento: `chat_azione` (mostrata, aperta, interazione, conversazione, chiusa), `chat_modo` (utente,
automatica, ripresa), `chat_secondi` (dal caricamento della pagina), `chat_secondi_aperta` (sulla chiusura),
`chat_interazione` (sulla chiusura: `si` se la chat è stata usata, oltre al clic di chiusura).

Clarity: eventi `chat_aperta_automatica|utente|ripresa`, `chat_chiusa_subito|senza_uso|dopo_uso`,
`chat_conversazione`; tag di sessione `chat_aperta`, `chat_chiusa`, `chat_conversazione` (filtro «Tag personalizzati»
nelle registrazioni).

Provato il 12/11 in produzione (iniezione nel browser): apertura dal pulsante → `aperta/utente`, chiusura con la X
dopo 4 s → `chiusa/utente/4/no`; la riapertura di una finestra lasciata aperta → `ripresa`.

## Importazione
1. Tag Manager → container GTM-THMLNFX → **Amministrazione → Importa container**.
2. File `GTM-THMLNFX_chat-hubspot_import.json`, area di lavoro esistente, **Unisci → Rinomina in conflitto**.
3. Anteprima delle modifiche: solo aggiunte (2 tag, 2 attivatori, 5 variabili).
4. **Anteprima** (Tag Assistant) su una pagina del sito: parte `CH - Misura…`; aprendo e chiudendo la chat compaiono
   gli eventi `chat_hubspot` e parte `GA4 - Chat HubSpot`.
5. **Pubblica**, annotando data e ora (inizio della fase A).

## Analisi
`chat-hubspot-bigquery.sql`: per fase (A/B) e dispositivo, sessioni, interazione, letture fino in fondo, aperture,
chiusure senza uso, conversazioni, contatti per 100 sessioni. Gli istanti di passaggio in testa alla query vanno
aggiornati con l'ora effettiva dei cambi in HubSpot.
