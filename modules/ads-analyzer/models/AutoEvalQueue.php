<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AutoEvalQueue
{
    public static function create(array $data): int
    {
        return Database::insert('ga_auto_eval_queue', [
            'project_id' => $data['project_id'],
            'run_id' => $data['run_id'],
            'scheduled_for' => $data['scheduled_for'],
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM ga_auto_eval_queue WHERE id = ?",
            [$id]
        ) ?: null;
    }

    /**
     * Prende il prossimo item pending con scheduled_for <= NOW() e lo segna processing
     */
    public static function getNextPending(): ?array
    {
        $item = Database::fetch(
            "SELECT * FROM ga_auto_eval_queue
             WHERE status = 'pending' AND scheduled_for <= NOW()
             ORDER BY scheduled_for ASC
             LIMIT 1"
        );

        if (!$item) {
            return null;
        }

        Database::update('ga_auto_eval_queue', [
            'status' => 'processing',
            'started_at' => date('Y-m-d H:i:s'),
            'attempts' => ($item['attempts'] ?? 0) + 1,
        ], 'id = ? AND status = ?', [$item['id'], 'pending']);

        // Ricarica per avere dati aggiornati
        return self::find($item['id']);
    }

    public static function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        $data = ['status' => $status];
        if ($status === 'completed' || $status === 'skipped') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($error !== null) {
            $data['error_message'] = $error;
        }
        return Database::update('ga_auto_eval_queue', $data, 'id = ?', [$id]) > 0;
    }

    public static function markSkipped(int $id, string $reason): bool
    {
        return Database::update('ga_auto_eval_queue', [
            'status' => 'skipped',
            'skip_reason' => $reason,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) > 0;
    }

    public static function existsForRun(int $runId): bool
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_auto_eval_queue WHERE run_id = ?",
            [$runId]
        );
        return ((int)($result['cnt'] ?? 0)) > 0;
    }

    public static function getByProject(int $projectId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_auto_eval_queue WHERE project_id = ? ORDER BY created_at DESC LIMIT ?",
            [$projectId, $limit]
        );
    }

    /**
     * Reset items bloccati in processing da troppo tempo
     */
    public static function resetStuckProcessing(int $minutes = 30): int
    {
        return Database::update(
            'ga_auto_eval_queue',
            ['status' => 'pending', 'started_at' => null],
            "status = 'processing' AND started_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$minutes]
        );
    }
}
