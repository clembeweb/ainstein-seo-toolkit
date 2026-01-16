<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id']) ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Modifica Gruppo</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($group['name']) ?></p>
        </div>
    </div>

    <!-- Form -->
    <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id'] . '/update') ?>" method="POST" class="space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
            <!-- Nome -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome gruppo <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       value="<?= e($group['name']) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="es. Brand Keywords, Transazionali, Informative...">
            </div>

            <!-- Descrizione -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Descrizione
                </label>
                <textarea name="description" id="description" rows="2"
                          class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                          placeholder="Descrizione opzionale del gruppo..."><?= e($group['description'] ?? '') ?></textarea>
            </div>

            <!-- Colore -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Colore
                </label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($colors as $color): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="color" value="<?= $color ?>" <?= ($group['color'] ?? '#006e96') === $color ? 'checked' : '' ?> class="sr-only peer">
                        <span class="block w-8 h-8 rounded-full border-2 border-transparent peer-checked:border-slate-900 dark:peer-checked:border-white peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-offset-white dark:peer-checked:ring-offset-slate-800 peer-checked:ring-primary-500 transition-all"
                              style="background-color: <?= $color ?>"></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Keyword Selection -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Keyword nel Gruppo</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Seleziona le keyword da includere in questo gruppo</p>
            </div>

            <?php if (empty($keywords)): ?>
            <div class="p-8 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Nessuna keyword disponibile. Sincronizza GSC o aggiungi keyword manualmente.
                </p>
            </div>
            <?php else: ?>
            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <input type="text" id="keywordSearch" placeholder="Cerca keyword..."
                           class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <span class="text-sm text-slate-500 dark:text-slate-400" id="selectedKeywordsCount"><?= count($groupKeywordIds) ?> selezionate</span>
                </div>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 sticky top-0">
                        <tr>
                            <th class="px-6 py-2 text-left">
                                <input type="checkbox" id="selectAllKeywords" class="rounded border-slate-300 dark:border-slate-600" <?= count($groupKeywordIds) === count($keywords) ? 'checked' : '' ?>>
                            </th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-6 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                            <th class="px-6 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700" id="keywordsTableBody">
                        <?php foreach ($keywords as $kw): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 keyword-row" data-keyword="<?= e(strtolower($kw['keyword'])) ?>">
                            <td class="px-6 py-2">
                                <input type="checkbox" name="keyword_ids[]" value="<?= $kw['id'] ?>"
                                       <?= in_array($kw['id'], $groupKeywordIds) ? 'checked' : '' ?>
                                       class="rounded border-slate-300 dark:border-slate-600 keyword-checkbox">
                            </td>
                            <td class="px-6 py-2">
                                <span class="text-sm text-slate-900 dark:text-white"><?= e($kw['keyword']) ?></span>
                                <?php if (in_array($kw['id'], $groupKeywordIds)): ?>
                                <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">nel gruppo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-2 text-right">
                                <?php
                                $pos = $kw['last_position'] ?? 0;
                                $posClass = $pos <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                           ($pos <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                           ($pos <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                                ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>">
                                    <?= $pos > 0 ? number_format($pos, 1) : '-' ?>
                                </span>
                            </td>
                            <td class="px-6 py-2 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?= number_format($kw['last_clicks'] ?? 0) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/groups/' . $group['id']) ?>" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Salva Modifiche
            </button>
        </div>
    </form>
</div>

<script>
// Search keywords
document.getElementById('keywordSearch')?.addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.keyword-row').forEach(row => {
        const keyword = row.dataset.keyword;
        row.style.display = keyword.includes(search) ? '' : 'none';
    });
});

// Select all visible
document.getElementById('selectAllKeywords')?.addEventListener('change', function() {
    document.querySelectorAll('.keyword-row:not([style*="display: none"]) .keyword-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
    updateSelectedCount();
});

// Update count
document.querySelectorAll('.keyword-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.keyword-checkbox:checked').length;
    const el = document.getElementById('selectedKeywordsCount');
    if (el) el.textContent = count + ' selezionate';
}
</script>
