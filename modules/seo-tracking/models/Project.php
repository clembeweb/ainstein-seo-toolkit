<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Project Model
 * Gestisce la tabella st_projects
 */
class Project
{
    protected string $table = 'st_projects';

    /**
     * Trova progetto per ID
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::fetch($sql, $params);
    }

    /**
     * Tutti i progetti di un utente
     */
    public function allByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Progetti con statistiche
     */
    public function allWithStats(int $userId): array
    {
        $sql = "
            SELECT
                p.*,
                (SELECT COUNT(*) FROM st_keywords WHERE project_id = p.id) as keywords_count,
                (SELECT COUNT(*) FROM st_alerts WHERE project_id = p.id AND status = 'new') as alerts_count,
                gsc.property_url as gsc_property,
                ga4.property_name as ga4_property
            FROM {$this->table} p
            LEFT JOIN st_gsc_connections gsc ON p.id = gsc.project_id AND gsc.is_active = 1
            LEFT JOIN st_ga4_connections ga4 ON p.id = ga4.project_id AND ga4.is_active = 1
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ";

        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Progetto con tutte le info
     */
    public function findWithConnections(int $id, int $userId): ?array
    {
        $project = $this->find($id, $userId);

        if (!$project) {
            return null;
        }

        // GSC Connection
        $project['gsc_connection'] = Database::fetch(
            "SELECT * FROM st_gsc_connections WHERE project_id = ?",
            [$id]
        );

        // GA4 Connection
        $project['ga4_connection'] = Database::fetch(
            "SELECT id, project_id, property_id, property_name, is_active, last_sync_at, last_error, created_at
             FROM st_ga4_connections WHERE project_id = ?",
            [$id]
        );

        // Alert settings
        $project['alert_settings'] = Database::fetch(
            "SELECT * FROM st_alert_settings WHERE project_id = ?",
            [$id]
        );

        // Stats
        $project['stats'] = $this->getStats($id);

        return $project;
    }

    /**
     * Statistiche progetto
     */
    public function getStats(int $projectId): array
    {
        // Keywords count
        $keywordsCount = Database::count('st_keywords', 'project_id = ?', [$projectId]);

        // Last 7 days GSC data
        $gscStats = Database::fetch("
            SELECT
                SUM(total_clicks) as total_clicks,
                SUM(total_impressions) as total_impressions,
                AVG(avg_position) as avg_position,
                AVG(avg_ctr) as avg_ctr
            FROM st_gsc_daily
            WHERE project_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ", [$projectId]);

        // Last 7 days GA4 data
        $ga4Stats = Database::fetch("
            SELECT
                SUM(sessions) as total_sessions,
                SUM(revenue) as total_revenue,
                SUM(purchases) as total_purchases
            FROM st_ga4_daily
            WHERE project_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ", [$projectId]);

        // Keywords in top positions
        $topPositions = Database::fetch("
            SELECT
                SUM(CASE WHEN last_position <= 3 THEN 1 ELSE 0 END) as top3,
                SUM(CASE WHEN last_position <= 10 THEN 1 ELSE 0 END) as top10,
                SUM(CASE WHEN last_position <= 20 THEN 1 ELSE 0 END) as top20
            FROM st_keywords
            WHERE project_id = ? AND last_position IS NOT NULL
        ", [$projectId]);

        // Active alerts count
        $alertsCount = Database::count('st_alerts', 'project_id = ? AND status = ?', [$projectId, 'new']);

        return [
            'keywords_count' => $keywordsCount,
            'clicks_7d' => (int) ($gscStats['total_clicks'] ?? 0),
            'impressions_7d' => (int) ($gscStats['total_impressions'] ?? 0),
            'avg_position_7d' => round((float) ($gscStats['avg_position'] ?? 0), 1),
            'avg_ctr_7d' => round((float) ($gscStats['avg_ctr'] ?? 0) * 100, 2),
            'sessions_7d' => (int) ($ga4Stats['total_sessions'] ?? 0),
            'revenue_7d' => (float) ($ga4Stats['total_revenue'] ?? 0),
            'purchases_7d' => (int) ($ga4Stats['total_purchases'] ?? 0),
            'top3' => (int) ($topPositions['top3'] ?? 0),
            'top10' => (int) ($topPositions['top10'] ?? 0),
            'top20' => (int) ($topPositions['top20'] ?? 0),
            'alerts_count' => $alertsCount,
        ];
    }

    /**
     * Crea nuovo progetto
     */
    public function create(array $data): int
    {
        $projectId = Database::insert($this->table, $data);

        // Crea record alert settings di default
        Database::insert('st_alert_settings', ['project_id' => $projectId]);

        return $projectId;
    }

    /**
     * Aggiorna progetto
     */
    public function update(int $id, array $data, int $userId): bool
    {
        return Database::update($this->table, $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Elimina progetto
     */
    public function delete(int $id, int $userId): bool
    {
        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Aggiorna stato sync
     */
    public function updateSyncStatus(int $id, string $status): void
    {
        $data = ['sync_status' => $status];

        if ($status === 'completed') {
            $data['last_sync_at'] = date('Y-m-d H:i:s');
        }

        Database::update($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Aggiorna stato connessione GSC
     */
    public function setGscConnected(int $id, bool $connected): void
    {
        Database::update($this->table, ['gsc_connected' => $connected ? 1 : 0], 'id = ?', [$id]);
    }

    /**
     * Aggiorna stato connessione GA4
     */
    public function setGa4Connected(int $id, bool $connected): void
    {
        Database::update($this->table, ['ga4_connected' => $connected ? 1 : 0], 'id = ?', [$id]);
    }

    /**
     * Progetti con sync abilitato
     */
    public function getWithSyncEnabled(): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE sync_enabled = 1 ORDER BY last_sync_at ASC"
        );
    }

    /**
     * Progetti attivi per sync (alias per cron)
     */
    public function getActiveForSync(): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE sync_enabled = 1 AND (gsc_connected = 1 OR ga4_connected = 1) ORDER BY last_sync_at ASC"
        );
    }

    /**
     * Progetti con report AI abilitati per giorno specifico
     */
    public function getForWeeklyReport(int $dayOfWeek): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE ai_reports_enabled = 1 AND weekly_report_day = ?",
            [$dayOfWeek]
        );
    }

    /**
     * Progetti per report mensile
     */
    public function getForMonthlyReport(int $dayOfMonth): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE ai_reports_enabled = 1 AND monthly_report_day = ?",
            [$dayOfMonth]
        );
    }

    /**
     * Conta progetti utente
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * Normalizza dominio
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = rtrim($domain, '/');

        // Rimuovi protocollo se presente
        $domain = preg_replace('#^https?://#i', '', $domain);

        // Rimuovi www se presente
        $domain = preg_replace('#^www\.#i', '', $domain);

        return strtolower($domain);
    }
}
