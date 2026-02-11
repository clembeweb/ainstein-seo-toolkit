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
                <i data-lucide="download" class="w-4 h-4"></i>
                <?= __('Esporta CSV') ?>
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <i data-lucide="unlink" class="w-6 h-6 text-red-600 dark:text-red-400"></i>
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
                    <i data-lucide="file-text" class="w-6 h-6 text-primary-600 dark:text-primary-400"></i>
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
                    <i data-lucide="percent" class="w-6 h-6 text-amber-600 dark:text-amber-400"></i>
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
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <i data-lucide="info" class="w-5 h-5 text-blue-500"></i>
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
                <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
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
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="check-circle" class="w-8 h-8 text-green-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('Nessuna Pagina Orfana') ?></h3>
        <p class="text-slate-500 dark:text-slate-400">
            <?= __('Tutte le tue pagine hanno almeno un link interno in entrata.') ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full data-table" data-sortable>
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="w-12 text-center px-4 py-3">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="w-12 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">#</th>
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('URL') ?></th>
                        <th class="sortable w-40 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Keyword Target') ?></th>
                        <th class="sortable w-32 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Link in Uscita') ?></th>
                        <th class="w-32 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Stato') ?></th>
                        <th class="w-20 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php
                    $startIndex = isset($pagination) ? $pagination['from'] : 1;
                    foreach ($orphanPages as $index => $page):
                    ?>
                    <tr class="orphan-row hover:bg-slate-50 dark:hover:bg-slate-700/50" data-url-id="<?= $page['id'] ?>">
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
                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                <?= __('Pagina Isola') ?>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs rounded-lg">
                                <i data-lucide="unlink" class="w-3 h-3"></i>
                                <?= __('Orfano') ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="<?= e($page['url']) ?>" target="_blank"
                                   class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition"
                                   title="<?= __('Apri URL') ?>">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                </a>
                                <form action="<?= url("/internal-links/project/{$project['id']}/urls/delete/{$page['id']}") ?>" method="POST" class="inline"
                                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo URL?', {destructive: true}).then(() => $el.submit())">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition"
                                            title="<?= __('Elimina') ?>">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
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
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i data-lucide="lightbulb" class="w-5 h-5 text-amber-500"></i>
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

// Reinit Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
