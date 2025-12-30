<div class="space-y-6" x-data="serpManager()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/ai-content/keywords') ?>" class="hover:text-primary-600">Keywords</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>SERP</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($keyword['keyword']) ?></h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= strtoupper($keyword['language']) ?> &bull; <?= e($keyword['location']) ?>
                <?php if ($keyword['serp_extracted_at']): ?>
                &bull; Estratto il <?= date('d/m/Y H:i', strtotime($keyword['serp_extracted_at'])) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="<?= url('/ai-content/keywords') ?>" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Indietro
            </a>
            <button @click="generateArticle()"
                    :disabled="selectedSources.length === 0 || generating"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <svg x-show="!generating" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <svg x-show="generating" x-cloak class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Genera Articolo
            </button>
        </div>
    </div>

    <!-- Selected Sources Info -->
    <div x-show="selectedSources.length > 0" x-cloak class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-primary-900 dark:text-primary-100">
                        <span x-text="selectedSources.length"></span> fonte/i selezionate
                    </p>
                    <p class="text-sm text-primary-700 dark:text-primary-300">
                        Costo stimato: <span x-text="3 + selectedSources.length + 10"></span> crediti
                        (SERP: 3 + Scraping: <span x-text="selectedSources.length"></span> + AI: 10)
                    </p>
                </div>
            </div>
            <button @click="selectedSources = []" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                Deseleziona tutto
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- SERP Results -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Risultati Organici (Top <?= count($keyword['serp_results']) ?>)</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Seleziona le fonti da cui estrarre contenuto per il brief</p>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($keyword['serp_results'] as $result): ?>
                    <label class="flex items-start gap-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors">
                        <input type="checkbox"
                               x-model="selectedSources"
                               value="<?= e($result['url']) ?>"
                               class="mt-1 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center justify-center h-5 w-5 rounded bg-slate-200 dark:bg-slate-600 text-xs font-medium text-slate-600 dark:text-slate-300">
                                    <?= $result['position'] ?>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= e($result['domain']) ?></span>
                            </div>
                            <h3 class="font-medium text-slate-900 dark:text-white text-sm"><?= e($result['title']) ?></h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2"><?= e($result['snippet']) ?></p>
                            <a href="<?= e($result['url']) ?>" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline mt-2">
                                Apri URL
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Custom URL -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <h3 class="font-medium text-slate-900 dark:text-white mb-3">Aggiungi URL personalizzato</h3>
                <div class="flex gap-2">
                    <input type="url" x-model="customUrl" placeholder="https://example.com/article" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <button @click="addCustomUrl()" :disabled="!customUrl" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 transition-colors">
                        Aggiungi
                    </button>
                </div>
                <!-- Custom URLs list -->
                <template x-if="customUrls.length > 0">
                    <div class="mt-3 space-y-2">
                        <template x-for="(url, index) in customUrls" :key="index">
                            <div class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-700/50 rounded">
                                <input type="checkbox" x-model="selectedSources" :value="url" class="h-4 w-4 rounded border-slate-300 text-primary-600">
                                <span class="flex-1 text-sm text-slate-600 dark:text-slate-300 truncate" x-text="url"></span>
                                <button @click="removeCustomUrl(index)" class="text-slate-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- PAA Questions Sidebar -->
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-900 dark:text-white">People Also Ask</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Domande correlate da Google</p>
                </div>

                <?php if (empty($keyword['paa_questions'])): ?>
                <div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">
                    Nessuna domanda trovata
                </div>
                <?php else: ?>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($keyword['paa_questions'] as $paa): ?>
                    <div class="p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($paa['question']) ?></p>
                                <?php if ($paa['snippet']): ?>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= e(substr($paa['snippet'], 0, 150)) ?>...</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <h3 class="font-medium text-blue-900 dark:text-blue-100 text-sm">Come funziona</h3>
                <ol class="mt-2 space-y-1 text-xs text-blue-700 dark:text-blue-300 list-decimal list-inside">
                    <li>Seleziona 3-6 fonti dalla SERP</li>
                    <li>Aggiungi URL personalizzati (opzionale)</li>
                    <li>Clicca "Genera Articolo"</li>
                    <li>Il sistema scrapperà i contenuti</li>
                    <li>L'AI genererà l'articolo ottimizzato</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function serpManager() {
    return {
        selectedSources: [],
        customUrl: '',
        customUrls: [],
        generating: false,

        addCustomUrl() {
            if (this.customUrl && this.customUrl.startsWith('http')) {
                if (!this.customUrls.includes(this.customUrl)) {
                    this.customUrls.push(this.customUrl);
                    this.selectedSources.push(this.customUrl);
                }
                this.customUrl = '';
            }
        },

        removeCustomUrl(index) {
            const url = this.customUrls[index];
            this.customUrls.splice(index, 1);
            const selectedIndex = this.selectedSources.indexOf(url);
            if (selectedIndex > -1) {
                this.selectedSources.splice(selectedIndex, 1);
            }
        },

        async generateArticle() {
            if (this.selectedSources.length === 0) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Seleziona almeno una fonte', type: 'error' }
                }));
                return;
            }

            if (this.selectedSources.length > 6) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Massimo 6 fonti consentite', type: 'error' }
                }));
                return;
            }

            this.generating = true;

            try {
                const response = await fetch('<?= url('/ai-content/articles/generate') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        keyword_id: <?= $keyword['id'] ?>,
                        sources: this.selectedSources
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Generazione articolo avviata!', type: 'success' }
                    }));

                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error, type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.generating = false;
            }
        }
    }
}
</script>
