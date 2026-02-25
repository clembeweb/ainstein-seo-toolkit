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