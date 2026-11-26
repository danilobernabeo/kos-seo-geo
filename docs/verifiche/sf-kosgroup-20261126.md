# Scansione Screaming Frog completa di kosgroup.com (26/11/2026)

Uso interno. Configurazione `kos-completa`, 8.821 URL, scansione nella notte tra il 25 e il 26/11, dopo i rilasci del
25/11 (CSS critico KOS 15 con le due correzioni, `LinkFrammenti`, MarkerClusterer locale, galleria delle schede,
contenuti delle strutture, immagini ridotte, title delle paginazioni). Analisi della scansione eseguita prima delle
esportazioni (Esportazione multipla completa). Esportazioni sul PC di Danilo in
`screaming-frog\kosgroup\2026.11.26.05.48.42\` (fuori da git). Confronto con la scansione del 24/11
(`2026.11.24.07.31.59`, nota `docs/verifiche/sf-kosgroup-20261124.md`).

## Esito in breve

Nessuna regressione. 925 pagine HTML: 921 con risposta 200, tutte indicizzabili, più i 4 redirect attesi (radice e
`www` verso `/it`, `/ritiro-referti`, vecchia `/privacy/`). Nessun 4xx o 5xx interno; canonical uguale all'indirizzo
su tutte; un solo blocco JSON-LD su tutte le 921; 0 errori di dato strutturato; title entro 65 e description entro
160; nessun title duplicato, H1 unico e non vuoto; hreflang senza link di ritorno mancanti; nessuna pagina non
indicizzabile in sitemap. Ricerche nel codice a zero (resti di tag MODX, stage, Yext, `utm_`, `gclid`, `gad_source`,
CSS vecchi), salvo `lorem_ipsum` (anomalia 1 del 24/11, aperta) e il falso positivo noto di `link_relativi`.

Rispetto al 24/11: 3 pagine nuove (`approfondimenti/colonscopia-preparazione-dieta-e-come-si-svolge`, news
`kos-acquisisce-l-ospedale-maria-luigia-di-monticelli-terme-parma`, news
`salute-mentale-in-adolescenza-al-via-la-collaborazione-villa-dei-pini-kos-e-meyer`), tutte con un blocco JSON-LD,
`dateModified` e title in regola; nessuna pagina sparita; stessi codici, indicizzabilità, canonical, meta robots,
description e H1 su tutte le pagine comuni. Conteggi dei tipi del grafo invariati, salvo gli aumenti dovuti alle 3
pagine nuove (nessun tipo diminuito; `IndividualPhysician` 99, nodi struttura e reparti invariati).

## Anomalie del 24/11: stato

| # | Anomalia del 24/11 | Stato al 26/11 |
|---|---|---|
| 1 | Lorem ipsum nelle FAQ di `/it/lavorare-in-kos/formazione-kos-academy` | **Aperta** (contenuto da KOS) |
| 2 | Pagine in sitemap senza link interni | Risolta: restano solo le 3 orfane volute (`/it/villa-dei-pini`, `/it/parla-con-noi`, `/villamargherita`) |
| 3 | 826 link con il solo frammento letti come link alla home | Risolta con `LinkFrammenti`: da 826 a 22 link verso `https://kosgroup.com/` (301 → `/it`), tutti `href="#"` lasciati di proposito: «Cerca» della barra di prenotazione su 19 pagine (attivatore GTM), «ACCESS THE BOOKING PORTAL» su `/en/book-online` (2) e il pulsante disattivato «Iscrizioni chiuse» di un evento 2025. Più 21 link da PDF verso vecchi indirizzi (`http://www.kosgroup.com/`, `/ritiro-referti`, `/privacy/`) |
| 4 | Avviso Google: `department` di tipo `MedicalOrganization` dentro `Hospital` (Villa Margherita, 3 pagine) | Aperta (6 avvisi), proposta del 24/11 invariata |
| 6 | `dateModified` assente su 19 convenzioni | Risolta: tutte le 921 pagine 200 hanno `dateModified`; nessun `dateModified` precedente a `datePublished` |
| 7 | Metadati «Microsoft Word» del PDF `01-comitati-ospiti-aa.pdf` | Risolta. Restano 49 PDF `noindex` con titoli «Microsoft Word/PowerPoint …» (non indicizzabili, nessun effetto sui risultati) |
| 8 | 2 pagine indicizzabili fuori sitemap (`villa-san-giuseppe/storico-liste-di-attesa`, informativa tirocini 2023) | Aperta, decisione di KOS |
| 9 | 51 immagini sopra 200 KB | Risolta il 25/11; nuova: la foto della news Villa dei Pini e Meyer (218 KB, varianti WebP tutte sotto soglia) |
| 10 | Link esterni in 404 | In parte: spariti asst-pavia, bollini e il vecchio link Hopkins; nuovi 403 (Hopkins nuovo indirizzo, health.com: probabili blocchi dei robot) e il 410 di un'offerta di lavoro chiusa linkata da `/en/work-in-kos/current-openings`; restano AIFA, handylex e i link nei PDF |
| 11 | Tempo di risposta | Migliorato: pagine HTML mediana 0,152 s (24/11: 0,210), 10° percentile 0,115 s (0,167), minimo 0,080 s (0,133), 90° percentile 0,98 s (1,52). La parte calda torna vicina ai valori del 18/11 |
| 13 | 172 pagine con `og:image` generica | Invariata, fuori perimetro |
| 15 | Title delle paginazioni «\| Gruppo KOS» e refuso | Risolta (49 title cambiati in «… – Pagina N \| KOS») |

Le anomalie 5, 12, 14 e 16 non sono ricontrollate in questa nota.

## Anomalie nuove

| # | Pagine | Cosa | Causa | Correzione | Perimetro |
|---|---|---|---|---|---|
| 1 | `/en/work-in-kos/current-openings` | Link a un'offerta di lavoro su `lavoraconnoi.kosgroup.com` che ora risponde 410 | Offerta chiusa nel portale delle candidature | Togliere il link o aggiornare l'elenco (dal portale o dal manager) | contenuto, bassa |
| 2 | Barra di prenotazione (19 schede e `/it/prenota-online`), `/en/book-online`, evento 2025 | 22 `href="#"` che i crawler leggono come link alla radice con redirect | Scelta del 25/11 per non rompere gli attivatori GTM | Dopo la verifica: `<button>` o `role="button"` senza `href`, con l'attivatore GTM sul clic dell'elemento | dopo il 4/12 |
| 3 | `/it/ritiro-referti` | Due link verso `services.kosservizi.com/referti/…` rispondono 500 a Screaming Frog (anche il 24/11) | Servizio esterno dei referti, probabilmente non risponde a richieste senza sessione | Verifica dal browser; se il servizio funziona per gli utenti, nessuna azione | da verificare |
| 4 | 50 pagine di elenco paginate | Description uguale tra le pagine dello stesso elenco, con testi generici e fuori tono («la tua marca preferita», «settore IT») | Description dell'elenco ripetuta sulle pagine `?page=N` (già il 24/11) | Description propria per ciascun elenco, con «– Pagina N» sulle successive | contenuto, minore |

## Confermato

Hreflang coerenti; sitemap senza URL non indicizzabili; nessun redirect interno da link HTML diversi da quelli
dell'anomalia 2; 219 PDF indicizzabili fuori sitemap (comunicati e documenti pubblici, come il 24/11); `jsonld_tipi`
con i conteggi del 24/11 più le 3 pagine nuove.
