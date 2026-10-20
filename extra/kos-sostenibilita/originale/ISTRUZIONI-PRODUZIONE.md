# Pubblicazione in produzione – Grafici pagina Sostenibilità KOS

Componenti: **1. I nostri dipendenti** · **2. Le nostre strutture** · **3. Attività del gruppo** · **4. Ricavi** — versioni IT ed EN.
Ambiente: MODX 3.2 · Bootstrap 5 (già nel tema) · nessuna libreria esterna aggiunta.

Tempo stimato: 60–90 minuti. Consigliato in orario di basso traffico.

---

## 0. Prima di iniziare

### 0.1 Bloccanti sui dati — da risolvere PRIMA della pubblicazione

| # | Componente | Cosa verificare |
|---|---|---|
| A | Ricavi | **2024 (812) e 2025 (846) sono valori inventati per il test.** Sostituirli con i dati ufficiali (§4). |
| B | Strutture | **57 RSA in Italia / 53 in Germania** è ricavato (110 − 53), non fornito: farlo confermare. |
| C | Attività | Il badge è **2025** ma il numero (+7500) è quello del 2023: aggiornare il numero o rimettere 2023. |

### 0.2 Prerequisiti in produzione

- [ ] **MIGX 3.x** installato (*Extra → Installer*). La versione 2.x non funziona su MODX 3.
- [ ] **ID delle risorse** Sostenibilità in produzione annotati (in test la pagina IT è la **54**):
      ID pagina IT = `____`  · ID pagina EN = `____`
      Se l'ID IT **non è 54**, va cambiato in 4 chunk (§3.3).
- [ ] Dove sta il codice dei box: nel **contenuto** delle due risorse o nel **template**? (Determina dove fare le sostituzioni al §5.)

### 0.3 Backup (obbligatorio)

- [ ] Dump del database.
- [ ] Copia di `/assets/css/`, `/assets/js/`, `/assets/img/grafici/`.
- [ ] Copia testuale (in un file) di: template della pagina Sostenibilità IT ed EN, contenuto delle due risorse.
- [ ] **Non cancellare** i PNG attuali dei grafici: servono per il rollback (§8).

---

## 1. Caricamento file statici

Caricare via FTP/SFTP rispettando i percorsi (la cartella `assets/` del pacchetto rispecchia quella del sito):

| File nel pacchetto | Destinazione sul server |
|---|---|
| `assets/css/kos-chart.css` | `/assets/css/` |
| `assets/css/kos-strutture.css` | `/assets/css/` |
| `assets/css/kos-ricavi.css` | `/assets/css/` |
| `assets/js/kos-chart.js` | `/assets/js/` |
| `assets/js/kos-ricavi.js` | `/assets/js/` |
| `assets/img/grafici/strutture-globo.png` | `/assets/img/grafici/` |
| `assets/img/grafici/icone/*.png` (7 file) | `/assets/img/grafici/icone/` (cartella nuova) |

Verifica: aprire nel browser `https://<dominio>/assets/js/kos-chart.js` → deve mostrare il codice, non un 404.

---

## 2. Template: link a CSS e JS

In **tutti** i template usati dalle pagine Sostenibilità (IT ed EN, se diversi):

**Nel `<head>`, dopo `custom.css`:**
```html
  <link href="/assets/css/kos-chart.css?v=20261006" rel="stylesheet" />
  <link href="/assets/css/kos-strutture.css?v=20261006" rel="stylesheet" />
  <link href="/assets/css/kos-ricavi.css?v=20261006" rel="stylesheet" />
</head>
```

**Prima di `</body>`, dopo gli script del tema (`main.js`):**
```html
  <script src="/assets/js/kos-chart.js?v=20261006" defer></script>
  <script src="/assets/js/kos-ricavi.js?v=20261006" defer></script>
</body>
```

- I link vanno **dentro** `<head>`, non tra `</head>` e `<body>` (in test era successo).
- Ad ogni futura modifica di un file, cambiare il suo `?v=` (es. data del giorno).
- Verificare che le pagine EN abbiano `<html lang="en">` (serve a JS per tooltip e formato numeri in inglese).

---

## 3. Elementi MODX

Creare prima una **categoria** `Sostenibilità` (*Elementi → Categorie*) e mettere lì tutto.

### 3.1 Template Variables (2)

Per ciascuna: *Elementi → Template Variables → Nuova*, tipo di input **migx**, configurazione nei file:

| Nome TV (esatto) | Configurazione | Accesso ai template |
|---|---|---|
| `chartDipendenti` | `modx/tv/chartDipendenti.md` | template della pagina Sostenibilità **IT** |
| `chartRicavi` | `modx/tv/chartRicavi.md` | template della pagina Sostenibilità **IT** |

⚠️ Il **Nome** deve essere esattamente quello indicato (la didascalia è libera). In test un nome diverso dava grafico vuoto.

### 3.2 Snippet (2)

*Elementi → Snippet → Nuovo*, nome esatto, incollare il contenuto del file:

| Nome | File |
|---|---|
| `kosChartDipendenti` | `modx/snippet/kosChartDipendenti.php` |
| `kosChartRicavi` | `modx/snippet/kosChartRicavi.php` |

### 3.3 Chunk (8)

*Elementi → Chunk → Nuovo*, nome = nome del file senza `.html`:

| Componente | IT | EN |
|---|---|---|
| Dipendenti | `kosDipendenti_it` | `kosDipendenti_en` |
| Strutture | `kosStrutture_it` | `kosStrutture_en` |
| Attività | `kosAttivita_it` | `kosAttivita_en` |
| Ricavi | `kosRicavi_it` | `kosRicavi_en` |

Se in produzione la pagina IT **non ha ID 54**: nei 4 chunk Dipendenti e Ricavi sostituire `&resource=`54`` con l'ID corretto.
(I dati stanno solo sulla pagina IT: la pagina EN li legge da lì.)

---

## 4. Inserimento dati

Aprire la **risorsa Sostenibilità IT** → scheda delle TV:

- **chartDipendenti**: 5 righe come nella tabella di `modx/tv/chartDipendenti.md`. Numeri **senza** punto delle migliaia (7032, non 7.032).
- **chartRicavi**: 5 righe come in `modx/tv/chartRicavi.md`, **con i valori ufficiali 2024–2025** (bloccante A).

**Salvare la risorsa** (non basta chiudere la finestra della riga MIGX).

Dati che stanno nei chunk (non in TV), da rileggere prima di pubblicare:
- `kosStrutture_*`: badge anno, 8 valori della lista, 53/91 sul globo.
- `kosAttivita_*`: badge anno e numero questionari (bloccante C).

---

## 5. Sostituzione dei box nella pagina

Nella pagina **IT** (contenuto o template, vedi §0.2) sostituire **per intero** ciascun box, dal `<div class="box-infografica ...">` fino al suo `</div>` di chiusura:

| Box attuale (titolo) | Riconoscibile da | Sostituire con |
|---|---|---|
| I nostri dipendenti | `dip-gruppo.png` | `[[$kosDipendenti_it]]` |
| Le nostre strutture | `le-nostre-strutture.png` | `[[$kosStrutture_it]]` |
| Attività del gruppo | `customer-chart` / questionari | `[[$kosAttivita_it]]` |
| Ricavi | `ricavi.png` | `[[$kosRicavi_it]]` |

Nella pagina **EN** lo stesso con i chunk `_en` (titoli: *Our employees, Our facilities, Group activities, Revenues*).

Alternativa con un'unica chiamata valida per entrambe le lingue: `[[$kosDipendenti_[[++cultureKey]]]]` ecc. — **solo** se `cultureKey` vale `it`/`en` nei rispettivi contesti (verifica: `<!-- [[++cultureKey]] -->` nel sorgente). In caso di dubbio usare i nomi espliciti.

⚠️ Il `<div class="row infografiche-container">` che contiene i box **resta**. L'errore più frequente in test è stato un `</div>` in più/in meno, che fa uscire un box dalla riga.

---

## 6. Pubblicazione e verifiche

1. **Sito → Svuota cache** (svuota tutti i contesti: salvare la risorsa IT non svuota la cache EN).
2. Aprire IT ed EN con **Ctrl+F5**.

### 6.1 Verifica rapida in console (F12 → Console), su entrambe le pagine

```js
const row = document.querySelector('.infografiche-container');
console.table({
  'box nella riga (atteso 4)': row.querySelectorAll(':scope > .box-infografica').length,
  'box annidati (atteso 0)':   document.querySelectorAll('.box-infografica .box-infografica').length,
  'colonne dipendenti (5)':    document.querySelectorAll('.kos-chart__col[role=img]').length,
  'punti ricavi (5)':          document.querySelectorAll('.kos-area__dot').length,
  'righe strutture (8)':       document.querySelectorAll('.kos-kpi:not(.kos-kpi--att) .kos-kpi__item').length,
  'JS dipendenti caricato':    [...document.scripts].some(s => s.src.includes('kos-chart.js')),
  'JS ricavi caricato':        [...document.scripts].some(s => s.src.includes('kos-ricavi.js')),
});
```

Se i numeri non tornano → §7.

### 6.2 Verifica visiva

- [ ] **Dipendenti**: tab Tutti/ITA/GER cambiano le barre; tooltip al passaggio del mouse; Esc lo chiude.
- [ ] **Strutture**: lista con icone, numeri che contano all'ingresso; globo con 53 / 91.
- [ ] **Attività**: riquadro bianco con icona e numero.
- [ ] **Ricavi**: area che si srotola, 5 punti con valori.
- [ ] **EN**: tooltip *Female/Male*, numeri con la virgola (11,770 · 13,700), ricavi come **€780M**.
- [ ] **Mobile** (DevTools, 375px): nessuno scroll orizzontale, box uno sotto l'altro.
- [ ] Nessun errore rosso in console.

---

## 7. Problemi noti e soluzioni (emersi in test)

| Sintomo | Causa | Soluzione |
|---|---|---|
| Un box finisce sotto invece che a fianco | `</div>` in più nella sostituzione | Script §6.1; togliere il `</div>` in eccesso prima del box spostato |
| Grafico con griglia ma senza dati | Snippet restituisce `"years":[]` | Chiamata di diagnosi: `[[!kosChartRicavi? &resource=`ID` &debug=`1`]]` → mostra nome TV, risorsa e valore letto. Cause: cache EN, nome TV diverso, dati non salvati |
| Box vuoto, senza griglia | CSS/JS non caricati nel template di quella lingua | §2, verificare anche il template EN |
| Testi in italiano sulla pagina inglese | Caricato il chunk `_it` (cultureKey non differenziata) | Usare i nomi espliciti `_en` |
| Tooltip/numeri in formato italiano su EN | JS vecchio in cache o `lang` mancante | Cambiare `?v=`, verificare `<html lang="en">` |

---

## 8. Rollback

I PNG originali non sono stati cancellati, quindi il ritorno indietro è immediato:

1. Ripristinare nel contenuto/template il codice salvato al §0.3 (i box con le immagini).
2. **Sito → Svuota cache.**

Chunk, snippet, TV e file CSS/JS nuovi possono restare: senza le chiamate nella pagina non hanno alcun effetto.

---

## 9. Aggiornamento annuale (dopo la pubblicazione del nuovo Bilancio)

| Componente | Dove | Cosa fare |
|---|---|---|
| Dipendenti | TV `chartDipendenti` (pagina IT) | Aggiungere la riga del nuovo anno. Il più vecchio esce da solo (finestra di 5 anni) |
| Ricavi | TV `chartRicavi` (pagina IT) | Aggiungere la riga del nuovo anno |
| Strutture | chunk `kosStrutture_it/_en` | Aggiornare badge, valori lista, 53/91 sul globo |
| Attività | chunk `kosAttivita_it/_en` | Aggiornare badge e numero |

Poi **Sito → Svuota cache**. Nessun file grafico da rifare.

Formato numeri nei chunk: IT con punto (13.700), EN con virgola (13,700). Nelle TV: solo cifre.

---

## 10. Punti aperti (non bloccanti)

- **Contrasto colori** (WCAG 1.4.11): barre donne/totale dei dipendenti e area ricavi sotto 3:1. Colori ereditati dal design originale: decisione da sottoporre al cliente/designer.
- **Test con screen reader** reale (NVDA o VoiceOver) consigliato dopo la pubblicazione.
- **Icone ambulatori e questionari** ritagliate da screenshot: sostituirle con i sorgenti del designer quando disponibili (stesso nome file, 72×71 px e 111×111 px).
- **Globo nascosto su mobile** (come prima): il dato "91 strutture in Italia" non è visibile sotto i 992px. Per mostrarlo togliere `d-none d-lg-block` dalla colonna del globo nei chunk Strutture.
