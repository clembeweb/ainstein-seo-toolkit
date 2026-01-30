<?php
/**
 * Form creazione nuovo progetto
 */
?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="<?= url('/ai-optimizer') ?>" class="hover:text-slate-700 dark:hover:text-slate-200">AI Optimizer</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 dark:text-white">Nuovo Progetto</span>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Crea Nuovo Progetto</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">Organizza le tue ottimizzazioni in progetti</p>
    </div>

    <!-- Form -->
    <form action="<?= url('/ai-optimizer/projects') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-5">
        <?= csrf_field() ?>

        <!-- Nome -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Nome Progetto <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name" required
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                   placeholder="Es: Blog Aziendale, Sito E-commerce...">
        </div>

        <!-- Dominio -->
        <div>
            <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Dominio
            </label>
            <input type="text" id="domain" name="domain"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                   placeholder="Es: miosito.it">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Opzionale. Utile per identificare il tuo sito vs competitor.</p>
        </div>

        <!-- Descrizione -->
        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Descrizione
            </label>
            <textarea id="description" name="description" rows="2"
                      class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                      placeholder="Breve descrizione del progetto..."></textarea>
        </div>

        <!-- Lingua e Location -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Lingua contenuti
                </label>
                <select id="language" name="language"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="it" selected>Italiano</option>
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="de">Deutsch</option>
                    <option value="fr">Français</option>
                </select>
            </div>

            <div>
                <label for="location_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    SERP Location
                </label>
                <select id="location_code" name="location_code"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="IT" selected>Italia</option>
                    <option value="US">USA</option>
                    <option value="UK">UK</option>
                    <option value="DE">Germania</option>
                    <option value="FR">Francia</option>
                    <option value="ES">Spagna</option>
                </select>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
            <a href="<?= url('/ai-optimizer') ?>"
               class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                Annulla
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                Crea Progetto
            </button>
        </div>
    </form>
</div>
