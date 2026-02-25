# Dashboard Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the generic "Da fare" dashboard section with project-centric cards showing per-project KPIs and contextual smart actions, plus rich "Cosa puoi fare" module blocks for new/exploring users.

**Architecture:** Add a batch-query method `allWithDashboardData()` on `GlobalProject` model to efficiently fetch per-project module KPIs in ~8 SQL queries (one per module table). Rewrite the dashboard view with new components for project cards and module capability blocks. Two modes: active user (project cards + quick tools + discover) and new user (module capability blocks).

**Tech Stack:** PHP 8+, Tailwind CSS (dark mode), Heroicons SVG, Alpine.js (minimal)

**Design doc:** `docs/plans/2026-02-25-dashboard-redesign-design.md`

---

## Task 1: Add `allWithDashboardData()` to GlobalProject model

**Files:**
- Modify: `core/Models/GlobalProject.php` (after line 294, after `allWithModuleStats`)

**Why:** The current `allWithModuleStats()` only returns `active_modules_count` and `last_module_activity`. We need per-module KPIs (health_score, keywords, articles) and action-relevant data (gsc_connected, articles_ready) — all fetched efficiently with batch queries.

**Step 1: Add the method**

Add after line 294 of `core/Models/GlobalProject.php`:

```php
/**
 * Dashboard-optimized: tutti i progetti con KPI leggeri per modulo.
 * Usa batch query (1 per modulo) anziché N×M singole query.
 *
 * @return array Progetti con chiavi extra: modules_data[], health_status, primary_action
 */
public function allWithDashboardData(int $userId): array
{
    $projects = $this->allByUser($userId);
    if (empty($projects)) {
        return [];
    }

    $projectIds = array_column($projects, 'id');
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));

    // Index per accesso rapido
    $indexed = [];
    foreach ($projects as &$project) {
        $project['modules_data'] = [];
        $project['active_modules'] = [];
        $project['health_status'] = 'gray'; // gray, green, amber, red
        $project['primary_action'] = null;
        $project['active_modules_count'] = 0;
        $project['last_module_activity'] = null;
        $indexed[$project['id']] = &$project;
    }
    unset($project);

    // --- Batch query per ogni modulo ---

    // SEO Audit
    try {
        $rows = Database::fetchAll(
            "SELECT global_project_id, id, health_score, issues_count, status, completed_at
             FROM sa_projects WHERE global_project_id IN ($placeholders)",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['seo-audit'] = [
                'module_project_id' => (int) $row['id'],
                'health_score' => (int) ($row['health_score'] ?? 0),
                'issues_count' => (int) ($row['issues_count'] ?? 0),
                'status' => $row['status'] ?? null,
            ];
            $indexed[$gpId]['active_modules'][] = 'seo-audit';
            $this->updateLastActivity($indexed[$gpId], $row['completed_at']);
        }
    } catch (\Exception $e) {}

    // SEO Tracking
    try {
        $rows = Database::fetchAll(
            "SELECT p.global_project_id, p.id, p.gsc_connected,
                    COUNT(k.id) as keywords_count
             FROM st_projects p
             LEFT JOIN st_keywords k ON k.project_id = p.id AND k.is_tracked = 1
             WHERE p.global_project_id IN ($placeholders)
             GROUP BY p.id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['seo-tracking'] = [
                'module_project_id' => (int) $row['id'],
                'keywords_count' => (int) ($row['keywords_count'] ?? 0),
                'gsc_connected' => (bool) ($row['gsc_connected'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'seo-tracking';
        }
    } catch (\Exception $e) {}

    // AI Content
    try {
        $rows = Database::fetchAll(
            "SELECT ap.global_project_id, ap.id,
                    COUNT(a.id) as articles_total,
                    SUM(CASE WHEN a.status = 'ready' THEN 1 ELSE 0 END) as articles_ready,
                    SUM(CASE WHEN a.status = 'published' THEN 1 ELSE 0 END) as articles_published,
                    MAX(a.created_at) as last_activity
             FROM aic_projects ap
             LEFT JOIN aic_articles a ON a.project_id = ap.id
             WHERE ap.global_project_id IN ($placeholders)
             GROUP BY ap.id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['ai-content'] = [
                'module_project_id' => (int) $row['id'],
                'articles_total' => (int) ($row['articles_total'] ?? 0),
                'articles_ready' => (int) ($row['articles_ready'] ?? 0),
                'articles_published' => (int) ($row['articles_published'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'ai-content';
            $this->updateLastActivity($indexed[$gpId], $row['last_activity']);
        }
    } catch (\Exception $e) {}

    // Keyword Research
    try {
        $rows = Database::fetchAll(
            "SELECT global_project_id, COUNT(*) as cnt, MAX(created_at) as last_at
             FROM kr_projects WHERE global_project_id IN ($placeholders)
             GROUP BY global_project_id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['keyword-research'] = [
                'projects_count' => (int) ($row['cnt'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'keyword-research';
            $this->updateLastActivity($indexed[$gpId], $row['last_at']);
        }
    } catch (\Exception $e) {}

    // Ads Analyzer
    try {
        $rows = Database::fetchAll(
            "SELECT global_project_id, COUNT(*) as cnt, MAX(created_at) as last_at
             FROM ga_projects WHERE global_project_id IN ($placeholders)
             GROUP BY global_project_id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['ads-analyzer'] = [
                'projects_count' => (int) ($row['cnt'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'ads-analyzer';
            $this->updateLastActivity($indexed[$gpId], $row['last_at']);
        }
    } catch (\Exception $e) {}

    // Internal Links
    try {
        $rows = Database::fetchAll(
            "SELECT global_project_id, COUNT(*) as cnt, MAX(created_at) as last_at
             FROM il_projects WHERE global_project_id IN ($placeholders)
             GROUP BY global_project_id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['internal-links'] = [
                'projects_count' => (int) ($row['cnt'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'internal-links';
            $this->updateLastActivity($indexed[$gpId], $row['last_at']);
        }
    } catch (\Exception $e) {}

    // Content Creator
    try {
        $rows = Database::fetchAll(
            "SELECT global_project_id, COUNT(*) as cnt, MAX(created_at) as last_at
             FROM cc_projects WHERE global_project_id IN ($placeholders)
             GROUP BY global_project_id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['content-creator'] = [
                'projects_count' => (int) ($row['cnt'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'content-creator';
            $this->updateLastActivity($indexed[$gpId], $row['last_at']);
        }
    } catch (\Exception $e) {}

    // Crawl Budget
    try {
        $rows = Database::fetchAll(
            "SELECT global_project_id, COUNT(*) as cnt, MAX(created_at) as last_at
             FROM cb_projects WHERE global_project_id IN ($placeholders)
             GROUP BY global_project_id",
            $projectIds
        );
        foreach ($rows as $row) {
            $gpId = (int) $row['global_project_id'];
            if (!isset($indexed[$gpId])) continue;
            $indexed[$gpId]['modules_data']['crawl-budget'] = [
                'projects_count' => (int) ($row['cnt'] ?? 0),
            ];
            $indexed[$gpId]['active_modules'][] = 'crawl-budget';
            $this->updateLastActivity($indexed[$gpId], $row['last_at']);
        }
    } catch (\Exception $e) {}

    // --- Calcola health_status e primary_action per ogni progetto ---
    foreach ($projects as &$project) {
        $project['active_modules_count'] = count($project['active_modules']);
        $md = $project['modules_data'];

        // Health status
        $project['health_status'] = $this->computeHealthStatus($md);

        // Primary action (prima che matcha, in ordine di priorità)
        $project['primary_action'] = $this->computePrimaryAction($project);
    }
    unset($project);

    // Ordina: red first, then amber, then green, then gray
    $healthOrder = ['red' => 0, 'amber' => 1, 'green' => 2, 'gray' => 3];
    usort($projects, function ($a, $b) use ($healthOrder) {
        return ($healthOrder[$a['health_status']] ?? 3) <=> ($healthOrder[$b['health_status']] ?? 3);
    });

    return $projects;
}

/**
 * Aggiorna last_module_activity se la nuova data è più recente.
 */
private function updateLastActivity(array &$project, ?string $date): void
{
    if ($date && ($project['last_module_activity'] === null || $date > $project['last_module_activity'])) {
        $project['last_module_activity'] = $date;
    }
}

/**
 * Calcola lo stato di salute del progetto basato sui moduli.
 */
private function computeHealthStatus(array $modulesData): string
{
    if (empty($modulesData)) {
        return 'gray';
    }

    $sa = $modulesData['seo-audit'] ?? null;
    if ($sa) {
        if (($sa['health_score'] ?? 0) < 40) return 'red';
        if (($sa['health_score'] ?? 0) < 70) return 'amber';
    }

    // Controlla se ci sono azioni urgenti
    $aic = $modulesData['ai-content'] ?? null;
    if ($aic && ($aic['articles_ready'] ?? 0) > 0) return 'amber';

    $st = $modulesData['seo-tracking'] ?? null;
    if ($st && ($st['keywords_count'] ?? 0) > 0 && !($st['gsc_connected'] ?? false)) return 'amber';

    return 'green';
}

/**
 * Determina l'azione primaria per un progetto.
 * Ritorna null se nessuna azione urgente, o array con text, cta, url, slug.
 */
private function computePrimaryAction(array $project): ?array
{
    $md = $project['modules_data'];
    $gpId = $project['id'];

    // 1. SEO Audit — problemi
    $sa = $md['seo-audit'] ?? null;
    if ($sa && ($sa['issues_count'] ?? 0) > 0 && ($sa['status'] ?? '') === 'completed') {
        $n = $sa['issues_count'];
        return [
            'text' => $n . ' problem' . ($n > 1 ? 'i' : 'a') . ' trovat' . ($n > 1 ? 'i' : 'o') . ' nel SEO Audit',
            'cta' => 'Vedi piano',
            'url' => '/seo-audit/project/' . $sa['module_project_id'] . '/results',
            'slug' => 'seo-audit',
            'severity' => 'warning',
        ];
    }

    // 2. AI Content — articoli pronti
    $aic = $md['ai-content'] ?? null;
    if ($aic && ($aic['articles_ready'] ?? 0) > 0) {
        $n = $aic['articles_ready'];
        return [
            'text' => $n . ' articol' . ($n > 1 ? 'i pronti' : 'o pronto') . ' da pubblicare',
            'cta' => 'Pubblica',
            'url' => '/ai-content/projects/' . $aic['module_project_id'],
            'slug' => 'ai-content',
            'severity' => 'info',
        ];
    }

    // 3. SEO Tracking — GSC non collegato
    $st = $md['seo-tracking'] ?? null;
    if ($st && ($st['keywords_count'] ?? 0) > 0 && !($st['gsc_connected'] ?? false)) {
        return [
            'text' => 'Collega Google Search Console per dati reali',
            'cta' => 'Collega',
            'url' => '/seo-tracking/project/' . $st['module_project_id'] . '/settings',
            'slug' => 'seo-tracking',
            'severity' => 'info',
        ];
    }

    // 4. Nessuna azione urgente — suggerisci modulo da attivare
    $allSlugs = ['seo-audit', 'seo-tracking', 'ai-content', 'keyword-research', 'ads-analyzer', 'internal-links'];
    $activeSlugs = $project['active_modules'];
    $missing = array_diff($allSlugs, $activeSlugs);
    if (!empty($missing)) {
        $suggestions = [
            'seo-audit' => 'Attiva SEO Audit per analizzare il sito',
            'seo-tracking' => 'Attiva SEO Tracking per monitorare le posizioni',
            'ai-content' => 'Attiva AI Content per generare articoli',
            'keyword-research' => 'Attiva Keyword Research per trovare opportunità',
        ];
        foreach ($suggestions as $slug => $text) {
            if (in_array($slug, $missing)) {
                return [
                    'text' => $text,
                    'cta' => 'Attiva',
                    'url' => '/projects/' . $gpId,
                    'slug' => $slug,
                    'severity' => 'suggestion',
                ];
            }
        }
    }

    return null;
}
```

**Step 2: Verify syntax**

Run: `php -l core/Models/GlobalProject.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add core/Models/GlobalProject.php
git commit -m "feat(dashboard): add allWithDashboardData() batch query method on GlobalProject"
```

---

## Task 2: Update dashboard route handler

**Files:**
- Modify: `public/index.php` (lines 515-656, the `/dashboard` route)

**Why:** Replace user-level widget queries with the new `allWithDashboardData()` call. Add `unusedModules` computation for the "Scopri cosa puoi fare" section.

**Step 1: Replace the route handler**

Replace the entire `/dashboard` route handler (lines 515-656 of `public/index.php`) with:

```php
Router::get('/dashboard', function () {
    Middleware::auth();

    $user = Auth::user();
    $uid = $user['id'];
    $modules = ModuleLoader::getUserModules($uid);
    $_credits = (float) ($user['credits'] ?? 0);

    // --- Stats header ---
    $usageToday = (float) Database::fetch(
        "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$uid]
    )['total'];

    $usageMonth = (float) Database::fetch(
        "SELECT COALESCE(SUM(credits_used), 0) as total FROM usage_log WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$uid]
    )['total'];

    // --- Global Projects con KPI per modulo (batch queries) ---
    $gpModel = new \Core\Models\GlobalProject();
    $globalProjects = [];
    try {
        $globalProjects = $gpModel->allWithDashboardData($uid);
    } catch (\Exception $e) {}

    // --- Conta azioni urgenti ---
    $urgentActionsCount = 0;
    foreach ($globalProjects as $gp) {
        if ($gp['primary_action'] && ($gp['primary_action']['severity'] ?? '') !== 'suggestion') {
            $urgentActionsCount++;
        }
    }

    // --- Moduli non usati (per "Scopri cosa puoi fare") ---
    $usedModuleSlugs = [];
    foreach ($globalProjects as $gp) {
        foreach ($gp['active_modules'] as $slug) {
            $usedModuleSlugs[$slug] = true;
        }
    }
    $allModuleSlugs = array_column($modules, 'slug');
    $unusedModuleSlugs = array_diff($allModuleSlugs, array_keys($usedModuleSlugs));

    // --- WordPress collegato (user-level, non project-level) ---
    $wpConnected = false;
    try {
        $wpConnected = (bool) Database::fetch(
            "SELECT COUNT(*) as cnt FROM aic_wp_sites WHERE user_id = ? AND is_active = 1", [$uid]
        )['cnt'];
    } catch (\Exception $e) {}

    // --- Switch new/active user ---
    $isNewUser = empty($globalProjects);

    return View::render('dashboard', [
        'title' => 'Dashboard',
        'user' => $user,
        'modules' => $modules,
        'credits' => $_credits,
        'usageToday' => $usageToday,
        'usageMonth' => $usageMonth,
        'globalProjects' => $globalProjects,
        'urgentActionsCount' => $urgentActionsCount,
        'unusedModuleSlugs' => $unusedModuleSlugs,
        'wpConnected' => $wpConnected,
        'isNewUser' => $isNewUser,
    ]);
});
```

**Step 2: Verify syntax**

Run: `php -l public/index.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add public/index.php
git commit -m "feat(dashboard): update route handler with per-project KPI data"
```

---

## Task 3: Create project-card component

**Files:**
- Create: `shared/views/components/dashboard-project-card.php`

**Why:** Reusable component for the project card with health indicator, module icons, KPIs, and primary action.

**Step 1: Create the component**

Create `shared/views/components/dashboard-project-card.php`:

```php
<?php
/**
 * Dashboard Project Card Component
 *
 * Mostra una card progetto con KPI per modulo, indicatore salute, azione primaria.
 *
 * Parametri:
 * - $project: array dal GlobalProject::allWithDashboardData()
 */

$gpId = $project['id'];
$gpName = htmlspecialchars($project['name'] ?? 'Progetto');
$gpUrl = $project['website_url'] ?? null;
$gpColor = $project['color'] ?? '#3B82F6';
$health = $project['health_status'] ?? 'gray';
$activeModules = $project['active_modules'] ?? [];
$modulesData = $project['modules_data'] ?? [];
$action = $project['primary_action'] ?? null;
$modulesCount = $project['active_modules_count'] ?? 0;

// Health indicator colors
$healthColors = [
    'red' => 'bg-red-500',
    'amber' => 'bg-amber-500',
    'green' => 'bg-emerald-500',
    'gray' => 'bg-slate-400',
];
$healthDot = $healthColors[$health] ?? $healthColors['gray'];

// Module icon paths (Heroicons) — stesse del layout
$moduleIcons = [
    'ai-content' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    'seo-audit' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'seo-tracking' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    'keyword-research' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'ads-analyzer' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z',
    'internal-links' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    'content-creator' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
    'crawl-budget' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
];

$moduleColorClasses = [
    'ai-content' => 'text-amber-500',
    'seo-audit' => 'text-emerald-500',
    'seo-tracking' => 'text-blue-500',
    'keyword-research' => 'text-purple-500',
    'ads-analyzer' => 'text-rose-500',
    'internal-links' => 'text-cyan-500',
    'content-creator' => 'text-indigo-500',
    'crawl-budget' => 'text-orange-500',
];

// Build KPIs array (max 3, priorità: audit → tracking → content → kw → ads → links)
$kpis = [];
$kpiPriority = ['seo-audit', 'seo-tracking', 'ai-content', 'keyword-research', 'ads-analyzer', 'internal-links'];

foreach ($kpiPriority as $slug) {
    if (count($kpis) >= 3) break;
    $data = $modulesData[$slug] ?? null;
    if (!$data) continue;

    switch ($slug) {
        case 'seo-audit':
            $score = $data['health_score'] ?? 0;
            $scoreColor = $score >= 70 ? 'text-emerald-500' : ($score >= 40 ? 'text-amber-500' : 'text-red-500');
            $kpis[] = ['value' => $score . '/100', 'label' => 'Health Score', 'color' => $scoreColor];
            break;
        case 'seo-tracking':
            $kpis[] = ['value' => $data['keywords_count'] ?? 0, 'label' => 'Keywords', 'color' => 'text-blue-500'];
            break;
        case 'ai-content':
            $ready = $data['articles_ready'] ?? 0;
            if ($ready > 0) {
                $kpis[] = ['value' => $ready . ' pronti', 'label' => 'Articoli', 'color' => 'text-amber-500'];
            } else {
                $kpis[] = ['value' => $data['articles_total'] ?? 0, 'label' => 'Articoli', 'color' => 'text-amber-500'];
            }
            break;
        case 'keyword-research':
            $kpis[] = ['value' => $data['projects_count'] ?? 0, 'label' => 'Ricerche', 'color' => 'text-purple-500'];
            break;
        case 'ads-analyzer':
            $kpis[] = ['value' => $data['projects_count'] ?? 0, 'label' => 'Campagne', 'color' => 'text-rose-500'];
            break;
        case 'internal-links':
            $kpis[] = ['value' => $data['projects_count'] ?? 0, 'label' => 'Analisi', 'color' => 'text-cyan-500'];
            break;
    }
}

// Action severity → border color
$actionBorderColors = [
    'warning' => 'border-l-amber-400',
    'info' => 'border-l-blue-400',
    'suggestion' => 'border-l-slate-300 dark:border-l-slate-600',
];
$actionIconColors = [
    'warning' => 'text-amber-500',
    'info' => 'text-blue-500',
    'suggestion' => 'text-slate-400',
];
?>

<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
    <!-- Header: salute + nome + moduli -->
    <a href="<?= url('/projects/' . $gpId) ?>" class="group block px-5 pt-4 pb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Health dot -->
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-3 h-3 rounded-full <?= $healthDot ?> ring-2 ring-offset-2 ring-offset-white dark:ring-offset-slate-800 <?= str_replace('bg-', 'ring-', $healthDot) ?>/30"></div>
                </div>
                <!-- Nome + URL -->
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate">
                        <?= $gpName ?>
                    </h3>
                    <?php if ($gpUrl): ?>
                    <p class="text-xs text-slate-400 dark:text-slate-500 truncate"><?= htmlspecialchars(preg_replace('#^https?://(www\.)?#', '', $gpUrl)) ?></p>
                    <?php else: ?>
                    <p class="text-xs text-slate-400 dark:text-slate-500"><?= $modulesCount ?> <?= $modulesCount == 1 ? 'modulo attivo' : 'moduli attivi' ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Module icons -->
            <div class="flex items-center gap-1 flex-shrink-0">
                <?php foreach ($activeModules as $slug):
                    $iconPath = $moduleIcons[$slug] ?? null;
                    $iconColor = $moduleColorClasses[$slug] ?? 'text-slate-400';
                    if (!$iconPath) continue;
                ?>
                <div class="w-5 h-5 flex items-center justify-center" title="<?= htmlspecialchars(ucfirst(str_replace('-', ' ', $slug))) ?>">
                    <svg class="w-3.5 h-3.5 <?= $iconColor ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $iconPath ?>"/>
                    </svg>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </a>

    <!-- KPIs -->
    <?php if (!empty($kpis)): ?>
    <div class="px-5 pb-3">
        <div class="grid grid-cols-3 gap-3">
            <?php foreach ($kpis as $kpi): ?>
            <div class="text-center">
                <p class="text-lg font-bold <?= $kpi['color'] ?>"><?= $kpi['value'] ?></p>
                <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider"><?= $kpi['label'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Primary Action -->
    <?php if ($action): ?>
    <div class="border-t border-slate-100 dark:border-slate-700/50 px-5 py-2.5 flex items-center justify-between gap-3 border-l-4 <?= $actionBorderColors[$action['severity'] ?? 'info'] ?? $actionBorderColors['info'] ?>">
        <p class="text-xs text-slate-600 dark:text-slate-400 truncate"><?= $action['text'] ?></p>
        <a href="<?= url($action['url']) ?>"
           class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
            <?= $action['cta'] ?>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    <?php elseif (empty($activeModules)): ?>
    <div class="border-t border-slate-100 dark:border-slate-700/50 px-5 py-2.5 flex items-center justify-between gap-3">
        <p class="text-xs text-slate-400 dark:text-slate-500">Nessun modulo attivo</p>
        <a href="<?= url('/projects/' . $gpId) ?>"
           class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
            Attiva
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    <?php else: ?>
    <div class="border-t border-slate-100 dark:border-slate-700/50 px-5 py-2.5 flex items-center gap-2">
        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
        </svg>
        <p class="text-xs text-emerald-600 dark:text-emerald-400">Tutto in ordine</p>
    </div>
    <?php endif; ?>
</div>
```

**Step 2: Verify syntax**

Run: `php -l shared/views/components/dashboard-project-card.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-project-card.php
git commit -m "feat(dashboard): add project-card component with KPIs and smart actions"
```

---

## Task 4: Create module-capabilities component

**Files:**
- Create: `shared/views/components/dashboard-module-block.php`

**Why:** Reusable block showing what a module can do, with capability list and costs. Used in both launchpad (new users) and "Scopri" section (active users).

**Step 1: Create the component**

Create `shared/views/components/dashboard-module-block.php`:

```php
<?php
/**
 * Dashboard Module Capabilities Block
 *
 * Mostra un blocco "Cosa puoi fare" per un modulo.
 *
 * Parametri:
 * - $block: array con slug, name, tagline, capabilities[], color, iconPath
 */

$slug = $block['slug'];
$name = $block['name'];
$tagline = $block['tagline'];
$capabilities = $block['capabilities'] ?? [];
$colorName = $block['color'] ?? 'slate';

$colorMap = [
    'amber' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-700/50', 'dot' => 'text-amber-400'],
    'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-700/50', 'dot' => 'text-emerald-400'],
    'blue' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-700/50', 'dot' => 'text-blue-400'],
    'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-400', 'border' => 'border-purple-200 dark:border-purple-700/50', 'dot' => 'text-purple-400'],
    'rose' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600 dark:text-rose-400', 'border' => 'border-rose-200 dark:border-rose-700/50', 'dot' => 'text-rose-400'],
    'cyan' => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-cyan-600 dark:text-cyan-400', 'border' => 'border-cyan-200 dark:border-cyan-700/50', 'dot' => 'text-cyan-400'],
    'orange' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-600 dark:text-orange-400', 'border' => 'border-orange-200 dark:border-orange-700/50', 'dot' => 'text-orange-400'],
];

$colors = $colorMap[$colorName] ?? $colorMap['blue'];
$iconPath = $block['iconPath'] ?? 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';
?>

<div class="bg-white dark:bg-slate-800 rounded-xl border <?= $colors['border'] ?> shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
    <!-- Header -->
    <div class="px-5 pt-5 pb-3">
        <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-xl <?= $colors['bg'] ?> flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5 <?= $colors['text'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $iconPath ?>"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($name) ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($tagline) ?></p>
            </div>
        </div>
    </div>

    <!-- Capabilities list -->
    <div class="px-5 pb-3 space-y-2">
        <?php foreach ($capabilities as $cap): ?>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-3.5 h-3.5 <?= $colors['dot'] ?> flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="text-sm text-slate-700 dark:text-slate-300 truncate"><?= htmlspecialchars($cap['text']) ?></span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500 flex-shrink-0 font-medium"><?= htmlspecialchars($cap['cost']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CTA -->
    <div class="border-t border-slate-100 dark:border-slate-700/50 px-5 py-3">
        <a href="<?= url('/projects/create') ?>"
           class="inline-flex items-center gap-1.5 text-sm font-medium <?= $colors['text'] ?> hover:opacity-80 transition-opacity">
            Inizia
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
    </div>
</div>
```

**Step 2: Verify syntax**

Run: `php -l shared/views/components/dashboard-module-block.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add shared/views/components/dashboard-module-block.php
git commit -m "feat(dashboard): add module-capabilities block component"
```

---

## Task 5: Rewrite dashboard.php view

**Files:**
- Modify: `shared/views/dashboard.php` (full rewrite)

**Why:** Replace entire view with new layout: header → alerts → project cards → quick tools → discover (active users) OR header → module blocks → docs tip (new users).

**Step 1: Rewrite the view**

Replace the entire content of `shared/views/dashboard.php` with the new layout.

The file is long (~469 lines). The structure is:

```
1. Data maps (module icons, colors, names) — KEEP existing maps at top
2. Helpers — KEEP greeting logic
3. Module capabilities data — NEW (array of blocks for "Cosa puoi fare")
4. Active user mode — NEW (header, alerts, project cards, quick tools, discover)
5. New user mode — NEW (header, module blocks, docs tip)
```

Key points for the engineer:
- Keep the `$moduleIcons`, `$moduleColors`, `$moduleNames` maps from lines 1-33 — they're reused
- Keep the greeting logic from lines 35-42
- Replace everything from line 44 onward

The new section structure for active users:

**a) Header** — Greeting + summary line ("N progetti · N azioni") + credits badge right-aligned

**b) Global alerts** — Only credit warnings (< 3 red, 3-10 amber). These are account-level, not project-level.

**c) Project cards grid** — `grid-cols-1 lg:grid-cols-2 gap-4`. Render via `View::partial('components/dashboard-project-card', ['project' => $gp])`. Max 6, with "Vedi tutti" link if more.

**d) Quick tools bar** — Inline pills for each active module (`inline-flex gap-2`).

**e) "Scopri cosa puoi fare"** — Only for `$unusedModuleSlugs`. Uses the module-block component.

The new section structure for new users:

**a) Header** — "Benvenuto su Ainstein, {name}!" + "Scegli uno strumento per iniziare."

**b) Module blocks grid** — `grid-cols-1 lg:grid-cols-2 gap-4`. All available modules.

**c) Docs tip banner** — Same as current.

The module capabilities data (`$moduleBlocks` array) should be defined at the top of the view, with this structure per module:

```php
$moduleBlocks = [
    'keyword-research' => [
        'slug' => 'keyword-research',
        'name' => 'Keyword Research',
        'tagline' => 'Trova le keyword giuste per il tuo business',
        'color' => 'purple',
        'iconPath' => $moduleIcons['keyword-research'],
        'capabilities' => [
            ['text' => 'Ricerca guidata con AI', 'cost' => 'da 2 crediti'],
            ['text' => 'Architettura sito completa', 'cost' => 'da 5 crediti'],
            ['text' => 'Piano editoriale', 'cost' => 'da 5 crediti'],
            ['text' => 'Quick check keyword', 'cost' => 'gratis'],
        ],
    ],
    'ai-content' => [
        'slug' => 'ai-content',
        'name' => 'AI Content Generator',
        'tagline' => 'Scrivi e pubblica articoli SEO ottimizzati',
        'color' => 'amber',
        'iconPath' => $moduleIcons['ai-content'],
        'capabilities' => [
            ['text' => 'Articoli SEO completi', 'cost' => 'da 3 crediti'],
            ['text' => 'Meta tag ottimizzati', 'cost' => 'da 1 credito'],
            ['text' => 'Pubblicazione WordPress', 'cost' => 'automatica'],
        ],
    ],
    'seo-audit' => [
        'slug' => 'seo-audit',
        'name' => 'SEO Audit',
        'tagline' => 'Scopri cosa migliorare nel tuo sito',
        'color' => 'emerald',
        'iconPath' => $moduleIcons['seo-audit'],
        'capabilities' => [
            ['text' => 'Audit tecnico completo', 'cost' => 'da 2 crediti'],
            ['text' => 'Piano d\'azione prioritizzato', 'cost' => 'incluso'],
            ['text' => 'Report esportabile', 'cost' => 'incluso'],
        ],
    ],
    'seo-tracking' => [
        'slug' => 'seo-tracking',
        'name' => 'SEO Tracking',
        'tagline' => 'Monitora le posizioni su Google ogni giorno',
        'color' => 'blue',
        'iconPath' => $moduleIcons['seo-tracking'],
        'capabilities' => [
            ['text' => 'Tracking keyword giornaliero', 'cost' => '1 cr/check'],
            ['text' => 'Dati reali da Search Console', 'cost' => 'gratis'],
            ['text' => 'Report AI settimanale', 'cost' => '1 credito'],
        ],
    ],
    'ads-analyzer' => [
        'slug' => 'ads-analyzer',
        'name' => 'Google Ads Analyzer',
        'tagline' => 'Analizza o crea campagne Google Ads',
        'color' => 'rose',
        'iconPath' => $moduleIcons['ads-analyzer'],
        'capabilities' => [
            ['text' => 'Analisi campagna esistente', 'cost' => 'da 2 crediti'],
            ['text' => 'Creazione campagna da zero', 'cost' => 'da 3 crediti'],
            ['text' => 'Valutazione performance', 'cost' => 'da 2 crediti'],
        ],
    ],
    'internal-links' => [
        'slug' => 'internal-links',
        'name' => 'Internal Links',
        'tagline' => 'Ottimizza la struttura dei link interni',
        'color' => 'cyan',
        'iconPath' => $moduleIcons['internal-links'],
        'capabilities' => [
            ['text' => 'Scansione struttura link', 'cost' => '1 credito'],
            ['text' => 'Mappa link interni', 'cost' => 'incluso'],
        ],
    ],
];
```

**Step 2: Verify syntax**

Run: `php -l shared/views/dashboard.php`
Expected: `No syntax errors detected`

**Step 3: Test in browser**

Navigate to `http://localhost/seo-toolkit/dashboard` and verify:
- Active user: header with summary, project cards with KPIs, quick tools, discover section
- New user: module blocks with capabilities
- Dark mode works
- Mobile responsive (1 column on small screens)

**Step 4: Commit**

```bash
git add shared/views/dashboard.php
git commit -m "feat(dashboard): rewrite view with project-centric cards and module capabilities"
```

---

## Task 6: Visual polish and browser verification

**Files:**
- Possibly tweak: `shared/views/dashboard.php`, `shared/views/components/dashboard-project-card.php`

**Step 1: Check all pages load without errors**

- Navigate to `/dashboard` as active user (admin@seo-toolkit.local / admin123)
- Check browser console for JS errors
- Check PHP error log for warnings
- Verify sidebar renders (proves `$user` is passed correctly)

**Step 2: Check responsive layout**

- Desktop (lg): 2-column grid for project cards and module blocks
- Tablet (md): same 2 columns
- Mobile (sm): single column

**Step 3: Check dark mode**

Toggle dark mode and verify:
- Project cards have correct dark backgrounds
- Health dots are visible
- KPI text is readable
- Action bar has correct contrast

**Step 4: Verify edge cases**

- User with 0 projects → sees module blocks (new user mode)
- User with 1 project, 0 modules → sees project card with "Attiva" CTA
- User with projects but no audit data → health dot is gray
- User with > 6 projects → sees "Vedi tutti" link
- Credit < 3 → red banner visible
- Credit 3-10 → amber banner visible
- Credit > 10 → no banner

**Step 5: Final commit if tweaks were needed**

```bash
git add -A
git commit -m "fix(dashboard): visual polish and edge case fixes"
```

---

## Summary

| Task | Files | Action |
|------|-------|--------|
| 1 | `core/Models/GlobalProject.php` | Add `allWithDashboardData()` batch query method |
| 2 | `public/index.php` | Update dashboard route handler |
| 3 | `shared/views/components/dashboard-project-card.php` | Create project card component |
| 4 | `shared/views/components/dashboard-module-block.php` | Create module capabilities block |
| 5 | `shared/views/dashboard.php` | Rewrite view with new layout |
| 6 | Various | Visual polish and browser verification |
