#!/bin/sh
# rilascio-20261116.sh — rilascio dello sprint 3 sul cloud indicato, nell'ordine giusto, dalla cartella dell'archivio del
# repository. Shell BusyBox (ash): niente sintassi bash.
#
#   sh scripts/deploy/rilascio-20261116.sh aa            # solo prove (nessuna scrittura)
#   sh scripts/deploy/rilascio-20261116.sh aa --scrivi   # per ogni passo: prova; se pulita, scrittura; poi il passo dopo
#   sh scripts/deploy/rilascio-20261116.sh kos [--scrivi]
#
# Senza --scrivi i passi dopo il primo possono segnalare FERMO perché dipendono da quelli prima (es. le TV str.* create
# dal primo passo): vale la prova del primo passo. Con --scrivi ogni passo parte solo se la sua prova non segnala FERMO,
# ERRORE o MANCANTE; altrimenti si ferma e indica il passo. Ogni script scrive il proprio backup e ripristino.
SITO="$1"; MODO="$2"; W=/www
case "$SITO" in
  aa) PASSI='installa-elementi.php anniazzurri/deploy/aa-schede.json
imposta-tv.php anniazzurri/dati/schede/strutture-yext-2026-11-06.json
aa-yext-contenuti.php
installa-elementi.php anniazzurri/deploy/aa-schede-attivazione.json
installa-elementi.php anniazzurri/deploy/aa-4-cls.json
alt-contenuti.php anniazzurri/dati/alt/regole-contenuti.json' ;;
  kos) PASSI='installa-elementi.php kosgroup/deploy/kos-11.json
installa-elementi.php kosgroup/deploy/kos-7-alt.json
alt-contenuti.php kosgroup/dati/alt/regole-contenuti.json' ;;
  *) echo "Uso: sh scripts/deploy/rilascio-20261116.sh aa|kos [--scrivi]"; exit 1 ;;
esac
N=0
echo "$PASSI" | while read -r SCRIPT ARG; do
  N=$((N + 1))
  echo "=================================================================== $N. $SCRIPT $ARG"
  OUT=$(php "scripts/deploy/$SCRIPT" "$W" $ARG 2>&1); RC=$?
  echo "$OUT" | tail -n 40
  if [ "$MODO" != "--scrivi" ]; then continue; fi
  if [ $RC -ne 0 ] || echo "$OUT" | grep -q -E 'FERMO|ERRORE|MANCANTE'; then
    echo "!!! Passo $N fermo nella prova: nessuna scrittura da qui in avanti. Risolvere e rilanciare (i passi già scritti risultano «già aggiornato»)."
    exit 2
  fi
  echo "------------------------------------------------------------------- $N. scrittura"
  OUT=$(php "scripts/deploy/$SCRIPT" "$W" $ARG --scrivi 2>&1); RC=$?
  echo "$OUT" | tail -n 15
  if [ $RC -ne 0 ]; then echo "!!! Passo $N: errore in scrittura (codice $RC). Vedere il ripristino indicato sopra."; exit 2; fi
done || exit 2
echo "=================================================================== fine ($SITO $MODO)"
