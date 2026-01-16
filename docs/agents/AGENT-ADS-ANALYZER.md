# AGENTE: Google Ads Analyzer

> **Ultimo aggiornamento:** 2026-01-12

## CONTESTO

**Modulo:** `ads-analyzer`
**Stato:** Completato (100%)
**Prefisso DB:** `ga_`

Modulo per analisi termini di ricerca Google Ads:
- Import CSV export Google Ads (formato italiano)
- Estrazione contesto automatica da landing page
- Estrazione keyword negative con AI
- Categorizzazione automatica
- Storico analisi multiple per progetto
- Export per Google Ads Editor

Flow: Upload CSV -> Landing URLs -> Contesto (auto/manual) -> Analisi AI -> Risultati + Export

---

## FUNZIONALITA COMPLETATE (5 Gen 2026)

1. Upload CSV formato italiano
2. Estrazione contesto manuale
3. Estrazione contesto automatica (landing page)
4. Analisi AI keyword negative
5. Analisi multiple per progetto (storico)
6. CRUD completo analisi
7. Export CSV e Google Ads Editor
8. Logging AI completo

---

## FILE CHIAVE

```
modules/ads-analyzer/
├── module.json
├── routes.php
├── controllers/
│   ├── DashboardController.php
│   ├── ProjectController.php
│   ├── AnalysisController.php              # Flow principale
│   ├── AnalysisHistoryController.php       # Storico analisi (NUOVO)
│   ├── ExportController.php
│   └── SettingsController.php
├── models/
│   ├── Project.php
│   ├── AdGroup.php
│   ├── SearchTerm.php
│   ├── Analysis.php                        # Storico analisi (NUOVO)
│   ├── NegativeCategory.php
│   ├── NegativeKeyword.php
│   └── BusinessContext.php
├── services/
│   ├── CsvParserService.php                # Parser CSV italiano
│   ├── KeywordAnalyzerService.php          # AI analysis
│   ├── ContextExtractorService.php         # Scraping + AI (NUOVO)
│   └── ValidationService.php
└── views/
    ├── dashboard/
    ├── projects/
    ├── analysis/
    │   ├── upload.php                      # Step 1
    │   ├── landing-urls.php                # Step 2 (NUOVO)
    │   ├── context.php                     # Step 2 alt
    │   └── results.php                     # Step 3-4
    ├── analyses/                           # NUOVO
    │   ├── index.php
    │   └── show.php
    └── export/
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `ga_projects` | Progetti analisi |
| `ga_saved_contexts` | Contesti business riutilizzabili |
| `ga_ad_groups` | Ad Group con landing_url, extracted_context |
| `ga_search_terms` | Termini di ricerca importati |
| `ga_analyses` | Storico analisi (NUOVO) |
| `ga_negative_categories` | Categorie con analysis_id |
| `ga_negative_keywords` | Keyword con analysis_id |

---

## BUG RISOLTI (5 Gen 2026)

| Bug | Fix |
|-----|-----|
| redirect() non esiste | Usato header('Location:') |
| CSRF token name errato | Usato _token e csrf_token() |
| is_zero_ctr boolean | Cast corretto |
| Database::execute | Database::update |
| Scraping 403 | Rimosso brotli da Accept-Encoding |
| HTML sporco | Pulizia avanzata con DOMDocument |

---

## GOLDEN RULES SPECIFICHE

1. **CSV Parser** - Gestisce formato italiano (`;` separatore, `,` decimale)
2. **BOM UTF-8** - CsvParserService rimuove BOM automaticamente
3. **KeywordAnalyzerService** - Usa AiService('ads-analyzer')
4. **ContextExtractorService** - Usa DOMDocument per pulizia HTML
5. **Categorie dinamiche** - Generate dall'AI, non predefinite
6. **Priorita keyword** - high, medium, evaluate
7. **Checkbox UI** - Alpine.js per selezione fluida
8. **Ogni analisi** - Ha propri categories/keywords via analysis_id
9. **Crediti** - Usare `Credits::getCost()` per costi dinamici (configurabili da admin):
   ```php
   $cost = Credits::getCost('context_extraction', 'ads-analyzer');
   $cost = Credits::getCost('ad_group_analysis', 'ads-analyzer');
   $cost = Credits::getCost('bulk_analysis', 'ads-analyzer');
   ```
   Vedi: `docs/core/CREDITS-SYSTEM.md`

---

## PROMPT PRONTI

### 1. Migliorare parsing CSV
```
Migliora CsvParserService.php per gestire:
- [es. nuove colonne, formati diversi, encoding]

FILE: modules/ads-analyzer/services/CsvParserService.php

Formato Google Ads italiano:
- Separatore: ;
- Decimale: ,
- Encoding: UTF-8 con BOM
```

### 2. Ottimizzare prompt AI
```
Ottimizza prompt in KeywordAnalyzerService.php

OBIETTIVO: [es. categorie piu precise, meno falsi positivi]

FILE: modules/ads-analyzer/services/KeywordAnalyzerService.php

OUTPUT JSON richiesto:
{
  "stats": {...},
  "categories": {
    "NOME": {
      "priority": "high|medium|evaluate",
      "description": "...",
      "keywords": [...]
    }
  }
}
```

### 3. Aggiungere nuovo formato export
```
Aggiungi formato export [nome] in ExportController.php

FILE: modules/ads-analyzer/controllers/ExportController.php

Formati esistenti:
- CSV semplice
- Google Ads Editor

Nuovo formato: [specifiche]
```

### 4. Migliorare estrazione contesto
```
Migliora ContextExtractorService.php

FILE: modules/ads-analyzer/services/ContextExtractorService.php

ATTUALE:
- Selettori CSS per main content
- Rimozione nav, footer, sidebar
- Conversione HTML -> testo

MIGLIORARE:
- [es. nuovi selettori, handling JavaScript, etc.]
```

