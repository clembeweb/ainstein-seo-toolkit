<?php

namespace Modules\CrawlBudget\Services;

use Core\Database;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Models\Issue;
use Modules\CrawlBudget\Models\CrawlSession;

/**
 * BudgetAnalyzerService
 *
 * Analizza pagine crawlate e rileva issue in 3 categorie:
 * - redirect: chain, loop, temporanei
 * - waste: pagine vuote, thin content, soft 404, parametri
 * - indexability: conflitti noindex/sitemap, canonical, robots
 *
 * Calcola il Crawl Budget Score (0-100).
 */
class BudgetAnalyzerService
{
    private Issue $issueModel;
    private Page $pageModel;

    public function __construct()
    {
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
    }

    /**
     * Analizza una singola pagina e crea le issue trovate.
     *
     * @return array Issues create
     */
    public function analyzePage(array $pageData, int $projectId, int $sessionId): array
    {
        $issues = [];

        $issues = array_merge($issues, $this->checkRedirects($pageData));
        $issues = array_merge($issues, $this->checkWaste($pageData));
        $issues = array_merge($issues, $this->checkIndexability($pageData));

        // Inserisci le issue nel DB
        foreach ($issues as &$issue) {
            $issue['project_id'] = $projectId;
            $issue['session_id'] = $sessionId;
            $issue['page_id'] = $pageData['page_id'] ?? null;
            $this->issueModel->create($issue);
        }

        return $issues;
    }

    // =========================================================================
    // REDIRECT CHECKS
    // =========================================================================

    /**
     * Controlla issue legate ai redirect.
     */
    private function checkRedirects(array $data): array
    {
        $issues = [];
        $hops = (int) ($data['redirect_hops'] ?? 0);

        if ($hops === 0) {
            return $issues;
        }

        $chain = $data['redirect_chain'] ?? '';
        $chainArray = is_string($chain) ? json_decode($chain, true) : (is_array($chain) ? $chain : []);
        if (!is_array($chainArray)) {
            $chainArray = [];
        }

        // Redirect loop detection
        $urls = [];
        $isLoop = false;
        foreach ($chainArray as $hop) {
            $hopUrl = explode('|', $hop)[0] ?? '';
            if (isset($urls[$hopUrl])) {
                $isLoop = true;
                break;
            }
            $urls[$hopUrl] = true;
        }

        if ($isLoop) {
            $issues[] = [
                'category' => 'redirect',
                'type' => 'redirect_loop',
                'severity' => 'critical',
                'title' => 'Loop di redirect rilevato',
                'details' => json_encode([
                    'chain' => $chainArray,
                    'hops' => $hops,
                ]),
            ];
            return $issues; // Loop ha priorita, non aggiungere altre issue redirect
        }

        // Redirect che termina con 4xx
        $finalStatus = $this->getFinalStatusFromChain($chainArray);
        if ($finalStatus >= 400 && $finalStatus < 500) {
            $issues[] = [
                'category' => 'redirect',
                'type' => 'redirect_to_4xx',
                'severity' => 'critical',
                'title' => "Redirect termina con errore {$finalStatus}",
                'details' => json_encode([
                    'chain' => $chainArray,
                    'final_status' => $finalStatus,
                ]),
            ];
        }

        // Redirect che termina con 5xx
        if ($finalStatus >= 500) {
            $issues[] = [
                'category' => 'redirect',
                'type' => 'redirect_to_5xx',
                'severity' => 'critical',
                'title' => "Redirect termina con errore server {$finalStatus}",
                'details' => json_encode([
                    'chain' => $chainArray,
                    'final_status' => $finalStatus,
                ]),
            ];
        }

        // Chain di redirect (2+ hop)
        if ($hops >= 2) {
            $issues[] = [
                'category' => 'redirect',
                'type' => 'redirect_chain',
                'severity' => 'critical',
                'title' => "Catena di redirect ({$hops} hop)",
                'details' => json_encode([
                    'chain' => $chainArray,
                    'hops' => $hops,
                    'target' => $data['redirect_target'] ?? null,
                ]),
            ];
        }

        // Redirect temporaneo (302/307)
        if ($this->hasTemporaryRedirect($chainArray)) {
            $issues[] = [
                'category' => 'redirect',
                'type' => 'redirect_temporary',
                'severity' => 'warning',
                'title' => 'Redirect temporaneo (302) — considerare 301',
                'details' => json_encode([
                    'chain' => $chainArray,
                ]),
            ];
        }

        // Singolo redirect (1 hop, non temporaneo e non verso errore)
        if ($hops === 1 && $finalStatus >= 200 && $finalStatus < 400 && !$this->hasTemporaryRedirect($chainArray)) {
            $issues[] = [
                'category' => 'redirect',
                'type' => 'redirect_single',
                'severity' => 'notice',
                'title' => 'Redirect singolo (1 hop)',
                'details' => json_encode([
                    'chain' => $chainArray,
                    'target' => $data['redirect_target'] ?? null,
                ]),
            ];
        }

        return $issues;
    }

    /**
     * Estrai status finale dalla chain.
     */
    private function getFinalStatusFromChain(array $chain): int
    {
        if (empty($chain)) {
            return 0;
        }
        $lastHop = end($chain);
        $parts = explode('|', $lastHop);
        return (int) ($parts[1] ?? 0);
    }

    /**
     * Controlla se la chain contiene redirect temporanei (302/307).
     */
    private function hasTemporaryRedirect(array $chain): bool
    {
        foreach ($chain as $hop) {
            $parts = explode('|', $hop);
            $status = (int) ($parts[1] ?? 0);
            if ($status === 302 || $status === 307) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // WASTE CHECKS
    // =========================================================================

    /**
     * Controlla issue legate a pagine spreco.
     */
    private function checkWaste(array $data): array
    {
        $issues = [];
        $httpStatus = (int) ($data['http_status'] ?? 0);
        $wordCount = (int) ($data['word_count'] ?? 0);
        $contentType = $data['content_type'] ?? '';
        $isHtml = stripos($contentType, 'text/html') !== false;
        $title = $data['title'] ?? '';
        $hasParams = !empty($data['has_parameters']);
        $canonicalUrl = $data['canonical_url'] ?? null;
        $url = $data['url'] ?? '';
        $depth = (int) ($data['depth'] ?? 0);

        // Soft 404: status 200 ma sembra una pagina 404
        if ($httpStatus === 200 && $isHtml) {
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

            // Word count < 50 con title generico
            if ($wordCount < 50 && $wordCount > 0 && empty(trim($title))) {
                $isSoft404 = true;
            }

            if ($isSoft404) {
                $issues[] = [
                    'category' => 'waste',
                    'type' => 'soft_404',
                    'severity' => 'critical',
                    'title' => 'Soft 404 — Pagina sembra errore ma risponde 200',
                    'details' => json_encode([
                        'http_status' => $httpStatus,
                        'page_title' => $title,
                        'word_count' => $wordCount,
                    ]),
                ];
            }
        }

        // Pagina vuota: 0 parole con status 200
        if ($httpStatus === 200 && $wordCount === 0 && $isHtml) {
            $issues[] = [
                'category' => 'waste',
                'type' => 'empty_page',
                'severity' => 'critical',
                'title' => 'Pagina vuota — nessun contenuto testuale',
                'details' => json_encode([
                    'http_status' => $httpStatus,
                    'content_type' => $contentType,
                ]),
            ];
        }

        // Thin content: < 100 parole
        if ($httpStatus === 200 && $isHtml && $wordCount > 0 && $wordCount < 100) {
            $issues[] = [
                'category' => 'waste',
                'type' => 'thin_content',
                'severity' => 'warning',
                'title' => "Contenuto scarso ({$wordCount} parole)",
                'details' => json_encode([
                    'word_count' => $wordCount,
                    'threshold' => 100,
                ]),
            ];
        }

        // URL con parametri senza canonical verso versione pulita
        if ($hasParams) {
            $urlWithoutParams = strtok($url, '?');
            $canonicalHasParams = $canonicalUrl !== null && strpos($canonicalUrl, '?') !== false;
            $canonicalMissing = $canonicalUrl === null;

            if ($canonicalMissing || $canonicalHasParams) {
                $issues[] = [
                    'category' => 'waste',
                    'type' => 'parameter_url_crawled',
                    'severity' => 'warning',
                    'title' => 'URL con parametri senza canonical verso versione pulita',
                    'details' => json_encode([
                        'url' => $url,
                        'canonical' => $canonicalUrl,
                        'clean_url' => $urlWithoutParams,
                    ]),
                ];
            }
        }

        // Pagina troppo profonda (depth > 4)
        if ($depth > 4) {
            $issues[] = [
                'category' => 'waste',
                'type' => 'deep_page',
                'severity' => 'notice',
                'title' => "Pagina a profondita elevata (livello {$depth})",
                'details' => json_encode([
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

    /**
     * Controlla issue legate all'indexability.
     */
    private function checkIndexability(array $data): array
    {
        $issues = [];
        $isIndexable = !empty($data['is_indexable']);
        $inSitemap = !empty($data['in_sitemap']);
        $inRobotsAllowed = $data['in_robots_allowed'] ?? true;
        $canonicalUrl = $data['canonical_url'] ?? null;
        $url = $data['url'] ?? '';
        $canonicalMatches = $data['canonical_matches'] ?? true;

        // Noindex ma presente in sitemap — segnale contraddittorio
        if (!$isIndexable && $inSitemap) {
            $issues[] = [
                'category' => 'indexability',
                'type' => 'noindex_in_sitemap',
                'severity' => 'critical',
                'title' => 'Pagina noindex presente in sitemap',
                'details' => json_encode([
                    'url' => $url,
                    'indexability_reason' => $data['indexability_reason'] ?? 'noindex',
                ]),
            ];
        }

        // Segnali misti: noindex + canonical verso altra pagina
        if (!$isIndexable && $canonicalUrl !== null && $canonicalUrl !== $url) {
            $issues[] = [
                'category' => 'indexability',
                'type' => 'mixed_signals',
                'severity' => 'critical',
                'title' => 'Segnali contraddittori — noindex + canonical verso altra pagina',
                'details' => json_encode([
                    'url' => $url,
                    'canonical_url' => $canonicalUrl,
                    'indexability_reason' => $data['indexability_reason'] ?? null,
                ]),
            ];
        }

        // Bloccata in robots.txt ma riceve link interni
        $internalLinksIn = (int) ($data['internal_links_in'] ?? 0);
        if (!$inRobotsAllowed && $internalLinksIn > 0) {
            $issues[] = [
                'category' => 'indexability',
                'type' => 'blocked_but_linked',
                'severity' => 'warning',
                'title' => "Bloccata da robots.txt ma riceve {$internalLinksIn} link interni",
                'details' => json_encode([
                    'url' => $url,
                    'internal_links_in' => $internalLinksIn,
                ]),
            ];
        }

        // Canonical mismatch
        if ($canonicalUrl !== null && !$canonicalMatches) {
            $issues[] = [
                'category' => 'indexability',
                'type' => 'canonical_mismatch',
                'severity' => 'warning',
                'title' => 'Canonical URL diverso dalla pagina',
                'details' => json_encode([
                    'url' => $url,
                    'canonical_url' => $canonicalUrl,
                ]),
            ];
        }

        // Bloccata in robots.txt (informativo)
        if (!$inRobotsAllowed) {
            $issues[] = [
                'category' => 'indexability',
                'type' => 'blocked_in_robots',
                'severity' => 'notice',
                'title' => 'Pagina bloccata da robots.txt',
                'details' => json_encode([
                    'url' => $url,
                ]),
            ];
        }

        return $issues;
    }

    // =========================================================================
    // POST-ANALYSIS (dopo crawl completo)
    // =========================================================================

    /**
     * Analisi post-crawl che richiede tutte le pagine.
     * Eseguire DOPO il completamento del crawl.
     */
    public function runPostAnalysis(int $projectId, int $sessionId): int
    {
        $issuesCreated = 0;

        // 1. Aggiorna internal_links_in per tutte le pagine
        $this->pageModel->updateInternalLinksIn($sessionId);

        // 2. Rileva pagine orfane (internal_links_in == 0, escludi homepage)
        $issuesCreated += $this->detectOrphanPages($projectId, $sessionId);

        // 3. Rileva titoli duplicati
        $issuesCreated += $this->detectDuplicateTitles($projectId, $sessionId);

        // 4. Rileva canonical chain (A→B→C)
        $issuesCreated += $this->detectCanonicalChains($projectId, $sessionId);

        // 5. Rileva noindex che riceve molti link
        $issuesCreated += $this->detectNoindexWithLinks($projectId, $sessionId);

        return $issuesCreated;
    }

    /**
     * Pagine orfane: nessun link interno in entrata (esclusa homepage).
     */
    private function detectOrphanPages(int $projectId, int $sessionId): int
    {
        $count = 0;
        $orphans = Database::fetchAll(
            "SELECT id, url, depth FROM cb_pages
             WHERE session_id = ? AND status = 'crawled'
             AND internal_links_in = 0 AND depth > 0
             ORDER BY id",
            [$sessionId]
        );

        foreach ($orphans as $page) {
            $this->issueModel->create([
                'project_id' => $projectId,
                'session_id' => $sessionId,
                'page_id' => (int) $page['id'],
                'category' => 'waste',
                'type' => 'orphan_page',
                'severity' => 'notice',
                'title' => 'Pagina orfana — nessun link interno in entrata',
                'details' => json_encode([
                    'url' => $page['url'],
                    'depth' => (int) $page['depth'],
                ]),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Titoli duplicati tra pagine.
     */
    private function detectDuplicateTitles(int $projectId, int $sessionId): int
    {
        $count = 0;
        $duplicates = $this->pageModel->getDuplicateTitles($sessionId);

        foreach ($duplicates as $dup) {
            $urls = explode('|||', $dup['urls']);
            // Crea issue per ogni URL coinvolta
            foreach ($urls as $url) {
                $page = $this->pageModel->findByUrl($sessionId, $url);
                if (!$page) {
                    continue;
                }

                $this->issueModel->create([
                    'project_id' => $projectId,
                    'session_id' => $sessionId,
                    'page_id' => (int) $page['id'],
                    'category' => 'waste',
                    'type' => 'duplicate_title',
                    'severity' => 'warning',
                    'title' => "Titolo duplicato con altre {$dup['cnt']} pagine",
                    'details' => json_encode([
                        'title' => $dup['title'],
                        'duplicate_urls' => $urls,
                        'count' => (int) $dup['cnt'],
                    ]),
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Canonical chain: pagina A canonical→B, B ha canonical diverso→C.
     */
    private function detectCanonicalChains(int $projectId, int $sessionId): int
    {
        $count = 0;

        // Trova pagine con canonical diverso dall'URL
        $pagesWithCanonical = Database::fetchAll(
            "SELECT id, url, canonical_url FROM cb_pages
             WHERE session_id = ? AND status = 'crawled'
             AND canonical_url IS NOT NULL AND canonical_url != url",
            [$sessionId]
        );

        foreach ($pagesWithCanonical as $page) {
            // Cerca la pagina target del canonical
            $target = $this->pageModel->findByUrl($sessionId, $page['canonical_url']);
            if (!$target) {
                continue;
            }

            // Se il target ha a sua volta un canonical diverso → chain
            if (
                !empty($target['canonical_url']) &&
                $target['canonical_url'] !== $target['url'] &&
                $target['canonical_url'] !== $page['url']
            ) {
                $this->issueModel->create([
                    'project_id' => $projectId,
                    'session_id' => $sessionId,
                    'page_id' => (int) $page['id'],
                    'category' => 'indexability',
                    'type' => 'canonical_chain',
                    'severity' => 'critical',
                    'title' => 'Catena di canonical — A→B→C',
                    'details' => json_encode([
                        'page_url' => $page['url'],
                        'canonical_1' => $page['canonical_url'],
                        'canonical_2' => $target['canonical_url'],
                    ]),
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Pagine noindex che ricevono molti link interni (>= 3).
     */
    private function detectNoindexWithLinks(int $projectId, int $sessionId): int
    {
        $count = 0;

        $pages = Database::fetchAll(
            "SELECT id, url, internal_links_in FROM cb_pages
             WHERE session_id = ? AND status = 'crawled'
             AND is_indexable = 0 AND internal_links_in >= 3
             ORDER BY internal_links_in DESC",
            [$sessionId]
        );

        foreach ($pages as $page) {
            $this->issueModel->create([
                'project_id' => $projectId,
                'session_id' => $sessionId,
                'page_id' => (int) $page['id'],
                'category' => 'indexability',
                'type' => 'noindex_receives_links',
                'severity' => 'warning',
                'title' => "Pagina noindex riceve {$page['internal_links_in']} link interni",
                'details' => json_encode([
                    'url' => $page['url'],
                    'internal_links_in' => (int) $page['internal_links_in'],
                ]),
            ]);
            $count++;
        }

        return $count;
    }

    // =========================================================================
    // SCORE CALCULATION
    // =========================================================================

    /**
     * Calcola il Crawl Budget Score (0-100).
     *
     * Formula:
     *   score = 100
     *   score -= min(40, critical * 3)
     *   score -= min(30, warning * 1.5)
     *   score -= min(10, notice * 0.5)
     *   score -= min(20, waste_percentage * 0.4)
     */
    public function calculateScore(int $sessionId): int
    {
        $severity = $this->issueModel->countBySeverity($sessionId);

        $totalPages = $this->pageModel->countBySession($sessionId, 'crawled');
        if ($totalPages === 0) {
            return 0;
        }

        // Calcola waste percentage
        $wastePages = $this->countWastePages($sessionId);
        $wastePercentage = ($wastePages / $totalPages) * 100;

        $score = 100.0;
        $score -= min(40, $severity['critical'] * 3);
        $score -= min(30, $severity['warning'] * 1.5);
        $score -= min(10, $severity['notice'] * 0.5);
        $score -= min(20, $wastePercentage * 0.4);

        return max(0, (int) round($score));
    }

    /**
     * Conta pagine "spreco": non-200 + thin + parametri senza canonical.
     */
    private function countWastePages(int $sessionId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(DISTINCT p.id) as cnt FROM cb_pages p
             WHERE p.session_id = ? AND p.status = 'crawled' AND (
                 p.http_status < 200 OR p.http_status >= 300
                 OR (p.word_count < 100 AND p.http_status = 200)
                 OR (p.has_parameters = 1 AND (p.canonical_url IS NULL OR p.canonical_url LIKE '%?%'))
             )",
            [$sessionId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Restituisce il label per lo score.
     */
    public static function getScoreLabel(int $score): string
    {
        if ($score >= 90) {
            return 'Eccellente';
        }
        if ($score >= 70) {
            return 'Buono';
        }
        if ($score >= 50) {
            return 'Migliorabile';
        }
        return 'Critico';
    }

    /**
     * Restituisce il colore CSS per lo score.
     */
    public static function getScoreColor(int $score): string
    {
        if ($score >= 90) {
            return 'emerald';
        }
        if ($score >= 70) {
            return 'blue';
        }
        if ($score >= 50) {
            return 'amber';
        }
        return 'red';
    }
}
