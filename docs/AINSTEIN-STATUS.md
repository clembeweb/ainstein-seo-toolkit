# AINSTEIN - Stato Progetto

**Ultimo aggiornamento:** 2026-01-02
**Ambiente produzione:** https://ainstein.it
**Repository:** https://github.com/clembeweb/ainstein-seo-toolkit.git

---

## 🎯 OVERVIEW

Ainstein è una piattaforma SaaS modulare per tool SEO con integrazione AI.
Target: agenzie, e-commerce, freelancer, consulenti digitali.
Pricing model: simile a SEMrush (~50-60€/mese).

| Aspetto | Dettaglio |
|---------|-----------|
| **Stack** | PHP 8.0+, MySQL, Tailwind CSS, Alpine.js, HTMX |
| **AI Provider** | Claude API (Anthropic) + OpenAI (fallback) |
| **Hosting** | SiteGround GoGeek |
| **Lingua UI** | Italiano |

---

## 📊 STATO MODULI (Aggiornato 2 Gen 2026)

| Modulo | Slug | Prefisso DB | Files | Stato | % |
|--------|------|-------------|-------|-------|---|
| AI SEO Content Generator | `ai-content` | `aic_` | 23 | ✅ Funzionante | 98% |
| SEO Audit | `seo-audit` | `sa_` | 26 | ✅ Completato | 95% |
| Google Ads Analyzer | `ads-analyzer` | `ga_` | 24 | 🆕 Nuovo | 90% |
| Internal Links Analyzer | `internal-links` | `il_` | 28 | ⚠️ Parziale | 85% |
| SEO Position Tracking | `seo-tracking` | `st_` | 53 | 🔄 In corso | 80% |
| AI Content Bulk Creator | `content-creator` | `cc_` | 0 | ❌ Non impl. | 0% |

---

## ✅ COMPLETATI OGGI (2 Gen 2026)

### seo-audit
- [x] Database 12 tabelle `sa_*`
- [x] CrawlerService (sitemap + spider)
- [x] IssueDetector (50+ tipi issue)
- [x] AiAnalysisService integrato
- [x] GscService OAuth2
- [x] Dashboard con health score
- [x] Export CSV

### ai-content
- [x] Fix "MySQL server has gone away" (Database::reconnect)
- [x] Fix UI preview articolo (classe `prose` Tailwind)
- [x] Card Keywords cliccabile in dashboard

### ads-analyzer (NUOVO)
- [x] Struttura modulo completa
- [x] Parser CSV Google Ads (formato IT)
- [x] KeywordAnalyzerService con AI
- [x] Categorie negative dinamiche
- [x] Export per Google Ads Editor

### Core
- [x] GoogleOAuthService centralizzato
- [x] Database::reconnect() per operazioni lunghe
- [x] ScraperService improvements

---

## 🔴 BUG APERTI

### seo-tracking (Priorità ALTA)
| Bug | File | Status |
|-----|------|--------|
| OAuth GSC callback | GscController.php | Test pending |
| GA4 Service Account | Ga4Controller.php | Non testato |
| Route selectProperty | routes.php | ✅ Fixato |

### internal-links (Priorità MEDIA)
| Bug | File | Status |
|-----|------|--------|
| Icone Lucide | views/*.php | 39 occorrenze |
| Dropdown modello AI | views/analysis/index.php | Rimuovere |

---

## 🎯 PROSSIMI STEP

### Priorità 1: OAuth GSC
1. Configurare redirect URI in Google Cloud Console:
   `https://ainstein.it/oauth/google/callback`
2. Test flusso completo seo-tracking
3. Test flusso seo-audit

### Priorità 2: Test Produzione
1. Test completo seo-audit con sito reale
2. Test ads-analyzer con export CSV
3. Verificare logging AI in admin

### Priorità 3: Fix UI
1. Migrare icone Lucide → Heroicons (internal-links)
2. Rimuovere dropdown modello AI

### Priorità 4: Nuovi Moduli
1. Implementare content-creator

---

## 🔧 SERVIZI CONDIVISI

| Servizio | Descrizione | Usato da |
|----------|-------------|----------|
| `AiService.php` | Chiamate AI con logging | Tutti |
| `ScraperService.php` | HTTP client + DOM parser | ai-content, seo-audit |
| `GoogleOAuthService.php` | OAuth2 centralizzato | seo-audit, seo-tracking |
| `SitemapService.php` | Parser sitemap/robots.txt | seo-audit, internal-links |
| `ExportService.php` | Export CSV/PDF | seo-audit, ads-analyzer |
| `CsvImportService.php` | Parser CSV | ads-analyzer |

---

## 📁 STRUTTURA PROGETTO

```
C:\laragon\www\seo-toolkit\          # Locale
~/www/ainstein.it/public_html/       # Produzione

├── core/                    # Framework core
├── services/                # Servizi condivisi
├── modules/
│   ├── ai-content/         # 23 files
│   ├── seo-audit/          # 26 files
│   ├── ads-analyzer/       # 24 files (NUOVO)
│   ├── internal-links/     # 28 files
│   └── seo-tracking/       # 53 files
├── shared/views/            # Layout, sidebar, components
├── config/                  # Configurazione
├── docs/                    # Documentazione interna
└── storage/                 # Logs, cache
```

---

## 🔐 CREDENZIALI E ACCESSI

### SSH Produzione
```bash
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
```

### Database Produzione
- Host: localhost
- DB: dbj0xoiwysdlk1
- User: u6iaaermphtha

### Google OAuth
- Redirect URI: `https://ainstein.it/oauth/google/callback`
- Scopes: webmasters.readonly, analytics.readonly

---

## 📈 STATISTICHE

| Metrica | Valore |
|---------|--------|
| File PHP totali | 154 |
| Moduli attivi | 5 |
| Moduli da implementare | 1 |
| Tabelle DB | ~40 |

---

*Documento aggiornato automaticamente - 2026-01-02*
