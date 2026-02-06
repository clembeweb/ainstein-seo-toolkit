# AINSTEIN - Stato Progetto

**Ultimo aggiornamento:** 2026-02-06
**Ambiente produzione:** https://ainstein.it
**Repository:** https://github.com/clembeweb/ainstein-seo-toolkit.git

---

## VISION

> **"Ainstein non ti mostra i problemi SEO. Li risolve per te."**

Non siamo un SEO tool con AI appiccicata. Siamo un **AI-agent per SEO** che usa i tool come input.

📄 Vedi: [STRATEGY.md](./STRATEGY.md) per posizionamento completo
📄 Vedi: [ROADMAP.md](./ROADMAP.md) per piano sviluppo AI

---

## OVERVIEW

Ainstein è una piattaforma SaaS modulare per tool SEO con integrazione AI.
Target: agenzie, e-commerce, freelancer, consulenti digitali.
Pricing model: ~55€/mese (vs 120€+ competitor).

| Aspetto | Dettaglio |
|---------|-----------|
| **Stack** | PHP 8.0+, MySQL, Tailwind CSS, Alpine.js, HTMX |
| **AI Provider** | Claude API (Anthropic) + OpenAI (fallback) |
| **Hosting** | SiteGround GoGeek |
| **Lingua UI** | Italiano |

---

## STATO MODULI (Aggiornato 06 Feb 2026)

| Modulo | Slug | Stato Base | Stato AI | % Totale |
|--------|------|------------|----------|----------|
| AI SEO Content Generator | `ai-content` | ✅ 100% | ✅ Completa | **100%** |
| SEO Audit | `seo-audit` | ✅ 100% | ✅ Completa | **100%** |
| Google Ads Analyzer | `ads-analyzer` | ✅ 100% | ✅ Completa | **100%** |
| Internal Links Analyzer | `internal-links` | ✅ 85% | ❌ Mancante | **75%** |
| SEO Position Tracking | `seo-tracking` | ✅ 95% | ✅ Completa | **95%** |
| AI Keyword Research | `keyword-research` | ✅ 100% | ✅ Completa | **100%** |
| AI Content Bulk Creator | `content-creator` | ❌ 0% | ❌ | **0%** |

### Legenda Stato AI
- ✅ **Completa**: AI integrata e funzionante per tutte le feature
- ⚠️ **Parziale**: AI presente ma mancano feature chiave (vedi ROADMAP.md)
- ❌ **Mancante**: Nessuna integrazione AI

---

## GAP AI DA COLMARE (Priorità)

### 🔴 Priorità Alta (FASE 1)

| Modulo | Feature Mancante | Impatto |
|--------|------------------|---------|
| **internal-links** | AI Link Suggester (suggerimenti link + anchor) | Alto |

### 🟡 Priorità Media (FASE 2)

| Modulo | Feature Mancante | Impatto |
|--------|------------------|---------|
| **seo-tracking** | Alert backend + Email notifiche | Medio |
| **seo-tracking** | Monthly executive report | Basso |
| **content-creator** | MVP completo | Medio |

> **Nota:** Quick Wins Finder ✅ (2026-01-28), Weekly AI Digest ✅ (2026-02-03)

📄 Dettagli: [ROADMAP.md](./ROADMAP.md)

---

## COMPLETATI RECENTEMENTE

### 2026-02-06
- [x] **Cover Image Generation** - Generazione immagine di copertina via DALL-E 3 per articoli (`ai-content`)
- [x] CoverImageService: prompt AI (Claude Haiku) + DALL-E 3 API (1792x1024, natural style)
- [x] Toggle opzionale in settings AUTO e wizard MANUAL (default ON, 3 crediti)
- [x] Step SSE 'cover' nella pipeline AUTO (tra saving e publishing)
- [x] Rigenera/Rimuovi copertina dalla vista articolo
- [x] Storage locale `storage/images/covers/` servite via controller (no accesso diretto)
- [x] Migration `007_add_cover_image.sql` (cover_image_path, generate_cover, step ENUM)
- [x] API logging via ApiLoggerService (provider: openai_dalle)
- [x] **AI Keyword Research** - Nuovo modulo completo (`keyword-research`, prefisso `kr_`)
- [x] Research Guidata: wizard 4-step (Brief → API collection SSE → AI clustering → Results)
- [x] Architettura Sito: wizard 3-step (Brief → Collection+AI → Struttura pagine con URL/H1)
- [x] Quick Check: ricerca keyword istantanea senza progetto (gratis)
- [x] KeywordInsightService: wrapper Google Keyword Insight API (RapidAPI) con ApiLoggerService
- [x] 5 controller, 3 model, 1 service, 10 view, dashboard con 3 modalità
- [x] Sistema crediti integrato (2-5 crediti clustering AI, 5 crediti architettura)
- [x] SSE real-time per raccolta keyword con session_write_close()
- [x] Sidebar accordion con navigazione progetto
- [x] Export CSV con BOM UTF-8
- [x] Documentazione completa aggiornata

### 2026-02-03
- [x] **Rank Check con Job in Background** (SSE streaming real-time)
- [x] **DataForSeoService** integrato per verifica posizioni SERP
- [x] **RankJob + RankQueue models** per gestione job asincroni
- [x] **RankCheckController** con pattern SSE (start/stream/status/cancel)
- [x] **Import keyword da GSC** con selezione e assegnazione gruppi
- [x] **Cron dispatchers** per GSC sync e AI reports automatici
- [x] **Admin settings** per configurazione cron rank/gsc/ai
- [x] **Weekly AI Digest** completamente funzionante (cron admin-configured)
- [x] Migrazione `012_rank_jobs.sql` deployata in produzione
- [x] Documentazione CLAUDE.md aggiornata con pattern Background Jobs

### 2026-01-30
- [x] Integrazione Mozilla Readability in ScraperService per estrazione contenuti intelligente
- [x] Migrato ai-content CRON (process_queue.php) a ScraperService::scrape() con Readability
- [x] Aggiunta estrazione link interni automatica in ScraperService
- [x] Nuova Golden Rule #12: "Scraping SEMPRE con Readability"
- [x] Aggiornata documentazione (CLAUDE.md, GOLDEN-RULES.md)
- [x] Fix scraping contenuti: da ~10 parole a centinaia (siti Elementor e strutture complesse)

### 2026-01-29
- [x] Refactoring sistema scheduling AUTO mode: da globale a per-keyword (ai-content)
- [x] Nuova UI inline editing per data/ora e fonti in vista Coda (ai-content)
- [x] Rimossi settings globali scheduling (publish_times, articles_per_day, auto_select_sources)
- [x] Ogni keyword in coda ha ora `scheduled_at` e `sources_count` individuali
- [x] Pulsante "Pianifica" per keyword non ancora schedulate (ai-content)
- [x] Update real-time con Alpine.js computed properties (ai-content)
- [x] Fix CSRF token validation per AJAX calls (`_csrf_token` field)
- [x] Semplificato AutoConfig.php (solo auto_publish e wp_site_id)
- [x] Aggiornato dispatcher CRON per nuovo sistema per-keyword
- [x] Aggiornata documentazione AGENT-AI-CONTENT.md

### 2026-01-28
- [x] Fix redirect project-aware dopo eliminazione articolo (ai-content)
- [x] Fix sidebar mancante pagina Jobs - aggiunto title, user, modules (ai-content)
- [x] Fix navigation tabs in tutte le viste manual mode (ai-content)
- [x] Standardizzazione session flash keys in tutti i controller (ai-content)
- [x] Fix sintassi view path JobController (ai-content)
- [x] Audit completo modulo AI Content Generator
- [x] SEO Page Analyzer completato (seo-tracking)
- [x] Position Compare feature (seo-tracking)
- [x] Fix Quick Wins URL navigation (seo-tracking)

### 2026-01-26
- [x] Rimosso GA4 da seo-tracking (semplificazione)
- [x] Aggiunto Rank Check con DataForSEO (seo-tracking)
- [x] Aggiunta tabella Locations per gestione location-based tracking
- [x] Action Plan AI completato (seo-audit)
- [x] Job Controller aggiunto (ai-content)
- [x] DataForSeoService centralizzato
- [x] Cleanup database progetto Amevista (liberati 1.5GB)

### 2026-01-19
- [x] Audit completo piattaforma pre-beta
- [x] Documentazione strategica (STRATEGY.md, ROADMAP.md)
- [x] Verifica stato tutti i moduli

### 2026-01-16
- [x] Fix crawler page limit bug (seo-audit)
- [x] Fix UTF-8 sanitization (ai-content)

### 2026-01-09
- [x] Storico scansioni con health score trend (seo-audit)
- [x] Session tracking su issues e pages
- [x] Google Ads Analyzer completato

---

## SERVIZI CONDIVISI

| Servizio | Descrizione | Usato da |
|----------|-------------|----------|
| `AiService.php` | Multi-provider AI + logging | Tutti |
| `ScraperService.php` | HTTP client + Readability (estrazione contenuti intelligente) | ai-content, ai-optimizer, seo-audit, internal-links |
| `GoogleOAuthService.php` | OAuth2 centralizzato | seo-audit, seo-tracking |
| `SitemapService.php` | Parser sitemap/robots.txt | seo-audit, internal-links |
| `ExportService.php` | Export CSV/PDF | seo-audit, ads-analyzer |
| `CsvImportService.php` | Parser CSV | ads-analyzer |
| `DataForSeoService.php` | API DataForSEO per rank check | seo-tracking |
| `KeywordInsightService.php` | Google Keyword Insight API (RapidAPI) | keyword-research (modulo-locale) |

---

## STRUTTURA PROGETTO

```
ainstein-seo-toolkit/
├── core/                    # Framework core
├── services/                # Servizi condivisi
├── modules/
│   ├── ai-content/         # Content Generator
│   ├── seo-audit/          # Audit Tecnico
│   ├── ads-analyzer/       # Google Ads Analyzer
│   ├── internal-links/     # Link Analysis
│   ├── seo-tracking/       # Position Tracking
│   └── keyword-research/   # AI Keyword Research
├── admin/                   # Admin panel
├── shared/views/            # Layout, sidebar, components
├── config/                  # Configurazione
├── docs/                    # Documentazione
│   ├── STRATEGY.md         # Posizionamento strategico
│   ├── ROADMAP.md          # Piano sviluppo AI
│   ├── specs/              # Specifiche moduli
│   └── agents/             # Doc agenti AI
└── storage/                 # Logs, cache
```

---

## ULTIMO DEPLOY

| Aspetto | Dettaglio |
|---------|-----------|
| **Data** | 2026-02-06 |
| **Modifiche principali** | Cover image DALL-E 3 per ai-content (auto + manual), AI Keyword Research module |
| **Migration** | `007_add_cover_image.sql` - cover_image_path, generate_cover, step ENUM 'cover' |
| **File nuovi** | CoverImageService.php, storage/images/covers/.htaccess + keyword-research (20 file) |

---

## PROSSIMI STEP

### Immediati (questa settimana)
1. Test browser completo modulo keyword-research
2. Deploy keyword-research in produzione
3. Definire prompt per AI Link Suggester

### Breve termine (2-3 settimane)
1. Rilascio AI Link Suggester (internal-links)
2. Completare Alert backend + email notifiche (seo-tracking)
3. Monthly executive report (seo-tracking)

### Medio termine (1 mese)
1. Content Creator MVP
2. Landing page + pricing
3. Onboarding utenti beta

---

## METRICHE TARGET

| Metrica | 6 mesi | 12 mesi |
|---------|--------|---------|
| Utenti paganti | 20 | 50 |
| MRR | 1.100€ | 2.750€ |
| Churn rate | <10% | <8% |

---

## ACCESSI

### SSH Produzione
```bash
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
```

### Google OAuth
- Redirect URI: `https://ainstein.it/oauth/google/callback`
- Scopes: webmasters.readonly, analytics.readonly

---

## DOCUMENTAZIONE CORRELATA

| Documento | Contenuto |
|-----------|-----------|
| [STRATEGY.md](./STRATEGY.md) | Posizionamento, competitor, economia |
| [ROADMAP.md](./ROADMAP.md) | Piano sviluppo AI in 3 fasi |
| [PLATFORM_OVERVIEW.md](./PLATFORM_OVERVIEW.md) | Architettura tecnica |
| [GOLDEN-RULES.md](./GOLDEN-RULES.md) | Regole sviluppo |
| [specs/*.md](./specs/) | Specifiche tecniche moduli |

---

*Documento aggiornato - 2026-02-06*
