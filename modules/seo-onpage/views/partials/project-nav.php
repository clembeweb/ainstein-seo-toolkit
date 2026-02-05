<?php
/**
 * Project Navigation Tabs - SEO Onpage Optimizer
 * Variables: $project, $currentPage
 */
$projectId = $project['id'];
$basePath = '/seo-onpage/project/' . $projectId;

$tabs = [
    'dashboard' => ['label' => 'Overview', 'path' => $basePath, 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    'pages' => ['label' => 'Pagine', 'path' => $basePath . '/pages', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'audit' => ['label' => 'Audit', 'path' => $basePath . '/audit', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    'issues' => ['label' => 'Issues', 'path' => $basePath . '/issues', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    'ai' => ['label' => 'AI Suggestions', 'path' => $basePath . '/ai', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
    'settings' => ['label' => 'Impostazioni', 'path' => $basePath . '/settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
];
?>

<div class="mb-6">
    <!-- Project Header -->
    <div class="flex items-center gap-4 mb-4">
        <a href="<?= url('/seo-onpage') ?>" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white truncate"><?= e($project['name']) ?></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 truncate"><?= e($project['domain']) ?></p>
        </div>
        <?php if (isset($project['stats']['avg_score']) && $project['stats']['avg_score']): ?>
        <div class="flex items-center gap-2">
            <?php
            $score = $project['stats']['avg_score'];
            $scoreColor = $score >= 80 ? 'text-emerald-600 dark:text-emerald-400' :
                         ($score >= 60 ? 'text-amber-600 dark:text-amber-400' :
                         'text-red-600 dark:text-red-400');
            ?>
            <span class="text-2xl font-bold <?= $scoreColor ?>"><?= round($score) ?></span>
            <span class="text-sm text-slate-500 dark:text-slate-400">Score</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
            <?php foreach ($tabs as $key => $tab): ?>
            <?php
            $isActive = $currentPage === $key;
            $activeClass = $isActive
                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600';
            ?>
            <a href="<?= url($tab['path']) ?>"
               class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors <?= $activeClass ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tab['icon'] ?>"/>
                </svg>
                <?= $tab['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
