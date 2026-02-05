<?php
$currentPage = 'settings';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura le opzioni del progetto</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <form method="POST" action="<?= url('/seo-onpage/project/' . $project['id'] . '/settings') ?>" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Nome Progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Nome Progetto *
                </label>
                <input type="text" name="name" id="name" required
                       value="<?= e($project['name']) ?>"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Dominio -->
            <div>
                <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Dominio *
                </label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 text-sm">
                        https://
                    </span>
                    <input type="text" name="domain" id="domain" required
                           value="<?= e($project['domain']) ?>"
                           class="flex-1 px-4 py-2 rounded-r-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <!-- Device Default -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Device Predefinito
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="default_device" value="desktop" <?= ($project['default_device'] ?? 'desktop') === 'desktop' ? 'checked' : '' ?>
                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Desktop</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="default_device" value="mobile" <?= ($project['default_device'] ?? 'desktop') === 'mobile' ? 'checked' : '' ?>
                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Mobile</span>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="confirmDelete()" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Elimina progetto
                </button>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salva Modifiche
                </button>
            </div>
        </form>
    </div>

    <!-- Project Stats -->
    <?php if (!empty($project['stats'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Statistiche Progetto</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $project['stats']['pages_total'] ?? 0 ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pagine totali</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= $project['stats']['pages_completed'] ?? 0 ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Analizzate</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $project['stats']['avg_score'] ?? '-' ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Score medio</p>
            </div>
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $project['stats']['issues_critical'] ?? 0 ?></p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Issues critici</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Modal (hidden form) -->
<form id="delete-form" method="POST" action="<?= url('/seo-onpage/project/' . $project['id'] . '/delete') ?>" style="display: none;">
    <?= csrf_field() ?>
</form>

<script>
function confirmDelete() {
    if (confirm('Sei sicuro di voler eliminare questo progetto? Tutti i dati associati verranno persi.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
