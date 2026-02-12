<?php $currentPage = 'analyses'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="analysisResultsManager(<?= htmlspecialchars(json_encode([
    'projectId' => $project['id'],
    'analysisId' => $analysis['id'],
    'baseUrl' => url(''),
    'selectedCount' => $selectedCount,
    'totalKeywords' => $totalKeywords
])) ?>)">

    <!-- Analysis Info Card -->
    <?php if (!empty($analysis['business_context'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Contesto Business</h3>
        <p class="text-sm text-slate-600 dark:text-slate-400"><?= nl2br(e($analysis['business_context'])) ?></p>
    </div>
    <?php endif; ?>

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
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalKeywords ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Totali</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($analysisData) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Ad Groups</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $analysis['total_categories'] ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Categorie</p>
            </div>
        </div>
    </div>

    <?php if (empty($analysisData)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Nessun risultato</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Questa analisi non contiene risultati.
            </p>
        </div>
    </div>
    <?php else: ?>
    <!-- Ad Groups Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Tab Navigation -->
        <div class="border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
            <nav class="flex -mb-px">
                <?php $firstAdGroup = array_key_first($analysisData); ?>
                <?php foreach ($analysisData as $adGroupId => $data): ?>
                <button
                    @click="activeTab = <?= $adGroupId ?>"
                    :class="activeTab === <?= $adGroupId ?> ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                >
                    <?= e($data['ad_group']['name']) ?>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-700">
                        <?php
                        $kwCount = 0;
                        foreach ($data['categories'] as $cat) {
                            $kwCount += count($cat['keywords']);
                        }
                        echo $kwCount;
                        ?>
                    </span>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Tab Content -->
        <?php foreach ($analysisData as $adGroupId => $data): ?>
        <div x-show="activeTab === <?= $adGroupId ?>" class="p-6 space-y-4">
            <?php if (!empty($data['categories'])): ?>
                <?php foreach ($data['categories'] as $category): ?>
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
    <?php endif; ?>

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
function analysisResultsManager(config) {
    return {
        projectId: config.projectId,
        analysisId: config.analysisId,
        baseUrl: config.baseUrl,
        selectedCount: config.selectedCount,
        totalKeywords: config.totalKeywords,
        activeTab: <?= $firstAdGroup ?? 0 ?>,
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
                await fetch(`${this.baseUrl}/ads-analyzer/projects/${this.projectId}/analyses/${this.analysisId}/keywords/${keywordId}/toggle`, {
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
                await fetch(`${this.baseUrl}/ads-analyzer/projects/${this.projectId}/analyses/${this.analysisId}/categories/${categoryId}/${action}`, {
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

        updateSelectedCount() {
            this.selectedCount = Object.values(this.keywordStates).filter(v => v).length;
        },

        async copyAllKeywords() {
            // Get selected keywords as text
            const selectedKeywords = [];
            <?php foreach ($analysisData as $data): ?>
                <?php foreach ($data['categories'] as $cat): ?>
                    <?php foreach ($cat['keywords'] as $kw): ?>
                    if (this.keywordStates[<?= $kw['id'] ?>]) {
                        selectedKeywords.push('<?= addslashes($kw['keyword']) ?>');
                    }
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>

            if (selectedKeywords.length === 0) {
                window.ainstein.alert('Nessuna keyword selezionata', 'warning');
                return;
            }

            try {
                await navigator.clipboard.writeText(selectedKeywords.join('\n'));
                this.copyButtonText = `Copiate ${selectedKeywords.length}!`;
                setTimeout(() => {
                    this.copyButtonText = 'Copia tutte';
                }, 2000);
            } catch (err) {
                console.error('Copy failed:', err);
            }
        }
    };
}
</script>

<!-- Hidden CSRF token for AJAX -->
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
