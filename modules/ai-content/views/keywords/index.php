<?php if (!empty($projectId) && !empty($project)): ?>
<?php $currentPage = 'keywords'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>
<?php endif; ?>

<div class="space-y-6" x-data="keywordsManager()">
    <?php if (!empty($projectId) && !empty($project)): ?>
    <!-- Header (project view) -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Keywords</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= count($keywords) ?> keyword nel progetto</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button @click="showAddModal = true" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Keyword
            </button>
        </div>
    </div>
    <?php elseif (empty($projectId) || empty($project)): ?>
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
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Keywords</span>
            </li>
        </ol>
    </nav>

    <!-- Header (global view) -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Keywords</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci le keyword per la generazione articoli</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button @click="showAddModal = true" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Keyword
            </button>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($keywords)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessuna keyword</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            Aggiungi la tua prima keyword per iniziare ad analizzare la SERP e generare articoli ottimizzati.
        </p>
        <div class="mt-6">
            <button @click="showAddModal = true" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Keyword
            </button>
        </div>
    </div>
    <?php else: ?>
    <!-- Keywords Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keyword</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Lingua</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($keywords as $kw): ?>
                    <?php
                        // Determine status
                        $status = 'new';
                        $statusLabel = 'Nuova';
                        $statusClass = 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';

                        if ($kw['serp_count'] > 0) {
                            $status = 'serp_ready';
                            $statusLabel = 'SERP Pronta';
                            $statusClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                        }
                        if (!empty($kw['has_brief'])) {
                            $status = 'brief_ready';
                            $statusLabel = 'Brief Pronto';
                            $statusClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300';
                        }
                        if (!empty($kw['article_status']) && $kw['article_status'] === 'ready') {
                            $status = 'article_ready';
                            $statusLabel = 'Articolo Pronto';
                            $statusClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                        }
                        if (!empty($kw['article_status']) && $kw['article_status'] === 'published') {
                            $status = 'published';
                            $statusLabel = 'Pubblicato';
                            $statusClass = 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                        }
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors"
                        onclick="window.location.href='<?= url(!empty($projectId) ? '/ai-content/projects/' . $projectId . '/keywords/' . $kw['id'] . '/wizard' : '/ai-content/keywords/' . $kw['id'] . '/wizard') ?>'">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white"><?= e($kw['keyword']) ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($kw['location']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                <?= strtoupper($kw['language']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y', strtotime($kw['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Open Wizard -->
                                <a href="<?= url(!empty($projectId) ? '/ai-content/projects/' . $projectId . '/keywords/' . $kw['id'] . '/wizard' : '/ai-content/keywords/' . $kw['id'] . '/wizard') ?>"
                                   class="p-2 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Apri Wizard">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </a>

                                <!-- Delete -->
                                <button @click="confirmDelete(<?= $kw['id'] ?>, '<?= e(addslashes($kw['keyword'])) ?>')"
                                        class="p-2 rounded-lg text-slate-500 hover:text-red-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Elimina">
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

        <!-- Pagination -->
        <?php if ($pagination['last_page'] > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Pagina <?= $pagination['current_page'] ?> di <?= $pagination['last_page'] ?>
                    (<?= $pagination['total'] ?> keywords)
                </p>
                <div class="flex gap-2">
                    <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= url('/ai-content/keywords?page=' . ($pagination['current_page'] - 1)) ?>" class="px-3 py-1 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                        Precedente
                    </a>
                    <?php endif; ?>
                    <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="<?= url('/ai-content/keywords?page=' . ($pagination['current_page'] + 1)) ?>" class="px-3 py-1 rounded-lg border border-slate-300 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                        Successiva
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Add Keyword Modal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showAddModal = false"></div>

            <div x-show="showAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Aggiungi Keyword</h3>

                <form action="<?= url(!empty($projectId) ? '/ai-content/projects/' . $projectId . '/keywords' : '/ai-content/keywords') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Keyword *</label>
                            <input type="text" name="keyword" required placeholder="es. come fare SEO" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lingua</label>
                                <select name="language" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="it">Italiano</option>
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                    <option value="de">Deutsch</option>
                                    <option value="fr">Français</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Location</label>
                                <select name="location" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="Italy">Italy</option>
                                    <option value="United States">United States</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Spain">Spain</option>
                                    <option value="Germany">Germany</option>
                                    <option value="France">France</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                            Aggiungi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showDeleteModal" class="fixed inset-0 bg-slate-900/50" @click="showDeleteModal = false"></div>

            <div x-show="showDeleteModal" x-transition class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sei sicuro di voler eliminare la keyword "<span x-text="deleteKeyword" class="font-medium"></span>"?
                </p>

                <form :action="deleteUrl" method="POST" class="mt-6">
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
</div>

<script>
function keywordsManager() {
    return {
        showAddModal: false,
        showDeleteModal: false,
        deleteId: null,
        deleteKeyword: '',
        deleteUrl: '',

        confirmDelete(id, keyword) {
            this.deleteId = id;
            this.deleteKeyword = keyword;
            this.deleteUrl = '<?= url(!empty($projectId) ? '/ai-content/projects/' . $projectId . '/keywords' : '/ai-content/keywords') ?>/' + id + '/delete';
            this.showDeleteModal = true;
        }
    }
}
</script>
