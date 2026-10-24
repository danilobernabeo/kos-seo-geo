#!/bin/sh
# File statici non trovati (404) dal log degli errori di nginx, dopo una data. Sola lettura. Shell BusyBox, awk POSIX.
# Uso: sh scripts/checks/log-404-file.sh <log> <AAAA/MM/GG> [n righe, default 40]
#   sh scripts/checks/log-404-file.sh /log/nginx_error.log 2026/10/18
# Riepilogo per tipo (immagini, PDF, altro) e i file più richiesti; il log di accesso di MODX Cloud non è attivo,
# quindi le pagine (gestite da MODX) non compaiono: qui solo i file che nginx cerca su disco.
LOG=${1:-/log/nginx_error.log}
DA=${2:?data di partenza AAAA/MM/GG}
N=${3:-40}
TMP=${TMPDIR:-/tmp}/log404.$$
# solo righe dalla data in poi; il log è in ordine di tempo, si salta fino alla prima riga utile
# righe utili con la data davanti (data<TAB>file), poi solo i file
awk -v da="$DA" '$1 >= da && /open\(\) "/ && /No such file or directory/' "$LOG" \
  | sed -n 's/^\([0-9/]*\) .*open() "\/c[0-9]*\/www\([^"]*\)".*/\1	\2/p' > "$TMP.g"
cut -f2 "$TMP.g" > "$TMP"
TOT=$(wc -l < "$TMP" | tr -d ' ')
echo "== 404 su file statici dal $DA: $TOT richieste ($(sort -u "$TMP" | wc -l | tr -d ' ') file diversi)"
echo "-- per tipo"
awk '{ t="altro"; l=tolower($0)
       if (l ~ /\.(jpe?g|png|gif|webp|svg)$/) t="immagini"; else if (l ~ /\.pdf$/) t="pdf";
       else if (l ~ /(favicon|apple-touch-icon)/) t="icone del sito"; c[t]++ }
     END { for (k in c) printf "%8d  %s\n", c[k], k }' "$TMP" | sort -rn
echo "-- per giorno: totale, icone del sito, immagini, pdf"
awk -F'	' '{ g=$1; l=tolower($2); t[g]++
              if (l ~ /(favicon|apple-touch-icon)/) i[g]++
              else if (l ~ /\.(jpe?g|png|gif|webp|svg)$/) m[g]++
              else if (l ~ /\.pdf$/) p[g]++ }
            END { for (g in t) printf "%s %6d %6d %6d %6d\n", g, t[g], i[g]+0, m[g]+0, p[g]+0 }' "$TMP.g" | sort
echo "-- primi $N file"
sort "$TMP" | uniq -c | sort -rn | head -n "$N"
echo "-- prima e ultima riga considerate"
awk -v da="$DA" '$1 >= da && $1 ~ /^20[0-9][0-9]\// { if (!p) { print $1, $2; p=1 } l=$1" "$2 } END { print l }' "$LOG"
rm -f "$TMP" "$TMP.g"
