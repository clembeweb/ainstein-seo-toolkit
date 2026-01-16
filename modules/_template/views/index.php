<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Template Module</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Questo e un modulo template da usare come base per nuovi moduli SEO.</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Come creare un nuovo modulo</h3>

            <ol class="list-decimal list-inside space-y-3 text-slate-700 dark:text-slate-300">
                <li>
                    Copia la cartella <code class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-sm">modules/_template</code> e rinominala
                    <br><span class="text-sm text-slate-500 dark:text-slate-400">Es: <code>modules/keyword-research</code></span>
                </li>
                <li>
                    Modifica <code class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-sm">module.json</code> con i dati del tuo modulo
                </li>
                <li>
                    Aggiorna lo slug in <code class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-sm">routes.php</code>
                </li>
                <li>
                    Registra il modulo nel database:
                    <pre class="mt-2 p-3 bg-slate-100 dark:bg-slate-700 rounded text-sm overflow-x-auto">INSERT INTO modules (slug, name, description, version, is_active)
VALUES ('keyword-research', 'Keyword Research', 'Ricerca keyword', '1.0.0', 1);</pre>
                </li>
                <li>
                    Crea le views in <code class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-sm">views/</code>
                </li>
                <li>
                    Se il modulo ha sub-entita (es. progetti), leggi <code class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-sm">docs/MODULE_NAVIGATION.md</code>
                </li>
            </ol>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Struttura modulo</h3>

            <pre class="p-4 bg-slate-100 dark:bg-slate-700 rounded-xl text-sm overflow-x-auto text-slate-800 dark:text-slate-200">modules/nome-modulo/
├── module.json       # Metadata del modulo
├── routes.php        # Routes del modulo
├── Controllers/      # Controller (PSR-4: Modules\NomeModulo\Controllers)
├── Models/           # Models (PSR-4: Modules\NomeModulo\Models)
├── Services/         # Services (PSR-4: Modules\NomeModulo\Services)
├── views/            # Views del modulo
│   ├── index.php
│   └── [sezioni]/
└── assets/           # CSS/JS specifici (opzionale)</pre>
        </div>
    </div>

    <!-- Navigation Standard -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl">
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-amber-800 dark:text-amber-200 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Standard Navigazione (IMPORTANTE)
            </h3>

            <div class="space-y-3 text-sm text-amber-700 dark:text-amber-300">
                <p><strong>Se il modulo ha sub-entita (progetti, campagne, etc.):</strong></p>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>NON creare una sidebar separata nel modulo</li>
                    <li>Integrare la navigazione nell'accordion della sidebar principale</li>
                    <li>Modificare <code class="bg-amber-100 dark:bg-amber-900/40 px-1 rounded">shared/views/components/nav-items.php</code></li>
                    <li>Le views usano tutto lo spazio content (no wrapper con sidebar)</li>
                </ul>
                <p class="pt-2">
                    <strong>Documentazione completa:</strong>
                    <code class="bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded">docs/MODULE_NAVIGATION.md</code>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Servizi disponibili</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                    <h4 class="font-medium text-slate-900 dark:text-white">AiService</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Integrazione con Claude API per analisi AI</p>
                    <p class="text-xs text-primary-600 dark:text-primary-400 mt-2">Costo: 1-5 crediti</p>
                </div>
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                    <h4 class="font-medium text-slate-900 dark:text-white">ScraperService</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Fetch e parsing pagine web</p>
                    <p class="text-xs text-primary-600 dark:text-primary-400 mt-2">Costo: 0.1 crediti/URL</p>
                </div>
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4">
                    <h4 class="font-medium text-slate-900 dark:text-white">ExportService</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Export dati in CSV/Excel</p>
                    <p class="text-xs text-primary-600 dark:text-primary-400 mt-2">Costo: 0-0.5 crediti</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Esempio View Standard -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Struttura View Standard</h3>

            <pre class="p-4 bg-slate-100 dark:bg-slate-700 rounded-xl text-sm overflow-x-auto text-slate-800 dark:text-slate-200">&lt;!-- views/sezione/index.php --&gt;
&lt;div class="space-y-6"&gt;
    &lt;!-- Page Header --&gt;
    &lt;div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"&gt;
        &lt;div&gt;
            &lt;h1 class="text-2xl font-bold text-slate-900 dark:text-white"&gt;
                &lt;?= __('Page Title') ?&gt;
            &lt;/h1&gt;
            &lt;p class="mt-1 text-slate-500 dark:text-slate-400"&gt;
                &lt;?= __('Description') ?&gt;
            &lt;/p&gt;
        &lt;/div&gt;
        &lt;div class="flex items-center gap-3"&gt;
            &lt;!-- Action buttons --&gt;
        &lt;/div&gt;
    &lt;/div&gt;

    &lt;!-- Quick Stats (in header, NOT sidebar) --&gt;
    &lt;div class="grid grid-cols-2 md:grid-cols-4 gap-4"&gt;
        &lt;div class="bg-white dark:bg-slate-800 rounded-xl border p-4"&gt;
            &lt;!-- stat card --&gt;
        &lt;/div&gt;
    &lt;/div&gt;

    &lt;!-- Main Content --&gt;
    &lt;div class="bg-white dark:bg-slate-800 rounded-2xl border p-6"&gt;
        &lt;!-- content --&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>
        </div>
    </div>

    <!-- Esempio form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Esempio form</h3>

            <form action="<?= url('/' . $moduleSlug . '/analyze') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="input" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Input</label>
                    <input type="text" name="input" id="input" placeholder="Inserisci qualcosa..."
                           class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-slate-700 dark:text-white sm:text-sm">
                </div>

                <button type="submit" class="inline-flex items-center rounded-xl border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition">
                    Analizza
                    <span class="ml-2 text-xs opacity-75">(1 credito)</span>
                </button>
            </form>
        </div>
    </div>
</div>
