# Screaming Frog 18/11/2026 · kosgroup.com · salute generale del sito

Uso interno. Controllo generale sulla stessa scansione di `screaming-frog-20261118-kos.md`, avviata il 17/11 sera: 8.801 URL trovati, di cui 5.847 interni e 916 pagine HTML con risposta 200. Le voci fuori dagli audit sono indicate come «miglioria», da consuntivare a parte. Non compaiono nei documenti per KOS o per l'agenzia finché non sono decise.

## Quadro sintetico

| Area | Stato | Dato |
| --- | --- | --- |
| Risposte | Buono | Interni: 5.775 risposte 200, 10 redirect 301 (tutti voluti), 2 errori 404 (da link dentro un PDF e da un'immagine), 60 URL bloccati da robots.txt come previsto. Nessun 5xx interno |
| Indicizzabilità | Buono | 916 HTML indicizzabili, canonical uguale all'URL su tutte; sitemap di 868 URL, tutti 200 e indicizzabili. Fuori sitemap: 46 pagine `?page=N` e 2 pagine nascoste nel menu (storico liste d'attesa di Villa San Giuseppe, informativa tirocini 2023), come il 15/11 |
| Dato strutturato | Buono | 1 JSON-LD per pagina, 0 errori. Avvisi facoltativi: `priceRange`, `geo` e orari sulle attività locali citate; `image` su 8 articoli; `duration` e `creator` sui 21 video |
| Title e description | Buono | 0 title duplicati; description duplicate in 5 gruppi (news); 4 description sotto i 70 caratteri, 1 oltre i 160 (`/villamargherita`) |
| Immagini | Da migliorare | 50 immagini oltre 200 KB (47 il 15/11; nuove le foto di Villa Margherita e la news MOC); 4.173 senza attributi `width`/`height` |
| Link | Discreto | 10 pagine raggiungibili solo dalla sitemap; 3 link vuoti (I2); 6 link relativi; 23 link interni verso redirect (18 dagli elenchi degli approfondimenti verso 6 articoli in 301, 5 verso `beata-corte`); 42 link esterni in errore 4xx, 2 in 5xx |
| Prestazioni | Buono al momento della scansione | Mediana 0,12 s, 75° percentile 0,22 s (cache scaldata). Intestazioni `no-store, no-cache` e cookie `PHPSESSID` su tutte le pagine HTML. Le risposte 404 non hanno le intestazioni di sicurezza |
| Sicurezza | Buono | HSTS, `nosniff`, `SAMEORIGIN` e referrer policy su tutte le risposte del sito. `Content-Security-Policy` assente: miglioria, da valutare con Cloud Edge |

## Da correggere (piccoli, nel perimetro o a costo trascurabile)

| Rilievo | Pagine | Evidenza | Proposta |
| --- | --- | --- | --- |
| Link vuoti nelle «aree di intervento» | 3 | Filottrano e San Severino («Medicina dello Sport»), Ospedale San Pancrazio («Riabilitazione da dipendenze»); link verso la home | Collegare la voce alla pagina servizio o mostrarla senza link (I2 del rapporto sugli sprint) |
| Link relativi | 1 pagina, 6 link | `…/approfondimenti/sclerosi-multipla-tra-ricerca-cura-e-riabilitazione` | `href="/it/…"` nel contenuto |
| Link a pagine inesistenti | 2 | `istruzioni-per-il-primo-accesso-al-dossier-sanitario.pdf` → `/ritiro-referti` (404); Settimana del cervello 2025 → `banner-settimana-del-cervello-2025-mobile.jpg` (404) | Redirect 301 `/ritiro-referti` → `/it/ritiro-referti` (regola web); immagine mobile da ricaricare o sorgente da togliere |
| Link interni verso redirect | 23 | Elenchi degli approfondimenti verso 6 articoli rinominati (301); 5 riquadri «Beata Corte» verso `corte-orero` | Link alla destinazione finale (contenuto e TV) |
| Commenti interni nell'HTML | 13 sorgenti su 50 | Commenti «Extra delle schede struttura … @graph», «Barra di ricerca Yext Answers … linea C», «Extra delle landing … voce 24» | Commenti MODX `[[- ]]` (regola del progetto) |
| Villa Margherita, nodo | 1 | `description` con «Chiama lo 0444 043322», `telephone` +39 0444 15851 | Togliere il numero dalla descrizione del nodo |

## Contenuti (di KOS, da segnalare)

- **Testo segnaposto** «Lorem ipsum» in 2 FAQ di `/it/lavorare-in-kos/formazione-kos-academy` (invariato dal 15/11).
- **Pagine senza link interni** (in sitemap):
  - 5 news di Villa dei Pini del 2026, che non compaiono negli elenchi;
  - 2 progetti speciali del 2025;
  - `/it/parla-con-noi`, `/it/villa-dei-pini` (38 parole) e `/villamargherita`.
  - Le news vanno capite: forse sono escluse dagli elenchi per categoria o data.
- **Professionisti senza specialità** (Calorio, Ali): da aggiungere alla nota dello sprint 3, che cita solo i refusi nei ruoli. Tipo dell'Ospedale San Pancrazio (`MedicalClinic`, elencato tra le cliniche psichiatriche): domanda per KOS.
- **Articoli senza immagine** (8), con og:image predefinita: file dell'immagine da ricaricare.
- **Pagine con poco testo:** 27 sotto le 100 parole, di cui 15 convenzioni e 11 pagine di professionisti. Sono pagine di servizio: da arricchire solo dove servono alla ricerca.
- **Link esterni rotti:** 42 in 4xx, quasi tutti dentro PDF o verso siti istituzionali che rispondono 403 ai crawler (salute.gov.it, agenziafarmaco.gov.it). In pagine HTML sono 9 su 7 pagine; due sono 404 veri (la scheda Santo Stefano Mede verso asst-pavia.it e una news del 2024). I 2 in 5xx sono le pagine dei referti di `services.kosservizi.com`, che rispondono 500 al crawler: da provare con un browser, perché `/it/ritiro-referti` ha 895 link in entrata.
- **PDF:** 1 indicizzabile con titolo «Microsoft Word - 1 - COMITATI OSPITI_AA_31.01.24» (`/assets/doc/comunicati/2024/01-comitati-ospiti-aa.pdf`); 27 URL con caratteri non ASCII o spazi nel nome (3 PDF e 24 immagini).

## Migliorie (fuori dalle voci, a consuntivo)

1. **Immagini:** 50 sopra 200 KB, fino a 1,8 MB (foto dei medici `giacomo-brocani.jpg`, `cristina-beretta.jpg`, `leila-bakdounes.jpg`, `hero-bg-home.png` 1,3 MB, galleria di Villa Sant'Alessandro). Si può usare lo stesso tetto di peso di AA 17 (`scripts/immagini/tetto-peso.php`). In più, `width` e `height` sulle immagini dei template, per il CLS.
2. **Cache HTML:** `Cache-Control: no-store, no-cache, must-revalidate` e `PHPSESSID` su tutte le 916 pagine HTML (e sulla 404). Nessun proxy o CDN può tenere in cache le pagine. Da rivedere prima di Cloud Edge, insieme alla CSP (regola del progetto: rivedere intestazioni prima di attivarlo).
3. **Profondità della scansione:** 77 pagine a 10 clic o più dalla home, fino a 14. Sono news e approfondimenti raggiungibili solo scorrendo la paginazione. Servono archivi per anno o categoria, oppure link «correlati» nelle news (come la paginazione news AA, già fuori piano).
4. **Paginazione:** i title delle pagine `?page=N` finiscono con «| Gruppo KOS» invece di «| KOS», e `og:url` punta alla prima pagina mentre il canonical punta alla pagina stessa (46 pagine). È coerente con la scelta di non indicizzarle in sitemap, ma va uniformato al formato dei title.
5. **og:image predefinita** sulle 43 pagine di cure di psichiatria e trattamenti di riabilitazione: testata da riattivare o immagini dedicate (C3 del 15/11).
6. **Configurazione di Screaming Frog** (`docs/verifica-screaming-frog.md`):
   - XPath delle briciole per kosgroup.com (`//nav[@id="breadcrumbs"]`);
   - avviare «Analisi scansione» prima dell'esportazione, altrimenti i filtri Sitemap restano vuoti;
   - chiave PageSpeed per il campione.

## Confronto con il 15/11

| Indicatore | 15/11 | 18/11 |
| --- | --- | --- |
| Pagine HTML 200 | 914 | 916 |
| Errori del dato strutturato | 308 pagine | 0 |
| Immagini senza attributo `alt` | 17 | 0 |
| Foto delle strutture con alt vuoto negli elenchi | 49 | 0 |
| Immagini con alt vuoto (tutte, comprese icone) | 274 | 225 |
| Schede struttura senza nodo | 3 | 0 |
| Pagine orfane (solo sitemap) | 9 | 10 |
| Immagini oltre 200 KB | 47 | 50 |
| Link esterni 4xx | 43 | 42 |
| Tempo di risposta, 75° percentile | 1,18 s | 0,22 s |

## Revisione indipendente

Verifica di Fable del 18/11 in `docs/revisioni/screaming-frog-20261118-kos-revisione.md`; rettifiche riportate sopra (link interni verso redirect, commenti interni, link esterni rotti in HTML, conteggi minori).
