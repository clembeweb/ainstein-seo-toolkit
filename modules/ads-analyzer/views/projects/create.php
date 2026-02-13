<div class="max-w-3xl mx-auto space-y-6" x-data="{
    projectType: '<?= e($preselectedType ?? '') ?>',
    submitting: false
}">
    <!-- Header -->
    <div>
        <a href="<?= url('/ads-analyzer') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alla dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scegli la modalita e configura il tuo progetto Google Ads</p>
    </div>

    <!-- Scelta tipo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Card: Analisi Campagne -->
        <button type="button" @click="projectType = 'campaign'"
                :class="projectType === 'campaign' ? 'ring-2 ring-blue-500 border-blue-500 dark:border-blue-400' : 'border-slate-200 dark:border-slate-700 hover:border-blue-300'"
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border p-5 text-left transition-all cursor-pointer">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm flex-shrink-0">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Analisi Campagne</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Collega Google Ads Script, raccogli dati e ottieni valutazioni AI sulle campagne esistenti.</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 mt-2">
                        7 crediti/valutazione
                    </span>
                </div>
            </div>
        </button>

        <!-- Card: Campaign Creator -->
        <button type="button" @click="projectType = 'campaign-creator'"
                :class="projectType === 'campaign-creator' ? 'ring-2 ring-amber-500 border-amber-500 dark:border-amber-400' : 'border-slate-200 dark:border-slate-700 hover:border-amber-300'"
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border p-5 text-left transition-all cursor-pointer">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-sm flex-shrink-0">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Crea Campagna AI</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Genera da zero una campagna completa (Search o PMax) con keyword, copy ed estensioni.</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 mt-2">
                        9 crediti totali
                    </span>
                </div>
            </div>
        </button>
    </div>

    <!-- Form Analisi Campagne -->
    <div x-show="projectType === 'campaign'" x-transition x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <form action="<?= url('/ads-analyzer/projects/store') ?>" method="POST" @submit="submitting = true">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="campaign">

                <div class="p-6 space-y-5">
                    <div>
                        <label for="name-campaign" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Nome progetto <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name-campaign" name="name" required
                               value="<?= e($_SESSION['old_input']['name'] ?? '') ?>"
                               placeholder="Es: Campagna Pegaso Q1 2026"
                               class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="description-campaign" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Descrizione <span class="text-slate-400">(opzionale)</span>
                        </label>
                        <textarea id="description-campaign" name="description" rows="2"
                                  placeholder="Note o descrizione del progetto..."
                                  class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= e($_SESSION['old_input']['description'] ?? '') ?></textarea>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium mb-1">Prossimi passi</p>
                                <ol class="list-decimal list-inside space-y-1">
                                    <li>Configurare lo script Google Ads</li>
                                    <li>Eseguire lo script dall'account Google Ads</li>
                                    <li>Ricevere automaticamente i dati campagne</li>
                                    <li>Avviare la valutazione AI su copy e performance</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-end gap-3">
                    <a href="<?= url('/ads-analyzer') ?>" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Annulla</a>
                    <button type="submit" :disabled="submitting" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 inline-flex items-center">
                        <svg x-show="submitting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="submitting ? 'Creazione...' : 'Crea e Continua'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Campaign Creator -->
    <div x-show="projectType === 'campaign-creator'" x-transition x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <form action="<?= url('/ads-analyzer/projects/store') ?>" method="POST" @submit="submitting = true"
                  x-data="{ campaignType: '<?= e($_SESSION['old_input']['campaign_type_gads'] ?? '') ?>' }">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="campaign-creator">

                <div class="p-6 space-y-5">
                    <!-- Nome -->
                    <div>
                        <label for="name-creator" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Nome progetto <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name-creator" name="name" required
                               value="<?= e($_SESSION['old_input']['name'] ?? '') ?>"
                               placeholder="Es: Campagna Search Scarpe Running"
                               class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <!-- Tipo campagna -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Tipo campagna <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="campaignType = 'search'"
                                    :class="campaignType === 'search' ? 'ring-2 ring-amber-500 border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-amber-300'"
                                    class="border rounded-lg p-3 text-left transition-all">
                                <div class="font-medium text-slate-900 dark:text-white text-sm">Search</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">RSA con keyword e ad group</div>
                            </button>
                            <button type="button" @click="campaignType = 'pmax'"
                                    :class="campaignType === 'pmax' ? 'ring-2 ring-amber-500 border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-amber-300'"
                                    class="border rounded-lg p-3 text-left transition-all">
                                <div class="font-medium text-slate-900 dark:text-white text-sm">Performance Max</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Asset group con search themes</div>
                            </button>
                        </div>
                        <input type="hidden" name="campaign_type_gads" :value="campaignType">
                    </div>

                    <!-- Landing URL -->
                    <div>
                        <label for="landing-url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            URL Landing Page <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="landing-url" name="landing_url" required
                               value="<?= e($_SESSION['old_input']['landing_url'] ?? '') ?>"
                               placeholder="https://www.esempio.it/landing-page"
                               class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">La pagina verra analizzata per estrarre contesto e generare keyword pertinenti</p>
                    </div>

                    <!-- Brief -->
                    <div>
                        <label for="brief" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Brief campagna <span class="text-red-500">*</span>
                        </label>
                        <textarea id="brief" name="brief" rows="4" required minlength="20"
                                  placeholder="Descrivi l'obiettivo della campagna, il target, i prodotti/servizi da promuovere, il tono di voce desiderato..."
                                  class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"><?= e($_SESSION['old_input']['brief'] ?? '') ?></textarea>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Minimo 20 caratteri. Piu dettagli fornisci, migliore sara il risultato.</p>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-amber-700 dark:text-amber-300">
                                <p class="font-medium mb-1">Come funziona</p>
                                <ol class="list-decimal list-inside space-y-1">
                                    <li>Analisi automatica della landing page (1 credito)</li>
                                    <li>Keyword research AI con ad group e negative (3 crediti)</li>
                                    <li>Generazione completa: copy, estensioni, keyword (5 crediti)</li>
                                    <li>Copia/incolla in piattaforma o esporta CSV per Google Ads Editor</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-end gap-3">
                    <a href="<?= url('/ads-analyzer') ?>" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Annulla</a>
                    <button type="submit" :disabled="submitting || !campaignType" class="px-6 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 inline-flex items-center">
                        <svg x-show="submitting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="submitting ? 'Creazione...' : 'Crea e Avvia Wizard'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php unset($_SESSION['old_input']); ?>
