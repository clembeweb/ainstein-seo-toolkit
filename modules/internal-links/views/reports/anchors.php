<!-- Anchor Text Analysis Report -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('Analisi Anchor Text') ?></h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                <?= __('Analizza l\'utilizzo e la distribuzione degli anchor text') ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url("/internal-links/project/{$project['id']}/export?type=anchors") ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <?= __('Esporta CSV') ?>
            </a>
        </div>
    </div>

    <!-- Alerts for Duplicate Anchors -->
    <?php if (!empty($duplicateAnchors)): ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-800 dark:text-amber-200"><?= __('Anchor Duplicati Rilevati') ?></h3>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    <?= __('Trovati') ?> <?= count($duplicateAnchors) ?> <?= __('anchor text che puntano a URL multipli.') ?>
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach (array_slice($duplicateAnchors, 0, 5) as $dup): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 text-xs rounded-lg">
                        "<?= e(mb_substr($dup['anchor'] ?? '', 0, 20)) ?><?= mb_strlen($dup['anchor'] ?? '') > 20 ? '...' : '' ?>"
                        <span class="text-amber-600 dark:text-amber-400">&rarr; <?= $dup['unique_destinations'] ?> URL</span>
                    </span>
                    <?php endforeach; ?>
                    <?php if (count($duplicateAnchors) > 5): ?>
                    <span class="text-xs text-amber-600 dark:text-amber-400">+<?= count($duplicateAnchors) - 5 ?> <?= __('altri') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['unique_anchors'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Anchor Unici') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400"><?= number_format($stats['total_links'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Link Interni') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($stats['duplicate_anchors'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Anchor Duplicati') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['avg_length'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Lungh. Media (car.)') ?></p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <a href="<?= url("/internal-links/project/{$project['id']}/reports/anchors?filter=all") ?>"
           class="px-4 py-2 text-sm font-medium rounded-lg transition <?= ($filter ?? 'all') === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <?= __('Tutti gli Anchor') ?>
        </a>
        <a href="<?= url("/internal-links/project/{$project['id']}/reports/anchors?filter=duplicates") ?>"
           class="px-4 py-2 text-sm font-medium rounded-lg transition flex items-center gap-1 <?= ($filter ?? '') === 'duplicates' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <?= __('Solo Duplicati') ?>
        </a>
        <a href="<?= url("/internal-links/project/{$project['id']}/reports/anchors?filter=single") ?>"
           class="px-4 py-2 text-sm font-medium rounded-lg transition flex items-center gap-1 <?= ($filter ?? '') === 'single' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= __('Anchor Puliti') ?>
        </a>
    </div>

    <!-- Search and Actions Bar -->
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <!-- Search -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text"
                   id="anchorSearch"
                   placeholder="<?= __('Cerca anchor text') ?>..."
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

    <!-- Anchors Table -->
    <?php if (empty($anchors)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('Nessun Dato Anchor Text') ?></h3>
        <p class="text-slate-500 dark:text-slate-400">
            <?= __('Esegui prima lo scraping degli URL per analizzare gli anchor text.') ?>
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
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Anchor Text') ?></th>
                        <th class="sortable w-24 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Utilizzi') ?></th>
                        <th class="sortable w-28 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Destinazioni') ?></th>
                        <th class="sortable w-24 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('Score') ?></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= __('URL Target') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($anchors as $anchor): ?>
                    <tr class="anchor-row hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= ($anchor['unique_destinations'] ?? 0) > 1 ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' ?>">
                        <td class="text-center px-4 py-3">
                            <input type="checkbox" name="anchor_ids[]" value="<?= e($anchor['anchor'] ?? '') ?>" class="anchor-checkbox rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3 max-w-[250px]">
                            <div class="flex items-center gap-2">
                                <?php if (($anchor['unique_destinations'] ?? 0) > 1): ?>
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center" title="<?= __('Anchor Duplicato') ?>">
                                    <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </span>
                                <?php endif; ?>
                                <span class="font-medium text-slate-900 dark:text-white truncate" title="<?= e($anchor['anchor'] ?? '') ?>">
                                    <?= e(mb_substr($anchor['anchor'] ?? '', 0, 50)) ?><?= mb_strlen($anchor['anchor'] ?? '') > 50 ? '...' : '' ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center justify-center px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-sm font-medium">
                                <?= number_format($anchor['usage_count'] ?? $anchor['count'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center justify-center px-2 py-1 rounded text-sm font-medium <?= ($anchor['unique_destinations'] ?? 0) > 1 ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' ?>">
                                <?= $anchor['unique_destinations'] ?? 1 ?>
                            </span>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (!empty($anchor['avg_score'])): ?>
                            <span class="text-sm font-medium <?= $anchor['avg_score'] >= 7 ? 'text-green-600' : ($anchor['avg_score'] >= 4 ? 'text-amber-600' : 'text-red-600') ?>">
                                <?= $anchor['avg_score'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 max-w-[300px]">
                            <div class="flex flex-wrap gap-1">
                                <?php
                                $destinations = $anchor['destinations'] ?? [];
                                if (is_string($destinations)) {
                                    $destinations = explode('|||', $destinations);
                                }
                                foreach (array_slice($destinations, 0, 3) as $dest):
                                ?>
                                <a href="<?= e($dest) ?>" target="_blank"
                                   class="inline-block max-w-[120px] truncate text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded"
                                   title="<?= e($dest) ?>">
                                    <?= e(mb_substr(parse_url($dest, PHP_URL_PATH) ?: '/', 0, 20)) ?>
                                </a>
                                <?php endforeach; ?>
                                <?php if (count($destinations) > 3): ?>
                                <span class="text-xs text-slate-400">+<?= count($destinations) - 3 ?> <?= __('altri') ?></span>
                                <?php endif; ?>
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
        <p class="text-sm text-slate-600 dark:text-slate-400">
            <?= __('Mostrando') ?> <?= $pagination['from'] ?> <?= __('a') ?> <?= $pagination['to'] ?> <?= __('di') ?> <?= number_format($pagination['total']) ?>
        </p>
        <div class="flex gap-2">
            <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?= url("/internal-links/project/{$project['id']}/reports/anchors?page=" . ($pagination['current_page'] - 1) . "&filter=" . ($filter ?? 'all')) ?>"
               class="px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                <?= __('Precedente') ?>
            </a>
            <?php endif; ?>
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="<?= url("/internal-links/project/{$project['id']}/reports/anchors?page=" . ($pagination['current_page'] + 1) . "&filter=" . ($filter ?? 'all')) ?>"
               class="px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                <?= __('Successivo') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.anchor-checkbox').forEach(cb => cb.checked = this.checked);
});

// Search functionality
const searchInput = document.getElementById('anchorSearch');
searchInput?.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.anchor-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.anchor-checkbox:checked')).map(cb => cb.value);

    if (!action) {
        window.ainstein.alert('Seleziona un\'azione', 'warning');
        return;
    }

    if (selected.length === 0) {
        window.ainstein.alert('Seleziona almeno un anchor', 'warning');
        return;
    }

    const doAction = () => {
        fetch('<?= url("/internal-links/project/{$project['id']}/links/bulk-anchors") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ action: action, anchors: selected })
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
        window.ainstein.confirm(`Sei sicuro di voler eliminare i link con ${selected.length} anchor selezionati?`, {destructive: true}).then(() => doAction());
    } else {
        doAction();
    }
}

</script>
