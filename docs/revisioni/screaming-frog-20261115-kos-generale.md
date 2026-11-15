# Screaming Frog 15/11/2026 · kosgroup.com · stato generale (SEO tecnico, contenuti, GEO)

Uso interno. Seconda analisi del revisore (Claude Fable 5.2) sulla scansione completa del 15/11/2026 (8.766 URL, 914 pagine HTML 200),
fuori dal perimetro delle note di consegna. Il confronto con le note è in `screaming-frog-20261115-kos.md`.

## Riepilogo

- **Fondamenta solide:** nessuna pagina HTML rotta o non indicizzabile per errore, canonical e hreflang puliti, sitemap coerente, URL ordinati, compressione e header di sicurezza di base presenti, crawler AI ammessi, contenuto leggibile senza JavaScript.
- **Punto tecnico più evidente:** le 379 pagine di articoli, news ed eventi rispondono in 1,1-1,2 s di mediana, contro 0,2 s del resto del sito.
- **Punto GEO più evidente:** gli orari. Nel dato strutturato vengono da Yext, nel testo dalla redazione, e sulle schede controllate spesso non coincidono o nel testo mancano. Un assistente AI può rispondere con l'orario sbagliato.
- **Contenuti medici senza firma:** i 215 approfondimenti sono firmati «Redazione Gruppo KOS», senza revisore; le 100 pagine delle cure non hanno autore, data né dato strutturato proprio.
- **Architettura:** schede, servizi e medici stanno entro 3-4 clic; gli approfondimenti arrivano a 14 clic e 100 su 215 hanno al massimo 2 link in entrata.
- **Accessibilità:** i report di Screaming Frog sono vuoti (la scansione era «Text Only», i controlli richiedono il rendering JavaScript); ci sono solo controlli statici su 69 sorgenti.

### Le 10 azioni a miglior rapporto beneficio/sforzo

| # | Azione | Beneficio | Sforzo | Voce d'audit |
| --- | --- | --- | --- | --- |
| 1 | Scrivere nel sorgente della home i numeri dei contatori (oggi «+0 Posti letto», il valore arriva da JavaScript) | GEO: i dati chiave del gruppo diventano leggibili | 1 ora | Fuori perimetro |
| 2 | Trovare la chiamata lenta del template degli articoli (379 pagine a 1,2 s) | SEO e utenti: tempo di risposta di 4 pagine su 10 | Mezza giornata di analisi | KOS 2 |
| 3 | Un solo dato per gli orari: stesso valore nel testo e nel JSON-LD, orario visibile su tutte le schede | GEO alta: risposte corrette su «quando è aperto» | 1-2 giorni più i dati di KOS (incontro del 19/11) | KOS 24, passaggio a RetailTune |
| 4 | `width` e `height` sulle immagini (mancano sul 98,9%) dallo snippet comune `immagine` | Utenti e SEO: stabilità visiva (CLS) | 1 giorno | Fuori perimetro (su anniazzurri.it è la voce 4) |
| 5 | Title dei 150 professionisti con ruolo e sede («Nome, ortopedico a Villa dei Pini \| KOS»): oggi 146 sotto 30 caratteri | SEO: ricerche per nome e specialità | Mezza giornata (script già esistente) | KOS 21-23, estensione |
| 6 | Nodo proprio sulle 100 pagine delle cure (`MedicalCondition`, `MedicalTherapy`, `MedicalProcedure`) con le FAQ già visibili | GEO: le cure diventano entità collegate alle strutture | 2-3 giorni | Fuori perimetro (già in piano «da valutare a consuntivo») |
| 7 | `Event` sui 19 eventi e `ItemList` sugli elenchi (strutture per tipologia, medici) | GEO e risultati avanzati | 1 giorno | Fuori perimetro (estensione di KOS 10) |
| 8 | Revisore medico sugli approfondimenti: la libreria ha già `reviewedBy`, le 150 schede dei professionisti esistono | E-E-A-T sui contenuti sanitari | 1 giorno più l'elenco di KOS | KOS 10, estensione |
| 9 | Collegare gli approfondimenti: blocco «Approfondimenti correlati» sulle pagine delle cure e indici per tema | SEO: 100 articoli escono dalla periferia del sito | 1-2 giorni | Fuori perimetro |
| 10 | Rivedere `Cache-Control: no-store` e cookie di sessione sulle pagine HTML | Prerequisito di Cloud Edge; navigazione avanti/indietro | Mezza giornata di analisi | KOS 2 (collegata), Cloud Edge |

## Metodo e limiti

- **Dati:** CSV della scansione (38 iniziali più 43 copiati dal computer di Danilo, tra cui `tutti_link_in_entrata.csv` con 154.152 link) e 69 sorgenti HTML (23 schede, almeno una pagina per sezione).
- **Non disponibili:**
  - report di accessibilità e PageSpeed vuoti (`accessibilità_tutti.csv`: 914 righe senza valori);
  - quasi duplicati non calcolati da Screaming Frog;
  - `testo_pagina/` non copiato (914 file): i controlli sul testo sono fatti sui 69 sorgenti;
  - `lastmod` della sitemap non presente nelle esportazioni.
- **robots.txt e llms.txt:** letti dalle copie del repository (`kosgroup/files/`), perché le richieste al sito sono state rifiutate dallo strumento.
- **Voci d'audit:** i file dell'audit KosGroup non sono in `docs/` e da qui non sono leggibili. Le voci citate sono quelle note dal repository (2, 3, 4, 6, 7, 8, 9, 10, 11, 12-13, 18, 19, 21-23, 24). «Fuori perimetro» va quindi riletto contro le voci 1, 5, 14-17 e 20, che non conosco.
- **Sforzo:** stima di massima, da confermare.

## Architettura e link interni

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo | Voce |
| --- | --- | --- | --- | --- | --- |
| Approfondimenti e news raggiungibili solo dalla paginazione | 220 pagine a 5 o più clic dalla home, fino a 14: 131 approfondimenti su 215, 62 news su 145, 26 pagine di paginazione. Es. `/it/news-e-media/approfondimenti/il-linfedema-post-oncologico` (14 clic, 2 link) | Media | SEO: scansione e peso interno bassi sui contenuti informativi | 1-2 giorni (indici per tema, correlati) | Fuori perimetro |
| Pagine con pochi link in entrata | Con al massimo 2 link unici: 100 approfondimenti, 87 news, 60 convenzioni su 60, 11 video, 9 eventi, 8 trattamenti di psichiatria, 3 professionisti | Media | SEO | Come sopra | Fuori perimetro |
| Approfondimenti poco collegati alle cure | Nei 215 articoli solo 60 link verso `/servizi-e-cure/` e 111 verso le schede; 352 verso altri articoli | Media | SEO e GEO: articolo, cura e struttura non formano un percorso | 1-2 giorni più redazione | Fuori perimetro |
| Blocco «Argomenti correlati» uguale ovunque | Le stesse 3 news (Villa dei Pini, Villa Sant'Alessandro) collegate da 407 pagine; non sono correlate al tema della pagina | Bassa | SEO: link interni spesi sempre sulle stesse 3 pagine | Mezza giornata (correlati per sezione o struttura) | Fuori perimetro |
| Schede, servizi, medici | Schede: 3 clic, mediana 58 link, minimo 4 (`poliambulatorio-parco-di-veio`, `poliambulatorio-valdaso`). Servizi: 2-4 clic. Medici: 3 clic, 3 link (elenco, scheda della sede) | — | Va bene | — | — |
| Pagine in sitemap senza link interni | 9, tra cui 5 news di Villa dei Pini, `/it/parla-con-noi`, `/it/villa-dei-pini` | Bassa | SEO | 1-2 ore | Fuori perimetro |
| Link verso pagine paginate | 313 link a `?page=N`, 46 pagine indicizzabili con canonical su sé stesse (deciso il 12/11) | Bassa | Nessuna azione | — | — |
| Ancore | Nel contenuto 14.732 link: 1.971 senza testo ma 1.962 con alt dell'immagine; 9 del tutto vuoti (4 video KOSplayers in home, 2 su `villa-dei-pini-civitanova`); «Leggi l'articolo» 217 volte, «clicca qui» 3 | Bassa | Accessibilità e SEO | 1 ora | KOS 7 (per gli alt) |
| Link interni verso redirect | 7 URL (già noti) più link ai domini storici nel contenuto (`sstefano.it` 3, `casadicuravillapini.it` 2, `neomesia.com` 1), che tornano sul sito con un 301 | Bassa | SEO | 1 ora | KOS 4 (collegata) |
| Catene di redirect | Nessuna interna; 988 esterne, 978 di YouTube | — | Va bene | — | — |
| Link esterni rotti o http | 14 con 404, 2 con 400, 2 con 500 (`services.kosservizi.com/referti/…` da `/it/ritiro-referti`, da provare con un browser); 54 URL esterni in http (salute.gov.it 19, normattiva.it 11), quasi tutti dentro PDF | Bassa | Utenti | 2 ore, più i PDF di KOS | Fuori perimetro |
| Elenco strutture generato da JavaScript | `/it/strutture`: 0 link alle schede nel sorgente (dati in `data-strutture.js`); le schede restano collegate da `/it/centri` e dalle pagine per tipologia | Bassa | GEO: la pagina «Strutture» è vuota per chi non esegue JavaScript | 1 giorno (elenco dal server), oppure nulla fino allo store locator | Linea C (RetailTune) |

## Contenuti

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo | Voce |
| --- | --- | --- | --- | --- | --- |
| Approfondimenti sanitari senza autore né revisore | 215 `Article` e 145 `NewsArticle` con `author` = ente; nel testo «Articolo scritto da Redazione Gruppo KOS»; nessun `reviewedBy`, nessuna pagina `MedicalWebPage` | Alta | E-E-A-T su contenuti YMYL: motori e assistenti AI pesano la firma medica | 1 giorno più l'elenco dei revisori da KOS | KOS 10, estensione |
| Pagine delle cure senza data né responsabile | 100 pagine `/servizi-e-cure/…`: nessuna data visibile, nessun autore; 57 senza H2 | Media | E-E-A-T, estraibilità | 1 giorno (data di revisione e responsabile clinico nel template) | Fuori perimetro |
| Contenuti datati | Approfondimenti pubblicati da più di 3 anni: 79 (63 da più di 5); news: 26. Su 33 approfondimenti vecchi `dateModified` è novembre 2026 per il solo aggiornamento di title e description | Media | SEO e GEO: contenuto medico vecchio presentato come aggiornato | Revisione editoriale (KOS); 2 ore per non toccare `dateModified` sui ritocchi ai metadati | Fuori perimetro |
| Schede dei professionisti povere | 150 pagine, mediana 161 parole, 60 sotto 150; title «Nome \| KOS» (146 sotto 30 caratteri); H1 uguale al title su 149 | Media | SEO: ricerche «dottor X + specialità + città» | Mezza giornata per i title; i testi sono di KOS | KOS 21-23 e KOS 11, estensione |
| Tecnologie e convenzioni sottili | Tecnologie: 21 su 23 sotto 300 parole (mediana 188), title di 13 caratteri. Convenzioni: 16 su 60 sotto 150 parole, title di 23 caratteri | Bassa | SEO | Contenuti di KOS; title da script | KOS 21-23, estensione |
| Description generiche | 311 su 914 iniziano con «Scopri…»; chiusure «Visita ora!» (8), «Contattaci ora» (6); una nomina «il brand Kos» (`/it/centri/santo-stefano-mede`) | Bassa | SEO: clic dai risultati | 1 giorno di riscrittura validata | KOS 21-23 (già trattata, secondo giro) |
| Versione inglese ridotta | 19 pagine `/en` (istituzionali) contro 895 italiane; hreflang corretti; 41 link dalle pagine inglesi a pagine italiane, di cui 20 verso `/it` | Bassa | Utenti esteri; nessun errore tecnico | Scelta di KOS | Fuori perimetro |
| Pagine quasi vuote | `/it/villa-dei-pini` (38 parole); `/it/villa-margherita` (127); `/it/ritiro-referti` (141 parole, 895 link in entrata) | Bassa | SEO, utenti | 2 ore più i testi | Fuori perimetro |
| Titoli nel contenuto | Salti di livello in 46 sorgenti su 69; il titolo del banner «Diventa un KOSplayer» compare tra le intestazioni del contenuto in 53 su 69 | Bassa | Estraibilità, accessibilità | Mezza giornata (banner come paragrafo) | Fuori perimetro (voce 6 su anniazzurri.it) |
| Testo segnaposto | «Lorem ipsum» in due FAQ di `/it/lavorare-in-kos/formazione-kos-academy` | Bassa | Utenti | 10 minuti (KOS) | — |

Non rilevati: pagine HTML con contenuto identico (nessun hash duplicato), title o description duplicati fuori dalla paginazione, pagine 200 vuote.

## GEO: visibilità negli assistenti AI

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo | Voce |
| --- | --- | --- | --- | --- | --- |
| Orari diversi tra testo e dato strutturato | Su 5 schede confrontate. Cardinal Ferrari: testo «lunedì-sabato 8:00-20:00», JSON-LD 07:30-20:30 più un giorno 09:00-20:00. Punto Prelievi Biemme: testo prelievi 07:30-09:30 e 07:30-10:00, JSON-LD 07:30-12:30 e 15:00-16:00. Valdaso: 10:00-18:00 contro 09:30-18:30 (corretto dopo la scansione) | Alta | GEO: risposta sbagliata a una domanda frequente | 1-2 giorni più i dati | KOS 24, RetailTune |
| Orari assenti dal testo | Su 21 schede con nodo, in 7 la ricerca nel testo non trova orari della struttura (Casa Cima, Casa Sampeyre, Istituto Santo Stefano, Myolab, Villa Rosa, Clinica dentale Villalba; su Casa Villa Maura solo quello del centro diurno). 11 schede hanno nel JSON-LD 08:00-20:00 sette giorni su sette | Media | GEO | Blocco orari dal server, dalle stesse TV | KOS 24, estensione |
| Contatori della home a zero | Nel sorgente `<span class="counter-to" num-finale="13000">0</span>`: senza JavaScript si legge «+0 Posti letto a disposizione»; lo stesso su `/en` | Media | GEO: numeri del gruppo non leggibili | 1 ora | Fuori perimetro |
| Pagine delle cure senza entità | 100 pagine `/servizi-e-cure/…` con il solo `WebPage`; le FAQ sono visibili ma non nel grafo; le schede citano le cure come `MedicalTherapy`/`MedicalCondition` senza `@id` | Media | GEO: la cura non è un'entità collegabile | 2-3 giorni | Fuori perimetro (in piano a consuntivo) |
| Eventi come articoli | 19 eventi tipizzati `Article`: senza `startDate` e `location` | Bassa | GEO, risultati avanzati | Mezza giornata più i campi data | Fuori perimetro |
| Elenchi senza `ItemList` | `/it/centri`, 12 pagine `/it/strutture/<tipologia>`, elenco dei medici: nessun elenco strutturato | Bassa | GEO | Mezza giornata | Fuori perimetro |
| Tecnologie senza nodo | 23 pagine con il solo `WebPage`; `MedicalDevice` è usato solo dentro 8 schede | Bassa | GEO | Mezza giornata | Fuori perimetro |
| Telefono di Villa Margherita | Psichiatria: testo 0444 1585888, JSON-LD 0444 15851 (deciso il 12/11: numero del profilo Google). Sulle altre 20 schede controllate il telefono coincide | Bassa | Coerenza NAP | Scelta di KOS | KOS 11 |
| Indirizzi scritti in due forme | Myolab: testo «Viale Don Minzoni, 29A», JSON-LD «Viale Don Giovanni Minzoni 29/A»; Biemme: «Via Giacomo Matteotti 9» nel JSON-LD | Bassa | Coerenza NAP | 1 ora | KOS 24 |
| llms.txt | 79 link, tutti 200; tutte le 63 schede presenti; nessun professionista, 3 sole pagine delle cure, nessuna indicazione di orari o prenotazione | Bassa | GEO | 2 ore (sezioni «Cure» e «Prenotare») | KOS 18, estensione |
| Email | Scritte in entità HTML (59 nel campione), non con JavaScript: leggibili da chi decodifica l'HTML | — | Va bene | — | — |

Bene per il GEO:
- il testo delle pagine è nell'HTML del server (mediana 603 parole);
- telefono, indirizzo, «Come raggiungerci» (in auto e con i mezzi) e direzione sanitaria sono testo su tutte le 23 schede;
- FAQ visibili su schede e pagine delle cure;
- prestazioni prenotabili nel catalogo di 16 strutture;
- robots.txt con il solo gruppo `*`, senza blocchi ai crawler AI.

## Tecnico

| Rilievo | Numeri ed esempi | Gravità | Impatto | Sforzo | Voce |
| --- | --- | --- | --- | --- | --- |
| Template degli articoli lento | Tempo di risposta mediano: approfondimenti 1,21 s (151 su 215 oltre 1 s), news 1,19 s (105 su 145), eventi 1,07 s; schede 0,26 s, servizi 0,20 s, medici 0,18 s, video 0,18 s. Massimo 2,04 s (`…/approfondimenti/psicologo`) | Alta | SEO e utenti; causa da cercare (chiamata non in cache nel template, non vista dalla scansione) | Mezza giornata di analisi | KOS 2 |
| Immagini senza dimensioni | 4.136 immagini su 4.183 usate senza `width`/`height`; nel campione 2.544 tag su 3.077 | Media | Utenti e SEO (CLS) | 1 giorno | Fuori perimetro |
| HTML non memorizzabile | `Cache-Control: no-store, no-cache, must-revalidate` e cookie `PHPSESSID` su 914 pagine su 914 | Media | Niente cache intermedia né bfcache; blocca il beneficio di Cloud Edge | Mezza giornata di analisi (sessione anonima di MODX) | KOS 2, Cloud Edge |
| Molti file JS e CSS | 14 script locali in fondo alla pagina (jQuery, Popper, Bootstrap, Swiper, PhotoSwipe…), senza `defer`, e 10 fogli di stile nell'head, su ogni pagina; nessun `preconnect` | Media | Utenti (tempo di caricamento su mobile) | 1-2 giorni (unione e `defer`), con collaudo visivo | KOS 2 |
| Pagine pesanti | `/it/prenota-online` 760 KB di HTML (113 KB compressi); `/it/centri/villa-dei-pini-civitanova` 396 KB (catalogo di 274 prestazioni nel JSON-LD); mediana del sito 88 KB | Bassa | Utenti | Da valutare: catalogo su richiesta | KOS 24 |
| Immagini pesanti e formati | 47 sopra 200 KB (massimo 1,8 MB, 7 foto di medici, `hero-bg-home.png` 1,3 MB); 2.665 WebP su 4.183; 52 pagine senza sorgenti WebP, 43 senza immagini `lazy` | Bassa | Utenti | Mezza giornata (terzo giro di ottimizzazione) | KOS 2 |
| CSP assente | Nessun `Content-Security-Policy`. Presenti: HSTS `max-age=31536000` (senza `includeSubDomains`), `X-Content-Type-Options: nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` | Bassa | Sicurezza, nessun effetto SEO | 1-2 giorni (CSP in sola segnalazione) | Fuori perimetro |
| `Permissions-Policy` con Yext | `geolocation=(self "https://cdn.yextapis.com")` su tutte le risposte | Bassa | Da aggiornare con RetailTune (già nel registro) | 10 minuti | Linea C |
| `target="_blank"` senza `noopener` | 8 link su 1.641 nel campione; 4 pagine segnalate (`sicurezza_link_crossorigin_non_sicuri.csv`: `/it/contattaci`, posizioni aperte) | Bassa | Sicurezza | 30 minuti | Fuori perimetro |
| Contenuto misto | Nessuna risorsa http nelle pagine; solo link http dentro 17 PDF verso `http://www.kosgroup.com/` | Bassa | — | Rigenerare i PDF (KOS) | KOS 8 |
| URL | Pagine HTML: nessuna maiuscola, nessun underscore, nessuno slash finale incoerente; 114 oltre 115 caratteri; 10 alias con la lettera accentata caduta (`…-perch-…`, `comorbilit-…`). Risorse: 87 con maiuscole, 25 con spazi, 22 con underscore | Bassa | SEO marginale | Non toccare gli URL esistenti; regola per i nuovi | Fuori perimetro |
| Risorse bloccate da robots.txt | 60 URL, tutti moduli con `?formId=`; nessun CSS, JS o immagine bloccati | — | Va bene | — | KOS 19 |
| 3 immagini senza risposta | «Connessione rifiutata» su 3 file (elenco nel primo rapporto): da ricontrollare | Bassa | Utenti | 10 minuti | — |
| Sitemap | 866 URL tutti 200; nessuna sitemap delle immagini; `lastmod` non verificabile dalle esportazioni | Bassa | SEO | 2 ore (immagini nella sitemap delle schede) | Fuori perimetro |

Non rilevati: pagine 200 sospette (soft 404), catene di redirect interne, parametri fuori dalla paginazione, pagine HTML non compresse.

## Accessibilità

I report di Screaming Frog non contengono dati: i controlli axe richiedono il rendering JavaScript e la scansione era «Text Only». Per averli serve una scansione con rendering JavaScript e l'opzione di accessibilità, anche solo sulle 28 pagine del campione.

Controlli statici su 69 sorgenti (indicativi, non sostituiscono axe):

| Rilievo | Numeri | Gravità | Impatto | Sforzo |
| --- | --- | --- | --- | --- |
| Pulsanti senza testo | 39 `<button>` su 214 senza testo interno (da verificare se hanno `aria-label`) | Media | Utenti con lettore di schermo | 2 ore |
| `iframe` senza `title` | 8 su 34 (mappe, video) | Bassa | Lettore di schermo | 1 ora |
| Salti di livello nei titoli | 46 pagine su 69 | Bassa | Navigazione per titoli | Mezza giornata |
| Pagine senza `main` | 14 su 69 senza `role="main"` o `<main>` | Bassa | Navigazione per aree | 1 ora |
| Immagini senza attributo `alt` | 17 su 2 pagine (intero sito, dalla scansione) | Bassa | Lettore di schermo | 30 minuti |
| Link senza testo né alt | 9 (intero sito) | Bassa | Lettore di schermo | 30 minuti |

Presenti: `lang` su tutte le pagine (it su 895, en su 19), link «Vai al contenuto principale» su 914 pagine, `nav` con etichetta sulle briciole, campi dei moduli con `id`, nessun blocco dello zoom.

L'accessibilità non è tra le voci note dell'audit: fuori perimetro. `/it/dichiarazione-di-accessibilita` esiste: conviene confrontarla con una scansione axe.

## Cosa va bene

- **Risposte:** 914 pagine HTML tutte 200 e indicizzabili; 2 soli 404 interni (un'immagine e un link dentro un PDF); pagina 404 vera.
- **Canonical, hreflang, sitemap:** canonical uguale all'URL ovunque; 19 coppie it/en reciproche; sitemap di 866 URL senza redirect né errori.
- **Meta:** title e description presenti e unici; un solo title oltre 65 caratteri; nessuna description oltre 160; un H1 per pagina.
- **Dato strutturato:** un blocco per pagina, nessun errore schema.org; schede, professionisti, articoli e video tipizzati; nessun microdato o RDFa residuo.
- **Rete:** gzip su HTML, CSS e JS; cache di 180 giorni e versione `?v=` sulle risorse statiche; HSTS, `nosniff`, `X-Frame-Options`, `Referrer-Policy`.
- **URL:** minuscoli, con trattini, senza slash finale; nessun parametro oltre `?page`.
- **Crawler AI:** robots.txt senza blocchi; llms.txt con 79 link validi; contenuto nel sorgente senza JavaScript; nessun resto di Yext, stage o `utm_`.
- **Schede:** nome, indirizzo, telefono, come arrivare e mappa come testo; prenotazione online con link diretto per prestazione su 16 strutture.
