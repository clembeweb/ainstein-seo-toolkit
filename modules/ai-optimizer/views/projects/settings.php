<?php
/**
 * Impostazioni progetto
 */
$currentPage = 'settings';
?>

<div class="space-y-6">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <div class="max-w-2xl">
        <!-- Form Impostazioni -->
        <form action="<?= url('/ai-optimizer/project/' . $projectId . '/settings') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome Progetto <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required value="<?= e($project['name']) ?>"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500">
            </div>

            <div>
                <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Dominio
                </label>
                <input type="text" id="domain" name="domain" value="<?= e($project['domain'] ?? '') ?>"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Descrizione
                </label>
                <textarea id="description" name="description" rows="2"
                          class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500"><?= e($project['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Lingua contenuti
                    </label>
                    <select id="language" name="language"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                        <option value="it" <?= ($project['language'] ?? 'it') === 'it' ? 'selected' : '' ?>>Italiano</option>
                        <option value="en" <?= ($project['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= ($project['language'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                        <option value="de" <?= ($project['language'] ?? '') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                        <option value="fr" <?= ($project['language'] ?? '') === 'fr' ? 'selected' : '' ?>>Français</option>
                    </select>
                </div>

                <div>
                    <label for="location_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        SERP Location
                    </label>
                    <select id="location_code" name="location_code"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                        <option value="IT" <?= ($project['location_code'] ?? 'IT') === 'IT' ? 'selected' : '' ?>>Italia</option>
                        <option value="US" <?= ($project['location_code'] ?? '') === 'US' ? 'selected' : '' ?>>USA</option>
                        <option value="UK" <?= ($project['location_code'] ?? '') === 'UK' ? 'selected' : '' ?>>UK</option>
                        <option value="DE" <?= ($project['location_code'] ?? '') === 'DE' ? 'selected' : '' ?>>Germania</option>
                        <option value="FR" <?= ($project['location_code'] ?? '') === 'FR' ? 'selected' : '' ?>>Francia</option>
                        <option value="ES" <?= ($project['location_code'] ?? '') === 'ES' ? 'selected' : '' ?>>Spagna</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors">
                    Salva Modifiche
                </button>
            </div>
        </form>

        <!-- Danger Zone -->
        <div class="mt-8 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">Zona Pericolosa</h3>
            <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                Eliminando il progetto, tutte le ottimizzazioni associate verranno eliminate definitivamente.
            </p>
            <form action="<?= url('/ai-optimizer/project/' . $projectId . '/delete') ?>" method="POST"
                  onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto? Questa azione non può essere annullata.');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    Elimina Progetto
                </button>
            </form>
        </div>
    </div>
</div>
