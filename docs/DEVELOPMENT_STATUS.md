# AINSTEIN - Stato Sviluppo

**Ultimo aggiornamento:** 2025-01-02
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

| Modulo | Slug | Stato | Note |
|--------|------|-------|------|
| AI SEO Content Generator | `ai-content` | 98% | Wizard completo |
| Internal Links Analyzer | `internal-links` | 85% | 39 Lucide icons da migrare |
| SEO Audit | `seo-audit` | 90% | Bug logica crawl |
| SEO Position Tracking | `seo-tracking` | 70% | Bug routes/controller |
| AI Content Bulk Creator | `content-creator` | 0% | Non implementato |

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

## DA FARE (Bug Fix)

### CRITICAL (seo-tracking)
- [ ] AiReportService -> usa AiService centralizzato
- [ ] KeywordController metodi mancanti (add, all)
- [ ] Redirect paths errati

### HIGH (internal-links)
- [ ] Migrare 39 Lucide icons -> Heroicons SVG
- [x] ~~Rimuovere dropdown modello AI deprecato~~ (fatto 2025-01-02)

### MEDIUM
- [ ] Creare SiteConfig model in seo-audit
- [ ] Fix logica status crawl

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
| File PHP | ~200 |
| Moduli attivi | 4 |
| Tabelle DB | 52 |
| Lucide icons da fixare | 39 |

---

*Aggiornato: 2025-01-02 - Fix WordPress, Database, Brief persistence*
