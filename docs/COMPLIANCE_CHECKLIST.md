# COMPLIANCE CHECKLIST - SEO Toolkit

Checklist di requisiti estratti dalla documentazione ufficiale per verificare la conformità di ogni modulo.

---

## REQUISITI GLOBALI (da PLATFORM_STANDARDS.md)

### Lingua
- [ ] Tutta l'interfaccia utente in ITALIANO
- [ ] Titoli e intestazioni in italiano
- [ ] Etichette pulsanti in italiano
- [ ] Messaggi di errore/successo in italiano
- [ ] Placeholder nei campi in italiano
- [ ] Tooltip e messaggi di aiuto in italiano
- [ ] Voci menu e navigazione in italiano
- [ ] Messaggi di conferma in italiano
- [ ] Notifiche toast in italiano

### Eccezioni Termini Tecnici (OK in inglese)
- URL, CSV, API, SEO, XML, Sitemap, HTTP/HTTPS, ID, HTML, JSON, Dashboard

### Naming Conventions (Codice in inglese)
- [ ] Nomi variabili in inglese ($projectId, $urlCount)
- [ ] Nomi funzioni in inglese (importUrls(), validateProject())
- [ ] Nomi classi in inglese PascalCase (ProjectController, UrlModel)
- [ ] Controller: PascalCase + Controller (ProjectController.php)
- [ ] Model: PascalCase singolare (Keyword.php)
- [ ] Views: kebab-case (keyword-index.php)

---

## REQUISITI DATABASE

### Prefissi Tabelle per Modulo
- [ ] internal-links: `il_`
- [ ] ai-content: `aic_`
- [ ] seo-audit: `sa_`
- [ ] seo-tracking: `st_`
- [ ] content-creator: `cc_`

### Struttura Tabelle
- [ ] Foreign keys con ON DELETE CASCADE dove appropriato
- [ ] Timestamp: created_at, updated_at su ogni tabella
- [ ] Soft delete: campo deleted_at quando necessario
- [ ] Prepared statements per tutte le query

---

## REQUISITI NAVIGAZIONE (da MODULE_NAVIGATION.md)

### Sidebar
- [ ] Usare ACCORDION nella sidebar principale
- [ ] NO sidebar separate nel modulo
- [ ] Navigazione gestita in nav-items.php
- [ ] Accordion espande quando si è dentro un progetto
- [ ] Voce attiva evidenziata
- [ ] Dashboard usa exact: true per highlight

### Views
- [ ] Views usano `<div class="space-y-6">` senza wrapper sidebar
- [ ] Quick stats in header/banner, NON in sidebar
- [ ] NO wrapper `<div class="flex min-h-screen">`
- [ ] Separatori per categorie di navigazione
- [ ] Settings in fondo con border-top

### Route Pattern
- [ ] Pattern: `/modulo/entity/{id}/section`
- [ ] Rilevamento entità in nav-items.php
- [ ] Blocco accordion in nav-items.php

---

## REQUISITI IMPORT URL (da IMPORT_STANDARDS.md)

### Servizi Condivisi
- [ ] Usare CsvImportService per import CSV
- [ ] Usare SitemapService per import sitemap
- [ ] Componente UI: shared/views/components/import-tabs.php

### API Endpoints Richiesti
- [ ] POST /{module}/api/sitemap-discover
- [ ] POST /{module}/api/sitemap
- [ ] POST /{module}/project/{id}/urls/import-csv
- [ ] POST /{module}/project/{id}/urls/store (manual)

---

## REQUISITI AI SERVICE (da AI_SERVICE_STANDARDS.md)

### Istanziazione
- [ ] SEMPRE specificare module_slug: `new AiService('nome-modulo')`
- [ ] MAI usare AiService senza module slug

### Metodi
- [ ] Verificare isConfigured() prima di chiamate
- [ ] Gestire sempre response error/success
- [ ] Propagare credits_used al chiamante

### Modello AI
- [ ] Usare modello corretto: `claude-sonnet-4-20250514`
- [ ] MAI usare modelli deprecati (claude-3-*)

---

## REQUISITI SERVIZI CONDIVISI (da PLATFORM_OVERVIEW.md)

### Servizi Disponibili
| Servizio | Path | Uso |
|----------|------|-----|
| AiService | services/AiService.php | Chiamate Claude API |
| ScraperService | services/ScraperService.php | Fetch pagine web |
| CsvImportService | services/CsvImportService.php | Parse CSV |
| SitemapService | services/SitemapService.php | Parse sitemap |
| ExportService | services/ExportService.php | Export CSV/PDF |

### Regole
- [ ] MAI usare curl_init/curl_exec diretto nei moduli
- [ ] MAI usare file_get_contents per HTTP nei moduli
- [ ] SEMPRE usare ScraperService per fetch HTTP
- [ ] SEMPRE usare servizi condivisi invece di reimplementare

---

## REQUISITI UI/UX (da docs/)

### Icone
- [ ] Usare Heroicons (SVG inline)
- [ ] MAI usare Lucide icons
- [ ] MAI usare icon fonts

### Componenti
- [ ] Tailwind CSS
- [ ] Alpine.js per interattività
- [ ] HTMX per aggiornamenti parziali
- [ ] NON usare x-collapse (richiede plugin)
- [ ] Usare x-show con x-transition per expand/collapse

### Form
- [ ] CSRF token presente su tutti i form POST
- [ ] Validazione client-side con Alpine.js
- [ ] Messaggi validazione in italiano

### Feedback
- [ ] Loading states visivi
- [ ] Progress bar per operazioni lunghe
- [ ] Conferma prima di azioni distruttive

---

## REQUISITI module.json

```json
{
  "name": "Nome Modulo (italiano)",
  "slug": "nome-modulo",
  "version": "X.X.X",
  "description": "Descrizione breve",
  "icon": "heroicon-name",
  "menu_order": N,
  "credits": {
    "action_name": {
      "cost": N,
      "description": "Descrizione in italiano"
    }
  },
  "settings": {
    "setting_key": {
      "type": "text|password|number|select",
      "label": "Label in italiano",
      "default": "valore",
      "admin_only": true|false
    }
  },
  "routes_prefix": "/nome-modulo"
}
```

---

## REQUISITI SICUREZZA

### SQL Injection
- [ ] Prepared statements per TUTTE le query
- [ ] MAI concatenare input utente in query

### XSS
- [ ] htmlspecialchars() su tutto l'output utente
- [ ] escape() in views

### CSRF
- [ ] Token CSRF su tutti i form POST
- [ ] Validazione CSRF nel controller

### Secrets
- [ ] API keys solo in admin settings
- [ ] MAI hardcodare chiavi nel codice
- [ ] Token OAuth criptati in DB

---

## REQUISITI SISTEMA CREDITI

### Verifica
- [ ] Verifica crediti PRIMA di ogni operazione
- [ ] Blocca e mostra messaggio se insufficienti

### Consumo
- [ ] Scala crediti DOPO operazione completata
- [ ] Logga in usage_log ogni consumo

### Costi Standard
| Operazione | Crediti |
|------------|---------|
| scrape_url | 0.1-1 |
| ai_analysis_small | 1 |
| ai_analysis_medium | 2 |
| ai_analysis_large | 5 |
| export_csv | 0 |

---

## CHECKLIST PER MODULO

### Internal Links (internal-links)
- [ ] Prefisso DB: il_
- [ ] Import URL: CSV, Sitemap, Manual
- [ ] Scraping via ScraperService
- [ ] Analisi AI via AiService
- [ ] Export via ExportService

### AI Content (ai-content)
- [ ] Prefisso DB: aic_
- [ ] SERP extraction via SerpApiService
- [ ] Scraping via ScraperService
- [ ] Generazione articoli via AiService
- [ ] Modello: claude-sonnet-4-20250514
- [ ] WordPress connector

### SEO Audit (seo-audit)
- [ ] Prefisso DB: sa_
- [ ] Crawl via SitemapService + ScraperService
- [ ] Issue detection via IssueDetector
- [ ] Analisi AI via AiService
- [ ] GSC via OAuth

### SEO Tracking (seo-tracking)
- [ ] Prefisso DB: st_
- [ ] GSC via OAuth
- [ ] GA4 via Service Account
- [ ] Report AI via AiService
- [ ] Alert system

### Content Creator (content-creator)
- [ ] Prefisso DB: cc_
- [ ] Import via CsvImportService + SitemapService
- [ ] Scraping via ScraperService
- [ ] Generazione via AiService
- [ ] CMS connectors

---

## NOTE

- Questa checklist deve essere verificata per OGNI modulo prima del rilascio
- Ogni violazione deve essere documentata nel report di audit
- Le violazioni critiche devono essere corrette prima del deploy
