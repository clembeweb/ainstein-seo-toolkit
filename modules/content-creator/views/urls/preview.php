<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$statusColors = [
    'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    'scraped' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'generated' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    'approved' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
    'rejected' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
    'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
];
$statusLabels = [
    'pending' => 'In Attesa',
    'scraped' => 'Scrappata',
    'generated' => 'Generata',
    'approved' => 'Approvata',
    'rejected' => 'Rifiutata',
    'published' => 'Pubblicata',
    'error' => 'Errore',
];

$status = $url['status'] ?? 'pending';
$statusColor = $statusColors[$status] ?? $statusColors['pending'];
$statusLabel = $statusLabels[$status] ?? $status;

$hasScrapedData = !empty($url['scraped_title']) || !empty($url['scraped_h1']) || !empty($url['scraped_content']);
$hasAiContent = !empty($url['ai_content']);

$projectId = $project['id'];
$urlId = $url['id'];
$wordCount = (int) ($url['ai_word_count'] ?? 0);
?>

<div class="space-y-6" x-data="{
    editing: false,
    saving: false,
    saveSuccess: false,
    showPreview: true,
    aiH1: <?= json_encode($url['ai_h1'] ?? '') ?>,
    aiContent: <?= json_encode($url['ai_content'] ?? '') ?>,

    get wordCount() {
        const text = this.aiContent.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        return text ? text.split(' ').length : 0;
    },

    async save() {
        this.saving = true;
        this.saveSuccess = false;
        try {
            const formData = new FormData();
            formData.append('_csrf_token', '<?= csrf_token() ?>');
            formData.append('ai_h1', this.aiH1);
            formData.append('ai_content', this.aiContent);
            formData.append('ai_word_count', this.wordCount);

            const resp = await fetch('<?= url("/content-creator/projects/{$projectId}/urls/{$urlId}/update") ?>', {
                method: 'POST',
                body: formData
            });

            if (resp.ok) {
                const data = await resp.json();
                if (data.success) {
                    this.editing = false;
                    this.saveSuccess = true;
                    setTimeout(() => this.saveSuccess = false, 3000);
                }
            }
        } catch (err) {
            console.error('Errore salvataggio:', err);
        }
        this.saving = false;
    }
}">

    <!-- Back link -->
    <div>
        <a href="<?= url("/content-creator/projects/{$projectId}/results") ?>"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna ai risultati
        </a>
    </div>

    <!-- URL Info Header -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <a href="<?= e($url['url']) ?>" target="_blank" rel="noopener noreferrer"
                       class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 truncate max-w-lg block"
                       title="<?= e($url['url']) ?>">
                        <?= e($url['url']) ?>
                    </a>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </div>
                <div class="flex items-center gap-4 mt-1">
                    <?php if (!empty($url['keyword'])): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Keyword: <span class="font-medium"><?= e($url['keyword']) ?></span>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($url['intent'])): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Intent: <span class="font-medium"><?= e($url['intent']) ?></span>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($url['source_type']) && $url['source_type'] !== 'manual'): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Fonte: <span class="font-medium"><?= e($url['source_type']) ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded text-xs font-medium <?= $statusColor ?>">
                    <?= $statusLabel ?>
                </span>
                <?php if ($wordCount > 0): ?>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <?= number_format($wordCount) ?> parole
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Success notification -->
    <div x-show="saveSuccess" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Contenuto salvato con successo</span>
        </div>
    </div>

    <?php if ($hasScrapedData): ?>
    <!-- Dati Originali (collapsible) -->
    <details class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <summary class="px-5 py-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
            <span class="text-base font-semibold text-slate-900 dark:text-white inline-flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                Dati Originali (Scraping)
                <?php if ($url['scraped_word_count'] ?? 0): ?>
                <span class="text-xs font-normal text-slate-400"><?= number_format($url['scraped_word_count']) ?> parole</span>
                <?php endif; ?>
            </span>
        </summary>
        <div class="px-5 pb-5 space-y-3 border-t border-slate-200 dark:border-slate-700 pt-4">
            <?php if (!empty($url['scraped_title'])): ?>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Title</label>
                <p class="text-sm text-slate-900 dark:text-white"><?= e($url['scraped_title']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($url['scraped_h1'])): ?>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">H1</label>
                <p class="text-sm text-slate-900 dark:text-white"><?= e($url['scraped_h1']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($url['scraped_price'])): ?>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Prezzo</label>
                <p class="text-sm text-slate-900 dark:text-white font-medium"><?= e($url['scraped_price']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </details>
    <?php endif; ?>

    <!-- Contenuto AI Generato -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Contenuto Generato
                <span class="text-xs font-normal text-slate-400" x-text="wordCount + ' parole'"></span>
            </h3>
            <?php if ($hasAiContent): ?>
            <div class="flex items-center gap-2">
                <!-- Toggle Preview/Source -->
                <button @click="showPreview = !showPreview; editing = false"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <span x-text="showPreview ? 'Sorgente HTML' : 'Anteprima'"></span>
                </button>
                <!-- Edit -->
                <button @click="editing = !editing; if(editing) showPreview = false"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="editing ? 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200' : 'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400 dark:hover:bg-primary-900/50'">
                    <template x-if="!editing">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </template>
                    <span x-text="editing ? 'Annulla' : 'Modifica'"></span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($hasAiContent): ?>
        <div class="p-5 space-y-4">
            <!-- H1 -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">H1</label>
                <template x-if="!editing">
                    <p class="text-lg font-semibold text-slate-900 dark:text-white" x-text="aiH1"></p>
                </template>
                <template x-if="editing">
                    <input type="text" x-model="aiH1"
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Titolo H1...">
                </template>
            </div>

            <!-- Contenuto HTML -->
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Contenuto</label>

                <!-- Preview mode -->
                <template x-if="showPreview && !editing">
                    <div class="prose prose-sm dark:prose-invert max-w-none border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-900/50"
                         x-html="aiContent"></div>
                </template>

                <!-- Source mode (read-only) -->
                <template x-if="!showPreview && !editing">
                    <pre class="text-xs text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-lg p-4 overflow-x-auto max-h-96" x-text="aiContent"></pre>
                </template>

                <!-- Edit mode -->
                <template x-if="editing">
                    <div>
                        <textarea x-model="aiContent" rows="20"
                                  class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm font-mono focus:ring-primary-500 focus:border-primary-500"
                                  placeholder="Contenuto HTML..."></textarea>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-slate-400 dark:text-slate-500">HTML - usa tag h2, h3, p, ul, ol, strong</span>
                            <span class="text-xs font-medium text-slate-500" x-text="wordCount + ' parole'"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Save Button -->
            <template x-if="editing">
                <div class="flex items-center gap-3 pt-2">
                    <button @click="save()" :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <template x-if="!saving">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="saving">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="saving ? 'Salvataggio...' : 'Salva modifiche'"></span>
                    </button>
                    <button @click="editing = false; showPreview = true" class="px-4 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </button>
                </div>
            </template>
        </div>
        <?php else: ?>
        <div class="p-8 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-purple-400 dark:text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Contenuto non ancora generato</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Avvia la generazione AI dalla dashboard del progetto</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <?php if ($status === 'generated'): ?>
        <!-- Approve -->
        <form action="<?= url("/content-creator/projects/{$projectId}/urls/{$urlId}/approve") ?>" method="POST" class="inline">
            <?= csrf_field() ?>
            <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Approva
            </button>
        </form>

        <!-- Reject -->
        <form action="<?= url("/content-creator/projects/{$projectId}/urls/{$urlId}/reject") ?>" method="POST" class="inline">
            <?= csrf_field() ?>
            <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-orange-500 text-white text-sm font-medium hover:bg-orange-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Rifiuta
            </button>
        </form>
        <?php endif; ?>

        <!-- Delete -->
        <form action="<?= url("/content-creator/projects/{$projectId}/urls/{$urlId}/delete") ?>" method="POST" class="inline"
              onsubmit="return confirm('Sei sicuro di voler eliminare questa URL? Questa azione non può essere annullata.')">
            <?= csrf_field() ?>
            <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Elimina
            </button>
        </form>

        <div class="flex-1"></div>

        <a href="<?= url("/content-creator/projects/{$projectId}/results") ?>"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna ai risultati
        </a>
    </div>
</div>
