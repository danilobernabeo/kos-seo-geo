# Baseline anniazzurri.it

Istantanea degli elementi del cloud di produzione (c0072) presa il 19/10/2026, aggiornata il 20/10/2026 (modifiche di ottimizzazione fatte fuori dal progetto) e il 22/10/2026 (riallineamento dello stage prima dello sprint 2: link assoluti, `link_tag_scheme` = abs, noindex per le pagine non ricercabili, fatti in produzione il 21/10) e il 31/10/2026 (dopo gli interventi della chat sulle prestazioni: nessun elemento del progetto cambiato in produzione; entrano le modifiche di contenuto ai chunk e alle TV e gli elementi nuovi dei lavori extra) con `scripts/export-elements.php`, prima di qualsiasi modifica del progetto. Sola consultazione.

Valori segreti lato server sostituiti con un segnaposto (restano invariati sul sito):
- `snippets/countContact.php`: chiave di cifratura.

Le chiavi pubbliche lato browser (Google Maps, Yext, reCAPTCHA site key) sono lasciate: compaiono già nel sorgente delle pagine.
`site_url` in `settings.json` vale `http://localhost/` perché l'export gira da riga di comando.
