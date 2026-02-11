<!-- Link Juice Distribution Report -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= __('Analisi Link Juice') ?></h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                <?= __('Analizza la distribuzione del link equity tra le tue pagine') ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url("/internal-links/project/{$project['id']}/export?type=juice") ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                <i data-lucide="download" class="w-4 h-4"></i>
                <?= __('Esporta CSV') ?>
            </a>
        </div>
    </div>

    <!-- Juice Flow Distribution -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= number_format($juiceStats['optimal'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Ottimale') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($juiceStats['good'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Buono') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($juiceStats['weak'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Debole') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($juiceStats['poor'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Scarso') ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-400"><?= number_format($juiceStats['unanalyzed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= __('Non Analizzati') ?></p>
        </div>
    </div>

    <!-- Legend -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <span class="font-medium text-slate-700 dark:text-slate-300"><?= __('Comprendere il Juice Ratio') ?>:</span>
            <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                Ratio &gt; 1 = <?= __('Ricevitore di Juice') ?>
            </span>
            <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                Ratio = 1 = <?= __('Bilanciato') ?>
            </span>
            <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                Ratio &lt; 1 = <?= __('Hub di Juice') ?>
            </span>
            <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                Sink = <?= __('riceve ma non passa') ?>
            </span>
            <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <?= __('Orfano') ?> = <?= __('Nessun link in entrata') ?>
            </span>
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
                   id="juiceSearch"
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

    <!-- Pages Table -->
    <?php if (empty($pages)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="flask-conical" class="w-8 h-8 text-slate-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= __('Nessun Dato Disponibile') ?></h3>
        <p class="text-slate-500 dark:text-slate-400">
            <?= __('Esegui prima lo scraping degli URL per vedere la distribuzione del link juice.') ?>
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
                        <th class="sortable px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('URL') ?></th>
                        <th class="sortable w-36 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Keyword') ?></th>
                        <th class="sortable w-28 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('In Entrata') ?></th>
                        <th class="sortable w-28 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('In Uscita') ?></th>
                        <th class="sortable w-24 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Score') ?></th>
                        <th class="sortable w-28 text-center px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Juice Ratio') ?></th>
                        <th class="w-32 px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= __('Stato') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($pages as $page): ?>
                    <tr class="juice-row hover:bg-slate-50 dark:hover:bg-slate-700/50" data-url="<?= e($page['url']) ?>">
                        <td class="text-center px-4 py-3">
                            <input type="checkbox" name="page_urls[]" value="<?= e($page['url']) ?>" class="juice-checkbox rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3 max-w-[300px]">
                            <a href="<?= e($page['url']) ?>" target="_blank"
                               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 truncate block"
                               title="<?= e($page['url']) ?>">
                                <?= e(mb_substr($page['url'], 0, 50)) ?><?= mb_strlen($page['url']) > 50 ? '...' : '' ?>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($page['keyword'])): ?>
                            <span class="text-sm text-slate-600 dark:text-slate-400"><?= e(mb_substr($page['keyword'], 0, 25)) ?></span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center justify-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium rounded-lg">
                                <?= number_format($page['incoming_count'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center justify-center px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium rounded-lg">
                                <?= number_format($page['outgoing_count'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (!empty($page['avg_incoming_score'])): ?>
                            <span class="text-sm font-medium <?= $page['avg_incoming_score'] >= 7 ? 'text-green-600' : ($page['avg_incoming_score'] >= 4 ? 'text-amber-600' : 'text-red-600') ?>">
                                <?= $page['avg_incoming_score'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-3">
                            <?php if (is_numeric($page['juice_ratio'] ?? null)): ?>
                                <?php
                                $ratio = $page['juice_ratio'];
                                if ($ratio >= 2) {
                                    $ratioClass = 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300';
                                } elseif ($ratio >= 1) {
                                    $ratioClass = 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300';
                                } else {
                                    $ratioClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300';
                                }
                                ?>
                                <span class="inline-flex items-center justify-center px-2 py-1 <?= $ratioClass ?> text-sm font-medium rounded-lg">
                                    <?= $ratio ?>x
                                </span>
                            <?php elseif (($page['juice_ratio'] ?? '') === 'sink'): ?>
                                <span class="inline-flex items-center justify-center px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium rounded-lg">
                                    Sink
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center justify-center px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm font-medium rounded-lg">
                                    <?= __('Orfano') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            if (($page['incoming_count'] ?? 0) == 0) {
                                $status = [__('Orfano'), 'red'];
                            } elseif (($page['outgoing_count'] ?? 0) == 0) {
                                $status = ['Link Sink', 'purple'];
                            } elseif (is_numeric($page['juice_ratio'] ?? null) && $page['juice_ratio'] >= 2) {
                                $status = [__('Pagina Autorita'), 'green'];
                            } elseif (is_numeric($page['juice_ratio'] ?? null) && $page['juice_ratio'] >= 1) {
                                $status = [__('Bilanciato'), 'blue'];
                            } else {
                                $status = [__('Pagina Hub'), 'amber'];
                            }
                            ?>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-<?= $status[1] ?>-100 dark:bg-<?= $status[1] ?>-900/30 text-<?= $status[1] ?>-700 dark:text-<?= $status[1] ?>-300 text-xs rounded-lg">
                                <?= $status[0] ?>
                            </span>
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
                <a href="<?= url("/internal-links/project/{$project['id']}/reports/juice?page=" . ($pagination['current_page'] - 1)) ?>"
                   class="px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                    <?= __('Precedente') ?>
                </a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="<?= url("/internal-links/project/{$project['id']}/reports/juice?page=" . ($pagination['current_page'] + 1)) ?>"
                   class="px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                    <?= __('Successivo') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Info Section -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i data-lucide="info" class="w-5 h-5 text-primary-500"></i>
            <?= __('Tipi di Pagina Spiegati') ?>
        </h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <h4 class="font-medium text-green-700 dark:text-green-300 mb-1"><?= __('Pagina Autorita') ?></h4>
                <p class="text-green-600 dark:text-green-400 text-xs">
                    <?= __('Riceve piu link di quanti ne dia. Queste pagine accumulano link equity.') ?>
                </p>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <h4 class="font-medium text-amber-700 dark:text-amber-300 mb-1"><?= __('Pagina Hub') ?></h4>
                <p class="text-amber-600 dark:text-amber-400 text-xs">
                    <?= __('Da piu link di quanti ne riceva. Distribuisce link equity alle altre pagine.') ?>
                </p>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h4 class="font-medium text-blue-700 dark:text-blue-300 mb-1"><?= __('Pagina Bilanciata') ?></h4>
                <p class="text-blue-600 dark:text-blue-400 text-xs">
                    <?= __('Riceve e da approssimativamente lo stesso numero di link.') ?>
                </p>
            </div>
            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <h4 class="font-medium text-purple-700 dark:text-purple-300 mb-1">Link Sink</h4>
                <p class="text-purple-600 dark:text-purple-400 text-xs">
                    <?= __('Riceve link ma non ha link in uscita. Il link equity si ferma qui.') ?>
                </p>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <h4 class="font-medium text-red-700 dark:text-red-300 mb-1"><?= __('Pagine Orfane') ?></h4>
                <p class="text-red-600 dark:text-red-400 text-xs">
                    <?= __('Nessun link in entrata. Non ricevono link equity.') ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.juice-checkbox').forEach(cb => cb.checked = this.checked);
});

// Search functionality
const searchInput = document.getElementById('juiceSearch');
searchInput?.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.juice-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.juice-checkbox:checked')).map(cb => cb.value);

    if (!action) {
        window.ainstein.alert('Seleziona un\'azione', 'warning');
        return;
    }

    if (selected.length === 0) {
        window.ainstein.alert('Seleziona almeno una pagina', 'warning');
        return;
    }

    if (action === 'delete') {
        window.ainstein.confirm(`Sei sicuro di voler eliminare ${selected.length} pagine?`, {destructive: true}).then(() => {
            window.ainstein.alert('Funzione in sviluppo', 'info');
        });
        return;
    }

    window.ainstein.alert('Funzione in sviluppo', 'info');
}

// Reinit Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
