# KOS 7 · AA 9 · Testi alternativi delle immagini

Voci degli audit: KosGroup settembre 2026, slide 20 (pattern per le immagini strategiche e non decorative, priorità
media); Anni Azzurri aprile 2026, slide 25 (alt descrittivi su tutte le immagini rilevanti, dopo il lavoro sulle
immagini di sfondo AA-1).

## Misura dell'8/11 (pagine di produzione)

Controllo con Claude in Chrome di tutte le pagine della sitemap (kosgroup.com 865, anniazzurri.it 454) e delle 36
landing AA fuori sitemap: per ogni immagine, alt assente, vuoto o scritto.

| Sito | Immagini | Senza alt | Con alt vuoto (foto) | Note |
| --- | --- | --- | --- | --- |
| kosgroup.com | 26.168 | 339: 336 segnaposto dei medici nelle schede, 3 frecce | 5.627 su 894 file | 874 foto delle strutture (gallerie ed elenchi), 351 testate di articoli e news, 78 foto delle pagine istituzionali; il resto è decorativo |
| anniazzurri.it | 11.332 e landing | 10: 9 icone e la foto della direttrice di Volpiano | 18 foto in 3 news | dopo AA-1 e gli alt delle news del 27/10 |

Su anniazzurri.it la voce è quasi chiusa dagli interventi di ottobre. Su kosgroup.com le immagini senza alt sono
poche; il problema è l'alt vuoto su foto di contenuto, cioè la mancanza dei pattern che l'audit chiede.

## Pattern nel codice (pacchetto `kosgroup/deploy/kos-7-alt.json`)

| Immagini | Dove | Alt |
| --- | --- | --- |
| Gallerie delle strutture (874 occorrenze con gli elenchi) | snippet `galleryStruttura` | «nome della struttura – foto N di T» (TV `str.nome` o titolo della pagina) |
| Foto delle strutture negli elenchi | chunk `listaStrutture`, `servizi-centri-row` | nome della struttura |
| Testata di articoli, news, eventi e tecnologie | template `informativo`, `tecnologie` | titolo della pagina (prima `[[+introtext]]`, segnaposto inesistente: alt sempre vuoto) |
| Immagine di fondo pagina | chunk `imgFooter` | titolo della pagina (prima l'alt era il testo rotto «*pagetitle]]») |
| Freccia «Leggi l'articolo» | chunk `news-struttura` | vuoto (decorativa) |

Restano con alt vuoto, perché decorative o già descritte dal testo accanto: avatar e foto del banner «KOSplayer»,
icone, separatori e sfondi, anteprime dei video accanto al titolo, loghi dei progetti speciali accanto al nome, foto
delle tecnologie nell'elenco (link con il nome, già `aria-hidden`).

## Immagini nei contenuti (`scripts/deploy/alt-contenuti.php`)

Regole per sito in `kosgroup/dati/alt/regole-contenuti.json` e `anniazzurri/dati/alt/regole-contenuti.json`: per ogni
immagine (src) l'alt da aggiungere se manca o, con `sostituisci_vuoto`, da mettere al posto di quello vuoto; un alt
già scritto non si tocca. Lingua per prefisso dell'URI (`it/`, `en/`). Prova, `--scrivi` con tabella di backup e
`ripristino.json`, `--ripristina`. La prova elenca anche le regole che non trovano l'immagine nei contenuti (immagine in
un template o in un chunk).

Testi scritti guardando le foto, senza nomi di persone (salvo la direttrice di Volpiano, nominata nella pagina):
**proposta da validare**. Le varianti mobili delle stesse foto hanno lo stesso testo.

### kosgroup.com (pagine istituzionali, italiano e inglese)

| Immagine | Alt (it) | Alt (en) |
| --- | --- | --- |
| `chi-siamo/2002-1.jpg` | Gruppo KOS, 2002: la nascita del Gruppo | KOS Group, 2002: the Group is founded |
| `chi-siamo/2002-2.jpg` | Gruppo KOS, 2002: la nascita del Gruppo | KOS Group, 2002: the Group is founded |
| `chi-siamo/2003-banner.jpg` | Gruppo KOS, 2003: le prime tre RSA in Liguria | KOS Group, 2003: the first three nursing homes in Liguria |
| `chi-siamo/2004-1.jpg` | Gruppo KOS, 2004: l'ingresso nella riabilitazione psichiatrica | KOS Group, 2004: entry into psychiatric rehabilitation |
| `chi-siamo/2004-2.jpg` | Gruppo KOS, 2004: l'ingresso nella riabilitazione psichiatrica | KOS Group, 2004: entry into psychiatric rehabilitation |
| `chi-siamo/2006-banner.jpg` | Gruppo KOS, 2006: l'acquisizione delle Residenze Anni Azzurri | KOS Group, 2006: acquisition of the Anni Azzurri residences |
| `chi-siamo/2007-1.jpg` | Gruppo KOS, 2007: la riabilitazione si rafforza con Santo Stefano | KOS Group, 2007: rehabilitation grows with Santo Stefano |
| `chi-siamo/2007-2.jpg` | Gruppo KOS, 2007: la riabilitazione si rafforza con Santo Stefano | KOS Group, 2007: rehabilitation grows with Santo Stefano |
| `chi-siamo/2009-banner.jpg` | Gruppo KOS, 2009: il Gruppo cambia nome e nasce KOS | KOS Group, 2009: the Group changes its name to KOS |
| `chi-siamo/2019-1.jpg` | Gruppo KOS, 2019: l'espansione in Germania con Charleston | KOS Group, 2019: expansion into Germany with Charleston |
| `chi-siamo/2019-2.jpg` | Gruppo KOS, 2019: l'espansione in Germania con Charleston | KOS Group, 2019: expansion into Germany with Charleston |
| `chi-siamo/oggi-banner.jpg` | Gruppo KOS oggi | KOS Group today |
| `home-img.jpg` | Persona che passeggia nel giardino di una struttura del Gruppo KOS | Person walking in the garden of a KOS Group facility |
| `img-laterale-1.jpg` | Una struttura sanitaria del Gruppo KOS | A KOS Group healthcare facility |
| `img-laterale-2.jpg` | Operatrice accanto a un'ospite | Care worker with a resident |
| `img-counter.jpg` | Ospite in carrozzina affacciata al balcone con un'operatrice | Resident in a wheelchair on a balcony with a care worker |
| `img-faq.jpg` | Paziente con esoscheletro e fisioterapista durante la riabilitazione | Patient with an exoskeleton and a physiotherapist during rehabilitation |
| `img-faq-2.jpg` | Operatrice accompagna un'ospite in carrozzina | Care worker assisting a resident in a wheelchair |
| `img-competenze-2.jpg` | Fisioterapista e paziente durante un esercizio con un dispositivo di riabilitazione | Physiotherapist and patient using a rehabilitation device |
| `img-competenze-1.jpg` | Paziente e terapista durante la riabilitazione del cammino | Patient and therapist during gait rehabilitation |
| `chi-siamo/cura-delle-persone-banner.jpg` | Terapista e paziente durante un esercizio di riabilitazione | Therapist and patient during a rehabilitation exercise |
| `chi-siamo/cura-di-te-1.jpg` | Bambini al tavolo durante un'attività di terapia | Children at a table during a therapy activity |
| `chi-siamo/cura-di-te-2.jpg` | Operatrice e ospite in carrozzina leggono insieme | Care worker and resident in a wheelchair reading together |
| `chi-siamo/sicurezza-nelle-cure-banner.jpg` | Operatrice sanitaria e ospite nel corridoio di una struttura | Healthcare worker and resident in a facility corridor |
| `chi-siamo/assistenza-personalizzata-1.jpg` | Paziente e terapista durante la riabilitazione del cammino | Patient and therapist during gait rehabilitation |
| `chi-siamo/assistenza-personalizzata-2.jpg` | Fisioterapista e paziente durante un esercizio con un dispositivo di riabilitazione | Physiotherapist and patient using a rehabilitation device |
| `chi-siamo/visione-valori-missione-1.jpg` | Riunione dell'équipe sanitaria | Healthcare team meeting |
| `chi-siamo/visione-valori-missione-2.jpg` | Operatrice accanto a un'ospite con deambulatore | Care worker with a resident using a walker |
| `chi-siamo/principi-banner.jpg` | Medici consultano insieme un tablet | Doctors reviewing a tablet together |
| `chi-siamo/ricerca-scientifica-banner.jpg` | Guanto robotico per la riabilitazione della mano | Robotic glove for hand rehabilitation |
| `chi-siamo/aggiornamento-continuo-1.jpg` | Paziente con esoscheletro e fisioterapista | Patient with an exoskeleton and a physiotherapist |
| `chi-siamo/aggiornamento-continuo-2.jpg` | Riabilitazione con la realtà virtuale | Virtual reality rehabilitation |
| `intro-pagina-imgs.jpg` | Fisioterapista e paziente durante un esercizio di riabilitazione | Physiotherapist and patient during a rehabilitation exercise |
| `intro-pagina-imgs-2.jpg` | Fisioterapista durante un trattamento | Physiotherapist during a treatment |
| `intro-pagina-imgs-3.jpg` | Operatrice accompagna un'ospite | Care worker assisting a resident |
| `intro-pagina-imgs-4.jpg` | Infermiera misura la pressione a un'ospite | Nurse measuring a resident's blood pressure |
| `kos-academy-2.jpg` | Operatori sanitari in aula durante un corso KOS Academy | Healthcare workers attending a KOS Academy course |
| `mychekup-analisi-banner.jpg` | Medica e paziente durante un colloquio | Doctor talking with a patient |

In più: `medico-thumb.svg` (segnaposto dei medici senza foto, 336 nelle schede struttura) con alt vuoto.
Esclusa la pagina Sostenibilità (lavoro extra `kos-sostenibilita`).

### anniazzurri.it

| Immagine | Alt |
| --- | --- |
| `dir-residenza-anni-azzurri-volpiano.jpg` | Stefania Sali, direttrice della Residenza Anni Azzurri Volpiano |
| `prestazioni-riabilitative-2.png` | (vuoto: decorativa) |
| `prestazioni-infermieristiche-2.png` | (vuoto: decorativa) |
| `prestazioni-specialistiche-2.png` | (vuoto: decorativa) |
| `supporto-socio-assistenziale-2.png` | (vuoto: decorativa) |
| `prestazioni-diagnostiche-2.png` | (vuoto: decorativa) |
| `supporto-psicologico-2.png` | (vuoto: decorativa) |
| `icon-esperienze-formative.svg` | (vuoto: decorativa) |
| `icon-ore-totali-formazione.svg` | (vuoto: decorativa) |
| `icon-collaboratori.png` | (vuoto: decorativa) |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-1.jpg` | Piantine in vaso su un tavolo, progetto Invecchiamento attivo della RSA San Giuseppe |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-2.jpg` | Operatrice e ospite compongono foglie su un telo durante un laboratorio |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-3.jpg` | Ospite e operatore osservano le piante nell'orto |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-4.jpg` | Ospiti e operatrice al tavolo del laboratorio con foglie e limoni |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-5.jpg` | Ospiti e operatori con i teli decorati davanti al porticato della residenza |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-6.jpg` | Telo decorato con le impronte delle foglie |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-7.jpg` | Ospiti e operatori al tavolo del laboratorio con le foglie |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-8.jpg` | Ospite in carrozzina accarezza le capre durante una visita alla fattoria |
| `news/484/rsa-sa-giuseppe-invecchiamento-attivo-9.jpg` | Ospite in carrozzina raccoglie le foglie di un albero in giardino |
| `news/524/grosseto-presentato-il-centro-diurno-maremma-01.jpg` | Intervento al microfono alla presentazione del Centro Diurno Anni Azzurri Maremma a Grosseto |
| `news/524/grosseto-presentato-il-centro-diurno-maremma-02.jpg` | Pubblico alla presentazione del Centro Diurno Anni Azzurri Maremma |
| `news/524/grosseto-presentato-il-centro-diurno-maremma-03.jpg` | Brochure del Centro Diurno Anni Azzurri Maremma |
| `news/524/grosseto-presentato-il-centro-diurno-maremma-04.jpg` | Interventi dei relatori alla presentazione del Centro Diurno Anni Azzurri Maremma |
| `news/524/grosseto-presentato-il-centro-diurno-maremma-05.jpg` | Autorità in visita al Centro Diurno Anni Azzurri Maremma |
| `news/529/kos-premiata-al-convegno-agire-2026-con-il-progetto-pannolone-intelligente-01.jpg` | Premiazione del progetto Pannolone intelligente al convegno AGIRE 2026 |
| `news/529/kos-premiata-al-convegno-agire-2026-con-il-progetto-pannolone-intelligente-02.jpg` | Consegna degli attestati ai premiati del convegno AGIRE 2026 |
| `news/529/kos-premiata-al-convegno-agire-2026-con-il-progetto-pannolone-intelligente-03.jpg` | Foto di gruppo dei premiati al convegno AGIRE 2026 |
| `news/529/kos-premiata-al-convegno-agire-2026-con-il-progetto-pannolone-intelligente-04.jpg` | Presentazione del progetto «Il pannolone intelligente: oltre il presidio, verso il servizio» al convegno AGIRE 2026 |

## Rilascio

Stage, poi produzione: prima la prova di `alt-contenuti.php` (pagine e immagini toccate), poi `--scrivi`; elementi con
`installa-elementi.php` e `kos-7-alt.json`. Dopo: stesso controllo dell'8/11 sulle pagine della sitemap.
