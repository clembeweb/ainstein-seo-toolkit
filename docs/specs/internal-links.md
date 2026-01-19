# INTERNAL LINKS ANALYZER - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `internal-links` |
| **Prefisso DB** | `il_` |
| **Files** | 28+ |
| **Stato** | ⚠️ Parziale (85%) |
| **Ultimo update** | 2026-01-19 |

Modulo per analisi e ottimizzazione della struttura di link interni con mapping, detection pagine orfane e analisi AI.

---

## Architettura

```
modules/internal-links/
├── module.json
├── routes.php
├── database/
│   └── schema.sql
├── controllers/
│   └── ProjectController.php
├── models/
│   ├── Project.php
│   ├── Url.php
│   ├── InternalLink.php
│   └── Snapshot.php
├── services/
│   └── Scraper.php
└── views/
    ├── projects/
    ├── urls/
    ├── scraper/
    ├── links/
    ├── analysis/
    ├── analyzer/
    ├── reports/
    └── compare/
```

---

## Funzionalità

### ✅ Implementate
- [x] CRUD progetti
- [x] Import URL (manuale, CSV, sitemap)
- [x] Scraping pagine con rate limiting
- [x] Estrazione link interni/esterni
- [x] Detection pagine orfane
- [x] Mappa link con statistiche
- [x] Graph visualization
- [x] Analisi anchor text
- [x] Report Link Juice distribution
- [x] Snapshots per confronto nel tempo
- [x] Export CSV (links, anchors, orphans)
- [x] Bulk actions su URL e link

### ❌ Da Implementare
- [ ] **AI Link Suggester** (FASE 1 - PRIORITÀ ALTA)
- [ ] Topic clusters detection
- [ ] PageRank interno simulato

---

## Database Schema

```sql
-- Progetti
il_projects              -- Progetti analisi
  - id, user_id, name, base_url
  - stats (JSON)
  - scrape_delay, max_urls

-- URL
il_urls                  -- URL da analizzare
  - id, project_id
  - url, keyword, status
  - content_html, scraped_at

-- Link
il_internal_links        -- Link trovati
  - id, project_id
  - source_url_id, destination_url
  - anchor_text, is_internal
  - ai_relevance_score, ai_juice_flow
  - ai_notes, ai_suggestion

-- Snapshots
il_snapshots             -- Istantanee per confronto
  - id, project_id, user_id
  - name, description
  - data (JSON), created_at
```

---

## Routes Principali

```php
// Progetti
GET  /internal-links                              # Lista progetti
GET  /internal-links/projects/create              # Form
POST /internal-links/projects                     # Store
GET  /internal-links/project/{id}                 # Dashboard

// URL
GET  /internal-links/project/{id}/urls            # Lista URL
GET  /internal-links/project/{id}/urls/import     # Form import
POST /internal-links/project/{id}/urls/store      # Store URL
POST /internal-links/project/{id}/urls/bulk       # Bulk actions

// Scraping
GET  /internal-links/project/{id}/scrape          # Pagina scraping
POST /internal-links/project/{id}/scrape/batch    # Batch scrape (AJAX)
POST /internal-links/project/{id}/scrape/reset    # Reset

// Links
GET  /internal-links/project/{id}/links           # Lista link
GET  /internal-links/project/{id}/links/graph     # Graph visualization
GET  /internal-links/project/{id}/orphans         # Pagine orfane

// Analysis
GET  /internal-links/project/{id}/analysis        # Analisi AI
POST /internal-links/project/{id}/analysis/start  # Start AI analysis

// Reports
GET  /internal-links/project/{id}/reports/anchors # Anchor analysis
GET  /internal-links/project/{id}/reports/juice   # Link juice
GET  /internal-links/project/{id}/reports/orphans # Orphan pages

// Compare
GET  /internal-links/project/{id}/compare         # Snapshots
POST /internal-links/project/{id}/compare/create  # Create snapshot

// Export
GET  /internal-links/project/{id}/export          # Export CSV
```

---

## Crediti

| Azione | Costo |
|--------|-------|
| Scrape URL | 0.1 |
| AI analysis link | 2 |
| AI suggestions batch | 5 |

---

## GAP AI - Da Implementare (FASE 1)

### Stato Attuale AI
- ✅ AI analisi relevance/juice per link esistenti
- ❌ **AI Link Suggester mancante** (feature differenziante)

### Feature Mancante: AI Link Suggester

**Obiettivo:** Suggerire automaticamente dove creare nuovi link interni.

**Input:**
- Mappa link esistente
- Contenuto pagine

**Output per ogni suggerimento:**
```json
{
  "source_url": "/blog/guida-seo-2026",
  "target_url": "/servizi/consulenza-seo",
  "anchor_text": "consulenza SEO professionale",
  "priority": 9,
  "section": "Paragrafo 3 - dopo 'Per ottenere risultati...'",
  "reason": "La pagina sorgente parla di strategie SEO, collegamento naturale al servizio"
}
```

**Implementazione:**
1. Nuovo service `LinkSuggesterService.php`
2. Analisi contenuto pagine con AI
3. Matching semantico source/target
4. Generazione anchor text ottimizzati
5. Vista "Suggerimenti Link" con azioni rapide
6. Export suggerimenti

**Priorità:** 🔴 ALTA - FASE 1 Roadmap

📄 Vedi: [ROADMAP.md](../ROADMAP.md)

---

## Note Implementazione

1. **Scraper** - Usa delay configurabile per evitare ban
2. **Graph** - Usa vis.js per visualizzazione
3. **Snapshots** - Salva stato completo in JSON
4. **AI Analysis** - Usa `AiService('internal-links')`
5. **Sitemap** - Usa `SitemapService` condiviso

---

*Spec creata - 2026-01-19*
