# Internal Links AI Suggester — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add AI-powered link suggestion, snippet generation, and CMS push capability to the Internal Links module, completing the missing 15%.

**Architecture:** Hybrid deterministic + AI pipeline. Phase 1 (free) extracts keywords and computes similarity scores to generate candidates. Phase 2 (AI batch) validates candidates and generates diverse anchor variants. Phase 3 (AI on-demand) finds precise insertion points in content. CMS push reuses Content Creator's connector infrastructure via `cc_connectors`.

**Tech Stack:** PHP 8+, MySQL, AiService (Claude API), Tailwind CSS, Alpine.js, HTMX, WordPressConnector

**Design doc:** `docs/plans/2026-03-03-internal-links-suggester-design.md`

---

## Task 1: Database Schema — New table + ALTER existing

**Files:**
- Modify: `modules/internal-links/database/schema.sql`

**Step 1: Add `il_link_suggestions` table and ALTER statements to schema.sql**

Append to end of `schema.sql`:

```sql
-- ============================================
-- IL_LINK_SUGGESTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_link_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    source_url_id INT NOT NULL,
    destination_url_id INT NOT NULL,

    -- Deterministic scoring (Phase 1)
    keyword_score INT DEFAULT 0,
    category_bonus INT DEFAULT 0,
    total_score INT DEFAULT 0,
    reason ENUM('hub_needs_outgoing','orphan_needs_inbound','topical_relevance') NOT NULL,

    -- AI enrichment (Phase 2)
    ai_relevance_score TINYINT NULL COMMENT '1-10 semantic relevance',
    ai_suggested_anchors JSON NULL COMMENT '["anchor1","anchor2","anchor3"]',
    ai_placement_hint TEXT NULL,
    ai_confidence ENUM('high','medium','low') NULL,
    ai_anchor_diversity_note TEXT NULL,
    ai_analyzed_at DATETIME NULL,

    -- AI insertion point (Phase 3, on-demand)
    ai_snippet_html TEXT NULL COMMENT 'Paragraph with link inserted',
    ai_original_paragraph TEXT NULL COMMENT 'Original paragraph before modification',
    ai_insertion_method ENUM('inline_existing_text','contextual_sentence') NULL,
    ai_anchor_used VARCHAR(255) NULL,
    ai_snippet_generated_at DATETIME NULL,

    -- Status
    status ENUM('pending','ai_validated','snippet_ready','applied','dismissed') DEFAULT 'pending',
    applied_at DATETIME NULL,
    applied_method ENUM('manual_copy','cms_push') NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project_status (project_id, status),
    INDEX idx_source (source_url_id),
    INDEX idx_destination (destination_url_id),
    INDEX idx_score (total_score),
    UNIQUE KEY unique_suggestion (project_id, source_url_id, destination_url_id),
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (source_url_id) REFERENCES il_urls(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_url_id) REFERENCES il_urls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add connector_id to il_projects
ALTER TABLE il_projects ADD COLUMN connector_id INT NULL AFTER status;

-- Add suggestion stats to il_project_stats
ALTER TABLE il_project_stats
    ADD COLUMN total_suggestions INT DEFAULT 0,
    ADD COLUMN pending_suggestions INT DEFAULT 0,
    ADD COLUMN applied_suggestions INT DEFAULT 0;
```

**Step 2: Run SQL on local database**

```bash
mysql -u root seo_toolkit < modules/internal-links/database/schema_suggestions.sql
```

Create a separate migration file `modules/internal-links/database/migration_suggestions.sql` with just the CREATE TABLE and ALTERs for production deployment, keeping `schema.sql` as the full reference.

**Step 3: Verify tables exist**

```bash
mysql -u root seo_toolkit -e "DESCRIBE il_link_suggestions;"
mysql -u root seo_toolkit -e "SHOW COLUMNS FROM il_projects LIKE 'connector_id';"
mysql -u root seo_toolkit -e "SHOW COLUMNS FROM il_project_stats LIKE '%suggestions%';"
```

**Step 4: Commit**

```bash
git add modules/internal-links/database/
git commit -m "feat(internal-links): add il_link_suggestions table and connector_id column"
```

---

## Task 2: Suggestion Model

**Files:**
- Create: `modules/internal-links/models/Suggestion.php`

**Step 1: Create the Suggestion model**

```php
<?php

namespace Modules\InternalLinks\Models;

use Core\Database;

/**
 * Suggestion Model
 *
 * Manages il_link_suggestions table — AI-powered link suggestions
 */
class Suggestion
{
    protected string $table = 'il_link_suggestions';

    /**
     * Find suggestion by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Find suggestion with joined URL data
     */
    public function findWithUrls(int $id, int $projectId): ?array
    {
        $sql = "
            SELECT s.*,
                   src.url as source_url, src.keyword as source_keyword,
                   dst.url as destination_url, dst.keyword as destination_keyword
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE s.id = ? AND s.project_id = ?
        ";
        return Database::fetch($sql, [$id, $projectId]);
    }

    /**
     * Get suggestions for a project with pagination and filters
     */
    public function getByProject(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where = ['s.project_id = ?'];
        $params = [$projectId];

        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['reason'])) {
            $where[] = 's.reason = ?';
            $params[] = $filters['reason'];
        }

        if (!empty($filters['min_score'])) {
            $where[] = 's.total_score >= ?';
            $params[] = (int) $filters['min_score'];
        }

        if (!empty($filters['confidence'])) {
            $where[] = 's.ai_confidence = ?';
            $params[] = $filters['confidence'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(src.url LIKE ? OR dst.url LIKE ? OR src.keyword LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countSql = "
            SELECT COUNT(*) as cnt
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE {$whereStr}
        ";
        $total = (int) Database::fetch($countSql, $params)['cnt'];

        $sql = "
            SELECT s.*,
                   src.url as source_url, src.keyword as source_keyword,
                   dst.url as destination_url, dst.keyword as destination_keyword
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE {$whereStr}
            ORDER BY s.total_score DESC, s.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;

        return [
            'data' => Database::fetchAll($sql, $params),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
        ];
    }

    /**
     * Bulk insert suggestions from deterministic phase
     */
    public function bulkInsert(int $projectId, array $suggestions): int
    {
        if (empty($suggestions)) return 0;

        $inserted = 0;
        foreach ($suggestions as $s) {
            try {
                Database::insert($this->table, [
                    'project_id' => $projectId,
                    'source_url_id' => $s['source_url_id'],
                    'destination_url_id' => $s['destination_url_id'],
                    'keyword_score' => $s['keyword_score'],
                    'category_bonus' => $s['category_bonus'],
                    'total_score' => $s['total_score'],
                    'reason' => $s['reason'],
                    'status' => 'pending',
                ]);
                $inserted++;
            } catch (\Exception $e) {
                // Duplicate — skip (UNIQUE constraint)
            }
        }
        return $inserted;
    }

    /**
     * Get suggestions pending AI validation (Phase 2)
     */
    public function getPendingAiValidation(int $projectId, int $limit = 30): array
    {
        $sql = "
            SELECT s.*,
                   src.url as source_url, src.keyword as source_keyword,
                   src.content_html as source_content,
                   dst.url as destination_url, dst.keyword as destination_keyword,
                   dst.content_html as destination_content
            FROM {$this->table} s
            JOIN il_urls src ON s.source_url_id = src.id
            JOIN il_urls dst ON s.destination_url_id = dst.id
            WHERE s.project_id = ? AND s.status = 'pending'
            ORDER BY s.total_score DESC
            LIMIT ?
        ";
        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Update AI validation results (Phase 2)
     */
    public function updateAiValidation(int $id, array $data): bool
    {
        return Database::update($this->table, [
            'ai_relevance_score' => $data['relevance_score'] ?? null,
            'ai_suggested_anchors' => isset($data['suggested_anchors']) ? json_encode($data['suggested_anchors']) : null,
            'ai_placement_hint' => $data['placement_hint'] ?? null,
            'ai_confidence' => $data['confidence'] ?? null,
            'ai_anchor_diversity_note' => $data['anchor_diversity_note'] ?? null,
            'ai_analyzed_at' => date('Y-m-d H:i:s'),
            'status' => ($data['confidence'] ?? '') === 'low' ? 'dismissed' : 'ai_validated',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Update AI snippet (Phase 3)
     */
    public function updateSnippet(int $id, array $data): bool
    {
        return Database::update($this->table, [
            'ai_snippet_html' => $data['snippet_html'] ?? null,
            'ai_original_paragraph' => $data['original_paragraph'] ?? null,
            'ai_insertion_method' => $data['insertion_method'] ?? null,
            'ai_anchor_used' => $data['anchor_used'] ?? null,
            'ai_snippet_generated_at' => date('Y-m-d H:i:s'),
            'status' => 'snippet_ready',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark suggestion as applied
     */
    public function markApplied(int $id, string $method = 'manual_copy'): bool
    {
        return Database::update($this->table, [
            'status' => 'applied',
            'applied_at' => date('Y-m-d H:i:s'),
            'applied_method' => $method,
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark suggestion as dismissed
     */
    public function markDismissed(int $id): bool
    {
        return Database::update($this->table, [
            'status' => 'dismissed',
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Get stats for a project
     */
    public function getStats(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'ai_validated' THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN status = 'snippet_ready' THEN 1 ELSE 0 END) as snippet_ready,
                SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END) as applied,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                AVG(CASE WHEN ai_relevance_score IS NOT NULL THEN ai_relevance_score END) as avg_ai_score
            FROM {$this->table}
            WHERE project_id = ?
        ";
        $result = Database::fetch($sql, [$projectId]);
        return [
            'total' => (int) ($result['total'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0),
            'validated' => (int) ($result['validated'] ?? 0),
            'snippet_ready' => (int) ($result['snippet_ready'] ?? 0),
            'applied' => (int) ($result['applied'] ?? 0),
            'dismissed' => (int) ($result['dismissed'] ?? 0),
            'actionable' => (int) ($result['validated'] ?? 0) + (int) ($result['snippet_ready'] ?? 0),
            'avg_ai_score' => $result['avg_ai_score'] ? round((float) $result['avg_ai_score'], 1) : null,
        ];
    }

    /**
     * Get anchor text distribution for the project (for diversity analysis)
     */
    public function getAnchorDistribution(int $projectId): array
    {
        // Get anchors from existing links
        $sql = "
            SELECT anchor_text_clean as anchor, COUNT(*) as count
            FROM il_internal_links
            WHERE project_id = ? AND anchor_text_clean IS NOT NULL AND anchor_text_clean != ''
            GROUP BY anchor_text_clean
            ORDER BY count DESC
            LIMIT 30
        ";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Delete all suggestions for a project (before regeneration)
     */
    public function deleteByProject(int $projectId, bool $keepApplied = true): int
    {
        $where = 'project_id = ?';
        if ($keepApplied) {
            $where .= " AND status != 'applied'";
        }
        return Database::delete($this->table, $where, [$projectId]);
    }

    /**
     * Count suggestions by project
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $ids, string $status, int $projectId): int
    {
        if (empty($ids)) return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$projectId]);

        $extra = '';
        if ($status === 'applied') {
            $extra = ", applied_at = NOW(), applied_method = 'manual_copy'";
        }

        $sql = "UPDATE {$this->table} SET status = ?{$extra} WHERE id IN ({$placeholders}) AND project_id = ?";
        array_unshift($params, $status);
        return Database::query($sql, $params);
    }
}
```

**Step 2: Verify syntax**

```bash
php -l modules/internal-links/models/Suggestion.php
```

**Step 3: Commit**

```bash
git add modules/internal-links/models/Suggestion.php
git commit -m "feat(internal-links): add Suggestion model for il_link_suggestions"
```

---

## Task 3: SuggestionService — Deterministic Engine (Phase 1)

**Files:**
- Create: `modules/internal-links/services/SuggestionService.php`

**Step 1: Create SuggestionService with keyword extraction and similarity scoring**

This is the core algorithmic engine ported from Amevista's `internal-linking-plan.php`. Key adaptations:
- Works from `il_urls` table (keyword + content_html) instead of WordPress `wp_posts`
- Category inference from URL structure (no WP taxonomy)
- Stopwords for IT/EN/DE/ES/FR/NL/PT (same as Amevista)

```php
<?php

namespace Modules\InternalLinks\Services;

use Core\Database;
use Modules\InternalLinks\Models\Suggestion;
use Modules\InternalLinks\Models\Url;
use Modules\InternalLinks\Models\InternalLink;

/**
 * SuggestionService
 *
 * Hybrid deterministic + AI engine for generating internal link suggestions.
 * Phase 1: Keyword extraction + similarity scoring (free, no AI credits)
 * Phase 2: AI validation + anchor generation (uses AiService)
 * Phase 3: AI snippet generation on-demand (uses AiService)
 */
class SuggestionService
{
    private Suggestion $suggestion;
    private Url $urlModel;
    private InternalLink $linkModel;

    /** @var array Cached keyword index: url_id => [keywords] */
    private array $keywordIndex = [];

    /** @var array Cached URL data: url_id => [url, keyword, content_excerpt] */
    private array $urlData = [];

    /** @var array Cached link map: source_url_id => [destination_url_id => true] */
    private array $existingLinks = [];

    /** @var array Cached inbound counts: url_id => count */
    private array $inboundCounts = [];

    /** @var array Cached outbound counts: url_id => count */
    private array $outboundCounts = [];

    /** @var array Cached category per URL: url_id => category_slug */
    private array $urlCategories = [];

    // Multilingual stopwords (IT, EN, DE, ES, FR, NL, PT)
    private const STOPWORDS = [
        'il','lo','la','le','gli','un','una','di','del','della','delle','dei','degli',
        'da','in','con','su','per','tra','fra','che','come','sono','e','a','o','al',
        'alla','alle','ai','agli','dal','dalla','nel','nella','nelle','nei','non','si',
        'ci','se','anche','questo','questa','questi','queste','ogni','tutto',
        'the','and','or','but','in','on','at','to','for','of','with','by','from',
        'is','are','was','were','be','been','have','has','had','do','does','did',
        'will','would','shall','should','can','could','may','might','must','not','no',
        'so','if','that','this','these','those','it','its','you','your','we','our',
        'they','their','he','she','him','her','who','which','what','how','why','when',
        'where','all','each','every','both','few','more','most','some','any','other',
        'into','about','than','very',
        'die','der','das','den','dem','des','ein','eine','einen','einem','einer',
        'und','oder','aber','auf','zu','mit','von','bei','nach','ist','sind',
        'war','hat','haben','wird','werden','kann','nicht','auch','noch','nur',
        'el','los','las','una','unos','unas','pero','del','con','por','para',
        'sin','sobre','entre','que','como','son','ser','estar','hay','todo',
        'les','des','du','et','ou','mais','dans','sur','pour','par','avec','sans',
        'qui','est','ont','pas','ne','plus','aussi','ce','cette','ces','son','sa',
        'het','een','en','maar','op','aan','voor','met','van','bij','naar','over',
        'onder','door','als','zijn','was','heeft','hebben','wordt','niet','ook',
        'os','as','uns','umas','mas','em','do','dos','das','no','na','nos','nas',
        'com','sem','entre','ser','estar','todo','cada','seu','sua',
    ];

    private array $stopwordsFlipped;

    // Related category pairs for bonus scoring
    private const RELATED_PAIRS = [
        'guide|technology', 'technology|guide',
        'guide|trends', 'trends|guide',
        'guide|reviews', 'reviews|guide',
        'news|trends', 'trends|news',
        'tutorials|guide', 'guide|tutorials',
    ];

    public function __construct()
    {
        $this->suggestion = new Suggestion();
        $this->urlModel = new Url();
        $this->linkModel = new InternalLink();
        $this->stopwordsFlipped = array_flip(self::STOPWORDS);
    }

    /**
     * Phase 1: Generate deterministic suggestions
     *
     * @return array ['total_candidates' => int, 'plan_a' => int, 'plan_b' => int]
     */
    public function generateDeterministic(int $projectId): array
    {
        // Clear old non-applied suggestions
        $this->suggestion->deleteByProject($projectId, true);

        // Load all data into memory
        $this->loadProjectData($projectId);

        if (count($this->urlData) < 5) {
            return ['total_candidates' => 0, 'plan_a' => 0, 'plan_b' => 0, 'error' => 'Servono almeno 5 URL scrapate'];
        }

        // Build keyword index
        $this->buildKeywordIndex();

        $suggestions = [];

        // Plan A: Hub pages with few outgoing links
        $planA = $this->buildPlanA($projectId);
        $suggestions = array_merge($suggestions, $planA);

        // Plan B: Orphan pages needing inbound links
        $planB = $this->buildPlanB($projectId);
        $suggestions = array_merge($suggestions, $planB);

        // Cap per source URL: max 8 suggestions
        $suggestions = $this->capPerSource($suggestions, 8);

        // Bulk insert
        $inserted = $this->suggestion->bulkInsert($projectId, $suggestions);

        return [
            'total_candidates' => $inserted,
            'plan_a' => count($planA),
            'plan_b' => count($planB),
        ];
    }

    /**
     * Load all project data into memory for fast processing
     */
    private function loadProjectData(int $projectId): void
    {
        // Load scraped URLs
        $urls = Database::fetchAll(
            "SELECT id, url, keyword, content_html FROM il_urls WHERE project_id = ? AND status = 'scraped' AND content_html IS NOT NULL",
            [$projectId]
        );

        $this->urlData = [];
        foreach ($urls as $u) {
            $this->urlData[$u['id']] = $u;
        }

        if (empty($this->urlData)) return;

        $urlIds = array_keys($this->urlData);
        $placeholders = implode(',', array_fill(0, count($urlIds), '?'));

        // Load existing internal links
        $links = Database::fetchAll(
            "SELECT source_url_id, destination_url FROM il_internal_links WHERE project_id = ? AND is_internal = 1",
            [$projectId]
        );

        $this->existingLinks = [];
        $this->inboundCounts = array_fill_keys($urlIds, 0);
        $this->outboundCounts = array_fill_keys($urlIds, 0);

        // Build URL-to-ID lookup
        $urlToId = [];
        foreach ($this->urlData as $id => $u) {
            $normalized = rtrim(strtolower($u['url']), '/');
            $urlToId[$normalized] = $id;
        }

        foreach ($links as $link) {
            $sourceId = $link['source_url_id'];
            $destNormalized = rtrim(strtolower($link['destination_url']), '/');
            $destId = $urlToId[$destNormalized] ?? null;

            if (!isset($this->existingLinks[$sourceId])) {
                $this->existingLinks[$sourceId] = [];
            }

            if ($destId !== null) {
                $this->existingLinks[$sourceId][$destId] = true;
                $this->inboundCounts[$destId] = ($this->inboundCounts[$destId] ?? 0) + 1;
            }

            $this->outboundCounts[$sourceId] = ($this->outboundCounts[$sourceId] ?? 0) + 1;
        }

        // Infer categories from URL structure
        $this->inferCategories();
    }

    /**
     * Infer page categories from URL path structure
     * e.g., /blog/technology/post-slug -> "technology"
     */
    private function inferCategories(): void
    {
        $this->urlCategories = [];
        foreach ($this->urlData as $id => $u) {
            $path = parse_url($u['url'], PHP_URL_PATH) ?? '';
            $segments = array_values(array_filter(explode('/', $path)));

            // Try second-to-last segment as category (common pattern: /blog/category/slug)
            if (count($segments) >= 2) {
                $category = $segments[count($segments) - 2];
                // Skip numeric segments and very short ones
                if (!is_numeric($category) && mb_strlen($category) > 2) {
                    $this->urlCategories[$id] = strtolower($category);
                }
            }
        }
    }

    /**
     * Build keyword index for all URLs
     */
    private function buildKeywordIndex(): void
    {
        $this->keywordIndex = [];
        foreach ($this->urlData as $id => $u) {
            $text = '';
            // Triple weight for keyword
            if (!empty($u['keyword'])) {
                $text = str_repeat($u['keyword'] . ' ', 3);
            }
            // First 800 chars of content
            if (!empty($u['content_html'])) {
                $text .= mb_substr(strip_tags($u['content_html']), 0, 800);
            }
            $this->keywordIndex[$id] = $this->extractKeywords($text, 20);
        }
    }

    /**
     * Extract top keywords from text (ported from Amevista)
     */
    private function extractKeywords(string $text, int $limit = 20): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $kw = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 3) continue;
            if (isset($this->stopwordsFlipped[$w])) continue;
            $kw[$w] = ($kw[$w] ?? 0) + 1;
        }
        arsort($kw);
        return array_slice(array_keys($kw), 0, $limit);
    }

    /**
     * Calculate keyword overlap score with positional weighting (ported from Amevista)
     */
    private function keywordOverlap(array $kw1, array $kw2): int
    {
        $set1 = array_flip($kw1);
        $score = 0;
        foreach ($kw2 as $i => $w) {
            if (isset($set1[$w])) {
                $weight1 = max(1, 10 - (array_search($w, $kw1) ?? 10));
                $weight2 = max(1, 10 - $i);
                $score += ($weight1 + $weight2);
            }
        }
        return $score;
    }

    /**
     * Find related URLs by keyword similarity
     */
    private function findRelated(int $urlId, array $excludeIds = [], int $limit = 5): array
    {
        if (!isset($this->keywordIndex[$urlId])) return [];

        $kw = $this->keywordIndex[$urlId];
        $sourceCategory = $this->urlCategories[$urlId] ?? 'unknown';
        $scores = [];

        foreach ($this->keywordIndex as $candidateId => $candidateKw) {
            if ($candidateId === $urlId) continue;
            if (in_array($candidateId, $excludeIds)) continue;

            $score = $this->keywordOverlap($kw, $candidateKw);

            // Category bonus
            $candidateCategory = $this->urlCategories[$candidateId] ?? 'unknown';
            if ($sourceCategory !== 'unknown' && $candidateCategory === $sourceCategory) {
                $score += 8;
            } elseif ($sourceCategory !== 'unknown' && $candidateCategory !== 'unknown') {
                $pair = "$sourceCategory|$candidateCategory";
                if (in_array($pair, self::RELATED_PAIRS)) {
                    $score += 3;
                }
            }

            if ($score > 0) {
                $scores[$candidateId] = ['score' => $score, 'category_bonus' =>
                    ($candidateCategory === $sourceCategory && $sourceCategory !== 'unknown') ? 8 :
                    (in_array("$sourceCategory|$candidateCategory", self::RELATED_PAIRS) ? 3 : 0)
                ];
            }
        }

        uasort($scores, fn($a, $b) => $b['score'] - $a['score']);
        return array_slice($scores, 0, $limit, true);
    }

    /**
     * Plan A: Hub pages with high inbound but few outgoing magazine links
     */
    private function buildPlanA(int $projectId): array
    {
        $suggestions = [];

        // Sort by inbound count desc
        arsort($this->inboundCounts);

        foreach ($this->inboundCounts as $urlId => $inboundCount) {
            if ($inboundCount === 0) continue;

            $outbound = $this->outboundCounts[$urlId] ?? 0;
            if ($outbound >= 3) continue;

            $alreadyLinked = array_keys($this->existingLinks[$urlId] ?? []);
            $related = $this->findRelated($urlId, $alreadyLinked, 5);

            $count = 0;
            foreach ($related as $targetId => $scoreData) {
                if ($count >= 3) break;
                $suggestions[] = [
                    'source_url_id' => $urlId,
                    'destination_url_id' => $targetId,
                    'keyword_score' => $scoreData['score'] - $scoreData['category_bonus'],
                    'category_bonus' => $scoreData['category_bonus'],
                    'total_score' => $scoreData['score'],
                    'reason' => 'hub_needs_outgoing',
                ];
                $count++;
            }
        }

        return $suggestions;
    }

    /**
     * Plan B: Orphan pages with 0 inbound links
     */
    private function buildPlanB(int $projectId): array
    {
        $suggestions = [];

        foreach ($this->urlData as $urlId => $url) {
            if (($this->inboundCounts[$urlId] ?? 0) > 0) continue;

            // Find pages that SHOULD link to this orphan
            $kw = $this->keywordIndex[$urlId] ?? [];
            if (empty($kw)) continue;

            $sourceCategory = $this->urlCategories[$urlId] ?? 'unknown';
            $candidates = [];

            foreach ($this->keywordIndex as $candidateId => $candidateKw) {
                if ($candidateId === $urlId) continue;
                if (isset($this->existingLinks[$candidateId][$urlId])) continue;

                $score = $this->keywordOverlap($kw, $candidateKw);
                $candidateCategory = $this->urlCategories[$candidateId] ?? 'unknown';
                $catBonus = 0;
                if ($sourceCategory !== 'unknown' && $candidateCategory === $sourceCategory) {
                    $score += 8;
                    $catBonus = 8;
                }

                if ($score > 5) {
                    $candidates[$candidateId] = ['score' => $score, 'category_bonus' => $catBonus];
                }
            }

            uasort($candidates, fn($a, $b) => $b['score'] - $a['score']);
            $topCandidates = array_slice($candidates, 0, 3, true);

            foreach ($topCandidates as $linkerId => $scoreData) {
                $suggestions[] = [
                    'source_url_id' => $linkerId,
                    'destination_url_id' => $urlId,
                    'keyword_score' => $scoreData['score'] - $scoreData['category_bonus'],
                    'category_bonus' => $scoreData['category_bonus'],
                    'total_score' => $scoreData['score'],
                    'reason' => 'orphan_needs_inbound',
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Cap suggestions per source URL
     */
    private function capPerSource(array $suggestions, int $max = 8): array
    {
        // Sort by total_score desc within each source
        usort($suggestions, fn($a, $b) => $b['total_score'] - $a['total_score']);

        $perSource = [];
        $capped = [];

        foreach ($suggestions as $s) {
            $sourceId = $s['source_url_id'];
            $perSource[$sourceId] = ($perSource[$sourceId] ?? 0) + 1;
            if ($perSource[$sourceId] <= $max) {
                $capped[] = $s;
            }
        }

        return $capped;
    }

    // ─── Phase 2 & 3: AI methods (separate task) ────────────────

    /**
     * Build AI validation prompt (Phase 2)
     */
    public function buildValidationPrompt(array $suggestions, array $anchorDistribution): string
    {
        // Implemented in Task 4
        return '';
    }

    /**
     * Build AI snippet prompt (Phase 3)
     */
    public function buildSnippetPrompt(array $suggestion, string $sourceContentHtml, array $existingAnchors): string
    {
        // Implemented in Task 4
        return '';
    }
}
```

**Step 2: Verify syntax**

```bash
php -l modules/internal-links/services/SuggestionService.php
```

**Step 3: Commit**

```bash
git add modules/internal-links/services/SuggestionService.php
git commit -m "feat(internal-links): add SuggestionService with deterministic keyword engine"
```

---

## Task 4: SuggestionService — AI Prompts (Phase 2 & 3)

**Files:**
- Modify: `modules/internal-links/services/SuggestionService.php`

**Step 1: Implement `buildValidationPrompt()` for Phase 2 batch AI validation**

Replace the placeholder method in SuggestionService:

```php
/**
 * Build AI validation prompt (Phase 2)
 *
 * Evaluates suggestion candidates, generates diverse anchor text variants,
 * and checks anchor distribution for over-optimization.
 */
public function buildValidationPrompt(array $suggestions, array $anchorDistribution): string
{
    $candidatesText = '';
    foreach ($suggestions as $i => $s) {
        $srcExcerpt = mb_substr(strip_tags($s['source_content'] ?? ''), 0, 300);
        $dstExcerpt = mb_substr(strip_tags($s['destination_content'] ?? ''), 0, 300);
        $candidatesText .= sprintf(
            "%d. [Score: %d, Motivo: %s]\n   SORGENTE (ID %d): %s\n   Keyword: %s\n   Contenuto: %s\n   DESTINAZIONE (ID %d): %s\n   Keyword: %s\n   Contenuto: %s\n\n",
            $i + 1,
            $s['total_score'],
            $s['reason'],
            $s['source_url_id'], $s['source_url'],
            $s['source_keyword'] ?? '-',
            $srcExcerpt ?: '[nessun contenuto]',
            $s['destination_url_id'], $s['destination_url'],
            $s['destination_keyword'] ?? '-',
            $dstExcerpt ?: '[nessun contenuto]'
        );
    }

    $anchorsText = '';
    if (!empty($anchorDistribution)) {
        $anchorsText = "## ANCORA GIA' USATE NEL PROGETTO (top per frequenza)\n";
        foreach (array_slice($anchorDistribution, 0, 20) as $a) {
            $anchorsText .= sprintf("- \"%s\" (%dx)\n", $a['anchor'], $a['count']);
        }
    }

    return <<<PROMPT
Sei un esperto SEO specializzato in internal linking. Analizza questi candidati per nuovi link interni e valida la loro rilevanza semantica reale.

## CANDIDATI DA VALUTARE
{$candidatesText}

{$anchorsText}

## ISTRUZIONI

Per ogni candidato:
1. Valuta se la SORGENTE e la DESTINAZIONE sono REALMENTE correlate semanticamente (non solo keyword overlap)
2. Se la correlazione e' bassa o forzata, assegna confidence "low" (verra' scartato)
3. Per i candidati validi, genera 3 varianti di anchor text:
   - Variante 1: keyword-focused (termine chiave della destinazione)
   - Variante 2: contesto naturale (frase che si integra nel testo della sorgente)
   - Variante 3: diversificata (EVITA ancore gia' sovra-usate nel progetto)
4. Indica il punto del contenuto sorgente piu' adatto per inserire il link
5. Se un'ancora suggerita e' gia' troppo frequente nel progetto, segnalalo

## FORMATO RISPOSTA

Rispondi SOLO con un array JSON:
[
    {
        "candidate_index": 1,
        "relevance_score": 8,
        "confidence": "high",
        "suggested_anchors": ["ancora keyword", "frase naturale per il contesto", "variante diversificata"],
        "placement_hint": "Il paragrafo che tratta di [argomento] e' il punto migliore per inserire il link",
        "anchor_diversity_note": "L'ancora 'keyword X' e' gia' usata 12 volte, preferire la variante 3"
    }
]

Note:
- confidence: "high" (forte correlazione), "medium" (correlazione accettabile), "low" (forzato/irrilevante, da scartare)
- relevance_score: 1-10 (basato sulla correlazione semantica REALE, non sul keyword_score algoritmico)
- Se un candidato e' chiaramente irrilevante, rispondi con confidence "low" e relevance_score <= 3
PROMPT;
}
```

**Step 2: Implement `buildSnippetPrompt()` for Phase 3 on-demand snippet generation**

```php
/**
 * Build AI snippet prompt (Phase 3)
 *
 * Finds the best insertion point in the source content and generates
 * a ready-to-use HTML snippet with the link naturally inserted.
 */
public function buildSnippetPrompt(
    string $sourceContentHtml,
    string $destinationUrl,
    string $destinationTitle,
    string $destinationKeyword,
    array $suggestedAnchors,
    array $existingAnchorsInPage,
    array $existingAnchorsForDest,
    int $totalLinksInPage
): string {
    $anchorsInPageText = '';
    if (!empty($existingAnchorsInPage)) {
        $anchorsInPageText = "## ANCORE GIA' PRESENTI NELLA PAGINA SORGENTE\n";
        foreach (array_slice($existingAnchorsInPage, 0, 15) as $a) {
            $anchorsInPageText .= "- \"{$a}\"\n";
        }
    }

    $anchorsForDestText = '';
    if (!empty($existingAnchorsForDest)) {
        $anchorsForDestText = "## ANCORE GIA' USATE PER QUESTA DESTINAZIONE (nel progetto)\n";
        foreach ($existingAnchorsForDest as $a) {
            $anchorsForDestText .= "- \"{$a}\"\n";
        }
    }

    $suggestedText = implode(', ', array_map(fn($a) => "\"$a\"", $suggestedAnchors));

    return <<<PROMPT
Sei un esperto SEO. Devi inserire UN link interno in modo NATURALE nel contenuto HTML fornito.

## DESTINAZIONE DEL LINK
URL: {$destinationUrl}
Titolo: {$destinationTitle}
Keyword: {$destinationKeyword}
Ancore suggerite: {$suggestedText}

## CONTENUTO HTML DELLA PAGINA SORGENTE
{$sourceContentHtml}

{$anchorsInPageText}
{$anchorsForDestText}

Link totali gia' presenti nella pagina: {$totalLinksInPage}

## ISTRUZIONI CRITICHE

1. Trova il paragrafo PIU' COERENTE con il tema della destinazione
2. NON forzare: se nessun paragrafo e' naturalmente collegato, usa method "contextual_sentence"
3. Per "inline_existing_text": scegli parole GIA' presenti nel testo come ancora
   - L'ancora deve avere senso come testo linkato (no singole preposizioni, no frammenti)
   - NON inserire dentro heading (h1-h6), link esistenti, o attributi HTML
4. Per "contextual_sentence": genera una frase NELLA LINGUA del contenuto
   - Usa frasi come "Scopri anche...", "Approfondisci...", "Leggi anche..."
   - La frase deve sembrare scritta dall'autore, non da un bot
5. EVITA ancore gia' usate per questa destinazione — genera una VARIANTE
6. L'ancora deve essere diversa dalle ancore gia' presenti nella pagina (evita over-optimization)
7. Genera lo snippet HTML del paragrafo modificato, pronto da copiare

## FORMATO RISPOSTA

Rispondi SOLO con JSON:
{
    "paragraph_excerpt": "primi 150 caratteri del paragrafo individuato...",
    "anchor_text": "l'ancora scelta",
    "anchor_alternatives": ["variante1", "variante2"],
    "insertion_method": "inline_existing_text",
    "confidence": "high",
    "reason": "Spiegazione breve del perche' questo punto e' naturale",
    "snippet_html": "<p>...testo con <a href=\"URL\">ancora</a> inserita...</p>",
    "original_paragraph": "<p>...testo originale del paragrafo...</p>"
}

Note:
- insertion_method: "inline_existing_text" (preferito) o "contextual_sentence" (fallback)
- confidence: "high", "medium", "low"
- snippet_html: SOLO il paragrafo modificato, non l'intero contenuto
- original_paragraph: il paragrafo originale prima della modifica
PROMPT;
}

/**
 * Parse AI validation response (Phase 2)
 */
public function parseValidationResponse(string $response, array $suggestions): array
{
    $results = [];

    if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
        $response = $matches[0];
    }

    $parsed = json_decode($response, true);
    if (!is_array($parsed)) return $results;

    foreach ($parsed as $item) {
        $index = ($item['candidate_index'] ?? 0) - 1;
        if (!isset($suggestions[$index])) continue;

        $results[$index] = [
            'relevance_score' => max(1, min(10, (int) ($item['relevance_score'] ?? 5))),
            'confidence' => in_array($item['confidence'] ?? '', ['high', 'medium', 'low']) ? $item['confidence'] : 'medium',
            'suggested_anchors' => $item['suggested_anchors'] ?? [],
            'placement_hint' => mb_substr($item['placement_hint'] ?? '', 0, 500),
            'anchor_diversity_note' => mb_substr($item['anchor_diversity_note'] ?? '', 0, 500),
        ];
    }

    return $results;
}

/**
 * Parse AI snippet response (Phase 3)
 */
public function parseSnippetResponse(string $response): ?array
{
    if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
        $response = $matches[0];
    }

    $parsed = json_decode($response, true);
    if (!is_array($parsed)) return null;

    return [
        'snippet_html' => $parsed['snippet_html'] ?? null,
        'original_paragraph' => $parsed['original_paragraph'] ?? null,
        'insertion_method' => in_array($parsed['insertion_method'] ?? '', ['inline_existing_text', 'contextual_sentence'])
            ? $parsed['insertion_method'] : 'contextual_sentence',
        'anchor_used' => mb_substr($parsed['anchor_text'] ?? '', 0, 255),
        'confidence' => $parsed['confidence'] ?? 'medium',
        'reason' => $parsed['reason'] ?? '',
    ];
}
```

**Step 2: Verify syntax**

```bash
php -l modules/internal-links/services/SuggestionService.php
```

**Step 3: Commit**

```bash
git add modules/internal-links/services/SuggestionService.php
git commit -m "feat(internal-links): add AI prompts for suggestion validation and snippet generation"
```

---

## Task 5: Routes — Suggestion endpoints

**Files:**
- Modify: `modules/internal-links/routes.php`

**Step 1: Add suggestion routes**

Add after the existing export route (line ~1810) in `routes.php`. These routes follow the exact same patterns as the existing analysis and link routes.

Routes to add:
```
GET  /internal-links/project/{id}/suggestions        → suggestions list page
POST /internal-links/project/{id}/suggestions/generate → start deterministic + AI generation (AJAX lungo)
POST /internal-links/project/{id}/suggestions/validate → AI validation batch (AJAX lungo)
POST /internal-links/project/{id}/suggestions/{sid}/snippet → generate snippet for one suggestion (AJAX)
POST /internal-links/project/{id}/suggestions/{sid}/apply   → mark as applied
POST /internal-links/project/{id}/suggestions/{sid}/dismiss → mark as dismissed
POST /internal-links/project/{id}/suggestions/bulk   → bulk actions (apply/dismiss/snippet)
GET  /internal-links/project/{id}/suggestions/export → export CSV/HTML
```

Each route handler must:
- Call `Middleware::auth()` + get `$user`
- Use `ProjectAccessService::findAccessible()` or `$projectModel->find($id, $userId)`
- Verify project exists, return 404 if not
- Follow AJAX lungo pattern for generation endpoints (ignore_user_abort, ob_start, session_write_close)
- Return View::render with `'user' => $user` for GET routes
- Use CSRF on all POST routes

The route implementations should use `SuggestionService` for generation, `AiService` for AI calls, and `Database::reconnect()` after each AI call.

Refer to:
- `routes.php:515-634` (analysis routes) for the AI batch pattern
- `routes.php:445-484` (links list) for the paginated list pattern
- `routes.php:1755-1810` (export) for the CSV export pattern

**Step 2: Verify syntax**

```bash
php -l modules/internal-links/routes.php
```

**Step 3: Commit**

```bash
git add modules/internal-links/routes.php
git commit -m "feat(internal-links): add suggestion routes (generate, validate, snippet, apply)"
```

---

## Task 6: Suggestions View — List page with filters and actions

**Files:**
- Create: `modules/internal-links/views/suggestions/index.php`

**Step 1: Create the suggestions list view**

This view follows the same patterns as `views/links/index.php` and `views/analysis/index.php`:
- Breadcrumb navigation
- KPI cards row (total, pending, validated, applied)
- Filter bar (status, reason, score range, search)
- Table with columns: Source URL, Destination URL, Score, Anchors, Status, Actions
- Each row has: "Genera Snippet", "Copia", "Applica CMS", "Applica manuale", "Ignora" buttons
- Snippet modal (Alpine.js) showing generated HTML with copy-to-clipboard
- Bulk action bar (table-bulk-bar component)
- Pagination (table-pagination component)
- Generate button in header (triggers AJAX lungo)

UI color: cyan (matching internal-links module color from CLAUDE.md)

CSS standards per Golden Rule #20: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`

Table columns reference:
| Colonna | Dato |
|---------|------|
| Checkbox | bulk select |
| Sorgente | source_url (truncated) + keyword badge |
| Destinazione | destination_url (truncated) + keyword badge |
| Score | total_score + ai_relevance_score badges |
| Motivo | reason badge (hub/orphan/topical) |
| Ancore | ai_suggested_anchors (pills) |
| Stato | status badge |
| Azioni | dropdown menu |

Alpine.js components needed:
- `suggestionList()`: filters, bulk selection, AJAX handlers
- `snippetModal()`: shows snippet HTML, copy-to-clipboard
- `generateSuggestions()`: triggers generation with progress feedback

**Step 2: Verify PHP syntax**

```bash
php -l modules/internal-links/views/suggestions/index.php
```

**Step 3: Commit**

```bash
git add modules/internal-links/views/suggestions/index.php
git commit -m "feat(internal-links): add suggestions list view with filters and actions"
```

---

## Task 7: Dashboard Integration — Widget + Quick Action card

**Files:**
- Modify: `modules/internal-links/views/projects/show.php`
- Modify: `modules/internal-links/routes.php` (project show route to pass suggestion stats)

**Step 1: Add suggestion stats to project show route**

In the route handler for `GET /internal-links/project/{id}` (line ~56), add:

```php
$suggestionModel = new \Modules\InternalLinks\Models\Suggestion();
$suggestionStats = $suggestionModel->getStats((int) $id);
```

Pass `'suggestionStats' => $suggestionStats` to `View::render()`.

**Step 2: Add Quick Action card in show.php**

Add a 5th card to the Quick Actions grid (after "Analisi AI"), changing grid to `md:grid-cols-5`:

```php
<a href="<?= url('/internal-links/project/' . $project['id'] . '/suggestions') ?>"
   class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md transition-shadow text-center">
    <div class="h-10 w-10 rounded-lg bg-cyan-100 dark:bg-cyan-900/50 mx-auto flex items-center justify-center mb-2">
        <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    </div>
    <p class="text-sm font-medium text-slate-900 dark:text-white">Suggerimenti</p>
    <p class="text-xs text-slate-500 dark:text-slate-400">
        <?= $suggestionStats['actionable'] ?> da applicare
    </p>
</a>
```

**Step 3: Verify syntax and commit**

```bash
php -l modules/internal-links/views/projects/show.php
git add modules/internal-links/views/projects/show.php modules/internal-links/routes.php
git commit -m "feat(internal-links): add suggestions widget to project dashboard"
```

---

## Task 8: Project Settings — CMS Connector selector

**Files:**
- Modify: `modules/internal-links/views/projects/settings.php`
- Modify: `modules/internal-links/routes.php` (settings route + update handler)

**Step 1: Pass connectors list to settings view**

In the settings route handler (line ~63), add:

```php
// Fetch user's CMS connectors from Content Creator
$connectors = Database::fetchAll(
    "SELECT id, name, type, is_active, last_test_status FROM cc_connectors WHERE user_id = ? AND is_active = 1",
    [$userId]
);
```

Pass `'connectors' => $connectors` to `View::render()`.

**Step 2: Add connector section to settings.php**

After the existing form fields (scrape_delay, user_agent), add a new section:

```php
<!-- CMS Connector -->
<div class="border-t border-slate-200 dark:border-slate-700 pt-6">
    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Connettore CMS</h4>
    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
        Collega un CMS per applicare automaticamente i link suggeriti
    </p>

    <?php if (!empty($connectors)): ?>
    <select name="connector_id" id="connector_id" class="block w-full rounded-lg border ...">
        <option value="">Nessun connettore</option>
        <?php foreach ($connectors as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($project['connector_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?> (<?= ucfirst($c['type']) ?>)
            <?= $c['last_test_status'] === 'success' ? '✓' : '⚠' ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php else: ?>
    <div class="text-sm text-slate-500 dark:text-slate-400">
        Nessun connettore configurato.
        <a href="<?= url('/content-creator/connectors/create') ?>" class="text-cyan-600 hover:underline">
            Configura in Content Creator
        </a>
    </div>
    <?php endif; ?>
</div>
```

**Step 3: Handle connector_id in update route**

In the update POST handler, add `connector_id` to the update array:

```php
'connector_id' => !empty($_POST['connector_id']) ? (int) $_POST['connector_id'] : null,
```

**Step 4: Verify syntax and commit**

```bash
php -l modules/internal-links/views/projects/settings.php
php -l modules/internal-links/routes.php
git add modules/internal-links/views/projects/settings.php modules/internal-links/routes.php
git commit -m "feat(internal-links): add CMS connector selector to project settings"
```

---

## Task 9: WordPress Plugin — Raw content endpoint

**Files:**
- Modify: `storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php`

**Step 1: Register new endpoint in `registerEndpoints()`**

After the existing `/seo-audit` route registration (line ~321), add:

```php
// Get raw HTML content of a single post (for internal linking)
register_rest_route('seo-toolkit/v1', '/posts/(?P<id>\d+)/raw-content', [
    'methods' => 'GET',
    'callback' => [$this, 'getPostRawContent'],
    'permission_callback' => [$this, 'verifyApiKey']
]);
```

**Step 2: Add the callback method**

After the `getAllContent` method:

```php
/**
 * Get raw HTML content of a single post
 * Used by Internal Links module for link injection
 */
public function getPostRawContent($request): \WP_REST_Response {
    $post_id = (int) $request['id'];
    $post = get_post($post_id);

    if (!$post || $post->post_status === 'trash') {
        return new \WP_REST_Response([
            'success' => false,
            'error' => 'Post non trovato'
        ], 404);
    }

    $categories = wp_get_post_categories($post_id, ['fields' => 'all']);
    $cat_data = [];
    foreach ($categories as $cat) {
        $cat_data[] = [
            'id' => $cat->term_id,
            'name' => $cat->name,
            'slug' => $cat->slug,
        ];
    }

    return new \WP_REST_Response([
        'success' => true,
        'post_id' => $post_id,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'url' => get_permalink($post_id),
        'status' => $post->post_status,
        'categories' => $cat_data,
        'word_count' => str_word_count(wp_strip_all_tags($post->post_content)),
    ]);
}
```

**Step 3: Bump plugin version**

Update `$this->version` from `'1.1.0'` to `'1.2.0'`.

**Step 4: Verify syntax and commit**

```bash
php -l storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php
git add storage/plugins/seo-toolkit-connector/seo-toolkit-connector.php
git commit -m "feat(wp-connector): add raw-content endpoint for internal links module"
```

---

## Task 10: CMS Push Integration — Apply via connector

**Files:**
- Modify: `modules/internal-links/routes.php` (the apply route from Task 5)

**Step 1: Implement CMS push in the suggestion apply route**

The POST `/suggestions/{sid}/apply` route should support `method=cms_push`:

```php
// When method is 'cms_push':
// 1. Verify project has connector_id
// 2. Load connector from cc_connectors
// 3. Find CMS entity ID by matching URL
// 4. Fetch raw content via GET /posts/{id}/raw-content
// 5. If snippet exists, inject into content
//    - If inline: replace original_paragraph with snippet_html
//    - If contextual: append snippet before last </p>
// 6. Push updated content via PUT /posts/{id}
// 7. Mark suggestion as applied with method='cms_push'
```

Use `Modules\ContentCreator\Services\Connectors\WordPressConnector` with config from `cc_connectors`:

```php
use Modules\ContentCreator\Services\Connectors\WordPressConnector;

// In the route handler:
$connector = Database::fetch("SELECT * FROM cc_connectors WHERE id = ? AND user_id = ?", [$project['connector_id'], $userId]);
$config = json_decode($connector['config'], true);
$wp = new WordPressConnector($config);
```

For URL-to-CMS-ID matching, use the WordPressConnector's `fetchItems('posts')` to get all posts and match by URL. Cache the mapping in session or DB for subsequent applies.

**Step 2: Add a new `makeRequest` method to WordPressConnector for raw-content**

The existing `fetchItems()` strips HTML. We need to call the new `/raw-content` endpoint directly. Add a public method or use the existing `makeRequest()` (which is private). The simplest approach: add a public `fetchRawContent(int $postId)` to WordPressConnector:

```php
/**
 * Fetch raw HTML content of a post (for internal linking)
 */
public function fetchRawContent(int $postId): array
{
    return $this->makeRequest('GET', '/wp-json/seo-toolkit/v1/posts/' . $postId . '/raw-content');
}
```

**Step 3: Verify syntax and commit**

```bash
php -l modules/internal-links/routes.php
php -l modules/content-creator/services/connectors/WordPressConnector.php
git add modules/internal-links/routes.php modules/content-creator/services/connectors/WordPressConnector.php
git commit -m "feat(internal-links): implement CMS push for link suggestions via WordPress connector"
```

---

## Task 11: Export — CSV and HTML report

**Files:**
- Modify: `modules/internal-links/routes.php` (export route from Task 5)

**Step 1: Implement suggestion export**

The `GET /suggestions/export` route supports `format=csv` and `format=html`:

**CSV format:** Standard columns: Source URL, Destination URL, Score, AI Score, Reason, Status, Anchors, Snippet

**HTML format:** Generates a standalone HTML report similar to Amevista's `internal-linking-plan.html`:
- KPI cards (total suggestions, applied, pending)
- Grouped by reason (Plan A / Plan B)
- Each suggestion card with: source → destination, score badges, anchor options, snippet if available
- Print-friendly CSS

Use `ob_start()` / `ob_get_clean()` for HTML generation, same pattern as Amevista.

**Step 2: Commit**

```bash
git add modules/internal-links/routes.php
git commit -m "feat(internal-links): add suggestion export in CSV and HTML formats"
```

---

## Task 12: module.json + Stats Update + Final Integration

**Files:**
- Modify: `modules/internal-links/module.json`
- Modify: `modules/internal-links/models/Project.php` (updateStats)

**Step 1: Add cost_ai_snippet to module.json**

```json
"cost_ai_snippet": {
    "type": "number",
    "label": "Costo Snippet AI",
    "description": "Crediti per generazione snippet inserimento link",
    "default": 1,
    "min": 0,
    "step": 0.1,
    "admin_only": true,
    "group": "costs"
}
```

**Step 2: Update `Project::updateStats()` to include suggestion counts**

Add 3 new subqueries to the INSERT...ON DUPLICATE KEY UPDATE in `updateStats()`:

```sql
total_suggestions = (SELECT COUNT(*) FROM il_link_suggestions WHERE project_id = ?),
pending_suggestions = (SELECT COUNT(*) FROM il_link_suggestions WHERE project_id = ? AND status IN ('pending','ai_validated','snippet_ready')),
applied_suggestions = (SELECT COUNT(*) FROM il_link_suggestions WHERE project_id = ? AND status = 'applied'),
```

**Step 3: Verify syntax and commit**

```bash
php -l modules/internal-links/models/Project.php
git add modules/internal-links/module.json modules/internal-links/models/Project.php
git commit -m "feat(internal-links): add snippet cost config and suggestion stats to project stats"
```

---

## Task 13: Documentation Update

**Files:**
- Modify: `CLAUDE.md` (update internal-links status to "Completo")
- Modify: `docs/data-model.html` (add il_link_suggestions table)
- Create or modify: `shared/views/docs/internal-links.php` (user docs)

**Step 1: Update CLAUDE.md**

Change internal-links row in STATO MODULI table:
```
| Internal Links | `internal-links` | `il_` | Completo (AI Suggester + CMS push) |
```

**Step 2: Update data model**

Add `il_link_suggestions` table to the Mermaid erDiagram in `docs/data-model.html`.

**Step 3: Update user documentation**

Add "Suggerimenti AI" section to the internal links user docs page.

**Step 4: Commit**

```bash
git add CLAUDE.md docs/data-model.html shared/views/docs/internal-links.php
git commit -m "docs: update internal-links to complete status with AI Suggester documentation"
```

---

## Execution Order & Dependencies

```
Task 1 (DB Schema) ──────────────────────────────────────┐
Task 2 (Suggestion Model) ────── depends on Task 1 ──────┤
Task 3 (SuggestionService Phase 1) ── depends on Task 2 ─┤
Task 4 (SuggestionService AI) ── depends on Task 3 ──────┤
Task 5 (Routes) ── depends on Tasks 2,3,4 ───────────────┤
Task 6 (View) ── depends on Task 5 ──────────────────────┤
Task 7 (Dashboard Widget) ── depends on Task 5 ──────────┤ parallel with 6
Task 8 (Settings Connector) ── depends on Task 1 ────────┤ parallel with 5,6,7
Task 9 (WP Plugin) ── independent ────────────────────────┤ parallel with all
Task 10 (CMS Push) ── depends on Tasks 5,8,9 ────────────┤
Task 11 (Export) ── depends on Task 5 ───────────────────┤ parallel with 10
Task 12 (module.json + Stats) ── depends on Task 2 ──────┤ parallel with 5-11
Task 13 (Docs) ── depends on all ─────────────────────────┘
```

**Parallelizable groups:**
- Tasks 6, 7, 8 can run in parallel (all depend on Task 5)
- Task 9 is fully independent
- Tasks 10, 11 can run in parallel
