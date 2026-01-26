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
    <form action="<?= url('/seo-tracking/project/store') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
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

            <!-- Notifications -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Notifiche</h2>

                <div>
                    <label for="notification_emails" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Email per notifiche
                    </label>
                    <input type="text" name="notification_emails" id="notification_emails"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="email1@example.com, email2@example.com">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Email separate da virgola per ricevere alert e report</p>
                </div>
            </div>

            <!-- AI Reports -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Report AI</h2>

                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="ai_reports_enabled" value="1" checked
                               class="mt-1 h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="block text-sm font-medium text-slate-900 dark:text-white">Abilita report AI</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Genera automaticamente analisi settimanali e mensili con Claude AI</span>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="weekly_report_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Giorno report settimanale
                            </label>
                            <select name="weekly_report_day" id="weekly_report_day"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="1">Lunedi</option>
                                <option value="2">Martedi</option>
                                <option value="3">Mercoledi</option>
                                <option value="4">Giovedi</option>
                                <option value="5">Venerdi</option>
                                <option value="6">Sabato</option>
                                <option value="0">Domenica</option>
                            </select>
                        </div>
                        <div>
                            <label for="monthly_report_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Giorno report mensile
                            </label>
                            <select name="monthly_report_day" id="monthly_report_day"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <?php for ($i = 1; $i <= 28; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
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

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-slate-900 dark:text-white">Google Search Console</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Monitora posizioni keyword, click e impressioni dalla ricerca Google</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-slate-900 dark:text-white">Google Analytics 4</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Traccia sessioni, conversioni e revenue attribuito al traffico organico</p>
                </div>
            </div>
        </div>
    </div>
</div>
