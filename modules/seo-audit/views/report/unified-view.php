<?php
/**
 * Vista integrata Report AI Unificato — dentro layout Ainstein
 *
 * Variabili attese:
 *   $project, $report, $reportData, $siteProfile, $cost, $currentPage
 */

$projectId = $project['id'];
$hasReport = !empty($report);
$healthScore = (int)($report['health_score'] ?? $project['health_score'] ?? 0);
$budgetScore = (int)($report['budget_score'] ?? 0);
$domain = $project['domain'] ?? $project['url'] ?? '';

// Raggruppa issues per severity
$issues = $reportData['issues'] ?? [];
$positives = $reportData['positives'] ?? [];
$timeline = $reportData['timeline'] ?? [];
$summary = $reportData['executive_summary'] ?? '';
$priorityActions = $reportData['priority_actions'] ?? [];
$estimatedImpact = $reportData['estimated_impact'] ?? '';

$criticals = array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'critical');
$importants = array_filter($issues, fn($i) => in_array($i['severity'] ?? '', ['warning', 'important']));
$minors = array_filter($issues, fn($i) => in_array($i['severity'] ?? '', ['notice', 'minor']));

$issueNum = 0;
$csrfToken = csrf_token();
?>

<!-- Project Nav -->
<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php if (!$hasReport): ?>
<!-- No report yet — CTA Generate -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center"
     x-data="reportGenerator()">

    <div class="max-w-lg mx-auto">
        <svg class="w-16 h-16 mx-auto text-emerald-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
        </svg>

        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
            Report AI Unificato
        </h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
            Analisi completa on-page + crawl budget con raccomandazioni personalizzate,
            priorità d'azione e timeline di implementazione.
        </p>

        <div class="flex items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Costo: <?= (float)$cost ?> crediti
        </div>

        <button @click="generate()"
                :disabled="generating"
                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors">
            <template x-if="!generating">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            </template>
            <template x-if="generating">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </template>
            <span x-text="generating ? 'Generazione in corso...' : 'Genera Report AI'"></span>
        </button>

        <template x-if="error">
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg text-sm text-red-700 dark:text-red-300" x-text="error"></div>
        </template>
    </div>
</div>

<script>
function reportGenerator() {
    return {
        generating: false,
        error: null,
        async generate() {
            this.generating = true;
            this.error = null;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= $csrfToken ?>');
                const resp = await fetch('<?= url('/seo-audit/project/' . $projectId . '/report/generate') ?>', {
                    method: 'POST',
                    body: formData,
                });
                if (!resp.ok) throw new Error('Errore di connessione');
                const data = await resp.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    this.error = data.error || 'Errore durante la generazione';
                }
            } catch (e) {
                this.error = e.message || 'Errore imprevisto';
            } finally {
                this.generating = false;
            }
        }
    };
}
</script>

<?php else: ?>
<!-- Report exists — show it -->

<!-- Header -->
<div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-xl p-6 mb-5 text-white">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold">Report SEO Unificato</h1>
            <p class="text-sm text-slate-300 mt-1">
                <?= e($domain) ?> &mdash; <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 text-sm font-bold">
                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                <?= count($criticals) ?> Critici
            </div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 text-sm font-bold">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <?= count($importants) ?> Importanti
            </div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 text-sm font-bold">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                <?= count($minors) ?> Minori
            </div>
        </div>
    </div>
</div>

<!-- Score + Actions row -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
        <div class="text-3xl font-bold <?= $healthScore >= 80 ? 'text-emerald-600' : ($healthScore >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $healthScore ?></div>
        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Health Score</div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
        <div class="text-3xl font-bold <?= $budgetScore >= 80 ? 'text-emerald-600' : ($budgetScore >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $budgetScore ?></div>
        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Budget Score</div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
        <div class="text-lg font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($siteProfile['size_label'] ?? 'N/A') ?></div>
        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tipo: <?= htmlspecialchars($siteProfile['type'] ?? 'generico') ?></div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center justify-center">
        <a href="<?= url('/seo-audit/project/' . $projectId . '/report/download') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Scarica HTML
        </a>
    </div>
</div>

<!-- Executive Summary -->
<?php if ($summary): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 mb-5">
    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Panoramica</h3>
    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= nl2br(e($summary)) ?></p>
</div>
<?php endif; ?>

<!-- Issues sections -->
<div x-data="{ filter: 'all' }">
    <!-- Filter toolbar -->
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors">Tutti</button>
        <button @click="filter = 'critical'" :class="filter === 'critical' ? 'bg-red-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors">Critici (<?= count($criticals) ?>)</button>
        <button @click="filter = 'important'" :class="filter === 'important' ? 'bg-amber-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors">Importanti (<?= count($importants) ?>)</button>
        <button @click="filter = 'minor'" :class="filter === 'minor' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors">Minori (<?= count($minors) ?>)</button>
    </div>

    <!-- Critical issues -->
    <?php if (count($criticals) > 0): ?>
    <div x-show="filter === 'all' || filter === 'critical'" class="mb-5">
        <div class="flex items-center gap-3 mb-3 pb-2 border-b-2 border-red-200 dark:border-red-800">
            <h2 class="text-base font-bold text-red-600 dark:text-red-400">Problemi critici</h2>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800">Priorità 1</span>
            <span class="text-xs text-slate-400">(<?= count($criticals) ?>)</span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <?php foreach ($criticals as $issue): $issueNum++; ?>
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-red-500 overflow-hidden">
                <div @click="open = !open" class="flex items-center gap-3 p-4 cursor-pointer select-none hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="w-7 h-7 rounded-full bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold flex items-center justify-center shrink-0"><?= $issueNum ?></span>
                    <span class="flex-1 text-sm font-semibold text-slate-900 dark:text-white"><?= e($issue['title'] ?? '') ?></span>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 shrink-0"><?= strtoupper(e($issue['impact'] ?? 'ALTO')) ?></span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div x-show="open" x-collapse class="px-4 pb-4 pl-14 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    <p><?= nl2br(e($issue['description'] ?? '')) ?></p>
                    <?php if (!empty($issue['affected_urls'])): ?>
                    <div class="mt-2 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-xs">
                        <strong class="text-slate-700 dark:text-slate-300">URL interessate:</strong><br>
                        <?php foreach (array_slice($issue['affected_urls'], 0, 5) as $url): ?>
                        <code class="text-red-600 dark:text-red-400"><?= e($url) ?></code><br>
                        <?php endforeach; ?>
                        <?php if (count($issue['affected_urls']) > 5): ?>
                        <em class="text-slate-400">... e altre <?= count($issue['affected_urls']) - 5 ?></em>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($issue['code_example'])): ?>
                    <pre class="mt-2 bg-slate-900 text-slate-200 rounded-lg p-3 text-xs font-mono overflow-x-auto"><code><?= e($issue['code_example']) ?></code></pre>
                    <?php endif; ?>
                    <?php if (!empty($issue['fix'])): ?>
                    <div class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm">
                        <strong class="text-emerald-700 dark:text-emerald-400">Fix:</strong> <?= nl2br(e($issue['fix'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Important issues -->
    <?php if (count($importants) > 0): ?>
    <div x-show="filter === 'all' || filter === 'important'" class="mb-5">
        <div class="flex items-center gap-3 mb-3 pb-2 border-b-2 border-amber-200 dark:border-amber-800">
            <h2 class="text-base font-bold text-amber-600 dark:text-amber-400">Problemi importanti</h2>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">Priorità 2</span>
            <span class="text-xs text-slate-400">(<?= count($importants) ?>)</span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <?php foreach ($importants as $issue): $issueNum++; ?>
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-amber-500 overflow-hidden">
                <div @click="open = !open" class="flex items-center gap-3 p-4 cursor-pointer select-none hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="w-7 h-7 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-xs font-bold flex items-center justify-center shrink-0"><?= $issueNum ?></span>
                    <span class="flex-1 text-sm font-semibold text-slate-900 dark:text-white"><?= e($issue['title'] ?? '') ?></span>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 shrink-0"><?= strtoupper(e($issue['impact'] ?? 'MEDIO')) ?></span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div x-show="open" x-collapse class="px-4 pb-4 pl-14 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    <p><?= nl2br(e($issue['description'] ?? '')) ?></p>
                    <?php if (!empty($issue['affected_urls'])): ?>
                    <div class="mt-2 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg text-xs">
                        <strong class="text-slate-700 dark:text-slate-300">URL interessate:</strong><br>
                        <?php foreach (array_slice($issue['affected_urls'], 0, 5) as $url): ?>
                        <code class="text-amber-600 dark:text-amber-400"><?= e($url) ?></code><br>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($issue['code_example'])): ?>
                    <pre class="mt-2 bg-slate-900 text-slate-200 rounded-lg p-3 text-xs font-mono overflow-x-auto"><code><?= e($issue['code_example']) ?></code></pre>
                    <?php endif; ?>
                    <?php if (!empty($issue['fix'])): ?>
                    <div class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm">
                        <strong class="text-emerald-700 dark:text-emerald-400">Fix:</strong> <?= nl2br(e($issue['fix'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Minor issues -->
    <?php if (count($minors) > 0): ?>
    <div x-show="filter === 'all' || filter === 'minor'" class="mb-5">
        <div class="flex items-center gap-3 mb-3 pb-2 border-b-2 border-blue-200 dark:border-blue-800">
            <h2 class="text-base font-bold text-blue-600 dark:text-blue-400">Problemi minori</h2>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">Priorità 3</span>
            <span class="text-xs text-slate-400">(<?= count($minors) ?>)</span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <?php foreach ($minors as $issue): $issueNum++; ?>
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-blue-500 overflow-hidden">
                <div @click="open = !open" class="flex items-center gap-3 p-4 cursor-pointer select-none hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="w-7 h-7 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0"><?= $issueNum ?></span>
                    <span class="flex-1 text-sm font-semibold text-slate-900 dark:text-white"><?= e($issue['title'] ?? '') ?></span>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 shrink-0"><?= strtoupper(e($issue['impact'] ?? 'BASSO')) ?></span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div x-show="open" x-collapse class="px-4 pb-4 pl-14 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    <p><?= nl2br(e($issue['description'] ?? '')) ?></p>
                    <?php if (!empty($issue['fix'])): ?>
                    <div class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm">
                        <strong class="text-emerald-700 dark:text-emerald-400">Fix:</strong> <?= nl2br(e($issue['fix'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Positives -->
<?php if (count($positives) > 0): ?>
<div class="mb-5">
    <div class="flex items-center gap-3 mb-3 pb-2 border-b-2 border-emerald-200 dark:border-emerald-800">
        <h2 class="text-base font-bold text-emerald-600 dark:text-emerald-400">Aspetti positivi</h2>
        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Ben fatto</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
        <?php foreach ($positives as $positive): ?>
        <div class="flex items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-sm text-slate-700 dark:text-slate-300">
            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?= e($positive) ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Timeline -->
<?php if (!empty($timeline)): ?>
<div class="mb-5">
    <h2 class="text-base font-bold text-slate-900 dark:text-white mb-3">Piano d'azione consigliato</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <?php if (!empty($timeline['week1'])): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-red-500 p-4">
            <div class="text-sm font-bold text-red-600 dark:text-red-400 mb-2">Settimana 1</div>
            <p class="text-sm text-slate-600 dark:text-slate-400"><?= nl2br(e($timeline['week1'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($timeline['week2_3'])): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-amber-500 p-4">
            <div class="text-sm font-bold text-amber-600 dark:text-amber-400 mb-2">Settimane 2-3</div>
            <p class="text-sm text-slate-600 dark:text-slate-400"><?= nl2br(e($timeline['week2_3'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($timeline['week4_plus'])): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-blue-500 p-4">
            <div class="text-sm font-bold text-blue-600 dark:text-blue-400 mb-2">Settimana 4+</div>
            <p class="text-sm text-slate-600 dark:text-slate-400"><?= nl2br(e($timeline['week4_plus'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Priority Actions -->
<?php if (!empty($priorityActions)): ?>
<div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 mb-5 overflow-hidden">
    <div @click="open = !open" class="flex items-center gap-3 p-4 cursor-pointer select-none">
        <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Azioni prioritarie (<?= count($priorityActions) ?>)</span>
    </div>
    <div x-show="open" x-collapse class="px-4 pb-4">
        <ol class="list-decimal list-inside text-sm text-slate-600 dark:text-slate-400 space-y-1">
            <?php foreach ($priorityActions as $action): ?>
            <li><?= e($action) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
</div>
<?php endif; ?>

<!-- Estimated Impact -->
<?php if ($estimatedImpact): ?>
<div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 mb-5 overflow-hidden">
    <div @click="open = !open" class="flex items-center gap-3 p-4 cursor-pointer select-none">
        <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Impatto stimato delle correzioni</span>
    </div>
    <div x-show="open" x-collapse class="px-4 pb-4 text-sm text-slate-600 dark:text-slate-400">
        <p><?= nl2br(e($estimatedImpact)) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Re-generate -->
<div class="text-center text-xs text-slate-400 dark:text-slate-500 mt-6" x-data="reportGenerator()">
    <button @click="generate()" :disabled="generating"
            class="text-emerald-600 dark:text-emerald-400 hover:underline disabled:opacity-50">
        <span x-text="generating ? 'Rigenerazione in corso...' : 'Rigenera report'"></span>
    </button>
    &middot; Costo: <?= (float)$cost ?> crediti
    <template x-if="error">
        <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded text-red-600 dark:text-red-400" x-text="error"></div>
    </template>
</div>

<script>
function reportGenerator() {
    return {
        generating: false,
        error: null,
        async generate() {
            this.generating = true;
            this.error = null;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= $csrfToken ?>');
                const resp = await fetch('<?= url('/seo-audit/project/' . $projectId . '/report/generate') ?>', {
                    method: 'POST',
                    body: formData,
                });
                if (!resp.ok) throw new Error('Errore di connessione');
                const data = await resp.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    this.error = data.error || 'Errore durante la generazione';
                }
            } catch (e) {
                this.error = e.message || 'Errore imprevisto';
            } finally {
                this.generating = false;
            }
        }
    };
}
</script>

<?php endif; ?>
