<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports') ?>" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($report['title'] ?? 'Report AI') ?></h1>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                <?= e($project['name']) ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <button onclick="window.print()" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Stampa
            </button>
            <button onclick="copyToClipboard()" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Copia
            </button>
        </div>
    </div>

    <?php
    $typeLabel = match($report['report_type']) {
        'weekly_digest' => 'Digest Settimanale',
        'monthly_executive' => 'Executive Mensile',
        'anomaly_analysis' => 'Analisi Anomalie',
        'custom' => 'Personalizzato',
        default => ucfirst($report['report_type']),
    };
    $typeColor = match($report['report_type']) {
        'weekly_digest' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        'monthly_executive' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
        'anomaly_analysis' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    };
    ?>

    <!-- Report Meta -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <span class="px-3 py-1 rounded-full text-sm font-medium <?= $typeColor ?>">
                <?= $typeLabel ?>
            </span>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Generato: <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
            </span>
            <?php if (!empty($report['period_start']) && !empty($report['period_end'])): ?>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Periodo: <?= date('d/m', strtotime($report['period_start'])) ?> - <?= date('d/m/Y', strtotime($report['period_end'])) ?>
            </span>
            <?php endif; ?>
            <span class="text-sm text-slate-500 dark:text-slate-400">
                <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <?= number_format($report['tokens_used'] ?? 0) ?> token utilizzati
            </span>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div id="report-content" class="prose prose-slate dark:prose-invert max-w-none p-6 lg:p-8">
            <?= $renderedContent ?? nl2br(e($report['content'])) ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center">
        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/reports') ?>" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
            ← Torna alla lista report
        </a>
        <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/reports/' . $report['id'] . '/delete') ?>" method="POST" x-data @submit.prevent="window.ainstein.confirm('Eliminare questo report?', {destructive: true}).then(() => $el.submit())">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                Elimina report
            </button>
        </form>
    </div>
</div>

<script>
function copyToClipboard() {
    const content = document.getElementById('report-content').innerText;
    navigator.clipboard.writeText(content).then(() => {
        window.ainstein.toast('Report copiato negli appunti!', 'success');
    }).catch(err => {
        console.error('Errore nella copia:', err);
    });
}
</script>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    #report-content {
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }
}

/* Prose styling for markdown content */
.prose h1 { font-size: 1.875rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; }
.prose h2 { font-size: 1.5rem; font-weight: 600; margin-top: 1.75rem; margin-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
.prose h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
.prose p { margin-bottom: 1rem; line-height: 1.75; }
.prose ul, .prose ol { margin-bottom: 1rem; padding-left: 1.5rem; }
.prose li { margin-bottom: 0.25rem; }
.prose strong { font-weight: 600; }
.prose code {
    background-color: #f1f5f9;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}
.dark .prose code { background-color: #1e293b; }
.prose pre {
    background-color: #1e293b;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1rem;
}
.prose blockquote {
    border-left: 4px solid #006e96;
    padding-left: 1rem;
    font-style: italic;
    color: #64748b;
    margin: 1rem 0;
}
.dark .prose blockquote { color: #94a3b8; }
.prose table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
.prose th, .prose td {
    border: 1px solid #e2e8f0;
    padding: 0.5rem 0.75rem;
    text-align: left;
}
.dark .prose th, .dark .prose td { border-color: #334155; }
.prose th { background-color: #f8fafc; font-weight: 600; }
.dark .prose th { background-color: #1e293b; }
</style>
