# foto/

Foto della pagina autore di Antonella Schiavino. **Entrambi i file sono presenti.**

| File | Dimensioni | Peso | Uso |
|---|---|---|---|
| `antonella-schiavino-originale.jpg` | 1500 × 1794 | 740 KB | sorgente, non pubblicare |
| `antonella-schiavino.jpg` | 1000 × 1000 | 144 KB | **da pubblicare** |

## Il file da pubblicare

| | |
|---|---|
| Destinazione sul sito | `assets/img/autori/antonella-schiavino.jpg` |
| Valore della TV `aut.foto` | `assets/img/autori/antonella-schiavino.jpg` |
| Testo alternativo | `Antonella Schiavino` |

Lo snippet `aaSchema` legge `width` e `height` dell'`ImageObject` **dal file reale**:
non vanno scritti a mano, ma il file dev'essere leggibile dal filesystem del sito al
percorso indicato.

## Come è stato prodotto il quadrato

L'originale è un ritratto verticale, non quadrato. Il file pubblicabile è un
ritaglio quadrato di 1400 px di lato preso dall'originale a partire da (70, 160),
poi ridotto a 1000 × 1000 e salvato in JPEG progressivo a qualità 88.

L'inquadratura tiene il volto nella metà superiore con un po' di spazio sopra la
testa e le spalle incluse: regge sia nel riquadro quadrato della pagina autore sia
nella maschera tonda del box a fondo articolo, dove gli angoli vengono tagliati.

Se serve rigenerarlo con un'inquadratura diversa, si riparte dall'originale: non va
reinterpolato il file già ridotto.

## Dimensioni d'uso

- Pagina autore: 200 × 200 (`scheda-autore__foto`)
- Box a fondo articolo: 120 × 120, tonda (`box-autore__foto`)

Entrambe sono molto sotto i 1000 px del sorgente, quindi c'è margine per gli schermi
ad alta densità senza ulteriori varianti.
