<?php $currentPage = 'context'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Info costi -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-300">
                <p class="font-medium">Estrazione contesto automatica</p>
                <p class="mt-1">Per ogni Ad Group con URL, l'AI analizzer&agrave; la landing page ed estrarra automaticamente il contesto business. Costo: <strong>3 crediti per Ad Group</strong>.</p>
            </div>
        </div>
    </div>

    <!-- Lista Ad Groups -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6"
         x-data="landingUrlsManager()">

        <div class="space-y-4">
            <?php foreach ($adGroups as $adGroup): ?>
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4"
                 x-data="{
                     url: '<?= e($adGroup['landing_url'] ?? '') ?>',
                     context: `<?= addslashes($adGroup['extracted_context'] ?? '') ?>`,
                     loading: false,
                     saved: false,
                     error: null
                 }">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-medium text-slate-900 dark:text-white"><?= e($adGroup['name']) ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= number_format($adGroup['terms_count']) ?> termini</p>
                    </div>
                    <template x-if="context && context.length > 0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Contesto estratto
                        </span>
                    </template>
                </div>

                <div class="flex gap-2">
                    <input
                        type="url"
                        x-model="url"
                        placeholder="https://esempio.com/landing-page"
                        class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        @blur="saveUrl(<?= $adGroup['id'] ?>, url)"
                    >
                    <button
                        type="button"
                        class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 flex items-center transition-colors"
                        :disabled="!url || loading"
                        @click="extractContext(<?= $adGroup['id'] ?>)"
                    >
                        <template x-if="loading">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <svg x-show="!loading" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span x-text="loading ? 'Estrazione...' : 'Estrai'"></span>
                    </button>
                </div>

                <!-- Contesto estratto -->
                <template x-if="context && context.length > 0">
                    <div class="mt-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Contesto estratto:</p>
                        <p class="text-sm text-slate-700 dark:text-slate-300" x-text="context.length > 200 ? context.substring(0, 200) + '...' : context"></p>
                    </div>
                </template>

                <!-- Errore -->
                <template x-if="error">
                    <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                    </div>
                </template>

                <!-- Salvato -->
                <template x-if="saved && !error">
                    <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">URL salvato</p>
                </template>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Azioni -->
        <div class="flex justify-between items-center pt-6 mt-6 border-t border-slate-200 dark:border-slate-700">
            <div class="text-sm text-slate-600 dark:text-slate-400">
                <span class="font-medium">Crediti disponibili:</span>
                <?= number_format($userCredits) ?>
            </div>

            <div class="flex gap-3">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/context') ?>"
                   class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Salta (contesto manuale)
                </a>

                <button
                    type="button"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 flex items-center transition-colors"
                    :disabled="extractingAll"
                    @click="extractAllContexts()"
                >
                    <template x-if="extractingAll">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="extractingAll ? 'Estrazione in corso...' : 'Estrai Tutti'"></span>
                </button>

                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/context') ?>"
                   class="px-6 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors inline-flex items-center">
                    Continua
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Help -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-6">
        <h3 class="font-medium text-slate-900 dark:text-white mb-3">Come funziona</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li>Inserisci l'URL della <strong>landing page</strong> per ogni Ad Group</li>
            <li>Clicca <strong>Estrai</strong> per analizzare la pagina con AI</li>
            <li>L'AI generera automaticamente il <strong>contesto business</strong></li>
            <li>Continua allo step successivo per <strong>revisionare e avviare l'analisi</strong></li>
        </ol>
        <p class="mt-4 text-xs text-slate-500 dark:text-slate-500">
            Puoi anche saltare questo step e inserire il contesto manualmente nella pagina successiva.
        </p>
    </div>
</div>

<script>
function landingUrlsManager() {
    return {
        extractingAll: false,

        async saveUrl(adGroupId, url) {
            console.log('Saving URL for Ad Group', adGroupId, url);

            const container = event.target.closest('[x-data]');
            const data = Alpine.$data(container);

            try {
                const formData = new FormData();
                formData.append('landing_url', url);
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url('/ads-analyzer/projects/' . $project['id']) ?>/ad-groups/${adGroupId}/landing-url`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Save result:', result);

                if (result.success) {
                    data.saved = true;
                    data.error = null;
                    setTimeout(() => data.saved = false, 2000);
                } else {
                    data.error = result.error;
                }
            } catch (err) {
                console.error('Save error:', err);
                data.error = 'Errore di connessione';
            }
        },

        async extractContext(adGroupId) {
            console.log('Extracting context for Ad Group', adGroupId);

            const container = event.target.closest('[x-data]');
            const data = Alpine.$data(container);

            data.loading = true;
            data.error = null;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url('/ads-analyzer/projects/' . $project['id']) ?>/ad-groups/${adGroupId}/extract-context`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Extract result:', result);

                if (result.success) {
                    data.context = result.context;
                } else {
                    data.error = result.error;
                }
            } catch (err) {
                console.error('Extract error:', err);
                data.error = 'Errore durante l\'estrazione';
            } finally {
                data.loading = false;
            }
        },

        async extractAllContexts() {
            console.log('Extracting all contexts');

            this.extractingAll = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch(`<?= url('/ads-analyzer/projects/' . $project['id']) ?>/extract-all-contexts`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                console.log('Extract all result:', data);

                if (data.success) {
                    // Reload page to show updated contexts
                    window.location.reload();
                } else {
                    window.ainstein.alert(data.error || 'Errore durante l\'estrazione', 'error');
                }
            } catch (err) {
                console.error('Extract all error:', err);
                window.ainstein.alert('Errore di connessione', 'error');
            } finally {
                this.extractingAll = false;
            }
        }
    };
}
</script>
