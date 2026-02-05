<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="<?= url('/seo-onpage') ?>" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Configura un nuovo sito da analizzare</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <form method="POST" action="<?= url('/seo-onpage/projects') ?>" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Nome Progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Nome Progetto *
                </label>
                <input type="text" name="name" id="name" required
                       value="<?= e($_POST['name'] ?? '') ?>"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       placeholder="Es: Blog Aziendale">
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Un nome descrittivo per identificare il progetto</p>
            </div>

            <!-- Dominio -->
            <div>
                <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Dominio *
                </label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 text-sm">
                        https://
                    </span>
                    <input type="text" name="domain" id="domain" required
                           value="<?= e($_POST['domain'] ?? '') ?>"
                           class="flex-1 px-4 py-2 rounded-r-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                           placeholder="esempio.com">
                </div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Il dominio del sito da analizzare (senza www)</p>
            </div>

            <!-- Device Default -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Device Predefinito
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="default_device" value="desktop" checked
                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Desktop</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="default_device" value="mobile"
                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Mobile</span>
                    </label>
                </div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Device usato per l'analisi delle pagine</p>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/seo-onpage') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                    Annulla
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crea Progetto
                </button>
            </div>
        </form>
    </div>
</div>
