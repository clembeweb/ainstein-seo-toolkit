<?php
/**
 * Valutazione AI Campagne - Risultati
 *
 * Variables:
 * - $project: project data (id, name)
 * - $evaluation: evaluation record (id, name, status, campaigns_evaluated, ads_evaluated, credits_used, created_at, completed_at)
 * - $aiResponse: decoded AI response array
 * - $user, $modules
 */

// Area translations
$areaLabels = [
    'copy' => 'Copy',
    'landing' => 'Landing Page',
    'performance' => 'Performance',
    'budget' => 'Budget',
    'extensions' => 'Estensioni',
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
?>

<div class="space-y-6" x-data="{ openCampaigns: {} }">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Campagne
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Valutazione AI Campagne</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= e($evaluation['name'] ?? 'Valutazione') ?> &mdash; <?= e($project['name']) ?>
            </p>
        </div>
        <?php if ($hasResults): ?>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Stampa
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($isError): ?>
    <!-- Error State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-800 p-12">
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
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 p-12">
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

    <?php elseif (!$hasResults): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12">
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
    <!-- RESULTS -->
    <!-- ============================================ -->

    <?php
    $overallScore = (float)($aiResponse['overall_score'] ?? 0);
    $summary = $aiResponse['summary'] ?? '';
    $campaigns = $aiResponse['campaigns'] ?? [];
    $topRecommendations = $aiResponse['top_recommendations'] ?? [];
    $extensionsEval = $aiResponse['extensions_evaluation'] ?? null;
    ?>

    <!-- Overall Score Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <!-- Score Circle -->
            <div class="flex-shrink-0 flex justify-center">
                <div class="w-24 h-24 rounded-full flex items-center justify-center border-4 <?= scoreBorderClass($overallScore) ?> bg-white dark:bg-slate-900">
                    <span class="text-3xl font-bold <?= scoreTextClass($overallScore) ?>"><?= number_format($overallScore, 1) ?></span>
                </div>
            </div>

            <!-- Summary & Metadata -->
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Punteggio Complessivo</h2>
                <?php if ($summary): ?>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($summary) ?></p>
                <?php endif; ?>

                <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <?= date('d/m/Y H:i', strtotime($evaluation['created_at'])) ?>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <?= (int)($evaluation['campaigns_evaluated'] ?? 0) ?> campagne valutate
                    </div>
                    <?php if (!empty($evaluation['ads_evaluated'])): ?>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <?= (int)$evaluation['ads_evaluated'] ?> annunci analizzati
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($evaluation['credits_used'])): ?>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                        </svg>
                        <?= number_format((float)$evaluation['credits_used'], 1) ?> crediti
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Recommendations -->
    <?php if (!empty($topRecommendations)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
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

    <!-- Campaign-by-Campaign Analysis -->
    <?php if (!empty($campaigns)): ?>
    <div class="space-y-4">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Analisi per Campagna</h2>

        <?php foreach ($campaigns as $cIndex => $campaign): ?>
        <?php
        $campaignScore = (float)($campaign['score'] ?? 0);
        $strengths = $campaign['strengths'] ?? [];
        $issues = $campaign['issues'] ?? [];
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
             x-data="{ open: <?= $cIndex === 0 ? 'true' : 'false' ?> }">

            <!-- Campaign Header (Accordion Toggle) -->
            <button @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= scoreBadgeBgClass($campaignScore) ?>">
                        <?= number_format($campaignScore, 1) ?>
                    </span>
                    <span class="font-medium text-slate-900 dark:text-white text-left truncate"><?= e($campaign['campaign_name'] ?? 'Campagna') ?></span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <?php if (!empty($issues)): ?>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        <?= count($issues) ?> problem<?= count($issues) !== 1 ? 'i' : 'a' ?>
                    </span>
                    <?php endif; ?>
                    <svg :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>

            <!-- Campaign Content -->
            <div x-show="open" x-collapse>
                <div class="border-t border-slate-200 dark:border-slate-700 px-6 py-5 space-y-5">

                    <!-- Punti di Forza -->
                    <?php if (!empty($strengths)): ?>
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Punti di Forza
                        </h4>
                        <ul class="space-y-1.5">
                            <?php foreach ($strengths as $strength): ?>
                            <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                <?= e($strength) ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Problemi -->
                    <?php if (!empty($issues)): ?>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            Problemi Rilevati
                        </h4>
                        <div class="space-y-3">
                            <?php foreach ($issues as $issue): ?>
                            <?php
                            $severity = $issue['severity'] ?? 'low';
                            $area = $issue['area'] ?? '';
                            $severityClasses = [
                                'high' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                                'low' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            ];
                            $severityClass = $severityClasses[$severity] ?? $severityClasses['low'];
                            $severityLabels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Bassa'];
                            $severityLabel = $severityLabels[$severity] ?? ucfirst($severity);
                            $areaLabel = $areaLabels[$area] ?? ucfirst($area);
                            ?>
                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <!-- Severity Badge -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $severityClass ?>">
                                        <?= $severityLabel ?>
                                    </span>
                                    <!-- Area Tag -->
                                    <?php if ($area): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                        <?= e($areaLabel) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Description -->
                                <p class="text-sm text-slate-700 dark:text-slate-300"><?= e($issue['description'] ?? '') ?></p>

                                <!-- Recommendation -->
                                <?php if (!empty($issue['recommendation'])): ?>
                                <div class="mt-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                        <p class="text-sm text-amber-800 dark:text-amber-300"><?= e($issue['recommendation']) ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Empty Issues/Strengths -->
                    <?php if (empty($strengths) && empty($issues)): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">
                        Nessun dettaglio disponibile per questa campagna.
                    </p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Extensions Evaluation -->
    <?php if (!empty($extensionsEval)): ?>
    <?php $extScore = (float)($extensionsEval['score'] ?? 0); ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                </svg>
                Valutazione Estensioni
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold <?= scoreBadgeBgClass($extScore) ?>">
                <?= number_format($extScore, 1) ?>/10
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Missing Extensions -->
            <?php if (!empty($extensionsEval['missing'])): ?>
            <div>
                <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
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

            <!-- Suggestions -->
            <?php if (!empty($extensionsEval['suggestions'])): ?>
            <div>
                <h4 class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Suggerimenti
                </h4>
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
        </div>
    </div>
    <?php endif; ?>

    <!-- Back Link -->
    <div class="flex items-center justify-between pt-2">
        <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns') ?>" class="inline-flex items-center text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alle campagne
        </a>

        <?php if (!empty($evaluation['completed_at'])): ?>
        <span class="text-xs text-slate-400 dark:text-slate-500">
            Completata il <?= date('d/m/Y H:i', strtotime($evaluation['completed_at'])) ?>
        </span>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>
