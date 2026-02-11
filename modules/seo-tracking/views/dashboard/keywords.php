<?php
use Modules\SeoTracking\Helpers\PaginationHelper;

$currentPage = 'keywords';
$filters = $filters ?? [];
$pagination = $pagination ?? ['total_items' => 0, 'total_pages' => 1, 'current_page' => 1];
$keywords = $keywords ?? [];
$stats = $stats ?? [];
?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Keywords</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($stats['total_keywords'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Click Totali</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= number_format($stats['total_clicks'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Impressioni</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= number_format($stats['total_impressions'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">CTR Medio</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?= $stats['avg_ctr'] ?? 0 ?>%</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pos. Media</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1"><?= $stats['avg_position'] ?? 0 ?></p>
        </div>
    </div>

    <!-- Filtri GSC Style -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="space-y-4">
            <!-- Riga 1: Date + Ricerca -->
            <div class="flex flex-wrap gap-3 items-end">
                <div class="w-36">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Da</label>
                    <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-36">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">A</label>
                    <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Cerca Keyword</label>
                    <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>"
                           placeholder="Filtra per keyword..."
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Riga 2: Filtri avanzati -->
            <div class="flex flex-wrap gap-3 items-end">
                <div class="w-24">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Click min</label>
                    <input type="number" name="min_clicks" value="<?= (int)($filters['min_clicks'] ?? 0) ?: '' ?>"
                           min="0" placeholder="0"
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-24">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Pos max</label>
                    <input type="number" name="max_position" value="<?= ($filters['max_position'] ?? 0) ?: '' ?>"
                           min="0" step="0.1" placeholder="100"
                           class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-32">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Ordina per</label>
                    <select name="sort" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="clicks" <?= ($filters['sort'] ?? '') === 'clicks' ? 'selected' : '' ?>>Click</option>
                        <option value="impressions" <?= ($filters['sort'] ?? '') === 'impressions' ? 'selected' : '' ?>>Impressioni</option>
                        <option value="avg_position" <?= ($filters['sort'] ?? '') === 'avg_position' ? 'selected' : '' ?>>Posizione</option>
                        <option value="ctr" <?= ($filters['sort'] ?? '') === 'ctr' ? 'selected' : '' ?>>CTR</option>
                        <option value="keyword" <?= ($filters['sort'] ?? '') === 'keyword' ? 'selected' : '' ?>>Keyword</option>
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Ordine</label>
                    <select name="dir" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="desc" <?= ($filters['dir'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Desc</option>
                        <option value="asc" <?= ($filters['dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Asc</option>
                    </select>
                </div>
                <div class="w-20">
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Mostra</label>
                    <select name="per_page" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="25" <?= ($filters['per_page'] ?? 50) == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= ($filters['per_page'] ?? 50) == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($filters['per_page'] ?? 50) == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition">
                        Filtra
                    </button>
                    <a href="?" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-md hover:bg-slate-300 dark:hover:bg-slate-600 text-sm font-medium transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulkActionsBar" class="hidden bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3 flex items-center justify-between">
        <span class="text-sm text-blue-700 dark:text-blue-300">
            <span id="selectedCount">0</span> elementi selezionati
        </span>
        <button type="button" onclick="bulkDelete()" class="px-4 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium transition">
            Elimina Selezionati
        </button>
    </div>

    <!-- Tabella Keywords -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="w-10 px-3 py-3">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                   class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">Click</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">Impr</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">CTR</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">Pos</th>
                        <th class="w-12 px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($keywords)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-4 text-slate-500 dark:text-slate-400">Nessuna keyword trovata</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Prova a modificare i filtri o sincronizza i dati GSC.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($keywords as $kw): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50" data-id="<?= (int)($kw['id'] ?? 0) ?>">
                                <td class="px-3 py-3">
                                    <input type="checkbox" class="row-checkbox rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500"
                                           value="<?= (int)($kw['id'] ?? 0) ?>" onchange="updateBulkBar()">
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?= e($kw['keyword'] ?? '') ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($kw['url'])): ?>
                                        <a href="<?= e($kw['url']) ?>" target="_blank"
                                           class="text-xs text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 truncate block max-w-[200px]"
                                           title="<?= e($kw['url']) ?>">
                                            <?= e(strlen($kw['url']) > 40 ? '...' . substr($kw['url'], -40) : $kw['url']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    <?= number_format($kw['clicks'] ?? 0) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                    <?= number_format($kw['impressions'] ?? 0) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                    <?= number_format($kw['ctr'] ?? 0, 1) ?>%
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php
                                    $pos = (float)($kw['avg_position'] ?? 0);
                                    $posColor = $pos <= 3 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                               ($pos <= 10 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' :
                                               ($pos <= 20 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300'));
                                    ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posColor ?>">
                                        <?= number_format($pos, 1) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <button onclick="deleteKeyword(<?= (int)($kw['id'] ?? 0) ?>)"
                                            class="text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition" title="Elimina">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    <?= PaginationHelper::render($pagination, $filters) ?>
</div>

<!-- JavaScript -->
<script>
const projectId = <?= (int)$project['id'] ?>;
const baseUrl = '<?= url('/seo-tracking/project/' . $project['id']) ?>';

function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const bar = document.getElementById('bulkActionsBar');
    const count = document.getElementById('selectedCount');

    if (checked.length > 0) {
        bar.classList.remove('hidden');
        count.textContent = checked.length;
    } else {
        bar.classList.add('hidden');
    }

    // Update selectAll state
    const all = document.querySelectorAll('.row-checkbox');
    document.getElementById('selectAll').checked = all.length > 0 && checked.length === all.length;
}

function deleteKeyword(id) {
    window.ainstein.confirm('Eliminare questa keyword dai dati GSC?', {destructive: true}).then(() => {
    fetch(`${baseUrl}/keywords/delete/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            window.ainstein.alert(data.error || 'Errore durante l\'eliminazione', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        window.ainstein.alert('Errore di rete', 'error');
    });
    });
}

function bulkDelete() {
    const ids = [...document.querySelectorAll('.row-checkbox:checked')].map(cb => cb.value);
    if (ids.length === 0) return;

    window.ainstein.confirm(`Eliminare ${ids.length} keywords dai dati GSC?`, {destructive: true}).then(() => {
    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));

    fetch(`${baseUrl}/keywords/bulk-delete`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            ids.forEach(id => {
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) row.remove();
            });
            updateBulkBar();
            document.getElementById('selectAll').checked = false;
        } else {
            window.ainstein.alert(data.error || 'Errore durante l\'eliminazione', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        window.ainstein.alert('Errore di rete', 'error');
    });
    });
}
</script>
