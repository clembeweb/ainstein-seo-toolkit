# GOOGLE ADS ANALYZER - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `ads-analyzer` |
| **Prefisso DB** | `ga_` |
| **Files** | 24 |
| **Stato** | 🆕 Nuovo (90%) |
| **Ultimo update** | 2026-01-02 |

Modulo per analisi termini di ricerca Google Ads ed estrazione keyword negative con AI.

---

## Architettura Implementata

```
modules/ads-analyzer/
├── module.json
├── routes.php
├── controllers/
│   ├── DashboardController.php
│   ├── ProjectController.php
│   ├── AnalysisController.php     # Flow principale
│   └── ExportController.php
├── models/
│   ├── Project.php
│   ├── AdGroup.php
│   ├── SearchTerm.php
│   ├── NegativeCategory.php
│   ├── NegativeKeyword.php
│   └── BusinessContext.php
├── services/
│   ├── CsvParserService.php       # Parser CSV Google Ads (IT)
│   ├── KeywordAnalyzerService.php # AI analysis
│   └── NegativeExtractorService.php
└── views/
    ├── dashboard/
    ├── projects/
    ├── analysis/
    │   ├── upload.php
    │   ├── context.php
    │   └── results.php
    └── export/
```

---

## Database Schema

```sql
-- Progetti e contesti
ga_projects           -- Progetti analisi
ga_saved_contexts     -- Contesti business riutilizzabili

-- Dati importati
ga_ad_groups          -- Ad Group dal CSV
ga_search_terms       -- Termini di ricerca

-- Risultati analisi
ga_negative_categories -- Categorie trovate dall'AI
ga_negative_keywords   -- Keyword negative estratte

-- Log
ga_analysis_log       -- Log analisi AI
```

---

## Flow Operativo

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   UPLOAD    │ ──▶ │  CONTESTO   │ ──▶ │  ANALISI    │ ──▶ │  RISULTATI  │
│    CSV      │     │  BUSINESS   │     │     AI      │     │  + EXPORT   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

1. **Upload CSV** - Export Google Ads termini di ricerca
2. **Contesto Business** - Descrizione attività per AI
3. **Analisi AI** - Estrazione keyword negative per Ad Group
4. **Risultati** - Selezione e export per Google Ads Editor

---

## Funzionalità Implementate

### CSV Parser
- [x] Formato italiano (separatore `;`, decimale `,`)
- [x] Gestione BOM UTF-8
- [x] Raggruppamento per Ad Group
- [x] Calcolo CTR 0% e impressioni sprecate

### AI Analysis
- [x] Prompt dinamico basato su contesto business
- [x] Categorie negative generate automaticamente
- [x] Priorità: high, medium, evaluate
- [x] Integrato con AiService centralizzato

### Export
- [x] CSV semplice
- [x] Formato Google Ads Editor
- [x] Copia testo rapida

---

## Crediti

| Azione | Costo |
|--------|-------|
| Analisi per Ad Group (≤3) | 2 |
| Analisi bulk (4+) | 1.5 |
| Re-analisi | 2 |

---

## Routes Principali

```php
// Progetti
GET  /ads-analyzer                              # Dashboard
GET  /ads-analyzer/projects/create              # Form
GET  /ads-analyzer/projects/{id}                # Dettaglio

// Flow analisi
GET  /ads-analyzer/projects/{id}/upload         # Step 1
POST /ads-analyzer/projects/{id}/upload         # Process CSV
GET  /ads-analyzer/projects/{id}/context        # Step 2
POST /ads-analyzer/projects/{id}/analyze        # Step 3 AI
GET  /ads-analyzer/projects/{id}/results        # Step 4

// Export
GET  /ads-analyzer/projects/{id}/export/csv
GET  /ads-analyzer/projects/{id}/export/google-ads-editor
```

---

## AI Prompt Structure

```
Sei un esperto Google Ads. Analizza i termini di ricerca...

CONTESTO BUSINESS:
{business_context}

TERMINI DI RICERCA:
{terms_list}

OUTPUT JSON:
{
  "stats": {...},
  "categories": {
    "CATEGORIA_1": {
      "priority": "high|medium|evaluate",
      "description": "...",
      "keywords": ["kw1", "kw2"]
    }
  }
}
```

---

## Bug Noti

| Bug | Severity | Status |
|-----|----------|--------|
| - | - | Nessun bug critico |

---

## Note Implementazione

1. **KeywordAnalyzerService** usa AiService centralizzato
2. **CSV Parser** gestisce formato italiano Google Ads
3. **Categorie** generate dinamicamente dall'AI (non predefinite)
4. **Checkbox** gestiti con Alpine.js per UX fluida

---

*Spec creata - 2026-01-02*
