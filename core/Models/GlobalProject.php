<?php

namespace Core\Models;

use Core\Database;

/**
 * GlobalProject Model
 *
 * Gestisce la tabella `projects` (hub centralizzato progetti globali)
 * e le relazioni con le tabelle progetto dei singoli moduli.
 */
class GlobalProject
{
    protected string $table = 'projects';

    /**
     * Configurazione moduli supportati.
     * Ogni entry mappa slug → tabella, label, colore, icona Heroicons, route prefix.
     */
    private const MODULE_CONFIG = [
        'ai-content' => [
            'table' => 'aic_projects',
            'label' => 'AI Content Generator',
            'color' => 'amber',
            'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'route_prefix' => '/ai-content/projects',
        ],
        'seo-audit' => [
            'table' => 'sa_projects',
            'label' => 'SEO Audit',
            'color' => 'emerald',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'route_prefix' => '/seo-audit/project',
        ],
        'seo-tracking' => [
            'table' => 'st_projects',
            'label' => 'SEO Tracking',
            'color' => 'blue',
            'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'route_prefix' => '/seo-tracking/project',
        ],
        'keyword-research' => [
            'table' => 'kr_projects',
            'label' => 'Keyword Research',
            'color' => 'purple',
            'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
            'route_prefix' => '/keyword-research/project',
            'type_in_route' => true,
        ],
        'ads-analyzer' => [
            'table' => 'ga_projects',
            'label' => 'Google Ads Analyzer',
            'color' => 'rose',
            'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z',
            'route_prefix' => '/ads-analyzer/projects',
        ],
        'internal-links' => [
            'table' => 'il_projects',
            'label' => 'Internal Links',
            'color' => 'cyan',
            'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
            'route_prefix' => '/internal-links/project',
        ],
        'content-creator' => [
            'table' => 'cc_projects',
            'label' => 'Content Creator',
            'color' => 'orange',
            'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
            'route_prefix' => '/content-creator/projects',
        ],
    ];

    /**
     * Sotto-tipi per moduli che supportano varianti.
     * Solo i moduli qui elencati mostreranno un modal di selezione tipo durante l'attivazione.
     */
    private const MODULE_TYPES = [
        'ai-content' => [
            'manual' => [
                'label' => 'Articoli Manuali',
                'description' => 'Aggiungi keyword una alla volta e controlla ogni articolo',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
            'auto' => [
                'label' => 'Articoli Automatici',
                'description' => 'Genera articoli in batch da una lista di keyword',
                'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            ],
            'meta-tag' => [
                'label' => 'SEO Meta Tags',
                'description' => 'Genera title e description ottimizzati per le tue pagine',
                'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            ],
        ],
        'keyword-research' => [
            'research' => [
                'label' => 'Research Guidata',
                'description' => 'Clustering AI con volumi di ricerca e intent',
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
            ],
            'architecture' => [
                'label' => 'Architettura Sito',
                'description' => 'Struttura URL e gerarchia pagine ottimizzata',
                'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
            ],
            'editorial' => [
                'label' => 'Piano Editoriale',
                'description' => 'Piano contenuti mensile basato su keyword strategiche',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ],
        ],
        'ads-analyzer' => [
            'campaign' => [
                'label' => 'Analisi Campagne',
                'description' => 'Analizza keyword negative e valuta performance campagne',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            ],
            'campaign-creator' => [
                'label' => 'Crea Campagna AI',
                'description' => 'Genera una campagna Google Ads completa con AI',
                'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ],
    ];

    /**
     * Ritorna la configurazione di tutti i moduli.
     */
    public function getModuleConfig(): array
    {
        return self::MODULE_CONFIG;
    }

    /**
     * Ritorna la configurazione di un singolo modulo.
     */
    public function getModuleConfigBySlug(string $slug): ?array
    {
        return self::MODULE_CONFIG[$slug] ?? null;
    }

    /**
     * Ritorna i tipi disponibili per un modulo, o tutti i moduli tipizzati.
     */
    public function getModuleTypes(?string $slug = null): array
    {
        if ($slug !== null) {
            return self::MODULE_TYPES[$slug] ?? [];
        }
        return self::MODULE_TYPES;
    }

    // ─────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────

    /**
     * Trova progetto per ID (con verifica opzionale utente).
     */
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

    /**
     * Tutti i progetti di un utente, con filtro status opzionale.
     */
    public function allByUser(int $userId, string $status = 'all'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];

        if ($status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Tutti i progetti con conteggio moduli attivi e ultima attivita.
     */
    public function allWithModuleStats(int $userId): array
    {
        $projects = $this->allByUser($userId);

        foreach ($projects as &$project) {
            $activeCount = 0;
            $lastActivity = null;

            foreach (self::MODULE_CONFIG as $slug => $config) {
                try {
                    $row = Database::fetch(
                        "SELECT COUNT(*) as cnt, MAX(created_at) as last_at FROM {$config['table']} WHERE global_project_id = ?",
                        [$project['id']]
                    );

                    if ($row && (int) $row['cnt'] > 0) {
                        $activeCount++;
                        if ($row['last_at'] && ($lastActivity === null || $row['last_at'] > $lastActivity)) {
                            $lastActivity = $row['last_at'];
                        }
                    }
                } catch (\Exception $e) {
                    // Tabella potrebbe non esistere - graceful degradation
                    continue;
                }
            }

            $project['active_modules_count'] = $activeCount;
            $project['last_module_activity'] = $lastActivity;
        }
        unset($project);

        return $projects;
    }

    /**
     * Crea nuovo progetto globale.
     */
    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    /**
     * Aggiorna progetto con verifica ownership.
     */
    public function update(int $id, array $data, int $userId): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update($this->table, $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Elimina progetto (FK ON DELETE SET NULL gestisce i moduli).
     */
    public function delete(int $id, int $userId): bool
    {
        // Verifica ownership prima di eliminare
        $project = $this->find($id, $userId);
        if (!$project) {
            return false;
        }

        return Database::delete($this->table, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Conta progetti per utente (per limiti piano).
     */
    public function countByUser(int $userId): int
    {
        return Database::count($this->table, 'user_id = ?', [$userId]);
    }

    // ─────────────────────────────────────────────
    // MODULE RELATIONS
    // ─────────────────────────────────────────────

    /**
     * Ottieni tutti i moduli attivi per un progetto globale.
     * Controlla tutte le 7 tabelle modulo per record con global_project_id = $id.
     *
     * @return array<int, array{slug: string, module_project_id: int, label: string, table: string, color: string, route_prefix: string, type: ?string, type_label: ?string}>
     */
    public function getActiveModules(int $id): array
    {
        $active = [];

        foreach (self::MODULE_CONFIG as $slug => $config) {
            try {
                $hasTypes = isset(self::MODULE_TYPES[$slug]);
                $sql = $hasTypes
                    ? "SELECT id, type FROM {$config['table']} WHERE global_project_id = ?"
                    : "SELECT id FROM {$config['table']} WHERE global_project_id = ?";

                $rows = Database::fetchAll($sql, [$id]);

                foreach ($rows as $row) {
                    $type = $row['type'] ?? null;
                    $typeLabel = null;
                    if ($type && $hasTypes && isset(self::MODULE_TYPES[$slug][$type])) {
                        $typeLabel = self::MODULE_TYPES[$slug][$type]['label'];
                    }

                    $active[] = [
                        'slug' => $slug,
                        'module_project_id' => (int) $row['id'],
                        'label' => $config['label'],
                        'table' => $config['table'],
                        'color' => $config['color'],
                        'icon' => $config['icon'],
                        'route_prefix' => $config['route_prefix'],
                        'type' => $type,
                        'type_label' => $typeLabel,
                    ];
                }
            } catch (\Exception $e) {
                // Tabella potrebbe non esistere
                continue;
            }
        }

        return $active;
    }

    /**
     * Ottieni KPI di tutti i moduli attivi per un progetto globale.
     * Chiama getProjectKpi() su ogni model modulo, con try/catch per resilienza.
     *
     * @return array<string, array> KPI indicizzati per slug modulo
     */
    public function getModuleStats(int $id): array
    {
        $activeModules = $this->getActiveModules($id);
        $stats = [];

        // Mappa slug → classe model
        $modelMap = [
            'ai-content' => \Modules\AiContent\Models\Project::class,
            'seo-audit' => \Modules\SeoAudit\Models\Project::class,
            'seo-tracking' => \Modules\SeoTracking\Models\Project::class,
            'keyword-research' => \Modules\KeywordResearch\Models\Project::class,
            'ads-analyzer' => \Modules\AdsAnalyzer\Models\Project::class,
            'internal-links' => \Modules\InternalLinks\Models\Project::class,
            'content-creator' => \Modules\ContentCreator\Models\Project::class,
        ];

        foreach ($activeModules as $module) {
            $slug = $module['slug'];
            $moduleProjectId = $module['module_project_id'];
            $key = $module['type'] ? "{$slug}:{$module['type']}" : $slug;

            if (!isset($modelMap[$slug])) {
                continue;
            }

            try {
                $modelClass = $modelMap[$slug];

                // ads-analyzer usa metodi statici
                if ($slug === 'ads-analyzer') {
                    $stats[$key] = $modelClass::getProjectKpi($moduleProjectId);
                } else {
                    $model = new $modelClass();
                    $stats[$key] = $model->getProjectKpi($moduleProjectId);
                }
            } catch (\Exception $e) {
                // Modulo potrebbe non essere disponibile
                $stats[$key] = [
                    'metrics' => [],
                    'lastActivity' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Ritorna i tipi NON ancora attivati per ogni modulo tipizzato.
     * Usato dalla dashboard per mostrare il bottone "+ Aggiungi tipo".
     *
     * @return array<string, array> Tipi rimanenti indicizzati per slug modulo
     */
    public function getRemainingTypes(int $id): array
    {
        $remaining = [];

        foreach (self::MODULE_TYPES as $slug => $allTypes) {
            if (!isset(self::MODULE_CONFIG[$slug])) {
                continue;
            }

            $table = self::MODULE_CONFIG[$slug]['table'];

            try {
                $rows = Database::fetchAll(
                    "SELECT DISTINCT type FROM {$table} WHERE global_project_id = ? AND type IS NOT NULL",
                    [$id]
                );
                $activeTypes = array_column($rows, 'type');
            } catch (\Exception $e) {
                $activeTypes = [];
            }

            $notActivated = [];
            foreach ($allTypes as $typeKey => $typeInfo) {
                if (!in_array($typeKey, $activeTypes, true)) {
                    $notActivated[$typeKey] = $typeInfo;
                }
            }

            if (!empty($notActivated)) {
                $remaining[$slug] = $notActivated;
            }
        }

        return $remaining;
    }

    // ─────────────────────────────────────────────
    // MODULE ACTIVATION
    // ─────────────────────────────────────────────

    /**
     * Attiva un modulo per un progetto globale.
     * Crea il record nella tabella modulo appropriata.
     *
     * @return int|null ID del progetto modulo creato, o null in caso di errore
     */
    public function activateModule(int $globalProjectId, string $moduleSlug, int $userId, array $extraData = []): ?int
    {
        // Verifica che il modulo sia valido
        if (!isset(self::MODULE_CONFIG[$moduleSlug])) {
            return null;
        }

        // Leggi il progetto globale per nome e dominio
        $project = $this->find($globalProjectId, $userId);
        if (!$project) {
            return null;
        }

        $name = $project['name'];
        $domain = $project['domain'] ?? '';

        try {
            switch ($moduleSlug) {
                case 'ai-content':
                    return Database::insert('aic_projects', array_merge([
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'type' => $extraData['type'] ?? 'manual',
                        'default_language' => $extraData['default_language'] ?? 'it',
                    ], $this->filterExtraData($extraData, ['type', 'default_language'])));

                case 'seo-audit':
                    $projectId = Database::insert('sa_projects', [
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'base_url' => $domain ?: null,
                        'status' => 'pending',
                    ]);
                    // Crea record configurazione sito
                    Database::insert('sa_site_config', ['project_id' => $projectId]);
                    return $projectId;

                case 'seo-tracking':
                    $projectId = Database::insert('st_projects', [
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'domain' => $domain ?: '',
                    ]);
                    // Crea record alert settings
                    Database::insert('st_alert_settings', ['project_id' => $projectId]);
                    return $projectId;

                case 'keyword-research':
                    Database::insert('kr_projects', [
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'type' => $extraData['type'] ?? 'research',
                    ]);
                    return (int) Database::lastInsertId();

                case 'ads-analyzer':
                    return Database::insert('ga_projects', [
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'type' => $extraData['type'] ?? 'campaign',
                        'status' => 'draft',
                    ]);

                case 'internal-links':
                    $projectId = Database::insert('il_projects', [
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'base_url' => $domain ?: null,
                        'status' => 'active',
                    ]);
                    // Crea record stats
                    Database::insert('il_project_stats', ['project_id' => $projectId]);
                    return $projectId;

                case 'content-creator':
                    return Database::insert('cc_projects', [
                        'user_id' => $userId,
                        'global_project_id' => $globalProjectId,
                        'name' => $name,
                        'base_url' => $domain ?: null,
                        'status' => 'active',
                    ]);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            error_log("[GlobalProject] activateModule error for {$moduleSlug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Filtra i campi extra per evitare duplicati con quelli gia impostati.
     */
    private function filterExtraData(array $extraData, array $excludeKeys): array
    {
        $filtered = [];
        foreach ($extraData as $key => $value) {
            if (!in_array($key, $excludeKeys, true)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }
}
