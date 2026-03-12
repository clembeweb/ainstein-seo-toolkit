<?php
/**
 * Valutazione AI Campagne - Risultati (Tabbed Dashboard)
 *
 * Variables:
 * - $project: project data (id, name)
 * - $evaluation: evaluation record
 * - $aiResponse: decoded AI response array
 * - $user, $modules
 * - $currentRun: current run data (nullable)
 * - $periods: period selector data ['7' => [...], '14' => [...], '30' => [...]]
 * - $currentPeriod: current period bucket ('7', '14', '30' or null)
 */

$canEdit = ($access_role ?? 'owner') !== 'viewer';

// Area translations
$areaLabels = [
    'copy' => 'Copy',
    'landing' => 'Landing Page',
    'performance' => 'Performance',
    'budget' => 'Budget',
    'extensions' => 'Estensioni',
    'keywords' => 'Keyword',
    'match_type' => 'Match Type',
];

// Campaign type badge classes
$campaignTypeConfig = [
    'SEARCH' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Search'],
    'SHOPPING' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Shopping'],
    'PERFORMANCE_MAX' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'PMax'],
    'DISPLAY' => ['bg' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300', 'label' => 'Display'],
    'VIDEO' => ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'label' => 'Video'],
];

// Helper: score color class
function scoreColorClass(float $score): string {
    if ($score < 5) return 'bg-red-500 dark:bg-red-600';
    if ($score <= 7) return 'bg-amber-500 dark:bg-amber-600';
    return 'bg-emerald-500 dark:bg-emerald-600';
}

function scoreTextClass(float $score): string {
    if ($score < 5) return 'text-red-600 dark:text-red-400';
    if ($score <= 7) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
}

function scoreBorderClass(float $score): string {
    if ($score < 5) return 'border-red-500 dark:border-red-600';
    if ($score <= 7) return 'border-amber-500 dark:border-amber-600';
    return 'border-emerald-500 dark:border-emerald-600';
}

function scoreBadgeBgClass(float $score): string {
    if ($score < 5) return 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
    if ($score <= 7) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300';
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
}

$isError = ($evaluation['status'] ?? '') === 'error';
$isAnalyzing = ($evaluation['status'] ?? '') === 'analyzing';
$hasResults = !empty($aiResponse) && !$isError && !$isAnalyzing;
$isNoChange = !$hasResults && !$isError && !$isAnalyzing
    && ($evaluation['status'] === 'completed')
    && (($evaluation['credits_used'] ?? 1) == 0);
$noChangeDeltas = $isNoChange && !empty($evaluation['metric_deltas'])
    ? json_decode($evaluation['metric_deltas'], true) : null;

// Aree per cui AI può generare contenuto
$generatableAreas = ['copy', 'extensions', 'keywords'];

// Helper: render area risultato AI inline
function renderAiGenerator(string $key, bool $showApply = false): string {
    $applyBtn = '';
    if ($showApply) {
        $applyBtn = <<<APPLY
            <button x-show="generators['{$key}']?.data && ['copy','extensions','keywords'].includes(generators['{$key}']?.type) && !generators['{$key}']?.applied"
                @click="openApplyModal('{$key}')"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Applica su Google Ads
            </button>
            <span x-show="generators['{$key}']?.applied" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/40">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Applicato
            </span>
APPLY;
    }
    return <<<HTML
<div x-show="generators['{$key}']?.loading" class="mt-3 flex items-center gap-2 text-xs text-primary-600 dark:text-primary-400">
    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    Generazione in corso...
</div>
<div x-show="generators['{$key}']?.result" x-cloak class="mt-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            Contenuto generato
        </span>
        <div class="flex items-center gap-2">
            <button @click="copyResult('{$key}')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/60 transition-colors">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <span x-text="generators['{$key}']?.copied ? 'Copiato!' : 'Copia'"></span>
            </button>
            <button x-show="generators['{$key}']?.data && ['copy','extensions','keywords'].includes(generators['{$key}']?.type)"
                @click="exportCsv('{$key}')"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-rose-700 bg-rose-100 hover:bg-rose-200 dark:text-rose-300 dark:bg-rose-900/40 dark:hover:bg-rose-900/60 transition-colors">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Esporta CSV Ads Editor
            </button>
{$applyBtn}
        </div>
    </div>
    <pre x-text="generators['{$key}']?.result" class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-sans leading-relaxed max-h-96 overflow-y-auto"></pre>
</div>
<div x-show="generators['{$key}']?.error" x-cloak class="mt-2 text-xs text-red-600 dark:text-red-400" x-text="generators['{$key}']?.error"></div>
HTML;
}
?>

<?php $currentPage = 'evaluations'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="evaluationDashboard()">

    <?php if ($isError): ?>
    <!-- Error State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-12">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Errore durante la valutazione</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Si e verificato un errore durante l'analisi delle campagne. Riprova o contatta il supporto.
            </p>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>" class="mt-6 inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Torna alle campagne
            </a>
        </div>
    </div>

    <?php elseif ($isAnalyzing): ?>
    <!-- Analyzing State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-800 p-12">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-amber-600 dark:text-amber-400 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Analisi in corso...</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                La valutazione AI delle campagne e in fase di elaborazione. Questa pagina si aggiornera automaticamente.
            </p>
        </div>
    </div>
    <script>setTimeout(() => location.reload(), 5000);</script>

    <?php elseif ($isNoChange): ?>
    <!-- No Change State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-8">
        <div class="flex flex-col items-center text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Nessun cambiamento significativo</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg">
                Le metriche delle campagne non hanno subito variazioni superiori alla soglia configurata rispetto al periodo precedente.
                Non e stata effettuata un'analisi AI per risparmiare crediti.
            </p>
            <div class="flex items-center gap-3 mt-3">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Auto
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">0 crediti</span>
                <span class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($evaluation['created_at'])) ?></span>
                <span class="text-xs text-slate-400">&bull; <?= $evaluation['campaigns_evaluated'] ?? 0 ?> campagne</span>
            </div>
        </div>
    </div>

    <?php if ($noChangeDeltas): ?>
    <!-- Metric Deltas -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Variazioni rilevate</h3>
        <?php
        $deltaLabels = [
            'total_clicks' => ['label' => 'Click', 'icon' => 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122'],
            'total_impressions' => ['label' => 'Impressioni', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
            'total_cost' => ['label' => 'Costo', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'invert' => true],
            'total_conversions' => ['label' => 'Conversioni', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            'avg_ctr' => ['label' => 'CTR', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
            'avg_cpc' => ['label' => 'CPC', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'invert' => true],
        ];
        ?>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <?php foreach ($deltaLabels as $key => $cfg): ?>
            <?php if (isset($noChangeDeltas[$key])): ?>
            <?php
            $d = $noChangeDeltas[$key];
            $pct = $d['percent_display'] ?? 0;
            $invert = $cfg['invert'] ?? false;
            if (abs($pct) < 0.5) {
                $colorClass = 'text-slate-400';
                $arrow = '&rarr;';
            } else {
                $isGood = $pct > 0;
                if ($invert) $isGood = !$isGood;
                $colorClass = $isGood ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                $arrow = $pct > 0
                    ? '<svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                    : '<svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
            }
            ?>
            <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
                <div class="h-8 w-8 rounded-lg bg-slate-200 dark:bg-slate-600 flex items-center justify-center flex-shrink-0">
                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $cfg['icon'] ?>"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= $cfg['label'] ?></p>
                    <p class="text-sm font-medium <?= $colorClass ?>">
                        <?= $arrow ?> <?= $pct >= 0 ? '+' : '' ?><?= $pct ?>%
                    </p>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($evaluation['previous_eval_id'])): ?>
    <div class="text-center">
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $evaluation['previous_eval_id']) ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Vedi ultima valutazione completa
        </a>
    </div>
    <?php endif; ?>

    <?php elseif (!$hasResults): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Nessun risultato disponibile</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                La valutazione non contiene ancora risultati. Potrebbe essere necessario rieseguirla.
            </p>
        </div>
    </div>

    <?php else: ?>
    <!-- ============================================ -->
    <!-- TABBED DASHBOARD RESULTS -->
    <!-- ============================================ -->

    <?php
    $overallScore = (float)($aiResponse['overall_score'] ?? 0);
    $summary = $aiResponse['summary'] ?? '';
    $campaigns = $aiResponse['campaigns'] ?? [];
    $topRecommendations = $aiResponse['top_recommendations'] ?? [];
    $extensionsEval = $aiResponse['extensions_evaluation'] ?? null;
    $landingEval = $aiResponse['landing_evaluation'] ?? null;
    $campaignSuggestions = $aiResponse['campaign_suggestions'] ?? [];
    $landingSuggestions = $aiResponse['landing_suggestions'] ?? [];
    $trend = $aiResponse['trend'] ?? null;
    $changesSummary = $aiResponse['changes_summary'] ?? null;
    $newIssues = $aiResponse['new_issues'] ?? [];
    $resolvedIssues = $aiResponse['resolved_issues'] ?? [];
    $evalType = $evaluation['eval_type'] ?? 'manual';
    $metricDeltas = !empty($evaluation['metric_deltas']) ? json_decode($evaluation['metric_deltas'], true) : null;

    // Helper: format KPI value based on metric key
    function formatKpiValue(string $key, $value): string {
        $val = (float)$value;
        return match(true) {
            str_contains($key, 'cpc') => '€' . number_format($val, 2, ',', '.'),
            str_contains($key, 'ctr') => number_format($val, 2, ',', '.') . '%',
            str_contains($key, 'cost') => '€' . number_format($val, 0, ',', '.'),
            str_contains($key, 'value') => '€' . number_format($val, 0, ',', '.'),
            default => number_format($val, 0, ',', '.'),
        };
    }

    $trendConfig = [
        'improving' => ['label' => 'In miglioramento', 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'icon' => 'M5 10l7-7m0 0l7 7m-7-7v18'],
        'stable' => ['label' => 'Stabile', 'color' => 'text-slate-600 dark:text-slate-400', 'bg' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300', 'icon' => 'M5 12h14'],
        'declining' => ['label' => 'In calo', 'color' => 'text-red-600 dark:text-red-400', 'bg' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'icon' => 'M19 14l-7 7m0 0l-7-7m7 7V3'],
        'mixed' => ['label' => 'Misto', 'color' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300', 'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'],
    ];
    $trendConf = $trendConfig[$trend] ?? null;

    // Count issues by severity across all campaigns
    $totalHighIssues = 0;
    $totalMediumIssues = 0;
    $totalLowIssues = 0;
    foreach ($campaigns as $c) {
        foreach ($c['issues'] ?? [] as $iss) {
            $sev = $iss['severity'] ?? 'low';
            if ($sev === 'high') $totalHighIssues++;
            elseif ($sev === 'medium') $totalMediumIssues++;
            else $totalLowIssues++;
        }
    }

    // Severity classes reused
    $severityClasses = [
        'high' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
        'low' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    ];
    $severityLabels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Bassa'];
    $priorityClasses = $severityClasses;
    $priorityLabels = $severityLabels;

    // Period selector data
    $periodsJson = json_encode($periods ?? []);
    $currentPeriodStr = $currentPeriod ?? '30';
    ?>

    <!-- ============================================ -->
    <!-- HEADER: Score + Summary + Period + Actions -->
    <!-- ============================================ -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col md:flex-row md:items-start gap-6">
            <!-- Score Circle -->
            <div class="flex-shrink-0 flex justify-center">
                <div class="relative">
                    <div class="w-24 h-24 rounded-full flex items-center justify-center border-4 <?= scoreBorderClass($overallScore) ?> bg-white dark:bg-slate-900">
                        <span class="text-3xl font-bold <?= scoreTextClass($overallScore) ?>"><?= number_format($overallScore, 1) ?></span>
                    </div>
                    <?php if ($trendConf): ?>
                    <div class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full <?= $trendConf['bg'] ?> flex items-center justify-center" title="<?= $trendConf['label'] ?>">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $trendConf['icon'] ?>"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary & Meta -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Punteggio Complessivo</h2>
                    <?php if ($evalType === 'auto'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Auto
                    </span>
                    <?php endif; ?>
                    <?php if ($trendConf): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $trendConf['bg'] ?>"><?= $trendConf['label'] ?></span>
                    <?php endif; ?>
                    <?php if ($totalHighIssues > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"><?= $totalHighIssues ?> critici</span>
                    <?php endif; ?>
                </div>
                <?php if ($summary): ?>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($summary) ?></p>
                <?php endif; ?>

                <!-- Metadata row -->
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                    <span><?= date('d/m/Y H:i', strtotime($evaluation['created_at'])) ?></span>
                    <span><?= (int)($evaluation['campaigns_evaluated'] ?? 0) ?> campagne</span>
                    <?php if (!empty($evaluation['ad_groups_evaluated'])): ?>
                    <span><?= (int)$evaluation['ad_groups_evaluated'] ?> gruppi</span>
                    <?php endif; ?>
                    <?php if (!empty($evaluation['landing_pages_analyzed'])): ?>
                    <span><?= (int)$evaluation['landing_pages_analyzed'] ?> landing</span>
                    <?php endif; ?>
                    <?php if (!empty($evaluation['credits_used'])): ?>
                    <span><?= number_format((float)$evaluation['credits_used'], 1) ?> crediti</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions & Period Selector -->
            <div class="flex-shrink-0 flex flex-col items-end gap-3">
                <!-- Period Selector -->
                <?php if (!empty($periods)): ?>
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-700 rounded-lg p-1">
                    <?php foreach (['7' => '7g', '14' => '14g', '30' => '30g'] as $pKey => $pLabel): ?>
                    <?php
                    $pData = $periods[$pKey] ?? ['available' => false];
                    $isActive = ($currentPeriodStr === $pKey);
                    $isAvailable = $pData['available'] ?? false;
                    ?>
                    <?php if ($isAvailable && !$isActive): ?>
                    <?php
                    $navUrl = $pData['has_evaluation'] && $pData['evaluation_id']
                        ? url("/ads-analyzer/projects/{$project['id']}/campaigns/evaluations/{$pData['evaluation_id']}")
                        : null;
                    ?>
                    <?php if ($navUrl): ?>
                    <a href="<?= $navUrl ?>" class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-600 transition-colors"><?= $pLabel ?></a>
                    <?php else: ?>
                    <span class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-400 dark:text-slate-500 cursor-not-allowed" title="Nessuna analisi per questo periodo"><?= $pLabel ?></span>
                    <?php endif; ?>
                    <?php elseif ($isActive): ?>
                    <span class="px-3 py-1.5 text-xs font-medium rounded-md bg-white dark:bg-slate-600 text-rose-700 dark:text-rose-300 shadow-sm"><?= $pLabel ?></span>
                    <?php else: ?>
                    <span class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-400 dark:text-slate-500 cursor-not-allowed opacity-50" title="Nessuna run disponibile"><?= $pLabel ?></span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Action buttons -->
                <div class="flex items-center gap-2">
                    <a href="<?= url("/ads-analyzer/projects/{$project['id']}/campaigns/evaluations/{$evaluation['id']}/export-pdf") ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 transition-colors"
                       target="_blank">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF
                    </a>
                    <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Indietro
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- KPI CARDS (metric deltas) -->
    <!-- ============================================ -->
    <?php if (!empty($metricDeltas)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <?php foreach ($metricDeltas as $key => $delta): ?>
        <?php
        $deltaPercent = (float)($delta['percent'] ?? 0);
        $positiveIsGood = $delta['positive_is_good'] ?? true;
        $isPositive = $deltaPercent > 0;
        $isGood = ($isPositive && $positiveIsGood) || (!$isPositive && !$positiveIsGood);
        $isBad = ($isPositive && !$positiveIsGood) || (!$isPositive && $positiveIsGood);
        $kpiColor = abs($deltaPercent) < 0.02 ? 'text-slate-500 dark:text-slate-400' : ($isGood ? 'text-emerald-600 dark:text-emerald-400' : ($isBad ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400'));
        $kpiBg = abs($deltaPercent) < 0.02 ? 'bg-slate-50 dark:bg-slate-700/30' : ($isGood ? 'bg-emerald-50 dark:bg-emerald-900/10' : ($isBad ? 'bg-red-50 dark:bg-red-900/10' : 'bg-slate-50 dark:bg-slate-700/30'));
        $kpiArrow = $deltaPercent > 0 ? '↑' : ($deltaPercent < 0 ? '↓' : '→');
        ?>
        <div class="<?= $kpiBg ?> rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1"><?= e($delta['label'] ?? $key) ?></p>
            <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= formatKpiValue($key, $delta['current'] ?? 0) ?></p>
            <p class="text-xs font-medium <?= $kpiColor ?> mt-0.5">
                <?= $kpiArrow ?> <?= isset($delta['percent_display']) ? abs($delta['percent_display']) . '%' : number_format(abs($deltaPercent * 100), 1) . '%' ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- TAB NAVIGATION -->
    <!-- ============================================ -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
            <button @click="activeTab = 'panoramica'" :class="activeTab === 'panoramica' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                Panoramica
            </button>
            <button @click="activeTab = 'campagne'" :class="activeTab === 'campagne' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                Campagne (<?= count($campaigns) ?>)
            </button>
            <?php if (!empty($extensionsEval)): ?>
            <button @click="activeTab = 'estensioni'" :class="activeTab === 'estensioni' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                Estensioni
            </button>
            <?php endif; ?>
            <?php if (!empty($landingEval) && ($evaluation['landing_pages_analyzed'] ?? 0) > 0): ?>
            <button @click="activeTab = 'landing'" :class="activeTab === 'landing' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                Landing Pages
            </button>
            <?php endif; ?>
            <?php if (!empty($campaignSuggestions) || !empty($landingSuggestions)): ?>
            <button @click="activeTab = 'azioni'" :class="activeTab === 'azioni' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                Azioni
            </button>
            <?php endif; ?>
        </nav>
    </div>

    <!-- ============================================ -->
    <!-- TAB: PANORAMICA -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'panoramica'" x-cloak class="space-y-6">

        <!-- Changes Summary (auto-eval) -->
        <?php if ($changesSummary): ?>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Riepilogo Cambiamenti</h3>
                    <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed"><?= e($changesSummary) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- New Issues / Resolved Issues -->
        <?php if (!empty($newIssues) || !empty($resolvedIssues)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (!empty($newIssues)): ?>
            <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Nuovi Problemi (<?= count($newIssues) ?>)
                </h3>
                <ul class="space-y-2">
                    <?php foreach ($newIssues as $ni): ?>
                    <li class="flex items-start gap-2 text-sm text-red-700 dark:text-red-400">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                        <?= e(is_string($ni) ? $ni : ($ni['description'] ?? '')) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if (!empty($resolvedIssues)): ?>
            <div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Problemi Risolti (<?= count($resolvedIssues) ?>)
                </h3>
                <ul class="space-y-2">
                    <?php foreach ($resolvedIssues as $ri): ?>
                    <li class="flex items-start gap-2 text-sm text-emerald-700 dark:text-emerald-400">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <?= e(is_string($ri) ? $ri : ($ri['description'] ?? '')) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Top Recommendations -->
        <?php if (!empty($topRecommendations)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Raccomandazioni Principali
            </h2>
            <ol class="space-y-3">
                <?php foreach ($topRecommendations as $index => $recommendation): ?>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-sm font-bold flex items-center justify-center">
                        <?= $index + 1 ?>
                    </span>
                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed pt-0.5"><?= e($recommendation) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

        <!-- Issues Heatmap -->
        <?php if (!empty($campaigns)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Score per Campagna
            </h2>
            <div class="space-y-3">
                <?php foreach ($campaigns as $cIdx => $camp): ?>
                <?php
                $cScore = (float)($camp['score'] ?? 0);
                $cIssuesHigh = count(array_filter($camp['issues'] ?? [], fn($i) => ($i['severity'] ?? '') === 'high'));
                $cIssuesMed = count(array_filter($camp['issues'] ?? [], fn($i) => ($i['severity'] ?? '') === 'medium'));
                $cIssuesLow = count(array_filter($camp['issues'] ?? [], fn($i) => ($i['severity'] ?? '') === 'low'));
                $cType = strtoupper($camp['campaign_type'] ?? 'SEARCH');
                $cTypeConf = $campaignTypeConfig[$cType] ?? ['bg' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'label' => $cType];
                $barWidth = max(5, min(100, $cScore * 10));
                ?>
                <button @click="activeTab = 'campagne'; $nextTick(() => openDrawer(<?= $cIdx ?>))"
                    class="w-full text-left group">
                    <div class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-16 text-right text-sm font-bold <?= scoreTextClass($cScore) ?>"><?= number_format($cScore, 1) ?>/10</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $cTypeConf['bg'] ?>"><?= $cTypeConf['label'] ?></span>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors"><?= e($camp['campaign_name'] ?? 'Campagna') ?></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                <div class="h-2 rounded-full <?= scoreColorClass($cScore) ?> transition-all" style="width: <?= $barWidth ?>%"></div>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex items-center gap-1">
                            <?php if ($cIssuesHigh > 0): ?><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"><?= $cIssuesHigh ?></span><?php endif; ?>
                            <?php if ($cIssuesMed > 0): ?><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300"><?= $cIssuesMed ?></span><?php endif; ?>
                            <?php if ($cIssuesLow > 0): ?><span class="px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"><?= $cIssuesLow ?></span><?php endif; ?>
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ============================================ -->
    <!-- TAB: CAMPAGNE (Card Grid) -->
    <!-- ============================================ -->
    <div x-show="activeTab === 'campagne'" x-cloak class="space-y-6">

        <?php if (!empty($campaigns)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($campaigns as $cIndex => $campaign): ?>
            <?php
            $campaignScore = (float)($campaign['score'] ?? 0);
            $issues = $campaign['issues'] ?? [];
            $adGroupsAI = $campaign['ad_groups'] ?? [];
            $campType = strtoupper($campaign['campaign_type'] ?? 'SEARCH');
            $typeConf = $campaignTypeConfig[$campType] ?? ['bg' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'label' => $campType];
            $highCount = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'high'));
            $medCount = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'medium'));
            $lowCount = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'low'));
            ?>
            <button @click="openDrawer(<?= $cIndex ?>)"
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 text-left hover:shadow-md hover:border-rose-300 dark:hover:border-rose-700 transition-all group relative overflow-hidden">
                <!-- Left color bar -->
                <div class="absolute left-0 top-0 bottom-0 w-1 <?= scoreColorClass($campaignScore) ?>"></div>

                <div class="flex items-start justify-between mb-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $typeConf['bg'] ?>"><?= $typeConf['label'] ?></span>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                            <?= e($campaign['campaign_name'] ?? 'Campagna') ?>
                        </h3>
                    </div>
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center border-2 <?= scoreBorderClass($campaignScore) ?> ml-3">
                        <span class="text-sm font-bold <?= scoreTextClass($campaignScore) ?>"><?= number_format($campaignScore, 1) ?></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($highCount > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"><?= $highCount ?> alta</span>
                    <?php endif; ?>
                    <?php if ($medCount > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300"><?= $medCount ?> media</span>
                    <?php endif; ?>
                    <?php if ($lowCount > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"><?= $lowCount ?> bassa</span>
                    <?php endif; ?>
                    <?php if (count($issues) === 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Nessun problema</span>
                    <?php endif; ?>
                    <?php if (!empty($adGroupsAI)): ?>
                    <span class="text-xs text-slate-400"><?= count($adGroupsAI) ?> gruppi</span>
                    <?php endif; ?>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- ============================================ -->
    <!-- DRAWER: Dettaglio Campagna (slide-in right) -->
    <!-- ============================================ -->
    <!-- Backdrop -->
    <div x-show="drawerOpen" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="closeDrawer()"
         class="fixed inset-0 bg-black/40 z-40"></div>

    <!-- Drawer Panel -->
    <div x-show="drawerOpen" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 bottom-0 w-full md:w-[60%] lg:w-[50%] bg-white dark:bg-slate-800 shadow-2xl z-50 overflow-y-auto">

        <template x-if="selectedCampaign !== null && campaignsData[selectedCampaign]">
            <div class="p-6 space-y-5">
                <!-- Drawer Header -->
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" :class="campaignsData[selectedCampaign].scoreBadgeClass" x-text="campaignsData[selectedCampaign].score + '/10'"></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="campaignsData[selectedCampaign].typeBg" x-text="campaignsData[selectedCampaign].typeLabel"></span>
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white" x-text="campaignsData[selectedCampaign].name"></h2>
                    </div>
                    <button @click="closeDrawer()" class="flex-shrink-0 p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Type-Specific Insights -->
                <template x-if="campaignsData[selectedCampaign].typeInsights">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1" x-text="'Analisi ' + campaignsData[selectedCampaign].typeLabel"></h4>
                                <p class="text-sm text-blue-700 dark:text-blue-400" x-text="campaignsData[selectedCampaign].typeInsights"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Strengths -->
                <template x-if="campaignsData[selectedCampaign].strengths.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Punti di Forza
                        </h4>
                        <ul class="space-y-1.5">
                            <template x-for="(s, i) in campaignsData[selectedCampaign].strengths" :key="i">
                                <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                                    <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                    <span x-text="s"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                <!-- Issues -->
                <template x-if="campaignsData[selectedCampaign].issues.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            Problemi
                        </h4>
                        <div class="space-y-3">
                            <template x-for="(issue, iIdx) in campaignsData[selectedCampaign].issues" :key="iIdx">
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="issue.severityClass" x-text="issue.severityLabel"></span>
                                        <template x-if="issue.areaLabel">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300" x-text="issue.areaLabel"></span>
                                        </template>
                                    </div>
                                    <p class="text-sm text-slate-700 dark:text-slate-300" x-text="issue.description"></p>
                                    <template x-if="issue.recommendation">
                                        <div class="mt-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                                            <div class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                <p class="text-sm text-amber-800 dark:text-amber-300" x-text="issue.recommendation"></p>
                                            </div>
                                        </div>
                                    </template>
                                    <?php if ($canEdit): ?>
                                    <template x-if="issue.genType">
                                        <div>
                                            <button @click="generateFix(issue.genType, {issue: issue.description, recommendation: issue.recommendation || '', campaign_name: campaignsData[selectedCampaign].name}, issue.genKey)"
                                                :disabled="isLoading(issue.genKey)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 disabled:opacity-50 transition-colors mt-2">
                                                <svg x-show="!isLoading(issue.genKey)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                                <svg x-show="isLoading(issue.genKey)" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <span x-text="isLoading(issue.genKey) ? 'Generazione...' : 'Genera con AI'"></span>
                                            </button>
                                            <div x-html="getGeneratorHtml(issue.genKey)"></div>
                                        </div>
                                    </template>
                                    <?php endif; ?>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Ad Groups -->
                <template x-if="campaignsData[selectedCampaign].adGroups.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span x-text="'Gruppi Annunci (' + campaignsData[selectedCampaign].adGroups.length + ')'"></span>
                        </h4>
                        <div class="space-y-2">
                            <template x-for="(ag, agIdx) in campaignsData[selectedCampaign].adGroups" :key="agIdx">
                                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden" x-data="{ agOpen: false }">
                                    <button @click="agOpen = !agOpen" class="w-full px-4 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold" :class="ag.scoreBadgeClass" x-text="ag.score"></span>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-left truncate" x-text="ag.name"></span>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <template x-if="ag.issueCount > 0">
                                                <span class="text-xs text-slate-400" x-text="ag.issueCount + ' problem' + (ag.issueCount !== 1 ? 'i' : 'a')"></span>
                                            </template>
                                            <svg :class="agOpen ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </button>
                                    <div x-show="agOpen" x-collapse>
                                        <div class="border-t border-slate-200 dark:border-slate-700 px-4 py-4 space-y-4">
                                            <!-- Mini score cards -->
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                <template x-for="metric in ag.metrics" :key="metric.label">
                                                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                                                        <p class="text-lg font-bold" :class="metric.colorClass" x-text="metric.value"></p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="metric.label"></p>
                                                    </div>
                                                </template>
                                            </div>
                                            <!-- Landing analysis -->
                                            <template x-if="ag.landingAnalysis">
                                                <div class="bg-slate-50 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600 rounded-lg p-3">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400" x-text="ag.landingAnalysis"></p>
                                                </div>
                                            </template>
                                            <!-- Strengths -->
                                            <template x-if="ag.strengths.length > 0">
                                                <ul class="space-y-1">
                                                    <template x-for="(s, si) in ag.strengths" :key="si">
                                                        <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400">
                                                            <span class="mt-1 w-1 h-1 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                                            <span x-text="s"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </template>
                                            <!-- Issues -->
                                            <template x-if="ag.issues.length > 0">
                                                <div class="space-y-2">
                                                    <template x-for="(agIss, agIssIdx) in ag.issues" :key="agIssIdx">
                                                        <div class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-3">
                                                            <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium" :class="agIss.severityClass" x-text="agIss.severityLabel"></span>
                                                                <template x-if="agIss.areaLabel">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300" x-text="agIss.areaLabel"></span>
                                                                </template>
                                                            </div>
                                                            <p class="text-xs text-slate-700 dark:text-slate-300" x-text="agIss.description"></p>
                                                            <template x-if="agIss.recommendation">
                                                                <p class="mt-1.5 text-xs text-amber-700 dark:text-amber-400 italic" x-text="agIss.recommendation"></p>
                                                            </template>
                                                            <?php if ($canEdit): ?>
                                                            <template x-if="agIss.genType">
                                                                <div>
                                                                    <button @click="generateFix(agIss.genType, {issue: agIss.description, recommendation: agIss.recommendation || '', campaign_name: campaignsData[selectedCampaign].name, ad_group_name: ag.name}, agIss.genKey)"
                                                                        :disabled="isLoading(agIss.genKey)"
                                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 disabled:opacity-50 transition-colors mt-2">
                                                                        <svg x-show="!isLoading(agIss.genKey)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                                                        <svg x-show="isLoading(agIss.genKey)" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                        <span x-text="isLoading(agIss.genKey) ? 'Generazione...' : 'Genera con AI'"></span>
                                                                    </button>
                                                                    <div x-html="getGeneratorHtml(agIss.genKey)"></div>
                                                                </div>
                                                            </template>
                                                            <?php endif; ?>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- ============================================ -->
    <!-- TAB: ESTENSIONI -->
    <!-- ============================================ -->
    <?php if (!empty($extensionsEval)): ?>
    <?php $extScore = (float)($extensionsEval['score'] ?? 0); ?>
    <div x-show="activeTab === 'estensioni'" x-cloak class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/></svg>
                    Valutazione Estensioni
                </h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?= scoreBadgeBgClass($extScore) ?>"><?= number_format($extScore, 1) ?>/10</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if (!empty($extensionsEval['present'])): ?>
                <div>
                    <h4 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Estensioni Presenti
                    </h4>
                    <ul class="space-y-1.5">
                        <?php foreach ($extensionsEval['present'] as $present): ?>
                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            <?= e($present) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($extensionsEval['missing'])): ?>
                <div>
                    <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Estensioni Mancanti
                    </h4>
                    <ul class="space-y-1.5">
                        <?php foreach ($extensionsEval['missing'] as $missing): ?>
                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                            <?= e($missing) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($extensionsEval['suggestions'])): ?>
            <div class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-700">
                <h4 class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-2">Suggerimenti</h4>
                <ul class="space-y-1.5">
                    <?php foreach ($extensionsEval['suggestions'] as $suggestion): ?>
                    <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                        <?= e($suggestion) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($canEdit && !empty($extensionsEval['missing'])): ?>
            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button @click="generateFix('extensions', <?= e(json_encode(['missing' => $extensionsEval['missing']])) ?>, 'ext_main')"
                    :disabled="isLoading('ext_main')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 disabled:opacity-50 transition-colors">
                    <svg x-show="!generators['ext_main']?.loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    <svg x-show="generators['ext_main']?.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="generators['ext_main']?.loading ? 'Generazione...' : 'Genera Estensioni Mancanti'"></span>
                </button>
                <?= renderAiGenerator('ext_main', $canEdit && !empty($project['google_ads_customer_id'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- TAB: LANDING PAGES -->
    <!-- ============================================ -->
    <?php if (!empty($landingEval) && ($evaluation['landing_pages_analyzed'] ?? 0) > 0): ?>
    <?php $landScore = (float)($landingEval['overall_score'] ?? 0); ?>
    <div x-show="activeTab === 'landing'" x-cloak class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Analisi Landing Pages
                </h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?= scoreBadgeBgClass($landScore) ?>"><?= number_format($landScore, 1) ?>/10</span>
            </div>

            <?php if (!empty($landingEval['issues'])): ?>
            <div class="space-y-3">
                <?php foreach ($landingEval['issues'] as $landIssue): ?>
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                    <?php if (!empty($landIssue['url'])): ?>
                    <p class="text-xs font-mono text-slate-500 dark:text-slate-400 mb-2 truncate"><?= e($landIssue['url']) ?></p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-700 dark:text-slate-300"><?= e($landIssue['issue'] ?? '') ?></p>
                    <?php if (!empty($landIssue['recommendation'])): ?>
                    <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            <p class="text-sm text-amber-800 dark:text-amber-300"><?= e($landIssue['recommendation']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-slate-500 dark:text-slate-400">Nessun problema rilevato nelle landing pages analizzate.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- TAB: AZIONI -->
    <!-- ============================================ -->
    <?php if (!empty($campaignSuggestions) || !empty($landingSuggestions)): ?>
    <div x-show="activeTab === 'azioni'" x-cloak class="space-y-6">

        <!-- Campaign Suggestions -->
        <?php if (!empty($campaignSuggestions)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Suggerimenti Campagne
            </h2>
            <div class="space-y-3">
                <?php foreach ($campaignSuggestions as $sIndex => $cs): ?>
                <?php
                $csPriority = $cs['priority'] ?? 'medium';
                $csPriorityClass = $priorityClasses[$csPriority] ?? $priorityClasses['medium'];
                $csPriorityLabel = $priorityLabels[$csPriority] ?? ucfirst($csPriority);
                $csAreaLower = strtolower($cs['area'] ?? '');
                $csGenType = null;
                if (str_contains($csAreaLower, 'copy') || str_contains($csAreaLower, 'annunci')) $csGenType = 'copy';
                elseif (str_contains($csAreaLower, 'keyword')) $csGenType = 'keywords';
                elseif (str_contains($csAreaLower, 'estension') || str_contains($csAreaLower, 'extension')) $csGenType = 'extensions';
                $sugKey = "sug_{$sIndex}";
                ?>
                <div class="flex items-start gap-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                    <div class="flex flex-wrap gap-2 flex-shrink-0 pt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $csPriorityClass ?>"><?= $csPriorityLabel ?></span>
                        <?php if (!empty($cs['area'])): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300"><?= e($cs['area']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= e($cs['suggestion'] ?? '') ?></p>
                        <?php if (!empty($cs['expected_impact'])): ?>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Impatto: <?= e($cs['expected_impact']) ?></p>
                        <?php endif; ?>
                        <?php if ($canEdit && $csGenType): ?>
                        <button @click="generateFix('<?= $csGenType ?>', <?= e(json_encode(['suggestion' => $cs['suggestion'] ?? '', 'expected_impact' => $cs['expected_impact'] ?? '', 'campaign_name' => ''])) ?>, '<?= $sugKey ?>')"
                            :disabled="isLoading('<?= $sugKey ?>')"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 disabled:opacity-50 transition-colors mt-2">
                            <svg x-show="!generators['<?= $sugKey ?>']?.loading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            <svg x-show="generators['<?= $sugKey ?>']?.loading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="generators['<?= $sugKey ?>']?.loading ? 'Generazione...' : 'Genera con AI'"></span>
                        </button>
                        <?= renderAiGenerator($sugKey, $canEdit && !empty($project['google_ads_customer_id'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Landing Suggestions -->
        <?php if (!empty($landingSuggestions) && ($evaluation['landing_pages_analyzed'] ?? 0) > 0): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Suggerimenti Landing Pages
            </h2>
            <div class="space-y-3">
                <?php foreach ($landingSuggestions as $ls): ?>
                <?php
                $lsPriority = $ls['priority'] ?? 'medium';
                $lsPriorityClass = $priorityClasses[$lsPriority] ?? $priorityClasses['medium'];
                $lsPriorityLabel = $priorityLabels[$lsPriority] ?? ucfirst($lsPriority);
                ?>
                <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $lsPriorityClass ?>"><?= $lsPriorityLabel ?></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Azione manuale
                        </span>
                        <?php if (!empty($ls['url'])): ?>
                        <span class="text-xs font-mono text-slate-500 dark:text-slate-400 truncate max-w-xs"><?= e($ls['url']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= e($ls['suggestion'] ?? '') ?></p>
                    <?php if (!empty($ls['expected_impact'])): ?>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Impatto: <?= e($ls['expected_impact']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <!-- Back Link -->
    <div class="flex items-center justify-between pt-2">
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>" class="inline-flex items-center text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Torna alle campagne
        </a>
        <?php if (!empty($evaluation['completed_at'])): ?>
        <span class="text-xs text-slate-400 dark:text-slate-500">Completata il <?= date('d/m/Y H:i', strtotime($evaluation['completed_at'])) ?></span>
        <?php endif; ?>
    </div>

    <?php endif; ?>

    <!-- Modal Doppia Conferma Applica su Google Ads -->
    <div x-show="applyModal.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="applyModal.open = false">
        <div class="fixed inset-0 bg-black/50" @click="applyModal.open = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 z-10">
            <!-- Step 1: Prima conferma -->
            <template x-if="!applyModal.confirmed && !applyModal.applying && !applyModal.done">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Applica su Google Ads</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Questa azione modifica il tuo account</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 mb-4">
                        <p class="text-sm text-slate-700 dark:text-slate-300 mb-2">Stai per applicare:</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="applyModal.description"></p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-5">
                        <p class="text-xs text-amber-800 dark:text-amber-300">
                            <strong>Attenzione:</strong> Le modifiche verranno applicate direttamente sul tuo account Google Ads.
                            I nuovi annunci saranno creati in <strong>pausa</strong>. Le estensioni saranno attive.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="applyModal.open = false" class="flex-1 px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg transition-colors">
                            Annulla
                        </button>
                        <button @click="applyModal.confirmed = true" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            Continua
                        </button>
                    </div>
                </div>
            </template>

            <!-- Step 2: Seconda conferma (digitare CONFERMA) -->
            <template x-if="applyModal.confirmed && !applyModal.applying && !applyModal.done">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Conferma finale</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Scrivi CONFERMA per procedere</p>
                        </div>
                    </div>
                    <input type="text" x-model="applyModal.confirmText"
                        placeholder="Scrivi CONFERMA"
                        class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-4"
                        @keydown.enter="applyModal.confirmText.toUpperCase() === 'CONFERMA' && executeApply()">
                    <div class="flex gap-3">
                        <button @click="applyModal.confirmed = false; applyModal.confirmText = ''" class="flex-1 px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg transition-colors">
                            Indietro
                        </button>
                        <button @click="executeApply()" :disabled="applyModal.confirmText.toUpperCase() !== 'CONFERMA'"
                            :class="applyModal.confirmText.toUpperCase() === 'CONFERMA' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed dark:bg-slate-700 dark:text-slate-500'"
                            class="flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors">
                            Applica ora
                        </button>
                    </div>
                </div>
            </template>

            <!-- Step 3: In corso -->
            <template x-if="applyModal.applying">
                <div class="text-center py-4">
                    <svg class="w-10 h-10 mx-auto mb-3 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Applicazione in corso su Google Ads...</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Non chiudere questa finestra</p>
                </div>
            </template>

            <!-- Step 4: Completato -->
            <template x-if="applyModal.done">
                <div class="text-center py-4">
                    <template x-if="!applyModal.error">
                        <div>
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="applyModal.resultMessage"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Le modifiche sono attive sul tuo account Google Ads</p>
                        </div>
                    </template>
                    <template x-if="applyModal.error">
                        <div>
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-400" x-text="applyModal.error"></p>
                        </div>
                    </template>
                    <button @click="applyModal.open = false; resetApplyModal()" class="mt-4 px-4 py-2 text-sm font-medium text-white bg-slate-600 hover:bg-slate-700 rounded-lg transition-colors">
                        Chiudi
                    </button>
                </div>
            </template>
        </div>
    </div>

</div>

<?php if ($hasResults): ?>
<script>
function evaluationDashboard() {
    return {
        activeTab: 'panoramica',
        drawerOpen: false,
        selectedCampaign: null,
        generators: {},
        applyModal: { open: false, key: '', confirmed: false, confirmText: '', applying: false, done: false, error: null, resultMessage: '', description: '' },
        generateUrl: '<?= e($generateUrl ?? '') ?>',
        applyUrl: '<?= e($applyUrl ?? '') ?>',
        csrfToken: '<?= csrf_token() ?>',

        // Campaign data for drawer (populated from PHP)
        campaignsData: <?= json_encode(array_map(function($cIndex, $campaign) use ($campaignTypeConfig, $areaLabels, $generatableAreas, $severityClasses, $severityLabels) {
            $campType = strtoupper($campaign['campaign_type'] ?? 'SEARCH');
            $typeConf = $campaignTypeConfig[$campType] ?? ['bg' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'label' => $campType];
            $score = (float)($campaign['score'] ?? 0);

            $issues = array_map(function($iIndex, $issue) use ($areaLabels, $generatableAreas, $cIndex, $severityClasses, $severityLabels) {
                $sev = $issue['severity'] ?? 'low';
                $area = $issue['area'] ?? '';
                $genType = in_array($area, $generatableAreas) ? $area : null;
                return [
                    'severity' => $sev,
                    'severityClass' => $severityClasses[$sev] ?? $severityClasses['low'],
                    'severityLabel' => $severityLabels[$sev] ?? ucfirst($sev),
                    'area' => $area,
                    'areaLabel' => !empty($area) ? ($areaLabels[$area] ?? ucfirst($area)) : null,
                    'description' => $issue['description'] ?? '',
                    'recommendation' => $issue['recommendation'] ?? null,
                    'genType' => $genType,
                    'genKey' => "issue_{$cIndex}_{$iIndex}",
                ];
            }, array_keys($campaign['issues'] ?? []), $campaign['issues'] ?? []);

            $adGroups = array_map(function($agIndex, $ag) use ($cIndex, $severityClasses, $severityLabels, $areaLabels, $generatableAreas) {
                $agScore = (float)($ag['score'] ?? 0);
                $metrics = [];
                if (isset($ag['keyword_coherence'])) $metrics[] = ['label' => 'Coerenza KW', 'value' => number_format((float)$ag['keyword_coherence'], 1), 'colorClass' => $agScore < 5 ? 'text-red-600 dark:text-red-400' : ($agScore <= 7 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400')];
                if (isset($ag['ad_relevance'])) {
                    $arScore = (float)$ag['ad_relevance'];
                    $metrics[] = ['label' => 'Pertinenza Annunci', 'value' => number_format($arScore, 1), 'colorClass' => $arScore < 5 ? 'text-red-600 dark:text-red-400' : ($arScore <= 7 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400')];
                }
                if (isset($ag['landing_coherence'])) {
                    $lcScore = (float)$ag['landing_coherence'];
                    $metrics[] = ['label' => 'Coerenza Landing', 'value' => number_format($lcScore, 1), 'colorClass' => $lcScore < 5 ? 'text-red-600 dark:text-red-400' : ($lcScore <= 7 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400')];
                }
                if (isset($ag['quality_score_avg'])) {
                    $qsScore = (float)$ag['quality_score_avg'];
                    $metrics[] = ['label' => 'QS Medio', 'value' => number_format($qsScore, 1), 'colorClass' => $qsScore < 5 ? 'text-red-600 dark:text-red-400' : ($qsScore <= 7 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400')];
                }

                $agIssues = array_map(function($agIssIdx, $agIss) use ($cIndex, $agIndex, $severityClasses, $severityLabels, $areaLabels, $generatableAreas) {
                    $s = $agIss['severity'] ?? 'low';
                    $a = $agIss['area'] ?? '';
                    $genType = in_array($a, $generatableAreas) ? $a : null;
                    return [
                        'severityClass' => $severityClasses[$s] ?? $severityClasses['low'],
                        'severityLabel' => $severityLabels[$s] ?? ucfirst($s),
                        'areaLabel' => !empty($a) ? ($areaLabels[$a] ?? ucfirst($a)) : null,
                        'description' => $agIss['description'] ?? '',
                        'recommendation' => $agIss['recommendation'] ?? null,
                        'genType' => $genType,
                        'genKey' => "ag_{$cIndex}_{$agIndex}_{$agIssIdx}",
                    ];
                }, array_keys($ag['issues'] ?? []), $ag['issues'] ?? []);

                return [
                    'name' => $ag['ad_group_name'] ?? 'Gruppo',
                    'score' => number_format($agScore, 1),
                    'scoreBadgeClass' => $agScore < 5 ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : ($agScore <= 7 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'),
                    'metrics' => $metrics,
                    'landingAnalysis' => $ag['landing_analysis'] ?? null,
                    'strengths' => $ag['strengths'] ?? [],
                    'issues' => $agIssues,
                    'issueCount' => count($ag['issues'] ?? []),
                ];
            }, array_keys($campaign['ad_groups'] ?? []), $campaign['ad_groups'] ?? []);

            return [
                'name' => $campaign['campaign_name'] ?? 'Campagna',
                'score' => number_format($score, 1),
                'scoreBadgeClass' => $score < 5 ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : ($score <= 7 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'),
                'typeLabel' => $typeConf['label'],
                'typeBg' => $typeConf['bg'],
                'typeInsights' => $campaign['type_specific_insights'] ?? null,
                'strengths' => $campaign['strengths'] ?? [],
                'issues' => $issues,
                'adGroups' => $adGroups,
            ];
        }, array_keys($campaigns), $campaigns)) ?>,

        openDrawer(index) {
            this.selectedCampaign = index;
            this.drawerOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeDrawer() {
            this.drawerOpen = false;
            this.selectedCampaign = null;
            document.body.style.overflow = '';
        },

        isLoading(key) {
            return !!(this.generators[key] && this.generators[key].loading);
        },

        getGeneratorHtml(key) {
            const gen = this.generators[key];
            if (!gen) return '';
            let html = '';
            if (gen.loading) {
                html += '<div class="mt-3 flex items-center gap-2 text-xs text-primary-600 dark:text-primary-400"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generazione in corso...</div>';
            }
            if (gen.result) {
                html += '<div class="mt-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4"><pre class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-sans leading-relaxed max-h-96 overflow-y-auto">' + this.escapeHtml(gen.result) + '</pre></div>';
                <?php if ($canEdit && !empty($project['google_ads_customer_id'])): ?>
                if (gen.data && ['copy','extensions','keywords'].includes(gen.type) && !gen.applied) {
                    html += '<button onclick="document.querySelector(\'[x-data]\')._x_dataStack[0].openApplyModal(\'' + key + '\')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors mt-2"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg> Applica su Google Ads</button>';
                }
                if (gen.applied) {
                    html += '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/40 mt-2"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Applicato</span>';
                }
                <?php endif; ?>
            }
            if (gen.error) {
                html += '<div class="mt-2 text-xs text-red-600 dark:text-red-400">' + this.escapeHtml(gen.error) + '</div>';
            }
            return html;
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        async generateFix(type, context, key) {
            this.generators[key] = { loading: true, result: null, error: null, copied: false };

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('type', type);
                formData.append('context', JSON.stringify(context));

                const resp = await fetch(this.generateUrl, { method: 'POST', body: formData });

                if (!resp.ok) {
                    if (resp.status === 502 || resp.status === 504) {
                        this.generators[key] = { loading: false, result: null, error: 'Timeout del server. Riprova tra qualche istante.', copied: false };
                        return;
                    }
                    const errData = await resp.json().catch(() => null);
                    throw new Error(errData?.error || 'Errore HTTP ' + resp.status);
                }

                const data = await resp.json();
                if (data.error) throw new Error(data.error);

                this.generators[key] = { loading: false, result: data.content || '', type: data.type || '', data: data.data || null, error: null, copied: false };
            } catch (e) {
                this.generators[key] = { loading: false, result: null, type: '', data: null, error: e.message || 'Errore di connessione. Riprova.', copied: false };
            }
        },

        async copyResult(key) {
            const text = this.generators[key]?.result;
            if (!text) return;
            try { await navigator.clipboard.writeText(text); } catch (_) {
                const ta = document.createElement('textarea');
                ta.value = text; ta.style.cssText = 'position:fixed;left:-999px';
                document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
            }
            this.generators[key].copied = true;
            setTimeout(() => { if (this.generators[key]) this.generators[key].copied = false; }, 2000);
        },

        exportCsv(key) {
            const gen = this.generators[key];
            if (!gen || !gen.data) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.generateUrl.replace('/generate', '/export-csv');
            form.target = '_blank';
            const fields = { '_csrf_token': this.csrfToken, 'type': gen.type, 'data': JSON.stringify(gen.data), 'campaign_name': '<?= e($project['name'] ?? '') ?>' };
            for (const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = value;
                form.appendChild(input);
            }
            document.body.appendChild(form); form.submit(); document.body.removeChild(form);
        },

        openApplyModal(key) {
            const gen = this.generators[key];
            if (!gen || !gen.data) return;
            const isDuplicateRemoval = gen.data?.action === 'remove_duplicates';
            const typeDescriptions = {
                'copy': 'Verra creato un nuovo annuncio RSA in stato PAUSED nel gruppo di annunci della campagna. Potrai attivarlo manualmente da Google Ads.',
                'extensions': 'Verranno aggiunte le estensioni generate (sitelink, callout, snippet strutturati) alla campagna. Le estensioni esistenti non verranno modificate.',
                'keywords': isDuplicateRemoval
                    ? 'Verranno RIMOSSE le keyword duplicate dai gruppi di annunci indicati. Le keyword verranno mantenute nel gruppo piu pertinente. Questa operazione non e reversibile.'
                    : 'Verranno aggiunte le keyword negative alla campagna a livello di campagna. Le keyword negative esistenti non verranno modificate.',
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
            this.applyModal.confirmed = false;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('type', gen.type);
                formData.append('data', JSON.stringify(gen.data));

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
    };
}
</script>
<?php endif; ?>
