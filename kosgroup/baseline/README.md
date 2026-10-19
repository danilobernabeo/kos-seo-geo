# Baseline kosgroup.com

Istantanea degli elementi del cloud di produzione (c0095) presa il 19/10/2026 con `scripts/export-elements.php`, prima di qualsiasi modifica del progetto. Sola consultazione.

Valori segreti lato server sostituiti con un segnaposto (restano invariati sul sito):
- `snippets/syncYoutubeVideos.php`: chiave YouTube Data API;
- `snippets/arca24Import.php`: token Bearer di Arca24.

Le chiavi pubbliche lato browser (Google Maps, Yext, reCAPTCHA site key) sono lasciate: compaiono già nel sorgente delle pagine.
`site_url` in `settings.json` vale `http://localhost/` perché l'export gira da riga di comando.
