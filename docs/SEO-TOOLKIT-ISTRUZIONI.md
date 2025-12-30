# SEO TOOLKIT - Istruzioni Progetto

## OVERVIEW

**SEO Toolkit** è una piattaforma SaaS modulare per tool SEO con integrazione AI.

| Aspetto | Dettaglio |
|---------|-----------|
| **Directory** | `C:\laragon\www\seo-toolkit` |
| **Database** | MySQL - `seo_toolkit` |
| **Stack** | PHP (MVC custom), Tailwind CSS, Alpine.js, HTMX |
| **AI** | Claude API (Anthropic) |
| **Lingua UI** | Italiano |

---

## ARCHITETTURA

```
seo-toolkit/
├── public/
│   └── index.php              # Entry point unico
├── core/
│   ├── Router.php             # Routing moduli
│   ├── ModuleLoader.php       # Carica moduli dinamicamente
│   ├── Database.php           # PDO wrapper
│   ├── Auth.php               # Autenticazione utenti
│   ├── Middleware.php         # Middleware (auth, CSRF)
│   ├── Credits.php            # Sistema crediti
│   └── View.php               # Template engine
├── services/                  # Servizi CONDIVISI tra moduli
│   ├── AiService.php          # Claude API
│   ├── ScraperService.php     # Guzzle + DomCrawler
│   ├── ExportService.php      # CSV/Excel/PDF export
│   ├── CsvImportService.php   # Parser CSV riusabile
│   └── SitemapService.php     # Parser sitemap + robots.txt
├── modules/
│   ├── _template/             # Template base per nuovi moduli
│   └── [nome-modulo]/         # Ogni modulo autocontenuto
│       ├── module.json        # Config + settings + crediti
│       ├── routes.php         # Rotte del modulo
│       ├── controllers/
│       ├── models/
│       └── views/
├── shared/
│   ├── views/
│   │   ├── layout.php         # Layout master
│   │   ├── sidebar.php        # Menu dinamico
│   │   └── components/        # UI riusabili
│   │       ├── nav-items.php
│   │       └── import-tabs.php
│   └── assets/
│       ├── css/style.css
│       └── js/app.js
├── config/
│   ├── app.php
│   ├── database.php
│   └── modules.php            # Moduli attivi
├── docs/                      # Documentazione
│   ├── PLATFORM_STANDARDS.md
│   ├── PLATFORM_OVERVIEW.md
│   ├── MODULE_NAVIGATION.md
│   ├── IMPORT_STANDARDS.md
│   └── specs/
└── storage/
    ├── logs/
    └── cache/
```

---

## MODULI ESISTENTI

| Modulo | Slug | Prefisso DB | Stato |
|--------|------|-------------|-------|
| Template Base | `_template` | - | 📁 Scheletro per nuovi moduli |
| Internal Links Analyzer | `internal-links` | `il_` | ✅ Completato |
| AI SEO Content Generator | `ai-content` | `aic_` | 🔄 Parziale (UI incompleta) |
| AI Content Bulk Creator | `content-creator` | `cc_` | 📋 Specifiche pronte |
| SEO Audit | `seo-audit` | `sa_` | 📋 Specifiche pronte |

---

## DOCUMENTAZIONE (/docs)

Prima di sviluppare qualsiasi modulo, Claude DEVE leggere la documentazione pertinente.

### Struttura /docs
```
docs/
├── PLATFORM_STANDARDS.md    # Convenzioni globali, lingua IT, naming
├── PLATFORM_OVERVIEW.md     # Architettura e overview piattaforma
├── MODULE_NAVIGATION.md     # Standard navigazione sidebar/routes
├── IMPORT_STANDARDS.md      # Pattern import URL (CSV/Sitemap/Manual)
└── specs/                   # Specifiche tecniche moduli
    ├── ai-seo-content-spec.md
    ├── ai-content-bulk-creator-specs.md
    └── seo-audit-module-spec.md
```

### Ordine Lettura Obbligatorio

| Fase | File da Leggere | Motivo |
|------|-----------------|--------|
| **Sempre** | `PLATFORM_STANDARDS.md` | Convenzioni base |
| **Nuovo modulo** | `MODULE_NAVIGATION.md` | Struttura routes/views |
| **Import URL** | `IMPORT_STANDARDS.md` | Componenti e servizi |
| **Integrazione AI** | `AI_SERVICE_STANDARDS.md` | Standard chiamate AI |
| **Modulo specifico** | `specs/[nome]-spec.md` | Requisiti funzionali |

---

## CONVENZIONI OBBLIGATORIE

### Database
- **Prefisso tabelle**: ogni modulo usa un prefisso univoco (2-3 lettere + underscore)
- **Foreign keys**: sempre con `ON DELETE CASCADE` dove appropriato
- **Timestamps**: `created_at`, `updated_at` su ogni tabella
- **Soft delete**: campo `deleted_at` quando necessario

### Naming
- **Moduli**: slug kebab-case (`ai-content`, `seo-audit`)
- **Tabelle**: prefisso + snake_case (`aic_keywords`, `sa_pages`)
- **Controller**: PascalCase + Controller (`KeywordController.php`)
- **Model**: PascalCase singolare (`Keyword.php`)
- **Views**: kebab-case (`keyword-index.php`)

### File module.json
```json
{
  "name": "Nome Modulo",
  "slug": "nome-modulo",
  "version": "1.0.0",
  "description": "Descrizione breve",
  "icon": "heroicon-name",
  "menu_order": 10,
  "credits": {
    "action_name": {
      "cost": 1,
      "description": "Descrizione consumo"
    }
  },
  "settings": {
    "setting_key": {
      "type": "text|password|number|select",
      "label": "Label IT",
      "default": "valore",
      "admin_only": true|false
    }
  },
  "routes_prefix": "/nome-modulo"
}
```

---

## PATTERN IMPLEMENTATIVI

### Import URL (standard per tutti i moduli)
Ogni modulo che richiede import URL deve usare:
1. **Componente UI**: `shared/views/components/import-tabs.php`
2. **Servizi**: `CsvImportService`, `SitemapService`
3. **Metodi supportati**: CSV upload, Sitemap XML, Manual input, CMS connector
4. **Documentazione**: Leggi `docs/IMPORT_STANDARDS.md` prima di implementare

### Progress Real-time
Per operazioni lunghe (scraping, AI generation):
```php
// Controller
header('Content-Type: text/event-stream');
foreach ($items as $i => $item) {
    // processo
    echo "data: " . json_encode(['progress' => ($i+1)/$total * 100]) . "\n\n";
    ob_flush(); flush();
}
```
```html
<!-- View con HTMX -->
<div hx-ext="sse" sse-connect="/endpoint" sse-swap="message">
    <div class="progress-bar"></div>
</div>
```

### Integrazione AI
Usa sempre `AiService` condiviso:
```php
$ai = new AiService();
$response = $ai->complete([
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 4096,
    'messages' => [
        ['role' => 'user', 'content' => $prompt]
    ]
]);
```

### Sistema Crediti
```php
// Verifica disponibilità
$credits = CreditService::getBalance($userId);
if ($credits < $requiredCredits) {
    return $this->error('Crediti insufficienti');
}

// Consuma crediti
CreditService::consume($userId, $amount, 'descrizione_operazione', $moduleSlug);
```

### Export
```php
// CSV
$export = new ExportService();
$export->toCsv($data, $columns, $filename);

// PDF (se implementato)
$export->toPdf($html, $filename);
```

---

## FLOW SVILUPPO NUOVO MODULO

### Step 0: Documentazione (OBBLIGATORIO)
```
1. Leggi docs/PLATFORM_STANDARDS.md
2. Leggi docs/MODULE_NAVIGATION.md
3. Leggi docs/specs/[nome]-spec.md (se esiste)
4. Se import URL richiesto: leggi docs/IMPORT_STANDARDS.md
```

### Step 1: Struttura base
```
modules/nuovo-modulo/
├── module.json
├── routes.php
├── controllers/
├── models/
└── views/
```
**Tip**: Copia da `modules/_template/` come base.

### Step 2: Database
- Crea migration SQL con prefisso modulo
- Tabelle relazionate a `users` e `projects` (se multi-progetto)

### Step 3: Routes
```php
// routes.php
$router->get('/nuovo-modulo', 'DashboardController@index');
$router->get('/nuovo-modulo/projects', 'ProjectController@index');
// ... altre rotte
```

### Step 4: MVC
- Controller: gestisce request/response
- Model: interazione DB con PDO
- View: PHP + Tailwind + Alpine.js

### Step 5: Integrazione Shell
- Registra modulo in `config/modules.php`
- Verifica sidebar e navigazione

---

## CONNETTORI CMS

### WordPress/WooCommerce (prioritario)
```php
class WordPressConnector {
    private $siteUrl;
    private $username;
    private $appPassword;
    
    public function getPosts($type = 'post', $perPage = 100);
    public function getProducts($perPage = 100);
    public function updatePost($postId, $data);
    public function createPost($data);
}
```

Autenticazione: Application Passwords di WordPress.

---

## API ESTERNE UTILIZZATE

| Servizio | Uso | Config |
|----------|-----|--------|
| Claude API (Anthropic) | AI analysis, content generation | Admin settings |
| SerpAPI | SERP extraction, PAA | Admin settings (modulo ai-content) |
| Google Search Console | SEO data | OAuth2 (modulo seo-audit) |
| PageSpeed API | Performance (opzionale) | API key pubblica |

---

## BEST PRACTICES

### Sicurezza
- Sanitizza tutti gli input utente
- Usa prepared statements PDO
- Valida CSRF su form POST
- API keys solo in admin settings (mai lato utente)
- Tokens OAuth criptati in DB

### Performance
- Cache risultati costosi (TTL appropriato)
- Job queue per operazioni lunghe
- Pagination su liste (max 25-50 items)
- Lazy load per dati pesanti

### UX
- Feedback visivo immediato (loading states)
- Progress bar per operazioni lunghe
- Messaggi di errore chiari in italiano
- Conferma prima di azioni distruttive

---

## TEMPLATE PROMPT CLAUDE CODE

Per sviluppare un nuovo modulo:

```
Crea il modulo [NOME] per SEO Toolkit in C:\laragon\www\seo-toolkit\modules\[slug]\

PRIMA DI INIZIARE, LEGGI:
1. docs/PLATFORM_STANDARDS.md
2. docs/MODULE_NAVIGATION.md
3. docs/specs/[nome]-spec.md (se esiste)
4. Se modulo richiede import URL: docs/IMPORT_STANDARDS.md

CONTESTO:
- Piattaforma SaaS modulare PHP
- Stack: Tailwind CSS, Alpine.js, HTMX
- Servizi condivisi in /services/
- Pattern MVC custom
- Database MySQL con prefisso [XX]_
- Lingua UI: Italiano

SPECIFICHE:
[inserire specifiche modulo o riferimento a spec file]

ORDINE IMPLEMENTAZIONE:
1. Leggi documentazione richiesta
2. Struttura cartelle + module.json + routes.php
3. Tabelle database (SQL + esecuzione)
4. Models base
5. Controllers CRUD
6. Views (seguendo MODULE_NAVIGATION.md)
7. Funzionalità specifiche (seguendo spec)
8. Integrazione crediti
9. Test funzionale

Procedi step by step. Fermati dopo ogni step per conferma.
```

---

## CHECKLIST PRE-RILASCIO MODULO

### Documentazione
- [ ] Letto PLATFORM_STANDARDS.md
- [ ] Letto MODULE_NAVIGATION.md
- [ ] Letto spec modulo (se esiste)
- [ ] Letto IMPORT_STANDARDS.md (se import URL richiesto)

### Implementazione
- [ ] module.json completo (credits, settings)
- [ ] Tabelle DB create con prefisso corretto
- [ ] CRUD funzionante
- [ ] Navigazione conforme a MODULE_NAVIGATION.md
- [ ] Import URL conforme a IMPORT_STANDARDS.md (se richiesto)
- [ ] Progress real-time (se operazioni lunghe)
- [ ] Integrazione AI tramite AiService (se richiesto)
- [ ] Sistema crediti integrato
- [ ] Export dati (CSV minimo)
- [ ] UI in italiano
- [ ] Test end-to-end

---

## NOTE TECNICHE

### HTMX
Usato per:
- Partial updates senza reload
- SSE per progress real-time
- Form submission async

### Alpine.js
Usato per:
- Toggle UI states
- Dropdown/modal
- Form validation client-side
- Tab navigation

### Tailwind CSS
- Utility-first
- Custom theme in `tailwind.config.js`
- Componenti in `shared/views/components/`

---

## CHANGELOG ISTRUZIONI

| Data | Modifica |
|------|----------|
| 2025-01 | Aggiunta sezione DOCUMENTAZIONE (/docs) |
| 2025-01 | Aggiornato template prompt con lettura docs obbligatoria |
| 2025-01 | Aggiornata checklist pre-rilascio |
| 2025-01 | Aggiunto riferimento a _template in /modules |
| 2025-01 | Aggiornato stato moduli esistenti |
