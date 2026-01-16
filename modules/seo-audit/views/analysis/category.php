<?php
use Modules\SeoAudit\Models\Issue;

$categoryColors = [
    'meta' => 'blue',
    'headings' => 'purple',
    'images' => 'pink',
    'links' => 'indigo',
    'content' => 'amber',
    'technical' => 'slate',
    'schema' => 'emerald',
    'security' => 'red',
    'sitemap' => 'cyan',
    'robots' => 'orange',
];
$color = $categoryColors[$category] ?? 'slate';
?>

<div class="space-y-6" x-data="aiCategoryAnalysis()">
    <!-- Breadcrumb + Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/seo-audit') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">SEO Audit</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/seo-audit/project/' . $project['id'] . '/analysis') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Analisi AI</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white"><?= e($categoryLabel) ?></span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-<?= $color ?>-500"></span>
                    Analisi AI: <?= e($categoryLabel) ?>
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analisi dettagliata della categoria</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category) ?>"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Vedi Issues
                </a>
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    Crediti: <span class="font-semibold text-slate-900 dark:text-white"><?= $credits['balance'] ?></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Category Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $categoryStats['total'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Totale Issues</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-red-200 dark:border-red-900/50 p-4 text-center">
            <p class="text-2xl font-bold text-red-600"><?= $categoryStats['critical'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Critiche</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-yellow-200 dark:border-yellow-900/50 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600"><?= $categoryStats['warning'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Warning</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-900/50 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600"><?= $categoryStats['notice'] ?? 0 ?></p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Notice</p>
        </div>
    </div>

    <?php if ($analysis): ?>
    <!-- Analisi Esistente -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white">Analisi Generata</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <?= date('d/m/Y H:i', strtotime($analysis['created_at'])) ?> - <?= $analysis['credits_used'] ?> crediti usati
                    </p>
                </div>
            </div>
            <button @click="regenerate()"
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Rigenera (<?= $credits['category_cost'] ?> cr)
            </button>
        </div>
        <div class="p-6">
            <div class="prose prose-slate dark:prose-invert max-w-none">
                <?php
                // Render markdown basic
                $content = $analysis['content'];
                $content = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-6 mb-3">$1</h3>', $content);
                $content = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-bold text-slate-900 dark:text-white mt-8 mb-4">$1</h2>', $content);
                $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
                $content = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $content);
                $content = preg_replace('/`(.+?)`/', '<code class="px-1 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-sm">$1</code>', $content);
                $content = preg_replace('/^- (.+)$/m', '<li class="ml-4">$1</li>', $content);
                $content = preg_replace('/^\d+\. (.+)$/m', '<li class="ml-4 list-decimal">$1</li>', $content);
                $content = nl2br($content);
                echo $content;
                ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- CTA Genera Analisi -->
    <div class="bg-gradient-to-br from-<?= $color ?>-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 rounded-2xl border border-<?= $color ?>-200 dark:border-slate-700 p-8 text-center">
        <div class="mx-auto w-16 h-16 rounded-full bg-<?= $color ?>-100 dark:bg-<?= $color ?>-900/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-<?= $color ?>-600 dark:text-<?= $color ?>-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Genera Analisi: <?= e($categoryLabel) ?></h2>
        <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-lg mx-auto">
            L'AI analizzerà nel dettaglio tutti i problemi di questa categoria,
            fornendo soluzioni specifiche, priorità e template di esempio.
        </p>

        <?php if (($categoryStats['total'] ?? 0) === 0): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6 max-w-md mx-auto">
            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                <strong>Attenzione:</strong> Non ci sono issues in questa categoria da analizzare.
            </p>
        </div>
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>" class="inline-flex items-center px-6 py-3 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
            Torna alla Dashboard
        </a>
        <?php else: ?>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button @click="generate()"
                    :disabled="loading || <?= $credits['balance'] < $credits['category_cost'] ? 'true' : 'false' ?>"
                    class="inline-flex items-center px-6 py-3 rounded-lg bg-<?= $color ?>-600 text-white font-medium hover:bg-<?= $color ?>-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <template x-if="!loading">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Genera Analisi (<?= $credits['category_cost'] ?> crediti)
                    </span>
                </template>
                <template x-if="loading">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generazione in corso...
                    </span>
                </template>
            </button>
            <?php if ($credits['balance'] < $credits['category_cost']): ?>
            <p class="text-sm text-red-600 dark:text-red-400">
                Crediti insufficienti. Richiesti: <?= $credits['category_cost'] ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Error Message -->
    <div x-show="error" x-cloak class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex items-center justify-between">
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/analysis') ?>"
           class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna a Overview
        </a>
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/category/' . $category) ?>"
           class="inline-flex items-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700">
            Vedi tutte le Issues
            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<script>
function aiCategoryAnalysis() {
    return {
        loading: false,
        error: null,

        async generate() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('<?= url('/seo-audit/project/' . $project['id'] . '/analyze/' . $category) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= csrf_token() ?>'
                    }
                });

                const data = await response.json();

                if (data.error) {
                    this.error = data.message;
                } else {
                    // Reload page to show result
                    window.location.reload();
                }
            } catch (e) {
                this.error = 'Errore di connessione. Riprova.';
            } finally {
                this.loading = false;
            }
        },

        async regenerate() {
            if (!confirm('Vuoi rigenerare l\'analisi? Verranno consumati <?= $credits['category_cost'] ?> crediti.')) {
                return;
            }
            await this.generate();
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
