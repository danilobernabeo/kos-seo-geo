#!/bin/sh
# Converte i documenti Markdown di docs/ in docx dentro docs/docx/.
# Uso: scripts/docs/md-in-docx.sh [file.md ...]   (senza argomenti: tutti i docs/*.md)
# I documenti nati come Claude Docs si esportano dalla scheda, non da qui.
set -e
cd "$(dirname "$0")/../.."
mkdir -p docs/docx
[ $# -eq 0 ] && set -- docs/*.md
for f in "$@"; do
  nome=$(basename "$f" .md)
  # la convenzione ha la versione ufficiale esportata dalla scheda condivisa
  [ "$nome" = convenzione-identificativi ] && continue
  pandoc "$f" -f gfm -t docx -o "docs/docx/$nome.docx"
  echo "docs/docx/$nome.docx"
done
