<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * KeywordGroup Model
 * Gestisce la tabella st_keyword_groups e st_keyword_group_members
 */
class KeywordGroup
{
    protected string $table = 'st_keyword_groups';
    protected string $membersTable = 'st_keyword_group_members';

    /**
     * Trova gruppo per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Trova gruppo per ID con verifica progetto
     */
    public function findByProject(int $id, int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND project_id = ?",
            [$id, $projectId]
        );
    }

    /**
     * Trova gruppo per nome
     */
    public function findByName(int $projectId, string $name): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND name = ?",
            [$projectId, $name]
        );
    }

    /**
     * Tutti i gruppi di un progetto
     */
    public function allByProject(int $projectId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE project_id = ?";
        $params = [$projectId];

        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Gruppi con statistiche keyword
     */
    public function allWithStats(int $projectId): array
    {
        $sql = "
            SELECT
                g.*,
                COUNT(DISTINCT m.keyword_id) as keyword_count,
                AVG(k.last_position) as avg_position,
                SUM(k.last_clicks) as total_clicks,
                SUM(k.last_impressions) as total_impressions,
                SUM(CASE WHEN k.last_position <= 3 THEN 1 ELSE 0 END) as top3_count,
                SUM(CASE WHEN k.last_position <= 10 THEN 1 ELSE 0 END) as top10_count
            FROM {$this->table} g
            LEFT JOIN {$this->membersTable} m ON g.id = m.group_id
            LEFT JOIN st_keywords k ON m.keyword_id = k.id
            WHERE g.project_id = ?
            GROUP BY g.id
            ORDER BY g.sort_order ASC, g.name ASC
        ";

        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Crea gruppo
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Aggiorna gruppo
     */
    public function update(int $id, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Elimina gruppo
     */
    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Conta gruppi per progetto
     */
    public function countByProject(int $projectId): int
    {
        return Database::count($this->table, 'project_id = ?', [$projectId]);
    }

    // =========================================
    // GESTIONE MEMBRI (keyword in gruppo)
    // =========================================

    /**
     * Aggiunge keyword al gruppo
     */
    public function addKeyword(int $groupId, int $keywordId): bool
    {
        try {
            Database::insert($this->membersTable, [
                'group_id' => $groupId,
                'keyword_id' => $keywordId,
            ]);
            return true;
        } catch (\Exception $e) {
            // Ignora duplicati
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                return true;
            }
            throw $e;
        }
    }

    /**
     * Aggiunge multiple keyword al gruppo
     */
    public function addKeywords(int $groupId, array $keywordIds): int
    {
        $added = 0;
        foreach ($keywordIds as $keywordId) {
            if ($this->addKeyword($groupId, (int)$keywordId)) {
                $added++;
            }
        }
        return $added;
    }

    /**
     * Rimuove keyword dal gruppo
     */
    public function removeKeyword(int $groupId, int $keywordId): bool
    {
        return Database::delete(
            $this->membersTable,
            'group_id = ? AND keyword_id = ?',
            [$groupId, $keywordId]
        ) > 0;
    }

    /**
     * Rimuove tutte le keyword dal gruppo
     */
    public function clearKeywords(int $groupId): int
    {
        return Database::delete($this->membersTable, 'group_id = ?', [$groupId]);
    }

    /**
     * Sincronizza keyword del gruppo (replace all)
     */
    public function syncKeywords(int $groupId, array $keywordIds): void
    {
        $this->clearKeywords($groupId);
        $this->addKeywords($groupId, $keywordIds);
    }

    /**
     * Ottieni keyword del gruppo
     */
    public function getKeywords(int $groupId): array
    {
        $sql = "
            SELECT k.*
            FROM st_keywords k
            JOIN {$this->membersTable} m ON k.id = m.keyword_id
            WHERE m.group_id = ?
            ORDER BY k.last_position IS NULL, k.last_position ASC
        ";

        return Database::fetchAll($sql, [$groupId]);
    }

    /**
     * Ottieni keyword del gruppo con metriche
     */
    public function getKeywordsWithMetrics(int $groupId, int $days = 7): array
    {
        $sql = "
            SELECT
                k.*,
                m.added_at as group_added_at,
                kp.avg_position as period_position,
                kp.total_clicks as period_clicks,
                kp.total_impressions as period_impressions,
                kp.position_change
            FROM st_keywords k
            JOIN {$this->membersTable} m ON k.id = m.keyword_id
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
            WHERE m.group_id = ?
            ORDER BY k.last_position IS NULL, k.last_position ASC
        ";

        return Database::fetchAll($sql, [$days, $groupId]);
    }

    /**
     * Conta keyword nel gruppo
     */
    public function countKeywords(int $groupId): int
    {
        return Database::count($this->membersTable, 'group_id = ?', [$groupId]);
    }

    /**
     * Gruppi di una keyword
     */
    public function getGroupsForKeyword(int $keywordId): array
    {
        $sql = "
            SELECT g.*
            FROM {$this->table} g
            JOIN {$this->membersTable} m ON g.id = m.group_id
            WHERE m.keyword_id = ?
            ORDER BY g.name ASC
        ";

        return Database::fetchAll($sql, [$keywordId]);
    }

    /**
     * Verifica se keyword appartiene a gruppo
     */
    public function hasKeyword(int $groupId, int $keywordId): bool
    {
        $count = Database::count(
            $this->membersTable,
            'group_id = ? AND keyword_id = ?',
            [$groupId, $keywordId]
        );
        return $count > 0;
    }

    /**
     * Statistiche aggregate del gruppo
     */
    public function getStats(int $groupId): array
    {
        $sql = "
            SELECT
                COUNT(DISTINCT m.keyword_id) as total_keywords,
                AVG(k.last_position) as avg_position,
                SUM(k.last_clicks) as total_clicks,
                SUM(k.last_impressions) as total_impressions,
                AVG(k.last_ctr) as avg_ctr,
                SUM(CASE WHEN k.last_position <= 3 THEN 1 ELSE 0 END) as top3_count,
                SUM(CASE WHEN k.last_position <= 10 THEN 1 ELSE 0 END) as top10_count,
                SUM(CASE WHEN k.last_position > 10 AND k.last_position <= 20 THEN 1 ELSE 0 END) as top20_count,
                SUM(CASE WHEN k.last_position > 20 OR k.last_position IS NULL THEN 1 ELSE 0 END) as beyond20_count
            FROM {$this->membersTable} m
            LEFT JOIN st_keywords k ON m.keyword_id = k.id
            WHERE m.group_id = ?
        ";

        $result = Database::fetch($sql, [$groupId]);

        return [
            'total_keywords' => (int)($result['total_keywords'] ?? 0),
            'avg_position' => round((float)($result['avg_position'] ?? 0), 1),
            'total_clicks' => (int)($result['total_clicks'] ?? 0),
            'total_impressions' => (int)($result['total_impressions'] ?? 0),
            'avg_ctr' => round((float)($result['avg_ctr'] ?? 0), 2),
            'top3_count' => (int)($result['top3_count'] ?? 0),
            'top10_count' => (int)($result['top10_count'] ?? 0),
            'top20_count' => (int)($result['top20_count'] ?? 0),
            'beyond20_count' => (int)($result['beyond20_count'] ?? 0),
        ];
    }

    /**
     * Colori predefiniti per gruppi
     */
    public static function getDefaultColors(): array
    {
        return [
            '#006e96', // Primary Ainstein
            '#00a3d9', // Accent
            '#004d69', // Secondary
            '#ec4899', // Pink
            '#ef4444', // Red
            '#f97316', // Orange
            '#eab308', // Yellow
            '#22c55e', // Green
            '#14b8a6', // Teal
            '#3b82f6', // Blue
        ];
    }
}
