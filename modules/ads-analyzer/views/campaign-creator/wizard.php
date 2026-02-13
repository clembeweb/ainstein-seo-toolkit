<?php
$isPmax = ($project['campaign_type_gads'] ?? 'search') === 'pmax';
$campaignTypeLabel = $isPmax ? 'Performance Max' : 'Search';
$keywordsJson = json_encode($keywords ?? []);
$campaignJson = json_encode($campaign['assets'] ?? []);
$projectId = $project['id'];
?>

<div class="space-y-6" x-data="campaignCreatorWizard()">

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/ads-analyzer/projects') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Progetti
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Campaign Creator &middot; <?= $campaignTypeLabel ?>
            </p>
        </div>
    </div>

    <!-- Stepper -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <template x-for="(step, idx) in steps" :key="idx">
                <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="currentStep > idx ? 'bg-emerald-500 text-white' : (currentStep === idx ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500')">
                            <template x-if="currentStep > idx">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <template x-if="currentStep <= idx">
                                <span x-text="idx + 1"></span>
                            </template>
                        </div>
                        <span class="text-sm font-medium hidden sm:block"
                              :class="currentStep >= idx ? 'text-slate-900 dark:text-white' : 'text-slate-400'"
                              x-text="step"></span>
                    </div>
                    <template x-if="idx < steps.length - 1">
                        <div class="flex-1 mx-4 h-0.5 rounded"
                             :class="currentStep > idx ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STEP 1: Info Progetto -->
    <!-- ============================================ -->
    <div x-show="currentStep === 0" x-transition>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Riepilogo Progetto</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Tipo campagna</p>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                            <?= $campaignTypeLabel ?>
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Landing page</p>
                    <a href="<?= e($project['landing_url'] ?? '#') ?>" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all"><?= e($project['landing_url'] ?? '') ?></a>
                </div>
            </div>

            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Brief campagna</p>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line"><?= e($project['brief'] ?? '') ?></p>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-amber-700 dark:text-amber-300">
                        <p class="font-medium">Costo operazione</p>
                        <p class="mt-1">Scraping landing (1 credito) + Keyword Research AI (3 crediti) = <strong>4 crediti</strong></p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="startKeywordResearch()" :disabled="loading"
                        class="inline-flex items-center px-5 py-2.5 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors disabled:opacity-50">
                    <svg x-show="loading" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Analisi in corso...' : 'Avvia Analisi'"></span>
                    <svg x-show="!loading" class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading state -->
        <div x-show="loading" x-transition class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center mt-6">
            <svg class="w-12 h-12 text-amber-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-slate-600 dark:text-slate-400" x-text="statusMsg"></p>
            <p class="mt-1 text-sm text-slate-400">Potrebbe richiedere 30-60 secondi</p>
        </div>

        <!-- Error -->
        <div x-show="errorMsg" x-transition class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mt-4">
            <p class="text-sm text-red-700 dark:text-red-300" x-text="errorMsg"></p>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STEP 2: Keyword Review -->
    <!-- ============================================ -->
    <div x-show="currentStep === 1" x-transition>
        <div class="space-y-6">

            <!-- Keyword stats -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        <?= $isPmax ? 'Search Themes e Audience' : 'Keywords per Ad Group' ?>
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            <span class="font-medium text-slate-900 dark:text-white" x-text="selectedPositive"></span> selezionate,
                            <span class="font-medium text-red-600 dark:text-red-400" x-text="selectedNegative"></span> negative
                        </span>
                    </div>
                </div>

                <!-- Keywords positive -->
                <?php if ($isPmax): ?>
                <!-- PMax: Search Themes -->
                <div class="space-y-2">
                    <template x-for="kw in positiveKeywords" :key="kw.id">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <label class="flex items-center gap-3 flex-1 cursor-pointer">
                                <input type="checkbox" :checked="kw.is_selected == 1"
                                       @change="toggleKw(kw)"
                                       class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                                <span class="text-sm text-slate-900 dark:text-white" x-text="kw.keyword"></span>
                            </label>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="kw.keyword.length <= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                  x-text="kw.keyword.length + '/80'"></span>
                        </div>
                    </template>
                </div>
                <?php else: ?>
                <!-- Search: Keywords per Ad Group -->
                <div class="space-y-4">
                    <template x-for="(kws, group) in keywordsByGroup" :key="group">
                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                            <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-2">
                                <h4 class="text-sm font-medium text-slate-900 dark:text-white" x-text="group"></h4>
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                <template x-for="kw in kws" :key="kw.id">
                                    <div class="flex items-center justify-between py-2 px-4">
                                        <label class="flex items-center gap-3 flex-1 cursor-pointer">
                                            <input type="checkbox" :checked="kw.is_selected == 1"
                                                   @change="toggleKw(kw)"
                                                   class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                                            <span class="text-sm text-slate-900 dark:text-white" x-text="kw.keyword"></span>
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <span x-show="kw.intent" class="text-xs px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300" x-text="kw.intent"></span>
                                            <select @change="updateMatchType(kw, $event.target.value)"
                                                    :value="kw.match_type"
                                                    class="text-xs rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 py-0.5 px-1.5">
                                                <option value="broad">Broad</option>
                                                <option value="phrase">Phrase</option>
                                                <option value="exact">Exact</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <?php endif; ?>
            </div>

            <!-- Negative Keywords -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                 x-show="negativeKeywords.length > 0">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Keyword Negative
                </h3>
                <div class="space-y-2">
                    <template x-for="kw in negativeKeywords" :key="kw.id">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <label class="flex items-center gap-3 flex-1 cursor-pointer">
                                <input type="checkbox" :checked="kw.is_selected == 1"
                                       @change="toggleKw(kw)"
                                       class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-slate-900 dark:text-white" x-text="kw.keyword"></span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span x-show="kw.reason" class="text-xs text-slate-400 dark:text-slate-500 max-w-48 truncate" x-text="kw.reason"></span>
                                <select @change="updateMatchType(kw, $event.target.value)"
                                        :value="kw.match_type"
                                        class="text-xs rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 py-0.5 px-1.5">
                                    <option value="broad">Broad</option>
                                    <option value="phrase">Phrase</option>
                                    <option value="exact">Exact</option>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <button type="button" @click="regenerateStep('keywords')" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Rigenera Keywords
                </button>
                <button type="button" @click="startCampaignGeneration()" :disabled="loading || selectedPositive === 0"
                        class="inline-flex items-center px-5 py-2.5 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors disabled:opacity-50">
                    <svg x-show="loading" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Generazione in corso...' : 'Genera Campagna (5 crediti)'"></span>
                    <svg x-show="!loading" class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </div>

            <!-- Error -->
            <div x-show="errorMsg" x-transition class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <p class="text-sm text-red-700 dark:text-red-300" x-text="errorMsg"></p>
            </div>

            <!-- Loading -->
            <div x-show="loading" x-transition class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
                <svg class="w-12 h-12 text-amber-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-slate-600 dark:text-slate-400" x-text="statusMsg"></p>
                <p class="mt-1 text-sm text-slate-400">Potrebbe richiedere 30-90 secondi</p>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STEP 3: Risultati Campagna -->
    <!-- ============================================ -->
    <div x-show="currentStep === 2" x-transition>
        <div class="space-y-6">

            <!-- Actions bar -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Campagna Generata</h2>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="copyAll()"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            Copia Tutto
                        </button>
                        <a href="<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/export") ?>"
                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Esporta CSV
                        </a>
                        <button type="button" @click="regenerateStep('campaign')" :disabled="loading"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Rigenera
                        </button>
                    </div>
                </div>
            </div>

            <!-- Headlines -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Headlines <span class="text-xs text-slate-400 font-normal">(max 30 char)</span></h3>
                    <button @click="copySection('headlines')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <template x-for="(h, i) in campaignAssets.headlines || []" :key="'h'+i">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                            <span class="text-sm text-slate-900 dark:text-white" x-text="h"></span>
                            <span class="text-xs font-medium ml-2 flex-shrink-0"
                                  :class="h.length <= 30 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                  x-text="h.length + '/30'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Long Headlines (PMax only) -->
            <?php if ($isPmax): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                 x-show="campaignAssets.long_headlines && campaignAssets.long_headlines.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Long Headlines <span class="text-xs text-slate-400 font-normal">(max 90 char)</span></h3>
                    <button @click="copySection('long_headlines')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(lh, i) in campaignAssets.long_headlines || []" :key="'lh'+i">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                            <span class="text-sm text-slate-900 dark:text-white" x-text="lh"></span>
                            <span class="text-xs font-medium ml-2 flex-shrink-0"
                                  :class="lh.length <= 90 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                  x-text="lh.length + '/90'"></span>
                        </div>
                    </template>
                </div>
            </div>
            <?php endif; ?>

            <!-- Descriptions -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Descriptions <span class="text-xs text-slate-400 font-normal">(max 90 char)</span></h3>
                    <button @click="copySection('descriptions')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(d, i) in campaignAssets.descriptions || []" :key="'d'+i">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                            <span class="text-sm text-slate-900 dark:text-white" x-text="d"></span>
                            <span class="text-xs font-medium ml-2 flex-shrink-0"
                                  :class="d.length <= 90 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                  x-text="d.length + '/90'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Display Paths + Business Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Display Paths -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                     x-show="campaignAssets.display_paths">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-3">Display Paths <span class="text-xs text-slate-400 font-normal">(max 15 char)</span></h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                            <span class="text-sm"><span class="text-slate-400">esempio.it/</span><span class="text-slate-900 dark:text-white font-medium" x-text="campaignAssets.display_paths?.path1 || ''"></span></span>
                            <span class="text-xs font-medium" :class="(campaignAssets.display_paths?.path1 || '').length <= 15 ? 'text-emerald-600' : 'text-red-600'"
                                  x-text="(campaignAssets.display_paths?.path1 || '').length + '/15'"></span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                            <span class="text-sm"><span class="text-slate-400">esempio.it/.../</span><span class="text-slate-900 dark:text-white font-medium" x-text="campaignAssets.display_paths?.path2 || ''"></span></span>
                            <span class="text-xs font-medium" :class="(campaignAssets.display_paths?.path2 || '').length <= 15 ? 'text-emerald-600' : 'text-red-600'"
                                  x-text="(campaignAssets.display_paths?.path2 || '').length + '/15'"></span>
                        </div>
                    </div>
                </div>

                <!-- Business Name (PMax) -->
                <?php if ($isPmax): ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                     x-show="campaignAssets.business_name">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-3">Business Name <span class="text-xs text-slate-400 font-normal">(max 25 char)</span></h3>
                    <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                        <span class="text-sm font-medium text-slate-900 dark:text-white" x-text="campaignAssets.business_name"></span>
                        <span class="text-xs font-medium"
                              :class="(campaignAssets.business_name || '').length <= 25 ? 'text-emerald-600' : 'text-red-600'"
                              x-text="(campaignAssets.business_name || '').length + '/25'"></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sitelinks -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                 x-show="campaignAssets.sitelinks && campaignAssets.sitelinks.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Sitelinks</h3>
                    <button @click="copySection('sitelinks')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="(sl, i) in campaignAssets.sitelinks || []" :key="'sl'+i">
                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-slate-900 dark:text-white" x-text="sl.title"></span>
                                <span class="text-xs font-medium"
                                      :class="(sl.title || '').length <= 25 ? 'text-emerald-600' : 'text-red-600'"
                                      x-text="(sl.title || '').length + '/25'"></span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="sl.desc1"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="sl.desc2"></p>
                            <p x-show="sl.url" class="text-xs text-blue-500 dark:text-blue-400 mt-1" x-text="sl.url"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Callouts -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                 x-show="campaignAssets.callouts && campaignAssets.callouts.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Callouts <span class="text-xs text-slate-400 font-normal">(max 25 char)</span></h3>
                    <button @click="copySection('callouts')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(c, i) in campaignAssets.callouts || []" :key="'c'+i">
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm border"
                              :class="c.length <= 25 ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'">
                            <span x-text="c"></span>
                            <span class="text-xs opacity-70" x-text="c.length + '/25'"></span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Structured Snippets -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                 x-show="campaignAssets.structured_snippets && campaignAssets.structured_snippets.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Structured Snippets <span class="text-xs text-slate-400 font-normal">(max 25 char/valore)</span></h3>
                    <button @click="copySection('snippets')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="space-y-4">
                    <template x-for="(snippet, i) in campaignAssets.structured_snippets || []" :key="'ss'+i">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white mb-2">
                                Header: <span class="text-amber-600 dark:text-amber-400" x-text="snippet.header"></span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(v, j) in snippet.values || []" :key="'sv'+i+'-'+j">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs border"
                                          :class="v.length <= 25 ? 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300' : 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700'">
                                        <span x-text="v"></span>
                                        <span class="opacity-50" x-text="v.length + '/25'"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Keywords / Search Themes (dalla Step 2) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white"><?= $isPmax ? 'Search Themes' : 'Keywords' ?></h3>
                    <button @click="copySection('keywords')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <?php if ($isPmax): ?>
                <div class="flex flex-wrap gap-2">
                    <template x-for="kw in positiveKeywords.filter(k => k.is_selected == 1)" :key="'pk'+kw.id">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                              x-text="kw.keyword"></span>
                    </template>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <template x-for="(kws, group) in selectedKeywordsByGroup" :key="'sg'+group">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white mb-1" x-text="group"></p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="kw in kws" :key="'sgk'+kw.id">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                          x-text="formatMatchType(kw.keyword, kw.match_type)"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <?php endif; ?>
            </div>

            <!-- Negative Keywords (dalla Step 2) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                 x-show="negativeKeywords.filter(k => k.is_selected == 1).length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Keyword Negative</h3>
                    <button @click="copySection('negatives')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="kw in negativeKeywords.filter(k => k.is_selected == 1)" :key="'nk'+kw.id">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800"
                              x-text="formatMatchType(kw.keyword, kw.match_type)"></span>
                    </template>
                </div>
            </div>

            <!-- Copy toast -->
            <div x-show="copySuccess" x-transition
                 class="fixed bottom-6 right-6 bg-emerald-600 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium z-50">
                Copiato negli appunti!
            </div>
        </div>
    </div>
</div>

<script>
function campaignCreatorWizard() {
    return {
        steps: ['Input', 'Keywords', 'Campagna'],
        currentStep: <?= $currentStep ?>,
        projectId: <?= $projectId ?>,
        csrfToken: '<?= csrf_token() ?>',
        isPmax: <?= $isPmax ? 'true' : 'false' ?>,

        // State
        loading: false,
        statusMsg: '',
        errorMsg: '',
        copySuccess: false,

        // Keywords
        keywords: <?= $keywordsJson ?>,

        // Campaign assets
        campaignAssets: <?= $campaignJson ?>,

        // Computed
        get positiveKeywords() {
            return this.keywords.filter(k => !parseInt(k.is_negative));
        },
        get negativeKeywords() {
            return this.keywords.filter(k => parseInt(k.is_negative));
        },
        get selectedPositive() {
            return this.positiveKeywords.filter(k => parseInt(k.is_selected)).length;
        },
        get selectedNegative() {
            return this.negativeKeywords.filter(k => parseInt(k.is_selected)).length;
        },
        get keywordsByGroup() {
            const groups = {};
            this.positiveKeywords.forEach(kw => {
                const g = kw.ad_group_name || 'Generale';
                if (!groups[g]) groups[g] = [];
                groups[g].push(kw);
            });
            return groups;
        },
        get selectedKeywordsByGroup() {
            const groups = {};
            this.positiveKeywords.filter(k => parseInt(k.is_selected)).forEach(kw => {
                const g = kw.ad_group_name || 'Generale';
                if (!groups[g]) groups[g] = [];
                groups[g].push(kw);
            });
            return groups;
        },

        // Step 1: Keyword Research
        async startKeywordResearch() {
            this.loading = true;
            this.errorMsg = '';
            this.statusMsg = 'Analisi landing page e keyword research AI...';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/generate-kw") ?>`, {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    if (resp.status === 502 || resp.status === 504) {
                        this.statusMsg = 'Operazione avviata. Ricarica tra qualche secondo.';
                        setTimeout(() => location.reload(), 15000);
                        return;
                    }
                    const err = await resp.json().catch(() => ({}));
                    this.errorMsg = err.error || 'Errore durante la keyword research';
                    this.loading = false;
                    return;
                }

                const data = await resp.json();

                if (!data.success) {
                    this.errorMsg = data.error || 'Errore sconosciuto';
                    this.loading = false;
                    return;
                }

                // Ricarica per vedere le keyword
                location.reload();

            } catch (e) {
                this.errorMsg = 'Errore di connessione. Riprova.';
                this.loading = false;
            }
        },

        // Step 2: Toggle keyword
        async toggleKw(kw) {
            kw.is_selected = kw.is_selected == 1 ? 0 : 1;

            try {
                await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/toggle-kw") ?>/${kw.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken },
                });
            } catch (e) {
                // Revert on error
                kw.is_selected = kw.is_selected == 1 ? 0 : 1;
            }
        },

        async updateMatchType(kw, newType) {
            const oldType = kw.match_type;
            kw.match_type = newType;

            const formData = new FormData();
            formData.append('match_type', newType);

            try {
                await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/update-match") ?>/${kw.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken },
                    body: formData,
                });
            } catch (e) {
                kw.match_type = oldType;
            }
        },

        // Step 2 → 3: Generate Campaign
        async startCampaignGeneration() {
            this.loading = true;
            this.errorMsg = '';
            this.statusMsg = 'Generazione campagna completa con AI...';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/generate") ?>`, {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    if (resp.status === 502 || resp.status === 504) {
                        this.statusMsg = 'Operazione avviata. Ricarica tra qualche secondo.';
                        setTimeout(() => location.reload(), 15000);
                        return;
                    }
                    const err = await resp.json().catch(() => ({}));
                    this.errorMsg = err.error || 'Errore durante la generazione';
                    this.loading = false;
                    return;
                }

                const data = await resp.json();

                if (!data.success) {
                    this.errorMsg = data.error || 'Errore sconosciuto';
                    this.loading = false;
                    return;
                }

                // Ricarica per vedere la campagna
                location.reload();

            } catch (e) {
                this.errorMsg = 'Errore di connessione. Riprova.';
                this.loading = false;
            }
        },

        // Regenerate
        async regenerateStep(step) {
            if (!confirm(step === 'keywords' ? 'Rigenerare keywords e campagna? (verranno consumati crediti aggiuntivi)' : 'Rigenerare la campagna? (verranno consumati 5 crediti aggiuntivi)')) {
                return;
            }

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('step', step);

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/regenerate") ?>`, {
                    method: 'POST',
                    body: formData,
                });

                if (resp.ok) {
                    location.reload();
                }
            } catch (e) {
                this.errorMsg = 'Errore nella rigenerazione';
            }
        },

        // Copy functions
        async copyAll() {
            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('section', 'all');

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/copy-text") ?>`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await resp.json();

                if (data.success) {
                    await navigator.clipboard.writeText(data.text);
                    this.showCopyToast();
                }
            } catch (e) {
                // Fallback
            }
        },

        async copySection(section) {
            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('section', section);

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/copy-text") ?>`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await resp.json();

                if (data.success) {
                    await navigator.clipboard.writeText(data.text);
                    this.showCopyToast();
                }
            } catch (e) {
                // Fallback
            }
        },

        showCopyToast() {
            this.copySuccess = true;
            setTimeout(() => this.copySuccess = false, 2000);
        },

        // Helpers
        formatMatchType(keyword, matchType) {
            if (matchType === 'exact') return '[' + keyword + ']';
            if (matchType === 'phrase') return '"' + keyword + '"';
            return keyword;
        },
    };
}
</script>
