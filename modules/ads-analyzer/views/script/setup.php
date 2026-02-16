<?php $currentPage = 'script'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div x-data="scriptSetup()" class="space-y-6">

    <!-- Token API -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Token API</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Usato dallo script per autenticarsi con il server</p>
            </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Token</label>
                    <div class="flex items-center gap-2">
                        <code class="text-sm font-mono text-slate-900 dark:text-white" x-text="showToken ? fullToken : maskedToken"></code>
                        <button @click="showToken = !showToken" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" :title="showToken ? 'Nascondi' : 'Mostra'">
                            <template x-if="!showToken">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </template>
                            <template x-if="showToken">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>
                <?php if (!empty($project['api_token_created_at'])): ?>
                <div class="text-right">
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Creato il</label>
                    <span class="text-sm text-slate-700 dark:text-slate-300"><?= date('d/m/Y H:i', strtotime($project['api_token_created_at'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Rigenerare il token invalida lo script attualmente installato in Google Ads</span>
            </div>
            <button
                @click="regenerateToken()"
                :disabled="regenerating"
                class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium border border-amber-300 dark:border-amber-600 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-50"
            >
                <svg class="w-4 h-4 mr-2" :class="regenerating && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="regenerating ? 'Rigenerazione...' : 'Rigenera Token'"></span>
            </button>
        </div>

        <!-- Feedback rigenerazione -->
        <template x-if="tokenMessage">
            <div class="mt-3 flex items-center gap-2 p-3 rounded-lg text-sm"
                 :class="tokenError ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <template x-if="!tokenError">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </template>
                    <template x-if="tokenError">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </template>
                </svg>
                <span x-text="tokenMessage"></span>
            </div>
        </template>
    </div>

    <!-- Configurazione -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Configurazione</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Scegli quali dati raccogliere e il periodo di riferimento</p>
            </div>
        </div>

        <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script/config') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="space-y-5">
                <!-- Checkboxes dati -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Dati da raccogliere</label>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    type="checkbox"
                                    id="enable_search_terms"
                                    name="enable_search_terms"
                                    value="1"
                                    <?= ($config['enable_search_terms'] ?? true) ? 'checked' : '' ?>
                                    class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="enable_search_terms" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Termini di ricerca
                                </label>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Raccoglie i termini di ricerca effettivi che attivano i tuoi annunci
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    type="checkbox"
                                    id="enable_campaign_performance"
                                    name="enable_campaign_performance"
                                    value="1"
                                    <?= ($config['enable_campaign_performance'] ?? false) ? 'checked' : '' ?>
                                    class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="enable_campaign_performance" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Performance campagne
                                </label>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Raccoglie metriche di performance per campagna (impressioni, clic, costo, conversioni)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="date_range" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Periodo di riferimento
                    </label>
                    <select
                        id="date_range"
                        name="date_range"
                        class="w-full sm:w-64 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                        <option value="LAST_7_DAYS" <?= ($config['date_range'] ?? '') === 'LAST_7_DAYS' ? 'selected' : '' ?>>Ultimi 7 giorni</option>
                        <option value="LAST_14_DAYS" <?= ($config['date_range'] ?? '') === 'LAST_14_DAYS' ? 'selected' : '' ?>>Ultimi 14 giorni</option>
                        <option value="LAST_30_DAYS" <?= ($config['date_range'] ?? 'LAST_30_DAYS') === 'LAST_30_DAYS' ? 'selected' : '' ?>>Ultimi 30 giorni</option>
                        <option value="LAST_90_DAYS" <?= ($config['date_range'] ?? '') === 'LAST_90_DAYS' ? 'selected' : '' ?>>Ultimi 90 giorni</option>
                        <option value="ALL_TIME" <?= ($config['date_range'] ?? '') === 'ALL_TIME' ? 'selected' : '' ?>>Tutto il periodo</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Periodo di tempo per cui lo script raccoglie i dati ad ogni esecuzione
                    </p>
                </div>

                <!-- Campaign Filter -->
                <div>
                    <label for="campaign_filter" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Filtro campagne <span class="text-slate-400 font-normal">(opzionale)</span>
                    </label>
                    <input
                        type="text"
                        id="campaign_filter"
                        name="campaign_filter"
                        value="<?= e($config['campaign_filter'] ?? '') ?>"
                        placeholder="es. Brand|Search|Shopping"
                        class="w-full sm:w-96 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Espressione regolare per filtrare le campagne per nome. Lascia vuoto per includere tutte le campagne.
                    </p>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit" class="inline-flex items-center px-6 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Salva Configurazione
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Script Google Ads -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Script Google Ads</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Copia e installa questo script nel tuo account Google Ads</p>
            </div>
        </div>

        <!-- Info box -->
        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium mb-1">Come funziona</p>
                    <p>Lo script viene eseguito periodicamente nel tuo account Google Ads e invia i dati (termini di ricerca, performance campagne) al nostro server tramite API. I dati vengono poi analizzati per identificare keyword negative e opportunita di ottimizzazione.</p>
                </div>
            </div>
        </div>

        <!-- Script code -->
        <div class="relative">
            <div class="absolute top-2 right-2 z-10">
                <button
                    @click="copyScript()"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    :class="copied ? 'bg-emerald-600 text-white' : 'bg-slate-600 text-slate-200 hover:bg-slate-500'"
                >
                    <template x-if="!copied">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </template>
                    <template x-if="copied">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <span x-text="copied ? 'Copiato!' : 'Copia Script'"></span>
                </button>
            </div>
            <pre class="bg-slate-900 text-slate-100 rounded-lg p-4 overflow-x-auto text-xs leading-relaxed max-h-96 overflow-y-auto"><code id="script-code"><?= e($script) ?></code></pre>
        </div>

        <!-- Endpoint info -->
        <div class="mt-3 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <span>Endpoint: <code class="font-mono text-slate-600 dark:text-slate-300"><?= e($endpointUrl) ?></code></span>
        </div>

        <!-- Istruzioni -->
        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Come installare lo script in Google Ads</h3>
            <ol class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center">1</span>
                    <span>Accedi al tuo account <strong class="text-slate-900 dark:text-white">Google Ads</strong> e vai su <strong class="text-slate-900 dark:text-white">Strumenti e impostazioni</strong> > <strong class="text-slate-900 dark:text-white">Script</strong></span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center">2</span>
                    <span>Clicca sul pulsante <strong class="text-slate-900 dark:text-white">+</strong> per creare un nuovo script</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center">3</span>
                    <span>Assegna un nome (es. "Ainstein Data Collector") e <strong class="text-slate-900 dark:text-white">incolla lo script</strong> copiato sopra nell'editor</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center">4</span>
                    <span>Clicca su <strong class="text-slate-900 dark:text-white">Autorizza</strong> per consentire allo script l'accesso ai dati dell'account e alle URL esterne</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center">5</span>
                    <span>Esegui un <strong class="text-slate-900 dark:text-white">test manuale</strong> con "Anteprima" per verificare che funzioni correttamente</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center">6</span>
                    <span>Imposta la <strong class="text-slate-900 dark:text-white">frequenza di esecuzione</strong> (consigliato: giornaliera) e salva</span>
                </li>
            </ol>
        </div>
    </div>

    <!-- Ultime Esecuzioni -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg class="h-5 w-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ultime Esecuzioni</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Storico delle esecuzioni dello script</p>
                </div>
            </div>
            <?php if (!empty($recentRuns)): ?>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script/runs') ?>" class="text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300">
                Vedi tutte
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($recentRuns)): ?>
        <div class="px-4 py-12 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Nessuna esecuzione registrata</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Le esecuzioni appariranno qui dopo aver installato lo script in Google Ads</p>
        </div>
        <?php else: ?>
        <table class="w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($recentRuns as $run): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                        <?= date('d/m/Y H:i', strtotime($run['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                        <?php
                        $runTypeLabels = [
                            'search_terms' => 'Termini di ricerca',
                            'campaign_performance' => 'Performance campagne',
                            'full' => 'Completa',
                        ];
                        echo e($runTypeLabels[$run['run_type']] ?? ucfirst($run['run_type']));
                        ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php
                        $statusConfig = [
                            'received' => ['label' => 'Ricevuto', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'],
                            'processing' => ['label' => 'In elaborazione', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'],
                            'completed' => ['label' => 'Completato', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
                            'error' => ['label' => 'Errore', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
                        ];
                        $sc = $statusConfig[$run['status']] ?? ['label' => ucfirst($run['status']), 'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'];
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $sc['class'] ?>">
                            <?= $sc['label'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 dark:text-white text-right">
                        <?= number_format($run['items_received'] ?? 0) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
function scriptSetup() {
    return {
        fullToken: <?= json_encode($project['api_token'] ?? '') ?>,
        showToken: false,
        copied: false,
        regenerating: false,
        tokenMessage: '',
        tokenError: false,

        get maskedToken() {
            const t = this.fullToken;
            if (!t || t.length < 12) return t;
            return t.substring(0, 8) + '...' + t.substring(t.length - 4);
        },

        async regenerateToken() {
            if (!confirm('Sei sicuro di voler rigenerare il token? Lo script attualmente installato in Google Ads smettera di funzionare fino a quando non lo aggiornerai con il nuovo codice.')) {
                return;
            }

            this.regenerating = true;
            this.tokenMessage = '';
            this.tokenError = false;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/script/regenerate-token') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await resp.json();

                if (data.success) {
                    this.fullToken = data.token;
                    this.tokenMessage = data.message || 'Token rigenerato con successo. Ricopia lo script aggiornato.';
                    this.tokenError = false;
                    // Ricarica la pagina dopo 2s per aggiornare lo script generato
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    this.tokenMessage = data.error || 'Errore durante la rigenerazione del token.';
                    this.tokenError = true;
                }
            } catch (e) {
                this.tokenMessage = 'Errore di connessione. Riprova.';
                this.tokenError = true;
            } finally {
                this.regenerating = false;
            }
        },

        async copyScript() {
            try {
                const code = document.getElementById('script-code').textContent;
                await navigator.clipboard.writeText(code);
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            } catch (e) {
                // Fallback per browser meno recenti
                const textarea = document.createElement('textarea');
                textarea.value = document.getElementById('script-code').textContent;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            }
        }
    };
}
</script>
