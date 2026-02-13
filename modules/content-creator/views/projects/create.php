<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="<?= url('/content-creator') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <form action="<?= url('/content-creator/projects') ?>" method="POST" class="p-6 space-y-6">
            <?= csrf_field() ?>

            <!-- Nome -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome progetto <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required maxlength="255"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                       placeholder="Es. E-commerce Scarpe - Meta Tags">
            </div>

            <!-- Descrizione -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Descrizione
                </label>
                <textarea id="description" name="description" rows="2"
                          class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Descrizione opzionale del progetto"></textarea>
            </div>

            <!-- URL Base -->
            <div>
                <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    URL Base del sito
                </label>
                <input type="url" id="base_url" name="base_url"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                       placeholder="https://www.example.com">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Opzionale. Usato per discovery automatico delle URL.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Tipo Contenuto -->
                <div>
                    <label for="content_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Tipo contenuto
                    </label>
                    <select id="content_type" name="content_type"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="product">Prodotto</option>
                        <option value="category">Categoria</option>
                        <option value="article">Articolo/Blog</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <!-- Lingua -->
                <div>
                    <label for="language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Lingua
                    </label>
                    <select id="language" name="language"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="it">Italiano</option>
                        <option value="en">English</option>
                        <option value="es">Espanol</option>
                        <option value="de">Deutsch</option>
                        <option value="fr">Francais</option>
                    </select>
                </div>

                <!-- Tono -->
                <div>
                    <label for="tone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Tono
                    </label>
                    <select id="tone" name="tone"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="professionale">Professionale</option>
                        <option value="informativo">Informativo</option>
                        <option value="tecnico">Tecnico</option>
                        <option value="commerciale">Commerciale</option>
                    </select>
                </div>
            </div>

            <!-- Connettore CMS -->
            <?php if (!empty($connectors)): ?>
            <div>
                <label for="connector_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Connettore CMS (opzionale)
                </label>
                <select id="connector_id" name="connector_id"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Nessun connettore</option>
                    <?php foreach ($connectors as $conn): ?>
                    <option value="<?= $conn['id'] ?>"><?= e($conn['name']) ?> (<?= e($conn['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Seleziona un connettore per pubblicare direttamente sul CMS.</p>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/content-creator') ?>"
                   class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Annulla
                </a>
                <button type="submit"
                        class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    Crea Progetto
                </button>
            </div>
        </form>
    </div>
</div>
