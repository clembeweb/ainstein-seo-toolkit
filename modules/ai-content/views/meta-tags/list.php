<?php
/**
 * Lista Meta Tags con filtri, colonne ordinabili e generazione inline SSE
 */

include __DIR__ . '/../../../../shared/views/components/table-helpers.php';

$currentSort = $filters['sort'] ?? 'created_at';
$currentDir = $filters['dir'] ?? 'desc';
$baseUrl = url("/ai-content/projects/{$project['id']}/meta-tags/list");
$paginationFilters = array_filter([
    'status' => $filters['status'] ?? '',
    'q' => $filters['search'] ?? '',
    'sort' => $filters['sort'] ?? '',
    'dir' => $filters['dir'] ?? '',
], fn($v) => $v !== '' && $v !== null);
$sortFilters = array_filter([
    'status' => $filters['status'] ?? '',
    'q' => $filters['search'] ?? '',
], fn($v) => $v !== '' && $v !== null);
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
                    :disabled="loading || generating"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Scrape (<?= $stats['pending'] ?>)
            </button>
            <button type="button"
                    @click="startGenerate()"
                    :disabled="loading || generating"
                    x-show="!generating"
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Genera AI (<?= $stats['scraped'] ?>)
            </button>
            <!-- Pulsante annulla durante generazione -->
            <button type="button"
                    @click="cancelGenerate()"
                    x-show="generating" x-cloak
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Annulla generazione
            </button>
        </div>
    </div>

    <!-- Progress bar generazione inline (non modal) -->
    <div x-show="generating" x-cloak x-transition
         class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                    Generazione AI in corso...
                </span>
            </div>
            <span class="text-sm text-primary-600 dark:text-primary-400">
                <span x-text="genCompleted"></span>/<span x-text="genTotal"></span>
                <template x-if="genFailed > 0">
                    <span class="text-red-500 ml-2">(<span x-text="genFailed"></span> errori)</span>
                </template>
            </span>
        </div>
        <div class="w-full bg-primary-200 dark:bg-primary-800 rounded-full h-2">
            <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                 :style="'width: ' + genPercent + '%'"></div>
        </div>
        <p class="text-xs text-primary-500 dark:text-primary-400 mt-1 truncate" x-text="genCurrentUrl"></p>
    </div>

    <!-- Filtri -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
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
            <?php if (!empty($filters['sort'])): ?>
            <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            <input type="hidden" name="dir" value="<?= e($filters['dir'] ?? 'desc') ?>">
            <?php endif; ?>
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
    <?= \Core\View::partial('components/table-bulk-bar', [
        'actions' => [
            ['label' => 'Approva', 'action' => 'bulkApprove()', 'color' => 'emerald'],
            ['label' => 'Elimina', 'action' => 'bulkDelete()', 'color' => 'red'],
        ],
    ]) ?>

    <!-- Table -->
    <?php if (empty($metaTags)): ?>
    <?= \Core\View::partial('components/table-empty-state', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
        'heading' => 'Nessun meta tag',
        'message' => 'Inizia importando le URL delle pagine da ottimizzare',
        'ctaText' => 'Importa URL',
        'ctaUrl' => url("/ai-content/projects/{$project['id']}/meta-tags/import"),
    ]) ?>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <?= table_checkbox_header() ?>
                        <?= table_sort_header('URL / Titolo', 'url', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_sort_header('Meta Title', 'generated_title', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_sort_header('Meta Description', 'generated_desc', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_sort_header('Stato', 'status', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_header('Azioni', 'right') ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($metaTags as $item): ?>
                    <tr class="transition-colors duration-300"
                        :class="{
                            'bg-amber-50/50 dark:bg-amber-900/10': rowStates[<?= $item['id'] ?>] === 'queued',
                            'bg-primary-50/50 dark:bg-primary-900/10': rowStates[<?= $item['id'] ?>] === 'processing',
                            'bg-emerald-50/50 dark:bg-emerald-900/10': rowStates[<?= $item['id'] ?>] === 'done',
                            'bg-red-50/50 dark:bg-red-900/10': rowStates[<?= $item['id'] ?>] === 'error',
                            'hover:bg-slate-50 dark:hover:bg-slate-700/50': !rowStates[<?= $item['id'] ?>]
                        }"
                        id="row-<?= $item['id'] ?>">
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
                        <!-- Meta Title - aggiornato inline via SSE -->
                        <td class="px-4 py-3" id="title-<?= $item['id'] ?>">
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'processing'">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-primary-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-primary-600 dark:text-primary-400 italic">Generazione in corso...</span>
                                </div>
                            </template>
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'queued'">
                                <span class="text-sm text-amber-600 dark:text-amber-400 italic">In coda...</span>
                            </template>
                            <template x-if="generatedData[<?= $item['id'] ?>]?.title">
                                <div class="max-w-xs">
                                    <p class="text-sm text-slate-900 dark:text-white truncate" x-text="generatedData[<?= $item['id'] ?>].title"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><span x-text="generatedData[<?= $item['id'] ?>].title_length"></span> caratteri</p>
                                </div>
                            </template>
                            <template x-if="!rowStates[<?= $item['id'] ?>] && !generatedData[<?= $item['id'] ?>]?.title">
                                <?php if ($item['generated_title']): ?>
                                <div class="max-w-xs">
                                    <p class="text-sm text-slate-900 dark:text-white truncate"><?= e($item['generated_title']) ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= mb_strlen($item['generated_title']) ?> caratteri</p>
                                </div>
                                <?php else: ?>
                                <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </template>
                        </td>
                        <!-- Meta Description - aggiornato inline via SSE -->
                        <td class="px-4 py-3" id="desc-<?= $item['id'] ?>">
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'processing'">
                                <span class="text-sm text-primary-400 italic">...</span>
                            </template>
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'queued'">
                                <span class="text-sm text-amber-400 italic">...</span>
                            </template>
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'error'">
                                <span class="text-sm text-red-500" x-text="rowErrors[<?= $item['id'] ?>] || 'Errore'"></span>
                            </template>
                            <template x-if="generatedData[<?= $item['id'] ?>]?.desc">
                                <div class="max-w-sm">
                                    <p class="text-sm text-slate-900 dark:text-white line-clamp-2" x-text="generatedData[<?= $item['id'] ?>].desc"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><span x-text="generatedData[<?= $item['id'] ?>].desc_length"></span> caratteri</p>
                                </div>
                            </template>
                            <template x-if="!rowStates[<?= $item['id'] ?>] && !generatedData[<?= $item['id'] ?>]?.desc">
                                <?php if ($item['generated_desc']): ?>
                                <div class="max-w-sm">
                                    <p class="text-sm text-slate-900 dark:text-white line-clamp-2"><?= e($item['generated_desc']) ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= mb_strlen($item['generated_desc']) ?> caratteri</p>
                                </div>
                                <?php else: ?>
                                <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </template>
                        </td>
                        <!-- Stato -->
                        <td class="px-4 py-3" id="status-<?= $item['id'] ?>">
                            <template x-if="generatedData[<?= $item['id'] ?>]?.status">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                      :class="{
                                          'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300': generatedData[<?= $item['id'] ?>].status === 'generated',
                                          'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300': generatedData[<?= $item['id'] ?>].status === 'error'
                                      }"
                                      x-text="generatedData[<?= $item['id'] ?>].status === 'generated' ? 'Generated' : 'Error'"></span>
                            </template>
                            <template x-if="!generatedData[<?= $item['id'] ?>]?.status">
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
                            </template>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$item['id']}") ?>"
                                   class="p-2 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Modifica">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"
                                   class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Apri URL">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                                <?php if ($item['status'] === 'generated' || $item['status'] === 'error'): ?>
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
                                <button type="button"
                                        @click="publishOne(<?= $item['id'] ?>)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-green-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Pubblica su WP">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
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
        <?= \Core\View::partial('components/table-pagination', [
            'pagination' => $pagination,
            'baseUrl' => $baseUrl,
            'filters' => $paginationFilters,
        ]) ?>
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

        // Generazione SSE
        generating: false,
        generateJobId: null,
        genTotal: 0,
        genCompleted: 0,
        genFailed: 0,
        genPercent: 0,
        genCurrentUrl: '',
        eventSource: null,
        rowStates: {},      // {id: 'queued'|'processing'|'done'|'error'}
        rowErrors: {},      // {id: 'error message'}
        generatedData: {},  // {id: {title, desc, title_length, desc_length, status}}

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = <?= json_encode(array_map(fn($i) => (string)$i['id'], $metaTags)) ?>;
            } else {
                this.selectedIds = [];
            }
        },

        // =====================
        // GENERAZIONE SSE
        // =====================

        async startGenerate() {
            this.generating = true;
            this.genCompleted = 0;
            this.genFailed = 0;
            this.genPercent = 0;
            this.genCurrentUrl = 'Avvio...';
            this.rowStates = {};
            this.rowErrors = {};
            this.generatedData = {};

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/start-generate-job") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await resp.json();

                if (!data.success) {
                    window.ainstein.alert(data.error || 'Errore avvio generazione', 'error');
                    this.generating = false;
                    return;
                }

                this.generateJobId = data.job_id;
                this.genTotal = data.items_queued;

                // Marca le righe in coda
                if (data.item_ids) {
                    data.item_ids.forEach(id => {
                        this.rowStates[id] = 'queued';
                    });
                }

                this.connectGenerateSSE();

            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
                this.generating = false;
            }
        },

        connectGenerateSSE() {
            const url = `<?= url("/ai-content/projects/{$project['id']}/meta-tags/generate-stream") ?>?job_id=${this.generateJobId}`;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('started', (e) => {
                const data = JSON.parse(e.data);
                this.genTotal = data.total_items;
            });

            this.eventSource.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.genCurrentUrl = data.current_url;
                this.genPercent = data.percent;

                // Marca la riga corrente come "processing"
                if (data.current_id) {
                    this.rowStates[data.current_id] = 'processing';
                }
            });

            this.eventSource.addEventListener('item_completed', (e) => {
                const data = JSON.parse(e.data);
                this.genCompleted++;
                this.genPercent = this.genTotal > 0 ? Math.round((this.genCompleted / this.genTotal) * 100) : 0;

                // Aggiorna riga inline
                this.rowStates[data.id] = 'done';
                this.generatedData[data.id] = {
                    title: data.generated_title,
                    desc: data.generated_desc,
                    title_length: data.title_length,
                    desc_length: data.desc_length,
                    status: 'generated'
                };

                // Flash verde poi rimuovi lo stato dopo 3 secondi
                setTimeout(() => {
                    if (this.rowStates[data.id] === 'done') {
                        delete this.rowStates[data.id];
                    }
                }, 3000);
            });

            this.eventSource.addEventListener('item_error', (e) => {
                const data = JSON.parse(e.data);
                this.genFailed++;

                this.rowStates[data.id] = 'error';
                this.rowErrors[data.id] = data.error;
                this.generatedData[data.id] = { status: 'error' };
            });

            this.eventSource.addEventListener('completed', (e) => {
                const data = JSON.parse(e.data);
                this.eventSource.close();
                this.generating = false;
                this.genPercent = 100;
                this.genCurrentUrl = 'Completato!';

                // Pulisci stati "queued" rimasti
                Object.keys(this.rowStates).forEach(id => {
                    if (this.rowStates[id] === 'queued') {
                        delete this.rowStates[id];
                    }
                });
            });

            this.eventSource.addEventListener('cancelled', (e) => {
                this.eventSource.close();
                this.generating = false;
                this.genCurrentUrl = 'Annullato';

                // Pulisci stati "queued" rimasti
                Object.keys(this.rowStates).forEach(id => {
                    if (this.rowStates[id] === 'queued' || this.rowStates[id] === 'processing') {
                        delete this.rowStates[id];
                    }
                });
            });

            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.startGeneratePolling();
            };
        },

        async startGeneratePolling() {
            if (!this.generating) return;

            try {
                const resp = await fetch(`<?= url("/ai-content/projects/{$project['id']}/meta-tags/generate-job-status") ?>?job_id=${this.generateJobId}`);
                const data = await resp.json();

                if (data.success && data.job) {
                    this.genCompleted = data.job.items_completed;
                    this.genFailed = data.job.items_failed;
                    this.genPercent = data.job.progress;

                    if (data.job.status === 'completed' || data.job.status === 'error' || data.job.status === 'cancelled') {
                        this.generating = false;
                        this.genCurrentUrl = data.job.status === 'completed' ? 'Completato!' : 'Terminato';
                        return;
                    }
                }

                setTimeout(() => this.startGeneratePolling(), 2000);
            } catch (error) {
                setTimeout(() => this.startGeneratePolling(), 3000);
            }
        },

        async cancelGenerate() {
            if (!this.generateJobId) return;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('job_id', this.generateJobId);

                await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/cancel-generate-job") ?>', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                // SSE gestira l'evento cancelled
            }
        },

        // =====================
        // AZIONI ESISTENTI
        // =====================

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
                    window.ainstein.alert('Errore: ' + (data.error || 'Eliminazione fallita'), 'error');
                }
            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
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
                if (data.success) {
                    window.ainstein.toast(data.message, 'success');
                    location.reload();
                } else {
                    window.ainstein.alert('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
            }
            this.loading = false;
        },

        async approveOne(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url("/ai-content/projects/{$project['id']}/meta-tags") ?>/${id}/approve`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    window.ainstein.alert('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
            }
        },

        async publishOne(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url("/ai-content/projects/{$project['id']}/meta-tags") ?>/${id}/publish`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    window.ainstein.toast(data.message, 'success');
                    location.reload();
                } else {
                    window.ainstein.alert('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
            }
        },

        async bulkApprove() {
            try {
                await window.ainstein.confirm(`Approvare ${this.selectedIds.length} meta tag?`, {destructive: false});
            } catch (e) { return; }

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedIds.forEach(id => formData.append('ids[]', id));

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/bulk-approve") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    window.ainstein.toast(data.message, 'success');
                    location.reload();
                } else {
                    window.ainstein.alert('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
            }
        },

        async bulkDelete() {
            try {
                await window.ainstein.confirm(`Eliminare ${this.selectedIds.length} meta tag?`, {destructive: true});
            } catch (e) { return; }

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedIds.forEach(id => formData.append('ids[]', id));

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/bulk-delete") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    window.ainstein.toast(data.message, 'success');
                    location.reload();
                } else {
                    window.ainstein.alert('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                window.ainstein.alert('Errore di connessione', 'error');
            }
        }
    }
}
</script>
