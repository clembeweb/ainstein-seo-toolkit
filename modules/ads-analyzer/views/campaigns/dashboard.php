<?php
$currentPage = 'dashboard';
$canEdit = ($access_role ?? 'owner') !== 'viewer';
include __DIR__ . '/../partials/project-nav.php';
?>

<?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

<?php
// Helper: delta color/arrow
function deltaClass(float $percent, bool $invertColor = false): string {
    if (abs($percent) < 0.5) return 'text-slate-400 dark:text-slate-500';
    $isPositive = $percent > 0;
    if ($invertColor) $isPositive = !$isPositive;
    return $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
}

function deltaArrow(float $percent): string {
    if (abs($percent) < 0.5) return '&rarr;';
    return $percent > 0
        ? '<svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
        : '<svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
}

function scoreTextClass(float $score): string {
    if ($score < 5) return 'text-red-600 dark:text-red-400';
    if ($score <= 7) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
}
function scoreBorderClass(float $score): string {
    if ($score < 5) return 'border-red-500';
    if ($score <= 7) return 'border-amber-500';
    return 'border-emerald-500';
}

$hasGoogleAds = !empty($project['google_ads_customer_id']);
$googleAdsAccountName = $project['google_ads_account_name'] ?? '';

// ROAS/CPA helper per la tabella
$totalCost = (float)($latestStats['total_cost'] ?? 0);
$totalConversions = (float)($latestStats['total_conversions'] ?? 0);
$totalValue = (float)($latestStats['total_value'] ?? 0);
$roas = ($totalCost > 0 && $totalValue > 0) ? round($totalValue / $totalCost, 2) : 0;
$cpa = ($totalConversions > 0) ? round($totalCost / $totalConversions, 2) : 0;
?>

<div class="space-y-6" x-data="dashboardManager()">

    <!-- SEZIONE 1: Header — Account badge + Date Picker + Sync + Eval -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <!-- Google Ads Connection Badge + Health Score Badge -->
        <div class="flex items-center gap-2">
            <?php if ($hasGoogleAds): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                <?= e($googleAdsAccountName ?: $project['google_ads_customer_id']) ?>
            </span>
            <?php else: ?>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/70 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                Connetti Google Ads
            </a>
            <?php endif; ?>

            <?php if ($latestAiResponse && isset($latestAiResponse['overall_score'])): ?>
            <?php $healthScore = (float)$latestAiResponse['overall_score']; ?>
            <a href="<?= $latestEvalWithAi ? url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $latestEvalWithAi['id']) : '#' ?>"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border-2 <?= scoreBorderClass($healthScore) ?> bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <span class="font-bold <?= scoreTextClass($healthScore) ?>"><?= number_format($healthScore, 1) ?></span>
                <span class="text-slate-600 dark:text-slate-300">Health</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Date Picker + Sync Button -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Preset Buttons -->
            <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="setRange(7)"
                        :class="dateRange == 7 ? 'bg-rose-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        class="px-3 py-1.5 text-xs font-medium transition-colors">
                    7 giorni
                </button>
                <button @click="setRange(30)"
                        :class="dateRange == 30 ? 'bg-rose-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        class="px-3 py-1.5 text-xs font-medium border-l border-slate-200 dark:border-slate-700 transition-colors">
                    30 giorni
                </button>
                <button @click="setRange(90)"
                        :class="dateRange == 90 ? 'bg-rose-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        class="px-3 py-1.5 text-xs font-medium border-l border-slate-200 dark:border-slate-700 transition-colors">
                    90 giorni
                </button>
                <button @click="showCustom = !showCustom; dateRange = 'custom'"
                        :class="dateRange == 'custom' ? 'bg-rose-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        class="px-3 py-1.5 text-xs font-medium border-l border-slate-200 dark:border-slate-700 transition-colors">
                    Personalizzato
                </button>
            </div>

            <!-- Custom Date Inputs -->
            <div x-show="showCustom" x-cloak class="flex items-center gap-2">
                <input type="date" x-model="dateFrom"
                       class="text-xs border border-slate-300 dark:border-slate-600 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                <span class="text-slate-400 text-xs">-</span>
                <input type="date" x-model="dateTo"
                       class="text-xs border border-slate-300 dark:border-slate-600 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                <button @click="applyCustom()"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-rose-600 text-white hover:bg-rose-700 transition-colors">
                    Applica
                </button>
            </div>

            <!-- Data source indicator -->
            <span x-show="kpiSource === 'api'" x-cloak
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Live
            </span>
            <span x-show="kpiSource === 'db'" x-cloak
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                DB
            </span>

            <!-- Sync Button -->
            <?php if ($canEdit && $hasGoogleAds): ?>
            <button @click="startSync()"
                    :disabled="syncing"
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <template x-if="syncing">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <template x-if="!syncing">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </template>
                <span x-text="syncing ? 'Sincronizzazione...' : 'Sincronizza'"></span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($campaignSyncs)): ?>
    <!-- No data: Connetti o Sincronizza -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <?php if ($hasGoogleAds): ?>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Sincronizza i tuoi dati</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Il tuo account Google Ads è collegato. Avvia la prima sincronizzazione per importare le campagne.
        </p>
        <button @click="startSync()"
                :disabled="syncing"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-700 disabled:opacity-50 transition-colors">
            <template x-if="syncing">
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <template x-if="!syncing">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </template>
            <span x-text="syncing ? 'Sincronizzazione in corso...' : 'Avvia Sincronizzazione'"></span>
        </button>
        <?php else: ?>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Collega Google Ads</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Collega il tuo account Google Ads per iniziare a sincronizzare automaticamente i dati delle tue campagne.
        </p>
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            Connetti Google Ads
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <?php
    // Alert: problemi critici dall'ultima valutazione
    $highIssues = [];
    if ($latestAiResponse) {
        foreach ($latestAiResponse['campaigns'] ?? [] as $c) {
            foreach ($c['issues'] ?? [] as $issue) {
                if (($issue['severity'] ?? '') === 'high') {
                    $highIssues[] = $issue;
                }
            }
        }
    }
    // Alert da metric deltas
    $metricAlerts = [];
    if ($kpiDeltas) {
        foreach (['total_clicks', 'total_conversions'] as $key) {
            if (isset($kpiDeltas[$key]) && $kpiDeltas[$key]['percent'] <= -0.15) {
                $metricAlerts[] = $kpiDeltas[$key]['label'] . ': ' . $kpiDeltas[$key]['percent_display'] . '%';
            }
        }
    }
    ?>

    <!-- SEZIONE 3: Alert Banner AI -->
    <?php if (!empty($highIssues) || !empty($metricAlerts)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                    <?= count($highIssues) + count($metricAlerts) ?> problema/i rilevato/i
                    <?php if ($latestEvalWithAi): ?>
                    <span class="font-normal text-red-600 dark:text-red-400">&mdash; eval <?= date('d/m/Y', strtotime($latestEvalWithAi['created_at'])) ?></span>
                    <?php endif; ?>
                </p>
                <ul class="mt-1 text-sm text-red-700 dark:text-red-400 space-y-0.5">
                    <?php foreach ($metricAlerts as $alert): ?>
                    <li>&bull; <?= e($alert) ?></li>
                    <?php endforeach; ?>
                    <?php foreach (array_slice($highIssues, 0, 3) as $issue): ?>
                    <li>&bull; <?= e($issue['description'] ?? '') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($latestEval): ?>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $latestEval['id']) ?>" class="inline-block mt-2 text-sm font-medium text-red-800 dark:text-red-300 underline hover:no-underline">
                    Vedi dettagli
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Changes Summary (se presente) -->
    <?php if (!empty($latestAiResponse['changes_summary'])): ?>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-blue-800 dark:text-blue-300"><?= e($latestAiResponse['changes_summary']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- SEZIONE 2: KPI Bar — 6 cards -->
    <?php
    $kpiCards = [
        ['key' => 'total_clicks', 'alpine' => 'kpiClicks', 'label' => 'Click', 'value' => $latestStats['total_clicks'] ?? 0, 'format' => 'number', 'icon' => 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122', 'color' => 'blue'],
        ['key' => 'total_cost', 'alpine' => 'kpiCost', 'label' => 'Costo', 'value' => $latestStats['total_cost'] ?? 0, 'format' => 'euro', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'red', 'invert' => true],
        ['key' => 'total_conversions', 'alpine' => 'kpiConversions', 'label' => 'Conversioni', 'value' => $latestStats['total_conversions'] ?? 0, 'format' => 'decimal', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
        ['key' => 'avg_ctr', 'alpine' => 'kpiCtr', 'label' => 'CTR', 'value' => $latestStats['avg_ctr'] ?? 0, 'format' => 'percent', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'color' => 'amber'],
        ['key' => 'roas', 'alpine' => 'kpiRoas', 'label' => 'ROAS', 'value' => $roas, 'format' => 'roas', 'icon' => 'M12 8v13m0-13V6a4 4 0 00-4-4H6.914a2 2 0 00-1.96 1.608l-.544 2.72A2 2 0 006.37 8H12zm0 0V6a4 4 0 014-4h1.086a2 2 0 011.96 1.608l.544 2.72A2 2 0 0117.63 8H12z', 'color' => 'purple'],
        ['key' => 'cpa', 'alpine' => 'kpiCpa', 'label' => 'CPA', 'value' => $cpa, 'format' => 'euro', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'rose', 'invert' => true],
    ];
    ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php foreach ($kpiCards as $kpi): ?>
        <?php
        $delta = $kpiDeltas[$kpi['key']] ?? null;
        $invertColor = $kpi['invert'] ?? false;
        $colorMap = [
            'blue' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400',
            'red' => 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400',
            'emerald' => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400',
            'amber' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400',
            'purple' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400',
            'rose' => 'bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400',
        ];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="h-8 w-8 rounded-lg <?= $colorMap[$kpi['color']] ?> flex items-center justify-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $kpi['icon'] ?>"/>
                    </svg>
                </div>
                <?php if ($delta): ?>
                <span x-show="kpiSource !== 'api'" class="text-xs font-medium <?= deltaClass($delta['percent_display'], $invertColor) ?>">
                    <?= deltaArrow($delta['percent_display']) ?>
                    <?= $delta['percent_display'] >= 0 ? '+' : '' ?><?= $delta['percent_display'] ?>%
                </span>
                <?php endif; ?>
            </div>
            <p class="text-xl font-bold text-slate-900 dark:text-white">
                <template x-if="loadingKpis">
                    <span class="inline-block h-6 w-14 bg-slate-200 dark:bg-slate-700 rounded animate-pulse"></span>
                </template>
                <template x-if="!loadingKpis">
                    <?php if ($kpi['format'] === 'euro'): ?>
                        <span x-text="formatEuro(<?= $kpi['alpine'] ?>)"></span>
                    <?php elseif ($kpi['format'] === 'percent'): ?>
                        <span x-text="formatPercent(<?= $kpi['alpine'] ?>)"></span>
                    <?php elseif ($kpi['format'] === 'decimal'): ?>
                        <span x-text="formatDecimal(<?= $kpi['alpine'] ?>)"></span>
                    <?php elseif ($kpi['format'] === 'roas'): ?>
                        <span x-text="formatRoas(<?= $kpi['alpine'] ?>)"></span>
                    <?php else: ?>
                        <span x-text="formatNumber(<?= $kpi['alpine'] ?>)"></span>
                    <?php endif; ?>
                </template>
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $kpi['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- SEZIONE 4: Type Summary Cards + Visual Widgets -->
    <?php
    // Aggregate per-type data
    $typeAggregates = [];
    foreach ($campaignsPerformance as $c) {
        $t = strtoupper($c['type'] ?? 'SEARCH');
        if (!isset($typeAggregates[$t])) {
            $typeAggregates[$t] = ['cost' => 0, 'clicks' => 0, 'impressions' => 0, 'conversions' => 0, 'conversion_value' => 0, 'count' => 0];
        }
        $typeAggregates[$t]['cost'] += (float)($c['cost'] ?? 0);
        $typeAggregates[$t]['clicks'] += (int)($c['clicks'] ?? 0);
        $typeAggregates[$t]['impressions'] += (int)($c['impressions'] ?? 0);
        $typeAggregates[$t]['conversions'] += (float)($c['conversions'] ?? 0);
        $typeAggregates[$t]['conversion_value'] += (float)($c['conversion_value'] ?? 0);
        $typeAggregates[$t]['count']++;
    }
    foreach ($typeAggregates as $t => &$agg) {
        $agg['roas'] = $agg['cost'] > 0 ? round($agg['conversion_value'] / $agg['cost'], 2) : 0;
        $agg['ctr'] = $agg['impressions'] > 0 ? round($agg['clicks'] / $agg['impressions'] * 100, 2) : 0;
        $agg['cpa'] = $agg['conversions'] > 0 ? round($agg['cost'] / $agg['conversions'], 2) : 0;
    }
    unset($agg);

    $typeLabelsGrid = ['SEARCH' => 'Search', 'PERFORMANCE_MAX' => 'PMax', 'SHOPPING' => 'Shopping', 'DISPLAY' => 'Display', 'VIDEO' => 'Video'];
    $typeColorsHex = ['SEARCH' => '#3b82f6', 'PERFORMANCE_MAX' => '#a855f7', 'SHOPPING' => '#f59e0b', 'DISPLAY' => '#f97316', 'VIDEO' => '#ef4444'];
    $typeColorsTw = [
        'SEARCH' => ['bg' => 'bg-blue-100 dark:bg-blue-900/50', 'text' => 'text-blue-700 dark:text-blue-300', 'border' => 'border-blue-200 dark:border-blue-800'],
        'PERFORMANCE_MAX' => ['bg' => 'bg-purple-100 dark:bg-purple-900/50', 'text' => 'text-purple-700 dark:text-purple-300', 'border' => 'border-purple-200 dark:border-purple-800'],
        'SHOPPING' => ['bg' => 'bg-amber-100 dark:bg-amber-900/50', 'text' => 'text-amber-700 dark:text-amber-300', 'border' => 'border-amber-200 dark:border-amber-800'],
        'DISPLAY' => ['bg' => 'bg-orange-100 dark:bg-orange-900/50', 'text' => 'text-orange-700 dark:text-orange-300', 'border' => 'border-orange-200 dark:border-orange-800'],
        'VIDEO' => ['bg' => 'bg-red-100 dark:bg-red-900/50', 'text' => 'text-red-700 dark:text-red-300', 'border' => 'border-red-200 dark:border-red-800'],
    ];
    $typeOrder = ['SEARCH', 'PERFORMANCE_MAX', 'SHOPPING', 'DISPLAY', 'VIDEO'];
    $activeTypes = array_filter($typeOrder, fn($t) => isset($typeAggregates[$t]));
    $typeCount = count($activeTypes);
    $totalSpend = array_sum(array_column($typeAggregates, 'cost'));
    $minSpendThreshold = $totalCost * 0.01;
    ?>

    <!-- Row 1: Type Summary Cards -->
    <?php if ($typeCount > 0): ?>
    <div class="grid grid-cols-1 <?= $typeCount === 1 ? '' : ($typeCount === 2 ? 'md:grid-cols-2' : 'md:grid-cols-3') ?> gap-4">
        <?php foreach ($activeTypes as $type):
            $agg = $typeAggregates[$type];
            $tw = $typeColorsTw[$type] ?? $typeColorsTw['SEARCH'];
            $label = $typeLabelsGrid[$type] ?? $type;
            $roasVal = $agg['roas'];
            $roasClass = $roasVal >= 4 ? 'text-emerald-600 dark:text-emerald-400' : ($roasVal >= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 <?= $typeCount === 1 ? 'md:col-span-1' : '' ?>">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $tw['bg'] ?> <?= $tw['text'] ?>"><?= $label ?></span>
                    <span class="text-sm text-slate-500 dark:text-slate-400"><?= $agg['count'] ?> campagn<?= $agg['count'] === 1 ? 'a' : 'e' ?></span>
                </div>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns?type=' . strtolower($type)) ?>"
                   class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium inline-flex items-center gap-1">
                    Dettagli
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Spesa</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($agg['cost'], 2, ',', '.') ?>&euro;</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-700/50 p-2.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400">ROAS</p>
                        <p class="text-lg font-bold <?= $agg['cost'] > 0 ? $roasClass : 'text-slate-400' ?>"><?= $agg['cost'] > 0 ? number_format($roasVal, 2, ',', '.') . 'x' : '-' ?></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-700/50 p-2.5">
                        <p class="text-xs text-slate-500 dark:text-slate-400">CTR</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($agg['ctr'], 2, ',', '.') ?>%</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Click</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= number_format($agg['clicks'], 0, ',', '.') ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Conv.</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= number_format($agg['conversions'], 0, ',', '.') ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">CPA</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= $agg['conversions'] > 0 ? number_format($agg['cpa'], 2, ',', '.') . '&euro;' : '-' ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Row 2: Budget Distribution Donut + Trend Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- LEFT: Budget Distribution Donut -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Budget</h2>
            <?php if ($totalSpend > 0 && $typeCount > 0): ?>
            <div class="flex flex-col items-center">
                <div class="relative w-44 h-44 mx-auto mb-6">
                    <div class="w-44 h-44 rounded-full" style="background: conic-gradient(
                        <?php
                        $offset = 0;
                        $segments = [];
                        foreach ($activeTypes as $t) {
                            $pct = ($typeAggregates[$t]['cost'] / $totalSpend) * 100;
                            $color = $typeColorsHex[$t] ?? '#94a3b8';
                            $segments[] = "{$color} {$offset}% " . ($offset + $pct) . "%";
                            $offset += $pct;
                        }
                        echo implode(', ', $segments);
                        ?>
                    );"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-26 h-26 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center" style="width: 6.5rem; height: 6.5rem;">
                            <div class="text-center">
                                <div class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($totalSpend, 0, ',', '.') ?>&euro;</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Budget Totale</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Legend -->
                <div class="w-full space-y-2">
                    <?php foreach ($activeTypes as $t):
                        $pct = round(($typeAggregates[$t]['cost'] / $totalSpend) * 100, 1);
                        $hexColor = $typeColorsHex[$t] ?? '#94a3b8';
                    ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= $hexColor ?>;"></span>
                            <span class="text-sm text-slate-700 dark:text-slate-300"><?= $typeLabelsGrid[$t] ?? $t ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-slate-900 dark:text-white"><?= number_format($typeAggregates[$t]['cost'], 2, ',', '.') ?>&euro;</span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 w-12 text-right"><?= $pct ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="flex items-center justify-center h-48">
                <p class="text-sm text-slate-400 dark:text-slate-500">Nessun dato di spesa disponibile</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: KPI Trend Chart (Chart.js — existing) -->
        <?php if (count($kpiTrend ?? []) >= 2): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Andamento KPI</h2>
                <select id="kpiMetricSelector" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value="clicks">Click</option>
                    <option value="cost">Costo</option>
                    <option value="conversions">Conversioni</option>
                    <option value="ctr">CTR %</option>
                </select>
            </div>
            <div class="h-56">
                <canvas id="kpiTrendChart"></canvas>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex items-center justify-center">
            <div class="text-center">
                <svg class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">Servono almeno 2 sync per il grafico trend</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Row 3: Campagne Critiche + Top Performer -->
    <?php
    // Critical campaigns: zero conversions, low ROAS, POOR ad strength
    $criticalCampaigns = [];
    foreach ($campaignsPerformance as $c) {
        $cCost = (float)($c['cost'] ?? 0);
        $cConv = (float)($c['conversions'] ?? 0);
        $cRoas = (float)($c['roas'] ?? 0);
        $reasons = [];

        if ($cCost > $minSpendThreshold && $cConv == 0) {
            $reasons[] = 'Zero conversioni';
        }
        if ($cCost > $minSpendThreshold && $cRoas > 0 && $cRoas < 2) {
            $reasons[] = 'ROAS ' . number_format($cRoas, 2, ',', '.') . 'x';
        }
        if (strtoupper($c['type'] ?? '') === 'PERFORMANCE_MAX') {
            foreach ($c['asset_groups'] ?? [] as $ag) {
                if (strtoupper($ag['ad_strength'] ?? '') === 'POOR') {
                    $reasons[] = 'Ad Strength POOR';
                    break;
                }
            }
        }

        if (!empty($reasons)) {
            $criticalCampaigns[] = ['name' => $c['name'] ?? '', 'cost' => $cCost, 'roas' => $cRoas, 'type' => $c['type'] ?? '', 'reasons' => $reasons];
        }
    }
    usort($criticalCampaigns, fn($a, $b) => $b['cost'] <=> $a['cost']);
    $criticalCampaigns = array_slice($criticalCampaigns, 0, 5);

    // Top performers: highest ROAS with meaningful spend
    $topPerformers = array_filter($campaignsPerformance, fn($c) =>
        (float)($c['cost'] ?? 0) > $minSpendThreshold && (float)($c['roas'] ?? 0) > 0
    );
    usort($topPerformers, fn($a, $b) => ($b['roas'] ?? 0) <=> ($a['roas'] ?? 0));
    $topPerformers = array_slice($topPerformers, 0, 5);
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- LEFT: Campagne Critiche -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                <div class="h-7 w-7 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Campagne Critiche</h2>
            </div>
            <?php if (!empty($criticalCampaigns)): ?>
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($criticalCampaigns as $cc):
                    $ccTw = $typeColorsTw[strtoupper($cc['type'])] ?? $typeColorsTw['SEARCH'];
                    $ccLabel = $typeLabelsGrid[strtoupper($cc['type'])] ?? $cc['type'];
                ?>
                <div class="px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium <?= $ccTw['bg'] ?> <?= $ccTw['text'] ?>"><?= $ccLabel ?></span>
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate" title="<?= e($cc['name']) ?>"><?= e(mb_strimwidth($cc['name'], 0, 30, '...')) ?></p>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($cc['reasons'] as $reason): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"><?= e($reason) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-red-600 dark:text-red-400 flex-shrink-0"><?= number_format($cc['cost'], 2, ',', '.') ?>&euro;</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="p-6 text-center">
                <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mx-auto mb-3">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna campagna critica rilevata</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Tutte le campagne stanno performando correttamente</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Top Performer -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                <div class="h-7 w-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Top Performer</h2>
            </div>
            <?php if (!empty($topPerformers)): ?>
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($topPerformers as $tp):
                    $tpRoas = (float)($tp['roas'] ?? 0);
                    $tpTw = $typeColorsTw[strtoupper($tp['type'] ?? 'SEARCH')] ?? $typeColorsTw['SEARCH'];
                    $tpLabel = $typeLabelsGrid[strtoupper($tp['type'] ?? 'SEARCH')] ?? $tp['type'];
                    $tpName = $tp['name'] ?? '';
                ?>
                <div class="px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium <?= $tpTw['bg'] ?> <?= $tpTw['text'] ?>"><?= $tpLabel ?></span>
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate" title="<?= e($tpName) ?>"><?= e(mb_strimwidth($tpName, 0, 30, '...')) ?></p>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <span>Spesa: <?= number_format((float)($tp['cost'] ?? 0), 2, ',', '.') ?>&euro;</span>
                                <span>Conv.: <?= number_format((float)($tp['conversions'] ?? 0), 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 flex-shrink-0"><?= number_format($tpRoas, 2, ',', '.') ?>x</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="p-6 text-center">
                <svg class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna campagna con ROAS positivo</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Servono campagne con spesa significativa e conversioni</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row 4: Spreco Budget + AI Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- LEFT: Spreco Budget -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-lg bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center">
                        <svg class="h-4 w-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Spreco Budget</h2>
                </div>
                <?php if (!empty($topWasteTerms)): ?>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/search-term-analysis') ?>" class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium">
                    Analisi completa &rarr;
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($topWasteTerms)): ?>
            <?php $wasteTotal = (float)($negativeStats['total_waste'] ?? 0); $wastePercent = $totalCost > 0 ? min(round(($wasteTotal / $totalCost) * 100, 1), 100) : 0; ?>
            <div class="p-5">
                <!-- Waste headline + progress bar -->
                <div class="flex items-end justify-between mb-2">
                    <div>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400"><?= number_format($wasteTotal, 2, ',', '.') ?>&euro;</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Spesa senza conversioni</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format((int)($negativeStats['waste_terms'] ?? 0)) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Termini</p>
                    </div>
                </div>
                <!-- Progress bar: waste / total -->
                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 mb-4">
                    <div class="bg-red-500 dark:bg-red-400 h-2 rounded-full transition-all" style="width: <?= $wastePercent ?>%;"></div>
                </div>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mb-4"><?= $wastePercent ?>% del budget totale</p>

                <!-- Top 5 waste terms with proportional bars -->
                <?php
                $maxWasteCost = 0;
                foreach ($topWasteTerms as $wt) { $maxWasteCost = max($maxWasteCost, (float)$wt['cost']); }
                ?>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Top termini spreco</p>
                <div class="space-y-2.5">
                    <?php foreach ($topWasteTerms as $term):
                        $termCost = (float)$term['cost'];
                        $barWidth = $maxWasteCost > 0 ? round(($termCost / $maxWasteCost) * 100) : 0;
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-0.5">
                            <p class="text-sm text-slate-900 dark:text-white truncate max-w-[200px]"><?= e($term['search_term']) ?></p>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400 ml-2 flex-shrink-0"><?= number_format($termCost, 2, ',', '.') ?>&euro;</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                            <div class="bg-red-400 dark:bg-red-500 h-1.5 rounded-full" style="width: <?= $barWidth ?>%;"></div>
                        </div>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5"><?= (int)$term['clicks'] ?> click &bull; <?= e($term['campaign_name'] ?? '') ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="p-6 text-center">
                <svg class="h-10 w-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">Sincronizza per analizzare i termini di ricerca</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">I search terms saranno disponibili dopo il sync</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: AI Insights -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">AI Insights</h2>
                </div>
                <?php if ($canEdit): ?>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 dark:text-slate-400">Auto-eval</span>
                    <button @click="toggleAutoEval()"
                            :disabled="togglingAutoEval"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            :class="autoEvalEnabled ? 'bg-amber-600' : 'bg-slate-300 dark:bg-slate-600'">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                              :class="autoEvalEnabled ? 'translate-x-4' : 'translate-x-0.5'"></span>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($latestAiResponse && isset($latestAiResponse['overall_score'])): ?>
            <?php $aiScore = (float)$latestAiResponse['overall_score']; ?>
            <div class="p-5">
                <!-- Score circle + summary -->
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0">
                        <?php
                        $scorePercent = $aiScore * 10;
                        $scoreStrokeDash = round($scorePercent * 2.51327, 1); // circumference ~251.327 for r=40
                        $scoreStrokeColor = $aiScore >= 7 ? '#10b981' : ($aiScore >= 5 ? '#f59e0b' : '#ef4444');
                        ?>
                        <svg class="w-20 h-20" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="stroke-slate-200 dark:stroke-slate-700"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke-width="8"
                                    stroke="<?= $scoreStrokeColor ?>"
                                    stroke-linecap="round"
                                    stroke-dasharray="<?= $scoreStrokeDash ?> 251.327"
                                    transform="rotate(-90 50 50)"/>
                            <text x="50" y="50" text-anchor="middle" dominant-baseline="central"
                                  class="fill-slate-900 dark:fill-white" font-size="22" font-weight="bold"><?= number_format($aiScore, 1) ?></text>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <?php if ($latestEval): ?>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-1">
                            <?= date('d/m/Y H:i', strtotime($latestEval['created_at'])) ?>
                            &bull; <?= $latestEval['campaigns_evaluated'] ?? 0 ?> campagne
                            <?php if (($latestEval['eval_type'] ?? 'manual') === 'auto'): ?>
                            &bull; <span class="text-blue-600 dark:text-blue-400">Auto</span>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($latestAiResponse['summary'])): ?>
                        <p class="text-sm text-slate-700 dark:text-slate-300 line-clamp-3"><?= e($latestAiResponse['summary']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top 3 recommendations -->
                <?php $recommendations = array_slice($latestAiResponse['top_recommendations'] ?? [], 0, 3); ?>
                <?php if (!empty($recommendations)): ?>
                <div class="mb-4">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Raccomandazioni</p>
                    <div class="space-y-2">
                        <?php foreach ($recommendations as $i => $rec): ?>
                        <div class="flex gap-2">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center"><?= $i + 1 ?></span>
                            <p class="text-sm text-slate-700 dark:text-slate-300"><?= e(is_array($rec) ? ($rec['text'] ?? ($rec['description'] ?? '')) : $rec) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Campaign scores mini-list -->
                <?php $aiCampaigns = array_slice($latestAiResponse['campaigns'] ?? [], 0, 4); ?>
                <?php if (!empty($aiCampaigns)): ?>
                <div class="mb-4">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Punteggi campagne</p>
                    <div class="space-y-1.5">
                        <?php foreach ($aiCampaigns as $aiC):
                            $campScore = (float)($aiC['score'] ?? 0);
                            $campScoreColor = $campScore >= 7 ? 'text-emerald-600 dark:text-emerald-400' : ($campScore >= 5 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                            $campScoreBg = $campScore >= 7 ? 'bg-emerald-500' : ($campScore >= 5 ? 'bg-amber-500' : 'bg-red-500');
                        ?>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold <?= $campScoreColor ?> w-8"><?= number_format($campScore, 1) ?></span>
                            <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                                <div class="<?= $campScoreBg ?> h-1.5 rounded-full" style="width: <?= min($campScore * 10, 100) ?>%;"></div>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[120px]" title="<?= e($aiC['campaign_name'] ?? $aiC['name'] ?? '') ?>"><?= e(mb_strimwidth($aiC['campaign_name'] ?? $aiC['name'] ?? '', 0, 20, '...')) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Link to full report -->
                <?php if ($latestEval && $latestEval['status'] === 'completed'): ?>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $latestEval['id']) ?>"
                   class="inline-flex items-center gap-1.5 w-full justify-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors">
                    Vedi report completo
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- No AI data — CTA -->
            <div class="p-6 text-center">
                <div class="h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mx-auto mb-3">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-900 dark:text-white mb-1">Nessuna valutazione AI</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Lancia una valutazione per ottenere insights e raccomandazioni</p>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Lancia Valutazione AI
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SEZIONE 7: Quick Info Footer -->
    <?php if ($latestSync): ?>
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2.5">
        <p class="text-xs text-slate-500 dark:text-slate-400 text-center">
            Ultimo sync: <span class="font-medium text-slate-600 dark:text-slate-300"><?= date('d/m/Y H:i', strtotime($latestSync['started_at'])) ?></span>
            <?php if (($latestSync['date_range_start'] ?? null) && ($latestSync['date_range_end'] ?? null)): ?>
            &middot; Periodo: <span class="font-medium text-slate-600 dark:text-slate-300"><?= date('d/m', strtotime($latestSync['date_range_start'])) ?> - <?= date('d/m', strtotime($latestSync['date_range_end'])) ?></span>
            <?php endif; ?>
            &middot; <span class="font-medium text-slate-600 dark:text-slate-300"><?= $totalCampaigns ?></span> campagne,
            <span class="font-medium text-slate-600 dark:text-slate-300"><?= $totalAdGroups ?></span> ad group,
            <span class="font-medium text-slate-600 dark:text-slate-300"><?= number_format($totalKeywords) ?></span> kw
        </p>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <?php if (($access_role ?? 'owner') === 'owner'): ?>
    <!-- Actions -->
    <div class="flex items-center justify-between">
        <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/delete') ?>" method="POST"
              x-data @submit.prevent="window.ainstein.confirm('Eliminare questo progetto e tutti i dati associati?', {destructive: true}).then(() => $el.submit())">
            <?= csrf_field() ?>
            <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                Elimina progetto
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Sync Status Toast -->
    <div x-show="syncMessage" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-2"
     class="fixed bottom-6 right-6 z-50 max-w-sm"
     :class="syncError ? 'bg-red-50 dark:bg-red-900/90 border-red-200 dark:border-red-800' : 'bg-emerald-50 dark:bg-emerald-900/90 border-emerald-200 dark:border-emerald-800'"
     style="border-width: 1px; border-radius: 0.75rem; padding: 1rem;">
    <div class="flex items-start gap-3">
        <template x-if="syncError">
            <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </template>
        <template x-if="!syncError">
            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </template>
        <div>
            <p class="text-sm" :class="syncError ? 'text-red-800 dark:text-red-300' : 'text-emerald-800 dark:text-emerald-300'" x-text="syncMessage"></p>
            <a x-show="tokenExpiredUrl" :href="tokenExpiredUrl"
               class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Riconnetti Account
            </a>
        </div>
    </div>
    </div>
</div>

<script>
function dashboardManager() {
    return {
        autoEvalEnabled: <?= $autoEvalEnabled ? 'true' : 'false' ?>,
        togglingAutoEval: false,
        syncing: false,
        syncMessage: '',
        syncError: false,
        tokenExpiredUrl: null,

        // Live KPI properties
        dateRange: '<?= (strtotime($dateTo) - strtotime($dateFrom)) / 86400 ?>',
        dateFrom: '<?= e($dateFrom) ?>',
        dateTo: '<?= e($dateTo) ?>',
        showCustom: false,
        loadingKpis: false,
        kpiSource: '<?= !empty($campaignSyncs) ? "db" : "none" ?>',

        // KPI values (inizializzati da PHP)
        kpiClicks: <?= (int)($latestStats['total_clicks'] ?? 0) ?>,
        kpiImpressions: <?= (int)($latestStats['total_impressions'] ?? 0) ?>,
        kpiCost: <?= round((float)($latestStats['total_cost'] ?? 0), 2) ?>,
        kpiConversions: <?= round((float)($latestStats['total_conversions'] ?? 0), 1) ?>,
        kpiCtr: <?= round((float)($latestStats['avg_ctr'] ?? 0), 2) ?>,
        kpiAvgCpc: <?= round((float)($latestStats['avg_cpc'] ?? 0), 2) ?>,
        kpiRoas: <?= $roas ?>,
        kpiCpa: <?= $cpa ?>,
        kpiCampaigns: <?= $totalCampaigns ?? 0 ?>,

        setRange(days) {
            this.dateRange = days;
            this.showCustom = false;
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - days);
            this.dateFrom = from.toISOString().split('T')[0];
            this.dateTo = to.toISOString().split('T')[0];
            this.fetchLiveKpis();
        },

        applyCustom() {
            if (this.dateFrom && this.dateTo) {
                this.fetchLiveKpis();
            }
        },

        async fetchLiveKpis() {
            this.loadingKpis = true;
            try {
                const url = `<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/live-kpis') ?>?date_from=${this.dateFrom}&date_to=${this.dateTo}`;
                const resp = await fetch(url);

                if (!resp.ok) {
                    const err = await resp.json().catch(() => ({}));
                    throw new Error(err.error || `Errore server (${resp.status})`);
                }

                const data = await resp.json();
                if (data.success && data.kpis) {
                    const k = data.kpis;
                    this.kpiClicks = k.clicks;
                    this.kpiImpressions = k.impressions;
                    this.kpiCost = k.cost;
                    this.kpiConversions = k.conversions;
                    this.kpiCtr = k.ctr;
                    this.kpiAvgCpc = k.avg_cpc;
                    this.kpiCampaigns = k.campaigns;
                    this.kpiSource = k.source;
                    // Calcola ROAS e CPA live
                    this.kpiRoas = (k.cost > 0 && (k.conversion_value || 0) > 0) ? ((k.conversion_value || 0) / k.cost) : 0;
                    this.kpiCpa = (k.conversions > 0) ? (k.cost / k.conversions) : 0;
                }

                const newUrl = new URL(window.location);
                newUrl.searchParams.set('date_from', this.dateFrom);
                newUrl.searchParams.set('date_to', this.dateTo);
                history.replaceState(null, '', newUrl);

            } catch (err) {
                console.error('LiveKPI fetch failed:', err);
            } finally {
                this.loadingKpis = false;
            }
        },

        formatNumber(n) {
            return Number(n).toLocaleString('it-IT');
        },
        formatEuro(n) {
            return Number(n).toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '\u20ac';
        },
        formatDecimal(n) {
            return Number(n).toLocaleString('it-IT', {minimumFractionDigits: 1, maximumFractionDigits: 1});
        },
        formatPercent(n) {
            return Number(n).toFixed(2) + '%';
        },
        formatRoas(n) {
            return n > 0 ? Number(n).toFixed(2) + 'x' : '-';
        },

        async toggleAutoEval() {
            this.togglingAutoEval = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/toggle-auto-evaluate') ?>', {
                    method: 'POST',
                    body: formData
                });
                if (!resp.ok) {
                    throw new Error(`Errore server (${resp.status})`);
                }
                const data = await resp.json();
                if (data.success) {
                    this.autoEvalEnabled = data.auto_evaluate;
                }
            } catch (err) {
                console.error('Toggle auto-eval failed:', err);
            } finally {
                this.togglingAutoEval = false;
            }
        },

        async startSync() {
            if (this.syncing) return;
            this.syncing = true;
            this.syncMessage = '';
            this.syncError = false;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '<?= csrf_token() ?>';
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                formData.append('date_from', this.dateFrom);
                formData.append('date_to', this.dateTo);

                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/sync') ?>', {
                    method: 'POST',
                    body: formData
                });

                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success) {
                        this.syncMessage = 'Sincronizzazione completata! ' + (data.campaigns_count || 0) + ' campagne importate.';
                        this.syncError = false;
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        this.syncMessage = data.error || 'Errore durante la sincronizzazione.';
                        this.syncError = true;
                    }
                } else {
                    const data = await resp.json().catch(() => ({}));
                    if (data.token_expired && data.connect_url) {
                        this.syncMessage = 'Token Google Ads scaduto. Riconnetti il tuo account.';
                        this.syncError = true;
                        this.tokenExpiredUrl = data.connect_url;
                    } else {
                        this.syncMessage = data.error || 'Errore durante la sincronizzazione (HTTP ' + resp.status + ').';
                        this.syncError = true;
                    }
                }
            } catch (err) {
                console.error('Sync failed:', err);
                this.syncMessage = 'Errore di connessione. Riprova.';
                this.syncError = true;
            } finally {
                this.syncing = false;
                if (this.syncMessage) {
                    setTimeout(() => { this.syncMessage = ''; }, 5000);
                }
            }
        }
    };
}
</script>

<?php if (count($kpiTrend ?? []) >= 2): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const kpiTrend = <?= json_encode($kpiTrend) ?>;
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const metricConfig = {
        clicks: { label: 'Click', color: '#3b82f6', bgColor: 'rgba(59, 130, 246, 0.1)', format: v => v.toLocaleString('it-IT') },
        cost: { label: 'Costo', color: '#ef4444', bgColor: 'rgba(239, 68, 68, 0.1)', format: v => v.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' \u20ac' },
        conversions: { label: 'Conversioni', color: '#10b981', bgColor: 'rgba(16, 185, 129, 0.1)', format: v => v.toLocaleString('it-IT', {minimumFractionDigits: 1, maximumFractionDigits: 1}) },
        ctr: { label: 'CTR %', color: '#f59e0b', bgColor: 'rgba(245, 158, 11, 0.1)', format: v => v.toFixed(2) + '%' },
    };

    let kpiChart = null;

    function renderChart(metric) {
        const cfg = metricConfig[metric];
        const labels = kpiTrend.map(d => d.label);
        const data = kpiTrend.map(d => d[metric]);

        if (kpiChart) kpiChart.destroy();

        const ctx = document.getElementById('kpiTrendChart');
        if (!ctx) return;

        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.color = textColor;

        kpiChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: cfg.label,
                    data: data,
                    borderColor: cfg.color,
                    backgroundColor: cfg.bgColor,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: cfg.color,
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return cfg.label + ': ' + cfg.format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: function(value) { return cfg.format(value); }
                        },
                        beginAtZero: true,
                    }
                }
            }
        });
    }

    document.getElementById('kpiMetricSelector')?.addEventListener('change', function() {
        renderChart(this.value);
    });

    document.addEventListener('DOMContentLoaded', function() {
        renderChart('clicks');
    });
})();
</script>
<?php endif; ?>
