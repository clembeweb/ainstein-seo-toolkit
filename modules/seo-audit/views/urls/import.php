<?php
/**
 * Import URLs Page - SEO Audit
 * 3 tabs: Manuale, CSV, WordPress (se connettore attivo)
 * (Spider integrato in dashboard crawl-control.php)
 */

$projectId = $project['id'];
$baseUrl = $project['base_url'] ?? '';
$currentPage = 'import';
$hasWp = !empty($wpConnector);
$defaultTab = $hasWp ? 'wordpress' : 'manual';
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-4xl">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Importa URL Manualmente</h1>
        <p class="mt-1 text-slate-500 dark:text-slate-400">Aggiungi URL specifici a <?= e($project['name']) ?> per l'audit SEO</p>
    </div>

    <!-- Tabs: Manual, CSV, WordPress -->
    <div x-data="{ activeTab: '<?= $defaultTab ?>' }" class="space-y-6">
        <!-- Tab Navigation -->
        <div class="flex border-b border-slate-200 dark:border-slate-700">
            <button @click="activeTab = 'manual'"
                    :class="activeTab === 'manual' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-3 border-b-2 font-medium text-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Inserimento Manuale
            </button>
            <button @click="activeTab = 'csv'"
                    :class="activeTab === 'csv' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-3 border-b-2 font-medium text-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Importa da CSV
            </button>
            <?php if ($hasWp): ?>
            <button @click="activeTab = 'wordpress'"
                    :class="activeTab === 'wordpress' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="flex items-center gap-2 px-6 py-3 border-b-2 font-medium text-sm transition">
                <!-- Heroicons: globe-alt -->
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                WordPress
            </button>
            <?php endif; ?>
        </div>

        <!-- ==================== TAB MANUALE ==================== -->
        <div x-show="activeTab === 'manual'" x-cloak x-transition
             class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <form action="<?= url("/seo-audit/project/{$projectId}/urls/store") ?>" method="POST" id="manualForm">
                <?= csrf_field() ?>
                <input type="hidden" name="import_type" value="manual">
                <div class="p-6 space-y-6">
                    <!-- Info box -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Inserisci gli URL che vuoi analizzare. Puoi usare URL completi o relativi (verranno convertiti usando <strong><?= e($baseUrl) ?></strong>).
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Lista URL <span class="text-red-500">*</span>
                        </label>
                        <textarea name="urls_text" rows="12" required
                                  placeholder="Inserisci un URL per riga:

/chi-siamo
/servizi
/contatti
https://esempio.it/pagina-specifica

# Le righe che iniziano con # vengono ignorate"
                                  class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Un URL per riga. Supporta URL assoluti (https://...) e relativi (/pagina).
                        </p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-sm text-slate-500">Gli URL verranno aggiunti alla coda di scansione</span>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-600/25 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Importa URL
                    </button>
                </div>
            </form>
        </div>

        <!-- ==================== TAB CSV ==================== -->
        <div x-show="activeTab === 'csv'" x-cloak x-transition
             class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <form action="<?= url("/seo-audit/project/{$projectId}/urls/store") ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="import_type" value="csv">
                <div class="p-6 space-y-6">
                    <!-- Info box -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Carica un file CSV o TXT contenente la lista degli URL da analizzare.
                        </p>
                    </div>

                    <!-- File upload -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            File CSV <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer border-slate-300 dark:border-slate-600 hover:border-primary-400 dark:hover:border-primary-500 bg-slate-50 dark:bg-slate-900/30 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">
                                        <span class="font-semibold">Clicca per caricare</span> o trascina qui
                                    </p>
                                    <p class="text-xs text-slate-400">CSV o TXT (max 10MB)</p>
                                </div>
                                <input type="file" name="csv_file" accept=".csv,.txt" required class="hidden">
                            </label>
                        </div>
                    </div>

                    <!-- Config options -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Colonna URL
                            </label>
                            <select name="url_column" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="0">Colonna 1 (A)</option>
                                <option value="1">Colonna 2 (B)</option>
                                <option value="2">Colonna 3 (C)</option>
                                <option value="auto">Auto-rileva</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Delimitatore
                            </label>
                            <select name="delimiter" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="auto">Auto-rileva</option>
                                <option value=",">Virgola (,)</option>
                                <option value=";">Punto e virgola (;)</option>
                                <option value="\t">Tab</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="has_header" value="1" id="hasHeader" checked
                               class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <label for="hasHeader" class="text-sm text-slate-700 dark:text-slate-300">
                            La prima riga contiene l'intestazione (verra ignorata)
                        </label>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-sm text-slate-500">Gli URL verranno aggiunti alla coda di scansione</span>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-600/25 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importa da CSV
                    </button>
                </div>
            </form>
        </div>

        <?php if ($hasWp): ?>
        <!-- ==================== TAB WORDPRESS ==================== -->
        <?php
            $wpConfig = json_decode($wpConnector['config'] ?? '{}', true);
            $wpSiteUrl = $wpConfig['url'] ?? '';
            $wpSeoPlugin = $wpConnector['seo_plugin'] ?? 'Non rilevato';
            $wpLastTest = $wpConnector['last_test_at'] ?? null;
            $wpTestStatus = $wpConnector['last_test_status'] ?? null;
        ?>
        <div x-show="activeTab === 'wordpress'" x-cloak x-transition
             x-data="wpImport()"
             class="space-y-6">

            <!-- Connector info + Config (hidden during import) -->
            <div x-show="!importing && !completed" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6 space-y-6">
                    <!-- Info box -->
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                Importa le pagine dal tuo WordPress connesso. L'analisi viene effettuata direttamente tramite i dati del CMS, senza scraping.
                            </p>
                        </div>
                    </div>

                    <!-- Connector details -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-700">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <!-- Heroicons: link -->
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Connettore WordPress
                        </h4>
                        <div class="grid sm:grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Nome:</span>
                                <span class="ml-2 text-slate-900 dark:text-white font-medium"><?= e($wpConnector['name']) ?></span>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">URL sito:</span>
                                <a href="<?= e($wpSiteUrl) ?>" target="_blank" class="ml-2 text-emerald-600 dark:text-emerald-400 hover:underline"><?= e($wpSiteUrl) ?></a>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Plugin SEO:</span>
                                <span class="ml-2 text-slate-900 dark:text-white"><?= e($wpSeoPlugin) ?></span>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Ultimo test:</span>
                                <span class="ml-2 text-slate-900 dark:text-white">
                                    <?php if ($wpLastTest): ?>
                                        <?= date('d/m/Y H:i', strtotime($wpLastTest)) ?>
                                        <?php if ($wpTestStatus === 'success'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 ml-1">OK</span>
                                        <?php elseif ($wpTestStatus === 'error'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 ml-1">Errore</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Mai testato
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Content types to import -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Tipi di contenuto da importare</h4>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="types.post"
                                       class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Post</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 ml-2">Articoli del blog</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="types.page"
                                       class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Pagine</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 ml-2">Pagine statiche del sito</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="types.product"
                                       class="rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Prodotti</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 ml-2">Prodotti WooCommerce (se installato)</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Warning: no type selected -->
                    <div x-show="!types.post && !types.page && !types.product"
                         class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <p class="text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2">
                            <!-- Heroicons: exclamation-triangle -->
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            Seleziona almeno un tipo di contenuto.
                        </p>
                    </div>
                </div>

                <!-- Footer with start button -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">I dati esistenti verranno sostituiti con quelli importati</span>
                    <button @click="startImport()"
                            :disabled="!types.post && !types.page && !types.product"
                            :class="(!types.post && !types.page && !types.product) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-emerald-700 shadow-lg shadow-emerald-600/25'"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-xl transition">
                        <!-- Heroicons: arrow-down-tray -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Importa e Analizza
                    </button>
                </div>
            </div>

            <!-- ==================== PROGRESS SECTION ==================== -->
            <div x-show="importing || completed || cancelled || errorMsg" x-cloak
                 class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6 space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <template x-if="importing && !completed && !cancelled">
                                <span class="flex items-center gap-2">
                                    <!-- Heroicons: arrow-path (spinning) -->
                                    <svg class="w-5 h-5 text-emerald-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Importazione in corso...
                                </span>
                            </template>
                            <template x-if="completed">
                                <span class="flex items-center gap-2">
                                    <!-- Heroicons: check-circle -->
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Importazione completata!
                                </span>
                            </template>
                            <template x-if="cancelled">
                                <span class="flex items-center gap-2">
                                    <!-- Heroicons: x-circle -->
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Importazione annullata
                                </span>
                            </template>
                            <template x-if="errorMsg">
                                <span class="flex items-center gap-2">
                                    <!-- Heroicons: exclamation-circle -->
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-red-600 dark:text-red-400" x-text="errorMsg"></span>
                                </span>
                            </template>
                        </h3>
                    </div>

                    <!-- Progress bar -->
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-slate-600 dark:text-slate-400" x-text="progress.completed + ' di ' + progress.total + ' pagine analizzate'"></span>
                            <span class="font-medium text-slate-900 dark:text-white" x-text="progress.percent + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full transition-all duration-500 ease-out"
                                 :class="completed ? 'bg-emerald-500' : cancelled ? 'bg-amber-500' : 'bg-emerald-500'"
                                 :style="'width: ' + progress.percent + '%'"></div>
                        </div>
                    </div>

                    <!-- Current URL -->
                    <div x-show="importing && !completed && !cancelled && progress.currentUrl" x-cloak
                         class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <svg class="w-4 h-4 flex-shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span class="truncate" x-text="progress.currentUrl"></span>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-slate-50 dark:bg-slate-900/30 rounded-xl">
                            <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="progress.completed"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Analizzate</p>
                        </div>
                        <div class="text-center p-3 bg-slate-50 dark:bg-slate-900/30 rounded-xl">
                            <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="progress.total"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Totali</p>
                        </div>
                        <div class="text-center p-3 bg-slate-50 dark:bg-slate-900/30 rounded-xl">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" x-text="progress.issues"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Problemi</p>
                        </div>
                    </div>
                </div>

                <!-- Footer: cancel or redirect -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <template x-if="importing && !completed && !cancelled">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-sm text-slate-500 dark:text-slate-400">L'importazione continua anche se chiudi questa pagina</span>
                            <button @click="cancelImport()"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <!-- Heroicons: x-mark -->
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Annulla
                            </button>
                        </div>
                    </template>
                    <template x-if="completed">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-sm text-emerald-600 dark:text-emerald-400">Reindirizzamento alla dashboard tra pochi secondi...</span>
                            <a href="<?= url('/seo-audit/project/' . $projectId . '/dashboard') ?>"
                               class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-600/25 transition">
                                <!-- Heroicons: arrow-right -->
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                Vai alla Dashboard
                            </a>
                        </div>
                    </template>
                    <template x-if="cancelled">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-sm text-slate-500 dark:text-slate-400">Le pagine gia analizzate sono state salvate</span>
                            <a href="<?= url('/seo-audit/project/' . $projectId . '/dashboard') ?>"
                               class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-600 text-white text-sm font-medium rounded-xl hover:bg-slate-700 transition">
                                Vai alla Dashboard
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <script>
        function wpImport() {
            return {
                types: { post: true, page: true, product: false },
                importing: false,
                completed: false,
                cancelled: false,
                errorMsg: '',
                progress: {
                    completed: 0,
                    total: 0,
                    percent: 0,
                    currentUrl: '',
                    issues: 0,
                },
                eventSource: null,
                polling: false,
                jobId: null,
                pollTimer: null,

                // URLs
                streamUrl: '<?= url('/seo-audit/project/' . $projectId . '/import/wordpress/stream') ?>',
                jobStatusUrl: '<?= url('/seo-audit/project/' . $projectId . '/crawl/job-status') ?>',
                cancelUrl: '<?= url('/seo-audit/project/' . $projectId . '/crawl/cancel-job') ?>',
                dashboardUrl: '<?= url('/seo-audit/project/' . $projectId . '/dashboard') ?>',
                csrfToken: '<?= csrf_token() ?>',

                startImport() {
                    // Build types string from checkboxes
                    const selectedTypes = [];
                    if (this.types.post) selectedTypes.push('post');
                    if (this.types.page) selectedTypes.push('page');
                    if (this.types.product) selectedTypes.push('product');

                    if (selectedTypes.length === 0) return;

                    this.importing = true;
                    this.completed = false;
                    this.cancelled = false;
                    this.errorMsg = '';
                    this.progress = { completed: 0, total: 0, percent: 0, currentUrl: '', issues: 0 };

                    const sseUrl = this.streamUrl + '?types=' + selectedTypes.join(',');
                    this.eventSource = new EventSource(sseUrl);

                    // Started event
                    this.eventSource.addEventListener('started', (e) => {
                        try {
                            const data = JSON.parse(e.data);
                            this.progress.total = data.total || 0;
                            this.jobId = data.job_id || null;
                        } catch (_) {}
                    });

                    // Page analyzed event
                    this.eventSource.addEventListener('page_analyzed', (e) => {
                        try {
                            const data = JSON.parse(e.data);
                            this.progress.completed = data.completed || 0;
                            this.progress.total = data.total || this.progress.total;
                            this.progress.percent = data.percent || 0;
                            this.progress.currentUrl = data.url || '';
                            this.progress.issues += (data.issues || 0);
                        } catch (_) {}
                    });

                    // Page error event (non-fatal, continue)
                    this.eventSource.addEventListener('page_error', (e) => {
                        // Increment completed to keep progress moving
                        this.progress.completed++;
                        if (this.progress.total > 0) {
                            this.progress.percent = Math.round((this.progress.completed / this.progress.total) * 100 * 10) / 10;
                        }
                    });

                    // Completed event
                    this.eventSource.addEventListener('completed', (e) => {
                        this.eventSource.close();
                        this.eventSource = null;
                        this.importing = false;
                        this.completed = true;
                        this.progress.percent = 100;

                        // Redirect to dashboard after 2 seconds
                        setTimeout(() => {
                            window.location.href = this.dashboardUrl;
                        }, 2000);
                    });

                    // Cancelled event
                    this.eventSource.addEventListener('cancelled', (e) => {
                        this.eventSource.close();
                        this.eventSource = null;
                        this.importing = false;
                        this.cancelled = true;
                    });

                    // Server-sent error event (custom from backend)
                    this.eventSource.addEventListener('error', (e) => {
                        try {
                            const data = JSON.parse(e.data);
                            this.errorMsg = data.message || 'Errore durante l\'importazione';
                            this.eventSource.close();
                            this.eventSource = null;
                            this.importing = false;
                        } catch (_) {
                            // Native SSE error (no data) - handled by onerror below
                        }
                    });

                    // Native SSE connection error — fallback to polling
                    this.eventSource.onerror = () => {
                        if (this.completed || this.cancelled) return;
                        this.eventSource.close();
                        this.eventSource = null;
                        if (this.jobId) {
                            this.startPolling();
                        }
                    };
                },

                async cancelImport() {
                    if (!this.jobId) return;

                    try {
                        const formData = new FormData();
                        formData.append('_csrf_token', this.csrfToken);
                        formData.append('job_id', this.jobId);

                        await fetch(this.cancelUrl, {
                            method: 'POST',
                            body: formData,
                        });
                    } catch (_) {}
                    // The cancelled event from SSE/polling will update state
                },

                startPolling() {
                    if (this.polling) return;
                    this.polling = true;
                    this._poll();
                },

                async _poll() {
                    if (!this.polling || this.completed || this.cancelled) return;

                    try {
                        const resp = await fetch(this.jobStatusUrl + '?job_id=' + this.jobId);
                        if (!resp.ok) {
                            // Server error — retry
                            this.pollTimer = setTimeout(() => this._poll(), 3000);
                            return;
                        }
                        const data = await resp.json();
                        const job = data.job || {};

                        // Update progress
                        this.progress.completed = job.items_completed || this.progress.completed;
                        this.progress.total = job.items_total || this.progress.total;
                        if (this.progress.total > 0) {
                            this.progress.percent = Math.round((this.progress.completed / this.progress.total) * 100 * 10) / 10;
                        }
                        this.progress.currentUrl = job.current_item || this.progress.currentUrl;

                        if (job.status === 'completed') {
                            this.polling = false;
                            this.importing = false;
                            this.completed = true;
                            this.progress.percent = 100;
                            setTimeout(() => {
                                window.location.href = this.dashboardUrl;
                            }, 2000);
                            return;
                        }

                        if (job.status === 'cancelled') {
                            this.polling = false;
                            this.importing = false;
                            this.cancelled = true;
                            return;
                        }

                        if (job.status === 'error') {
                            this.polling = false;
                            this.importing = false;
                            this.errorMsg = job.error_message || 'Errore durante l\'importazione';
                            return;
                        }

                        // Still running — poll again
                        this.pollTimer = setTimeout(() => this._poll(), 3000);

                    } catch (_) {
                        // Network error — retry
                        this.pollTimer = setTimeout(() => this._poll(), 5000);
                    }
                },

                destroy() {
                    if (this.eventSource) {
                        this.eventSource.close();
                    }
                    if (this.pollTimer) {
                        clearTimeout(this.pollTimer);
                    }
                    this.polling = false;
                }
            };
        }
        </script>
        <?php endif; ?>
    </div>

    <!-- Help -->
    <div class="mt-6 p-5 bg-slate-50 dark:bg-slate-900/30 rounded-xl border border-slate-200 dark:border-slate-700">
        <h4 class="font-semibold text-slate-900 dark:text-white mb-3">Come funziona</h4>
        <div class="grid md:grid-cols-2 gap-4 text-sm text-slate-600 dark:text-slate-400">
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <span class="text-primary-600 dark:text-primary-400 font-bold">1</span>
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Aggiungi URL</p>
                    <p>Inserisci manualmente, carica un CSV<?php if ($hasWp): ?> o importa da WordPress<?php endif; ?></p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <span class="text-primary-600 dark:text-primary-400 font-bold">2</span>
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Avvia scansione</p>
                    <p>Torna alla dashboard e avvia lo spider</p>
                </div>
            </div>
        </div>
        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            <strong>Suggerimento:</strong> Per la scoperta automatica degli URL, usa lo Spider dalla dashboard. Questa pagina e per importare URL specifici.
        </p>
    </div>
</div>
