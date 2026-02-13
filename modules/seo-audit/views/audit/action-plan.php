<?php
// Health score colors
$currentScore = $plan['health_current'] ?? ($project['health_score'] ?? 0);
$expectedScore = $plan['health_expected'] ?? $currentScore;
$scoreDiff = $expectedScore - $currentScore;

if ($currentScore >= 80) {
    $scoreColor = 'text-emerald-600 dark:text-emerald-400';
    $scoreRing = 'stroke-emerald-500';
} elseif ($currentScore >= 50) {
    $scoreColor = 'text-yellow-600 dark:text-yellow-400';
    $scoreRing = 'stroke-yellow-500';
} else {
    $scoreColor = 'text-red-600 dark:text-red-400';
    $scoreRing = 'stroke-red-500';
}

// Progress
$totalFixes = $plan['total_fixes'] ?? 0;
$completedFixes = $plan['fixes_completed'] ?? 0;
$progress = $totalFixes > 0 ? round(($completedFixes / $totalFixes) * 100) : 0;

// Formatted time
$estimatedMinutes = $plan['estimated_time_minutes'] ?? 0;
if ($estimatedMinutes >= 60) {
    $hours = floor($estimatedMinutes / 60);
    $mins = $estimatedMinutes % 60;
    $formattedTime = $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
} else {
    $formattedTime = "{$estimatedMinutes} min";
}
?>

<?php $currentPage = 'action-plan'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="actionPlan(<?= htmlspecialchars(json_encode([
    'projectId' => $project['id'],
    'baseUrl' => url(''),
    'planExists' => !empty($plan),
    'totalFixes' => $totalFixes,
    'completedFixes' => $completedFixes,
    'progress' => $progress,
    'csrfToken' => csrf_token(),
])) ?>)">

    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Piano d'Azione AI</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($project['name']) ?></p>
                </div>
            </div>
            <?php if ($plan): ?>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/action-plan/export') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Esporta To-Do List
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($plan): ?>
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Score Attuale -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Score Attuale</p>
                    <p class="text-3xl font-bold <?= $scoreColor ?>"><?= $currentScore ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Score Atteso -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Score Atteso</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?= $expectedScore ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Punti Guadagno -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Punti Guadagno</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">+<?= $scoreDiff ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tempo Stimato -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Tempo Stimato</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $formattedTime ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Progresso</span>
            <span class="text-sm text-slate-500 dark:text-slate-400" x-text="`${completedFixes}/${totalFixes} fix`"><?= $completedFixes ?>/<?= $totalFixes ?> fix</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-3 rounded-full transition-all duration-500"
                 :style="`width: ${progress}%`"
                 style="width: <?= $progress ?>%"></div>
        </div>
        <p class="mt-2 text-right text-sm font-medium" :class="progress === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'" x-text="`${progress}%`"><?= $progress ?>%</p>
    </div>

    <!-- Pages List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pagine ordinate per impatto</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Risolvile in questo ordine per massimizzare il risultato</p>
        </div>

        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($plan['pages'] as $index => $page):
                $pageId = $page['page_id'];
                $fixes = $pagesWithFixes[$pageId] ?? [];
                $pageCompleted = $page['fixes_completed'];
                $pageTotal = $page['fixes_count'];
                $isComplete = $pageCompleted == $pageTotal;
                $priorityColor = $page['max_priority'] >= 8 ? 'red' : ($page['max_priority'] >= 5 ? 'yellow' : 'blue');
            ?>
            <div x-data="{ open: false }" class="<?= $isComplete ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : '' ?>">
                <!-- Page Header -->
                <button @click="open = !open" class="w-full p-5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors text-left">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center <?= $isComplete ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700' ?>">
                            <?php if ($isComplete): ?>
                            <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <?php else: ?>
                            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-900 dark:text-white truncate"><?= e($page['url']) ?></p>
                            <div class="flex flex-wrap items-center gap-3 mt-1 text-xs">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-<?= $priorityColor ?>-100 dark:bg-<?= $priorityColor ?>-900/30 text-<?= $priorityColor ?>-700 dark:text-<?= $priorityColor ?>-400">
                                    <?= $pageTotal ?> problemi
                                </span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">+<?= $page['total_impact'] ?> punti</span>
                                <span class="text-slate-500 dark:text-slate-400">~<?= $page['total_time'] ?> min</span>
                                <span class="text-slate-500 dark:text-slate-400" x-text="'<?= $pageCompleted ?>/' + '<?= $pageTotal ?>' + ' completati'"><?= $pageCompleted ?>/<?= $pageTotal ?></span>
                            </div>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Fixes List (Expanded) -->
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <?php foreach ($fixes as $fix): ?>
                    <div class="p-5 border-b border-slate-200 dark:border-slate-700 last:border-b-0" x-data="{ completed: <?= $fix['is_completed'] ? 'true' : 'false' ?> }">
                        <div class="flex items-start gap-4">
                            <!-- Checkbox -->
                            <button @click="toggleFix(<?= $fix['id'] ?>, completed); completed = !completed"
                                    class="flex-shrink-0 mt-1 w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors"
                                    :class="completed ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300 dark:border-slate-600 hover:border-emerald-500'">
                                <svg x-show="completed" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>

                            <div class="flex-1 min-w-0">
                                <!-- Step Header -->
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">STEP <?= $fix['step_order'] ?></span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white" :class="completed && 'line-through opacity-60'"><?= e($fix['issue_title'] ?? $fix['issue_type']) ?></span>
                                </div>

                                <!-- Fix Code Box -->
                                <?php if (!empty($fix['fix_code'])): ?>
                                <div class="relative bg-slate-800 dark:bg-slate-950 rounded-lg p-4 mb-3 group">
                                    <pre class="text-sm text-emerald-400 font-mono whitespace-pre-wrap break-all"><?= e($fix['fix_code']) ?></pre>
                                    <button @click="copyToClipboard(`<?= e(addslashes($fix['fix_code'])) ?>`)"
                                            class="absolute top-2 right-2 px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                        Copia
                                    </button>
                                </div>
                                <?php endif; ?>

                                <!-- Explanation -->
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3"><?= e($fix['fix_explanation']) ?></p>

                                <!-- Meta Info -->
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <?= $fix['time_estimate_minutes'] ?> min
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <?php
                                        $difficultyColors = ['facile' => 'emerald', 'medio' => 'yellow', 'difficile' => 'red'];
                                        $diffColor = $difficultyColors[$fix['difficulty']] ?? 'slate';
                                        ?>
                                        <span class="w-2 h-2 rounded-full bg-<?= $diffColor ?>-500"></span>
                                        <?= ucfirst($fix['difficulty']) ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                        +<?= $fix['impact_points'] ?> punti
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- No Plan - Generate CTA -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
        <div class="max-w-md mx-auto">
            <div class="h-20 w-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                <svg class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Nessun Piano Generato</h2>

            <?php if ($issueStats['total_issues'] > 0): ?>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                Analizzeremo <strong><?= $issueStats['total_issues'] ?> problemi</strong> su <strong><?= $issueStats['pages_with_issues'] ?> pagine</strong>
                e genereremo fix specifici pronti da copiare, ordinati per impatto.
            </p>

            <div class="flex items-center justify-center gap-6 mb-6 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    <span class="text-slate-600 dark:text-slate-400"><?= $issueStats['critical'] ?> critici</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                    <span class="text-slate-600 dark:text-slate-400"><?= $issueStats['warning'] ?> warning</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <span class="text-slate-600 dark:text-slate-400"><?= $issueStats['notice'] ?> notice</span>
                </div>
            </div>

            <div class="bg-slate-100 dark:bg-slate-700/50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">Costo stimato:</span>
                    <span class="font-semibold text-slate-900 dark:text-white">~<?= $credits['generate_cost'] ?> crediti</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                    <span class="text-slate-600 dark:text-slate-400">I tuoi crediti:</span>
                    <span class="font-semibold <?= $credits['balance'] >= $credits['generate_cost'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>"><?= number_format($credits['balance'], 1) ?></span>
                </div>
            </div>

            <?php if ($credits['balance'] >= $credits['generate_cost']): ?>
            <button @click="generatePlan()"
                    :disabled="generating"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:from-indigo-600 hover:to-purple-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="!generating" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <svg x-show="generating" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="generating ? 'Generazione in corso...' : 'Genera Piano d\'Azione'">Genera Piano d'Azione</span>
            </button>
            <?php else: ?>
            <div class="text-center">
                <p class="text-red-600 dark:text-red-400 mb-4">Crediti insufficienti per generare il piano.</p>
                <a href="<?= url('/credits/buy') ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 text-white font-semibold rounded-xl hover:bg-emerald-600 transition-colors">
                    Acquista Crediti
                </a>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center">
                <div class="h-16 w-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">Nessun Problema Rilevato!</h3>
                <p class="text-slate-600 dark:text-slate-400">Il tuo sito non ha problemi da risolvere. Health Score: 100!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function actionPlan(config) {
    return {
        projectId: config.projectId,
        baseUrl: config.baseUrl,
        planExists: config.planExists,
        totalFixes: config.totalFixes,
        completedFixes: config.completedFixes,
        progress: config.progress,
        csrfToken: config.csrfToken,
        generating: false,

        async generatePlan() {
            this.generating = true;

            try {
                const response = await fetch(`${this.baseUrl}/seo-audit/project/${this.projectId}/action-plan/generate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _token: this.csrfToken })
                });

                const data = await response.json();

                if (data.success) {
                    // Reload page to show generated plan
                    window.location.reload();
                } else {
                    window.ainstein.alert(data.message || 'Errore durante la generazione del piano', 'error');
                    this.generating = false;
                }
            } catch (error) {
                console.error('Error:', error);
                window.ainstein.alert('Errore di connessione', 'error');
                this.generating = false;
            }
        },

        async toggleFix(fixId, currentState) {
            try {
                const response = await fetch(`${this.baseUrl}/seo-audit/project/${this.projectId}/fix/${fixId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _token: this.csrfToken })
                });

                const data = await response.json();

                if (data.success) {
                    this.completedFixes = data.plan_progress.fixes_completed;
                    this.totalFixes = data.plan_progress.total_fixes;
                    this.progress = data.plan_progress.percentage;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show toast
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-slate-800 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                toast.textContent = 'Copiato negli appunti!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            });
        }
    }
}
</script>
