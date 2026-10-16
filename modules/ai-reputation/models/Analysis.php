<?php

namespace Modules\AiReputation\Models;

use Core\Database;

/**
 * Analysis model - tabella ar_analyses (giudizio del judge su una risposta)
 */
class Analysis
{
    /** Etichette italiane dei codici del judge (registro dal vivo, dettaglio risposta) */
    public const VERDICT_LABELS = ['positive' => 'positivo', 'neutral' => 'neutro', 'mixed' => 'misto', 'negative' => 'negativo', 'not_mentioned' => 'non nominato'];
    public const OUTCOME_LABELS = ['answered' => 'ha risposto', 'clarification_requested' => 'ha chiesto chiarimenti', 'refused' => 'si è rifiutata', 'empty' => 'risposta vuota'];
    public const HOMONYM_LABELS = ['no' => 'no', 'yes' => 'sì', 'uncertain' => 'da verificare'];

    /**
     * "Il motore" → "L'AI" nei testi del judge: il prompt lo chiamava così e i giudizi lo ripetevano.
     * Vale per i giudizi nuovi (JudgeService) e per quelli già salvati (byRun).
     */
    public static function plain(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }
        return strtr($text, [
            'Il motore' => "L'AI", 'il motore' => "l'AI",
            'Del motore' => "Dell'AI", 'del motore' => "dell'AI",
            'Dal motore' => "Dall'AI", 'dal motore' => "dall'AI",
            'Al motore' => "All'AI", 'al motore' => "all'AI",
            'I motori' => 'Le AI', 'i motori' => 'le AI',
        ]);
    }

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
            'doubts' => $enc($a['doubts'] ?? []),
            'cited_domains' => $enc($a['cited_domains']),
            'citations_noise' => $enc($a['citations_noise']),
            'contradicts_profile' => (int) ($a['contradicts_profile'] ?? 0),
            'contradiction_note' => $a['contradiction_note'] ?? null,
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
            foreach (['claims', 'competitors', 'negative_reasons', 'negative_urls', 'doubts', 'cited_domains', 'citations_noise'] as $k) {
                $r[$k] = json_decode((string) $r[$k], true) ?: [];
            }
            unset($r['raw']);
            foreach (['summary', 'homonym_note', 'contradiction_note'] as $k) {
                if (isset($r[$k])) {
                    $r[$k] = self::plain($r[$k]);
                }
            }
            foreach ($r['doubts'] as $i => $dbt) {
                if (is_array($dbt) && isset($dbt['doubt'])) {
                    $r['doubts'][$i]['doubt'] = self::plain((string) $dbt['doubt']);
                }
            }
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
