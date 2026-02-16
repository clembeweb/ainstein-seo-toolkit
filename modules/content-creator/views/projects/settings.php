<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$moduleSettings = \Core\ModuleLoader::getModuleSettings('content-creator');
$minWords = $aiSettings['min_words'] ?? null;
$customPrompt = $aiSettings['custom_prompt'] ?? '';
?>

<div class="space-y-6">
    <form action="<?= url('/content-creator/projects/' . $project['id'] . '/update') ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Informazioni Base -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Informazioni Base</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome progetto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="255"
                           value="<?= e($project['name']) ?>"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descrizione</label>
                    <textarea id="description" name="description" rows="2"
                              class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"><?= e($project['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL Base</label>
                    <input type="url" id="base_url" name="base_url"
                           value="<?= e($project['base_url'] ?? '') ?>"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="content_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo contenuto</label>
                        <select id="content_type" name="content_type"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="product" <?= ($project['content_type'] ?? '') === 'product' ? 'selected' : '' ?>>Prodotto</option>
                            <option value="category" <?= ($project['content_type'] ?? '') === 'category' ? 'selected' : '' ?>>Categoria</option>
                            <option value="article" <?= ($project['content_type'] ?? '') === 'article' ? 'selected' : '' ?>>Articolo/Blog</option>
                            <option value="service" <?= ($project['content_type'] ?? '') === 'service' ? 'selected' : '' ?>>Servizio</option>
                            <option value="custom" <?= ($project['content_type'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div>
                        <label for="language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lingua</label>
                        <select id="language" name="language"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <?php foreach (['it' => 'Italiano', 'en' => 'English', 'es' => 'Espanol', 'de' => 'Deutsch', 'fr' => 'Francais'] as $code => $label): ?>
                            <option value="<?= $code ?>" <?= ($project['language'] ?? 'it') === $code ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tono</label>
                        <select id="tone" name="tone"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <?php foreach (['professionale' => 'Professionale', 'informativo' => 'Informativo', 'tecnico' => 'Tecnico', 'commerciale' => 'Commerciale', 'persuasivo' => 'Persuasivo'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($project['tone'] ?? 'professionale') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connettore CMS -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Connettore CMS</h3>
            </div>
            <div class="p-6">
                <select id="connector_id" name="connector_id"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Nessun connettore</option>
                    <?php foreach ($connectors as $conn): ?>
                    <option value="<?= $conn['id'] ?>" <?= ((int)($project['connector_id'] ?? 0)) === (int)$conn['id'] ? 'selected' : '' ?>>
                        <?= e($conn['name']) ?> (<?= e($conn['type']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    <a href="<?= url('/content-creator/connectors') ?>" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">Gestisci connettori</a>
                </p>
            </div>
        </div>

        <!-- Lunghezza Contenuto -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Lunghezza Contenuto</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Override del numero minimo di parole per questo progetto (lascia vuoto per usare il default globale)</p>
            </div>
            <div class="p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Parole minime</label>
                    <input type="number" name="min_words" value="<?= $minWords !== null ? (int) $minWords : '' ?>" min="100" max="5000"
                           placeholder="Default in base al tipo contenuto"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Default: Prodotto 300, Categoria 400, Servizio 500, Articolo 800, Custom 300
                    </p>
                </div>
            </div>
        </div>

        <!-- Prompt Personalizzato -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Prompt Personalizzato</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Istruzioni aggiuntive per l'AI (opzionale)</p>
            </div>
            <div class="p-6">
                <textarea name="custom_prompt" rows="4"
                          class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Es. Usa un linguaggio tecnico per professionisti del settore. Includi sempre il brand name nel meta title."><?= e($customPrompt) ?></textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Queste istruzioni verranno aggiunte al prompt base dell'AI per ogni URL di questo progetto.</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button type="button" onclick="document.getElementById('delete-modal').classList.remove('hidden')"
                    class="px-4 py-2 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                Elimina progetto
            </button>
            <button type="submit"
                    class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Salva Impostazioni
            </button>
        </div>
    </form>
</div>

<!-- Delete Modal -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('delete-modal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Elimina Progetto</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Sei sicuro di voler eliminare <strong><?= e($project['name']) ?></strong>? Questa azione eliminerà anche tutte le URL e i dati generati. Non è reversibile.
            </p>
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('delete-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                    Annulla
                </button>
                <form action="<?= url('/content-creator/projects/' . $project['id'] . '/delete') ?>" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                        Elimina definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
