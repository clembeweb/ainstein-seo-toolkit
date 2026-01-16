# AGENTE: Internal Links Analyzer

> **Ultimo aggiornamento:** 2026-01-12

## CONTESTO

**Modulo:** `internal-links`
**Stato:** 85% Parziale
**Prefisso DB:** `il_`

Modulo per analisi link interni di un sito:
- Import URL (CSV, Sitemap, Manual)
- Scraping pagine per estrazione link
- Analisi struttura link interni
- Rilevamento pagine orfane
- Calcolo Link Juice
- Grafo visuale link
- Analisi AI

---

## FILE CHIAVE

```
modules/internal-links/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php
│   ├── UrlController.php
│   ├── AnalysisController.php              # ⚠️ TODO - non implementato
│   └── LinkController.php                  # ⚠️ TODO - non implementato
├── models/
│   ├── Project.php
│   ├── Url.php
│   ├── InternalLink.php
│   └── Snapshot.php
├── services/
│   ├── LinkExtractor.php
│   └── Scraper.php                         # ⚠️ Wrapper locale
└── views/
    ├── projects/
    ├── urls/
    ├── links/
    │   ├── index.php                       # ⚠️ Lucide icons
    │   ├── graph.php
    │   └── orphans.php                     # ⚠️ Lucide icons
    ├── reports/
    │   ├── anchors.php                     # ⚠️ Lucide icons
    │   ├── juice.php                       # ⚠️ Lucide icons
    │   └── orphans.php                     # ⚠️ Lucide icons
    └── analysis/
        └── index.php                       # ⚠️ Dropdown AI obsoleto
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `il_projects` | Progetti con base_url e settings |
| `il_urls` | URL importate/crawlate |
| `il_links` | Link interni estratti (from → to) |
| `il_snapshots` | Snapshot temporali per confronto |

---

## BUG APERTI

| Bug | Severity | File | Status |
|-----|----------|------|--------|
| Icone Lucide (39 occorrenze) | HIGH | views/*.php | Da fixare |
| Dropdown modello AI obsoleto | MEDIUM | views/analysis/index.php | Da rimuovere |
| Controller non implementati | LOW | AnalysisController, LinkController | TODO |

**Problema principale:** 39 occorrenze di icone Lucide da sostituire con Heroicons SVG.

---

## GOLDEN RULES SPECIFICHE

1. **Icone** - DEVE usare Heroicons SVG, NO Lucide (data-lucide)
2. **Scraper locale** - services/Scraper.php wrappa ScraperService condiviso
3. **Import URL** - Usa componente shared/views/components/import-tabs.php
4. **Servizi condivisi** - CsvImportService, SitemapService
5. **Accordion sidebar** - Navigazione in nav-items.php
6. **Route pattern** - `/internal-links/project/{id}/...`
7. **Crediti** - Usare `Credits::getCost()` per costi dinamici (configurabili da admin):
   ```php
   $cost = Credits::getCost('link_analysis', 'internal-links');
   $cost = Credits::getCost('ai_suggestions', 'internal-links');
   ```
   Vedi: `docs/core/CREDITS-SYSTEM.md`

---

## PROMPT PRONTI

### 1. Fix icone Lucide → Heroicons (PRIORITARIO)
```
Sostituisci TUTTE le icone Lucide con Heroicons SVG in internal-links

TROVA:
grep -rn "data-lucide" modules/internal-links/views/

SOSTITUISCI con SVG inline da heroicons.com

MAPPA COMUNE:
- download → <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
- search → <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
- check → <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
- x → <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
- external-link → <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
- refresh → <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>

Fai file per file, verifica sintassi PHP dopo ogni modifica.
```

### 2. Rimuovere dropdown modello AI
```
Rimuovi dropdown selezione modello AI obsoleto

FILE: modules/internal-links/views/analysis/index.php
RIGHE: 134-135 circa

I modelli claude-3-haiku e claude-3-sonnet sono deprecati.
Il modello è gestito centralmente da AiService.
```

### 3. Implementare AnalysisController
```
Implementa AnalysisController.php per internal-links

FILE: modules/internal-links/controllers/AnalysisController.php

FUNZIONALITÀ:
- Analisi AI struttura link
- Suggerimenti miglioramento
- Report problemi

USA AiService('internal-links') per chiamate AI.
```

### 4. Migliorare grafo link
```
Migliora visualizzazione grafo in views/links/graph.php

OBIETTIVO: [es. performance con molti nodi, zoom, filtri]

USA libreria esistente (D3.js o vis.js se già presente).
```

### 5. Fix pagine orfane
```
Verifica logica rilevamento pagine orfane

FILE:
- models/Url.php (query orphans)
- views/reports/orphans.php

Una pagina è orfana se:
- Non ha link in entrata da altre pagine del sito
- Non è la homepage

Verifica che la query sia corretta.
```
