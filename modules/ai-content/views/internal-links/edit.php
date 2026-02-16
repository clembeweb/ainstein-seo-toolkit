<?php $currentPage = 'internal-links'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-2xl mx-auto">

    <!-- Breadcrumb -->
    <div class="mb-6">
        <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna al Pool Link
        </a>
    </div>

    <!-- Edit Form -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Modifica Link</h2>
            <a href="<?= e($link['url']) ?>" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline break-all">
                <?= e($link['url']) ?>
            </a>
        </div>

        <form action="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/' . $link['id'] . '/update') ?>" method="POST" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Titolo
                </label>
                <input type="text" id="title" name="title" value="<?= e($link['title'] ?? '') ?>"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                       placeholder="Titolo della pagina">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Il titolo usato dall'AI per capire il contesto del link</p>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Descrizione
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                          placeholder="Meta description della pagina"><?= e($link['description'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">La descrizione aiuta l'AI a inserire il link nel contesto giusto</p>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?= $link['is_active'] ? 'checked' : '' ?>
                       class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                <label for="is_active" class="ml-2 text-sm text-slate-700 dark:text-slate-300">
                    Link attivo (incluso nel pool per la generazione articoli)
                </label>
            </div>

            <!-- Status Info -->
            <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Stato Scraping</dt>
                        <dd class="font-medium text-slate-900 dark:text-white">
                            <?php
                            $statusClass = match($link['scrape_status']) {
                                'pending' => 'text-blue-600 dark:text-blue-400',
                                'completed' => 'text-emerald-600 dark:text-emerald-400',
                                'error' => 'text-red-600 dark:text-red-400',
                                default => 'text-slate-600 dark:text-slate-400'
                            };
                            $statusLabel = match($link['scrape_status']) {
                                'pending' => 'In attesa',
                                'completed' => 'Completato',
                                'error' => 'Errore',
                                default => $link['scrape_status']
                            };
                            ?>
                            <span class="<?= $statusClass ?>"><?= $statusLabel ?></span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Fonte Sitemap</dt>
                        <dd class="font-medium text-slate-900 dark:text-white truncate" title="<?= e($link['sitemap_source'] ?? '-') ?>">
                            <?= e($link['sitemap_source'] ?? '-') ?>
                        </dd>
                    </div>
                    <?php if ($link['scraped_at']): ?>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Ultimo Scraping</dt>
                        <dd class="font-medium text-slate-900 dark:text-white">
                            <?= date('d/m/Y H:i', strtotime($link['scraped_at'])) ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                    <?php if ($link['scrape_error']): ?>
                    <div class="col-span-2">
                        <dt class="text-slate-500 dark:text-slate-400">Errore</dt>
                        <dd class="font-medium text-red-600 dark:text-red-400">
                            <?= e($link['scrape_error']) ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="confirmDelete()"
                        class="px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors">
                    Elimina Link
                </button>
                <div class="flex gap-3">
                    <a href="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links') ?>"
                       class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                        Salva Modifiche
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation -->
<div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-slate-900/50" onclick="closeDeleteModal()"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Conferma eliminazione</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Sei sicuro di voler eliminare questo link dal pool?
            </p>
            <form action="<?= url('/ai-content/projects/' . $project['id'] . '/internal-links/' . $link['id'] . '/delete') ?>" method="POST" class="mt-6">
                <?= csrf_field() ?>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                        Annulla
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">
                        Elimina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
