<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * CrawlSession Model
 *
 * Gestisce sessioni di crawl con state machine
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

    private const TABLE = 'cb_crawl_sessions';

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
            'config' => json_encode($config),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Aggiorna sessione
     */
    public function update(int $id, array $data): int
    {
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
     * Imposta status "stopping"
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
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Completa sessione con calcolo score
     */
    public function complete(int $id): int
    {
        return $this->update($id, [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Segna sessione come fallita
     */
    public function fail(int $id, string $error): int
    {
        return $this->update($id, [
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'completed_at' => date('Y-m-d H:i:s'),
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
     * Incrementa pagine crawlate (+1)
     */
    public function incrementPagesCrawled(int $id): bool
    {
        return Database::query(
            "UPDATE " . self::TABLE . " SET pages_crawled = pages_crawled + 1 WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Incrementa issues trovati
     */
    public function incrementIssuesFound(int $id, int $count = 1): bool
    {
        return Database::query(
            "UPDATE " . self::TABLE . " SET issues_found = issues_found + ? WHERE id = ?",
            [$count, $id]
        ) !== false;
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
     * Verifica se sessione e attiva
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
}
