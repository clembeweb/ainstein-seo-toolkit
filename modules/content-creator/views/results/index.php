<?php
/**
 * Content Creator - Lista risultati URL con filtri, ordinamento, bulk actions, SSE progress
 *
 * Variabili disponibili:
 * - $project: array dati progetto
 * - $urls: array paginato di URL
 * - $pagination: array con current_page, last_page, total, per_page, from, to
 * - $stats: array con total, pending, scraped, generated, approved, rejected, published, errors
 * - $filters: array con status, search, sort, dir
 * - $currentPage: 'results'
 */

$currentSort = $filters['sort'] ?? 'created_at';
$currentDir = $filters['dir'] ?? 'desc';

// Helper per generare URL di ordinamento
$sortUrl = function(string $column) use ($currentSort, $currentDir, $filters, $project, $pagination) {
    $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $params = ['sort' => $column, 'dir' => $newDir];
    if (!empty($filters['status'])) $params['status'] = $filters['status'];
    if (!empty($filters['search'])) $params['q'] = $filters['search'];
    if (($pagination['current_page'] ?? 1) > 1) $params['page'] = $pagination['current_page'];
    return url("/content-creator/projects/{$project['id']}/results") . '?' . http_build_query($params);
};

// Helper per icona freccia ordinamento
$sortIcon = function(string $column) use ($currentSort, $currentDir) {
    if ($currentSort !== $column) {
        return '<svg class="w-3 h-3 ml-1 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
    }
    if ($currentDir === 'asc') {
        return '<svg class="w-3 h-3 ml-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>';
    }
    return '<svg class="w-3 h-3 ml-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
};

// Status configuration
$statusColors = [
    'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    'scraped' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'generated' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    'approved' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
    'rejected' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
    'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];
$statusLabels = [
    'pending' => 'In Attesa',
    'scraped' => 'Scrappata',
    'generated' => 'Generata',
    'approved' => 'Approvata',
    'rejected' => 'Rifiutata',
    'published' => 'Pubblicata',
    'error' => 'Errore',
];

// Status tabs configuration
$statusTabs = [
    '' => ['label' => 'Tutti', 'count' => $stats['total']],
    'pending' => ['label' => 'In Attesa', 'count' => $stats['pending']],
    'scraped' => ['label' => 'Scrappate', 'count' => $stats['scraped']],
    'generated' => ['label' => 'Generate', 'count' => $stats['generated']],
    'approved' => ['label' => 'Approvate', 'count' => $stats['approved']],
    'rejected' => ['label' => 'Rifiutate', 'count' => $stats['rejected']],
    'published' => ['label' => 'Pubblicate', 'count' => $stats['published']],
    'error' => ['label' => 'Errori', 'count' => $stats['errors']],
];
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="resultsManager()">
    <!-- Header con azioni -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Risultati</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?= number_format($pagination['total']) ?> URL totali
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Scrape button -->
            <button type="button"
                    @click="startScrape()"
                    :disabled="scraping || generating"
                    x-show="!scraping"
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    <?= $stats['pending'] == 0 ? 'disabled' : '' ?>>
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                Scrape (<?= $stats['pending'] ?>)
            </button>
            <!-- Cancel scrape -->
            <button type="button"
                    @click="cancelScrape()"
                    x-show="scraping" x-cloak
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Annulla scraping
            </button>

            <!-- Generate button -->
            <button type="button"
                    @click="startGenerate()"
                    :disabled="scraping || generating"
                    x-show="!generating"
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    <?= ($stats['pending'] + $stats['scraped']) == 0 ? 'disabled' : '' ?>>
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Genera Contenuto (<?= $stats['pending'] + $stats['scraped'] ?>)
            </button>
            <!-- Cancel generate -->
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

    <!-- SSE Progress bar - Scraping -->
    <div x-show="scraping" x-cloak x-transition
         class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                    Scraping in corso...
                </span>
            </div>
            <span class="text-sm text-blue-600 dark:text-blue-400">
                <span x-text="scrapeCompleted"></span>/<span x-text="scrapeTotal"></span>
                <template x-if="scrapeFailed > 0">
                    <span class="text-red-500 ml-2">(<span x-text="scrapeFailed"></span> errori)</span>
                </template>
            </span>
        </div>
        <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                 :style="'width: ' + scrapePercent + '%'"></div>
        </div>
        <p class="text-xs text-blue-500 dark:text-blue-400 mt-1 truncate" x-text="scrapeCurrentUrl"></p>
    </div>

    <!-- SSE Progress bar - Generazione AI -->
    <div x-show="generating" x-cloak x-transition
         class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-purple-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-purple-700 dark:text-purple-300">
                    Generazione AI in corso...
                </span>
            </div>
            <span class="text-sm text-purple-600 dark:text-purple-400">
                <span x-text="genCompleted"></span>/<span x-text="genTotal"></span>
                <template x-if="genFailed > 0">
                    <span class="text-red-500 ml-2">(<span x-text="genFailed"></span> errori)</span>
                </template>
            </span>
        </div>
        <div class="w-full bg-purple-200 dark:bg-purple-800 rounded-full h-2">
            <div class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                 :style="'width: ' + genPercent + '%'"></div>
        </div>
        <p class="text-xs text-purple-500 dark:text-purple-400 mt-1 truncate" x-text="genCurrentUrl"></p>
    </div>

    <!-- Status filter tabs -->
    <div class="flex flex-wrap gap-2">
        <?php foreach ($statusTabs as $statusKey => $tab):
            $isActive = ($filters['status'] ?? '') === $statusKey;
            $tabParams = [];
            if ($statusKey !== '') $tabParams['status'] = $statusKey;
            if (!empty($filters['search'])) $tabParams['q'] = $filters['search'];
            if (!empty($filters['sort'])) $tabParams['sort'] = $filters['sort'];
            if (!empty($filters['dir'])) $tabParams['dir'] = $filters['dir'];
            $tabUrl = url("/content-creator/projects/{$project['id']}/results") . ($tabParams ? '?' . http_build_query($tabParams) : '');
        ?>
        <a href="<?= $tabUrl ?>"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                  <?= $isActive
                      ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600' ?>">
            <?= $tab['label'] ?>
            <span class="text-xs px-1.5 py-0.5 rounded-full <?= $isActive ? 'bg-primary-200 dark:bg-primary-800' : 'bg-slate-200 dark:bg-slate-600' ?>"><?= number_format($tab['count']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search bar -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="q"
                       value="<?= e($filters['search'] ?? '') ?>"
                       placeholder="Cerca URL, titolo o keyword..."
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
            </div>
            <?php if (!empty($filters['status'])): ?>
            <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
            <?php endif; ?>
            <?php if (!empty($filters['sort'])): ?>
            <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
            <input type="hidden" name="dir" value="<?= e($filters['dir'] ?? 'desc') ?>">
            <?php endif; ?>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Cerca
            </button>
            <?php if (!empty($filters['search'])): ?>
            <a href="<?= url("/content-creator/projects/{$project['id']}/results") . (!empty($filters['status']) ? '?status=' . e($filters['status']) : '') ?>"
               class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                Cancella ricerca
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
                        class="px-3 py-1.5 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Approva selezionati
                </button>
                <button type="button"
                        @click="bulkDelete()"
                        class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Elimina selezionati
                </button>
                <button type="button" @click="selectedIds = []; selectAll = false" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">
                    Deseleziona
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <?php if (empty($urls)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
        <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun risultato trovato</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
            Prova a modificare i filtri di ricerca.
            <?php else: ?>
            Inizia importando le URL delle pagine da ottimizzare.
            <?php endif; ?>
        </p>
        <?php if (empty($filters['search']) && empty($filters['status'])): ?>
        <a href="<?= url("/content-creator/projects/{$project['id']}/import") ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Importa URL
        </a>
        <?php else: ?>
        <a href="<?= url("/content-creator/projects/{$project['id']}/results") ?>"
           class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
            Reset filtri
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox"
                                   x-model="selectAll"
                                   @change="toggleSelectAll()"
                                   class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="<?= $sortUrl('url') ?>" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200">
                                URL <?= $sortIcon('url') ?>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="<?= $sortUrl('keyword') ?>" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200">
                                Keyword <?= $sortIcon('keyword') ?>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="<?= $sortUrl('ai_h1') ?>" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200">
                                Contenuto <?= $sortIcon('ai_h1') ?>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="<?= $sortUrl('ai_word_count') ?>" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200">
                                Parole <?= $sortIcon('ai_word_count') ?>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="<?= $sortUrl('status') ?>" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200">
                                Stato <?= $sortIcon('status') ?>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <a href="<?= $sortUrl('created_at') ?>" class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-200">
                                Data <?= $sortIcon('created_at') ?>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($urls as $item):
                        $itemStatus = $item['status'] ?? 'pending';
                        $itemStatusColor = $statusColors[$itemStatus] ?? $statusColors['pending'];
                        $itemStatusLabel = $statusLabels[$itemStatus] ?? $itemStatus;
                        $wordCount = (int) ($item['ai_word_count'] ?? 0);
                    ?>
                    <tr class="transition-colors duration-300 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                        :class="{
                            'bg-amber-50/50 dark:bg-amber-900/10': rowStates[<?= $item['id'] ?>] === 'queued',
                            'bg-blue-50/50 dark:bg-blue-900/10': rowStates[<?= $item['id'] ?>] === 'scraping',
                            'bg-purple-50/50 dark:bg-purple-900/10': rowStates[<?= $item['id'] ?>] === 'generating',
                            'bg-emerald-50/50 dark:bg-emerald-900/10': rowStates[<?= $item['id'] ?>] === 'done',
                            'bg-red-50/50 dark:bg-red-900/10': rowStates[<?= $item['id'] ?>] === 'error'
                        }"
                        id="row-<?= $item['id'] ?>">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="<?= $item['id'] ?>" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-xs">
                                <a href="<?= url("/content-creator/projects/{$project['id']}/urls/{$item['id']}") ?>"
                                   class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block"
                                   title="<?= e($item['url']) ?>">
                                    <?= e(mb_strlen($item['url']) > 60 ? mb_substr($item['url'], 0, 57) . '...' : $item['url']) ?>
                                </a>
                                <?php if (!empty($item['scraped_title'])): ?>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($item['scraped_title']) ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                <?= e($item['keyword'] ?? '-') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3" id="content-<?= $item['id'] ?>">
                            <!-- Stato inline SSE -->
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'generating'">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-purple-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-purple-600 dark:text-purple-400 italic">Generazione in corso...</span>
                                </div>
                            </template>
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'scraping'">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-blue-600 dark:text-blue-400 italic">Scraping...</span>
                                </div>
                            </template>
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'queued'">
                                <span class="text-sm text-amber-600 dark:text-amber-400 italic">In coda...</span>
                            </template>
                            <template x-if="rowStates[<?= $item['id'] ?>] === 'error'">
                                <span class="text-sm text-red-500" x-text="rowErrors[<?= $item['id'] ?>] || 'Errore'"></span>
                            </template>
                            <!-- Risultato inline da SSE -->
                            <template x-if="generatedData[<?= $item['id'] ?>]?.h1">
                                <div class="max-w-xs">
                                    <p class="text-sm text-slate-900 dark:text-white truncate" x-text="generatedData[<?= $item['id'] ?>].h1"></p>
                                </div>
                            </template>
                            <!-- Dati statici (no SSE) -->
                            <template x-if="!rowStates[<?= $item['id'] ?>] && !generatedData[<?= $item['id'] ?>]?.h1">
                                <?php if (!empty($item['ai_h1'])): ?>
                                <div class="max-w-xs">
                                    <p class="text-sm text-slate-900 dark:text-white truncate"><?= e($item['ai_h1']) ?></p>
                                </div>
                                <?php else: ?>
                                <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                <?php endif; ?>
                            </template>
                        </td>
                        <td class="px-4 py-3 text-center" id="words-<?= $item['id'] ?>">
                            <template x-if="generatedData[<?= $item['id'] ?>]?.word_count">
                                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400" x-text="generatedData[<?= $item['id'] ?>].word_count"></span>
                            </template>
                            <template x-if="!generatedData[<?= $item['id'] ?>]?.word_count">
                                <?php if ($wordCount > 0): ?>
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-400"><?= number_format($wordCount) ?></span>
                                <?php else: ?>
                                <span class="text-sm text-slate-400">-</span>
                                <?php endif; ?>
                            </template>
                        </td>
                        <td class="px-4 py-3" id="status-<?= $item['id'] ?>">
                            <!-- Stato aggiornato via SSE -->
                            <template x-if="generatedData[<?= $item['id'] ?>]?.status">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                      :class="{
                                          'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300': generatedData[<?= $item['id'] ?>].status === 'scraped',
                                          'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300': generatedData[<?= $item['id'] ?>].status === 'generated',
                                          'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300': generatedData[<?= $item['id'] ?>].status === 'error'
                                      }"
                                      x-text="generatedData[<?= $item['id'] ?>].status === 'scraped' ? 'Scrappata' : (generatedData[<?= $item['id'] ?>].status === 'generated' ? 'Generata' : 'Errore')"></span>
                            </template>
                            <!-- Stato statico -->
                            <template x-if="!generatedData[<?= $item['id'] ?>]?.status">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $itemStatusColor ?>">
                                    <?= $itemStatusLabel ?>
                                </span>
                            </template>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y', strtotime($item['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Dettagli -->
                                <a href="<?= url("/content-creator/projects/{$project['id']}/urls/{$item['id']}") ?>"
                                   class="p-2 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Dettagli">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <!-- Apri URL esterna -->
                                <a href="<?= e($item['url']) ?>" target="_blank" rel="noopener"
                                   class="p-2 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                   title="Apri URL">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                                <?php if ($itemStatus === 'generated'): ?>
                                <!-- Approva rapido -->
                                <button type="button"
                                        @click="approveOne(<?= $item['id'] ?>)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-teal-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Approva">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <!-- Rifiuta rapido -->
                                <button type="button"
                                        @click="rejectOne(<?= $item['id'] ?>)"
                                        class="p-2 rounded-lg text-slate-500 hover:text-orange-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                        title="Rifiuta">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
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
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Mostrando <?= $pagination['from'] ?> - <?= $pagination['to'] ?> di <?= number_format($pagination['total']) ?> risultati
            </p>
            <div class="flex items-center gap-2">
                <?php
                $paginationParams = [];
                if (!empty($filters['status'])) $paginationParams['status'] = $filters['status'];
                if (!empty($filters['search'])) $paginationParams['q'] = $filters['search'];
                if (!empty($filters['sort'])) $paginationParams['sort'] = $filters['sort'];
                if (!empty($filters['dir'])) $paginationParams['dir'] = $filters['dir'];
                $paginationQuery = $paginationParams ? '&' . http_build_query($paginationParams) : '';
                ?>
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $paginationQuery ?>"
                   class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-600 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Precedente
                </a>
                <?php endif; ?>

                <?php
                // Page numbers
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['last_page'], $pagination['current_page'] + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                <a href="?page=<?= $p ?><?= $paginationQuery ?>"
                   class="px-3 py-1.5 rounded text-sm font-medium transition-colors
                          <?= $p === $pagination['current_page']
                              ? 'bg-primary-600 text-white'
                              : 'border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $paginationQuery ?>"
                   class="px-3 py-1.5 rounded border border-slate-300 dark:border-slate-600 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Successivo
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function resultsManager() {
    return {
        // Bulk selection
        selectedIds: [],
        selectAll: false,

        // Scraping SSE state
        scraping: false,
        scrapeJobId: null,
        scrapeCompleted: 0,
        scrapeTotal: 0,
        scrapeFailed: 0,
        scrapePercent: 0,
        scrapeCurrentUrl: '',

        // Generation SSE state
        generating: false,
        genJobId: null,
        genCompleted: 0,
        genTotal: 0,
        genFailed: 0,
        genPercent: 0,
        genCurrentUrl: '',

        // SSE connection
        eventSource: null,

        // Row inline states
        rowStates: {},       // {id: 'queued'|'scraping'|'generating'|'done'|'error'}
        rowErrors: {},       // {id: 'error message'}
        generatedData: {},   // {id: {h1, word_count, status}}

        // =====================
        // BULK SELECTION
        // =====================

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedIds = <?= json_encode(array_map(fn($i) => (string)$i['id'], $urls)) ?>;
            } else {
                this.selectedIds = [];
            }
        },

        // =====================
        // SCRAPING SSE
        // =====================

        async startScrape() {
            this.scraping = true;
            this.scrapeCompleted = 0;
            this.scrapeFailed = 0;
            this.scrapePercent = 0;
            this.scrapeCurrentUrl = 'Avvio scraping...';
            this.rowStates = {};
            this.rowErrors = {};

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch('<?= url("/content-creator/projects/{$project['id']}/start-scrape-job") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }

                const data = await resp.json();

                if (!data.success) {
                    window.ainstein?.alert?.(data.error || 'Errore avvio scraping', 'error');
                    this.scraping = false;
                    return;
                }

                this.scrapeJobId = data.job_id;
                this.scrapeTotal = data.items_queued || 0;

                // Marca le righe in coda
                if (data.item_ids) {
                    data.item_ids.forEach(id => {
                        this.rowStates[id] = 'queued';
                    });
                }

                this.connectScrapeSSE();

            } catch (error) {
                console.error('startScrape error:', error);
                window.ainstein?.alert?.('Errore di connessione', 'error');
                this.scraping = false;
            }
        },

        connectScrapeSSE() {
            const url = `<?= url("/content-creator/projects/{$project['id']}/scrape-stream") ?>?job_id=${this.scrapeJobId}`;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('started', (e) => {
                const data = JSON.parse(e.data);
                this.scrapeTotal = data.total_items || this.scrapeTotal;
            });

            this.eventSource.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.scrapeCurrentUrl = data.current_url || '';
                this.scrapePercent = data.percent || 0;

                if (data.current_id) {
                    this.rowStates[data.current_id] = 'scraping';
                }
            });

            this.eventSource.addEventListener('item_completed', (e) => {
                const data = JSON.parse(e.data);
                this.scrapeCompleted++;
                this.scrapePercent = this.scrapeTotal > 0 ? Math.round((this.scrapeCompleted / this.scrapeTotal) * 100) : 0;

                this.rowStates[data.url_id] = 'done';
                this.generatedData[data.url_id] = {
                    status: 'scraped'
                };

                setTimeout(() => {
                    if (this.rowStates[data.url_id] === 'done') {
                        delete this.rowStates[data.url_id];
                    }
                }, 3000);
            });

            this.eventSource.addEventListener('item_error', (e) => {
                const data = JSON.parse(e.data);
                this.scrapeFailed++;

                this.rowStates[data.url_id] = 'error';
                this.rowErrors[data.url_id] = data.error || 'Errore scraping';
                this.generatedData[data.url_id] = { status: 'error' };
            });

            this.eventSource.addEventListener('completed', (e) => {
                this.eventSource.close();
                this.eventSource = null;
                this.scraping = false;
                this.scrapePercent = 100;
                this.scrapeCurrentUrl = 'Completato!';

                this.cleanupQueuedStates();

                // Reload pagina dopo 1.5s per aggiornare contatori
                setTimeout(() => location.reload(), 1500);
            });

            this.eventSource.addEventListener('cancelled', (e) => {
                this.eventSource.close();
                this.eventSource = null;
                this.scraping = false;
                this.scrapeCurrentUrl = 'Annullato';
                this.cleanupQueuedStates();
            });

            // Errori custom dal server
            this.eventSource.addEventListener('error', (e) => {
                try {
                    const d = JSON.parse(e.data);
                    this.scrapeCurrentUrl = 'Errore: ' + (d.message || 'sconosciuto');
                    this.eventSource.close();
                    this.eventSource = null;
                    this.scraping = false;
                } catch (_) {
                    // Errore nativo SSE - gestito da onerror
                }
            });

            // Fallback: polling se SSE si disconnette
            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.eventSource = null;
                this.startPolling('scrape');
            };
        },

        async cancelScrape() {
            if (!this.scrapeJobId) return;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('job_id', this.scrapeJobId);

                await fetch('<?= url("/content-creator/projects/{$project['id']}/cancel-scrape-job") ?>', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                // SSE gestira l'evento cancelled
            }
        },

        // =====================
        // GENERAZIONE AI SSE
        // =====================

        async startGenerate() {
            this.generating = true;
            this.genCompleted = 0;
            this.genFailed = 0;
            this.genPercent = 0;
            this.genCurrentUrl = 'Avvio generazione AI...';
            this.rowStates = {};
            this.rowErrors = {};
            this.generatedData = {};

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch('<?= url("/content-creator/projects/{$project['id']}/start-generate-job") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }

                const data = await resp.json();

                if (!data.success) {
                    window.ainstein?.alert?.(data.error || 'Errore avvio generazione', 'error');
                    this.generating = false;
                    return;
                }

                this.genJobId = data.job_id;
                this.genTotal = data.items_queued || 0;

                // Marca le righe in coda
                if (data.item_ids) {
                    data.item_ids.forEach(id => {
                        this.rowStates[id] = 'queued';
                    });
                }

                this.connectGenerateSSE();

            } catch (error) {
                console.error('startGenerate error:', error);
                window.ainstein?.alert?.('Errore di connessione', 'error');
                this.generating = false;
            }
        },

        connectGenerateSSE() {
            const url = `<?= url("/content-creator/projects/{$project['id']}/generate-stream") ?>?job_id=${this.genJobId}`;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('started', (e) => {
                const data = JSON.parse(e.data);
                this.genTotal = data.total_items || this.genTotal;
            });

            this.eventSource.addEventListener('progress', (e) => {
                const data = JSON.parse(e.data);
                this.genCurrentUrl = data.current_url || '';
                this.genPercent = data.percent || 0;

                if (data.current_id) {
                    this.rowStates[data.current_id] = 'generating';
                }
            });

            this.eventSource.addEventListener('item_completed', (e) => {
                const data = JSON.parse(e.data);
                this.genCompleted++;
                this.genPercent = this.genTotal > 0 ? Math.round((this.genCompleted / this.genTotal) * 100) : 0;

                // Aggiorna riga inline
                this.rowStates[data.url_id] = 'done';
                this.generatedData[data.url_id] = {
                    h1: data.h1 || '',
                    word_count: data.word_count || 0,
                    status: 'generated'
                };

                // Flash verde poi rimuovi stato dopo 3s
                setTimeout(() => {
                    if (this.rowStates[data.url_id] === 'done') {
                        delete this.rowStates[data.url_id];
                    }
                }, 3000);
            });

            this.eventSource.addEventListener('item_error', (e) => {
                const data = JSON.parse(e.data);
                this.genFailed++;

                this.rowStates[data.url_id] = 'error';
                this.rowErrors[data.url_id] = data.error || 'Errore generazione';
                this.generatedData[data.url_id] = { status: 'error' };
            });

            this.eventSource.addEventListener('completed', (e) => {
                this.eventSource.close();
                this.eventSource = null;
                this.generating = false;
                this.genPercent = 100;
                this.genCurrentUrl = 'Completato!';

                this.cleanupQueuedStates();

                // Reload pagina dopo 1.5s per aggiornare contatori
                setTimeout(() => location.reload(), 1500);
            });

            this.eventSource.addEventListener('cancelled', (e) => {
                this.eventSource.close();
                this.eventSource = null;
                this.generating = false;
                this.genCurrentUrl = 'Annullato';
                this.cleanupQueuedStates();
            });

            // Errori custom dal server
            this.eventSource.addEventListener('error', (e) => {
                try {
                    const d = JSON.parse(e.data);
                    this.genCurrentUrl = 'Errore: ' + (d.message || 'sconosciuto');
                    this.eventSource.close();
                    this.eventSource = null;
                    this.generating = false;
                } catch (_) {
                    // Errore nativo SSE - gestito da onerror
                }
            });

            // Fallback: polling se SSE si disconnette
            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.eventSource = null;
                this.startPolling('generate');
            };
        },

        async cancelGenerate() {
            if (!this.genJobId) return;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('job_id', this.genJobId);

                await fetch('<?= url("/content-creator/projects/{$project['id']}/cancel-generate-job") ?>', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                // SSE gestira l'evento cancelled
            }
        },

        // =====================
        // POLLING FALLBACK
        // =====================

        async startPolling(type) {
            const isActive = type === 'scrape' ? this.scraping : this.generating;
            if (!isActive) return;

            const jobId = type === 'scrape' ? this.scrapeJobId : this.genJobId;
            const endpoint = type === 'scrape'
                ? `<?= url("/content-creator/projects/{$project['id']}/scrape-job-status") ?>?job_id=${jobId}`
                : `<?= url("/content-creator/projects/{$project['id']}/generate-job-status") ?>?job_id=${jobId}`;

            try {
                const resp = await fetch(endpoint);

                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }

                const data = await resp.json();

                if (data.success && data.job) {
                    if (type === 'scrape') {
                        this.scrapeCompleted = data.job.items_completed || 0;
                        this.scrapeFailed = data.job.items_failed || 0;
                        this.scrapePercent = data.job.progress || 0;
                        this.scrapeCurrentUrl = data.job.current_item || '';
                    } else {
                        this.genCompleted = data.job.items_completed || 0;
                        this.genFailed = data.job.items_failed || 0;
                        this.genPercent = data.job.progress || 0;
                        this.genCurrentUrl = data.job.current_item || '';
                    }

                    if (data.job.status === 'completed' || data.job.status === 'error' || data.job.status === 'cancelled') {
                        if (type === 'scrape') {
                            this.scraping = false;
                            this.scrapeCurrentUrl = data.job.status === 'completed' ? 'Completato!' : 'Terminato';
                        } else {
                            this.generating = false;
                            this.genCurrentUrl = data.job.status === 'completed' ? 'Completato!' : 'Terminato';
                        }

                        // Reload per aggiornare contatori
                        if (data.job.status === 'completed') {
                            setTimeout(() => location.reload(), 1500);
                        }
                        return;
                    }
                }

                // Continua polling
                setTimeout(() => this.startPolling(type), 2000);
            } catch (error) {
                console.error('Polling error:', error);
                setTimeout(() => this.startPolling(type), 3000);
            }
        },

        // =====================
        // AZIONI SINGOLE/BULK
        // =====================

        async approveOne(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url("/content-creator/projects/{$project['id']}/urls") ?>/${id}/approve`, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    window.ainstein?.alert?.('Errore: ' + (data.error || 'Approvazione fallita'), 'error');
                }
            } catch (error) {
                window.ainstein?.alert?.('Errore di connessione', 'error');
            }
        },

        async rejectOne(id) {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url("/content-creator/projects/{$project['id']}/urls") ?>/${id}/reject`, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    window.ainstein?.alert?.('Errore: ' + (data.error || 'Rifiuto fallito'), 'error');
                }
            } catch (error) {
                window.ainstein?.alert?.('Errore di connessione', 'error');
            }
        },

        async bulkApprove() {
            if (this.selectedIds.length === 0) return;

            try {
                if (window.ainstein?.confirm) {
                    await window.ainstein.confirm(`Approvare ${this.selectedIds.length} URL selezionate?`, {destructive: false});
                }
            } catch (e) { return; }

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedIds.forEach(id => formData.append('ids[]', id));

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/urls/bulk-approve") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                if (data.success) {
                    window.ainstein?.toast?.(data.message || 'URL approvate', 'success');
                    location.reload();
                } else {
                    window.ainstein?.alert?.('Errore: ' + (data.error || 'Approvazione fallita'), 'error');
                }
            } catch (error) {
                window.ainstein?.alert?.('Errore di connessione', 'error');
            }
        },

        async bulkDelete() {
            if (this.selectedIds.length === 0) return;

            try {
                if (window.ainstein?.confirm) {
                    await window.ainstein.confirm(`Eliminare ${this.selectedIds.length} URL selezionate? Questa azione non puo essere annullata.`, {destructive: true});
                } else if (!confirm(`Eliminare ${this.selectedIds.length} URL selezionate?`)) {
                    return;
                }
            } catch (e) { return; }

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedIds.forEach(id => formData.append('ids[]', id));

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/urls/bulk-delete") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                if (data.success) {
                    window.ainstein?.toast?.(data.message || 'URL eliminate', 'success');
                    location.reload();
                } else {
                    window.ainstein?.alert?.('Errore: ' + (data.error || 'Eliminazione fallita'), 'error');
                }
            } catch (error) {
                window.ainstein?.alert?.('Errore di connessione', 'error');
            }
        },

        // =====================
        // HELPERS
        // =====================

        cleanupQueuedStates() {
            Object.keys(this.rowStates).forEach(id => {
                if (this.rowStates[id] === 'queued' || this.rowStates[id] === 'scraping' || this.rowStates[id] === 'generating') {
                    delete this.rowStates[id];
                }
            });
        },

        // Cleanup on component destroy
        destroy() {
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
        }
    }
}
</script>
