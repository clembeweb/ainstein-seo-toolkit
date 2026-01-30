<?php
/**
 * Import URL per Meta Tags
 * 4 tab: WordPress, Sitemap, CSV, Manuale
 */
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-4xl mx-auto space-y-6" x-data="importWizard()">
    <!-- Header -->
    <div>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Importa URL</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Importa le pagine per cui vuoi generare meta tag SEO ottimizzati
        </p>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <button type="button"
                        @click="activeTab = 'wordpress'"
                        :class="activeTab === 'wordpress' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    WordPress
                </button>
                <button type="button"
                        @click="activeTab = 'sitemap'"
                        :class="activeTab === 'sitemap' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Sitemap
                </button>
                <button type="button"
                        @click="activeTab = 'csv'"
                        :class="activeTab === 'csv' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    CSV
                </button>
                <button type="button"
                        @click="activeTab = 'manual'"
                        :class="activeTab === 'manual' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Manuale
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- WordPress Tab -->
            <div x-show="activeTab === 'wordpress'" x-cloak>
                <?php if (empty($wpSites)): ?>
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-slate-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun sito WordPress collegato</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Collega un sito WordPress per importare le pagine direttamente
                    </p>
                    <a href="<?= url('/ai-content/wordpress') ?>"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                        Collega WordPress
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Seleziona sito WordPress
                        </label>
                        <select x-model="wpSiteId" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($wpSites as $site): ?>
                            <option value="<?= $site['id'] ?>"><?= e($site['name']) ?> (<?= e($site['url']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Tipo di contenuto
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="wpPostTypes" value="post" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Articoli</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="wpPostTypes" value="page" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Pagine</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Numero massimo
                        </label>
                        <input type="number" x-model="wpLimit" min="10" max="500" class="w-32 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="pt-4">
                        <button type="button"
                                @click="importFromWp()"
                                :disabled="!wpSiteId || loading"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importa da WordPress
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sitemap Tab -->
            <div x-show="activeTab === 'sitemap'" x-cloak>
                <div class="space-y-4">
                    <!-- Step 1: Discover -->
                    <div x-show="!sitemaps.length">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                URL del sito
                            </label>
                            <div class="flex gap-2">
                                <input type="text"
                                       x-model="siteUrl"
                                       placeholder="https://example.com"
                                       class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <button type="button"
                                        @click="discoverSitemaps()"
                                        :disabled="!siteUrl || loading"
                                        class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!loading">Trova Sitemap</span>
                                    <span x-show="loading">Ricerca...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Select Sitemaps -->
                    <div x-show="sitemaps.length > 0">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-slate-900 dark:text-white">Sitemap trovate</h3>
                            <button type="button" @click="sitemaps = []" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                                Cambia sito
                            </button>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <template x-for="sitemap in sitemaps" :key="sitemap.url">
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                    <input type="checkbox" :value="sitemap.url" x-model="selectedSitemaps" class="mt-1 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="sitemap.url"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="sitemap.type + (sitemap.count ? ' - ' + sitemap.count + ' URL' : '')"></p>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Filtro URL (opzionale)
                            </label>
                            <input type="text"
                                   x-model="sitemapFilter"
                                   placeholder="Es: /blog/ per filtrare solo gli articoli"
                                   class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div class="pt-4">
                            <button type="button"
                                    @click="importFromSitemap()"
                                    :disabled="!selectedSitemaps.length || loading"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Importa URL
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CSV Tab -->
            <div x-show="activeTab === 'csv'" x-cloak>
                <form @submit.prevent="importFromCsv()" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                File CSV
                            </label>
                            <input type="file"
                                   accept=".csv,.txt"
                                   @change="csvFile = $event.target.files[0]"
                                   class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-primary-100 file:text-primary-700 dark:file:bg-primary-900/50 dark:file:text-primary-300">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Delimitatore
                                </label>
                                <select x-model="csvDelimiter" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="auto">Auto-detect</option>
                                    <option value=",">Virgola (,)</option>
                                    <option value=";">Punto e virgola (;)</option>
                                    <option value="\t">Tab</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Colonna URL (0-based)
                                </label>
                                <input type="number"
                                       x-model="csvUrlColumn"
                                       min="0"
                                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>

                        <label class="flex items-center">
                            <input type="checkbox" x-model="csvHasHeader" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Il file ha una riga di intestazione</span>
                        </label>

                        <div class="pt-4">
                            <button type="submit"
                                    :disabled="!csvFile || loading"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Importa CSV
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Manual Tab -->
            <div x-show="activeTab === 'manual'" x-cloak>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            URL (uno per riga)
                        </label>
                        <textarea
                            x-model="manualUrls"
                            rows="10"
                            placeholder="https://example.com/page1&#10;https://example.com/page2&#10;https://example.com/page3"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"></textarea>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Inserisci gli URL completi, uno per riga. Le righe vuote e i commenti (#) vengono ignorati.
                        </p>
                    </div>

                    <div class="pt-4">
                        <button type="button"
                                @click="importManual()"
                                :disabled="!manualUrls.trim() || loading"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importa URL
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Message -->
    <div x-show="resultMessage" x-cloak
         :class="resultSuccess ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'"
         class="rounded-lg border p-4">
        <div class="flex items-center gap-3">
            <template x-if="resultSuccess">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </template>
            <template x-if="!resultSuccess">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </template>
            <span :class="resultSuccess ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" x-text="resultMessage"></span>
        </div>
    </div>
</div>

<script>
function importWizard() {
    return {
        activeTab: 'wordpress',
        loading: false,
        resultMessage: '',
        resultSuccess: false,

        // WordPress
        wpSiteId: '',
        wpPostTypes: ['post', 'page'],
        wpLimit: 100,

        // Sitemap
        siteUrl: '',
        sitemaps: [],
        selectedSitemaps: [],
        sitemapFilter: '',

        // CSV
        csvFile: null,
        csvDelimiter: 'auto',
        csvUrlColumn: 0,
        csvHasHeader: true,

        // Manual
        manualUrls: '',

        async discoverSitemaps() {
            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('site_url', this.siteUrl);

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/discover") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.sitemaps = data.sitemaps;
                } else {
                    this.resultMessage = data.error;
                    this.resultSuccess = false;
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        },

        async importFromWp() {
            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('wp_site_id', this.wpSiteId);
                formData.append('limit', this.wpLimit);
                this.wpPostTypes.forEach(t => formData.append('post_types[]', t));

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/import/wp") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.resultMessage = data.success ? data.message : data.error;
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>';
                    }, 1500);
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        },

        async importFromSitemap() {
            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('filter', this.sitemapFilter);
                this.selectedSitemaps.forEach(s => formData.append('sitemaps[]', s));

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/import/sitemap") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.resultMessage = data.success ? data.message : data.error;
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>';
                    }, 1500);
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        },

        async importFromCsv() {
            if (!this.csvFile) return;

            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('csv_file', this.csvFile);
                formData.append('delimiter', this.csvDelimiter);
                formData.append('url_column', this.csvUrlColumn);
                if (this.csvHasHeader) formData.append('has_header', '1');

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/import/csv") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.resultMessage = data.success ? data.message : data.error;
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>';
                    }, 1500);
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        },

        async importManual() {
            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('urls_text', this.manualUrls);

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/import/manual") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.resultMessage = data.success ? data.message : data.error;
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>';
                    }, 1500);
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        }
    }
}
</script>
