# Design: Export PDF Report + CSV Google Ads Editor

> Data: 2026-02-24 | Modulo: ads-analyzer | Stato: Approvato

## Obiettivo

Aggiungere due funzionalita di export alla pagina valutazione campagne:
1. **PDF Report** — export completo del report con coerenza grafica
2. **CSV Google Ads Editor** — export per sezione dei suggerimenti AI generati

## Decisioni

| Scelta | Decisione | Motivazione |
|--------|-----------|-------------|
| Libreria PDF | mpdf | Riuso futuro su altri moduli, CSS avanzato, header/footer nativi |
| CSV scope | Per sezione singola | Granulare, l'utente sceglie cosa importare |
| Output AI | JSON strutturato | Pulito, riusabile, CSV preciso (no regex parsing) |
| Costo crediti PDF | Gratuito | Il costo e gia nella valutazione AI |

## 1. PDF Export con mpdf

### Template PDF (pagine)

1. **Copertina**: Logo Ainstein, nome progetto, data, punteggio overall
2. **Executive Summary**: Punteggio, trend, sommario, metriche delta (tabella)
3. **Raccomandazioni Top**: Lista numerata
4. **Analisi Campagne**: Sotto-sezione per campagna (score, forza, problemi, ad groups)
5. **Estensioni**: Score, mancanti, presenti, suggerimenti
6. **Landing Pages**: Score, analisi per URL, suggerimenti
7. **Suggerimenti Miglioramento**: Tabella priorita/area/impatto

### Specifiche

- Header: Logo sx, nome progetto centro, data dx
- Footer: "Pagina X di Y", "Generato da Ainstein - ainstein.it"
- Colori: Palette coerente con UI (slate/rose per ads-analyzer)
- Layout: Table-based (no flexbox/grid per compatibilita mpdf)
- Encoding: UTF-8
- Route: `GET /ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/export-pdf`

## 2. CSV Export per sezione

### Output AI strutturato

Modifica `EvaluationGeneratorService` per restituire JSON:

```json
// Type: copy
{
  "headlines": ["Headline 1 (max 30)", "..."],
  "descriptions": ["Description 1 (max 90)", "..."],
  "paths": {"path1": "...", "path2": "..."}
}

// Type: extensions
{
  "sitelinks": [{"title": "...", "desc1": "...", "desc2": "...", "url": "..."}],
  "callouts": ["..."],
  "structured_snippets": [{"header": "...", "values": ["..."]}]
}

// Type: keywords
{
  "keywords": [{"keyword": "...", "match_type": "exact|phrase|broad", "is_negative": true}]
}
```

### Formato CSV

Identico a `CampaignCreatorService::generateSearchCsv()`:
- BOM UTF-8
- Colonne: Row Type, Campaign, Ad Group, Headline 1-15, Description 1-4, Path 1/2, Final URL, Sitelink Text, Callout Text, etc.
- Row Types: Ad, Keyword, Negative Keyword, Sitelink, Callout, Structured Snippet

### Flusso utente

1. Clicca "Genera con AI" su un issue/suggerimento
2. Vede anteprima formattata (tabella headlines con char count, keywords con badge match type)
3. Sotto l'anteprima appare bottone "Esporta CSV per Ads Editor"
4. Click -> download CSV importabile in Google Ads Editor

### Route

`POST /ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/export-csv`
Body: `{fix_key, type}`

## 3. File da creare/modificare

| File | Azione |
|------|--------|
| `composer.json` | Aggiungere `mpdf/mpdf` |
| `modules/ads-analyzer/services/EvaluationPdfService.php` | **Nuovo** |
| `modules/ads-analyzer/views/campaigns/evaluation-pdf.php` | **Nuovo** |
| `modules/ads-analyzer/services/EvaluationCsvService.php` | **Nuovo** |
| `modules/ads-analyzer/services/EvaluationGeneratorService.php` | **Modifica** — output JSON |
| `modules/ads-analyzer/controllers/CampaignController.php` | **Modifica** — route export |
| `modules/ads-analyzer/views/campaigns/evaluation.php` | **Modifica** — bottoni + anteprima |
| `public/index.php` | **Modifica** — route |
