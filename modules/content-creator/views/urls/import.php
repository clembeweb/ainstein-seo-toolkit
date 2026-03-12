<?php
/**
 * Import URL per Content Creator
 * 4 tab: CSV, Sitemap, CMS, Manuale
 *
 * Required variables:
 * - $project: array with project data
 * - $currentPage: string identifying current page ('import')
 * - $connectors: array of configured CMS connectors (can be empty)
 */
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-4xl mx-auto space-y-6" x-data="importWizard()">
    <!-- Header -->
    <div>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Importa URL</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Importa le pagine per cui vuoi generare contenuti ottimizzati
        </p>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <button type="button"
                        @click="activeTab = 'csv'"
                        :class="activeTab === 'csv' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    CSV
                </button>
                <button type="button"
                        @click="activeTab = 'sitemap'"
                        :class="activeTab === 'sitemap' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Sitemap
                </button>
                <button type="button"
                        @click="activeTab = 'cms'"
                        :class="activeTab === 'cms' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    CMS
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
            <!-- CSV Tab -->
            <div x-show="activeTab === 'csv'">
                <div class="space-y-4">
                    <!-- Step 1: Upload file -->
                    <div x-show="!csvPreviewRows.length">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                File CSV <span class="text-red-500">*</span>
                            </label>
                            <input type="file"
                                   accept=".csv,.txt"
                                   @change="previewCsv($event)"
                                   class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-primary-100 file:text-primary-700 dark:file:bg-primary-900/50 dark:file:text-primary-300">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-4">
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
                            <div class="flex items-end">
                                <label class="flex items-center pb-2">
                                    <input type="checkbox" x-model="csvHasHeader" class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Prima riga = intestazione</span>
                                </label>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">
                            Carica un file CSV e vedrai un'anteprima delle prime righe per mappare le colonne.
                        </p>
                    </div>

                    <!-- Step 2: Preview + Column Mapping -->
                    <div x-show="csvPreviewRows.length > 0" x-cloak>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Anteprima CSV</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="csvTotalRows + ' righe trovate' + (csvHasHeader ? ' (esclusa intestazione)' : '')"></p>
                            </div>
                            <button type="button" @click="resetCsvPreview()" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                                Cambia file
                            </button>
                        </div>

                        <!-- Column Mapping Dropdowns -->
                        <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-3">Mappa le colonne</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-400 mb-1">
                                        URL <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="csvUrlColumn" class="w-full px-3 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <template x-for="(header, idx) in csvHeaders" :key="idx">
                                            <option :value="idx" x-text="header || ('Colonna ' + (idx + 1))"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-400 mb-1">Keyword</label>
                                    <select x-model="csvKeywordColumn" class="w-full px-3 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="-1">-- Ignora --</option>
                                        <template x-for="(header, idx) in csvHeaders" :key="idx">
                                            <option :value="idx" x-text="header || ('Colonna ' + (idx + 1))"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-400 mb-1">Categoria</label>
                                    <select x-model="csvCategoryColumn" class="w-full px-3 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="-1">-- Ignora --</option>
                                        <template x-for="(header, idx) in csvHeaders" :key="idx">
                                            <option :value="idx" x-text="header || ('Colonna ' + (idx + 1))"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Table -->
                        <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xl">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                                        <template x-for="(header, idx) in csvHeaders" :key="idx">
                                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                                <div class="flex items-center gap-1.5">
                                                    <span x-text="header || ('Col ' + (idx + 1))"></span>
                                                    <span x-show="parseInt(csvUrlColumn) === idx" class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">URL</span>
                                                    <span x-show="parseInt(csvKeywordColumn) === idx" class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">KW</span>
                                                    <span x-show="parseInt(csvCategoryColumn) === idx" class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">CAT</span>
                                                </div>
                                            </th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <template x-for="(row, rowIdx) in csvPreviewRows" :key="rowIdx">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <template x-for="(cell, cellIdx) in row" :key="cellIdx">
                                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 max-w-xs truncate" x-text="cell"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-4">
                            <button type="button"
                                    @click="importFromCsv()"
                                    :disabled="loading"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="'Importa ' + csvTotalRows + ' URL'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sitemap Tab -->
            <div x-show="activeTab === 'sitemap'" x-cloak x-init="siteUrl = '<?= e($project['base_url'] ?? '') ?>'">
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
                                        class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
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
                            <button type="button" @click="sitemaps = []; selectedSitemaps = []" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                                Cambia sito
                            </button>
                        </div>

                        <!-- Filter -->
                        <div class="mb-3">
                            <input type="text"
                                   x-model="sitemapFilter"
                                   placeholder="Filtra sitemap..."
                                   class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <template x-for="sitemap in sitemaps.filter(s => !sitemapFilter || s.url.toLowerCase().includes(sitemapFilter.toLowerCase()))" :key="sitemap.url">
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                    <input type="checkbox" :value="sitemap.url" x-model="selectedSitemaps" class="mt-1 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="sitemap.url"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="sitemap.type + (sitemap.count ? ' - ' + sitemap.count + ' URL' : '')"></p>
                                    </div>
                                </label>
                            </template>
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

            <!-- CMS Tab -->
            <div x-show="activeTab === 'cms'" x-cloak>
                <?php if (empty($connectors)): ?>
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-slate-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun connettore configurato</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Configura un connettore CMS per importare le pagine direttamente dal tuo sito
                    </p>
                    <a href="<?= url('/content-creator/connectors') ?>"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Configura Connettori
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Seleziona connettore
                        </label>
                        <select x-model="cmsConnectorId" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($connectors as $connector): ?>
                            <option value="<?= $connector['id'] ?>"><?= e($connector['name']) ?> (<?= e($connector['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Tipo di contenuto
                        </label>
                        <select x-model="cmsEntityType" class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="products">Prodotti</option>
                            <option value="categories">Categorie</option>
                            <option value="pages">Pagine</option>
                        </select>
                    </div>

                    <div class="pt-4">
                        <button type="button"
                                @click="importFromCms()"
                                :disabled="!cmsConnectorId || loading"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importa da CMS
                        </button>
                    </div>
                </div>
                <?php endif; ?>
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
        activeTab: '<?= !empty($project['connector_id']) ? 'cms' : 'csv' ?>',
        loading: false,
        resultMessage: '',
        resultSuccess: false,

        // CSV
        csvFile: null,
        csvDelimiter: 'auto',
        csvUrlColumn: 0,
        csvKeywordColumn: -1,
        csvCategoryColumn: -1,
        csvHasHeader: true,
        csvPreviewRows: [],
        csvHeaders: [],
        csvTotalRows: 0,

        // Sitemap
        siteUrl: '<?= e($project['base_url'] ?? '') ?>',
        sitemaps: [],
        selectedSitemaps: [],
        sitemapFilter: '',

        // CMS
        cmsConnectorId: '<?= (int)($project['connector_id'] ?? 0) ?: '' ?>',
        cmsEntityType: 'products',

        // Manual
        manualUrls: '',

        async discoverSitemaps() {
            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('site_url', this.siteUrl);

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/discover") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    this.resultMessage = 'Errore del server (' + response.status + ')';
                    this.resultSuccess = false;
                    this.loading = false;
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    this.sitemaps = data.sitemaps;
                    if (this.sitemaps.length === 0) {
                        this.resultMessage = 'Nessuna sitemap trovata per questo sito';
                        this.resultSuccess = false;
                    }
                } else {
                    this.resultMessage = data.error || 'Errore nella ricerca delle sitemap';
                    this.resultSuccess = false;
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

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/import/sitemap") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    this.resultMessage = 'Errore del server (' + response.status + ')';
                    this.resultSuccess = false;
                    this.loading = false;
                    return;
                }

                const data = await response.json();
                this.resultMessage = data.success ? data.message : (data.error || 'Importazione fallita');
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/content-creator/projects/{$project['id']}") ?>';
                    }, 1500);
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        },

        previewCsv(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.csvFile = file;

            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const lines = text.split(/\r?\n/).filter(l => l.trim());
                if (!lines.length) return;

                // Auto-detect delimiter
                let delim = this.csvDelimiter;
                if (delim === 'auto') {
                    const first = lines[0];
                    if (first.includes('\t')) delim = '\t';
                    else if (first.includes(';')) delim = ';';
                    else delim = ',';
                }

                // Simple CSV parse (handles quoted fields)
                const parseLine = (line) => {
                    const result = [];
                    let current = '';
                    let inQuotes = false;
                    for (let i = 0; i < line.length; i++) {
                        const ch = line[i];
                        if (ch === '"') {
                            if (inQuotes && line[i + 1] === '"') { current += '"'; i++; }
                            else inQuotes = !inQuotes;
                        } else if (ch === delim && !inQuotes) {
                            result.push(current.trim());
                            current = '';
                        } else {
                            current += ch;
                        }
                    }
                    result.push(current.trim());
                    return result;
                };

                const allRows = lines.map(parseLine);

                // Headers
                if (this.csvHasHeader && allRows.length > 0) {
                    this.csvHeaders = allRows[0];
                    const dataRows = allRows.slice(1);
                    this.csvPreviewRows = dataRows.slice(0, 5);
                    this.csvTotalRows = dataRows.length;
                } else {
                    const colCount = allRows[0]?.length || 0;
                    this.csvHeaders = Array.from({length: colCount}, (_, i) => 'Colonna ' + (i + 1));
                    this.csvPreviewRows = allRows.slice(0, 5);
                    this.csvTotalRows = allRows.length;
                }

                // Auto-detect URL column (first column with URLs)
                const sampleRow = this.csvPreviewRows[0] || [];
                for (let i = 0; i < sampleRow.length; i++) {
                    if (/^https?:\/\//i.test(sampleRow[i]) || /\.\w{2,}\//.test(sampleRow[i])) {
                        this.csvUrlColumn = i;
                        break;
                    }
                }

                // Auto-detect keyword/category from header names
                this.csvKeywordColumn = -1;
                this.csvCategoryColumn = -1;
                this.csvHeaders.forEach((h, i) => {
                    const lower = h.toLowerCase();
                    if (this.csvKeywordColumn === -1 && /keyword|parola|kw/.test(lower)) this.csvKeywordColumn = i;
                    if (this.csvCategoryColumn === -1 && /categ|category|tipo|type/.test(lower)) this.csvCategoryColumn = i;
                });
            };
            reader.readAsText(file);
        },

        resetCsvPreview() {
            this.csvFile = null;
            this.csvPreviewRows = [];
            this.csvHeaders = [];
            this.csvTotalRows = 0;
            this.csvUrlColumn = 0;
            this.csvKeywordColumn = -1;
            this.csvCategoryColumn = -1;
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
                formData.append('keyword_column', this.csvKeywordColumn);
                formData.append('category_column', this.csvCategoryColumn);
                if (this.csvHasHeader) formData.append('has_header', '1');

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/import/csv") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    this.resultMessage = 'Errore del server (' + response.status + ')';
                    this.resultSuccess = false;
                    this.loading = false;
                    return;
                }

                const data = await response.json();
                this.resultMessage = data.success ? data.message : (data.error || 'Importazione fallita');
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/content-creator/projects/{$project['id']}") ?>';
                    }, 1500);
                }
            } catch (error) {
                this.resultMessage = 'Errore di connessione';
                this.resultSuccess = false;
            }

            this.loading = false;
        },

        async importFromCms() {
            this.loading = true;
            this.resultMessage = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('connector_id', this.cmsConnectorId);
                formData.append('entity_type', this.cmsEntityType);

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/import/cms") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    this.resultMessage = 'Errore del server (' + response.status + ')';
                    this.resultSuccess = false;
                    this.loading = false;
                    return;
                }

                const data = await response.json();
                this.resultMessage = data.success ? data.message : (data.error || 'Importazione fallita');
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/content-creator/projects/{$project['id']}") ?>';
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

                const response = await fetch('<?= url("/content-creator/projects/{$project['id']}/import/manual") ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    this.resultMessage = 'Errore del server (' + response.status + ')';
                    this.resultSuccess = false;
                    this.loading = false;
                    return;
                }

                const data = await response.json();
                this.resultMessage = data.success ? data.message : (data.error || 'Importazione fallita');
                this.resultSuccess = data.success;

                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '<?= url("/content-creator/projects/{$project['id']}") ?>';
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
