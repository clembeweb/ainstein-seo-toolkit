<?php
/**
 * Valutazione AI Campagne - Report v2 (Single-page layout)
 *
 * Variables from controller:
 * - $project, $evaluation, $aiResponse, $syncMetrics, $metricDeltas
 * - $negativeSummary, $productData, $generateUrl, $applyUrl
 * - $access_role, $currentSync, $savedFixes, $agIdMap, $assetGroupIdMap
 * - $user, $modules, $title
 */

// View states
$canEdit = ($access_role ?? 'owner') !== 'viewer';
$isError = ($evaluation['status'] ?? '') === 'error';
$isAnalyzing = ($evaluation['status'] ?? '') === 'analyzing';
$hasResults = !empty($aiResponse) && !$isError && !$isAnalyzing;
$isNoChange = !$hasResults && !$isError && !$isAnalyzing
    && ($evaluation['status'] === 'completed')
    && (($evaluation['credits_used'] ?? 1) == 0);

// Data preparation for Alpine
$overallScore = (float)($aiResponse['overall_score'] ?? 0);
$summary = $aiResponse['summary'] ?? '';
$campaigns = $aiResponse['campaigns'] ?? [];
$topRecommendations = $aiResponse['top_recommendations'] ?? [];

// Campaign type badge config
$campaignTypeConfig = [
    'SEARCH' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Search'],
    'SHOPPING' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Shopping'],
    'PERFORMANCE_MAX' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'PMax'],
    'DISPLAY' => ['bg' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300', 'label' => 'Display'],
    'VIDEO' => ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'label' => 'Video'],
];

// fix_type validi per cui AI puo generare contenuto
$validFixTypes = ['rewrite_ads', 'add_negatives', 'remove_duplicates', 'add_extensions'];

// Merge sync + AI data per campaign for the table partial
$viewCampaigns = [];
$allOptimizations = [];
if ($hasResults && !empty($syncMetrics['campaigns'])) {
    foreach ($syncMetrics['campaigns'] as $cIdx => $syncCamp) {
        $aiCamp = null;
        foreach ($campaigns as $ac) {
            if (($ac['campaign_name'] ?? '') === ($syncCamp['campaign_name'] ?? $syncCamp['name'] ?? '')) {
                $aiCamp = $ac;
                break;
            }
        }
        $viewCampaigns[] = [
            'sync' => $syncCamp,
            'ai' => $aiCamp,
            'type' => strtoupper($syncCamp['campaign_type'] ?? 'SEARCH'),
            'isPmax' => strtoupper($syncCamp['campaign_type'] ?? '') === 'PERFORMANCE_MAX',
        ];

        // Extract optimizations into flat list
        if ($aiCamp) {
            foreach ($aiCamp['ad_groups'] ?? [] as $agIdx => $ag) {
                foreach ($ag['optimizations'] ?? [] as $oIdx => $opt) {
                    $key = "opt_{$cIdx}_{$agIdx}_{$oIdx}";
                    $allOptimizations[$key] = array_merge($opt, [
                        'campaign_name' => $aiCamp['campaign_name'] ?? '',
                        'ad_group_name' => $ag['ad_group_name'] ?? '',
                    ]);
                }
            }
            foreach ($aiCamp['asset_group_analysis'] ?? [] as $agIdx => $ag) {
                foreach ($ag['optimizations'] ?? [] as $oIdx => $opt) {
                    $key = "popt_{$cIdx}_{$agIdx}_{$oIdx}";
                    $allOptimizations[$key] = array_merge($opt, [
                        'campaign_name' => $aiCamp['campaign_name'] ?? '',
                        'ad_group_name' => $ag['asset_group_name'] ?? '',
                    ]);
                }
            }
        }
    }
}

// Score helper functions
function evalScoreColorClass(float $score): string {
    if ($score < 5) return 'text-red-600 dark:text-red-400';
    if ($score <= 7) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
}
function evalScoreBorderClass(float $score): string {
    if ($score < 5) return 'border-red-500';
    if ($score <= 7) return 'border-amber-500';
    return 'border-emerald-500';
}
function evalScoreBgClass(float $score): string {
    if ($score < 5) return 'bg-red-500';
    if ($score <= 7) return 'bg-amber-500';
    return 'bg-emerald-500';
}
function evalScoreBadgeClass(float $score): string {
    if ($score < 5) return 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
    if ($score <= 7) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300';
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
}
?>

<?php $currentPage = 'evaluations'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="evaluationReport()">

    <!-- ============================================ -->
    <!-- ERROR STATE                                   -->
    <!-- ============================================ -->
    <?php if ($isError): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-12">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center mb-4">
                <!-- Heroicon: exclamation-triangle -->
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Errore durante la valutazione</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                <?= e($evaluation['error_message'] ?? 'Si e verificato un errore. Riprova o contatta il supporto.') ?>
            </p>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>"
               class="mt-6 inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors">
                <!-- Heroicon: arrow-left -->
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Torna alle campagne
            </a>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ANALYZING STATE (auto-refresh)                -->
    <!-- ============================================ -->
    <?php elseif ($isAnalyzing): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-rose-200 dark:border-rose-800 p-12">
        <div class="text-center">
            <svg class="h-12 w-12 mx-auto mb-4 text-rose-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Analisi in corso...</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">La pagina si aggiornera automaticamente al completamento.</p>
        </div>
    </div>
    <script>setTimeout(() => location.reload(), 5000);</script>

    <!-- ============================================ -->
    <!-- NO CHANGE STATE                               -->
    <!-- ============================================ -->
    <?php elseif ($isNoChange): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                <!-- Heroicon: check-circle -->
                <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Nessuna variazione significativa</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">I dati non sono cambiati rispetto all'ultima valutazione. Nessun credito consumato.</p>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>"
               class="mt-6 inline-flex items-center px-4 py-2 rounded-lg bg-slate-600 text-white font-medium hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Torna alle campagne
            </a>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- RESULTS                                       -->
    <!-- ============================================ -->
    <?php elseif ($hasResults): ?>

    <!-- SECTION: Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Report Campagne</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Periodo: <?= date('d/m/Y', strtotime($currentSync['date_range_start'] ?? '')) ?> &mdash; <?= date('d/m/Y', strtotime($currentSync['date_range_end'] ?? '')) ?>
                &middot; Sync: <?= date('d/m/Y H:i', strtotime($currentSync['completed_at'] ?? '')) ?>
                &middot; <?= count($viewCampaigns) ?> campagn<?= count($viewCampaigns) === 1 ? 'a' : 'e' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <!-- PDF export -->
            <a href="<?= url("/ads-analyzer/projects/{$project['id']}/campaigns/evaluations/{$evaluation['id']}/export-pdf") ?>"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 transition-colors">
                <!-- Heroicon: document-arrow-down -->
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                PDF
            </a>
            <!-- Back -->
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                <!-- Heroicon: arrow-left -->
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Indietro
            </a>
        </div>
    </div>

    <!-- SECTION: KPI Bar -->
    <?php
    $kpiConfig = [
        'cost' => ['label' => 'Spesa', 'format' => 'currency', 'positive_is_good' => false],
        'clicks' => ['label' => 'Click', 'format' => 'number', 'positive_is_good' => true],
        'ctr' => ['label' => 'CTR', 'format' => 'percent', 'positive_is_good' => true],
        'conversions' => ['label' => 'Conversioni', 'format' => 'number', 'positive_is_good' => true],
        'roas' => ['label' => 'ROAS', 'format' => 'roas', 'positive_is_good' => true],
        'cpa' => ['label' => 'CPA', 'format' => 'currency', 'positive_is_good' => false],
    ];
    $totals = $syncMetrics['totals'] ?? [];
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <?php foreach ($kpiConfig as $kpiKey => $cfg): ?>
        <?php
        $value = $totals[$kpiKey] ?? 0;
        $delta = $metricDeltas[$kpiKey] ?? null;
        $deltaPct = $delta ? ($delta['delta_pct'] ?? 0) : 0;
        // Format value
        $formatted = match($cfg['format']) {
            'currency' => "\xe2\x82\xac" . number_format((float)$value, $kpiKey === 'cpa' ? 2 : 0, ',', '.'),
            'percent' => number_format((float)$value, 1, ',', '.') . '%',
            'roas' => number_format((float)$value, 1, ',', '.') . 'x',
            default => number_format((float)$value, 0, ',', '.'),
        };
        // Delta color logic
        $isGood = $deltaPct > 0 ? $cfg['positive_is_good'] : !$cfg['positive_is_good'];
        $deltaColor = abs($deltaPct) < 0.5
            ? 'text-slate-400'
            : ($isGood ? 'text-emerald-500 dark:text-emerald-400' : 'text-red-500 dark:text-red-400');
        $arrow = $deltaPct > 0.5 ? '&uarr;' : ($deltaPct < -0.5 ? '&darr;' : '&rarr;');
        // Special highlight for ROAS
        $valueColor = $kpiKey === 'roas'
            ? ($value >= 4 ? 'text-emerald-600 dark:text-emerald-400' : ($value >= 2 ? 'text-amber-500 dark:text-amber-400' : 'text-red-500'))
            : 'text-slate-900 dark:text-white';
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $cfg['label'] ?></div>
            <div class="text-xl font-bold <?= $valueColor ?> mt-1"><?= $formatted ?></div>
            <?php if ($delta): ?>
            <div class="text-xs <?= $deltaColor ?> mt-1"><?= $arrow ?> <?= $deltaPct >= 0 ? '+' : '' ?><?= number_format(abs($deltaPct), 1) ?>%</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- SECTION: Campaign Table -->
    <?php include __DIR__ . '/partials/report-campaign-table.php'; ?>

    <!-- SECTION: Product Analysis (only if Shopping/PMax) -->
    <?php if (!empty($productData)): ?>
    <?php include __DIR__ . '/partials/report-product-analysis.php'; ?>
    <?php endif; ?>

    <!-- SECTION: AI Summary + Score -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col sm:flex-row items-start gap-6">
            <!-- Score Circle -->
            <div class="flex-shrink-0 flex flex-col items-center">
                <div class="relative w-24 h-24">
                    <!-- Background circle -->
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 96 96">
                        <circle cx="48" cy="48" r="40" fill="none" stroke="currentColor" stroke-width="6"
                                class="text-slate-200 dark:text-slate-700"/>
                        <circle cx="48" cy="48" r="40" fill="none" stroke-width="6"
                                stroke-dasharray="<?= round($overallScore / 10 * 251.2, 1) ?> 251.2"
                                stroke-linecap="round"
                                class="<?= evalScoreColorClass($overallScore) ?>"
                                style="transition: stroke-dasharray 0.6s ease;"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-bold <?= evalScoreColorClass($overallScore) ?>"><?= number_format($overallScore, 1) ?></span>
                    </div>
                </div>
                <span class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Score</span>
            </div>

            <!-- Summary + Recommendations -->
            <div class="flex-1 min-w-0">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <!-- Heroicon: sparkles -->
                    <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                    </svg>
                    Analisi Complessiva AI
                </h2>

                <?php if (!empty($summary)): ?>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4"><?= nl2br(e($summary)) ?></p>
                <?php endif; ?>

                <?php if (!empty($topRecommendations)): ?>
                <div class="mt-4">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
                        <!-- Heroicon: light-bulb -->
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                        </svg>
                        Raccomandazioni Principali
                    </h3>
                    <ol class="space-y-2">
                        <?php foreach (array_slice($topRecommendations, 0, 5) as $rIdx => $rec): ?>
                        <li class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-400">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-center justify-center mt-0.5">
                                <?= $rIdx + 1 ?>
                            </span>
                            <span class="leading-relaxed"><?= e(is_string($rec) ? $rec : ($rec['recommendation'] ?? $rec['text'] ?? '')) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SECTION: Optimizations Batch -->
    <?php if (!empty($allOptimizations)): ?>
    <?php include __DIR__ . '/partials/report-optimizations.php'; ?>
    <?php endif; ?>

    <!-- SECTION: Negative KW Summary -->
    <?php if (!empty($negativeSummary)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <!-- Heroicon: funnel -->
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                    </svg>
                    Keyword Negative
                </h2>

                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <?php if (isset($negativeSummary['total_waste'])): ?>
                    <div class="text-sm text-slate-600 dark:text-slate-400">
                        Spesa stimata sprecata:
                        <span class="font-semibold text-red-600 dark:text-red-400"><?= "\xe2\x82\xac" . number_format((float)($negativeSummary['total_waste'] ?? 0), 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap gap-2 mb-3">
                    <?php
                    $highPriority = $negativeSummary['high_priority'] ?? 0;
                    $mediumPriority = $negativeSummary['medium_priority'] ?? 0;
                    $lowPriority = $negativeSummary['low_priority'] ?? 0;
                    $appliedCount = $negativeSummary['applied_count'] ?? 0;
                    ?>
                    <?php if ($highPriority > 0): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                        <?= $highPriority ?> alta priorita
                    </span>
                    <?php endif; ?>
                    <?php if ($mediumPriority > 0): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                        <?= $mediumPriority ?> media priorita
                    </span>
                    <?php endif; ?>
                    <?php if ($lowPriority > 0): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        <?= $lowPriority ?> bassa priorita
                    </span>
                    <?php endif; ?>
                    <?php if ($appliedCount > 0): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                        <!-- Heroicon: check -->
                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        <?= $appliedCount ?> applicate
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($negativeSummary['summary_text'])): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($negativeSummary['summary_text']) ?></p>
                <?php endif; ?>
            </div>

            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/search-term-analysis') ?>"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-rose-600 text-white hover:bg-rose-700 transition-colors flex-shrink-0">
                <!-- Heroicon: arrow-top-right-on-square -->
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                </svg>
                Analisi Termini
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Back Link -->
    <div class="flex justify-center pt-2 pb-4">
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>"
           class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors">
            <!-- Heroicon: arrow-left -->
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Torna alla lista campagne
        </a>
    </div>

    <?php endif; ?>

    <!-- ============================================ -->
    <!-- APPLY MODAL                                   -->
    <!-- ============================================ -->
    <template x-if="applyModal.open">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="resetApplyModal()">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50" @click="resetApplyModal()"></div>
            <!-- Modal -->
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 z-10">
                <template x-if="!applyModal.done">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Applica su Google Ads</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4" x-text="applyModal.description"></p>

                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4">
                            <div class="flex items-start gap-2">
                                <!-- Heroicon: exclamation-triangle -->
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                </svg>
                                <p class="text-sm text-amber-800 dark:text-amber-300">Questa azione modifichera il tuo account Google Ads. Digita <strong>CONFERMA</strong> per procedere.</p>
                            </div>
                        </div>

                        <input type="text" x-model="applyModal.confirmText"
                               placeholder="Digita CONFERMA"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 mb-4">

                        <div class="flex justify-end gap-3">
                            <button @click="resetApplyModal()"
                                    class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                                Annulla
                            </button>
                            <button @click="executeApply()"
                                    :disabled="applyModal.confirmText.toUpperCase() !== 'CONFERMA' || applyModal.applying"
                                    class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <template x-if="applyModal.applying">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Applicando...
                                    </span>
                                </template>
                                <template x-if="!applyModal.applying">
                                    <span>Applica Modifiche</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="applyModal.done">
                    <div class="text-center">
                        <template x-if="!applyModal.error">
                            <div>
                                <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Modifiche Applicate</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4" x-text="applyModal.resultMessage"></p>
                            </div>
                        </template>
                        <template x-if="applyModal.error">
                            <div>
                                <div class="mx-auto h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Errore</h3>
                                <p class="text-sm text-red-600 dark:text-red-400 mb-4" x-text="applyModal.error"></p>
                            </div>
                        </template>
                        <button @click="resetApplyModal()"
                                class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                            Chiudi
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>

<script>
function evaluationReport() {
    return {
        // UI state
        expandedCampaigns: {},
        expandedAdGroups: {},
        selectedOptimizations: {},
        generators: {},
        generatingAll: false,
        genAllCurrent: 0,
        genAllTotal: 0,

        // URLs and tokens
        generateUrl: '<?= e($generateUrl ?? '') ?>',
        applyUrl: '<?= e($applyUrl ?? '') ?>',
        csrfToken: '<?= csrf_token() ?>',

        // Apply modal
        applyModal: { open: false, key: '', confirmed: false, confirmText: '', applying: false, done: false, error: null, resultMessage: '', description: '' },

        // Preloaded campaign data for table interactions
        campaignsData: <?= json_encode($viewCampaigns, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_TAG) ?>,

        // Preloaded saved fixes
        savedFixes: <?= json_encode(array_map(function($fix) {
            return [
                'id' => $fix['id'],
                'fix_type' => $fix['fix_type'],
                'scope_level' => $fix['scope_level'] ?? '',
                'campaign_name' => $fix['campaign_name'] ?? '',
                'ad_group_name' => $fix['ad_group_name'] ?? '',
                'target_ad_index' => $fix['target_ad_index'] ?? null,
                'issue_description' => $fix['issue_description'] ?? '',
                'data' => json_decode($fix['ai_response'] ?? '{}', true),
                'content' => $fix['display_text'] ?? '',
                'status' => $fix['status'] ?? '',
            ];
        }, $savedFixes ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_TAG) ?>,

        // All optimizations for batch processing
        allOptimizations: <?= json_encode($allOptimizations, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_TAG) ?>,

        init() {
            // Restore saved fixes into generators map
            if (this.savedFixes && this.savedFixes.length) {
                this.savedFixes.forEach(fix => {
                    // Find matching optimization key
                    const matchKey = Object.keys(this.allOptimizations || {}).find(k => {
                        const opt = this.allOptimizations[k];
                        return opt.campaign_name === fix.campaign_name
                            && opt.ad_group_name === fix.ad_group_name
                            && fix.fix_type === (opt.fix_type || opt.type);
                    });
                    if (matchKey && fix.content) {
                        this.generators[matchKey] = {
                            loading: false,
                            result: fix.content,
                            data: fix.data,
                            type: fix.fix_type,
                            copied: false,
                            applied: fix.status === 'applied',
                            fixId: fix.id,
                            error: null,
                        };
                    }
                });
            }
        },

        // Toggle methods
        toggleCampaign(idx) {
            this.expandedCampaigns[idx] = !this.expandedCampaigns[idx];
        },

        toggleAdGroup(key) {
            this.expandedAdGroups[key] = !this.expandedAdGroups[key];
        },

        isLoading(key) {
            return !!(this.generators[key] && this.generators[key].loading);
        },

        // Generate fix via AI
        async generateFix(type, context, key) {
            if (this.generators[key]?.loading) return;
            this.generators[key] = { loading: true, result: null, error: null, data: null, type: type, copied: false, applied: false };

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('type', type);
                formData.append('context', JSON.stringify(context));
                if (context.target_ad_index) formData.append('target_ad_index', context.target_ad_index);

                const response = await fetch(this.generateUrl, { method: 'POST', body: formData });
                if (!response.ok) {
                    if (response.status === 502 || response.status === 504) {
                        this.generators[key] = { loading: false, result: null, error: 'Timeout del server. Riprova tra qualche istante.', copied: false, applied: false, type: type, data: null };
                        return;
                    }
                    const errData = await response.json().catch(() => null);
                    throw new Error(errData?.error || 'Errore HTTP ' + response.status);
                }
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                this.generators[key] = {
                    loading: false,
                    result: data.content || data.display_text || '',
                    data: data.data || data.ai_response || null,
                    type: data.type || type,
                    copied: false,
                    applied: false,
                    fixId: data.fix_id || data.id || null,
                    adGroupIdGoogle: context.ad_group_id_google || null,
                    error: null,
                };
            } catch (err) {
                this.generators[key] = {
                    loading: false,
                    result: null,
                    data: null,
                    type: type,
                    copied: false,
                    applied: false,
                    error: err.message || 'Errore di connessione. Riprova.',
                };
            }
        },

        // Generate all pending optimizations
        async generateAll() {
            if (this.generatingAll) return;
            const pending = Object.keys(this.allOptimizations || {}).filter(k => !this.generators[k]?.result && !this.generators[k]?.loading);
            if (pending.length === 0) return;

            this.generatingAll = true;
            this.genAllTotal = pending.length;
            this.genAllCurrent = 0;

            for (const key of pending) {
                if (!this.generatingAll) break; // cancelled
                this.genAllCurrent++;
                const opt = this.allOptimizations[key];
                const fixType = opt.fix_type || opt.type || 'rewrite_ads';
                const context = {
                    issue: opt.issue || opt.description || '',
                    recommendation: opt.recommendation || '',
                    campaign_name: opt.campaign_name || '',
                    ad_group_name: opt.ad_group_name || '',
                };
                await this.generateFix(fixType, context, key);
                // Small delay to avoid overwhelming the server
                await new Promise(r => setTimeout(r, 500));
            }

            this.generatingAll = false;
        },

        // Copy result to clipboard
        async copyResult(key) {
            const gen = this.generators[key];
            if (!gen?.result) return;
            try {
                await navigator.clipboard.writeText(gen.result);
            } catch (_) {
                const ta = document.createElement('textarea');
                ta.value = gen.result;
                ta.style.cssText = 'position:fixed;left:-999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            gen.copied = true;
            setTimeout(() => { if (this.generators[key]) this.generators[key].copied = false; }, 2000);
        },

        // Export CSV
        exportCsv(key) {
            const gen = this.generators[key];
            if (!gen || !gen.data) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.generateUrl.replace('/generate', '/export-csv');
            form.target = '_blank';
            const fields = {
                '_csrf_token': this.csrfToken,
                'type': gen.type,
                'data': JSON.stringify(gen.data),
                'campaign_name': '<?= e($project['name'] ?? '') ?>',
            };
            for (const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        // Apply modal
        openApplyModal(key) {
            const gen = this.generators[key];
            if (!gen || !gen.data) return;
            const isDuplicateRemoval = gen.type === 'remove_duplicates' || gen.data?.action === 'remove_duplicates';
            const typeDescriptions = {
                'rewrite_ads': 'Verra creato un nuovo annuncio RSA in stato PAUSED nel gruppo di annunci selezionato. Potrai attivarlo manualmente da Google Ads.',
                'add_negatives': 'Verranno aggiunte le keyword negative alla campagna. Le keyword negative esistenti non verranno modificate.',
                'remove_duplicates': 'Verranno RIMOSSE le keyword duplicate dai gruppi di annunci indicati. Questa operazione non e reversibile.',
                'add_extensions': 'Verranno aggiunte le estensioni generate (sitelink, callout, snippet strutturati) alla campagna.',
            };
            this.applyModal = {
                open: true,
                key: key,
                confirmed: false,
                confirmText: '',
                applying: false,
                done: false,
                error: null,
                resultMessage: '',
                description: typeDescriptions[gen.type] || 'Verranno applicate le modifiche generate su Google Ads.',
            };
        },

        async executeApply() {
            if (this.applyModal.confirmText.toUpperCase() !== 'CONFERMA') return;
            const key = this.applyModal.key;
            const gen = this.generators[key];
            if (!gen || !gen.data) return;

            this.applyModal.applying = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('type', gen.type);
                formData.append('data', JSON.stringify(gen.data));
                if (gen.fixId) formData.append('fix_id', gen.fixId);
                if (gen.adGroupIdGoogle) formData.append('ad_group_id_google', gen.adGroupIdGoogle);

                const resp = await fetch(this.applyUrl, { method: 'POST', body: formData });

                if (!resp.ok) {
                    if (resp.status === 502 || resp.status === 504) {
                        throw new Error('Timeout del server. Riprova tra qualche istante.');
                    }
                    const errData = await resp.json().catch(() => null);
                    throw new Error(errData?.error || 'Errore HTTP ' + resp.status);
                }

                const data = await resp.json();
                if (data.error) throw new Error(data.error);

                this.applyModal.applying = false;
                this.applyModal.done = true;
                this.applyModal.resultMessage = data.message || 'Modifiche applicate con successo su Google Ads.';

                // Mark generator as applied
                if (this.generators[key]) {
                    this.generators[key].applied = true;
                }
            } catch (e) {
                this.applyModal.applying = false;
                this.applyModal.done = true;
                this.applyModal.error = e.message || 'Errore di connessione. Riprova.';
            }
        },

        resetApplyModal() {
            this.applyModal = { open: false, key: '', confirmed: false, confirmText: '', applying: false, done: false, error: null, resultMessage: '', description: '' };
        },

        // Helper: escape HTML for safe rendering
        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },
    };
}
</script>
