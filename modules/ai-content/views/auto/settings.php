<?php $currentPage = 'settings'; ?>
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Impostazioni Automazione</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Configura la pubblicazione automatica degli articoli
        </p>
    </div>

    <!-- Form -->
    <form action="<?= url('/ai-content/projects/' . $project['id'] . '/auto/settings') ?>" method="POST"
          class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700"
          x-data="settingsForm()"
          @submit="prepareSubmit">
        <?= csrf_field() ?>

        <div class="p-6 space-y-6">
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

            <!-- Cover Image Section -->
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Immagine di Copertina</h3>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" id="generate_cover" name="generate_cover" value="1"
                               <?= ($config['generate_cover'] ?? true) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary-600 border-slate-300 dark:border-slate-600 rounded focus:ring-primary-500">
                    </div>
                    <div class="ml-3">
                        <label for="generate_cover" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Genera immagine di copertina
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Genera automaticamente un'immagine di copertina per ogni articolo tramite DALL-E 3 (3 crediti extra per articolo)
                        </p>
                    </div>
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
                            <li>Estrazione SERP: 3 crediti (Standard)</li>
                            <li>Scraping fonti: 1 credito per fonte (Base)</li>
                            <li>Generazione brief: 3 crediti (Standard)</li>
                            <li>Generazione articolo: 10 crediti (Premium)</li>
                            <li>Immagine di copertina: 3 crediti (Standard, opzionale)</li>
                        </ul>
                        <p class="mt-2 text-xs">
                            Totale stimato per articolo (3 fonti): ~19 crediti (+ 3 con copertina)
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
            <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="flex items-center">
                    <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="submitting" x-cloak class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? 'Salvataggio...' : 'Salva Impostazioni'"></span>
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function settingsForm() {
    return {
        autoPublish: <?= ($config['auto_publish'] ?? false) ? 'true' : 'false' ?>,
        submitting: false,

        prepareSubmit() {
            this.submitting = true;
            return true;
        }
    }
}
</script>
