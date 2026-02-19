<?php
/**
 * Lista progetti AI Optimizer
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Article Optimizer</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Ottimizza i tuoi articoli esistenti con l'intelligenza artificiale</p>
        </div>
        <a href="<?= url('/projects/create') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuovo Progetto
        </a>
    </div>

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-6">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Come funziona</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Ottimizza i tuoi articoli esistenti in 4 semplici step:
                </p>
                <ol class="mt-3 text-sm text-amber-700 dark:text-amber-300 space-y-1 list-decimal list-inside">
                    <li><strong>Import:</strong> Inserisci URL articolo e keyword target</li>
                    <li><strong>Analisi:</strong> AI analizza i gap vs top competitor SERP</li>
                    <li><strong>Riscrittura:</strong> AI genera versione ottimizzata</li>
                    <li><strong>Export:</strong> Copia o scarica il nuovo contenuto</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Lista Progetti -->
    <?php if (empty($projects)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun progetto</h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6">Crea il tuo primo progetto per iniziare a ottimizzare i tuoi articoli.</p>
        <a href="<?= url('/projects/create') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crea Progetto
        </a>
    </div>
    <?php else: ?>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($projects as $project): ?>
        <a href="<?= url('/ai-optimizer/project/' . $project['id']) ?>"
           class="block bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate"><?= e($project['name']) ?></h3>
                    <?php if (!empty($project['domain'])): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400 truncate"><?= e($project['domain']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex-shrink-0 ml-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        <?= $project['language'] ?? 'it' ?>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span><?= $project['optimization_count'] ?? 0 ?> articoli</span>
                </div>
                <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span><?= $project['completed_count'] ?? 0 ?> completati</span>
                </div>
            </div>

            <?php if (!empty($project['description'])): ?>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300 line-clamp-2"><?= e($project['description']) ?></p>
            <?php endif; ?>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    Aggiornato: <?= date('d/m/Y H:i', strtotime($project['updated_at'])) ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
