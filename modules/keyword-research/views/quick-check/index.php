<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Quick Check</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Quick Check Keyword</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Controlla volume, CPC e competition di una keyword istantaneamente</p>
    </div>

    <!-- Search Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <form action="<?= url('/keyword-research/quick-check/search') ?>" method="POST" class="flex gap-4 items-end" x-data="{ loading: false }" @submit="loading = true">
            <?= csrf_field() ?>

            <div class="flex-1">
                <label for="keyword" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Keyword
                </label>
                <input type="text" name="keyword" id="keyword" required
                       value="<?= e($keyword ?? '') ?>"
                       placeholder="Es: consulente seo, dentista roma..."
                       class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div class="w-40">
                <label for="location" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Location
                </label>
                <select name="location" id="location"
                        class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="IT" <?= ($location ?? 'IT') === 'IT' ? 'selected' : '' ?>>Italia</option>
                    <option value="US" <?= ($location ?? '') === 'US' ? 'selected' : '' ?>>USA</option>
                    <option value="GB" <?= ($location ?? '') === 'GB' ? 'selected' : '' ?>>UK</option>
                    <option value="DE" <?= ($location ?? '') === 'DE' ? 'selected' : '' ?>>Germania</option>
                    <option value="FR" <?= ($location ?? '') === 'FR' ? 'selected' : '' ?>>Francia</option>
                    <option value="ES" <?= ($location ?? '') === 'ES' ? 'selected' : '' ?>>Spagna</option>
                </select>
            </div>

            <button type="submit" :disabled="loading" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors disabled:opacity-50">
                <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <svg x-show="loading" x-cloak class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Ricerca...' : 'Cerca'"></span>
            </button>
        </form>

        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
            Nessun credito richiesto. I risultati non vengono salvati.
        </p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="ml-3 text-sm text-red-700 dark:text-red-300"><?= e($error) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
    <?php $main = $results['main']; ?>

    <!-- Main Keyword Card -->
    <?php if ($main): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">"<?= e($main['text'] ?? $keyword) ?>"</h2>
            <?php if (!empty($main['intent'])): ?>
            <?php
            $intentColors = [
                'informational' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'transactional' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                'commercial' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'navigational' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
            ];
            $intentColor = $intentColors[strtolower($main['intent'])] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
            ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $intentColor ?>">
                <?= e(ucfirst($main['intent'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($main['volume'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Volume mensile</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($main['competition_level'] ?? 'N/A') ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Competition</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 text-center">
                <?php
                $lowBid = $main['low_bid'] ?? 0;
                $highBid = $main['high_bid'] ?? 0;
                $cpcDisplay = ($lowBid > 0 || $highBid > 0)
                    ? number_format($lowBid, 2) . ' - ' . number_format($highBid, 2)
                    : 'N/A';
                ?>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $cpcDisplay ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">CPC (EUR)</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 text-center">
                <?php
                $trend = $main['trend'] ?? 0;
                $trendColor = $trend > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($trend < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white');
                $trendIcon = $trend > 0 ? '+' : '';
                ?>
                <p class="text-2xl font-bold <?= $trendColor ?>"><?= $trendIcon . number_format($trend, 0) ?>%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Trend</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Keywords -->
    <?php if (!empty($results['related'])): ?>
    <?php include __DIR__ . '/../partials/table-helpers.php'; ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
         x-data="quickCheckTable()">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-slate-900 dark:text-white">
                    Keyword correlate
                    <span class="text-sm font-normal text-slate-500 dark:text-slate-400">
                        (<span x-text="filteredKeywords.length"></span> di <?= $results['total_found'] ?> trovate)
                    </span>
                </h3>
                <div class="flex items-center gap-2">
                    <select x-model="filterIntent" @change="currentPage = 1"
                            class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tutti gli intent</option>
                        <option value="informational">Informational</option>
                        <option value="commercial">Commercial</option>
                        <option value="transactional">Transactional</option>
                        <option value="navigational">Navigational</option>
                    </select>
                    <select x-model="filterCompetition" @change="currentPage = 1"
                            class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tutte le comp.</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                    <button x-show="filterIntent || filterCompetition" x-cloak
                            @click="filterIntent = ''; filterCompetition = ''; currentPage = 1"
                            class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 underline">
                        Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left cursor-pointer select-none" @click="toggleSort('text')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Keyword <span x-html="krSortIcon('text', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-right cursor-pointer select-none" @click="toggleSort('volume')">
                            <span class="inline-flex items-center justify-end text-xs font-medium text-slate-500 dark:text-slate-400 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Volume <span x-html="krSortIcon('volume', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-center cursor-pointer select-none" @click="toggleSort('competition_level')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Competition <span x-html="krSortIcon('competition_level', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-right cursor-pointer select-none" @click="toggleSort('high_bid')">
                            <span class="inline-flex items-center justify-end text-xs font-medium text-slate-500 dark:text-slate-400 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                CPC <span x-html="krSortIcon('high_bid', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-center cursor-pointer select-none" @click="toggleSort('intent')">
                            <span class="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Intent <span x-html="krSortIcon('intent', sortField, sortDir)"></span>
                            </span>
                        </th>
                        <th class="px-6 py-3 text-right cursor-pointer select-none" @click="toggleSort('trend')">
                            <span class="inline-flex items-center justify-end text-xs font-medium text-slate-500 dark:text-slate-400 uppercase hover:text-slate-700 dark:hover:text-slate-200">
                                Trend <span x-html="krSortIcon('trend', sortField, sortDir)"></span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="kw in paginatedKeywords" :key="kw.text">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-6 py-3 text-slate-900 dark:text-white font-medium" x-text="kw.text"></td>
                        <td class="px-6 py-3 text-right text-slate-700 dark:text-slate-300" x-text="(kw.volume || 0).toLocaleString()"></td>
                        <td class="px-6 py-3 text-center">
                            <span :class="krCompClass(kw.competition_level)" x-text="kw.competition_level ? kw.competition_level.charAt(0).toUpperCase() + kw.competition_level.slice(1) : 'N/A'"></span>
                        </td>
                        <td class="px-6 py-3 text-right text-slate-700 dark:text-slate-300" x-text="kw.high_bid > 0 ? kw.high_bid.toFixed(2) + ' EUR' : '-'"></td>
                        <td class="px-6 py-3 text-center">
                            <template x-if="kw.intent">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="krIntentClass(kw.intent)" x-text="kw.intent.charAt(0).toUpperCase() + kw.intent.slice(1)"></span>
                            </template>
                            <template x-if="!kw.intent">
                                <span class="text-slate-400">-</span>
                            </template>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <span :class="kw.trend > 0 ? 'text-emerald-600 dark:text-emerald-400' : (kw.trend < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-500')"
                                  x-text="(kw.trend > 0 ? '+' : '') + Math.round(kw.trend) + '%'"></span>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <template x-if="totalPages > 1">
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Mostrando <span class="font-medium" x-text="paginationFrom"></span> - <span class="font-medium" x-text="paginationTo"></span>
                    di <span class="font-medium" x-text="filteredKeywords.length"></span> risultati
                </p>
                <div class="flex items-center gap-1">
                    <button x-show="currentPage > 1" @click="goToPage(currentPage - 1)"
                            class="px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md hover:bg-slate-50 dark:hover:bg-slate-600 transition">
                        Precedente
                    </button>
                    <template x-for="(p, idx) in pageNumbers" :key="idx">
                        <span class="inline-flex">
                            <span x-show="p === '...'" class="px-2 text-slate-400">...</span>
                            <button x-show="p !== '...'" @click="goToPage(p)"
                                    class="px-3 py-1.5 text-sm font-medium rounded-md transition"
                                    :class="p === currentPage ? 'bg-primary-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    x-text="p"></button>
                        </span>
                    </template>
                    <button x-show="currentPage < totalPages" @click="goToPage(currentPage + 1)"
                            class="px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md hover:bg-slate-50 dark:hover:bg-slate-600 transition">
                        Successivo
                    </button>
                </div>
            </div>
        </template>
    </div>

    <script>
    function quickCheckTable() {
        return {
            keywords: <?= json_encode($results['related']) ?>,
            sortField: 'volume',
            sortDir: 'desc',
            currentPage: 1,
            perPage: 25,
            filterIntent: '',
            filterCompetition: '',
            toggleSort(field) {
                if (this.sortField === field) {
                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDir = (field === 'text' || field === 'intent' || field === 'competition_level') ? 'asc' : 'desc';
                }
                this.currentPage = 1;
            },
            get filteredKeywords() {
                let result = this.keywords;
                if (this.filterIntent) {
                    result = result.filter(kw => (kw.intent || '').toLowerCase() === this.filterIntent);
                }
                if (this.filterCompetition) {
                    result = result.filter(kw => (kw.competition_level || '').toLowerCase() === this.filterCompetition);
                }
                return result;
            },
            get sortedKeywords() {
                return krSortArray(this.filteredKeywords, this.sortField, this.sortDir);
            },
            get paginatedKeywords() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.sortedKeywords.slice(start, start + this.perPage);
            },
            get totalPages() {
                return Math.ceil(this.filteredKeywords.length / this.perPage);
            },
            get paginationFrom() {
                return this.filteredKeywords.length > 0 ? (this.currentPage - 1) * this.perPage + 1 : 0;
            },
            get paginationTo() {
                return Math.min(this.currentPage * this.perPage, this.filteredKeywords.length);
            },
            get pageNumbers() {
                return krPageNumbers(this.currentPage, this.totalPages);
            },
            goToPage(page) {
                this.currentPage = Math.max(1, Math.min(page, this.totalPages));
            }
        };
    }
    </script>
    <?php endif; ?>
    <?php endif; ?>
</div>
