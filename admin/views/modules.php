<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestione Moduli</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Attiva o disattiva i moduli della piattaforma</p>
    </div>

    <?php if (empty($allModules)): ?>
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Nessun modulo installato</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
            I moduli appariranno qui una volta installati. Copia il template da <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">modules/_template</code> per creare un nuovo modulo.
        </p>
    </div>
    <?php else: ?>
    <!-- Modules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($allModules as $m): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-lg bg-gradient-to-br <?= $m['is_active'] ? 'from-primary-500 to-primary-600' : 'from-slate-400 to-slate-500' ?> flex items-center justify-center shadow-sm">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white"><?= e($m['name']) ?></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">v<?= e($m['version']) ?></p>
                        </div>
                    </div>
                    <?php if ($m['is_active']): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Attivo
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        Inattivo
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($m['description'])): ?>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400"><?= e($m['description']) ?></p>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <code class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-2 py-1 rounded"><?= e($m['slug']) ?></code>

                        <div class="flex items-center gap-2">
                            <!-- Settings Button -->
                            <a href="<?= url('/admin/modules/' . $m['id'] . '/settings') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Impostazioni">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>

                            <!-- Toggle Button -->
                            <form action="<?= url('/admin/modules/' . $m['id'] . '/toggle') ?>" method="POST" class="inline">
                                <?= csrf_field() ?>
                                <?php if ($m['is_active']): ?>
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Disattiva
                                </button>
                                <?php else: ?>
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Attiva
                                </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Help Card -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Come creare un nuovo modulo</h4>
        <ol class="text-sm text-slate-600 dark:text-slate-400 space-y-2 list-decimal list-inside">
            <li>Copia la cartella <code class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">modules/_template</code></li>
            <li>Rinominala con lo slug del modulo (es: <code class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">keyword-research</code>)</li>
            <li>Modifica <code class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">module.json</code> e <code class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">routes.php</code></li>
            <li>Registra il modulo nel database con una query INSERT</li>
        </ol>
    </div>
</div>
