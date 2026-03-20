<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6" x-data="importImageWizard()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Importa Prodotti</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Importa prodotti con le loro immagini per generare varianti AI</p>
        </div>
        <a href="<?= url("/content-creator/projects/{$project['id']}/images") ?>"
           class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
            &larr; Torna alla lista
        </a>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-4">
            <?php if ($connectorSupportsImages): ?>
            <button @click="activeTab = 'cms'" :class="activeTab === 'cms' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Da CMS
            </button>
            <?php endif; ?>
            <button @click="activeTab = 'csv'" :class="activeTab === 'csv' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                CSV
            </button>
            <button @click="activeTab = 'sitemap'" :class="activeTab === 'sitemap' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                Sitemap
            </button>
            <button @click="activeTab = 'bulk'" :class="activeTab === 'bulk' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                URL Prodotti
            </button>
            <button @click="activeTab = 'manual'" :class="activeTab === 'manual' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Upload Manuale
            </button>
        </nav>
    </div>

    <!-- CMS Tab -->
    <?php if ($connectorSupportsImages): ?>
    <div x-show="activeTab === 'cms'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Importa da <?= e($connector['type'] ?? 'CMS') ?></h3>
                <button @click="fetchCmsProducts()" :disabled="cmsLoading"
                        class="inline-flex items-center px-3 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" :class="cmsLoading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="cmsLoading ? 'Caricamento...' : 'Carica Prodotti'"></span>
                </button>
            </div>

            <!-- Global category selector -->
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Categoria per tutti:</label>
                <select x-model="defaultCategory" @change="setAllCategories()"
                        class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                    <option value="fashion">Fashion</option>
                    <option value="home">Home</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            <!-- Products table -->
            <div x-show="cmsProducts.length > 0">
                <div class="flex items-center gap-2 mb-3">
                    <input type="checkbox" @change="toggleAllCms($event)" class="rounded border-slate-300 dark:border-slate-600">
                    <span class="text-sm text-slate-500 dark:text-slate-400" x-text="cmsSelected.length + '/' + cmsProducts.length + ' selezionati'"></span>
                </div>
                <div class="max-h-96 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 w-10"></th>
                                <th class="px-4 py-3 w-16 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Foto</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Prodotto</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">SKU</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Categoria</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <template x-for="product in cmsProducts" :key="product.id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" :value="product.id"
                                               @change="toggleCmsSelect(product.id)"
                                               :checked="cmsSelected.includes(product.id)"
                                               class="rounded border-slate-300 dark:border-slate-600">
                                    </td>
                                    <td class="px-4 py-3">
                                        <img :src="product.image_url" class="w-12 h-12 object-cover rounded-lg" loading="lazy"
                                             :alt="product.name" onerror="this.style.display='none'">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-white" x-text="product.name"></td>
                                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400" x-text="product.sku || '—'"></td>
                                    <td class="px-4 py-3">
                                        <select :data-id="product.id" x-model="cmsCategories[product.id]"
                                                class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-xs">
                                            <option value="fashion">Fashion</option>
                                            <option value="home">Home</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button @click="importCms()" :disabled="importing || cmsSelected.length === 0"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                        <span x-text="importing ? 'Importazione...' : 'Importa ' + cmsSelected.length + ' prodotti'"></span>
                    </button>
                </div>
            </div>

            <div x-show="cmsProducts.length === 0 && !cmsLoading" class="text-center py-8 text-sm text-slate-500 dark:text-slate-400">
                Clicca "Carica Prodotti" per recuperare i prodotti dal CMS
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- CSV Tab -->
    <div x-show="activeTab === 'csv'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Importa da CSV</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Il CSV deve avere almeno le colonne: URL immagine, Nome prodotto</p>

            <form @submit.prevent="importCsv()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">File CSV</label>
                        <input type="file" accept=".csv" x-ref="csvFile"
                               class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 dark:file:bg-orange-900/20 dark:file:text-orange-400">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" x-model="csvHasHeader" id="has_header" class="rounded border-slate-300 dark:border-slate-600">
                        <label for="has_header" class="text-sm text-slate-700 dark:text-slate-300">Il file ha una riga di intestazione</label>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Colonna URL immagine</label>
                            <input type="number" x-model="csvColImageUrl" min="0" value="0"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Colonna Nome</label>
                            <input type="number" x-model="csvColName" min="0" value="1"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Colonna SKU (-1 = nessuna)</label>
                            <input type="number" x-model="csvColSku" min="-1" value="-1"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Colonna Categoria (-1 = default)</label>
                            <input type="number" x-model="csvColCategory" min="-1" value="-1"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria default</label>
                        <select x-model="defaultCategory"
                                class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                            <option value="fashion">Fashion</option>
                            <option value="home">Home</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="importing"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                            <span x-text="importing ? 'Importazione...' : 'Importa CSV'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sitemap Tab -->
    <div x-show="activeTab === 'sitemap'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-5">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Importa da Sitemap</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Scopri la sitemap del sito, seleziona le URL prodotto e importa le immagini in batch.</p>

            <!-- Step 1: Site URL -->
            <div class="flex gap-3">
                <input type="text" x-model="sitemapSiteUrl" placeholder="www.example.com"
                       @keydown.enter.prevent="discoverSitemaps()"
                       class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-orange-500 focus:border-orange-500">
                <button @click="discoverSitemaps()" :disabled="sitemapLoading || !sitemapSiteUrl.trim()"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                    <template x-if="sitemapLoading">
                        <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!sitemapLoading">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </template>
                    <span x-text="sitemapLoading ? 'Ricerca...' : 'Trova Sitemap'"></span>
                </button>
            </div>

            <!-- Step 2: Sitemap list -->
            <template x-if="sitemaps.length > 0">
                <div class="space-y-4">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        <span x-text="sitemaps.length"></span> sitemap trovate
                    </p>

                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700/50">
                                <tr>
                                    <th class="px-4 py-3 w-10"></th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Sitemap</th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left w-24">URL</th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left w-28">Fonte</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <template x-for="(sm, idx) in sitemaps" :key="idx">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" :value="sm.url"
                                                   @change="toggleSitemapSelect(sm.url)"
                                                   :checked="sitemapSelected.includes(sm.url)"
                                                   class="rounded border-slate-300 dark:border-slate-600">
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white break-all" x-text="sm.url"></td>
                                        <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400" x-text="sm.url_count !== null ? sm.url_count : '—'"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="sm.source === 'robots.txt' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'"
                                                  x-text="sm.source"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Filter + Max URLs -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Filtro URL (wildcard)</label>
                            <input type="text" x-model="sitemapFilter" placeholder="Es. *product* oppure *prodott*"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                            <p class="mt-1 text-xs text-slate-400">Lascia vuoto per includere tutte le URL</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Max URL</label>
                            <input type="number" x-model.number="sitemapMaxUrls" min="1" max="500"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button @click="analyzeSitemapProducts()" :disabled="bulkAnalyzing || sitemapSelected.length === 0"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                            <template x-if="bulkAnalyzing">
                                <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="bulkAnalyzing ? 'Analisi in corso...' : 'Analizza Prodotti'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Sitemap bulk results (shared with URL Prodotti) -->
            <template x-if="bulkResults.length > 0 && activeTab === 'sitemap'">
                <div class="space-y-4 border-t border-slate-200 dark:border-slate-700 pt-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            <span x-text="bulkResults.filter(r => !r.error && r.best_image).length"></span> prodotti con immagine trovati su
                            <span x-text="bulkResults.length"></span> URL analizzate
                        </p>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Categoria per tutti:</label>
                            <select x-model="bulkDefaultCategory" @change="setBulkCategoryAll()"
                                    class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                                <option value="fashion">Fashion</option>
                                <option value="home">Home</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bulk results table -->
                    <div class="flex items-center gap-2 mb-2">
                        <input type="checkbox" @change="toggleAllBulk($event)" class="rounded border-slate-300 dark:border-slate-600"
                               :checked="bulkSelected.length === bulkResults.filter(r => !r.error && r.best_image).length && bulkSelected.length > 0">
                        <span class="text-sm text-slate-500 dark:text-slate-400" x-text="bulkSelected.length + ' selezionati'"></span>
                    </div>
                    <div class="max-h-[32rem] overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 w-10"></th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">URL</th>
                                    <th class="px-4 py-3 w-20 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Immagine</th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Nome prodotto</th>
                                    <th class="px-4 py-3 w-28 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Categoria</th>
                                    <th class="px-4 py-3 w-16 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Stato</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <template x-for="(result, idx) in bulkResults" :key="idx">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50" :class="result.error ? 'opacity-60' : ''">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" :disabled="!!result.error || !result.best_image"
                                                   @change="toggleBulkSelect(idx)"
                                                   :checked="bulkSelected.includes(idx)"
                                                   class="rounded border-slate-300 dark:border-slate-600 disabled:opacity-30">
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white max-w-[200px]">
                                            <span class="block truncate" x-text="result.url" :title="result.url"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="relative">
                                                <template x-if="result.best_image && result.best_image.thumb">
                                                    <div class="relative group cursor-pointer" @click="bulkPickerOpen = (bulkPickerOpen === idx ? null : idx)">
                                                        <img :src="result.best_image.thumb" class="w-14 h-14 object-cover rounded-lg" loading="lazy">
                                                        <template x-if="result.all_images && result.all_images.length > 1">
                                                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center shadow">
                                                                <span x-text="result.all_images.length"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="!result.best_image || !result.best_image.thumb">
                                                    <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                </template>

                                                <!-- Mini image picker popup -->
                                                <template x-if="bulkPickerOpen === idx && result.all_images && result.all_images.length > 1">
                                                    <div class="absolute left-0 top-16 z-30 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl p-3 min-w-[200px]"
                                                         @click.outside="bulkPickerOpen = null">
                                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Seleziona immagine:</p>
                                                        <div class="flex gap-2 flex-wrap">
                                                            <template x-for="(altImg, imgIdx) in result.all_images" :key="imgIdx">
                                                                <div @click.stop="selectBulkImage(idx, imgIdx); bulkPickerOpen = null"
                                                                     class="w-14 h-14 rounded-lg border-2 cursor-pointer overflow-hidden transition-all"
                                                                     :class="result.best_image && result.best_image.src === altImg.src ? 'border-orange-500 ring-1 ring-orange-500/30' : 'border-slate-200 dark:border-slate-600 hover:border-orange-400'">
                                                                    <div class="w-full h-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-[9px] text-slate-400 p-0.5 text-center"
                                                                         x-text="altImg.source === 'json-ld' ? 'Schema' : altImg.source === 'og' ? 'OG' : 'IMG'">
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <template x-if="!result.error">
                                                <input type="text" x-model="result.page_title"
                                                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                                       placeholder="Nome prodotto">
                                            </template>
                                            <template x-if="result.error">
                                                <span class="text-xs text-red-500" x-text="result.error"></span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3">
                                            <template x-if="!result.error">
                                                <select x-model="result._category"
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-xs">
                                                    <option value="fashion">Fashion</option>
                                                    <option value="home">Home</option>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="!result.error && result.best_image">
                                                <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </template>
                                            <template x-if="!result.error && !result.best_image">
                                                <svg class="w-5 h-5 text-amber-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                </svg>
                                            </template>
                                            <template x-if="result.error">
                                                <svg class="w-5 h-5 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <button @click="importBulk()" :disabled="importing || bulkSelected.length === 0"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                            <span x-text="importing ? 'Importazione...' : 'Importa ' + bulkSelected.length + ' prodotti selezionati'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- URL Prodotti Tab (bulk) -->
    <div x-show="activeTab === 'bulk'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">URL Prodotti</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Inserisci gli URL delle pagine prodotto (uno per riga, max 20). Le immagini verranno estratte automaticamente.</p>

            <!-- Step 1: Multi-URL textarea -->
            <div class="space-y-3">
                <textarea x-model="bulkUrlsText" rows="6"
                          placeholder="https://shop.com/prodotto-1&#10;https://shop.com/prodotto-2&#10;https://shop.com/prodotto-3"
                          class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm font-mono focus:ring-orange-500 focus:border-orange-500"></textarea>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400" x-text="countValidUrls() + ' URL valide'"></span>
                    <button @click="analyzeBulkUrls()" :disabled="bulkAnalyzing || countValidUrls() === 0"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                        <template x-if="bulkAnalyzing">
                            <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <template x-if="!bulkAnalyzing">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </template>
                        <span x-text="bulkAnalyzing ? 'Analisi in corso...' : 'Analizza Pagine'"></span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Results table (same structure as sitemap results) -->
            <template x-if="bulkResults.length > 0 && activeTab === 'bulk'">
                <div class="space-y-4 border-t border-slate-200 dark:border-slate-700 pt-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            <span x-text="bulkResults.filter(r => !r.error && r.best_image).length"></span> prodotti con immagine trovati su
                            <span x-text="bulkResults.length"></span> URL analizzate
                        </p>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Categoria per tutti:</label>
                            <select x-model="bulkDefaultCategory" @change="setBulkCategoryAll()"
                                    class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                                <option value="fashion">Fashion</option>
                                <option value="home">Home</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mb-2">
                        <input type="checkbox" @change="toggleAllBulk($event)" class="rounded border-slate-300 dark:border-slate-600"
                               :checked="bulkSelected.length === bulkResults.filter(r => !r.error && r.best_image).length && bulkSelected.length > 0">
                        <span class="text-sm text-slate-500 dark:text-slate-400" x-text="bulkSelected.length + ' selezionati'"></span>
                    </div>
                    <div class="max-h-[32rem] overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 w-10"></th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">URL</th>
                                    <th class="px-4 py-3 w-20 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Immagine</th>
                                    <th class="px-4 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Nome prodotto</th>
                                    <th class="px-4 py-3 w-28 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-left">Categoria</th>
                                    <th class="px-4 py-3 w-16 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Stato</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <template x-for="(result, idx) in bulkResults" :key="idx">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50" :class="result.error ? 'opacity-60' : ''">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" :disabled="!!result.error || !result.best_image"
                                                   @change="toggleBulkSelect(idx)"
                                                   :checked="bulkSelected.includes(idx)"
                                                   class="rounded border-slate-300 dark:border-slate-600 disabled:opacity-30">
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white max-w-[200px]">
                                            <span class="block truncate" x-text="result.url" :title="result.url"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="relative">
                                                <template x-if="result.best_image && result.best_image.thumb">
                                                    <div class="relative group cursor-pointer" @click="bulkPickerOpen = (bulkPickerOpen === idx ? null : idx)">
                                                        <img :src="result.best_image.thumb" class="w-14 h-14 object-cover rounded-lg" loading="lazy">
                                                        <template x-if="result.all_images && result.all_images.length > 1">
                                                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center shadow">
                                                                <span x-text="result.all_images.length"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="!result.best_image || !result.best_image.thumb">
                                                    <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                </template>

                                                <!-- Mini image picker popup -->
                                                <template x-if="bulkPickerOpen === idx && result.all_images && result.all_images.length > 1">
                                                    <div class="absolute left-0 top-16 z-30 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl p-3 min-w-[200px]"
                                                         @click.outside="bulkPickerOpen = null">
                                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Seleziona immagine:</p>
                                                        <div class="flex gap-2 flex-wrap">
                                                            <template x-for="(altImg, imgIdx) in result.all_images" :key="imgIdx">
                                                                <div @click.stop="selectBulkImage(idx, imgIdx); bulkPickerOpen = null"
                                                                     class="w-14 h-14 rounded-lg border-2 cursor-pointer overflow-hidden transition-all"
                                                                     :class="result.best_image && result.best_image.src === altImg.src ? 'border-orange-500 ring-1 ring-orange-500/30' : 'border-slate-200 dark:border-slate-600 hover:border-orange-400'">
                                                                    <div class="w-full h-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-[9px] text-slate-400 p-0.5 text-center"
                                                                         x-text="altImg.source === 'json-ld' ? 'Schema' : altImg.source === 'og' ? 'OG' : 'IMG'">
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <template x-if="!result.error">
                                                <input type="text" x-model="result.page_title"
                                                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                                       placeholder="Nome prodotto">
                                            </template>
                                            <template x-if="result.error">
                                                <span class="text-xs text-red-500" x-text="result.error"></span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3">
                                            <template x-if="!result.error">
                                                <select x-model="result._category"
                                                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-xs">
                                                    <option value="fashion">Fashion</option>
                                                    <option value="home">Home</option>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="!result.error && result.best_image">
                                                <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </template>
                                            <template x-if="!result.error && !result.best_image">
                                                <svg class="w-5 h-5 text-amber-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                </svg>
                                            </template>
                                            <template x-if="result.error">
                                                <svg class="w-5 h-5 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <button @click="importBulk()" :disabled="importing || bulkSelected.length === 0"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                            <span x-text="importing ? 'Importazione...' : 'Importa ' + bulkSelected.length + ' prodotti selezionati'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Manual Tab -->
    <div x-show="activeTab === 'manual'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Upload Manuale</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Carica una singola immagine prodotto</p>

            <form @submit.prevent="importManual()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Immagine prodotto <span class="text-red-500">*</span></label>
                        <input type="file" accept="image/png,image/jpeg,image/webp" x-ref="manualFile"
                               class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 dark:file:bg-orange-900/20 dark:file:text-orange-400">
                        <p class="mt-1 text-xs text-slate-500">PNG, JPG o WebP. Max 10MB.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome prodotto <span class="text-red-500">*</span></label>
                            <input type="text" x-model="manualName" required
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                   placeholder="Es. Giacca in pelle nera">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SKU</label>
                            <input type="text" x-model="manualSku"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                   placeholder="Opzionale">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria</label>
                            <select x-model="manualCategory"
                                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                                <option value="fashion">Fashion</option>
                                <option value="home">Home</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="importing"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                            <span x-text="importing ? 'Importazione...' : 'Importa'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Result message -->
    <div x-show="resultMessage" x-cloak
         :class="resultError ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300' : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'"
         class="border rounded-xl p-4 text-sm" x-text="resultMessage"></div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';
const projectId = <?= $project['id'] ?>;
const importUrl = '<?= url("/content-creator/projects/{$project['id']}/images/import") ?>';
const fetchCmsUrl = '<?= url("/content-creator/projects/{$project['id']}/images/fetch-cms") ?>';
const imageListUrl = '<?= url("/content-creator/projects/{$project['id']}/images") ?>';
const scrapeUrlEndpoint = '<?= url("/content-creator/projects/{$project['id']}/images/scrape-url") ?>';
const scrapeBatchUrl = '<?= url("/content-creator/projects/{$project['id']}/images/scrape-batch") ?>';
const discoverSitemapUrl = '<?= url("/content-creator/projects/{$project['id']}/images/discover-sitemap") ?>';
const sitemapUrlsEndpoint = '<?= url("/content-creator/projects/{$project['id']}/images/sitemap-urls") ?>';

function importImageWizard() {
    return {
        activeTab: '<?= $connectorSupportsImages ? 'cms' : 'csv' ?>',
        importing: false,
        resultMessage: '',
        resultError: false,
        defaultCategory: '<?= $defaults['scene_type'] ?? 'fashion' ?>',

        // CMS
        cmsLoading: false,
        cmsProducts: [],
        cmsSelected: [],
        cmsCategories: {},

        // CSV
        csvHasHeader: true,
        csvColImageUrl: 0,
        csvColName: 1,
        csvColSku: -1,
        csvColCategory: -1,

        // Sitemap
        sitemapSiteUrl: '',
        sitemapLoading: false,
        sitemaps: [],
        sitemapSelected: [],
        sitemapFilter: '',
        sitemapMaxUrls: 100,

        // Bulk URL
        bulkUrlsText: '',
        bulkAnalyzing: false,
        bulkResults: [],
        bulkSelected: [],
        bulkPickerOpen: null,

        // Shared
        bulkDefaultCategory: 'fashion',

        // Manual
        manualName: '',
        manualSku: '',
        manualCategory: 'fashion',

        setAllCategories() {
            for (const id of Object.keys(this.cmsCategories)) {
                this.cmsCategories[id] = this.defaultCategory;
            }
        },

        toggleCmsSelect(id) {
            const idx = this.cmsSelected.indexOf(id);
            if (idx > -1) this.cmsSelected.splice(idx, 1);
            else this.cmsSelected.push(id);
        },

        toggleAllCms(event) {
            if (event.target.checked) {
                this.cmsSelected = this.cmsProducts.map(p => p.id);
            } else {
                this.cmsSelected = [];
            }
        },

        toggleSitemapSelect(url) {
            const idx = this.sitemapSelected.indexOf(url);
            if (idx > -1) this.sitemapSelected.splice(idx, 1);
            else this.sitemapSelected.push(url);
        },

        toggleBulkSelect(idx) {
            const pos = this.bulkSelected.indexOf(idx);
            if (pos > -1) this.bulkSelected.splice(pos, 1);
            else this.bulkSelected.push(idx);
        },

        toggleAllBulk(event) {
            if (event.target.checked) {
                this.bulkSelected = this.bulkResults
                    .map((r, i) => (!r.error && r.best_image) ? i : null)
                    .filter(i => i !== null);
            } else {
                this.bulkSelected = [];
            }
        },

        setBulkCategoryAll() {
            this.bulkResults.forEach(r => {
                if (!r.error) r._category = this.bulkDefaultCategory;
            });
        },

        selectBulkImage(resultIdx, imageIdx) {
            const result = this.bulkResults[resultIdx];
            if (!result || !result.all_images || !result.all_images[imageIdx]) return;
            const selected = result.all_images[imageIdx];
            result.best_image = {
                src: selected.src,
                alt: selected.alt || '',
                source: selected.source,
                priority: selected.priority,
                thumb: result.best_image?.thumb || '',
            };
        },

        countValidUrls() {
            if (!this.bulkUrlsText.trim()) return 0;
            return this.bulkUrlsText.trim().split('\n')
                .map(l => l.trim())
                .filter(l => l && (l.startsWith('http://') || l.startsWith('https://'))).length;
        },

        async fetchCmsProducts() {
            this.cmsLoading = true;
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                const resp = await fetch(fetchCmsUrl, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.success && data.items) {
                    this.cmsProducts = data.items;
                    this.cmsCategories = {};
                    data.items.forEach(p => { this.cmsCategories[p.id] = this.defaultCategory; });
                } else {
                    alert(data.error || data.message || 'Errore nel caricamento prodotti');
                }
            } catch (e) {
                alert('Errore: ' + e.message);
            } finally {
                this.cmsLoading = false;
            }
        },

        async importCms() {
            this.importing = true;
            this.resultMessage = '';
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('source', 'cms');
                fd.append('default_category', this.defaultCategory);
                this.cmsSelected.forEach(id => fd.append('selected_ids[]', id));
                for (const [id, cat] of Object.entries(this.cmsCategories)) {
                    fd.append('categories[' + id + ']', cat);
                }
                const resp = await fetch(importUrl, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                this.resultMessage = data.message;
                this.resultError = !!data.error;
                if (data.success) setTimeout(() => { window.location.href = imageListUrl; }, 1500);
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.importing = false;
            }
        },

        async importCsv() {
            const file = this.$refs.csvFile?.files[0];
            if (!file) { alert('Seleziona un file CSV'); return; }
            this.importing = true;
            this.resultMessage = '';
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('source', 'csv');
                fd.append('csv_file', file);
                fd.append('col_image_url', this.csvColImageUrl);
                fd.append('col_name', this.csvColName);
                fd.append('col_sku', this.csvColSku);
                fd.append('col_category', this.csvColCategory);
                fd.append('default_category', this.defaultCategory);
                if (this.csvHasHeader) fd.append('has_header', '1');
                const resp = await fetch(importUrl, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                this.resultMessage = data.message;
                this.resultError = !!data.error;
                if (data.success) setTimeout(() => { window.location.href = imageListUrl; }, 1500);
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.importing = false;
            }
        },

        async discoverSitemaps() {
            if (!this.sitemapSiteUrl.trim()) return;
            this.sitemapLoading = true;
            this.sitemaps = [];
            this.sitemapSelected = [];
            this.bulkResults = [];
            this.bulkSelected = [];
            this.resultMessage = '';
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('site_url', this.sitemapSiteUrl.trim());
                const resp = await fetch(discoverSitemapUrl, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.success && data.sitemaps) {
                    this.sitemaps = data.sitemaps;
                } else {
                    this.resultMessage = data.error || 'Nessuna sitemap trovata';
                    this.resultError = true;
                }
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.sitemapLoading = false;
            }
        },

        async analyzeSitemapProducts() {
            if (this.sitemapSelected.length === 0) return;
            this.bulkAnalyzing = true;
            this.bulkResults = [];
            this.bulkSelected = [];
            this.resultMessage = '';
            try {
                // Step 1: Extract URLs from sitemaps
                const fd1 = new FormData();
                fd1.append('_csrf_token', csrfToken);
                fd1.append('sitemaps', JSON.stringify(this.sitemapSelected));
                fd1.append('filter', this.sitemapFilter);
                fd1.append('max_urls', this.sitemapMaxUrls);
                const resp1 = await fetch(sitemapUrlsEndpoint, { method: 'POST', body: fd1 });
                if (!resp1.ok) throw new Error(`Errore server (${resp1.status})`);
                const data1 = await resp1.json();
                if (data1.error) throw new Error(data1.message || 'Errore estrazione URL');
                if (!data1.urls || data1.urls.length === 0) {
                    this.resultMessage = 'Nessuna URL trovata nelle sitemap selezionate con il filtro applicato.';
                    this.resultError = true;
                    this.bulkAnalyzing = false;
                    return;
                }

                // Step 2: Scrape batch (max 20 at a time)
                const urls = data1.urls.slice(0, 20);
                if (data1.urls.length > 20) {
                    this.resultMessage = `Trovate ${data1.urls.length} URL, analizzate le prime 20.`;
                    this.resultError = false;
                }
                await this._scrapeBatch(urls);
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.bulkAnalyzing = false;
            }
        },

        async analyzeBulkUrls() {
            if (this.countValidUrls() === 0) return;
            this.bulkAnalyzing = true;
            this.bulkResults = [];
            this.bulkSelected = [];
            this.resultMessage = '';
            try {
                const urls = this.bulkUrlsText.trim().split('\n')
                    .map(l => l.trim())
                    .filter(l => l && (l.startsWith('http://') || l.startsWith('https://')))
                    .slice(0, 20);
                await this._scrapeBatch(urls);
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.bulkAnalyzing = false;
            }
        },

        async _scrapeBatch(urls) {
            const fd = new FormData();
            fd.append('_csrf_token', csrfToken);
            fd.append('urls', JSON.stringify(urls));
            const resp = await fetch(scrapeBatchUrl, { method: 'POST', body: fd });
            if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
            const data = await resp.json();
            if (data.error) throw new Error(data.message || 'Errore scraping batch');

            // Enrich results with _category field
            this.bulkResults = (data.results || []).map(r => ({
                ...r,
                _category: this.bulkDefaultCategory,
            }));

            // Auto-select results that have images
            this.bulkSelected = this.bulkResults
                .map((r, i) => (!r.error && r.best_image) ? i : null)
                .filter(i => i !== null);
        },

        async importBulk() {
            if (this.bulkSelected.length === 0) return;
            this.importing = true;
            this.resultMessage = '';
            try {
                const items = this.bulkSelected.map(idx => {
                    const r = this.bulkResults[idx];
                    return {
                        url: r.url,
                        image_url: r.best_image?.src || '',
                        name: r.page_title || '',
                        sku: '',
                        category: r._category || this.bulkDefaultCategory,
                    };
                }).filter(item => item.image_url && item.name);

                if (items.length === 0) {
                    this.resultMessage = 'Nessun prodotto valido da importare (controlla nome e immagine)';
                    this.resultError = true;
                    this.importing = false;
                    return;
                }

                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('source', 'bulk');
                fd.append('items', JSON.stringify(items));
                const resp = await fetch(importUrl, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                this.resultMessage = data.message;
                this.resultError = !!data.error;
                if (data.success) setTimeout(() => { window.location.href = imageListUrl; }, 1500);
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.importing = false;
            }
        },

        async importManual() {
            const file = this.$refs.manualFile?.files[0];
            if (!file) { alert('Seleziona un\'immagine'); return; }
            if (!this.manualName.trim()) { alert('Inserisci il nome del prodotto'); return; }
            if (file.size > 10 * 1024 * 1024) { alert('File troppo grande (max 10MB)'); return; }
            this.importing = true;
            this.resultMessage = '';
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('source', 'manual');
                fd.append('image_file', file);
                fd.append('product_name', this.manualName.trim());
                fd.append('sku', this.manualSku.trim());
                fd.append('category', this.manualCategory);
                const resp = await fetch(importUrl, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                this.resultMessage = data.message;
                this.resultError = !!data.error;
                if (data.success) setTimeout(() => { window.location.href = imageListUrl; }, 1500);
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.importing = false;
            }
        },
    }
}
</script>
