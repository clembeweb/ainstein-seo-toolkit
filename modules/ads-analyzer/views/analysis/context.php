<?php $currentPage = 'context'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Import Summary -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Riepilogo Import</h2>
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($adGroups) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Ad Groups</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['total_terms']) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Termini</p>
            </div>
            <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format(array_sum(array_column($adGroups, 'zero_ctr_count'))) ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">CTR 0%</p>
            </div>
        </div>

        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ad Groups trovati:</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($adGroups as $adGroup): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    <?= e($adGroup['name']) ?>
                    <span class="ml-1 text-slate-400">(<?= number_format($adGroup['terms_count']) ?>)</span>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php
    // Verifica se ci sono contesti estratti
    $hasExtractedContexts = false;
    $extractedContexts = [];
    foreach ($adGroups as $ag) {
        if (!empty($ag['extracted_context'])) {
            $hasExtractedContexts = true;
            $extractedContexts[$ag['name']] = $ag['extracted_context'];
        }
    }
    ?>

    <?php if ($hasExtractedContexts): ?>
    <!-- Contesti Estratti Automaticamente -->
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="font-medium text-emerald-800 dark:text-emerald-300">Contesti estratti automaticamente</p>
                <p class="text-sm text-emerald-700 dark:text-emerald-400 mt-1">I contesti seguenti sono stati estratti dalle landing page. Puoi usarli come base o modificarli.</p>

                <div class="mt-3 space-y-2">
                    <?php foreach ($extractedContexts as $name => $context): ?>
                    <details class="bg-white dark:bg-slate-800 rounded p-3 border border-emerald-200 dark:border-emerald-700">
                        <summary class="cursor-pointer font-medium text-sm text-emerald-800 dark:text-emerald-300"><?= e($name) ?></summary>
                        <p class="mt-2 text-sm text-slate-700 dark:text-slate-300"><?= e($context) ?></p>
                    </details>
                    <?php endforeach; ?>
                </div>

                <button
                    type="button"
                    class="mt-3 text-sm text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 underline"
                    onclick="useExtractedContexts()"
                >
                    Usa tutti i contesti estratti
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Link a estrazione automatica -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <p class="text-sm text-blue-800 dark:text-blue-300">
                Vuoi estrarre il contesto automaticamente dalle landing page?
                <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/landing-urls') ?>" class="font-medium underline hover:no-underline">
                    Vai all'estrazione automatica
                </a>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Context Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6"
         x-data="contextForm()"
    >
        <form @submit.prevent="startAnalysis">
            <?= csrf_field() ?>

            <!-- Saved Contexts -->
            <?php if (!empty($savedContexts)): ?>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Contesti salvati
                </label>
                <div class="flex gap-2">
                    <select
                        x-model="selectedContext"
                        @change="loadContext()"
                        class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="">-- Seleziona contesto --</option>
                        <?php foreach ($savedContexts as $ctx): ?>
                        <option value="<?= e($ctx['context']) ?>"><?= e($ctx['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <!-- Business Context -->
            <div class="mb-6">
                <label for="business_context" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Contesto Business <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="business_context"
                    x-model="context"
                    name="business_context"
                    rows="6"
                    required
                    minlength="30"
                    placeholder="Descrivi cosa vendi/promuovi, il target, cosa NON offri. Piu dettagli dai, migliore sara l'analisi.

Esempio: E-commerce di scarpe running. Vendiamo solo scarpe da corsa uomo/donna di brand premium (Nike, Asics, Brooks). NON vendiamo: scarpe da calcio, abbigliamento sportivo, accessori. Target: runner amatoriali e professionisti 25-55 anni."
                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                ><?= e($project['business_context'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Minimo 30 caratteri. <span x-text="context.length"></span> caratteri inseriti.
                </p>
            </div>

            <!-- Save Context -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="saveContext" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Salva questo contesto per riutilizzarlo</span>
                </label>
                <div x-show="saveContext" class="mt-3">
                    <input
                        type="text"
                        x-model="contextName"
                        placeholder="Nome del contesto (es: E-commerce Scarpe)"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                    >
                </div>
            </div>

            <!-- Credits Info -->
            <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-amber-800 dark:text-amber-300">
                            Crediti stimati: <span class="text-lg"><?= $estimatedCredits ?></span>
                        </p>
                        <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                            <?= count($adGroups) ?> Ad Group x <?= count($adGroups) <= 3 ? '2' : '1.5' ?> crediti
                        </p>
                        <p class="text-sm text-amber-700 dark:text-amber-400">
                            Crediti disponibili: <strong><?= number_format($userCredits) ?></strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Error -->
            <div x-show="error" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
            </div>

            <!-- Progress -->
            <div x-show="isAnalyzing" class="mb-6">
                <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400 mb-2">
                    <span x-text="currentStep"></span>
                    <span x-text="progress + '%'"></span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-amber-600 h-2 rounded-full transition-all" :style="'width: ' + progress + '%'"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    Annulla
                </a>
                <button
                    type="submit"
                    :disabled="context.length < 30 || isAnalyzing"
                    class="px-6 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center"
                >
                    <svg x-show="!isAnalyzing" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <svg x-show="isAnalyzing" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isAnalyzing ? 'Analisi in corso...' : 'Avvia Analisi AI'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Prepara contesti estratti per JavaScript
$extractedContextsJson = [];
foreach ($adGroups as $ag) {
    if (!empty($ag['extracted_context'])) {
        $extractedContextsJson[$ag['name']] = $ag['extracted_context'];
    }
}
?>

<script>
// Contesti estratti per uso JS
const extractedContexts = <?= json_encode($extractedContextsJson, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function useExtractedContexts() {
    const contexts = Object.entries(extractedContexts).map(([name, ctx]) => {
        return `[${name}]\n${ctx}`;
    }).join('\n\n---\n\n');

    const textarea = document.getElementById('business_context');
    if (textarea) {
        textarea.value = contexts;
        // Trigger Alpine.js update
        textarea.dispatchEvent(new Event('input'));
    }
}

function contextForm() {
    return {
        context: '<?= addslashes($project['business_context'] ?? '') ?>',
        selectedContext: '',
        saveContext: false,
        contextName: '',
        isAnalyzing: false,
        progress: 0,
        currentStep: '',
        error: null,

        loadContext() {
            if (this.selectedContext) {
                this.context = this.selectedContext;
            }
        },

        async startAnalysis() {
            console.log('=== START ANALYSIS ===');
            console.log('Context length:', this.context.length);

            if (this.context.length < 30) {
                this.error = 'Il contesto deve essere almeno 30 caratteri';
                return;
            }

            this.isAnalyzing = true;
            this.error = null;
            this.currentStep = 'Preparazione analisi...';
            this.progress = 10;

            try {
                const formData = new FormData();
                formData.append('business_context', this.context);

                const csrfInput = document.querySelector('input[name="_csrf_token"]');
                console.log('CSRF input found:', !!csrfInput);
                if (csrfInput) {
                    console.log('CSRF token:', csrfInput.value.substring(0, 10) + '...');
                    formData.append('_csrf_token', csrfInput.value);
                } else {
                    console.error('CSRF TOKEN NOT FOUND!');
                    this.error = 'Token CSRF non trovato';
                    this.isAnalyzing = false;
                    return;
                }

                if (this.saveContext && this.contextName) {
                    formData.append('save_context', '1');
                    formData.append('context_name', this.contextName);
                }

                this.currentStep = 'Analisi AI in corso...';
                this.progress = 30;

                const analyzeUrl = '<?= url('/ads-analyzer/projects/' . $project['id'] . '/analyze') ?>';
                console.log('Analyze URL:', analyzeUrl);
                console.log('Sending request...');

                const response = await fetch(analyzeUrl, {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                const responseText = await response.text();
                console.log('Response text (first 500 chars):', responseText.substring(0, 500));

                let data;
                try {
                    data = JSON.parse(responseText);
                    console.log('Parsed JSON:', data);
                } catch (jsonErr) {
                    console.error('JSON parse error:', jsonErr);
                    console.error('Full response:', responseText);
                    this.error = 'Errore server: risposta non valida. Controlla console (F12).';
                    this.isAnalyzing = false;
                    return;
                }

                if (data.error) {
                    console.log('Server error:', data.error);
                    this.error = data.error;
                    this.isAnalyzing = false;
                    return;
                }

                console.log('=== ANALYSIS SUCCESS ===');
                this.currentStep = 'Completato!';
                this.progress = 100;

                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);

            } catch (err) {
                console.error('=== FETCH ERROR ===');
                console.error('Error:', err.message);
                console.error('Stack:', err.stack);
                this.error = 'Errore: ' + err.message;
                this.isAnalyzing = false;
            }
        }
    };
}
</script>
