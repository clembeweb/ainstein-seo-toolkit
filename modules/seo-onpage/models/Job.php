<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * Job Model for SEO Onpage Optimizer Module
 * Manages sop_jobs table for background processing
 */
class Job
{
    protected string $table = 'sop_jobs';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Find job by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get active job for project
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
     * Create new job
     */
    public function create(array $data): int
    {
        $data['status'] = $data['status'] ?? self::STATUS_PENDING;
        $data['pages_completed'] = 0;
        $data['pages_failed'] = 0;
        $data['credits_used'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Start job
     */
    public function start(int $jobId): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]) > 0;
    }

    /**
     * Update progress
     */
    public function updateProgress(int $jobId, array $data): bool
    {
        return Database::update($this->table, $data, 'id = ?', [$jobId]) > 0;
    }

    /**
     * Increment completed count
     */
    public function incrementCompleted(int $jobId, float $creditsUsed = 0): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET
                pages_completed = pages_completed + 1,
                credits_used = credits_used + ?
             WHERE id = ?",
            [$creditsUsed, $jobId]
        );
    }

    /**
     * Increment failed count
     */
    public function incrementFailed(int $jobId): bool
    {
        return Database::execute(
            "UPDATE {$this->table} SET pages_failed = pages_failed + 1 WHERE id = ?",
            [$jobId]
        );
    }

    /**
     * Complete job
     */
    public function complete(int $jobId, array $data = []): bool
    {
        $data['status'] = self::STATUS_COMPLETED;
        $data['completed_at'] = date('Y-m-d H:i:s');
        return Database::update($this->table, $data, 'id = ?', [$jobId]) > 0;
    }

    /**
     * Cancel job
     */
    public function cancel(int $jobId): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_CANCELLED,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND status IN (?, ?)', [$jobId, self::STATUS_PENDING, self::STATUS_RUNNING]) > 0;
    }

    /**
     * Mark job as error
     */
    public function markError(int $jobId, string $message): bool
    {
        return Database::update($this->table, [
            'status' => self::STATUS_ERROR,
            'error_message' => $message,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]) > 0;
    }

    /**
     * Check if job is cancelled
     */
    public function isCancelled(int $jobId): bool
    {
        $job = $this->find($jobId);
        return $job && $job['status'] === self::STATUS_CANCELLED;
    }

    /**
     * Get job response for API
     */
    public function getJobResponse(int $jobId): array
    {
        $job = $this->find($jobId);

        if (!$job) {
            return ['success' => false, 'error' => 'Job non trovato'];
        }

        $total = (int) $job['pages_requested'];
        $completed = (int) $job['pages_completed'];
        $failed = (int) $job['pages_failed'];
        $progress = $total > 0 ? round((($completed + $failed) / $total) * 100, 1) : 0;

        return [
            'id' => $job['id'],
            'status' => $job['status'],
            'type' => $job['type'],
            'pages_requested' => $total,
            'pages_completed' => $completed,
            'pages_failed' => $failed,
            'progress' => $progress,
            'current_url' => $job['current_url'],
            'avg_score' => $job['avg_score'],
            'total_issues' => $job['total_issues'],
            'credits_used' => $job['credits_used'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
        ];
    }

    /**
     * Get recent jobs for project
     */
    public function getRecentForProject(int $projectId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE project_id = ? ORDER BY created_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }
}
