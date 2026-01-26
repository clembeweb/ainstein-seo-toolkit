<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $keyword['id']) ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alla keyword
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Modifica Keyword</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= e($keyword['keyword']) ?></p>
    </div>

    <!-- Form -->
    <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $keyword['id'] . '/update') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="_method" value="PUT">

        <div class="p-6 space-y-6">
            <!-- Keyword (readonly) -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Keyword</label>
                <input type="text" value="<?= e($keyword['keyword']) ?>" readonly
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400">
            </div>

            <!-- Group -->
            <div>
                <label for="group_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Gruppo</label>
                <div class="flex gap-2">
                    <select name="group_name_select" id="group_name_select" onchange="updateGroupInput(this)"
                            class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Nessun gruppo</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?= e($group['group_name']) ?>" <?= $keyword['group_name'] === $group['group_name'] ? 'selected' : '' ?>>
                            <?= e($group['group_name']) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Nuovo gruppo...</option>
                    </select>
                    <input type="text" name="group_name" id="group_name" value="<?= e($keyword['group_name'] ?? '') ?>"
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 <?= $keyword['group_name'] && !in_array($keyword['group_name'], array_column($groups, 'group_name')) ? '' : 'hidden' ?>"
                           placeholder="Nome nuovo gruppo">
                </div>
            </div>

            <!-- Target Position -->
            <div>
                <label for="target_position" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Posizione Target
                </label>
                <input type="number" name="target_position" id="target_position" min="1" max="100"
                       value="<?= e($keyword['target_position'] ?? '') ?>"
                       class="w-32 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="es. 3">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Obiettivo di posizione per questa keyword</p>
            </div>

            <!-- Target URL -->
            <div>
                <label for="target_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    URL Target
                </label>
                <input type="url" name="target_url" id="target_url" value="<?= e($keyword['target_url'] ?? '') ?>"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="https://example.com/pagina">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">La pagina che dovrebbe rankare per questa keyword</p>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Note</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                          placeholder="Note interne su questa keyword..."><?= e($keyword['notes'] ?? '') ?></textarea>
            </div>

            <!-- Tracking -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_tracked" value="1" <?= $keyword['is_tracked'] ? 'checked' : '' ?>
                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                <div>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">Traccia posizioni</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Monitora giornalmente le variazioni di posizione</span>
                </div>
            </label>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-between">
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $keyword['id']) ?>" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Salva Modifiche
            </button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-900/50 overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-medium text-red-600 dark:text-red-400 mb-2">Zona pericolosa</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Eliminando la keyword verranno cancellati anche tutti i dati storici di posizione associati.
            </p>
            <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $keyword['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questa keyword?');">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm">
                    Elimina Keyword
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function updateGroupInput(select) {
    const textInput = document.getElementById('group_name');
    if (select.value === '__new__') {
        textInput.classList.remove('hidden');
        textInput.value = '';
        textInput.focus();
    } else {
        textInput.classList.add('hidden');
        textInput.value = select.value;
    }
}
</script>
