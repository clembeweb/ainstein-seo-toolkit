<?php
$infoItems = match($currentType) {
    'architecture' => [
        'color' => 'blue',
        'items' => [
            'Progettare la struttura URL del sito con AI',
            'Ottenere suggerimenti per pagine, H1 e slug',
            'Esportare l\'architettura in Content Creator',
        ],
    ],
    'editorial' => [
        'color' => 'violet',
        'items' => [
            'Creare un piano editoriale mensile basato su keyword',
            'Analizzare competitor e gap di contenuto',
            'Esportare articoli in AI Content Generator',
        ],
    ],
    default => [
        'color' => 'emerald',
        'items' => [
            'Avviare una Research Guidata con clustering AI',
            'Analizzare keyword con volumi e intent',
            'Esportare i risultati in CSV',
        ],
    ],
};
?>
<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/keyword-research') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Keyword Research</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/keyword-research/projects?type=' . $currentType) ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= $typeConfig['label'] ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Nuovo Progetto</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto - <?= $typeConfig['label'] ?></h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea un nuovo progetto di <?= strtolower($typeConfig['label']) ?></p>
    </div>

    <!-- Form -->
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <form action="<?= url('/keyword-research/projects') ?>" method="POST" class="p-6 space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="<?= e($currentType) ?>">

                <!-- Nome Progetto -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nome Progetto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           placeholder="Es: Agenzia SEO Milano, E-commerce Scarpe..."
                           class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Descrizione
                    </label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Descrizione opzionale del progetto..."
                              class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>

                <!-- Location e Lingua -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="default_location" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Location
                        </label>
                        <select name="default_location" id="default_location"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="IT" selected>Italia</option>
                            <option value="US">Stati Uniti</option>
                            <option value="GB">Regno Unito</option>
                            <option value="DE">Germania</option>
                            <option value="FR">Francia</option>
                            <option value="ES">Spagna</option>
                        </select>
                    </div>
                    <div>
                        <label for="default_language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Lingua
                        </label>
                        <select name="default_language" id="default_language"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="it" selected>Italiano</option>
                            <option value="en">Inglese</option>
                            <option value="de">Tedesco</option>
                            <option value="fr">Francese</option>
                            <option value="es">Spagnolo</option>
                        </select>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-<?= $infoItems['color'] ?>-50 dark:bg-<?= $infoItems['color'] ?>-900/20 border border-<?= $infoItems['color'] ?>-200 dark:border-<?= $infoItems['color'] ?>-800 rounded-xl p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-<?= $infoItems['color'] ?>-600 dark:text-<?= $infoItems['color'] ?>-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-<?= $infoItems['color'] ?>-800 dark:text-<?= $infoItems['color'] ?>-200">Dopo la creazione potrai</h4>
                            <ul class="mt-1 text-sm text-<?= $infoItems['color'] ?>-700 dark:text-<?= $infoItems['color'] ?>-300 list-disc list-inside space-y-0.5">
                                <?php foreach ($infoItems['items'] as $item): ?>
                                <li><?= $item ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="<?= url('/keyword-research/projects?type=' . $currentType) ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                        Annulla
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
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
