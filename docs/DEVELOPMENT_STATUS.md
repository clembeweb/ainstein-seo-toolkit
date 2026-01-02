# AINSTEIN - Stato Sviluppo

**Ultimo aggiornamento:** 2026-01-02
**Deploy:** LIVE su https://ainstein.it

---

## STATO DEPLOY

| Ambiente | URL | Status |
|----------|-----|--------|
| **Produzione** | https://ainstein.it | ONLINE |
| **Locale** | http://localhost/seo-toolkit/public | Dev |
| **Repository** | github.com/clembeweb/ainstein-seo-toolkit | Sync |

---

## STATO MODULI

| Modulo | Slug | Files | Stato | Note |
|--------|------|-------|-------|------|
| AI SEO Content Generator | `ai-content` | 23 | 98% | Wizard completo, fix MySQL timeout |
| Internal Links Analyzer | `internal-links` | 28 | 85% | 39 Lucide icons da migrare |
| SEO Audit | `seo-audit` | 26 | 95% | Crawler, Issues, AI, GSC OAuth |
| SEO Position Tracking | `seo-tracking` | 53 | 80% | OAuth centralizzato, fix routes |
| Google Ads Analyzer | `ads-analyzer` | 24 | 90% | Nuovo modulo, CSV analysis |
| AI Content Bulk Creator | `content-creator` | 0 | 0% | Non implementato |

---

## COMPLETATO (Deploy 2025-12-30)

- [x] Sistema environment.php con costante ENV_LOADED
- [x] .env per credenziali (locale + produzione separati)
- [x] Database 52 tabelle importate su SiteGround
- [x] SSH + Git configurato
- [x] Document root configurato con .htaccess
- [x] HTTPS attivo
- [x] Router.php basePath fix per produzione
- [x] Users sincronizzati (admin + user)

---

## FIX COMPLETATI (2025-01-02)

### 1. WordPress Connector - Invalid JSON Response
| Problema | Causa | Soluzione |
|----------|-------|-----------|
| "Invalid JSON response" | WAF SiteGround blocca User-Agent Chrome simulato | Aggiunto `api_mode` in ScraperService |

**File modificati:**
- `services/ScraperService.php` - opzione `api_mode` per bypass browser headers, smart merge headers
- `modules/ai-content/controllers/WordPressController.php` - usa `api_mode: true`

### 2. Database Auto-Reconnect
| Problema | Causa | Soluzione |
|----------|-------|-----------|
| "MySQL server has gone away" | Connessione scade durante chiamate AI lunghe | Auto-reconnect con retry logic |

**File modificati:**
- `core/Database.php` - metodi `ping()`, `reconnect()`, `isGoneAway()`, retry su tutte le query

### 3. Brief Persistence
| Problema | Causa | Soluzione |
|----------|-------|-----------|
| Brief perso tornando indietro | Non salvato in DB | Salvataggio in `aic_keywords` |

**File modificati:**
- `modules/ai-content/models/Keyword.php` - metodi `saveBrief()`, `getBrief()`, `hasBrief()`
- `modules/ai-content/controllers/WizardController.php` - salva brief dopo generazione
- `modules/ai-content/controllers/KeywordController.php` - ripristina brief esistente
- `modules/ai-content/views/keywords/wizard.php` - carica brief salvato

**Colonne aggiunte a `aic_keywords`:**
```sql
brief_outline, brief_sources, brief_suggestions,
brief_generated_at, brief_model, brief_tokens_used
```

### 4. WordPress Dropdown Fix
| Problema | Causa | Soluzione |
|----------|-------|-----------|
| Dropdown vuoto in articoli | `wpSites` non passato alle view | Aggiunto in ArticleController |

**File modificati:**
- `modules/ai-content/controllers/ArticleController.php` - passa `wpSites` a index() e show()

---

## FIX COMPLETATI (2026-01-02)

### 5. OAuth Google Centralizzato
| Problema | Causa | Soluzione |
|----------|-------|-----------|
| Redirect URI diversi per modulo | Ogni modulo aveva callback proprio | Unico callback `/oauth/google/callback` |

**File creati:**
- `services/GoogleOAuthService.php` - servizio OAuth centralizzato
- `controllers/OAuthController.php` - callback centralizzato

**File modificati:**
- `public/index.php` - aggiunta route OAuth e autoloader Controllers
- `admin/views/settings.php` - redirect URI readonly con copy button
- `modules/seo-tracking/routes.php` - usa callback centralizzato
- `modules/seo-tracking/controllers/GscController.php` - usa GoogleOAuthService

### 6. Fix Routes GSC seo-tracking
| Problema | Causa | Soluzione |
|----------|-------|-----------|
| POST select-property non salvava | Chiamava `selectProperty()` invece di `saveProperty()` | Corretto mapping route→controller |
| sync-full method mismatch | Chiamava `syncFull()` invece di `fullSync()` | Corretto nome metodo |

**File modificati:**
- `modules/seo-tracking/routes.php` - fix POST select-property, fix sync-full, aggiunto GET select-property

---

## DA FARE (Bug Fix)

### CRITICAL (seo-tracking)
- [x] ~~Fix route selectProperty → saveProperty~~ (fatto 2026-01-02)
- [x] ~~Fix route syncFull → fullSync~~ (fatto 2026-01-02)
- [ ] Test completo OAuth GSC end-to-end
- [ ] Test GA4 Service Account upload
- [ ] AiReportService -> usa AiService centralizzato

### HIGH (internal-links)
- [ ] Migrare 39 Lucide icons -> Heroicons SVG
- [x] ~~Rimuovere dropdown modello AI deprecato~~ (fatto 2025-01-02)

### MEDIUM
- [ ] Creare SiteConfig model in seo-audit
- [ ] Fix logica status crawl
- [ ] Configurare redirect URI in Google Cloud Console

---

## STRUTTURA AMBIENTE

### File Configurazione
```
config/
├── app.php           # Config app (usa env())
├── database.php      # Config DB (usa env())
├── environment.php   # Carica .env, definisce ENV_LOADED
└── modules.php       # Moduli attivi
```

### Variabili .env
```
APP_NAME=Ainstein
APP_URL=https://ainstein.it  # o localhost/seo-toolkit per dev
APP_DEBUG=false              # true per dev

DB_HOST=localhost
DB_NAME=xxx
DB_USER=xxx
DB_PASS=xxx
```

### API Keys
Gestite in **Admin > Settings** (tabella `settings`), NON in .env:
- anthropic_api_key
- serpapi_key
- gsc_client_id / gsc_client_secret
- openai_api_key (opzionale)

---

## CREDENZIALI (riferimento)

### SiteGround SSH
```
Host: ssh.ainstein.it
Port: 18765
User: u1608-ykgnd3z1twn4
Key: siteground_key (locale, in .gitignore)
```

### Database Produzione
```
Host: localhost
Name: dbj0xoiwysdlk1
User: u6iaaermphtha
```

---

## COMANDI UTILI

### Deploy updates
```bash
# Locale -> GitHub -> Produzione
git add -A && git commit -m "description" && git push origin main
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it "cd ~/www/ainstein.it/public_html && git pull"
```

### Logs produzione
```bash
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it "tail -50 ~/logs/ainstein.it.error.log"
```

### Sync DB locale -> produzione
```bash
mysqldump -u root seo_toolkit > backup.sql
scp -i siteground_key -P 18765 backup.sql u1608-ykgnd3z1twn4@ssh.ainstein.it:~/
ssh ... "mysql -u USER -pPASS DB < ~/backup.sql"
```

---

## STATISTICHE

| Metrica | Valore |
|---------|--------|
| File PHP totali | ~154 (solo moduli) |
| Moduli attivi | 5 |
| Tabelle DB | 52+ |
| Lucide icons da fixare | 39 |

### File per modulo
| Modulo | Files PHP |
|--------|-----------|
| seo-tracking | 53 |
| internal-links | 28 |
| seo-audit | 26 |
| ads-analyzer | 24 |
| ai-content | 23 |

---

## FILE MODIFICATI OGGI (41 files)

**Core/Services:**
- services/ScraperService.php
- services/GoogleOAuthService.php (NEW)
- controllers/OAuthController.php (NEW)
- public/index.php
- core/Database.php
- admin/views/settings.php

**ads-analyzer (nuovo modulo):**
- 24 file totali (controllers, services, models, views, routes)

**ai-content:**
- controllers/KeywordController.php, WizardController.php, ArticleController.php, WordPressController.php
- models/Keyword.php
- views/keywords/wizard.php

**seo-tracking:**
- controllers/GscController.php
- routes.php

**seo-audit:**
- services/GscService.php

---

*Aggiornato: 2026-01-02 - OAuth centralizzato, fix routes GSC, nuovo modulo ads-analyzer*
