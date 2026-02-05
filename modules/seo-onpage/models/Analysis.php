<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * Analysis Model for SEO Onpage Optimizer Module
 * Manages sop_analyses table
 */
class Analysis
{
    protected string $table = 'sop_analyses';

    /**
     * Find analysis by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get latest analysis for page
     */
    public function getLatestForPage(int $pageId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE page_id = ? ORDER BY created_at DESC LIMIT 1",
            [$pageId]
        );
    }

    /**
     * Get all analyses for page
     */
    public function allByPage(int $pageId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE page_id = ? ORDER BY created_at DESC",
            [$pageId]
        );
    }

    /**
     * Get all analyses for project
     */
    public function allByProject(int $projectId, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT a.*, p.url
             FROM {$this->table} a
             JOIN sop_pages p ON a.page_id = p.id
             WHERE a.project_id = ?
             ORDER BY a.created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Create new analysis
     */
    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Create analysis from DataForSEO response
     */
    public function createFromApiResponse(int $projectId, int $pageId, int $userId, array $apiData, string $device = 'desktop', float $creditsUsed = 0): int
    {
        // Extract page data from API response
        $pageData = $apiData['page_data'] ?? [];
        $checks = $apiData['checks'] ?? [];

        // Count issues by severity
        $issuesCritical = 0;
        $issuesWarning = 0;
        $issuesNotice = 0;

        foreach ($checks as $check) {
            if (($check['passed'] ?? true) === false) {
                $severity = $this->mapSeverity($check['priority'] ?? 'low');
                if ($severity === 'critical') {
                    $issuesCritical++;
                } elseif ($severity === 'warning') {
                    $issuesWarning++;
                } else {
                    $issuesNotice++;
                }
            }
        }

        $data = [
            'project_id' => $projectId,
            'page_id' => $pageId,
            'user_id' => $userId,
            'device' => $device,
            'onpage_score' => $apiData['onpage_score'] ?? null,

            // Meta
            'meta_title' => $pageData['meta']['title'] ?? null,
            'meta_title_length' => isset($pageData['meta']['title']) ? strlen($pageData['meta']['title']) : null,
            'meta_description' => $pageData['meta']['description'] ?? null,
            'meta_description_length' => isset($pageData['meta']['description']) ? strlen($pageData['meta']['description']) : null,
            'canonical_url' => $pageData['canonical'] ?? null,

            // Content
            'content_word_count' => $pageData['content']['plain_text_word_count'] ?? null,
            'content_readability' => $pageData['content']['automated_readability_index'] ?? null,

            // Headings
            'h1_count' => count($pageData['htags']['h1'] ?? []),
            'h1_content' => ($pageData['htags']['h1'][0] ?? null),
            'h2_count' => count($pageData['htags']['h2'] ?? []),
            'h3_count' => count($pageData['htags']['h3'] ?? []),

            // Images
            'images_count' => $pageData['page_resource_stats']['images_count'] ?? 0,
            'images_without_alt' => $pageData['page_resource_stats']['images_without_alt_count'] ?? 0,
            'images_without_title' => $pageData['page_resource_stats']['images_without_title_count'] ?? 0,

            // Links
            'internal_links_count' => $pageData['internal_links_count'] ?? 0,
            'external_links_count' => $pageData['external_links_count'] ?? 0,
            'broken_links_count' => $pageData['broken_links_count'] ?? 0,

            // Technical
            'is_indexable' => ($pageData['is_indexable'] ?? true) ? 1 : 0,
            'has_schema_markup' => ($pageData['schema_types'] ?? null) ? 1 : 0,
            'has_hreflang' => ($pageData['hreflang'] ?? null) ? 1 : 0,

            // Core Web Vitals (if available)
            'lcp_score' => $pageData['page_timing']['lcp'] ?? null,
            'cls_score' => $pageData['page_timing']['cls'] ?? null,
            'ttfb_score' => $pageData['page_timing']['time_to_first_byte'] ?? null,

            // Performance
            'page_size_bytes' => $pageData['size'] ?? null,
            'dom_complete_ms' => $pageData['page_timing']['dom_complete'] ?? null,

            // Issues count
            'issues_critical' => $issuesCritical,
            'issues_warning' => $issuesWarning,
            'issues_notice' => $issuesNotice,

            // Raw data
            'checks_json' => json_encode($checks),
            'raw_data' => json_encode($apiData),

            // Credits
            'credits_used' => $creditsUsed,
            'api_cost' => $apiData['cost'] ?? 0,
        ];

        return $this->create($data);
    }

    /**
     * Map DataForSEO priority to severity
     */
    private function mapSeverity(string $priority): string
    {
        return match ($priority) {
            'high', 'critical' => 'critical',
            'medium' => 'warning',
            default => 'notice',
        };
    }

    /**
     * Get average score for project
     */
    public function getAverageScoreForProject(int $projectId): ?float
    {
        $result = Database::fetch(
            "SELECT AVG(onpage_score) as avg_score
             FROM {$this->table}
             WHERE project_id = ? AND onpage_score IS NOT NULL",
            [$projectId]
        );

        return $result['avg_score'] ? round($result['avg_score'], 1) : null;
    }

    /**
     * Get score distribution for project
     */
    public function getScoreDistribution(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT
                CASE
                    WHEN onpage_score >= 90 THEN 'excellent'
                    WHEN onpage_score >= 70 THEN 'good'
                    WHEN onpage_score >= 50 THEN 'average'
                    ELSE 'poor'
                END as category,
                COUNT(*) as count
             FROM {$this->table}
             WHERE project_id = ? AND onpage_score IS NOT NULL
             GROUP BY category
             ORDER BY FIELD(category, 'excellent', 'good', 'average', 'poor')",
            [$projectId]
        );
    }

    /**
     * Delete analysis
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }
}
