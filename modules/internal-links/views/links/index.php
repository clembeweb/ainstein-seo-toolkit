<!-- Links List Page -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('Link Interni') ?></h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                <?= __('Gestisci i link interni per') ?> <?= e($project['name']) ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url("/internal-links/project/{$project['id']}/links/export") ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <i data-lucide="download" class="w-4 h-4"></i>
                <?= __('Esporta CSV') ?>
            </a>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Link Totali') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= number_format($stats['internal'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Interni') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['external'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Esterni') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400"><?= number_format($stats['analyzed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Analizzati') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= isset($stats['avg_score']) ? number_format($stats['avg_score'], 1) : '-' ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Score Medio') ?></p>
        </div>
    </div>

    <!-- Search and Actions Bar -->
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <!-- Search -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
            </div>
            <input type="text"
                   id="linkSearch"
                   placeholder="<?= __('Cerca URL o anchor text') ?>..."
                   value="<?= e($filters['search'] ?? '') ?>"
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

    <!-- Links Table -->
    <?php if (empty($links)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="link" class="w-8 h-8 text-slate-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('Nessun link trovato') ?></h3>
        <p class="text-slate-500 dark:text-slate-400">
            <?= __('Esegui lo scraping per estrarre i link') ?>
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
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Sorgente') ?></th>
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Anchor Text') ?></th>
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Destinazione') ?></th>
                        <th class="sortable w-20 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Score') ?></th>
                        <th class="sortable w-24 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Juice') ?></th>
                        <th class="w-20 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($links as $link): ?>
                    <tr class="link-row hover:bg-slate-50 dark:hover:bg-slate-700/50" data-link-id="<?= $link['id'] ?>">
                        <td class="text-center px-4 py-3">
                            <input type="checkbox" name="link_ids[]" value="<?= $link['id'] ?>" class="link-checkbox rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3 max-w-[180px]">
                            <a href="<?= e($link['source_url'] ?? '') ?>" target="_blank"
                               class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 truncate block"
                               title="<?= e($link['source_url'] ?? '') ?>">
                                <?= e(strlen($link['source_url'] ?? '') > 35 ? substr($link['source_url'], 0, 35) . '...' : ($link['source_url'] ?? '')) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 max-w-[200px]">
                            <span class="text-sm font-medium text-slate-900 dark:text-white truncate block"
                                  title="<?= e($link['anchor_text'] ?? '') ?>">
                                <?= e(strlen($link['anchor_text'] ?? '') > 40 ? substr($link['anchor_text'], 0, 40) . '...' : ($link['anchor_text'] ?: '-')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 max-w-[180px]">
                            <a href="<?= e($link['destination_url']) ?>" target="_blank"
                               class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary-600 truncate block"
                               title="<?= e($link['destination_url']) ?>">
                                <?= e(strlen($link['destination_url']) > 35 ? substr($link['destination_url'], 0, 35) . '...' : $link['destination_url']) ?>
                            </a>
                            <?php if (!($link['is_internal'] ?? true)): ?>
                            <span class="text-xs text-blue-500"><?= __('esterno') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (!empty($link['ai_relevance_score'])): ?>
                            <?php
                            $score = $link['ai_relevance_score'];
                            if ($score <= 3) $scoreClass = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                            elseif ($score <= 6) $scoreClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                            else $scoreClass = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                            ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold <?= $scoreClass ?>">
                                <?= $score ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (!empty($link['ai_juice_flow'])): ?>
                            <?php
                            $juice = $link['ai_juice_flow'];
                            if ($juice === 'optimal') $juiceClass = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                            elseif ($juice === 'good') $juiceClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                            elseif ($juice === 'weak') $juiceClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                            else $juiceClass = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                            ?>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $juiceClass ?>">
                                <?= $juice ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <form action="<?= url("/internal-links/project/{$project['id']}/links/delete/{$link['id']}") ?>" method="POST" class="inline"
                                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo link?', {destructive: true}).then(() => $el.submit())">
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
    </div>

    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            <?= __('Pagina') ?> <?= $currentPage ?> <?= __('di') ?> <?= $totalPages ?> (<?= number_format($totalLinks) ?> <?= __('link') ?>)
        </p>
        <nav class="flex items-center gap-1">
            <?php if ($currentPage > 1): ?>
            <a href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $currentPage - 1])) ?>"
               class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700">
                <?= __('Precedente') ?>
            </a>
            <?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $currentPage + 1])) ?>"
               class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700">
                <?= __('Successiva') ?>
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
    document.querySelectorAll('.link-checkbox').forEach(cb => cb.checked = this.checked);
});

// Search functionality
const searchInput = document.getElementById('linkSearch');
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
        window.location.href = '<?= url("/internal-links/project/{$project['id']}/links") ?>?' + params.toString();
    }, 500);
});

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.link-checkbox:checked')).map(cb => cb.value);

    if (!action) {
        window.ainstein.alert('Seleziona un\'azione', 'warning');
        return;
    }

    if (selected.length === 0) {
        window.ainstein.alert('Seleziona almeno un link', 'warning');
        return;
    }

    const doAction = () => {
        fetch('<?= url("/internal-links/project/{$project['id']}/links/bulk") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ action: action, link_ids: selected })
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
        window.ainstein.confirm(`Sei sicuro di voler eliminare ${selected.length} link?`, {destructive: true}).then(() => doAction());
    } else {
        doAction();
    }
}

// Reinit Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
