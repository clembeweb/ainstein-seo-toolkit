<?php
/**
 * Preview Meta Tag con simulatore SERP
 * Permette di visualizzare, modificare e approvare i meta tag generati
 */
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-4xl mx-auto space-y-6" x-data="metaTagPreview()">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    Meta Tags
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">Preview</span>
            </li>
        </ol>
    </nav>

    <!-- URL Info -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-start justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white truncate">
                    <?= e($metaTag['original_title'] ?: 'Pagina senza titolo') ?>
                </h2>
                <a href="<?= e($metaTag['url']) ?>" target="_blank" rel="noopener"
                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 flex items-center gap-1">
                    <?= e($metaTag['url']) ?>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
            <span class="flex-shrink-0 px-3 py-1 text-sm font-medium rounded-full
                <?php
                switch ($metaTag['status']) {
                    case 'pending':
                        echo 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';
                        break;
                    case 'scraped':
                        echo 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                        break;
                    case 'generated':
                        echo 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                        break;
                    case 'approved':
                        echo 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                        break;
                    case 'published':
                        echo 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300';
                        break;
                    case 'error':
                        echo 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                        break;
                }
                ?>">
                <?= ucfirst($metaTag['status']) ?>
            </span>
        </div>
    </div>

    <!-- SERP Preview -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-4">Anteprima SERP Google</h3>

        <div class="bg-white border border-slate-200 rounded-lg p-4 max-w-2xl">
            <!-- Google-style result -->
            <div class="space-y-1">
                <p class="text-xs text-green-700 truncate"><?= e(parse_url($metaTag['url'], PHP_URL_HOST)) ?></p>
                <h3 class="text-xl text-blue-800 hover:underline cursor-pointer font-normal leading-tight"
                    x-text="title || 'Titolo non impostato'">
                </h3>
                <p class="text-sm text-slate-600 leading-relaxed line-clamp-2"
                   x-text="description || 'Descrizione non impostata'">
                </p>
            </div>
        </div>

        <!-- Character counters -->
        <div class="mt-4 flex gap-6">
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">Title:</span>
                <span class="text-xs font-medium"
                      :class="titleLength <= 60 ? 'text-green-600' : 'text-red-600'"
                      x-text="titleLength + '/60'"></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">Description:</span>
                <span class="text-xs font-medium"
                      :class="descLength <= 155 ? 'text-green-600' : 'text-red-600'"
                      x-text="descLength + '/155'"></span>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-4">Meta Tag Generati</h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Meta Title
                    <span class="text-xs text-slate-400 font-normal">(max 60 caratteri)</span>
                </label>
                <input type="text"
                       x-model="title"
                       maxlength="70"
                       placeholder="Inserisci il meta title..."
                       class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <div class="mt-1 flex justify-between text-xs">
                    <span class="text-slate-400">Attuale: <?= e($metaTag['current_meta_title'] ?: '-') ?></span>
                    <span :class="titleLength <= 60 ? 'text-green-600' : 'text-red-600'" x-text="titleLength + ' caratteri'"></span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Meta Description
                    <span class="text-xs text-slate-400 font-normal">(max 155 caratteri)</span>
                </label>
                <textarea
                    x-model="description"
                    rows="3"
                    maxlength="200"
                    placeholder="Inserisci la meta description..."
                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                <div class="mt-1 flex justify-between text-xs">
                    <span class="text-slate-400 truncate max-w-md">Attuale: <?= e($metaTag['current_meta_desc'] ?: '-') ?></span>
                    <span :class="descLength <= 155 ? 'text-green-600' : 'text-red-600'" x-text="descLength + ' caratteri'"></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="saveChanges()"
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50">
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Salva Modifiche
                </button>

                <?php if (in_array($metaTag['status'], ['generated', 'error'])): ?>
                <button type="button"
                        @click="approveAndSave()"
                        :disabled="loading || !title || !description"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50">
                    Approva
                </button>
                <?php endif; ?>

                <?php if ($metaTag['status'] === 'approved' && $metaTag['wp_post_id']): ?>
                <button type="button"
                        @click="publishToWp()"
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition-colors disabled:opacity-50">
                    Pubblica su WP
                </button>
                <?php endif; ?>
            </div>

            <button type="button"
                    @click="deleteMetaTag()"
                    class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                Elimina
            </button>
        </div>
    </div>

    <!-- Page Content Preview -->
    <?php if ($metaTag['scraped_content']): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300">Contenuto Pagina (estratto)</h3>
            <span class="text-xs text-slate-400"><?= $metaTag['scraped_word_count'] ?> parole</span>
        </div>
        <div class="prose prose-sm dark:prose-invert max-w-none max-h-60 overflow-y-auto">
            <p class="text-slate-600 dark:text-slate-400 whitespace-pre-wrap"><?= e(mb_substr($metaTag['scraped_content'], 0, 2000)) ?><?= strlen($metaTag['scraped_content']) > 2000 ? '...' : '' ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Errors -->
    <?php if ($metaTag['scrape_error'] || $metaTag['generation_error'] || $metaTag['publish_error']): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-red-700 dark:text-red-300 mb-2">Errori</h3>
        <ul class="text-sm text-red-600 dark:text-red-400 space-y-1">
            <?php if ($metaTag['scrape_error']): ?>
            <li>Scrape: <?= e($metaTag['scrape_error']) ?></li>
            <?php endif; ?>
            <?php if ($metaTag['generation_error']): ?>
            <li>Generazione: <?= e($metaTag['generation_error']) ?></li>
            <?php endif; ?>
            <?php if ($metaTag['publish_error']): ?>
            <li>Pubblicazione: <?= e($metaTag['publish_error']) ?></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Result Message -->
    <div x-show="message" x-cloak
         :class="success ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'"
         class="rounded-lg border p-4">
        <div class="flex items-center gap-3">
            <template x-if="success">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </template>
            <template x-if="!success">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </template>
            <span :class="success ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" x-text="message"></span>
        </div>
    </div>
</div>

<script>
function metaTagPreview() {
    return {
        title: <?= json_encode($metaTag['generated_title'] ?? '') ?>,
        description: <?= json_encode($metaTag['generated_desc'] ?? '') ?>,
        loading: false,
        message: '',
        success: false,

        get titleLength() {
            return this.title.length;
        },

        get descLength() {
            return this.description.length;
        },

        async saveChanges() {
            this.loading = true;
            this.message = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                formData.append('generated_title', this.title);
                formData.append('generated_desc', this.description);

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$metaTag['id']}/update") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.message = data.success ? 'Salvato!' : data.error;
                this.success = data.success;
            } catch (error) {
                this.message = 'Errore di connessione';
                this.success = false;
            }

            this.loading = false;
        },

        async approveAndSave() {
            await this.saveChanges();
            if (!this.success) return;

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$metaTag['id']}/approve") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.message = data.success ? 'Approvato!' : data.error;
                this.success = data.success;

                if (data.success) {
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                this.message = 'Errore di connessione';
                this.success = false;
            }

            this.loading = false;
        },

        async publishToWp() {
            this.loading = true;
            this.message = '';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$metaTag['id']}/publish") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                this.message = data.success ? 'Pubblicato su WordPress!' : data.error;
                this.success = data.success;

                if (data.success) {
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                this.message = 'Errore di connessione';
                this.success = false;
            }

            this.loading = false;
        },

        async deleteMetaTag() {
            try {
                await window.ainstein.confirm('Eliminare questo meta tag?', {destructive: true});
            } catch (e) { return; }

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$metaTag['id']}/delete") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>';
                } else {
                    this.message = data.error || 'Errore durante l\'eliminazione';
                    this.success = false;
                }
            } catch (error) {
                this.message = 'Errore di connessione';
                this.success = false;
            }

            this.loading = false;
        }
    }
}
</script>
