<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Extension
{
    public static function create(array $data): int
    {
        return Database::insert('ga_extensions', [
            'project_id' => $data['project_id'],
            'run_id' => $data['run_id'],
            'campaign_id_google' => $data['campaign_id_google'] ?? null,
            'extension_type' => $data['extension_type'],
            'extension_text' => $data['extension_text'] ?? null,
            'status' => $data['status'] ?? null,
            'clicks' => $data['clicks'] ?? 0,
            'impressions' => $data['impressions'] ?? 0,
        ]);
    }

    public static function getByRun(int $runId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_extensions WHERE run_id = ? ORDER BY extension_type, clicks DESC",
            [$runId]
        );
    }

    public static function getByRunGrouped(int $runId): array
    {
        $extensions = self::getByRun($runId);
        $grouped = [];
        foreach ($extensions as $ext) {
            $grouped[$ext['extension_type']][] = $ext;
        }
        return $grouped;
    }

    public static function countByProject(int $projectId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as cnt FROM ga_extensions WHERE project_id = ?",
            [$projectId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public static function deleteByRun(int $runId): bool
    {
        return Database::delete('ga_extensions', 'run_id = ?', [$runId]) >= 0;
    }
}
