<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="<?= url('/projects') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Progetti</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="<?= url('/projects/' . $project['id']) ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= htmlspecialchars($project['name']) ?></a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 dark:text-white font-medium">Impostazioni</span>
    </nav>

    <!-- Form Card -->
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Impostazioni progetto</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Modifica le informazioni del progetto.</p>
            </div>

            <form method="POST" action="<?= url('/projects/' . $project['id'] . '/settings') ?>" class="p-5 space-y-5" x-data="{ selectedColor: '<?= htmlspecialchars($project['color'] ?? '#3B82F6') ?>' }">
                <?= csrf_field() ?>

                <!-- Nome -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome progetto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           value="<?= htmlspecialchars($project['name'] ?? '') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                </div>

                <!-- Dominio -->
                <div>
                    <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Dominio
                    </label>
                    <input type="text" name="domain" id="domain"
                           placeholder="Es. esempio.it"
                           value="<?= htmlspecialchars($project['domain'] ?? '') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Opzionale. Modificare il dominio qui non aggiorna i moduli gi&agrave; attivati.</p>
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Descrizione
                    </label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Descrizione opzionale del progetto..."
                              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                </div>

                <!-- Colore -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Colore
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $colors = [
                            '#3B82F6' => 'Blu',
                            '#10B981' => 'Verde',
                            '#F59E0B' => 'Ambra',
                            '#EF4444' => 'Rosso',
                            '#8B5CF6' => 'Viola',
                            '#EC4899' => 'Rosa',
                            '#06B6D4' => 'Ciano',
                            '#F97316' => 'Arancione',
                        ];
                        foreach ($colors as $hex => $label): ?>
                        <button type="button"
                                @click="selectedColor = '<?= $hex ?>'"
                                :class="selectedColor === '<?= $hex ?>' ? 'ring-2 ring-offset-2 ring-slate-400 dark:ring-offset-slate-800' : ''"
                                class="w-8 h-8 rounded-full transition-all hover:scale-110"
                                style="background-color: <?= $hex ?>"
                                title="<?= $label ?>">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="color" :value="selectedColor">
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Salva Modifiche
                    </button>
                    <a href="<?= url('/projects/' . $project['id']) ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                        Annulla
                    </a>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="mt-8 bg-white dark:bg-slate-800 rounded-xl border border-red-200 dark:border-red-800 p-5">
            <h3 class="font-semibold text-red-600 dark:text-red-400">Zona pericolosa</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                L'eliminazione del progetto non cancella i dati dei moduli collegati, che diventeranno progetti standalone.
            </p>
            <form method="POST" action="<?= url('/projects/' . $project['id'] . '/delete') ?>"
                  x-data @submit.prevent="if(confirm('Sei sicuro di voler eliminare questo progetto? I moduli collegati non verranno eliminati.')) $el.submit()">
                <?= csrf_field() ?>
                <button type="submit" class="mt-3 inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Elimina progetto
                </button>
            </form>
        </div>
    </div>
</div>
