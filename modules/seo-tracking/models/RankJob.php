<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * RankJob Model
 *
 * Manages st_rank_jobs table for tracking
 * both cron and manual rank check jobs
 */
class RankJob
{
    protected string $table = 'st_rank_jobs';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';
    public const STATUS_CANCELLED = 'cancelled';

    // Type constants
    public const TYPE_CRON = 'cron';
    public const TYPE_MANUAL = 'manual';

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
            'keywords_found' => 0,
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

        if (isset($data['current_keyword_id'])) {
            $updateData['current_keyword_id'] = $data['current_keyword_id'];
        }
        if (isset($data['current_keyword'])) {
            $updateData['current_keyword'] = $data['current_keyword'];
        }
        if (isset($data['keywords_completed'])) {
            $updateData['keywords_completed'] = (int) $data['keywords_completed'];
        }
        if (isset($data['keywords_failed'])) {
            $updateData['keywords_failed'] = (int) $data['keywords_failed'];
        }
        if (isset($data['keywords_found'])) {
            $updateData['keywords_found'] = (int) $data['keywords_found'];
        }
        if (isset($data['avg_position'])) {
            $updateData['avg_position'] = (float) $data['avg_position'];
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
    public function incrementCompleted(int $id, bool $found = false): bool
    {
        $foundIncrement = $found ? ', keywords_found = keywords_found + 1' : '';
        return Database::query(
            "UPDATE {$this->table}
             SET keywords_completed = keywords_completed + 1{$foundIncrement}
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
        // Calcola posizione media
        $job = $this->find($id);
        $avgPosition = null;

        if ($job && $job['keywords_found'] > 0) {
            // Calcola dalla queue
            $avg = Database::fetch(
                "SELECT AVG(result_position) as avg_pos
                 FROM st_rank_queue
                 WHERE job_id = ? AND result_position IS NOT NULL",
                [$id]
            );
            $avgPosition = $avg['avg_pos'] ?? null;
        }

        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'current_keyword' => null,
            'current_keyword_id' => null,
            'completed_at' => date('Y-m-d H:i:s'),
        ];

        if ($avgPosition !== null) {
            $updateData['avg_position'] = round($avgPosition, 2);
        }

        return Database::update($this->table, $updateData, 'id = ?', [$id]) > 0;
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
     * Get job progress percentage
     */
    public function getProgress(int $id): int
    {
        $job = $this->find($id);
        if (!$job || $job['keywords_requested'] == 0) {
            return 0;
        }

        $done = $job['keywords_completed'] + $job['keywords_failed'];
        return min(100, round(($done / $job['keywords_requested']) * 100));
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
            'keywords_requested' => (int) $job['keywords_requested'],
            'keywords_completed' => (int) $job['keywords_completed'],
            'keywords_failed' => (int) $job['keywords_failed'],
            'keywords_found' => (int) $job['keywords_found'],
            'current_keyword' => $job['current_keyword'],
            'avg_position' => $job['avg_position'] ? round((float) $job['avg_position'], 1) : null,
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
                SUM(keywords_completed) as total_checks,
                SUM(keywords_found) as total_found,
                SUM(credits_used) as total_credits
             FROM {$this->table}
             WHERE project_id = ?",
            [$projectId]
        );

        return [
            'total_jobs' => (int) ($stats['total_jobs'] ?? 0),
            'completed_jobs' => (int) ($stats['completed_jobs'] ?? 0),
            'failed_jobs' => (int) ($stats['failed_jobs'] ?? 0),
            'total_checks' => (int) ($stats['total_checks'] ?? 0),
            'total_found' => (int) ($stats['total_found'] ?? 0),
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
