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
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <h3 class="font-semibold text-slate-900 dark:text-white">
                    Keyword correlate
                    <span class="text-sm font-normal text-slate-500 dark:text-slate-400">
                        (<span x-text="filteredKeywords.length"></span> di <?= $results['total_found'] ?> trovate)
                    </span>
                </h3>
            </div>

            <!-- Filter Bar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Volume -->
                <div class="relative" @click.outside="openDropdown = openDropdown === 'volume' ? '' : openDropdown">
                    <button @click="openDropdown = openDropdown === 'volume' ? '' : 'volume'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border transition-colors"
                            :class="hasFilter('volume') ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-600 dark:bg-primary-900/30 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'">
                        <span x-show="hasFilter('volume')" class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                        Volume
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openDropdown === 'volume'" x-cloak x-transition
                         class="absolute left-0 top-full mt-1 z-20 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-3 w-56">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Range volume mensile</p>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model.number="filters.volumeMin" placeholder="Min" min="0"
                                   @input="currentPage = 1"
                                   class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-2">
                            <span class="text-slate-400">-</span>
                            <input type="number" x-model.number="filters.volumeMax" placeholder="Max" min="0"
                                   @input="currentPage = 1"
                                   class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-2">
                        </div>
                        <button @click="filters.volumeMin = ''; filters.volumeMax = ''; currentPage = 1"
                                class="mt-2 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 underline">Reset</button>
                    </div>
                </div>

                <!-- CPC -->
                <div class="relative" @click.outside="openDropdown = openDropdown === 'cpc' ? '' : openDropdown">
                    <button @click="openDropdown = openDropdown === 'cpc' ? '' : 'cpc'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border transition-colors"
                            :class="hasFilter('cpc') ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-600 dark:bg-primary-900/30 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'">
                        <span x-show="hasFilter('cpc')" class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                        CPC
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openDropdown === 'cpc'" x-cloak x-transition
                         class="absolute left-0 top-full mt-1 z-20 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-3 w-56">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Range CPC (EUR)</p>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model.number="filters.cpcMin" placeholder="Min" min="0" step="0.01"
                                   @input="currentPage = 1"
                                   class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-2">
                            <span class="text-slate-400">-</span>
                            <input type="number" x-model.number="filters.cpcMax" placeholder="Max" min="0" step="0.01"
                                   @input="currentPage = 1"
                                   class="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-2">
                        </div>
                        <button @click="filters.cpcMin = ''; filters.cpcMax = ''; currentPage = 1"
                                class="mt-2 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 underline">Reset</button>
                    </div>
                </div>

                <!-- Intent -->
                <div class="relative" @click.outside="openDropdown = openDropdown === 'intent' ? '' : openDropdown">
                    <button @click="openDropdown = openDropdown === 'intent' ? '' : 'intent'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border transition-colors"
                            :class="hasFilter('intent') ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-600 dark:bg-primary-900/30 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'">
                        <span x-show="hasFilter('intent')" class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                        Intent
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openDropdown === 'intent'" x-cloak x-transition
                         class="absolute left-0 top-full mt-1 z-20 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-3 w-52">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Seleziona intent</p>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="informational" x-model="filters.intents" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">I</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Informational</span>
                        </label>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="commercial" x-model="filters.intents" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">C</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Commercial</span>
                        </label>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="transactional" x-model="filters.intents" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">T</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Transactional</span>
                        </label>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="navigational" x-model="filters.intents" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-purple-600 focus:ring-purple-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">N</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Navigational</span>
                        </label>
                        <button @click="filters.intents = []; currentPage = 1"
                                class="mt-2 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 underline">Reset</button>
                    </div>
                </div>

                <!-- Competition -->
                <div class="relative" @click.outside="openDropdown = openDropdown === 'competition' ? '' : openDropdown">
                    <button @click="openDropdown = openDropdown === 'competition' ? '' : 'competition'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border transition-colors"
                            :class="hasFilter('competition') ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-600 dark:bg-primary-900/30 dark:text-primary-300' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'">
                        <span x-show="hasFilter('competition')" class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                        Competition
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openDropdown === 'competition'" x-cloak x-transition
                         class="absolute left-0 top-full mt-1 z-20 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-3 w-48">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Seleziona livello</p>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="low" x-model="filters.competitions" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">Low</span>
                        </label>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="medium" x-model="filters.competitions" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                            <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">Medium</span>
                        </label>
                        <label class="flex items-center gap-2 py-1 cursor-pointer">
                            <input type="checkbox" value="high" x-model="filters.competitions" @change="currentPage = 1"
                                   class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                            <span class="text-sm text-red-600 dark:text-red-400 font-medium">High</span>
                        </label>
                        <button @click="filters.competitions = []; currentPage = 1"
                                class="mt-2 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 underline">Reset</button>
                    </div>
                </div>

                <!-- Separator -->
                <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>

                <!-- Include Keywords -->
                <div class="relative flex items-center">
                    <svg class="w-4 h-4 text-slate-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <input type="text" x-model="filters.includeKeywords" @input="currentPage = 1"
                           placeholder="Includi parole..."
                           class="w-36 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-2 focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Exclude Keywords -->
                <div class="relative flex items-center">
                    <svg class="w-4 h-4 text-slate-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    <input type="text" x-model="filters.excludeKeywords" @input="currentPage = 1"
                           placeholder="Escludi parole..."
                           class="w-36 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm py-1.5 px-2 focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Reset All -->
                <button x-show="hasAnyFilter" x-cloak @click="resetAllFilters()"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 underline">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset filtri
                </button>
            </div>

            <!-- Stats Bar -->
            <div x-show="hasAnyFilter" x-cloak x-transition
                 class="mt-3 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-700/30 rounded-lg px-3 py-2">
                <span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="filteredKeywords.length"></span> keyword filtrate
                </span>
                <span class="w-px h-3 bg-slate-300 dark:bg-slate-600"></span>
                <span>
                    Volume totale: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="statsVolume.toLocaleString()"></span>
                </span>
                <span class="w-px h-3 bg-slate-300 dark:bg-slate-600"></span>
                <span>
                    CPC medio: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="statsCpcAvg + ' EUR'"></span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" @change="toggleAll($event)" :checked="isAllSelected"
                                   class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </th>
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
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
                        :class="selectedKeywords.includes(kw.text) ? 'bg-primary-50/50 dark:bg-primary-900/10' : ''">
                        <td class="px-4 py-3">
                            <input type="checkbox" :value="kw.text" x-model="selectedKeywords"
                                   class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        </td>
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

        <!-- Floating Action Bar -->
        <div x-show="selectedKeywords.length > 0" x-cloak x-transition
             class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl rounded-xl px-5 py-3 flex items-center gap-4">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                <span class="text-primary-600 dark:text-primary-400 font-bold" x-text="selectedKeywords.length"></span> keyword selezionate
            </span>
            <?php if (!empty($stProjects)): ?>
            <button @click="showSendModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Invia a SEO Tracking
            </button>
            <?php endif; ?>
            <button @click="selectedKeywords = []"
                    class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                Deseleziona
            </button>
        </div>

        <!-- Modal: Invia a SEO Tracking -->
        <?php if (!empty($stProjects)): ?>
        <div x-show="showSendModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div x-show="showSendModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="fixed inset-0 bg-slate-900/50"
                     @click="showSendModal = false"></div>

                <div x-show="showSendModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Invia a SEO Tracking</h3>
                        <button @click="showSendModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Invierai <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedKeywords.length"></span> keyword al progetto selezionato per monitorare il posizionamento.
                    </p>

                    <!-- Progetto -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Progetto</label>
                        <select x-model="stSelectedProject" @change="loadProjectGroups()"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Seleziona progetto...</option>
                            <?php foreach ($stProjects as $sp): ?>
                            <option value="<?= $sp['id'] ?>"><?= e($sp['name']) ?><?= !empty($sp['domain']) ? ' (' . e($sp['domain']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Gruppo -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Gruppo (opzionale)</label>
                        <input type="text" x-model="stGroupName" list="st-groups-list"
                               placeholder="Es: brand, local, prodotti..."
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <datalist id="st-groups-list">
                            <template x-for="g in stProjectGroups" :key="g">
                                <option :value="g"></option>
                            </template>
                        </datalist>
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Le keyword verranno aggiunte a questo gruppo nel progetto</p>
                    </div>

                    <!-- Toast messaggio -->
                    <div x-show="sendMessage" x-cloak x-transition class="mb-4 rounded-lg p-3 text-sm"
                         :class="sendSuccess ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'"
                         x-text="sendMessage"></div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button @click="showSendModal = false"
                                class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            Annulla
                        </button>
                        <button @click="sendToTracking()" :disabled="sendLoading || !stSelectedProject"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50">
                            <svg x-show="sendLoading" x-cloak class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="sendLoading ? 'Invio...' : 'Invia'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function quickCheckTable() {
        return {
            keywords: <?= json_encode($results['related']) ?>,
            sortField: 'volume',
            sortDir: 'desc',
            currentPage: 1,
            perPage: 25,
            openDropdown: '',

            // Filters
            filters: {
                volumeMin: '',
                volumeMax: '',
                cpcMin: '',
                cpcMax: '',
                intents: [],
                competitions: [],
                includeKeywords: '',
                excludeKeywords: '',
            },

            // Selection
            selectedKeywords: [],

            // Send to Tracking
            showSendModal: false,
            sendLoading: false,
            sendMessage: '',
            sendSuccess: false,
            stSelectedProject: '',
            stGroupName: '',
            stProjectGroups: [],
            csrfToken: '<?= csrf_token() ?>',

            hasFilter(type) {
                switch (type) {
                    case 'volume': return this.filters.volumeMin !== '' || this.filters.volumeMax !== '';
                    case 'cpc': return this.filters.cpcMin !== '' || this.filters.cpcMax !== '';
                    case 'intent': return this.filters.intents.length > 0;
                    case 'competition': return this.filters.competitions.length > 0;
                    default: return false;
                }
            },

            get hasAnyFilter() {
                return this.hasFilter('volume') || this.hasFilter('cpc') ||
                       this.hasFilter('intent') || this.hasFilter('competition') ||
                       this.filters.includeKeywords.trim() !== '' || this.filters.excludeKeywords.trim() !== '';
            },

            resetAllFilters() {
                this.filters = {
                    volumeMin: '', volumeMax: '',
                    cpcMin: '', cpcMax: '',
                    intents: [], competitions: [],
                    includeKeywords: '', excludeKeywords: '',
                };
                this.currentPage = 1;
            },

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

                // Volume range
                if (this.filters.volumeMin !== '') {
                    result = result.filter(kw => (kw.volume || 0) >= this.filters.volumeMin);
                }
                if (this.filters.volumeMax !== '') {
                    result = result.filter(kw => (kw.volume || 0) <= this.filters.volumeMax);
                }

                // CPC range
                if (this.filters.cpcMin !== '') {
                    result = result.filter(kw => (kw.high_bid || 0) >= this.filters.cpcMin);
                }
                if (this.filters.cpcMax !== '') {
                    result = result.filter(kw => (kw.high_bid || 0) <= this.filters.cpcMax);
                }

                // Intent multi-select
                if (this.filters.intents.length > 0) {
                    result = result.filter(kw => this.filters.intents.includes((kw.intent || '').toLowerCase()));
                }

                // Competition multi-select
                if (this.filters.competitions.length > 0) {
                    result = result.filter(kw => this.filters.competitions.includes((kw.competition_level || '').toLowerCase()));
                }

                // Include keywords (comma-separated, OR logic)
                if (this.filters.includeKeywords.trim()) {
                    const terms = this.filters.includeKeywords.split(',').map(t => t.trim().toLowerCase()).filter(t => t);
                    if (terms.length > 0) {
                        result = result.filter(kw => {
                            const text = (kw.text || '').toLowerCase();
                            return terms.some(term => text.includes(term));
                        });
                    }
                }

                // Exclude keywords (comma-separated, AND logic)
                if (this.filters.excludeKeywords.trim()) {
                    const terms = this.filters.excludeKeywords.split(',').map(t => t.trim().toLowerCase()).filter(t => t);
                    if (terms.length > 0) {
                        result = result.filter(kw => {
                            const text = (kw.text || '').toLowerCase();
                            return !terms.some(term => text.includes(term));
                        });
                    }
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
            },

            // Stats
            get statsVolume() {
                return this.filteredKeywords.reduce((sum, kw) => sum + (kw.volume || 0), 0);
            },

            get statsCpcAvg() {
                const kwsWithCpc = this.filteredKeywords.filter(kw => (kw.high_bid || 0) > 0);
                if (kwsWithCpc.length === 0) return '0.00';
                const avg = kwsWithCpc.reduce((sum, kw) => sum + kw.high_bid, 0) / kwsWithCpc.length;
                return avg.toFixed(2);
            },

            // Selection
            get isAllSelected() {
                if (this.paginatedKeywords.length === 0) return false;
                return this.paginatedKeywords.every(kw => this.selectedKeywords.includes(kw.text));
            },

            toggleAll(event) {
                const pageTexts = this.paginatedKeywords.map(kw => kw.text);
                if (event.target.checked) {
                    const newSelected = [...this.selectedKeywords];
                    pageTexts.forEach(t => {
                        if (!newSelected.includes(t)) newSelected.push(t);
                    });
                    this.selectedKeywords = newSelected;
                } else {
                    this.selectedKeywords = this.selectedKeywords.filter(t => !pageTexts.includes(t));
                }
            },

            // Send to SEO Tracking
            async loadProjectGroups() {
                this.stProjectGroups = [];
                this.stGroupName = '';
                if (!this.stSelectedProject) return;

                try {
                    const resp = await fetch('<?= url('/keyword-research/quick-check/project-groups') ?>?project_id=' + this.stSelectedProject);
                    const data = await resp.json();
                    if (data.success) {
                        this.stProjectGroups = data.groups;
                    }
                } catch (e) {
                    console.error('Errore caricamento gruppi:', e);
                }
            },

            getSelectedKeywordsData() {
                return this.selectedKeywords.map(text => {
                    const kw = this.keywords.find(k => k.text === text);
                    if (!kw) return { text };
                    return {
                        text: kw.text,
                        volume: kw.volume || 0,
                        high_bid: kw.high_bid || 0,
                        competition_level: kw.competition_level || '',
                        intent: kw.intent || '',
                    };
                });
            },

            async sendToTracking() {
                if (!this.stSelectedProject || this.selectedKeywords.length === 0) return;

                this.sendLoading = true;
                this.sendMessage = '';

                try {
                    const formData = new FormData();
                    formData.append('_csrf_token', this.csrfToken);
                    formData.append('project_id', this.stSelectedProject);
                    formData.append('group_name', this.stGroupName);
                    formData.append('location', '<?= e($location ?? 'IT') ?>');
                    formData.append('keywords_data', JSON.stringify(this.getSelectedKeywordsData()));

                    const resp = await fetch('<?= url('/keyword-research/quick-check/send-to-tracking') ?>', {
                        method: 'POST',
                        body: formData,
                    });

                    const data = await resp.json();

                    if (data.success) {
                        let msg = data.added + ' keyword aggiunte al progetto';
                        if (data.skipped > 0) {
                            msg += ' (' + data.skipped + ' duplicate saltate)';
                        }
                        this.sendMessage = msg;
                        this.sendSuccess = true;
                        this.selectedKeywords = [];

                        setTimeout(() => {
                            this.showSendModal = false;
                            this.sendMessage = '';
                        }, 2500);
                    } else {
                        this.sendMessage = data.error || 'Errore durante l\'invio.';
                        this.sendSuccess = false;
                    }
                } catch (e) {
                    this.sendMessage = 'Errore di connessione.';
                    this.sendSuccess = false;
                }

                this.sendLoading = false;
            },
        };
    }
    </script>
    <?php endif; ?>
    <?php endif; ?>
</div>
