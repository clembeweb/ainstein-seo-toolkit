<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * GscDaily Model
 * Gestisce la tabella st_gsc_daily (aggregati giornalieri GSC)
 */
class GscDaily
{
    protected string $table = 'st_gsc_daily';

    /**
     * Upsert aggregato giornaliero
     */
    public function upsert(array $data): void
    {
        $sql = "
            INSERT INTO {$this->table}
                (project_id, date, total_clicks, total_impressions, avg_ctr, avg_position,
                 unique_queries, unique_pages, clicks_change, impressions_change, position_change)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_clicks = VALUES(total_clicks),
                total_impressions = VALUES(total_impressions),
                avg_ctr = VALUES(avg_ctr),
                avg_position = VALUES(avg_position),
                unique_queries = VALUES(unique_queries),
                unique_pages = VALUES(unique_pages),
                clicks_change = VALUES(clicks_change),
                impressions_change = VALUES(impressions_change),
                position_change = VALUES(position_change)
        ";

        Database::query($sql, [
            $data['project_id'],
            $data['date'],
            $data['total_clicks'] ?? 0,
            $data['total_impressions'] ?? 0,
            $data['avg_ctr'] ?? 0,
            $data['avg_position'] ?? 0,
            $data['unique_queries'] ?? 0,
            $data['unique_pages'] ?? 0,
            $data['clicks_change'] ?? null,
            $data['impressions_change'] ?? null,
            $data['position_change'] ?? null,
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
                SUM(total_clicks) as total_clicks,
                SUM(total_impressions) as total_impressions,
                AVG(avg_ctr) as avg_ctr,
                AVG(avg_position) as avg_position
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
            'clicks_change_pct' => $this->calcChangePercent($current['total_clicks'] ?? 0, $previous['total_clicks'] ?? 0),
            'impressions_change_pct' => $this->calcChangePercent($current['total_impressions'] ?? 0, $previous['total_impressions'] ?? 0),
            'position_change' => ($previous['avg_position'] ?? 0) - ($current['avg_position'] ?? 0),
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
     * Sommario per periodo (alias di getSum per compatibilita)
     */
    public function getSummary(int $projectId, string $startDate, string $endDate): array
    {
        $data = $this->getSum($projectId, $startDate, $endDate);
        return $data ?: [
            'total_clicks' => 0,
            'total_impressions' => 0,
            'avg_ctr' => 0,
            'avg_position' => 0,
        ];
    }
}
