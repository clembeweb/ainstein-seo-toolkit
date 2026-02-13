<?php
$currentPath = \Core\Router::currentPath();
$isAdmin = ($user['role'] ?? '') === 'admin';

if (!function_exists('navLink')) {
    function navLink($path, $label, $icon, $currentPath, $exact = false) {
        $isActive = $exact ? $currentPath === $path : str_starts_with($currentPath, $path);
        $baseClass = 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors';
        $activeClass = $isActive
            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white';

        return sprintf(
            '<a href="%s" class="%s %s">%s<span>%s</span></a>',
            url($path),
            $baseClass,
            $activeClass,
            $icon,
            $label
        );
    }
}

// Onboarding: moduli completati per icona "?" tour
$onbCompletedModules = [];
$onbModuleSlugs = ['ai-content', 'seo-audit', 'seo-tracking', 'keyword-research', 'internal-links', 'ads-analyzer'];
if (isset($user['id']) && class_exists('\\Core\\OnboardingService')) {
    $onbCompletedModules = \Core\OnboardingService::getCompletedModules($user['id']);
}

if (!function_exists('navTourButton')) {
    function navTourButton($slug, $completedModules) {
        $isCompleted = in_array($slug, $completedModules);
        $dot = !$isCompleted ? '<span class="absolute top-0.5 right-0.5 h-1.5 w-1.5 rounded-full bg-primary-500"></span>' : '';
        return '<button @click.stop="$dispatch(\'open-module-tour\', { slug: \'' . $slug . '\' })" '
            . 'class="relative p-1.5 text-slate-400 hover:text-primary-500 dark:hover:text-primary-400 transition-colors" '
            . 'title="Tour guidato">'
            . '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">'
            . '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />'
            . '</svg>' . $dot . '</button>';
    }
}

if (!function_exists('navSubLink')) {
    function navSubLink($path, $label, $icon, $currentPath, $exact = false) {
        $isActive = $exact ? ($currentPath === $path) : str_starts_with($currentPath, $path);
        $baseClass = 'flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-medium transition-colors';
        $activeClass = $isActive
            ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200';

        return sprintf(
            '<a href="%s" class="%s %s">%s<span>%s</span></a>',
            url($path),
            $baseClass,
            $activeClass,
            $icon,
            $label
        );
    }
}

// Check if we're inside an internal-links project
$internalLinksProjectId = null;
$internalLinksProject = null;
if (preg_match('#^/internal-links/project/(\d+)#', $currentPath, $matches)) {
    $internalLinksProjectId = (int) $matches[1];
    // Try to get project info
    try {
        if (class_exists('\\Modules\\InternalLinks\\Models\\Project')) {
            $projectModel = new \Modules\InternalLinks\Models\Project();
            $internalLinksProject = $projectModel->find($internalLinksProjectId);
        }
    } catch (\Exception $e) {
        // Silently fail - project info not critical for navigation
    }
}

// Check if we're inside a seo-audit project
$seoAuditProjectId = null;
$seoAuditProject = null;
$seoAuditIssues = ['critical' => 0, 'warning' => 0, 'notice' => 0, 'total' => 0];
$seoAuditPending = 0;
if (preg_match('#^/seo-audit/project/(\d+)#', $currentPath, $matches)) {
    $seoAuditProjectId = (int) $matches[1];
    // Try to get project info and issue counts
    try {
        if (class_exists('\\Modules\\SeoAudit\\Models\\Project')) {
            $projectModel = new \Modules\SeoAudit\Models\Project();
            $seoAuditProject = $projectModel->find($seoAuditProjectId);

            // Get issue counts for badges
            if (class_exists('\\Modules\\SeoAudit\\Models\\Issue')) {
                $issueModel = new \Modules\SeoAudit\Models\Issue();
                $seoAuditIssues = $issueModel->countBySeverity($seoAuditProjectId);
            }

            // Get pending pages count
            $seoAuditPending = (int) \Core\Database::fetch(
                "SELECT COUNT(*) as cnt FROM sa_pages WHERE project_id = ? AND status = 'pending'",
                [$seoAuditProjectId]
            )['cnt'] ?? 0;
        }
    } catch (\Exception $e) {
        // Silently fail - project info not critical for navigation
    }
}

// Check if we're inside a seo-tracking project
$seoTrackingProjectId = null;
$seoTrackingProject = null;
if (preg_match('#^/seo-tracking/project/(\d+)#', $currentPath, $matches)) {
    $seoTrackingProjectId = (int) $matches[1];
    // Try to get project info
    try {
        if (class_exists('\\Modules\\SeoTracking\\Models\\Project')) {
            $projectModel = new \Modules\SeoTracking\Models\Project();
            $seoTrackingProject = $projectModel->find($seoTrackingProjectId);
        }
    } catch (\Exception $e) {
        // Silently fail - project info not critical for navigation
    }
}

// Check if we're inside an ai-optimizer project
$aiOptimizerProjectId = null;
$aiOptimizerProject = null;
if (preg_match('#^/ai-optimizer/project/(\d+)#', $currentPath, $matches)) {
    $aiOptimizerProjectId = (int) $matches[1];
    // Try to get project info
    try {
        if (class_exists('\\Modules\\AiOptimizer\\Models\\Project')) {
            $projectModel = new \Modules\AiOptimizer\Models\Project();
            $aiOptimizerProject = $projectModel->find($aiOptimizerProjectId);
        }
    } catch (\Exception $e) {
        // Silently fail - project info not critical for navigation
    }
}

// Check if we're inside a keyword-research project
$krProjectId = null;
$krProject = null;
if (preg_match('#^/keyword-research/project/(\d+)#', $currentPath, $matches)) {
    $krProjectId = (int) $matches[1];
    try {
        if (class_exists('\\Modules\\KeywordResearch\\Models\\Project')) {
            $projectModel = new \Modules\KeywordResearch\Models\Project();
            $krProject = $projectModel->find($krProjectId);
        }
    } catch (\Exception $e) {
        // Silently fail
    }
}

// Check if we're inside an ads-analyzer project
$adsAnalyzerProjectId = null;
$adsAnalyzerProject = null;
if (preg_match('#^/ads-analyzer/projects/(\d+)#', $currentPath, $matches)) {
    $adsAnalyzerProjectId = (int) $matches[1];
    try {
        if (class_exists('\\Modules\\AdsAnalyzer\\Models\\Project')) {
            $adsAnalyzerProject = \Modules\AdsAnalyzer\Models\Project::find($adsAnalyzerProjectId);
        }
    } catch (\Exception $e) {
        // Silently fail
    }
}

// Check if we're inside an ai-content project
$aiContentProjectId = null;
$aiContentProject = null;
if (preg_match('#^/ai-content/projects/(\d+)#', $currentPath, $matches)) {
    $aiContentProjectId = (int) $matches[1];
    // Try to get project info
    try {
        if (class_exists('\\Modules\\AiContent\\Models\\Project')) {
            $projectModel = new \Modules\AiContent\Models\Project();
            $aiContentProject = $projectModel->find($aiContentProjectId);
        }
    } catch (\Exception $e) {
        // Silently fail - project info not critical for navigation
    }
}
?>

<div class="space-y-1">
    <!-- Dashboard -->
    <?= navLink('/dashboard', 'Dashboard', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>', $currentPath, true) ?>
</div>

<?php if (!empty($modules)): ?>
<div class="mt-6">
    <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Moduli</p>
    <div class="mt-2 space-y-1">
        <?php foreach ($modules as $module): ?>
            <?php if ($module['slug'] === 'internal-links'): ?>
                <!-- Internal Links Module with Accordion -->
                <div x-data="{ expanded: <?= $internalLinksProjectId ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/internal-links') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/internal-links') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?= navTourButton('internal-links', $onbCompletedModules) ?>
                        <?php if ($internalLinksProjectId): ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($internalLinksProjectId && $internalLinksProject): ?>
                    <!-- Project Sub-navigation -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($internalLinksProject['name']) ?>">
                            <?= e(mb_substr($internalLinksProject['name'], 0, 20)) ?><?= mb_strlen($internalLinksProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <!-- Main Navigation -->
                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}", 'Dashboard', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath, true) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/urls", 'URLs', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/scrape", 'Scraping', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/links", 'Links', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/analysis", 'AI Analysis', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/links/graph", 'Link Graph', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>', $currentPath) ?>

                        <!-- Reports Separator -->
                        <div class="px-2 py-1 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-2">Reports</div>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/reports/anchors", 'Anchor Analysis', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/reports/orphans", 'Orphan Pages', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/reports/juice", 'Link Juice', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>', $currentPath) ?>

                        <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/compare", 'Compare', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>', $currentPath) ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/internal-links/project/{$internalLinksProjectId}/settings", 'Settings', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($module['slug'] === 'seo-audit'): ?>
                <!-- SEO Audit Module with Accordion -->
                <div x-data="{ expanded: <?= $seoAuditProjectId ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/seo-audit') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/seo-audit') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?= navTourButton('seo-audit', $onbCompletedModules) ?>
                        <?php if ($seoAuditProjectId): ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($seoAuditProjectId && $seoAuditProject): ?>
                    <!-- Project Sub-navigation -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($seoAuditProject['name']) ?>">
                            <?= e(mb_substr($seoAuditProject['name'], 0, 20)) ?><?= mb_strlen($seoAuditProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <!-- Main Navigation -->
                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/dashboard", 'Dashboard', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath, true) ?>

                        <!-- Pagine con badge pending -->
                        <a href="<?= url("/seo-audit/project/{$seoAuditProjectId}/pages") ?>"
                           class="flex items-center justify-between px-3 py-1.5 rounded-md text-xs font-medium transition-colors <?= str_starts_with($currentPath, "/seo-audit/project/{$seoAuditProjectId}/pages") ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200' ?>">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Pagine
                            </span>
                            <?php if ($seoAuditPending > 0): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                <?= $seoAuditPending ?>
                            </span>
                            <?php endif; ?>
                        </a>

                        <!-- Issues con badge critici -->
                        <a href="<?= url("/seo-audit/project/{$seoAuditProjectId}/issues") ?>"
                           class="flex items-center justify-between px-3 py-1.5 rounded-md text-xs font-medium transition-colors <?= str_starts_with($currentPath, "/seo-audit/project/{$seoAuditProjectId}/issues") ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200' ?>">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Issues
                            </span>
                            <?php if (($seoAuditIssues['critical'] ?? 0) > 0): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                <?= $seoAuditIssues['critical'] ?>
                            </span>
                            <?php elseif (($seoAuditIssues['total'] ?? 0) > 0): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                                <?= $seoAuditIssues['total'] ?>
                            </span>
                            <?php endif; ?>
                        </a>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/analysis", 'Analisi AI', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/action-plan", 'Piano d\'Azione', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/gsc", 'Search Console', '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="currentColor" opacity="0.8"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="currentColor" opacity="0.6"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/history", 'Storico', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', $currentPath) ?>

                        <!-- Categories Separator -->
                        <div class="px-2 py-1 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-2">Categorie</div>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/category/meta", 'Meta Tags', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/category/headings", 'Headings', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/category/images", 'Immagini', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/category/links", 'Links', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/category/content", 'Contenuto', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/category/technical", 'Tecnico', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>

                        <!-- Reports Separator -->
                        <div class="px-2 py-1 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-2">Reports</div>

                        <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/export/csv", 'Export CSV', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', $currentPath) ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/seo-audit/project/{$seoAuditProjectId}/settings", 'Impostazioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($module['slug'] === 'seo-tracking'): ?>
                <!-- SEO Tracking Module with Accordion -->
                <div x-data="{ expanded: <?= $seoTrackingProjectId ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/seo-tracking') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/seo-tracking') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?= navTourButton('seo-tracking', $onbCompletedModules) ?>
                        <?php if ($seoTrackingProjectId): ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($seoTrackingProjectId && $seoTrackingProject): ?>
                    <!-- Project Sub-navigation -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($seoTrackingProject['name']) ?>">
                            <?= e(mb_substr($seoTrackingProject['name'], 0, 20)) ?><?= mb_strlen($seoTrackingProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <!-- Main Navigation -->
                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}", 'Overview', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath, true) ?>

                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/keywords", 'Keywords', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/trend", 'Trend', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/groups", 'Gruppi', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/quick-wins", 'Quick Wins', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/page-analyzer", 'Page Analyzer', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>', $currentPath) ?>

                        <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/gsc", 'Search Console', '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="currentColor" opacity="0.8"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="currentColor" opacity="0.6"/></svg>', $currentPath) ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/seo-tracking/project/{$seoTrackingProjectId}/settings", 'Impostazioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($module['slug'] === 'ai-optimizer'): ?>
                <!-- AI Optimizer Module with Accordion -->
                <div x-data="{ expanded: <?= $aiOptimizerProjectId ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/ai-optimizer') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/ai-optimizer') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?php if ($aiOptimizerProjectId): ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($aiOptimizerProjectId && $aiOptimizerProject): ?>
                    <!-- Project Sub-navigation -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($aiOptimizerProject['name']) ?>">
                            <?= e(mb_substr($aiOptimizerProject['name'], 0, 20)) ?><?= mb_strlen($aiOptimizerProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <!-- Main Navigation -->
                        <?= navSubLink("/ai-optimizer/project/{$aiOptimizerProjectId}", 'Dashboard', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath, true) ?>

                        <?= navSubLink("/ai-optimizer/project/{$aiOptimizerProjectId}/optimize", 'Nuova Ottimizzazione', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>', $currentPath) ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/ai-optimizer/project/{$aiOptimizerProjectId}/settings", 'Impostazioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($module['slug'] === 'ai-content'): ?>
                <!-- AI Content Module with Accordion -->
                <?php $aiContentExpanded = $aiContentProjectId || str_starts_with($currentPath, '/ai-content/wordpress'); ?>
                <div x-data="{ expanded: <?= $aiContentExpanded ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/ai-content') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/ai-content') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?= navTourButton('ai-content', $onbCompletedModules) ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Sub-navigation (sempre visibile quando espanso) -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">

                    <?php if ($aiContentProjectId && $aiContentProject): ?>
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($aiContentProject['name']) ?>">
                            <?= e(mb_substr($aiContentProject['name'], 0, 20)) ?><?= mb_strlen($aiContentProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <!-- Dashboard -->
                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}", 'Dashboard', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath, true) ?>

                        <?php if (($aiContentProject['type'] ?? 'manual') === 'manual'): ?>
                        <!-- Manual Mode Navigation -->
                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/keywords", 'Keywords', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/articles", 'Articoli', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/internal-links", 'Internal Links', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>', $currentPath) ?>

                        <?php elseif (($aiContentProject['type'] ?? 'manual') === 'auto'): ?>
                        <!-- Auto Mode Navigation -->
                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/auto/add", 'Aggiungi Keywords', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/auto/queue", 'Coda', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/articles", 'Articoli', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/internal-links", 'Internal Links', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>', $currentPath) ?>

                        <?php elseif (($aiContentProject['type'] ?? 'manual') === 'meta-tag'): ?>
                        <!-- Meta-Tag Mode Navigation -->
                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/meta-tags/import", 'Importa URL', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/meta-tags/list", 'Lista URL', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>', $currentPath) ?>

                        <?php endif; ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/ai-content/projects/{$aiContentProjectId}/settings", 'Impostazioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    <?php endif; ?>

                        <!-- Global Links (sempre visibili) -->
                        <div class="<?= $aiContentProjectId ? 'pt-1 mt-1 border-t border-slate-200 dark:border-slate-700' : '' ?>">
                            <div class="px-2 py-1 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Globale</div>
                            <?= navSubLink("/ai-content/wordpress", 'Siti WordPress', '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/></svg>', $currentPath) ?>
                            <?= navSubLink("/ai-content/jobs", 'Gestione Job', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($module['slug'] === 'keyword-research'): ?>
                <!-- Keyword Research Module with Accordion -->
                <?php $krExpanded = $krProjectId || str_starts_with($currentPath, '/keyword-research/quick-check'); ?>
                <div x-data="{ expanded: <?= $krExpanded ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/keyword-research') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/keyword-research') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?= navTourButton('keyword-research', $onbCompletedModules) ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Sub-navigation -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">

                    <?php if ($krProjectId && $krProject): ?>
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($krProject['name']) ?>">
                            <?= e(mb_substr($krProject['name'], 0, 20)) ?><?= mb_strlen($krProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <?= navSubLink("/keyword-research/project/{$krProjectId}/research", 'Research Guidata', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/keyword-research/project/{$krProjectId}/architecture", 'Architettura Sito', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>', $currentPath) ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/keyword-research/project/{$krProjectId}/settings", 'Impostazioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    <?php endif; ?>

                        <!-- Quick Check (sempre visibile) -->
                        <div class="<?= $krProjectId ? 'pt-1 mt-1 border-t border-slate-200 dark:border-slate-700' : '' ?>">
                            <?= navSubLink("/keyword-research/quick-check", 'Quick Check', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($module['slug'] === 'ads-analyzer'): ?>
                <!-- Google Ads Analyzer Module with Accordion -->
                <div x-data="{ expanded: <?= $adsAnalyzerProjectId ? 'true' : 'false' ?> }">
                    <!-- Module Link -->
                    <div class="flex items-center">
                        <a href="<?= url('/ads-analyzer') ?>"
                           class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= str_starts_with($currentPath, '/ads-analyzer') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                            </svg>
                            <span class="flex-1"><?= e($module['name']) ?></span>
                        </a>
                        <?= navTourButton('ads-analyzer', $onbCompletedModules) ?>
                        <?php if ($adsAnalyzerProjectId): ?>
                        <button @click="expanded = !expanded" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($adsAnalyzerProjectId && $adsAnalyzerProject): ?>
                    <?php $adsProjectType = $adsAnalyzerProject['type'] ?? 'negative-kw'; ?>
                    <!-- Project Sub-navigation -->
                    <div x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-3 mt-1 pl-3 border-l-2 border-slate-200 dark:border-slate-700 space-y-0.5">
                        <!-- Project Name Header -->
                        <div class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 truncate" title="<?= e($adsAnalyzerProject['name']) ?>">
                            <?= e(mb_substr($adsAnalyzerProject['name'], 0, 20)) ?><?= mb_strlen($adsAnalyzerProject['name']) > 20 ? '...' : '' ?>
                        </div>

                        <?php if ($adsProjectType === 'campaign'): ?>
                        <!-- Campaign Project Navigation -->
                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/campaign-dashboard", 'Dashboard', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/campaigns", 'Campagne', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/script", 'Script Google Ads', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/script/runs", 'Esecuzioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/search-term-analysis", 'Keyword Negative', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>', $currentPath) ?>

                        <?php else: ?>
                        <!-- Negative-KW Project Navigation -->
                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}", 'Dashboard', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentPath, true) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/upload", 'Carica CSV', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/landing-urls", 'Contesto', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/results", 'Risultati', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>', $currentPath) ?>

                        <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/analyses", 'Storico', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', $currentPath) ?>

                        <?php endif; ?>

                        <!-- Settings -->
                        <div class="pt-1 mt-1 border-t border-slate-200 dark:border-slate-700">
                            <?= navSubLink("/ads-analyzer/projects/{$adsAnalyzerProjectId}/edit", 'Impostazioni', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Other Modules (standard link) -->
                <?php if (in_array($module['slug'], $onbModuleSlugs)): ?>
                <div class="flex items-center">
                    <div class="flex-1">
                        <?= navLink('/' . $module['slug'], $module['name'], '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>', $currentPath) ?>
                    </div>
                    <?= navTourButton($module['slug'], $onbCompletedModules) ?>
                </div>
                <?php else: ?>
                <?= navLink('/' . $module['slug'], $module['name'], '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>', $currentPath) ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="mt-6">
    <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Amministrazione</p>
    <div class="mt-2 space-y-1">
        <?= navLink('/admin', 'Overview', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>', $currentPath, true) ?>

        <?= navLink('/admin/users', 'Utenti', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>', $currentPath) ?>

        <?= navLink('/admin/plans', 'Piani', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>', $currentPath) ?>

        <?= navLink('/admin/modules', 'Moduli', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>', $currentPath) ?>

        <?= navLink('/admin/settings', 'Impostazioni', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', $currentPath) ?>

        <?= navLink('/admin/ai-logs', 'AI Logs', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>', $currentPath) ?>

        <?= navLink('/admin/api-logs', 'API Logs', '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>', $currentPath) ?>
    </div>
</div>
<?php endif; ?>
