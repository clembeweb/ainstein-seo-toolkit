# SEO POSITION TRACKING - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-tracking` |
| **Prefisso DB** | `st_` |
| **Files** | 53 |
| **Stato** | 🔄 In corso (80%) |
| **Ultimo update** | 2026-01-02 |

Modulo per monitoraggio posizionamento keyword, traffico organico e revenue con dati GSC + GA4 e report AI.

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
│   ├── GscController.php          # OAuth GSC
│   ├── Ga4Controller.php          # Service Account GA4
│   ├── AlertController.php
│   ├── ReportController.php
│   └── AiController.php
├── models/
│   ├── Project.php
│   ├── Keyword.php
│   ├── KeywordTracking.php
│   ├── GscConnection.php
│   ├── GscData.php
│   ├── Ga4Connection.php
│   ├── Ga4Data.php
│   ├── Alert.php
│   └── AiReport.php
├── services/
│   ├── GscService.php
│   ├── Ga4Service.php
│   ├── KeywordMatcher.php
│   ├── RevenueAttributor.php
│   ├── AlertService.php
│   └── AiReportService.php
└── views/
    ├── projects/
    ├── dashboard/
    ├── keywords/
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
st_ga4_connections       -- Service Account GA4

-- Keyword tracking
st_keywords              -- Keyword monitorate
st_keyword_positions     -- Snapshot giornalieri
st_gsc_data              -- Dati storici GSC

-- GA4 data
st_ga4_data              -- Dati per landing page
st_ga4_daily             -- Aggregati giornalieri

-- Revenue attribution
st_keyword_revenue       -- Attribuzione revenue

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
- [x] OAuth GSC (da testare end-to-end)
- [x] Views dashboard
- [x] Views keyword detail

### 🔄 In Corso
- [ ] Test OAuth GSC completo
- [ ] GA4 Service Account upload
- [ ] Sync dati automatico

### ❌ Da Implementare
- [ ] Sistema alert
- [ ] Report AI automatici
- [ ] Revenue attribution
- [ ] Email notifiche

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

// Connessioni
GET  /seo-tracking/projects/{id}/gsc/connect          # OAuth start
GET  /oauth/google/callback                           # OAuth callback (centralizzato)
POST /seo-tracking/projects/{id}/gsc/save-property    # Salva proprietà
POST /seo-tracking/projects/{id}/gsc/sync             # Sync dati

// GA4
GET  /seo-tracking/projects/{id}/ga4/connect          # Form upload JSON
POST /seo-tracking/projects/{id}/ga4/upload           # Upload Service Account
```

---

## Bug Aperti

| Bug | Severity | File | Status |
|-----|----------|------|--------|
| OAuth callback test | HIGH | GscController | Test pending |
| GA4 upload JSON | MEDIUM | Ga4Controller | Non testato |

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
2. **GA4** usa Service Account (non OAuth utente)
3. **AiReportService** deve usare AiService centralizzato (verificare)
4. **Cron jobs** per sync giornaliero (da implementare)

---

*Spec aggiornata - 2026-01-02*
