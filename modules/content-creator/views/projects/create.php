<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/content-creator') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Content Creator</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/content-creator/projects') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Progetti</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Nuovo Progetto</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea un nuovo progetto per generare contenuti pagina</p>
    </div>

    <!-- Form -->
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <form action="<?= url('/content-creator/projects') ?>" method="POST" class="p-6 space-y-6">
                <?= csrf_field() ?>

                <!-- Nome -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome progetto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="255"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Es. E-commerce Scarpe - Contenuti Pagina">
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Descrizione
                    </label>
                    <textarea id="description" name="description" rows="2"
                              class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                              placeholder="Descrizione opzionale del progetto"></textarea>
                </div>

                <!-- URL Base -->
                <div>
                    <label for="base_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        URL Base del sito
                    </label>
                    <input type="url" id="base_url" name="base_url"
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://www.example.com">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Opzionale. Usato per discovery automatico delle URL.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tipo Contenuto -->
                    <div>
                        <label for="content_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Tipo contenuto
                        </label>
                        <select id="content_type" name="content_type"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="product">Prodotto</option>
                            <option value="category">Categoria</option>
                            <option value="article">Articolo/Blog</option>
                            <option value="service">Servizio</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <!-- Lingua -->
                    <div>
                        <label for="language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Lingua
                        </label>
                        <select id="language" name="language"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="it">Italiano</option>
                            <option value="en">English</option>
                            <option value="es">Espanol</option>
                            <option value="de">Deutsch</option>
                            <option value="fr">Francais</option>
                        </select>
                    </div>
                </div>

                <!-- Tono -->
                <div>
                    <label for="tone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Tono
                    </label>
                    <select id="tone" name="tone"
                            class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="professionale">Professionale</option>
                        <option value="informativo">Informativo</option>
                        <option value="tecnico">Tecnico</option>
                        <option value="commerciale">Commerciale</option>
                    </select>
                </div>

                <!-- Connettore CMS -->
                <?php if (!empty($connectors)): ?>
                <div>
                    <label for="connector_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Connettore CMS (opzionale)
                    </label>
                    <select id="connector_id" name="connector_id"
                            class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Nessun connettore</option>
                        <?php foreach ($connectors as $conn): ?>
                        <option value="<?= $conn['id'] ?>"><?= e($conn['name']) ?> (<?= e($conn['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Seleziona un connettore per pubblicare direttamente sul CMS.</p>
                </div>
                <?php endif; ?>

                <!-- Info Box -->
                <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-xl p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-teal-600 dark:text-teal-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-teal-800 dark:text-teal-200">Dopo la creazione potrai</h4>
                            <ul class="mt-1 text-sm text-teal-700 dark:text-teal-300 list-disc list-inside space-y-0.5">
                                <li>Importare URL da sitemap, CSV o connettore CMS</li>
                                <li>Generare contenuti HTML completi con AI</li>
                                <li>Pubblicare direttamente sul CMS collegato</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="<?= url('/content-creator') ?>"
                       class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                        Annulla
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crea Progetto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
