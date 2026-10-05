<?php

namespace Modules\AiReputation\Models;

use Core\Database;

/**
 * Run model - tabelle ar_runs e ar_responses (coda delle risposte da raccogliere)
 */
class Run
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public function find(int $id, ?int $projectId = null): ?array
    {
        $sql = "SELECT * FROM ar_runs WHERE id = ?";
        $params = [$id];
        if ($projectId !== null) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        }
        $row = Database::fetch($sql, $params);
        if ($row) {
            $row['engines'] = json_decode((string) $row['engines'], true) ?: [];
        }
        return $row;
    }

    public function getActiveForProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM ar_runs WHERE project_id = ? AND status IN ('pending', 'running') ORDER BY id DESC LIMIT 1",
            [$projectId]
        );
    }

    /**
     * Crea il run e la coda di risposte (prompt x engine x repeat), tutte pending.
     * @return int run id
     */
    public function create(int $projectId, int $userId, array $prompts, array $engines, int $repeats = 1): int
    {
        $runId = Database::insert('ar_runs', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'status' => self::STATUS_PENDING,
            'engines' => json_encode(array_values($engines)),
            'repeats' => $repeats,
            'prompts_total' => count($prompts),
            'responses_total' => count($prompts) * count($engines) * $repeats,
        ]);

        foreach ($prompts as $prompt) {
            foreach ($engines as $engine) {
                for ($i = 0; $i < $repeats; $i++) {
                    Database::insert('ar_responses', [
                        'run_id' => $runId,
                        'project_id' => $projectId,
                        'prompt_id' => (int) $prompt['id'],
                        'engine' => $engine,
                        'repeat_idx' => $i,
                        'status' => 'pending',
                    ]);
                }
            }
        }
        return $runId;
    }

    public function start(int $id): void
    {
        Database::update('ar_runs', ['status' => self::STATUS_RUNNING, 'started_at' => date('Y-m-d H:i:s')], 'id = ? AND status = ?', [$id, self::STATUS_PENDING]);
    }

    public function complete(int $id): void
    {
        Database::update('ar_runs', ['status' => self::STATUS_COMPLETED, 'finished_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public function fail(int $id, string $message): void
    {
        Database::update('ar_runs', ['status' => self::STATUS_FAILED, 'error_message' => $message, 'finished_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public function cancel(int $id): bool
    {
        return Database::update('ar_runs', ['status' => self::STATUS_CANCELLED, 'finished_at' => date('Y-m-d H:i:s')], "id = ? AND status IN ('pending', 'running')", [$id]) > 0;
    }

    public function isCancelled(int $id): bool
    {
        return Database::fetchColumn("SELECT status FROM ar_runs WHERE id = ?", [$id]) === self::STATUS_CANCELLED;
    }

    public function addProgress(int $id, bool $ok, float $cost, float $credits): void
    {
        $col = $ok ? 'responses_done' : 'responses_error';
        Database::execute(
            "UPDATE ar_runs SET {$col} = {$col} + 1, cost_total = cost_total + ?, credits_used = credits_used + ? WHERE id = ?",
            [$cost, $credits, $id]
        );
    }

    /** Prossima risposta pending della coda, con il testo del prompt */
    public function nextPending(int $runId): ?array
    {
        return Database::fetch("
            SELECT r.*, p.text AS prompt_text, p.cluster AS prompt_cluster
            FROM ar_responses r
            JOIN ar_prompts p ON p.id = r.prompt_id
            WHERE r.run_id = ? AND r.status = 'pending'
            ORDER BY p.sort_order, p.id, r.engine, r.repeat_idx
            LIMIT 1
        ", [$runId]);
    }

    public function saveResponse(int $responseId, array $r): void
    {
        Database::update('ar_responses', [
            'status' => $r['status'] === 'ok' ? 'ok' : 'error',
            'model' => $r['model'],
            'text' => $r['text'],
            'citations' => json_encode($r['citations'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sources_read' => json_encode($r['sources_read'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'queries' => json_encode($r['queries'], JSON_UNESCAPED_UNICODE),
            'search_count' => $r['search_count'],
            'raw' => $r['raw'] !== null ? json_encode($r['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'tokens_in' => $r['tokens_in'],
            'tokens_out' => $r['tokens_out'],
            'cost' => $r['cost'],
            'cost_is_real' => $r['cost_is_real'],
            'latency_ms' => $r['latency_ms'],
            'error_message' => $r['error_message'],
        ], 'id = ?', [$responseId]);
    }

    /** Tutte le risposte di un run (senza raw), con prompt */
    public function responses(int $runId): array
    {
        $rows = Database::fetchAll("
            SELECT r.id, r.prompt_id, r.engine, r.model, r.repeat_idx, r.status, r.text, r.citations, r.sources_read,
                   r.queries, r.search_count, r.tokens_in, r.tokens_out, r.cost, r.cost_is_real, r.latency_ms, r.error_message,
                   p.text AS prompt_text, p.cluster AS prompt_cluster, p.sort_order
            FROM ar_responses r
            JOIN ar_prompts p ON p.id = r.prompt_id
            WHERE r.run_id = ?
            ORDER BY p.sort_order, p.id, r.engine, r.repeat_idx
        ", [$runId]);
        foreach ($rows as &$row) {
            $row['citations'] = json_decode((string) $row['citations'], true) ?: [];
            $row['sources_read'] = json_decode((string) $row['sources_read'], true) ?: [];
            $row['queries'] = json_decode((string) $row['queries'], true) ?: [];
        }
        unset($row);
        return $rows;
    }

    public function allByProject(int $projectId, int $limit = 20): array
    {
        return Database::fetchAll("SELECT * FROM ar_runs WHERE project_id = ? ORDER BY id DESC LIMIT {$limit}", [$projectId]);
    }
}
