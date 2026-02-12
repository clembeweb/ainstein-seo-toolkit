<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/ads-analyzer') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alla dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scegli la modalità e crea un nuovo progetto Google Ads</p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <form action="<?= url('/ads-analyzer/projects/store') ?>" method="POST"
              x-data="{ projectType: '<?= e($preselectedType ?? $_SESSION['old_input']['type'] ?? 'negative-kw') ?>', submitting: false }"
              @submit="submitting = true">
            <?= csrf_field() ?>
            <input type="hidden" name="type" x-model="projectType">

            <div class="p-6 space-y-6">
                <!-- Tipo Progetto -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                        Tipo di progetto <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Card Keyword Negative -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="type_radio" value="negative-kw" x-model="projectType" class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Keyword Negative</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Carica CSV search terms da Google Ads e trova keyword negative con AI</p>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute top-3 right-3 hidden peer-checked:block">
                                <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </label>

                        <!-- Card Analisi Campagne -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="type_radio" value="campaign" x-model="projectType" class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Analisi Campagne</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Configura Google Ads Script per raccogliere e valutare dati campagne con AI</p>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute top-3 right-3 hidden peer-checked:block">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Nome progetto -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome progetto <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        value="<?= e($_SESSION['old_input']['name'] ?? '') ?>"
                        placeholder="Es: Campagna Pegaso Q1 2026"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Descrizione <span class="text-slate-400">(opzionale)</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Note o descrizione del progetto..."
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    ><?= e($_SESSION['old_input']['description'] ?? '') ?></textarea>
                </div>

                <!-- Hint Negative KW -->
                <div x-show="projectType === 'negative-kw'" x-transition class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-amber-700 dark:text-amber-300">
                            <p class="font-medium mb-1">Prossimi passi</p>
                            <ol class="list-decimal list-inside mt-1 space-y-1">
                                <li>Caricare il file CSV esportato da Google Ads</li>
                                <li>Inserire il contesto business per l'analisi AI</li>
                                <li>Avviare l'analisi e ottenere le keyword negative</li>
                                <li>Esportare per Google Ads Editor</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Hint Campaign -->
                <div x-show="projectType === 'campaign'" x-cloak x-transition class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Prossimi passi</p>
                            <ol class="list-decimal list-inside mt-1 space-y-1">
                                <li>Configurare lo script Google Ads</li>
                                <li>Eseguire lo script dall'account Google Ads</li>
                                <li>Ricevere automaticamente i dati campagne</li>
                                <li>Avviare la valutazione AI su copy e performance</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-end gap-3">
                <a href="<?= url('/ads-analyzer') ?>" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    Annulla
                </a>
                <button type="submit" :disabled="submitting" class="px-6 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 inline-flex items-center">
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

<?php unset($_SESSION['old_input']); ?>
