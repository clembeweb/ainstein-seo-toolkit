<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        AI Content
                    </a>
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-2 text-slate-900 dark:text-white font-medium">Nuovo Progetto</span>
                </li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Crea un nuovo progetto per organizzare keywords e articoli
        </p>
    </div>

    <!-- Form -->
    <form action="<?= url('/ai-content/projects') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700" x-data="{ projectType: 'manual' }">
        <?= csrf_field() ?>
        <input type="hidden" name="type" x-model="projectType">

        <div class="p-6 space-y-6">
            <!-- Nome progetto -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Nome progetto <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    maxlength="255"
                    placeholder="Es: Blog Aziendale, Sito E-commerce..."
                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
            </div>

            <!-- Descrizione -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Descrizione
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    placeholder="Descrizione opzionale del progetto..."
                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                ></textarea>
            </div>

            <!-- Tipo Progetto -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Tipo di progetto <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Card Manuale -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="type_radio" value="manual" x-model="projectType" class="sr-only peer">
                        <div class="p-4 rounded-lg border-2 transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500">
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 peer-checked:bg-primary-100 dark:peer-checked:bg-primary-900/50">
                                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">Manuale</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Aggiungi keyword una alla volta e lancia il wizard manualmente</p>
                                </div>
                            </div>
                        </div>
                        <div class="absolute top-3 right-3 hidden peer-checked:block">
                            <svg class="w-5 h-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </label>

                    <!-- Card Automatico -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="type_radio" value="auto" x-model="projectType" class="sr-only peer">
                        <div class="p-4 rounded-lg border-2 transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500">
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0 peer-checked:bg-primary-100 dark:peer-checked:bg-primary-900/50">
                                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">Automatico</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Inserisci lista keyword e lascia fare al sistema</p>
                                </div>
                            </div>
                        </div>
                        <div class="absolute top-3 right-3 hidden peer-checked:block">
                            <svg class="w-5 h-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Lingua e Location -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="default_language" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Lingua predefinita
                    </label>
                    <select
                        id="default_language"
                        name="default_language"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="it" selected>Italiano</option>
                        <option value="en">English</option>
                        <option value="de">Deutsch</option>
                        <option value="fr">Francais</option>
                        <option value="es">Espanol</option>
                    </select>
                </div>

                <div>
                    <label for="default_location" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Location predefinita
                    </label>
                    <select
                        id="default_location"
                        name="default_location"
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="Italy" selected>Italia</option>
                        <option value="United States">Stati Uniti</option>
                        <option value="United Kingdom">Regno Unito</option>
                        <option value="Germany">Germania</option>
                        <option value="France">Francia</option>
                        <option value="Spain">Spagna</option>
                    </select>
                </div>
            </div>

            <!-- Info box -->
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium mb-1">Lingua e Location</p>
                        <p class="text-blue-600 dark:text-blue-400">
                            Queste impostazioni verranno applicate come default quando aggiungi nuove keywords.
                            Potrai modificarle per ogni keyword singolarmente.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-end gap-3">
            <a href="<?= url('/ai-content') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                Crea Progetto
            </button>
        </div>
    </form>
</div>
