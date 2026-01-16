<?php
$severityColors = [
    'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
    'notice' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'info' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
];
$severityLabels = [
    'critical' => 'Critico',
    'warning' => 'Warning',
    'notice' => 'Notice',
    'info' => 'Info',
];
?>

<div class="space-y-6">
    <!-- Breadcrumb + Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/seo-audit') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">SEO Audit</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white"><?= e($categoryLabel) ?></span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($categoryLabel) ?></h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= $pagination['total'] ?> problemi trovati in questa categoria</p>
            </div>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/export/csv?category=' . $category) ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Esporta CSV
            </a>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4"
         x-data="{ open: <?= !empty($filters['severity']) || !empty($filters['issue_type']) ? 'true' : 'false' ?> }">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Quick Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category) ?>"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= empty($filters['severity']) ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
                    Tutti (<?= $pagination['total'] ?>)
                </a>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category . '?severity=critical') ?>"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['severity'] ?? '') === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-red-50 hover:text-red-600' ?>">
                    Critici
                </a>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category . '?severity=warning') ?>"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['severity'] ?? '') === 'warning' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-yellow-50 hover:text-yellow-600' ?>">
                    Warning
                </a>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category . '?severity=notice') ?>"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['severity'] ?? '') === 'notice' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-blue-50 hover:text-blue-600' ?>">
                    Notice
                </a>
            </div>

            <?php if (!empty($issueTypes)): ?>
            <div class="flex-1"></div>
            <button @click="open = !open" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 flex items-center gap-1">
                Filtri avanzati
                <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($issueTypes)): ?>
        <div x-show="open" x-transition class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <form method="GET" action="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category) ?>" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Tipo Problema</label>
                    <select name="issue_type" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Tutti i tipi</option>
                        <?php foreach ($issueTypes as $type => $label): ?>
                        <option value="<?= $type ?>" <?= ($filters['issue_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($filters['severity'])): ?>
                <input type="hidden" name="severity" value="<?= e($filters['severity']) ?>">
                <?php endif; ?>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                    Applica
                </button>
                <?php if (!empty($filters['issue_type'])): ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category . ($filters['severity'] ? '?severity=' . $filters['severity'] : '')) ?>" class="text-sm text-slate-500 hover:text-slate-700">
                    Rimuovi filtro
                </a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Issues Table -->
    <?php if (empty($issues)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun problema trovato</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            <?php if (!empty($filters['severity']) || !empty($filters['issue_type'])): ?>
            Nessun risultato con i filtri selezionati.
            <?php else: ?>
            Non sono stati rilevati problemi in questa categoria.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gravità</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Problema</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagina</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Elemento</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azione</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($issues as $issue): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $severityColors[$issue['severity']] ?? $severityColors['info'] ?>">
                                <?= $severityLabels[$issue['severity']] ?? $issue['severity'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($issue['title']) ?></p>
                            <?php if ($issue['recommendation']): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md"><?= e(substr($issue['recommendation'], 0, 100)) ?>...</p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($issue['page_url']): ?>
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $issue['page_id']) ?>" class="text-sm text-slate-600 dark:text-slate-300 hover:text-primary-600 max-w-xs truncate block" title="<?= e($issue['page_url']) ?>">
                                <?= e(strlen($issue['page_url']) > 50 ? '...' . substr($issue['page_url'], -47) : $issue['page_url']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-sm text-slate-400">Sito</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($issue['affected_element']): ?>
                            <code class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-300 max-w-xs truncate block">
                                <?= e(substr($issue['affected_element'], 0, 60)) ?><?= strlen($issue['affected_element']) > 60 ? '...' : '' ?>
                            </code>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($issue['page_id']): ?>
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $issue['page_id']) ?>" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 text-sm font-medium">
                                Dettagli
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Mostrando <?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['total'] ?>
            </p>
            <div class="flex items-center gap-2">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category . '?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1]))) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                    Precedente
                </a>
                <?php endif; ?>
                <span class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400">
                    Pagina <?= $pagination['current_page'] ?> di <?= $pagination['last_page'] ?>
                </span>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category . '?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1]))) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                    Successiva
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
