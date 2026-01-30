<?php $currentPage = 'internal-links'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="linksManager()">

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale URL</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['completed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pronte</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= number_format($stats['pending'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Da Elaborare</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400"><?= number_format($stats['active'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Attive</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['errors'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Errori</p>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/import') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Importa da Sitemap
            </a>

            <?php if (($stats['pending'] ?? 0) > 0): ?>
            <button @click="startScraping()"
                    :disabled="scraping"
                    class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                <svg x-show="!scraping" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg x-show="scraping" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="scraping ? 'Elaborazione...' : 'Elabora Pending'"></span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Search -->
        <form method="GET" class="flex items-center gap-2">
            <?php if (!empty($filters['status'])): ?>
            <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
            <?php endif; ?>
            <div class="relative">
                <input type="text" name="q" value="<?= e($filters['search'] ?? '') ?>"
                       placeholder="Cerca URL o titolo..."
                       class="pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 w-64">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-200 dark:hover:bg-slate-600">
                Cerca
            </button>
            <?php if (!empty($filters['search'])): ?>
            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links' . (!empty($filters['status']) ? '?status=' . $filters['status'] : '')) ?>" class="px-4 py-2 rounded-lg text-slate-500 dark:text-slate-400 text-sm hover:text-slate-700 dark:hover:text-slate-200">
                Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Scraping Progress -->
    <div x-show="scraping" x-cloak class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-blue-700 dark:text-blue-300" x-text="scrapeMessage"></span>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Filters -->
        <?php
        $baseFilterUrl = '/ai-content/projects/' . $project['id'] . '/internal-links';
        $searchParam = !empty($filters['search']) ? '&q=' . urlencode($filters['search']) : '';
        $currentStatus = $filters['status'] ?? null;
        ?>
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <a href="<?= url($baseFilterUrl . ($searchParam ? '?' . ltrim($searchParam, '&') : '')) ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= !$currentStatus ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Tutti
                </a>
                <a href="<?= url($baseFilterUrl . '?status=completed' . $searchParam) ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= $currentStatus === 'completed' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Pronte
                </a>
                <a href="<?= url($baseFilterUrl . '?status=pending' . $searchParam) ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= $currentStatus === 'pending' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Pending
                </a>
                <a href="<?= url($baseFilterUrl . '?status=error' . $searchParam) ?>"
                   class="px-6 py-3 text-sm font-medium border-b-2 <?= $currentStatus === 'error' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' ?>">
                    Errori
                </a>
            </nav>
        </div>

        <?php if (empty($links)): ?>
        <!-- Empty State -->
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <p class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessun link nel pool</p>
            <p class="mt-2 text-slate-500 dark:text-slate-400">Importa URL dal tuo sito per usarle come link interni negli articoli</p>
            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/import') ?>"
               class="inline-flex items-center mt-4 px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Importa da Sitemap
            </a>
        </div>
        <?php else: ?>

        <!-- Bulk Actions Bar -->
        <div x-show="selectedIds.length > 0" x-cloak class="px-6 py-3 bg-primary-50 dark:bg-primary-900/30 border-b border-primary-100 dark:border-primary-800 flex items-center justify-between">
            <span class="text-sm text-primary-700 dark:text-primary-300">
                <span x-text="selectedIds.length"></span> link selezionati
            </span>
            <div class="flex items-center gap-2">
                <button @click="bulkAction('activate')" class="px-3 py-1.5 rounded text-sm bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300">
                    Attiva
                </button>
                <button @click="bulkAction('deactivate')" class="px-3 py-1.5 rounded text-sm bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300">
                    Disattiva
                </button>
                <button @click="bulkAction('delete')" class="px-3 py-1.5 rounded text-sm bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300">
                    Elimina
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 w-12">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL / Titolo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Attivo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($links as $link): ?>
                    <?php
                        $statusClass = match($link['scrape_status']) {
                            'pending' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
                        };
                        $statusLabel = match($link['scrape_status']) {
                            'pending' => 'Pending',
                            'completed' => 'Pronto',
                            'error' => 'Errore',
                            default => $link['scrape_status']
                        };
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="<?= $link['id'] ?>" @change="toggleSelect(<?= $link['id'] ?>)" :checked="selectedIds.includes(<?= $link['id'] ?>)" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($link['title']): ?>
                            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($link['title']) ?></p>
                            <?php endif; ?>
                            <a href="<?= e($link['url']) ?>" target="_blank" class="text-xs text-primary-600 dark:text-primary-400 hover:underline truncate block max-w-md">
                                <?= e($link['url']) ?>
                            </a>
                            <?php if ($link['description']): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-1"><?= e(mb_substr($link['description'], 0, 100)) ?>...</p>
                            <?php endif; ?>
                            <?php if ($link['scrape_status'] === 'error' && $link['scrape_error']): ?>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1"><?= e($link['scrape_error']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="toggleActive(<?= $link['id'] ?>)"
                                    class="<?= $link['is_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?> hover:scale-110 transition-transform">
                                <?php if ($link['is_active']): ?>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <?php else: ?>
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?php endif; ?>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/' . $link['id'] . '/edit') ?>"
                                   class="p-1.5 rounded text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-700" title="Modifica">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button @click="confirmDelete(<?= $link['id'] ?>, '<?= e(addslashes($link['url'])) ?>')"
                                        class="p-1.5 rounded text-red-500 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/30" title="Elimina">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

        <!-- Footer with Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Info -->
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <?php if ($pagination['total'] > 0): ?>
                    Mostrati <?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= number_format($pagination['total']) ?> link
                    <?php else: ?>
                    Nessun link trovato
                    <?php endif; ?>
                </p>

                <!-- Pagination -->
                <?php if ($pagination['last_page'] > 1): ?>
                <?php
                $queryParams = [];
                if (!empty($filters['status'])) $queryParams['status'] = $filters['status'];
                if (!empty($filters['search'])) $queryParams['q'] = $filters['search'];
                $queryString = http_build_query($queryParams);
                ?>
                <div class="flex items-center gap-2">
                    <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= url($baseFilterUrl . '?' . ($queryString ? $queryString . '&' : '') . 'page=' . ($pagination['current_page'] - 1)) ?>"
                       class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        &larr; Precedente
                    </a>
                    <?php endif; ?>

                    <span class="px-3 py-1.5 text-sm text-slate-500 dark:text-slate-400">
                        Pagina <?= $pagination['current_page'] ?> di <?= $pagination['last_page'] ?>
                    </span>

                    <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="<?= url($baseFilterUrl . '?' . ($queryString ? $queryString . '&' : '') . 'page=' . ($pagination['current_page'] + 1)) ?>"
                       class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Successiva &rarr;
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <?php if (($stats['errors'] ?? 0) > 0): ?>
                    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/reset-errors') ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-1.5 rounded text-sm text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/30">
                            Reset Errori
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (($stats['total'] ?? 0) > 0): ?>
                    <button @click="confirmClear()" class="px-3 py-1.5 rounded text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                        Svuota Pool
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50" @click="showDeleteModal = false"></div>
            <div x-transition class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 break-all">
                    Eliminare questo link dal pool?<br>
                    <span class="font-mono text-xs" x-text="deleteUrl"></span>
                </p>
                <form :action="deleteFormUrl" method="POST" class="mt-6">
                    <?= csrf_field() ?>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                            Elimina
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Clear All Modal -->
    <div x-show="showClearModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/50" @click="showClearModal = false"></div>
            <div x-transition class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Svuota Pool</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sei sicuro di voler eliminare tutti i link dal pool? Questa azione non pu&ograve; essere annullata.
                </p>
                <form action="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/clear') ?>" method="POST" class="mt-6">
                    <?= csrf_field() ?>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showClearModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                            Svuota Pool
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Action Form (hidden) -->
    <form id="bulkForm" action="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/bulk') ?>" method="POST" class="hidden">
        <?= csrf_field() ?>
        <input type="hidden" name="action" x-model="bulkActionType">
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>
</div>

<script>
function linksManager() {
    return {
        selectedIds: [],
        showDeleteModal: false,
        showClearModal: false,
        deleteId: null,
        deleteUrl: '',
        deleteFormUrl: '',
        bulkActionType: '',
        scraping: false,
        scrapeMessage: '',

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = <?= json_encode(array_column($links, 'id')) ?>;
            } else {
                this.selectedIds = [];
            }
        },

        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) {
                this.selectedIds.splice(idx, 1);
            } else {
                this.selectedIds.push(id);
            }
        },

        confirmDelete(id, url) {
            this.deleteId = id;
            this.deleteUrl = url;
            this.deleteFormUrl = '<?= url('/ai-content/projects/' . $project['id'] . '/internal-links') ?>/' + id + '/delete';
            this.showDeleteModal = true;
        },

        confirmClear() {
            this.showClearModal = true;
        },

        bulkAction(action) {
            if (this.selectedIds.length === 0) return;
            if (action === 'delete' && !confirm('Eliminare i ' + this.selectedIds.length + ' link selezionati?')) return;
            this.bulkActionType = action;
            this.$nextTick(() => document.getElementById('bulkForm').submit());
        },

        async toggleActive(id) {
            try {
                const response = await fetch('<?= url('/ai-content/projects/' . $project['id'] . '/internal-links') ?>/' + id + '/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '_csrf_token=<?= csrf_token() ?>'
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async startScraping() {
            this.scraping = true;
            this.scrapeMessage = 'Avvio elaborazione...';

            try {
                const response = await fetch('<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/scrape') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: '_csrf_token=<?= csrf_token() ?>&batch_size=20'
                });
                const data = await response.json();

                if (data.success) {
                    this.scrapeMessage = data.message;
                    if (data.pending > 0) {
                        // Continue scraping
                        setTimeout(() => this.startScraping(), 1000);
                    } else {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    this.scrapeMessage = 'Errore: ' + data.error;
                    setTimeout(() => { this.scraping = false; }, 3000);
                }
            } catch (error) {
                this.scrapeMessage = 'Errore di rete';
                setTimeout(() => { this.scraping = false; }, 3000);
            }
        }
    }
}
</script>
