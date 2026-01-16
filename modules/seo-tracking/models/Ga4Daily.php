<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Ga4Daily Model
 * Gestisce la tabella st_ga4_daily (aggregati giornalieri GA4)
 */
class Ga4Daily
{
    protected string $table = 'st_ga4_daily';

    /**
     * Upsert aggregato giornaliero
     */
    public function upsert(array $data): void
    {
        $sql = "
            INSERT INTO {$this->table}
                (project_id, date, sessions, users, new_users,
                 avg_session_duration, bounce_rate, engagement_rate,
                 add_to_carts, begin_checkouts, purchases, revenue,
                 sessions_change, revenue_change)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                revenue = VALUES(revenue),
                sessions_change = VALUES(sessions_change),
                revenue_change = VALUES(revenue_change)
        ";

        Database::query($sql, [
            $data['project_id'],
            $data['date'],
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
            $data['sessions_change'] ?? null,
            $data['revenue_change'] ?? null,
        ]);
    }

    /**
     * Dati per range date
     */
    public function getByDateRange(int $projectId, string $startDate, string $endDate): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND date BETWEEN ? AND ?
             ORDER BY date ASC",
            [$projectId, $startDate, $endDate]
        );
    }

    /**
     * Somma metriche per periodo
     */
    public function getSum(int $projectId, string $startDate, string $endDate): ?array
    {
        return Database::fetch(
            "SELECT
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
             WHERE project_id = ? AND date BETWEEN ? AND ?",
            [$projectId, $startDate, $endDate]
        );
    }

    /**
     * Ultimo record
     */
    public function getLatest(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE project_id = ?
             ORDER BY date DESC
             LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Confronto periodi
     */
    public function comparePeriods(int $projectId, string $currentStart, string $currentEnd, string $previousStart, string $previousEnd): array
    {
        $current = $this->getSum($projectId, $currentStart, $currentEnd);
        $previous = $this->getSum($projectId, $previousStart, $previousEnd);

        return [
            'current' => $current,
            'previous' => $previous,
            'sessions_change_pct' => $this->calcChangePercent($current['sessions'] ?? 0, $previous['sessions'] ?? 0),
            'revenue_change_pct' => $this->calcChangePercent($current['revenue'] ?? 0, $previous['revenue'] ?? 0),
            'purchases_change_pct' => $this->calcChangePercent($current['purchases'] ?? 0, $previous['purchases'] ?? 0),
        ];
    }

    /**
     * Calcola variazione percentuale
     */
    private function calcChangePercent($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Revenue totale nel periodo
     */
    public function getTotalRevenue(int $projectId, string $startDate, string $endDate): float
    {
        $result = Database::fetch(
            "SELECT COALESCE(SUM(revenue), 0) as total
             FROM {$this->table}
             WHERE project_id = ? AND date BETWEEN ? AND ?",
            [$projectId, $startDate, $endDate]
        );

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Sommario per periodo (alias di getSum per compatibilita)
     */
    public function getSummary(int $projectId, string $startDate, string $endDate): array
    {
        $data = $this->getSum($projectId, $startDate, $endDate);
        return $data ?: [
            'sessions' => 0,
            'users' => 0,
            'new_users' => 0,
            'purchases' => 0,
            'revenue' => 0,
            'bounce_rate' => 0,
            'engagement_rate' => 0,
        ];
    }
}
