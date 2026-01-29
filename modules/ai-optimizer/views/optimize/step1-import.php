<?php
/**
 * Step 1: Import articolo
 */
$currentPage = 'optimize';
?>

<div class="space-y-6">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center gap-2">
        <?php
        $steps = [
            ['num' => 1, 'label' => 'Import', 'active' => true],
            ['num' => 2, 'label' => 'Analisi', 'active' => false],
            ['num' => 3, 'label' => 'Riscrittura', 'active' => false],
            ['num' => 4, 'label' => 'Export', 'active' => false],
        ];
        foreach ($steps as $i => $step):
        ?>
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium <?= $step['active'] ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' ?>">
                <?= $step['num'] ?>
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

    <?php if (!$isConfigured): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">
                API AI o SERP non configurate. Contatta l'amministratore.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Import -->
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">Importa Articolo</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Inserisci l'URL dell'articolo che vuoi ottimizzare e la keyword target.
            </p>

            <form action="<?= url('/ai-optimizer/project/' . $projectId . '/optimize') ?>" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div>
                    <label for="url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        URL Articolo <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="url" name="url" required
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                           placeholder="https://tuosito.it/articolo-da-ottimizzare">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">L'articolo verrà scaricato e analizzato</p>
                </div>

                <div>
                    <label for="keyword" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Keyword Target <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="keyword" name="keyword" required
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                           placeholder="Es: come fare SEO, guida marketing digitale...">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">La keyword per cui vuoi posizionare l'articolo</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="<?= url('/ai-optimizer/project/' . $projectId) ?>"
                       class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        Annulla
                    </a>
                    <button type="submit" <?= !$isConfigured ? 'disabled' : '' ?>
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importa e Continua
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
