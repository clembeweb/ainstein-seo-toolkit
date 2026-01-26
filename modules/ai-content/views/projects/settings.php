<?php $currentPage = 'settings'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Impostazioni Progetto</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Modifica le impostazioni di "<?= e($project['name']) ?>"
        </p>
    </div>

    <!-- Stats Summary -->
    <?php if (!empty($project['stats'])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="grid grid-cols-4 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['stats']['keywords_count'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Keywords</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['stats']['articles_total'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Articoli</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($project['stats']['articles_published'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pubblicati</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($project['stats']['total_words'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Parole totali</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Settings Form -->
    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/update') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700" x-data="{ submitting: false }" @submit="submitting = true">
        <?= csrf_field() ?>

        <div class="p-6 space-y-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Informazioni generali</h2>

            <!-- Nome progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome progetto <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    maxlength="255"
                    value="<?= e($project['name']) ?>"
                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
            </div>

            <!-- Descrizione -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Descrizione
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    placeholder="Descrizione opzionale del progetto..."
                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                ><?= e($project['description'] ?? '') ?></textarea>
            </div>

            <!-- Lingua e Location -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="default_language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Lingua predefinita
                    </label>
                    <select
                        id="default_language"
                        name="default_language"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="it" <?= ($project['default_language'] ?? 'it') === 'it' ? 'selected' : '' ?>>Italiano</option>
                        <option value="en" <?= ($project['default_language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="de" <?= ($project['default_language'] ?? '') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                        <option value="fr" <?= ($project['default_language'] ?? '') === 'fr' ? 'selected' : '' ?>>Francais</option>
                        <option value="es" <?= ($project['default_language'] ?? '') === 'es' ? 'selected' : '' ?>>Espanol</option>
                    </select>
                </div>

                <div>
                    <label for="default_location" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Location predefinita
                    </label>
                    <select
                        id="default_location"
                        name="default_location"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="Italy" <?= ($project['default_location'] ?? 'Italy') === 'Italy' ? 'selected' : '' ?>>Italia</option>
                        <option value="United States" <?= ($project['default_location'] ?? '') === 'United States' ? 'selected' : '' ?>>Stati Uniti</option>
                        <option value="United Kingdom" <?= ($project['default_location'] ?? '') === 'United Kingdom' ? 'selected' : '' ?>>Regno Unito</option>
                        <option value="Germany" <?= ($project['default_location'] ?? '') === 'Germany' ? 'selected' : '' ?>>Germania</option>
                        <option value="France" <?= ($project['default_location'] ?? '') === 'France' ? 'selected' : '' ?>>Francia</option>
                        <option value="Spain" <?= ($project['default_location'] ?? '') === 'Spain' ? 'selected' : '' ?>>Spagna</option>
                    </select>
                </div>
            </div>

            <!-- WordPress Site -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4">Pubblicazione WordPress</h3>

                <div>
                    <label for="wp_site_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Sito predefinito per pubblicazione
                    </label>
                    <select
                        id="wp_site_id"
                        name="wp_site_id"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">-- Nessun sito predefinito --</option>
                        <?php foreach ($wpSites ?? [] as $site): ?>
                        <option value="<?= $site['id'] ?>" <?= ($project['wp_site_id'] ?? '') == $site['id'] ? 'selected' : '' ?>>
                            <?= e($site['name']) ?> (<?= e($site['url']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <?php if (empty($wpSites)): ?>
                        Nessun sito WordPress collegato. <a href="<?= url('/ai-content/wordpress') ?>" class="text-primary-600 hover:text-primary-700">Aggiungi un sito</a>
                        <?php else: ?>
                        Seleziona il sito WordPress predefinito per la pubblicazione degli articoli di questo progetto.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <a href="<?= url('/ai-content/projects/' . $project['id']) ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                Torna alla dashboard
            </a>
            <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center">
                <svg x-show="submitting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="submitting ? 'Salvataggio...' : 'Salva modifiche'"></span>
            </button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-900">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Zona pericolosa</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Eliminando questo progetto verranno eliminate anche tutte le keywords e gli articoli associati. Questa azione non può essere annullata.
            </p>

            <form action="<?= url('/ai-content/projects/' . $project['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto? Verranno eliminate <?= $project['stats']['keywords_count'] ?? 0 ?> keywords e <?= $project['stats']['articles_total'] ?? 0 ?> articoli. Questa azione non può essere annullata.');">
                <?= csrf_field() ?>
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition-colors">
                    Elimina progetto
                </button>
            </form>
        </div>
    </div>

    <!-- Info -->
    <div class="text-center text-xs text-slate-400 dark:text-slate-500">
        Progetto creato il <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
        <?php if (!empty($project['updated_at'])): ?>
        | Ultimo aggiornamento: <?= date('d/m/Y H:i', strtotime($project['updated_at'])) ?>
        <?php endif; ?>
    </div>
</div>
