<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class GeneratedFix
{
    public static function create(array $data): int
    {
        return Database::insert('ga_generated_fixes', [
            'evaluation_id' => $data['evaluation_id'],
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'fix_type' => $data['fix_type'],
            'scope_level' => $data['scope_level'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'ad_group_name' => $data['ad_group_name'] ?? null,
            'ad_group_id_google' => $data['ad_group_id_google'] ?? null,
            'campaign_id_google' => $data['campaign_id_google'] ?? null,
            'issue_description' => $data['issue_description'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
            'ai_response' => isset($data['ai_response']) ? json_encode($data['ai_response'], JSON_UNESCAPED_UNICODE) : null,
            'display_text' => $data['display_text'] ?? null,
            'credits_used' => $data['credits_used'] ?? 0,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM ga_generated_fixes WHERE id = ?",
            [$id]
        );
    }

    public static function getByEvaluation(int $evaluationId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_generated_fixes WHERE evaluation_id = ? ORDER BY created_at DESC",
            [$evaluationId]
        );
    }

    public static function markApplied(int $id, array $result): bool
    {
        return Database::update('ga_generated_fixes', [
            'status' => 'applied',
            'applied_at' => date('Y-m-d H:i:s'),
            'apply_result' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ], 'id = ?', [$id]) > 0;
    }

    public static function markFailed(int $id, string $error): bool
    {
        return Database::update('ga_generated_fixes', [
            'status' => 'failed',
            'apply_result' => json_encode(['error' => $error], JSON_UNESCAPED_UNICODE),
        ], 'id = ?', [$id]) > 0;
    }
}
