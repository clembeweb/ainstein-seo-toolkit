<!-- URLs List Page -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('URLs') ?></h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                <?= __('Manage URLs') ?> <?= __('for') ?> <?= e($project['name']) ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url("/internal-links/project/{$project['id']}/urls/import") ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <?= __('Import URLs') ?>
            </a>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <a href="<?= url("/internal-links/project/{$project['id']}/urls") ?>"
           class="px-4 py-2 text-sm font-medium rounded-lg transition <?= !($currentStatus ?? null) ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <?= __('All') ?>
            <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-600">
                <?= number_format(array_sum($statusStats ?? [])) ?>
            </span>
        </a>
        <?php
        $statusColors = [
            'pending' => 'bg-slate-500',
            'scraping' => 'bg-blue-500',
            'scraped' => 'bg-green-500',
            'error' => 'bg-red-500',
            'no_content' => 'bg-amber-500',
        ];
        foreach (($statusStats ?? []) as $status => $count):
            if ($count === 0) continue;
        ?>
        <a href="<?= url("/internal-links/project/{$project['id']}/urls?status={$status}") ?>"
           class="px-4 py-2 text-sm font-medium rounded-lg transition flex items-center gap-2 <?= ($currentStatus ?? null) === $status ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <span class="w-2 h-2 rounded-full <?= $statusColors[$status] ?>"></span>
            <?= ucfirst(str_replace('_', ' ', $status)) ?>
            <span class="text-xs px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-600">
                <?= number_format($count) ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search and Actions Bar -->
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <!-- Search -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text"
                   id="urlSearch"
                   placeholder="<?= __('Search URLs or keywords') ?>..."
                   value="<?= e($search ?? '') ?>"
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition text-sm">
        </div>

        <!-- Bulk Actions -->
        <div class="flex items-center gap-2">
            <select id="bulkAction" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm text-slate-700 dark:text-slate-300">
                <option value=""><?= __('Bulk Actions') ?></option>
                <option value="delete"><?= __('Delete Selected') ?></option>
                <option value="reset"><?= __('Reset to Pending') ?></option>
            </select>
            <button onclick="executeBulkAction()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <?= __('Apply') ?>
            </button>
        </div>
    </div>

    <!-- URLs Table -->
    <?php if (empty($urls)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('No URLs found') ?></h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6">
            <?= ($currentStatus ?? null) ? __('No URLs with status') . " '{$currentStatus}'" : __('Import URLs to start analyzing internal links') ?>
        </p>
        <?php if (!($currentStatus ?? null)): ?>
        <a href="<?= url("/internal-links/project/{$project['id']}/urls/import") ?>"
           class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white font-medium rounded-xl hover:bg-primary-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <?= __('Import URLs') ?>
        </a>
        <?php endif; ?>
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
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="sortable w-40 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Keyword') ?></th>
                        <th class="sortable w-28 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Status') ?></th>
                        <th class="sortable w-24 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('HTTP') ?></th>
                        <th class="sortable w-36 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Last Scraped') ?></th>
                        <th class="w-24 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($urls as $url): ?>
                    <tr class="url-row hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" data-url-id="<?= $url['id'] ?>">
                        <td class="text-center px-4 py-3">
                            <input type="checkbox" name="url_ids[]" value="<?= $url['id'] ?>" class="url-checkbox rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-md">
                                <a href="<?= e($url['url']) ?>" target="_blank"
                                   class="text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition truncate block font-medium text-sm">
                                    <?= e(strlen($url['url']) > 60 ? substr($url['url'], 0, 60) . '...' : $url['url']) ?>
                                </a>
                                <?php if (!empty($url['error_message'])): ?>
                                <p class="text-xs text-red-500 mt-1 truncate" title="<?= e($url['error_message']) ?>">
                                    <?= e(strlen($url['error_message']) > 50 ? substr($url['error_message'], 0, 50) . '...' : $url['error_message']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($url['keyword'])): ?>
                            <span class="text-sm text-slate-600 dark:text-slate-400"><?= e(strlen($url['keyword']) > 30 ? substr($url['keyword'], 0, 30) . '...' : $url['keyword']) ?></span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php
                            $statusBadges = [
                                'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                'scraping' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'scraped' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'error' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                'no_content' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            ];
                            $badgeClass = $statusBadges[$url['status']] ?? $statusBadges['pending'];
                            ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                <?= ucfirst(str_replace('_', ' ', $url['status'])) ?>
                            </span>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (!empty($url['http_status'])): ?>
                            <span class="text-sm font-mono <?= $url['http_status'] >= 400 ? 'text-red-600' : ($url['http_status'] >= 300 ? 'text-amber-600' : 'text-green-600') ?>">
                                <?= $url['http_status'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($url['scraped_at'])): ?>
                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                <?= date('M d, H:i', strtotime($url['scraped_at'])) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <?php if ($url['status'] === 'scraped'): ?>
                                <a href="<?= url("/internal-links/project/{$project['id']}/links?source_url_id={$url['id']}") ?>"
                                   class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition"
                                   title="<?= __('View Links') ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </a>
                                <?php endif; ?>
                                <form action="<?= url("/internal-links/project/{$project['id']}/urls/delete/{$url['id']}") ?>" method="POST" class="inline"
                                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo URL?', {destructive: true}).then(() => $el.submit())">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition"
                                            title="<?= __('Delete') ?>">
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

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['last_page'] > 1): ?>
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Showing <?= $pagination['from'] ?> to <?= $pagination['to'] ?> of <?= number_format($pagination['total']) ?> results
        </p>
        <nav class="flex items-center gap-1">
            <?php if ($pagination['current_page'] > 1): ?>
            <a href="?page=<?= $pagination['current_page'] - 1 ?><?= ($currentStatus ?? null) ? '&status=' . $currentStatus : '' ?><?= ($search ?? null) ? '&search=' . urlencode($search) : '' ?>"
               class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700">
                Previous
            </a>
            <?php endif; ?>

            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="?page=<?= $pagination['current_page'] + 1 ?><?= ($currentStatus ?? null) ? '&status=' . $currentStatus : '' ?><?= ($search ?? null) ? '&search=' . urlencode($search) : '' ?>"
               class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700">
                Next
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.url-checkbox').forEach(cb => cb.checked = this.checked);
});

// Search functionality
const searchInput = document.getElementById('urlSearch');
let searchTimeout;

searchInput?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const params = new URLSearchParams(window.location.search);
        if (this.value) {
            params.set('search', this.value);
        } else {
            params.delete('search');
        }
        params.delete('page');
        window.location.href = '<?= url("/internal-links/project/{$project['id']}/urls") ?>?' + params.toString();
    }, 500);
});

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.url-checkbox:checked')).map(cb => cb.value);

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
