# GOOGLE ADS ANALYZER - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `ads-analyzer` |
| **Prefisso DB** | `ga_` |
| **Files** | ~35 |
| **Stato** | Completato (100%) |
| **Ultimo update** | 2026-01-05 |

Modulo per analisi termini di ricerca Google Ads ed estrazione keyword negative con AI.

---

## Funzionalita Implementate

### Core
- [x] CRUD Progetti
- [x] Upload CSV (formato Google Ads italiano)
- [x] Parsing CSV (separatore ;, decimale ,, BOM UTF-8)
- [x] Raggruppamento termini per Ad Group

### Estrazione Contesto
- [x] Contesto manuale (textarea)
- [x] Contesto automatico da landing page (scraping + AI)
- [x] URL landing per ogni Ad Group
- [x] Modifica contesto estratto

### Analisi AI
- [x] Analisi keyword negative con AI
- [x] Categorie generate dinamicamente
- [x] Priorita: high, medium, evaluate
- [x] Integrazione AiService centralizzato
- [x] Logging in AI Logs

### Analisi Multiple (NUOVO 2026-01-05)
- [x] Storico analisi per progetto
- [x] CRUD analisi (crea, visualizza, elimina)
- [x] Ogni analisi indipendente con propri risultati
- [x] Export per analisi specifica

### Export
- [x] CSV semplice
- [x] Formato Google Ads Editor
- [x] Copia testo rapida

---

## Architettura

```
modules/ads-analyzer/
├── module.json
├── routes.php
├── controllers/
│   ├── DashboardController.php
│   ├── ProjectController.php
│   ├── AnalysisController.php        # Flow principale
│   ├── AnalysisHistoryController.php # Storico analisi (NUOVO)
│   ├── ExportController.php
│   └── SettingsController.php
├── models/
│   ├── Project.php
│   ├── AdGroup.php
│   ├── SearchTerm.php
│   ├── Analysis.php                  # Storico analisi (NUOVO)
│   ├── NegativeCategory.php
│   ├── NegativeKeyword.php
│   └── BusinessContext.php
├── services/
│   ├── CsvParserService.php
│   ├── KeywordAnalyzerService.php
│   ├── ContextExtractorService.php   # Scraping + AI
│   └── ValidationService.php
└── views/
    ├── dashboard/
    ├── projects/
    ├── analysis/
    │   ├── upload.php
    │   ├── landing-urls.php
    │   ├── context.php
    │   └── results.php
    ├── analyses/                     # NUOVO
    │   ├── index.php
    │   └── show.php
    └── export/
```

---

## Database Schema

| Tabella | Descrizione |
|---------|-------------|
| ga_projects | Progetti |
| ga_ad_groups | Ad Group con landing_url, extracted_context |
| ga_search_terms | Termini di ricerca importati |
| ga_analyses | Storico analisi (NUOVO) |
| ga_negative_categories | Categorie con analysis_id |
| ga_negative_keywords | Keyword con analysis_id |
| ga_saved_contexts | Contesti riutilizzabili |

### Tabella ga_analyses (NUOVO)

```sql
CREATE TABLE ga_analyses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    business_context TEXT NULL,
    context_mode ENUM('manual', 'auto', 'mixed') DEFAULT 'manual',
    ad_groups_analyzed INT DEFAULT 0,
    total_categories INT DEFAULT 0,
    total_keywords INT DEFAULT 0,
    credits_used INT DEFAULT 0,
    status ENUM('draft', 'analyzing', 'completed', 'error') DEFAULT 'draft',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Flow Operativo

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   UPLOAD    │ ──▶ │  LANDING    │ ──▶ │  ANALISI    │ ──▶ │  RISULTATI  │
│    CSV      │     │    URLs     │     │     AI      │     │  + EXPORT   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │  CONTESTO   │
                    │ AUTO/MANUAL │
                    └─────────────┘
```

1. **Upload CSV** - Export Google Ads termini di ricerca
2. **Landing URLs** - Assegna URL landing agli Ad Group
3. **Estrazione Contesto** - Automatica da landing o manuale
4. **Analisi AI** - Estrazione keyword negative per Ad Group
5. **Risultati** - Selezione e export per Google Ads Editor

---

## Crediti

| Azione | Costo |
|--------|-------|
| Analisi per Ad Group | 2 |
| Estrazione contesto da landing | 3 |

---

## Routes Principali

```php
// Progetti
GET  /ads-analyzer                              # Dashboard
GET  /ads-analyzer/projects/create              # Form
GET  /ads-analyzer/projects/{id}                # Dettaglio

// Upload e Contesto
GET  /ads-analyzer/projects/{id}/upload         # Step 1
POST /ads-analyzer/projects/{id}/upload         # Process CSV
GET  /ads-analyzer/projects/{id}/landing-urls   # Step 2
GET  /ads-analyzer/projects/{id}/context        # Step 2 alt

// Estrazione contesto
POST /ads-analyzer/projects/{id}/ad-groups/{adGroupId}/extract-context
POST /ads-analyzer/projects/{id}/extract-all-contexts

// Analisi
POST /ads-analyzer/projects/{id}/analyze        # Step 3 AI
GET  /ads-analyzer/projects/{id}/results        # Step 4 (legacy)

// Analisi Multiple (NUOVO)
GET  /ads-analyzer/projects/{id}/analyses
GET  /ads-analyzer/projects/{id}/analyses/{analysisId}
POST /ads-analyzer/projects/{id}/analyses/{analysisId}/delete

// Export
GET  /ads-analyzer/projects/{id}/export/csv
GET  /ads-analyzer/projects/{id}/export/google-ads-editor
GET  /ads-analyzer/projects/{id}/analyses/{analysisId}/export
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

## Note Implementazione

1. **KeywordAnalyzerService** usa AiService centralizzato
2. **CSV Parser** gestisce formato italiano Google Ads
3. **Categorie** generate dinamicamente dall'AI (non predefinite)
4. **ContextExtractorService** usa DOMDocument per pulizia HTML avanzata
5. **Checkbox** gestiti con Alpine.js per UX fluida
6. **Ogni analisi** ha propri categories/keywords via `analysis_id`

---

## Changelog

| Data | Modifica |
|------|----------|
| 2026-01-05 | Aggiunta feature Analisi Multiple per progetto |
| 2026-01-04 | Fix scraping 403, pulizia HTML avanzata |
| 2026-01-03 | Estrazione contesto automatica da landing page |
| 2026-01-02 | Release iniziale modulo |

---

*Spec aggiornata - 2026-01-05*
