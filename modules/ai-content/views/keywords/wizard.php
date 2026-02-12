<?php
$baseUrl = !empty($keyword['project_id']) ? '/ai-content/projects/' . $keyword['project_id'] : '/ai-content';
?>

<?php if (!empty($projectId) && !empty($project)): ?>
<?php $currentPage = 'keywords'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>
<?php endif; ?>

<div class="space-y-6" x-data="keywordWizard(<?= e(json_encode($wizardData)) ?>)">
    <?php if (empty($projectId) || empty($project)): ?>
    <!-- Breadcrumbs (solo se non c'è il project-nav) -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    AI Content
                </a>
            </li>
            <?php if (!empty($keyword['project_id']) && !empty($project)): ?>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="<?= url('/ai-content/projects/' . $keyword['project_id']) ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <?= e($project['name'] ?? 'Progetto') ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="<?= url($baseUrl . '/keywords') ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    Keywords
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Wizard</span>
            </li>
        </ol>
    </nav>

    <!-- Header with Keyword Info -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($keyword['keyword']) ?></h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= strtoupper($keyword['language']) ?> - <?= e($keyword['location']) ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= url($baseUrl . '/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Torna alle Keywords
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Progress Steps (pattern: keyword-research wizard) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <?php
            $steps = [
                0 => 'SERP & Fonti',
                1 => 'Brief',
                2 => 'Articolo',
                3 => 'Pubblica',
            ];
            foreach ($steps as $idx => $label):
            $stepNum = $idx + 1;
            ?>
            <div class="flex items-center<?= $idx < 3 ? ' flex-1' : '' ?>">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                         :class="currentStep > <?= $stepNum ?> ? 'bg-emerald-500 text-white' : (currentStep >= <?= $stepNum ?> ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400')">
                        <template x-if="currentStep > <?= $stepNum ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="currentStep <= <?= $stepNum ?>">
                            <span><?= $stepNum ?></span>
                        </template>
                    </div>
                    <span class="text-sm font-medium hidden sm:block"
                          :class="currentStep >= <?= $stepNum ?> ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'">
                        <?= $label ?>
                    </span>
                </div>
                <?php if ($idx < 3): ?>
                <div class="flex-1 mx-4 h-0.5 rounded transition-colors"
                     :class="currentStep > <?= $stepNum ?> ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'">
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step Content Container -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 min-h-[500px]">

        <!-- ========== STEP 1: SERP & Fonti ========== -->
        <div x-show="currentStep === 1" x-cloak class="p-6">
            <div class="space-y-6">
                <!-- SERP Extraction -->
                <div x-show="!serpExtracted">
                    <div class="text-center py-12">
                        <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4">
                            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Estrai i risultati SERP</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                            Analizza i primi 10 risultati di Google per scoprire cosa funziona e selezionare le fonti migliori.
                        </p>
                        <div class="mt-6">
                            <button @click="extractSerp()" :disabled="extractingSerp" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors disabled:opacity-50">
                                <svg x-show="!extractingSerp" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <svg x-show="extractingSerp" x-cloak class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="extractingSerp ? 'Estrazione in corso...' : 'Estrai SERP'"></span>
                            </button>
                        </div>
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Costo: 3 crediti
                        </p>
                    </div>
                </div>

                <!-- SERP Results (when extracted) -->
                <div x-show="serpExtracted">
                    <!-- Organic Results -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Risultati Organici
                            <span class="ml-2 text-sm font-normal text-slate-500">(seleziona max 6 fonti)</span>
                        </h3>
                        <div class="space-y-3">
                            <template x-for="(result, index) in serpResults" :key="result.id || index">
                                <div class="flex items-start gap-4 p-4 rounded-lg border transition-all cursor-pointer"
                                     :class="{
                                         'border-primary-500 bg-primary-50 dark:bg-primary-900/20': isSourceSelected(result.url),
                                         'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600': !isSourceSelected(result.url)
                                     }"
                                     @click="toggleSource(result)">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                             :class="{
                                                 'border-primary-500 bg-primary-500': isSourceSelected(result.url),
                                                 'border-slate-300 dark:border-slate-600': !isSourceSelected(result.url)
                                             }">
                                            <svg x-show="isSourceSelected(result.url)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300" x-text="result.position"></span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 truncate" x-text="result.domain"></span>
                                        </div>
                                        <h4 class="font-medium text-slate-900 dark:text-white line-clamp-1" x-text="result.title"></h4>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mt-1" x-text="result.snippet"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- PAA Questions -->
                    <div class="mb-8" x-show="paaQuestions.length > 0">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            People Also Ask
                            <span class="ml-2 text-sm font-normal text-slate-500">(includi nel brief)</span>
                        </h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="(paa, index) in paaQuestions" :key="index">
                                <div class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                     :class="{
                                         'border-purple-500 bg-purple-50 dark:bg-purple-900/20': isPaaSelected(paa.question),
                                         'border-slate-200 dark:border-slate-700 hover:border-slate-300': !isPaaSelected(paa.question)
                                     }"
                                     @click="togglePaa(paa)">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center"
                                             :class="{
                                                 'border-purple-500 bg-purple-500': isPaaSelected(paa.question),
                                                 'border-slate-300 dark:border-slate-600': !isPaaSelected(paa.question)
                                             }">
                                            <svg x-show="isPaaSelected(paa.question)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="paa.question"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Custom URL Input -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Aggiungi URL Custom
                        </h3>
                        <div class="flex gap-3">
                            <input type="url" x-model="customUrl" placeholder="https://esempio.com/articolo"
                                   class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                   @keyup.enter="addCustomUrl()">
                            <button @click="addCustomUrl()" :disabled="!customUrl" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Custom URLs List -->
                        <div class="mt-3 space-y-2" x-show="customUrls.length > 0">
                            <template x-for="(url, index) in customUrls" :key="index">
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                                    <span class="text-sm text-emerald-700 dark:text-emerald-300 truncate" x-text="url"></span>
                                    <button @click="removeCustomUrl(index)" class="p-1 text-emerald-600 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Selected Summary -->
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                        <h4 class="font-medium text-slate-900 dark:text-white mb-2">Riepilogo Selezione</h4>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span class="text-slate-600 dark:text-slate-300">Fonti SERP:</span>
                                <span class="font-semibold text-slate-900 dark:text-white" x-text="selectedSources.length"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-600 dark:text-slate-300">URL Custom:</span>
                                <span class="font-semibold text-slate-900 dark:text-white" x-text="customUrls.length"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span class="text-slate-600 dark:text-slate-300">Domande PAA:</span>
                                <span class="font-semibold text-slate-900 dark:text-white" x-text="selectedPaa.length"></span>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Costo scraping: <span x-text="totalSources"></span> crediti (1 per URL)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STEP 2: Brief ========== -->
        <div x-show="currentStep === 2" x-cloak class="p-6">
            <div class="space-y-6">
                <!-- Generate Brief Button -->
                <div x-show="!briefGenerated">
                    <div class="text-center py-12">
                        <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
                            <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Genera il Brief</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                            Analizzeremo le fonti selezionate per creare un brief completo con struttura, entita chiave e search intent.
                        </p>

                        <!-- Progress during generation -->
                        <div x-show="generatingBrief" class="mt-6 max-w-md mx-auto">
                            <div class="bg-slate-100 dark:bg-slate-700 rounded-full h-2 mb-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + briefProgress + '%'"></div>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400" x-text="briefProgressText"></p>
                        </div>

                        <div x-show="!generatingBrief" class="mt-6">
                            <button @click="generateBrief()" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Genera Brief
                            </button>
                        </div>
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Costo: <span x-text="totalSources"></span> crediti scraping + 5 crediti AI
                        </p>
                    </div>
                </div>

                <!-- Brief Content (when generated) -->
                <div x-show="briefGenerated">
                    <!-- Search Intent -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Search Intent</h3>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-sm font-medium"
                                  :class="{
                                      'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300': briefData.searchIntent === 'informational',
                                      'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300': briefData.searchIntent === 'transactional',
                                      'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300': briefData.searchIntent === 'navigational',
                                      'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300': briefData.searchIntent === 'commercial'
                                  }"
                                  x-text="briefData.searchIntent?.charAt(0).toUpperCase() + briefData.searchIntent?.slice(1)">
                            </span>
                            <p class="text-sm text-slate-600 dark:text-slate-400" x-text="briefData.intentDescription"></p>
                        </div>
                    </div>

                    <!-- AI Strategic Analysis (when available) -->
                    <div class="mb-6" x-show="briefData.aiAnalysis" x-cloak>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Analisi Strategica AI
                        </h3>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800 space-y-4">

                            <!-- Content Strategy -->
                            <div x-show="briefData.aiAnalysis?.contentStrategy">
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">Strategia Contenuto</h4>
                                <p class="text-sm text-amber-700 dark:text-amber-300" x-text="briefData.aiAnalysis?.contentStrategy"></p>
                            </div>

                            <!-- Winning Title Suggestions (clickable) -->
                            <div x-show="briefData.aiAnalysis?.winningTitles?.length > 0">
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                    Titoli Suggeriti dall'AI
                                    <span class="font-normal text-amber-600 dark:text-amber-400">(clicca per usare come H1)</span>
                                </h4>
                                <div class="space-y-1">
                                    <template x-for="(title, idx) in (briefData.aiAnalysis?.winningTitles || [])" :key="idx">
                                        <button @click="briefData.suggestedHeadings[0].text = title"
                                                class="block w-full text-left px-3 py-2 rounded text-sm transition-colors"
                                                :class="briefData.suggestedHeadings[0]?.text === title
                                                    ? 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100 font-medium'
                                                    : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-amber-100 dark:hover:bg-amber-800/50'">
                                            <span x-text="title"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Content Gaps -->
                            <div x-show="briefData.aiAnalysis?.contentGaps?.length > 0">
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">Gap nei Contenuti Competitor</h4>
                                <ul class="list-disc list-inside text-sm text-amber-700 dark:text-amber-300 space-y-1">
                                    <template x-for="(gap, idx) in (briefData.aiAnalysis?.contentGaps || [])" :key="idx">
                                        <li x-text="gap"></li>
                                    </template>
                                </ul>
                            </div>

                            <!-- Unique Angles -->
                            <div x-show="briefData.aiAnalysis?.uniqueAngles?.length > 0">
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">Angoli Unici Consigliati</h4>
                                <ul class="list-disc list-inside text-sm text-amber-700 dark:text-amber-300 space-y-1">
                                    <template x-for="(angle, idx) in (briefData.aiAnalysis?.uniqueAngles || [])" :key="idx">
                                        <li x-text="angle"></li>
                                    </template>
                                </ul>
                            </div>

                            <!-- Key Differentiators -->
                            <div x-show="briefData.aiAnalysis?.keyDifferentiators?.length > 0">
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">Come Differenziarsi</h4>
                                <ul class="list-disc list-inside text-sm text-amber-700 dark:text-amber-300 space-y-1">
                                    <template x-for="(diff, idx) in (briefData.aiAnalysis?.keyDifferentiators || [])" :key="idx">
                                        <li x-text="diff"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Suggested Structure -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Struttura Suggerita</h3>
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                            <ul class="space-y-2">
                                <template x-for="(heading, index) in briefData.suggestedHeadings" :key="index">
                                    <li class="flex items-center gap-2">
                                        <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300" x-text="heading.tag"></span>
                                        <input type="text" x-model="heading.text"
                                               class="flex-1 px-3 py-1.5 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-1 focus:ring-primary-500">
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <!-- Key Entities -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Entita / Termini Chiave</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(entity, index) in briefData.entities" :key="index">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    <span x-text="entity"></span>
                                    <button @click="briefData.entities.splice(index, 1)" class="ml-2 text-slate-400 hover:text-red-500">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- PAA to Answer -->
                    <div class="mb-6" x-show="selectedPaa.length > 0">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Domande da Rispondere</h3>
                        <ul class="space-y-2">
                            <template x-for="(paa, index) in selectedPaa" :key="index">
                                <li class="flex items-start gap-2 text-sm">
                                    <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-slate-700 dark:text-slate-300" x-text="paa.question"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Word Count -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Word Count Consigliato</h3>
                        <div class="flex items-center gap-4">
                            <input type="number" x-model="briefData.targetWordCount"
                                   class="w-32 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                            <span class="text-sm text-slate-500 dark:text-slate-400">parole</span>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Note Aggiuntive per l'AI</h3>
                        <textarea x-model="briefData.additionalNotes" rows="3" placeholder="Aggiungi istruzioni specifiche per la generazione dell'articolo..."
                                  class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                    </div>

                    <!-- Rigenera Brief Button -->
                    <div class="flex justify-center pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button @click="regenerateBrief()" :disabled="generatingBrief" class="inline-flex items-center px-4 py-2 rounded-lg border border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-50">
                            <svg x-show="!generatingBrief" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <svg x-show="generatingBrief" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="generatingBrief ? 'Rigenerazione...' : 'Rigenera Brief'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STEP 3: Articolo ========== -->
        <div x-show="currentStep === 3" x-cloak class="p-6">
            <div class="space-y-6">
                <!-- Generate Article Button -->
                <div x-show="!articleGenerated">
                    <div class="text-center py-12">
                        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
                            <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Genera l'Articolo</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                            L'AI generera un articolo completo basato sul brief creato, ottimizzato per la keyword target.
                        </p>

                        <!-- Progress during generation -->
                        <div x-show="generatingArticle" class="mt-6 max-w-md mx-auto">
                            <div class="bg-slate-100 dark:bg-slate-700 rounded-full h-2 mb-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + articleProgress + '%'"></div>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400" x-text="articleProgressText"></p>
                        </div>

                        <div x-show="!generatingArticle" class="mt-6 space-y-4">
                            <!-- Cover Image Toggle -->
                            <div class="flex items-center justify-center">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="generateCover" class="h-4 w-4 text-primary-600 border-slate-300 dark:border-slate-600 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Genera immagine di copertina</span>
                                    <span class="ml-1 text-xs text-slate-400 dark:text-slate-500">(+3 crediti)</span>
                                </label>
                            </div>

                            <button @click="generateArticle()" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Genera Articolo
                            </button>
                        </div>
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Costo: <span x-text="generateCover ? '13' : '10'">10</span> crediti
                        </p>
                    </div>
                </div>

                <!-- Article Content (when generated) -->
                <div x-show="articleGenerated">
                    <!-- Title -->
                    <div class="mb-6">
                        <label class="flex items-center justify-between text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <span>Titolo</span>
                            <span :class="articleData.title?.length > 60 ? 'text-red-500' : 'text-slate-400'" x-text="(articleData.title?.length || 0) + '/60'"></span>
                        </label>
                        <input type="text" x-model="articleData.title"
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-lg font-medium focus:ring-2 focus:ring-primary-500">
                    </div>

                    <!-- Meta Description -->
                    <div class="mb-6">
                        <label class="flex items-center justify-between text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <span>Meta Description</span>
                            <span :class="articleData.metaDescription?.length > 155 ? 'text-red-500' : 'text-slate-400'" x-text="(articleData.metaDescription?.length || 0) + '/155'"></span>
                        </label>
                        <textarea x-model="articleData.metaDescription" rows="2"
                                  class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                    </div>

                    <!-- Content Toggle -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Contenuto</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500" x-text="articleData.wordCount + ' parole'"></span>
                                <div class="flex rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden">
                                    <button @click="contentView = 'editor'" class="px-3 py-1 text-sm transition-colors"
                                            :class="contentView === 'editor' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                                        Editor
                                    </button>
                                    <button @click="contentView = 'preview'" class="px-3 py-1 text-sm transition-colors"
                                            :class="contentView === 'preview' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Editor -->
                    <div x-show="contentView === 'editor'" class="mb-6">
                        <textarea x-model="articleData.content" rows="20"
                                  class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>

                    <!-- Content Preview -->
                    <div x-show="contentView === 'preview'" class="mb-6">
                        <div class="prose prose-slate dark:prose-invert max-w-none p-6 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700"
                             x-html="articleData.content">
                        </div>
                    </div>

                    <!-- Regenerate Button -->
                    <div class="flex justify-center">
                        <button @click="regenerateArticle()" :disabled="generatingArticle" class="inline-flex items-center px-4 py-2 rounded-lg border border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-50">
                            <svg x-show="!generatingArticle" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <svg x-show="generatingArticle" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Rigenera (10 crediti)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STEP 4: Pubblica ========== -->
        <div x-show="currentStep === 4" x-cloak class="p-6">
            <div class="space-y-6">
                <!-- No WP Sites Alert -->
                <div x-show="wpSites.length === 0">
                    <div class="text-center py-12">
                        <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
                            <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun sito WordPress configurato</h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                            Per pubblicare gli articoli devi prima collegare almeno un sito WordPress.
                        </p>
                        <div class="mt-6">
                            <a href="<?= url('/ai-content/wordpress') ?>" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Configura Sito WordPress
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Publish Form -->
                <div x-show="wpSites.length > 0">
                    <!-- Success Message -->
                    <div x-show="publishSuccess" class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-emerald-800 dark:text-emerald-200">Articolo pubblicato!</h4>
                                <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">L'articolo e stato pubblicato con successo su WordPress.</p>
                                <a :href="publishedUrl" target="_blank" class="inline-flex items-center mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                                    <span>Visualizza articolo</span>
                                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div x-show="!publishSuccess" class="max-w-xl mx-auto">
                        <div class="text-center mb-8">
                            <div class="mx-auto h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center mb-4">
                                <svg class="h-8 w-8 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Pubblica su WordPress</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Seleziona il sito e le opzioni di pubblicazione.
                            </p>
                        </div>

                        <!-- Site Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Sito WordPress</label>
                            <select x-model="publishData.wpSiteId" @change="loadCategories()"
                                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="">Seleziona un sito...</option>
                                <template x-for="site in wpSites" :key="site.id">
                                    <option :value="site.id" x-text="site.name + ' (' + site.url + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Category Selection -->
                        <div class="mb-6" x-show="publishData.wpSiteId">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Categoria</label>
                            <div x-show="loadingCategories" class="text-sm text-slate-500">Caricamento categorie...</div>
                            <select x-show="!loadingCategories && categories.length > 0" x-model="publishData.categoryId"
                                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="">Nessuna categoria</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                            <p x-show="!loadingCategories && categories.length === 0" class="text-sm text-slate-500">Nessuna categoria disponibile</p>
                        </div>

                        <!-- Status Selection -->
                        <div class="mb-8" x-show="publishData.wpSiteId">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Stato Pubblicazione</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center p-4 rounded-lg border cursor-pointer transition-all"
                                       :class="publishData.status === 'draft' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-slate-200 dark:border-slate-700'">
                                    <input type="radio" x-model="publishData.status" value="draft" class="sr-only">
                                    <div class="flex items-center gap-3">
                                        <div class="w-4 h-4 rounded-full border-2"
                                             :class="publishData.status === 'draft' ? 'border-primary-500 bg-primary-500' : 'border-slate-300'">
                                            <div x-show="publishData.status === 'draft'" class="w-full h-full rounded-full bg-white scale-50"></div>
                                        </div>
                                        <div>
                                            <span class="font-medium text-slate-900 dark:text-white">Bozza</span>
                                            <p class="text-xs text-slate-500">Non visibile pubblicamente</p>
                                        </div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 rounded-lg border cursor-pointer transition-all"
                                       :class="publishData.status === 'publish' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-slate-200 dark:border-slate-700'">
                                    <input type="radio" x-model="publishData.status" value="publish" class="sr-only">
                                    <div class="flex items-center gap-3">
                                        <div class="w-4 h-4 rounded-full border-2"
                                             :class="publishData.status === 'publish' ? 'border-primary-500 bg-primary-500' : 'border-slate-300'">
                                            <div x-show="publishData.status === 'publish'" class="w-full h-full rounded-full bg-white scale-50"></div>
                                        </div>
                                        <div>
                                            <span class="font-medium text-slate-900 dark:text-white">Pubblicato</span>
                                            <p class="text-xs text-slate-500">Visibile a tutti</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Publish Button -->
                        <div class="text-center" x-show="publishData.wpSiteId">
                            <button @click="publish()" :disabled="publishing || !publishData.wpSiteId"
                                    class="inline-flex items-center px-8 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors disabled:opacity-50">
                                <svg x-show="!publishing" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <svg x-show="publishing" x-cloak class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="publishing ? 'Pubblicazione in corso...' : 'Pubblica su WordPress'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Footer -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <button x-show="currentStep > 1" @click="prevStep()" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Indietro
            </button>
            <div x-show="currentStep === 1"></div>

            <button x-show="currentStep < 4 && canProceed()" @click="nextStep()" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Avanti
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <a x-show="currentStep === 4" href="<?= url('/ai-content') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Torna alla Dashboard
            </a>
        </div>
    </div>
</div>

<script>
function keywordWizard(initialData) {
    return {
        // State
        currentStep: initialData.currentStep || 1,
        keywordId: initialData.keywordId,
        articleId: initialData.articleId || null,

        // Step 1 - SERP
        serpExtracted: initialData.serpExtracted || false,
        extractingSerp: false,
        serpResults: initialData.serpResults || [],
        paaQuestions: initialData.paaQuestions || [],
        selectedSources: initialData.selectedSources || [],
        selectedPaa: initialData.selectedPaa || [],
        customUrls: initialData.customUrls || [],
        customUrl: '',

        // Step 2 - Brief
        briefGenerated: initialData.briefGenerated || false,
        generatingBrief: false,
        briefProgress: 0,
        briefProgressText: '',
        briefData: initialData.briefData || {
            searchIntent: '',
            intentDescription: '',
            suggestedHeadings: [],
            entities: [],
            targetWordCount: 1500,
            additionalNotes: '',
            aiAnalysis: null
        },

        // Step 3 - Article
        articleGenerated: initialData.articleGenerated || false,
        generatingArticle: false,
        generateCover: true,
        articleProgress: 0,
        articleProgressText: '',
        contentView: 'editor',
        articleData: initialData.articleData || {
            title: '',
            metaDescription: '',
            content: '',
            wordCount: 0
        },

        // Step 4 - Publish
        wpSites: initialData.wpSites || [],
        categories: [],
        loadingCategories: false,
        publishing: false,
        publishSuccess: false,
        publishedUrl: '',
        publishData: {
            wpSiteId: '',
            categoryId: '',
            status: 'draft'
        },

        // Computed
        get totalSources() {
            return this.selectedSources.length + this.customUrls.length;
        },

        // Methods
        isSourceSelected(url) {
            return this.selectedSources.some(s => s.url === url);
        },

        toggleSource(result) {
            const index = this.selectedSources.findIndex(s => s.url === result.url);
            if (index > -1) {
                this.selectedSources.splice(index, 1);
            } else if (this.selectedSources.length < 6) {
                this.selectedSources.push({
                    url: result.url,
                    title: result.title,
                    position: result.position
                });
            }
        },

        isPaaSelected(question) {
            return this.selectedPaa.some(p => p.question === question);
        },

        togglePaa(paa) {
            const index = this.selectedPaa.findIndex(p => p.question === paa.question);
            if (index > -1) {
                this.selectedPaa.splice(index, 1);
            } else {
                this.selectedPaa.push(paa);
            }
        },

        addCustomUrl() {
            if (this.customUrl && this.customUrl.startsWith('http') && this.customUrls.length < 3) {
                if (!this.customUrls.includes(this.customUrl)) {
                    this.customUrls.push(this.customUrl);
                }
                this.customUrl = '';
            }
        },

        removeCustomUrl(index) {
            this.customUrls.splice(index, 1);
        },

        canProceed() {
            switch (this.currentStep) {
                case 1:
                    return this.serpExtracted && this.totalSources > 0;
                case 2:
                    return this.briefGenerated;
                case 3:
                    return this.articleGenerated;
                default:
                    return true;
            }
        },

        nextStep() {
            if (this.currentStep < 4 && this.canProceed()) {
                this.currentStep++;
                this.saveProgress();
            }
        },

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        async extractSerp() {
            if (this.extractingSerp) return;
            this.extractingSerp = true;

            const url = '<?= url('/ai-content/keywords') ?>/' + this.keywordId + '/serp';
            const body = JSON.stringify({ _token: '<?= csrf_token() ?>' });

            // DEBUG LOGGING
            console.log('=== SERP REQUEST ===');
            console.log('URL:', url);
            console.log('Method: POST');
            console.log('Body:', body);
            console.log('CSRF Token:', '<?= csrf_token() ?>');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body
                });

                // DEBUG RESPONSE
                console.log('=== SERP RESPONSE ===');
                console.log('Status:', response.status);
                console.log('OK:', response.ok);
                console.log('StatusText:', response.statusText);

                const text = await response.text();
                console.log('Raw response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response was not JSON:', text.substring(0, 500));
                    this.showToast('Errore: risposta non valida dal server', 'error');
                    return;
                }

                if (data.success) {
                    this.serpExtracted = true;
                    this.serpResults = data.organic || [];
                    this.paaQuestions = data.paa || [];
                    console.log('SERP Results:', this.serpResults);
                    console.log('PAA Questions:', this.paaQuestions);
                    this.showToast('SERP estratta con successo', 'success');
                } else {
                    console.error('SERP Error:', data.error);
                    this.showToast(data.error || 'Errore estrazione SERP', 'error');
                }
            } catch (error) {
                console.error('=== SERP FETCH ERROR ===');
                console.error('Error:', error);
                this.showToast('Errore di connessione', 'error');
            } finally {
                this.extractingSerp = false;
            }
        },

        async generateBrief() {
            if (this.generatingBrief) return;
            this.generatingBrief = true;
            this.briefProgress = 0;

            try {
                // Simulate progress for scraping
                const totalSources = this.totalSources;
                for (let i = 1; i <= totalSources; i++) {
                    this.briefProgress = Math.round((i / (totalSources + 1)) * 80);
                    this.briefProgressText = `Scraping fonte ${i}/${totalSources}...`;
                    await new Promise(r => setTimeout(r, 1000));
                }

                this.briefProgressText = 'Generazione brief...';
                this.briefProgress = 90;

                const response = await fetch('<?= url('/ai-content/wizard') ?>/' + this.keywordId + '/brief', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        sources: this.selectedSources,
                        customUrls: this.customUrls,
                        paaQuestions: this.selectedPaa
                    })
                });

                if (!response.ok) {
                    const statusMsg = response.status >= 500
                        ? `Errore server (${response.status}). L'operazione potrebbe aver impiegato troppo tempo. Riprova con meno fonti.`
                        : `Errore HTTP ${response.status}`;
                    this.showToast(statusMsg, 'error');
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    this.briefGenerated = true;
                    this.briefData = data.brief;
                    this.articleId = data.articleId;
                    this.briefProgress = 100;
                    this.showToast('Brief generato con successo', 'success');
                } else {
                    this.showToast(data.error || 'Errore generazione brief', 'error');
                }
            } catch (error) {
                console.error('Brief generation error:', error);
                this.showToast('Errore di rete. L\'operazione potrebbe aver impiegato troppo tempo. Riprova.', 'error');
            } finally {
                this.generatingBrief = false;
            }
        },

        async generateArticle() {
            if (this.generatingArticle) return;
            this.generatingArticle = true;
            this.articleProgress = 0;

            try {
                // Simulate progress
                const progressInterval = setInterval(() => {
                    if (this.articleProgress < 90) {
                        this.articleProgress += Math.random() * 10;
                        const messages = [
                            'Analisi brief...',
                            'Creazione struttura...',
                            'Generazione introduzione...',
                            'Sviluppo contenuto...',
                            'Ottimizzazione SEO...',
                            'Finalizzazione...',
                            ...(this.generateCover ? ['Generazione copertina...'] : [])
                        ];
                        this.articleProgressText = messages[Math.floor(Math.random() * messages.length)];
                    }
                }, 2000);

                const response = await fetch('<?= url('/ai-content/wizard') ?>/' + this.keywordId + '/article', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        articleId: this.articleId,
                        briefData: this.briefData,
                        generateCover: this.generateCover
                    })
                });

                clearInterval(progressInterval);

                if (!response.ok) {
                    const statusMsg = response.status >= 500
                        ? `Errore server (${response.status}). L'operazione potrebbe aver impiegato troppo tempo. Riprova.`
                        : `Errore HTTP ${response.status}`;
                    this.showToast(statusMsg, 'error');
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    this.articleGenerated = true;
                    this.articleData = data.article;
                    this.articleProgress = 100;
                    this.showToast('Articolo generato con successo', 'success');
                } else {
                    this.showToast(data.error || 'Errore generazione articolo', 'error');
                }
            } catch (error) {
                console.error('Article generation error:', error);
                this.showToast('Errore di rete. L\'operazione potrebbe aver impiegato troppo tempo. Riprova.', 'error');
            } finally {
                this.generatingArticle = false;
            }
        },

        async regenerateArticle() {
            this.articleGenerated = false;
            await this.generateArticle();
        },

        async regenerateBrief() {
            this.briefGenerated = false;
            await this.generateBrief();
        },

        async loadCategories() {
            if (!this.publishData.wpSiteId) {
                this.categories = [];
                return;
            }

            this.loadingCategories = true;
            try {
                const response = await fetch('<?= url('/ai-content/wordpress/sites') ?>/' + this.publishData.wpSiteId + '/categories');
                const data = await response.json();
                this.categories = data.categories || [];
            } catch (error) {
                this.categories = [];
            } finally {
                this.loadingCategories = false;
            }
        },

        async publish() {
            if (this.publishing || !this.publishData.wpSiteId) return;
            this.publishing = true;

            try {
                const response = await fetch('<?= url('/ai-content/articles') ?>/' + this.articleId + '/publish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        wp_site_id: this.publishData.wpSiteId,
                        category_id: this.publishData.categoryId,
                        status: this.publishData.status
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.publishSuccess = true;
                    this.publishedUrl = data.wp_post_url || '#';
                    this.showToast('Articolo pubblicato con successo!', 'success');
                } else {
                    this.showToast(data.error || 'Errore pubblicazione', 'error');
                }
            } catch (error) {
                this.showToast('Errore di connessione', 'error');
            } finally {
                this.publishing = false;
            }
        },

        saveProgress() {
            // Save current wizard state to localStorage
            localStorage.setItem('wizard_' + this.keywordId, JSON.stringify({
                currentStep: this.currentStep,
                selectedSources: this.selectedSources,
                selectedPaa: this.selectedPaa,
                customUrls: this.customUrls,
                briefData: this.briefData,
                articleId: this.articleId
            }));
        },

        showToast(message, type) {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { message, type }
            }));
        }
    }
}
</script>
