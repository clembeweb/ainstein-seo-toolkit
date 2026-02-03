# SEO POSITION TRACKING - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-tracking` |
| **Prefisso DB** | `st_` |
| **Files** | 62 |
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
│   ├── RankJob.php                # Job tracking per rank check (SSE)
│   ├── RankQueue.php              # Queue keyword per rank check
│   ├── Alert.php
│   └── AiReport.php
├── services/
│   ├── GscService.php             # OAuth GSC + sync metriche
│   ├── GroupStatsService.php      # Statistiche gruppi
│   ├── PositionCompareService.php # Confronto posizioni
│   ├── RankCheckerService.php     # Orchestratore rank check con SSE
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

-- Rank Check (Background Jobs)
st_rank_jobs             -- Job tracking (pending/running/completed/cancelled)
st_rank_queue            -- Queue keyword per job
st_rank_checks           -- Risultati rank check

-- Alert system
st_alert_settings        -- Config alert
st_alerts                -- Log alert

-- AI Reports
st_ai_reports            -- Report generati
st_sync_log              -- Log sync
```

---

## Provider Rank Check

Il sistema supporta 3 provider in cascata per il rank checking:

| Priorità | Provider | Endpoint | Costo |
|----------|----------|----------|-------|
| 1 (Primary) | DataForSEO | `/serp/google/organic/live/regular` | ~$0.002/ricerca |
| 2 (Fallback) | SerpAPI | `/search.json` | ~$0.005/ricerca |
| 3 (Fallback) | Serper.dev | `/search` | ~$0.001/ricerca |

### Logica Cascata
```php
// RankCheckerService::checkPosition()
1. Prova DataForSEO se configurato
2. Se fallisce (errore/no crediti) → prova SerpAPI
3. Se fallisce → prova Serper.dev
4. Se tutti falliscono → ritorna errore
```

### Configurazione Provider
- **DataForSEO**: API key in Settings → Moduli → SEO Tracking
- **SerpAPI**: API key separata
- **Serper.dev**: API key separata

### Depth Ricerca
Tutti i provider cercano fino a **100 risultati** (10 pagine x 10 risultati).

---

## API Logging Integration

Tutte le chiamate API del modulo sono loggate in `api_logs` via `ApiLoggerService`.

### File Integrati
- `services/DataForSeoService.php` - Log chiamate SERP e keyword volumes
- `modules/seo-tracking/services/RankCheckerService.php` - Log per tutti i provider

### Dati Loggati
- Request/response payload completi
- Durata chiamata in ms
- Costo API (se disponibile)
- Context con keyword e target domain

### Visualizzazione
Admin → API Logs (`/admin/api-logs`)
- Filtro per provider: `dataforseo`, `serpapi`, `serper`
- Filtro per modulo: `seo-tracking`

---

## Funzionalità

### ✅ Implementate
- [x] CRUD progetti (semplificato: solo nome + dominio)
- [x] CRUD keyword tracking (solo keyword manuali, no auto-discovery)
- [x] **Keyword Groups** (raggruppamento M:N con statistiche)
- [x] **Position Compare** (confronto posizioni tra periodi - stile SEMrush)
- [x] **SEO Page Analyzer** (analisi AI singola pagina con suggerimenti)
- [x] **Quick Wins Finder** (keyword pos 11-20 facili da spingere)
- [x] **Rank Check manuale** via DataForSEO API con job in background (SSE streaming)
- [x] **Rank Check automatico** (cron admin-configured)
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
- [x] **Import keyword da GSC** con selezione e assegnazione gruppi
- [x] **DataForSEO come provider SERP primario**
- [x] **Cascata automatica provider** (DataForSEO → SerpAPI → Serper)
- [x] **Ricerca fino a 100 posizioni**
- [x] **API Logging per tutte le chiamate**

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

// Rank Check (Background Jobs + SSE)
GET  /seo-tracking/projects/{id}/rank-check            # Vista rank check
POST /seo-tracking/projects/{id}/rank-check/start      # Avvia job rank check
GET  /seo-tracking/projects/{id}/rank-check/stream     # SSE streaming progress
GET  /seo-tracking/projects/{id}/rank-check/status     # Polling fallback status
POST /seo-tracking/projects/{id}/rank-check/cancel     # Annulla job

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

### 2026-02-03
- Aggiunto DataForSEO come provider SERP primario
- Implementata cascata provider per rank checking
- Estesa ricerca SERP a 100 posizioni (era 50)
- Integrato ApiLoggerService per logging chiamate API
- Aggiunto pannello admin API Logs

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
| `migrations/012_rank_jobs.sql` | ✅ Tabelle st_rank_jobs, st_rank_queue, st_rank_checks |

---

## Prossimi Step / TODO

### 🔄 Alert Backend (Priorità: MEDIA)

**Stato attuale:** UI presente in `views/alerts/`, backend parziale in `AlertController.php` e `AlertService.php`.

**Da completare:**
1. **AlertService.php** - Implementare metodi:
   ```php
   public function checkAlerts(int $projectId): array    // Verifica condizioni alert
   public function triggerAlert(int $alertId, array $data): void  // Scatta alert
   public function getActiveAlerts(int $projectId): array  // Lista alert attivi
   ```

2. **AlertController.php** - Completare endpoints:
   - `POST /projects/{id}/alerts` - Crea alert
   - `PUT /projects/{id}/alerts/{alertId}` - Modifica alert
   - `DELETE /projects/{id}/alerts/{alertId}` - Elimina alert
   - `POST /projects/{id}/alerts/{alertId}/test` - Test alert

3. **Tipi di alert da supportare:**
   - Posizione scende sotto soglia (es: keyword esce da top 10)
   - Posizione sale sopra soglia (es: keyword entra in top 3)
   - Variazione percentuale (es: -20% click settimana)
   - Keyword perde/guadagna posizioni

4. **Tabelle DB già presenti:**
   - `st_alert_settings` - Configurazione alert
   - `st_alerts` - Log alert scattati

---

### ❌ Email Notifiche Alert (Priorità: MEDIA)

**Dipende da:** Alert Backend completato

**Da implementare:**
1. Creare `services/EmailService.php` (se non esiste) o usare esistente
2. Template email in `views/emails/alert-notification.php`
3. Integrare in `AlertService::triggerAlert()`:
   ```php
   if ($alert['notify_email']) {
       $emailService->sendAlertNotification($project, $alert, $data);
   }
   ```
4. Configurare SMTP in `.env` (già documentato in DEPLOY.md)

---

### ❌ Monthly Executive Report (Priorità: BASSA)

**Da implementare:**
1. **AiReportService.php** - Aggiungere metodo:
   ```php
   public function generateMonthlyExecutive(int $projectId, int $userId): array
   ```

2. **Prompt AI** per report mensile (più dettagliato del weekly):
   - Trend mensili vs mese precedente
   - Top 10 keyword performance
   - Opportunità identificate
   - Raccomandazioni strategiche

3. **Cron dispatcher** - Aggiungere in `ai-report-dispatcher.php`:
   ```php
   // Check se è il primo del mese
   if (date('j') === '1' && $this->shouldRunMonthly()) {
       $this->generateMonthlyReports();
   }
   ```

4. **Admin settings** in `module.json`:
   ```json
   "monthly_report_enabled": { ... },
   "monthly_report_day": { ... },
   "monthly_report_time": { ... }
   ```

---

### ✅ Completato (Reference)

1. **Redirect URI** già configurato: `https://ainstein.it/oauth/google/callback`
2. **OAuth GSC** funzionante e testato
3. **GA4 rimosso** - non più in scope

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

### Rank Check con Job in Background (2026-02-03)
Sistema completo per rank check massivo con progress real-time:

**Componenti:**
- `RankCheckController.php` - Controller con SSE streaming
- `RankCheckerService.php` - Orchestratore elaborazione
- `RankJob.php` - Model job tracking
- `RankQueue.php` - Model queue keyword
- `DataForSeoService.php` - API wrapper (condiviso)

**Flusso:**
1. Utente seleziona keyword e avvia rank check
2. Controller crea job in `st_rank_jobs` (status: pending)
3. Keyword aggiunte a `st_rank_queue` con job_id
4. SSE stream elabora queue e invia eventi real-time
5. Risultati salvati in `st_rank_checks`
6. Job marcato completed/error

**Eventi SSE:**
- `started` - Job avviato con totale items
- `progress` - Aggiornamento % completamento
- `item_completed` - Singola keyword completata
- `item_error` - Errore su keyword
- `completed` - Job terminato
- `cancelled` - Job annullato

**Pattern implementato:** Vedi CLAUDE.md sezione "Background Processing"

**Routes:**
```
GET  /projects/{id}/rank-check         # Vista
POST /projects/{id}/rank-check/start   # Avvia job
GET  /projects/{id}/rank-check/stream  # SSE streaming
GET  /projects/{id}/rank-check/status  # Polling fallback
POST /projects/{id}/rank-check/cancel  # Annulla
```

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
