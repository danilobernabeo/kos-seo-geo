# TV `chartDipendenti`

- **Tipo di input:** migx · **Categoria:** Sostenibilità · **Accesso ai template:** template della pagina Sostenibilità
- **Didascalia:** Grafico "I nostri dipendenti"

## Form Tabs
```json
[{
  "caption": "Dati anno",
  "fields": [
    {"field": "anno",       "caption": "Anno",                    "inputTVtype": "number"},
    {"field": "totale_ita", "caption": "ITA – Totale dipendenti", "inputTVtype": "number"},
    {"field": "donne_ita",  "caption": "ITA – Donne",             "inputTVtype": "number"},
    {"field": "uomini_ita", "caption": "ITA – Uomini",            "inputTVtype": "number"},
    {"field": "totale_ger", "caption": "GER – Totale dipendenti", "inputTVtype": "number"},
    {"field": "donne_ger",  "caption": "GER – Donne",             "inputTVtype": "number"},
    {"field": "uomini_ger", "caption": "GER – Uomini",            "inputTVtype": "number"}
  ]
}]
```

## Grid Columns
```json
[
  {"header": "Anno",       "dataIndex": "anno",       "width": 60},
  {"header": "ITA tot.",   "dataIndex": "totale_ita", "width": 80},
  {"header": "ITA donne",  "dataIndex": "donne_ita",  "width": 80},
  {"header": "ITA uomini", "dataIndex": "uomini_ita", "width": 80},
  {"header": "GER tot.",   "dataIndex": "totale_ger", "width": 80},
  {"header": "GER donne",  "dataIndex": "donne_ger",  "width": 80},
  {"header": "GER uomini", "dataIndex": "uomini_ger", "width": 80}
]
```

## Dati (fonte: Bilanci di Sostenibilità, dipendenti al 31/12) — numeri SENZA punto delle migliaia

| Anno | ITA tot. | ITA donne | ITA uomini | GER tot. | GER donne | GER uomini |
|---|---:|---:|---:|---:|---:|---:|
| 2021 | 7100 | 5632 | 1468 | 3941 | 3140 | 801 |
| 2022 | 7210 | 5694 | 1516 | 4131 | 3297 | 834 |
| 2023 | 7402 | 5875 | 1527 | 4429 | 3538 | 891 |
| 2024 | 7145 | 5661 | 1484 | 4569 | 3661 | 908 |
| 2025 | 7032 | 5566 | 1466 | 4738 | 3695 | 1037 |

Nota: GER 2025 → 1037 + 3695 = 4732 ≠ 4738 (6 non attribuiti): il totale si inserisce così com'è.
