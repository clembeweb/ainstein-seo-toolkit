# SEO POSITION TRACKING - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-tracking` |
| **Prefisso DB** | `st_` |
| **Files** | 57 |
| **Stato** | ✅ Attivo (90%) |
| **Ultimo update** | 2026-01-28 |

Modulo per monitoraggio posizionamento keyword con dati GSC, Rank Check API (DataForSEO), Page Analyzer e report AI.

> **Nota:** GA4 è stato rimosso per semplificazione. Il modulo si concentra su GSC + Rank Check.

---

## Architettura

```
modules/seo-tracking/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php
│   ├── DashboardController.php
│   ├── KeywordController.php
│   ├── GroupController.php        # Keyword Groups
│   ├── CompareController.php      # Position Compare
│   ├── GscController.php          # OAuth GSC
│   ├── RankCheckController.php    # DataForSEO Rank Check
│   ├── PageAnalyzerController.php # SEO Page Analyzer
│   ├── QuickWinsController.php    # Quick Wins Finder
│   ├── AlertController.php
│   ├── ReportController.php
│   └── AiController.php
├── models/
│   ├── Project.php
│   ├── Keyword.php
│   ├── KeywordGroup.php           # Keyword Groups M:N
│   ├── KeywordTracking.php
│   ├── GscConnection.php
│   ├── GscData.php
│   ├── Location.php               # Locations per rank check
│   ├── Alert.php
│   └── AiReport.php
├── services/
│   ├── GscService.php
│   ├── GroupStatsService.php      # Statistiche gruppi
│   ├── PositionCompareService.php # Confronto posizioni
│   ├── KeywordMatcher.php
│   ├── AlertService.php
│   └── AiReportService.php
# In /services/ (root - condiviso)
│   └── DataForSeoService.php      # Rank Check API
└── views/
    ├── projects/
    ├── dashboard/
    ├── keywords/
    ├── groups/                    # Keyword Groups views
    ├── compare/                   # Position Compare views
    ├── ai/                        # Page Analyzer, Quick Wins
    ├── alerts/
    ├── reports/
    └── connections/
```

---

## Database Schema

```sql
-- Progetti e connessioni
st_projects              -- Progetti tracking
st_gsc_connections       -- Token OAuth GSC
st_locations             -- Locations per rank check (es: Italy, Milan)

-- Keyword tracking
st_keywords              -- Keyword monitorate
st_keyword_groups        -- Gruppi di keyword
st_keyword_group_members -- Relazione M:N keyword-gruppo
st_keyword_positions     -- Snapshot giornalieri (da Rank Check)
st_gsc_data              -- Dati storici GSC

-- Alert system
st_alert_settings        -- Config alert
st_alerts                -- Log alert

-- AI Reports
st_ai_reports            -- Report generati
st_sync_log              -- Log sync
```

---

## Funzionalità

### ✅ Implementate
- [x] CRUD progetti
- [x] CRUD keyword tracking
- [x] **Keyword Groups** (raggruppamento M:N con statistiche)
- [x] **Position Compare** (confronto posizioni tra periodi - stile SEMrush)
- [x] **SEO Page Analyzer** (analisi AI singola pagina con suggerimenti)
- [x] **Quick Wins Finder** (keyword pos 11-20 facili da spingere)
- [x] **Rank Check** via DataForSEO API
- [x] **Locations** gestione location per rank check
- [x] OAuth GSC completo e funzionante
- [x] Redirect URI dinamico (multi-dominio)
- [x] Token refresh automatico
- [x] Selezione property GSC
- [x] Sync dati GSC
- [x] Views dashboard
- [x] Views keyword detail
- [x] Schema DB allineato al codice

### 🔄 In Corso
- [ ] Sync dati automatico (cron)
- [ ] Weekly AI Digest

### ❌ Da Implementare
- [ ] Sistema alert
- [ ] Report AI automatici
- [ ] Email notifiche

> **Nota:** GA4 è stato rimosso. Revenue attribution non più in scope.

---

## Crediti

| Azione | Costo |
|--------|-------|
| GSC full sync (16 mesi) | 10 |
| Weekly digest AI | 5 |
| Monthly executive AI | 15 |
| Keyword analysis AI | 5 |
| Revenue attribution AI | 8 |

---

## Routes Principali

```php
// Progetti
GET  /seo-tracking                                    # Lista
GET  /seo-tracking/projects/create                    # Form
GET  /seo-tracking/projects/{id}                      # Dashboard

// Keywords
GET  /seo-tracking/projects/{id}/keywords             # Lista
GET  /seo-tracking/projects/{id}/keywords/add         # Form add
GET  /seo-tracking/projects/{id}/keywords/{kwId}      # Dettaglio

// Keyword Groups
GET  /seo-tracking/projects/{id}/groups               # Lista gruppi
GET  /seo-tracking/projects/{id}/groups/create        # Form crea
GET  /seo-tracking/projects/{id}/groups/{gId}         # Dettaglio
GET  /seo-tracking/projects/{id}/groups/{gId}/edit    # Form modifica

// Position Compare
GET  /seo-tracking/projects/{id}/compare              # Vista principale
POST /seo-tracking/projects/{id}/compare/data         # AJAX dati confronto
GET  /seo-tracking/projects/{id}/compare/export       # Export CSV

// SEO Page Analyzer (NEW)
GET  /seo-tracking/projects/{id}/ai/page-analyzer     # Vista analisi pagina
POST /seo-tracking/projects/{id}/ai/analyze-page      # AJAX analisi AI

// Quick Wins (NEW)
GET  /seo-tracking/projects/{id}/ai/quick-wins        # Lista opportunità

// Rank Check (NEW)
POST /seo-tracking/projects/{id}/keywords/{kwId}/check # Check posizione via DataForSEO

// Locations (NEW)
GET  /seo-tracking/projects/{id}/locations            # Gestione locations
POST /seo-tracking/projects/{id}/locations            # Aggiungi location

// Connessioni GSC
GET  /seo-tracking/projects/{id}/gsc/connect          # OAuth start
GET  /oauth/google/callback                           # OAuth callback (centralizzato)
POST /seo-tracking/projects/{id}/gsc/save-property    # Salva proprietà
POST /seo-tracking/projects/{id}/gsc/sync             # Sync dati
```

---

## Bug Risolti

### 2026-01-28
| Bug | Severity | File | Status |
|-----|----------|------|--------|
| Quick Wins URL navigation | MEDIUM | QuickWinsController | ✅ Fixato |
| Page Analyzer return statement | MEDIUM | PageAnalyzerController | ✅ Fixato |
| INT type compatibility migration | LOW | migrations/ | ✅ Fixato |

### 2026-01-07
| Bug | Severity | File | Status |
|-----|----------|------|--------|
| OAuth callback | HIGH | GscController | ✅ Fixato |
| Redirect URI dinamico | HIGH | GoogleOAuthService | ✅ Fixato |
| Token refresh | HIGH | GscService | ✅ Fixato |
| Schema DB mismatch | HIGH | migrations/ | ✅ Fixato |

### Migration Scripts

| File | Descrizione |
|------|-------------|
| `migrations/full_schema_fix.sql` | Fix schema allineamento DB/codice |
| `migrations/001_keyword_groups.sql` | ✅ Tabelle Keyword Groups |
| `migrations/003_add_search_volume.sql` | ✅ Colonna search_volume + indici |
| `migrations/004_locations.sql` | ✅ Tabella st_locations per rank check |

---

## Prossimi Step

1. **Configurare redirect URI** in Google Cloud Console:
   `https://ainstein.it/oauth/google/callback`

2. **Test OAuth GSC**:
   - Login → Nuovo progetto → Connetti GSC
   - Verificare callback e salvataggio token
   - Verificare lista proprietà

3. **Test GA4**:
   - Upload JSON Service Account
   - Verificare connessione
   - Test sync dati

---

## Note Implementazione

1. **OAuth GSC** condiviso con seo-audit via `GoogleOAuthService`
2. **Redirect URI dinamico** - supporta localhost, vhost e produzione senza config manuale
3. **GA4** usa Service Account (non OAuth utente)
4. **AiReportService** deve usare AiService centralizzato (verificare)
5. **Cron jobs** per sync giornaliero (da implementare)

---

## Pattern OAuth GSC (Reference)

Questo modulo è il **reference pattern** per implementare flussi OAuth verso GSC.

Vedi: `docs/GOLDEN-RULES.md` - Regola #11

**Punti chiave:**
- `GoogleOAuthService` centralizzato in `/services/`
- Redirect URI dinamico basato su `$_SERVER['HTTP_HOST']`
- Token storage in tabella dedicata con refresh automatico
- Property selection in step separato dopo OAuth
- Supporto multi-dominio (localhost, vhost, produzione)

---

## GAP AI - Da Implementare (FASE 2)

### Stato Attuale AI
- ✅ SEO Page Analyzer - Implementato e funzionante
- ✅ Quick Wins Finder - Implementato
- ⚠️ AiReportService presente ma non completamente funzionante
- ❌ Weekly Digest non attivo (cron da configurare)

### Feature Mancante: Weekly AI Digest

**Obiettivo:** Report settimanale automatico con insight azionabili.

**Cron settimanale che genera:**
- Riepilogo performance vs settimana precedente
- Top 3 azioni prioritarie
- Keyword in crescita/calo
- Opportunità identificate

**Output esempio:**
```markdown
## Weekly SEO Digest - 13-19 Gennaio 2026

### Performance
- Traffico organico: +12% vs settimana precedente
- Impression totali: 45.000 (+8%)

### Top 3 Azioni Prioritarie
1. "consulenza seo milano" - Posizione 11 → Ottimizza H1
2. "audit seo gratuito" - CTR 1.2% → Riscrivi meta description
3. "tool seo italiano" - 500 impression, 0 click → Verifica intent
```

**Implementazione:**
1. Cron job settimanale (lunedi mattina)
2. `AiReportService::generateWeeklyDigest($projectId)`
3. Salvataggio in `st_ai_reports`
4. Email notifica (opzionale)
5. Vista dashboard con ultimo digest

**Priorità:** 🟡 MEDIA - FASE 2 Roadmap

📄 Vedi: [ROADMAP.md](../ROADMAP.md)

---

## Nuove Feature Implementate (2026-01-28)

### SEO Page Analyzer
Analisi AI di una singola pagina con:
- Analisi on-page SEO (title, meta, headings, content)
- Suggerimenti di miglioramento
- Score qualità
- Azioni prioritarie

**Route:** `/seo-tracking/projects/{id}/ai/page-analyzer`

### Quick Wins Finder
Identificazione automatica keyword in posizione 11-20 facilmente ottimizzabili:
- Lista keyword con potenziale
- Azioni suggerite per ogni keyword
- Filtri per volume, posizione, CTR

**Route:** `/seo-tracking/projects/{id}/ai/quick-wins`

### DataForSEO Rank Check
Verifica posizioni keyword in tempo reale via API DataForSEO:
- Check singola keyword
- Bulk check
- Supporto multi-location (es: Italy, Milan, Rome)

**Service:** `/services/DataForSeoService.php`

---

*Spec aggiornata - 2026-01-28*
