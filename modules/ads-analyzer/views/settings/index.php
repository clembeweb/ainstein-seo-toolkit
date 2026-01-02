<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/ads-analyzer') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alla dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura le preferenze del modulo Google Ads Analyzer</p>
    </div>

    <!-- Settings Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <form action="<?= url('/ads-analyzer/settings') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="space-y-6">
                <!-- Max Terms -->
                <div>
                    <label for="max_terms_per_analysis" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Termini max per analisi AI
                    </label>
                    <input
                        type="number"
                        id="max_terms_per_analysis"
                        name="max_terms_per_analysis"
                        min="50"
                        max="500"
                        value="<?= e($settings['max_terms_per_analysis'] ?? 300) ?>"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Limite termini inviati all'AI per singola analisi (50-500). Maggiore = piu preciso ma piu costoso.
                    </p>
                </div>

                <!-- Auto-select High Priority -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            type="checkbox"
                            id="auto_select_high_priority"
                            name="auto_select_high_priority"
                            value="1"
                            <?= ($settings['auto_select_high_priority'] ?? true) ? 'checked' : '' ?>
                            class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                        >
                    </div>
                    <div class="ml-3">
                        <label for="auto_select_high_priority" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Auto-seleziona keyword priorita Alta
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Seleziona automaticamente le keyword con priorita 'high' per l'export
                        </p>
                    </div>
                </div>

                <!-- Auto-select Medium Priority -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            type="checkbox"
                            id="auto_select_medium_priority"
                            name="auto_select_medium_priority"
                            value="1"
                            <?= ($settings['auto_select_medium_priority'] ?? true) ? 'checked' : '' ?>
                            class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                        >
                    </div>
                    <div class="ml-3">
                        <label for="auto_select_medium_priority" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Auto-seleziona keyword priorita Media
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Seleziona automaticamente le keyword con priorita 'medium' per l'export
                        </p>
                    </div>
                </div>

                <!-- Default Match Type -->
                <div>
                    <label for="default_match_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Match type predefinito export
                    </label>
                    <select
                        id="default_match_type"
                        name="default_match_type"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                        <option value="exact" <?= ($settings['default_match_type'] ?? 'phrase') === 'exact' ? 'selected' : '' ?>>
                            Corrispondenza esatta [keyword]
                        </option>
                        <option value="phrase" <?= ($settings['default_match_type'] ?? 'phrase') === 'phrase' ? 'selected' : '' ?>>
                            Corrispondenza a frase "keyword"
                        </option>
                        <option value="broad" <?= ($settings['default_match_type'] ?? 'phrase') === 'broad' ? 'selected' : '' ?>>
                            Corrispondenza generica
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Match type usato nell'export per Google Ads Editor
                    </p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="<?= url('/ads-analyzer') ?>" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        Annulla
                    </a>
                    <button type="submit" class="px-6 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors">
                        Salva Impostazioni
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Saved Contexts -->
    <?php if (!empty($savedContexts)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Contesti Business Salvati</h2>
        <div class="space-y-3">
            <?php foreach ($savedContexts as $ctx): ?>
            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-slate-900 dark:text-white truncate"><?= e($ctx['name']) ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e(substr($ctx['context'], 0, 100)) ?>...</p>
                </div>
                <form action="<?= url('/ads-analyzer/contexts/' . $ctx['id'] . '/delete') ?>" method="POST" class="ml-3">
                    <?= csrf_field() ?>
                    <button type="submit" onclick="return confirm('Eliminare questo contesto?')" class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
