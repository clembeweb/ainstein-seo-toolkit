<div class="max-w-2xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
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
                <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto') ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <?= e($project['name']) ?>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Impostazioni</span>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impostazioni Automazione</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Configura la generazione automatica degli articoli
        </p>
    </div>

    <!-- Form -->
    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/settings') ?>" method="POST"
          class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700"
          x-data="settingsForm()"
          @submit="prepareSubmit">
        <?= csrf_field() ?>

        <div class="p-6 space-y-6">
            <!-- Scheduling Section -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Scheduling</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Articoli per giorno -->
                    <div>
                        <label for="articles_per_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Articoli per giorno
                        </label>
                        <select id="articles_per_day" name="articles_per_day"
                                x-model="articlesPerDay"
                                @change="updateTimeInputs()"
                                class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= ($config['articles_per_day'] ?? 1) == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Quanti articoli generare al giorno
                        </p>
                    </div>

                    <!-- Fonti SERP -->
                    <div>
                        <label for="auto_select_sources" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Fonti da analizzare
                        </label>
                        <select id="auto_select_sources" name="auto_select_sources"
                                class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= ($config['auto_select_sources'] ?? 3) == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Quante fonti SERP analizzare per ogni articolo
                        </p>
                    </div>
                </div>

                <!-- Orari pubblicazione - Dynamic Time Inputs -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Orari di generazione
                    </label>
                    <input type="hidden" name="publish_times" :value="publishTimesString">

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                        <template x-for="(time, index) in publishTimes" :key="index">
                            <div class="relative">
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1" x-text="'Articolo ' + (index + 1)"></label>
                                <input type="time"
                                       x-model="publishTimes[index]"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            </div>
                        </template>
                    </div>

                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Imposta l'orario di generazione per ogni articolo giornaliero
                    </p>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-700">

            <!-- WordPress Section -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Pubblicazione WordPress</h3>

                <!-- Auto Publish Toggle -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" id="auto_publish" name="auto_publish" value="1"
                               x-model="autoPublish"
                               <?= ($config['auto_publish'] ?? false) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary-600 border-slate-300 dark:border-slate-600 rounded focus:ring-primary-500">
                    </div>
                    <div class="ml-3">
                        <label for="auto_publish" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Pubblica automaticamente su WordPress
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Gli articoli generati verranno pubblicati automaticamente sul sito selezionato
                        </p>
                    </div>
                </div>

                <!-- WordPress Site Select -->
                <div class="mt-4" x-show="autoPublish" x-transition>
                    <label for="wp_site_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Sito WordPress
                    </label>
                    <?php if (empty($wpSites)): ?>
                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-amber-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                Nessun sito WordPress configurato.
                                <a href="<?= url('/ai-content/wordpress') ?>" class="underline hover:no-underline">Aggiungi un sito</a>
                            </p>
                        </div>
                    </div>
                    <?php else: ?>
                    <select id="wp_site_id" name="wp_site_id"
                            class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Seleziona un sito...</option>
                        <?php foreach ($wpSites as $site): ?>
                        <option value="<?= $site['id'] ?>" <?= ($config['wp_site_id'] ?? '') == $site['id'] ? 'selected' : '' ?>>
                            <?= e($site['name']) ?> (<?= e($site['url']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium mb-1">Costi crediti</p>
                        <ul class="text-blue-600 dark:text-blue-400 space-y-1">
                            <li>Estrazione SERP: 3 crediti</li>
                            <li>Scraping fonti: 1 credito per fonte</li>
                            <li>Generazione brief: 5 crediti</li>
                            <li>Generazione articolo: 10 crediti</li>
                        </ul>
                        <p class="mt-2 text-xs">
                            Totale stimato per articolo (3 fonti): ~21 crediti
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-end gap-3">
            <a href="<?= url('/ai-content/projects/' . $project['id'] . '/auto') ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                Annulla
            </a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salva Impostazioni
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function settingsForm() {
    // Default time slots spread throughout the day
    const defaultTimes = ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00', '21:00', '08:00', '10:00', '12:00'];

    // Initial config values
    const initialTimes = <?= json_encode($config['publish_times'] ?? ['09:00']) ?>;
    const initialArticlesPerDay = <?= (int) ($config['articles_per_day'] ?? 1) ?>;

    // Prepare publish times array matching articles_per_day
    const preparedTimes = [];
    for (let i = 0; i < initialArticlesPerDay; i++) {
        preparedTimes.push(initialTimes[i] || defaultTimes[i] || '09:00');
    }

    return {
        autoPublish: <?= ($config['auto_publish'] ?? false) ? 'true' : 'false' ?>,
        articlesPerDay: initialArticlesPerDay,
        publishTimes: preparedTimes,

        get publishTimesString() {
            return this.publishTimes.join(', ');
        },

        updateTimeInputs() {
            const count = parseInt(this.articlesPerDay);
            const currentTimes = [...this.publishTimes];

            // Resize array to match articles_per_day
            if (count > currentTimes.length) {
                // Add more times
                for (let i = currentTimes.length; i < count; i++) {
                    currentTimes.push(defaultTimes[i] || '09:00');
                }
            } else if (count < currentTimes.length) {
                // Remove excess times
                currentTimes.length = count;
            }

            this.publishTimes = currentTimes;
        },

        prepareSubmit() {
            // Ensure publish_times is properly formatted before submit
            // Already handled by the :value binding
            return true;
        }
    }
}
</script>
