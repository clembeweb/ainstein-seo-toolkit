<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Keyword Model
 * Gestisce la tabella st_keywords
 */
class Keyword
{
    protected string $table = 'st_keywords';

    /**
     * Trova keyword per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova keyword per ID con verifica progetto
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        );
    }

    /**
     * Tutte le keyword di un progetto
     */
    public function allByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if (!empty($filters['group'])) {
            $sql .= " AND keyword_group = ?";
            $params[] = $filters['group'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND keyword LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $orderBy = $filters['order_by'] ?? 'last_position';
        $orderDir = strtoupper($filters['order_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Gestione NULL per ordinamento posizione
        if ($orderBy === 'last_position') {
            $sql .= " ORDER BY last_position IS NULL, last_position {$orderDir}";
        } else {
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
        }

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int) $filters['offset'];
            }
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Keyword con dati posizione recenti
     */
    public function allWithPositions(int $projectId, int $days = 7): array
    {
        $sql = "
            SELECT
                k.*,
                kp.avg_position as current_position,
                kp.total_clicks as period_clicks,
                kp.total_impressions as period_impressions,
                kp.position_change
            FROM {$this->table} k
            LEFT JOIN (
                SELECT
                    keyword_id,
                    AVG(avg_position) as avg_position,
                    SUM(total_clicks) as total_clicks,
                    SUM(total_impressions) as total_impressions,
                    (
                        SELECT position_change
                        FROM st_keyword_positions
                        WHERE keyword_id = kp2.keyword_id
                        ORDER BY date DESC
                        LIMIT 1
                    ) as position_change
                FROM st_keyword_positions kp2
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY keyword_id
            ) kp ON k.id = kp.keyword_id
            WHERE k.project_id = ?
            ORDER BY k.last_position IS NULL, k.last_position ASC
        ";

        return Database::fetchAll($sql, [$days, $projectId]);
    }

    /**
     * Gruppi keyword distinti
     */
    public function getGroups(int $projectId): array
    {
        $sql = "SELECT DISTINCT keyword_group FROM {$this->table}
                WHERE project_id = ? AND keyword_group IS NOT NULL
                ORDER BY keyword_group";
        $results = Database::fetchAll($sql, [$projectId]);

        return array_column($results, 'keyword_group');
    }

    /**
     * Crea keyword
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Crea multiple keyword
     */
    public function createMany(int $projectId, array $keywords): int
    {
        $inserted = 0;

        foreach ($keywords as $kw) {
            $data = [
                'project_id' => $projectId,
                'keyword' => trim($kw['keyword']),
                'keyword_group' => $kw['group'] ?? null,
                'is_brand' => $kw['is_brand'] ?? false,
                'target_url' => $kw['target_url'] ?? null,
                'priority' => $kw['priority'] ?? 'medium',
            ];

            try {
                Database::insert($this->table, $data);
                $inserted++;
            } catch (\Exception $e) {
                // Ignora duplicati
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }

        return $inserted;
    }

    /**
     * Aggiorna keyword
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina keyword
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna cache posizione
     */
    public function updatePositionCache(int $id, array $data): void
    {
        Database::update($this->table, [
            'last_position' => $data['position'],
            'last_clicks' => $data['clicks'],
            'last_impressions' => $data['impressions'],
            'last_ctr' => $data['ctr'],
            'last_updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * Conta keyword per progetto
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    /**
     * Top keyword per click
     */
    public function getTopByClicks(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND last_clicks IS NOT NULL
             ORDER BY last_clicks DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Keyword con maggiori variazioni posizione
     */
    public function getTopMovers(int $projectId, int $limit = 10): array
    {
        $sql = "
            SELECT
                k.*,
                kp.position_change,
                kp.avg_position as current_position
            FROM {$this->table} k
            JOIN st_keyword_positions kp ON k.id = kp.keyword_id
            WHERE k.project_id = ?
              AND kp.date = (SELECT MAX(date) FROM st_keyword_positions WHERE keyword_id = k.id)
              AND kp.position_change IS NOT NULL
            ORDER BY ABS(kp.position_change) DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Keyword per priorità alta con alert attivi
     */
    public function getHighPriorityWithAlerts(int $projectId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND priority = 'high' AND alert_enabled = 1",
            [$projectId]
        );
    }

    /**
     * Cerca keyword esistente
     */
    public function findByKeyword(int $projectId, string $keyword): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND keyword = ?",
            [$projectId, $keyword]
        );
    }

    /**
     * Statistiche keyword per progetto
     */
    public function getStats(int $projectId): array
    {
        $total = Database::count($this->table, 'project_id = ?', [$projectId]);

        $top10 = Database::count(
            $this->table,
            'project_id = ? AND last_position IS NOT NULL AND last_position <= 10',
            [$projectId]
        );

        $top3 = Database::count(
            $this->table,
            'project_id = ? AND last_position IS NOT NULL AND last_position <= 3',
            [$projectId]
        );

        $withClicks = Database::count(
            $this->table,
            'project_id = ? AND last_clicks > 0',
            [$projectId]
        );

        return [
            'total_keywords' => $total,
            'keywords_top3' => $top3,
            'keywords_top10' => $top10,
            'keywords_with_clicks' => $withClicks,
        ];
    }
}
