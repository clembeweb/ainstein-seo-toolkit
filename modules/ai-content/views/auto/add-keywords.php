<div class="max-w-2xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    AI Content
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto') ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <?= e($project['name']) ?>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Aggiungi Keyword</span>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Aggiungi Keyword</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Inserisci le keyword da aggiungere alla coda di generazione automatica
        </p>
    </div>

    <!-- Form -->
    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/add') ?>" method="POST"
          class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700"
          x-data="keywordsForm()"
          @submit="submitting = true">
        <?= csrf_field() ?>

        <div class="p-6 space-y-6">
            <!-- Textarea Keywords -->
            <div>
                <label for="keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Keywords <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="keywords"
                    name="keywords"
                    rows="10"
                    required
                    x-model="keywordsText"
                    @input="updateCount()"
                    placeholder="Inserisci una keyword per riga, ad esempio:&#10;&#10;come fare SEO&#10;migliori strategie marketing&#10;guida email marketing 2024&#10;tool analisi competitor"
                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                ></textarea>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Una keyword per riga. Le righe vuote e i duplicati verranno ignorati.
                </p>
            </div>

            <!-- Preview Counter -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                            <span x-text="keywordCount">0</span> keyword da aggiungere
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Le keyword verranno aggiunte alla coda. Potrai impostare data/ora dalla vista Coda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium mb-1">Come funziona</p>
                        <ul class="text-blue-600 dark:text-blue-400 space-y-1 list-disc list-inside">
                            <li>Le keyword vengono aggiunte alla coda di elaborazione</li>
                            <li>Ogni keyword viene schedulata negli slot disponibili</li>
                            <li>Il sistema estrae automaticamente la SERP, genera il brief e crea l'articolo</li>
                            <li>Puoi monitorare lo stato dalla dashboard del progetto</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-end gap-3">
            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                Annulla
            </a>
            <button type="submit"
                    :disabled="keywordCount === 0 || submitting"
                    :class="(keywordCount === 0 || submitting) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-700'"
                    class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium transition-colors">
                <span class="flex items-center">
                    <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <svg x-show="submitting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? 'Aggiunta in corso...' : 'Aggiungi alla Coda'"></span>
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function keywordsForm() {
    return {
        keywordsText: '',
        keywordCount: 0,
        submitting: false,

        updateCount() {
            const lines = this.keywordsText.split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);
            const unique = [...new Set(lines)];
            this.keywordCount = unique.length;
        }
    }
}
</script>
