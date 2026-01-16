<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * GscData Model
 * Gestisce la tabella st_gsc_data (dati storici GSC)
 */
class GscData
{
    protected string $table = 'st_gsc_data';

    /**
     * Upsert dati GSC
     */
    public function upsert(array $data): string
    {
        $sql = "
            INSERT INTO {$this->table}
                (project_id, date, query, page, country, device, clicks, impressions, ctr, position, keyword_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                clicks = VALUES(clicks),
                impressions = VALUES(impressions),
                ctr = VALUES(ctr),
                position = VALUES(position),
                keyword_id = COALESCE(VALUES(keyword_id), keyword_id)
        ";

        $stmt = Database::query($sql, [
            $data['project_id'],
            $data['date'],
            $data['query'] ?? '',
            $data['page'] ?? '',
            $data['country'] ?? 'all',
            $data['device'] ?? 'ALL',
            $data['clicks'] ?? 0,
            $data['impressions'] ?? 0,
            $data['ctr'] ?? 0,
            $data['position'] ?? 0,
            $data['keyword_id'] ?? null,
        ]);

        return $stmt->rowCount() === 1 ? 'inserted' : 'updated';
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
             ORDER BY date DESC, clicks DESC",
            [$projectId, $startDate, $endDate]
        );
    }

    /**
     * Top query per click
     */
    public function getTopQueries(int $projectId, string $startDate, string $endDate, int $limit = 50, int $minClicks = 0): array
    {
        $sql = "
            SELECT
                query,
                keyword_id,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(position) as avg_position,
                AVG(ctr) as avg_ctr,
                COUNT(DISTINCT page) as pages_count
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY query, keyword_id
            HAVING total_clicks >= ?
            ORDER BY total_clicks DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate, $minClicks, $limit]);
    }

    /**
     * Top pages per click
     */
    public function getTopPages(int $projectId, string $startDate, string $endDate, int $limit = 50): array
    {
        $sql = "
            SELECT
                page,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(position) as avg_position,
                AVG(ctr) as avg_ctr,
                COUNT(DISTINCT query) as queries_count
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY page
            ORDER BY total_clicks DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate, $limit]);
    }

    /**
     * Dati per keyword tracciata
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
     * Keyword che hanno portato click a una specifica pagina in una data
     * Usato per revenue attribution GA4
     */
    public function getKeywordsByPage(int $projectId, string $pageUrl, string $date): array
    {
        return Database::fetchAll(
            "SELECT query, clicks, impressions, position
             FROM {$this->table}
             WHERE project_id = ? AND page = ? AND date = ? AND clicks > 0
             ORDER BY clicks DESC",
            [$projectId, $pageUrl, $date]
        );
    }

    /**
     * Ultimi dati aggregati per keyword
     */
    public function getLatestForKeyword(int $keywordId): ?array
    {
        $sql = "
            SELECT
                AVG(position) as avg_position,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(ctr) as avg_ctr
            FROM {$this->table}
            WHERE keyword_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ";

        return Database::fetch($sql, [$keywordId]);
    }

    /**
     * Aggiorna keyword_id per query matchanti
     */
    public function updateKeywordMatch(int $projectId, int $keywordId, string $keyword): int
    {
        $sql = "
            UPDATE {$this->table}
            SET keyword_id = ?
            WHERE project_id = ? AND LOWER(query) = LOWER(?) AND keyword_id IS NULL
        ";

        $stmt = Database::query($sql, [$keywordId, $projectId, $keyword]);
        return $stmt->rowCount();
    }

    /**
     * Aggregati giornalieri
     */
    public function getDailyAggregates(int $projectId, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT
                date,
                SUM(clicks) as total_clicks,
                SUM(impressions) as total_impressions,
                AVG(ctr) as avg_ctr,
                AVG(position) as avg_position,
                COUNT(DISTINCT query) as unique_queries,
                COUNT(DISTINCT page) as unique_pages
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date ASC
        ";

        return Database::fetchAll($sql, [$projectId, $startDate, $endDate]);
    }

    /**
     * Query uniche nel periodo
     */
    public function getUniqueQueries(int $projectId, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT DISTINCT query
            FROM {$this->table}
            WHERE project_id = ? AND date BETWEEN ? AND ?
            ORDER BY query
        ";

        $results = Database::fetchAll($sql, [$projectId, $startDate, $endDate]);
        return array_column($results, 'query');
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

    /**
     * Ottiene posizioni per una specifica keyword nel date range
     */
    public function getKeywordPositions(int $projectId, string $keyword, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT
                date,
                query as keyword,
                position,
                clicks,
                impressions,
                ctr
            FROM {$this->table}
            WHERE project_id = ?
              AND LOWER(query) = LOWER(?)
              AND date BETWEEN ? AND ?
            ORDER BY date ASC
        ";

        return Database::fetchAll($sql, [$projectId, $keyword, $startDate, $endDate]);
    }
}
