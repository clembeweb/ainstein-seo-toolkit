<?php
$currentPage = 'pages';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6" x-data="importManager()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Importa Pagine</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Aggiungi URL al progetto per l'analisi</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="-mb-px flex" aria-label="Tabs">
                <button @click="activeTab = 'sitemap'" :class="activeTab === 'sitemap' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                    Sitemap
                </button>
                <button @click="activeTab = 'csv'" :class="activeTab === 'csv' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                    CSV / Lista
                </button>
                <button @click="activeTab = 'manual'" :class="activeTab === 'manual' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                    Manuale
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Sitemap Tab -->
            <div x-show="activeTab === 'sitemap'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">URL Sitemap</label>
                    <div class="flex gap-2">
                        <input type="url" x-model="sitemapUrl"
                               placeholder="https://<?= e($project['domain']) ?>/sitemap.xml"
                               class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <button @click="importSitemap()" :disabled="loading"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 disabled:opacity-50 transition-colors">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importa
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Lascia vuoto per usare sitemap.xml del dominio</p>
                </div>
            </div>

            <!-- CSV Tab -->
            <div x-show="activeTab === 'csv'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Lista URL (una per riga)</label>
                    <textarea x-model="csvContent" rows="10"
                              placeholder="https://esempio.com/pagina-1&#10;https://esempio.com/pagina-2&#10;https://esempio.com/pagina-3"
                              class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-mono text-sm"></textarea>
                </div>
                <button @click="importCsv()" :disabled="loading || !csvContent.trim()"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 disabled:opacity-50 transition-colors">
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Importa URL
                </button>
            </div>

            <!-- Manual Tab -->
            <div x-show="activeTab === 'manual'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">URL da aggiungere</label>
                    <textarea x-model="manualUrls" rows="6"
                              placeholder="/pagina-1&#10;/pagina-2&#10;https://<?= e($project['domain']) ?>/pagina-3"
                              class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-mono text-sm"></textarea>
                    <p class="mt-1 text-sm text-slate-500">Puoi inserire URL completi o path relativi (verranno completati con https://<?= e($project['domain']) ?>)</p>
                </div>
                <button @click="importManual()" :disabled="loading || !manualUrls.trim()"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 disabled:opacity-50 transition-colors">
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Aggiungi URL
                </button>
            </div>
        </div>

        <!-- Result Message -->
        <div x-show="message" x-transition class="px-6 pb-6">
            <div :class="messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-300' : 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300'"
                 class="rounded-lg border p-4">
                <p x-text="message"></p>
            </div>
        </div>
    </div>
</div>

<script>
function importManager() {
    const projectId = <?= (int) $project['id'] ?>;
    const csrfToken = '<?= csrf_token() ?>';
    const baseUrl = '<?= rtrim(url(''), '/') ?>';
    const domain = '<?= e($project['domain']) ?>';

    return {
        activeTab: 'sitemap',
        loading: false,
        message: '',
        messageType: 'success',
        sitemapUrl: '',
        csvContent: '',
        manualUrls: '',

        async importSitemap() {
            this.loading = true;
            this.message = '';

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('sitemap_url', this.sitemapUrl);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/pages/import/sitemap`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                this.message = data.message || data.error;
                this.messageType = data.success ? 'success' : 'error';

                if (data.success && data.inserted > 0) {
                    setTimeout(() => {
                        window.location.href = `${baseUrl}/seo-onpage/project/${projectId}/pages`;
                    }, 1500);
                }
            } catch (e) {
                this.message = 'Errore di connessione';
                this.messageType = 'error';
            }

            this.loading = false;
        },

        async importCsv() {
            this.loading = true;
            this.message = '';

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('csv_content', this.csvContent);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/pages/import/csv`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                this.message = data.message || data.error;
                this.messageType = data.success ? 'success' : 'error';

                if (data.success && data.inserted > 0) {
                    setTimeout(() => {
                        window.location.href = `${baseUrl}/seo-onpage/project/${projectId}/pages`;
                    }, 1500);
                }
            } catch (e) {
                this.message = 'Errore di connessione';
                this.messageType = 'error';
            }

            this.loading = false;
        },

        async importManual() {
            this.loading = true;
            this.message = '';

            // Prepara URL (aggiungi dominio se path relativo)
            let urls = this.manualUrls.split('\n').map(u => {
                u = u.trim();
                if (u && !u.startsWith('http')) {
                    u = 'https://' + domain + (u.startsWith('/') ? '' : '/') + u;
                }
                return u;
            }).filter(u => u).join('\n');

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('urls', urls);

            try {
                const resp = await fetch(`${baseUrl}/seo-onpage/project/${projectId}/pages/import/manual`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await resp.json();

                this.message = data.message || data.error;
                this.messageType = data.success ? 'success' : 'error';

                if (data.success && data.inserted > 0) {
                    setTimeout(() => {
                        window.location.href = `${baseUrl}/seo-onpage/project/${projectId}/pages`;
                    }, 1500);
                }
            } catch (e) {
                this.message = 'Errore di connessione';
                this.messageType = 'error';
            }

            this.loading = false;
        }
    };
}
</script>
