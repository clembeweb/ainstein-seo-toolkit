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
            <button @click="activeTab = 'url'" :class="activeTab === 'url' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                Da URL
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

    <!-- URL Tab -->
    <div x-show="activeTab === 'url'" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Importa da URL Prodotto</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Inserisci l'URL di una pagina prodotto. Le immagini verranno estratte automaticamente.</p>

            <!-- Step 1: URL input -->
            <div class="flex gap-3">
                <input type="url" x-model="urlInput" placeholder="https://shop.com/prodotto-esempio"
                       @keydown.enter.prevent="scrapeUrl()"
                       class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-orange-500 focus:border-orange-500">
                <button @click="scrapeUrl()" :disabled="urlLoading || !urlInput.trim()"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                    <template x-if="urlLoading">
                        <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!urlLoading">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </template>
                    <span x-text="urlLoading ? 'Analisi...' : 'Analizza Pagina'"></span>
                </button>
            </div>

            <!-- Step 2: Image grid -->
            <template x-if="urlImages.length > 0">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            <span x-text="urlImages.length"></span> immagini trovate — seleziona quella del prodotto
                        </p>
                        <span class="text-xs text-slate-400" x-text="'Fonte: ' + urlPageUrl"></span>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                        <template x-for="(img, idx) in urlImages" :key="idx">
                            <div @click="urlSelectedImage = idx"
                                 class="relative rounded-xl border-2 cursor-pointer overflow-hidden aspect-square transition-all"
                                 :class="urlSelectedImage === idx
                                     ? 'border-orange-500 ring-2 ring-orange-500/30 shadow-lg'
                                     : 'border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500'">
                                <!-- Priority badge -->
                                <template x-if="img.priority === 'high'">
                                    <span class="absolute top-1 left-1 z-10 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-500 text-white shadow">
                                        Consigliata
                                    </span>
                                </template>
                                <!-- Source badge -->
                                <span class="absolute top-1 right-1 z-10 inline-flex items-center px-1 py-0.5 rounded text-[9px] font-medium"
                                      :class="img.source === 'json-ld' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                                            : img.source === 'og' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                            : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'"
                                      x-text="img.source === 'json-ld' ? 'Schema' : img.source === 'og' ? 'OG' : 'IMG'"></span>
                                <!-- Thumbnail -->
                                <template x-if="img.thumb">
                                    <img :src="img.thumb" class="w-full h-full object-cover" :alt="img.alt" loading="lazy">
                                </template>
                                <template x-if="!img.thumb">
                                    <div class="w-full h-full flex items-center justify-center bg-slate-100 dark:bg-slate-700">
                                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </template>
                                <!-- Selected check -->
                                <template x-if="urlSelectedImage === idx">
                                    <div class="absolute inset-0 bg-orange-500/10 flex items-center justify-center">
                                        <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center shadow-lg">
                                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Alt text of selected image -->
                    <template x-if="urlSelectedImage !== null && urlImages[urlSelectedImage]?.alt">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Alt: <span class="italic" x-text="urlImages[urlSelectedImage].alt"></span>
                        </p>
                    </template>

                    <!-- Step 3: Product details -->
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome prodotto <span class="text-red-500">*</span></label>
                                <input type="text" x-model="urlProductName" required
                                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                       placeholder="Es. Giacca in pelle nera">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SKU</label>
                                <input type="text" x-model="urlSku"
                                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                       placeholder="Opzionale">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria</label>
                                <select x-model="urlCategory"
                                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                                    <option value="fashion">Fashion</option>
                                    <option value="home">Home</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button @click="importFromUrl()" :disabled="importing || urlSelectedImage === null || !urlProductName.trim()"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-600 text-white font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors">
                                <span x-text="importing ? 'Importazione...' : 'Importa Prodotto'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- No images found -->
            <template x-if="urlScraped && urlImages.length === 0">
                <div class="text-center py-6">
                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna immagine prodotto trovata in questa pagina.</p>
                    <p class="text-xs text-slate-400 mt-1">Prova con la tab "Upload Manuale" per caricare l'immagine direttamente.</p>
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

        // URL
        urlInput: '',
        urlLoading: false,
        urlImages: [],
        urlSelectedImage: null,
        urlProductName: '',
        urlSku: '',
        urlCategory: 'fashion',
        urlPageUrl: '',
        urlScraped: false,

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

        async scrapeUrl() {
            if (!this.urlInput.trim()) return;
            this.urlLoading = true;
            this.urlImages = [];
            this.urlSelectedImage = null;
            this.urlScraped = false;
            this.resultMessage = '';
            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('url', this.urlInput.trim());
                const resp = await fetch(scrapeUrlEndpoint, { method: 'POST', body: fd });
                if (!resp.ok) throw new Error(`Errore server (${resp.status})`);
                const data = await resp.json();
                if (data.error) {
                    this.resultMessage = data.message || 'Errore durante l\'analisi';
                    this.resultError = true;
                } else {
                    this.urlImages = data.images || [];
                    this.urlPageUrl = data.url || this.urlInput;
                    this.urlProductName = data.page_title || '';
                    // Auto-select first high priority image
                    const highIdx = this.urlImages.findIndex(i => i.priority === 'high');
                    if (highIdx >= 0) this.urlSelectedImage = highIdx;
                }
                this.urlScraped = true;
            } catch (e) {
                this.resultMessage = 'Errore: ' + e.message;
                this.resultError = true;
            } finally {
                this.urlLoading = false;
            }
        },

        async importFromUrl() {
            if (this.urlSelectedImage === null) { alert('Seleziona un\'immagine'); return; }
            if (!this.urlProductName.trim()) { alert('Inserisci il nome del prodotto'); return; }
            this.importing = true;
            this.resultMessage = '';
            try {
                const img = this.urlImages[this.urlSelectedImage];
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('source', 'url');
                fd.append('image_url', img.src);
                fd.append('product_url', this.urlPageUrl);
                fd.append('product_name', this.urlProductName.trim());
                fd.append('sku', this.urlSku.trim());
                fd.append('category', this.urlCategory);
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
