<?php

namespace Modules\ContentCreator\Models;

use Core\Database;

/**
 * Job Model for Content Creator Module
 *
 * Gestisce la tabella cc_jobs per il tracking
 * dei job in background (scrape, generate, cms_push).
 * Pattern basato su ai-content/ScrapeJob.php
 */
class Job
{
    protected string $table = 'cc_jobs';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';
    public const STATUS_CANCELLED = 'cancelled';

    // Type constants
    public const TYPE_SCRAPE = 'scrape';
    public const TYPE_GENERATE = 'generate';
    public const TYPE_CMS_PUSH = 'cms_push';

    /**
     * Find job by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Find job with user validation
     */
    public function findByUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Get active job for project (running or pending)
     */
    public function getActiveForProject(int $projectId, string $type = self::TYPE_SCRAPE): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND type = ? AND status IN ('pending', 'running')
             ORDER BY created_at DESC LIMIT 1",
            [$projectId, $type]
        );
    }

    /**
     * Get latest job for project
     */
    public function getLatestForProject(int $projectId, string $type = self::TYPE_SCRAPE): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND type = ?
             ORDER BY created_at DESC LIMIT 1",
            [$projectId, $type]
        );
    }

    /**
     * Get jobs by project with pagination
     */
    public function getByProject(int $projectId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE project_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Create new job
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, [
            'project_id' => (int) $data['project_id'],
            'user_id' => (int) $data['user_id'],
            'type' => $data['type'] ?? self::TYPE_SCRAPE,
            'status' => self::STATUS_PENDING,
            'items_requested' => (int) ($data['items_requested'] ?? 0),
            'items_completed' => 0,
            'items_failed' => 0,
            'credits_used' => 0,
        ]);
    }

    /**
     * Start job execution
     */
    public function start(int $id): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Update job progress
     */
    public function updateProgress(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['current_item'])) {
            $updateData['current_item'] = $data['current_item'];
        }
        if (isset($data['current_item_id'])) {
            $updateData['current_item_id'] = (int) $data['current_item_id'];
        }
        if (isset($data['items_completed'])) {
            $updateData['items_completed'] = (int) $data['items_completed'];
        }
        if (isset($data['items_failed'])) {
            $updateData['items_failed'] = (int) $data['items_failed'];
        }
        if (isset($data['credits_used'])) {
            $updateData['credits_used'] = (float) $data['credits_used'];
        }

        if (empty($updateData)) {
            return false;
        }

        return Database::update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Increment completed count
     */
    public function incrementCompleted(int $id): bool
    {
        return Database::query(
            "UPDATE {$this->table}
             SET items_completed = items_completed + 1
             WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Increment failed count
     */
    public function incrementFailed(int $id): bool
    {
        return Database::query(
            "UPDATE {$this->table}
             SET items_failed = items_failed + 1
             WHERE id = ?",
            [$id]
        ) !== false;
    }

    /**
     * Add credits used
     */
    public function addCreditsUsed(int $id, float $credits): bool
    {
        return Database::query(
            "UPDATE {$this->table}
             SET credits_used = credits_used + ?
             WHERE id = ?",
            [$credits, $id]
        ) !== false;
    }

    /**
     * Complete job successfully
     */
    public function complete(int $id): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_COMPLETED,
            'current_item' => null,
            'current_item_id' => null,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark job as error
     */
    public function markError(int $id, string $error): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_ERROR,
            'error_message' => $error,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Cancel job
     */
    public function cancel(int $id): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_CANCELLED,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND status IN (?, ?)', [$id, self::STATUS_PENDING, self::STATUS_RUNNING]) > 0;
    }

    /**
     * Reset stuck jobs (running for too long) for a project
     *
     * @param int $projectId Project ID
     * @param int $maxMinutes Maximum minutes a job should run
     * @return int Number of jobs reset
     */
    public function resetStuckJobs(int $projectId, int $maxMinutes = 30): int
    {
        $cutoffTime = date('Y-m-d H:i:s', time() - ($maxMinutes * 60));

        return Database::update($this->table, [
            'status' => self::STATUS_ERROR,
            'error_message' => "Timeout - job rimasto in esecuzione per piu di {$maxMinutes} minuti",
            'completed_at' => date('Y-m-d H:i:s'),
        ], "project_id = ? AND status = 'running' AND started_at < ?", [$projectId, $cutoffTime]);
    }

    /**
     * Check if job is still running
     */
    public function isRunning(int $id): bool
    {
        $job = $this->find($id);
        return $job && $job['status'] === self::STATUS_RUNNING;
    }

    /**
     * Check if job was cancelled
     */
    public function isCancelled(int $id): bool
    {
        $job = $this->find($id);
        return $job && $job['status'] === self::STATUS_CANCELLED;
    }

    /**
     * Get job progress percentage
     */
    public function getProgress(int $id): int
    {
        $job = $this->find($id);
        if (!$job || $job['items_requested'] == 0) {
            return 0;
        }

        $done = $job['items_completed'] + $job['items_failed'];
        return min(100, round(($done / $job['items_requested']) * 100));
    }

    /**
     * Get job for API response
     */
    public function getJobResponse(int $id): array
    {
        $job = $this->find($id);
        if (!$job) {
            return ['error' => 'Job non trovato'];
        }

        return [
            'id' => (int) $job['id'],
            'status' => $job['status'],
            'type' => $job['type'],
            'items_requested' => (int) $job['items_requested'],
            'items_completed' => (int) $job['items_completed'],
            'items_failed' => (int) $job['items_failed'],
            'current_item' => $job['current_item'],
            'credits_used' => round((float) $job['credits_used'], 2),
            'progress' => $this->getProgress($id),
            'error_message' => $job['error_message'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
        ];
    }

    /**
     * Get job statistics for project
     */
    public function getStatsForProject(int $projectId): array
    {
        $stats = Database::fetch(
            "SELECT
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_jobs,
                SUM(items_completed) as total_items,
                SUM(credits_used) as total_credits
             FROM {$this->table}
             WHERE project_id = ?",
            [$projectId]
        );

        return [
            'total_jobs' => (int) ($stats['total_jobs'] ?? 0),
            'completed_jobs' => (int) ($stats['completed_jobs'] ?? 0),
            'failed_jobs' => (int) ($stats['failed_jobs'] ?? 0),
            'total_items' => (int) ($stats['total_items'] ?? 0),
            'total_credits' => (float) ($stats['total_credits'] ?? 0),
        ];
    }

    /**
     * Clean old completed/cancelled jobs (keep last N per project)
     */
    public function cleanOldJobs(int $keepPerProject = 20): int
    {
        $deleted = 0;

        // Get all projects
        $projects = Database::fetchAll("SELECT DISTINCT project_id FROM {$this->table}");

        foreach ($projects as $project) {
            $projectId = $project['project_id'];

            // Get IDs to keep
            $keepIds = Database::fetchAll(
                "SELECT id FROM {$this->table}
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
                    $this->table,
                    "project_id = ? AND id NOT IN ({$placeholders}) AND status IN ('completed', 'cancelled', 'error')",
                    $params
                );
            }
        }

        return $deleted;
    }
}
