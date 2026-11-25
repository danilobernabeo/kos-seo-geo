# Scansione Screaming Frog completa di anniazzurri.it (25/11/2026)

Uso interno. Configurazione `aa-completa` (0,5 URL/s), 7.852 URL, analisi della scansione eseguita prima delle
esportazioni (Esportazione multipla, tutte le schede, esportazioni di massa e report). Esportazioni sul PC di Danilo in
`screaming-frog\anniazzurri\2026.11.25.23.08.55\` (fuori da git). Confronto con la scansione del 23/11
(`2026.11.23.23.33.22`).

## Esito in breve

Una regressione grave, introdotta dal rilascio AA 8 del 24/11 e già corretta nel repository (anomalia 1). Per il resto
nessuna regressione: 466 pagine HTML (461 con risposta 200, 5 redirect http→https), 454 indicizzabili, nessun 4xx/5xx
interno, canonical uguale all'indirizzo su tutte le indicizzabili, un solo blocco JSON-LD su tutte le pagine (escluse le
5 dei virtual tour, `noindex`), 0 errori di dato strutturato, title entro 65 e description entro 160, nessun duplicato
di title, description o H1, nessuna immagine sopra 200 KB. Rispetto al 23/11: stessi codici, indicizzabilità, title,
description, H1, canonical e meta robots su tutte le pagine presenti in entrambe. Tempo di risposta delle pagine HTML:
mediana 0,17 s, 90° percentile 0,28 s (23/11: 1,11 s, cache svuotata dai salvataggi del pomeriggio).

## Anomalie

| # | Pagine | Cosa | Causa | Correzione | Perimetro |
|---|---|---|---|---|---|
| 1 | Tutte le schede residenza (57), reparti, 14 pagine provincia, servizi senza sede | Dal JSON-LD spariscono il nodo `#localbusiness` (`MedicalBusiness`/`MedicalOrganization`), i `#department`, gli `ItemList` delle province, i `Service` di RSA Aperta e Anni Azzurri a Casa, `brand`, orari e coordinate. Esempio: `/residenza-anni-azzurri-san-luca` ha solo `WebPage`, `BreadcrumbList`, `WebSite`, `Organization`. Conteggi dei tipi: `MedicalBusiness` 44 → 0, `ItemList` 14 → 0, `OpeningHoursSpecification` 65 → 0, `GeoCoordinates` 100 → 0 | Il pacchetto `aa-8-residenze-20261124` (briciole di `/residenze`) ha modificato e installato `anniazzurri/elements/snippets/aaSchema.php`, che è la versione precedente alle schede (baseline dei pacchetti `aa-schede`), invece di `anniazzurri/elements/aa-schede/aaSchema.php`, la versione in uso. Le briciole di `/residenze` sono corrette, ma tutto il grafo delle strutture è tornato allo stato di prima dello sprint 3 | Nuovo pacchetto `aa-8-correzione-schema-20261125`: la versione delle schede con la correzione delle briciole di AA 8. `snippets/aaSchema.php` riportato alla versione precedente (resta solo come baseline); manifest del 24/11 corretto | AA 2, 5, 10, 11, 21 e AA 8: **urgente, prima della verifica del 30/11** |
| 2 | `/oss-in-lombardia-corso-ial-e-assunzioni` | Non più scansionata: ora `noindex, follow` e fuori dalla sitemap (era la news orfana dell'anomalia 7 del 23/11) | Casella «Ricercabile» tolta dal manager | Nessuna, se voluto: coerente con la regola della sitemap | contenuto |
| 3 | `/sportello-sociale-orientamento-per-assistenza-anziani` | Pagina rinominata in `/sos-caregiver-orientamento-per-assistenza-anziani`: il vecchio indirizzo risponde 301 verso il nuovo; il nuovo è in sitemap, indicizzabile, con un blocco JSON-LD e collegato da 453 pagine | Modifica dal manager | Nessuna | contenuto |
| 4 | Link esterni | 404 ancora presenti: inps.it (`/cosa-dice-la-legge-104-sull-assistenza-dei-genitori-anziani`) e bollinirosargento.it (`/residenze-anni-azzurri-premiate-con-i-bollini-rosaargento`), dopo un redirect. Nuovi 403, probabili blocchi dei robot: allaboutcookies.org (`/privacy`), due DOI Wiley dentro un PDF delle news | Siti esterni cambiati | Aggiornare inps e bollini; i 403 solo dopo una prova dal browser | contenuto, bassa |
| 5 | `/privacy`, `/silver-economy-forum-anni-azzurri-kos-presente`, `/giornata-mondiale-dell-igiene-delle-mani`, `/kos-academy-lancia-il-corso-per-diventare-operatore-socio-sanitario-oss` | Link esterni in `http://` (guide ai cookie, silvereconomyforum.it, epicentro.iss.it, sstefano.it) | Testi vecchi | Portarli a `https://` | contenuto, bassa |
| 6 | 7 news | `utm_source=generator` negli indirizzi degli embed di Spotify | Codice di incorporamento di Spotify | Nessuna: sono iframe esterni, non link interni | noto |
| 7 | 150 link da PDF | Carte dei servizi e degli alloggi con link `http://anniazzurri.it/...` (Codice etico, convenzioni, Parco Sempione), che rispondono 301 | Contenuto dei PDF | Nessuna sul sito; alla prossima revisione delle carte | fuori perimetro |
| 8 | `/servizi-personalizzati`, `/riabilitazione-specializzata-post-ictus` | In sitemap senza link interni | Landing di campagna | Decisione di KOS, invariata | noto |

## Confermato

216 PDF `noindex` nella sitemap dedicata ai PDF (voluto, per la deindicizzazione); nessuna pagina HTML non indicizzabile
in sitemap; nessun link interno relativo, con `gclid`/`gad_source`, verso lo stage, Yext o il CDN delle icone; avvisi
di convalida solo facoltativi (74 `dateModified` raccomandato su `ScholarlyArticle`, 1 su `ProfilePage`, 1 su
`identifier` di `Person`).

Da ricontrollare dopo la correzione dell'anomalia 1: nodi `#localbusiness` uguali al canonical sulle 57 residenze,
`department`, `ItemList` delle 14 province, `Service`, `brand`, telefono e orari.
