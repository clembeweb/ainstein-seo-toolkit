<?php
/**
 * Lista Meta Tags con filtri e azioni bulk
 */
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="metaTagsList()">
    <!-- Header con azioni -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Meta Tags</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?= $pagination['total'] ?> URL totali
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="runScrape()"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Scrape (<?= $stats['pending'] ?>)
            </button>
            <button type="button"
                    @click="runGenerate()"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Genera (<?= $stats['scraped'] ?>)
            </button>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="q"
                       value="<?= e($filters['search'] ?? '') ?>"
                       placeholder="Cerca URL o titolo..."
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <select name="status" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tutti gli stati</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="scraped" <?= ($filters['status'] ?? '') === 'scraped' ? 'selected' : '' ?>>Scrappate</option>
                    <option value="generated" <?= ($filters['status'] ?? '') === 'generated' ? 'selected' : '' ?>>Generate</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approvate</option>
                    <option value="published" <?= ($filters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Pubblicate</option>
                    <option value="error" <?= ($filters['status'] ?? '') === 'error' ? 'selected' : '' ?>>Errori</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                Filtra
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
            <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-show="selectedIds.length > 0" x-cloak
         class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                <span x-text="selectedIds.length"></span> selezionati
            </span>
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="bulkApprove()"
                        class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                    Approva
                </button>
                <button type="button"
                        @click="bulkDelete()"
                        class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">
                    Elimina
                </button>
                <button type="button" @click="selectedIds = []" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">
                    Deseleziona
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <?php if (empty($metaTags)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
        </svg>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun meta tag</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            Inizia importando le URL delle pagine da ottimizzare
        </p>
        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/import") ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
            Importa URL
        </a>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">URL / Titolo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Meta Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Meta Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($metaTags as $item): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="<?= $item['id'] ?>" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-xs">
                                <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$item['id']}") ?>"
                                   class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block">
                                    <?= e($item['original_title'] ?: 'Senza titolo') ?>
                                </a>
                                <a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"
                                   class="text-xs text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 truncate block">
                                    <?= e($item['url']) ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($item['generated_title']): ?>
                            <div class="max-w-xs">
                                <p class="text-sm text-slate-900 dark:text-white truncate"><?= e($item['generated_title']) ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= strlen($item['generated_title']) ?> caratteri</p>
                            </div>
                            <?php else: ?>
                            <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($item['generated_desc']): ?>
                            <div class="max-w-sm">
                                <p class="text-sm text-slate-900 dark:text-white line-clamp-2"><?= e($item['generated_desc']) ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?= strlen($item['generated_desc']) ?> caratteri</p>
                            </div>
                            <?php else: ?>
                            <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                <?php
                                switch ($item['status']) {
                                    case 'pending':
                                        echo 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';
                                        break;
                                    case 'scraped':
                                        echo 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                                        break;
                                    case 'generated':
                                        echo 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                                        break;
                                    case 'approved':
                                        echo 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                                        break;
                                    case 'published':
                                        echo 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300';
                                        break;
                                    case 'error':
                                        echo 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                                        break;
                                }
                                ?>">
                                <?= ucfirst($item['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Edit/Preview -->
                                <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$item['id']}") ?>"
                                   class="p-2 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Modifica">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <!-- View URL -->
                                <a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"
                                   class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Apri URL">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                                <?php if ($item['status'] === 'generated' || $item['status'] === 'error'): ?>
                                <!-- Approve -->
                                <button type="button"
                                        @click="approveOne(<?= $item['id'] ?>)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Approva">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php if ($item['status'] === 'approved' && $item['wp_post_id']): ?>
                                <!-- Publish -->
                                <button type="button"
                                        @click="publishOne(<?= $item['id'] ?>)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-green-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Pubblica su WP">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <!-- Delete -->
                                <button type="button"
                                        @click="confirmDelete(<?= $item['id'] ?>, '<?= e(addslashes($item['url'])) ?>')"
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
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Mostrando <?= $pagination['from'] ?> - <?= $pagination['to'] ?> di <?= $pagination['total'] ?>
            </p>
            <div class="flex items-center gap-2">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=<?= $pagination['current_page'] - 1 ?><?= !empty($filters['status']) ? '&status=' . e($filters['status']) : '' ?><?= !empty($filters['search']) ? '&q=' . e($filters['search']) : '' ?>"
                   class="px-3 py-1 rounded border border-slate-300 dark:border-slate-600 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700">
                    Precedente
                </a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?page=<?= $pagination['current_page'] + 1 ?><?= !empty($filters['status']) ? '&status=' . e($filters['status']) : '' ?><?= !empty($filters['search']) ? '&q=' . e($filters['search']) : '' ?>"
                   class="px-3 py-1 rounded border border-slate-300 dark:border-slate-600 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700">
                    Successivo
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-slate-900/50"
                 @click="showDeleteModal = false"></div>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sei sicuro di voler eliminare questo meta tag?
                </p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 truncate" x-text="deleteUrl"></p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            @click="showDeleteModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </button>
                    <button type="button"
                            @click="deleteOne()"
                            :disabled="deleting"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                        <span x-show="!deleting">Elimina</span>
                        <span x-show="deleting">Eliminazione...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function metaTagsList() {
    return {
        selectedIds: [],
        loading: false,
        showDeleteModal: false,
        deleteId: null,
        deleteUrl: '',
        deleting: false,

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = <?= json_encode(array_map(fn($i) => (string)$i['id'], $metaTags)) ?>;
            } else {
                this.selectedIds = [];
            }
        },

        confirmDelete(id, url) {
            this.deleteId = id;
            this.deleteUrl = url;
            this.showDeleteModal = true;
        },

        async deleteOne() {
            if (!this.deleteId) return;

            this.deleting = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url("/ai-content/projects/{$project['id']}/meta-tags") ?>/${this.deleteId}/delete`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    this.showDeleteModal = false;
                    location.reload();
                } else {
                    alert('Errore: ' + (data.error || 'Eliminazione fallita'));
                }
            } catch (error) {
                alert('Errore di connessione');
            }
            this.deleting = false;
        },

        async runScrape() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('batch_size', '10');

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/scrape") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success ? data.message : 'Errore: ' + data.error);
                if (data.success) location.reload();
            } catch (error) {
                alert('Errore di connessione');
            }
            this.loading = false;
        },

        async runGenerate() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('batch_size', '10');

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/generate") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success ? data.message : 'Errore: ' + data.error);
                if (data.success) location.reload();
            } catch (error) {
                alert('Errore di connessione');
            }
            this.loading = false;
        },

        async approveOne(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`/ai-content/projects/<?= $project['id'] ?>/meta-tags/${id}/approve`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore: ' + data.error);
                }
            } catch (error) {
                alert('Errore di connessione');
            }
        },

        async publishOne(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`/ai-content/projects/<?= $project['id'] ?>/meta-tags/${id}/publish`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success ? data.message : 'Errore: ' + data.error);
                if (data.success) location.reload();
            } catch (error) {
                alert('Errore di connessione');
            }
        },

        async bulkApprove() {
            if (!confirm(`Approvare ${this.selectedIds.length} meta tag?`)) return;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedIds.forEach(id => formData.append('ids[]', id));

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/bulk-approve") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success ? data.message : 'Errore: ' + data.error);
                if (data.success) location.reload();
            } catch (error) {
                alert('Errore di connessione');
            }
        },

        async bulkDelete() {
            if (!confirm(`Eliminare ${this.selectedIds.length} meta tag?`)) return;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedIds.forEach(id => formData.append('ids[]', id));

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/bulk-delete") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success ? data.message : 'Errore: ' + data.error);
                if (data.success) location.reload();
            } catch (error) {
                alert('Errore di connessione');
            }
        }
    }
}
</script>
