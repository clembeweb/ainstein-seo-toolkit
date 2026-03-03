<?php
/**
 * Crawl Budget — Redirect
 *
 * Variables: $project, $issues, $pagination, $filters, $activeSubTab
 */

$baseUrl = url('/seo-audit/project/' . $project['id']);
$budgetUrl = $baseUrl . '/budget';

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

<?php $currentPage = 'budget'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">

    <!-- Sub-navigation tabs -->
    <div class="flex items-center gap-2 mb-6">
        <a href="<?= url($budgetUrl) ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Panoramica</a>
        <a href="<?= url($budgetUrl . '/redirects') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Redirect</a>
        <a href="<?= url($budgetUrl . '/waste') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Pagine Spreco</a>
        <a href="<?= url($budgetUrl . '/indexability') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Indicizzabilit&agrave;</a>
    </div>

    <!-- Header -->
    <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Problemi Redirect</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= $pagination['total'] ?> problemi di redirect rilevati</p>
    </div>

    <!-- Severity Filter Bar -->
    <div class="flex flex-wrap gap-2">
        <a href="<?= url($budgetUrl . '/redirects') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= empty($filters['severity']) ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
            Tutti
        </a>
        <a href="<?= url($budgetUrl . '/redirects?severity=critical') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['severity'] ?? '') === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
            Critici
        </a>
        <a href="<?= url($budgetUrl . '/redirects?severity=warning') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['severity'] ?? '') === 'warning' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
            Warning
        </a>
        <a href="<?= url($budgetUrl . '/redirects?severity=notice') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['severity'] ?? '') === 'notice' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
            Notice
        </a>
    </div>

    <!-- Issues Table -->
    <?php if (empty($issues)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun problema di redirect</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            <?php if (!empty($filters['severity'])): ?>
            Nessun risultato con i filtri selezionati.
            <?php else: ?>
            Non sono stati rilevati problemi di redirect nel crawl budget.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gravit&agrave;</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dettagli</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($issues as $issue): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <?php if (!empty($issue['url'])): ?>
                            <span class="text-sm text-slate-900 dark:text-white max-w-xs truncate block" title="<?= e($issue['url']) ?>">
                                <?= e(strlen($issue['url']) > 50 ? '...' . substr($issue['url'], -47) : $issue['url']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-sm text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-600 dark:text-slate-300"><?= e($issue['issue_type'] ?? $issue['title'] ?? '-') ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $severityColors[$issue['severity'] ?? 'info'] ?? $severityColors['info'] ?>">
                                <?= $severityLabels[$issue['severity'] ?? 'info'] ?? $issue['severity'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($issue['recommendation'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm truncate" title="<?= e($issue['recommendation']) ?>">
                                <?= e(substr($issue['recommendation'], 0, 80)) ?><?= strlen($issue['recommendation'] ?? '') > 80 ? '...' : '' ?>
                            </p>
                            <?php elseif (!empty($issue['affected_element'])): ?>
                            <code class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-300">
                                <?= e(substr($issue['affected_element'], 0, 60)) ?><?= strlen($issue['affected_element'] ?? '') > 60 ? '...' : '' ?>
                            </code>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= \Core\View::partial('components/table-pagination', [
            'pagination' => $pagination,
            'baseUrl' => url($budgetUrl . '/redirects'),
            'filters' => $filters,
        ]) ?>
    </div>
    <?php endif; ?>
</div>
