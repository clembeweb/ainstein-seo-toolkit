# AGENTE: SEO Position Tracking

## CONTESTO

**Modulo:** `seo-tracking`
**Stato:** 80% In corso
**Prefisso DB:** `st_`

Modulo per monitoraggio posizionamento keyword con:
- Dati Google Search Console (OAuth)
- Dati Google Analytics 4 (Service Account)
- Tracking posizioni giornaliero
- Sistema alert
- Report AI automatici
- Revenue attribution

---

## FILE CHIAVE

```
modules/seo-tracking/
├── routes.php                              # ⚠️ Verificare route patterns
├── controllers/
│   ├── ProjectController.php
│   ├── DashboardController.php
│   ├── KeywordController.php               # ⚠️ Route mismatch
│   ├── GscController.php                   # OAuth GSC
│   ├── Ga4Controller.php                   # Service Account GA4
│   ├── AlertController.php
│   ├── ReportController.php
│   └── AiController.php
├── models/
│   ├── Project.php
│   ├── Keyword.php
│   ├── GscConnection.php
│   ├── Ga4Connection.php
│   └── AiReport.php
├── services/
│   ├── GscService.php
│   ├── Ga4Service.php
│   ├── AlertService.php
│   └── AiReportService.php                 # ⚠️ Verificare uso AiService
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

## BUG APERTI

| Bug | Severity | File | Status |
|-----|----------|------|--------|
| OAuth GSC callback | HIGH | GscController.php | Test pending |
| GA4 Service Account | MEDIUM | Ga4Controller.php | Non testato |
| Route selectProperty | LOW | routes.php | ✅ Fixato |

**Problema principale:** OAuth callback non testato end-to-end in produzione.

---

## GOLDEN RULES SPECIFICHE

1. **OAuth GSC** - Usa GoogleOAuthService centralizzato (condiviso con seo-audit)
2. **GA4** - Service Account JSON, NON OAuth utente
3. **AiReportService** - DEVE usare AiService('seo-tracking'), verificare!
4. **Route pattern** - `/seo-tracking/projects/{id}/...` (NON `/seo-tracking/{id}/...`)
5. **Cron jobs** - daily-sync.php per sync automatico
6. **Crediti:**
   - GSC full sync (16 mesi): 10 crediti
   - Weekly digest AI: 5 crediti
   - Monthly executive AI: 15 crediti
   - Keyword analysis AI: 5 crediti

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
