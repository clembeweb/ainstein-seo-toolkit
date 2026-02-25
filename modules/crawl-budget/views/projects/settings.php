<?php
/**
 * Crawl Budget Optimizer — Impostazioni Progetto
 */
$projectId = $project['id'];
?>

<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/crawl-budget/projects/' . $projectId) ?>" class="hover:text-orange-600 dark:hover:text-orange-400">
                <?= e($project['name']) ?>
            </a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span>Impostazioni</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Impostazioni Progetto</h1>
    </div>

    <!-- Settings Form -->
    <form action="<?= url('/crawl-budget/projects/' . $projectId . '/settings') ?>" method="POST"
          class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-5">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

        <!-- Nome Progetto -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome Progetto</label>
            <input type="text" id="name" name="name" value="<?= e($project['name'] ?? '') ?>"
                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>

        <!-- Dominio (read-only) -->
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dominio</label>
            <input type="text" value="<?= e($project['domain'] ?? '') ?>" disabled
                   class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 cursor-not-allowed">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Il dominio non può essere modificato dopo la creazione.</p>
        </div>

        <!-- Max Pagine -->
        <div>
            <label for="max_pages" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Max pagine per crawl</label>
            <input type="number" id="max_pages" name="max_pages" value="<?= e($project['max_pages'] ?? 500) ?>"
                   min="10" max="5000" step="10"
                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Massimo 5.000 pagine per singolo crawl.</p>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
            <a href="<?= url('/crawl-budget/projects/' . $projectId) ?>"
               class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                Annulla
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Salva Impostazioni
            </button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900 p-6">
        <h3 class="text-base font-semibold text-red-600 dark:text-red-400 mb-2">Zona Pericolosa</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">L'eliminazione del progetto cancellerà tutti i dati di crawl, issue e report.</p>
        <form action="<?= url('/crawl-budget/projects/' . $projectId . '/delete') ?>" method="POST"
              onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto? Tutti i dati saranno persi.')">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Elimina Progetto
            </button>
        </form>
    </div>
</div>
