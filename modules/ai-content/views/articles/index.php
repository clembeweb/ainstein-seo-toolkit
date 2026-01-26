<?php
// Base URL for project context
$baseUrl = !empty($projectId) ? '/ai-content/projects/' . $projectId : '/ai-content';
?>

<?php if (!empty($projectId) && !empty($project)): ?>
<?php $currentPage = 'articles'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>
<?php endif; ?>

<div class="space-y-6" x-data="articlesManager()">
    <?php if (empty($projectId) || empty($project)): ?>
    <!-- Breadcrumbs (global view) -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    AI Content
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Articoli</span>
            </li>
        </ol>
    </nav>

    <!-- Header (global view) -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Articoli</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci gli articoli generati con AI</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <!-- Stats -->
            <?php if (!empty($stats)): ?>
            <div class="hidden sm:flex items-center gap-4 text-sm">
                <span class="text-slate-500 dark:text-slate-400">
                    <span class="font-medium text-slate-700 dark:text-slate-300"><?= $stats['total'] ?? 0 ?></span> totali
                </span>
                <span class="text-emerald-600 dark:text-emerald-400">
                    <span class="font-medium"><?= $stats['ready'] ?? 0 ?></span> pronti
                </span>
                <span class="text-blue-600 dark:text-blue-400">
                    <span class="font-medium"><?= $stats['published'] ?? 0 ?></span> pubblicati
                </span>
            </div>
            <?php endif; ?>

            <a href="<?= url($baseUrl . '/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Articolo
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="flex flex-wrap gap-2">
        <a href="<?= url($baseUrl . '/articles') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= empty($currentStatus) ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            Tutti
        </a>
        <a href="<?= url($baseUrl . '/articles?status=draft') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'draft' ? 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            Bozze
        </a>
        <a href="<?= url($baseUrl . '/articles?status=generating') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'generating' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            In generazione
        </a>
        <a href="<?= url($baseUrl . '/articles?status=ready') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'ready' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            Pronti
        </a>
        <a href="<?= url($baseUrl . '/articles?status=published') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'published' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            Pubblicati
        </a>
        <a href="<?= url($baseUrl . '/articles?status=failed') ?>"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $currentStatus === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            Falliti
        </a>
    </div>

    <?php if (empty($articles)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
            <?php if ($currentStatus): ?>
                Nessun articolo <?= $currentStatus === 'generating' ? 'in generazione' : ($currentStatus === 'ready' ? 'pronto' : ($currentStatus === 'published' ? 'pubblicato' : ($currentStatus === 'failed' ? 'fallito' : 'in bozza'))) ?>
            <?php else: ?>
                Nessun articolo
            <?php endif; ?>
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            <?php if ($currentStatus): ?>
                Non ci sono articoli con questo status. Prova a cambiare filtro.
            <?php else: ?>
                Inizia selezionando una keyword e le fonti dalla SERP per generare il tuo primo articolo SEO-ottimizzato.
            <?php endif; ?>
        </p>
        <?php if (!$currentStatus): ?>
        <div class="mt-6">
            <a href="<?= url($baseUrl . '/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Vai alle Keywords
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Articles Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Titolo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Parole</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($articles as $article): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <a href="<?= url($baseUrl . '/articles/' . $article['id']) ?>" class="font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block max-w-xs">
                                        <?= e($article['title'] ?: 'Articolo #' . $article['id']) ?>
                                    </a>
                                    <?php if (!empty($article['meta_description'])): ?>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs"><?= e(substr($article['meta_description'], 0, 60)) ?>...</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (!empty($article['keyword'])): ?>
                            <a href="<?= url('/ai-content/keywords/' . $article['keyword_id'] . '/serp') ?>" class="inline-flex items-center gap-1 text-sm text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <?= e($article['keyword']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-500 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($article['word_count'] > 0): ?>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= number_format($article['word_count']) ?></span>
                            <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-500 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php
                            $statusConfig = [
                                'draft' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-400', 'label' => 'Bozza'],
                                'generating' => ['bg' => 'bg-amber-100 dark:bg-amber-900/50', 'text' => 'text-amber-700 dark:text-amber-300', 'label' => 'Generazione...', 'spinner' => true],
                                'ready' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/50', 'text' => 'text-emerald-700 dark:text-emerald-300', 'label' => 'Pronto'],
                                'published' => ['bg' => 'bg-blue-100 dark:bg-blue-900/50', 'text' => 'text-blue-700 dark:text-blue-300', 'label' => 'Pubblicato'],
                                'failed' => ['bg' => 'bg-red-100 dark:bg-red-900/50', 'text' => 'text-red-700 dark:text-red-300', 'label' => 'Fallito'],
                            ];
                            $status = $statusConfig[$article['status']] ?? $statusConfig['draft'];
                            ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $status['bg'] ?> <?= $status['text'] ?>">
                                <?php if (!empty($status['spinner'])): ?>
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <?php endif; ?>
                                <?= $status['label'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- View -->
                                <a href="<?= url($baseUrl . '/articles/' . $article['id']) ?>"
                                   class="p-2 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Visualizza">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                <?php if ($article['status'] === 'ready'): ?>
                                <!-- Publish to WP -->
                                <button @click="showPublishModal(<?= $article['id'] ?>, '<?= e(addslashes($article['title'] ?: 'Articolo #' . $article['id'])) ?>')"
                                        class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Pubblica su WordPress">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </button>
                                <?php endif; ?>

                                <?php if ($article['status'] !== 'published' && $article['status'] !== 'generating'): ?>
                                <!-- Regenerate -->
                                <button @click="regenerateArticle(<?= $article['id'] ?>)"
                                        :disabled="regenerating === <?= $article['id'] ?>"
                                        class="p-2 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors disabled:opacity-50"
                                        title="Rigenera (10 crediti)">
                                    <svg x-show="regenerating !== <?= $article['id'] ?>" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <svg x-show="regenerating === <?= $article['id'] ?>" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>

                                <!-- Delete -->
                                <button @click="confirmDelete(<?= $article['id'] ?>, '<?= e(addslashes($article['title'] ?: 'Articolo #' . $article['id'])) ?>')"
                                        class="p-2 rounded-lg text-slate-500 hover:text-red-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Elimina">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                <?php endif; ?>

                                <?php if ($article['status'] === 'published' && !empty($article['published_url'])): ?>
                                <!-- View Published -->
                                <a href="<?= e($article['published_url']) ?>"
                                   target="_blank"
                                   class="p-2 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Apri articolo pubblicato">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Pagina <?= $pagination['current_page'] ?> di <?= $pagination['last_page'] ?>
                    (<?= $pagination['total'] ?> articoli)
                </p>
                <div class="flex gap-2">
                    <?php
                    $queryParams = $currentStatus ? '&status=' . $currentStatus : '';
                    ?>
                    <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= url($baseUrl . '/articles?page=' . ($pagination['current_page'] - 1) . $queryParams) ?>"
                       class="px-3 py-1 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Precedente
                    </a>
                    <?php endif; ?>
                    <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="<?= url($baseUrl . '/articles?page=' . ($pagination['current_page'] + 1) . $queryParams) ?>"
                       class="px-3 py-1 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Successiva
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showDeleteModal = false"></div>

            <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Conferma eliminazione</h3>
                    </div>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    Sei sicuro di voler eliminare l'articolo "<span x-text="deleteTitle" class="font-medium text-slate-700 dark:text-slate-300"></span>"? Questa azione non può essere annullata.
                </p>

                <form :action="deleteUrl" method="POST">
                    <?= csrf_field() ?>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition-colors">
                            Elimina
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Publish to WordPress Modal -->
    <div x-show="publishModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="publishModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="publishModal = false"></div>

            <div x-show="publishModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Pubblica su WordPress</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    Stai per pubblicare: "<span x-text="publishTitle" class="font-medium"></span>"
                </p>

                <form @submit.prevent="submitPublish()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sito WordPress *</label>
                            <select x-model="publishSiteId" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">Seleziona un sito...</option>
                                <?php if (!empty($wpSites)): ?>
                                    <?php foreach ($wpSites as $site): ?>
                                    <option value="<?= $site['id'] ?>"><?= e($site['name']) ?> (<?= e($site['url']) ?>)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($wpSites)): ?>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                <a href="<?= url($baseUrl . '/wordpress') ?>" class="underline">Configura un sito WordPress</a> per pubblicare.
                            </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status pubblicazione</label>
                            <select x-model="publishStatus" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="draft">Bozza</option>
                                <option value="publish">Pubblicato</option>
                                <option value="pending">In attesa di revisione</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="publishModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            Annulla
                        </button>
                        <button type="submit"
                                :disabled="publishing || !publishSiteId"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <svg x-show="publishing" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="publishing ? 'Pubblicazione...' : 'Pubblica'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function articlesManager() {
    const baseUrl = '<?= rtrim(url(''), '/') ?>';
    const articlesUrl = '<?= url('/ai-content/articles') ?>';

    return {
        // Delete modal
        showDeleteModal: false,
        deleteId: null,
        deleteTitle: '',
        deleteUrl: '',

        // Publish modal
        publishModal: false,
        publishId: null,
        publishTitle: '',
        publishSiteId: '',
        publishStatus: 'draft',
        publishing: false,

        // Regenerate state
        regenerating: null,

        confirmDelete(id, title) {
            this.deleteId = id;
            this.deleteTitle = title;
            this.deleteUrl = articlesUrl + '/' + id + '/delete';
            this.showDeleteModal = true;
        },

        showPublishModal(id, title) {
            this.publishId = id;
            this.publishTitle = title;
            this.publishSiteId = '';
            this.publishStatus = 'draft';
            this.publishModal = true;
        },

        async submitPublish() {
            if (!this.publishSiteId || this.publishing) return;

            this.publishing = true;

            try {
                const response = await fetch(articlesUrl + '/' + this.publishId + '/publish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        wp_site_id: this.publishSiteId,
                        status: this.publishStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Articolo pubblicato!', type: 'success' }
                    }));
                    this.publishModal = false;
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante la pubblicazione', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.publishing = false;
            }
        },

        async regenerateArticle(id) {
            if (this.regenerating) return;

            if (!confirm('Rigenerare l\'articolo? Verranno consumati 10 crediti.')) {
                return;
            }

            this.regenerating = id;

            try {
                const response = await fetch(articlesUrl + '/' + id + '/regenerate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Rigenerazione avviata!', type: 'success' }
                    }));

                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante la rigenerazione', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.regenerating = null;
            }
        }
    }
}
</script>
