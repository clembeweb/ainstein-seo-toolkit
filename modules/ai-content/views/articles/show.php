<?php
// Base URL for project context
$baseUrl = !empty($article['project_id']) ? '/ai-content/projects/' . $article['project_id'] : '/ai-content';
?>
<div class="space-y-6" x-data="articleEditor(<?= htmlspecialchars(json_encode([
    'id' => $article['id'],
    'title' => $article['title'] ?? '',
    'meta_description' => $article['meta_description'] ?? '',
    'content' => $article['content'] ?? '',
    'status' => $article['status'] ?? 'draft',
]), ENT_QUOTES) ?>)">

    <!-- Breadcrumbs -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="<?= url('/ai-content') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    AI Content
                </a>
            </li>
            <?php if (!empty($article['project_id']) && !empty($project)): ?>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="<?= url('/ai-content/projects/' . $article['project_id']) ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <?= e($project['name'] ?? 'Progetto') ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="<?= url($baseUrl . '/articles') ?>" class="ml-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    Articoli
                </a>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-2 text-slate-900 dark:text-white font-medium">#<?= $article['id'] ?></span>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="<?= url($baseUrl . '/articles') ?>" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    <?= e($article['title'] ?: 'Articolo #' . $article['id']) ?>
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Creato il <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?>
                </p>
            </div>
        </div>

        <!-- Quick Status Badge -->
        <?php
        $statusConfig = [
            'draft' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-400', 'label' => 'Bozza'],
            'generating' => ['bg' => 'bg-amber-100 dark:bg-amber-900/50', 'text' => 'text-amber-700 dark:text-amber-300', 'label' => 'In generazione', 'spinner' => true],
            'ready' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/50', 'text' => 'text-emerald-700 dark:text-emerald-300', 'label' => 'Pronto'],
            'published' => ['bg' => 'bg-blue-100 dark:bg-blue-900/50', 'text' => 'text-blue-700 dark:text-blue-300', 'label' => 'Pubblicato'],
            'failed' => ['bg' => 'bg-red-100 dark:bg-red-900/50', 'text' => 'text-red-700 dark:text-red-300', 'label' => 'Fallito'],
        ];
        $status = $statusConfig[$article['status']] ?? $statusConfig['draft'];
        ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium <?= $status['bg'] ?> <?= $status['text'] ?>">
            <?php if (!empty($status['spinner'])): ?>
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <?php endif; ?>
            <?= $status['label'] ?>
        </span>
    </div>

    <?php if ($article['status'] === 'failed'): ?>
    <!-- Error Banner -->
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Generazione fallita</h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                    <?php
                    $briefData = is_array($article['brief_data']) ? $article['brief_data'] : json_decode($article['brief_data'] ?? '{}', true);
                    echo e($briefData['error'] ?? 'Si e verificato un errore durante la generazione dell\'articolo.');
                    ?>
                </p>
                <div class="mt-3">
                    <button @click="regenerateArticle()"
                            :disabled="regenerating"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                        <svg x-show="!regenerating" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="regenerating" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Riprova generazione (10 crediti)
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($article['status'] === 'generating'): ?>
    <!-- Generating Progress -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 animate-spin flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Generazione in corso...</h3>
                <p class="text-sm text-amber-700 dark:text-amber-300">L'articolo e in fase di generazione. Questa pagina si aggiornera automaticamente.</p>
                <?php
                $briefData = is_array($article['brief_data']) ? $article['brief_data'] : json_decode($article['brief_data'] ?? '{}', true);
                $startedAt = $briefData['generation_started_at'] ?? null;
                $canReset = false;
                $elapsedMinutes = 0;
                if ($startedAt) {
                    $elapsedMinutes = round((time() - strtotime($startedAt)) / 60, 1);
                    $canReset = $elapsedMinutes >= 5;
                }
                ?>
                <div class="mt-3 flex items-center gap-3">
                    <?php if ($startedAt): ?>
                    <span class="text-xs text-amber-600 dark:text-amber-400">
                        In corso da <?= $elapsedMinutes ?> minuti
                    </span>
                    <?php endif; ?>

                    <?php if ($canReset): ?>
                    <button @click="resetArticle()"
                            :disabled="resetting"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 transition-colors disabled:opacity-50">
                        <svg x-show="!resetting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="resetting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="resetting ? 'Reset...' : 'Reset status'"></span>
                    </button>
                    <?php else: ?>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Reset disponibile tra <?= ceil(5 - $elapsedMinutes) ?> minuti
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Auto-refresh while generating
        setTimeout(() => window.location.reload(), 5000);
    </script>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-6">
        <!-- Left Column: Content Preview/Editor (70%) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- View Toggle -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-700 rounded-lg">
                    <button @click="viewMode = 'preview'"
                            :class="viewMode === 'preview' ? 'bg-white dark:bg-slate-600 shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-slate-600'"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Preview
                        </span>
                    </button>
                    <button @click="viewMode = 'editor'"
                            :class="viewMode === 'editor' ? 'bg-white dark:bg-slate-600 shadow-sm' : 'hover:bg-slate-200 dark:hover:bg-slate-600'"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            HTML Editor
                        </span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <span x-show="hasChanges" x-cloak class="text-sm text-amber-600 dark:text-amber-400">
                        Modifiche non salvate
                    </span>
                    <button x-show="hasChanges"
                            x-cloak
                            @click="saveArticle()"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50">
                        <svg x-show="!saving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="saving" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salva
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Preview Mode -->
                <div x-show="viewMode === 'preview'" class="p-6 lg:p-8">
                    <?php if (empty($article['content'])): ?>
                    <div class="text-center py-12">
                        <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                            <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">Nessun contenuto disponibile</p>
                    </div>
                    <?php else: ?>
                    <!-- Article Preview with Typography -->
                    <article class="prose prose-lg prose-slate dark:prose-invert max-w-none
                                   prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white
                                   prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4
                                   prose-h3:text-xl prose-h3:mt-6 prose-h3:mb-3
                                   prose-p:text-slate-700 dark:prose-p:text-slate-300 prose-p:leading-relaxed
                                   prose-a:text-primary-600 dark:prose-a:text-primary-400
                                   prose-strong:text-slate-900 dark:prose-strong:text-white
                                   prose-ul:my-4 prose-ol:my-4 prose-li:my-1"
                            x-html="content">
                    </article>
                    <?php endif; ?>
                </div>

                <!-- Editor Mode -->
                <div x-show="viewMode === 'editor'" x-cloak class="relative">
                    <textarea x-model="content"
                              @input="markChanged()"
                              class="w-full h-[600px] p-6 font-mono text-sm bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 border-0 focus:ring-0 resize-none"
                              placeholder="Inserisci il contenuto HTML dell'articolo..."></textarea>
                    <div class="absolute bottom-3 right-3 text-xs text-slate-400">
                        <span x-text="wordCount"></span> parole
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar (30%) -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Metadata Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5 space-y-5">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Dettagli
                </h3>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Titolo</label>
                    <input type="text"
                           x-model="title"
                           @input="markChanged()"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="Titolo articolo">
                    <p class="mt-1 text-xs" :class="title.length > 60 ? 'text-amber-600' : 'text-slate-400'">
                        <span x-text="title.length"></span>/60 caratteri
                    </p>
                </div>

                <!-- Meta Description -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Meta Description</label>
                    <textarea x-model="metaDescription"
                              @input="markChanged()"
                              rows="3"
                              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                              placeholder="Meta description per SEO..."></textarea>
                    <p class="mt-1 text-xs" :class="metaDescription.length > 155 ? 'text-red-600' : (metaDescription.length > 140 ? 'text-amber-600' : 'text-slate-400')">
                        <span x-text="metaDescription.length"></span>/155 caratteri
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Parole</p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= number_format($article['word_count'] ?? 0) ?></p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Crediti</p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-white"><?= $article['credits_used'] ?? 0 ?></p>
                    </div>
                </div>

                <!-- Keyword -->
                <?php if (!empty($article['keyword'])): ?>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Keyword</p>
                    <a href="<?= url('/ai-content/keywords/' . $article['keyword_id'] . '/serp') ?>"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?= e($article['keyword']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Generation Time -->
                <?php
                $briefData = is_array($article['brief_data']) ? $article['brief_data'] : json_decode($article['brief_data'] ?? '{}', true);
                ?>
                <?php if (!empty($briefData['generation_time_ms'])): ?>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Tempo generazione</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        <?= number_format($briefData['generation_time_ms'] / 1000, 1) ?>s
                    </p>
                </div>
                <?php endif; ?>

                <!-- Published Info -->
                <?php if ($article['status'] === 'published'): ?>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pubblicazione</p>
                    <?php if (!empty($article['published_url'])): ?>
                    <a href="<?= e($article['published_url']) ?>"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Vedi su WordPress
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($article['published_at'])): ?>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?= date('d/m/Y H:i', strtotime($article['published_at'])) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Azioni
                </h3>

                <!-- Save Button -->
                <button @click="saveArticle()"
                        :disabled="saving || !hasChanges"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="!saving" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <svg x-show="saving" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="saving ? 'Salvataggio...' : 'Salva modifiche'"></span>
                </button>

                <?php if ($article['status'] === 'ready'): ?>
                <!-- Publish to WordPress -->
                <button @click="showPublishModal = true"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Pubblica su WordPress
                </button>
                <?php endif; ?>

                <?php if ($article['status'] !== 'published' && $article['status'] !== 'generating'): ?>
                <!-- Regenerate -->
                <button @click="regenerateArticle()"
                        :disabled="regenerating"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400 font-medium hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors disabled:opacity-50">
                    <svg x-show="!regenerating" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg x-show="regenerating" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="regenerating ? 'Rigenerazione...' : 'Rigenera (10 crediti)'"></span>
                </button>
                <?php endif; ?>

                <!-- Back to List -->
                <a href="<?= url($baseUrl . '/articles') ?>"
                   class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    Torna alla lista
                </a>
            </div>

            <!-- Sources Card -->
            <?php if (!empty($article['sources'])): ?>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Fonti (<?= count($article['sources']) ?>)
                </h3>
                <ul class="space-y-2">
                    <?php foreach ($article['sources'] as $source): ?>
                    <li class="flex items-start gap-2 text-sm">
                        <?php $scrapeStatus = $source['scrape_status'] ?? 'pending'; ?>
                        <?php if ($scrapeStatus === 'success'): ?>
                        <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php elseif ($scrapeStatus === 'failed'): ?>
                        <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <?php else: ?>
                        <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php endif; ?>
                        <a href="<?= e($source['url']) ?>"
                           target="_blank"
                           class="text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 truncate"
                           title="<?= e($source['url']) ?>">
                            <?= e(parse_url($source['url'], PHP_URL_HOST) ?: $source['url']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Publish to WordPress Modal -->
    <div x-show="showPublishModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showPublishModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/50" @click="showPublishModal = false"></div>

            <div x-show="showPublishModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Pubblica su WordPress</h3>

                <form @submit.prevent="publishToWordPress()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sito WordPress *</label>
                            <select x-model="publishSiteId" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">Seleziona un sito...</option>
                                <?php if (!empty($wpSites)): ?>
                                    <?php foreach ($wpSites as $site): ?>
                                    <option value="<?= $site['id'] ?>"><?= e($site['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($wpSites)): ?>
                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                Nessun sito configurato.
                                <a href="<?= url('/ai-content/wordpress') ?>" class="underline">Aggiungi un sito WordPress</a>
                            </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria</label>
                            <input type="text"
                                   x-model="publishCategory"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                   placeholder="es. Blog, News, Guide...">
                            <p class="mt-1 text-xs text-slate-500">Lascia vuoto per usare la categoria predefinita</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status pubblicazione</label>
                            <select x-model="publishStatus" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="draft">Bozza</option>
                                <option value="publish">Pubblicato</option>
                                <option value="pending">In attesa di revisione</option>
                                <option value="private">Privato</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showPublishModal = false" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            Annulla
                        </button>
                        <button type="submit"
                                :disabled="publishing || !publishSiteId"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <svg x-show="publishing" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="publishing ? 'Pubblicazione...' : 'Pubblica'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function articleEditor(initialData) {
    return {
        // Data
        id: initialData.id,
        title: initialData.title || '',
        metaDescription: initialData.meta_description || '',
        content: initialData.content || '',
        originalTitle: initialData.title || '',
        originalMetaDescription: initialData.meta_description || '',
        originalContent: initialData.content || '',
        status: initialData.status,

        // UI state
        viewMode: 'preview',
        hasChanges: false,
        saving: false,
        regenerating: false,
        resetting: false,

        // Publish modal
        showPublishModal: false,
        publishSiteId: '',
        publishCategory: '',
        publishStatus: 'draft',
        publishing: false,

        // Computed
        get wordCount() {
            const text = this.content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            return text ? text.split(' ').filter(w => w.length > 0).length : 0;
        },

        // Methods
        markChanged() {
            this.hasChanges = (
                this.title !== this.originalTitle ||
                this.metaDescription !== this.originalMetaDescription ||
                this.content !== this.originalContent
            );
        },

        async saveArticle() {
            if (this.saving || !this.hasChanges) return;

            this.saving = true;

            try {
                const formData = new FormData();
                formData.append('_token', '<?= csrf_token() ?>');
                formData.append('title', this.title);
                formData.append('meta_description', this.metaDescription);
                formData.append('content', this.content);

                const response = await fetch('<?= url('/ai-content/articles/' . $article['id']) ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.originalTitle = this.title;
                    this.originalMetaDescription = this.metaDescription;
                    this.originalContent = this.content;
                    this.hasChanges = false;

                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Articolo salvato', type: 'success' }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante il salvataggio', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.saving = false;
            }
        },

        async regenerateArticle() {
            if (this.regenerating) return;

            if (!confirm('Rigenerare l\'articolo? Verranno consumati 10 crediti e il contenuto attuale verra sostituito.')) {
                return;
            }

            this.regenerating = true;

            try {
                const response = await fetch('<?= url('/ai-content/articles/' . $article['id'] . '/regenerate') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Rigenerazione avviata!', type: 'success' }
                    }));
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante la rigenerazione', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.regenerating = false;
            }
        },

        async resetArticle() {
            if (this.resetting) return;

            if (!confirm('Resettare lo status dell\'articolo? L\'articolo tornerà allo stato precedente (bozza).')) {
                return;
            }

            this.resetting = true;

            try {
                const response = await fetch('<?= url('/ai-content/articles/' . $article['id'] . '/reset') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Status resettato', type: 'success' }
                    }));
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante il reset', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.resetting = false;
            }
        },

        async publishToWordPress() {
            if (this.publishing || !this.publishSiteId) return;

            this.publishing = true;

            try {
                const response = await fetch('<?= url('/ai-content/articles/' . $article['id'] . '/publish') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _token: '<?= csrf_token() ?>',
                        wp_site_id: this.publishSiteId,
                        category: this.publishCategory,
                        status: this.publishStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Articolo pubblicato!', type: 'success' }
                    }));
                    this.showPublishModal = false;
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.error || 'Errore durante la pubblicazione', type: 'error' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Errore di connessione', type: 'error' }
                }));
            } finally {
                this.publishing = false;
            }
        }
    }
}
</script>
