# SEO POSITION TRACKING - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-tracking` |
| **Prefisso DB** | `st_` |
| **Files** | 57 |
| **Stato** | ✅ Attivo (95%) |
| **Ultimo update** | 2026-02-03 |

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
│   ├── GscService.php             # OAuth GSC + sync metriche
│   ├── GroupStatsService.php      # Statistiche gruppi
│   ├── PositionCompareService.php # Confronto posizioni
│   ├── KeywordMatcher.php
│   ├── AlertService.php
│   └── AiReportService.php        # Report AI (weekly, monthly)
├── cron/
│   ├── rank-dispatcher.php        # Auto rank check (admin scheduled)
│   ├── gsc-sync-dispatcher.php    # Auto GSC sync (admin scheduled)
│   ├── ai-report-dispatcher.php   # Auto AI reports (admin scheduled)
│   └── daily-sync.php             # (Legacy) Sync manuale
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
- [x] CRUD progetti (semplificato: solo nome + dominio)
- [x] CRUD keyword tracking (solo keyword manuali, no auto-discovery)
- [x] **Keyword Groups** (raggruppamento M:N con statistiche)
- [x] **Position Compare** (confronto posizioni tra periodi - stile SEMrush)
- [x] **SEO Page Analyzer** (analisi AI singola pagina con suggerimenti)
- [x] **Quick Wins Finder** (keyword pos 11-20 facili da spingere)
- [x] **Rank Check** via DataForSEO API (cron automatico admin-configured)
- [x] **Locations** gestione location per rank check
- [x] OAuth GSC completo e funzionante
- [x] Redirect URI dinamico (multi-dominio)
- [x] Token refresh automatico
- [x] Selezione property GSC
- [x] **GSC Auto Sync** (cron admin-configured, solo keyword tracciate)
- [x] **AI Reports automatici** (cron admin-configured, weekly digest)
- [x] Views dashboard
- [x] Views keyword detail
- [x] Schema DB allineato al codice

### 🔄 In Corso
- [ ] Alert UI funzionante (UI presente, backend da completare)

### ❌ Da Implementare
- [ ] Email notifiche alert
- [ ] Monthly executive report (cron)

> **Nota:** GA4 è stato rimosso. Le impostazioni per-progetto utente (Report AI, sync_enabled) sono state rimosse.
> La configurazione cron è ora centralizzata nelle impostazioni modulo admin.

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
3. **GA4** rimosso - non più in scope
4. **AiReportService** usa `AiService('seo-tracking')` centralizzato ✅
5. **Cron jobs** implementati con scheduler admin-configured:
   - `rank-dispatcher.php` - rank check automatico
   - `gsc-sync-dispatcher.php` - sync GSC metriche keyword tracciate
   - `ai-report-dispatcher.php` - weekly AI digest
6. **Creazione progetto** semplificata: solo nome + dominio
7. **Impostazioni progetto** ridotte: nome, dominio, email notifiche, alert UI, GSC connection

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

## Architettura Cron Jobs (Admin-Configured)

### Gestione Centralizzata
Tutti i cron job automatici sono configurati dall'admin nelle impostazioni del modulo (`/admin/modules/{id}/settings`).
L'utente NON ha impostazioni per-progetto relative a scheduling/sync.

### Cron Jobs Disponibili

| Cron | File | Descrizione | Settings |
|------|------|-------------|----------|
| **Rank Check** | `rank-dispatcher.php` | Verifica posizioni SERP via API | `rank_auto_enabled`, `rank_auto_days`, `rank_auto_time` |
| **GSC Sync** | `gsc-sync-dispatcher.php` | Sync metriche GSC per keyword tracciate | `gsc_sync_enabled`, `gsc_sync_frequency`, `gsc_sync_time` |
| **AI Reports** | `ai-report-dispatcher.php` | Genera weekly digest AI | `ai_reports_enabled`, `ai_reports_day`, `ai_reports_time` |

### Crontab Esempio (Produzione)
```bash
# Rank Check - ogni 5 minuti
*/5 * * * * php /path/to/modules/seo-tracking/cron/rank-dispatcher.php

# GSC Sync - ogni ora
0 * * * * php /path/to/modules/seo-tracking/cron/gsc-sync-dispatcher.php

# AI Reports - ogni ora (scheduler interno)
0 * * * * php /path/to/modules/seo-tracking/cron/ai-report-dispatcher.php
```

### Flusso GSC Semplificato
1. Utente crea progetto (solo nome + dominio)
2. Utente connette GSC (OAuth)
3. Utente aggiunge keyword manualmente
4. Cron admin sincronizza metriche GSC solo per keyword tracciate
5. Nessuna auto-discovery keyword

### Weekly AI Digest ✅ Implementato
- Cron job: `ai-report-dispatcher.php`
- Metodo: `AiReportService::generateWeeklyDigest($projectId, $userId)`
- Salvataggio: `st_ai_reports`
- Configurazione: admin module settings

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

*Spec aggiornata - 2026-02-03*

---

## Refactoring 2026-02-03

### Modifiche UI
- **Creazione progetto**: rimossi campi Notifications, Report AI, Info cards GA4
- **Impostazioni progetto**: rimossi sync_enabled, data_retention, Report AI section
- Mantenuti: Nome, Dominio, Email notifiche, Alert UI, GSC connection, Delete zone

### Nuove Impostazioni Admin (module.json)
```json
{
  "gsc_sync_enabled": "Abilita sync GSC automatico",
  "gsc_sync_frequency": "Frequenza (daily, mon_thu, mon_wed_fri, weekly)",
  "gsc_sync_time": "Orario sync (HH:MM)",
  "ai_reports_enabled": "Abilita report AI automatici",
  "ai_reports_day": "Giorno settimana (0-6)",
  "ai_reports_time": "Orario generazione (HH:MM)"
}
```

### Nuovi Metodi GscService
- `syncTrackedKeywordsOnly(int $projectId)`: sync metriche GSC solo per keyword tracciate (is_tracked=1)

### File Rimossi/Obsoleti
- `daily-sync.php` - legacy, sostituito dai dispatcher
