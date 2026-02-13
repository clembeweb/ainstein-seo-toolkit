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
              x-data="{ submitting: false }"
              @submit="submitting = true">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="campaign">

            <div class="p-6 space-y-6">

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
