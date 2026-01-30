<?php
/**
 * Step 3: Riscrittura AI
 */
$currentPage = 'optimize';
$analysis = $optimization['analysis_data'] ?? [];
?>

<div class="space-y-6" x-data="refactorApp()">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center gap-2">
        <?php
        $steps = [
            ['num' => 1, 'label' => 'Import', 'done' => true],
            ['num' => 2, 'label' => 'Analisi', 'done' => true],
            ['num' => 3, 'label' => 'Riscrittura', 'active' => true],
            ['num' => 4, 'label' => 'Export', 'active' => false],
        ];
        foreach ($steps as $i => $step):
        ?>
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium <?= !empty($step['done']) ? 'bg-emerald-500 text-white' : (!empty($step['active']) ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500') ?>">
                <?php if (!empty($step['done'])): ?>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?php else: ?><?= $step['num'] ?><?php endif; ?>
            </div>
            <span class="ml-2 text-sm <?= !empty($step['active']) ? 'text-slate-900 dark:text-white font-medium' : 'text-slate-500' ?>"><?= $step['label'] ?></span>
            <?php if ($i < count($steps) - 1): ?>
            <svg class="w-5 h-5 mx-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Score & Summary -->
    <?php if (!empty($analysis['summary'])): ?>
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-5">
        <div class="flex items-center gap-6">
            <div class="text-center">
                <div class="text-4xl font-bold <?= ($optimization['seo_score'] ?? 0) >= 70 ? 'text-green-600' : (($optimization['seo_score'] ?? 0) >= 40 ? 'text-primary-600' : 'text-red-600') ?>">
                    <?= $optimization['seo_score'] ?? 0 ?>/100
                </div>
                <p class="text-xs text-slate-500 mt-1">Score SEO</p>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Problemi Identificati</h3>
                <ul class="space-y-1">
                    <?php foreach ($analysis['summary']['main_issues'] ?? [] as $issue): ?>
                    <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="w-4 h-4 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <?= e($issue) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($optimization['status'] === 'refactored' && !empty($optimization['optimized_content'])): ?>
    <!-- Già riscritto -->
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <span class="text-emerald-700 dark:text-emerald-300 font-medium">Riscrittura completata!</span>
            <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize/' . $optimization['id'] . '/export') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                Vai all'Export
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
    <?php else: ?>

    <!-- Opzioni Riscrittura -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Opzioni Riscrittura</h3>

        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Target Parole</label>
                <input type="number" x-model="targetWordCount"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                       placeholder="<?= $analysis['content']['recommended_word_count'] ?? 1500 ?>">
                <p class="text-xs text-slate-500 mt-1">Consigliato: <?= $analysis['content']['recommended_word_count'] ?? 1500 ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tono</label>
                <select x-model="tone" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="professionale">Professionale</option>
                    <option value="informale">Informale</option>
                    <option value="tecnico">Tecnico</option>
                    <option value="divulgativo">Divulgativo</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 mb-6">
            <input type="checkbox" id="keepStructure" x-model="keepStructure" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
            <label for="keepStructure" class="text-sm text-slate-700 dark:text-slate-300">Mantieni struttura originale (espandi ma non riorganizzare)</label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
            <div class="text-sm text-slate-500">
                Costo: <span class="font-semibold text-primary-600"><?= $creditCost ?></span> crediti
                (hai <?= $userCredits ?>)
            </div>
            <button @click="runRefactor()" :disabled="refactoring || <?= $userCredits < $creditCost ? 'true' : 'false' ?>"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span x-text="refactoring ? 'Riscrittura in corso...' : 'Genera Articolo Ottimizzato'"></span>
            </button>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="refactoring" class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mx-auto"></div>
        <p class="mt-4 text-slate-600 dark:text-slate-300">L'AI sta riscrivendo l'articolo...</p>
        <p class="text-sm text-slate-500 mt-1">Può richiedere 1-2 minuti</p>
    </div>

    <!-- Error -->
    <div x-show="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
    </div>

    <?php endif; ?>
</div>

<script>
function refactorApp() {
    return {
        refactoring: false,
        error: null,
        targetWordCount: <?= $analysis['content']['recommended_word_count'] ?? 1500 ?>,
        tone: 'professionale',
        keepStructure: false,

        async runRefactor() {
            this.refactoring = true;
            this.error = null;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= csrf_token() ?>');
                formData.append('target_word_count', this.targetWordCount);
                formData.append('tone', this.tone);
                formData.append('keep_structure', this.keepStructure ? '1' : '0');

                const response = await fetch('<?= url('/ai-optimizer/project/' . $projectId . '/optimize/' . $optimization['id'] . '/refactor') ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.error) {
                    this.error = data.message;
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (err) {
                this.error = 'Errore: ' + err.message;
            } finally {
                this.refactoring = false;
            }
        }
    };
}
</script>
