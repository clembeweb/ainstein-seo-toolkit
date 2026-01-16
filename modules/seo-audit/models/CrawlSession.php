<?php

namespace Modules\SeoAudit\Models;

use Core\Database;

/**
 * CrawlSession Model
 *
 * Gestisce sessioni di crawl con supporto stop/resume
 */
class CrawlSession
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_STOPPING = 'stopping';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    private const TABLE = 'sa_crawl_sessions';

    /**
     * Trova sessione per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . " WHERE id = ?",
            [$id]
        );
    }

    /**
     * Trova sessione attiva per progetto
     */
    public function findActiveByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . "
             WHERE project_id = ? AND status IN ('pending', 'running', 'paused', 'stopping')
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Trova ultima sessione per progetto
     */
    public function findLatestByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . "
             WHERE project_id = ?
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Crea nuova sessione
     */
    public function create(int $projectId, array $config = []): int
    {
        return Database::insert(self::TABLE, [
            'project_id' => $projectId,
            'status' => self::STATUS_PENDING,
            'max_pages' => $config['max_pages'] ?? 500,
            'crawl_mode' => $config['crawl_mode'] ?? 'both',
            'respect_robots' => $config['respect_robots'] ?? 1,
            'include_external' => $config['include_external'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Aggiorna sessione
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update(self::TABLE, $data, 'id = ?', [$id]);
    }

    /**
     * Avvia sessione
     */
    public function start(int $id): int
    {
        return $this->update($id, [
            'status' => self::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Imposta status "stopping" (segnale per il crawler di fermarsi)
     */
    public function requestStop(int $id): int
    {
        return $this->update($id, [
            'status' => self::STATUS_STOPPING,
        ]);
    }

    /**
     * Ferma sessione definitivamente
     */
    public function stop(int $id): int
    {
        return $this->update($id, [
            'status' => self::STATUS_STOPPED,
            'stopped_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Completa sessione con calcolo stats storico
     */
    public function complete(int $id): int
    {
        $session = $this->find($id);
        if (!$session) {
            return 0;
        }

        $projectId = $session['project_id'];

        // Calcola stats issues per questa sessione
        $stats = Database::fetch("
            SELECT
                COALESCE(SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END), 0) as critical,
                COALESCE(SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END), 0) as warning,
                COALESCE(SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END), 0) as notice
            FROM sa_issues
            WHERE session_id = ?
        ", [$id]);

        // Calcola health score (formula bilanciata)
        $pagesCount = max(1, $session['pages_crawled'] ?: 1);
        $critical = $stats['critical'] ?? 0;
        $warning = $stats['warning'] ?? 0;
        $notice = $stats['notice'] ?? 0;

        // Penalità: critical pesa molto, ma con diminishing returns
        $criticalPenalty = min($critical * 5, 40); // max 40 punti persi per critical
        $warningPenalty = min($warning * 1, 30);   // max 30 punti persi per warning
        $noticePenalty = min($notice * 0.2, 10);   // max 10 punti persi per notice

        $healthScore = max(0, round(100 - $criticalPenalty - $warningPenalty - $noticePenalty));

        // Aggiorna sessione con stats
        $result = $this->update($id, [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => date('Y-m-d H:i:s'),
            'health_score' => $healthScore,
            'critical_count' => $stats['critical'] ?? 0,
            'warning_count' => $stats['warning'] ?? 0,
            'notice_count' => $stats['notice'] ?? 0,
        ]);

        // Aggiorna anche health_score del progetto
        Database::update('sa_projects', [
            'health_score' => $healthScore,
        ], 'id = ?', [$projectId]);

        return $result;
    }

    /**
     * Segna sessione come fallita
     */
    public function fail(int $id, string $error): int
    {
        return $this->update($id, [
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'last_error_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Aggiorna progresso
     */
    public function updateProgress(int $id, int $pagesCrawled, ?string $currentUrl = null, ?int $issuesFound = null): int
    {
        $data = ['pages_crawled' => $pagesCrawled];

        if ($currentUrl !== null) {
            $data['current_url'] = $currentUrl;
        }

        if ($issuesFound !== null) {
            $data['issues_found'] = $issuesFound;
        }

        return $this->update($id, $data);
    }

    /**
     * Imposta pagine trovate
     */
    public function setPagesFound(int $id, int $count): int
    {
        return $this->update($id, ['pages_found' => $count]);
    }

    /**
     * Verifica se sessione deve fermarsi
     */
    public function shouldStop(int $id): bool
    {
        $session = $this->find($id);
        return $session && $session['status'] === self::STATUS_STOPPING;
    }

    /**
     * Verifica se sessione e' attiva
     */
    public function isActive(int $id): bool
    {
        $session = $this->find($id);
        return $session && in_array($session['status'], [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_PAUSED,
        ]);
    }

    /**
     * Ottieni statistiche sessione
     */
    public function getStats(int $id): array
    {
        $session = $this->find($id);

        if (!$session) {
            return [];
        }

        $progress = $session['pages_found'] > 0
            ? round(($session['pages_crawled'] / $session['pages_found']) * 100, 1)
            : 0;

        return [
            'id' => $session['id'],
            'status' => $session['status'],
            'pages_found' => $session['pages_found'],
            'pages_crawled' => $session['pages_crawled'],
            'issues_found' => $session['issues_found'],
            'current_url' => $session['current_url'],
            'progress_percent' => $progress,
            'started_at' => $session['started_at'],
            'elapsed_seconds' => $session['started_at']
                ? time() - strtotime($session['started_at'])
                : 0,
        ];
    }

    /**
     * Lista sessioni per progetto
     */
    public function listByProject(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM " . self::TABLE . "
             WHERE project_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Elimina sessioni vecchie (oltre N giorni)
     */
    public function cleanOld(int $projectId, int $daysOld = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return Database::delete(
            self::TABLE,
            'project_id = ? AND created_at < ? AND status IN (?, ?, ?)',
            [$projectId, $cutoff, self::STATUS_COMPLETED, self::STATUS_STOPPED, self::STATUS_FAILED]
        );
    }
}
