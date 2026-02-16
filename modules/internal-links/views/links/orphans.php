<!-- Orphan Pages List -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('Pagine Orfane') ?></h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                <?= __('Pagine senza link in entrata per') ?> <?= e($project['name']) ?>
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

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format(count($orphanPages)) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Pagine Orfane') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $project['total_urls'] > 0 ? number_format((count($orphanPages) / $project['total_urls']) * 100, 1) : 0 ?>%</p>
            <p class="text-xs text-slate-500 mt-1"><?= __('% del Totale') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_urls']) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('URL Totali') ?></p>
        </div>
    </div>

    <!-- Alert -->
    <?php if (!empty($orphanPages)): ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-amber-800 dark:text-amber-200"><?= __('Attenzione') ?></h3>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    <?= __('Le pagine orfane non ricevono link juice e potrebbero avere difficolta a posizionarsi.') ?>
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
                   placeholder="<?= __('Cerca URL') ?>..."
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
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('Ottimo!') ?></h3>
        <p class="text-slate-500 dark:text-slate-400">
            <?= __('Non ci sono pagine orfane nel progetto.') ?>
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
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('URL') ?></th>
                        <th class="sortable w-40 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Keyword') ?></th>
                        <th class="sortable w-32 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Link in Uscita') ?></th>
                        <th class="w-32 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Ultimo Scrape') ?></th>
                        <th class="w-20 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($orphanPages as $page): ?>
                    <tr class="orphan-row hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" data-url-id="<?= $page['id'] ?>">
                        <td class="text-center px-4 py-3">
                            <input type="checkbox" name="url_ids[]" value="<?= $page['id'] ?>" class="orphan-checkbox rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-md">
                                <a href="<?= e($page['url']) ?>" target="_blank"
                                   class="text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition truncate block font-medium text-sm">
                                    <?= e(strlen($page['url']) > 60 ? substr($page['url'], 0, 60) . '...' : $page['url']) ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($page['keyword'])): ?>
                            <span class="text-sm text-slate-600 dark:text-slate-400"><?= e(strlen($page['keyword']) > 30 ? substr($page['keyword'], 0, 30) . '...' : $page['keyword']) ?></span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                <?= number_format($page['outbound_links'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($page['last_scraped'])): ?>
                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                <?= date('M d, H:i', strtotime($page['last_scraped'])) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
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
