<?php
/**
 * Step 4: Export risultato
 */
$currentPage = 'optimize';
?>

<div class="space-y-6">
    <?php include __DIR__ . '/../partials/project-nav.php'; ?>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center gap-2">
        <?php
        $steps = [
            ['num' => 1, 'label' => 'Import', 'done' => true],
            ['num' => 2, 'label' => 'Analisi', 'done' => true],
            ['num' => 3, 'label' => 'Riscrittura', 'done' => true],
            ['num' => 4, 'label' => 'Export', 'active' => true],
        ];
        foreach ($steps as $i => $step):
        ?>
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium <?= !empty($step['done']) ? 'bg-emerald-500 text-white' : (!empty($step['active']) ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500') ?>">
                <?php if (!empty($step['done'])): ?>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?php else: ?><?= $step['num'] ?><?php endif; ?>
            </div>
            <span class="ml-2 text-sm <?= !empty($step['active']) ? 'text-slate-900 dark:text-white font-medium' : 'text-slate-500' ?>"><?= $step['label'] ?></span>
            <?php if ($i < count($steps) - 1): ?>
            <svg class="w-5 h-5 mx-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Success Banner -->
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-semibold text-emerald-800 dark:text-emerald-200">Ottimizzazione completata!</p>
                <p class="text-sm text-emerald-700 dark:text-emerald-300">
                    <?= number_format($optimization['original_word_count']) ?> → <?= number_format($optimization['optimized_word_count']) ?> parole
                    (<?= $optimization['optimized_word_count'] > $optimization['original_word_count'] ? '+' : '' ?><?= number_format($optimization['optimized_word_count'] - $optimization['original_word_count']) ?>)
                </p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Preview Content -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Title & Meta -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase mb-3">Titolo & Meta</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500">Titolo</label>
                        <p class="font-medium text-slate-900 dark:text-white"><?= e($optimization['optimized_title']) ?></p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Meta Description</label>
                        <p class="text-sm text-slate-600 dark:text-slate-300"><?= e($optimization['optimized_meta_description']) ?></p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">H1</label>
                        <p class="font-medium text-slate-900 dark:text-white"><?= e($optimization['optimized_h1']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Content Preview -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase">Contenuto</h3>
                    <button onclick="copyContent()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Copia HTML
                    </button>
                </div>

                <div id="optimizedContent" class="prose prose-slate dark:prose-invert max-w-none text-sm">
                    <?= $optimization['optimized_content'] ?>
                </div>
            </div>
        </div>

        <!-- Actions Sidebar -->
        <div class="space-y-4">
            <!-- Export Options -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Esporta</h3>

                <div class="space-y-3">
                    <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize/' . $optimization['id'] . '/download') ?>"
                       class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-700 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Scarica HTML
                    </a>

                    <button onclick="copyContent()"
                            class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-700 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        Copia negli Appunti
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Statistiche</h3>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Parole originali</dt>
                        <dd class="font-medium text-slate-900 dark:text-white"><?= number_format($optimization['original_word_count']) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Parole ottimizzate</dt>
                        <dd class="font-medium text-emerald-600"><?= number_format($optimization['optimized_word_count']) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Score SEO</dt>
                        <dd class="font-medium <?= ($optimization['seo_score'] ?? 0) >= 70 ? 'text-green-600' : 'text-primary-600' ?>"><?= $optimization['seo_score'] ?? 'N/A' ?>/100</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Crediti usati</dt>
                        <dd class="font-medium text-slate-900 dark:text-white"><?= $optimization['credits_used'] ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
                <a href="<?= url('/ai-optimizer/project/' . $projectId . '/optimize') ?>"
                   class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuova Ottimizzazione
                </a>

                <a href="<?= url('/ai-optimizer/project/' . $projectId) ?>"
                   class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Torna al Progetto
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyContent() {
    const content = document.getElementById('optimizedContent').innerHTML;
    navigator.clipboard.writeText(content).then(() => {
        alert('Contenuto copiato negli appunti!');
    }).catch(err => {
        console.error('Errore copia:', err);
    });
}
</script>
