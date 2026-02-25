<?php $currentPage = 'keywords'; ?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Active Job Banner (se c'è un job in corso) -->
    <?php if (!empty($activeJob)): ?>
    <div id="jobStatusBanner" class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">
                        Aggiornamento posizioni in corso
                    </p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400" id="jobProgress">
                        <?= (int)$activeJob['keywords_completed'] ?> / <?= (int)$activeJob['keywords_requested'] ?> keyword elaborate
                        <?php if ($activeJob['current_keyword']): ?>
                            - Attuale: <?= e($activeJob['current_keyword']) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="cancelActiveJob()" class="text-xs px-2 py-1 rounded bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900">
                    Annulla
                </button>
                <button onclick="refreshJobStatus()" class="text-xs px-2 py-1 rounded bg-emerald-100 dark:bg-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-700">
                    Aggiorna stato
                </button>
            </div>
        </div>
        <div class="mt-2">
            <div class="w-full bg-emerald-200 dark:bg-emerald-800 rounded-full h-1.5">
                <?php
                $progress = $activeJob['keywords_requested'] > 0
                    ? round(($activeJob['keywords_completed'] / $activeJob['keywords_requested']) * 100)
                    : 0;
                ?>
                <div id="jobProgressBar" class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: <?= $progress ?>%"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page Info + Actions -->
    <div class="flex flex-col gap-3">
        <div class="flex justify-between items-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?= number_format($stats['total'] ?? 0) ?> keyword totali, <?= number_format($stats['tracked'] ?? 0) ?> tracciate
            </p>
            <div class="flex items-center gap-2">
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <!-- Aggiorna Volumi (blu) - Solo admin -->
                <button type="button" onclick="showRefreshModal('volumes')" id="refreshVolumesBtn"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
                        title="Aggiorna volumi di ricerca, CPC e competizione">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Volumi
                </button>

                <!-- Aggiorna Posizioni (verde) - Solo admin -->
                <button type="button" onclick="showRefreshModal('positions')" id="refreshPositionsBtn"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                        title="Aggiorna posizioni SERP delle keyword tracciate">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Posizioni
                </button>

                <!-- Aggiorna Tutto (indigo) - Solo admin -->
                <button type="button" onclick="showRefreshModal('all')" id="refreshAllBtn"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors"
                        title="Aggiorna volumi e posizioni insieme">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Tutto
                </button>

                <div class="w-px h-6 bg-slate-300 dark:bg-slate-600 mx-1"></div>
                <?php endif; ?>

                <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/add') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Aggiungi Keyword
                </a>
            </div>
        </div>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <!-- Indicatore costi - Solo admin -->
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
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
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

    <!-- Confronta Periodo -->
    <div x-data="dateRangeCompare()" class="relative">
        <?php if (!empty($compareMode)): ?>
        <!-- Compare mode active banner -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Confronto periodo: <?= e($dateFrom) ?> &mdash; <?= e($dateTo) ?>
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">
                            <?= (int) round((strtotime($dateTo) - strtotime($dateFrom)) / 86400) ?> giorni di confronto
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="open = !open" class="text-xs px-3 py-1.5 rounded-lg bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors">
                        Modifica periodo
                    </button>
                    <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords' . (($filters['search'] || $filters['is_tracked'] !== null || $filters['group_name'] || $filters['position_max']) ? '?' . http_build_query(array_filter(['search' => $filters['search'], 'tracked' => $filters['is_tracked'], 'group' => $filters['group_name'], 'position' => $filters['position_max']], fn($v) => $v !== null && $v !== '')) : '')) ?>"
                       class="text-xs px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        Chiudi confronto
                    </a>
                </div>
            </div>

            <!-- Expandable date range panel -->
            <div x-show="open" x-collapse class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <!-- Preserve current filters -->
                    <?php if ($filters['search']): ?>
                    <input type="hidden" name="search" value="<?= e($filters['search']) ?>">
                    <?php endif; ?>
                    <?php if ($filters['is_tracked'] !== null): ?>
                    <input type="hidden" name="tracked" value="<?= e($filters['is_tracked']) ?>">
                    <?php endif; ?>
                    <?php if ($filters['group_name']): ?>
                    <input type="hidden" name="group" value="<?= e($filters['group_name']) ?>">
                    <?php endif; ?>
                    <?php if ($filters['position_max']): ?>
                    <input type="hidden" name="position" value="<?= e($filters['position_max']) ?>">
                    <?php endif; ?>

                    <!-- Preset buttons -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium mr-1">Periodo:</span>
                        <button type="button" @click="applyPreset('7d')" :class="preset === '7d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">7gg</button>
                        <button type="button" @click="applyPreset('14d')" :class="preset === '14d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">14gg</button>
                        <button type="button" @click="applyPreset('28d')" :class="preset === '28d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">28gg</button>
                        <button type="button" @click="applyPreset('3m')" :class="preset === '3m' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">3 mesi</button>
                    </div>

                    <!-- Date inputs -->
                    <div class="flex items-center gap-2">
                        <div>
                            <label class="block text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Da</label>
                            <input type="date" name="date_from" x-model="dateFrom"
                                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">A</label>
                            <input type="date" name="date_to" x-model="dateTo"
                                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                        Confronta
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Toggle button to enter compare mode -->
        <div>
            <button @click="open = !open" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Confronta periodo
                <svg class="w-4 h-4 ml-1.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Expandable date range panel -->
            <div x-show="open" x-collapse class="mt-3">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <!-- Preserve current filters -->
                        <?php if ($filters['search']): ?>
                        <input type="hidden" name="search" value="<?= e($filters['search']) ?>">
                        <?php endif; ?>
                        <?php if ($filters['is_tracked'] !== null): ?>
                        <input type="hidden" name="tracked" value="<?= e($filters['is_tracked']) ?>">
                        <?php endif; ?>
                        <?php if ($filters['group_name']): ?>
                        <input type="hidden" name="group" value="<?= e($filters['group_name']) ?>">
                        <?php endif; ?>
                        <?php if ($filters['position_max']): ?>
                        <input type="hidden" name="position" value="<?= e($filters['position_max']) ?>">
                        <?php endif; ?>

                        <!-- Preset buttons -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium mr-1">Periodo:</span>
                            <button type="button" @click="applyPreset('7d')" :class="preset === '7d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">7gg</button>
                            <button type="button" @click="applyPreset('14d')" :class="preset === '14d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">14gg</button>
                            <button type="button" @click="applyPreset('28d')" :class="preset === '28d' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">28gg</button>
                            <button type="button" @click="applyPreset('3m')" :class="preset === '3m' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors">3 mesi</button>
                        </div>

                        <!-- Date inputs -->
                        <div class="flex items-center gap-2">
                            <div>
                                <label class="block text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">Da</label>
                                <input type="date" name="date_from" x-model="dateFrom"
                                       class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">A</label>
                                <input type="date" name="date_to" x-model="dateTo"
                                       class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                            Confronta
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Results info -->
    <?php if (!empty($keywords)): ?>
    <div class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
        <span>Mostrando <span class="font-medium">1</span> - <span class="font-medium"><?= count($keywords) ?></span> di <span class="font-medium"><?= number_format($stats['total'] ?? count($keywords)) ?></span> keyword</span>
    </div>
    <?php endif; ?>

    <!-- Bulk Actions -->
    <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/bulk') ?>" method="POST" id="bulkForm">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Bulk Actions Bar -->
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center gap-4">
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
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600" onclick="toggleAll(this)">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Keyword</th>
                            <?php if (!empty($compareMode)): ?>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. Inizio</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pos. Fine</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Delta</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Volume</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Gruppo</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Stato</th>
                            <?php else: ?>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Location">Loc</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Posizione</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Volume</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">CPC</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Comp.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Intento di ricerca">Intento</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase" title="Stagionalit&agrave;">Stagion.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aggiornato</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Azioni</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($keywords as $kw): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="keyword_ids[]" value="<?= $kw['id'] ?>" class="rounded border-slate-300 dark:border-slate-600 keyword-checkbox" onchange="updateSelectedCount()">
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/' . $kw['id']) ?>" class="block">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400"><?= e($kw['keyword']) ?></p>
                                    <?php if (empty($compareMode) && $kw['group_name']): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($kw['group_name']) ?></span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <?php if (!empty($compareMode)): ?>
                            <!-- Compare mode columns -->
                            <td class="px-4 py-3 text-right">
                                <?php $posStart = $kw['position_start'] ?? 0; ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                    <?= $posStart > 0 ? number_format($posStart, 1) : '-' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php
                                $posEnd = $kw['position_end'] ?? 0;
                                $posEndClass = $posEnd <= 3 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' :
                                              ($posEnd <= 10 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                              ($posEnd <= 20 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'));
                                ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posEndClass ?>">
                                    <?= $posEnd > 0 ? number_format($posEnd, 1) : '-' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $compareStatus = $kw['compare_status'] ?? 'stable';
                                $delta = $kw['position_delta'] ?? 0;
                                ?>
                                <?php if ($compareStatus === 'improved'): ?>
                                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </svg>
                                    +<?= abs($delta) ?>
                                </span>
                                <?php elseif ($compareStatus === 'declined'): ?>
                                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </svg>
                                    <?= $delta ?>
                                </span>
                                <?php elseif ($compareStatus === 'new'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    NUOVA
                                </span>
                                <?php elseif ($compareStatus === 'lost'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                    PERSA
                                </span>
                                <?php else: ?>
                                <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-slate-900 dark:text-white">
                                <?php if (!empty($kw['search_volume'])): ?>
                                    <?= number_format($kw['search_volume']) ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-left text-sm text-slate-500 dark:text-slate-400">
                                <?= e($kw['group_name'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $statusLabel = match($compareStatus) {
                                    'improved' => 'Migliorata',
                                    'declined' => 'Peggiorata',
                                    'new' => 'Nuova',
                                    'lost' => 'Persa',
                                    default => 'Stabile'
                                };
                                $statusClass = match($compareStatus) {
                                    'improved' => 'text-emerald-600 dark:text-emerald-400',
                                    'declined' => 'text-red-600 dark:text-red-400',
                                    'new' => 'text-blue-600 dark:text-blue-400',
                                    'lost' => 'text-slate-500 dark:text-slate-400',
                                    default => 'text-slate-500 dark:text-slate-400'
                                };
                                ?>
                                <span class="text-xs font-medium <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <?php else: ?>
                            <!-- Normal mode columns -->
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
                                <?php
                                $intent = $kw['keyword_intent'] ?? null;
                                if ($intent):
                                    // Parse intent - puo' essere singolo o multiplo (separato da virgola)
                                    $intents = array_map('trim', explode(',', strtolower($intent)));
                                    $intentBadges = [];
                                    foreach ($intents as $i) {
                                        $badge = match($i) {
                                            'commercial' => ['C', 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'Commercial'],
                                            'informational' => ['I', 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'Informational'],
                                            'navigational' => ['N', 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300', 'Navigational'],
                                            'transactional' => ['T', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'Transactional'],
                                            default => null
                                        };
                                        if ($badge) $intentBadges[] = $badge;
                                    }
                                    foreach ($intentBadges as $badge):
                                ?>
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold <?= $badge[1] ?>" title="<?= e($badge[2]) ?>">
                                        <?= $badge[0] ?>
                                    </span>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="showSeasonalityModal(<?= $kw['id'] ?>, '<?= e(addslashes($kw['keyword'])) ?>', '<?= e($kw['location_code'] ?? 'IT') ?>')"
                                        class="p-1 rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30"
                                        title="Visualizza stagionalit&agrave;">
                                    <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </button>
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
                                    <button onclick="checkSingleKeyword(<?= $kw['id'] ?>, '<?= e(addslashes($kw['keyword'])) ?>', '<?= e($kw['location_code'] ?? 'IT') ?>', this)"
                                            class="p-1 rounded hover:bg-emerald-100 dark:hover:bg-emerald-900/30 single-check-btn"
                                            title="Verifica posizione SERP (1 credito)">
                                        <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
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
                            <?php endif; ?>
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
// ============================================================
// ALPINE.JS: DATE RANGE COMPARE COMPONENT
// ============================================================
function dateRangeCompare() {
    return {
        open: false,
        compareActive: <?= !empty($compareMode) ? 'true' : 'false' ?>,
        preset: 'custom',
        dateFrom: '<?= e($dateFrom ?? date('Y-m-d', strtotime('-28 days'))) ?>',
        dateTo: '<?= e($dateTo ?? date('Y-m-d')) ?>',

        init() {
            // Detect active preset from current dates
            if (this.compareActive) {
                const diffDays = Math.round((new Date(this.dateTo) - new Date(this.dateFrom)) / (1000 * 60 * 60 * 24));
                if (diffDays === 7) this.preset = '7d';
                else if (diffDays === 14) this.preset = '14d';
                else if (diffDays === 28) this.preset = '28d';
                else if (diffDays >= 89 && diffDays <= 92) this.preset = '3m';
                else this.preset = 'custom';
            }
        },

        applyPreset(key) {
            this.preset = key;
            const today = new Date();
            const to = today.toISOString().split('T')[0];
            this.dateTo = to;

            let from = new Date(today);
            switch (key) {
                case '7d':
                    from.setDate(from.getDate() - 7);
                    break;
                case '14d':
                    from.setDate(from.getDate() - 14);
                    break;
                case '28d':
                    from.setDate(from.getDate() - 28);
                    break;
                case '3m':
                    from.setMonth(from.getMonth() - 3);
                    break;
            }
            this.dateFrom = from.toISOString().split('T')[0];
        }
    };
}
</script>

<script>
// ============================================================
// CONFIGURAZIONE E VARIABILI GLOBALI
// ============================================================
const baseUrl = '<?= url('') ?>';
const projectId = <?= $project['id'] ?>;
const userCredits = <?= $userCredits ?? 0 ?>;
const hasActiveJob = <?= !empty($activeJob) ? 'true' : 'false' ?>;
const activeJobId = <?= !empty($activeJob) ? (int)$activeJob['id'] : 'null' ?>;

// Configurazione refresh
const refreshConfig = {
    volumes: {
        btn: 'refreshVolumesBtn',
        url: '/keywords/refresh-volumes',
        label: 'Volumi',
        cost: <?= $volumeCost ?? 0 ?>,
        count: <?= $totalKw ?? 0 ?>,
        description: 'Aggiorna volumi di ricerca, CPC e livello di competizione per tutte le keyword.',
        isBackground: false
    },
    positions: {
        btn: 'refreshPositionsBtn',
        url: '/keywords/start-positions-job',
        label: 'Posizioni',
        cost: <?= $positionCost ?? 0 ?>,
        count: <?= $trackedKw ?? 0 ?>,
        description: 'Aggiorna le posizioni SERP per le keyword tracciate. Il processo avviene in background.',
        isBackground: true
    },
    all: {
        btn: 'refreshAllBtn',
        url: '/keywords/start-positions-job',
        label: 'Tutto',
        cost: <?= $allCost ?? 0 ?>,
        count: <?= $totalKw ?? 0 ?>,
        description: 'Aggiorna sia volumi che posizioni. Le posizioni vengono elaborate in background.',
        isBackground: true
    }
};

// ============================================================
// SELEZIONE KEYWORD
// ============================================================
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

function getSelectedKeywordIds() {
    return Array.from(document.querySelectorAll('.keyword-checkbox:checked')).map(cb => cb.value);
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
        window.ainstein.alert('Seleziona almeno una keyword', 'warning');
        return;
    }

    if (action === 'delete') {
        e.preventDefault();
        window.ainstein.confirm('Sei sicuro di voler eliminare ' + checked + ' keyword?', {destructive: true}).then(() => {
            document.getElementById('bulkForm').submit();
        });
    }
});

// ============================================================
// ELIMINAZIONE SINGOLA KEYWORD
// ============================================================
function deleteKeyword(id, keyword) {
    window.ainstein.confirm(`Sei sicuro di voler eliminare la keyword "${keyword}"?`, {destructive: true}).then(() => {
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
            const row = document.querySelector(`input[value="${id}"]`).closest('tr');
            row.remove();
            showToast('Keyword eliminata', 'success');
        } else {
            showToast(data.error || 'Errore durante l\'eliminazione', 'error');
        }
    })
    .catch(err => {
        console.error('Delete failed:', err);
        showToast('Errore durante l\'eliminazione', 'error');
    });
    });
}

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
function showToast(message, type = 'info', duration = 4000) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 z-50 max-w-sm transform transition-all duration-300';

    const bgColor = type === 'success' ? 'bg-emerald-100 dark:bg-emerald-900/50 border-emerald-200 dark:border-emerald-800' :
                    type === 'error' ? 'bg-red-100 dark:bg-red-900/50 border-red-200 dark:border-red-800' :
                    'bg-blue-100 dark:bg-blue-900/50 border-blue-200 dark:border-blue-800';
    const iconColor = type === 'success' ? 'text-emerald-500' :
                      type === 'error' ? 'text-red-500' : 'text-blue-500';
    const icon = type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                 type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' :
                 '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';

    toast.innerHTML = `
        <div class="${bgColor} border rounded-lg shadow-lg p-4 flex items-center gap-3">
            <svg class="w-5 h-5 ${iconColor} flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                ${icon}
            </svg>
            <p class="text-sm text-slate-700 dark:text-slate-200">${message}</p>
        </div>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ============================================================
// MODAL DI CONFERMA REFRESH
// ============================================================
function showRefreshModal(type) {
    const config = refreshConfig[type];
    if (!config) return;

    // Check if there's already an active job
    if (hasActiveJob) {
        showToast('C\'è già un job in esecuzione. Attendi il completamento.', 'error');
        return;
    }

    const selectedIds = getSelectedKeywordIds();
    const useSelection = selectedIds.length > 0;

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

    if (keywordCount === 0) {
        showToast(type === 'positions' ? 'Nessuna keyword selezionata o tracciata.' : 'Nessuna keyword da aggiornare.', 'error');
        return;
    }

    window.refreshSelectedIds = useSelection ? selectedIds : null;

    const selectionNote = useSelection
        ? `<p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Verranno elaborate solo le ${keywordCount} keyword selezionate</p>`
        : '';

    const isBackground = config.isBackground;
    const backgroundNote = isBackground
        ? `<p class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Il processo avverrà in background. Potrai continuare a navigare.
           </p>`
        : '';

    const modal = document.createElement('div');
    modal.id = 'refreshModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRefreshModal()"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6">
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
                ${backgroundNote}
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
                        Crediti insufficienti.
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

// ============================================================
// ESECUZIONE REFRESH
// ============================================================
function executeRefresh(type) {
    closeRefreshModal();

    const config = refreshConfig[type];

    // Volumi: esecuzione sincrona (batch veloce)
    if (type === 'volumes') {
        executeRefreshSync(type);
        return;
    }

    // Posizioni/Tutto: avvia job in background (NON BLOCCANTE)
    executeBackgroundJob(type);
}

// Refresh sincrono per volumi
function executeRefreshSync(type) {
    const config = refreshConfig[type];
    const btn = document.getElementById(config.btn);
    const originalHTML = btn.innerHTML;
    const csrfToken = document.querySelector('input[name="_csrf_token"]').value;
    const selectedIds = window.refreshSelectedIds || null;
    window.refreshSelectedIds = null;

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

    let bodyData = '_csrf_token=' + encodeURIComponent(csrfToken);
    if (selectedIds && selectedIds.length > 0) {
        selectedIds.forEach(id => {
            bodyData += '&keyword_ids[]=' + encodeURIComponent(id);
        });
    }

    fetch(`${baseUrl}/seo-tracking/project/${projectId}/keywords/refresh-volumes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: bodyData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Volumi aggiornati!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.error || 'Errore durante l\'aggiornamento', 'error');
            btn.innerHTML = originalHTML;
            ['refreshVolumesBtn', 'refreshPositionsBtn', 'refreshAllBtn'].forEach(id => {
                document.getElementById(id).disabled = false;
            });
        }
    })
    .catch(err => {
        console.error('Refresh failed:', err);
        showToast('Errore di connessione', 'error');
        btn.innerHTML = originalHTML;
        ['refreshVolumesBtn', 'refreshPositionsBtn', 'refreshAllBtn'].forEach(id => {
            document.getElementById(id).disabled = false;
        });
    });
}

// Avvia job in background (NON BLOCCANTE - nessuna modal)
function executeBackgroundJob(type) {
    const csrfToken = document.querySelector('input[name="_csrf_token"]').value;
    const selectedIds = window.refreshSelectedIds || null;
    window.refreshSelectedIds = null;

    // Solo mostra loading sul bottone
    const config = refreshConfig[type];
    const btn = document.getElementById(config.btn);
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Avvio...
    `;

    let bodyData = '_csrf_token=' + encodeURIComponent(csrfToken);
    if (selectedIds && selectedIds.length > 0) {
        selectedIds.forEach(id => {
            bodyData += '&keyword_ids[]=' + encodeURIComponent(id);
        });
    }

    // Per "tutto", prima aggiorna volumi sincronamente
    if (type === 'all') {
        bodyData += '&include_volumes=1';
    }

    fetch(`${baseUrl}/seo-tracking/project/${projectId}/keywords/start-positions-job`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: bodyData
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;

        if (data.success) {
            // Mostra toast e ricarica pagina per vedere il banner
            showToast('Job avviato in background! Puoi continuare a navigare.', 'success', 5000);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.error || 'Errore nell\'avvio del job', 'error');
        }
    })
    .catch(err => {
        console.error('Start job failed:', err);
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        showToast('Errore di connessione', 'error');
    });
}

// ============================================================
// GESTIONE JOB ATTIVO (BANNER)
// ============================================================
function refreshJobStatus() {
    if (!activeJobId) {
        showToast('Nessun job attivo', 'info');
        return;
    }

    fetch(`${baseUrl}/seo-tracking/project/${projectId}/keywords/positions-job-status?job_id=${activeJobId}`, {
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            showToast(data.error || 'Errore nel recupero stato', 'error');
            return;
        }

        const job = data.job;

        // Se completato/errore/annullato, ricarica pagina
        if (job.status === 'completed' || job.status === 'error' || job.status === 'cancelled') {
            showToast(job.status === 'completed' ? 'Job completato!' : 'Job terminato', job.status === 'completed' ? 'success' : 'info');
            setTimeout(() => window.location.reload(), 1000);
            return;
        }

        // Aggiorna banner
        const progressEl = document.getElementById('jobProgress');
        const barEl = document.getElementById('jobProgressBar');
        if (progressEl) {
            progressEl.textContent = `${job.keywords_completed} / ${job.keywords_requested} keyword elaborate` +
                (job.current_keyword ? ` - Attuale: ${job.current_keyword}` : '');
        }
        if (barEl && job.keywords_requested > 0) {
            const percent = Math.round((job.keywords_completed / job.keywords_requested) * 100);
            barEl.style.width = percent + '%';
        }

        showToast('Stato aggiornato', 'info', 2000);
    })
    .catch(err => {
        console.error('Status check failed:', err);
        showToast('Errore: ' + err.message, 'error');
    });
}

function cancelActiveJob() {
    if (!activeJobId) {
        showToast('Nessun job attivo', 'info');
        return;
    }

    window.ainstein.confirm('Sei sicuro di voler annullare il job in corso?', {destructive: true}).then(() => {

    const csrfInput = document.querySelector('input[name="_csrf_token"]');
    if (!csrfInput) {
        showToast('Errore: token CSRF non trovato. Ricarica la pagina.', 'error');
        return;
    }
    const csrfToken = csrfInput.value;

    fetch(`${baseUrl}/seo-tracking/project/${projectId}/keywords/cancel-positions-job`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: `_csrf_token=${encodeURIComponent(csrfToken)}&job_id=${activeJobId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('Job annullato', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore durante l\'annullamento', 'error');
        }
    })
    .catch(err => {
        console.error('Cancel failed:', err);
        showToast('Errore: ' + err.message, 'error');
    });
    });
}

// Auto-refresh banner se c'è un job attivo (ogni 30 secondi - non troppo frequente)
// Disabilitato temporaneamente per debug - riattivare quando stabile
// if (hasActiveJob) {
//     setInterval(refreshJobStatus, 30000);
// }

// Legacy function
function updateVolumes() {
    showRefreshModal('volumes');
}

// Chiusura modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRefreshModal();
    }
});

// Check singola keyword
function checkSingleKeyword(keywordId, keyword, locationCode, btnElement) {
    // Verifica crediti
    if (userCredits < 1) {
        window.ainstein.alert('Crediti insufficienti per verificare la posizione.', 'warning');
        return;
    }

    // Mostra loading sul bottone
    const originalHTML = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    `;

    const csrfToken = document.querySelector('input[name="_csrf_token"]').value;

    fetch(`${baseUrl}/seo-tracking/project/${projectId}/rank-check/single`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_csrf_token=${encodeURIComponent(csrfToken)}&keyword=${encodeURIComponent(keyword)}&location=${encodeURIComponent(locationCode)}&device=desktop`
    })
    .then(response => response.json())
    .then(data => {
        btnElement.disabled = false;
        btnElement.innerHTML = originalHTML;

        if (data.error) {
            showSingleCheckResult(keyword, false, null, data.error);
            return;
        }

        // Aggiorna la cella posizione nella riga
        const row = btnElement.closest('tr');
        const positionCell = row.querySelector('td:nth-child(4) span');
        if (positionCell && data.serp_position) {
            const pos = data.serp_position;
            let posClass = 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
            if (pos <= 3) posClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
            else if (pos <= 10) posClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
            else if (pos <= 20) posClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300';

            positionCell.className = `inline-flex px-2 py-0.5 rounded text-xs font-medium ${posClass}`;
            positionCell.textContent = pos.toFixed(1);
        } else if (positionCell && !data.found) {
            positionCell.className = 'inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
            positionCell.textContent = '-';
        }

        // Aggiorna la cella "Aggiornato" (colonna 10: checkbox, keyword, loc, pos, vol, cpc, comp, intento, stagion, aggiornato)
        const updatedCell = row.querySelector('td:nth-child(10)');
        if (updatedCell) {
            const now = new Date();
            const timeStr = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            updatedCell.innerHTML = `<span class="text-emerald-600 dark:text-emerald-400">${timeStr}</span>`;
        }

        showSingleCheckResult(keyword, data.found, data.serp_position, null, data.serp_url);
    })
    .catch(err => {
        console.error('Check failed:', err);
        btnElement.disabled = false;
        btnElement.innerHTML = originalHTML;
        showSingleCheckResult(keyword, false, null, 'Errore di connessione');
    });
}

function showSingleCheckResult(keyword, found, position, error, url) {
    // Toast notification in basso a destra
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 z-50 max-w-sm bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-4 transform transition-all duration-300';

    if (error) {
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">${keyword}</p>
                    <p class="text-xs text-red-500">${error}</p>
                </div>
            </div>
        `;
    } else if (found) {
        const posClass = position <= 3 ? 'text-emerald-600' : position <= 10 ? 'text-blue-600' : position <= 20 ? 'text-amber-600' : 'text-slate-600';
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">${keyword}</p>
                    <p class="text-xs ${posClass} font-semibold">Posizione #${position}</p>
                    ${url ? `<p class="text-xs text-slate-400 truncate mt-0.5">${url}</p>` : ''}
                </div>
            </div>
        `;
    } else {
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">${keyword}</p>
                    <p class="text-xs text-amber-600">Non trovata in top 100</p>
                </div>
            </div>
        `;
    }

    document.body.appendChild(toast);

    // Auto-remove dopo 4 secondi
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ============================================================
// SEASONALITY MODAL
// ============================================================
let seasonalityChart = null;

function showSeasonalityModal(keywordId, keyword, locationCode) {
    // Crea modal
    const modal = document.createElement('div');
    modal.id = 'seasonalityModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSeasonalityModal()"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-2xl w-full p-6">
            <button onclick="closeSeasonalityModal()" class="absolute top-4 right-4 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="mb-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Stagionalit&agrave;</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1" id="seasonalityKeyword">${keyword}</p>
            </div>

            <div id="seasonalityContent" class="min-h-[300px] flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-8 h-8 text-indigo-500 animate-spin mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Caricamento dati...</p>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Carica dati
    loadSeasonalityData(keywordId, locationCode);
}

function closeSeasonalityModal() {
    const modal = document.getElementById('seasonalityModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
    if (seasonalityChart) {
        seasonalityChart.destroy();
        seasonalityChart = null;
    }
}

function loadSeasonalityData(keywordId, locationCode) {
    fetch(`${baseUrl}/seo-tracking/project/${projectId}/keywords/${keywordId}/seasonality?location=${encodeURIComponent(locationCode)}`, {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('seasonalityContent');
        if (!container) return;

        if (!data.success) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-red-300 dark:text-red-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-400">${data.error || 'Errore nel caricamento'}</p>
                </div>
            `;
            return;
        }

        if (!data.has_data) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Nessun dato di stagionalit&agrave; disponibile</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Aggiorna i volumi per ottenere i dati mensili</p>
                </div>
            `;
            return;
        }

        // Mostra grafico
        container.innerHTML = `
            <div class="w-full">
                <canvas id="seasonalityChart" height="250"></canvas>
            </div>
        `;

        renderSeasonalityChart(data.labels, data.data);
    })
    .catch(err => {
        console.error('Seasonality load failed:', err);
        const container = document.getElementById('seasonalityContent');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-red-300 dark:text-red-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Errore di connessione</p>
                </div>
            `;
        }
    });
}

function renderSeasonalityChart(labels, data) {
    const ctx = document.getElementById('seasonalityChart');
    if (!ctx) return;

    // Detect dark mode
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? '#334155' : '#e2e8f0';

    seasonalityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Volume ricerche',
                data: data,
                backgroundColor: isDark ? 'rgba(129, 140, 248, 0.7)' : 'rgba(99, 102, 241, 0.7)',
                borderColor: isDark ? 'rgb(129, 140, 248)' : 'rgb(99, 102, 241)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#f1f5f9' : '#1e293b',
                    bodyColor: isDark ? '#cbd5e1' : '#475569',
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString() + ' ricerche';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: textColor,
                        font: { size: 11 }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor
                    },
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        callback: function(value) {
                            if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'k';
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });
}

// Chiudi modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSeasonalityModal();
    }
});
</script>
