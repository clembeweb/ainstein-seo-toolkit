<?php
/**
 * Import URLs Page - SEO Audit
 * Solo importazione manuale e CSV
 * (Spider integrato in dashboard crawl-control.php)
 */

$projectId = $project['id'];
$baseUrl = $project['base_url'] ?? '';
?>

<div class="max-w-4xl">
    <!-- Header -->
    <div class="mb-8">
        <a href="<?= url("/seo-audit/project/{$projectId}/dashboard") ?>" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 mb-4 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Torna alla Dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Importa URL Manualmente</h1>
        <p class="mt-1 text-slate-500 dark:text-slate-400">Aggiungi URL specifici a <?= e($project['name']) ?> per l'audit SEO</p>
    </div>

    <!-- 2 Tabs: Manual, CSV -->
    <div x-data="{ activeTab: 'manual' }" class="space-y-6">
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
        </div>

        <!-- ==================== TAB MANUALE ==================== -->
        <div x-show="activeTab === 'manual'" x-cloak x-transition
             class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
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
             class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
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
                    <p>Inserisci manualmente o carica un file CSV</p>
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
