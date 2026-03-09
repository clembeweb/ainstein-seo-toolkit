<?php

namespace Modules\SeoAudit\Services;

use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\Page;
use Core\Database;

/**
 * Crawl Budget Issue Detector
 *
 * Detects redirect, waste, and indexability issues from crawled page data.
 * Runs AFTER standard IssueDetector on the same pages.
 *
 * Ported from crawl-budget module BudgetAnalyzerService.
 * Uses sa_issues table (same as standard SEO issues).
 */
class BudgetIssueDetector
{
    private Issue $issueModel;
    private Page $pageModel;
    private int $projectId;
    private ?int $sessionId = null;

    public function __construct()
    {
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
    }

    public function init(int $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function setSessionId(?int $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * Analyze a single page for budget issues (called per-page during crawl)
     */
    public function analyzePage(array $pageData): array
    {
        $issues = [];
        $issues = array_merge($issues, $this->checkRedirects($pageData));
        $issues = array_merge($issues, $this->checkWaste($pageData));
        $issues = array_merge($issues, $this->checkIndexability($pageData));
        return $issues;
    }

    /**
     * Analyze and save budget issues for a page
     * @return int Number of issues created
     */
    public function analyzeAndSave(array $pageData, int $pageId): int
    {
        // Skip per pagine senza contenuto reale (rate_limited, errori rete, infrastruttura)
        $statusCode = (int) ($pageData['status_code'] ?? 0);
        $url = $pageData['url'] ?? '';
        if ($statusCode === 0
            || ($pageData['status'] ?? '') === 'rate_limited'
            || preg_match('#/cdn-cgi/#i', $url)) {
            return 0;
        }

        $issues = $this->analyzePage($pageData);

        foreach ($issues as $issue) {
            $issue['project_id'] = $this->projectId;
            $issue['session_id'] = $this->sessionId;
            $issue['page_id'] = $pageId;
            $issue['source'] = 'crawler';
            $this->issueModel->create($issue);
        }

        return count($issues);
    }

    /**
     * Post-analysis after all pages crawled
     * @return int Number of issues created
     */
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

    // =========================================================================
    // REDIRECT CHECKS
    // =========================================================================

    private function checkRedirects(array $data): array
    {
        $issues = [];
        $hops = (int) ($data['redirect_hops'] ?? 0);

        if ($hops === 0) {
            return $issues;
        }

        $chain = $data['redirect_chain'] ?? '';
        $chainArray = is_string($chain) ? (json_decode($chain, true) ?: []) : (is_array($chain) ? $chain : []);

        // Redirect loop
        if (!empty($data['is_redirect_loop'])) {
            $issues[] = [
                'category' => 'redirect',
                'issue_type' => 'redirect_loop',
                'severity' => 'critical',
                'title' => 'Loop di redirect rilevato',
                'description' => json_encode([
                    'chain' => $chainArray,
                    'hops' => $hops,
                ]),
            ];
            return $issues;
        }

        // Final status from chain
        $finalStatus = $this->getFinalStatusFromChain($chainArray);

        // Redirect ending in 4xx
        if ($finalStatus >= 400 && $finalStatus < 500) {
            $issues[] = [
                'category' => 'redirect',
                'issue_type' => 'redirect_to_4xx',
                'severity' => 'critical',
                'title' => "Redirect termina con errore {$finalStatus}",
                'description' => json_encode([
                    'chain' => $chainArray,
                    'final_status' => $finalStatus,
                ]),
            ];
        }

        // Redirect ending in 5xx
        if ($finalStatus >= 500) {
            $issues[] = [
                'category' => 'redirect',
                'issue_type' => 'redirect_to_5xx',
                'severity' => 'critical',
                'title' => "Redirect termina con errore server {$finalStatus}",
                'description' => json_encode([
                    'chain' => $chainArray,
                    'final_status' => $finalStatus,
                ]),
            ];
        }

        // Chain 2+ hops
        if ($hops >= 2) {
            $issues[] = [
                'category' => 'redirect',
                'issue_type' => 'redirect_chain',
                'severity' => 'critical',
                'title' => "Catena di redirect ({$hops} hop)",
                'description' => json_encode([
                    'chain' => $chainArray,
                    'hops' => $hops,
                    'target' => $data['redirect_target'] ?? null,
                ]),
            ];
        }

        // Temporary redirect (302/307)
        if ($this->hasTemporaryRedirect($chainArray)) {
            $issues[] = [
                'category' => 'redirect',
                'issue_type' => 'redirect_temporary',
                'severity' => 'warning',
                'title' => 'Redirect temporaneo (302) — considerare 301',
                'description' => json_encode([
                    'chain' => $chainArray,
                ]),
            ];
        }

        // Single redirect (informational)
        if ($hops === 1 && $finalStatus >= 200 && $finalStatus < 400 && !$this->hasTemporaryRedirect($chainArray)) {
            $issues[] = [
                'category' => 'redirect',
                'issue_type' => 'redirect_single',
                'severity' => 'notice',
                'title' => 'Redirect singolo (1 hop)',
                'description' => json_encode([
                    'chain' => $chainArray,
                    'target' => $data['redirect_target'] ?? null,
                ]),
            ];
        }

        return $issues;
    }

    private function getFinalStatusFromChain(array $chain): int
    {
        if (empty($chain)) {
            return 0;
        }
        $lastHop = end($chain);
        // SA chain format: [{"url":"...","status":301}, ...]
        return (int) ($lastHop['status'] ?? 0);
    }

    private function hasTemporaryRedirect(array $chain): bool
    {
        foreach ($chain as $hop) {
            $status = (int) ($hop['status'] ?? 0);
            if ($status === 302 || $status === 307) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // WASTE CHECKS
    // =========================================================================

    private function checkWaste(array $data): array
    {
        $issues = [];
        $statusCode = (int) ($data['status_code'] ?? 0);
        $wordCount = (int) ($data['word_count'] ?? 0);
        $title = $data['title'] ?? '';
        $hasParams = !empty($data['has_parameters']);
        $canonicalUrl = $data['canonical_url'] ?? null;
        $url = $data['url'] ?? '';
        $depth = (int) ($data['depth'] ?? 0);

        // Soft 404: status 200 but looks like error page
        if ($statusCode === 200) {
            $titleLower = strtolower($title);
            $isSoft404 = false;

            if (
                strpos($titleLower, '404') !== false ||
                strpos($titleLower, 'not found') !== false ||
                strpos($titleLower, 'pagina non trovata') !== false ||
                strpos($titleLower, 'page not found') !== false
            ) {
                $isSoft404 = true;
            }

            if ($wordCount < 50 && $wordCount > 0 && empty(trim($title))) {
                $isSoft404 = true;
            }

            if ($isSoft404) {
                $issues[] = [
                    'category' => 'waste',
                    'issue_type' => 'soft_404',
                    'severity' => 'critical',
                    'title' => 'Soft 404 — Pagina sembra errore ma risponde 200',
                    'description' => json_encode([
                        'status_code' => $statusCode,
                        'page_title' => $title,
                        'word_count' => $wordCount,
                    ]),
                ];
            }
        }

        // Empty page
        if ($statusCode === 200 && $wordCount === 0) {
            $issues[] = [
                'category' => 'waste',
                'issue_type' => 'empty_page',
                'severity' => 'critical',
                'title' => 'Pagina vuota — nessun contenuto testuale',
                'description' => json_encode([
                    'status_code' => $statusCode,
                ]),
            ];
        }

        // Thin content < 100 words
        if ($statusCode === 200 && $wordCount > 0 && $wordCount < 100) {
            $issues[] = [
                'category' => 'waste',
                'issue_type' => 'thin_content_budget',
                'severity' => 'warning',
                'title' => "Contenuto scarso ({$wordCount} parole) — spreco crawl budget",
                'description' => json_encode([
                    'word_count' => $wordCount,
                    'threshold' => 100,
                ]),
            ];
        }

        // URL with parameters without clean canonical
        if ($hasParams) {
            $canonicalHasParams = $canonicalUrl !== null && strpos($canonicalUrl, '?') !== false;
            $canonicalMissing = $canonicalUrl === null;

            if ($canonicalMissing || $canonicalHasParams) {
                $issues[] = [
                    'category' => 'waste',
                    'issue_type' => 'parameter_url_crawled',
                    'severity' => 'warning',
                    'title' => 'URL con parametri senza canonical verso versione pulita',
                    'description' => json_encode([
                        'url' => $url,
                        'canonical' => $canonicalUrl,
                        'clean_url' => strtok($url, '?'),
                    ]),
                ];
            }
        }

        // Deep page (depth > 4)
        if ($depth > 4) {
            $issues[] = [
                'category' => 'waste',
                'issue_type' => 'deep_page',
                'severity' => 'notice',
                'title' => "Pagina a profondità elevata (livello {$depth})",
                'description' => json_encode([
                    'depth' => $depth,
                    'threshold' => 4,
                ]),
            ];
        }

        return $issues;
    }

    // =========================================================================
    // INDEXABILITY CHECKS
    // =========================================================================

    private function checkIndexability(array $data): array
    {
        $issues = [];
        $isIndexable = !empty($data['is_indexable']);
        $inSitemap = !empty($data['in_sitemap']);
        $inRobotsAllowed = $data['in_robots_allowed'] ?? null;
        $canonicalUrl = $data['canonical_url'] ?? null;
        $url = $data['url'] ?? '';

        // Noindex but in sitemap
        if (!$isIndexable && $inSitemap) {
            $issues[] = [
                'category' => 'indexability',
                'issue_type' => 'noindex_in_sitemap',
                'severity' => 'critical',
                'title' => 'Pagina noindex presente in sitemap',
                'description' => json_encode([
                    'url' => $url,
                    'indexability_reason' => $data['indexability_reason'] ?? 'noindex',
                ]),
            ];
        }

        // Mixed signals: noindex + canonical to different page
        if (!$isIndexable && $canonicalUrl !== null && rtrim($canonicalUrl, '/') !== rtrim($url, '/')) {
            $issues[] = [
                'category' => 'indexability',
                'issue_type' => 'mixed_signals',
                'severity' => 'critical',
                'title' => 'Segnali contraddittori — noindex + canonical verso altra pagina',
                'description' => json_encode([
                    'url' => $url,
                    'canonical_url' => $canonicalUrl,
                ]),
            ];
        }

        // Blocked by robots.txt but receives internal links
        $internalLinksIn = (int) ($data['internal_links_in'] ?? 0);
        if ($inRobotsAllowed === 0 && $internalLinksIn > 0) {
            $issues[] = [
                'category' => 'indexability',
                'issue_type' => 'blocked_but_linked',
                'severity' => 'warning',
                'title' => "Bloccata da robots.txt ma riceve {$internalLinksIn} link interni",
                'description' => json_encode([
                    'url' => $url,
                    'internal_links_in' => $internalLinksIn,
                ]),
            ];
        }

        // Canonical mismatch
        if ($canonicalUrl !== null && rtrim($canonicalUrl, '/') !== rtrim($url, '/')) {
            $issues[] = [
                'category' => 'indexability',
                'issue_type' => 'canonical_mismatch_budget',
                'severity' => 'warning',
                'title' => 'Canonical URL diverso dalla pagina — spreco crawl budget',
                'description' => json_encode([
                    'url' => $url,
                    'canonical_url' => $canonicalUrl,
                ]),
            ];
        }

        return $issues;
    }

    // =========================================================================
    // POST-ANALYSIS (after full crawl)
    // =========================================================================

    private function detectOrphanPages(): int
    {
        $count = 0;
        $sessionFilter = $this->sessionId ? " AND session_id = " . (int)$this->sessionId : "";

        $orphans = Database::fetchAll(
            "SELECT id, url, depth FROM sa_pages
             WHERE project_id = ? AND status = 'crawled'
             AND internal_links_in = 0 AND depth > 0 {$sessionFilter}
             ORDER BY id",
            [$this->projectId]
        );

        foreach ($orphans as $page) {
            $this->issueModel->create([
                'project_id' => $this->projectId,
                'session_id' => $this->sessionId,
                'page_id' => (int) $page['id'],
                'category' => 'waste',
                'issue_type' => 'orphan_page_budget',
                'severity' => 'notice',
                'title' => 'Pagina orfana — nessun link interno in entrata',
                'description' => json_encode([
                    'url' => $page['url'],
                    'depth' => (int) $page['depth'],
                ]),
                'source' => 'crawler',
            ]);
            $count++;
        }

        return $count;
    }

    private function detectDuplicateTitles(): int
    {
        $count = 0;
        $sessionFilter = $this->sessionId ? " AND session_id = " . (int)$this->sessionId : "";

        $duplicates = Database::fetchAll(
            "SELECT title, GROUP_CONCAT(url SEPARATOR '|||') as urls, COUNT(*) as cnt
             FROM sa_pages
             WHERE project_id = ? AND title IS NOT NULL AND title != '' {$sessionFilter}
             GROUP BY title
             HAVING cnt > 1
             ORDER BY cnt DESC",
            [$this->projectId]
        );

        foreach ($duplicates as $dup) {
            $urls = explode('|||', $dup['urls']);
            foreach ($urls as $url) {
                $page = $this->pageModel->findByUrl($this->projectId, $url);
                if (!$page) continue;

                $this->issueModel->create([
                    'project_id' => $this->projectId,
                    'session_id' => $this->sessionId,
                    'page_id' => (int) $page['id'],
                    'category' => 'waste',
                    'issue_type' => 'duplicate_title_budget',
                    'severity' => 'warning',
                    'title' => "Titolo duplicato con altre {$dup['cnt']} pagine — spreco crawl budget",
                    'description' => json_encode([
                        'title' => $dup['title'],
                        'duplicate_urls' => $urls,
                        'count' => (int) $dup['cnt'],
                    ]),
                    'source' => 'crawler',
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function detectCanonicalChains(): int
    {
        $count = 0;
        $sessionFilter = $this->sessionId ? " AND session_id = " . (int)$this->sessionId : "";

        $pagesWithCanonical = Database::fetchAll(
            "SELECT id, url, canonical_url FROM sa_pages
             WHERE project_id = ? AND status = 'crawled'
             AND canonical_url IS NOT NULL AND canonical_url != url {$sessionFilter}",
            [$this->projectId]
        );

        foreach ($pagesWithCanonical as $page) {
            $target = $this->pageModel->findByUrl($this->projectId, $page['canonical_url']);
            if (!$target) continue;

            if (
                !empty($target['canonical_url']) &&
                $target['canonical_url'] !== $target['url'] &&
                $target['canonical_url'] !== $page['url']
            ) {
                $this->issueModel->create([
                    'project_id' => $this->projectId,
                    'session_id' => $this->sessionId,
                    'page_id' => (int) $page['id'],
                    'category' => 'indexability',
                    'issue_type' => 'canonical_chain',
                    'severity' => 'critical',
                    'title' => 'Catena di canonical — A→B→C',
                    'description' => json_encode([
                        'page_url' => $page['url'],
                        'canonical_1' => $page['canonical_url'],
                        'canonical_2' => $target['canonical_url'],
                    ]),
                    'source' => 'crawler',
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function detectNoindexWithLinks(): int
    {
        $count = 0;
        $sessionFilter = $this->sessionId ? " AND session_id = " . (int)$this->sessionId : "";

        $pages = Database::fetchAll(
            "SELECT id, url, internal_links_in FROM sa_pages
             WHERE project_id = ? AND status = 'crawled'
             AND is_indexable = 0 AND internal_links_in >= 3 {$sessionFilter}
             ORDER BY internal_links_in DESC",
            [$this->projectId]
        );

        foreach ($pages as $page) {
            $this->issueModel->create([
                'project_id' => $this->projectId,
                'session_id' => $this->sessionId,
                'page_id' => (int) $page['id'],
                'category' => 'indexability',
                'issue_type' => 'noindex_receives_links',
                'severity' => 'warning',
                'title' => "Pagina noindex riceve {$page['internal_links_in']} link interni",
                'description' => json_encode([
                    'url' => $page['url'],
                    'internal_links_in' => (int) $page['internal_links_in'],
                ]),
                'source' => 'crawler',
            ]);
            $count++;
        }

        return $count;
    }
}
