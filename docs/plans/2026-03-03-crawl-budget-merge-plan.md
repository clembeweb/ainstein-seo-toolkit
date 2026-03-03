# Crawl Budget Merge into SEO Audit — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Integrate crawl budget analysis into SEO Audit module with unified crawler, new "Crawl Budget" tab, and AI report in dual format (HTML standalone + integrated view).

**Architecture:** Extend SA crawler with manual redirect tracing from CB. Add nullable columns to `sa_pages`. Create separate `BudgetIssueDetector` service. Add new tab/routes/views. Build `UnifiedReportService` generating contextual AI reports in amevista style.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js, AiService (Claude API)

**Design doc:** `docs/plans/2026-03-03-crawl-budget-merge-design.md`

---

## Task 1: Database Migration — Add budget columns to sa_pages

**Files:**
- Create: `modules/seo-audit/migrations/003_add_budget_columns.sql`

**Context:**
- Current `sa_pages` columns: see `modules/seo-audit/models/Page.php`
- Current `sa_site_config` columns: see `modules/seo-audit/models/SiteConfig.php`
- All new columns are nullable — zero impact on existing data
- Follow CB column definitions from `modules/crawl-budget/models/Page.php`

**Step 1: Create migration file**

```sql
-- Migration: Add crawl budget columns to sa_pages and sa_site_config
-- Date: 2026-03-03
-- Safe: all columns nullable, no existing data affected

-- sa_pages: redirect tracking
ALTER TABLE sa_pages
  ADD COLUMN redirect_chain JSON DEFAULT NULL COMMENT 'Array hop: [{"url":"...","status":301}, ...]',
  ADD COLUMN redirect_hops TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Numero hop redirect (0=nessuno)',
  ADD COLUMN redirect_target VARCHAR(2048) DEFAULT NULL COMMENT 'URL finale dopo redirect chain',
  ADD COLUMN is_redirect_loop TINYINT(1) DEFAULT 0 COMMENT 'Loop rilevato nella chain';

-- sa_pages: crawl budget metadata
ALTER TABLE sa_pages
  ADD COLUMN depth TINYINT(3) UNSIGNED DEFAULT 0 COMMENT 'Profondità dal root (0=homepage)',
  ADD COLUMN discovered_from VARCHAR(2048) DEFAULT NULL COMMENT 'URL che ha scoperto questa pagina',
  ADD COLUMN has_parameters TINYINT(1) DEFAULT 0 COMMENT 'URL contiene query string',
  ADD COLUMN in_sitemap TINYINT(1) DEFAULT NULL COMMENT 'URL trovata nella sitemap XML',
  ADD COLUMN in_robots_allowed TINYINT(1) DEFAULT NULL COMMENT 'URL permessa da robots.txt',
  ADD COLUMN crawl_source ENUM('spider','sitemap','import') DEFAULT 'spider' COMMENT 'Come è stata scoperta';

-- sa_site_config: sitemap + robots rules
ALTER TABLE sa_site_config
  ADD COLUMN sitemap_urls LONGTEXT DEFAULT NULL COMMENT 'JSON array URL dalle sitemap',
  ADD COLUMN crawl_delay INT DEFAULT NULL COMMENT 'Crawl-Delay da robots.txt',
  ADD COLUMN robots_rules JSON DEFAULT NULL COMMENT 'Regole parsed per User-Agent';

-- sa_unified_reports: AI report storage
CREATE TABLE IF NOT EXISTS sa_unified_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  session_id INT DEFAULT NULL,
  report_type ENUM('unified','crawl_budget','on_page') DEFAULT 'unified',
  html_content LONGTEXT NOT NULL COMMENT 'Report HTML completo self-contained',
  summary TEXT DEFAULT NULL COMMENT 'Executive summary estratto',
  priority_actions JSON DEFAULT NULL COMMENT 'Top 5 azioni prioritarie',
  estimated_impact JSON DEFAULT NULL COMMENT 'Metriche impatto stimato',
  site_profile JSON DEFAULT NULL COMMENT 'Profilo sito rilevato (tipo, dimensione, settore)',
  health_score TINYINT(3) UNSIGNED DEFAULT NULL,
  budget_score TINYINT(3) UNSIGNED DEFAULT NULL,
  waste_percentage DECIMAL(5,2) DEFAULT NULL,
  credits_used INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_project_date (project_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sa_crawl_sessions: add budget_score
ALTER TABLE sa_crawl_sessions
  ADD COLUMN budget_score TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Crawl budget score 0-100',
  ADD COLUMN waste_percentage DECIMAL(5,2) DEFAULT NULL COMMENT 'Percentuale spreco crawl budget';

-- sa_projects: add budget_score
ALTER TABLE sa_projects
  ADD COLUMN budget_score TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Crawl budget score 0-100';
```

**Step 2: Run migration on local DB**

```bash
mysql -u root seo_toolkit < modules/seo-audit/migrations/003_add_budget_columns.sql
```

**Step 3: Verify columns exist**

```bash
mysql -u root seo_toolkit -e "DESCRIBE sa_pages;" | grep -E "redirect_|depth|discovered_|has_param|in_sitemap|in_robots|crawl_source"
mysql -u root seo_toolkit -e "DESCRIBE sa_site_config;" | grep -E "sitemap_urls|crawl_delay|robots_rules"
mysql -u root seo_toolkit -e "SHOW TABLES LIKE 'sa_unified_reports';"
```

**Step 4: Commit**

```bash
git add modules/seo-audit/migrations/003_add_budget_columns.sql
git commit -m "feat(seo-audit): add budget columns migration for crawl budget merge"
```

---

## Task 2: RobotsTxtParser service

**Files:**
- Create: `modules/seo-audit/services/RobotsTxtParser.php`
- Reference: `modules/crawl-budget/services/BudgetCrawlerService.php` lines 126-205 (`parseRobotsTxt()`, `isUrlAllowed()`)

**Context:**
- Port the robots.txt parsing logic from CB's `BudgetCrawlerService`
- Standalone service so it can be used independently
- Needs: `parseRobotsTxt($content)` and `isUrlAllowed($url, $rules)` methods
- Pattern matching: Googlebot rules priority, then `*` fallback
- Store parsed rules as JSON in `sa_site_config.robots_rules`

**Step 1: Create RobotsTxtParser**

```php
<?php
namespace Modules\SeoAudit\Services;

/**
 * Parser per robots.txt — estrae regole Allow/Disallow per User-Agent
 * Ported from crawl-budget module BudgetCrawlerService
 */
class RobotsTxtParser
{
    /**
     * Parse robots.txt content into structured rules
     * @return array ['agent' => ['allow' => [...], 'disallow' => [...]], '_crawl_delay' => int|null]
     */
    public function parse(string $content): array
    {
        // Port from BudgetCrawlerService::parseRobotsTxt() lines 126-164
        // Extract User-agent blocks, Allow/Disallow rules, Crawl-delay, Sitemap directives
    }

    /**
     * Check if URL is allowed by parsed robots rules
     */
    public function isAllowed(string $url, array $rules): bool
    {
        // Port from BudgetCrawlerService::isUrlAllowed() lines 169-205
        // Priority: Googlebot > * > default allow
        // Allow overrides Disallow when same-length match
    }

    /**
     * Extract Sitemap directives from robots.txt
     * @return array of sitemap URLs
     */
    public function extractSitemaps(string $content): array
    {
        // Extract "Sitemap: URL" lines
    }

    /**
     * Extract Crawl-delay value
     */
    public function extractCrawlDelay(string $content): ?int
    {
        // From parsed rules '_crawl_delay' key
    }
}
```

**Step 2: Verify syntax**

```bash
php -l modules/seo-audit/services/RobotsTxtParser.php
```

**Step 3: Commit**

```bash
git add modules/seo-audit/services/RobotsTxtParser.php
git commit -m "feat(seo-audit): add RobotsTxtParser service for crawl budget analysis"
```

---

## Task 3: SitemapParser service

**Files:**
- Create: `modules/seo-audit/services/SitemapParser.php`
- Reference: `modules/crawl-budget/services/BudgetCrawlerService.php` lines 231-270 (`parseSitemap()`)
- Also reference: `services/SitemapService.php` (shared platform service)

**Context:**
- The platform already has `SitemapService` in `services/SitemapService.php` for basic sitemap operations
- CB has its own recursive parser with depth limits (3 levels) and URL cap (10K)
- Create a lightweight wrapper that uses `SitemapService` where possible and adds CB-specific features
- Must store results in `sa_site_config.sitemap_urls` as JSON

**Step 1: Create SitemapParser**

```php
<?php
namespace Modules\SeoAudit\Services;

use Services\SitemapService;

/**
 * Extended sitemap parser for crawl budget analysis
 * Wraps platform SitemapService + adds recursive index parsing and URL dedup
 */
class SitemapParser
{
    private SitemapService $sitemapService;
    private const MAX_URLS = 10000;
    private const MAX_DEPTH = 3;

    public function __construct()
    {
        $this->sitemapService = new SitemapService();
    }

    /**
     * Discover and parse all sitemaps for a domain
     * @return array ['urls' => [...], 'sitemaps' => [...], 'total' => int]
     */
    public function discoverAndParse(string $baseUrl, ?string $robotsTxt = null): array
    {
        // 1. Extract sitemap URLs from robots.txt (if provided)
        // 2. Fallback to /sitemap.xml
        // 3. Recursive parsing (sitemap index → child sitemaps)
        // 4. Deduplicate, cap at MAX_URLS, filter same-domain
    }

    /**
     * Parse a single sitemap (regular or index)
     * Recursive for sitemap indexes, max MAX_DEPTH levels
     */
    public function parseSitemap(string $url, int $depth = 0): array
    {
        // Port from BudgetCrawlerService::parseSitemap() lines 231-270
    }
}
```

**Step 2: Verify syntax**

```bash
php -l modules/seo-audit/services/SitemapParser.php
```

**Step 3: Commit**

```bash
git add modules/seo-audit/services/SitemapParser.php
git commit -m "feat(seo-audit): add SitemapParser service for crawl budget sitemap analysis"
```

---

## Task 4: Extend CrawlerService with redirect chain tracing

**Files:**
- Modify: `modules/seo-audit/services/CrawlerService.php`
  - Add `traceRedirectChain()` private method (new, ~60 lines)
  - Modify `crawlPage()` (lines 430-463) to call redirect tracing before fetch
  - Add `computeBudgetMetadata()` private method (new, ~30 lines)
  - Add property: `private array $sitemapUrls = []`
  - Add property: `private array $robotsRules = []`
  - Add method: `setSitemapUrls(array $urls)` and `setRobotsRules(array $rules)`

**Context:**
- `crawlPage()` currently at lines 430-463: fetches via ScraperService, extracts SEO data, returns array
- `resolveRedirects()` at lines 345-358: already exists but uses auto-follow (not manual hop tracing)
- CB's manual tracing: `BudgetCrawlerService.php` lines 344-389 (the core redirect chain logic)
- The key difference: CB uses `CURLOPT_FOLLOWLOCATION => false` and manually follows each hop
- **Critical**: Don't break the existing `crawlPage()` flow — add the new data alongside existing data

**Step 1: Add new properties and setter methods**

After line 52 (last property), add:
```php
// Budget analysis data (set before crawl starts)
private array $sitemapUrls = [];
private array $robotsRules = [];
```

Add setter methods after `setConfig()`:
```php
public function setSitemapUrls(array $urls): self { $this->sitemapUrls = $urls; return $this; }
public function setRobotsRules(array $rules): self { $this->robotsRules = $rules; return $this; }
```

**Step 2: Add `traceRedirectChain()` method**

Add new private method (place after `resolveRedirects()` around line 360):
```php
/**
 * Trace redirect chain manually (hop-by-hop) without auto-follow
 * Ported from crawl-budget BudgetCrawlerService
 */
private function traceRedirectChain(string $url): array
{
    $chain = [];
    $visited = [];
    $maxHops = 10;
    $currentUrl = $url;
    $isLoop = false;

    for ($hop = 0; $hop < $maxHops; $hop++) {
        if (in_array($currentUrl, $visited)) {
            $isLoop = true;
            break;
        }
        $visited[] = $currentUrl;

        $ch = curl_init($currentUrl);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 0) {
            $chain[] = ['url' => $currentUrl, 'status' => 0];
            break;
        }

        if ($httpCode >= 300 && $httpCode < 400) {
            if (preg_match('/^Location:\s*(.+)$/mi', $response, $m)) {
                $nextUrl = $this->normalizeUrl(trim($m[1]), $currentUrl);
                if (!$nextUrl) break;
                $chain[] = ['url' => $currentUrl, 'status' => $httpCode];
                $currentUrl = $nextUrl;
                continue;
            }
        }

        $chain[] = ['url' => $currentUrl, 'status' => $httpCode];
        break;
    }

    return [
        'chain' => $chain,
        'hops' => max(0, count($chain) - 1),
        'target' => $currentUrl,
        'is_loop' => $isLoop,
    ];
}
```

**Step 3: Add `computeBudgetMetadata()` method**

```php
/**
 * Compute crawl budget metadata for a page
 */
private function computeBudgetMetadata(string $url, ?string $parentUrl = null, int $parentDepth = 0): array
{
    $parsedUrl = parse_url($url);
    $hasParameters = !empty($parsedUrl['query']);
    $inSitemap = in_array(rtrim($url, '/'), array_map(fn($u) => rtrim($u, '/'), $this->sitemapUrls));

    $robotsParser = new RobotsTxtParser();
    $inRobotsAllowed = empty($this->robotsRules) ? null : ($robotsParser->isAllowed($url, $this->robotsRules) ? 1 : 0);

    return [
        'depth' => $parentUrl ? $parentDepth + 1 : 0,
        'discovered_from' => $parentUrl,
        'has_parameters' => $hasParameters ? 1 : 0,
        'in_sitemap' => $inSitemap ? 1 : 0,
        'in_robots_allowed' => $inRobotsAllowed,
        'crawl_source' => $inSitemap ? 'sitemap' : 'spider',
    ];
}
```

**Step 4: Modify `crawlPage()` to include budget data**

In `crawlPage()` (lines 430-463), add redirect tracing BEFORE the existing fetch, and merge budget metadata into the return array.

The existing return array ends around line 462. Add the new fields to the returned array:
```php
// After existing extraction (line 451-462), merge budget data:
$redirectData = $this->traceRedirectChain($url);
$budgetMeta = $this->computeBudgetMetadata($url);

// Add to return array:
'redirect_chain' => json_encode($redirectData['chain']),
'redirect_hops' => $redirectData['hops'],
'redirect_target' => $redirectData['target'],
'is_redirect_loop' => $redirectData['is_loop'] ? 1 : 0,
'depth' => $budgetMeta['depth'],
'discovered_from' => $budgetMeta['discovered_from'],
'has_parameters' => $budgetMeta['has_parameters'],
'in_sitemap' => $budgetMeta['in_sitemap'],
'in_robots_allowed' => $budgetMeta['in_robots_allowed'],
'crawl_source' => $budgetMeta['crawl_source'],
```

**Step 5: Verify syntax**

```bash
php -l modules/seo-audit/services/CrawlerService.php
```

**Step 6: Commit**

```bash
git add modules/seo-audit/services/CrawlerService.php
git commit -m "feat(seo-audit): extend crawler with redirect chain tracing and budget metadata"
```

---

## Task 5: Update Page model with new columns

**Files:**
- Modify: `modules/seo-audit/models/Page.php`
  - Add new columns to `upsert()` method whitelist
  - Add `getWastePages()` method for budget score calculation
  - Add `getStatusDistribution()` method
  - Add `getTopRedirectChains()` method
  - Add `updateInternalLinksIn()` method (port from CB)
  - Add `getDuplicateTitles()` if not already present

**Context:**
- Current `upsert()` at line 129-147: inserts/updates page data
- The upsert likely has a whitelist of allowed columns — add new budget columns to it
- CB Page model methods to port: see `modules/crawl-budget/models/Page.php`

**Step 1: Add new columns to upsert whitelist and add budget query methods**

The new methods needed:
```php
public function getWastePages(int $projectId, ?int $sessionId = null): int
{
    // Count pages with: status != 2xx OR word_count < 100 OR has_parameters with self-canonical
    // Used for waste_percentage calculation
}

public function getStatusDistribution(int $projectId, ?int $sessionId = null): array
{
    // Returns: ['2xx' => count, '3xx' => count, '4xx' => count, '5xx' => count]
}

public function getTopRedirectChains(int $projectId, int $limit = 10, ?int $sessionId = null): array
{
    // Returns pages with redirect_hops > 0, ordered by hops DESC
}

public function updateInternalLinksIn(int $projectId, ?int $sessionId = null): void
{
    // Recalculate internal_links_in from discovered_from field
    // Port from CB: modules/crawl-budget/models/Page.php lines 222-238
}
```

**Step 2: Verify syntax**

```bash
php -l modules/seo-audit/models/Page.php
```

**Step 3: Commit**

```bash
git add modules/seo-audit/models/Page.php
git commit -m "feat(seo-audit): add budget query methods to Page model"
```

---

## Task 6: BudgetIssueDetector service

**Files:**
- Create: `modules/seo-audit/services/BudgetIssueDetector.php`
- Reference: `modules/crawl-budget/services/BudgetAnalyzerService.php` (all check methods)
- Reference: `modules/seo-audit/services/IssueDetector.php` (pattern for issue creation)

**Context:**
- Separate from existing `IssueDetector.php` to avoid touching on-page checks
- Uses same `Issue::create()` pattern for storage in `sa_issues`
- Called AFTER `IssueDetector::analyzeAndSave()` on the same page data
- Must define new issue types in `Issue::ISSUE_TYPES` constant (or use raw create)
- 3 categories × ~5 types each = ~15 new issue types
- Also has post-analysis methods run after full crawl

**Step 1: Create BudgetIssueDetector**

```php
<?php
namespace Modules\SeoAudit\Services;

use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\Page;
use Core\Database;

/**
 * Crawl Budget Issue Detector
 * Detects redirect, waste, and indexability issues from crawled page data.
 * Runs AFTER standard IssueDetector on the same pages.
 *
 * Ported from crawl-budget module BudgetAnalyzerService.
 */
class BudgetIssueDetector
{
    private Issue $issueModel;
    private Page $pageModel;
    private int $projectId;
    private ?int $sessionId = null;

    // Issue type definitions (category, severity, title, recommendation)
    // Follow same structure as Issue::ISSUE_TYPES
    public const BUDGET_ISSUE_TYPES = [
        // Redirect issues
        'redirect_loop'      => ['category' => 'redirect', 'severity' => 'critical', 'title' => 'Loop di redirect rilevato', 'recommendation' => '...'],
        'redirect_to_4xx'    => ['category' => 'redirect', 'severity' => 'critical', 'title' => 'Redirect termina con errore 4xx', 'recommendation' => '...'],
        'redirect_to_5xx'    => ['category' => 'redirect', 'severity' => 'critical', 'title' => 'Redirect termina con errore 5xx', 'recommendation' => '...'],
        'redirect_chain'     => ['category' => 'redirect', 'severity' => 'warning', 'title' => 'Catena di redirect (2+ hop)', 'recommendation' => '...'],
        'redirect_temporary' => ['category' => 'redirect', 'severity' => 'warning', 'title' => 'Redirect temporaneo (302/307)', 'recommendation' => '...'],
        // Waste issues
        'soft_404'               => ['category' => 'waste', 'severity' => 'critical', 'title' => 'Soft 404 rilevato', 'recommendation' => '...'],
        'empty_page'             => ['category' => 'waste', 'severity' => 'critical', 'title' => 'Pagina vuota', 'recommendation' => '...'],
        'thin_content'           => ['category' => 'waste', 'severity' => 'warning', 'title' => 'Contenuto sottile (<100 parole)', 'recommendation' => '...'],
        'parameter_url_crawled'  => ['category' => 'waste', 'severity' => 'warning', 'title' => 'URL con parametri senza canonical pulita', 'recommendation' => '...'],
        'deep_page'              => ['category' => 'waste', 'severity' => 'notice', 'title' => 'Pagina troppo profonda (>4 livelli)', 'recommendation' => '...'],
        // Indexability issues
        'noindex_in_sitemap'     => ['category' => 'indexability', 'severity' => 'critical', 'title' => 'Noindex presente nella sitemap', 'recommendation' => '...'],
        'mixed_signals'          => ['category' => 'indexability', 'severity' => 'critical', 'title' => 'Segnali contrastanti (noindex + canonical)', 'recommendation' => '...'],
        'blocked_but_linked'     => ['category' => 'indexability', 'severity' => 'warning', 'title' => 'Bloccata da robots.txt ma linkata', 'recommendation' => '...'],
        'canonical_mismatch'     => ['category' => 'indexability', 'severity' => 'warning', 'title' => 'Canonical non corrisponde all\'URL', 'recommendation' => '...'],
        // Post-analysis issues
        'orphan_page_budget'     => ['category' => 'waste', 'severity' => 'notice', 'title' => 'Pagina orfana (nessun link in ingresso)', 'recommendation' => '...'],
        'duplicate_title_budget' => ['category' => 'waste', 'severity' => 'warning', 'title' => 'Titolo duplicato (spreco crawl budget)', 'recommendation' => '...'],
        'canonical_chain'        => ['category' => 'indexability', 'severity' => 'critical', 'title' => 'Catena di canonical (A→B→C)', 'recommendation' => '...'],
        'noindex_receives_links' => ['category' => 'indexability', 'severity' => 'warning', 'title' => 'Pagina noindex riceve ≥3 link interni', 'recommendation' => '...'],
    ];

    public function __construct() { /* init models */ }
    public function init(int $projectId): self { /* set projectId */ }
    public function setSessionId(?int $sessionId): self { /* set sessionId */ }

    /** Analyze a single page for budget issues (called during crawl) */
    public function analyzePage(array $pageData): array
    {
        $issues = [];
        $issues = array_merge($issues, $this->checkRedirects($pageData));
        $issues = array_merge($issues, $this->checkWaste($pageData));
        $issues = array_merge($issues, $this->checkIndexability($pageData));
        return $issues;
    }

    /** Analyze + save (convenience wrapper) */
    public function analyzeAndSave(array $pageData, int $pageId): int { /* ... */ }

    /** Run post-analysis after all pages crawled */
    public function runPostAnalysis(): int
    {
        $count = 0;
        $this->pageModel->updateInternalLinksIn($this->projectId, $this->sessionId);
        $count += $this->detectOrphanPages();
        $count += $this->detectDuplicateTitles();
        $count += $this->detectCanonicalChains();
        $count += $this->detectNoindexWithLinks();
        return $count;
    }

    // Port each check method from BudgetAnalyzerService:
    private function checkRedirects(array $data): array { /* lines 62-175 */ }
    private function checkWaste(array $data): array { /* lines 212-323 */ }
    private function checkIndexability(array $data): array { /* lines 332-414 */ }
    private function detectOrphanPages(): int { /* lines 449-478 */ }
    private function detectDuplicateTitles(): int { /* lines 483-516 */ }
    private function detectCanonicalChains(): int { /* lines 521-565 */ }
    private function detectNoindexWithLinks(): int { /* lines 570-600 */ }
}
```

**Step 2: Add new budget categories to AuditAnalyzer CATEGORY_CONFIG**

Modify `modules/seo-audit/services/AuditAnalyzer.php` lines 24-75, add after 'robots' entry:
```php
'redirect'      => ['icon' => 'arrow-path',           'color' => 'rose',    'label' => 'Redirect',         'description' => 'Catene e loop di redirect'],
'waste'         => ['icon' => 'trash',                 'color' => 'orange',  'label' => 'Pagine Spreco',    'description' => 'Pagine che sprecano crawl budget'],
'indexability'  => ['icon' => 'eye-slash',             'color' => 'violet',  'label' => 'Indicizzabilità',  'description' => 'Conflitti di indicizzazione'],
```

**Step 3: Verify syntax**

```bash
php -l modules/seo-audit/services/BudgetIssueDetector.php
php -l modules/seo-audit/services/AuditAnalyzer.php
```

**Step 4: Commit**

```bash
git add modules/seo-audit/services/BudgetIssueDetector.php modules/seo-audit/services/AuditAnalyzer.php
git commit -m "feat(seo-audit): add BudgetIssueDetector with redirect/waste/indexability checks"
```

---

## Task 7: BudgetScoreCalculator service

**Files:**
- Create: `modules/seo-audit/services/BudgetScoreCalculator.php`
- Reference: `modules/crawl-budget/services/BudgetAnalyzerService.php` lines 616-688

**Context:**
- Separate score from SA health score — they coexist
- Formula from CB: `100 - (critical*3 cap40 + warning*1.5 cap30 + notice*0.5 cap10 + waste%*0.4 cap20)`
- Counts only budget issues (categories: redirect, waste, indexability)
- Stored in `sa_crawl_sessions.budget_score` and `sa_projects.budget_score`

**Step 1: Create BudgetScoreCalculator**

```php
<?php
namespace Modules\SeoAudit\Services;

use Core\Database;

class BudgetScoreCalculator
{
    /** Calculate budget score 0-100 for a project/session */
    public function calculate(int $projectId, ?int $sessionId = null): array
    {
        // 1. Count budget issues by severity (only categories: redirect, waste, indexability)
        // 2. Calculate waste_percentage from Page model
        // 3. Apply formula
        // 4. Return ['score' => int, 'label' => string, 'color' => string, 'waste_percentage' => float]
    }

    public static function getLabel(int $score): string
    {
        if ($score >= 90) return 'Eccellente';
        if ($score >= 70) return 'Buono';
        if ($score >= 50) return 'Migliorabile';
        return 'Critico';
    }

    public static function getColor(int $score): string
    {
        if ($score >= 90) return 'emerald';
        if ($score >= 70) return 'blue';
        if ($score >= 50) return 'amber';
        return 'red';
    }
}
```

**Step 2: Verify + Commit**

```bash
php -l modules/seo-audit/services/BudgetScoreCalculator.php
git add modules/seo-audit/services/BudgetScoreCalculator.php
git commit -m "feat(seo-audit): add BudgetScoreCalculator for crawl budget scoring"
```

---

## Task 8: Integrate budget analysis into CrawlController SSE flow

**Files:**
- Modify: `modules/seo-audit/controllers/CrawlController.php`
  - In `processStream()` (lines 628-768): add BudgetIssueDetector call after IssueDetector
  - In `start()`: add sitemap discovery + robots.txt parsing before crawl
  - After crawl completion: call `BudgetIssueDetector::runPostAnalysis()` and `BudgetScoreCalculator::calculate()`

**Context:**
- `processStream()` SSE loop at lines 694-763: for each page:
  1. Crawl page → Save page → IssueDetector → send SSE event
  2. **Add**: BudgetIssueDetector after IssueDetector
- On completion (lines 664-691):
  1. Finalize session → send 'completed' event
  2. **Add**: runPostAnalysis(), calculate budget score, save to session+project
- `start()` method: creates session, discovers URLs
  - **Add**: fetch robots.txt, parse rules, discover sitemaps, seed sitemap URLs
  - Pass sitemapUrls and robotsRules to CrawlerService

**Step 1: Add imports at top of file**

```php
use Modules\SeoAudit\Services\BudgetIssueDetector;
use Modules\SeoAudit\Services\BudgetScoreCalculator;
use Modules\SeoAudit\Services\RobotsTxtParser;
use Modules\SeoAudit\Services\SitemapParser;
```

**Step 2: Modify `start()` to init budget data**

After session creation, before URL discovery:
```php
// Fetch and parse robots.txt
$robotsContent = @file_get_contents($baseUrl . '/robots.txt');
$robotsParser = new RobotsTxtParser();
$robotsRules = $robotsContent ? $robotsParser->parse($robotsContent) : [];
$sitemapDirectives = $robotsContent ? $robotsParser->extractSitemaps($robotsContent) : [];
$crawlDelay = $robotsContent ? $robotsParser->extractCrawlDelay($robotsContent) : null;

// Parse sitemaps
$sitemapParser = new SitemapParser();
$sitemapResult = $sitemapParser->discoverAndParse($baseUrl, $robotsContent);
$sitemapUrls = $sitemapResult['urls'] ?? [];

// Save to sa_site_config
$siteConfigModel->updateBudgetData($projectId, [
    'robots_rules' => json_encode($robotsRules),
    'sitemap_urls' => json_encode($sitemapUrls),
    'crawl_delay' => $crawlDelay,
]);

// Pass to crawler
$crawler->setSitemapUrls($sitemapUrls);
$crawler->setRobotsRules($robotsRules);
```

**Step 3: Modify `processStream()` to run budget detection**

After existing issue detection (around line 730-740), add:
```php
// Budget issue detection (after standard IssueDetector)
$budgetDetector = new BudgetIssueDetector();
$budgetDetector->init($projectId)->setSessionId($sessionId);
$budgetIssuesCount = $budgetDetector->analyzeAndSave($pageData, $pageId);
$totalIssues += $budgetIssuesCount;
```

On crawl completion (around lines 664-691), add before 'completed' event:
```php
// Post-analysis for budget issues
$budgetDetector = new BudgetIssueDetector();
$budgetDetector->init($projectId)->setSessionId($sessionId);
$postIssues = $budgetDetector->runPostAnalysis();

// Calculate and save budget score
$budgetCalc = new BudgetScoreCalculator();
$budgetResult = $budgetCalc->calculate($projectId, $sessionId);
// Update session and project with budget_score
```

**Step 4: Verify syntax**

```bash
php -l modules/seo-audit/controllers/CrawlController.php
```

**Step 5: Commit**

```bash
git add modules/seo-audit/controllers/CrawlController.php
git commit -m "feat(seo-audit): integrate budget analysis into crawl SSE flow"
```

---

## Task 9: BudgetResultsController + Routes

**Files:**
- Create: `modules/seo-audit/controllers/BudgetResultsController.php`
- Modify: `modules/seo-audit/routes.php` (add new routes after line 196, before STRUTTURA LINK section)

**Context:**
- Controller handles the "Crawl Budget" tab views: overview, redirects, waste, indexability
- Pattern: follow `AuditController` structure (auth, findAccessible, View::render with $user)
- Reference: `modules/crawl-budget/controllers/ResultsController.php`
- Routes follow: `/seo-audit/project/{id}/budget`, `/budget/redirects`, `/budget/waste`, `/budget/indexability`

**Step 1: Create BudgetResultsController**

```php
<?php
namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\View;
use Core\Pagination;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Services\BudgetScoreCalculator;

class BudgetResultsController
{
    public function overview(int $id): string
    {
        Middleware::auth();
        $user = Auth::user();
        $project = (new Project())->findAccessible($id, $user['id']);
        if (!$project) { header('Location: ' . url('/seo-audit')); exit; }

        // Budget score + stats
        $budgetCalc = new BudgetScoreCalculator();
        $budgetResult = $budgetCalc->calculate($id);

        // Issue counts for budget categories only (redirect, waste, indexability)
        // Status distribution, top issues, top redirect chains

        return View::render('seo-audit::budget/overview', [
            'title' => 'Crawl Budget — ' . $project['name'],
            'user' => $user,
            'modules' => \Core\ModuleLoader::getActiveModules(),
            'project' => $project,
            'currentPage' => 'budget',
            'budgetScore' => $budgetResult,
            // ... more data
        ]);
    }

    public function redirects(int $id): string { /* similar pattern, filtered to redirect category */ }
    public function waste(int $id): string { /* filtered to waste category */ }
    public function indexability(int $id): string { /* filtered to indexability category */ }
}
```

**Step 2: Add routes**

In `modules/seo-audit/routes.php`, add new section after AUDIT DASHBOARD section (after line 196):

```php
// ============================================
// CRAWL BUDGET TAB ROUTES
// ============================================

use Modules\SeoAudit\Controllers\BudgetResultsController;

Router::get('/seo-audit/project/{id}/budget', function ($id) {
    Middleware::auth();
    $controller = new BudgetResultsController();
    return $controller->overview((int) $id);
});

Router::get('/seo-audit/project/{id}/budget/redirects', function ($id) {
    Middleware::auth();
    $controller = new BudgetResultsController();
    return $controller->redirects((int) $id);
});

Router::get('/seo-audit/project/{id}/budget/waste', function ($id) {
    Middleware::auth();
    $controller = new BudgetResultsController();
    return $controller->waste((int) $id);
});

Router::get('/seo-audit/project/{id}/budget/indexability', function ($id) {
    Middleware::auth();
    $controller = new BudgetResultsController();
    return $controller->indexability((int) $id);
});
```

**Step 3: Verify syntax**

```bash
php -l modules/seo-audit/controllers/BudgetResultsController.php
php -l modules/seo-audit/routes.php
```

**Step 4: Commit**

```bash
git add modules/seo-audit/controllers/BudgetResultsController.php modules/seo-audit/routes.php
git commit -m "feat(seo-audit): add BudgetResultsController and budget tab routes"
```

---

## Task 10: Add "Crawl Budget" tab to project navigation

**Files:**
- Modify: `modules/seo-audit/views/partials/project-nav.php` (lines 15-23 for tabs, lines 26-38 for aliases)

**Context:**
- Tab definition at lines 15-23: array of tabs with path, label, icon
- Active tab aliases at lines 26-38: maps page names to tab keys
- Add 'budget' tab between 'links' and 'action-plan'
- Need new Heroicons SVG for 'arrow-path' icon (redirect/budget concept)

**Step 1: Add tab definition**

In `$tabs` array (line 15-23), add after 'links' entry (line 19):
```php
'budget' => ['path' => '/budget', 'label' => 'Crawl Budget', 'icon' => 'arrow-path'],
```

**Step 2: Add alias mapping**

In `$aliases` array (lines 28-36), add:
```php
'budget' => ['budget', 'budget-redirects', 'budget-waste', 'budget-indexability'],
```

**Step 3: Add icon SVG**

In `$navIcons` array (lines 41-51), add:
```php
'arrow-path' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>',
```

**Step 4: Verify syntax + Commit**

```bash
php -l modules/seo-audit/views/partials/project-nav.php
git add modules/seo-audit/views/partials/project-nav.php
git commit -m "feat(seo-audit): add Crawl Budget tab to project navigation"
```

---

## Task 11: Budget tab views — Overview

**Files:**
- Create: `modules/seo-audit/views/budget/overview.php`
- Reference: `modules/crawl-budget/views/results/overview.php`
- Reference: `modules/crawl-budget/views/dashboard.php` (for score ring SVG)

**Context:**
- Layout: includes `project-nav.php` partial, uses `layout.php` wrapper
- Must pass `'user' => $user` (Golden Rule #22)
- Hero KPI row: Budget Score ring, % Spreco, Pagine Sprecate, Redirect Chain count
- Sub-tabs: Panoramica, Redirect, Waste, Indicizzabilità (links to sub-routes)
- Overview content: severity breakdown, status distribution, top 10 issues
- CSS: rounded-xl, px-4 py-3, dark:bg-slate-700/50 (Golden Rule #20)
- All text in Italian (Golden Rule #3)

**Step 1: Create overview view**

Build the view with:
1. Score ring SVG (port from CB dashboard.php lines 18-28)
2. KPI cards row (4 cards)
3. Sub-tab navigation (4 tabs)
4. Severity breakdown (dot + count)
5. Status distribution (stacked bars 2xx/3xx/4xx/5xx)
6. Top issues table
7. "Genera Report AI" CTA button

**Step 2: Create sub-tab views**

- Create: `modules/seo-audit/views/budget/redirects.php` — filtered issue table for redirect category
- Create: `modules/seo-audit/views/budget/waste.php` — filtered issue table for waste category
- Create: `modules/seo-audit/views/budget/indexability.php` — filtered issue table for indexability category

Each follows the same pattern:
- Severity filter bar (Tutti/Critici/Warning/Notice)
- Issue table: URL, Tipo, Severità badge, Dettagli
- Pagination via shared component

**Step 3: Verify syntax**

```bash
php -l modules/seo-audit/views/budget/overview.php
php -l modules/seo-audit/views/budget/redirects.php
php -l modules/seo-audit/views/budget/waste.php
php -l modules/seo-audit/views/budget/indexability.php
```

**Step 4: Commit**

```bash
git add modules/seo-audit/views/budget/
git commit -m "feat(seo-audit): add Crawl Budget tab views (overview, redirects, waste, indexability)"
```

---

## Task 12: SiteProfileDetector service

**Files:**
- Create: `modules/seo-audit/services/SiteProfileDetector.php`

**Context:**
- Analyzes crawled data to detect: site type, size, sector, languages
- Used by UnifiedReportService to contextualize the AI report
- Detection from URL patterns + page titles + schema types found

**Step 1: Create SiteProfileDetector**

```php
<?php
namespace Modules\SeoAudit\Services;

use Core\Database;

class SiteProfileDetector
{
    /**
     * Detect site profile from crawled data
     * @return array [type, size, size_label, sector, languages, avg_depth, internal_links_ratio]
     */
    public function detect(int $projectId): array
    {
        // Type detection (from URL patterns + schema types):
        // e-commerce: /product, /cart, /shop, Product schema → 'e-commerce'
        // blog: /blog, /article, /post, Article schema → 'blog'
        // saas: /pricing, /features, /docs → 'saas'
        // corporate: /about, /team, /services → 'corporate'

        // Size: count pages → micro/piccolo/medio/grande

        // Sector: analyze most common words in top-10 page titles

        // Languages: extract from hreflang tags or URL patterns (/it/, /en/)

        // Avg depth: AVG(depth) from sa_pages

        // Internal links ratio: % of pages with internal_links_count > 0
    }
}
```

**Step 2: Verify + Commit**

```bash
php -l modules/seo-audit/services/SiteProfileDetector.php
git add modules/seo-audit/services/SiteProfileDetector.php
git commit -m "feat(seo-audit): add SiteProfileDetector for AI report contextualization"
```

---

## Task 13: UnifiedReport model

**Files:**
- Create: `modules/seo-audit/models/UnifiedReport.php`

**Context:**
- CRUD for `sa_unified_reports` table
- Methods: create, findByProject, findLatest, delete

**Step 1: Create model**

Standard model following `modules/seo-audit/models/Issue.php` pattern.

**Step 2: Verify + Commit**

```bash
php -l modules/seo-audit/models/UnifiedReport.php
git add modules/seo-audit/models/UnifiedReport.php
git commit -m "feat(seo-audit): add UnifiedReport model"
```

---

## Task 14: UnifiedReportService — AI report generation

**Files:**
- Create: `modules/seo-audit/services/UnifiedReportService.php`
- Reference: `modules/crawl-budget/services/BudgetReportService.php` (AI call pattern)
- Reference: `C:\laragon\www\amevista\report\audit\audit_seo_onpage_produzione.html` (output format)

**Context:**
- This is the core service that generates the AI report
- Gathers data from both on-page (SA) and crawl budget analyses
- Builds a contextual prompt using SiteProfileDetector
- Calls AiService and expects JSON response
- Renders JSON into HTML standalone template
- Saves report to `sa_unified_reports` and to file system
- Cost: 15 credits (configurable)

**Step 1: Create UnifiedReportService**

Key methods:
```php
public function generate(int $projectId, int $userId, ?int $sessionId = null): array
{
    // 1. Credit check (15 credits)
    // 2. Gather on-page data (from AuditAnalyzer)
    // 3. Gather budget data (from BudgetScoreCalculator + budget issues)
    // 4. Detect site profile (from SiteProfileDetector)
    // 5. Build AI prompt with full context
    // 6. Call AiService::analyze()
    // 7. Database::reconnect()
    // 8. Parse JSON response
    // 9. Render HTML standalone template
    // 10. Save to sa_unified_reports + file system
    // 11. Consume credits
    // 12. Return ['success' => true, 'report_id' => id, 'html_path' => path]
}

private function gatherData(int $projectId, ?int $sessionId): array { /* ... */ }
private function buildPrompt(array $data): string { /* ... */ }
private function buildDataContext(array $data): string { /* ... */ }
private function renderHtmlStandalone(array $reportData, array $siteProfile): string { /* ... */ }
```

**Step 2: AI prompt structure**

The prompt asks Claude to return JSON with the structure defined in the design doc (section 6.3).
Include site profile, both scores, waste %, all issue data, top issues with URLs.

**Step 3: Verify + Commit**

```bash
php -l modules/seo-audit/services/UnifiedReportService.php
git add modules/seo-audit/services/UnifiedReportService.php
git commit -m "feat(seo-audit): add UnifiedReportService for contextual AI report generation"
```

---

## Task 15: HTML standalone report template

**Files:**
- Create: `modules/seo-audit/views/report/unified-template.php`
- Reference: `C:\laragon\www\amevista\report\audit\audit_seo_onpage_produzione.html` (exact CSS/JS to replicate)

**Context:**
- This PHP template receives `$reportData` (parsed JSON from AI) and `$siteProfile`
- Outputs a self-contained HTML file with inline CSS + JS
- Must replicate the amevista reference style exactly:
  - Inter font, JetBrains Mono for code
  - CSS variables for severity colors (critical red, important amber, minor blue, positive green)
  - Header with gradient dark slate, stat chips
  - Toolbar: expand/collapse/filter/hide buttons
  - Hidden issues drawer with localStorage persistence
  - Sections: Critici (Priorità 1), Importanti (Priorità 2), Minori (Priorità 3), Positivi
  - Issue cards: 2-column grid, expandable, border-left colored, num badge, impact tag
  - Fix boxes: green bg with border
  - Code blocks: dark bg, JetBrains Mono
  - Tables inside issue bodies
  - Timeline row: 3 cards (Settimana 1/2-3/4+)
  - Test replicabili panel (expandable)
  - Print-friendly @media print
  - Responsive: 2 cols → 1 col on mobile
- Footer: "Report generato da Ainstein — {date}"

**Step 1: Create template**

Port the CSS from the amevista reference (lines 7-338) and JS (lines 837-972).
The PHP part iterates over `$reportData['issues']` to generate issue cards, `$reportData['positives']` for the green grid, `$reportData['timeline']` for the timeline cards.

**Step 2: Verify syntax**

```bash
php -l modules/seo-audit/views/report/unified-template.php
```

**Step 3: Commit**

```bash
git add modules/seo-audit/views/report/unified-template.php
git commit -m "feat(seo-audit): add HTML standalone report template in amevista style"
```

---

## Task 16: Integrated report view + controller + routes

**Files:**
- Create: `modules/seo-audit/views/report/unified-view.php`
- Create: `modules/seo-audit/controllers/UnifiedReportController.php`
- Modify: `modules/seo-audit/routes.php` (add report routes)

**Context:**
- Integrated view: same structure as standalone but inside Ainstein layout
- Uses Tailwind + Alpine.js instead of vanilla CSS/JS
- Dark mode support
- Link to download the standalone HTML version
- Controller methods: view (GET), generate (POST AJAX), download (GET)

**Step 1: Create UnifiedReportController**

```php
<?php
namespace Modules\SeoAudit\Controllers;

use Core\Auth;
use Core\Middleware;
use Core\View;
use Core\Database;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\UnifiedReport;
use Modules\SeoAudit\Services\UnifiedReportService;

class UnifiedReportController
{
    /** GET: View report */
    public function view(int $id): string { /* ... */ }

    /** POST AJAX: Generate report */
    public function generate(int $id): void
    {
        // AJAX long pattern: ignore_user_abort, ob_start, session_write_close
        // Call UnifiedReportService::generate()
        // ob_end_clean + json response + exit
    }

    /** GET: Download standalone HTML */
    public function download(int $id): void { /* serve HTML file */ }
}
```

**Step 2: Create integrated view**

- Inside layout.php with sidebar
- Same structure as standalone: header, toolbar, sections, positives, timeline
- Tailwind classes instead of CSS variables
- Alpine.js `x-data` for interactivity (expand/collapse, filter, hide)
- Dark mode: all elements with `dark:` prefix

**Step 3: Add routes**

Add to `routes.php` in a new UNIFIED REPORT section:
```php
// ============================================
// UNIFIED AI REPORT ROUTES
// ============================================

use Modules\SeoAudit\Controllers\UnifiedReportController;

Router::get('/seo-audit/project/{id}/report', function ($id) {
    Middleware::auth();
    return (new UnifiedReportController())->view((int) $id);
});

Router::post('/seo-audit/project/{id}/report/generate', function ($id) {
    Middleware::auth();
    Middleware::csrf();
    (new UnifiedReportController())->generate((int) $id);
});

Router::get('/seo-audit/project/{id}/report/download', function ($id) {
    Middleware::auth();
    (new UnifiedReportController())->download((int) $id);
});
```

**Step 4: Verify syntax**

```bash
php -l modules/seo-audit/controllers/UnifiedReportController.php
php -l modules/seo-audit/views/report/unified-view.php
php -l modules/seo-audit/routes.php
```

**Step 5: Commit**

```bash
git add modules/seo-audit/controllers/UnifiedReportController.php modules/seo-audit/views/report/unified-view.php modules/seo-audit/routes.php
git commit -m "feat(seo-audit): add unified report controller, integrated view, and routes"
```

---

## Task 17: Legacy banner on Crawl Budget module

**Files:**
- Modify: `modules/crawl-budget/views/dashboard.php` (add banner after line 29, before first HTML output)

**Context:**
- Amber banner at top of CB dashboard
- Links to corresponding SA project via `global_project_id`
- Non-blocking: user can dismiss or continue using CB
- Must find SA project ID from `sa_projects` where `global_project_id` matches

**Step 1: Add banner**

After line 29 (closing PHP tag of variable setup), before first HTML div:
```php
<?php
// Check if corresponding SA project exists
$saProjectId = null;
if (!empty($project['global_project_id'])) {
    $saProject = \Core\Database::query(
        "SELECT id FROM sa_projects WHERE global_project_id = ? AND user_id = ? LIMIT 1",
        [$project['global_project_id'], $user['id'] ?? 0]
    );
    if ($saProject) {
        $saProjectId = $saProject[0]['id'] ?? null;
    }
}
?>

<?php if ($saProjectId): ?>
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 mb-6">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="flex-1">
            <p class="font-medium text-amber-800 dark:text-amber-200">
                L'analisi Crawl Budget è ora integrata in SEO Audit
            </p>
            <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                Puoi continuare a usare questo modulo, oppure passare a SEO Audit per un'analisi unificata con report avanzato.
            </p>
        </div>
        <a href="<?= url('/seo-audit/project/' . $saProjectId . '/budget') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium transition-colors whitespace-nowrap">
            Vai a SEO Audit
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
    </div>
</div>
<?php endif; ?>
```

**Step 2: Verify syntax + Commit**

```bash
php -l modules/crawl-budget/views/dashboard.php
git add modules/crawl-budget/views/dashboard.php
git commit -m "feat(crawl-budget): add legacy banner pointing to SEO Audit integration"
```

---

## Task 18: Update module.json with report cost setting

**Files:**
- Modify: `modules/seo-audit/module.json` — add `cost_unified_report` setting in costs group

**Context:**
- Follow existing pattern in module.json (see `modules/seo-tracking/module.json` for reference)
- Costs group is always last with `"group_order": 99, "collapsed": true`
- Default value: 15 credits

**Step 1: Add setting**

In the costs group of module.json, add:
```json
{
    "key": "cost_unified_report",
    "label": "Costo Report AI Unificato",
    "type": "number",
    "default": 15,
    "group": "costs",
    "description": "Crediti per generare il report AI unificato (on-page + crawl budget)"
}
```

**Step 2: Commit**

```bash
git add modules/seo-audit/module.json
git commit -m "feat(seo-audit): add unified report cost setting to module.json"
```

---

## Task 19: Storage directory for report files

**Files:**
- Create: `storage/reports/` directory (if not exists)

**Context:**
- HTML standalone reports saved to `storage/reports/{project_id}/report-{date}.html`
- Directory must exist with write permissions
- Add .gitkeep to track the directory

**Step 1: Create directory**

```bash
mkdir -p storage/reports
touch storage/reports/.gitkeep
```

**Step 2: Commit**

```bash
git add storage/reports/.gitkeep
git commit -m "feat(seo-audit): add storage directory for report files"
```

---

## Task 20: Manual integration test

**NOT a code task — manual browser testing checklist.**

**Pre-requisites:**
- Run migration on local DB
- Clear any browser cache

**Test 1: Existing SA functionality (regression check)**
1. Open `http://localhost/seo-toolkit/seo-audit`
2. Open an existing project → Dashboard loads correctly
3. Verify all existing tabs work: Pagine, Problemi, Struttura Link, Action Plan, Storico
4. Verify new "Crawl Budget" tab appears in navigation
5. Start a new crawl → Verify SSE progress works → Verify completion

**Test 2: Crawl Budget tab**
1. After crawl completes, click "Crawl Budget" tab
2. Verify: Budget Score ring displays, KPI cards show data
3. Click sub-tabs: Redirect, Pagine Spreco, Indicizzabilità
4. Verify issue tables load with pagination

**Test 3: Unified AI Report**
1. From Budget overview, click "Genera Report AI"
2. Verify loading state, credit deduction
3. After generation: verify integrated view displays correctly
4. Click "Scarica HTML" → verify standalone HTML opens in new tab
5. In standalone: test expand/collapse, filter, hide issue, print view

**Test 4: CB Legacy Banner**
1. Open Crawl Budget module
2. If project has matching SA project via global_project_id → banner appears
3. Click "Vai a SEO Audit" → navigates to SA budget tab

---

## Task 21: Run migration on production

**After all testing passes locally.**

```bash
# SSH to production
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it

# Run migration
cd ~/www/ainstein.it/public_html
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 < modules/seo-audit/migrations/003_add_budget_columns.sql

# Verify
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "DESCRIBE sa_pages;" | grep redirect_chain
mysql -u u6iaaermphtha -pexkwryfz7ieh dbj0xoiwysdlk1 -e "SHOW TABLES LIKE 'sa_unified_reports';"

# Deploy code
git pull origin main
```

---

## Task 22: Update documentation

**Files:**
- Modify: `docs/data-model.html` — add new columns to sa_pages diagram, add sa_unified_reports table
- Modify: `shared/views/docs/seo-audit.php` — document new Crawl Budget tab and unified report feature
- Modify: `CLAUDE.md` — update module status, add note about crawl budget integration

**Context:**
- Golden Rule #18: always update docs after significant development
- Data model uses Mermaid.js erDiagram format
- User docs follow structure: Cos'è, Quick Start, Funzionalità, Costi crediti, Suggerimenti

**Step 1: Update each doc file**

**Step 2: Commit**

```bash
git add docs/data-model.html shared/views/docs/seo-audit.php CLAUDE.md
git commit -m "docs: update documentation for crawl budget merge into SEO Audit"
```
