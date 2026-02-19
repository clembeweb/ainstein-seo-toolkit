<?php

namespace Modules\KeywordResearch\Models;

use Core\Database;

class Project
{
    protected string $table = 'kr_projects';

    /**
     * Configurazione centralizzata per tipo progetto.
     * Senza parametro ritorna tutti i tipi, con parametro ritorna solo quello specifico.
     */
    public static function typeConfig(?string $type = null): array
    {
        $configs = [
            'research' => [
                'label' => 'Research Guidata',
                'route_segment' => 'research',
                'gradient' => 'from-emerald-500 to-teal-600',
                'badge_bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            ],
            'architecture' => [
                'label' => 'Architettura Sito',
                'route_segment' => 'architecture',
                'gradient' => 'from-blue-500 to-indigo-600',
                'badge_bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            ],
            'editorial' => [
                'label' => 'Piano Editoriale',
                'route_segment' => 'editorial',
                'gradient' => 'from-violet-500 to-purple-600',
                'badge_bg' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ],
        ];

        if ($type !== null) {
            return $configs[$type] ?? $configs['research'];
        }

        return $configs;
    }

    /**
     * Tipi validi per validazione input.
     */
    public static function validTypes(): array
    {
        return ['research', 'architecture', 'editorial'];
    }

    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        return Database::fetch($sql, $params);
    }

    public function allByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public function getRecentByUser(int $userId, int $limit = 5): array
    {
        $sql = "
            SELECT p.*,
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as researches_count,
                (SELECT SUM(filtered_keywords_count) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as total_keywords,
                (SELECT COUNT(*) FROM kr_clusters c JOIN kr_researches r ON c.research_id = r.id WHERE r.project_id = p.id) as total_clusters,
                (SELECT MAX(r2.created_at) FROM kr_researches r2 WHERE r2.project_id = p.id) as last_research_at
            FROM {$this->table} p
            WHERE p.user_id = ?
            ORDER BY p.updated_at DESC
            LIMIT ?
        ";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public function allWithStats(int $userId, ?string $type = null): array
    {
        $sql = "
            SELECT p.*,
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = p.id) as researches_count,
                (SELECT COUNT(*) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as completed_count,
                (SELECT SUM(filtered_keywords_count) FROM kr_researches WHERE project_id = p.id AND status = 'completed') as total_keywords,
                (SELECT COUNT(*) FROM kr_clusters c JOIN kr_researches r ON c.research_id = r.id WHERE r.project_id = p.id) as total_clusters
            FROM {$this->table} p
            WHERE p.user_id = ?
        ";
        $params = [$userId];

        if ($type !== null && in_array($type, self::validTypes(), true)) {
            $sql .= " AND p.type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY p.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    public function create(array $data): int
    {
        Database::insert($this->table, [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'research',
            'default_location' => $data['default_location'] ?? 'IT',
            'default_language' => $data['default_language'] ?? 'it',
        ]);
        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        Database::update($this->table, $data, 'id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $researchIds = Database::fetchAll(
            "SELECT id FROM kr_researches WHERE project_id = ?",
            [$id]
        );

        foreach ($researchIds as $r) {
            Database::delete('kr_editorial_items', 'research_id = ?', [$r['id']]);
            Database::delete('kr_keywords', 'research_id = ?', [$r['id']]);
            Database::delete('kr_clusters', 'research_id = ?', [$r['id']]);
        }

        Database::delete('kr_researches', 'project_id = ?', [$id]);
        Database::delete($this->table, 'id = ?', [$id]);
    }

    /**
     * KPI standardizzati per il progetto (usato da GlobalProject hub).
     *
     * @return array{metrics: array, lastActivity: ?string}
     */
    public function getProjectKpi(int $projectId): array
    {
        $researchStats = Database::fetch("
            SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN filtered_keywords_count ELSE 0 END), 0) as total_keywords,
                MAX(created_at) as last_activity
            FROM kr_researches
            WHERE project_id = ?
        ", [$projectId]);

        $clusterCount = 0;
        try {
            $cRow = Database::fetch("
                SELECT COUNT(*) as cnt
                FROM kr_clusters c
                INNER JOIN kr_researches r ON c.research_id = r.id
                WHERE r.project_id = ?
            ", [$projectId]);
            $clusterCount = (int) ($cRow['cnt'] ?? 0);
        } catch (\Exception $e) {
            // Graceful degradation
        }

        $metrics = [
            ['label' => 'Ricerche completate', 'value' => (int) ($researchStats['completed_count'] ?? 0)],
            ['label' => 'Keyword totali', 'value' => (int) ($researchStats['total_keywords'] ?? 0)],
            ['label' => 'Cluster', 'value' => $clusterCount],
        ];

        return [
            'metrics' => $metrics,
            'lastActivity' => $researchStats['last_activity'] ?? null,
        ];
    }
}
