<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Automatico
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Generazione articoli automatica schedulata
                </p>
            </div>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <!-- Toggle Attivo/Pausa -->
            <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/toggle') ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <?php if ($config['is_active'] ?? true): ?>
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-400 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pausa
                </button>
                <?php else: ?>
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Attiva
                </button>
                <?php endif; ?>
            </form>

            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/settings') ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Impostazioni
            </a>

            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/add') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Aggiungi Keyword
            </a>

            <!-- Processa Ora Button -->
            <button type="button" id="btn-process-now" onclick="startProcess()" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" <?= ($stats['pending'] ?? 0) == 0 ? 'disabled' : '' ?>>
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Processa Ora
            </button>
        </div>
    </div>

    <!-- Status Banner -->
    <?php if (!($config['is_active'] ?? true)): ?>
    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-amber-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm text-amber-700 dark:text-amber-400">
                L'automazione è in pausa. Le keyword in coda non verranno elaborate fino alla riattivazione.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Process Status Panel (hidden by default) -->
    <div id="process-panel" class="hidden bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div id="process-spinner" class="mr-3">
                    <svg class="animate-spin h-6 w-6 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white" id="process-title">Elaborazione in corso</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400" id="process-subtitle">Avvio...</p>
                </div>
            </div>
            <button type="button" id="btn-cancel-process" onclick="cancelProcess()" class="inline-flex items-center px-3 py-2 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Annulla
            </button>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400 mb-1">
                <span id="process-progress-text">0%</span>
                <span id="process-stats">0 / 0 keyword</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                <div id="process-progress-bar" class="bg-primary-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <!-- Current Keyword & Step -->
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3">
                <p class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide mb-1">Keyword Corrente</p>
                <p class="font-medium text-slate-900 dark:text-white truncate" id="process-current-keyword">-</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3">
                <p class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide mb-1">Step</p>
                <p class="font-medium text-slate-900 dark:text-white" id="process-current-step">-</p>
            </div>
        </div>

        <!-- Completed Message (shown when done) -->
        <div id="process-completed" class="hidden mt-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-medium text-emerald-700 dark:text-emerald-400" id="process-completed-text">Elaborazione completata!</p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-300" id="process-completed-stats"></p>
                </div>
            </div>
        </div>

        <!-- Error Message (shown on error) -->
        <div id="process-error" class="hidden mt-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-medium text-red-700 dark:text-red-400">Errore durante l'elaborazione</p>
                    <p class="text-sm text-red-600 dark:text-red-300" id="process-error-text"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- In Coda -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">In Coda</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['pending'] ?? 0) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                keyword da elaborare
            </p>
        </div>

        <!-- Completate Oggi -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Completate Oggi</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($todayStats['completed_today'] ?? 0) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                articoli generati oggi
            </p>
        </div>

        <!-- Prossima Schedulata -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Prossima</p>
                    <?php if (!empty($stats['next_scheduled'])): ?>
                    <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white"><?= date('H:i', strtotime($stats['next_scheduled'])) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= date('d/m/Y', strtotime($stats['next_scheduled'])) ?></p>
                    <?php else: ?>
                    <p class="mt-1 text-xl font-bold text-slate-400 dark:text-slate-500">--:--</p>
                    <p class="text-sm text-slate-400 dark:text-slate-500">nessuna</p>
                    <?php endif; ?>
                </div>
                <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Coda Pending -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Coda</h2>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                    Vedi tutta
                </a>
            </div>
            <?php if (empty($pendingItems)): ?>
            <div class="p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nessuna keyword in coda</p>
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto/add') ?>" class="mt-3 inline-flex items-center text-sm text-primary-600 dark:text-primary-400 hover:underline">
                    Aggiungi keyword
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php else: ?>
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($pendingItems as $item): ?>
                <li class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($item['keyword']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <?= date('d/m H:i', strtotime($item['scheduled_at'])) ?>
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        In attesa
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Articoli Recenti -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Articoli Recenti</h2>
            </div>
            <?php if (empty($completedItems)): ?>
            <div class="p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nessun articolo generato</p>
            </div>
            <?php else: ?>
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($completedItems as $item): ?>
                <li class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($item['keyword']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <?= date('d/m/Y H:i', strtotime($item['completed_at'])) ?>
                        </p>
                    </div>
                    <?php if (!empty($item['article_id'])): ?>
                    <a href="<?= url('/ai-content/articles/' . $item['article_id']) ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                        Vedi articolo
                    </a>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                        Completato
                    </span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Errori Recenti -->
    <?php if (!empty($errorItems)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
        <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 rounded-t-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-lg font-semibold text-red-700 dark:text-red-400">Errori Recenti</h2>
            </div>
        </div>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($errorItems as $item): ?>
            <li class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($item['keyword']) ?></p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1"><?= e($item['error_message'] ?? 'Errore sconosciuto') ?></p>
                    </div>
                    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/queue/' . $item['id'] . '/retry') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Riprova
                        </button>
                    </form>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Breadcrumb / Back -->
    <div class="pt-4">
        <a href="<?= url('/ai-content') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Torna ai progetti
        </a>
    </div>
</div>

<script>
// Process Control JavaScript
const projectId = <?= (int) $project['id'] ?>;
const csrfToken = '<?= csrf_token() ?>';
const baseUrl = '<?= rtrim(url(''), '/') ?>';
let currentJobId = null;
let pollInterval = null;

// Start process
async function startProcess() {
    const btn = document.getElementById('btn-process-now');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Avvio...';

    try {
        const formData = new FormData();
        formData.append('_token', csrfToken);

        const response = await fetch(`${baseUrl}/ai-content/projects/${projectId}/auto/process/start`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            currentJobId = data.job_id;
            showProcessPanel();
            updateProcessUI({
                progress: 0,
                keywords_requested: data.keywords_requested,
                keywords_completed: 0,
                current_keyword: null,
                current_step_label: 'Avvio...'
            });
            startPolling();
        } else {
            alert(data.error || 'Errore nell\'avvio del processo');
            resetButton();
        }
    } catch (error) {
        console.error('Start process error:', error);
        alert('Errore di connessione');
        resetButton();
    }
}

// Poll for status
async function pollStatus() {
    if (!currentJobId) return;

    try {
        const response = await fetch(`${baseUrl}/ai-content/projects/${projectId}/auto/process/status?job_id=${currentJobId}`, {
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success && data.has_job) {
            const job = data.job;
            updateProcessUI(job);

            if (data.is_completed) {
                stopPolling();
                showCompleted(job);
            } else if (data.is_error) {
                stopPolling();
                showError(job.error_message || 'Errore sconosciuto');
            } else if (data.is_cancelled) {
                stopPolling();
                showCancelled();
            }
        }
    } catch (error) {
        console.error('Poll status error:', error);
    }
}

// Cancel process
async function cancelProcess() {
    if (!currentJobId) return;

    if (!confirm('Sei sicuro di voler annullare l\'elaborazione?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('job_id', currentJobId);

        const response = await fetch(`${baseUrl}/ai-content/projects/${projectId}/auto/process/cancel`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            showCancelled();
        } else {
            alert(data.error || 'Errore nell\'annullamento');
        }
    } catch (error) {
        console.error('Cancel process error:', error);
        alert('Errore di connessione');
    }
}

// UI Update functions
function showProcessPanel() {
    document.getElementById('process-panel').classList.remove('hidden');
    document.getElementById('process-completed').classList.add('hidden');
    document.getElementById('process-error').classList.add('hidden');
    document.getElementById('btn-cancel-process').classList.remove('hidden');
    document.getElementById('process-spinner').classList.remove('hidden');
}

function updateProcessUI(job) {
    const progress = job.progress || 0;
    const total = job.keywords_requested || 0;
    const completed = job.keywords_completed || 0;
    const failed = job.keywords_failed || 0;

    document.getElementById('process-progress-bar').style.width = `${progress}%`;
    document.getElementById('process-progress-text').textContent = `${progress}%`;
    document.getElementById('process-stats').textContent = `${completed + failed} / ${total} keyword`;
    document.getElementById('process-current-keyword').textContent = job.current_keyword || '-';
    document.getElementById('process-current-step').textContent = job.current_step_label || '-';
    document.getElementById('process-subtitle').textContent = `${completed} completate, ${failed} errori`;
}

function showCompleted(job) {
    document.getElementById('process-spinner').classList.add('hidden');
    document.getElementById('btn-cancel-process').classList.add('hidden');
    document.getElementById('process-completed').classList.remove('hidden');
    document.getElementById('process-title').textContent = 'Elaborazione completata';
    document.getElementById('process-completed-stats').textContent =
        `${job.keywords_completed} articoli generati` +
        (job.keywords_failed > 0 ? `, ${job.keywords_failed} errori` : '');

    // Update progress to 100%
    document.getElementById('process-progress-bar').style.width = '100%';
    document.getElementById('process-progress-bar').classList.remove('bg-primary-600');
    document.getElementById('process-progress-bar').classList.add('bg-emerald-600');

    resetButton();
    setTimeout(() => location.reload(), 3000);
}

function showError(errorMessage) {
    document.getElementById('process-spinner').classList.add('hidden');
    document.getElementById('btn-cancel-process').classList.add('hidden');
    document.getElementById('process-error').classList.remove('hidden');
    document.getElementById('process-error-text').textContent = errorMessage;
    document.getElementById('process-title').textContent = 'Elaborazione fallita';

    document.getElementById('process-progress-bar').classList.remove('bg-primary-600');
    document.getElementById('process-progress-bar').classList.add('bg-red-600');

    resetButton();
}

function showCancelled() {
    stopPolling();
    document.getElementById('process-spinner').classList.add('hidden');
    document.getElementById('btn-cancel-process').classList.add('hidden');
    document.getElementById('process-title').textContent = 'Elaborazione annullata';
    document.getElementById('process-subtitle').textContent = 'Il processo è stato interrotto';

    document.getElementById('process-progress-bar').classList.remove('bg-primary-600');
    document.getElementById('process-progress-bar').classList.add('bg-amber-600');

    resetButton();
    setTimeout(() => location.reload(), 2000);
}

function resetButton() {
    const btn = document.getElementById('btn-process-now');
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Processa Ora';
}

function startPolling() {
    pollInterval = setInterval(pollStatus, 2000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

// Check for existing running job on page load
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch(`${baseUrl}/ai-content/projects/${projectId}/auto/process/status`, {
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success && data.has_job && data.is_running) {
            currentJobId = data.job.id;
            showProcessPanel();
            updateProcessUI(data.job);
            startPolling();
        }
    } catch (error) {
        console.error('Initial status check error:', error);
    }
});
</script>
