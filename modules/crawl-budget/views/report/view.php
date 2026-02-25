<?php
/**
 * Crawl Budget Optimizer — AI Report View
 */
$projectId = $project['id'];
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-4">
    <a href="<?= url('/crawl-budget/projects/' . $projectId) ?>" class="hover:text-orange-600 dark:hover:text-orange-400"><?= e($project['name']) ?></a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Report AI</span>
</div>

<!-- Report Header -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Report AI — Crawl Budget</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Generato il <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Risultati Dettagliati
            </a>
            <?php if (($credits['balance'] ?? 0) >= ($credits['report_cost'] ?? 5)): ?>
            <button onclick="regenerateReport()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Rigenera (<?= (int) ($credits['report_cost'] ?? 5) ?> cr)
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Summary -->
<?php if (!empty($report['summary'])): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3">
        <svg class="w-5 h-5 inline mr-1 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Riepilogo
    </h2>
    <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed"><?= $report['summary'] ?></div>
</div>
<?php endif; ?>

<!-- Priority Actions -->
<?php if (!empty($report['priority_actions']) && is_array($report['priority_actions'])): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4">
        <svg class="w-5 h-5 inline mr-1 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        Azioni Prioritarie
    </h2>
    <div class="space-y-3">
        <?php foreach ($report['priority_actions'] as $i => $action): ?>
        <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 text-xs font-bold flex items-center justify-center"><?= $i + 1 ?></span>
            <div class="text-sm text-slate-700 dark:text-slate-300">
                <?php if (is_array($action)): ?>
                    <p class="font-medium"><?= e($action['title'] ?? $action['action'] ?? '') ?></p>
                    <?php if (!empty($action['description'])): ?>
                    <p class="text-slate-500 dark:text-slate-400 mt-1"><?= e($action['description']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?= e($action) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Full Report Content -->
<?php if (!empty($report['content'])): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4">
        <svg class="w-5 h-5 inline mr-1 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Analisi Completa
    </h2>
    <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 [&_h1]:text-lg [&_h2]:text-base [&_h3]:text-sm [&_h1]:font-bold [&_h2]:font-semibold [&_h3]:font-medium [&_h1]:text-slate-900 [&_h2]:text-slate-900 [&_h3]:text-slate-900 dark:[&_h1]:text-white dark:[&_h2]:text-white dark:[&_h3]:text-white [&_ul]:list-disc [&_ol]:list-decimal [&_li]:ml-4">
        <?= $report['content'] ?>
    </div>
</div>
<?php endif; ?>

<!-- Estimated Impact -->
<?php if (!empty($report['estimated_impact']) && is_array($report['estimated_impact'])): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4">
        <svg class="w-5 h-5 inline mr-1 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        Impatto Stimato
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($report['estimated_impact'] as $key => $value): ?>
        <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1"><?= e(ucfirst(str_replace('_', ' ', $key))) ?></p>
            <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= e(is_array($value) ? json_encode($value) : $value) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Back to Dashboard -->
<div class="text-center">
    <a href="<?= url('/crawl-budget/projects/' . $projectId) ?>"
       class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Torna alla Dashboard
    </a>
</div>

<!-- Regenerate Script -->
<script>
function regenerateReport() {
    if (!confirm('Vuoi rigenerare il report AI? Verranno consumati <?= (int) ($credits['report_cost'] ?? 5) ?> crediti.')) return;

    const btn = event.target.closest('button');
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Generazione...';

    fetch('<?= url('/crawl-budget/projects/' . $projectId . '/report/generate') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]')?.content || '<?= csrf_token() ?>')
    })
    .then(r => r.ok ? r.json() : Promise.reject('Errore di rete'))
    .then(data => {
        if (data.error) {
            alert(data.message || 'Errore nella generazione');
            btn.disabled = false;
            btn.innerHTML = origHTML;
        } else {
            window.location.reload();
        }
    })
    .catch(err => {
        alert('Errore: ' + err);
        btn.disabled = false;
        btn.innerHTML = origHTML;
    });
}
</script>
