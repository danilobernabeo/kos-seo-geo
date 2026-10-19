#!/bin/sh
# Analisi del log degli errori di nginx su MODX Cloud (shell BusyBox: awk POSIX, niente gawk).
# Uso: sh analisi-log.sh [file_log] [righe]
# Default: /log/nginx_error.log, ultime 200000 righe.

LOG="${1:-/log/nginx_error.log}"
N="${2:-200000}"
BOTS='client: (20\.171\.20[67]\.|52\.230\.152\.|74\.7\.(175|227|228|230|241|242|243|244)\.|132\.196\.86\.|172\.182\.20[27]\.|4\.227\.36\.|20\.125\.66\.|157\.55\.|207\.46\.|40\.77\.|52\.167\.)'
# Intervalli: OpenAI GPTBot (gptbot.json, ottobre 2026) e blocchi principali di Bingbot.

echo "== FILE"; ls -la "$(dirname "$LOG")"
echo "== PERIODO"; head -1 "$LOG" | cut -c1-20; tail -1 "$LOG" | cut -c1-20

echo "== TIPI DI MESSAGGIO (ultime $N righe)"
tail -n "$N" "$LOG" \
 | sed -E 's/^[0-9\/: ]+\[([a-z]+)\] [0-9#]+: (\*[0-9]+ )?/[\1] /' \
 | cut -c1-70 | sort | uniq -c | sort -rn | head -25

echo "== IP BLOCCATI O LIMITATI (ultime $N righe)"
tail -n "$N" "$LOG" | grep -E 'limiting requests|forbidden|timed out|refused' \
 | grep -oE 'client: [0-9a-fA-F.:]+' | sort | uniq -c | sort -rn | head -20

echo "== WHACKABOT (ultime 10)"
grep 'whackabot' "$LOG" | tail -n 10 | cut -c1-260

echo "== EVENTI SU IP DI OPENAI E MICROSOFT (tutto il log, ultimi 50)"
grep -E 'whackabot|forbidden|limiting' "$LOG" | grep -E "$BOTS" \
 | sed -E 's/^([0-9\/]+ [0-9:]+) \[[a-z]+\] [0-9#]+: \*[0-9]+ /\1 /' | cut -c1-220 | tail -n 50

echo "== FILE MANCANTI PIU' RICHIESTI (ultime $N righe)"
tail -n "$N" "$LOG" | grep -oE 'open\(\) "[^"]+" failed' \
 | sed -E 's/open\(\) "\/c[0-9]+\/www\///; s/" failed$//' | sort | uniq -c | sort -rn | head -40
