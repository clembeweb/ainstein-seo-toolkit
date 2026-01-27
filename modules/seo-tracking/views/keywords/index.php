<?php $currentPage = 'keywords'; ?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Page Info + Actions -->
    <div class="flex flex-col gap-3">
        <div class="flex justify-between items-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?= number_format($stats['total'] ?? 0) ?> keyword totali, <?= number_format($stats['tracked'] ?? 0) ?> tracciate
            </p>
            <div class="flex items-center gap-2">
                <!-- Aggiorna Volumi (blu) -->
                <button type="button" onclick="showRefreshModal('volumes')" id="refreshVolumesBtn"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
                        title="Aggiorna volumi di ricerca, CPC e competizione">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Volumi
                </button>

                <!-- Aggiorna Posizioni (verde) -->
                <button type="button" onclick="showRefreshModal('positions')" id="refreshPositionsBtn"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                        title="Aggiorna posizioni SERP delle keyword tracciate">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Posizioni
                </button>

                <!-- Aggiorna Tutto (indigo) -->
                <button type="button" onclick="showRefreshModal('all')" id="refreshAllBtn"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors"
                        title="Aggiorna volumi e posizioni insieme">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Tutto
                </button>

                <div class="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1"></div>

                <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/add') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Aggiungi Keyword
                </a>
            </div>
        </div>

        <!-- Indicatore costi -->
        <?php
        $totalKw = $stats['total'] ?? 0;
        $trackedKw = $stats['tracked'] ?? 0;
        $volumeCost = $totalKw >= 10 ? round($totalKw * 0.3, 1) : round($totalKw * 0.5, 1);
        $positionCost = $trackedKw * 1;
        $allCost = $volumeCost + $positionCost;
        ?>
        <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Costi stimati:
            </span>
            <span class="text-blue-600 dark:text-blue-400">Volumi: ~<?= $volumeCost ?> crediti</span>
            <span class="text-emerald-600 dark:text-emerald-400">Posizioni: ~<?= $positionCost ?> crediti</span>
            <span class="text-indigo-600 dark:text-indigo-400">Tutto: ~<?= $allCost ?> crediti</span>
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
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                Cancella filtri
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Bulk Actions -->
    <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/bulk') ?>" method="POST" id="bulkForm">
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
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600" onclick="toggleAll(this)">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Location">Loc</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Volume</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CPC</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Comp.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tracciata</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aggiornato</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($keywords as $kw): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="keyword_ids[]" value="<?= $kw['id'] ?>" class="rounded border-slate-300 dark:border-slate-600 keyword-checkbox" onchange="updateSelectedCount()">
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="block">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"><?= e($kw['keyword']) ?></p>
                                    <?php if ($kw['group_name']): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($kw['group_name']) ?></span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center" title="<?= e($kw['location_code'] ?? 'IT') ?>">
                                <span class="inline-flex items-center justify-center w-6 h-4 rounded-sm bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase">
                                    <?= e($kw['location_code'] ?? 'IT') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
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
                            <td class="px-4 py-3 text-right text-sm text-slate-900 dark:text-white">
                                <?php if (!empty($kw['search_volume'])): ?>
                                    <?= number_format($kw['search_volume']) ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                                <?php if (!empty($kw['cpc'])): ?>
                                    €<?= number_format($kw['cpc'], 2) ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $compLevel = $kw['competition_level'] ?? null;
                                if ($compLevel):
                                    $compClass = match($compLevel) {
                                        'LOW' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                        'MEDIUM' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                                        'HIGH' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
                                    };
                                    $compText = match($compLevel) {
                                        'LOW' => 'B',
                                        'MEDIUM' => 'M',
                                        'HIGH' => 'A',
                                        default => '-'
                                    };
                                ?>
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold <?= $compClass ?>" title="<?= e($compLevel) ?>">
                                        <?= $compText ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($kw['is_tracked']): ?>
                                <svg class="w-5 h-5 text-amber-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php else: ?>
                                <span class="text-slate-300 dark:text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-500 dark:text-slate-400">
                                <?php if (!empty($kw['last_updated_at'])): ?>
                                    <?= date('d/m H:i', strtotime($kw['last_updated_at'])) ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-500">Mai</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700" title="Dettaglio">
                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $kw['id'] . '/edit') ?>" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700" title="Modifica">
                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button onclick="deleteKeyword(<?= $kw['id'] ?>, '<?= e(addslashes($kw['keyword'])) ?>')" class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30" title="Elimina">
                                        <svg class="w-4 h-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
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

// Single keyword delete
function deleteKeyword(id, keyword) {
    if (!confirm(`Sei sicuro di voler eliminare la keyword "${keyword}"?`)) {
        return;
    }

    const baseUrl = '<?= url('') ?>';
    const projectId = <?= $project['id'] ?>;
    const csrfToken = document.querySelector('input[name="_csrf_token"]').value;

    fetch(`${baseUrl}/seo-tracking/project/${projectId}/keywords/${id}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`input[value="${id}"]`).closest('tr');
            row.remove();
            // Update count
            const countEl = document.querySelector('.text-sm.text-slate-500');
            if (countEl) {
                const text = countEl.textContent;
                const match = text.match(/(\d+)/);
                if (match) {
                    const newCount = parseInt(match[1]) - 1;
                    countEl.textContent = text.replace(/\d+/, newCount);
                }
            }
        } else {
            alert(data.error || 'Errore durante l\'eliminazione');
        }
    })
    .catch(err => {
        console.error('Delete failed:', err);
        alert('Errore durante l\'eliminazione');
    });
}

// Configurazione refresh
const refreshConfig = {
    volumes: {
        btn: 'refreshVolumesBtn',
        url: '/keywords/refresh-volumes',
        label: 'Volumi',
        loadingLabel: 'Aggiornamento volumi...',
        cost: <?= $volumeCost ?>,
        count: <?= $totalKw ?>,
        description: 'Aggiorna volumi di ricerca, CPC e livello di competizione per tutte le keyword.'
    },
    positions: {
        btn: 'refreshPositionsBtn',
        url: '/keywords/refresh-positions',
        label: 'Posizioni',
        loadingLabel: 'Aggiornamento posizioni...',
        cost: <?= $positionCost ?>,
        count: <?= $trackedKw ?>,
        description: 'Aggiorna le posizioni SERP per le keyword tracciate.'
    },
    all: {
        btn: 'refreshAllBtn',
        url: '/keywords/refresh-all',
        label: 'Tutto',
        loadingLabel: 'Aggiornamento completo...',
        cost: <?= $allCost ?>,
        count: <?= $totalKw ?>,
        description: 'Aggiorna sia volumi che posizioni in un\'unica operazione.'
    }
};

const baseUrl = '<?= url('') ?>';
const projectId = <?= $project['id'] ?>;
const userCredits = <?= $userCredits ?? 0 ?>;

// Ottieni keyword selezionate
function getSelectedKeywordIds() {
    return Array.from(document.querySelectorAll('.keyword-checkbox:checked')).map(cb => cb.value);
}

// Modal di conferma
function showRefreshModal(type) {
    const config = refreshConfig[type];
    if (!config) return;

    // Per posizioni/tutto: usa selezione se presente, altrimenti tutte le tracciate
    const selectedIds = getSelectedKeywordIds();
    const useSelection = selectedIds.length > 0 && (type === 'positions' || type === 'all' || type === 'volumes');

    let keywordCount, cost;
    if (useSelection) {
        keywordCount = selectedIds.length;
        if (type === 'volumes') {
            cost = keywordCount >= 10 ? Math.round(keywordCount * 0.3 * 10) / 10 : Math.round(keywordCount * 0.5 * 10) / 10;
        } else if (type === 'positions') {
            cost = keywordCount * 1;
        } else {
            const volCost = keywordCount >= 10 ? keywordCount * 0.3 : keywordCount * 0.5;
            cost = Math.round((volCost + keywordCount) * 10) / 10;
        }
    } else {
        keywordCount = config.count;
        cost = config.cost;
    }

    // Controlla se ci sono keyword da aggiornare
    if (keywordCount === 0) {
        alert(type === 'positions' ? 'Nessuna keyword selezionata o tracciata da aggiornare.' : 'Nessuna keyword da aggiornare.');
        return;
    }

    // Salva selezione per executeRefresh
    window.refreshSelectedIds = useSelection ? selectedIds : null;

    const selectionNote = useSelection
        ? `<p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Verranno elaborate solo le ${keywordCount} keyword selezionate</p>`
        : '';

    // Crea modal
    const modal = document.createElement('div');
    modal.id = 'refreshModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRefreshModal()"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <button onclick="closeRefreshModal()" class="absolute top-4 right-4 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="text-center mb-6">
                <div class="mx-auto h-12 w-12 rounded-full ${type === 'volumes' ? 'bg-blue-100 dark:bg-blue-900/50' : type === 'positions' ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-indigo-100 dark:bg-indigo-900/50'} flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 ${type === 'volumes' ? 'text-blue-600 dark:text-blue-400' : type === 'positions' ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Aggiorna ${config.label}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">${config.description}</p>
                ${selectionNote}
            </div>

            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg p-4 mb-6 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Keyword da elaborare:</span>
                    <span class="font-medium text-slate-900 dark:text-white">${keywordCount.toLocaleString()}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Costo stimato:</span>
                    <span class="font-medium ${type === 'volumes' ? 'text-blue-600 dark:text-blue-400' : type === 'positions' ? 'text-emerald-600 dark:text-emerald-400' : 'text-indigo-600 dark:text-indigo-400'}">${cost} crediti</span>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-700 pt-3 flex justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Il tuo saldo:</span>
                    <span class="font-semibold ${userCredits >= cost ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}">${userCredits.toLocaleString()} crediti</span>
                </div>
            </div>

            ${userCredits < cost ? `
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                    <p class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Crediti insufficienti. Ricarica per continuare.
                    </p>
                </div>
            ` : ''}

            <div class="flex gap-3">
                <button onclick="closeRefreshModal()" class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Annulla
                </button>
                <button onclick="executeRefresh('${type}')" ${userCredits < cost ? 'disabled' : ''} class="flex-1 px-4 py-2.5 rounded-lg ${type === 'volumes' ? 'bg-blue-600 hover:bg-blue-700' : type === 'positions' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700'} text-white font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Conferma
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

function closeRefreshModal() {
    const modal = document.getElementById('refreshModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

// Esecuzione refresh
function executeRefresh(type) {
    closeRefreshModal();

    const config = refreshConfig[type];
    const btn = document.getElementById(config.btn);
    const originalHTML = btn.innerHTML;
    const csrfToken = document.querySelector('input[name="_csrf_token"]').value;

    // Recupera gli ID selezionati (se presenti)
    const selectedIds = window.refreshSelectedIds || null;
    window.refreshSelectedIds = null; // Reset

    // Disabilita tutti i pulsanti e mostra loading
    ['refreshVolumesBtn', 'refreshPositionsBtn', 'refreshAllBtn'].forEach(id => {
        document.getElementById(id).disabled = true;
    });

    btn.innerHTML = `
        <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Attendi...
    `;

    // Costruisci body con keyword_ids se selezionati
    let bodyData = '_csrf_token=' + encodeURIComponent(csrfToken);
    if (selectedIds && selectedIds.length > 0) {
        selectedIds.forEach(id => {
            bodyData += '&keyword_ids[]=' + encodeURIComponent(id);
        });
    }

    fetch(`${baseUrl}/seo-tracking/project/${projectId}${config.url}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: bodyData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showResultModal(true, data);
        } else {
            showResultModal(false, data);
            restoreButtons(type, originalHTML);
        }
    })
    .catch(err => {
        console.error('Refresh failed:', err);
        showResultModal(false, { error: 'Errore di connessione. Riprova.' });
        restoreButtons(type, originalHTML);
    });
}

function restoreButtons(type, originalHTML) {
    const config = refreshConfig[type];
    document.getElementById(config.btn).innerHTML = originalHTML;
    ['refreshVolumesBtn', 'refreshPositionsBtn', 'refreshAllBtn'].forEach(id => {
        document.getElementById(id).disabled = false;
    });
}

function showResultModal(success, data) {
    // Costruisci dettagli posizioni se presenti
    let detailsHtml = '';
    if (success && data.details && data.details.length > 0) {
        detailsHtml = `
            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 mb-4 max-h-48 overflow-y-auto text-left">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2 uppercase">Dettagli posizioni:</p>
                <div class="space-y-1.5">
                    ${data.details.map(d => {
                        if (d.error) {
                            return `<div class="flex justify-between items-center text-xs">
                                <span class="text-slate-700 dark:text-slate-300 truncate mr-2">${d.keyword}</span>
                                <span class="text-red-500 flex-shrink-0">Errore</span>
                            </div>`;
                        }
                        const posClass = d.found
                            ? (d.position <= 3 ? 'text-emerald-600 dark:text-emerald-400' :
                               d.position <= 10 ? 'text-blue-600 dark:text-blue-400' :
                               d.position <= 20 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400')
                            : 'text-red-500 dark:text-red-400';
                        const posText = d.found ? '#' + d.position : 'Non trovata';
                        const prevText = d.previous_position ? ` (era #${d.previous_position})` : '';
                        return `<div class="flex justify-between items-center text-xs">
                            <span class="text-slate-700 dark:text-slate-300 truncate mr-2">${d.keyword}</span>
                            <span class="${posClass} flex-shrink-0 font-medium">${posText}${d.found ? prevText : ''}</span>
                        </div>`;
                    }).join('')}
                </div>
            </div>
        `;
    }

    const modal = document.createElement('div');
    modal.id = 'resultModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="${success ? 'window.location.reload()' : 'closeResultModal()'}"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 text-center">
            <div class="mx-auto h-14 w-14 rounded-full ${success ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-red-100 dark:bg-red-900/50'} flex items-center justify-center mb-4">
                ${success ? `
                    <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                ` : `
                    <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                `}
            </div>

            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                ${success ? 'Aggiornamento completato!' : 'Errore'}
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                ${success ? (data.message || 'Dati aggiornati correttamente.') : (data.error || 'Si e\' verificato un errore.')}
            </p>

            ${detailsHtml}

            ${success && data.credits_used ? `
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 mb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Crediti utilizzati: <span class="font-semibold">${data.credits_used}</span>
                    </p>
                </div>
            ` : ''}

            <button onclick="${success ? 'window.location.reload()' : 'closeResultModal()'}" class="w-full px-4 py-2.5 rounded-lg ${success ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-600 hover:bg-slate-700'} text-white font-medium transition-colors">
                ${success ? 'Chiudi e aggiorna' : 'Chiudi'}
            </button>
        </div>
    `;

    document.body.appendChild(modal);
}

function closeResultModal() {
    const modal = document.getElementById('resultModal');
    if (modal) {
        modal.remove();
        ['refreshVolumesBtn', 'refreshPositionsBtn', 'refreshAllBtn'].forEach(id => {
            document.getElementById(id).disabled = false;
        });
    }
}

// Chiusura modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRefreshModal();
        closeResultModal();
    }
});

// Legacy function per compatibilita
function updateVolumes() {
    showRefreshModal('volumes');
}
</script>
