<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * KeywordPosition Model
 * Gestisce la tabella st_keyword_positions (snapshot giornalieri)
 */
class KeywordPosition
{
    protected string $table = 'st_keyword_positions';

    /**
     * Upsert posizione giornaliera
     */
    public function upsert(array $data): void
    {
        $sql = "
            INSERT INTO {$this->table}
                (project_id, keyword_id, date, avg_position, best_position,
                 total_clicks, total_impressions, avg_ctr,
                 position_change, clicks_change, impressions_change,
                 top_pages, country_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                avg_position = VALUES(avg_position),
                best_position = VALUES(best_position),
                total_clicks = VALUES(total_clicks),
                total_impressions = VALUES(total_impressions),
                avg_ctr = VALUES(avg_ctr),
                position_change = VALUES(position_change),
                clicks_change = VALUES(clicks_change),
                impressions_change = VALUES(impressions_change),
                top_pages = VALUES(top_pages),
                country_data = VALUES(country_data)
        ";

        Database::query($sql, [
            $data['project_id'],
            $data['keyword_id'],
            $data['date'],
            $data['avg_position'],
            $data['best_position'] ?? null,
            $data['total_clicks'] ?? 0,
            $data['total_impressions'] ?? 0,
            $data['avg_ctr'] ?? 0,
            $data['position_change'] ?? null,
            $data['clicks_change'] ?? null,
            $data['impressions_change'] ?? null,
            isset($data['top_pages']) ? json_encode($data['top_pages']) : null,
            isset($data['country_data']) ? json_encode($data['country_data']) : null,
        ]);
    }

    /**
     * Storico posizioni per keyword
     */
    public function getByKeyword(int $keywordId, string $startDate, string $endDate): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE keyword_id = ? AND date BETWEEN ? AND ?
             ORDER BY date ASC",
            [$keywordId, $startDate, $endDate]
        );
    }

    /**
     * Ultime N posizioni per keyword
     */
    public function getLastN(int $keywordId, int $n = 30): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE keyword_id = ?
             ORDER BY date DESC
             LIMIT ?",
            [$keywordId, $n]
        );
    }

    /**
     * Ultime 2 posizioni (per calcolo variazione)
     */
    public function getLastTwo(int $keywordId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE keyword_id = ?
             ORDER BY date DESC
             LIMIT 2",
            [$keywordId]
        );
    }

    /**
     * Posizioni per progetto in una data
     */
    public function getByProjectDate(int $projectId, string $date): array
    {
        return Database::fetchAll(
            "SELECT kp.*, k.keyword, k.priority, k.keyword_group
             FROM {$this->table} kp
             JOIN st_keywords k ON kp.keyword_id = k.id
             WHERE kp.project_id = ? AND kp.date = ?
             ORDER BY kp.avg_position ASC",
            [$projectId, $date]
        );
    }

    /**
     * Top movers (maggiori variazioni)
     */
    public function getTopMovers(int $projectId, string $date, int $limit = 10): array
    {
        $sql = "
            SELECT kp.*, k.keyword, k.priority
            FROM {$this->table} kp
            JOIN st_keywords k ON kp.keyword_id = k.id
            WHERE kp.project_id = ? AND kp.date = ?
              AND kp.position_change IS NOT NULL
            ORDER BY ABS(kp.position_change) DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $date, $limit]);
    }

    /**
     * Keyword migliorate
     */
    public function getImproved(int $projectId, string $date, int $limit = 10): array
    {
        $sql = "
            SELECT kp.*, k.keyword, k.priority
            FROM {$this->table} kp
            JOIN st_keywords k ON kp.keyword_id = k.id
            WHERE kp.project_id = ? AND kp.date = ?
              AND kp.position_change > 0
            ORDER BY kp.position_change DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $date, $limit]);
    }

    /**
     * Keyword peggiorate
     */
    public function getDeclined(int $projectId, string $date, int $limit = 10): array
    {
        $sql = "
            SELECT kp.*, k.keyword, k.priority
            FROM {$this->table} kp
            JOIN st_keywords k ON kp.keyword_id = k.id
            WHERE kp.project_id = ? AND kp.date = ?
              AND kp.position_change < 0
            ORDER BY kp.position_change ASC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $date, $limit]);
    }

    /**
     * Elimina dati vecchi
     */
    public function deleteOldData(int $projectId, int $monthsToKeep = 16): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$monthsToKeep} months"));

        $stmt = Database::query(
            "DELETE FROM {$this->table} WHERE project_id = ? AND date < ?",
            [$projectId, $cutoffDate]
        );

        return $stmt->rowCount();
    }
}
