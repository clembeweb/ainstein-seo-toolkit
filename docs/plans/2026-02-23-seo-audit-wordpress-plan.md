# SEO Audit via WordPress Plugin - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enable SEO Audit to analyze WordPress sites without scraping by using the WP plugin REST API, with centralized CMS connectors at the Global Project level, and fix the crawl system to run in background (not dependent on frontend polling).

**Architecture:** New `project_connectors` table at core level linked to Global Projects. WordPress plugin extended with `/seo-audit` endpoint returning pre-extracted SEO data. SEO Audit import page gets a "WordPress" tab. Crawl system refactored from frontend-polling to SSE + background processing with `ignore_user_abort(true)`.

**Tech Stack:** PHP 8+, MySQL, Alpine.js, Tailwind CSS, SSE (Server-Sent Events), WordPress REST API

---

## Task 1: Database Migration - project_connectors table

**Files:**
- Create: `database/migrations/010_create_project_connectors.sql`

**Step 1: Write the migration SQL**

```sql
-- Project Connectors (centralized CMS connections for Global Projects)
CREATE TABLE IF NOT EXISTS project_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('wordpress','shopify','prestashop','magento') NOT NULL,
    name VARCHAR(255) NOT NULL,
    config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_test_at DATETIME NULL,
    last_test_status ENUM('success','error') NULL,
    last_test_message VARCHAR(500) NULL,
    seo_plugin VARCHAR(50) NULL,
    wp_version VARCHAR(20) NULL,
    plugin_version VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Step 2: Run migration locally**

Run: `C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe -u root seo_toolkit < database/migrations/010_create_project_connectors.sql`
Expected: No errors

**Step 3: Verify table created**

Run: `C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe -u root seo_toolkit -e "DESCRIBE project_connectors;"`
Expected: All columns listed

**Step 4: Commit**

```bash
git add database/migrations/010_create_project_connectors.sql
git commit -m "feat: add project_connectors table for centralized CMS connections"
```

---

## Task 2: Database Migration - sa_pages source columns + sa_crawl_jobs table

**Files:**
- Create: `modules/seo-audit/database/migrations/006_add_wordpress_support.sql`

**Step 1: Write the migration SQL**

```sql
-- Add source tracking to sa_pages
ALTER TABLE sa_pages ADD COLUMN source ENUM('crawl','wordpress') DEFAULT 'crawl' AFTER session_id;
ALTER TABLE sa_pages ADD COLUMN cms_entity_id INT NULL AFTER source;
ALTER TABLE sa_pages ADD COLUMN cms_entity_type VARCHAR(50) NULL AFTER cms_entity_id;

-- Background crawl jobs (fixes navigation-away bug)
CREATE TABLE IF NOT EXISTS sa_crawl_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('crawl','wordpress') DEFAULT 'crawl',
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    config JSON NULL,
    items_total INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500) NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_project (project_id),
    INDEX idx_session (session_id),
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sa_crawl_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Step 2: Run migration locally**

Run: `C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe -u root seo_toolkit < modules/seo-audit/database/migrations/006_add_wordpress_support.sql`
Expected: No errors

**Step 3: Verify changes**

Run: `C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe -u root seo_toolkit -e "DESCRIBE sa_pages;" && C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe -u root seo_toolkit -e "DESCRIBE sa_crawl_jobs;"`
Expected: `source`, `cms_entity_id`, `cms_entity_type` in sa_pages; all sa_crawl_jobs columns

**Step 4: Commit**

```bash
git add modules/seo-audit/database/migrations/006_add_wordpress_support.sql
git commit -m "feat: add WordPress source tracking and background crawl jobs to seo-audit"
```

---

## Task 3: Core Model - ProjectConnector

**Files:**
- Create: `core/Models/ProjectConnector.php`

**Step 1: Create the model**

Pattern follows `core/Models/GlobalProject.php`. Key methods:

- `find(int $id): ?array` - Find connector by ID
- `getByProject(int $projectId): array` - All connectors for a project
- `getActiveByProject(int $projectId, string $type = null): ?array` - Active connector, optionally by type
- `create(array $data): int` - Insert new connector
- `update(int $id, array $data): bool` - Update connector
- `delete(int $id): bool` - Delete connector
- `updateTestStatus(int $id, string $status, ?string $message, ?array $extra): bool` - Update after test

Config JSON structure: `{ "url": "https://example.com", "api_key": "stk_abc123..." }`

The `getActiveByProject()` method is critical - it's how seo-audit finds the WP connector for a project:

```php
public function getActiveByProject(int $projectId, ?string $type = null): ?array
{
    $sql = "SELECT * FROM project_connectors WHERE project_id = ? AND is_active = 1";
    $params = [$projectId];
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY created_at DESC LIMIT 1";
    return Database::fetch($sql, $params) ?: null;
}
```

**Step 2: Verify syntax**

Run: `php -l core/Models/ProjectConnector.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add core/Models/ProjectConnector.php
git commit -m "feat: add ProjectConnector model for centralized CMS connections"
```

---

## Task 4: CrawlJob Model for seo-audit

**Files:**
- Create: `modules/seo-audit/models/CrawlJob.php`

**Step 1: Create the model**

Pattern follows `modules/seo-tracking/models/RankJob.php`. Key methods:

- `create(array $data): int` - Create job (project_id, session_id, user_id, type, items_total, config JSON)
- `find(int $id): ?array` - Find job by ID
- `findActiveByProject(int $projectId): ?array` - Active (pending/running) job for project
- `start(int $id): bool` - Set status=running, started_at=now
- `updateProgress(int $id, int $completed, ?string $currentItem): bool` - Update progress
- `incrementCompleted(int $id): bool` - items_completed + 1
- `incrementFailed(int $id): bool` - items_failed + 1
- `complete(int $id): bool` - Set status=completed, completed_at=now
- `markError(int $id, string $message): bool` - Set status=error, error_message
- `cancel(int $id): bool` - Set status=cancelled if pending/running
- `isCancelled(int $id): bool` - Check if status=cancelled
- `getJobResponse(int $id): array` - Formatted response for SSE/polling
- `resetStuckJobs(int $minutesThreshold = 30): int` - Reset running jobs older than N minutes

**Step 2: Verify syntax**

Run: `php -l modules/seo-audit/models/CrawlJob.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add modules/seo-audit/models/CrawlJob.php
git commit -m "feat: add CrawlJob model for background crawl processing"
```

---

## Task 5: WordPress Plugin - Add /seo-audit endpoint

**Files:**
- Modify: `storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php`

**Step 1: Register the new endpoint**

In the `register_routes()` method (around line 247), add after existing route registrations:

```php
// SEO Audit - Full page data extraction
register_rest_route('seo-toolkit/v1', '/seo-audit', [
    'methods'  => 'GET',
    'callback' => [$this, 'handle_seo_audit'],
    'permission_callback' => [$this, 'check_api_key'],
    'args' => [
        'per_page' => ['default' => 50, 'sanitize_callback' => 'absint'],
        'page'     => ['default' => 1, 'sanitize_callback' => 'absint'],
        'type'     => ['default' => 'post,page', 'sanitize_callback' => 'sanitize_text_field'],
    ],
]);
```

**Step 2: Implement handle_seo_audit() method**

This method:
1. Queries published posts/pages/products based on `type` param
2. For each post, extracts ALL SEO data:
   - Title tag (from SEO plugin meta or `wp_title()`)
   - Meta description (from SEO plugin meta)
   - Canonical URL (`wp_get_canonical_url()`)
   - Robots meta (from SEO plugin or WP 5.7+ `wp_robots()`)
   - Open Graph tags (from SEO plugin meta)
   - Headings: parse `post_content` with DOMDocument, extract H1-H6 with text
   - Images: parse `post_content` with DOMDocument, extract src/alt/width/height
   - Internal links: parse `post_content`, compare domain with `home_url()`
   - External links: same parse, opposite domain check
   - Schema JSON-LD: capture from output buffer of `wp_head()` or SEO plugin
   - Word count: `str_word_count(wp_strip_all_tags(post_content))`
3. First page also includes `site_info` with:
   - `has_ssl`: `is_ssl()`
   - `has_robots_txt`: `file_exists(ABSPATH . 'robots.txt')` or virtual robots
   - `robots_txt_content`: read the file
   - `has_sitemap`: check common sitemap URLs
   - `wp_version`, `seo_plugin`, `plugin_version`

Private helpers needed:
- `extract_headings(string $html): array` - DOMDocument parse for H1-H6
- `extract_images(string $html): array` - DOMDocument parse for img tags
- `extract_links(string $html, string $siteUrl): array` - DOMDocument parse, split internal/external
- `extract_schema_jsonld(int $postId): array` - Output buffer wp_head or SEO plugin meta
- `get_seo_title(int $postId): string` - SEO plugin-aware title extraction
- `get_seo_description(int $postId): string` - SEO plugin-aware desc extraction
- `get_robots_meta(int $postId): string` - SEO plugin or WP robots
- `get_og_tags(int $postId): array` - OG title, description, image from SEO plugin

**Step 3: Verify syntax**

Run: `php -l storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php`
Expected: No syntax errors

**Step 4: Commit**

```bash
git add storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php
git commit -m "feat: add /seo-audit endpoint to WordPress plugin for scraping-free audit"
```

---

## Task 6: Shared WordPressSeoConnector Service

**Files:**
- Create: `services/connectors/WordPressSeoConnector.php`

**Step 1: Create the connector**

Pattern based on `modules/content-creator/services/connectors/WordPressConnector.php` but focused on SEO audit data:

```php
namespace Services\Connectors;

use Services\ApiLoggerService;

class WordPressSeoConnector
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
    }

    public function test(): array { /* GET /seo-toolkit/v1/ping */ }

    public function fetchSeoAudit(int $page = 1, int $perPage = 50, string $types = 'post,page'): array
    {
        /* GET /seo-toolkit/v1/seo-audit?page=$page&per_page=$perPage&type=$types */
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        /* curl with X-SEO-Toolkit-Key header, ApiLoggerService::log() */
    }
}
```

Key: `fetchSeoAudit()` returns the response from the WP plugin's `/seo-audit` endpoint. Handles pagination (returns `total`, `total_pages`, `current_page`, `pages` array).

All API calls logged via `ApiLoggerService::log('wordpress_api', $endpoint, ...)` with module `'seo-audit'`.

**Step 2: Verify syntax**

Run: `php -l services/connectors/WordPressSeoConnector.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add services/connectors/WordPressSeoConnector.php
git commit -m "feat: add WordPressSeoConnector for SEO audit data fetching"
```

---

## Task 7: GlobalProjectController - Connector CRUD

**Files:**
- Modify: `controllers/GlobalProjectController.php`

**Step 1: Add connector methods to controller**

Add these methods to `GlobalProjectController`:

- `addConnector(int $id): void` - POST `/projects/{id}/connectors`
  - Validates type, name, url, api_key
  - Tests connection first via `WordPressSeoConnector::test()`
  - Creates record in `project_connectors`
  - Saves `seo_plugin`, `wp_version`, `plugin_version` from test response
  - Flash success/error, redirect to `/projects/{id}`

- `testConnector(int $id): void` - POST `/projects/{id}/connectors/test`
  - AJAX endpoint, takes connector_id
  - Loads connector config, runs test
  - Updates `last_test_at`, `last_test_status`, `last_test_message`
  - Returns JSON `{ success, message, seo_plugin, wp_version }`

- `removeConnector(int $id): void` - POST `/projects/{id}/connectors/remove`
  - Validates connector belongs to project
  - Deletes record
  - Flash success, redirect

- `downloadPlugin(): void` - GET `/projects/download-plugin/wordpress`
  - Reuses ZIP logic from content-creator's `ConnectorController::downloadPlugin()`
  - Serves `storage/plugins/seo-toolkit-connector/` as ZIP download

**Step 2: Update dashboard() method**

Add connector data to the view render (around line 152):

```php
$connectorModel = new \Core\Models\ProjectConnector();
$connectors = $connectorModel->getByProject($id);
```

Pass `'connectors' => $connectors` to `View::render()`.

**Step 3: Add routes**

In `public/index.php`, after existing `/projects` routes:

```php
Router::post('/projects/:id/connectors', function ($id) {
    $controller = new \Controllers\GlobalProjectController();
    $controller->addConnector((int) $id);
});

Router::post('/projects/:id/connectors/test', function ($id) {
    $controller = new \Controllers\GlobalProjectController();
    $controller->testConnector((int) $id);
});

Router::post('/projects/:id/connectors/remove', function ($id) {
    $controller = new \Controllers\GlobalProjectController();
    $controller->removeConnector((int) $id);
});

Router::get('/projects/download-plugin/wordpress', function () {
    $controller = new \Controllers\GlobalProjectController();
    $controller->downloadPlugin();
});
```

**Step 4: Verify syntax**

Run: `php -l controllers/GlobalProjectController.php`
Expected: No syntax errors

**Step 5: Commit**

```bash
git add controllers/GlobalProjectController.php public/index.php
git commit -m "feat: add CMS connector management to Global Projects"
```

---

## Task 8: Dashboard View - CMS Connector Card

**Files:**
- Modify: `shared/views/projects/dashboard.php`

**Step 1: Add CMS connector section**

Insert after the "Moduli Attivi" section and before the project description section. The card shows:

**No connector configured:**
- Invito a configurare WordPress
- Link download plugin WP
- Form inline: nome, URL sito, API key, bottone "Connetti"

**Connector configured:**
- Status badge (verde connesso / rosso errore)
- Info: nome sito, SEO plugin, versione WP, versione plugin
- Ultimo test con timestamp
- Bottoni: "Testa Connessione" (AJAX), "Rimuovi" (confirm + POST)

**Alpine.js component** `connectorManager()`:
- `testing: false` - stato bottone test
- `showForm: false` - toggle form aggiunta
- `async testConnection(connectorId)` - AJAX POST a `/projects/{id}/connectors/test`
- `confirmRemove(connectorId)` - confirm dialog + POST a `/projects/{id}/connectors/remove`

Badge sui moduli attivi: quando un connettore WP e attivo, mostrare un piccolo badge "WP" accanto ai moduli compatibili (seo-audit, content-creator).

**Step 2: Verify in browser**

Test: Navigate to `http://localhost/seo-toolkit/projects/{id}`
Expected: CMS connector section visible, form works, test button works

**Step 3: Commit**

```bash
git add shared/views/projects/dashboard.php
git commit -m "feat: add CMS connector card to Global Project dashboard"
```

---

## Task 9: Refactor CrawlController - Background Job Pattern

**Files:**
- Modify: `modules/seo-audit/controllers/CrawlController.php`

This is the fix for the navigation-away bug. Replace frontend-polling with SSE + background processing.

**Step 1: Refactor start() method**

Current: Creates session, discovers URLs, returns JSON.
New: Creates session + creates `sa_crawl_jobs` record, returns `job_id`.

```php
public function start(int $id): void
{
    // ... existing auth, validation, session creation ...
    // ... existing URL discovery ...

    // NEW: Create background job
    $jobModel = new CrawlJob();
    $jobId = $jobModel->create([
        'project_id' => $id,
        'session_id' => $session['id'],
        'user_id' => $user['id'],
        'type' => 'crawl',
        'items_total' => $pagesFound,
        'config' => json_encode($crawlConfig),
    ]);

    echo json_encode([
        'success' => true,
        'job_id' => $jobId,
        'session_id' => $session['id'],
        'pages_found' => $pagesFound,
    ]);
    exit;
}
```

**Step 2: Add processStream() method (SSE)**

New method for SSE processing. Pattern from `seo-tracking/RankCheckController::processStream()`:

```php
public function processStream(int $id): void
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    ignore_user_abort(true);
    set_time_limit(0);
    session_write_close();

    $jobId = (int) ($_GET['job_id'] ?? 0);
    // ... validate job belongs to project ...

    $jobModel->start($jobId);
    $sendEvent('started', ['total' => $job['items_total']]);

    $crawler = new CrawlerService();
    $crawler->init($id, $job['user_id'])->setSessionId($job['session_id'])->setConfig(json_decode($job['config'], true));

    while (true) {
        Database::reconnect();

        if ($jobModel->isCancelled($jobId)) {
            $sendEvent('cancelled', $jobModel->getJobResponse($jobId));
            break;
        }

        $pendingPage = /* get next pending sa_pages row */;
        if (!$pendingPage) {
            $jobModel->complete($jobId);
            $sessionModel->complete($job['session_id']);
            $sendEvent('completed', $jobModel->getJobResponse($jobId));
            break;
        }

        try {
            $pageData = $crawler->crawlPage($pendingPage['url']);
            Database::reconnect();
            $crawler->savePage($pageData);
            $issueDetector->analyzeAndSave($pageData, $pageId);
            $jobModel->incrementCompleted($jobId);
            $sendEvent('page_completed', [
                'url' => $pendingPage['url'],
                'completed' => $job['items_completed'] + 1,
                'total' => $job['items_total'],
                'issues' => $issueCount,
            ]);
        } catch (\Exception $e) {
            $jobModel->incrementFailed($jobId);
            $sendEvent('page_error', ['url' => $pendingPage['url'], 'error' => $e->getMessage()]);
        }

        usleep($crawler->getRequestDelay() * 1000);
    }

    exit;
}
```

**Step 3: Add jobStatus() method (polling fallback)**

```php
public function jobStatus(int $id): void
{
    $jobId = (int) ($_GET['job_id'] ?? 0);
    $jobModel = new CrawlJob();
    $job = $jobModel->getJobResponse($jobId);
    echo json_encode(['success' => true, 'job' => $job]);
    exit;
}
```

**Step 4: Add cancelJob() method**

```php
public function cancelJob(int $id): void
{
    Middleware::auth();
    Middleware::csrf();
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $jobModel = new CrawlJob();
    $jobModel->cancel($jobId);
    echo json_encode(['success' => true]);
    exit;
}
```

**Step 5: Keep existing crawlBatch() as deprecated fallback**

Don't delete `crawlBatch()` yet - keep it working for backward compatibility during transition. Add a deprecation comment.

**Step 6: Add new routes**

In `modules/seo-audit/routes.php`:

```php
// Background job routes (new)
Router::get('/seo-audit/project/:id/crawl/stream', function ($id) {
    $controller = new CrawlController();
    $controller->processStream((int) $id);
});

Router::get('/seo-audit/project/:id/crawl/job-status', function ($id) {
    $controller = new CrawlController();
    $controller->jobStatus((int) $id);
});

Router::post('/seo-audit/project/:id/crawl/cancel-job', function ($id) {
    $controller = new CrawlController();
    $controller->cancelJob((int) $id);
});
```

**Step 7: Verify syntax**

Run: `php -l modules/seo-audit/controllers/CrawlController.php`
Expected: No syntax errors

**Step 8: Commit**

```bash
git add modules/seo-audit/controllers/CrawlController.php modules/seo-audit/routes.php
git commit -m "feat: refactor seo-audit crawl to background job with SSE"
```

---

## Task 10: Refactor crawl-control.php Frontend - SSE + Polling Fallback

**Files:**
- Modify: `modules/seo-audit/views/partials/crawl-control.php`

**Step 1: Replace polling JS with SSE + fallback**

Replace the `setInterval`/`processBatch` code (lines ~449-534) with:

```javascript
// Alpine.js component for crawl progress
function crawlProgress() {
    return {
        jobId: null,
        eventSource: null,
        polling: false,
        progress: { completed: 0, total: 0, percent: 0, currentUrl: '' },
        status: 'idle', // idle, running, completed, cancelled, error

        startCrawl(formData) {
            // POST /crawl/start with config
            // On success: this.jobId = data.job_id; this.connectSSE();
        },

        connectSSE() {
            this.status = 'running';
            this.eventSource = new EventSource(
                `/seo-toolkit/seo-audit/project/${projectId}/crawl/stream?job_id=${this.jobId}`
            );

            this.eventSource.addEventListener('page_completed', (e) => {
                const d = JSON.parse(e.data);
                this.progress.completed = d.completed;
                this.progress.total = d.total;
                this.progress.percent = Math.round((d.completed / d.total) * 100);
                this.progress.currentUrl = d.url;
                this.updateUI();
            });

            this.eventSource.addEventListener('completed', (e) => {
                this.eventSource.close();
                this.status = 'completed';
                // Redirect to dashboard after short delay
                setTimeout(() => location.reload(), 1500);
            });

            this.eventSource.addEventListener('cancelled', (e) => {
                this.eventSource.close();
                this.status = 'cancelled';
                setTimeout(() => location.reload(), 1500);
            });

            // SSE native error = disconnection (proxy timeout, nav away)
            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.startPolling(); // Fallback
            };
        },

        async startPolling() {
            this.polling = true;
            while (this.polling) {
                try {
                    const resp = await fetch(
                        `/seo-toolkit/seo-audit/project/${projectId}/crawl/job-status?job_id=${this.jobId}`
                    );
                    const data = await resp.json();
                    if (data.success) {
                        const job = data.job;
                        this.progress.completed = job.items_completed;
                        this.progress.total = job.items_total;
                        this.progress.percent = Math.round((job.items_completed / job.items_total) * 100);
                        this.progress.currentUrl = job.current_item || '';
                        this.updateUI();

                        if (['completed', 'error', 'cancelled'].includes(job.status)) {
                            this.polling = false;
                            this.status = job.status;
                            setTimeout(() => location.reload(), 1500);
                            return;
                        }
                    }
                } catch (e) { /* network error, retry */ }
                await new Promise(r => setTimeout(r, 3000));
            }
        },

        async cancelCrawl() {
            if (!confirm('Vuoi annullare il crawl?')) return;
            const formData = new FormData();
            formData.append('_csrf_token', csrfToken);
            formData.append('job_id', this.jobId);
            await fetch(`/seo-toolkit/seo-audit/project/${projectId}/crawl/cancel-job`, {
                method: 'POST', body: formData
            });
        },

        updateUI() { /* update progress bar, stats, current URL display */ }
    };
}
```

**Step 2: On page load, check for active job**

When the user navigates back to the crawl page, check if there's a running job:

```javascript
// x-init handler
async init() {
    // Check for active job
    const resp = await fetch(`/seo-toolkit/seo-audit/project/${projectId}/crawl/status`);
    const data = await resp.json();
    if (data.active_job_id) {
        this.jobId = data.active_job_id;
        // Try SSE first, falls back to polling
        this.connectSSE();
    }
}
```

**Step 3: Verify in browser**

Test scenarios:
1. Start crawl → progress shows via SSE
2. Navigate away → come back → polling picks up where left off
3. Cancel button stops the crawl
4. Completed crawl redirects to dashboard

**Step 4: Commit**

```bash
git add modules/seo-audit/views/partials/crawl-control.php
git commit -m "feat: replace frontend polling with SSE + polling fallback for seo-audit crawl"
```

---

## Task 11: SEO Audit - WordPress Import Controller

**Files:**
- Modify: `modules/seo-audit/controllers/ApiController.php`

**Step 1: Add importWordPress() method**

New SSE endpoint that fetches all pages from WP plugin and analyzes them:

```php
public function importWordPress(int $projectId): void
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    ignore_user_abort(true);
    set_time_limit(0);
    session_write_close();

    // 1. Load project, verify auth, get connector
    // 2. Create CrawlSession (source=wordpress in config)
    // 3. Create CrawlJob (type=wordpress)

    $connector = new WordPressSeoConnector($connectorConfig);

    // 4. First fetch to get total count
    $firstPage = $connector->fetchSeoAudit(1, 50, $types);
    $totalPages = $firstPage['total'];
    $totalApiPages = $firstPage['total_pages'];

    // Save site_info for project-level checks
    $siteInfo = $firstPage['site_info'] ?? [];
    // Process robots.txt, sitemap presence, SSL

    $jobModel->start($jobId);
    $sendEvent('started', ['total' => $totalPages]);

    // 5. Loop through API pages
    for ($apiPage = 1; $apiPage <= $totalApiPages; $apiPage++) {
        $response = ($apiPage === 1) ? $firstPage : $connector->fetchSeoAudit($apiPage, 50, $types);
        Database::reconnect();

        foreach ($response['pages'] as $wpPage) {
            if ($jobModel->isCancelled($jobId)) {
                $sendEvent('cancelled', []);
                goto done;
            }

            // 6. Convert WP data to sa_pages format
            $pageData = $this->convertWpPageToAuditData($wpPage, $project['base_url']);

            // 7. Save to sa_pages
            $pageId = $this->savePage($projectId, $job['session_id'], $pageData);

            // 8. Run IssueDetector
            $issueCount = $issueDetector->analyzeAndSave($pageData, $pageId);

            Database::reconnect();
            $jobModel->incrementCompleted($jobId);
            $sendEvent('page_analyzed', [
                'url' => $wpPage['url'],
                'completed' => $completed++,
                'total' => $totalPages,
                'issues' => $issueCount,
            ]);
        }
    }

    // 9. Project-level checks
    $issueDetector->detectDuplicates($projectId, $job['session_id']);
    $issueDetector->detectOrphanPages($projectId, $job['session_id']);
    // Site-level: robots.txt, sitemap, SSL from site_info

    // 10. Complete
    $jobModel->complete($jobId);
    $sessionModel->complete($job['session_id']);
    // Update project stats

    $sendEvent('completed', $jobModel->getJobResponse($jobId));

    done:
    exit;
}
```

**Step 2: Add convertWpPageToAuditData() helper**

Maps WordPress plugin response to `sa_pages` column format:

```php
private function convertWpPageToAuditData(array $wpPage, string $baseUrl): array
{
    return [
        'url' => $wpPage['url'],
        'source' => 'wordpress',
        'cms_entity_id' => $wpPage['id'],
        'cms_entity_type' => $wpPage['type'],
        'status' => 'crawled',
        'status_code' => 200,
        'title' => $wpPage['title_tag'] ?? '',
        'title_length' => mb_strlen($wpPage['title_tag'] ?? ''),
        'meta_description' => $wpPage['meta_description'] ?? '',
        'meta_description_length' => mb_strlen($wpPage['meta_description'] ?? ''),
        'canonical_url' => $wpPage['canonical'] ?? '',
        'meta_robots' => $wpPage['robots_meta'] ?? '',
        'og_title' => $wpPage['og_title'] ?? '',
        'og_description' => $wpPage['og_description'] ?? '',
        'og_image' => $wpPage['og_image'] ?? '',
        'h1_count' => count(array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 1)),
        'h1_texts' => json_encode(
            array_map(fn($h) => $h['text'],
            array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 1))
        ),
        'h2_count' => count(array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 2)),
        'h3_count' => count(array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 3)),
        'h4_count' => count(array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 4)),
        'h5_count' => count(array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 5)),
        'h6_count' => count(array_filter($wpPage['headings'] ?? [], fn($h) => $h['level'] === 6)),
        'word_count' => $wpPage['word_count'] ?? 0,
        'images_count' => count($wpPage['images'] ?? []),
        'images_without_alt' => count(array_filter($wpPage['images'] ?? [], fn($i) => empty($i['alt']))),
        'images_data' => json_encode($wpPage['images'] ?? []),
        'internal_links_count' => count($wpPage['internal_links'] ?? []),
        'external_links_count' => count($wpPage['external_links'] ?? []),
        'nofollow_links_count' => count(array_filter(
            array_merge($wpPage['internal_links'] ?? [], $wpPage['external_links'] ?? []),
            fn($l) => ($l['nofollow'] ?? false)
        )),
        'links_data' => json_encode([
            'internal' => $wpPage['internal_links'] ?? [],
            'external' => $wpPage['external_links'] ?? [],
        ]),
        'has_schema' => !empty($wpPage['schema_json_ld']),
        'schema_types' => json_encode(
            array_map(fn($s) => $s['@type'] ?? 'Unknown', $wpPage['schema_json_ld'] ?? [])
        ),
        'is_indexable' => !str_contains(strtolower($wpPage['robots_meta'] ?? ''), 'noindex'),
        'indexability_reason' => str_contains(strtolower($wpPage['robots_meta'] ?? ''), 'noindex')
            ? 'noindex in meta robots' : null,
    ];
}
```

**Step 3: Add route**

In `modules/seo-audit/routes.php`:

```php
Router::get('/seo-audit/project/:id/import/wordpress/stream', function ($id) {
    $controller = new ApiController();
    $controller->importWordPress((int) $id);
});
```

**Step 4: Verify syntax**

Run: `php -l modules/seo-audit/controllers/ApiController.php`
Expected: No syntax errors

**Step 5: Commit**

```bash
git add modules/seo-audit/controllers/ApiController.php modules/seo-audit/routes.php
git commit -m "feat: add WordPress import with SSE for seo-audit"
```

---

## Task 12: SEO Audit - WordPress Import Tab (View)

**Files:**
- Modify: `modules/seo-audit/views/urls/import.php`

**Step 1: Add WordPress tab**

The import page currently has 2 tabs (Manuale, CSV). Add a third "WordPress" tab that is **only visible when a WP connector is active** for the project's Global Project.

The tab shows:
- Connector info (name, URL, last tested)
- Checkbox per tipo: Post, Pagine, Prodotti (WooCommerce)
- "Importa e Analizza" button
- Progress section (hidden until started): SSE-driven progress bar, current URL, stats

Controller must pass `$wpConnector` to the view (null if no connector). The tab visibility is controlled by `$wpConnector !== null`.

Alpine.js component `wpImport()`:
- `types: { post: true, page: true, product: false }` - selected types
- `importing: false` - state
- `progress: { completed: 0, total: 0, percent: 0, currentUrl: '' }` - progress
- `startImport()` - opens EventSource to `/import/wordpress/stream?types=post,page&job_id=X`
- SSE event handlers same pattern as crawl-control.php
- On complete: redirect to audit dashboard

**Step 2: Update ProjectController::import() to pass connector data**

In `modules/seo-audit/controllers/ProjectController.php`, the `import()` method needs to load the WP connector:

```php
// In import() method, before View::render()
$wpConnector = null;
if (!empty($project['global_project_id'])) {
    $connectorModel = new \Core\Models\ProjectConnector();
    $wpConnector = $connectorModel->getActiveByProject($project['global_project_id'], 'wordpress');
}

return View::render('seo-audit::urls/import', [
    // ... existing vars ...
    'wpConnector' => $wpConnector,
]);
```

**Step 3: Verify in browser**

Test: Navigate to `http://localhost/seo-toolkit/seo-audit/project/{id}/import`
Expected: WordPress tab visible (if connector exists), form works, SSE progress shows

**Step 4: Commit**

```bash
git add modules/seo-audit/views/urls/import.php modules/seo-audit/controllers/ProjectController.php
git commit -m "feat: add WordPress import tab to seo-audit URL import page"
```

---

## Task 13: IssueDetector - WordPress source awareness

**Files:**
- Modify: `modules/seo-audit/services/IssueDetector.php`

**Step 1: Add source awareness**

The IssueDetector currently works on page data arrays. For WordPress-sourced pages, some checks should be skipped (response time, redirect chains).

Add `source` field awareness:

```php
public function analyzePage(array $pageData): array
{
    $issues = [];
    $source = $pageData['source'] ?? 'crawl';

    $issues = array_merge($issues, $this->checkMetaTags($pageData));
    $issues = array_merge($issues, $this->checkHeadings($pageData));
    $issues = array_merge($issues, $this->checkImages($pageData));
    $issues = array_merge($issues, $this->checkLinks($pageData));
    $issues = array_merge($issues, $this->checkContent($pageData));

    // Skip HTTP-dependent checks for WordPress source
    if ($source === 'crawl') {
        $issues = array_merge($issues, $this->checkTechnical($pageData));
    } else {
        // WordPress: only canonical check (we have the data)
        $issues = array_merge($issues, $this->checkCanonical($pageData));
    }

    $issues = array_merge($issues, $this->checkSchema($pageData));

    return $issues;
}
```

Extract `checkCanonical()` from `checkTechnical()` so it can be called independently for WP source.

**Step 2: Add site-level WordPress checks**

New method `analyzeSiteInfo(array $siteInfo, int $projectId, int $sessionId)`:

```php
public function analyzeSiteInfo(array $siteInfo, int $projectId, int $sessionId): void
{
    // Check robots.txt
    if (empty($siteInfo['has_robots_txt'])) {
        // Create project-level issue: missing_robots_txt
    }

    // Check sitemap
    if (empty($siteInfo['has_sitemap'])) {
        // Create project-level issue: missing_sitemap
    }

    // Check SSL
    if (empty($siteInfo['has_ssl'])) {
        // Create project-level issue: not_https (critical)
    }
}
```

**Step 3: Verify syntax**

Run: `php -l modules/seo-audit/services/IssueDetector.php`
Expected: No syntax errors

**Step 4: Commit**

```bash
git add modules/seo-audit/services/IssueDetector.php
git commit -m "feat: add WordPress source awareness to IssueDetector"
```

---

## Task 14: Cron Dispatcher for Stuck Crawl Jobs

**Files:**
- Create: `modules/seo-audit/cron/crawl-dispatcher.php`

**Step 1: Create dispatcher**

Simple cron script that:
1. Finds `sa_crawl_jobs` with `status=running` and `started_at` > 30 minutes ago
2. Resets them to `error` with message "Job timeout - processo interrotto"
3. Updates corresponding `sa_crawl_sessions` to `failed`
4. Updates `sa_projects.status` back to `completed` (or whatever it was before)

```php
<?php
/**
 * Cron: Reset stuck crawl jobs (run every 5 minutes)
 * SiteGround: /usr/bin/php /home/.../modules/seo-audit/cron/crawl-dispatcher.php
 */
require_once __DIR__ . '/../../../core/bootstrap.php';

set_time_limit(0);

$jobModel = new \Modules\SeoAudit\Models\CrawlJob();
$reset = $jobModel->resetStuckJobs(30); // 30 minutes threshold

if ($reset > 0) {
    error_log("[seo-audit-crawl-dispatcher] Reset {$reset} stuck crawl jobs");
}
```

**Step 2: Verify syntax**

Run: `php -l modules/seo-audit/cron/crawl-dispatcher.php`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add modules/seo-audit/cron/crawl-dispatcher.php
git commit -m "feat: add cron dispatcher for stuck seo-audit crawl jobs"
```

---

## Task 15: Integration Testing & Documentation

**Files:**
- Modify: `shared/views/docs/seo-audit.php` (update docs)
- Modify: `docs/data-model.html` (add project_connectors + sa_crawl_jobs)

**Step 1: Manual integration test checklist**

Test the full flow:

1. **Connector setup:**
   - [ ] Go to `/projects/{id}` → CMS connector card visible
   - [ ] "Download Plugin" downloads ZIP
   - [ ] Configure connector (name, URL, API key) → test passes
   - [ ] Connector status shown as green
   - [ ] "Testa Connessione" button works (AJAX)
   - [ ] "Rimuovi" deletes connector

2. **WordPress import:**
   - [ ] Go to `/seo-audit/project/{id}/import`
   - [ ] WordPress tab visible when connector exists
   - [ ] Tab hidden when no connector
   - [ ] Select post types → "Importa e Analizza"
   - [ ] SSE progress shows: current URL, completed/total, progress bar
   - [ ] Navigate away → come back → polling shows progress
   - [ ] Completed → redirects to dashboard
   - [ ] Dashboard shows health score and issues

3. **Background crawl (scraping):**
   - [ ] Start crawl → SSE progress works
   - [ ] Navigate away → job continues in background
   - [ ] Come back → polling picks up progress
   - [ ] Cancel button stops job
   - [ ] Completed crawl shows results

4. **Edge cases:**
   - [ ] WP plugin not installed → test fails gracefully
   - [ ] WP plugin wrong API key → clear error message
   - [ ] Large site (500+ pages) → pagination works
   - [ ] Empty site (0 pages) → graceful message

**Step 2: Update user docs**

In `shared/views/docs/seo-audit.php`, add section about WordPress integration:
- Come configurare il connettore WordPress
- Come scaricare e installare il plugin WP
- Come eseguire un audit via WordPress (senza scraping)
- Vantaggi vs crawl tradizionale

**Step 3: Update data model**

In `docs/data-model.html`, add:
- `project_connectors` table in core/shared section
- `sa_crawl_jobs` table in seo-audit section
- New columns in `sa_pages` (source, cms_entity_id, cms_entity_type)

**Step 4: Commit**

```bash
git add shared/views/docs/seo-audit.php docs/data-model.html
git commit -m "docs: update seo-audit docs and data model for WordPress integration"
```

---

## Execution Order Summary

| Order | Task | Description | Dependencies |
|-------|------|-------------|--------------|
| 1 | Task 1 | DB: project_connectors table | None |
| 2 | Task 2 | DB: sa_pages columns + sa_crawl_jobs | None |
| 3 | Task 3 | Model: ProjectConnector | Task 1 |
| 4 | Task 4 | Model: CrawlJob | Task 2 |
| 5 | Task 5 | WP Plugin: /seo-audit endpoint | None (independent) |
| 6 | Task 6 | Service: WordPressSeoConnector | Task 5 |
| 7 | Task 7 | Controller: GlobalProject connector CRUD | Tasks 3, 6 |
| 8 | Task 8 | View: Dashboard CMS connector card | Task 7 |
| 9 | Task 9 | Controller: CrawlController refactor (SSE) | Task 4 |
| 10 | Task 10 | View: crawl-control.php refactor (SSE frontend) | Task 9 |
| 11 | Task 11 | Controller: WordPress import SSE | Tasks 4, 6, 13 |
| 12 | Task 12 | View: WordPress import tab | Tasks 11, 3 |
| 13 | Task 13 | Service: IssueDetector WP awareness | None (can be parallel) |
| 14 | Task 14 | Cron: crawl-dispatcher.php | Task 4 |
| 15 | Task 15 | Testing & docs | All above |

**Parallelizable groups:**
- Tasks 1-2 (DB migrations) in parallel
- Tasks 3-4 (Models) in parallel after DB
- Tasks 5, 13 (WP plugin, IssueDetector) independent, can be done anytime
- Tasks 9-10 (crawl refactor) together
- Tasks 11-12 (WP import) together
