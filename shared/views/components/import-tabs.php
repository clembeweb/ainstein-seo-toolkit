<?php
/**
 * Componente Tab Importazione Riutilizzabile
 *
 * Interfaccia standardizzata a 3 tab (CSV, Sitemap, Manuale) per qualsiasi modulo.
 *
 * Parametri richiesti:
 * @param int    $projectId    ID del progetto
 * @param string $importUrl    URL base per le azioni di import (es. "/internal-links/project/1/urls")
 * @param string $moduleSlug   Identificativo del modulo (es. "internal-links")
 * @param array  $project      Array dati progetto con chiavi 'name' e 'base_url'
 *
 * Parametri opzionali:
 * @param string $backUrl      URL per il pulsante indietro (default: $importUrl)
 * @param string $backLabel    Etichetta pulsante indietro (default: "Torna agli URL")
 * @param bool   $showKeyword  Mostra opzione colonna keyword nel CSV (default: true)
 * @param int    $maxUrls      Massimo URL importabili (default: 10000)
 * @param array  $apiRoutes    Override route API ['discover' => ..., 'sitemap' => ..., 'csv' => ...]
 *
 * Utilizzo:
 * <?php
 * $projectId = $project['id'];
 * $importUrl = "/internal-links/project/{$projectId}/urls";
 * $moduleSlug = "internal-links";
 * include __DIR__ . '/../../../shared/views/components/import-tabs.php';
 * ?>
 */

// Valori default parametri
$projectId = $projectId ?? 0;
$importUrl = $importUrl ?? '';
$moduleSlug = $moduleSlug ?? '';
$project = $project ?? ['name' => '', 'base_url' => ''];
$backUrl = $backUrl ?? $importUrl;
$backLabel = $backLabel ?? 'Torna agli URL';
$showKeyword = $showKeyword ?? true;
$maxUrls = $maxUrls ?? 10000;

// Route API default
$apiRoutes = array_merge([
    'discover' => "/{$moduleSlug}/api/sitemap-discover",
    'sitemap' => "/{$moduleSlug}/api/sitemap",
    'csv' => "{$importUrl}/import-csv",
    'manual' => "{$importUrl}/store",
], $apiRoutes ?? []);
?>

<!-- Componente Tab Importazione -->
<div class="max-w-4xl">
    <!-- Header Pagina -->
    <div class="mb-8">
        <a href="<?= url($backUrl) ?>" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 mb-4 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <?= e($backLabel) ?>
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Importa URL</h1>
        <p class="mt-1 text-slate-500 dark:text-slate-400">Aggiungi URL a <?= e($project['name']) ?> per l'analisi</p>
    </div>

    <!-- Tab Importazione -->
    <div x-data="{ activeTab: 'csv' }" class="space-y-6">
        <!-- Navigazione Tab -->
        <div class="flex border-b border-slate-200 dark:border-slate-700">
            <button @click="activeTab = 'csv'"
                    :class="activeTab === 'csv' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-3 border-b-2 font-medium text-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Carica CSV
            </button>
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

        <!-- Tab Carica CSV -->
        <div x-show="activeTab === 'csv'" x-transition class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <form action="<?= url($apiRoutes['csv']) ?>" method="POST" enctype="multipart/form-data" id="csvForm">
                <?= csrf_field() ?>
                <input type="hidden" name="import_type" value="csv">

                <div class="p-6 space-y-6">
                    <!-- Zona Upload File -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            File CSV <span class="text-red-500">*</span>
                        </label>
                        <div class="import-upload-zone" id="csvDropZone">
                            <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" class="hidden" required>
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        <span class="text-primary-600 dark:text-primary-400">Clicca per caricare</span> o trascina qui
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">File CSV o TXT fino a 10MB</p>
                                </div>
                            </div>
                            <div id="csvFileInfo" class="hidden mt-4 p-3 bg-slate-50 dark:bg-slate-900 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span id="csvFileName" class="text-sm font-medium text-slate-900 dark:text-white"></span>
                                    <button type="button" onclick="clearCsvFile()" class="ml-auto text-slate-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opzioni CSV -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Prima riga intestazione -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="has_header" value="1" id="hasHeader" checked
                                   class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                            <label for="hasHeader" class="text-sm text-slate-700 dark:text-slate-300">
                                La prima riga è l'intestazione
                            </label>
                        </div>

                        <!-- Delimitatore -->
                        <div>
                            <label for="delimiter" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Delimitatore
                            </label>
                            <select name="delimiter" id="delimiter"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                                <option value=",">Virgola (,)</option>
                                <option value=";">Punto e virgola (;)</option>
                                <option value="&#9;">Tab</option>
                                <option value="auto">Auto-rileva</option>
                            </select>
                        </div>
                    </div>

                    <!-- Mappatura Colonne -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="urlColumn" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Colonna URL <span class="text-red-500">*</span>
                            </label>
                            <select name="url_column" id="urlColumn"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                                <option value="0">Colonna 1</option>
                                <option value="1">Colonna 2</option>
                                <option value="2">Colonna 3</option>
                                <option value="3">Colonna 4</option>
                                <option value="4">Colonna 5</option>
                            </select>
                        </div>
                        <?php if ($showKeyword): ?>
                        <div>
                            <label for="keywordColumn" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Colonna Keyword (opzionale)
                            </label>
                            <select name="keyword_column" id="keywordColumn"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                                <option value="">Nessuna</option>
                                <option value="0">Colonna 1</option>
                                <option value="1">Colonna 2</option>
                                <option value="2">Colonna 3</option>
                                <option value="3">Colonna 4</option>
                                <option value="4">Colonna 5</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Anteprima -->
                    <div id="csvPreview" class="hidden">
                        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Anteprima (prime 5 righe)</h4>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-sm" id="previewTable">
                                <thead class="bg-slate-50 dark:bg-slate-900">
                                    <tr id="previewHeader"></tr>
                                </thead>
                                <tbody id="previewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition shadow-lg shadow-primary-600/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importa da CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab Sitemap -->
        <div x-show="activeTab === 'sitemap'" x-cloak x-transition
             x-data="importSitemapHandler()"
             class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <form @submit.prevent="importSelected()">
                <input type="hidden" name="import_type" value="sitemap">

                <div class="p-6 space-y-6">
                    <!-- Sezione Auto-rileva -->
                    <div class="p-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-xl border border-primary-200 dark:border-primary-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-primary-900 dark:text-primary-200">Auto-rileva da robots.txt</h4>
                                <p class="text-sm text-primary-700 dark:text-primary-300 mt-1">
                                    Scansiona <code class="px-1.5 py-0.5 bg-primary-100 dark:bg-primary-800 rounded text-xs"><?= e($project['base_url']) ?>/robots.txt</code> per le sitemap
                                </p>
                            </div>
                            <button type="button"
                                    @click="discoverSitemaps()"
                                    :disabled="isDiscovering"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg shadow-primary-600/25">
                                <svg class="w-4 h-4" :class="isDiscovering && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span x-text="isDiscovering ? 'Ricerca...' : 'Trova Sitemap'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Lista Sitemap Trovate -->
                    <div x-show="discoveredSitemaps.length > 0" x-transition class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Sitemap trovate (<span x-text="discoveredSitemaps.length"></span>)
                            </h4>
                            <div class="flex gap-2">
                                <button type="button" @click="selectAll()" class="text-xs text-primary-600 hover:text-primary-700">Seleziona tutte</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="selectNone()" class="text-xs text-slate-500 hover:text-slate-700">Deseleziona tutte</button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <template x-for="(sitemap, index) in discoveredSitemaps" :key="index">
                                <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <input type="checkbox"
                                           :value="sitemap.url"
                                           x-model="selectedSitemaps"
                                           class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="sitemap.url"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            Fonte: <span x-text="sitemap.source === 'robots.txt' ? 'robots.txt' : (sitemap.source === 'manual' ? 'Manuale' : 'Percorso comune')"></span>
                                            <template x-if="sitemap.url_count !== null && sitemap.url_count >= 0">
                                                <span class="ml-2 text-green-600 dark:text-green-400">
                                                    (<span x-text="sitemap.url_count.toLocaleString()"></span> URL)
                                                </span>
                                            </template>
                                        </p>
                                    </div>
                                    <button type="button"
                                            @click.prevent="previewSingleSitemap(sitemap.url)"
                                            class="p-2 text-slate-400 hover:text-primary-600 transition"
                                            title="Anteprima">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </label>
                            </template>
                        </div>

                        <p x-show="selectedSitemaps.length > 0" class="text-sm text-slate-600 dark:text-slate-400">
                            <span x-text="selectedSitemaps.length"></span> sitemap selezionate
                        </p>
                    </div>

                    <!-- Messaggio Nessuna Sitemap Trovata -->
                    <div x-show="discoveryDone && discoveredSitemaps.length === 0" x-transition
                         class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-900 dark:text-amber-200">Nessuna sitemap trovata in robots.txt</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300">Inserisci l'URL della sitemap manualmente qui sotto</p>
                            </div>
                        </div>
                    </div>

                    <!-- Divisore -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 bg-white dark:bg-slate-800 text-slate-500">Oppure inserisci manualmente</span>
                        </div>
                    </div>

                    <!-- URL Sitemap Manuale -->
                    <div>
                        <label for="sitemapUrl" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            URL Sitemap
                        </label>
                        <div class="flex gap-3">
                            <div class="flex-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                    </svg>
                                </div>
                                <input type="url" x-model="manualSitemapUrl" id="sitemapUrl"
                                       placeholder="https://esempio.it/sitemap.xml"
                                       class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                            </div>
                            <button type="button" @click="addManualSitemap()"
                                    :disabled="!manualSitemapUrl"
                                    class="px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Filtro URL -->
                    <div>
                        <label for="urlFilter" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Filtro URL (opzionale)
                        </label>
                        <input type="text" x-model="urlFilter" id="urlFilter"
                               placeholder="es. /prodotti/* oppure /blog/*"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Usa * come carattere jolly. Solo gli URL che corrispondono a questo pattern verranno importati.
                        </p>
                    </div>

                    <!-- Massimo URL -->
                    <div>
                        <label for="maxUrls" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Massimo URL
                        </label>
                        <input type="number" x-model="maxUrls" id="maxUrls" min="1" max="<?= $maxUrls ?>"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    </div>

                    <!-- Anteprima Sitemap -->
                    <div x-show="previewUrls.length > 0" x-transition>
                        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Anteprima (<span x-text="previewTotal.toLocaleString()"></span> URL trovati)
                            <template x-if="previewDuplicates > 0">
                                <span class="text-amber-600 dark:text-amber-400 ml-2">
                                    (<span x-text="previewDuplicates"></span> duplicati rimossi)
                                </span>
                            </template>
                        </h4>
                        <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                            <ul class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                                <template x-for="url in previewUrls.slice(0, 50)" :key="url">
                                    <li class="px-4 py-2 text-slate-600 dark:text-slate-400 truncate" x-text="url"></li>
                                </template>
                                <template x-if="previewUrls.length > 50">
                                    <li class="px-4 py-2 text-slate-500 dark:text-slate-500 italic">
                                        ... e altri <span x-text="(previewUrls.length - 50).toLocaleString()"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                    <button type="button" @click="previewSelected()"
                            :disabled="selectedSitemaps.length === 0 && !manualSitemapUrl"
                            class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition">
                        Anteprima
                    </button>
                    <button type="submit"
                            :disabled="isImporting || (selectedSitemaps.length === 0 && !manualSitemapUrl)"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 disabled:opacity-50 transition shadow-lg shadow-primary-600/25">
                        <svg class="w-4 h-4" :class="isImporting && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span x-text="isImporting ? 'Importazione...' : 'Importa da Sitemap'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab Inserimento Manuale -->
        <div x-show="activeTab === 'manual'" x-cloak x-transition class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <form action="<?= url($apiRoutes['manual']) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="import_type" value="manual">

                <div class="p-6 space-y-6">
                    <div>
                        <label for="urlsText" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            URL <span class="text-red-500">*</span>
                        </label>
                        <textarea name="urls_text" id="urlsText" rows="12" required
                                  placeholder="Inserisci un URL per riga. Puoi aggiungere una keyword dopo il tab o la virgola:

/pagina-1
/pagina-2, keyword target
https://esempio.it/pagina-3	altra keyword

Le righe che iniziano con # vengono ignorate."
                                  class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition font-mono text-sm"></textarea>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Un URL per riga. Usa tab o virgola per separare la keyword.</span>
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
    </div>

    <!-- Info Aiuto -->
    <div class="mt-6 p-5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">Suggerimenti per l'importazione</h4>
                <ul class="space-y-1 list-disc list-inside">
                    <li>Gli URL possono essere relativi (es. /prodotti/articolo-1) o assoluti (URL completo)</li>
                    <li>Gli URL duplicati vengono saltati automaticamente</li>
                    <li>Massimo <?= number_format($maxUrls) ?> URL per importazione</li>
                    <?php if ($showKeyword): ?>
                    <li>Le keyword aiutano l'analizzatore AI a capire il contesto della pagina</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Configurazione da PHP
const importConfig = {
    projectId: <?= (int) $projectId ?>,
    baseUrl: '<?= e($project['base_url'] ?? '') ?>',
    maxUrls: <?= (int) $maxUrls ?>,
    apiRoutes: {
        discover: '<?= url($apiRoutes['discover']) ?>',
        sitemap: '<?= url($apiRoutes['sitemap']) ?>',
    },
    redirectUrl: '<?= url($importUrl) ?>'
};

// Componente Alpine Importazione Sitemap
function importSitemapHandler() {
    return {
        isDiscovering: false,
        discoveryDone: false,
        discoveredSitemaps: [],
        selectedSitemaps: [],
        manualSitemapUrl: '',
        urlFilter: '',
        maxUrls: importConfig.maxUrls,
        previewUrls: [],
        previewTotal: 0,
        previewDuplicates: 0,
        isImporting: false,

        async discoverSitemaps() {
            this.isDiscovering = true;
            this.discoveryDone = false;

            try {
                const response = await fetch(importConfig.apiRoutes.discover, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ project_id: importConfig.projectId, get_counts: true })
                });

                const data = await response.json();

                if (data.success) {
                    this.discoveredSitemaps = data.sitemaps || [];
                    this.selectedSitemaps = this.discoveredSitemaps.map(s => s.url);

                    if (this.discoveredSitemaps.length > 0) {
                        showImportToast(`Trovate ${this.discoveredSitemaps.length} sitemap`, 'success');
                    } else {
                        showImportToast('Nessuna sitemap trovata in robots.txt', 'warning');
                    }
                } else {
                    showImportToast(data.error || 'Errore nella ricerca delle sitemap', 'error');
                }
            } catch (error) {
                console.error('Errore ricerca:', error);
                showImportToast('Errore nella ricerca delle sitemap', 'error');
            } finally {
                this.isDiscovering = false;
                this.discoveryDone = true;
            }
        },

        selectAll() {
            this.selectedSitemaps = this.discoveredSitemaps.map(s => s.url);
        },

        selectNone() {
            this.selectedSitemaps = [];
        },

        addManualSitemap() {
            if (!this.manualSitemapUrl) return;

            if (!this.discoveredSitemaps.find(s => s.url === this.manualSitemapUrl)) {
                this.discoveredSitemaps.push({
                    url: this.manualSitemapUrl,
                    source: 'manual',
                    url_count: null
                });
            }

            if (!this.selectedSitemaps.includes(this.manualSitemapUrl)) {
                this.selectedSitemaps.push(this.manualSitemapUrl);
            }

            this.manualSitemapUrl = '';
            showImportToast('Sitemap aggiunta', 'success');
        },

        async previewSingleSitemap(url) {
            showImportToast('Caricamento anteprima...', 'info');

            try {
                const response = await fetch(importConfig.apiRoutes.sitemap, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        project_id: importConfig.projectId,
                        action: 'preview',
                        sitemap_url: url
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.previewUrls = data.preview_urls || [];
                    this.previewTotal = data.total_found || 0;
                    this.previewDuplicates = 0;
                    showImportToast(`Trovati ${this.previewTotal.toLocaleString()} URL`, 'success');
                } else {
                    showImportToast(data.error || 'Errore nel caricamento anteprima', 'error');
                }
            } catch (error) {
                console.error('Errore anteprima:', error);
                showImportToast('Errore nel caricamento anteprima', 'error');
            }
        },

        async previewSelected() {
            const sitemapsToPreview = this.selectedSitemaps.length > 0
                ? this.selectedSitemaps
                : (this.manualSitemapUrl ? [this.manualSitemapUrl] : []);

            if (sitemapsToPreview.length === 0) {
                showImportToast('Seleziona almeno una sitemap', 'warning');
                return;
            }

            showImportToast('Caricamento anteprima...', 'info');

            try {
                const response = await fetch(importConfig.apiRoutes.sitemap, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        project_id: importConfig.projectId,
                        action: 'preview',
                        sitemap_urls: sitemapsToPreview,
                        url_filter: this.urlFilter,
                        max_urls: this.maxUrls
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.previewUrls = data.urls || data.preview_urls || [];
                    this.previewTotal = data.total_unique || data.total_found || 0;
                    this.previewDuplicates = data.duplicates_removed || 0;
                    showImportToast(`Trovati ${this.previewTotal.toLocaleString()} URL unici`, 'success');
                } else {
                    showImportToast(data.error || 'Errore nel caricamento anteprima', 'error');
                }
            } catch (error) {
                console.error('Errore anteprima:', error);
                showImportToast('Errore nel caricamento anteprima', 'error');
            }
        },

        async importSelected() {
            const sitemapsToImport = this.selectedSitemaps.length > 0
                ? this.selectedSitemaps
                : (this.manualSitemapUrl ? [this.manualSitemapUrl] : []);

            if (sitemapsToImport.length === 0) {
                showImportToast('Seleziona almeno una sitemap', 'warning');
                return;
            }

            this.isImporting = true;

            try {
                const response = await fetch(importConfig.apiRoutes.sitemap, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        project_id: importConfig.projectId,
                        action: 'import',
                        sitemap_urls: sitemapsToImport,
                        url_filter: this.urlFilter,
                        max_urls: this.maxUrls
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showImportToast(`${data.imported.toLocaleString()} URL importati!`, 'success');
                    setTimeout(() => {
                        window.location.href = importConfig.redirectUrl;
                    }, 1500);
                } else {
                    showImportToast(data.error || 'Importazione fallita', 'error');
                }
            } catch (error) {
                console.error('Errore importazione:', error);
                showImportToast('Errore durante l\'importazione', 'error');
            } finally {
                this.isImporting = false;
            }
        }
    };
}

// Gestione File CSV
const csvDropZone = document.getElementById('csvDropZone');
const csvFile = document.getElementById('csvFile');
const csvFileInfo = document.getElementById('csvFileInfo');
const csvFileName = document.getElementById('csvFileName');

csvDropZone?.addEventListener('click', () => csvFile.click());

csvFile?.addEventListener('change', function() {
    if (this.files.length > 0) {
        csvFileName.textContent = this.files[0].name;
        csvFileInfo.classList.remove('hidden');
    }
});

function clearCsvFile() {
    csvFile.value = '';
    csvFileInfo.classList.add('hidden');
    document.getElementById('csvPreview')?.classList.add('hidden');
}

// Conteggio URL Manuali
const urlsText = document.getElementById('urlsText');
const urlCount = document.getElementById('urlCount');

urlsText?.addEventListener('input', function() {
    const lines = this.value.split('\n').filter(line => {
        line = line.trim();
        return line && !line.startsWith('#');
    });
    urlCount.textContent = `${lines.length} URL`;
});

// Drag and drop
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    csvDropZone?.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    csvDropZone?.addEventListener(eventName, () => csvDropZone.classList.add('dragover'));
});

['dragleave', 'drop'].forEach(eventName => {
    csvDropZone?.addEventListener(eventName, () => csvDropZone.classList.remove('dragover'));
});

csvDropZone?.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length) {
        csvFile.files = files;
        csvFile.dispatchEvent(new Event('change'));
    }
});

// Helper notifica toast
function showImportToast(message, type) {
    if (typeof app !== 'undefined' && app.showToast) {
        app.showToast(message, type);
    } else {
        console.log(`[${type}] ${message}`);
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-white text-sm font-medium ${
            type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
            type === 'warning' ? 'bg-amber-600' : 'bg-blue-600'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
}
</script>

<style>
.import-upload-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.import-upload-zone:hover {
    border-color: #006e96;
    background-color: #f8fafc;
}
.dark .import-upload-zone {
    border-color: #475569;
}
.dark .import-upload-zone:hover {
    border-color: #33a7c7;
    background-color: rgba(0, 110, 150, 0.1);
}
.import-upload-zone.dragover {
    border-color: #006e96;
    background-color: #e6f4f8;
}
.dark .import-upload-zone.dragover {
    border-color: #33a7c7;
    background-color: rgba(0, 110, 150, 0.2);
}
</style>
