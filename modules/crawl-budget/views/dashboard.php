<?php
/**
 * Crawl Budget Optimizer — Dashboard Progetto
 *
 * Mostra: score, issue summary, CTA crawl, risultati tabs, report AI
 * Include sezione crawl progress con SSE + polling fallback
 */

$score = $score ?? 0;
$scoreLabel = $scoreLabel ?? 'N/A';
$scoreColor = $scoreColor ?? 'slate';
$hasSession = !empty($session);
$isCompleted = $hasSession && ($session['status'] ?? '') === 'completed';
$isCrawling = $hasSession && in_array($session['status'] ?? '', ['pending', 'running']);
$projectId = $project['id'];
$domain = $project['domain'] ?? '';

// Score ring colors
$ringColors = [
    'emerald' => ['stroke' => 'stroke-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
    'blue' => ['stroke' => 'stroke-blue-500', 'text' => 'text-blue-600 dark:text-blue-400', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
    'amber' => ['stroke' => 'stroke-amber-500', 'text' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-50 dark:bg-amber-900/20'],
    'red' => ['stroke' => 'stroke-red-500', 'text' => 'text-red-600 dark:text-red-400', 'bg' => 'bg-red-50 dark:bg-red-900/20'],
    'slate' => ['stroke' => 'stroke-slate-300', 'text' => 'text-slate-500 dark:text-slate-400', 'bg' => 'bg-slate-50 dark:bg-slate-800'],
];
$ring = $ringColors[$scoreColor] ?? $ringColors['slate'];
$circumference = 2 * 3.14159 * 45;
$dashOffset = $circumference - ($circumference * $score / 100);
?>

<!-- Orphaned project notice -->
<?= \Core\View::partial('components/orphaned-project-notice', ['project' => $project]) ?>

<!-- Header -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
            <div class="flex items-center gap-2 mt-1">
                <a href="<?= e('https://' . ltrim($domain, 'https://')) ?>" target="_blank"
                   class="text-sm text-orange-600 dark:text-orange-400 hover:underline flex items-center gap-1">
                    <?= e($domain) ?>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                <?php if (!empty($project['last_crawl_at'])): ?>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Ultimo crawl: <?= date('d/m/Y H:i', strtotime($project['last_crawl_at'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/settings') ?>"
               class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Impostazioni
            </a>
        </div>
    </div>
</div>

<!-- Crawl Progress Section (Alpine.js) -->
<div x-data="crawlProgress()" x-cloak>
    <!-- Start Crawl Button (if no crawl running) -->
    <?php if (!$isCrawling): ?>
    <div class="mb-6 flex items-center gap-3">
        <button @click="startCrawl()"
                :disabled="running"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span x-text="running ? 'Avvio in corso...' : '<?= $isCompleted ? 'Ri-analizza Sito' : 'Avvia Analisi' ?>'"></span>
        </button>
        <span class="text-xs text-slate-500 dark:text-slate-400">Gratuito — nessun credito richiesto</span>
    </div>
    <?php endif; ?>

    <!-- Progress Card (shown during crawl) -->
    <template x-if="running">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-orange-200 dark:border-orange-800 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Crawl in corso...</h3>
                <button @click="cancelCrawl()" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 font-medium">Annulla</button>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 mb-3">
                <div class="bg-orange-500 h-3 rounded-full transition-all duration-300"
                     :style="'width: ' + progress + '%'"></div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">
                    <span x-text="pagesCrawled"></span> / <span x-text="pagesFound"></span> pagine
                </span>
                <span class="text-slate-600 dark:text-slate-400">
                    <span x-text="issuesFound"></span> problemi trovati
                </span>
                <span class="text-orange-600 dark:text-orange-400 font-medium" x-text="progress + '%'"></span>
            </div>

            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 truncate" x-show="currentUrl">
                <span x-text="currentUrl"></span>
            </div>
        </div>
    </template>
</div>

<?php if ($isCompleted): ?>
<!-- Score + KPI Row -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
    <!-- Score Circle -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 flex flex-col items-center justify-center">
        <div class="relative w-28 h-28">
            <svg class="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke-width="8" class="stroke-slate-200 dark:stroke-slate-700"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke-width="8"
                        class="<?= $ring['stroke'] ?>"
                        stroke-linecap="round"
                        stroke-dasharray="<?= $circumference ?>"
                        stroke-dashoffset="<?= $dashOffset ?>"/>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-2xl font-bold <?= $ring['text'] ?>"><?= $score ?></span>
            </div>
        </div>
        <p class="mt-2 text-sm font-medium <?= $ring['text'] ?>"><?= $scoreLabel ?></p>
        <p class="text-xs text-slate-500 dark:text-slate-400">Budget Score</p>
    </div>

    <!-- KPI Cards -->
    <?= \Core\View::partial('components/dashboard-kpi-card', [
        'label' => 'Pagine Analizzate',
        'value' => $totalPages,
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'color' => 'slate',
    ]) ?>

    <?= \Core\View::partial('components/dashboard-kpi-card', [
        'label' => 'Problemi Critici',
        'value' => $severityCounts['critical'] ?? 0,
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        'color' => 'red',
    ]) ?>

    <?= \Core\View::partial('components/dashboard-kpi-card', [
        'label' => 'Warning',
        'value' => $severityCounts['warning'] ?? 0,
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'color' => 'amber',
    ]) ?>
</div>

<!-- Results Tabs -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex -mb-px overflow-x-auto">
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results') ?>"
               class="px-4 py-3 text-sm font-medium border-b-2 border-orange-500 text-orange-600 dark:text-orange-400 whitespace-nowrap">
                Overview
            </a>
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/redirects') ?>"
               class="px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 border-b-2 border-transparent hover:border-slate-300 whitespace-nowrap">
                Redirect
            </a>
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/waste') ?>"
               class="px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 border-b-2 border-transparent hover:border-slate-300 whitespace-nowrap">
                Pagine Spreco
            </a>
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/indexability') ?>"
               class="px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 border-b-2 border-transparent hover:border-slate-300 whitespace-nowrap">
                Indexability
            </a>
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/results/pages') ?>"
               class="px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 border-b-2 border-transparent hover:border-slate-300 whitespace-nowrap">
                Tutte le Pagine
            </a>
        </nav>
    </div>

    <!-- Quick Summary inside tab card -->
    <div class="p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php
            $categories = [
                'redirect' => ['label' => 'Redirect', 'icon' => 'M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                'waste' => ['label' => 'Pagine Spreco', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                'indexability' => ['label' => 'Indexability', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
            ];
            foreach ($categories as $catSlug => $catInfo):
                $catCount = 0;
                foreach ($issueSummary as $row) {
                    if ($row['category'] === $catSlug) $catCount += (int) $row['cnt'];
                }
            ?>
            <div class="text-center p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                <svg class="w-6 h-6 mx-auto text-slate-400 dark:text-slate-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $catInfo['icon'] ?>"/>
                </svg>
                <p class="text-lg font-bold text-slate-900 dark:text-white"><?= $catCount ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400"><?= $catInfo['label'] ?></p>
            </div>
            <?php endforeach; ?>

            <div class="text-center p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                <svg class="w-6 h-6 mx-auto text-slate-400 dark:text-slate-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-lg font-bold text-slate-900 dark:text-white"><?= $totalPages ?></p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pagine</p>
            </div>
        </div>
    </div>
</div>

<!-- AI Report Card -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Report AI</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                <?php if ($report): ?>
                    Generato il <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
                <?php else: ?>
                    Genera un report con azioni prioritarie e raccomandazioni
                <?php endif; ?>
            </p>
        </div>
        <div>
            <?php if ($report): ?>
            <a href="<?= url('/crawl-budget/projects/' . $projectId . '/report') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-900/30 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Vedi Report
            </a>
            <?php else: ?>
            <div x-data="{ generating: false }" class="flex items-center gap-2">
                <button @click="generating = true; fetch('<?= url('/crawl-budget/projects/' . $projectId . '/report/generate') ?>', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'_csrf_token=<?= csrf_token() ?>'}).then(r=>r.json()).then(d=>{generating=false; if(d.success) location.reload(); else alert(d.message||'Errore')}).catch(()=>{generating=false; alert('Errore di connessione')})"
                        :disabled="generating"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition-colors disabled:opacity-50">
                    <svg x-show="!generating" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <svg x-show="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="generating ? 'Generazione...' : 'Genera Report AI'"></span>
                </button>
                <span class="text-xs text-slate-500 dark:text-slate-400"><?= $credits['report_cost'] ?? 5 ?> crediti</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$isCompleted && !$isCrawling): ?>
<!-- Landing educativa per nuovi progetti -->
<div class="mt-8 border-t border-slate-200 dark:border-slate-700 pt-8">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Scopri cosa puoi fare</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-2">Analizza il crawl budget del tuo sito e ottimizza l'indicizzazione</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 text-center">
            <div class="w-12 h-12 mx-auto mb-4 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Redirect Chains</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Identifica catene di redirect, loop, redirect temporanei e redirect verso errori 4xx/5xx</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 text-center">
            <div class="w-12 h-12 mx-auto mb-4 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Pagine Spreco</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Trova pagine vuote, thin content, soft 404, URL con parametri e pagine troppo profonde</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 text-center">
            <div class="w-12 h-12 mx-auto mb-4 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Indexability</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Rileva conflitti tra noindex e sitemap, canonical mismatch, segnali contraddittori</p>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function crawlProgress() {
    return {
        running: <?= $isCrawling ? 'true' : 'false' ?>,
        progress: 0,
        pagesCrawled: 0,
        pagesFound: 0,
        issuesFound: 0,
        currentUrl: '',
        jobId: null,
        eventSource: null,
        pollingInterval: null,

        startCrawl() {
            if (this.running) return;
            this.running = true;

            fetch('<?= url('/crawl-budget/projects/' . $projectId . '/crawl/start') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: '_csrf_token=<?= csrf_token() ?>'
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                if (data.error) {
                    alert(data.message || 'Errore');
                    this.running = false;
                    return;
                }
                this.jobId = data.job_id;
                this.pagesFound = data.pages_found || 0;
                this.connectSSE(data.job_id);
            })
            .catch(err => {
                alert('Errore di connessione: ' + err.message);
                this.running = false;
            });
        },

        connectSSE(jobId) {
            const url = '<?= url('/crawl-budget/projects/' . $projectId . '/crawl/stream') ?>?job_id=' + jobId;
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('item_completed', (e) => {
                const d = JSON.parse(e.data);
                this.pagesCrawled = d.completed || 0;
                this.pagesFound = d.total || this.pagesFound;
                this.issuesFound += (d.issues || 0);
                this.progress = parseFloat(d.percent) || 0;
                this.currentUrl = d.url || '';
            });

            this.eventSource.addEventListener('completed', () => {
                this.eventSource.close();
                this.running = false;
                location.reload();
            });

            this.eventSource.addEventListener('cancelled', () => {
                this.eventSource.close();
                this.running = false;
                location.reload();
            });

            this.eventSource.addEventListener('error', (e) => {
                // SSE failed — fallback to polling
                if (this.eventSource) this.eventSource.close();
                this.startPolling(jobId);
            });

            // Polling fallback after 30s if no events
            setTimeout(() => {
                if (this.running && this.pagesCrawled === 0) {
                    if (this.eventSource) this.eventSource.close();
                    this.startPolling(jobId);
                }
            }, 30000);
        },

        startPolling(jobId) {
            this.pollingInterval = setInterval(() => {
                fetch('<?= url('/crawl-budget/projects/' . $projectId . '/crawl/job-status') ?>?job_id=' + jobId)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const job = data.job || {};
                    this.pagesCrawled = job.items_completed || 0;
                    this.pagesFound = job.items_total || this.pagesFound;
                    this.progress = this.pagesFound > 0 ? Math.round((this.pagesCrawled / this.pagesFound) * 100) : 0;

                    if (job.status === 'completed' || job.status === 'cancelled' || job.status === 'error') {
                        clearInterval(this.pollingInterval);
                        this.running = false;
                        location.reload();
                    }
                });
            }, 3000);
        },

        cancelCrawl() {
            if (!this.jobId) return;
            fetch('<?= url('/crawl-budget/projects/' . $projectId . '/crawl/cancel') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: '_csrf_token=<?= csrf_token() ?>&job_id=' + this.jobId
            });
        }
    }
}
</script>
