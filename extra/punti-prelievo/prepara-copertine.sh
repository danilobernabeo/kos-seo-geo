#!/bin/sh
# Copertine del box pieghevole dai PDF di files/assets/doc (pannello destro della prima pagina del 6 ante), 600 × 857,
# JPG 82, con il riquadro di indirizzo e orari (in basso a destra) coperto di bianco: gli orari stanno nel testo della
# sezione e la copertina non deve ripeterli (decisione di Danilo, 27/11). Richiede pdftoppm e ImageMagick.
# Uso, dalla cartella extra/punti-prelievo: sh prepara-copertine.sh
set -e
tmp=$(mktemp -d)
for coppia in poliambulatorio-san-martino-bollate:poliambulatorio-san-martino \
              centro-medico-riabilitativo-cinisello:centro-medico-riabilitativo; do
  pdf=${coppia%%:*}; nome=${coppia##*:}
  pdftoppm -r 300 -f 1 -l 1 -png "files/assets/doc/punto-prelievi-$pdf.pdf" "$tmp/p"
  img=$(ls "$tmp"/p*.png); w=$(identify -format %w "$img"); h=$(identify -format %h "$img"); terzo=$((w / 3))
  convert "$img" -crop "${terzo}x${h}+$((w - terzo))+0" +repage -resize 600x \
    -fill white -draw "rectangle 366,712 599,856" \
    -strip -sampling-factor 4:2:0 -quality 82 -interlace JPEG "files/assets/img/pieghevole-punto-prelievi-$nome.jpg"
  rm -f "$tmp"/p*.png
  echo "files/assets/img/pieghevole-punto-prelievi-$nome.jpg"
done
rm -rf "$tmp"
