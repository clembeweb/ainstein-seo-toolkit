# AGENTE: SEO Position Tracking

> **Ultimo aggiornamento:** 2026-01-12

## CONTESTO

**Modulo:** `seo-tracking`
**Stato:** 95% In corso
**Prefisso DB:** `st_`

Modulo per monitoraggio posizionamento keyword con:
- Dati Google Search Console (OAuth)
- Dati Google Analytics 4 (Service Account)
- Tracking posizioni giornaliero
- **Keyword Groups** (raggruppamento M:N con statistiche)
- Sistema alert
- Report AI automatici
- Revenue attribution

---

## FILE CHIAVE

```
modules/seo-tracking/
├── routes.php                              # ✅ Routes aggiornate
├── controllers/
│   ├── ProjectController.php
│   ├── DashboardController.php
│   ├── KeywordController.php
│   ├── GroupController.php                 # ✅ NEW - Gestione gruppi keyword
│   ├── GscController.php                   # OAuth GSC
│   ├── Ga4Controller.php                   # Service Account GA4
│   ├── AlertController.php
│   ├── ReportController.php
│   └── AiController.php
├── models/
│   ├── Project.php
│   ├── Keyword.php
│   ├── KeywordGroup.php                    # ✅ NEW - Model gruppi M:N
│   ├── GscConnection.php
│   ├── Ga4Connection.php
│   └── AiReport.php
├── services/
│   ├── GscService.php
│   ├── Ga4Service.php
│   ├── GroupStatsService.php               # ✅ NEW - Statistiche gruppi
│   ├── AlertService.php
│   └── AiReportService.php                 # ⚠️ Verificare uso AiService
├── views/groups/                           # ✅ NEW - Views gruppi
│   ├── index.php
│   ├── create.php
│   ├── show.php
│   └── edit.php
└── cron/
    ├── daily-sync.php
    ├── weekly-reports.php
    └── monthly-reports.php
```

---

## DATABASE

| Tabella | Descrizione |
|---------|-------------|
| `st_projects` | Progetti tracking |
| `st_keywords` | Keyword monitorate |
| `st_keyword_groups` | ✅ NEW - Gruppi di keyword |
| `st_keyword_group_members` | ✅ NEW - Relazione M:N keyword-gruppo |
| `st_keyword_positions` | Snapshot giornalieri |
| `st_gsc_connections` | Token OAuth GSC |
| `st_gsc_data` | Dati storici GSC |
| `st_gsc_daily` | Aggregati giornalieri |
| `st_ga4_connections` | Service Account GA4 |
| `st_ga4_data` | Dati per landing page |
| `st_ga4_daily` | Aggregati giornalieri |
| `st_keyword_revenue` | Attribuzione revenue |
| `st_alert_settings` | Config alert |
| `st_alerts` | Log alert |
| `st_ai_reports` | Report AI generati |
| `st_sync_log` | Log sync |

---

## BUG RISOLTI (2026-01-07)

| Bug | Severity | File | Status |
|-----|----------|------|--------|
| OAuth GSC callback | HIGH | GscController.php | ✅ Fixato |
| Redirect URI dinamico | HIGH | GoogleOAuthService.php | ✅ Fixato |
| Token refresh mismatch | HIGH | GscService.php | ✅ Fixato |
| Schema DB mismatch | HIGH | migrations/full_schema_fix.sql | ✅ Fixato |
| Route selectProperty | LOW | routes.php | ✅ Fixato |
| GA4 Service Account | MEDIUM | Ga4Controller.php | 🔄 Da testare |

### Fix Schema DB Applicati

Script: `modules/seo-tracking/migrations/full_schema_fix.sql`

| Tabella | Fix |
|---------|-----|
| `st_projects` | Aggiunto `'failed'` a ENUM sync_status |
| `st_gsc_connections` | property_url/property_type nullable |
| `st_gsc_data` | query/page nullable |
| `st_sync_log` | Aggiunto sync_type gsc_daily/ga4_daily/gsc_full |
| `st_alerts` | Aggiunto severity high/medium |
| `st_ai_reports` | Aggiunto report_type anomaly_analysis |
| `st_keywords` | Aggiunte colonne: is_tracked, source, notes, target_position |
| `st_keywords` | Rinominato keyword_group → group_name |

### Fix Codice Applicati

| File | Fix |
|------|-----|
| `GscService.php:143` | Usato `updateAccessToken()` invece di `updateTokens()` |
| `GscService.php:204,235,252` | Cambiato `site_url` → `property_url` |
| `GscData.php:35-42` | Aggiunto null coalescing per campi opzionali |
| `GscConnection.php:29` | Aggiunto metodo `getByProject()` |
| `Keyword.php` | Rinominato `keyword_group` → `group_name` |
| `GoogleOAuthService.php:57-64` | Redirect URI dinamico multi-dominio |

---

## FEATURE: KEYWORD GROUPS (2026-01-07)

Raggruppamento keyword con relazione many-to-many e statistiche aggregate.

### Routes

```
GET  /seo-tracking/projects/{id}/groups               # Lista gruppi
GET  /seo-tracking/projects/{id}/groups/create        # Form crea
POST /seo-tracking/projects/{id}/groups/store         # Salva
GET  /seo-tracking/projects/{id}/groups/{gId}         # Dettaglio
GET  /seo-tracking/projects/{id}/groups/{gId}/edit    # Form modifica
POST /seo-tracking/projects/{id}/groups/{gId}/update  # Aggiorna
POST /seo-tracking/projects/{id}/groups/{gId}/delete  # Elimina
POST /seo-tracking/projects/{id}/groups/{gId}/add-keyword     # API AJAX
POST /seo-tracking/projects/{id}/groups/{gId}/remove-keyword  # API AJAX
GET  /seo-tracking/api/projects/{id}/groups/{gId}/chart       # API grafico
```

### Schema DB

```sql
-- Migration: migrations/001_keyword_groups.sql

st_keyword_groups (
    id, project_id, name, description, color,
    is_active, sort_order, created_at, updated_at
)

st_keyword_group_members (
    id, group_id, keyword_id, added_at
)
```

### Funzionalità

- CRUD gruppi con colori personalizzabili
- Selezione multipla keyword (M:N)
- Statistiche aggregate: posizione media, click, impressioni
- Distribuzione posizioni (Top 3, Top 10, etc.)
- Confronto storico 7 giorni
- Top performers e movers per gruppo
- Grafico trend temporale

---

## GOLDEN RULES SPECIFICHE

1. **OAuth GSC** - Usa GoogleOAuthService centralizzato (condiviso con seo-audit)
2. **GA4** - Service Account JSON, NON OAuth utente
3. **AiReportService** - DEVE usare AiService('seo-tracking'), verificare!
4. **Route pattern** - `/seo-tracking/projects/{id}/...` (NON `/seo-tracking/{id}/...`)
5. **Cron jobs** - daily-sync.php per sync automatico
6. **Crediti** - Usare `Credits::getCost()` per costi dinamici (configurabili da admin):
   ```php
   $cost = Credits::getCost('gsc_full_sync', 'seo-tracking');
   $cost = Credits::getCost('quick_wins', 'seo-tracking');
   $cost = Credits::getCost('weekly_digest', 'seo-tracking');
   $cost = Credits::getCost('monthly_executive', 'seo-tracking');
   ```
   Vedi: `docs/core/CREDITS-SYSTEM.md`

---

## PROMPT PRONTI

### 1. Fix OAuth GSC callback
```
Fix OAuth callback in modules/seo-tracking/controllers/GscController.php

PROBLEMA: Callback dopo autorizzazione Google non funziona

VERIFICA:
1. Redirect URI in Google Cloud Console: https://ainstein.it/oauth/google/callback
2. GoogleOAuthService.php gestisce callback
3. Token salvato in st_gsc_connections
4. Redirect a select-property dopo successo

FILE:
- controllers/GscController.php
- services/GoogleOAuthService.php (condiviso)
```

### 2. Verificare AiReportService
```
Verifica che AiReportService.php usi AiService centralizzato

FILE: modules/seo-tracking/services/AiReportService.php

DEVE:
- Usare: new AiService('seo-tracking')
- NON usare: curl_init, file_get_contents per API
- Chiamare Database::reconnect() dopo AI

Se usa curl diretto, refactoring completo.
```

### 3. Implementare GA4 upload
```
Completa upload Service Account GA4

FILE:
- controllers/Ga4Controller.php
- services/Ga4Service.php

FLOW:
1. Upload file JSON credentials
2. Validare formato
3. Test connessione
4. Salvare in st_ga4_connections (criptato)
5. Sync dati iniziale
```

### 4. Fix route mismatches
```
Allinea routes e controller methods in seo-tracking

FILE: routes.php + controllers/KeywordController.php

PROBLEMI NOTI:
- Metodo add() non esiste
- Signature update() non corrisponde
- Redirect paths errati

Pattern corretto: /seo-tracking/projects/{id}/keywords/...
```

### 5. Implementare sistema alert
```
Completa sistema alert in modules/seo-tracking/

FILE:
- controllers/AlertController.php
- services/AlertService.php
- models/Alert.php, AlertSettings.php

TIPI ALERT:
- Posizione scesa > X posizioni
- Keyword uscita da top 10
- Click -20% vs settimana precedente
- Nuova keyword in top 10

Notifiche: email via SMTP
```
