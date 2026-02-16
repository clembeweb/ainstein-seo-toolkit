<!-- Orphan Pages Report -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('Pagine Orfane') ?></h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                <?= __('Pagine senza link interni in entrata') ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url("/internal-links/project/{$project['id']}/export?type=orphans") ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <?= __('Esporta CSV') ?>
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4l16 16"/></svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400"><?= number_format($orphanCount ?? 0) ?></p>
                    <p class="text-sm text-slate-500"><?= __('Pagine Orfane') ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($totalUrls ?? 0) ?></p>
                    <p class="text-sm text-slate-500"><?= __('Pagine Totali') ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <?php $orphanPercent = ($totalUrls ?? 0) > 0 ? round((($orphanCount ?? 0) / $totalUrls) * 100, 1) : 0; ?>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400"><?= $orphanPercent ?>%</p>
                    <p class="text-sm text-slate-500"><?= __('Tasso Orfani') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Banner -->
    <?php if (($orphanCount ?? 0) > 0): ?>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-blue-800 dark:text-blue-200"><?= __('Cosa sono le Pagine Orfane?') ?></h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    <?= __('Le pagine orfane non hanno link interni che puntano ad esse. Potrebbero essere difficili da scoprire per i motori di ricerca.') ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search and Actions Bar -->
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <!-- Search -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text"
                   id="orphanSearch"
                   placeholder="<?= __('Cerca URL o keyword') ?>..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition text-sm">
        </div>

        <!-- Bulk Actions -->
        <div class="flex items-center gap-2">
            <select id="bulkAction" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm text-slate-700 dark:text-slate-300">
                <option value=""><?= __('Bulk Actions') ?></option>
                <option value="delete"><?= __('Delete Selected') ?></option>
            </select>
            <button onclick="executeBulkAction()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <?= __('Apply') ?>
            </button>
        </div>
    </div>

    <!-- Orphan Pages Table -->
    <?php if (empty($orphanPages)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('Nessuna Pagina Orfana') ?></h3>
        <p class="text-slate-500 dark:text-slate-400">
            <?= __('Tutte le tue pagine hanno almeno un link interno in entrata.') ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full data-table" data-sortable>
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="w-12 text-center px-4 py-3">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="w-12 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">#</th>
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('URL') ?></th>
                        <th class="sortable w-40 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Keyword Target') ?></th>
                        <th class="sortable w-32 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Link in Uscita') ?></th>
                        <th class="w-32 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Stato') ?></th>
                        <th class="w-20 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php
                    $startIndex = isset($pagination) ? $pagination['from'] : 1;
                    foreach ($orphanPages as $index => $page):
                    ?>
                    <tr class="orphan-row hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" data-url-id="<?= $page['id'] ?>">
                        <td class="text-center px-4 py-3">
                            <input type="checkbox" name="url_ids[]" value="<?= $page['id'] ?>" class="orphan-checkbox rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3 text-slate-400 text-sm"><?= $startIndex + $index ?></td>
                        <td class="px-4 py-3 max-w-[400px]">
                            <a href="<?= e($page['url']) ?>" target="_blank"
                               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 truncate block"
                               title="<?= e($page['url']) ?>">
                                <?= e(mb_substr($page['url'], 0, 60)) ?><?= mb_strlen($page['url']) > 60 ? '...' : '' ?>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($page['keyword'])): ?>
                            <span class="inline-flex items-center px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-lg">
                                <?= e(mb_substr($page['keyword'], 0, 30)) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (($page['outgoing_count'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center justify-center px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm rounded-lg">
                                <?= number_format($page['outgoing_count']) ?>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center justify-center px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm rounded-lg">
                                0 (<?= __('Isola') ?>)
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (($page['outgoing_count'] ?? 0) == 0): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= __('Pagina Isola') ?>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4l16 16"/></svg>
                                <?= __('Orfano') ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="<?= e($page['url']) ?>" target="_blank"
                                   class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition"
                                   title="<?= __('Apri URL') ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <form action="<?= url("/internal-links/project/{$project['id']}/urls/delete/{$page['id']}") ?>" method="POST" class="inline"
                                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo URL?', {destructive: true}).then(() => $el.submit())">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition"
                                            title="<?= __('Elimina') ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (isset($pagination) && $pagination['total'] > $pagination['per_page']): ?>
        <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                <?= __('Mostrando') ?> <?= $pagination['from'] ?> <?= __('a') ?> <?= $pagination['to'] ?> <?= __('di') ?> <?= number_format($pagination['total']) ?>
            </p>
            <div class="flex gap-2">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="<?= url("/internal-links/project/{$project['id']}/reports/orphans?page=" . ($pagination['current_page'] - 1)) ?>"
                   class="px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                    <?= __('Precedente') ?>
                </a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="<?= url("/internal-links/project/{$project['id']}/reports/orphans?page=" . ($pagination['current_page'] + 1)) ?>"
                   class="px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                    <?= __('Successivo') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tips Section -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <?= __('Come Correggere le Pagine Orfane') ?>
        </h3>
        <div class="grid md:grid-cols-2 gap-4 text-sm text-slate-600 dark:text-slate-400">
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-600">1</span>
                <p><?= __('Aggiungi link contestuali da post del blog o articoli correlati') ?></p>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-600">2</span>
                <p><?= __('Includi la pagina nelle categorie o tag pertinenti') ?></p>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-600">3</span>
                <p><?= __('Aggiungi ai menu di navigazione o link nel footer se appropriato') ?></p>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary-600">4</span>
                <p><?= __('Valuta se la pagina debba essere consolidata o reindirizzata') ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.orphan-checkbox').forEach(cb => cb.checked = this.checked);
});

// Search functionality
const searchInput = document.getElementById('orphanSearch');
searchInput?.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.orphan-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.orphan-checkbox:checked')).map(cb => cb.value);

    if (!action) {
        window.ainstein.alert('Seleziona un\'azione', 'warning');
        return;
    }

    if (selected.length === 0) {
        window.ainstein.alert('Seleziona almeno un URL', 'warning');
        return;
    }

    const doAction = () => {
        fetch('<?= url("/internal-links/project/{$project['id']}/urls/bulk") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ action: action, url_ids: selected })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                window.ainstein.alert(data.error || 'Errore', 'error');
            }
        })
        .catch(error => {
            window.ainstein.alert('Errore durante l\'esecuzione', 'error');
        });
    };

    if (action === 'delete') {
        window.ainstein.confirm(`Sei sicuro di voler eliminare ${selected.length} URL?`, {destructive: true}).then(() => doAction());
    } else {
        doAction();
    }
}

</script>
