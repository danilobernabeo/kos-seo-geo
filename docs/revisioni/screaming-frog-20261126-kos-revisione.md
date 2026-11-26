# Screaming Frog 26/11/2026 · kosgroup.com · revisione indipendente della nota

Uso interno. Revisione del 26/11/2026, ore 06:15 (Claude Fable 5.2), della nota `docs/verifiche/sf-kosgroup-20261126.md`. Ogni numero è ricalcolato con script propri sui 76 CSV della scansione del 26/11 e confrontato per pagina con i CSV del 24/11 (`interni_html`, `estrazione_personalizzata_tutti`, `codici_di_risposta_tutti`, `ricerca_personalizzata_tutti`, più `hreflang_tutti`, `interni_pdf`, avvisi del dato strutturato e sitemap, presenti nella stessa cartella). Per i pacchetti ho letto `git log` dal 24/11, i 9 manifest di `kosgroup/deploy/` nati dal 24/11 e le loro baseline. Richieste a kosgroup.com: 5 in tutto, tra le 06:01 e le 06:04, una al secondo (4 pagine e l'intestazione di `kos.min.css`), solo per confermare. Nessuna richiesta allo stage, nessun accesso SSH, nessuna modifica. Il docx non è stato generato (vincolo: un solo file nuovo nel repository).

## Riepilogo

1. **L'esito della nota regge: nessuna regressione dai rilasci del 25/11.** Sulle 918 pagine comuni alle due scansioni sono identici, pagina per pagina: codice, indicizzabilità, canonical, meta robots, description, H1, hreflang, `og:image`, nomi delle briciole JSON-LD, `#localbusiness`, `#department`, `#medico`/`#person`, `mainEntity`, `brand`, telefono, `datePublished`. Cambiano solo i 45 title delle paginazioni (voluto) e, nel grafo, 8 `ImageObject` in più e 19 `dateModified` in più (correzioni del 24/11).
2. **Il CSS critico non ha portato testo né resti di tag nelle pagine.** Il blocco `<style>` servito è identico, byte per byte, al chunk del repository; nessun `[[`, nessuna entità HTML dentro il CSS, `<head>` chiuso regolarmente; il conteggio delle parole è invariato su 458 pagine e ogni differenza sulle altre 460 è contenuto (N6).
3. **Nessun pacchetto di kosgroup.com ha installato una copia vecchia o un file diverso da quello in uso.** Ogni elemento ha un solo file sorgente in tutti i manifest; le baseline dei file modificati coincidono con la versione precedente in git. Una sola riserva, senza effetti: `kosSchema` del 24/11 ha portato su KOS anche la modifica alle briciole fatta per AA 8 (N7).
4. **Rilievo nuovo più importante, non dovuto al 25/11 e mai segnalato: 316 link rotti su 158 pagine.** Il banner «Diventa un KOSplayer» ha `href="//it/lavorare-in-kos/perche-lavorare-in-kos"`, che browser e crawler leggono come sito `https://it/…` (inesistente). Confermato in produzione alle 06:04. Correzione di una riga, da fare prima del 30/11 (N1).
5. **Il CSS critico pesa 94 kB per pagina** (19 kB compressi): l'HTML è raddoppiato (mediana 89 → 184 kB; trasferito 14,6 → 34,3 kB) e i meta `og:*` stanno ora a circa 97 kB dall'inizio. Non è un errore, ma va deciso se ridurlo (N2).
6. **La scansione ha tre punti ciechi su kosgroup.com**: non vede più `kos.min.css` (foglio nel body), l'estrazione `briciole_visibili` è vuota su tutte le pagine (classe che il sito non usa) e `link_relativi` gira ancora con la regola vecchia (905 falsi positivi che nascondono 2 link relativi veri) (N3, N4).
7. **Anomalie 5, 12, 14, 16 del 24/11**: 5 e 14 risolte con il pacchetto del 24/11; 12 invariata (139 pagine); 16 invariata (77 pagine, erano 76).
8. **Sei correzioni alla nota**, tutte minori (tabella): 45 title e non 49, 67 PDF e non 49, 18 schede e non 19, tipi del grafo non «invariati», anomalia 15 chiusa solo in parte, `href="http://` passato da 1 a 0.

## Correzioni alla nota

Esito: C confermato, D da correggere, S smentito, N non verificabile dai dati.

| Affermazione della nota | Esito | Evidenza |
| --- | --- | --- |
| 8.821 URL; 925 pagine HTML, 921 con 200 tutte indicizzabili, 4 redirect attesi | C | `codici_di_risposta_tutti` 8.821 righe; `interni_html` 921 + 4 (`/`, `http://www`, `/ritiro-referti`, `www…/privacy/`, tutti 301) |
| Nessun 4xx o 5xx interno | C | `interni_tutti`: 5.777 con 200, 4 con 301, 60 bloccati da robots.txt (moduli con `?formId=`) |
| Canonical uguale all'indirizzo su tutte | C | 921 su 921; anche `og:url` = canonical su 921 (il 24/11 differiva sulle paginazioni) |
| Un blocco JSON-LD su tutte, 0 errori | C | `jsonld_blocchi` = 1 su 921; errori Schema.org e Google 0; 395 pagine con soli avvisi facoltativi, stessi tipi del 24/11 |
| Title entro 65, description entro 160, nessun title duplicato, H1 unico | C | Massimi rispettati; 0 duplicati; `h1_tutti` occorrenze = 1 su 921 |
| Hreflang senza ritorni mancanti | C | 38 pagine (19 coppie) con `it`, `en`, `x-default`; reciproche, destinazioni 200, identiche al 24/11 |
| Ricerche nel codice a zero salvo `lorem_ipsum` e `link_relativi` | C, con aggiunta | Vero. In più `href="http://` è passato da 1 pagina a 0 (link alla biblioteca di Arco corretto il 25/11): la nota non lo dice |
| 3 pagine nuove, nessuna sparita | C | Le tre indicate; title 64, 64, 55 caratteri; `dateModified` ≥ `datePublished`; immagine propria |
| Stessi codici, canonical, meta robots, description, H1 sulle pagine comuni | C | 0 differenze su 918. Cambia l'H2-1 di `/it/news-e-media` (notizia in evidenza: contenuto) |
| «Conteggi dei tipi del grafo invariati, salvo le 3 pagine nuove» | D | Sulle pagine comuni c'è un `ImageObject` in più su 8 articoli (immagine di riserva del pacchetto del 24/11). Nessun tipo diminuito, `IndividualPhysician` 99: vero. Le pagine che toccano il limite di 50 valori di `jsonld_tipi` sono 17 (erano 14) |
| Anomalia 2: restano 3 orfane volute | C | `url_orfani_link_in_entrata`: `/it/villa-dei-pini`, `/it/parla-con-noi`, `/villamargherita` (erano 10) |
| Anomalia 3: da 826 a 22 link verso la radice; 21 link da PDF | C | 22 link HTML (19 «Cerca», 2 su `/en/book-online`, 1 «Iscrizioni chiuse»), 21 da 18 PDF |
| «Cerca» su 19 pagine; nell'anomalia nuova 2 «19 schede e `/it/prenota-online`» | D | Le pagine sono 19 in tutto: `/it/prenota-online` e 18 schede. Con `/en/book-online` e l'evento: 21 pagine |
| Anomalia 4: 6 avvisi `department` su 3 pagine | C | Identico al 24/11 |
| Anomalia 6: `dateModified` su tutte le 921; mai precedente a `datePublished` | C | 0 pagine senza; 0 casi invertiti; nessuna data prima del 2000 o nel futuro. Le 19 convenzioni hanno ora 18/01/2025 |
| Anomalia 7: «restano 49 PDF `noindex` con titoli Microsoft Word/PowerPoint» | D | Sono 67: 47 «Microsoft Word…» e 20 «…PowerPoint», come il 24/11. Titolo del comunicato corretto: confermato. PDF: 603, nessuno nuovo o tolto, 219 indicizzabili, 369 `noindex`, 15 con canonical HTTP |
| Anomalia 8: 2 pagine indicizzabili fuori sitemap | C | Le stesse due; le altre 45 fuori sitemap sono le paginazioni |
| Anomalia 9: risolta; nuova foto da 218 kB, WebP sotto soglia | C | 217.888 byte; WebP più pesante 176 kB. Altre due immagini sono appena sopra 200.000 byte ma sotto la soglia di Screaming Frog (204.800): `news/952/02-area-mindfulness-3.jpg` (203 kB), `bg-ospedale-san-pancrazio.jpg` (202 kB) |
| Anomalia 10: link esterni | C | Spariti asst-pavia, bollini, vecchio Hopkins, embed YouTube malformato, `cdn.rawgit.com`; nuovi 403 (Hopkins, health.com) e 410; restano AIFA, handylex, PDF |
| Anomalia 11: tempi (mediana 0,152 s, 10° percentile 0,115, minimo 0,080, 90° percentile 0,98) | C | Ricalcolati uguali. Oltre 1 s: 81 pagine (241 il 24/11); oltre 2 s: 1 (7) |
| Anomalia 13: 172 pagine con `og:image` generica | C | 172, stessa ripartizione |
| Anomalia 15: «Risolta (49 title cambiati)» | D | I title cambiati sono 45, tutti nel formato «… – Pagina N \| KOS». Restano però fuori regola, già citati il 24/11: `/it/mappa-del-sito` e l'articolo sulla neurodivergenza (senza marchio, title = H1), «\| Kos» su due pagine check-up. E `og:title` delle 45 paginazioni è rimasto quello della prima pagina (N5) |
| Anomalia nuova 3: due link a `services.kosservizi.com` con 500 | C | Presenti anche il 24/11 |
| Anomalia nuova 4: 50 pagine di elenco con description ripetuta | C | 5 gruppi, 50 pagine (5 elenchi e 45 paginazioni) |
| 219 PDF indicizzabili fuori sitemap; nessun altro redirect interno da link HTML | C | Ricalcolati |
| «Nessuna regressione» | C | Vedi riepilogo, punti 1-3. I rilievi sotto non nascono dai rilasci del 25/11, salvo N2 (scelta) e N3 (effetto sullo strumento) |

## Anomalie del 24/11 non ricontrollate dalla nota

| # | Anomalia | Stato al 26/11 | Evidenza |
| --- | --- | --- | --- |
| 5 | 8 articoli senza `image` (avviso Google Article) | Risolta con il ripiego | Avvisi `image` da 8 a 0; sugli stessi 8 articoli compare `ImageObject` con l'immagine generica. La copertina vera resta da chiedere a KOS |
| 12 | 139 pagine con meno di 200 parole | Invariata | 139: 113 sotto `/it/chi-siamo` (medici, tecnologie), 16 convenzioni (45-148 parole), minimo `/it/villa-dei-pini` (38) |
| 14 | `<img src="">` negli elenchi | Risolta | `img_lazy` aumentato su `news?page=15` e `approfondimenti?page=19, 20, 22, 24`; su `news?page=15` (richiesta delle 06:01) nessun `src=""`, al suo posto l'immagine generica |
| 16 | Pagine a livello 10 o più | Invariata | 77 (76 il 24/11), massimo 14, di cui 70 approfondimenti |

## Rilievi nuovi, in ordine di gravità

### N1 · Link rotto nel banner «Diventa un KOSplayer»: 316 link su 158 pagine

- **Cosa.** `<div class="banner-btn"><a href="//it/lavorare-in-kos/perche-lavorare-in-kos">`: con due barre l'indirizzo è «relativo al protocollo» e punta al sito `it`. Screaming Frog lo registra come `https://it/lavorare-in-kos/perche-lavorare-in-kos`, «Ricerca DNS non riuscita», 316 link in entrata (stesso numero il 15, 18 e 24/11). Chi clicca il banner arriva a una pagina di errore del browser.
- **Pagine.** 158: 38 di ambulatori e medicina specialistica, 30 di psichiatria, 25 di riabilitazione (due link per pagina), più alcune schede e `formazione-kos-academy`. Esempio confermato alle 06:04: `/it/servizi-e-cure/psichiatria/dipendenze` (due occorrenze).
- **Causa.** Chunk `kosplayer-banner-small` (`kosgroup/baseline/chunks/`, riga 16): `href="/[[~36]]"`. Da quando `link_tag_scheme` è `abs`, `[[~36]]` restituisce già `/it/…` e la barra scritta a mano la raddoppia. È l'unico caso nel repository (`grep '"/\[\[~'`).
- **Perché non è mai emerso.** Sta nell'elenco dei link esterni senza risposta, che le note non hanno letto; il 26/11 l'esportazione dei link in entrata per questi URL non c'è.
- **Correzione.** `href="[[~36]]"` nel chunk, come elemento statico con manifest e baseline; in cache, nessun tag nuovo. Controllo: 0 `href="//` nelle pagine.
- **Quando.** Prima del 30/11: è una riga in un chunk di contenuto, fuori da head, `scripts`, fogli e template sotto verifica, e un crawler dell'agenzia lo conta come 316 link rotti.

### N2 · CSS critico di 94 kB in ogni pagina

- **Cosa.** Il chunk `kos-css-critico` è 94.229 byte (19 kB compressi), cioè un quarto del foglio intero (485 kB, 75 kB compressi), ripetuto in ogni pagina e mai in cache del browser (l'HTML è `no-store`). HTML mediano da 89 a 184 kB; trasferito da 14,6 a 34,3 kB; rapporto testo/codice da 9,3% a 4,6%. Aumento uniforme: tra 94,0 e 95,5 kB su 901 pagine.
- **Effetti.** Nessuno su indicizzazione e dato strutturato. Tre cose da sapere: (a) title, description, hreflang e canonical restano nei primi 2 kB, ma i meta `og:*` e `twitter:card` vengono dopo il blocco, a circa 97 kB dall'inizio (Screaming Frog li legge su 921 pagine; per i lettori di anteprime che si fermano presto non ho una prova, in un senso o nell'altro); (b) gli strumenti di audit segnalano il rapporto testo/codice dimezzato e il peso dell'HTML; (c) il blocco usa la sintassi `@media (width<=991px)` (39 volte), che Safari prima della versione 16.4 (marzo 2023) e i browser più vecchi ignorano, mentre `kos.min.css` usa `max-width`: su quei browser la prima visualizzazione da mobile è senza le regole per lo schermo piccolo finché non arriva il foglio.
- **Causa.** Regole raccolte da tutte le 874 pagine e da tre larghezze in un solo blocco (registro, 25/11).
- **Correzione proposta.** Dopo la verifica: un blocco critico per famiglia di template (obiettivo 15-20 kB non compressi) e sintassi `max-width` nell'uscita di `costruisci.mjs`. Prima del 30/11, solo se si rilascia comunque qualcosa sull'head entro il 27/11: spostare il blocco `og:*` sopra `[[$kos-css-critico]]` e provare un'anteprima con gli strumenti di Facebook e LinkedIn. Altrimenti si lascia com'è: le misure del 25/11 sono buone e l'head è sotto verifica.
- **Quando.** Dopo il 4/12 (riduzione); la prova dell'anteprima si può fare subito, non tocca il sito.

### N3 · Punti ciechi della scansione dopo il 25/11

| Punto | Evidenza | Correzione (profilo `kos-completa`, nessuna modifica al sito) |
| --- | --- | --- |
| `kos.min.css` non è più tra gli URL scansionati | Il 24/11 c'era (`text/css`, 200); il 26/11 i CSS interni sono solo `kos-ricavi`, `kos-chart`, `kos-strutture`. Screaming Frog non segue il `<link rel="stylesheet">` nel body né il `preload`. La ricerca `css-bundle` trova comunque il nome su 921 pagine. Il foglio risponde 200 (06:01), 485.483 byte: i 485.468 del repository più 15 fine riga convertiti, cioè la versione del 23/11 | Aggiungere `https://kosgroup.com/assets/css/kos.min.css` ai controlli in modalità elenco, o una ricerca «Does Not Contain» su `rel="stylesheet" href="/assets/css/kos.min.css` |
| `briciole_visibili` vuota su 921 pagine (anche il 24/11) | Il sito usa `<nav id="breadcrumbs">` con `<a>` e `<span class="bc-attivo">`, non la classe `breadcrumb-item`. Il confronto tra briciole visibili e JSON-LD, previsto dalla guida, sui CSV non è possibile. Sulle 4 pagine richieste i due percorsi coincidono (nomi e indirizzi) | XPath per kosgroup.com: `//nav[@id="breadcrumbs"]/a \| //nav[@id="breadcrumbs"]/span[@class="bc-attivo"]`; riga dedicata nella guida |
| `link_relativi` con la regola vecchia | 905 pagine segnalate (902 il 24/11): sono le email offuscate. La regola nuova della guida, provata sulle 4 pagine richieste, dà 0 | Aggiornare l'espressione nel profilo salvato sul PC, come scritto nella guida il 24/11 |
| Link in entrata verso URL esterni senza risposta non esportati | Senza quel file N1 non si vede | Aggiungere all'esportazione «Nessuna risposta – link in entrata» |

Quando: prima della prossima scansione; nulla da rilasciare.

### N4 · Due link relativi veri, nascosti dai falsi positivi

- **Cosa.** `relativo_al_percorso_link_in_uscita` elenca 24 link: i 22 `href="#"` noti e due nuovi, «Scarica il programma completo» e «Scarica il programma», con `href` senza barra iniziale verso `assets/doc/eventi/settimana-del-cervello-2026-villa-sant-alessandro.pdf` e `…/settimana-del-cervello-2025.pdf`. Il conteggio di `link_relativi` su queste due pagine è salito di uno (4 → 5 e 1 → 2).
- **Pagine.** I due eventi «Settimana del cervello» 2025 e 2026 a Roma, salvati dal manager il 25/11 alle 08:54 e 08:55 (`dateModified`).
- **Causa probabile.** L'editor del manager riscrive i percorsi come relativi al salvataggio. Funzionano grazie a `<base href>`, ma violano la regola dei link assoluti.
- **Correzione.** `sostituisci-testo.php` sulle due risorse (`/assets/doc/…`); poi verificare l'opzione dei percorsi relativi dell'editor, perché ogni salvataggio può ripetere il caso.
- **Quando.** Prima del 30/11 se si fa insieme a N1 (solo contenuto); altrimenti dopo, senza rischi.
- Le 3.006 immagini con `src` relativo su 787 pagine sono il caso già noto (registro, 22/11).

### N5 · `og:title` delle paginazioni non allineato al title

- **Cosa.** Sulle 45 pagine `?page=N` il title è «News – Pagina 15 \| KOS» ma `og:title` resta «Ultime notizie e aggiornamenti \| KOS». Il pacchetto del 24/11 ha allineato title e `og:url`, non `og:title`.
- **Correzione.** Nel plugin `MultiPaginationCanonical`, stessa sostituzione anche su `og:title`. Insieme, la description con «– Pagina N» (anomalia nuova 4 della nota).
- **Quando.** Dopo il 4/12: minore, e tocca la testata delle paginazioni.
- Nota a margine, non dovuta al 25/11: il collegamento «Vai al contenuto principale» dei template (`[[~[[*id]]]]#main-content`) sulle paginazioni porta alla prima pagina dell'elenco (`/it/news-e-media/news#main-content` su `?page=15`). `LinkFrammenti` aggiunge `?page=N` solo ai link che riscrive. Accessibilità, da sistemare con lo stesso intervento.

### N6 · Differenze di testo tra le due scansioni: tutte spiegate

Nessuna azione. Serve a chiudere il controllo «contenuti visibili introdotti dal CSS critico».

| Differenza di parole | Pagine | Spiegazione |
| --- | --- | --- |
| 0 | 458 | Convenzioni, medici, servizi, pagine istituzionali, inglese: stesso testo con e senza CSS critico |
| +36 (e +3 frasi) | 398: home e tutti gli articoli di news, approfondimenti, eventi, video | Riquadro «Argomenti correlati» con l'ultima notizia e il suo riassunto (la news Meyer del 25/11); verificato su `/approfondimenti/fisiatria` |
| da +50 a +595 | 7 schede (Villa Sant'Alessandro, Villa dei Pini Civitanova, Cardinal Ferrari, Villa Armonia…) | `contenutiStruttura`: tutti i contenuti nella pagina, 9 visibili. Su Villa Sant'Alessandro l'HTML arriva a 362 kB |
| da −64 a +94 | 37 paginazioni ed elenchi | Scorrimento degli articoli per le 3 pagine nuove |
| −33, −26 | Posizioni aperte (it/en), `residenze-per-anziani` (2) | Offerta chiusa; due residenze in meno nell'elenco |

### N7 · Pacchetti dal 24/11: esito del controllo sulle copie

| Pacchetto | File installato | Baseline | Esito |
| --- | --- | --- | --- |
| `kos-correzioni-20261124` | `MultiPaginationCanonical.php`, `prepareNews.php`, `shared/snippets/seoSchema.php` | Uguali alla versione precedente in git (`51c9ecfc`) o alla copia di `kosgroup/baseline/`; per `kosSchema` la baseline è la versione del 15/11 (`01804522`), l'ultima installata su KOS | C con riserva: il file installato contiene anche la modifica di `7e5d32a` (AA 8, pagine escluse dalle briciole saltate solo come livelli intermedi), non citata nel manifest. Effetto su kosgroup.com: nessuno, briciole JSON-LD identiche su 918 pagine |
| `kos-contenuti-struttura-20261125` | `contenutiStruttura.php` (file nuovo nel repository) | Copia di `kosgroup/baseline/` del 5/11 | C. Non verificabile da qui che la produzione fosse ancora quella del 5/11 |
| `kos-markerclusterer-20261125` | `mappa-kos-nel-mondo.html`, 6 file nuovi | Uguale a `kosgroup/baseline/` | C. `markerclusterer.js` locale 200, `cdn.rawgit.com` sparito |
| `kos-link-frammenti-20261125` | `LinkFrammenti.php` (nuovo), `main.js` | `base-main.js` del pacchetto | C. `main.js` servito con versione nuova (`?v=1795599329`) |
| `kos-15-css-critico-20261125` e due correzioni | `head`, `scripts`, `informativo`, `tecnologie`, `kos-css-critico`, `kosDimInfoImage` | Ognuna uguale alla versione precedente in git (`1dea882e`, `f7c10797`, `d3a46ba2`, `a8cdbbcb`, `4878b1ce`, `e44698a1`) | C. Il chunk in produzione è l'ultima versione (seconda correzione), identico al repository |
| `kos-2c-webp-galleria-20261125` | Sola impostazione `kosseogeo.img_webp_css` | — | C. Blocco di 607 byte in pagina, `@media` a capo |
| `kos-barra-branca-20261125` (extra) | `barraPrenotazioneDBmedicoPrestazione.php` | `kosgroup/baseline/` del 22/11 | C. Il file è baseline + cache del 22/11 + `&branca`: nessuna parte persa. Rilascio previsto dal 27/11 |

Nessun elemento ha due file sorgente nei manifest di kosgroup.com. Restano le due copie doppie già segnalate il 25/11 (`head-extra-landing`, `head-extra-strutture` in `chunks/` e in `voce-24-attivazione/`): nessun pacchetto dal 24/11 le tocca.

## Cosa fare e quando

| Quando | Azione |
| --- | --- |
| Entro il 27/11 (prima della verifica del 30/11) | N1: `href` del banner KOSplayer. Facoltativo nello stesso giro: N4 (due link relativi) |
| Subito, senza toccare il sito | Prova di un'anteprima di condivisione (N2); profilo `kos-completa` aggiornato (N3) |
| Nella nota del 26/11 | Le sei correzioni della tabella; stato delle anomalie 5, 12, 14, 16; N1 tra le anomalie nuove |
| Dopo il 4/12 | N2 (CSS critico più piccolo), N5 (`og:title`, description e collegamento al contenuto sulle paginazioni), `href="#"` della barra (anomalia nuova 2 della nota) |
