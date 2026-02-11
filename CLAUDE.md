# AINSTEIN - Istruzioni Claude Code

> Questo file viene caricato automaticamente ad ogni sessione.
> Ultimo aggiornamento: 2026-02-11

---

## CONTESTO PROGETTO

| Aspetto | Dettaglio |
|---------|-----------|
| **Nome** | Ainstein SEO Toolkit |
| **Directory locale** | `C:\laragon\www\seo-toolkit` |
| **Produzione** | https://ainstein.it |
| **Stack** | PHP 8+, MySQL, Tailwind CSS, Alpine.js, HTMX |
| **AI Provider** | Claude API (Anthropic) + OpenAI fallback |
| **Lingua UI** | Italiano (sempre) |
| **Database** | MySQL `seo_toolkit` (locale) / `dbj0xoiwysdlk1` (prod) |

---

## GOLDEN RULES (INVIOLABILI)

```
1. AiService SEMPRE centralizzato    → new AiService('modulo-slug')
2. Icone SOLO Heroicons SVG          → MAI Lucide, MAI FontAwesome
3. Lingua UI ITALIANO                → Tutti i testi visibili
4. Prefisso DB per modulo            → aic_, sa_, st_, il_, ga_, cc_, kr_
5. API Keys in Database              → MAI in .env, MAI hardcoded
6. Routes pattern standard           → /modulo/projects/{id}/sezione
7. Prepared statements SEMPRE        → MAI concatenare SQL
8. CSRF token su form POST           → campo `_csrf_token` (con underscore!)
9. ai-content è il reference         → Copia pattern da lì
10. Database::reconnect()            → Prima di salvare dopo AI call
11. OAuth GSC pattern seo-tracking   → GoogleOAuthService centralizzato
12. Scraping SEMPRE con Readability  → ScraperService::scrape() per tutto
13. Background jobs per operazioni lunghe → SSE + job queue (vedi sezione dedicata)
14. ApiLoggerService per API     → Tutte le chiamate API esterne loggano
15. ignore_user_abort(true)      → SEMPRE in SSE streams (proxy chiude connessione)
16. ModuleLoader::getSetting()   → NON getModuleSetting() (non esiste)
```

---

## STATO MODULI

| Modulo | Slug | Prefisso DB | Stato | Note |
|--------|------|-------------|-------|------|
| AI Content Generator | `ai-content` | `aic_` | 100% | Reference pattern, scheduling per-keyword, cover image DALL-E 3, brief AI strategico, user edits nel prompt, SERP provider SerpAPI/Serper.dev con fallback |
| SEO Audit | `seo-audit` | `sa_` | 100% | Action Plan AI completato |
| Google Ads Analyzer | `ads-analyzer` | `ga_` | 100% | Completo |
| Internal Links | `internal-links` | `il_` | 85% | Manca AI Suggester |
| SEO Tracking | `seo-tracking` | `st_` | 95% | Rank Check con DataForSEO/SerpAPI/Serper, API Logs integrato, provider SERP/Volume configurabili da admin |
| AI Keyword Research | `keyword-research` | `kr_` | 100% | Research Guidata, Architettura Sito, Quick Check |
| Content Creator | `content-creator` | `cc_` | 0% | Da implementare |

---

## DOCS DI RIFERIMENTO

**Prima di modificare qualsiasi cosa, leggi i file pertinenti in `/mnt/project/`:**

| Quando | Leggi |
|--------|-------|
| Sempre | `GOLDEN-RULES.md` |
| Nuovo modulo | `PLATFORM_OVERVIEW.md`, `MODULE_NAVIGATION.md` |
| Import URL | `IMPORT_STANDARDS.md` |
| Integrazione AI | `AI_SERVICE_STANDARDS.md` |
| Sistema crediti | `CREDITS-SYSTEM.md` |
| Modulo specifico | `specs/[modulo].md` o `AGENT-[MODULO].md` |
| API esterne | `API-LOGS.md` |
| Deploy | `DEPLOY.md`, `DEPLOY-VERIFY-AINSTEIN.md` |

---

## STANDARD module.json (Admin Settings)

Ogni modulo DEVE avere `settings_groups` nel suo `module.json` per organizzare la pagina admin `/admin/modules/{id}/settings`.

**Ordine gruppi standard:**

| Order | Gruppo | Descrizione | Note |
|-------|--------|-------------|------|
| 0 | `ai_config` | Provider/modello AI | Gestito da componente custom |
| 0.5 | `provider_config` | Provider esterni (SERP, volumi) | Solo se necessario |
| 1 | `general` | Impostazioni specifiche modulo | - |
| 2+ | Feature groups | Cron, sync, etc. | Specifici per modulo |
| 99 | `costs` | Costi operazioni | **Sempre collapsed: true** |

**Reference**: `modules/seo-tracking/module.json` (implementazione completa)

**Regole:**
- Tutti i settings DEVONO avere `"group"` assegnato (no settings "ungrouped")
- Costi SEMPRE nel gruppo `costs` con `collapsed: true`
- Moduli AI DEVONO avere `ai_provider`, `ai_model`, `ai_fallback_enabled` in gruppo `ai_config`
- Icone gruppi: `sparkles`, `globe-alt`, `cog`, `chart-bar`, `refresh`, `currency-euro`

---

## STRUTTURA PROGETTO

```
seo-toolkit/
├── core/                    # Framework (Router, Database, Auth, Credits)
├── services/                # Servizi CONDIVISI
│   ├── AiService.php        # Claude API - USARE SEMPRE
│   ├── ScraperService.php   # Scraping con Readability - USARE SEMPRE
│   ├── GoogleOAuthService.php
│   ├── SitemapService.php
│   ├── CsvImportService.php
│   ├── DataForSeoService.php # Rank check API
│   ├── ApiLoggerService.php # Logging chiamate API - USARE SEMPRE
│   └── RapidApiKeywordService.php # Keyword volumes (seo-tracking)
├── modules/
│   ├── ai-content/          # REFERENCE per nuovi moduli
│   ├── seo-audit/
│   ├── ads-analyzer/
│   ├── internal-links/
│   ├── seo-tracking/
│   └── keyword-research/    # AI Keyword Research
├── shared/views/
│   ├── layout.php
│   └── components/
│       ├── nav-items.php    # Sidebar con accordion
│       └── import-tabs.php  # Componente import URL
└── config/
```

---

## COMANDI FREQUENTI

### SSH Produzione
```bash
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html
```

### Git Deploy
```bash
# Locale
git add .
git commit -m "tipo: descrizione"
git push origin main

# Produzione (da SSH)
git pull origin main
```

### Verifica Sintassi PHP
```bash
php -l path/to/file.php
```

### Test Connessione DB (locale)
```bash
mysql -u root seo_toolkit -e "SHOW TABLES;"
```

---

## TEST LOCALE

### Credenziali Test
```
URL Base: http://localhost/seo-toolkit
Email: admin@seo-toolkit.local
Password: admin123
```

### Test con cURL (da terminale)
```bash
# 1. Login e salva cookie
curl -c cookies.txt -b cookies.txt "http://localhost/seo-toolkit/login" -s | grep -oP 'name="csrf_token"\s+value="\K[^"]+'
# Usa il token ottenuto:
curl -c cookies.txt -b cookies.txt -X POST "http://localhost/seo-toolkit/login" -d "email=admin@seo-toolkit.local&password=admin123&csrf_token=TOKEN_QUI"

# 2. Test pagine autenticate
curl -b cookies.txt "http://localhost/seo-toolkit/ai-content" -s -o /dev/null -w "%{http_code}"
```

### Pagine da Testare (Meta Tags)
- Dashboard: `/ai-content/projects/{id}/meta-tags`
- Import: `/ai-content/projects/{id}/meta-tags/import`
- Lista: `/ai-content/projects/{id}/meta-tags/list`
- Preview: `/ai-content/projects/{id}/meta-tags/{tagId}/preview`

---

## PATTERN DI SVILUPPO

### Nuovo Controller
```php
<?php
namespace Modules\NomeModulo\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;

class NomeController
{
    public function index()
    {
        Middleware::auth();
        $user = Auth::user();
        
        // logica...
        
        View::render('nome-modulo::vista', [
            'data' => $data,
            'modules' => \Core\ModuleLoader::getActiveModules()
        ]);
    }
}
```

### Chiamata AI
```php
use Services\AiService;

$ai = new AiService('nome-modulo'); // SEMPRE specificare modulo

if (!$ai->isConfigured()) {
    return ['error' => true, 'message' => 'AI non configurata'];
}

$result = $ai->analyze($userId, $prompt, $content, 'nome-modulo');

Database::reconnect(); // PRIMA di salvare
$model->save($data);
```

### Consumo Crediti
```php
use Core\Credits;

$cost = Credits::getCost('operazione', 'nome-modulo');

if (!Credits::hasEnough($userId, $cost)) {
    return ['error' => "Crediti insufficienti. Richiesti: {$cost}"];
}

// Esegui operazione...

Credits::consume($userId, $cost, 'operazione', 'nome-modulo');
```

### Scraping Contenuti Web
```php
use Services\ScraperService;

$scraper = new ScraperService();
$result = $scraper->scrape($url);  // USA SEMPRE scrape()

if ($result['success']) {
    $title = $result['title'];
    $content = $result['content'];          // Testo pulito via Readability
    $headings = $result['headings'];        // Array struttura H1-H6
    $wordCount = $result['word_count'];
    $internalLinks = $result['internal_links']; // Link interni estratti
}

// MAI usare DOMDocument/XPath diretto per estrarre contenuti!
// MAI creare servizi di scraping custom per modulo!
```

### Logging Chiamate API Esterne
```php
use Services\ApiLoggerService;

$startTime = microtime(true);  // PRIMA della chiamata

// Esegui chiamata API...
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);

// Log DOPO la chiamata
ApiLoggerService::log('provider-name', '/endpoint', $requestPayload, $data, $httpCode, $startTime, [
    'module' => 'nome-modulo',
    'cost' => $data['cost'] ?? 0,
    'context' => "keyword={$keyword}",
]);

// Provider validi: dataforseo, serpapi, serper, google_gsc, google_oauth, google_ga4
// IMPORTANTE: Redact API keys prima del log!
```

### Background Processing (Operazioni Lunghe)

**Quando usare job in background:**
- Operazioni > 5 secondi
- Elaborazione batch (3+ items)
- Chiamate API esterne con rate limits
- Operazioni che devono essere cancellabili dall'utente

**Pattern Database:**
```sql
-- Tabella job tracking (esempio: st_rank_jobs)
CREATE TABLE {prefix}_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('cron', 'manual') DEFAULT 'manual',
    status ENUM('pending', 'running', 'completed', 'error', 'cancelled') DEFAULT 'pending',

    -- Progress
    items_requested INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500) DEFAULT NULL,

    -- Timestamps
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_project (project_id)
);

-- Aggiungi job_id alla tabella queue esistente
ALTER TABLE {prefix}_queue ADD COLUMN job_id INT DEFAULT NULL;
```

**Pattern Backend (Controller):**
```php
// 1. POST /start-job - Crea job e ritorna job_id
public function startJob(int $projectId): void {
    // Verifica auth, crediti, progetto
    // Crea record in {prefix}_jobs
    // Popola {prefix}_queue con job_id
    echo json_encode(['success' => true, 'job_id' => $jobId]);
}

// 2. GET /stream - SSE per progress real-time
public function processStream(int $projectId): void {
    // Headers SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    // CRITICO: Continua esecuzione anche se proxy chiude connessione
    ignore_user_abort(true);
    set_time_limit(0);

    // IMPORTANTE: Chiudi sessione PRIMA del loop
    session_write_close();

    // Loop elaborazione
    while ($item = $queue->getNextPendingForJob($jobId)) {
        // IMPORTANTE: Riconnetti DB dopo ogni chiamata API
        Database::reconnect();

        // Check cancellazione
        if ($jobModel->isCancelled($jobId)) {
            $this->sendEvent('cancelled', [...]);
            break;
        }

        // Elabora item
        // ...

        // Invia evento SSE
        $this->sendEvent('item_completed', [...]);
    }

    $this->sendEvent('completed', [...]);
}

// Helper per SSE
private function sendEvent(string $event, array $data): void {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();  // Guard: server potrebbe non avere buffer
    flush();
}

// 3. GET /job-status - Polling fallback
public function jobStatus(int $projectId): void {
    echo json_encode(['success' => true, 'job' => $jobModel->getJobResponse($jobId)]);
}

// 4. POST /cancel-job - Annulla job
public function cancelJob(int $projectId): void {
    $jobModel->cancel($jobId);
    echo json_encode(['success' => true]);
}
```

**Pattern Frontend (Alpine.js):**
```javascript
// Avvia job
async startJob() {
    const resp = await fetch('/start-job', { method: 'POST', body: formData });
    const data = await resp.json();
    this.jobId = data.job_id;
    this.connectSSE();
}

// Connetti SSE
connectSSE() {
    this.eventSource = new EventSource(`/stream?job_id=${this.jobId}`);

    this.eventSource.addEventListener('item_completed', (e) => {
        const data = JSON.parse(e.data);
        // Aggiorna UI
    });

    this.eventSource.addEventListener('completed', (e) => {
        this.eventSource.close();
        // Finalizza
    });

    // Errori custom dal server (event: error nel stream)
    this.eventSource.addEventListener('error', (e) => {
        try {
            const d = JSON.parse(e.data);
            // Errore server-sent con messaggio
            this.status = 'Errore: ' + d.message;
            this.eventSource.close();
        } catch (_) {
            // Errore nativo SSE (no data) - gestito da onerror sotto
        }
    });

    // Fallback a polling se SSE si disconnette (proxy timeout)
    this.eventSource.onerror = () => {
        this.eventSource.close();
        this.startPolling();
    };
}

// Polling fallback
async startPolling() {
    const resp = await fetch(`/job-status?job_id=${this.jobId}`);
    const data = await resp.json();
    // Aggiorna UI
    if (data.job.status === 'running') {
        setTimeout(() => this.startPolling(), 2000);
    }
}
```

**Eventi SSE standard:**
- `started` - Job avviato con totale items
- `progress` - Aggiornamento progresso (current_item, percent)
- `item_completed` - Singolo item completato con risultati
- `item_error` - Errore su singolo item
- `completed` - Job terminato con successo
- `cancelled` - Job annullato dall'utente

**Reference Implementation:** `modules/seo-tracking/controllers/RankCheckController.php`

---

## ICONE HEROICONS (più usate)

```html
<!-- Check -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- X/Close -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
</svg>

<!-- Plus -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
</svg>

<!-- Search -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
</svg>

<!-- Download -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
</svg>

<!-- Refresh -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
</svg>

<!-- Trash -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
</svg>
```

---

## CHECKLIST PRE-COMMIT

```
[ ] Nessun curl diretto per API AI
[ ] Nessuna icona Lucide/FontAwesome
[ ] Tutti i testi UI in italiano
[ ] Tabelle con prefisso modulo corretto
[ ] Nessuna API key in file
[ ] Query SQL con prepared statements
[ ] CSRF token: campo `_csrf_token` in form/FormData, `csrf_token()` per il valore
[ ] Database::reconnect() dopo chiamate lunghe
[ ] Scraping usa ScraperService::scrape()
[ ] Chiamate API esterne loggano con ApiLoggerService
[ ] php -l su file modificati
[ ] Operazioni lunghe usano job in background (SSE)
[ ] session_write_close() prima di loop SSE
[ ] ignore_user_abort(true) + set_time_limit(0) in SSE streams
[ ] ob_flush() con guard: if (ob_get_level()) ob_flush()
[ ] ModuleLoader::getSetting() per settings modulo (NON getModuleSetting)
[ ] SSE: salvare risultati nel DB PRIMA dell'evento completed (polling fallback)
[ ] Testare in produzione: deploy → creare tabelle DB → attivare modulo
```

---

## WORKFLOW CONSIGLIATO

1. **Leggi docs pertinenti** prima di iniziare
2. **Un task alla volta** - fermati per conferma
3. **Verifica sintassi** dopo ogni modifica PHP
4. **Test manuale** in browser prima di commit
5. **Commit atomici** con messaggi descrittivi

---

## TROUBLESHOOTING RAPIDO

| Problema | Causa probabile | Fix |
|----------|-----------------|-----|
| 500 Error | Sintassi PHP | `php -l file.php` |
| "MySQL gone away" | Connessione scaduta | `Database::reconnect()` |
| Sidebar non appare | `$modules` non passato | Aggiungi a View::render() |
| Icone non visibili | Lucide invece di Heroicons | Sostituisci con SVG |
| Crediti non scalano | `Credits::consume()` mancante | Aggiungi dopo operazione |
| "Database is limited" | Limite SiteGround temporaneo | Attendi qualche minuto e riprova |
| Scraping poche parole | CSS selectors invece di Readability | Usa `ScraperService::scrape()` |
| SSE blocca altre request | Sessione non chiusa | `session_write_close()` prima del loop |
| SSE non invia eventi | Output buffering | `ob_flush(); flush();` dopo ogni evento |
| Job bloccato in processing | Processo crashato | `resetStuckProcessing(30)` nel model queue |
| API call non loggata | ApiLoggerService mancante | Aggiungi log dopo ogni chiamata API |
| "Errore di connessione" AJAX POST | Campo CSRF con nome sbagliato | Usare `_csrf_token` (con underscore), NON `csrf_token` |
| SSE timeout dopo 30s | `set_time_limit` non rimosso | `set_time_limit(0)` prima del loop SSE e prima di AI call |
| SSE "Connessione persa" 90% | Proxy SiteGround chiude SSE | `ignore_user_abort(true)` + polling fallback |
| SSE dati persi dopo disconnect | Risultati inviati solo via SSE | Salvare nel DB PRIMA dell'evento `completed` |
| `ob_flush(): Failed to flush` | Nessun buffer attivo | `if (ob_get_level()) ob_flush()` |
| `getModuleSetting()` undefined | Metodo inesistente | Usare `ModuleLoader::getSetting(slug, key, default)` |
| Modulo non visibile in prod | Tabelle/record assenti | Creare tabelle + INSERT in `modules` |

---

*File generato per Claude Code - Non modificare manualmente senza aggiornare la data*
