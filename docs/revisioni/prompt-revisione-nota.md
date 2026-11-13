# Revisione indipendente di una nota di consegna (da lanciare in Claude Code)

Uso interno. Da lanciare in Claude Code sul computer di Danilo, dalla cartella del repository, con un modello diverso
da quello che ha scritto la nota (`/model`). Il revisore non modifica nulla: produce un rapporto in
`docs/revisioni/<nota>-revisione-<AAAAMMGG>.md`.

Prompt (sostituire `<NOTA>` con il percorso della nota, es. `docs/docx/Nota di consegna dello sprint 2.docx` o
`docs/riferimenti/nota-di-consegna-sprint-1.md`):

---

Sei il revisore indipendente di una nota di consegna destinata a Gruppo KOS e alla sua agenzia SEO (Cerved/ProWeb),
che verificherà il lavoro. Lingua: italiano. Non modificare nessun file del repository e non toccare i siti: solo
letture e richieste HTTP in GET, al massimo una richiesta al secondo (protezione anti-bot di MODX Cloud).

Nota: <NOTA> (se è un .docx, estrai il testo con pandoc o python-docx).

Fonti: CLAUDE.md (convenzioni vincolanti e regole sui documenti condivisi), docs/registro-decisioni.md,
docs/piano-sprint-1.md, i documenti delle singole voci in docs/, il codice in shared/, kosgroup/, anniazzurri/, gli
script e i rapporti in scripts/collaudo/ e docs/evidenze/, i due audit nel progetto (se disponibili).

Controlla:
1. Ogni affermazione fattuale (numeri, date, conteggi, proprietà schema.org, URL, voce d'audit) è sostenuta dalle
   fonti? Segnala ciò che non trovi o che le contraddice.
2. Stato attuale dei siti: per ogni «Come verificare», apri l'URL indicato (curl o browser) e controlla che la pagina
   oggi corrisponda a quanto scritto (dato strutturato nel sorgente servito dal server, intestazioni dei PDF,
   canonical, title). Segnala le differenze, comprese quelle dovute a modifiche successive.
3. Coerenza interna (stessi numeri ovunque, sintesi e sezioni allineate).
4. Correttezza tecnica delle scelte e delle motivazioni sugli scostamenti (schema.org, documentazione Google),
   verificata sulle pagine ufficiali.
5. Contenuti che non devono stare in un documento per KOS e l'agenzia: proposte fuori piano o a consuntivo, dettagli
   interni, nomi non necessari, toni difensivi, promesse non dimostrabili.
6. Verificabilità: ogni voce ha un modo concreto di essere controllata dall'agenzia?

Rapporto (max ~700 parole) in docs/revisioni/: problemi in ordine di gravità, ciascuno con citazione breve, perché è
un problema, evidenza (comando e output o fonte), correzione proposta; distingui «errore» da «da verificare»; poi un
elenco breve di ciò che hai verificato come corretto.
