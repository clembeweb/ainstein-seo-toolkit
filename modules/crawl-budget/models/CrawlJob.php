<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * CrawlJob Model
 *
 * Gestisce cb_crawl_jobs per tracciamento job di crawl in background
 */
class CrawlJob
{
    private const TABLE = 'cb_crawl_jobs';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';
    public const STATUS_CANCELLED = 'cancelled';


    /**
     * Trova job per ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . " WHERE id = ?",
            [$id]
        );
    }

    /**
     * Trova job attivo per progetto (pending o running)
     */
    public function findActiveByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . "
             WHERE project_id = ? AND status IN ('pending', 'running')
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Trova ultimo job per progetto
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
     * Lista job per progetto
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
     * Crea nuovo job
     */
    public function create(array $data): int
    {
        $config = isset($data['config'])
            ? (is_string($data['config']) ? $data['config'] : json_encode($data['config']))
            : null;

        return Database::insert(self::TABLE, [
            'project_id' => (int) $data['project_id'],
            'session_id' => (int) $data['session_id'],
            'user_id' => (int) $data['user_id'],

            'status' => self::STATUS_PENDING,
            'config' => $config,
            'items_total' => (int) ($data['items_total'] ?? 0),
            'items_completed' => 0,
            'items_failed' => 0,
        ]);
    }

    /**
     * Avvia esecuzione job
     */
    public function start(int $id): bool
    {
        return Database::update(self::TABLE, [
            'status' => self::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna campi job
     */
    public function update(int $id, array $data): bool
    {
        return Database::update(self::TABLE, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Aggiorna progresso job
     */
    public function updateProgress(int $id, int $completed, ?string $currentItem = null): bool
    {
        $data = [
            'items_completed' => $completed,
        ];

        if ($currentItem !== null) {
            $data['current_item'] = $currentItem;
        }

        return Database::update(self::TABLE, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Incrementa contatore completati (+1)
     */
    public function incrementCompleted(int $id): bool
    {
        return Database::query(
            "UPDATE " . self::TABLE . "
             SET items_completed = items_completed + 1
             WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Incrementa contatore falliti (+1)
     */
    public function incrementFailed(int $id): bool
    {
        return Database::query(
            "UPDATE " . self::TABLE . "
             SET items_failed = items_failed + 1
             WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Completa job con successo
     */
    public function complete(int $id): bool
    {
        return Database::update(self::TABLE, [
            'status' => self::STATUS_COMPLETED,
            'current_item' => null,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Segna job come errore
     */
    public function markError(int $id, string $message): bool
    {
        return Database::update(self::TABLE, [
            'status' => self::STATUS_ERROR,
            'error_message' => $message,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Annulla job (solo se pending o running)
     */
    public function cancel(int $id): bool
    {
        return Database::update(self::TABLE, [
            'status' => self::STATUS_CANCELLED,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND status IN (?, ?)', [
            $id,
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
        ]) > 0;
    }

    /**
     * Verifica se job e' stato annullato (lettura fresca dal DB)
     *
     * CRITICO: usato nei loop SSE, deve SEMPRE leggere dal DB
     */
    public function isCancelled(int $id): bool
    {
        $job = Database::fetch(
            "SELECT status FROM " . self::TABLE . " WHERE id = ?",
            [$id]
        );
        return $job && $job['status'] === self::STATUS_CANCELLED;
    }

    /**
     * Verifica se job e' ancora in esecuzione
     */
    public function isRunning(int $id): bool
    {
        $job = Database::fetch(
            "SELECT status FROM " . self::TABLE . " WHERE id = ?",
            [$id]
        );
        return $job && $job['status'] === self::STATUS_RUNNING;
    }

    /**
     * Risposta formattata per SSE/polling
     */
    public function getJobResponse(int $id): array
    {
        $job = $this->find($id);
        if (!$job) {
            return ['error' => 'Job non trovato'];
        }

        $total = (int) $job['items_total'];
        $completed = (int) $job['items_completed'];
        $failed = (int) $job['items_failed'];
        $done = $completed + $failed;
        $progress = round(($done / max($total, 1)) * 100, 1);

        return [
            'id' => (int) $job['id'],
            'status' => $job['status'],
            'items_total' => $total,
            'items_completed' => $completed,
            'items_failed' => $failed,
            'current_item' => $job['current_item'],
            'progress' => min(100, $progress),
            'error_message' => $job['error_message'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
        ];
    }

    /**
     * Reset job bloccati in running da troppo tempo
     *
     * @param int $minutesThreshold Minuti massimi di esecuzione
     * @return int Numero di job resettati
     */
    public function resetStuckJobs(int $minutesThreshold = 30): int
    {
        $cutoffTime = date('Y-m-d H:i:s', time() - ($minutesThreshold * 60));

        return Database::update(self::TABLE, [
            'status' => self::STATUS_ERROR,
            'error_message' => "Timeout - job rimasto in esecuzione per oltre {$minutesThreshold} minuti",
            'completed_at' => date('Y-m-d H:i:s'),
        ], "status = 'running' AND started_at < ?", [$cutoffTime]);
    }

    /**
     * Elimina job vecchi completati/annullati/errore
     */
    public function cleanOldJobs(int $keepPerProject = 20): int
    {
        $deleted = 0;

        $projects = Database::fetchAll(
            "SELECT DISTINCT project_id FROM " . self::TABLE
        );

        foreach ($projects as $project) {
            $projectId = $project['project_id'];

            $keepIds = Database::fetchAll(
                "SELECT id FROM " . self::TABLE . "
                 WHERE project_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?",
                [$projectId, $keepPerProject]
            );

            $keepIdsList = array_column($keepIds, 'id');

            if (!empty($keepIdsList)) {
                $placeholders = implode(',', array_fill(0, count($keepIdsList), '?'));
                $params = array_merge([$projectId], $keepIdsList);

                $deleted += Database::delete(
                    self::TABLE,
                    "project_id = ? AND id NOT IN ({$placeholders}) AND status IN ('completed', 'cancelled', 'error')",
                    $params
                );
            }
        }

        return $deleted;
    }
}
