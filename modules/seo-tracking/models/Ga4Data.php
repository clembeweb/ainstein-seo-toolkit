<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Ga4Data Model
 * Gestisce la tabella st_ga4_data (dati GA4 per landing page)
 */
class Ga4Data
{
    protected string $table = 'st_ga4_data';

    /**
     * Upsert dati GA4
     */
    public function upsert(array $data): bool
    {
        $sql = "
            INSERT INTO {$this->table}
                (project_id, date, landing_page, source_medium, country, device_category,
                 sessions, users, new_users, avg_session_duration, bounce_rate, engagement_rate,
                 add_to_carts, begin_checkouts, purchases, revenue)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                sessions = VALUES(sessions),
                users = VALUES(users),
                new_users = VALUES(new_users),
                avg_session_duration = VALUES(avg_session_duration),
                bounce_rate = VALUES(bounce_rate),
                engagement_rate = VALUES(engagement_rate),
                add_to_carts = VALUES(add_to_carts),
                begin_checkouts = VALUES(begin_checkouts),
                purchases = VALUES(purchases),
                revenue = VALUES(revenue)
        ";

        Database::query($sql, [
            $data['project_id'],
            $data['date'],
            $data['landing_page'],
            $data['source_medium'] ?? 'google / organic',
            $data['country'] ?? null,
            $data['device_category'] ?? null,
            $data['sessions'] ?? 0,
            $data['users'] ?? 0,
            $data['new_users'] ?? 0,
            $data['avg_session_duration'] ?? 0,
            $data['bounce_rate'] ?? 0,
            $data['engagement_rate'] ?? 0,
            $data['add_to_carts'] ?? 0,
            $data['begin_checkouts'] ?? 0,
            $data['purchases'] ?? 0,
            $data['revenue'] ?? 0,
        ]);

        return true;
    }

    /**
     * Dati per progetto e data
     */
    public function getByProjectDate(int $projectId, string $date): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE project_id = ? AND date = ?",
            [$projectId, $date]
        );
    }

    /**
     * Dati per range date
     */
    public function getByDateRange(int $projectId, string $startDate, string $endDate): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND date BETWEEN ? AND ?
             ORDER BY date ASC, landing_page ASC",
            [$projectId, $startDate, $endDate]
        );
    }

    /**
     * Top landing pages per revenue
     */
    public function getTopPagesByRevenue(int $projectId, string $startDate, string $endDate, int $limit = 20): array
    {
        $sql = "
            SELECT
                landing_page,
                SUM(sessions) as total_sessions,
                SUM(users) as total_users,
                SUM(purchases) as total_purchases,
                SUM(revenue) as total_revenue,
                SUM(revenue) / NULLIF(SUM(sessions), 0) as revenue_per_session
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY landing_page
            ORDER BY total_revenue DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate, $limit]);
    }

    /**
     * Top landing pages per sessioni
     */
    public function getTopPagesBySessions(int $projectId, string $startDate, string $endDate, int $limit = 20): array
    {
        $sql = "
            SELECT
                landing_page,
                SUM(sessions) as total_sessions,
                SUM(users) as total_users,
                AVG(bounce_rate) as avg_bounce_rate,
                AVG(engagement_rate) as avg_engagement_rate,
                SUM(revenue) as total_revenue
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY landing_page
            ORDER BY total_sessions DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate, $limit]);
    }

    /**
     * Aggregati per data
     */
    public function getDailyAggregates(int $projectId, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT
                date,
                SUM(sessions) as sessions,
                SUM(users) as users,
                SUM(new_users) as new_users,
                AVG(avg_session_duration) as avg_session_duration,
                AVG(bounce_rate) as bounce_rate,
                AVG(engagement_rate) as engagement_rate,
                SUM(add_to_carts) as add_to_carts,
                SUM(begin_checkouts) as begin_checkouts,
                SUM(purchases) as purchases,
                SUM(revenue) as revenue
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
