# AGENTE: Google Ads Analyzer

## CONTESTO

**Modulo:** `ads-analyzer`
**Stato:** 90% Completato
**Prefisso DB:** `ga_`

Modulo per analisi termini di ricerca Google Ads:
- Import CSV export Google Ads (formato italiano)
- Estrazione keyword negative con AI
- Categorizzazione automatica
- Export per Google Ads Editor

Flow: Upload CSV → Contesto Business → Analisi AI → Risultati + Export

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
│   └── ExportController.php
├── models/
│   ├── Project.php
│   ├── AdGroup.php
│   ├── SearchTerm.php
│   ├── NegativeCategory.php
│   ├── NegativeKeyword.php
│   └── BusinessContext.php
├── services/
│   ├── CsvParserService.php                # Parser CSV italiano
│   ├── KeywordAnalyzerService.php          # AI analysis
│   └── NegativeExtractorService.php
└── views/
    ├── dashboard/
    ├── projects/
    ├── analysis/
    │   ├── upload.php                      # Step 1
    │   ├── context.php                     # Step 2
    │   └── results.php                     # Step 3-4
    └── export/
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `ga_projects` | Progetti analisi |
| `ga_saved_contexts` | Contesti business riutilizzabili |
| `ga_ad_groups` | Ad Group dal CSV |
| `ga_search_terms` | Termini di ricerca importati |
| `ga_negative_categories` | Categorie trovate dall'AI |
| `ga_negative_keywords` | Keyword negative estratte |
| `ga_analysis_log` | Log analisi AI |

---

## BUG APERTI

| Bug | Severity | Status |
|-----|----------|--------|
| Nessun bug critico | - | ✅ |

**Modulo nuovo (2 Gen 2026)**, testato e funzionante.

---

## GOLDEN RULES SPECIFICHE

1. **CSV Parser** - Gestisce formato italiano (`;` separatore, `,` decimale)
2. **BOM UTF-8** - CsvParserService rimuove BOM automaticamente
3. **KeywordAnalyzerService** - Usa AiService('ads-analyzer')
4. **Categorie dinamiche** - Generate dall'AI, non predefinite
5. **Priorità keyword** - high, medium, evaluate
6. **Checkbox UI** - Alpine.js per selezione fluida
7. **Crediti:**
   - Analisi per Ad Group (≤3): 2 crediti
   - Analisi bulk (4+): 1.5 crediti/gruppo
   - Re-analisi: 2 crediti

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

OBIETTIVO: [es. categorie più precise, meno falsi positivi]

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

### 4. Aggiungere filtri risultati
```
Aggiungi filtri alla pagina risultati

FILE: modules/ads-analyzer/views/analysis/results.php

FILTRI:
- Per priorità (high/medium/evaluate)
- Per categoria
- Per Ad Group
- Ricerca keyword

USA Alpine.js per filtering client-side.
```

### 5. Salvare contesti business
```
Implementa salvataggio contesti business riutilizzabili

TABELLA: ga_saved_contexts
FILE:
- controllers/AnalysisController.php
- views/analysis/context.php

FLOW:
- Checkbox "Salva per riutilizzo"
- Nome contesto
- Dropdown per selezionare contesto salvato
```
