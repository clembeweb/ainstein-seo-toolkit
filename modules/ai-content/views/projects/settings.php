<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        AI Content
                    </a>
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <a href="<?= url('/ai-content/projects/' . $project['id']) ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        <?= e($project['name']) ?>
                    </a>
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-2 text-slate-900 dark:text-white font-medium">Impostazioni</span>
                </li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni Progetto</h1>
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
    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/update') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
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
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <a href="<?= url('/ai-content/projects/' . $project['id']) ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                Torna alla dashboard
            </a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Salva modifiche
            </button>
        </div>
    </form>

    <!-- Danger Zone -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-900">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Zona pericolosa</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Eliminando questo progetto verranno eliminate anche tutte le keywords e gli articoli associati. Questa azione non puo essere annullata.
            </p>

            <form action="<?= url('/ai-content/projects/' . $project['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto? Verranno eliminate <?= $project['stats']['keywords_count'] ?? 0 ?> keywords e <?= $project['stats']['articles_total'] ?? 0 ?> articoli. Questa azione non puo essere annullata.');">
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
