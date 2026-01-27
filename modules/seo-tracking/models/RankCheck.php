<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * RankCheck Model
 * Gestisce la tabella st_rank_checks per lo storico verifiche SERP
 */
class RankCheck
{
    protected string $table = 'st_rank_checks';

    /**
     * Trova check per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Crea un nuovo record di check
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Trova tutti i check di un progetto (con limit)
     */
    public function findByProject(int $projectId, int $limit = 50, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ?
             ORDER BY checked_at DESC
             LIMIT ? OFFSET ?",
            [$projectId, $limit, $offset]
        );
    }

    /**
     * Trova lo storico di una keyword specifica
     */
    public function findByKeyword(int $projectId, string $keyword, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND keyword = ?
             ORDER BY checked_at DESC
             LIMIT ?",
            [$projectId, $keyword, $limit]
        );
    }

    /**
     * Ottieni l'ultimo check per ogni keyword del progetto
     */
    public function getLatestByProject(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT rc.*
             FROM {$this->table} rc
             INNER JOIN (
                 SELECT keyword, MAX(checked_at) as max_date
                 FROM {$this->table}
                 WHERE project_id = ?
                 GROUP BY keyword
             ) latest ON rc.keyword = latest.keyword AND rc.checked_at = latest.max_date
             WHERE rc.project_id = ?
             ORDER BY rc.serp_position IS NULL, rc.serp_position ASC",
            [$projectId, $projectId]
        );
    }

    /**
     * Conta i check totali di un progetto
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Conta i check di oggi per un utente (per limiti)
     */
    public function countTodayByUser(int $userId): int
    {
        return (int) Database::fetch(
            "SELECT COUNT(*) as cnt FROM {$this->table}
             WHERE user_id = ? AND DATE(checked_at) = CURDATE()",
            [$userId]
        )['cnt'] ?? 0;
    }

    /**
     * Ottieni statistiche dei check per progetto
     */
    public function getStats(int $projectId): array
    {
        $stats = Database::fetch(
            "SELECT
                COUNT(*) as total_checks,
                COUNT(DISTINCT keyword) as unique_keywords,
                AVG(CASE WHEN serp_position IS NOT NULL THEN serp_position END) as avg_position,
                SUM(CASE WHEN serp_position IS NOT NULL THEN 1 ELSE 0 END) as found_count,
                SUM(CASE WHEN serp_position IS NULL THEN 1 ELSE 0 END) as not_found_count,
                SUM(CASE WHEN serp_position <= 10 THEN 1 ELSE 0 END) as top10_count,
                SUM(CASE WHEN serp_position <= 3 THEN 1 ELSE 0 END) as top3_count
             FROM {$this->table}
             WHERE project_id = ?",
            [$projectId]
        );

        return [
            'total_checks' => (int) ($stats['total_checks'] ?? 0),
            'unique_keywords' => (int) ($stats['unique_keywords'] ?? 0),
            'avg_position' => $stats['avg_position'] ? round((float) $stats['avg_position'], 1) : null,
            'found_count' => (int) ($stats['found_count'] ?? 0),
            'not_found_count' => (int) ($stats['not_found_count'] ?? 0),
            'top10_count' => (int) ($stats['top10_count'] ?? 0),
            'top3_count' => (int) ($stats['top3_count'] ?? 0),
        ];
    }

    /**
     * Cerca check con filtri
     */
    public function search(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (!empty($filters['keyword'])) {
            $sql .= " AND keyword LIKE ?";
            $params[] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['device'])) {
            $sql .= " AND device = ?";
            $params[] = $filters['device'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(checked_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(checked_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (isset($filters['found_only']) && $filters['found_only']) {
            $sql .= " AND serp_position IS NOT NULL";
        }

        if (isset($filters['not_found_only']) && $filters['not_found_only']) {
            $sql .= " AND serp_position IS NULL";
        }

        $sql .= " ORDER BY checked_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int) $filters['offset'];
            }
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Conta check con filtri (per paginazione)
     */
    public function countWithFilters(int $projectId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (!empty($filters['keyword'])) {
            $sql .= " AND keyword LIKE ?";
            $params[] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['device'])) {
            $sql .= " AND device = ?";
            $params[] = $filters['device'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(checked_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(checked_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (isset($filters['found_only']) && $filters['found_only']) {
            $sql .= " AND serp_position IS NOT NULL";
        }

        if (isset($filters['not_found_only']) && $filters['not_found_only']) {
            $sql .= " AND serp_position IS NULL";
        }

        $result = Database::fetch($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Elimina check vecchi (pulizia)
     */
    public function deleteOlderThan(int $projectId, int $days): int
    {
        return Database::delete(
            $this->table,
            'project_id = ? AND checked_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$projectId, $days]
        );
    }

    /**
     * Ottieni le keyword più verificate
     */
    public function getTopCheckedKeywords(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT
                keyword,
                COUNT(*) as check_count,
                AVG(CASE WHEN serp_position IS NOT NULL THEN serp_position END) as avg_position,
                MAX(checked_at) as last_check
             FROM {$this->table}
             WHERE project_id = ?
             GROUP BY keyword
             ORDER BY check_count DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Confronta posizioni SERP vs GSC per una keyword
     */
    public function getPositionHistory(int $projectId, string $keyword, int $days = 30): array
    {
        return Database::fetchAll(
            "SELECT
                DATE(checked_at) as date,
                serp_position,
                gsc_position,
                position_diff,
                device
             FROM {$this->table}
             WHERE project_id = ? AND keyword = ?
               AND checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY checked_at ASC",
            [$projectId, $keyword, $days]
        );
    }

    /**
     * Raggruppa i rank check per URL SERP
     * Restituisce statistiche aggregate per ogni URL
     */
    public function getUrlsGrouped(int $projectId, array $filters = []): array
    {
        $sql = "SELECT
                    serp_url as url,
                    COUNT(DISTINCT keyword) as keyword_count,
                    ROUND(AVG(serp_position), 1) as avg_position,
                    MIN(serp_position) as best_position,
                    MAX(checked_at) as last_check,
                    COUNT(*) as total_checks
                FROM {$this->table}
                WHERE project_id = ?
                  AND serp_url IS NOT NULL
                  AND serp_position IS NOT NULL";
        $params = [$projectId];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(checked_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(checked_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND serp_url LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY serp_url";

        if (!empty($filters['min_keywords'])) {
            $sql .= " HAVING keyword_count >= ?";
            $params[] = (int) $filters['min_keywords'];
        }

        if (!empty($filters['max_position'])) {
            if (strpos($sql, 'HAVING') !== false) {
                $sql .= " AND avg_position <= ?";
            } else {
                $sql .= " HAVING avg_position <= ?";
            }
            $params[] = (float) $filters['max_position'];
        }

        $sql .= " ORDER BY keyword_count DESC, avg_position ASC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int) $filters['offset'];
            }
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Ottieni le keyword per una specifica URL
     * Restituisce l'ultimo check per ogni keyword
     */
    public function getKeywordsByUrl(int $projectId, string $url, array $filters = []): array
    {
        $sql = "SELECT
                    rc.keyword,
                    rc.serp_position,
                    rc.checked_at,
                    rc.location,
                    rc.device
                FROM {$this->table} rc
                INNER JOIN (
                    SELECT keyword, MAX(checked_at) as max_date
                    FROM {$this->table}
                    WHERE project_id = ?
                      AND serp_url = ?
                    GROUP BY keyword
                ) latest ON rc.keyword = latest.keyword AND rc.checked_at = latest.max_date
                WHERE rc.project_id = ?
                  AND rc.serp_url = ?";
        $params = [$projectId, $url, $projectId, $url];

        if (!empty($filters['device'])) {
            $sql .= " AND rc.device = ?";
            $params[] = $filters['device'];
        }

        if (!empty($filters['location'])) {
            $sql .= " AND rc.location = ?";
            $params[] = $filters['location'];
        }

        $sql .= " ORDER BY rc.serp_position ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Conta le URL distinte raggruppate (per paginazione)
     */
    public function countUrlsGrouped(int $projectId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM (
                    SELECT serp_url, COUNT(DISTINCT keyword) as keyword_count, AVG(serp_position) as avg_position
                    FROM {$this->table}
                    WHERE project_id = ?
                      AND serp_url IS NOT NULL
                      AND serp_position IS NOT NULL";
        $params = [$projectId];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(checked_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(checked_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND serp_url LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY serp_url";

        $havingClauses = [];
        if (!empty($filters['min_keywords'])) {
            $havingClauses[] = "keyword_count >= ?";
            $params[] = (int) $filters['min_keywords'];
        }

        if (!empty($filters['max_position'])) {
            $havingClauses[] = "avg_position <= ?";
            $params[] = (float) $filters['max_position'];
        }

        if (!empty($havingClauses)) {
            $sql .= " HAVING " . implode(" AND ", $havingClauses);
        }

        $sql .= ") as url_counts";

        $result = Database::fetch($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }
}
