<?php

namespace Modules\SeoOnpage\Models;

use Core\Database;

/**
 * Queue Model for SEO Onpage Optimizer Module
 * Manages sop_queue table for audit items
 */
class Queue
{
    protected string $table = 'sop_queue';

    /**
     * Find queue item by ID
     */
    public function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Get next pending item for job
     */
    public function getNextPendingForJob(int $jobId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table}
             WHERE job_id = ? AND status = 'pending'
             ORDER BY scheduled_at ASC LIMIT 1",
            [$jobId]
        );
    }

    /**
     * Get all items for job
     */
    public function allByJob(int $jobId): array
    {
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE job_id = ? ORDER BY scheduled_at ASC",
            [$jobId]
        );
    }

    /**
     * Add item to queue
     */
    public function add(array $data): int
    {
        $data['status'] = $data['status'] ?? 'pending';
        $data['scheduled_at'] = $data['scheduled_at'] ?? date('Y-m-d H:i:s');
        return Database::insert($this->table, $data);
    }

    /**
     * Bulk add items to queue
     */
    public function bulkAdd(int $jobId, int $projectId, array $pages, string $device = 'desktop'): int
    {
        $count = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($pages as $page) {
            $this->add([
                'job_id' => $jobId,
                'project_id' => $projectId,
                'page_id' => $page['id'] ?? null,
                'url' => $page['url'],
                'device' => $device,
                'status' => 'pending',
                'scheduled_at' => $now,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Update item status
     */
    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $data = ['status' => $status];

        if ($status === 'processing') {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($status, ['completed', 'error'])) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }

        return Database::update($this->table, $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Mark as completed with analysis ID
     */
    public function markCompleted(int $id, int $analysisId): bool
    {
        return Database::update($this->table, [
            'status' => 'completed',
            'analysis_id' => $analysisId,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    /**
     * Mark as error
     */
    public function markError(int $id, string $message): bool
    {
        return $this->updateStatus($id, 'error', $message);
    }

    /**
     * Count items by status for job
     */
    public function countByJobAndStatus(int $jobId, string $status): int
    {
        return Database::count($this->table, 'job_id = ? AND status = ?', [$jobId, $status]);
    }

    /**
     * Count pending items for job
     */
    public function countPendingForJob(int $jobId): int
    {
        return $this->countByJobAndStatus($jobId, 'pending');
    }

    /**
     * Reset stuck processing items (older than X minutes)
     */
    public function resetStuckProcessing(int $jobId, int $minutes = 10): int
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return Database::execute(
            "UPDATE {$this->table}
             SET status = 'pending', started_at = NULL
             WHERE job_id = ? AND status = 'processing' AND started_at < ?",
            [$jobId, $threshold]
        );
    }

    /**
     * Delete all items for job
     */
    public function deleteByJob(int $jobId): int
    {
        return Database::delete($this->table, 'job_id = ?', [$jobId]);
    }
}
