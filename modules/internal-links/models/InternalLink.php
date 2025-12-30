<?php

namespace Modules\InternalLinks\Models;

use Core\Database;

/**
 * InternalLink Model
 *
 * Manages il_internal_links table and related operations
 */
class InternalLink
{
    protected string $table = 'il_internal_links';

    /**
     * Find link by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get links for a project with pagination and filters
     */
    public function getByProject(int $projectId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where = ['il.project_id = ?'];
        $params = [$projectId];

        // Apply filters
        if (!empty($filters['source_url_id'])) {
            $where[] = 'il.source_url_id = ?';
            $params[] = $filters['source_url_id'];
        }

        if (!empty($filters['is_internal'])) {
            $where[] = 'il.is_internal = ?';
            $params[] = $filters['is_internal'] === 'yes' ? 1 : 0;
        }

        if (!empty($filters['juice_flow'])) {
            $where[] = 'il.ai_juice_flow = ?';
            $params[] = $filters['juice_flow'];
        }

        if (!empty($filters['min_score'])) {
            $where[] = 'il.ai_relevance_score >= ?';
            $params[] = (int) $filters['min_score'];
        }

        if (!empty($filters['max_score'])) {
            $where[] = 'il.ai_relevance_score <= ?';
            $params[] = (int) $filters['max_score'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(il.anchor_text LIKE ? OR il.destination_url LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (isset($filters['analyzed']) && $filters['analyzed'] !== '') {
            if ($filters['analyzed'] === 'yes') {
                $where[] = 'il.ai_relevance_score IS NOT NULL';
            } else {
                $where[] = 'il.ai_relevance_score IS NULL';
            }
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} il WHERE " . str_replace('il.', '', implode(' AND ', $where));
        // Rebuild for count (simpler)
        $countParams = [$projectId];
        $total = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE project_id = ?", $countParams)['total'] ?? 0;

        // Get records with source URL info
        $sql = "
            SELECT il.*, u.url as source_url, u.keyword as source_keyword
            FROM {$this->table} il
            JOIN il_urls u ON il.source_url_id = u.id
            WHERE {$whereClause}
            ORDER BY il.id DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;

        $records = Database::fetchAll($sql, $params);

        return [
            'data' => $records,
            'total' => (int) $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Get links by source URL
     */
    public function getBySourceUrl(int $sourceUrlId): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE source_url_id = ?
            ORDER BY link_position ASC
        ";

        return Database::fetchAll($sql, [$sourceUrlId]);
    }

    /**
     * Get incoming links to a URL
     */
    public function getIncomingLinks(int $projectId, string $url): array
    {
        $normalizedUrl = strtolower(rtrim($url, '/'));

        $sql = "
            SELECT il.*, u.url as source_url, u.keyword as source_keyword
            FROM {$this->table} il
            JOIN il_urls u ON il.source_url_id = u.id
            WHERE il.project_id = ?
            AND LOWER(TRIM(TRAILING '/' FROM il.destination_url)) = ?
            ORDER BY il.id ASC
        ";

        return Database::fetchAll($sql, [$projectId, $normalizedUrl]);
    }

    /**
     * Delete a single link by ID
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Delete links for a source URL
     */
    public function deleteBySourceUrl(int $sourceUrlId): int
    {
        return Database::delete($this->table, 'source_url_id = ?', [$sourceUrlId]);
    }

    /**
     * Delete links by anchor text in a project
     */
    public function deleteByAnchor(int $projectId, string $anchorText): int
    {
        return Database::delete($this->table, 'project_id = ? AND anchor_text_clean = ?', [$projectId, $anchorText]);
    }

    /**
     * Delete all links in project
     */
    public function deleteByProject(int $projectId): int
    {
        return Database::delete($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Bulk insert links
     */
    public function bulkInsert(array $links): int
    {
        if (empty($links)) {
            return 0;
        }

        $inserted = 0;

        foreach ($links as $link) {
            try {
                Database::insert($this->table, [
                    'project_id' => $link['project_id'],
                    'source_url_id' => $link['source_url_id'],
                    'destination_url' => $link['destination_url'],
                    'anchor_text' => $link['anchor_text'],
                    'anchor_text_clean' => $link['anchor_text_clean'],
                    'link_position' => $link['link_position'],
                    'source_block' => $link['source_block'] ?? null,
                    'is_internal' => $link['is_internal'] ? 1 : 0,
                    'is_valid' => $link['is_valid'] ? 1 : 0,
                ]);
                $inserted++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $inserted;
    }

    /**
     * Get links pending AI analysis
     */
    public function getPendingAnalysis(int $projectId, int $limit = 50): array
    {
        $sql = "
            SELECT il.*, u.url as source_url, u.keyword as source_keyword, u.content_html
            FROM {$this->table} il
            JOIN il_urls u ON il.source_url_id = u.id
            WHERE il.project_id = ?
            AND il.ai_relevance_score IS NULL
            AND il.is_internal = 1
            ORDER BY il.source_url_id ASC, il.link_position ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Update AI analysis results
     */
    public function updateAnalysis(int $id, array $analysis): bool
    {
        return Database::update($this->table, [
            'ai_relevance_score' => $analysis['relevance_score'] ?? null,
            'ai_anchor_quality' => $analysis['anchor_quality'] ?? null,
            'ai_juice_flow' => $analysis['juice_flow'] ?? null,
            'ai_notes' => $analysis['notes'] ?? null,
            'ai_suggestions' => $analysis['suggestion'] ?? null,
            'analyzed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Get score distribution
     */
    public function getScoreDistribution(int $projectId): array
    {
        $sql = "
            SELECT
                CASE
                    WHEN ai_relevance_score BETWEEN 1 AND 3 THEN 'low'
                    WHEN ai_relevance_score BETWEEN 4 AND 6 THEN 'medium'
                    WHEN ai_relevance_score BETWEEN 7 AND 10 THEN 'high'
                    ELSE 'unanalyzed'
                END as score_range,
                COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ?
            GROUP BY score_range
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        $distribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'unanalyzed' => 0,
        ];

        foreach ($results as $row) {
            $distribution[$row['score_range']] = (int) $row['count'];
        }

        return $distribution;
    }

    /**
     * Get juice flow distribution
     */
    public function getJuiceFlowDistribution(int $projectId): array
    {
        $sql = "
            SELECT
                COALESCE(ai_juice_flow, 'unanalyzed') as juice_flow,
                COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ?
            GROUP BY ai_juice_flow
        ";

        $results = Database::fetchAll($sql, [$projectId]);

        $distribution = [
            'optimal' => 0,
            'good' => 0,
            'weak' => 0,
            'poor' => 0,
            'orphan' => 0,
            'unanalyzed' => 0,
        ];

        foreach ($results as $row) {
            $key = $row['juice_flow'] ?: 'unanalyzed';
            if (isset($distribution[$key])) {
                $distribution[$key] = (int) $row['count'];
            }
        }

        return $distribution;
    }

    /**
     * Get most used anchors
     */
    public function getMostUsedAnchors(int $projectId, int $limit = 20): array
    {
        $sql = "
            SELECT anchor_text_clean as anchor, COUNT(*) as count
            FROM {$this->table}
            WHERE project_id = ?
            AND anchor_text_clean IS NOT NULL
            AND anchor_text_clean != ''
            GROUP BY anchor_text_clean
            ORDER BY count DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Find duplicate anchors pointing to different URLs
     */
    public function findDuplicateAnchors(int $projectId): array
    {
        $sql = "
            SELECT
                anchor_text_clean as anchor,
                COUNT(DISTINCT LOWER(TRIM(TRAILING '/' FROM destination_url))) as unique_destinations,
                GROUP_CONCAT(DISTINCT destination_url SEPARATOR '|||') as destinations
            FROM {$this->table}
            WHERE project_id = ?
            AND anchor_text_clean IS NOT NULL
            AND anchor_text_clean != ''
            AND is_internal = 1
            GROUP BY anchor_text_clean
            HAVING unique_destinations > 1
            ORDER BY unique_destinations DESC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Get orphan pages (pages with no incoming links)
     */
    public function getOrphanPages(int $projectId): array
    {
        $sql = "
            SELECT u.id, u.url, u.keyword
            FROM il_urls u
            WHERE u.project_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM {$this->table} il
                WHERE il.project_id = ?
                AND LOWER(TRIM(TRAILING '/' FROM il.destination_url)) = LOWER(TRIM(TRAILING '/' FROM u.url))
            )
            ORDER BY u.url ASC
        ";

        return Database::fetchAll($sql, [$projectId, $projectId]);
    }

    /**
     * Get link statistics
     */
    public function getStats(int $projectId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(DISTINCT CONCAT(source_url_id, '-', destination_url)) as unique_links,
                SUM(CASE WHEN is_internal = 1 THEN 1 ELSE 0 END) as internal,
                SUM(CASE WHEN is_internal = 0 THEN 1 ELSE 0 END) as external
            FROM {$this->table}
            WHERE project_id = ?
        ";

        $result = Database::fetch($sql, [$projectId]);

        $analysisSql = "
            SELECT
                COUNT(*) as total_internal,
                SUM(CASE WHEN ai_relevance_score IS NOT NULL THEN 1 ELSE 0 END) as analyzed,
                AVG(ai_relevance_score) as avg_score
            FROM {$this->table}
            WHERE project_id = ? AND is_internal = 1
        ";

        $analysisResult = Database::fetch($analysisSql, [$projectId]);

        // Count orphan pages (URLs with no inbound links)
        $orphanSql = "
            SELECT COUNT(*) as orphan_count
            FROM il_urls u
            WHERE u.project_id = ?
            AND u.status = 'scraped'
            AND NOT EXISTS (
                SELECT 1 FROM {$this->table} l
                WHERE l.project_id = u.project_id
                AND LOWER(TRIM(TRAILING '/' FROM l.destination_url)) = LOWER(TRIM(TRAILING '/' FROM u.url))
                AND l.is_internal = 1
            )
        ";
        $orphanResult = Database::fetch($orphanSql, [$projectId]);

        $internal = (int) ($result['internal'] ?? 0);
        $analyzed = (int) ($analysisResult['analyzed'] ?? 0);

        return [
            'total_links' => (int) ($result['total'] ?? 0),
            'unique_links' => (int) ($result['unique_links'] ?? 0),
            'internal' => $internal,
            'external' => (int) ($result['external'] ?? 0),
            'analyzed' => $analyzed,
            'pending' => max(0, $internal - $analyzed),
            'avg_score' => $analysisResult['avg_score'] ? round((float) $analysisResult['avg_score'], 1) : null,
            'orphan_pages' => (int) ($orphanResult['orphan_count'] ?? 0),
        ];
    }

    /**
     * Clean anchor text
     */
    public static function cleanAnchor(?string $anchor): ?string
    {
        if ($anchor === null) {
            return null;
        }

        $anchor = preg_replace('/\s+/', ' ', $anchor);
        $anchor = trim($anchor);

        if (strlen($anchor) > 500) {
            $anchor = substr($anchor, 0, 497) . '...';
        }

        return $anchor ?: null;
    }
}
