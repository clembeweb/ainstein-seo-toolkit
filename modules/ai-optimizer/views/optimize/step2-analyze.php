<?php
/**
 * Step 2: Analisi Gap
 */
$currentPage = 'optimize';
?>

<div class="space-y-6" x-data="analyzeApp()">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center gap-2">
        <?php
        $steps = [
            ['num' => 1, 'label' => 'Import', 'active' => false, 'done' => true],
            ['num' => 2, 'label' => 'Analisi', 'active' => true],
            ['num' => 3, 'label' => 'Riscrittura', 'active' => false],
            ['num' => 4, 'label' => 'Export', 'active' => false],
        ];
        foreach ($steps as $i => $step):
        ?>
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium <?= !empty($step['done']) ? 'bg-emerald-500 text-white' : ($step['active'] ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400') ?>">
                <?php if (!empty($step['done'])): ?>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?php else: ?>
                <?= $step['num'] ?>
                <?php endif; ?>
            </div>
            <span class="ml-2 text-sm <?= $step['active'] ? 'text-slate-900 dark:text-white font-medium' : 'text-slate-500 dark:text-slate-400' ?>"><?= $step['label'] ?></span>
            <?php if ($i < count($steps) - 1): ?>
            <svg class="w-5 h-5 mx-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Articolo Info -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 p-3 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                <svg class="w-6 h-6 text-primary-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white"><?= e($optimization['keyword']) ?></h3>
                <a href="<?= e($optimization['original_url']) ?>" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate block"><?= e($optimization['original_url']) ?></a>
                <div class="flex items-center gap-4 mt-2 text-sm text-slate-500 dark:text-slate-400">
                    <span>Title: <?= e(mb_substr($optimization['original_title'] ?? 'N/A', 0, 50)) ?>...</span>
                    <span><?= number_format($optimization['original_word_count']) ?> parole</span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-2xl font-bold text-primary-600 dark:text-amber-400"><?= $creditCost ?></span>
                <p class="text-xs text-slate-500 dark:text-slate-400">crediti</p>
            </div>
        </div>
    </div>

    <?php if (!$isConfigured): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <p class="text-sm text-red-700 dark:text-red-300">API non configurate. Contatta l'amministratore.</p>
    </div>
    <?php elseif ($userCredits < $creditCost): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <p class="text-sm text-red-700 dark:text-red-300">Crediti insufficienti. Hai <?= $userCredits ?> crediti, ne servono <?= $creditCost ?>.</p>
    </div>
    <?php else: ?>

    <!-- Analisi già completata? -->
    <?php if ($optimization['status'] === 'analyzed' && !empty($optimization['analysis_data'])): ?>
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-emerald-700 dark:text-emerald-300 font-medium">Analisi completata - Score: <?= $optimization['seo_score'] ?>/100</span>
            </div>
            <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize/' . $optimization['id'] . '/refactor') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                Procedi alla Riscrittura
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bottone Analisi -->
    <div class="text-center" x-show="!analyzing && !analysisData">
        <button @click="runAnalysis()"
                class="inline-flex items-center gap-2 px-6 py-3 text-base font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            Avvia Analisi Gap vs Competitor
        </button>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">L'AI analizzerà il tuo articolo vs i top 3-4 risultati Google</p>
    </div>

    <!-- Loading -->
    <div x-show="analyzing" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mx-auto"></div>
        <p class="mt-4 text-slate-600 dark:text-slate-300 font-medium">Analisi in corso...</p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Scraping competitor e analisi AI (può richiedere 30-60 secondi)</p>
    </div>

    <!-- Error -->
    <div x-show="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
    </div>

    <?php endif; ?>
</div>

<script>
function analyzeApp() {
    return {
        analyzing: false,
        analysisData: null,
        error: null,

        async runAnalysis() {
            this.analyzing = true;
            this.error = null;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= csrf_token() ?>');

                const response = await fetch('<?= url('/ai-optimizer/project/' . $projectId . '/optimize/' . $optimization['id'] . '/analyze') ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.error) {
                    this.error = data.message;
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.analysisData = data.data;
                }
            } catch (err) {
                this.error = 'Errore durante l\'analisi: ' + err.message;
            } finally {
                this.analyzing = false;
            }
        }
    };
}
</script>
