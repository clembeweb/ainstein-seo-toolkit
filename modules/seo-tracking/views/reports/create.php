<div class="space-y-6">
    <!-- Header -->
    <div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Genera Report AI</h1>
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            <?= e($project['name']) ?>
        </p>
    </div>

    <!-- Credits Banner -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium">Crediti AI disponibili</p>
                    <p class="text-sm text-white/80">La generazione consumerà crediti in base al tipo di report</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold"><?= number_format($credits ?? 0) ?></p>
                <p class="text-sm text-white/80">crediti</p>
            </div>
        </div>
    </div>

    <!-- Report Types -->
    <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/reports/generate') ?>" method="POST" id="generate-form">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Weekly Digest -->
            <label class="report-type-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-slate-200 dark:border-slate-700 p-6 cursor-pointer hover:border-primary-500 transition-colors">
                <input type="radio" name="report_type" value="weekly_digest" class="sr-only" checked>
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Digest Settimanale</h3>
                            <span class="text-sm text-slate-500 dark:text-slate-400">~5 crediti</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Riepilogo delle performance SEO degli ultimi 7 giorni con trend, keyword in movimento e suggerimenti.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Trend posizioni</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Top movers</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Suggerimenti</span>
                        </div>
                    </div>
                </div>
                <div class="check-indicator hidden mt-4 text-center">
                    <span class="inline-flex items-center text-primary-600 dark:text-primary-400 font-medium">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Selezionato
                    </span>
                </div>
            </label>

            <!-- Monthly Executive -->
            <label class="report-type-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-slate-200 dark:border-slate-700 p-6 cursor-pointer hover:border-primary-500 transition-colors">
                <input type="radio" name="report_type" value="monthly_executive" class="sr-only">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Executive Mensile</h3>
                            <span class="text-sm text-slate-500 dark:text-slate-400">~10 crediti</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Report approfondito per stakeholder con analisi ROI, proiezioni e raccomandazioni strategiche.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Analisi ROI</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Proiezioni</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Strategia</span>
                        </div>
                    </div>
                </div>
                <div class="check-indicator hidden mt-4 text-center">
                    <span class="inline-flex items-center text-primary-600 dark:text-primary-400 font-medium">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Selezionato
                    </span>
                </div>
            </label>

            <!-- Anomaly Analysis -->
            <label class="report-type-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-slate-200 dark:border-slate-700 p-6 cursor-pointer hover:border-primary-500 transition-colors">
                <input type="radio" name="report_type" value="anomaly_analysis" class="sr-only">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Analisi Anomalie</h3>
                            <span class="text-sm text-slate-500 dark:text-slate-400">~3 crediti</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Analisi degli alert recenti con diagnosi delle cause e suggerimenti per il recupero.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Diagnosi</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Cause</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Recovery</span>
                        </div>
                    </div>
                </div>
                <div class="check-indicator hidden mt-4 text-center">
                    <span class="inline-flex items-center text-primary-600 dark:text-primary-400 font-medium">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Selezionato
                    </span>
                </div>
            </label>

            <!-- Custom Report -->
            <label class="report-type-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border-2 border-slate-200 dark:border-slate-700 p-6 cursor-pointer hover:border-primary-500 transition-colors">
                <input type="radio" name="report_type" value="custom" class="sr-only">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Report Personalizzato</h3>
                            <span class="text-sm text-slate-500 dark:text-slate-400">~5 crediti</span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Fai una domanda specifica sui dati e ottieni un'analisi personalizzata.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Domanda libera</span>
                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">Analisi mirata</span>
                        </div>
                    </div>
                </div>
                <div class="check-indicator hidden mt-4 text-center">
                    <span class="inline-flex items-center text-primary-600 dark:text-primary-400 font-medium">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Selezionato
                    </span>
                </div>
            </label>
        </div>

        <!-- Custom Question (shown only for custom type) -->
        <div id="custom-question-container" class="hidden mt-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    La tua domanda
                </label>
                <textarea name="custom_question" rows="4" class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Es: Quali keyword hanno il maggior potenziale di crescita basandosi sui dati dell'ultimo mese?"></textarea>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Sii specifico nella tua domanda per ottenere un'analisi piu accurata.
                </p>
            </div>
        </div>

        <!-- Generate Button -->
        <div class="flex justify-end gap-4 mt-6">
            <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports') ?>" class="px-6 py-3 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Annulla
            </a>
            <button type="submit" id="generate-btn" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Genera Report
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.report-type-card');
    const customContainer = document.getElementById('custom-question-container');
    const form = document.getElementById('generate-form');
    const generateBtn = document.getElementById('generate-btn');

    function updateSelection() {
        cards.forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            const indicator = card.querySelector('.check-indicator');

            if (radio.checked) {
                card.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/10');
                card.classList.remove('border-slate-200', 'dark:border-slate-700');
                indicator.classList.remove('hidden');
            } else {
                card.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/10');
                card.classList.add('border-slate-200', 'dark:border-slate-700');
                indicator.classList.add('hidden');
            }
        });

        // Show/hide custom question
        const selectedType = document.querySelector('input[name="report_type"]:checked').value;
        if (selectedType === 'custom') {
            customContainer.classList.remove('hidden');
        } else {
            customContainer.classList.add('hidden');
        }
    }

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            updateSelection();
        });
    });

    // Initial state
    updateSelection();

    // Form submit handling
    form.addEventListener('submit', function(e) {
        generateBtn.disabled = true;
        generateBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generazione in corso...
        `;
    });
});
</script>
