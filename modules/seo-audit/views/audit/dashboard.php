<?php
// Health score colors
$score = $project['health_score'] ?? 0;
if ($score >= 80) {
    $scoreColor = 'text-emerald-600 dark:text-emerald-400';
    $scoreBg = 'bg-emerald-500';
    $scoreRing = 'stroke-emerald-500';
} elseif ($score >= 50) {
    $scoreColor = 'text-yellow-600 dark:text-yellow-400';
    $scoreBg = 'bg-yellow-500';
    $scoreRing = 'stroke-yellow-500';
} else {
    $scoreColor = 'text-red-600 dark:text-red-400';
    $scoreBg = 'bg-red-500';
    $scoreRing = 'stroke-red-500';
}

// Category icons SVG
$categoryIcons = [
    'meta' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    'headings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>',
    'images' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    'links' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
    'content' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>',
    'technical' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'schema' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
    'security' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    'sitemap' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>',
    'robots' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"/>',
];

$categoryColors = [
    'meta' => 'blue',
    'headings' => 'purple',
    'images' => 'pink',
    'links' => 'indigo',
    'content' => 'amber',
    'technical' => 'slate',
    'schema' => 'emerald',
    'security' => 'red',
    'sitemap' => 'cyan',
    'robots' => 'orange',
];
?>

<div class="space-y-6">
    <!-- Header con Health Score -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Info Progetto -->
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
                        <a href="<?= e($project['base_url']) ?>" target="_blank" class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 flex items-center gap-1">
                            <?= e($project['base_url']) ?>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <?= $project['completed_at'] ? date('d/m/Y H:i', strtotime($project['completed_at'])) : 'In corso' ?>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <?= number_format($pageStats['total']) ?> pagine analizzate
                    </span>
                    <?php if ($project['gsc_connected']): ?>
                    <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        GSC Connesso
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Score Gauge -->
            <div class="flex items-center gap-6">
                <div class="relative w-32 h-32">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" fill="none" stroke-width="12" class="stroke-slate-200 dark:stroke-slate-700"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke-width="12" class="<?= $scoreRing ?>" stroke-linecap="round"
                                stroke-dasharray="<?= 314 * ($score / 100) ?> 314" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold <?= $scoreColor ?>"><?= $score ?></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">/ 100</span>
                    </div>
                </div>
                <div class="text-sm">
                    <p class="font-medium text-slate-900 dark:text-white mb-2">Health Score</p>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="text-slate-600 dark:text-slate-400"><?= $issueCounts['critical'] ?> critici</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span class="text-slate-600 dark:text-slate-400"><?= $issueCounts['warning'] ?> warning</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                            <span class="text-slate-600 dark:text-slate-400"><?= $issueCounts['notice'] ?> notice</span>
                        </div>
                    </div>
                    <?php
                    // Trend vs crawl precedente
                    $crawlHistory = \Core\Database::fetchAll("
                        SELECT id, health_score, critical_count, warning_count, created_at
                        FROM sa_crawl_sessions
                        WHERE project_id = ? AND status = 'completed' AND health_score > 0
                        ORDER BY id DESC LIMIT 5
                    ", [$project['id']]);
                    $previousScore = isset($crawlHistory[1]) ? $crawlHistory[1]['health_score'] : null;
                    $scoreDiff = $previousScore !== null ? ($score - $previousScore) : null;
                    ?>
                    <?php if ($scoreDiff !== null): ?>
                    <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <?php if ($scoreDiff > 0): ?>
                            <span class="text-emerald-600 dark:text-emerald-400 text-xs font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                +<?= $scoreDiff ?> vs precedente
                            </span>
                        <?php elseif ($scoreDiff < 0): ?>
                            <span class="text-red-600 dark:text-red-400 text-xs font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                <?= $scoreDiff ?> vs precedente
                            </span>
                        <?php else: ?>
                            <span class="text-slate-500 dark:text-slate-400 text-xs">= invariato</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($pageStats['total']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pagine Totali</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($issueCounts['critical']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Issues Critiche</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($pageStats['indexable']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Indicizzabili</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($pageStats['avg_load_time']) ?><span class="text-sm font-normal text-slate-500">ms</span></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tempo Medio</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Spider Crawl Control - SEMPRE visibile (accordion gestisce aperto/chiuso) -->
    <?php
    // Prepara variabili per il partial
    $session = null;
    if ($project['current_session_id'] ?? null) {
        $session = \Core\Database::fetch(
            "SELECT * FROM sa_crawl_sessions WHERE id = ?",
            [$project['current_session_id']]
        );
    }
    include __DIR__ . '/../partials/crawl-control.php';
    ?>

    <!-- Categorie Grid -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Categorie Audit</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <?php foreach ($categoryStats as $slug => $cat): ?>
            <?php if ($cat['is_gsc']) continue; // Skip GSC categories ?>
            <?php
            $color = $categoryColors[$slug] ?? 'slate';
            $hasIssues = $cat['total'] > 0;
            ?>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $slug) ?>"
               class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md hover:border-<?= $color ?>-300 dark:hover:border-<?= $color ?>-700 transition-all group">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-<?= $color ?>-100 dark:bg-<?= $color ?>-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-<?= $color ?>-600 dark:text-<?= $color ?>-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <?= $categoryIcons[$slug] ?? $categoryIcons['technical'] ?>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-slate-900 dark:text-white"><?= e($cat['label']) ?></h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= $cat['total'] ?> issue<?= $cat['total'] !== 1 ? 's' : '' ?></p>
                    </div>
                </div>
                <?php if ($hasIssues): ?>
                <div class="flex items-center gap-2">
                    <?php if ($cat['critical'] > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                        <?= $cat['critical'] ?> critici
                    </span>
                    <?php endif; ?>
                    <?php if ($cat['warning'] > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300">
                        <?= $cat['warning'] ?> warning
                    </span>
                    <?php endif; ?>
                    <?php if ($cat['notice'] > 0 && $cat['critical'] == 0 && $cat['warning'] == 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        <?= $cat['notice'] ?> notice
                    </span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 text-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Nessun problema
                </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Issues -->
    <?php if (!empty($topIssues)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Problemi Principali</h2>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/issues') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 flex items-center gap-1">
                Vedi tutti
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($topIssues as $issue): ?>
            <?php
            $severityColors = [
                'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                'notice' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'info' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
            ];
            $severityColor = $severityColors[$issue['severity']] ?? $severityColors['info'];
            ?>
            <div class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-start gap-4">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $severityColor ?>">
                        <?= ucfirst($issue['severity']) ?>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white"><?= e($issue['title']) ?></p>
                        <?php if ($issue['page_url']): ?>
                        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $issue['page_id']) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 truncate block">
                            <?= e($issue['page_url']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $issue['category']) ?>" class="text-xs text-slate-500 dark:text-slate-400 hover:text-primary-600 whitespace-nowrap">
                        <?= \Modules\SeoAudit\Models\Issue::CATEGORIES[$issue['category']] ?? $issue['category'] ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sezione Analisi AI -->
    <?php
    // Controlla se esiste un'analisi AI
    $aiService = new \Modules\SeoAudit\Services\AiAnalysisService();
    $aiAnalysis = $aiService->getAnalysis($project['id'], 'overview');
    $overviewCost = $aiService->getCost('overview');
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Analisi AI</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Raccomandazioni intelligenti per migliorare il tuo sito</p>
                </div>
            </div>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                Crediti: <span class="font-semibold text-slate-900 dark:text-white"><?= $credits['balance'] ?></span>
            </span>
        </div>

        <div class="p-6">
            <?php if ($aiAnalysis): ?>
            <!-- Analisi Esistente -->
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium text-slate-900 dark:text-white">Analisi Overview Generata</h3>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y H:i', strtotime($aiAnalysis['created_at'])) ?>
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        <?= e(substr(strip_tags($aiAnalysis['content']), 0, 200)) ?>...
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/analysis') ?>"
                           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Leggi Analisi Completa
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- CTA Genera Analisi -->
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <div class="flex-1">
                    <h3 class="font-medium text-slate-900 dark:text-white mb-2">Genera Analisi AI Overview</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        L'AI analizzerà tutti i dati dell'audit e fornirà una panoramica completa con priorità,
                        punti di forza e una roadmap di intervento.
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <?php if ($issueCounts['total'] > 0): ?>
                    <a href="<?= url('/seo-audit/project/' . $project['id'] . '/analysis') ?>"
                       class="inline-flex items-center px-5 py-2.5 rounded-lg bg-gradient-to-r from-primary-600 to-purple-600 text-white text-sm font-medium hover:from-primary-700 hover:to-purple-700 transition-all shadow-sm hover:shadow-md">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Genera Analisi (<?= $overviewCost ?> crediti)
                    </a>
                    <?php if ($credits['balance'] < $overviewCost): ?>
                    <span class="text-xs text-red-600 dark:text-red-400">Crediti insufficienti</span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Esegui prima un crawl per generare l'analisi
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Links Analisi Categorie -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Analisi per categoria:</span>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/analysis') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700">
                    Vedi tutte
                </a>
            </div>
        </div>
    </div>

    <!-- Sezione Google Search Console -->
    <?php
    $gscService = new \Modules\SeoAudit\Services\GscService();
    $gscConnection = $gscService->getConnection($project['id']);
    $gscStats = $gscConnection ? $gscService->getStats($project['id'], 28) : null;
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 via-red-500 to-yellow-500 p-0.5">
                    <div class="w-full h-full rounded-md bg-white dark:bg-slate-800 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Google Search Console</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Dati di performance dalla ricerca Google</p>
                </div>
            </div>
            <?php if ($gscConnection): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Connesso
            </span>
            <?php endif; ?>
        </div>

        <div class="p-6">
            <?php if ($gscConnection): ?>
            <!-- GSC Connected - Show Stats -->
            <div class="space-y-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Proprietà: <strong class="text-slate-900 dark:text-white"><?= e($gscConnection['property_url']) ?></strong></span>
                    <?php if ($gscConnection['last_sync_at']): ?>
                    <span class="text-slate-400 dark:text-slate-500">Ultimo sync: <?= date('d/m/Y H:i', strtotime($gscConnection['last_sync_at'])) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($gscStats && !empty($gscStats['totals'])): ?>
                <!-- Mini Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($gscStats['totals']['clicks'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Click</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?= number_format($gscStats['totals']['impressions'] ?? 0) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Impressioni</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format(($gscStats['totals']['ctr'] ?? 0) * 100, 2) ?>%</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">CTR</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($gscStats['totals']['position'] ?? 0, 1) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pos. Media</p>
                    </div>
                </div>

                <!-- Top 3 Queries Preview -->
                <?php if (!empty($gscStats['queries'])): ?>
                <div class="mt-4">
                    <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Top Query</h4>
                    <div class="space-y-2">
                        <?php foreach (array_slice($gscStats['queries'], 0, 3) as $query): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400 truncate max-w-[60%]"><?= e($query['query']) ?></span>
                            <div class="flex items-center gap-4 text-xs">
                                <span class="text-slate-500"><?= number_format($query['clicks']) ?> click</span>
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                    Pos. <?= number_format($query['position'], 1) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">Nessun dato disponibile. Sincronizza per importare i dati.</p>
                </div>
                <?php endif; ?>

                <div class="flex items-center justify-center pt-2">
                    <a href="<?= url('/seo-audit/project/' . $project['id'] . '/gsc') ?>"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Vai alla Dashboard GSC
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- GSC Not Connected - Show CTA -->
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <div class="flex-1">
                    <h3 class="font-medium text-slate-900 dark:text-white mb-2">Connetti Google Search Console</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Importa dati di performance, query di ricerca e metriche di posizionamento
                        direttamente da Google Search Console per una visione completa della SEO del tuo sito.
                    </p>
                    <ul class="mt-3 space-y-1 text-sm text-slate-500 dark:text-slate-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Click e impressioni organiche
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Query di ricerca con posizione
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            CTR e trend di performance
                        </li>
                    </ul>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <a href="<?= url('/seo-audit/project/' . $project['id'] . '/gsc/connect') ?>"
                       class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        </svg>
                        Connetti GSC
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
