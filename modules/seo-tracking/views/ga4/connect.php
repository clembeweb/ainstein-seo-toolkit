<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/settings') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alle impostazioni
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Connetti Google Analytics 4</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Collega GA4 a <strong><?= e($project['name']) ?></strong> per tracciare sessioni, conversioni e revenue
        </p>
    </div>

    <!-- Form -->
    <form action="<?= url('/seo-tracking/projects/' . $project['id'] . '/ga4/connect') ?>" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="p-6 space-y-6">
            <!-- Property ID -->
            <div>
                <label for="property_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    GA4 Property ID <span class="text-red-500">*</span>
                </label>
                <input type="text" name="property_id" id="property_id" required
                       value="<?= e($connection['property_id'] ?? '') ?>"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                       placeholder="123456789">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Trovi il Property ID in GA4 → Amministrazione → Impostazioni property. Es: 123456789 (solo numeri)
                </p>
            </div>

            <!-- Service Account JSON -->
            <div>
                <label for="service_account_file" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Service Account JSON <?= $connection ? '' : '<span class="text-red-500">*</span>' ?>
                </label>

                <?php if ($connection && $connection['service_account_json']): ?>
                <div class="mb-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium">Service Account gia caricato</span>
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Carica un nuovo file solo se vuoi sostituirlo</p>
                </div>
                <?php endif; ?>

                <div class="relative">
                    <input type="file" name="service_account_file" id="service_account_file" accept=".json"
                           class="hidden" <?= $connection ? '' : 'required' ?>
                           onchange="updateFileName(this)">
                    <label for="service_account_file" class="flex items-center justify-center w-full px-4 py-8 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg cursor-pointer hover:border-primary-500 dark:hover:border-primary-400 transition-colors">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                <span class="font-medium text-primary-600 dark:text-primary-400">Clicca per caricare</span> o trascina il file
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" id="file-name">Solo file JSON</p>
                        </div>
                    </label>
                </div>
            </div>

            <?php if ($connection): ?>
            <!-- Stato attuale -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                <h3 class="text-sm font-medium text-slate-900 dark:text-white mb-2">Stato connessione attuale</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Property ID:</span>
                        <span class="ml-2 font-medium text-slate-900 dark:text-white"><?= e($connection['property_id']) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Ultimo sync:</span>
                        <span class="ml-2 font-medium text-slate-900 dark:text-white">
                            <?= $connection['last_sync_at'] ? date('d/m/Y H:i', strtotime($connection['last_sync_at'])) : 'Mai' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-between">
            <a href="<?= url('/seo-tracking/projects/' . $project['id'] . '/settings') ?>" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
                <?= $connection ? 'Aggiorna Connessione' : 'Connetti GA4' ?>
            </button>
        </div>
    </form>

    <!-- Instructions -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-medium text-slate-900 dark:text-white">Come ottenere le credenziali GA4</h2>
        </div>
        <div class="p-6 space-y-4 text-sm">
            <div class="flex gap-3">
                <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-medium text-xs">1</div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Crea un Service Account</p>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        Vai alla <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Google Cloud Console</a>,
                        seleziona o crea un progetto, poi crea un nuovo Service Account.
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-medium text-xs">2</div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Scarica la chiave JSON</p>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        Nella pagina del Service Account, vai su "Chiavi" → "Aggiungi chiave" → "Crea nuova chiave" → Seleziona JSON.
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-medium text-xs">3</div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Abilita la GA4 Data API</p>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        Cerca "Google Analytics Data API" nella <a href="https://console.cloud.google.com/apis/library" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">libreria API</a> e abilitala per il tuo progetto.
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-medium text-xs">4</div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Aggiungi il Service Account a GA4</p>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        In GA4, vai su Amministrazione → Gestione accessi property → Aggiungi utenti. Inserisci l'email del Service Account (es. nome@progetto.iam.gserviceaccount.com) con ruolo "Visualizzatore".
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-medium text-xs">5</div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">Trova il Property ID</p>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        In GA4, vai su Amministrazione → Impostazioni property. Il Property ID e il numero visualizzato sotto il nome della property.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Note -->
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h4 class="font-medium text-amber-900 dark:text-amber-100">Nota sulla sicurezza</h4>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Il file JSON del Service Account contiene credenziali sensibili. Viene memorizzato in modo sicuro e criptato nel database.
                    Non condividere mai questo file con terze parti.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0]?.name || 'Solo file JSON';
    document.getElementById('file-name').textContent = fileName;
}
</script>
