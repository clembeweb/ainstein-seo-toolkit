<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="<?= url('/projects') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Progetti</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 dark:text-white font-medium">Nuovo Progetto</span>
    </nav>

    <!-- Form Card -->
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Nuovo Progetto</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea un progetto per raggruppare i moduli di un cliente o sito web. Dopo la creazione, potrai attivare i moduli dalla dashboard del progetto.</p>
            </div>

            <form method="POST" action="<?= url('/projects') ?>" class="p-5 space-y-5" x-data="{ selectedColor: '#3B82F6' }">
                <?= csrf_field() ?>

                <!-- Nome -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome progetto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           placeholder="Es. Cliente ABC"
                           value="<?= htmlspecialchars(old('name')) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                </div>

                <!-- Dominio -->
                <div>
                    <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Dominio
                    </label>
                    <input type="text" name="domain" id="domain"
                           placeholder="Es. esempio.it"
                           value="<?= htmlspecialchars(old('domain')) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Opzionale. Il dominio viene ereditato dai moduli che lo richiedono.</p>
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Descrizione
                    </label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Descrizione opzionale del progetto..."
                              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"><?= htmlspecialchars(old('description')) ?></textarea>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crea Progetto
                    </button>
                    <a href="<?= url('/projects') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                        Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
