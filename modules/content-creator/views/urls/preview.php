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

$hasScrapedData = !empty($url['scraped_title']) || !empty($url['scraped_h1']) || !empty($url['scraped_meta_title']) || !empty($url['scraped_meta_description']);
$hasAiContent = !empty($url['ai_meta_title']) || !empty($url['ai_meta_description']) || !empty($url['ai_page_description']);

$projectId = $project['id'];
$urlId = $url['id'];
?>

<div class="space-y-6" x-data="{
    editing: false,
    saving: false,
    saveSuccess: false,
    metaTitle: '<?= addslashes(e($url['ai_meta_title'] ?? '')) ?>',
    metaDescription: '<?= addslashes(e($url['ai_meta_description'] ?? '')) ?>',
    pageDescription: '<?= addslashes(e($url['ai_page_description'] ?? '')) ?>',

    async save() {
        this.saving = true;
        this.saveSuccess = false;
        try {
            const formData = new FormData();
            formData.append('_csrf_token', '<?= csrf_token() ?>');
            formData.append('ai_meta_title', this.metaTitle);
            formData.append('ai_meta_description', this.metaDescription);
            formData.append('ai_page_description', this.pageDescription);

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
    },

    metaTitleColor() {
        const len = this.metaTitle.length;
        if (len === 0) return 'text-slate-400 dark:text-slate-500';
        if (len >= 50 && len <= 60) return 'text-emerald-600 dark:text-emerald-400';
        if ((len >= 40 && len < 50) || (len > 60 && len <= 65)) return 'text-amber-600 dark:text-amber-400';
        return 'text-red-600 dark:text-red-400';
    },

    metaDescColor() {
        const len = this.metaDescription.length;
        if (len === 0) return 'text-slate-400 dark:text-slate-500';
        if (len >= 140 && len <= 160) return 'text-emerald-600 dark:text-emerald-400';
        if ((len >= 120 && len < 140) || (len > 160 && len <= 170)) return 'text-amber-600 dark:text-amber-400';
        return 'text-red-600 dark:text-red-400';
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
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
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
                <?php if (!empty($url['keyword'])): ?>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Keyword: <span class="font-medium"><?= e($url['keyword']) ?></span>
                </p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded text-xs font-medium <?= $statusColor ?>">
                    <?= $statusLabel ?>
                </span>
                <?php if (!empty($url['scraped_at'])): ?>
                <span class="text-xs text-slate-500 dark:text-slate-400" title="Ultimo scraping">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= date('d/m/Y H:i', strtotime($url['scraped_at'])) ?>
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

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Left Column: Dati Originali -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    Dati Originali
                </h3>
            </div>

            <?php if ($hasScrapedData): ?>
            <div class="p-5 space-y-4">
                <!-- Scraped Title -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Title</label>
                    <p class="text-sm text-slate-900 dark:text-white"><?= e($url['scraped_title'] ?? '-') ?></p>
                </div>

                <!-- Scraped H1 -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">H1</label>
                    <p class="text-sm text-slate-900 dark:text-white"><?= e($url['scraped_h1'] ?? '-') ?></p>
                </div>

                <!-- Scraped Meta Title -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Meta Title</label>
                    <p class="text-sm text-slate-900 dark:text-white"><?= e($url['scraped_meta_title'] ?? '-') ?></p>
                    <?php if (!empty($url['scraped_meta_title'])): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= mb_strlen($url['scraped_meta_title']) ?> caratteri</span>
                    <?php endif; ?>
                </div>

                <!-- Scraped Meta Description -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Meta Description</label>
                    <p class="text-sm text-slate-900 dark:text-white"><?= e($url['scraped_meta_description'] ?? '-') ?></p>
                    <?php if (!empty($url['scraped_meta_description'])): ?>
                    <span class="text-xs text-slate-400 dark:text-slate-500"><?= mb_strlen($url['scraped_meta_description']) ?> caratteri</span>
                    <?php endif; ?>
                </div>

                <!-- Scraped Price (if any) -->
                <?php if (!empty($url['scraped_price'])): ?>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Prezzo</label>
                    <p class="text-sm text-slate-900 dark:text-white font-medium"><?= e($url['scraped_price']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Scraped Word Count -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Conteggio Parole</label>
                    <p class="text-sm text-slate-900 dark:text-white"><?= number_format($url['scraped_word_count'] ?? 0) ?> parole</p>
                </div>
            </div>
            <?php else: ?>
            <div class="p-8 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                    <svg class="h-6 w-6 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Non ancora scrappata</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Avvia lo scraping dalla dashboard del progetto</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Contenuto AI -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Contenuto AI
                </h3>
                <?php if ($hasAiContent): ?>
                <button @click="editing = !editing"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="editing ? 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200' : 'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400 dark:hover:bg-primary-900/50'">
                    <template x-if="!editing">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </template>
                    <span x-text="editing ? 'Annulla' : 'Modifica'"></span>
                </button>
                <?php endif; ?>
            </div>

            <?php if ($hasAiContent): ?>
            <div class="p-5 space-y-4">
                <!-- AI Meta Title -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Meta Title</label>
                    <template x-if="!editing">
                        <div>
                            <p class="text-sm text-slate-900 dark:text-white"><?= e($url['ai_meta_title'] ?? '-') ?></p>
                            <span class="text-xs text-slate-400 dark:text-slate-500"><?= mb_strlen($url['ai_meta_title'] ?? '') ?> caratteri</span>
                        </div>
                    </template>
                    <template x-if="editing">
                        <div>
                            <input type="text" x-model="metaTitle"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="Meta title...">
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-slate-400 dark:text-slate-500">Ottimale: 50-60 caratteri</span>
                                <span class="text-xs font-medium" :class="metaTitleColor()" x-text="metaTitle.length + ' caratteri'"></span>
                            </div>
                            <!-- Visual bar -->
                            <div class="mt-1 h-1 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300"
                                     :class="metaTitle.length >= 50 && metaTitle.length <= 60 ? 'bg-emerald-500' : (metaTitle.length > 60 ? 'bg-red-500' : 'bg-amber-500')"
                                     :style="'width: ' + Math.min(100, (metaTitle.length / 60) * 100) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- AI Meta Description -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Meta Description</label>
                    <template x-if="!editing">
                        <div>
                            <p class="text-sm text-slate-900 dark:text-white"><?= e($url['ai_meta_description'] ?? '-') ?></p>
                            <span class="text-xs text-slate-400 dark:text-slate-500"><?= mb_strlen($url['ai_meta_description'] ?? '') ?> caratteri</span>
                        </div>
                    </template>
                    <template x-if="editing">
                        <div>
                            <textarea x-model="metaDescription" rows="3"
                                      class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500"
                                      placeholder="Meta description..."></textarea>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-slate-400 dark:text-slate-500">Ottimale: 140-160 caratteri</span>
                                <span class="text-xs font-medium" :class="metaDescColor()" x-text="metaDescription.length + ' caratteri'"></span>
                            </div>
                            <!-- Visual bar -->
                            <div class="mt-1 h-1 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300"
                                     :class="metaDescription.length >= 140 && metaDescription.length <= 160 ? 'bg-emerald-500' : (metaDescription.length > 160 ? 'bg-red-500' : 'bg-amber-500')"
                                     :style="'width: ' + Math.min(100, (metaDescription.length / 160) * 100) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- AI Page Description -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Descrizione Pagina</label>
                    <template x-if="!editing">
                        <div>
                            <p class="text-sm text-slate-900 dark:text-white whitespace-pre-line"><?= e($url['ai_page_description'] ?? '-') ?></p>
                        </div>
                    </template>
                    <template x-if="editing">
                        <div>
                            <textarea x-model="pageDescription" rows="6"
                                      class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500"
                                      placeholder="Descrizione della pagina..."></textarea>
                            <div class="flex justify-end mt-1">
                                <span class="text-xs text-slate-400 dark:text-slate-500" x-text="pageDescription.length + ' caratteri'"></span>
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
                        <button @click="editing = false" class="px-4 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
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
                <p class="text-sm text-slate-500 dark:text-slate-400">Non ancora generata</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Avvia la generazione AI dalla dashboard del progetto</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SERP Preview Mockup -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Anteprima SERP
            </h3>
        </div>
        <div class="p-5">
            <div class="max-w-2xl">
                <?php
                $serpTitle = $url['ai_meta_title'] ?? $url['scraped_meta_title'] ?? $url['scraped_title'] ?? 'Titolo non disponibile';
                $serpDescription = $url['ai_meta_description'] ?? $url['scraped_meta_description'] ?? 'Descrizione non disponibile';
                $serpUrl = $url['url'] ?? '';

                // Truncate URL for display
                $parsedUrl = parse_url($serpUrl);
                $displayUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
                $displayPath = $parsedUrl['path'] ?? '';
                if (strlen($displayPath) > 50) {
                    $displayPath = substr($displayPath, 0, 47) . '...';
                }
                $displayUrl .= $displayPath;
                ?>

                <!-- Google-style SERP result -->
                <div class="space-y-1">
                    <!-- URL breadcrumb -->
                    <div class="flex items-center gap-1.5">
                        <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-600 dark:text-slate-400"><?= e($parsedUrl['host'] ?? '') ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-500 truncate max-w-md"><?= e($displayUrl) ?></p>
                        </div>
                    </div>

                    <!-- Title (editable reflects live changes) -->
                    <h3 class="text-xl leading-snug font-normal cursor-pointer hover:underline"
                        style="color: #1a0dab; font-family: arial, sans-serif;"
                        x-text="metaTitle || '<?= addslashes(e($serpTitle)) ?>'">
                    </h3>

                    <!-- Description -->
                    <p class="text-sm leading-relaxed" style="color: #4d5156; font-family: arial, sans-serif; line-height: 1.58;"
                       x-text="metaDescription || '<?= addslashes(e($serpDescription)) ?>'">
                    </p>
                </div>

                <!-- Length indicators -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-6 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 dark:text-slate-400">Title:</span>
                            <span :class="metaTitleColor()" class="font-medium"
                                  x-text="(metaTitle || '<?= addslashes(e($serpTitle)) ?>').length + '/60'"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 dark:text-slate-400">Description:</span>
                            <span :class="metaDescColor()" class="font-medium"
                                  x-text="(metaDescription || '<?= addslashes(e($serpDescription)) ?>').length + '/160'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Back to results -->
        <a href="<?= url("/content-creator/projects/{$projectId}/results") ?>"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna ai risultati
        </a>
    </div>
</div>
