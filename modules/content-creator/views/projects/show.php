<?php
/**
 * Content Creator - Dashboard progetto con tabella URL completa
 *
 * Variabili disponibili:
 * - $project: array dati progetto
 * - $stats: array con total, pending, scraped, generated, approved, rejected, published, errors
 * - $urls: array URL paginate
 * - $pagination: array con current_page, last_page, total, per_page, from, to
 * - $filters: array con status, search, sort, dir
 * - $currentPage: 'dashboard'
 */

include __DIR__ . '/../../../../shared/views/components/table-helpers.php';

$currentSort = $filters['sort'] ?? 'created_at';
$currentDir = $filters['dir'] ?? 'desc';
$baseUrl = url("/content-creator/projects/{$project['id']}");
$sortFilters = array_filter([
    'status' => $filters['status'] ?? '',
    'q' => $filters['search'] ?? '',
]);
$paginationFilters = array_merge($sortFilters, array_filter([
    'sort' => $currentSort !== 'created_at' ? $currentSort : '',
    'dir' => $currentDir !== 'desc' ? $currentDir : '',
]));

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

$statusTabs = [
    '' => ['label' => 'Tutti', 'count' => $stats['total']],
    'pending' => ['label' => 'In Attesa', 'count' => $stats['pending']],
    'scraped' => ['label' => 'Scrappate', 'count' => $stats['scraped']],
    'generated' => ['label' => 'Generate', 'count' => $stats['generated']],
    'approved' => ['label' => 'Approvate', 'count' => $stats['approved']],
    'published' => ['label' => 'Pubblicate', 'count' => $stats['published']],
    'error' => ['label' => 'Errori', 'count' => $stats['errors']],
];
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

<div class="space-y-6" x-data="dashboardManager()">
    <!-- Stats Cards -->
    <?= \Core\View::partial('components/dashboard-stats-row', [
        'cards' => [
            [
                'label' => 'URL totali',
                'value' => $stats['total'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                'color' => 'orange',
            ],
            [
                'label' => 'Scrappate',
                'value' => $stats['scraped'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>',
                'color' => 'blue',
            ],
            [
                'label' => 'Generate',
                'value' => $stats['generated'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
                'color' => 'purple',
            ],
            [
                'label' => 'Approvate',
                'value' => $stats['approved'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                'color' => 'emerald',
            ],
        ],
    ]) ?>

    <?php if ($stats['total'] > 0): ?>
    <!-- Progress Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Avanzamento</span>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                <?php
                $completedPct = $stats['total'] > 0
                    ? round(($stats['approved'] + $stats['published']) / $stats['total'] * 100)
                    : 0;
                ?>
                <?= $completedPct ?>% completato
            </span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
            <?php
            $total = max($stats['total'], 1);
            $segments = [
                ['pct' => $stats['published'] / $total * 100, 'color' => 'bg-emerald-500'],
                ['pct' => $stats['approved'] / $total * 100, 'color' => 'bg-teal-500'],
                ['pct' => $stats['generated'] / $total * 100, 'color' => 'bg-purple-500'],
                ['pct' => $stats['scraped'] / $total * 100, 'color' => 'bg-blue-500'],
                ['pct' => $stats['errors'] / $total * 100, 'color' => 'bg-red-500'],
            ];
            ?>
            <div class="flex h-full">
                <?php foreach ($segments as $seg): ?>
                <?php if ($seg['pct'] > 0): ?>
                <div class="<?= $seg['color'] ?> h-full" style="width: <?= $seg['pct'] ?>%"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pubblicati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-500"></span> Approvati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Generati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Scrappati</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Errori</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Scrape -->
        <form action="<?= url('/content-creator/projects/' . $project['id'] . '/start-scrape-job') ?>" method="POST"
              @submit="scraping = true">
            <?= csrf_field() ?>
            <button type="submit" :disabled="scraping"
                    class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium transition-colors
                           <?= $stats['pending'] > 0
                               ? 'bg-blue-600 text-white hover:bg-blue-700'
                               : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed' ?>"
                    <?= $stats['pending'] == 0 ? 'disabled' : '' ?>>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span x-show="!scraping">Scrape (<?= $stats['pending'] ?>)</span>
                <span x-show="scraping" x-cloak>Avvio...</span>
            </button>
        </form>

        <!-- Generate -->
        <form action="<?= url('/content-creator/projects/' . $project['id'] . '/start-generate-job') ?>" method="POST"
              @submit="generating = true">
            <?= csrf_field() ?>
            <button type="submit" :disabled="generating"
                    class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium transition-colors
                           <?= ($stats['pending'] + $stats['scraped']) > 0
                               ? 'bg-purple-600 text-white hover:bg-purple-700'
                               : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed' ?>"
                    <?= ($stats['pending'] + $stats['scraped']) == 0 ? 'disabled' : '' ?>>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span x-show="!generating">Genera Contenuto (<?= $stats['pending'] + $stats['scraped'] ?>)</span>
                <span x-show="generating" x-cloak>Avvio...</span>
            </button>
        </form>

        <!-- Export CSV -->
        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/export/csv') ?>"
           class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium transition-colors
                  <?= ($stats['generated'] + $stats['approved'] + $stats['published']) > 0
                      ? 'bg-slate-600 text-white hover:bg-slate-700'
                      : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500 pointer-events-none' ?>">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Esporta CSV
        </a>

        <!-- Import URL -->
        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/import') ?>"
           class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium bg-teal-600 text-white hover:bg-teal-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Importa URL
        </a>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap gap-2">
        <?php foreach ($statusTabs as $statusKey => $tab):
            $isActive = ($filters['status'] ?? '') === $statusKey;
            $tabParams = [];
            if ($statusKey !== '') $tabParams['status'] = $statusKey;
            if (!empty($filters['search'])) $tabParams['q'] = $filters['search'];
            if (!empty($filters['sort']) && $filters['sort'] !== 'created_at') $tabParams['sort'] = $filters['sort'];
            if (!empty($filters['dir']) && $filters['dir'] !== 'desc') $tabParams['dir'] = $filters['dir'];
            $tabUrl = $baseUrl . ($tabParams ? '?' . http_build_query($tabParams) : '');
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

    <!-- Search Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="q"
                       value="<?= e($filters['search'] ?? '') ?>"
                       placeholder="Cerca URL, keyword o titolo..."
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
            </div>
            <?php if (!empty($filters['status'])): ?>
            <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
            <?php endif; ?>
            <?php if (!empty($filters['sort']) && $filters['sort'] !== 'created_at'): ?>
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
            <a href="<?= $baseUrl . (!empty($filters['status']) ? '?status=' . e($filters['status']) : '') ?>"
               class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                Cancella ricerca
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-show="selectedIds.length > 0" x-cloak
         class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl p-4">
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
                    Approva
                </button>
                <button type="button"
                        @click="bulkDelete()"
                        class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Elimina
                </button>
                <button type="button" @click="selectedIds = []; selectAll = false" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">
                    Deseleziona
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <?php if (empty($urls)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
        <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun risultato</h3>
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
        <a href="<?= $baseUrl ?>"
           class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
            Reset filtri
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
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
                        <?= table_sort_header('URL', 'url', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_sort_header('Keyword', 'keyword', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_sort_header('Stato', 'status', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_sort_header('Data', 'created_at', $currentSort, $currentDir, $baseUrl, $sortFilters) ?>
                        <?= table_header('Azioni', 'right') ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($urls as $item):
                        $itemStatus = $item['status'] ?? 'pending';
                        $itemStatusColor = $statusColors[$itemStatus] ?? $statusColors['pending'];
                        $itemStatusLabel = $statusLabels[$itemStatus] ?? $itemStatus;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
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
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                            <?= e($item['keyword'] ?? '-') ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $itemStatusColor ?>">
                                <?= $itemStatusLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y', strtotime($item['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url("/content-creator/projects/{$project['id']}/urls/{$item['id']}") ?>"
                               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                Dettagli
                            </a>
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

    <?php else: ?>

    <!-- First time empty state -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Inizia importando le URL</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Importa le URL del tuo sito da CSV, Sitemap, CMS o inseriscile manualmente.
        </p>
        <a href="<?= url('/content-creator/projects/' . $project['id'] . '/import') ?>"
           class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Importa URL
        </a>
    </div>

    <?php endif; ?>

    <!-- Come funziona -->
    <?= \Core\View::partial('components/dashboard-how-it-works', [
        'color' => 'orange',
        'steps' => [
            ['title' => 'Configura', 'description' => 'Template e CMS target'],
            ['title' => 'Importa URL', 'description' => 'Pagine da riscrivere'],
            ['title' => 'Genera', 'description' => 'AI crea i contenuti HTML'],
            ['title' => 'Pubblica', 'description' => 'Invia al CMS'],
        ],
    ]) ?>
</div>

<script>
function dashboardManager() {
    return {
        selectedIds: [],
        selectAll: false,
        scraping: false,
        generating: false,

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedIds = <?= json_encode(array_map(fn($i) => (string)$i['id'], $urls)) ?>;
            } else {
                this.selectedIds = [];
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
                this.selectedIds.forEach(id => formData.append('url_ids[]', id));

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
                this.selectedIds.forEach(id => formData.append('url_ids[]', id));

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
        }
    }
}
</script>
