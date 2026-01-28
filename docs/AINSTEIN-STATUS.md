# AINSTEIN - Stato Progetto

**Ultimo aggiornamento:** 2026-01-28
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

## STATO MODULI (Aggiornato 28 Gen 2026)

| Modulo | Slug | Stato Base | Stato AI | % Totale |
|--------|------|------------|----------|----------|
| AI SEO Content Generator | `ai-content` | ✅ 98% | ✅ Completa | **98%** |
| SEO Audit | `seo-audit` | ✅ 100% | ✅ Completa | **100%** |
| Google Ads Analyzer | `ads-analyzer` | ✅ 100% | ✅ Completa | **100%** |
| Internal Links Analyzer | `internal-links` | ✅ 85% | ❌ Mancante | **75%** |
| SEO Position Tracking | `seo-tracking` | ✅ 90% | ⚠️ Parziale | **90%** |
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
| **seo-tracking** | Weekly AI Digest | Alto |
| **content-creator** | MVP completo | Medio |

> **Nota:** Quick Wins Finder è stato implementato (2026-01-28)

📄 Dettagli: [ROADMAP.md](./ROADMAP.md)

---

## COMPLETATI RECENTEMENTE

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
| `ScraperService.php` | HTTP client + DOM parser | ai-content, seo-audit, internal-links |
| `GoogleOAuthService.php` | OAuth2 centralizzato | seo-audit, seo-tracking |
| `SitemapService.php` | Parser sitemap/robots.txt | seo-audit, internal-links |
| `ExportService.php` | Export CSV/PDF | seo-audit, ads-analyzer |
| `CsvImportService.php` | Parser CSV | ads-analyzer |
| `DataForSeoService.php` | API DataForSEO per rank check | seo-tracking |

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
│   └── seo-tracking/       # Position Tracking
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
| **Data** | 2026-01-28 |
| **Modifiche principali** | Fix navigation e redirect ai-content, SEO Page Analyzer, Position Compare, audit completo ai-content |
| **Bug fix** | Session flash keys, view path syntax, sidebar Jobs, project-aware redirects |

---

## PROSSIMI STEP

### Immediati (questa settimana)
1. Definire prompt per AI Link Suggester
2. Test beta con 2-3 utenti
3. Ottimizzare Rank Check con bulk requests

### Breve termine (2-3 settimane)
1. Rilascio AI Link Suggester (internal-links)
2. Weekly AI Digest (seo-tracking)
3. Quick Wins Finder

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

*Documento aggiornato - 2026-01-26*
