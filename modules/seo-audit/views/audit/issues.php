<?php
use Modules\SeoAudit\Models\Issue;

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

<?php $currentPage = 'issues'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tutti i Problemi</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= $pagination['total'] ?> problemi rilevati</p>
            </div>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/export/csv') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Esporta CSV
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $issueCounts['total'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-red-200 dark:border-red-900/50 p-4">
            <p class="text-2xl font-bold text-red-600"><?= $issueCounts['critical'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Critici</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-yellow-200 dark:border-yellow-900/50 p-4">
            <p class="text-2xl font-bold text-yellow-600"><?= $issueCounts['warning'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Warning</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-900/50 p-4">
            <p class="text-2xl font-bold text-blue-600"><?= $issueCounts['notice'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Notice</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" action="<?= url('/seo-audit/project/' . $project['id'] . '/issues') ?>" class="flex flex-wrap items-end gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cerca</label>
                <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cerca per URL o titolo..." class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Categoria</label>
                <select name="category" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Tutte</option>
                    <?php foreach (Issue::CATEGORIES as $slug => $label): ?>
                    <?php if (!Issue::isGscCategory($slug)): ?>
                    <option value="<?= $slug ?>" <?= ($filters['category'] ?? '') === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Severity Filter -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Gravità</label>
                <select name="severity" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Tutte</option>
                    <option value="critical" <?= ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' ?>>Critico</option>
                    <option value="warning" <?= ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' ?>>Warning</option>
                    <option value="notice" <?= ($filters['severity'] ?? '') === 'notice' ? 'selected' : '' ?>>Notice</option>
                    <option value="info" <?= ($filters['severity'] ?? '') === 'info' ? 'selected' : '' ?>>Info</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                Filtra
            </button>

            <?php if (!empty($filters)): ?>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/issues') ?>" class="text-sm text-slate-500 hover:text-slate-700">
                Reset filtri
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Quick Category Filters -->
    <?php if (!empty($categoryStats)): ?>
    <div class="flex flex-wrap gap-2">
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/issues') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= empty($filters['category']) ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
            Tutte (<?= $issueCounts['total'] ?? 0 ?>)
        </a>
        <?php foreach ($categoryStats as $slug => $stats): ?>
        <?php if ($stats['total'] > 0 && !Issue::isGscCategory($slug)): ?>
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/issues?category=' . $slug) ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= ($filters['category'] ?? '') === $slug ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 hover:bg-slate-200' ?>">
            <?= e(Issue::CATEGORIES[$slug] ?? $slug) ?> (<?= $stats['total'] ?>)
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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
            <?php if (!empty($filters)): ?>
            Nessun risultato con i filtri selezionati.
            <?php else: ?>
            Ottimo! Non sono stati rilevati problemi SEO.
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoria</th>
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
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $issue['category']) ?>" class="text-sm text-slate-600 dark:text-slate-300 hover:text-primary-600">
                                <?= e(Issue::CATEGORIES[$issue['category']] ?? $issue['category']) ?>
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900 dark:text-white"><?= e($issue['title']) ?></p>
                            <?php if ($issue['recommendation']): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md truncate"><?= e(substr($issue['recommendation'], 0, 80)) ?>...</p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($issue['page_url']): ?>
                            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/page/' . $issue['page_id']) ?>" class="text-sm text-slate-600 dark:text-slate-300 hover:text-primary-600 max-w-xs truncate block" title="<?= e($issue['page_url']) ?>">
                                <?= e(strlen($issue['page_url']) > 40 ? '...' . substr($issue['page_url'], -37) : $issue['page_url']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-sm text-slate-400">Sito</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($issue['affected_element']): ?>
                            <code class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-300 max-w-xs truncate block">
                                <?= e(substr($issue['affected_element'], 0, 50)) ?><?= strlen($issue['affected_element']) > 50 ? '...' : '' ?>
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
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/issues?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1]))) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                    Precedente
                </a>
                <?php endif; ?>
                <span class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400">
                    Pagina <?= $pagination['current_page'] ?> di <?= $pagination['last_page'] ?>
                </span>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/issues?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1]))) ?>"
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
