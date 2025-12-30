<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * KeywordRevenue Model
 * Gestisce la tabella st_keyword_revenue (attribuzione revenue)
 */
class KeywordRevenue
{
    protected string $table = 'st_keyword_revenue';

    /**
     * Upsert attribuzione revenue
     */
    public function upsert(array $data): void
    {
        $sql = "
            INSERT INTO {$this->table}
                (project_id, keyword_id, date, query, landing_page,
                 clicks, impressions, position,
                 sessions, revenue, purchases, add_to_carts,
                 revenue_per_click, conversion_rate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                clicks = VALUES(clicks),
                impressions = VALUES(impressions),
                position = VALUES(position),
                sessions = VALUES(sessions),
                revenue = VALUES(revenue),
                purchases = VALUES(purchases),
                add_to_carts = VALUES(add_to_carts),
                revenue_per_click = VALUES(revenue_per_click),
                conversion_rate = VALUES(conversion_rate)
        ";

        Database::query($sql, [
            $data['project_id'],
            $data['keyword_id'] ?? null,
            $data['date'],
            $data['query'],
            $data['landing_page'],
            $data['clicks'] ?? 0,
            $data['impressions'] ?? 0,
            $data['position'] ?? 0,
            $data['sessions'] ?? 0,
            $data['revenue'] ?? 0,
            $data['purchases'] ?? 0,
            $data['add_to_carts'] ?? 0,
            $data['revenue_per_click'] ?? null,
            $data['conversion_rate'] ?? null,
        ]);
    }

    /**
     * Top keyword per revenue
     */
    public function getTopByRevenue(int $projectId, string $startDate, string $endDate, int $limit = 20): array
    {
        $sql = "
            SELECT
                query,
                keyword_id,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(position) as avg_position,
                SUM(revenue) as total_revenue,
                SUM(purchases) as total_purchases,
                SUM(revenue) / NULLIF(SUM(clicks), 0) as revenue_per_click,
                SUM(purchases) / NULLIF(SUM(clicks), 0) as conversion_rate
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY query, keyword_id
            ORDER BY total_revenue DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate, $limit]);
    }

    /**
     * Revenue per keyword tracciata
     */
    public function getByKeyword(int $keywordId, string $startDate, string $endDate): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE keyword_id = ? AND date BETWEEN ? AND ?
             ORDER BY date DESC",
            [$keywordId, $startDate, $endDate]
        );
    }

    /**
     * Totale revenue per keyword nel periodo
     */
    public function getTotalRevenueByKeyword(int $keywordId, string $startDate, string $endDate): float
    {
        $result = Database::fetch(
            "SELECT COALESCE(SUM(revenue), 0) as total
             FROM {$this->table}
             WHERE keyword_id = ? AND date BETWEEN ? AND ?",
            [$keywordId, $startDate, $endDate]
        );

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Top landing page per revenue
     */
    public function getTopPagesByRevenue(int $projectId, string $startDate, string $endDate, int $limit = 20): array
    {
        $sql = "
            SELECT
                landing_page,
                COUNT(DISTINCT query) as unique_keywords,
                SUM(clicks) as total_clicks,
                SUM(revenue) as total_revenue,
                SUM(purchases) as total_purchases,
                SUM(revenue) / NULLIF(SUM(clicks), 0) as revenue_per_click
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY landing_page
            ORDER BY total_revenue DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate, $limit]);
    }

    /**
     * Aggregati giornalieri revenue
     */
    public function getDailyRevenue(int $projectId, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT
                date,
                SUM(clicks) as total_clicks,
                SUM(revenue) as total_revenue,
                SUM(purchases) as total_purchases,
                COUNT(DISTINCT query) as unique_keywords
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date ASC
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate]);
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
