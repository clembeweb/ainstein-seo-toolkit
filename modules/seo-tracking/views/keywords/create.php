<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alle keyword
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Aggiungi Keyword</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Aggiungi keyword da monitorare per <strong><?= e($project['name']) ?></strong>
        </p>
    </div>

    <!-- Manual Input Form -->
    <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/store') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="p-6 space-y-6">
            <!-- Keywords Input -->
            <div>
                <label for="keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Keyword <span class="text-red-500">*</span>
                </label>
                <textarea name="keywords" id="keywords" rows="8" required
                          class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                          placeholder="scarpe running uomo&#10;migliori scarpe da corsa&#10;scarpe nike running"></textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Una keyword per riga</p>
            </div>

            <!-- Group -->
            <div>
                <label for="group_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Gruppo
                </label>
                <div class="flex gap-2">
                    <select name="group_name_select" id="group_name_select" onchange="updateGroupInput(this)"
                            class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Nessun gruppo</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?= e($group['group_name']) ?>"><?= e($group['group_name']) ?> (<?= $group['count'] ?>)</option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Nuovo gruppo...</option>
                    </select>
                    <input type="text" name="group_name" id="group_name"
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 hidden"
                           placeholder="Nome nuovo gruppo">
                </div>
            </div>

            <!-- Track -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_tracked" value="1" checked
                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                <div>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">Traccia posizioni</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Monitora giornalmente le variazioni di posizione</span>
                </div>
            </label>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-between">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords') ?>" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Aggiungi Keyword
            </button>
        </div>
    </form>

    <!-- CSV Import -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-medium text-slate-900 dark:text-white">Importa da CSV</h2>
        </div>
        <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/import') ?>" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="file" name="csv_file" accept=".csv" required
                           class="w-full text-sm text-slate-500 dark:text-slate-400
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0
                                  file:text-sm file:font-medium
                                  file:bg-primary-50 file:text-primary-700
                                  dark:file:bg-primary-900/50 dark:file:text-primary-300
                                  hover:file:bg-primary-100 dark:hover:file:bg-primary-900/70">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Importa
                </button>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Formato CSV: keyword, gruppo (opzionale), target_url (opzionale)
            </p>
        </form>
    </div>

    <!-- Tips -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Suggerimenti</h4>
                <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Le keyword vengono anche scoperte automaticamente durante la sincronizzazione GSC</li>
                    <li>• Usa i gruppi per organizzare keyword per categoria o intento</li>
                    <li>• Le keyword tracciate vengono monitorate con storico posizioni giornaliero</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function updateGroupInput(select) {
    const textInput = document.getElementById('group_name');
    if (select.value === '__new__') {
        textInput.classList.remove('hidden');
        textInput.focus();
    } else {
        textInput.classList.add('hidden');
        textInput.value = select.value;
    }
}
</script>
