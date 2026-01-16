<div class="space-y-6" x-data="resultsManager(<?= htmlspecialchars(json_encode([
    'projectId' => $project['id'],
    'selectedCount' => $selectedCount,
    'totalNegatives' => $totalNegatives
])) ?>)">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <?= e($project['name']) ?>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Risultati Analisi</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <span x-text="selectedCount"></span> keyword selezionate su <?= $totalNegatives ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <!-- Export Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Esporta
                    <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 rounded-lg bg-white dark:bg-slate-800 shadow-lg border border-slate-200 dark:border-slate-700 py-1 z-10">
                    <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/export/csv') ?>" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        CSV (tutte le selezionate)
                    </a>
                    <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/export/google-ads-editor') ?>" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Google Ads Editor
                    </a>
                    <button @click="copyAllKeywords()" class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Copia come testo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="selectedCount"></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Selezionate</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalNegatives ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Totali</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($adGroups) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Ad Groups</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_terms']) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Termini analizzati</p>
            </div>
        </div>
    </div>

    <!-- Ad Groups Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Tab Navigation -->
        <div class="border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
            <nav class="flex -mb-px">
                <?php foreach ($adGroups as $idx => $adGroup): ?>
                <button
                    @click="activeTab = <?= $adGroup['id'] ?>"
                    :class="activeTab === <?= $adGroup['id'] ?> ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                >
                    <?= e($adGroup['name']) ?>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-700" x-text="getTabCount(<?= $adGroup['id'] ?>)">
                        <?php
                        $kwCount = 0;
                        if (isset($analysisData[$adGroup['id']])) {
                            foreach ($analysisData[$adGroup['id']]['categories'] as $cat) {
                                $kwCount += count($cat['keywords']);
                            }
                        }
                        echo $kwCount;
                        ?>
                    </span>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Tab Content -->
        <?php foreach ($adGroups as $adGroup): ?>
        <div x-show="activeTab === <?= $adGroup['id'] ?>" class="p-6 space-y-4">
            <?php if (isset($analysisData[$adGroup['id']]) && !empty($analysisData[$adGroup['id']]['categories'])): ?>
                <?php foreach ($analysisData[$adGroup['id']]['categories'] as $category): ?>
                <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden" x-data="{ expanded: true }">
                    <!-- Category Header -->
                    <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-3 flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php if ($category['priority'] === 'high'): ?>
                                bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300
                                <?php elseif ($category['priority'] === 'medium'): ?>
                                bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                                <?php else: ?>
                                bg-slate-100 text-slate-600 dark:bg-slate-600 dark:text-slate-300
                                <?php endif; ?>
                            ">
                                <?= ucfirst($category['priority']) ?>
                            </span>
                            <span class="font-medium text-slate-900 dark:text-white"><?= e($category['category_name']) ?></span>
                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                (<span x-text="getCategorySelectedCount(<?= $category['id'] ?>)"><?= count(array_filter($category['keywords'], fn($k) => $k['is_selected'])) ?></span>/<?= count($category['keywords']) ?>)
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Bulk Actions -->
                            <button @click.stop="toggleCategory(<?= $category['id'] ?>, 'select_all')" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                Tutti
                            </button>
                            <span class="text-slate-300">|</span>
                            <button @click.stop="toggleCategory(<?= $category['id'] ?>, 'deselect_all')" class="text-xs text-red-600 hover:text-red-700 dark:text-red-400">
                                Nessuno
                            </button>
                            <span class="text-slate-300">|</span>
                            <button @click.stop="toggleCategory(<?= $category['id'] ?>, 'invert')" class="text-xs text-slate-600 hover:text-slate-700 dark:text-slate-400">
                                Inverti
                            </button>
                            <!-- Expand/Collapse -->
                            <svg :class="expanded ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Category Description -->
                    <?php if ($category['description']): ?>
                    <div x-show="expanded" class="px-4 py-2 bg-slate-50/50 dark:bg-slate-700/25 text-sm text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                        <?= e($category['description']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Keywords -->
                    <div x-show="expanded" class="p-4">
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($category['keywords'] as $keyword): ?>
                            <label
                                class="inline-flex items-center px-3 py-1.5 rounded-lg border cursor-pointer transition-colors"
                                :class="isKeywordSelected(<?= $keyword['id'] ?>) ? 'bg-amber-50 border-amber-300 dark:bg-amber-900/30 dark:border-amber-700' : 'bg-white border-slate-200 dark:bg-slate-800 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isKeywordSelected(<?= $keyword['id'] ?>)"
                                    @change="toggleKeyword(<?= $keyword['id'] ?>, <?= $category['id'] ?>)"
                                    class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 mr-2"
                                >
                                <span class="text-sm" :class="isKeywordSelected(<?= $keyword['id'] ?>) ? 'text-amber-700 dark:text-amber-300' : 'text-slate-700 dark:text-slate-300'">
                                    <?= e($keyword['keyword']) ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mt-2 text-slate-500 dark:text-slate-400">Nessuna keyword negativa trovata per questo Ad Group</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Copy Section -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-medium text-slate-900 dark:text-white">Copia rapida keyword selezionate</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    <span x-text="selectedCount"></span> keyword pronte per l'export
                </p>
            </div>
            <button
                @click="copyAllKeywords()"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <span x-text="copyButtonText">Copia tutte</span>
            </button>
        </div>
    </div>
</div>

<script>
function resultsManager(config) {
    return {
        projectId: config.projectId,
        selectedCount: config.selectedCount,
        totalNegatives: config.totalNegatives,
        activeTab: <?= $adGroups[0]['id'] ?? 0 ?>,
        copyButtonText: 'Copia tutte',

        // Keyword selection state (initialized from server data)
        keywordStates: <?= json_encode(array_reduce(
            array_merge(...array_map(fn($d) => $d['categories'] ?? [], $analysisData)),
            function($carry, $cat) {
                foreach ($cat['keywords'] ?? [] as $kw) {
                    $carry[$kw['id']] = (bool)$kw['is_selected'];
                }
                return $carry;
            },
            []
        )) ?>,

        // Category keyword mappings
        categoryKeywords: <?= json_encode(array_reduce(
            array_merge(...array_map(fn($d) => $d['categories'] ?? [], $analysisData)),
            function($carry, $cat) {
                $carry[$cat['id']] = array_column($cat['keywords'] ?? [], 'id');
                return $carry;
            },
            []
        )) ?>,

        isKeywordSelected(keywordId) {
            return this.keywordStates[keywordId] ?? false;
        },

        async toggleKeyword(keywordId, categoryId) {
            const newState = !this.keywordStates[keywordId];
            this.keywordStates[keywordId] = newState;
            this.updateSelectedCount();

            try {
                await fetch(`/ads-analyzer/projects/${this.projectId}/keywords/${keywordId}/toggle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_token=' + document.querySelector('input[name="_token"]')?.value
                });
            } catch (err) {
                console.error('Toggle failed:', err);
            }
        },

        async toggleCategory(categoryId, action) {
            const keywords = this.categoryKeywords[categoryId] || [];

            keywords.forEach(kwId => {
                if (action === 'select_all') {
                    this.keywordStates[kwId] = true;
                } else if (action === 'deselect_all') {
                    this.keywordStates[kwId] = false;
                } else if (action === 'invert') {
                    this.keywordStates[kwId] = !this.keywordStates[kwId];
                }
            });

            this.updateSelectedCount();

            try {
                await fetch(`/ads-analyzer/projects/${this.projectId}/categories/${categoryId}/${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_token=' + document.querySelector('input[name="_token"]')?.value
                });
            } catch (err) {
                console.error('Category toggle failed:', err);
            }
        },

        getCategorySelectedCount(categoryId) {
            const keywords = this.categoryKeywords[categoryId] || [];
            return keywords.filter(kwId => this.keywordStates[kwId]).length;
        },

        getTabCount(adGroupId) {
            // Count selected keywords for this ad group
            // This would need additional data mapping
            return this.selectedCount;
        },

        updateSelectedCount() {
            this.selectedCount = Object.values(this.keywordStates).filter(v => v).length;
        },

        async copyAllKeywords() {
            try {
                const response = await fetch(`/ads-analyzer/projects/${this.projectId}/copy-text`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_token=' + document.querySelector('input[name="_token"]')?.value
                });

                const data = await response.json();

                if (data.success) {
                    await navigator.clipboard.writeText(data.text);
                    this.copyButtonText = `Copiate ${data.count}!`;
                    setTimeout(() => {
                        this.copyButtonText = 'Copia tutte';
                    }, 2000);
                }
            } catch (err) {
                console.error('Copy failed:', err);
            }
        }
    };
}
</script>

<!-- Hidden CSRF token for AJAX -->
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
