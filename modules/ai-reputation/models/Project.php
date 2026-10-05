<?php

namespace Modules\AiReputation\Models;

use Core\Database;
use Core\ModuleLoader;

/**
 * Project model - tabella ar_projects (un soggetto monitorato)
 */
class Project
{
    public const SLUG = 'ai-reputation';
    protected string $table = 'ar_projects';

    /** Engine conosciuti, in ordine di peso (ADR-009) */
    public const ENGINES = ['openai', 'gemini', 'perplexity', 'anthropic'];

    public const ENGINE_LABELS = [
        'openai' => 'ChatGPT (OpenAI)',
        'gemini' => 'Gemini (Google)',
        'perplexity' => 'Perplexity',
        'anthropic' => 'Claude (Anthropic)',
    ];

    public function find(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        $row = Database::fetch($sql, $params);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Progetto accessibile dall'utente (owner o membro condiviso via Global Projects).
     */
    public function findAccessible(int $id, int $userId): ?array
    {
        $project = $this->find($id, $userId);
        if ($project) {
            $project['access_role'] = 'owner';
            return $project;
        }

        $project = $this->find($id);
        if (!$project || empty($project['global_project_id'])) {
            return null;
        }

        $role = \Services\ProjectAccessService::getRole((int) $project['global_project_id'], $userId);
        if ($role === null) {
            return null;
        }
        if ($role !== 'owner' && !\Services\ProjectAccessService::canAccessModule(
            (int) $project['global_project_id'], $userId, self::SLUG
        )) {
            return null;
        }

        $project['access_role'] = $role;
        return $project;
    }

    /**
     * Tutti i progetti dell'utente con conteggi (prompt attivi, run, ultimo run).
     */
    public function allWithStats(int $userId): array
    {
        $rows = Database::fetchAll("
            SELECT p.*,
                   (SELECT COUNT(*) FROM ar_prompts pr WHERE pr.project_id = p.id AND pr.is_active = 1) AS prompts_active,
                   (SELECT COUNT(*) FROM ar_runs r WHERE r.project_id = p.id AND r.status = 'completed') AS runs_completed,
                   (SELECT MAX(r.finished_at) FROM ar_runs r WHERE r.project_id = p.id AND r.status = 'completed') AS last_run_at,
                   (SELECT COALESCE(SUM(r.cost_total), 0) FROM ar_runs r WHERE r.project_id = p.id) AS cost_total
            FROM {$this->table} p
            WHERE p.user_id = ? AND p.status = 'active'
            ORDER BY p.updated_at DESC
        ", [$userId]);

        return array_map(fn($r) => $this->hydrate($r), $rows);
    }

    public function create(array $data): int
    {
        if (!isset($data['engines'])) {
            $data['engines'] = self::defaultEngines();
        }
        if (is_array($data['engines'])) {
            $data['engines'] = json_encode(array_values($data['engines']));
        }
        if (empty($data['subject_name'])) {
            $data['subject_name'] = $data['name'];
        }
        if (!isset($data['repeats'])) {
            $data['repeats'] = (int) ModuleLoader::getSetting(self::SLUG, 'default_repeats', 1);
        }
        return Database::insert($this->table, $data);
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['engines']) && is_array($data['engines'])) {
            $data['engines'] = json_encode(array_values($data['engines']));
        }
        return Database::update($this->table, $data, 'id = ?', [$id]) >= 0;
    }

    public function delete(int $id): bool
    {
        return Database::delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Engine attivi di default: quelli abilitati nelle impostazioni modulo (ADR-009).
     */
    public static function defaultEngines(): array
    {
        $engines = [];
        foreach (self::ENGINES as $engine) {
            $default = in_array($engine, ['openai', 'gemini', 'perplexity'], true);
            $enabled = ModuleLoader::getSetting(self::SLUG, "engine_{$engine}_enabled", $default);
            if ($enabled && $enabled !== '0') {
                $engines[] = $engine;
            }
        }
        return $engines;
    }

    /**
     * Peso di un engine nelle metriche aggregate (module.json, ADR-009).
     */
    public static function engineWeight(string $engine): float
    {
        $defaults = ['openai' => 1, 'gemini' => 1, 'perplexity' => 0.3, 'anthropic' => 0.5];
        return (float) ModuleLoader::getSetting(self::SLUG, "engine_{$engine}_weight", $defaults[$engine] ?? 1);
    }

    /**
     * KPI per la dashboard Global Projects.
     */
    public function getProjectKpi(int $projectId): array
    {
        $stats = Database::fetch("
            SELECT
                (SELECT COUNT(*) FROM ar_prompts WHERE project_id = ? AND is_active = 1) AS prompts_active,
                (SELECT COUNT(*) FROM ar_runs WHERE project_id = ? AND status = 'completed') AS runs_completed,
                (SELECT MAX(finished_at) FROM ar_runs WHERE project_id = ? AND status = 'completed') AS last_run_at,
                (SELECT COUNT(*) FROM ar_analyses WHERE project_id = ? AND negative = 1) AS negatives
        ", [$projectId, $projectId, $projectId, $projectId]);

        return [
            'metrics' => [
                ['label' => 'Domande attive', 'value' => (int) ($stats['prompts_active'] ?? 0)],
                ['label' => 'Run completati', 'value' => (int) ($stats['runs_completed'] ?? 0)],
                ['label' => 'Risposte negative', 'value' => (int) ($stats['negatives'] ?? 0)],
            ],
            'lastActivity' => $stats['last_run_at'] ?? null,
        ];
    }

    private function hydrate(array $row): array
    {
        $engines = json_decode((string) ($row['engines'] ?? ''), true);
        $row['engines'] = is_array($engines) && $engines ? $engines : self::defaultEngines();
        return $row;
    }
}
