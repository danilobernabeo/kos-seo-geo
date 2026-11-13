# Revisione indipendente: Nota di consegna dello sprint 2

Uso interno. Revisione del 13/11/2026 su `docs/docx/Nota di consegna dello sprint 2.docx` (datata 6/11). Modello del revisore: Claude Fable 5.2. Metodo: testo estratto con pandoc; confronto con piano, registro, documenti di voce e rapporti in `scripts/collaudo/`; 23 richieste GET alla produzione (una ogni 1,3 s) alle pagine dei «Come verificare»; documentazione Google. Limiti: i due audit non sono nel repository (`../materiali/audit` è vuota), quindi le richieste dell'audit sono lette dai documenti interni; Rich Results Test e PageSpeed non rieseguiti (solo le schermate del 6/11).

## Problemi, in ordine di gravità

**1. Errore — manca lo scostamento AA 3 (PDF).** «192 indicizzabili, 216 esclusi con X-Robots-Tag: noindex» e «In cinque punti la forma scelta differisce».
L'audit chiede `noindex, nofollow` su tutti i PDF (`docs/pdf/brief-pdf.md`, riga 29); la nota ne lascia 192 indicizzabili e usa il solo `noindex`. Lo stesso brief (riga 32) dice che «la nota di consegna lo motiva in una riga»: la riga non c'è. È il punto che l'agenzia noterà per primo, su una voce a priorità alta.
Correzione: sesta riga negli Scostamenti (carte dei servizi e pieghevoli restano indicizzabili perché sono la fonte più completa su servizi, tariffe e accesso; `noindex` senza `nofollow`, con il motivo) e «sei punti». Da verificare sull'audit se vale anche per KOS 8.

**2. Errore — le landing ripetono il nodo, non lo «puntano» soltanto.** «Le 5 landing puntano alla scheda della struttura invece di descriverla di nuovo»; «Le landing non descrivono più la struttura per conto proprio».
Nel sorgente di `/it/landing/cardiotac-parma` il nodo `…/centri/centro-cardinal-ferrari#localbusiness` è presente per intero (20 proprietà, catalogo compreso), come previsto da `docs/kos-24.md` («ripetuto per intero»). Chi apre il sorgente legge il contrario della nota.
Correzione: «le landing riportano lo stesso nodo della scheda, con lo stesso `@id`: un'entità sola, non una seconda descrizione».

**3. Errore — «21 carte dei servizi».** «Il 4/11 sostituite 21 carte dei servizi».
`docs/nota-consegna-pdf.md`, riga 33: 20 carte sostituite più un pieghevole nuovo (Hospice Beati Coniugi Martin); 21 sono i file.
Correzione: «20 carte dei servizi e un pieghevole nuovo».

**4. Errore — «79 immagini pesanti ottimizzate» (KOS 2).**
Gli elenchi `kos-2-immagini-pesanti.txt` (50) e `kos-2-immagini-giro-2.txt` (29) hanno 6 file in comune: le immagini distinte sono 73.
Correzione: «73 immagini (79 ottimizzazioni in due giri)» o solo «73».

**5. Da verificare — orario della verifica di anniazzurri.it.** «del 6 novembre (8:30)».
`scripts/collaudo/verifica-20261106/risultati.json`: avvio 06:29 UTC (7:29), file scritto alle 8:16. Gli esiti (33/3/1/1, 453 pagine) coincidono. Correggere l'orario o indicare il rapporto giusto, se ne esiste un altro.

**6. Verificabilità debole su quattro voci.**
- AA 3: «su una carta dei servizi… su un modulo interno», senza URL. Proposti (verificati oggi): `…/residenza-anni-azzurri-vico-mercati/Carta%20dei%20servizi.pdf` (nessun `X-Robots-Tag`) e `…/PARM%202025.pdf` (`noindex`).
- KOS 8: un solo esempio. Aggiungere `…/casa-villa-maura/contratto.pdf` (`noindex`, verificato).
- KOS 21-23: nessun URL di esempio.
- AA 6: «nessun "Guarda" dentro un titolo» è falso alla lettera: su Vico Mercati c'è l'H2 legittimo «Guarda il video della residenza». Scrivere «nessun "Guarda meno / Guarda di più"».
- AA 17: «immagini in WebP» vale per testata e schede; su Vico Mercati restano un PNG e un JPG (25 e 16 KB). Tenere solo «nessuna sopra 200 KB».

**7. Differenze dovute a modifiche successive (non errori al 6/11).**
Le sitemap hanno oggi 866 URL (kosgroup.com) e 457 (anniazzurri.it), contro 865 e 453. Vico Mercati ha ora il nodo `#localbusiness` (sprint 3). Villa dei Pini Civitanova non espone `medicalSpecialty`, che la nota elenca tra i campi del nodo: da verificare se è atteso. Una riga («conteggi al 6/11») evita contestazioni.

**8. Esempio fuori dai controlli dichiarati.** La landing `cardiotac-parma`, citata due volte, ha una description di 173 caratteri e il title senza « | KOS». Non è nella sitemap, quindi «description entro 160» resta vero sulle 865 pagine, ma è la pagina che l'agenzia aprirà. Correggere la description o dire che le landing sono fuori dalla sitemap.

**9. Formulazioni non dimostrabili.** «un assistente AI… trova la prestazione, la struttura e il link» e «i motori e i sistemi AI ora le leggono»: meglio «può trovare» e «sono leggibili nell'HTML». «L'obiettivo della voce è raggiunto in tutti» è un giudizio che spetta a chi verifica: toglierlo.

**10. Motivo più forte disponibile (FAQPage).** La nota motiva con i blocchi scritti a mano. Google ha tolto il risultato FAQ dalla Ricerca dal 7 maggio 2026 (pagina FAQPage di Google Search Central; anche `docs/kos-24.md`, riga 15): conviene citarlo.

## Verificato come corretto

- Verifica kosgroup.com del 6/11 alle 10:43: 38 OK, 6 informativi, 1 attenzione, 0 errori; 865 pagine; 12 user agent, tempi 0,155-1,245 s; 25 pagine dopo JavaScript; 543 PDF collegati.
- Collaudo voce 24: 63 righe OK (58 schede, 5 landing).
- Oggi in produzione: un solo JSON-LD su tutte le pagine aperte; ente `https://kosgroup.com/#organization` con partita IVA, codice fiscale, `subOrganization`, `parentOrganization` CIR; «KOS Group» su `/en`; briciole JSON-LD uguali a quelle visibili; nessuna briciola sulla landing.
- Tipi delle quattro schede campione (`MedicalClinic`, `Hospital`, `DiagnosticLab` + `MedicalBusiness`, `Dentist`); catalogo del Centro Cardinal Ferrari: 106 prestazioni, branca per branca come in tabella; `ReserveAction` presente.
- PDF: Biemme senza `noindex` e con `Link` canonical; conteggi 678/245/433/15/242/6 e 408/192/216/25/186/97+2.
- KOS 2: LCP 4,5 → 2,9 s, immagini 15,8 → 5,2 MB, 369 ms, 4.768 larghezze; `<picture>`, WebP e `loading="lazy"` su «La nostra storia».
- Title e description: 82, 310, 4, 5 H1, 19 pagine; AA 89, 90 (83/80), 6, circa 110; 157 clic; title di afasia a 67 caratteri; 6 title AA tra 66 e 70.
- AA 1, 6, 17: `<img>` con alt e WebP su `/rsa-in-lombardia`; nessun H2 ripetuto; 101 immagini, 51,4 → 13,2 MB, 1.613 e 4.442.
- `priceRange` è consigliato, non obbligatorio (documentazione Google LocalBusiness). Schermata del Rich Results Test della landing coerente con la tabella (le altre quattro non aperte).
- Date di rilascio della sintesi; KOS 10 in produzione dal 1/11.
