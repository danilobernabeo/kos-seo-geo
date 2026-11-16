# Migliorie proposte per kosgroup.com (fuori perimetro, a consuntivo)

Documento interno (non condividere con KOS o con l'agenzia; per il referente di Danilo solo dopo l'implementazione).
Fonte: scansione completa di Screaming Frog del 15/11/2026 (8.766 URL, 914 pagine HTML) e analisi del revisore
(`docs/revisioni/screaming-frog-20261115-kos-generale.md`). Per decisione di Danilo del 15/11 tutte le voci sono
**fuori perimetro**, anche quelle vicine a una voce d'audit (indicata solo come riferimento). Stime indicative in giornate
di lavoro, collaudo compreso; nessuna voce va avviata prima dell'approvazione.

## Priorità alta

| # | Intervento | Cosa abbiamo trovato | Beneficio | Stima | Dipende da |
|---|---|---|---|---|---|
| 1 | Orari: un solo dato per testo e dato strutturato | Su 5 schede confrontate il testo e il JSON-LD non coincidono (Cardinal Ferrari 8:00-20:00 contro 07:30-20:30; Biemme prelievi 07:30-10:00 contro 07:30-12:30). In 7 schede su 21 l'orario non è scritto nella pagina; 11 schede hanno 08:00-20:00 sette giorni su sette, probabilmente di comodo | GEO: gli assistenti AI rispondono con l'orario del dato strutturato o dei profili; un orario sbagliato è la risposta più dannosa | 1,5 (blocco orari nel template dalle TV `str.orari`, controllo di coerenza) | Orari confermati da KOS (incontro del 19/11), anagrafica RetailTune |
| 2 | Template degli articoli lento | Approfondimenti 1,21 s di mediana (151 su 215 oltre 1 s), news 1,19 s, eventi 1,07 s, contro 0,2 s di schede, servizi e medici | SEO e utenti: tempo di risposta di 4 pagine su 10 | 0,5 analisi più 0,5 correzione | Nessuna (rif. KOS 2) |
| 3 | Revisore medico sugli approfondimenti | 215 approfondimenti firmati «Redazione Gruppo KOS», nessun `reviewedBy`, nessun `MedicalWebPage` | E-E-A-T sui contenuti sanitari (YMYL): motori e assistenti AI pesano la firma medica | 1 (TV revisore, riga visibile «Rivisto da», `reviewedBy` verso `#medico` già supportato dalla libreria) | Elenco revisori di KOS (rif. KOS 10) |
| 4 | Contatori della home nel sorgente | Senza JavaScript si legge «+0 Posti letto a disposizione» (`num-finale="13000"`, testo 0), anche su `/en` | GEO: i numeri del gruppo non sono leggibili dai crawler AI | 0,1 | Nessuna |

## Priorità media

| # | Intervento | Cosa abbiamo trovato | Beneficio | Stima | Dipende da |
|---|---|---|---|---|---|
| 5 | Title dei professionisti con ruolo e sede | 150 pagine «Nome \| KOS» (146 sotto 30 caratteri), H1 uguale al title su 149, mediana 161 parole | SEO sulle ricerche «dottore + specialità + città» | 0,5 (script `aggiorna-campi.php`, dati già nelle TV ruolo e struttura) | Formula approvata (rif. KOS 21-23, KOS 11) |
| 6 | Pagine delle cure come entità mediche | 100 pagine `/servizi-e-cure/` con il solo `WebPage`; FAQ visibili ma fuori dal grafo; le schede citano le cure senza `@id` | GEO: la cura diventa un'entità collegata alle strutture che la trattano | 2,5 (già in piano a consuntivo dal 25/10, esteso a terapie e procedure) | Associazioni cure-strutture confermate da KOS |
| 7 | Data di revisione e responsabile sulle pagine delle cure | 100 pagine senza data né autore; 57 senza H2 | E-E-A-T ed estraibilità | 1 | Responsabili clinici da KOS; si fa con la 6 |
| 8 | Collegare approfondimenti e cure | 220 pagine a 5 o più clic (fino a 14); 100 approfondimenti con al massimo 2 link in entrata; nei 215 articoli solo 60 link verso le cure | SEO e GEO: articolo, cura e struttura diventano un percorso | 1,5 (blocco «Approfondimenti correlati» sulle cure dai tag, indici per tema) | Tag degli articoli (Tagger) da completare |
| 9 | «Argomenti correlati» davvero correlati | Le stesse 3 news collegate da 407 pagine | SEO: link interni oggi sprecati | 0,5 (correlati per sezione o struttura) | Si fa con la 8 |
| 10 | `width` e `height` sulle immagini | Mancano su 4.136 immagini su 4.183 | Utenti e SEO: stabilità visiva (CLS) | 1 (snippet comune `immagine` e plugin di caricamento) | Nessuna |
| 11 | HTML memorizzabile | `Cache-Control: no-store` e cookie `PHPSESSID` su tutte le 914 pagine | Prerequisito di Cloud Edge e della cache del browser (avanti/indietro) | 0,5 analisi più 0,5 configurazione | Decisione su Cloud Edge (rif. KOS 2) |
| 12 | Meno file JS e CSS, caricamento differito | 14 script senza `defer` e 10 fogli di stile su ogni pagina, nessun `preconnect` | Utenti su mobile, Core Web Vitals | 1,5 con collaudo visivo | Nessuna (rif. KOS 2) |
| 13 | Pulsanti senza testo accessibile | 39 `<button>` su 214 senza testo (da verificare `aria-label`) | Accessibilità | 0,3 | Nessuna |
| 28 | Font dal nostro dominio | Open Sans da `fonts.googleapis.com` (BaseTemplate, head formazione e gallery): connessione e CSS bloccante verso un terzo; segnalato anche nel riscontro di Cerved/ProWeb sullo sprint 1 (16/11) | Tempo di caricamento, privacy (nessuna chiamata a Google al caricamento) | 0,3 | Nessuna (rif. KOS 2) |

## Priorità bassa

| # | Intervento | Cosa abbiamo trovato | Beneficio | Stima |
|---|---|---|---|---|
| 14 | `Event` sugli eventi | 19 eventi tipizzati `Article`, senza `startDate` né `location` | GEO, risultati avanzati | 0,5 più i campi data |
| 15 | `ItemList` sugli elenchi | `/it/centri`, 12 pagine per tipologia, elenco dei medici | GEO | 0,5 |
| 16 | Nodo per le tecnologie | 23 pagine con il solo `WebPage`; tecnologie sottili (mediana 188 parole, title di 13 caratteri) | GEO | 0,5 |
| 17 | Elenco strutture nel sorgente | `/it/strutture` non ha link alle schede senza JavaScript | GEO | 1, oppure con il widget RetailTune |
| 18 | llms.txt più completo | Nessun professionista, 3 sole pagine delle cure, nessuna indicazione su orari e prenotazione | GEO | 0,3 |
| 19 | Sitemap delle immagini delle schede | Nessuna sitemap immagini | SEO immagini | 0,3 |
| 20 | Terzo giro sulle immagini | 47 sopra 200 KB (fino a 1,8 MB, 7 foto di medici, `hero-bg-home.png` 1,3 MB); 52 pagine senza WebP | Utenti | 0,5 |
| 21 | Banner «Diventa un KOSplayer» come paragrafo | Il suo titolo compare tra le intestazioni del contenuto in 53 pagine su 69; salti di livello in 46 | Estraibilità, accessibilità | 0,5 |
| 22 | Accessibilità minuta | 8 `iframe` senza `title`, 14 pagine senza `<main>`, 9 link vuoti (4 video in home), 17 immagini senza `alt` | Accessibilità | 0,3 |
| 23 | Sicurezza | CSP in sola segnalazione; HSTS senza `includeSubDomains`; 8 `target="_blank"` senza `noopener`; `Permissions-Policy` ancora con Yext (con RetailTune) | Sicurezza | 1,5 |
| 24 | Pagine povere da completare | `/it/villa-dei-pini` (38 parole), `/it/villa-margherita` (127), `/it/ritiro-referti` (141, con 895 link in entrata) | SEO, utenti | 0,3 più i testi |
| 25 | Link a domini storici e pagine senza link | `sstefano.it`, `casadicuravillapini.it`, `neomesia.com` nel contenuto (301); 9 pagine in sitemap senza link interni | SEO | 0,2 |
| 26 | Description generiche, secondo giro | 311 su 914 iniziano con «Scopri…»; chiusure «Visita ora!»; «il brand Kos» su Santo Stefano Mede; title di convenzioni e tecnologie da 13-23 caratteri | Clic dai risultati | 1 più validazione |
| 27 | Catalogo prenotazioni più leggero | `/it/prenota-online` 760 KB di HTML; Villa dei Pini 396 KB (274 prestazioni nel JSON-LD) | Utenti | da valutare |

Totale indicativo: priorità alta circa 4 giornate, media circa 12 (più la voce 28, aggiunta il 16/11; la sostituzione della ricerca Yext Answers è la linea C, `docs/ricerca/piano-prova-vertex.md`), bassa circa 9 (senza i testi e i dati a cura di KOS).

## Da segnalare a KOS (contenuti e dati, nessun lavoro nostro)

- Orari di ogni sede (voce 1) e telefono di Villa Margherita psichiatria (testo 0444 1585888, profilo 0444 15851).
- Indirizzi scritti in due forme (Myolab «Viale Don Minzoni, 29A» / «Viale Don Giovanni Minzoni 29/A»).
- «Lorem ipsum» in due FAQ di `/it/lavorare-in-kos/formazione-kos-academy`.
- 79 approfondimenti con più di 3 anni (63 oltre 5): revisione editoriale o data di revisione.
- Link esterni rotti (14 con 404, `services.kosservizi.com/referti/…` con 500 da `/it/ritiro-referti`) e link http nei PDF.
- Versione inglese ridotta a 19 pagine istituzionali: scelta da confermare.
- Marchi dismessi (Neomesia, Santo Stefano Riabilitazione, Polo Diagnostico) e stato di Myolab, Fidia, Villa dei Pini.
