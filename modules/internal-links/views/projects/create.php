<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/internal-links') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Internal Links</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Nuovo Progetto</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Crea Nuovo Progetto</h1>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <form action="<?= url('/internal-links/projects') ?>" method="POST" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome Progetto <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       placeholder="Es: Blog Aziendale"
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div>
                <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    URL Base <span class="text-red-500">*</span>
                </label>
                <input type="url" name="base_url" id="base_url" required
                       placeholder="https://www.esempio.it"
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">L'URL base del sito da analizzare</p>
            </div>

            <div>
                <label for="css_selector" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    CSS Selector (opzionale)
                </label>
                <input type="text" name="css_selector" id="css_selector"
                       placeholder="Es: article, .content, main"
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Selettori CSS per estrarre solo il contenuto principale (separati da virgola)</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="scrape_delay" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Delay tra richieste (ms)
                    </label>
                    <input type="number" name="scrape_delay" id="scrape_delay" value="1000" min="100"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Minimo 100ms per evitare blocchi</p>
                </div>

                <div>
                    <label for="user_agent" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        User Agent
                    </label>
                    <input type="text" name="user_agent" id="user_agent"
                           value="Mozilla/5.0 (compatible; SEOToolkit/1.0)"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/internal-links') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
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
