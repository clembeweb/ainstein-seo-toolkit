<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/internal-links') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Internal Links</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/internal-links/project/' . $project['id']) ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Impostazioni</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni Progetto</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Configurazione</h3>
                </div>
                <form action="<?= url('/internal-links/project/' . $project['id'] . '/update') ?>" method="POST" class="p-6 space-y-6">
                    <?= csrf_field() ?>

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome Progetto</label>
                        <input type="text" name="name" id="name" value="<?= e($project['name']) ?>" required
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL Base</label>
                        <input type="url" name="base_url" id="base_url" value="<?= e($project['base_url']) ?>" required
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label for="css_selector" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CSS Selector</label>
                        <input type="text" name="css_selector" id="css_selector" value="<?= e($project['css_selector'] ?? '') ?>"
                               placeholder="Es: article, .content, main"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lascia vuoto per usare tutto il body</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="scrape_delay" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Delay (ms)</label>
                            <input type="number" name="scrape_delay" id="scrape_delay" value="<?= $project['scrape_delay'] ?>" min="100"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Stato</label>
                            <select name="status" id="status"
                                    class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="active" <?= $project['status'] === 'active' ? 'selected' : '' ?>>Attivo</option>
                                <option value="paused" <?= $project['status'] === 'paused' ? 'selected' : '' ?>>In pausa</option>
                                <option value="archived" <?= $project['status'] === 'archived' ? 'selected' : '' ?>>Archiviato</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="user_agent" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">User Agent</label>
                        <input type="text" name="user_agent" id="user_agent" value="<?= e($project['user_agent'] ?? '') ?>"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                            Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Info -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Info Progetto</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">ID</dt>
                        <dd class="text-sm font-mono text-slate-900 dark:text-white">#<?= $project['id'] ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Creato</dt>
                        <dd class="text-sm text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($project['created_at'])) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">URL totali</dt>
                        <dd class="text-sm text-slate-900 dark:text-white"><?= number_format($project['total_urls']) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Link totali</dt>
                        <dd class="text-sm text-slate-900 dark:text-white"><?= number_format($project['total_links']) ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Danger Zone -->
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-6">
                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2">Zona Pericolosa</h3>
                <p class="text-sm text-red-600 dark:text-red-400 mb-4">L'eliminazione del progetto e irreversibile. Tutti i dati verranno persi.</p>
                <form action="<?= url('/internal-links/project/' . $project['id'] . '/delete') ?>" method="POST"
                      x-data @submit.prevent="window.ainstein.confirm('Sei sicuro di voler eliminare questo progetto? Questa azione e irreversibile.', {destructive: true, buttonText: 'Elimina Progetto'}).then(() => $el.submit())">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Elimina Progetto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
