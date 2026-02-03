# AINSTEIN - Golden Rules

**13 regole INVIOLABILI per lo sviluppo**

Queste regole garantiscono consistenza, manutenibilità e qualità del codice.

---

## 1️⃣ AiService SEMPRE Centralizzato

```php
// ✅ CORRETTO
$ai = new AiService('nome-modulo');
$response = $ai->analyze($userId, $systemPrompt, $userPrompt, 'nome-modulo');

// ❌ VIETATO - Mai curl diretto
$ch = curl_init('https://api.anthropic.com/...');
```

**Perché:** Logging automatico, gestione crediti, fallback provider, metriche unificate.

---

## 2️⃣ Icone SOLO Heroicons SVG

```html
<!-- ✅ CORRETTO - SVG inline -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- ❌ VIETATO -->
<i data-lucide="check"></i>
<i class="fa fa-check"></i>
```

**Perché:** Nessuna dipendenza JS, performance, consistenza UI.

---

## 3️⃣ Lingua UI: ITALIANO

Tutti i testi visibili all'utente DEVONO essere in italiano.

```php
// ✅ CORRETTO
$_SESSION['flash_success'] = 'Progetto creato con successo';
$_SESSION['flash_error'] = 'Crediti insufficienti';

// ❌ VIETATO
$_SESSION['flash_success'] = 'Project created successfully';
```

**Eccezioni:** Termini tecnici universali (URL, CSV, API, SEO, OAuth, etc.)

---

## 4️⃣ Prefisso DB per Modulo

Ogni modulo usa un prefisso univoco di 2-3 lettere:

| Modulo | Prefisso |
|--------|----------|
| ai-content | `aic_` |
| seo-audit | `sa_` |
| seo-tracking | `st_` |
| internal-links | `il_` |
| ads-analyzer | `ga_` |
| content-creator | `cc_` |

```sql
-- ✅ CORRETTO
CREATE TABLE sa_projects (...);
CREATE TABLE sa_pages (...);

-- ❌ VIETATO
CREATE TABLE projects (...);
CREATE TABLE seo_audit_projects (...);
```

---

## 5️⃣ API Keys in Database, MAI in .env

```php
// ✅ CORRETTO - Recupera da tabella settings
$apiKey = Settings::get('anthropic_api_key');
$serpApiKey = Settings::get('serpapi_key');

// ❌ VIETATO
$apiKey = $_ENV['ANTHROPIC_API_KEY'];
$apiKey = getenv('ANTHROPIC_API_KEY');
```

**Perché:** Gestione centralizzata da Admin Panel, nessun file sensibile nel repo.

---

## 6️⃣ Pattern Routes Standard

```php
// ✅ CORRETTO - Pattern uniforme
/modulo/projects                     // Lista progetti
/modulo/projects/create              // Form creazione
/modulo/projects/{id}                // Dettaglio progetto
/modulo/projects/{id}/sezione        // Sotto-sezione
/modulo/projects/{id}/sezione/{subId} // Dettaglio sotto-elemento

// ❌ VIETATO - Pattern inconsistenti
/modulo/keywords/{id}/add            // Manca projects
/modulo/{id}/gsc/connect             // Manca projects
```

---

## 7️⃣ Prepared Statements SEMPRE

```php
// ✅ CORRETTO
$stmt = $pdo->prepare("SELECT * FROM sa_projects WHERE user_id = ? AND id = ?");
$stmt->execute([$userId, $projectId]);

// ❌ VIETATO - SQL injection risk
$pdo->query("SELECT * FROM sa_projects WHERE user_id = $userId");
```

---

## 8️⃣ CSRF Token su Tutti i Form POST

```html
<!-- ✅ CORRETTO -->
<form method="POST" action="/modulo/action">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <!-- campi -->
</form>
```

```php
// Nel controller
if (!verify_csrf_token($_POST['csrf_token'])) {
    die('Token CSRF non valido');
}
```

---

## 9️⃣ ai-content è il Modulo Reference

Quando implementi un nuovo modulo, **copia i pattern da ai-content**:

- Struttura controller
- Integrazione AiService
- Pattern wizard multi-step
- Gestione crediti
- Flash messages
- Views layout

```bash
# Prima di iniziare un nuovo modulo
cat modules/ai-content/controllers/WizardController.php
cat modules/ai-content/services/ArticleGeneratorService.php
```

---

## 🔟 Database::reconnect() per Operazioni Lunghe

```php
// ✅ CORRETTO - Prima di operazioni post-API call
$response = $aiService->analyze(...); // Può durare 30-60s

Database::reconnect(); // Riconnetti prima di salvare
$model->save($data);

// ❌ VIETATO - Rischio "MySQL server has gone away"
$response = $aiService->analyze(...);
$model->save($data); // Può fallire se connessione scaduta
```

---

## 1️⃣1️⃣ OAuth GSC: Pattern seo-tracking

**Quando implementi flussi OAuth verso Google Search Console, usa `seo-tracking` come reference:**

```php
// ✅ CORRETTO - Usa GoogleOAuthService centralizzato
$oauth = new \Services\GoogleOAuthService();
$authUrl = $oauth->getAuthUrl($moduleSlug, $projectId, GoogleOAuthService::SCOPE_GSC);

// ✅ CORRETTO - Redirect URI dinamico (supporta multi-dominio)
public function getRedirectUri(): string {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = \Core\Router::url('/oauth/google/callback');
    return $scheme . '://' . $host . $basePath;
}

// ❌ VIETATO - URL hardcoded
$redirectUri = 'https://ainstein.it/oauth/google/callback';
```

**File reference:**
- `services/GoogleOAuthService.php` - Servizio OAuth centralizzato
- `modules/seo-tracking/services/GscService.php` - Sync dati GSC
- `modules/seo-tracking/controllers/GscController.php` - Flow OAuth
- `modules/seo-tracking/models/GscConnection.php` - Model token storage

**Schema DB pattern per OAuth tokens:**
```sql
CREATE TABLE st_gsc_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL UNIQUE,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,
    property_url VARCHAR(500) NULL,      -- NULL finché utente non seleziona
    property_type ENUM('URL_PREFIX','DOMAIN') NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_sync_at TIMESTAMP NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Flusso OAuth GSC:**
1. Utente clicca "Connetti GSC"
2. `GscController::connect()` → redirect a Google
3. Google callback → `OAuthController::googleCallback()`
4. Token salvato in `st_gsc_connections` (property NULL)
5. Redirect a `select-property` → utente sceglie property
6. `GscController::saveProperty()` → salva property_url
7. Sync dati → `GscService::syncSearchAnalytics()`

---

## 1️⃣2️⃣ Scraping: SEMPRE ScraperService + Readability

**Per qualsiasi operazione di scraping contenuti web, usare SEMPRE `ScraperService::scrape()`:**

```php
// ✅ CORRETTO - Usa ScraperService con Readability
use Services\ScraperService;

$scraper = new ScraperService();
$result = $scraper->scrape($url);

if ($result['success']) {
    $title = $result['title'];
    $content = $result['content'];      // Testo pulito (Readability)
    $headings = $result['headings'];    // Array H1-H6
    $wordCount = $result['word_count'];
    $internalLinks = $result['internal_links']; // Link interni con anchor text
}

// ❌ VIETATO - Scraping custom o CSS selectors manuali
$dom = new DOMDocument();
$dom->loadHTML($html);
$content = $xpath->query('//article')->item(0)->textContent;
```

**Perché:**
- Mozilla Readability estrae intelligentemente il contenuto principale
- Gestisce siti Elementor, WordPress, e qualsiasi struttura HTML
- Include estrazione automatica di heading e link interni
- Fallback robusto se Readability fallisce
- Risultati consistenti su tutti i moduli

**Moduli che usano ScraperService:**
| Modulo | Controller/Script | Metodo |
|--------|-------------------|--------|
| ai-content | WizardController | `scrape()` |
| ai-content | AutoController | `scrape()` |
| ai-content | process_queue.php | `scrape()` |
| ai-content | dispatcher.php | `scrape()` |
| ai-optimizer | ArticleAnalyzerService | `scrape()` |
| seo-audit | Crawler | `scrape()` |

**Output standard ScraperService::scrape():**
```php
[
    'success' => bool,
    'title' => string,
    'content' => string,        // Testo pulito senza HTML
    'html' => string,           // HTML del contenuto principale
    'headings' => array,        // ['h1' => [...], 'h2' => [...], ...]
    'word_count' => int,
    'internal_links' => array   // [['url' => ..., 'anchor' => ..., 'context' => ...], ...]
]
```

---

## 1️⃣3️⃣ API Logging Centralizzato

**SEMPRE** loggare tutte le chiamate API esterne con `ApiLoggerService`.

### Provider Supportati
- `dataforseo` - DataForSEO (SERP, keyword volumes)
- `serpapi` - SerpAPI (SERP fallback)
- `serper` - Serper.dev (SERP fallback)
- `google_gsc` - Google Search Console
- `google_oauth` - Google OAuth refresh
- `google_ga4` - Google Analytics 4

### Pattern Obbligatorio

```php
use Services\ApiLoggerService;

// 1. Cattura timestamp PRIMA della chiamata
$startTime = microtime(true);

// 2. Esegui chiamata API
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode($response, true);

// 3. Log DOPO la chiamata
ApiLoggerService::log('provider', '/endpoint', $request, $data, $httpCode, $startTime, [
    'module' => 'modulo-slug',
    'cost' => $data['cost'] ?? 0,
    'context' => 'info diagnostica',
]);
```

### Regole Specifiche
- **Redact API keys**: Rimuovi sempre le chiavi API dal payload prima del log
- **Paginazione**: Logga solo prima pagina + errori (evita spam)
- **Context**: Includi info utili per debug (keyword, page, target)
- **Cost tracking**: Estrai il costo dalla risposta se disponibile

### ❌ MAI fare
```php
// MAI chiamare API senza logging
$response = file_get_contents($apiUrl);

// MAI loggare API keys
ApiLoggerService::log('serpapi', '/search', ['api_key' => $key], ...);

// MAI loggare ogni pagina di paginazione
for ($i = 0; $i < 10; $i++) {
    ApiLoggerService::log(...); // 10 log inutili!
}
```

### ✅ SEMPRE fare
```php
// Redact API key
$logParams = $params;
$logParams['api_key'] = '[REDACTED]';
ApiLoggerService::log('serpapi', '/search', $logParams, $data, $httpCode, $startTime, [...]);

// Log condizionale per paginazione
if ($page === 1 || $error) {
    ApiLoggerService::log(...);
}
```

---

## 📋 CHECKLIST PRE-COMMIT

Prima di ogni commit, verifica:

- [ ] Nessun `curl` diretto per API AI
- [ ] Nessuna icona Lucide/FontAwesome
- [ ] Tutti i testi UI in italiano
- [ ] Tabelle con prefisso modulo corretto
- [ ] Nessuna API key in file
- [ ] Routes seguono pattern standard
- [ ] Query SQL con prepared statements
- [ ] CSRF token su form POST
- [ ] `Database::reconnect()` dopo chiamate lunghe
- [ ] Scraping usa `ScraperService::scrape()` con Readability
- [ ] Chiamate API esterne loggano con `ApiLoggerService::log()`

---

## 🚫 VIOLAZIONI COMUNI

| Violazione | Dove cercare | Fix |
|------------|--------------|-----|
| Lucide icons | `grep -r "data-lucide" modules/` | Sostituire con SVG Heroicons |
| Curl diretto | `grep -r "curl_init.*anthropic" modules/` | Usare AiService |
| Testi inglese | Review manuale views | Tradurre in italiano |
| SQL injection | `grep -r "query\(.*\$" modules/` | Usare prepare() |
| Scraping custom | `grep -r "DOMDocument\|loadHTML" modules/` | Usare ScraperService::scrape() |
| Chiamata API senza log | `grep -r "curl_exec\|file_get_contents.*api" services/` | Aggiungere ApiLoggerService::log() |

---

*Documento di riferimento - Non modificare senza approvazione*
