# Crawl Budget Optimizer — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a standalone Crawl Budget Optimizer module that crawls sites up to 5,000 pages, traces redirect chains, identifies waste pages and indexability conflicts, calculates a budget score, and generates AI reports.

**Architecture:** New module at `modules/crawl-budget/` following SEO Audit patterns (SSE crawl, job queue, session state machine). Crawler fetches pages with manual redirect tracing (no CURLOPT_FOLLOWLOCATION), analyzes on-the-fly via BudgetAnalyzerService, and stores results in `cb_*` tables. AI report via AiService.

**Tech Stack:** PHP 8+, MySQL (cb_ prefix), Tailwind CSS, Alpine.js, HTMX, SSE for crawl streaming.

**Reference module:** `modules/seo-audit/` — copy patterns from there.

---

## Task 1: Database Schema

**Files:**
- Create: `modules/crawl-budget/database/schema.sql`

**Step 1: Create database directory**

```bash
mkdir -p modules/crawl-budget/database/migrations
```

**Step 2: Write schema.sql**

Create `modules/crawl-budget/database/schema.sql` with all 7 tables:

```sql
-- =============================================
-- CRAWL BUDGET OPTIMIZER - Database Schema
-- Prefisso tabelle: cb_
-- =============================================

CREATE TABLE IF NOT EXISTS cb_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    global_project_id INT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    status ENUM('pending','crawling','completed','failed') DEFAULT 'pending',
    last_crawl_at DATETIME NULL,
    crawl_budget_score INT NULL,
    settings JSON,
    current_session_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_crawl_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    status ENUM('pending','running','paused','stopping','stopped','completed','failed') DEFAULT 'pending',
    pages_found INT DEFAULT 0,
    pages_crawled INT DEFAULT 0,
    issues_found INT DEFAULT 0,
    current_url VARCHAR(2048) NULL,
    config JSON,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_crawl_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    items_total INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(2048) NULL,
    config JSON,
    error_message TEXT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_session (project_id, session_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    url VARCHAR(2048) NOT NULL,
    status ENUM('pending','crawling','crawled','error') DEFAULT 'pending',
    http_status SMALLINT NULL,
    content_type VARCHAR(128) NULL,
    response_time_ms INT NULL,
    content_length INT NULL,
    word_count INT NULL,
    title VARCHAR(512) NULL,
    meta_robots VARCHAR(255) NULL,
    canonical_url VARCHAR(2048) NULL,
    canonical_matches TINYINT(1) DEFAULT 0,
    is_indexable TINYINT(1) DEFAULT 1,
    indexability_reason VARCHAR(255) NULL,
    redirect_target VARCHAR(2048) NULL,
    redirect_chain JSON NULL,
    redirect_hops TINYINT DEFAULT 0,
    in_sitemap TINYINT(1) DEFAULT 0,
    in_robots_allowed TINYINT(1) DEFAULT 1,
    internal_links_in INT DEFAULT 0,
    internal_links_out INT DEFAULT 0,
    has_parameters TINYINT(1) DEFAULT 0,
    depth TINYINT DEFAULT 0,
    discovered_from VARCHAR(2048) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    INDEX idx_project_session_status (project_id, session_id, status),
    INDEX idx_session_status (session_id, status),
    INDEX idx_session_http_status (session_id, http_status),
    UNIQUE KEY uk_session_url (session_id, url(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    page_id INT NULL,
    category ENUM('redirect','waste','indexability') NOT NULL,
    type VARCHAR(100) NOT NULL,
    severity ENUM('critical','warning','notice') NOT NULL,
    title VARCHAR(255) NOT NULL,
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES cb_pages(id) ON DELETE CASCADE,
    INDEX idx_session_category_severity (session_id, category, severity),
    INDEX idx_page (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_site_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    robots_txt TEXT NULL,
    robots_rules JSON NULL,
    sitemaps JSON NULL,
    sitemap_urls JSON NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    ai_response LONGTEXT,
    summary TEXT NULL,
    priority_actions JSON NULL,
    estimated_impact JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    INDEX idx_project_session (project_id, session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 3: Run schema on local database**

```bash
mysql -u root seo_toolkit < modules/crawl-budget/database/schema.sql
```

Expected: Query OK, 0 rows affected for each table.

**Step 4: Verify tables created**

```bash
mysql -u root seo_toolkit -e "SHOW TABLES LIKE 'cb_%';"
```

Expected: 7 tables listed (cb_projects, cb_crawl_sessions, cb_crawl_jobs, cb_pages, cb_issues, cb_site_config, cb_reports).

**Step 5: Commit**

```bash
git add modules/crawl-budget/database/
git commit -m "feat(crawl-budget): add database schema for crawl budget optimizer module"
```

---

## Task 2: Module Configuration (module.json + routes.php skeleton)

**Files:**
- Create: `modules/crawl-budget/module.json`
- Create: `modules/crawl-budget/routes.php`

**Step 1: Write module.json**

```json
{
    "name": "Crawl Budget Optimizer",
    "slug": "crawl-budget",
    "version": "1.0.0",
    "description": "Analisi crawl budget: redirect chains, pagine spreco, conflitti indexability",
    "icon": "magnifying-glass-circle",
    "menu_order": 55,
    "requires": {
        "php": ">=8.0",
        "services": ["scraper", "ai"]
    },
    "credits": {
        "crawl_per_page": {
            "cost": 0,
            "description": "Scansione singola pagina (Gratis)"
        },
        "report_generate": {
            "cost": 5,
            "description": "Generazione Report AI con priorita e raccomandazioni"
        }
    },
    "settings_groups": {
        "general": {
            "label": "Configurazione Generale",
            "icon": "cog-6-tooth",
            "description": "Impostazioni generali del crawler",
            "order": 0
        },
        "crawl_config": {
            "label": "Configurazione Crawl",
            "icon": "globe-alt",
            "description": "Parametri del crawler",
            "order": 1
        },
        "ai_config": {
            "label": "Configurazione AI",
            "icon": "sparkles",
            "description": "Provider e modello AI per i report",
            "order": 2
        },
        "costs": {
            "label": "Costi Operazioni",
            "icon": "currency-euro",
            "description": "Crediti consumati per ogni operazione",
            "order": 99,
            "collapsed": true
        }
    },
    "settings": {
        "max_pages_per_crawl": {
            "type": "number",
            "label": "Max pagine per crawl",
            "description": "Numero massimo di pagine da analizzare per sessione",
            "default": 5000,
            "min": 10,
            "max": 10000,
            "admin_only": true,
            "group": "crawl_config"
        },
        "crawl_delay_ms": {
            "type": "number",
            "label": "Ritardo tra richieste (ms)",
            "description": "Millisecondi di attesa tra una richiesta e l'altra",
            "default": 500,
            "min": 100,
            "max": 5000,
            "admin_only": true,
            "group": "crawl_config"
        },
        "respect_robots": {
            "type": "select",
            "label": "Rispetta robots.txt",
            "default": "1",
            "admin_only": true,
            "group": "crawl_config",
            "options": [
                {"value": "1", "label": "Si"},
                {"value": "0", "label": "No"}
            ]
        },
        "ai_provider": {
            "type": "select",
            "label": "Provider AI",
            "description": "Provider AI per i report di questo modulo",
            "default": "global",
            "admin_only": true,
            "group": "ai_config",
            "options": [
                {"value": "global", "label": "Usa impostazione globale"},
                {"value": "anthropic", "label": "Anthropic (Claude)"},
                {"value": "openai", "label": "OpenAI (GPT)"}
            ]
        },
        "ai_model": {
            "type": "select",
            "label": "Modello AI",
            "default": "global",
            "admin_only": true,
            "group": "ai_config",
            "options": [
                {"value": "global", "label": "Usa impostazione globale"}
            ]
        },
        "ai_fallback_enabled": {
            "type": "select",
            "label": "Fallback AI",
            "default": "global",
            "admin_only": true,
            "group": "ai_config",
            "options": [
                {"value": "global", "label": "Usa impostazione globale"},
                {"value": "1", "label": "Abilitato"},
                {"value": "0", "label": "Disabilitato"}
            ]
        },
        "cost_report_generate": {
            "type": "number",
            "label": "Costo Report AI",
            "default": 5,
            "min": 0,
            "step": 0.1,
            "admin_only": true,
            "group": "costs"
        }
    },
    "routes_prefix": "/crawl-budget"
}
```

**Step 2: Write routes.php skeleton**

All routes with controller stubs. Full route file — controllers will be implemented in later tasks.

```php
<?php
/**
 * Crawl Budget Optimizer Module - Routes
 */

use Core\Router;
use Core\Middleware;
use Core\ModuleLoader;
use Modules\CrawlBudget\Controllers\ProjectController;
use Modules\CrawlBudget\Controllers\CrawlController;
use Modules\CrawlBudget\Controllers\ResultsController;
use Modules\CrawlBudget\Controllers\ReportController;

$moduleSlug = 'crawl-budget';

if (!ModuleLoader::isModuleActive($moduleSlug)) {
    return;
}

// ============================================
// PROJECTS
// ============================================

Router::get('/crawl-budget', function () {
    return (new ProjectController())->index();
});

Router::get('/crawl-budget/create', function () {
    \Core\Router::redirect('/projects/create');
});

Router::get('/crawl-budget/projects/{id}', function ($id) {
    return (new ProjectController())->dashboard((int) $id);
});

Router::get('/crawl-budget/projects/{id}/settings', function ($id) {
    return (new ProjectController())->settings((int) $id);
});

Router::post('/crawl-budget/projects/{id}/settings', function ($id) {
    Middleware::csrf();
    return (new ProjectController())->updateSettings((int) $id);
});

// ============================================
// CRAWL
// ============================================

Router::post('/crawl-budget/projects/{id}/crawl/start', function ($id) {
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->start((int) $id);
});

Router::get('/crawl-budget/projects/{id}/crawl/stream', function ($id) {
    $controller = new CrawlController();
    $controller->processStream((int) $id);
});

Router::post('/crawl-budget/projects/{id}/crawl/cancel', function ($id) {
    Middleware::csrf();
    $controller = new CrawlController();
    return $controller->cancel((int) $id);
});

Router::get('/crawl-budget/projects/{id}/crawl/job-status', function ($id) {
    $controller = new CrawlController();
    return $controller->jobStatus((int) $id);
});

// ============================================
// RESULTS
// ============================================

Router::get('/crawl-budget/projects/{id}/results', function ($id) {
    return (new ResultsController())->overview((int) $id);
});

Router::get('/crawl-budget/projects/{id}/results/redirects', function ($id) {
    return (new ResultsController())->redirects((int) $id);
});

Router::get('/crawl-budget/projects/{id}/results/waste', function ($id) {
    return (new ResultsController())->waste((int) $id);
});

Router::get('/crawl-budget/projects/{id}/results/indexability', function ($id) {
    return (new ResultsController())->indexability((int) $id);
});

Router::get('/crawl-budget/projects/{id}/results/pages', function ($id) {
    return (new ResultsController())->pages((int) $id);
});

// ============================================
// REPORT AI
// ============================================

Router::post('/crawl-budget/projects/{id}/report/generate', function ($id) {
    Middleware::csrf();
    return (new ReportController())->generate((int) $id);
});

Router::get('/crawl-budget/projects/{id}/report', function ($id) {
    return (new ReportController())->view((int) $id);
});
```

**Step 3: Verify PHP syntax**

```bash
php -l modules/crawl-budget/routes.php
```

Expected: No syntax errors detected.

**Step 4: Commit**

```bash
git add modules/crawl-budget/module.json modules/crawl-budget/routes.php
git commit -m "feat(crawl-budget): add module.json config and routes skeleton"
```

---

## Task 3: Models — Project, CrawlSession, CrawlJob

**Files:**
- Create: `modules/crawl-budget/models/Project.php`
- Create: `modules/crawl-budget/models/CrawlSession.php`
- Create: `modules/crawl-budget/models/CrawlJob.php`

**Reference:** Copy patterns from `modules/seo-audit/models/Project.php`, `CrawlSession.php`, `CrawlJob.php`. Adapt table names to `cb_` prefix and module slug to `crawl-budget`.

**Step 1: Write Project.php**

Follow exact pattern from `modules/seo-audit/models/Project.php`:
- Namespace: `Modules\CrawlBudget\Models`
- Table: `cb_projects`
- Methods: `find()`, `findAccessible()` (with ProjectAccessService), `allByUser()`, `create()`, `update()`, `delete()`, `getProjectKpi()`
- `findAccessible()` must check `ProjectAccessService::canAccessModule()` with slug `'crawl-budget'`
- `getProjectKpi()` returns: score, pages_crawled, issues_found, last_crawl_at

**Step 2: Write CrawlSession.php**

Follow exact pattern from `modules/seo-audit/models/CrawlSession.php`:
- Namespace: `Modules\CrawlBudget\Models`
- Table: `cb_crawl_sessions`
- Constants: STATUS_PENDING through STATUS_FAILED (7 states)
- Methods: `find()`, `findActiveByProject()`, `findLatestByProject()`, `create()`, `update()`, `start()`, `complete()`, `fail()`, `requestStop()`, `stop()`, `incrementPagesCrawled()`, `incrementIssuesFound()`

**Step 3: Write CrawlJob.php**

Follow exact pattern from `modules/seo-audit/models/CrawlJob.php`:
- Namespace: `Modules\CrawlBudget\Models`
- Table: `cb_crawl_jobs`
- Constants: STATUS_PENDING through STATUS_CANCELLED
- Methods: `find()`, `findActiveByProject()`, `create()`, `start()`, `complete()`, `error()`, `cancel()`, `isCancelled()`, `incrementCompleted()`, `incrementFailed()`, `getJobResponse()`, `resetStuckJobs()`, `cleanOldJobs()`

**Step 4: Verify PHP syntax for all 3 files**

```bash
php -l modules/crawl-budget/models/Project.php
php -l modules/crawl-budget/models/CrawlSession.php
php -l modules/crawl-budget/models/CrawlJob.php
```

**Step 5: Commit**

```bash
git add modules/crawl-budget/models/
git commit -m "feat(crawl-budget): add Project, CrawlSession, CrawlJob models"
```

---

## Task 4: Models — Page, Issue, SiteConfig, Report

**Files:**
- Create: `modules/crawl-budget/models/Page.php`
- Create: `modules/crawl-budget/models/Issue.php`
- Create: `modules/crawl-budget/models/SiteConfig.php`
- Create: `modules/crawl-budget/models/Report.php`

**Step 1: Write Page.php**

- Namespace: `Modules\CrawlBudget\Models`
- Table: `cb_pages`
- Methods:
  - `find(int $id)` — by ID
  - `findByUrl(int $sessionId, string $url)` — check duplicates
  - `findPending(int $sessionId)` — next page to crawl (`LIMIT 1`)
  - `upsert(int $projectId, int $sessionId, string $url, array $data)` — INSERT or UPDATE
  - `markCrawling(int $id)` — set status='crawling'
  - `markCrawled(int $id, array $data)` — set status='crawled' + all crawl data
  - `markError(int $id, string $error)` — set status='error'
  - `countBySession(int $sessionId, ?string $status = null)` — count pages
  - `getBySession(int $sessionId, array $filters, int $page, int $perPage)` — paginated list with filters
  - `updateInternalLinksIn(int $sessionId)` — bulk UPDATE to calculate internal_links_in from link graph
  - `getDuplicateTitles(int $sessionId)` — GROUP BY title HAVING COUNT > 1

**Step 2: Write Issue.php**

- Table: `cb_issues`
- Methods:
  - `create(array $data)` — single insert
  - `createMany(array $issues)` — batch insert
  - `getBySession(int $sessionId, ?string $category, ?string $severity, int $page, int $perPage)` — paginated with filters
  - `countBySession(int $sessionId, ?string $category, ?string $severity)` — count
  - `getSummaryBySession(int $sessionId)` — GROUP BY category, severity → counts
  - `getTopIssues(int $sessionId, int $limit = 20)` — for AI report input
  - `deleteBySession(int $sessionId)` — cleanup

**Step 3: Write SiteConfig.php**

- Table: `cb_site_config`
- Methods:
  - `findByProject(int $projectId)` — get config
  - `upsert(int $projectId, array $data)` — INSERT ON DUPLICATE KEY UPDATE
  - `getSitemapUrls(int $projectId)` — return decoded JSON array
  - `getRobotsRules(int $projectId)` — return decoded JSON

**Step 4: Write Report.php**

- Table: `cb_reports`
- Methods:
  - `find(int $id)` — by ID
  - `findBySession(int $sessionId)` — latest report for session
  - `findLatestByProject(int $projectId)` — latest report
  - `create(array $data)` — insert
  - `update(int $id, array $data)` — update

**Step 5: Verify PHP syntax**

```bash
php -l modules/crawl-budget/models/Page.php
php -l modules/crawl-budget/models/Issue.php
php -l modules/crawl-budget/models/SiteConfig.php
php -l modules/crawl-budget/models/Report.php
```

**Step 6: Commit**

```bash
git add modules/crawl-budget/models/
git commit -m "feat(crawl-budget): add Page, Issue, SiteConfig, Report models"
```

---

## Task 5: BudgetCrawlerService — Core Crawler

This is the most complex service. It handles HTTP fetching with manual redirect tracing, HTML parsing, robots.txt parsing, and sitemap parsing.

**Files:**
- Create: `modules/crawl-budget/services/BudgetCrawlerService.php`

**Step 1: Write BudgetCrawlerService class structure**

```php
<?php
namespace Modules\CrawlBudget\Services;

use Core\Database;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Models\SiteConfig;
```

Key methods to implement:

**`init(int $projectId, string $domain, array $config)`** — Initialize crawler with project context and config.

**`fetchRobotsAndSitemap()`** — Fetch and parse robots.txt for the domain. Extract Sitemap directives. Parse robots rules per user-agent (Googlebot, Bingbot, *). Store in cb_site_config via SiteConfig model. Then fetch and parse all sitemaps (including nested sitemap indexes). Store all sitemap URLs in cb_site_config.sitemap_urls.

**`seedUrls(int $sessionId)`** — Insert homepage + all sitemap URLs into cb_pages as pending. Mark `in_sitemap=1` for URLs from sitemap.

**`crawlPage(string $url)`** — Core method. Returns array with all page data. Implementation:

1. Check robots.txt rules — if blocked, return `['in_robots_allowed' => false, ...]` but still register page
2. Fetch URL with curl (NO `CURLOPT_FOLLOWLOCATION`):
   - Set timeout 5s per request
   - Capture: http_code, content_type, total_time, size_download
   - If 3xx status: read Location header, add to redirect chain, follow manually (max 10 hops)
   - Loop detection: if URL appears twice in chain, mark as loop
3. For final response (2xx or 4xx/5xx terminal):
   - If content_type is text/html: parse HTML body
   - Extract: title (regex `<title>(.*?)</title>`), meta robots (regex), canonical (regex + Link header), X-Robots-Tag header
   - Extract internal links: regex `<a[^>]+href=["']([^"']+)["']` then filter same-domain
   - Calculate: word_count via `str_word_count(strip_tags($html))`
   - Determine is_indexable: check noindex in meta_robots, X-Robots-Tag, robots.txt block
   - Detect has_parameters: `parse_url($url, PHP_URL_QUERY) !== null`

Return array:
```php
[
    'url' => $url,
    'http_status' => $finalStatus,
    'content_type' => $contentType,
    'response_time_ms' => $totalTimeMs,
    'content_length' => $contentLength,
    'word_count' => $wordCount,
    'title' => $title,
    'meta_robots' => $metaRobots,
    'canonical_url' => $canonicalUrl,
    'canonical_matches' => ($canonicalUrl === $url || $canonicalUrl === null),
    'is_indexable' => $isIndexable,
    'indexability_reason' => $reason,
    'redirect_target' => $redirectTarget,
    'redirect_chain' => $chain,  // JSON: ["url1|301", "url2|302", "url3|200"]
    'redirect_hops' => count($chain) - 1,
    'in_robots_allowed' => $robotsAllowed,
    'has_parameters' => $hasParams,
    'internal_links' => $internalLinks,  // array of discovered URLs
    'internal_links_out' => count($internalLinks),
]
```

**`isUrlAllowed(string $url)`** — Check URL against parsed robots.txt rules. Check Googlebot rules first, then `*` rules. Return bool.

**`parseRobotsTxt(string $content)`** — Parse robots.txt into structured rules array: `['googlebot' => ['allow' => [...], 'disallow' => [...]], '*' => [...]]`. Handle `Crawl-delay` directive.

**`parseSitemap(string $url)`** — Fetch sitemap XML, detect if sitemap index (contains `<sitemapindex>`) or regular sitemap. For index: recursively fetch child sitemaps. Return flat array of all `<loc>` URLs. Max 10,000 URLs, max 3 levels deep.

**`extractInternalLinks(string $html, string $baseDomain)`** — Extract href from `<a>` tags, filter to same domain, normalize URLs (resolve relative paths, remove fragments). Return unique array.

**`normalizeUrl(string $url, string $baseUrl)`** — Resolve relative URLs, remove fragments, normalize scheme.

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/services/BudgetCrawlerService.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/services/BudgetCrawlerService.php
git commit -m "feat(crawl-budget): add BudgetCrawlerService with redirect chain tracing"
```

---

## Task 6: BudgetAnalyzerService — Issue Detection

**Files:**
- Create: `modules/crawl-budget/services/BudgetAnalyzerService.php`

**Step 1: Write BudgetAnalyzerService**

Methods:

**`analyzePage(array $pageData, int $projectId, int $sessionId)`** — Analyze a single page and return array of issues. Runs all 3 category checks. Each issue is an array:
```php
['page_id' => $id, 'category' => 'redirect', 'type' => 'redirect_chain', 'severity' => 'critical', 'title' => '...', 'details' => [...]]
```

**`checkRedirects(array $pageData)`** — Check redirect issues:
- `redirect_chain`: redirect_hops >= 2 → critical, title "Catena di redirect ({hops} hop)", details includes full chain
- `redirect_loop`: URL appears 2+ times in chain → critical
- `redirect_to_4xx`: final status 4xx → critical
- `redirect_to_5xx`: final status 5xx → critical
- `redirect_temporary`: 302/307 used → warning, "Redirect temporaneo (302) — considerare 301"
- `redirect_single`: hops == 1 → notice

**`checkWaste(array $pageData)`** — Check waste issues:
- `empty_page`: word_count == 0 AND http_status == 200 → critical
- `thin_content`: word_count < 100 AND http_status == 200 AND content_type contains 'html' → warning
- `soft_404`: http_status == 200 AND (title contains '404'/'not found'/'page not found' OR word_count < 50) → critical
- `parameter_url_crawled`: has_parameters == true AND (canonical_url is null OR canonical still has params) → warning
- `orphan_page`: internal_links_in == 0 → notice (NOTE: this runs in post-analysis, not per-page)
- `deep_page`: depth > 4 → notice

**`checkIndexability(array $pageData)`** — Check indexability issues:
- `noindex_in_sitemap`: is_indexable == false AND in_sitemap == true → critical
- `blocked_but_linked`: in_robots_allowed == false AND internal_links_in > 0 → warning
- `canonical_mismatch`: canonical_url != null AND canonical_url != url AND canonical_matches == false → warning
- `mixed_signals`: is_indexable == false (noindex) AND canonical_url != null AND canonical_url != url → critical
- `blocked_in_robots`: in_robots_allowed == false → notice

**`runPostAnalysis(int $projectId, int $sessionId)`** — Post-crawl analysis that needs all pages. Runs after crawl completes:
- Calculate `internal_links_in` for all pages (count links pointing to each URL)
- Detect `orphan_page`: pages with internal_links_in == 0 (exclude homepage)
- Detect `duplicate_title`: pages sharing same title
- Detect `canonical_chain`: page A canonical → page B, page B has different canonical → page C
- Detect `noindex_receives_links`: is_indexable == false AND internal_links_in >= 3

**`calculateScore(int $sessionId)`** — Calculate crawl budget score 0-100:
```php
$score = 100;
$score -= min(40, $criticalCount * 3);
$score -= min(30, $warningCount * 1.5);
$score -= min(10, $noticeCount * 0.5);
// waste_percentage = (non-200 pages + thin + parameter pages) / total * 100
$score -= min(20, $wastePercentage * 0.4);
return max(0, (int) round($score));
```

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/services/BudgetAnalyzerService.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/services/BudgetAnalyzerService.php
git commit -m "feat(crawl-budget): add BudgetAnalyzerService with 3-category issue detection"
```

---

## Task 7: BudgetReportService — AI Report Generation

**Files:**
- Create: `modules/crawl-budget/services/BudgetReportService.php`

**Step 1: Write BudgetReportService**

Methods:

**`generate(int $projectId, int $sessionId, int $userId)`** — Main method. Gathers metrics, builds prompt, calls AiService, parses response, stores in cb_reports.

Flow:
1. Gather data:
   - Total pages crawled, status code distribution (GROUP BY http_status)
   - Issue summary (GROUP BY category, severity → counts)
   - Top 20 issues sorted by severity (critical first)
   - Top 5 longest redirect chains
   - Waste distribution by type
   - Crawl budget score
2. Build prompt (Italian):
   ```
   Sei un esperto SEO specializzato in crawl budget optimization.
   Analizza i seguenti dati di crawl e genera un report strutturato in italiano.

   DOMINIO: {domain}
   SCORE CRAWL BUDGET: {score}/100
   PAGINE ANALIZZATE: {total}

   DISTRIBUZIONE STATUS CODE:
   {status_distribution}

   RIEPILOGO PROBLEMI:
   {issue_summary}

   TOP 20 PROBLEMI PIU GRAVI:
   {top_issues}

   CATENE DI REDIRECT PIU LUNGHE:
   {top_chains}

   Genera un report con queste sezioni:
   1. EXECUTIVE SUMMARY (3-4 righe)
   2. IMPATTO STIMATO (% crawl budget sprecato, pagine problematiche)
   3. TOP 5 AZIONI PRIORITARIE (ordinate per impatto, con URL specifici)
   4. ANALISI PER CATEGORIA (Redirect / Pagine Spreco / Indexability)
   5. QUICK WINS (fix facili con alto impatto)

   Rispondi SOLO con il report in formato HTML (usa tag h2, h3, p, ul, li, strong).
   ```
3. Call AiService:
   ```php
   $ai = new \Services\AiService('crawl-budget');
   $result = $ai->analyze($userId, $prompt, $dataContext, 'crawl-budget');
   Database::reconnect();
   ```
4. Parse response: extract summary (first paragraph), try to extract priority_actions as JSON
5. Save to cb_reports
6. Consume credits: `Credits::consume($userId, $cost, 'report_generate', 'crawl-budget')`

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/services/BudgetReportService.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/services/BudgetReportService.php
git commit -m "feat(crawl-budget): add BudgetReportService for AI report generation"
```

---

## Task 8: ProjectController

**Files:**
- Create: `modules/crawl-budget/controllers/ProjectController.php`

**Step 1: Write ProjectController**

Methods:

**`index()`** — List projects. Pattern from `modules/seo-audit/controllers/ProjectController.php`.
- `Middleware::auth()`, get user, `$this->projectModel->allByUser($user['id'])`
- Return `View::render('crawl-budget::projects/index', ['user' => $user, 'projects' => $projects, 'modules' => ModuleLoader::getActiveModules()])`

**`dashboard(int $id)`** — Project dashboard with score + results summary.
- Get project via `findAccessible($id, $user['id'])`
- Get latest session via `CrawlSession::findLatestByProject($id)`
- If session completed: get issue summary, score, page counts
- Get latest report if exists
- Return `View::render('crawl-budget::dashboard', [...])`

**`settings(int $id)`** — Project settings form.
- GET: render settings form with current config
- Return `View::render('crawl-budget::projects/settings', [...])`

**`updateSettings(int $id)`** — Save settings.
- POST: validate, update cb_projects.settings JSON
- Redirect back

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/controllers/ProjectController.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/controllers/ProjectController.php
git commit -m "feat(crawl-budget): add ProjectController with CRUD and dashboard"
```

---

## Task 9: CrawlController — Start + SSE Stream

The most critical controller. Handles crawl lifecycle.

**Files:**
- Create: `modules/crawl-budget/controllers/CrawlController.php`

**Step 1: Write CrawlController**

Follow exact pattern from `modules/seo-audit/controllers/CrawlController.php`.

**`start(int $id)`** — POST, starts a crawl. Flow:
1. `Middleware::auth()`, verify project access via `findAccessible()`
2. Check no active session (auto-clean orphans >30min)
3. Read config from POST + project settings (max_pages, crawl_delay_ms, respect_robots)
4. Clean old data: DELETE from cb_pages, cb_issues WHERE project_id
5. Create cb_crawl_session (pending)
6. Call `BudgetCrawlerService::fetchRobotsAndSitemap()` — discovery phase
7. Call `BudgetCrawlerService::seedUrls($sessionId)` — insert seed URLs
8. Update session pages_found count
9. Create cb_crawl_job (pending)
10. Update project status='crawling', current_session_id
11. `jsonResponse(['success' => true, 'session_id' => $sessionId, 'job_id' => $jobId, 'pages_found' => $count])`

**`processStream(int $id)`** — GET SSE, processes crawl. Flow:
1. SSE headers: Content-Type text/event-stream, Cache-Control no-cache, X-Accel-Buffering no
2. `ignore_user_abort(true)`, `set_time_limit(0)`, `session_write_close()`
3. Get job_id from query, verify job exists
4. Start job: `$jobModel->start($jobId)`
5. Init crawler service with project config
6. Send 'started' event
7. Main loop:
   ```
   while (true):
     Database::reconnect()
     Check cancellation via $jobModel->isCancelled($jobId)
     Fetch next pending page from cb_pages
     If none → run postAnalysis → calculate score → send 'completed' → break
     Mark page as 'crawling'
     $pageData = $crawlerService->crawlPage($url)
     Save page data to cb_pages
     Discover new URLs from internal_links → INSERT into cb_pages if new (check max_pages)
     $issues = $analyzerService->analyzePage($pageData)
     Save issues to cb_issues
     Update session progress
     Send 'item_completed' SSE event
     Rate limit: usleep($crawlDelayMs * 1000)
   ```
8. On completion: `NotificationService::send()` with operation_completed
9. On cancellation: `NotificationService::send()` with operation_failed

**`cancel(int $id)`** — POST, cancels crawl.
- Find active job, set status='cancelled'
- Return JSON success

**`jobStatus(int $id)`** — GET, polling fallback.
- Find latest job for project
- Return `$jobModel->getJobResponse($jobId)`

**Helper `sendEvent(string $event, array $data)`:**
```php
private function sendEvent(string $event, array $data): void
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}
```

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/controllers/CrawlController.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/controllers/CrawlController.php
git commit -m "feat(crawl-budget): add CrawlController with SSE stream and crawl lifecycle"
```

---

## Task 10: ResultsController

**Files:**
- Create: `modules/crawl-budget/controllers/ResultsController.php`

**Step 1: Write ResultsController**

All methods follow same pattern: auth, findAccessible, get latest session, query data, render view.

**`overview(int $id)`** — Results overview dashboard.
- Get: score, total pages, status distribution, issue summary by category/severity
- Get: top 5 redirect chains, waste percentage, indexability conflict count
- Render `crawl-budget::results/overview`

**`redirects(int $id)`** — Redirect issues tab.
- Query cb_issues WHERE category='redirect', with pagination + sort
- Also query cb_pages with redirect_hops > 0 for chain visualization
- Render `crawl-budget::results/redirects`

**`waste(int $id)`** — Waste pages tab.
- Query cb_issues WHERE category='waste', with pagination + sort
- Render `crawl-budget::results/waste`

**`indexability(int $id)`** — Indexability conflicts tab.
- Query cb_issues WHERE category='indexability', with pagination + sort
- Render `crawl-budget::results/indexability`

**`pages(int $id)`** — All pages list.
- Query cb_pages with filters (status, http_status range, is_indexable, has_parameters, depth)
- Pagination via `Pagination::make()`
- Render `crawl-budget::results/pages`

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/controllers/ResultsController.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/controllers/ResultsController.php
git commit -m "feat(crawl-budget): add ResultsController with overview and issue tabs"
```

---

## Task 11: ReportController

**Files:**
- Create: `modules/crawl-budget/controllers/ReportController.php`

**Step 1: Write ReportController**

**`generate(int $id)`** — POST, generate AI report.
- Auth, findAccessible, get latest completed session
- Check credits: `Credits::hasEnough($userId, $cost)`
- Call `BudgetReportService::generate($projectId, $sessionId, $userId)`
- `Database::reconnect()`
- Return JSON with report ID

Pattern: AJAX lungo (non SSE — report generation is a single AI call, 30-60s).
- `ignore_user_abort(true)`, `set_time_limit(300)`, `ob_start()`, `session_write_close()`
- After AI call: `ob_end_clean()`, `echo json_encode(...)`, `exit`

**`view(int $id)`** — GET, view report.
- Auth, findAccessible, get latest report
- Render `crawl-budget::report/view`

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/controllers/ReportController.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/controllers/ReportController.php
git commit -m "feat(crawl-budget): add ReportController for AI report generation"
```

---

## Task 12: Global Project Integration

**Files:**
- Modify: `core/Models/GlobalProject.php` — add crawl-budget to MODULE_CONFIG
- Modify: `shared/views/components/nav-items.php` — add sidebar entry
- Modify: `public/index.php` — register module routes (if needed)

**Step 1: Add to MODULE_CONFIG in GlobalProject.php**

Add entry to `MODULE_CONFIG` array:
```php
'crawl-budget' => [
    'table' => 'cb_projects',
    'label' => 'Crawl Budget Optimizer',
    'color' => 'orange',
    'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
    'route_prefix' => '/crawl-budget/projects',
],
```

**Step 2: Add sidebar entry in nav-items.php**

Add to `$navItems` array in the correct menu_order position (55):
```php
[
    'label' => 'Crawl Budget',
    'slug' => 'crawl-budget',
    'icon' => 'magnifying-glass-circle',
    'url' => '/crawl-budget',
    'color' => 'orange',
],
```

**Step 3: Register module in database**

```sql
INSERT INTO modules (name, slug, description, is_active, menu_order, created_at)
VALUES ('Crawl Budget Optimizer', 'crawl-budget', 'Analisi crawl budget: redirect chains, pagine spreco, conflitti indexability', 1, 55, NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

**Step 4: Verify syntax**

```bash
php -l core/Models/GlobalProject.php
php -l shared/views/components/nav-items.php
```

**Step 5: Commit**

```bash
git add core/Models/GlobalProject.php shared/views/components/nav-items.php
git commit -m "feat(crawl-budget): integrate with global projects and sidebar navigation"
```

---

## Task 13: Views — Dashboard + Projects List

**Files:**
- Create: `modules/crawl-budget/views/projects/index.php`
- Create: `modules/crawl-budget/views/dashboard.php`
- Create: `modules/crawl-budget/views/projects/settings.php`

**Step 1: Write projects/index.php**

Project listing page. Reference: `modules/seo-audit/views/projects/index.php`.
- Table with columns: Nome, Dominio, Score, Pagine, Ultimo Crawl, Azioni
- Score displayed as colored badge (green 90+, blue 70+, yellow 50+, red <50)
- Empty state via `View::partial('components/table-empty-state', [...])`
- "Nuovo Progetto" button → `url('/projects/create')`
- Standard table CSS (rounded-xl, px-4 py-3, dark:bg-slate-700/50)

**Step 2: Write dashboard.php**

Main project dashboard. Sections:
1. **Hero area**: Score circle (SVG donut chart), domain, last crawl date, severity counters (critical/warning/notice pills)
2. **CTA button**: "Avvia Analisi" (if no crawl yet) or "Ri-analizza" — opens confirm modal
3. **KPI row**: 4 `dashboard-kpi-card` components — Pagine Analizzate, Redirect Chains, Pagine Spreco, Conflitti Indexability
4. **Results tabs** (if crawl completed): Links to /results, /results/redirects, /results/waste, /results/indexability, /results/pages
5. **AI Report card**: If report exists show link, else show "Genera Report AI" button with credits cost
6. **Crawl progress section** (if crawling): Alpine.js component with SSE listener showing progress bar, current URL, counters, cancel button
7. **Landing educativa** (if no crawl): 7-section pattern per CLAUDE.md specs. Color: orange. Reference: `modules/keyword-research/views/dashboard.php` line 150+.

Alpine.js component for crawl progress:
```javascript
function crawlProgress() {
    return {
        running: false,
        progress: 0,
        pagesCrawled: 0,
        pagesFound: 0,
        issuesFound: 0,
        currentUrl: '',
        eventSource: null,
        startCrawl() {
            // POST to /crawl/start, then open SSE
        },
        connectSSE(jobId) {
            this.eventSource = new EventSource(url + '/crawl/stream?job_id=' + jobId);
            this.eventSource.addEventListener('item_completed', (e) => { ... });
            this.eventSource.addEventListener('completed', (e) => { ... });
            this.eventSource.addEventListener('cancelled', (e) => { ... });
            // Polling fallback after 30s timeout
        },
        cancelCrawl() {
            // POST to /crawl/cancel
        }
    }
}
```

**Step 3: Write projects/settings.php**

Settings form with fields:
- Max pagine per crawl (number input, default from project settings)
- Ritardo tra richieste ms (number input)
- Rispetta robots.txt (checkbox)
- CSRF token field

**Step 4: Verify PHP syntax**

```bash
php -l modules/crawl-budget/views/projects/index.php
php -l modules/crawl-budget/views/dashboard.php
php -l modules/crawl-budget/views/projects/settings.php
```

**Step 5: Commit**

```bash
git add modules/crawl-budget/views/
git commit -m "feat(crawl-budget): add dashboard, project list, and settings views"
```

---

## Task 14: Views — Crawl Progress

**Files:**
- Create: `modules/crawl-budget/views/crawl/progress.php`

**Step 1: Write progress.php**

Dedicated crawl-in-progress view (alternative to inline dashboard component). Used when user navigates to project during active crawl.

- Full-page progress display with:
  - Animated progress bar (Tailwind + Alpine.js transition)
  - Current URL being crawled (truncated with ellipsis)
  - 3 counter cards: Pagine crawlate, Issues trovati, Redirect chains
  - Event log: scrollable list of last 20 events (newest on top)
  - Cancel button with confirm dialog
- SSE connection with polling fallback (same Alpine component as dashboard)
- On completion: auto-redirect to `/crawl-budget/projects/{id}/results`

**Step 2: Verify PHP syntax**

```bash
php -l modules/crawl-budget/views/crawl/progress.php
```

**Step 3: Commit**

```bash
git add modules/crawl-budget/views/crawl/progress.php
git commit -m "feat(crawl-budget): add crawl progress view with SSE events"
```

---

## Task 15: Views — Results Overview

**Files:**
- Create: `modules/crawl-budget/views/results/overview.php`

**Step 1: Write overview.php**

Results overview page. Sections:

1. **Score hero**: Large score number with color (green/blue/yellow/red), label (Eccellente/Buono/Migliorabile/Critico)
2. **Status code distribution**: Horizontal stacked bar chart (CSS-only, no JS library):
   - 2xx green, 3xx yellow, 4xx orange, 5xx red
   - Labels with counts and percentages
3. **Issue summary cards**: 3 cards (Redirect, Waste, Indexability), each showing:
   - Critical/Warning/Notice counts with colored badges
   - "Vedi dettagli →" link to respective tab
4. **Top 5 issues**: Table with severity, category, title, affected URL
5. **Crawl stats**: Pagine totali, Tempo totale, Velocita media, Profondita max

Tab navigation bar at top linking to: Overview (active) | Redirect | Waste | Indexability | Tutte le Pagine

**Step 2: Verify PHP syntax and commit**

```bash
php -l modules/crawl-budget/views/results/overview.php
git add modules/crawl-budget/views/results/overview.php
git commit -m "feat(crawl-budget): add results overview view with score and metrics"
```

---

## Task 16: Views — Results Tabs (Redirects, Waste, Indexability, Pages)

**Files:**
- Create: `modules/crawl-budget/views/results/redirects.php`
- Create: `modules/crawl-budget/views/results/waste.php`
- Create: `modules/crawl-budget/views/results/indexability.php`
- Create: `modules/crawl-budget/views/results/pages.php`

**Step 1: Write redirects.php**

Table with redirect issues:
- Columns: URL Origine, Chain (visual: url1 → 301 → url2 → 302 → url3), Destinazione, Status Finale, Hop, Severity
- Chain visualization: small pills with status code colors (301 blue, 302 yellow)
- Sort by: hops (desc), severity
- Pagination via table-pagination component
- Filter: severity dropdown (all/critical/warning/notice)

**Step 2: Write waste.php**

Table with waste issues:
- Columns: URL, Tipo Spreco, Parole, Status HTTP, Links In, Links Out, Severity
- Tipo spreco as colored badge: thin_content (yellow), empty_page (red), soft_404 (red), parameter_url (orange), orphan (gray), deep (gray)
- Sort by: severity, word_count
- Filter: issue type dropdown, severity

**Step 3: Write indexability.php**

Table with indexability issues:
- Columns: URL, Conflitto, Meta Robots, Canonical, In Sitemap?, Robots.txt, Severity
- Conflitto as descriptive text (e.g., "Pagina noindex presente nella sitemap")
- Boolean columns as check/x icons (green check, red x)
- Sort by: severity
- Filter: issue type, severity

**Step 4: Write pages.php**

Complete page list:
- Columns: URL, Status, Tipo, Parole, Redirect, Indexabile, Sitemap, Depth, Tempo (ms)
- Status as colored badge (200 green, 301/302 yellow, 404 orange, 500 red)
- Indexabile as green check / red x
- Filter: status code range (2xx/3xx/4xx/5xx), is_indexable, has_parameters, depth range
- Sort: all columns sortable via `table_sort_header()`
- Pagination

All 4 views share the same tab navigation bar at top.

**Step 5: Verify PHP syntax for all 4 files**

```bash
php -l modules/crawl-budget/views/results/redirects.php
php -l modules/crawl-budget/views/results/waste.php
php -l modules/crawl-budget/views/results/indexability.php
php -l modules/crawl-budget/views/results/pages.php
```

**Step 6: Commit**

```bash
git add modules/crawl-budget/views/results/
git commit -m "feat(crawl-budget): add results tab views (redirects, waste, indexability, pages)"
```

---

## Task 17: Views — AI Report

**Files:**
- Create: `modules/crawl-budget/views/report/view.php`

**Step 1: Write report/view.php**

Report display page:
- Header: "Report Crawl Budget" + domain + date generated
- Report content rendered as HTML (from ai_response, already HTML formatted)
- Styled in a card container with prose-like typography (max-w-4xl mx-auto)
- Sidebar: score badge, quick stats, link back to results
- "Rigenera Report" button (costs credits) with confirm dialog
- Print-friendly styles (`@media print`)

**Step 2: Verify and commit**

```bash
php -l modules/crawl-budget/views/report/view.php
git add modules/crawl-budget/views/report/
git commit -m "feat(crawl-budget): add AI report view"
```

---

## Task 18: Cron Dispatcher

**Files:**
- Create: `modules/crawl-budget/cron/crawl-dispatcher.php`

**Step 1: Write crawl-dispatcher.php**

Follow exact pattern from `modules/seo-audit/cron/crawl-dispatcher.php`:

```php
<?php
/**
 * Cron: Reset stuck crawl budget jobs + cleanup
 * Eseguire ogni 5 minuti.
 * SiteGround: /usr/bin/php /home/.../modules/crawl-budget/cron/crawl-dispatcher.php
 */
if (php_sapi_name() !== 'cli') die('Solo CLI');
date_default_timezone_set('Europe/Rome');
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';
set_time_limit(0);
```

3 operations:
1. Reset stuck jobs (running > 30 min) → mark as error, fail corresponding sessions
2. Reset orphaned sessions (running > 30 min without active job) → mark as failed, reset project status
3. Clean old jobs (keep last 20 per project)

Use `error_log("[crawl-budget-dispatcher] ...")` for logging.

**Step 2: Verify and commit**

```bash
php -l modules/crawl-budget/cron/crawl-dispatcher.php
git add modules/crawl-budget/cron/
git commit -m "feat(crawl-budget): add cron dispatcher for stuck job cleanup"
```

---

## Task 19: Integration Testing — Manual Browser Test

**No files to create. Manual verification steps.**

**Step 1: Register module in local DB**

```bash
mysql -u root seo_toolkit -e "INSERT INTO modules (name, slug, description, is_active, menu_order, created_at) VALUES ('Crawl Budget Optimizer', 'crawl-budget', 'Analisi crawl budget', 1, 55, NOW()) ON DUPLICATE KEY UPDATE is_active = 1;"
```

**Step 2: Verify module appears in sidebar**

Navigate to `http://localhost/seo-toolkit/` — verify "Crawl Budget" appears in sidebar with orange icon.

**Step 3: Test project creation flow**

1. Navigate to `/projects/create`
2. Create a new project with a test domain (e.g., `example.com`)
3. Activate "Crawl Budget Optimizer" module
4. Verify redirect to dashboard

**Step 4: Test crawl flow**

1. On dashboard, click "Avvia Analisi"
2. Verify SSE connection opens and progress updates
3. Let crawl complete (or test with small site)
4. Verify results appear in tabs

**Step 5: Test AI report**

1. After crawl complete, click "Genera Report AI"
2. Verify report generates and displays

**Step 6: Fix any issues found during testing**

---

## Task 20: Documentation Update

**Files:**
- Modify: `CLAUDE.md` — add crawl-budget to module table
- Modify: `docs/data-model.html` — add cb_ tables to ER diagram
- Create: `shared/views/docs/crawl-budget.php` — user documentation

**Step 1: Update CLAUDE.md**

Add to STATO MODULI table:
```
| Crawl Budget Optimizer | `crawl-budget` | `cb_` | Completo (Fase 1) |
```

Add to cron jobs:
```
modules/crawl-budget/cron/crawl-dispatcher.php  # Every 5 Min (*/5 * * * *)
```

**Step 2: Update data-model.html**

Add `cb_` tables to Mermaid erDiagram with relationships.

**Step 3: Write user docs**

Create `shared/views/docs/crawl-budget.php` with standard structure:
- Cos'e il Crawl Budget Optimizer
- Quick Start (3 steps)
- Funzionalita (3 categorie di analisi)
- Come leggere il report
- Costi crediti
- FAQ / Suggerimenti

Add route in `public/index.php` `$validPages` array and sidebar docs entry.

**Step 4: Commit**

```bash
git add CLAUDE.md docs/data-model.html shared/views/docs/crawl-budget.php
git commit -m "docs(crawl-budget): add module to CLAUDE.md, data model, and user docs"
```

---

## Summary

| Task | Component | Estimated Complexity |
|------|-----------|---------------------|
| 1 | Database schema | Low |
| 2 | module.json + routes | Low |
| 3 | Models (Project, Session, Job) | Medium |
| 4 | Models (Page, Issue, SiteConfig, Report) | Medium |
| 5 | BudgetCrawlerService | **High** — core crawler with redirect tracing |
| 6 | BudgetAnalyzerService | **High** — 20 issue types across 3 categories |
| 7 | BudgetReportService | Medium — AI prompt + parsing |
| 8 | ProjectController | Low-Medium |
| 9 | CrawlController | **High** — SSE stream, lifecycle management |
| 10 | ResultsController | Medium |
| 11 | ReportController | Low-Medium |
| 12 | Global Project integration | Low |
| 13 | Views — Dashboard + List | Medium |
| 14 | Views — Crawl Progress | Medium |
| 15 | Views — Results Overview | Medium |
| 16 | Views — Results Tabs (4 views) | Medium-High |
| 17 | Views — AI Report | Low |
| 18 | Cron dispatcher | Low |
| 19 | Integration testing | Medium |
| 20 | Documentation | Low |

**Critical path:** Tasks 1-2 → 3-4 → 5-6 → 8-9 → 12-13 → 19
**Parallelizable:** Tasks 3+4, Tasks 5+6+7, Tasks 13+14+15+16+17
