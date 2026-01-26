<?php

namespace Modules\AiContent\Models;

use Core\Database;

/**
 * ProcessJob Model
 *
 * Manages aic_process_jobs table for tracking
 * both cron and manual article generation jobs
 */
class ProcessJob
{
    protected string $table = 'aic_process_jobs';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';
    public const STATUS_CANCELLED = 'cancelled';

    // Type constants
    public const TYPE_CRON = 'cron';
    public const TYPE_MANUAL = 'manual';

    // Step constants
    public const STEP_PENDING = 'pending';
    public const STEP_SERP = 'serp';
    public const STEP_SCRAPING = 'scraping';
    public const STEP_BRIEF = 'brief';
    public const STEP_ARTICLE = 'article';
    public const STEP_SAVING = 'saving';
    public const STEP_DONE = 'done';

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
    public function getActiveForProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE project_id = ? AND status IN ('pending', 'running')
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Static: Find active job for project (for CRON dispatcher)
     */
    public static function findActiveByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM aic_process_jobs
             WHERE project_id = ? AND status IN ('pending', 'running')
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Get latest job for project
     */
    public function getLatestForProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE project_id = ?
             ORDER BY created_at DESC LIMIT 1",
            [$projectId]
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
            'type' => $data['type'] ?? self::TYPE_MANUAL,
            'status' => self::STATUS_PENDING,
            'keywords_requested' => (int) ($data['keywords_requested'] ?? 0),
            'keywords_completed' => 0,
            'keywords_failed' => 0,
            'current_step' => self::STEP_PENDING,
            'articles_generated' => 0,
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

        if (isset($data['current_queue_id'])) {
            $updateData['current_queue_id'] = $data['current_queue_id'];
        }
        if (isset($data['current_keyword'])) {
            $updateData['current_keyword'] = $data['current_keyword'];
        }
        if (isset($data['current_step'])) {
            $updateData['current_step'] = $data['current_step'];
        }
        if (isset($data['keywords_completed'])) {
            $updateData['keywords_completed'] = (int) $data['keywords_completed'];
        }
        if (isset($data['keywords_failed'])) {
            $updateData['keywords_failed'] = (int) $data['keywords_failed'];
        }
        if (isset($data['articles_generated'])) {
            $updateData['articles_generated'] = (int) $data['articles_generated'];
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
             SET keywords_completed = keywords_completed + 1,
                 articles_generated = articles_generated + 1
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
             SET keywords_failed = keywords_failed + 1
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
            'current_step' => self::STEP_DONE,
            'current_keyword' => null,
            'current_queue_id' => null,
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
     * Get job statistics for project
     */
    public function getStatsForProject(int $projectId): array
    {
        $stats = Database::fetch(
            "SELECT
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_jobs,
                SUM(articles_generated) as total_articles,
                SUM(credits_used) as total_credits
             FROM {$this->table}
             WHERE project_id = ?",
            [$projectId]
        );

        return [
            'total_jobs' => (int) ($stats['total_jobs'] ?? 0),
            'completed_jobs' => (int) ($stats['completed_jobs'] ?? 0),
            'failed_jobs' => (int) ($stats['failed_jobs'] ?? 0),
            'total_articles' => (int) ($stats['total_articles'] ?? 0),
            'total_credits' => (float) ($stats['total_credits'] ?? 0),
        ];
    }

    /**
     * Clean old completed/cancelled jobs (keep last N per project)
     */
    public function cleanOldJobs(int $keepPerProject = 20): int
    {
        // This is a more complex query that keeps only the last N jobs per project
        $deleted = 0;

        $projects = Database::fetchAll(
            "SELECT DISTINCT project_id FROM {$this->table}"
        );

        foreach ($projects as $project) {
            $projectId = $project['project_id'];

            // Get IDs to delete
            $toDelete = Database::fetchAll(
                "SELECT id FROM {$this->table}
                 WHERE project_id = ? AND status IN ('completed', 'cancelled', 'error')
                 ORDER BY created_at DESC
                 LIMIT 18446744073709551615 OFFSET ?",
                [$projectId, $keepPerProject]
            );

            foreach ($toDelete as $row) {
                Database::delete($this->table, 'id = ?', [$row['id']]);
                $deleted++;
            }
        }

        return $deleted;
    }
}
