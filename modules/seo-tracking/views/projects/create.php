<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna ai progetti
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Nuovo Progetto</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Crea un nuovo progetto per monitorare posizioni e traffico</p>
    </div>

    <!-- Form -->
    <form action="<?= url('/seo-tracking/project/store') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <!-- Basic Info -->
        <div class="p-6 space-y-6">
            <div>
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Informazioni base</h2>

                <div class="space-y-4">
                    <!-- Project Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Nome progetto <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="es. Il mio sito e-commerce">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Un nome descrittivo per identificare il progetto</p>
                    </div>

                    <!-- Domain -->
                    <div>
                        <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Dominio <span class="text-red-500">*</span>
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-600 text-slate-500 dark:text-slate-400 text-sm">
                                https://
                            </span>
                            <input type="text" name="domain" id="domain" required
                                   class="flex-1 px-3 py-2 rounded-r-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="www.example.com">
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Il dominio deve corrispondere esattamente a quello configurato in Google Search Console</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Dopo la creazione potrai collegare Google Search Console
            </p>
            <div class="flex gap-3">
                <a href="<?= url('/seo-tracking') ?>" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Annulla
                </a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                    Crea Progetto
                </button>
            </div>
        </div>
    </form>

    <!-- Info Card -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-start gap-3">
            <div class="h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            </div>
            <div>
                <h4 class="font-medium text-slate-900 dark:text-white">Google Search Console</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Monitora posizioni keyword, click e impressioni dalla ricerca Google</p>
            </div>
        </div>
    </div>
</div>
