<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/seo-audit') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">SEO Audit</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Nuovo Audit</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Audit SEO</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura e avvia una nuova scansione SEO del tuo sito</p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <form action="<?= url('/seo-audit/store') ?>" method="POST" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Nome Progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome Progetto <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       placeholder="Es: Audit sito aziendale"
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- URL Sito -->
            <div>
                <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    URL Sito <span class="text-red-500">*</span>
                </label>
                <input type="url" name="base_url" id="base_url" required
                       placeholder="https://www.esempio.it"
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">L'URL completo del sito da analizzare (incluso https://)</p>
            </div>

            <!-- Crawl Mode: sempre spider (hidden) -->
            <input type="hidden" name="crawl_mode" value="spider">

            <!-- Limite Pagine -->
            <div>
                <label for="max_pages" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Limite Pagine
                </label>
                <div class="flex items-center gap-4">
                    <input type="number" name="max_pages" id="max_pages" value="500" min="10" max="5000"
                           class="block w-32 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <input type="range" id="max_pages_slider" min="10" max="2000" value="500" step="10"
                           class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer">
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Numero massimo di pagine da scansionare (10-5000)</p>
            </div>

            <!-- Stima Crediti -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-slate-900 dark:text-white">Stima Costo</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Basata sul limite pagine selezionato</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white" id="estimated_cost">
                            <?= number_format(500 * ($credits['crawl_cost'] ?? 0.2), 1) ?>
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">crediti stimati</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Crediti disponibili:</span>
                    <span class="font-medium text-slate-900 dark:text-white"><?= number_format($credits['balance'] ?? 0, 1) ?></span>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Cosa viene analizzato</h4>
                        <ul class="mt-1 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-0.5">
                            <li>Meta tags (title, description, OG)</li>
                            <li>Struttura heading (H1-H6)</li>
                            <li>Immagini e attributi alt</li>
                            <li>Link interni ed esterni</li>
                            <li>Schema markup</li>
                            <li>Indicizzabilità e canonical</li>
                            <li>Problemi di sicurezza (HTTPS)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/seo-audit') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                    Annulla
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crea Progetto
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxPagesInput = document.getElementById('max_pages');
    const maxPagesSlider = document.getElementById('max_pages_slider');
    const estimatedCost = document.getElementById('estimated_cost');
    const crawlCost = <?= $credits['crawl_cost'] ?? 0.2 ?>;

    function updateCost() {
        const pages = parseInt(maxPagesInput.value) || 500;
        const cost = (pages * crawlCost).toFixed(1);
        estimatedCost.textContent = cost;
    }

    maxPagesInput.addEventListener('input', function() {
        maxPagesSlider.value = this.value;
        updateCost();
    });

    maxPagesSlider.addEventListener('input', function() {
        maxPagesInput.value = this.value;
        updateCost();
    });
});
</script>
