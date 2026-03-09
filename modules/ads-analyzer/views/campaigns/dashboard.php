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

$trendLabels = [
    'improving' => ['label' => 'In miglioramento', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
    'stable' => ['label' => 'Stabile', 'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
    'declining' => ['label' => 'In calo', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
    'mixed' => ['label' => 'Misto', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'],
];

$hasGoogleAds = !empty($project['google_ads_customer_id']);
$googleAdsAccountName = $project['google_ads_account_name'] ?? '';
?>

<div class="space-y-6" x-data="dashboardManager()">

    <!-- Google Ads Connection Badge + Date Picker + Sync -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <!-- Google Ads Connection Badge -->
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
        </div>

        <!-- Date Picker + Sync Button -->
        <div class="flex flex-wrap items-center gap-3"
             x-data="{
                 dateRange: '<?= (strtotime($dateTo) - strtotime($dateFrom)) / 86400 ?>',
                 dateFrom: '<?= e($dateFrom) ?>',
                 dateTo: '<?= e($dateTo) ?>',
                 showCustom: false,
                 setRange(days) {
                     this.dateRange = days;
                     this.showCustom = false;
                     const to = new Date();
                     const from = new Date();
                     from.setDate(from.getDate() - days);
                     this.dateFrom = from.toISOString().split('T')[0];
                     this.dateTo = to.toISOString().split('T')[0];
                     window.location.href = `?date_from=${this.dateFrom}&date_to=${this.dateTo}`;
                 },
                 applyCustom() {
                     if (this.dateFrom && this.dateTo) {
                         window.location.href = `?date_from=${this.dateFrom}&date_to=${this.dateTo}`;
                     }
                 }
             }">
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

    <?php if (!empty($highIssues) || !empty($metricAlerts)): ?>
    <!-- Alert Banner -->
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                    <?= count($highIssues) + count($metricAlerts) ?> problema/i rilevato/i
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

    <!-- Health Score + KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

        <!-- Health Score Widget -->
        <?php if ($latestAiResponse && isset($latestAiResponse['overall_score'])): ?>
        <?php
        $healthScore = (float)$latestAiResponse['overall_score'];
        $trend = $latestAiResponse['trend'] ?? null;
        $changesSummary = $latestAiResponse['changes_summary'] ?? null;
        $evalType = ($latestEvalWithAi ?? $latestEval)['eval_type'] ?? 'manual';
        ?>
        <div class="md:col-span-1 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex flex-col items-center text-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center border-4 <?= scoreBorderClass($healthScore) ?> bg-white dark:bg-slate-900 mb-3">
                    <span class="text-2xl font-bold <?= scoreTextClass($healthScore) ?>"><?= number_format($healthScore, 1) ?></span>
                </div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Health Score</p>
                <div class="flex items-center gap-1.5 mt-1.5">
                    <?php if ($evalType === 'auto'): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">Auto</span>
                    <?php endif; ?>
                    <?php if ($trend && isset($trendLabels[$trend])): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $trendLabels[$trend]['class'] ?>">
                        <?= $trendLabels[$trend]['label'] ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if ($latestEvalWithAi ?? null): ?>
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $latestEvalWithAi['id']) ?>" class="mt-2 text-xs text-amber-600 dark:text-amber-400 hover:underline">
                    Dettagli
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="md:col-span-1 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex flex-col items-center text-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center border-4 border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 mb-3">
                    <span class="text-2xl font-bold text-slate-300 dark:text-slate-600">-</span>
                </div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Health Score</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Nessuna valutazione</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- KPI Cards (4) -->
        <?php
        $kpiCards = [
            ['key' => 'total_clicks', 'label' => 'Click', 'value' => $latestStats['total_clicks'] ?? 0, 'format' => 'number', 'icon' => 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122', 'color' => 'blue'],
            ['key' => 'total_cost', 'label' => 'Costo', 'value' => $latestStats['total_cost'] ?? 0, 'format' => 'euro', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'red', 'invert' => true],
            ['key' => 'total_conversions', 'label' => 'Conversioni', 'value' => $latestStats['total_conversions'] ?? 0, 'format' => 'decimal', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
            ['key' => 'avg_ctr', 'label' => 'CTR', 'value' => $latestStats['avg_ctr'] ?? 0, 'format' => 'percent', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'color' => 'amber'],
        ];
        ?>
        <?php foreach ($kpiCards as $kpi): ?>
        <?php
        $delta = $kpiDeltas[$kpi['key']] ?? null;
        $invertColor = $kpi['invert'] ?? false;
        $colorMap = ['blue' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400', 'red' => 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400', 'emerald' => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400', 'amber' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400'];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-2">
                <div class="h-8 w-8 rounded-lg <?= $colorMap[$kpi['color']] ?> flex items-center justify-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $kpi['icon'] ?>"/>
                    </svg>
                </div>
                <?php if ($delta): ?>
                <span class="text-xs font-medium <?= deltaClass($delta['percent_display'], $invertColor) ?>">
                    <?= deltaArrow($delta['percent_display']) ?>
                    <?= $delta['percent_display'] >= 0 ? '+' : '' ?><?= $delta['percent_display'] ?>%
                </span>
                <?php endif; ?>
            </div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                <?php if ($kpi['format'] === 'euro'): ?>
                    <?= number_format((float)$kpi['value'], 2, ',', '.') ?>&euro;
                <?php elseif ($kpi['format'] === 'percent'): ?>
                    <?= number_format((float)$kpi['value'], 2) ?>%
                <?php elseif ($kpi['format'] === 'decimal'): ?>
                    <?= number_format((float)$kpi['value'], 1) ?>
                <?php else: ?>
                    <?= number_format((int)$kpi['value']) ?>
                <?php endif; ?>
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= $kpi['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

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

    <!-- Ultima Sincronizzazione -->
    <?php if ($latestSync): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ultima sincronizzazione</h2>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/' . $latestSync['id']) ?>" class="text-sm font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700">
                Dettagli
                <svg class="w-4 h-4 ml-0.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div>
                <p class="text-slate-500 dark:text-slate-400">Data</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($latestSync['started_at'])) ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Tipo</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= e($latestSync['sync_type'] ?? '-') ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Items</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= number_format(($latestSync['campaigns_synced'] ?? 0) + ($latestSync['ad_groups_synced'] ?? 0) + ($latestSync['keywords_synced'] ?? 0) + ($latestSync['ads_synced'] ?? 0) + ($latestSync['search_terms_synced'] ?? 0)) ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Campagne</p>
                <p class="font-medium text-slate-900 dark:text-white"><?= number_format($totalCampaigns) ?></p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400">Periodo</p>
                <p class="font-medium text-slate-900 dark:text-white">
                    <?php if (($latestSync['date_range_start'] ?? null) && ($latestSync['date_range_end'] ?? null)): ?>
                    <?= date('d/m', strtotime($latestSync['date_range_start'])) ?> - <?= date('d/m/Y', strtotime($latestSync['date_range_end'])) ?>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- KPI Trend Chart -->
    <?php if (count($kpiTrend ?? []) >= 2): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Andamento KPI</h2>
            <select id="kpiMetricSelector" class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                <option value="clicks">Click</option>
                <option value="cost">Costo</option>
                <option value="conversions">Conversioni</option>
                <option value="ctr">CTR %</option>
            </select>
        </div>
        <div class="h-64">
            <canvas id="kpiTrendChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Evaluations Timeline + Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Valutazioni Timeline -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valutazioni AI</h2>
            </div>
            <?php if (empty($evaluations)): ?>
            <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                Nessuna valutazione ancora
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach (array_slice($evaluations, 0, 8) as $eval): ?>
                <?php
                $evalAi = json_decode($eval['ai_response'] ?? '{}', true);
                $evalScore = $evalAi['overall_score'] ?? null;
                $evalTrend = $evalAi['trend'] ?? null;
                $evalTypeLabel = ($eval['eval_type'] ?? 'manual') === 'auto' ? 'Auto' : 'Manuale';
                $isAutoEval = ($eval['eval_type'] ?? 'manual') === 'auto';
                $statusColors = [
                    'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                    'analyzing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                    'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                ];
                ?>
                <a href="<?= $eval['status'] === 'completed' ? url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $eval['id']) : '#' ?>"
                   class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <?php if ($evalScore !== null): ?>
                            <span class="text-sm font-bold <?= scoreTextClass((float)$evalScore) ?>"><?= number_format((float)$evalScore, 1) ?></span>
                            <?php endif; ?>
                            <span class="text-sm text-slate-700 dark:text-slate-300 truncate"><?= e($eval['name']) ?></span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <?php if ($isAutoEval): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Auto
                            </span>
                            <?php endif; ?>
                            <?php if ($evalTrend && isset($trendLabels[$evalTrend])): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $trendLabels[$evalTrend]['class'] ?>">
                                <?= $trendLabels[$evalTrend]['label'] ?>
                            </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$eval['status']] ?? 'bg-slate-100 text-slate-600' ?>">
                                <?= ucfirst($eval['status']) ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                        <?= date('d/m/Y H:i', strtotime($eval['created_at'])) ?>
                        &bull; <?= $eval['campaigns_evaluated'] ?? 0 ?> campagne
                        <?php if ($eval['credits_used'] > 0): ?>
                        &bull; <?= number_format($eval['credits_used'], 1) ?> crediti
                        <?php else: ?>
                        &bull; 0 crediti
                        <?php endif; ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Azioni rapide -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Azioni</h2>
            <div class="space-y-3">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Vedi Campagne e Valuta</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Dati performance e valutazione AI manuale</p>
                    </div>
                </a>

                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="h-10 w-10 rounded-lg bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">
                            <?= $hasGoogleAds ? 'Gestisci Connessione' : 'Connetti Google Ads' ?>
                        </p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?= $hasGoogleAds ? 'Account collegato: ' . e($googleAdsAccountName ?: $project['google_ads_customer_id']) : 'Collega il tuo account Google Ads' ?>
                        </p>
                    </div>
                </a>

                <?php if ($canEdit): ?>
                <!-- Toggle Auto-Valutazione -->
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
                    <div class="h-10 w-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-slate-900 dark:text-white">Auto-valutazione</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Valuta automaticamente ad ogni sincronizzazione</p>
                    </div>
                    <button @click="toggleAutoEval()"
                            :disabled="togglingAutoEval"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            :class="autoEvalEnabled ? 'bg-amber-600' : 'bg-slate-300 dark:bg-slate-600'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="autoEvalEnabled ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
        <p class="text-sm" :class="syncError ? 'text-red-800 dark:text-red-300' : 'text-emerald-800 dark:text-emerald-300'" x-text="syncMessage"></p>
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

        async toggleAutoEval() {
            this.togglingAutoEval = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/toggle-auto-evaluate') ?>', {
                    method: 'POST',
                    body: formData
                });
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
                formData.append('date_from', '<?= e($dateFrom) ?>');
                formData.append('date_to', '<?= e($dateTo) ?>');

                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/sync') ?>', {
                    method: 'POST',
                    body: formData
                });

                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success) {
                        this.syncMessage = 'Sincronizzazione completata! ' + (data.campaigns_count || 0) + ' campagne importate.';
                        this.syncError = false;
                        // Ricarica la pagina dopo 2 secondi per mostrare i nuovi dati
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        this.syncMessage = data.error || 'Errore durante la sincronizzazione.';
                        this.syncError = true;
                    }
                } else {
                    const data = await resp.json().catch(() => ({}));
                    this.syncMessage = data.error || 'Errore durante la sincronizzazione (HTTP ' + resp.status + ').';
                    this.syncError = true;
                }
            } catch (err) {
                console.error('Sync failed:', err);
                this.syncMessage = 'Errore di connessione. Riprova.';
                this.syncError = true;
            } finally {
                this.syncing = false;
                // Nascondi il messaggio dopo 5 secondi
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

    document.getElementById('kpiMetricSelector').addEventListener('change', function() {
        renderChart(this.value);
    });

    document.addEventListener('DOMContentLoaded', function() {
        renderChart('clicks');
    });
})();
</script>
<?php endif; ?>
