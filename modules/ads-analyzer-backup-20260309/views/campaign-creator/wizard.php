<?php
$canEdit = ($access_role ?? 'owner') !== 'viewer';
$isPmax = ($project['campaign_type_gads'] ?? 'search') === 'pmax';
$campaignTypeLabel = $isPmax ? 'Performance Max' : 'Search';
$keywordsJson = json_encode($keywords ?? []);
$campaignJson = json_encode($campaign['assets'] ?? []);
$projectId = $project['id'];
$inputMode = $project['input_mode'] ?? 'url';
$hasUrl = !empty($project['landing_url']);
$hasBrief = !empty($project['brief']);
$needsScraping = $inputMode !== 'brief'; // url e both hanno scraping
$isAutoBrief = ($inputMode === 'url' && $hasBrief && ($hasScrapedData ?? false));
$canEditBrief = ($inputMode === 'url'); // solo url mode permette edit brief
$scrapeCost = \Modules\AdsAnalyzer\Services\CampaignCreatorService::getCost('scrape');
$kwCost = \Modules\AdsAnalyzer\Services\CampaignCreatorService::getCost('keywords');
?>

<div class="space-y-6" x-data="campaignCreatorWizard()">

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/ads-analyzer/projects?type=campaign-creator') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
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
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
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
    <!-- STEP 1: Info Progetto (Phase A/B) -->
    <!-- ============================================ -->
    <div x-show="currentStep === 0" x-transition>

        <!-- Riepilogo Progetto (sempre visibile) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
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
                <?php if ($hasUrl): ?>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Landing page</p>
                    <a href="<?= e($project['landing_url']) ?>" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all"><?= e($project['landing_url']) ?></a>
                </div>
                <?php endif; ?>
            </div>

            <!-- ====== PHASE A: Analizza Landing (solo url/both, pre-scraping) ====== -->
            <?php if ($needsScraping): ?>
            <div x-show="!phaseADone" x-transition>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-2">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium">Analisi Landing Page</p>
                            <p class="mt-1">La pagina verra analizzata per estrarre contesto, prodotti/servizi e informazioni utili per la campagna.
                            <?php if ($inputMode === 'url'): ?>
                            L'AI generera automaticamente anche un brief di campagna.</p>
                            <?php else: ?>
                            Il brief fornito verra mantenuto.</p>
                            <?php endif; ?>
                            <p class="mt-1 text-xs">Costo: <strong><?= $scrapeCost ?> credito</strong></p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <?php if ($canEdit): ?>
                    <button type="button" @click="analyzeLanding()" :disabled="analyzingLanding"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
                        <svg x-show="analyzingLanding" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="analyzingLanding ? 'Analisi in corso...' : 'Analizza Landing Page'"></span>
                        <svg x-show="!analyzingLanding" class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <?php else: ?>
                    <span class="text-sm text-slate-500 dark:text-slate-400">Accesso in sola lettura</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Loading scraping -->
            <div x-show="analyzingLanding" x-transition class="mt-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 text-center">
                    <svg class="w-10 h-10 text-blue-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-3 text-slate-600 dark:text-slate-400 text-sm">Analisi della landing page in corso...</p>
                    <p class="mt-1 text-xs text-slate-400">Potrebbe richiedere 20-40 secondi</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ====== PHASE B: Risultati analisi + Avvia KW Research ====== -->
            <div x-show="phaseADone" x-transition>

                <!-- Contesto estratto (collapsabile) -->
                <template x-if="scrapedContext">
                    <div class="mt-4">
                        <button type="button" @click="showContext = !showContext"
                                class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                            <svg class="w-4 h-4 transition-transform" :class="showContext ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Contesto estratto dalla landing page
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Completato
                            </span>
                        </button>
                        <div x-show="showContext" x-transition x-cloak class="mt-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                            <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line" x-text="scrapedContext"></p>
                        </div>
                    </div>
                </template>

                <!-- Brief campagna -->
                <div class="mt-4">
                    <div class="flex items-center gap-2 mb-2">
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wide">Brief campagna</p>
                        <template x-if="isAutoBrief">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                Generato da AI
                            </span>
                        </template>
                        <?php if ($canEditBrief): ?>
                        <button type="button" @click="editingBrief = !editingBrief; if(editingBrief) editedBrief = brief"
                                x-show="!editingBrief" x-cloak
                                class="text-xs text-amber-600 dark:text-amber-400 hover:underline">
                            Modifica
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Brief read-only -->
                    <div x-show="!editingBrief">
                        <template x-if="brief">
                            <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 border border-slate-200 dark:border-slate-600" x-text="brief"></p>
                        </template>
                        <template x-if="!brief">
                            <p class="text-sm text-slate-500 dark:text-slate-400 italic">Nessun brief disponibile</p>
                        </template>
                    </div>

                    <!-- Brief editing -->
                    <?php if ($canEditBrief): ?>
                    <div x-show="editingBrief" x-transition x-cloak class="space-y-2">
                        <textarea x-model="editedBrief" rows="6"
                                  class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                                  placeholder="Descrivi il prodotto/servizio, il target, gli obiettivi..."></textarea>
                        <div class="flex items-center gap-2 justify-end">
                            <button type="button" @click="editingBrief = false"
                                    class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">
                                Annulla
                            </button>
                            <button type="button" @click="saveBrief()" :disabled="savingBrief"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50">
                                <svg x-show="savingBrief" x-cloak class="w-3 h-3 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Salva Brief
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Costo keyword research -->
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mt-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-amber-700 dark:text-amber-300">
                            <p class="font-medium">Prossimo passo: Keyword Research AI</p>
                            <p class="mt-1">L'AI analizera il contesto e il brief per generare keyword e gruppi di annunci ottimizzati.</p>
                            <p class="mt-1 text-xs">Costo: <strong><?= $kwCost ?> crediti</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Azioni Phase B -->
                <div class="flex items-center justify-between mt-4">
                    <?php if ($canEdit && $needsScraping): ?>
                    <button type="button" @click="regenerateStep('landing')" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Rianalizza landing page
                    </button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <?php if ($canEdit): ?>
                    <button type="button" @click="startKeywordResearch()" :disabled="loading"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors disabled:opacity-50">
                        <svg x-show="loading" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Keyword Research in corso...' : 'Avvia Keyword Research'"></span>
                        <svg x-show="!loading" class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Loading keyword research -->
        <div x-show="loading" x-transition class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center mt-6">
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
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        <?= $isPmax ? 'Search Themes e Audience' : 'Keywords per Ad Group' ?>
                    </h2>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            <span class="font-medium text-slate-900 dark:text-white" x-text="selectedPositive"></span> selezionate,
                            <span class="font-medium text-red-600 dark:text-red-400" x-text="selectedNegative"></span> negative
                        </span>
                        <span x-show="totalVolume > 0" class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            Vol. totale: <span class="font-medium" x-text="totalVolume.toLocaleString('it-IT')"></span>
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
                            <div class="flex items-center gap-2">
                                <span x-show="kw.search_volume" class="text-xs text-slate-500 dark:text-slate-400" x-text="parseInt(kw.search_volume).toLocaleString('it-IT')"></span>
                                <span x-show="kw.cpc > 0" class="text-xs text-amber-600 dark:text-amber-400" x-text="'€' + parseFloat(kw.cpc).toFixed(2)"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      :class="kw.keyword.length <= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                      x-text="kw.keyword.length + '/80'"></span>
                            </div>
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
                                        <label class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer">
                                            <input type="checkbox" :checked="kw.is_selected == 1"
                                                   @change="toggleKw(kw)"
                                                   class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500 flex-shrink-0">
                                            <span class="text-sm text-slate-900 dark:text-white truncate" x-text="kw.keyword"></span>
                                        </label>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span x-show="kw.search_volume" class="text-xs text-slate-500 dark:text-slate-400 tabular-nums" x-text="parseInt(kw.search_volume).toLocaleString('it-IT')"></span>
                                            <span x-show="kw.cpc > 0" class="text-xs text-amber-600 dark:text-amber-400 tabular-nums" x-text="'€' + parseFloat(kw.cpc).toFixed(2)"></span>
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
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
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
            <?php if ($canEdit): ?>
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
                    <span x-text="loading ? 'Generazione in corso...' : 'Genera Campagna (10 cr Premium)'"></span>
                    <svg x-show="!loading" class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?>

            <!-- Error -->
            <div x-show="errorMsg" x-transition class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <p class="text-sm text-red-700 dark:text-red-300" x-text="errorMsg"></p>
            </div>

            <!-- Loading -->
            <div x-show="loading" x-transition class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
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

            <!-- ============================================ -->
            <!-- HERO SUMMARY -->
            <!-- ============================================ -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <!-- Icon -->
                    <div class="flex-shrink-0 flex justify-center">
                        <div class="w-16 h-16 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                            <?php if ($isPmax): ?>
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            </svg>
                            <?php else: ?>
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Summary & Meta -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Campagna Generata</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                <?= $campaignTypeLabel ?>
                            </span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">Campagna pronta per l'implementazione. Copia i contenuti o esporta il CSV per importarlo in Google Ads.</p>

                        <!-- Metadata row -->
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="(campaignAssets?.headlines || []).length + ' headlines'"></span>
                            <span x-text="(campaignAssets?.descriptions || []).length + ' descriptions'"></span>
                            <span x-text="Object.keys(selectedKeywordsByGroup).length + ' ad groups'"></span>
                            <span x-text="selectedPositive + ' keywords'"></span>
                            <template x-if="campaignAssets?.sitelinks?.length">
                                <span x-text="campaignAssets.sitelinks.length + ' sitelinks'"></span>
                            </template>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex-shrink-0 flex flex-col items-end gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="copyAll()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                Copia Tutto
                            </button>
                            <a :href="`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/export") ?>?budget=${selectedBudget}`"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                CSV
                            </a>
                            <?php if ($canEdit): ?>
                            <button type="button" @click="regenerateStep('campaign')" :disabled="loading"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Rigenera
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- KPI SUMMARY CARDS -->
            <!-- ============================================ -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Headlines</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white" x-text="(campaignAssets?.headlines || []).length"></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Descriptions</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white" x-text="(campaignAssets?.descriptions || []).length"></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Ad Groups</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white" x-text="Object.keys(selectedKeywordsByGroup).length"></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Keywords</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white" x-text="selectedPositive"></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Budget/giorno</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">
                        <template x-if="campaignAssets?.daily_budget">
                            <span x-text="'€' + Number(campaignAssets.daily_budget[selectedBudget] || 0).toFixed(0)"></span>
                        </template>
                        <template x-if="!campaignAssets?.daily_budget">
                            <span>-</span>
                        </template>
                    </p>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- TAB NAVIGATION -->
            <!-- ============================================ -->
            <div class="border-b border-slate-200 dark:border-slate-700">
                <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
                    <button @click="campaignTab = 'annunci'" :class="campaignTab === 'annunci' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                        class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                        Annunci
                    </button>
                    <button @click="campaignTab = 'estensioni'" :class="campaignTab === 'estensioni' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                        class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                        Estensioni
                    </button>
                    <button @click="campaignTab = 'keywords'" :class="campaignTab === 'keywords' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                        class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                        Keywords (<span x-text="selectedPositive"></span>)
                    </button>
                    <button @click="campaignTab = 'budget'" :class="campaignTab === 'budget' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 hover:border-slate-300'"
                        class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                        Budget
                    </button>
                </nav>
            </div>

            <!-- ============================================ -->
            <!-- TAB: ANNUNCI -->
            <!-- ============================================ -->
            <div x-show="campaignTab === 'annunci'" x-cloak class="space-y-6">

                <!-- Headlines -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Headlines <span class="text-xs text-slate-400 font-normal">(max 30 char)</span></h3>
                        </div>
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
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
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
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Descriptions <span class="text-xs text-slate-400 font-normal">(max 90 char)</span></h3>
                        </div>
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
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
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
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
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

            </div>

            <!-- ============================================ -->
            <!-- TAB: ESTENSIONI -->
            <!-- ============================================ -->
            <div x-show="campaignTab === 'estensioni'" x-cloak class="space-y-6">

                <!-- Sitelinks -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                     x-show="campaignAssets.sitelinks && campaignAssets.sitelinks.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Sitelinks</h3>
                        </div>
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
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                     x-show="campaignAssets.callouts && campaignAssets.callouts.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h.008v.008H6V6z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Callouts <span class="text-xs text-slate-400 font-normal">(max 25 char)</span></h3>
                        </div>
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
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                     x-show="campaignAssets.structured_snippets && campaignAssets.structured_snippets.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Structured Snippets <span class="text-xs text-slate-400 font-normal">(max 25 char/valore)</span></h3>
                        </div>
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

            </div>

            <!-- ============================================ -->
            <!-- TAB: KEYWORDS -->
            <!-- ============================================ -->
            <div x-show="campaignTab === 'keywords'" x-cloak class="space-y-6">

                <!-- Keywords / Search Themes -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white"><?= $isPmax ? 'Search Themes' : 'Keywords' ?></h3>
                        </div>
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

                <!-- Negative Keywords -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
                     x-show="negativeKeywords.filter(k => k.is_selected == 1).length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Keyword Negative</h3>
                        </div>
                        <button @click="copySection('negatives')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline">Copia</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="kw in negativeKeywords.filter(k => k.is_selected == 1)" :key="'nk'+kw.id">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800"
                                  x-text="formatMatchType(kw.keyword, kw.match_type)"></span>
                        </template>
                    </div>
                </div>

            </div>

            <!-- ============================================ -->
            <!-- TAB: BUDGET -->
            <!-- ============================================ -->
            <div x-show="campaignTab === 'budget'" x-cloak class="space-y-6">

                <template x-if="campaignAssets?.daily_budget">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 7.629A3 3 0 009.017 9.43c-.023.212-.002.425.028.636l.506 3.541a4.5 4.5 0 01-.43 2.65L9 16.5l1.539-.513a2.25 2.25 0 011.422 0l.655.218a2.25 2.25 0 001.718-.122L15 15.75M8.25 12H12m9 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Budget Giornaliero Consigliato</h3>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4" x-text="campaignAssets.daily_budget.rationale || 'Basato su CPC e volumi delle keyword selezionate'"></p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Conservativo -->
                            <button type="button" @click="selectedBudget = 'conservative'"
                                    :class="selectedBudget === 'conservative'
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 ring-1 ring-emerald-500'
                                        : 'border-slate-200 dark:border-slate-700 hover:border-emerald-300 dark:hover:border-emerald-700'"
                                    class="relative p-4 rounded-xl border text-left transition-all">
                                <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Conservativo</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">
                                    <span x-text="'€' + Number(campaignAssets.daily_budget.conservative).toFixed(0)"></span>
                                    <span class="text-sm font-normal text-slate-500">/giorno</span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Copertura parziale, minimo rischio</div>
                                <div x-show="selectedBudget === 'conservative'" class="absolute top-2 right-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            </button>
                            <!-- Moderato -->
                            <button type="button" @click="selectedBudget = 'moderate'"
                                    :class="selectedBudget === 'moderate'
                                        ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-500'
                                        : 'border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-700'"
                                    class="relative p-4 rounded-xl border text-left transition-all">
                                <div class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-1">Moderato</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">
                                    <span x-text="'€' + Number(campaignAssets.daily_budget.moderate).toFixed(0)"></span>
                                    <span class="text-sm font-normal text-slate-500">/giorno</span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Buon bilanciamento copertura/costo</div>
                                <div x-show="selectedBudget === 'moderate'" class="absolute top-2 right-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            </button>
                            <!-- Aggressivo -->
                            <button type="button" @click="selectedBudget = 'aggressive'"
                                    :class="selectedBudget === 'aggressive'
                                        ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/20 ring-1 ring-rose-500'
                                        : 'border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-700'"
                                    class="relative p-4 rounded-xl border text-left transition-all">
                                <div class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-1">Aggressivo</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">
                                    <span x-text="'€' + Number(campaignAssets.daily_budget.aggressive).toFixed(0)"></span>
                                    <span class="text-sm font-normal text-slate-500">/giorno</span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Massima copertura e impression share</div>
                                <div x-show="selectedBudget === 'aggressive'" class="absolute top-2 right-2">
                                    <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">
                            <span x-text="'Stima mensile: €' + (Number(campaignAssets.daily_budget[selectedBudget]) * 30.4).toFixed(0)"></span>
                            — Il budget selezionato verra incluso nel CSV export.
                        </p>
                    </div>
                </template>

                <template x-if="!campaignAssets?.daily_budget">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 text-center">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna raccomandazione budget disponibile per questa campagna.</p>
                    </div>
                </template>

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
        campaignTab: 'annunci',

        // Phase A/B (Step 0)
        needsScraping: <?= $needsScraping ? 'true' : 'false' ?>,
        phaseADone: <?= ($phaseAComplete ?? false) ? 'true' : 'false' ?>,
        analyzingLanding: false,
        scrapedContext: <?= json_encode($scrapedContext ?? '') ?>,
        brief: <?= json_encode($project['brief'] ?? '') ?>,
        isAutoBrief: <?= $isAutoBrief ? 'true' : 'false' ?>,
        canEditBrief: <?= $canEditBrief ? 'true' : 'false' ?>,
        editingBrief: false,
        editedBrief: '',
        savingBrief: false,
        showContext: false,

        // Keywords
        keywords: <?= $keywordsJson ?>,

        // Campaign assets
        campaignAssets: <?= $campaignJson ?>,
        selectedBudget: 'moderate',

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
        get totalVolume() {
            return this.positiveKeywords
                .filter(k => parseInt(k.is_selected))
                .reduce((sum, k) => sum + (parseInt(k.search_volume) || 0), 0);
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

        // Phase A: Analizza landing page
        async analyzeLanding() {
            this.analyzingLanding = true;
            this.errorMsg = '';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/analyze-landing") ?>`, {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    if (resp.status === 502 || resp.status === 504) {
                        this.errorMsg = 'Operazione avviata. Ricarica tra qualche secondo.';
                        setTimeout(() => location.reload(), 15000);
                        return;
                    }
                    const err = await resp.json().catch(() => ({}));
                    this.errorMsg = err.error || 'Errore durante l\'analisi della landing page';
                    this.analyzingLanding = false;
                    return;
                }

                const data = await resp.json();

                if (!data.success) {
                    this.errorMsg = data.error || 'Errore sconosciuto';
                    this.analyzingLanding = false;
                    return;
                }

                // Aggiorna stato Phase A → B
                this.scrapedContext = data.scraped_context || '';
                this.brief = data.brief || '';
                this.isAutoBrief = data.is_auto_brief || false;
                this.phaseADone = true;
                this.analyzingLanding = false;

            } catch (e) {
                this.errorMsg = 'Errore di connessione. Riprova.';
                this.analyzingLanding = false;
            }
        },

        // Salva brief editato
        async saveBrief() {
            if (this.editedBrief.trim().length < 20) {
                this.errorMsg = 'Il brief deve contenere almeno 20 caratteri';
                return;
            }

            this.savingBrief = true;
            this.errorMsg = '';

            const formData = new FormData();
            formData.append('_csrf_token', this.csrfToken);
            formData.append('brief', this.editedBrief.trim());

            try {
                const resp = await fetch(`<?= url("/ads-analyzer/projects/{$projectId}/campaign-creator/update-brief") ?>`, {
                    method: 'POST',
                    body: formData,
                });

                const data = await resp.json();

                if (data.success) {
                    this.brief = this.editedBrief.trim();
                    this.editingBrief = false;
                    this.isAutoBrief = false;
                } else {
                    this.errorMsg = data.error || 'Errore nel salvataggio';
                }
            } catch (e) {
                this.errorMsg = 'Errore di connessione. Riprova.';
            }

            this.savingBrief = false;
        },

        // Step 1: Keyword Research
        async startKeywordResearch() {
            this.loading = true;
            this.errorMsg = '';
            this.statusMsg = 'Keyword research AI in corso...';

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
            const messages = {
                'landing': 'Rianalizzare la landing page? I risultati precedenti verranno cancellati.',
                'keywords': 'Rigenerare keywords e campagna? (verranno consumati crediti aggiuntivi)',
                'campaign': 'Rigenerare la campagna? (verranno consumati 10 crediti aggiuntivi)',
            };
            if (!confirm(messages[step] || messages['campaign'])) {
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
