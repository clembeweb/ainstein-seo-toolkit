<?php
/**
 * Rank Checker History View
 * Storico completo verifiche SERP
 */

$currentPage = 'history';
?>
<div class="space-y-6">
    <!-- Header + Navigation -->
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Page Title -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Storico Verifiche SERP</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tutte le verifiche posizione effettuate</p>
        </div>
        <div>
            <a href="<?= url("/seo-tracking/project/{$project['id']}/keywords") ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Gestisci Keywords
            </a>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="get" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Filtra Keyword</label>
                <input type="text" name="keyword" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Cerca keyword..."
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Device</label>
                <select name="device" class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    <option value="">Tutti</option>
                    <option value="desktop" <?= ($filters['device'] ?? '') === 'desktop' ? 'selected' : '' ?>>Desktop</option>
                    <option value="mobile" <?= ($filters['device'] ?? '') === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Da</label>
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">A</label>
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                       class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            </div>
            <div class="flex gap-2">
                <label class="flex items-center text-sm text-slate-600 dark:text-slate-400">
                    <input type="checkbox" name="found_only" <?= !empty($filters['found_only']) ? 'checked' : '' ?>
                           class="rounded border-slate-300 dark:border-slate-600 text-blue-600 mr-2">
                    Solo trovati
                </label>
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-md hover:bg-slate-200 dark:hover:bg-slate-600 text-sm font-medium transition-colors">
                Filtra
            </button>
            <a href="<?= url("/seo-tracking/project/{$project['id']}/rank-check/history") ?>"
               class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-sm">
                Reset
            </a>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Check Totali</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $stats['total_checks'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-800 p-4">
            <p class="text-sm text-emerald-600 dark:text-emerald-400">Trovati</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?= $stats['found_count'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-sm text-blue-600 dark:text-blue-400">Top 10</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= $stats['top10_count'] ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pos. Media</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $stats['avg_position'] ? number_format($stats['avg_position'], 1) : '-' ?></p>
        </div>
    </div>

    <!-- Tabella -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <?php if (empty($checks)): ?>
        <div class="px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p class="mt-4 text-slate-500 dark:text-slate-400">Nessuna verifica trovata</p>
            <a href="<?= url("/seo-tracking/project/{$project['id']}/keywords") ?>"
               class="mt-4 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                Vai alle Keywords
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">SERP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">GSC</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Diff</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Device</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($checks as $check): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <div>
                                <span class="text-sm font-medium text-slate-900 dark:text-white"><?= e($check['keyword']) ?></span>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($check['target_domain']) ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($check['serp_position']): ?>
                                <?php
                                $posClass = 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
                                if ($check['serp_position'] <= 3) $posClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
                                elseif ($check['serp_position'] <= 10) $posClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
                                elseif ($check['serp_position'] <= 20) $posClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
                                ?>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium <?= $posClass ?>"><?= $check['serp_position'] ?></span>
                            <?php else: ?>
                                <span class="text-slate-400 text-xs">Non trovato</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($check['gsc_position']): ?>
                                <span class="text-sm text-slate-600 dark:text-slate-400"><?= number_format($check['gsc_position'], 1) ?></span>
                            <?php else: ?>
                                <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($check['position_diff'] !== null): ?>
                                <?php
                                $diffClass = 'text-slate-500';
                                $diffPrefix = '';
                                if ($check['position_diff'] < 0) { $diffClass = 'text-emerald-600'; }
                                elseif ($check['position_diff'] > 0) { $diffClass = 'text-red-600'; $diffPrefix = '+'; }
                                ?>
                                <span class="text-sm font-medium <?= $diffClass ?>"><?= $diffPrefix . $check['position_diff'] ?></span>
                            <?php else: ?>
                                <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                <?= ucfirst($check['device']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-600 dark:text-slate-400"><?= e($check['location']) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($check['serp_url']): ?>
                                <a href="<?= e($check['serp_url']) ?>" target="_blank"
                                   class="text-xs text-slate-500 dark:text-slate-400 hover:text-blue-600 truncate block max-w-[200px]"
                                   title="<?= e($check['serp_url']) ?>">
                                    <?= e(mb_strlen($check['serp_url']) > 50 ? '...' . mb_substr($check['serp_url'], -47) : $check['serp_url']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm text-slate-500 dark:text-slate-400"><?= date('d/m/Y H:i', strtotime($check['checked_at'])) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginazione -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1):
            $paginationPerPage = $pagination['per_page'] ?? 50;
            $paginationFrom = ($pagination['current_page'] - 1) * $paginationPerPage + 1;
            $paginationTo = min($pagination['current_page'] * $paginationPerPage, $pagination['total_count']);
        ?>
        <?= \Core\View::partial('components/table-pagination', [
            'pagination' => [
                'current_page' => $pagination['current_page'],
                'last_page' => $pagination['total_pages'],
                'total' => $pagination['total_count'],
                'per_page' => $paginationPerPage,
                'from' => $paginationFrom,
                'to' => $paginationTo,
            ],
            'baseUrl' => url("/seo-tracking/project/{$project['id']}/rank-check/history"),
            'filters' => array_filter([
                'keyword' => $filters['keyword'] ?? '',
                'device' => $filters['device'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'found_only' => !empty($filters['found_only']) ? '1' : '',
                'not_found_only' => !empty($filters['not_found_only']) ? '1' : '',
            ], fn($v) => $v !== ''),
        ]) ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
