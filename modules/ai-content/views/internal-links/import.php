<?php $currentPage = 'internal-links'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-4xl mx-auto" x-data="importWizard()">

    <!-- Breadcrumb -->
    <div class="mb-6">
        <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna al Pool Link
        </a>
    </div>

    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Importa Link Interni</h1>
    <p class="text-slate-500 dark:text-slate-400 mb-8">Aggiungi URL al pool di link interni per <?= e($project['name']) ?></p>

    <!-- Tab Navigation -->
    <div class="flex border-b border-slate-200 dark:border-slate-700 mb-6">
        <button @click="activeTab = 'sitemap'"
                :class="activeTab === 'sitemap' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="flex items-center gap-2 px-6 py-3 border-b-2 font-medium text-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Sitemap
        </button>
        <button @click="activeTab = 'manual'"
                :class="activeTab === 'manual' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="flex items-center gap-2 px-6 py-3 border-b-2 font-medium text-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Inserimento Manuale
        </button>
    </div>

    <!-- Tab: Sitemap -->
    <div x-show="activeTab === 'sitemap'" x-transition class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">

        <!-- Step 1: Site URL -->
        <div x-show="step === 1" class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Inserisci URL del sito</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Inserisci l'indirizzo del sito da cui importare i link interni.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">URL Sito</label>
                    <input type="url" x-model="siteUrl" @keydown.enter="discoverSitemaps()"
                           placeholder="https://esempio.com"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                </div>

                <div x-show="error" x-cloak class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                    <span x-text="error"></span>
                </div>

                <div class="flex justify-end">
                    <button @click="discoverSitemaps()" :disabled="loading || !siteUrl"
                            class="inline-flex items-center px-6 py-3 rounded-xl bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-600/25">
                        <svg x-show="loading" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Ricerca...' : 'Cerca Sitemap'"></span>
                        <svg x-show="!loading" class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Select Sitemaps -->
        <div x-show="step === 2" x-cloak class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Seleziona Sitemap</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Abbiamo trovato <span class="font-medium" x-text="sitemaps.length"></span> sitemap. Seleziona quelle da cui importare.</p>

            <div class="space-y-3 mb-6 max-h-64 overflow-y-auto">
                <template x-for="(sitemap, idx) in sitemaps" :key="idx">
                    <label class="flex items-start p-4 rounded-xl border cursor-pointer transition-colors"
                           :class="selectedSitemaps.includes(sitemap.url) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                        <input type="checkbox" :value="sitemap.url" x-model="selectedSitemaps"
                               class="mt-1 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="sitemap.url"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Fonte: <span x-text="sitemap.source"></span>
                                <span x-show="sitemap.url_count" class="ml-2 text-green-600 dark:text-green-400">~<span x-text="sitemap.url_count?.toLocaleString()"></span> URL</span>
                            </p>
                        </div>
                    </label>
                </template>
            </div>

            <div x-show="error" x-cloak class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm mb-6">
                <span x-text="error"></span>
            </div>

            <div class="flex justify-between">
                <button @click="step = 1" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Indietro
                </button>
                <button @click="previewUrls()" :disabled="loading || selectedSitemaps.length === 0"
                        class="inline-flex items-center px-6 py-2.5 rounded-xl bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-600/25">
                    <svg x-show="loading" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Caricamento...' : 'Carica URL'"></span>
                </button>
            </div>
        </div>

        <!-- Step 3: Select URLs -->
        <div x-show="step === 3" x-cloak>
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Seleziona URL da importare</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Trovate <span class="font-medium" x-text="urls.length"></span> URL.
                    <span x-show="duplicatesRemoved > 0" class="text-amber-600 dark:text-amber-400">(<span x-text="duplicatesRemoved"></span> duplicati rimossi)</span>
                </p>
            </div>

            <!-- Selection Bar -->
            <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" @change="toggleAllUrls($event)" :checked="selectedUrls.length === urls.filter(u => !u.exists).length && selectedUrls.length > 0"
                               class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Seleziona tutte le nuove</span>
                    </label>
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        <span class="font-medium" x-text="selectedUrls.length"></span> selezionate
                    </span>
                </div>
                <div class="relative">
                    <input type="text" x-model="urlFilter" placeholder="Filtra URL..."
                           class="pl-8 pr-4 py-1.5 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-1 focus:ring-primary-500 w-48">
                    <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- URLs List -->
            <div class="max-h-80 overflow-y-auto">
                <template x-for="(urlItem, idx) in filteredUrls" :key="idx">
                    <label class="flex items-center px-6 py-3 border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer"
                           :class="urlItem.exists ? 'opacity-50' : ''">
                        <input type="checkbox" :value="urlItem.url" x-model="selectedUrls" :disabled="urlItem.exists"
                               class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500 disabled:opacity-50">
                        <span class="ml-3 text-sm text-slate-700 dark:text-slate-300 truncate flex-1" x-text="urlItem.url"></span>
                        <span x-show="urlItem.exists" class="ml-2 text-xs text-amber-600 dark:text-amber-400">Già presente</span>
                    </label>
                </template>
            </div>

            <div x-show="error" x-cloak class="px-6 py-4 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                <span x-text="error"></span>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex justify-between">
                <button @click="step = 2" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Indietro
                </button>
                <button @click="importUrls()" :disabled="loading || selectedUrls.length === 0"
                        class="inline-flex items-center px-6 py-2.5 rounded-xl bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-600/25">
                    <svg x-show="loading" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Importazione...' : 'Importa ' + selectedUrls.length + ' URL'"></span>
                </button>
            </div>
        </div>

        <!-- Success State -->
        <div x-show="step === 4" x-cloak class="p-8 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">Import completato!</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6">
                <span class="font-medium text-emerald-600 dark:text-emerald-400" x-text="importedCount"></span> URL importate nel pool.
            </p>
            <div class="flex justify-center gap-4">
                <button @click="resetWizard()" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Importa altre URL
                </button>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links') ?>"
                   class="px-6 py-2.5 rounded-xl bg-primary-600 text-white font-medium hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                    Vai al Pool
                </a>
            </div>
        </div>
    </div>

    <!-- Tab: Manual Input -->
    <div x-show="activeTab === 'manual'" x-cloak x-transition class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <form action="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/store-manual') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="p-6 space-y-6">
                <div>
                    <label for="urlsText" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        URL <span class="text-red-500">*</span>
                    </label>
                    <textarea name="urls_text" id="urlsText" rows="12" required
                              placeholder="Inserisci un URL per riga:

https://esempio.it/pagina-1
https://esempio.it/pagina-2
/pagina-relativa

Le righe che iniziano con # vengono ignorate."
                              class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition font-mono text-sm"></textarea>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Un URL per riga</span>
                        <span id="urlCount">0 URL</span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importa URL
                </button>
            </div>
        </form>
    </div>

    <!-- Help Box -->
    <div class="mt-6 p-5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Come funziona</h4>
                <ul class="space-y-1 list-disc list-inside">
                    <li>Gli URL importati verranno usati per inserire link interni negli articoli generati</li>
                    <li>Dopo l'import, esegui lo scraping per estrarre titolo e descrizione automaticamente</li>
                    <li>Gli URL duplicati vengono saltati automaticamente</li>
                    <li>Massimo 2000 URL per importazione da sitemap</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function importWizard() {
    return {
        activeTab: 'sitemap',
        step: 1,
        loading: false,
        error: '',
        siteUrl: '',
        sitemaps: [],
        selectedSitemaps: [],
        urls: [],
        selectedUrls: [],
        urlFilter: '',
        duplicatesRemoved: 0,
        importedCount: 0,

        get filteredUrls() {
            if (!this.urlFilter) return this.urls;
            const filter = this.urlFilter.toLowerCase();
            return this.urls.filter(u => u.url.toLowerCase().includes(filter));
        },

        async discoverSitemaps() {
            this.loading = true;
            this.error = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('site_url', this.siteUrl);

                const response = await fetch('<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/discover') ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.sitemaps = data.sitemaps;
                    this.selectedSitemaps = data.sitemaps.map(s => s.url);
                    this.step = 2;
                } else {
                    this.error = data.error || 'Errore nella ricerca delle sitemap';
                }
            } catch (e) {
                console.error('Errore:', e);
                this.error = 'Errore di rete. Riprova.';
            }

            this.loading = false;
        },

        async previewUrls() {
            this.loading = true;
            this.error = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                this.selectedSitemaps.forEach(s => formData.append('sitemaps[]', s));

                const response = await fetch('<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/preview') ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.urls = data.urls;
                    this.duplicatesRemoved = data.duplicates_removed || 0;
                    this.selectedUrls = this.urls.filter(u => !u.exists).map(u => u.url);
                    this.step = 3;
                } else {
                    this.error = data.error || 'Errore nel caricamento degli URL';
                }
            } catch (e) {
                console.error('Errore:', e);
                this.error = 'Errore di rete. Riprova.';
            }

            this.loading = false;
        },

        toggleAllUrls(event) {
            if (event.target.checked) {
                this.selectedUrls = this.urls.filter(u => !u.exists).map(u => u.url);
            } else {
                this.selectedUrls = [];
            }
        },

        async importUrls() {
            this.loading = true;
            this.error = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('sitemap_source', this.siteUrl);
                this.selectedUrls.forEach(u => formData.append('urls[]', u));

                const response = await fetch('<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/store') ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.importedCount = data.inserted;
                    this.step = 4;
                } else {
                    this.error = data.error || 'Errore durante l\'importazione';
                }
            } catch (e) {
                console.error('Errore:', e);
                this.error = 'Errore di rete. Riprova.';
            }

            this.loading = false;
        },

        resetWizard() {
            this.step = 1;
            this.siteUrl = '';
            this.sitemaps = [];
            this.selectedSitemaps = [];
            this.urls = [];
            this.selectedUrls = [];
            this.urlFilter = '';
            this.error = '';
        }
    }
}

// URL counter for manual tab
const urlsText = document.getElementById('urlsText');
const urlCount = document.getElementById('urlCount');

urlsText?.addEventListener('input', function() {
    const lines = this.value.split('\n').filter(line => {
        line = line.trim();
        return line && !line.startsWith('#');
    });
    urlCount.textContent = `${lines.length} URL`;
});
</script>
