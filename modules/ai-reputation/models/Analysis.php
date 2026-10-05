<?php

namespace Modules\AiReputation\Models;

use Core\Database;

/**
 * Analysis model - tabella ar_analyses (giudizio del judge su una risposta)
 */
class Analysis
{
    public function save(int $responseId, int $runId, int $projectId, array $a): int
    {
        $enc = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Database::delete('ar_analyses', 'response_id = ?', [$responseId]);
        return Database::insert('ar_analyses', [
            'response_id' => $responseId,
            'run_id' => $runId,
            'project_id' => $projectId,
            'outcome' => $a['outcome'],
            'verdict' => $a['verdict'],
            'summary' => $a['summary'],
            'brand_mentioned' => $a['brand_mentioned'],
            'mention_position' => $a['mention_position'],
            'is_homonym' => $a['is_homonym'],
            'homonym_note' => $a['homonym_note'],
            'sentiment' => $a['sentiment'],
            'claims' => $enc($a['claims']),
            'competitors' => $enc($a['competitors']),
            'negative' => $a['negative'],
            'negative_reasons' => $enc($a['negative_reasons']),
            'negative_urls' => $enc($a['negative_urls']),
            'cited_domains' => $enc($a['cited_domains']),
            'citations_noise' => $enc($a['citations_noise']),
            'judge_model' => $a['judge_model'],
            'raw' => $enc($a['raw']),
        ]);
    }

    /** Analisi di un run indicizzate per response_id */
    public function byRun(int $runId): array
    {
        $rows = Database::fetchAll("SELECT * FROM ar_analyses WHERE run_id = ?", [$runId]);
        $out = [];
        foreach ($rows as $r) {
            foreach (['claims', 'competitors', 'negative_reasons', 'negative_urls', 'cited_domains', 'citations_noise'] as $k) {
                $r[$k] = json_decode((string) $r[$k], true) ?: [];
            }
            unset($r['raw']);
            $out[(int) $r['response_id']] = $r;
        }
        return $out;
    }

    public function deleteByRun(int $runId): void
    {
        Database::delete('ar_analyses', 'run_id = ?', [$runId]);
        Database::update('ar_runs', ['analyses_done' => 0], 'id = ?', [$runId]);
    }

    /** Risposte ok del run che non hanno ancora un giudizio */
    public function pendingForRun(int $runId): array
    {
        return Database::fetchAll("
            SELECT r.*, p.text AS prompt_text, p.cluster AS prompt_cluster
            FROM ar_responses r
            JOIN ar_prompts p ON p.id = r.prompt_id
            LEFT JOIN ar_analyses a ON a.response_id = r.id
            WHERE r.run_id = ? AND r.status = 'ok' AND a.id IS NULL
            ORDER BY p.sort_order, p.id, r.engine, r.repeat_idx
        ", [$runId]);
    }
}
