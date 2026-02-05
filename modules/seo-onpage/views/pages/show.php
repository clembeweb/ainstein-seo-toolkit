<?php
$currentPage = 'pages';
include __DIR__ . '/../partials/project-nav.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-2">
                <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/pages') ?>" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white truncate"><?= e($page['title'] ?? 'Pagina senza titolo') ?></h1>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 truncate">
                <a href="<?= e($page['url']) ?>" target="_blank" class="hover:text-emerald-600 dark:hover:text-emerald-400">
                    <?= e($page['url']) ?>
                    <svg class="inline w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <?php if ($analysis): ?>
            <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/ai?page_id=' . $page['id']) ?>"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-600 text-white font-medium hover:bg-purple-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Suggerimenti AI
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Score e Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Score -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <?php
            $score = $page['onpage_score'] ?? null;
            $scoreColor = $score >= 80 ? 'text-emerald-600 dark:text-emerald-400' :
                         ($score >= 60 ? 'text-amber-600 dark:text-amber-400' :
                         ($score ? 'text-red-600 dark:text-red-400' : 'text-slate-400'));
            $scoreBg = $score >= 80 ? 'bg-emerald-100 dark:bg-emerald-900/30' :
                      ($score >= 60 ? 'bg-amber-100 dark:bg-amber-900/30' :
                      ($score ? 'bg-red-100 dark:bg-red-900/30' : 'bg-slate-100 dark:bg-slate-700'));
            ?>
            <div class="inline-flex items-center justify-center h-16 w-16 rounded-full <?= $scoreBg ?> mb-2">
                <span class="text-2xl font-bold <?= $scoreColor ?>"><?= $score ?? '-' ?></span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Score OnPage</p>
        </div>

        <!-- Issues Critici -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $analysis['issues_critical'] ?? 0 ?></p>
            <p class="text-sm text-slate-500 dark:text-slate-400">Critici</p>
        </div>

        <!-- Issues Warning -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $analysis['issues_warning'] ?? 0 ?></p>
            <p class="text-sm text-slate-500 dark:text-slate-400">Avvisi</p>
        </div>

        <!-- Parole -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($analysis['content_word_count'] ?? 0) ?></p>
            <p class="text-sm text-slate-500 dark:text-slate-400">Parole</p>
        </div>

        <!-- Links -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                <?= ($analysis['internal_links_count'] ?? 0) + ($analysis['external_links_count'] ?? 0) ?>
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400">Link Totali</p>
        </div>
    </div>

    <?php if (!$analysis): ?>
    <!-- No Analysis -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Pagina non analizzata</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Avvia un audit per analizzare questa pagina.</p>
        <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/audit') ?>"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Avvia Audit
        </a>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Issues List -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Issues Rilevati</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= count($issues) ?> problemi trovati</p>
                </div>

                <?php if (empty($issues)): ?>
                <div class="p-8 text-center">
                    <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400">Nessun problema rilevato!</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php
                    $categoryLabels = [
                        'meta' => 'Meta Tags',
                        'content' => 'Contenuto',
                        'images' => 'Immagini',
                        'links' => 'Link',
                        'technical' => 'Tecnico',
                        'performance' => 'Performance',
                    ];
                    foreach ($issues as $issue):
                        $severityColor = match($issue['severity']) {
                            'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                            'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                            default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                        };
                        $severityIcon = match($issue['severity']) {
                            'critical' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                            'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                            default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        };
                    ?>
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full <?= $severityColor ?>">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <?= $severityIcon ?>
                                    </svg>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><?= $categoryLabels[$issue['category']] ?? ucfirst($issue['category']) ?></span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $severityColor ?>">
                                        <?= $issue['severity'] === 'critical' ? 'Critico' : ($issue['severity'] === 'warning' ? 'Avviso' : 'Info') ?>
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($issue['message']) ?></p>
                                <?php if (!empty($issue['current_value'])): ?>
                                <div class="mt-2 text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Valore attuale:</span>
                                    <code class="ml-1 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-slate-700 dark:text-slate-300"><?= e(mb_substr($issue['current_value'], 0, 100)) ?><?= mb_strlen($issue['current_value']) > 100 ? '...' : '' ?></code>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($issue['recommended_value'])): ?>
                                <div class="mt-1 text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Raccomandato:</span>
                                    <code class="ml-1 px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-900/30 rounded text-emerald-700 dark:text-emerald-300"><?= e(mb_substr($issue['recommended_value'], 0, 100)) ?><?= mb_strlen($issue['recommended_value']) > 100 ? '...' : '' ?></code>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-4">
            <!-- Meta Info -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Informazioni Meta</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="text-xs text-slate-500 dark:text-slate-400">Title</label>
                        <p class="text-sm text-slate-900 dark:text-white"><?= e($page['title'] ?? 'Non presente') ?></p>
                        <p class="text-xs text-slate-500"><?= $analysis['meta_title_length'] ?? 0 ?> caratteri</p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 dark:text-slate-400">Description</label>
                        <p class="text-sm text-slate-900 dark:text-white"><?= e(mb_substr($analysis['meta_description'] ?? 'Non presente', 0, 160)) ?></p>
                        <p class="text-xs text-slate-500"><?= $analysis['meta_description_length'] ?? 0 ?> caratteri</p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 dark:text-slate-400">H1</label>
                        <p class="text-sm text-slate-900 dark:text-white"><?= $analysis['h1_count'] ?? 0 ?> elementi</p>
                    </div>
                </div>
            </div>

            <!-- Content Stats -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Statistiche Contenuto</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Parole</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= number_format($analysis['content_word_count'] ?? 0) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Immagini</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= $analysis['images_count'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Immagini senza alt</span>
                        <span class="text-sm font-medium <?= ($analysis['images_without_alt'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?>"><?= $analysis['images_without_alt'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Link interni</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= $analysis['internal_links_count'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Link esterni</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= $analysis['external_links_count'] ?? 0 ?></span>
                    </div>
                    <?php if (($analysis['broken_links_count'] ?? 0) > 0): ?>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Link rotti</span>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400"><?= $analysis['broken_links_count'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Analysis Info -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Dettagli Analisi</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Device</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white capitalize"><?= $analysis['device'] ?? 'desktop' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Ultima analisi</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($analysis['created_at'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Crediti usati</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= $analysis['credits_used'] ?? 0 ?></span>
                    </div>
                </div>
            </div>

            <!-- AI Suggestions -->
            <?php if (!empty($suggestions)): ?>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <h3 class="font-semibold text-purple-900 dark:text-purple-100"><?= count($suggestions) ?> Suggerimenti AI</h3>
                </div>
                <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">Suggerimenti pronti da applicare</p>
                <a href="<?= url('/seo-onpage/project/' . $project['id'] . '/ai?page_id=' . $page['id']) ?>"
                   class="inline-flex items-center text-sm font-medium text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-100">
                    Visualizza suggerimenti
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
