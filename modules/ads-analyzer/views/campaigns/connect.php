<?php
$currentPage = 'connect';
$canEdit = ($access_role ?? 'owner') !== 'viewer';
include __DIR__ . '/../partials/project-nav.php';
?>

<?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

<div class="space-y-6">

    <!-- Hero Section -->
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-rose-600 to-rose-800 p-8 text-white">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold">Connessione Google Ads</h1>
            </div>
            <p class="text-rose-100 max-w-2xl">
                Collega il tuo account Google Ads per sincronizzare automaticamente campagne, gruppi di annunci, keyword e termini di ricerca direttamente dall'API.
            </p>
        </div>
    </div>

    <!-- Connection Card -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">

        <?php if (!$isConnected && !$hasOAuthToken): ?>
        <!-- State: Not Connected - No OAuth token -->
        <div class="p-8 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Collega il tuo account Google Ads</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6">
                Accedi con il tuo account Google per autorizzare Ainstein a leggere i dati delle tue campagne pubblicitarie.
                I dati vengono letti in sola lettura e non verranno mai modificati senza il tuo consenso.
            </p>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect-google-ads') ?>"
               class="inline-flex items-center px-6 py-3 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Connetti Google Ads
            </a>
        </div>

        <?php elseif (!$isConnected && $hasOAuthToken && !empty($error)): ?>
        <!-- State: OAuth ok but API error (missing developer token, etc.) -->
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Autenticazione completata</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Account Google collegato, ma non e stato possibile recuperare la lista account.</p>
                </div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                <p class="text-sm text-red-700 dark:text-red-300"><?= e($error) ?></p>
                <p class="text-xs text-red-600 dark:text-red-400 mt-2">Verifica che il Developer Token e il MCC Customer ID siano configurati nelle Impostazioni Admin.</p>
            </div>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-600 text-white text-sm font-medium hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Riprova
            </a>
        </div>

        <?php elseif (!$isConnected && !empty($accounts)): ?>
        <!-- State: Connected but needs account selection -->
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Seleziona account</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Account Google collegato. Scegli l'account Google Ads da utilizzare.</p>
                </div>
            </div>

            <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="account_name" id="selected_account_name" value="">

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Account Google Ads</label>
                    <?php foreach ($accounts as $account): ?>
                    <label class="flex items-center gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-600 hover:border-rose-300 dark:hover:border-rose-600 cursor-pointer transition-colors has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50 dark:has-[:checked]:bg-rose-900/20">
                        <input type="radio" name="customer_id" value="<?= e($account['customer_id']) ?>"
                               data-account-name="<?= e($account['name']) ?>"
                               onchange="document.getElementById('selected_account_name').value=this.dataset.accountName"
                               class="text-rose-600 focus:ring-rose-500">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-900 dark:text-white"><?= e($account['name']) ?></span>
                                <?php if (!empty($account['is_manager'])): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">MCC</span>
                                <?php endif; ?>
                                <?php if (!empty($account['status']) && $account['status'] !== 'ENABLED'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"><?= e($account['status']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm text-slate-500 dark:text-slate-400"><?= e($account['display_id']) ?><?= !empty($account['currency']) ? ' · ' . e($account['currency']) : '' ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit"
                        class="inline-flex items-center px-6 py-3 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Conferma selezione
                </button>
            </form>
        </div>

        <?php else: ?>
        <!-- State: Fully Connected -->
        <div class="p-6" x-data="{ showConfirm: false }">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Account collegato</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                Attivo
                            </span>
                        </div>
                        <div class="mt-1 space-y-1">
                            <?php if (!empty($connectionInfo['google_ads_account_name'])): ?>
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-medium">Account:</span> <?= e($connectionInfo['google_ads_account_name']) ?>
                            </p>
                            <?php endif; ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                <span class="font-medium">Customer ID:</span> <?= e($connectionInfo['google_ads_customer_id']) ?>
                            </p>
                            <?php if (!empty($connectionInfo['last_sync_at'])): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                <span class="font-medium">Ultima sincronizzazione:</span> <?= date('d/m/Y H:i', strtotime($connectionInfo['last_sync_at'])) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($canEdit): ?>
                <div class="relative">
                    <button @click="showConfirm = true"
                            class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Disconnetti
                    </button>

                    <!-- Confirm Dialog -->
                    <div x-show="showConfirm" x-cloak
                         class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 p-4 z-10">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Confermi la disconnessione?</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">I dati gia sincronizzati verranno mantenuti, ma non sara possibile sincronizzare nuovi dati.</p>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button @click="showConfirm = false"
                                    class="px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                Annulla
                            </button>
                            <form action="<?= url('/ads-analyzer/projects/' . $project['id'] . '/disconnect-google-ads') ?>" method="POST" class="inline">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                    Disconnetti
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Info: What gets synced -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Cosa viene sincronizzato</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            $syncItems = [
                ['icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'label' => 'Campagne', 'desc' => 'Performance, budget, stato e metriche'],
                ['icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'label' => 'Gruppi di annunci', 'desc' => 'Struttura e metriche per gruppo'],
                ['icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14', 'label' => 'Keyword', 'desc' => 'Keyword target con match type e bid'],
                ['icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', 'label' => 'Annunci', 'desc' => 'Testi, titoli e performance degli annunci'],
                ['icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'label' => 'Termini di ricerca', 'desc' => 'Query reali degli utenti con metriche'],
            ];
            ?>
            <?php foreach ($syncItems as $item): ?>
            <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/30">
                <div class="h-8 w-8 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="h-4 w-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white"><?= $item['label'] ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?= $item['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Help Info -->
    <div class="bg-rose-50 dark:bg-rose-900/20 rounded-xl p-4 border border-rose-200 dark:border-rose-800">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="font-medium text-rose-900 dark:text-rose-100">Come funziona</h4>
                <ul class="mt-2 text-sm text-rose-700 dark:text-rose-300 space-y-1">
                    <li>1. Autorizza l'accesso al tuo account Google Ads tramite OAuth</li>
                    <li>2. Seleziona l'account pubblicitario da monitorare</li>
                    <li>3. I dati vengono sincronizzati automaticamente ogni ora</li>
                    <li>4. Puoi disconnettere l'account in qualsiasi momento</li>
                </ul>
            </div>
        </div>
    </div>

</div>
