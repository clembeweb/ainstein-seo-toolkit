<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/dashboard') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?> - Keywords</h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= number_format($stats['total'] ?? 0) ?> keyword totali, <?= number_format($stats['tracked'] ?? 0) ?> tracciate
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/add') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Keyword
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="<?= e($filters['search']) ?>" placeholder="Cerca keyword..."
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <select name="tracked" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutte</option>
                <option value="1" <?= $filters['is_tracked'] === '1' ? 'selected' : '' ?>>Solo tracciate</option>
                <option value="0" <?= $filters['is_tracked'] === '0' ? 'selected' : '' ?>>Non tracciate</option>
            </select>
            <select name="position" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutte le posizioni</option>
                <option value="3" <?= $filters['position_max'] === '3' ? 'selected' : '' ?>>Top 3</option>
                <option value="10" <?= $filters['position_max'] === '10' ? 'selected' : '' ?>>Top 10</option>
                <option value="20" <?= $filters['position_max'] === '20' ? 'selected' : '' ?>>Top 20</option>
                <option value="50" <?= $filters['position_max'] === '50' ? 'selected' : '' ?>>Top 50</option>
            </select>
            <select name="group" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                <option value="">Tutti i gruppi</option>
                <?php foreach ($groups as $group): ?>
                <option value="<?= e($group['group_name']) ?>" <?= $filters['group_name'] === $group['group_name'] ? 'selected' : '' ?>>
                    <?= e($group['group_name']) ?> (<?= $group['count'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-sm">
                Filtra
            </button>
            <?php if ($filters['search'] || $filters['is_tracked'] !== null || $filters['group_name'] || $filters['position_max']): ?>
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords') ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                Cancella filtri
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Bulk Actions -->
    <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/bulk') ?>" method="POST" id="bulkForm">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Bulk Actions Bar -->
            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 flex items-center gap-4">
                <span class="text-sm text-slate-500 dark:text-slate-400" id="selectedCount">0 selezionate</span>
                <select name="action" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="">Azione...</option>
                    <option value="track">Aggiungi al tracking</option>
                    <option value="untrack">Rimuovi dal tracking</option>
                    <option value="group">Sposta in gruppo</option>
                    <option value="delete">Elimina</option>
                </select>
                <input type="text" name="group_name" placeholder="Nome gruppo" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm hidden" id="bulkGroupInput">
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                    Applica
                </button>
            </div>

            <!-- Table -->
            <?php if (empty($keywords)): ?>
            <div class="p-12 text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna keyword trovata</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    <?php if ($filters['search'] || $filters['is_tracked'] !== null || $filters['group_name'] || $filters['position_max']): ?>
                    Prova a modificare i filtri di ricerca
                    <?php else: ?>
                    Sincronizza GSC per scoprire keyword o aggiungile manualmente
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600" onclick="toggleAll(this)">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Click</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Impr.</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tracciata</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($keywords as $kw): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="keyword_ids[]" value="<?= $kw['id'] ?>" class="rounded border-slate-300 dark:border-slate-600 keyword-checkbox" onchange="updateSelectedCount()">
                            </td>
                            <td class="px-6 py-4">
                                <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="block">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"><?= e($kw['keyword']) ?></p>
                                    <?php if ($kw['group_name']): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($kw['group_name']) ?></span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right">
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
                            <td class="px-6 py-4 text-right text-sm text-slate-900 dark:text-white"><?= number_format($kw['last_clicks'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-right text-sm text-slate-500 dark:text-slate-400"><?= number_format($kw['last_impressions'] ?? 0) ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($kw['is_tracked']): ?>
                                <svg class="w-5 h-5 text-amber-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php else: ?>
                                <span class="text-slate-300 dark:text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700" title="Dettaglio">
                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/keywords/' . $kw['id'] . '/edit') ?>" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700" title="Modifica">
                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function toggleAll(checkbox) {
    document.querySelectorAll('.keyword-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.keyword-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count + ' selezionate';
}

// Show group input when group action selected
document.querySelector('select[name="action"]').addEventListener('change', function() {
    const groupInput = document.getElementById('bulkGroupInput');
    groupInput.classList.toggle('hidden', this.value !== 'group');
});

// Confirm delete action
document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const action = this.querySelector('select[name="action"]').value;
    const checked = document.querySelectorAll('.keyword-checkbox:checked').length;

    if (checked === 0) {
        e.preventDefault();
        alert('Seleziona almeno una keyword');
        return;
    }

    if (action === 'delete' && !confirm('Sei sicuro di voler eliminare ' + checked + ' keyword?')) {
        e.preventDefault();
    }
});
</script>
