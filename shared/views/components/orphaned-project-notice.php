<?php
/**
 * Notice per progetti non collegati a un Progetto Globale.
 *
 * Parametri:
 * - $project (array): dati del progetto modulo (deve avere 'global_project_id')
 */
if (empty($project['global_project_id'])):
?>
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-amber-800 dark:text-amber-200">
                <span class="font-medium">Progetto standalone.</span>
                Crea un Progetto per gestire tutti i moduli da un'unica dashboard.
            </p>
        </div>
        <a href="<?= url('/projects') ?>" class="text-sm font-medium text-amber-700 dark:text-amber-300 hover:underline whitespace-nowrap flex-shrink-0">
            Vai ai Progetti
        </a>
    </div>
</div>
<?php endif; ?>
