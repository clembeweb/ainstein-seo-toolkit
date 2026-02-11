<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/keyword-research/projects') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Progetti</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Impostazioni</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Modifica le impostazioni di "<?= e($project['name']) ?>"
        </p>
    </div>

    <!-- Stats Summary -->
    <?php if (!empty($stats)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['researches_count'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Ricerche</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['total_clusters'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Cluster</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_keywords'] ?? 0) ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Keywords</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Settings Form -->
    <form action="<?= url('/keyword-research/project/' . $project['id'] . '/settings') ?>" method="POST"
          class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700"
          x-data="{ submitting: false }" @submit="submitting = true">
        <?= csrf_field() ?>

        <div class="p-6 space-y-6">
            <!-- Nome progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome progetto <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required maxlength="255"
                       value="<?= e($project['name']) ?>"
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <!-- Descrizione -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Descrizione
                </label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Descrizione opzionale del progetto..."
                          class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                ><?= e($project['description'] ?? '') ?></textarea>
            </div>

            <!-- Location e Lingua -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="default_location" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Location predefinita
                    </label>
                    <select id="default_location" name="default_location"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="IT" <?= ($project['default_location'] ?? 'IT') === 'IT' ? 'selected' : '' ?>>Italia</option>
                        <option value="US" <?= ($project['default_location'] ?? '') === 'US' ? 'selected' : '' ?>>Stati Uniti</option>
                        <option value="GB" <?= ($project['default_location'] ?? '') === 'GB' ? 'selected' : '' ?>>Regno Unito</option>
                        <option value="DE" <?= ($project['default_location'] ?? '') === 'DE' ? 'selected' : '' ?>>Germania</option>
                        <option value="FR" <?= ($project['default_location'] ?? '') === 'FR' ? 'selected' : '' ?>>Francia</option>
                        <option value="ES" <?= ($project['default_location'] ?? '') === 'ES' ? 'selected' : '' ?>>Spagna</option>
                    </select>
                </div>
                <div>
                    <label for="default_language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Lingua predefinita
                    </label>
                    <select id="default_language" name="default_language"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="it" <?= ($project['default_language'] ?? 'it') === 'it' ? 'selected' : '' ?>>Italiano</option>
                        <option value="en" <?= ($project['default_language'] ?? '') === 'en' ? 'selected' : '' ?>>Inglese</option>
                        <option value="de" <?= ($project['default_language'] ?? '') === 'de' ? 'selected' : '' ?>>Tedesco</option>
                        <option value="fr" <?= ($project['default_language'] ?? '') === 'fr' ? 'selected' : '' ?>>Francese</option>
                        <option value="es" <?= ($project['default_language'] ?? '') === 'es' ? 'selected' : '' ?>>Spagnolo</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <a href="<?= url('/keyword-research/project/' . $project['id'] . '/research') ?>" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                Torna al progetto
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
                Eliminando questo progetto verranno eliminate anche tutte le ricerche, i cluster e le keyword associate. Questa azione non può essere annullata.
            </p>
            <form action="<?= url('/keyword-research/project/' . $project['id'] . '/delete') ?>" method="POST"
                  x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo progetto? Verranno eliminate <?= $stats['researches_count'] ?? 0 ?> ricerche e <?= $stats['total_keywords'] ?? 0 ?> keyword. Questa azione non può essere annullata.', {destructive: true}).then(() => $el.submit())">
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
