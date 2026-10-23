#!/bin/sh
# AA-1 / AA-17 — genera in background le varianti WebP e le larghezze delle immagini di assets/img (o della cartella
# indicata), senza fermarsi quando la sessione SSH si chiude. Sul cloud non ci sono nohup né ps.
# Uso:   sh ~/kos-seo-geo/scripts/immagini/varianti-in-background.sh [cartella] > /dev/null 2>&1 < /dev/null &
# Stato: grep -c varianti ~/varianti.log; tail -2 ~/varianti.log   (finito quando l'ultima riga è «FINE ...»)
trap '' HUP
cd "$(dirname "$0")/../.." || exit 1
if ! mkdir ~/varianti.lock 2>/dev/null; then
  echo "già in corso (o interrotto: rimuovere ~/varianti.lock)" >> ~/varianti.log; exit 1
fi
echo "INIZIO $(date)" > ~/varianti.log
php scripts/immagini/ottimizza-immagini.php /www "${1:-assets/img}" --solo-varianti --soglia=0 --scrivi >> ~/varianti.log 2>&1 < /dev/null
echo "FINE $(date) (uscita $?)" >> ~/varianti.log
rmdir ~/varianti.lock
