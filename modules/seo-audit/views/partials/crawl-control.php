<?php
/**
 * Crawl Control Partial - Spider Integrato
 *
 * Widget unificato per configurazione e controllo crawl
 * Include preset rapidi e impostazioni avanzate
 *
 * Variabili richieste:
 * - $project: array con dati progetto
 * - $session: array|null con dati sessione crawl
 * - $credits: array con balance e costi
 */

// Determina se il crawl è attivo: status crawling/stopping OPPURE sessione attiva
$session = $session ?? null;
$hasActiveSession = $session && in_array($session['status'] ?? '', ['running', 'stopping']);
$isCrawling = in_array($project['status'] ?? '', ['crawling', 'stopping']) || $hasActiveSession;
$isPending = ($project['status'] ?? '') === 'pending';
$isStopping = ($project['status'] ?? '') === 'stopping' || ($session && ($session['status'] ?? '') === 'stopping');

// Calcola progress_percent in modo sicuro (evita errori undefined key)
$progressPercent = 0;
if ($session) {
    if (isset($session['progress_percent'])) {
        $progressPercent = (float) $session['progress_percent'];
    } elseif (($session['pages_found'] ?? 0) > 0) {
        $progressPercent = (($session['pages_crawled'] ?? 0) / $session['pages_found']) * 100;
    }
}

// Default config con tutti i parametri spider
$config = $project['crawl_config'] ? json_decode($project['crawl_config'], true) : [];
$config = array_merge([
    'max_pages' => $project['max_pages'] ?? 500,
    'max_depth' => 3,
    'request_delay' => 200,
    'timeout' => 20,
    'max_retries' => 2,
    'user_agent' => 'googlebot',
    'respect_robots' => 1,
    'follow_redirects' => 1,
], $config);

// Preset configurations
$presets = [
    'veloce' => [
        'label' => 'Veloce',
        'icon' => '🚀',
        'desc' => '100 pagine, profondità 2',
        'max_pages' => 100,
        'max_depth' => 2,
        'request_delay' => 0,
    ],
    'bilanciato' => [
        'label' => 'Bilanciato',
        'icon' => '⚖️',
        'desc' => '500 pagine, profondità 3',
        'max_pages' => 500,
        'max_depth' => 3,
        'request_delay' => 200,
    ],
    'completo' => [
        'label' => 'Completo',
        'icon' => '🔍',
        'desc' => '2000 pagine, profondità 5',
        'max_pages' => 2000,
        'max_depth' => 5,
        'request_delay' => 500,
    ],
];

// Accordion: chiuso se ha già scansioni completate (e non sta crawlando)
$hasCompletedScans = (($project['pages_crawled'] ?? 0) > 0) && !$isCrawling;
$lastScanDate = $project['completed_at'] ?? null;
?>

<!-- Crawl Control Card con Accordion -->
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
     id="crawl-control"
     x-data="{ expanded: <?= $hasCompletedScans ? 'false' : 'true' ?> }">

    <!-- Header (sempre visibile, cliccabile se ha scansioni) -->
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between <?= $hasCompletedScans ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors' : '' ?>"
         <?= $hasCompletedScans ? '@click="expanded = !expanded"' : '' ?>>
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Spider Crawl</h2>
                <?php if ($hasCompletedScans && $lastScanDate): ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Ultima scansione: <?= date('d/m/Y H:i', strtotime($lastScanDate)) ?> - <?= $project['pages_crawled'] ?> pagine
                </p>
                <?php else: ?>
                <p class="text-sm text-slate-500 dark:text-slate-400">Esplora e analizza il sito automaticamente</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($isCrawling): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 animate-pulse">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <?= $isStopping ? 'Arresto in corso...' : 'In esecuzione' ?>
            </span>
            <?php endif; ?>
            <?php if ($hasCompletedScans): ?>
            <!-- Chevron accordion -->
            <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contenuto accordion -->
    <div x-show="expanded" x-collapse class="p-6">
        <?php if ($isCrawling): ?>
        <!-- Crawl in Progress -->
        <div id="crawl-progress">
            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-slate-600 dark:text-slate-400">Progresso</span>
                    <span class="font-medium text-slate-900 dark:text-white" id="progress-text">
                        <?= $session ? $session['pages_crawled'] . '/' . $session['pages_found'] : '0/0' ?> pagine
                    </span>
                </div>
                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500"
                         id="progress-bar"
                         style="width: <?= $progressPercent ?>%"></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white" id="stat-pages"><?= $session ? $session['pages_crawled'] : 0 ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pagine</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400" id="stat-issues"><?= $session ? $session['issues_found'] : 0 ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Issues</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white" id="stat-percent"><?= round($progressPercent) ?>%</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Completato</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white" id="stat-time">0:00</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tempo</p>
                </div>
            </div>

            <!-- Current URL -->
            <div class="mb-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">URL corrente:</p>
                <p class="text-sm text-slate-700 dark:text-slate-300 truncate font-mono" id="current-url">
                    <?= $session && $session['current_url'] ? e($session['current_url']) : 'In attesa...' ?>
                </p>
            </div>

            <!-- Stop Button -->
            <?php if (!$isStopping): ?>
            <div class="flex justify-center">
                <button type="button" id="btn-stop-crawl"
                        class="inline-flex items-center px-6 py-3 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                    Interrompi Scansione
                </button>
            </div>
            <?php else: ?>
            <div class="text-center text-amber-600 dark:text-amber-400">
                <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm font-medium">Arresto in corso...</p>
                <p class="text-xs">Il crawl si fermera al termine della pagina corrente</p>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- Configuration Form -->
        <form id="crawl-config-form" class="space-y-6" x-data="crawlConfigForm()">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="crawl_mode" value="spider">

            <!-- Preset Buttons -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                    Modalita Scansione
                </label>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach ($presets as $key => $preset): ?>
                    <button type="button"
                            @click="applyPreset('<?= $key ?>')"
                            :class="currentPreset === '<?= $key ?>' ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/30 border-primary-300' : 'bg-slate-50 dark:bg-slate-700/50 border-slate-200 dark:border-slate-600 hover:bg-slate-100'"
                            class="p-3 rounded-xl border text-center transition-all">
                        <span class="text-2xl"><?= $preset['icon'] ?></span>
                        <p class="font-medium text-slate-900 dark:text-white mt-1"><?= $preset['label'] ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= $preset['desc'] ?></p>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Max Pages Slider -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Pagine da analizzare
                    </label>
                    <span class="text-sm font-bold text-slate-900 dark:text-white" x-text="maxPages"></span>
                </div>
                <input type="range" name="max_pages" x-model="maxPages" min="10" max="2000" step="10"
                       class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer">
                <div class="flex justify-between text-xs text-slate-400 mt-1">
                    <span>10</span>
                    <span>500</span>
                    <span>1000</span>
                    <span>2000</span>
                </div>
            </div>

            <!-- Robots.txt Option -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="respect_robots" value="1" x-model="respectRobots"
                       class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                <div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Rispetta robots.txt</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Segui le regole del sito</p>
                </div>
            </label>

            <!-- Advanced Settings Toggle -->
            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                <button type="button" @click="showAdvanced = !showAdvanced"
                        class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                    <svg class="w-4 h-4 transition-transform" :class="showAdvanced && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Impostazioni Avanzate
                </button>
            </div>

            <!-- Advanced Settings Panel -->
            <div x-show="showAdvanced" x-collapse class="space-y-4 pl-1">
                <!-- Depth -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Profondita massima
                        <span class="font-normal text-slate-400">(livelli di link)</span>
                    </label>
                    <input type="number" name="max_depth" x-model="maxDepth" min="1" max="10"
                           class="w-24 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                </div>

                <!-- Request Delay -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Ritardo tra richieste
                        <span class="font-normal text-slate-400">(ms)</span>
                    </label>
                    <input type="number" name="request_delay" x-model="requestDelay" min="0" max="5000" step="100"
                           class="w-24 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                </div>

                <!-- Timeout -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Timeout
                        <span class="font-normal text-slate-400">(secondi)</span>
                    </label>
                    <input type="number" name="timeout" x-model="timeout" min="5" max="60"
                           class="w-24 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                </div>

                <!-- User Agent -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        User-Agent
                    </label>
                    <select name="user_agent" x-model="userAgent"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm">
                        <option value="googlebot">Googlebot (consigliato)</option>
                        <option value="googlebot-mobile">Googlebot Mobile</option>
                        <option value="chrome">Chrome Browser</option>
                        <option value="default">SEOToolkit Spider</option>
                    </select>
                </div>

                <!-- Hidden fields for additional config -->
                <input type="hidden" name="max_retries" :value="maxRetries">
                <input type="hidden" name="follow_redirects" value="1">
            </div>

            <!-- Cost Estimate -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Costo stimato</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Crediti disponibili: <?= number_format($credits['balance'] ?? 0, 1) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-slate-900 dark:text-white" x-text="(maxPages * crawlCost).toFixed(1)"></p>
                    <p class="text-xs text-slate-500">crediti</p>
                </div>
            </div>

            <!-- Start Button -->
            <div class="flex justify-center">
                <button type="submit" id="btn-start-crawl"
                        class="inline-flex items-center px-8 py-3 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-medium hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Avvia Scansione
                </button>
            </div>

            <!-- Import Manual/CSV Link -->
            <div class="text-center pt-2 border-t border-slate-200 dark:border-slate-700">
                <a href="<?= url('/seo-audit/project/' . $project['id'] . '/import') ?>"
                   class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Oppure importa URL manualmente o da CSV →
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Alpine.js component for config form - MUST be defined BEFORE DOMContentLoaded
// so Alpine can find it when it initializes
window.crawlConfigForm = function() {
    const crawlCost = <?= $credits['crawl_cost'] ?? 0.2 ?>;
    return {
        // Preset configurations
        presets: <?= json_encode($presets) ?>,
        currentPreset: 'bilanciato',

        // Form values
        maxPages: <?= $config['max_pages'] ?>,
        maxDepth: <?= $config['max_depth'] ?>,
        requestDelay: <?= $config['request_delay'] ?>,
        timeout: <?= $config['timeout'] ?>,
        maxRetries: <?= $config['max_retries'] ?>,
        userAgent: '<?= $config['user_agent'] ?>',
        respectRobots: <?= $config['respect_robots'] ? 'true' : 'false' ?>,
        showAdvanced: false,
        crawlCost: crawlCost,

        applyPreset(preset) {
            console.log('applyPreset called:', preset);
            this.currentPreset = preset;
            const config = this.presets[preset];
            if (config) {
                this.maxPages = config.max_pages;
                this.maxDepth = config.max_depth;
                this.requestDelay = config.request_delay;
            }
        }
    };
};

document.addEventListener('DOMContentLoaded', function() {
    const projectId = <?= $project['id'] ?>;
    const crawlCost = <?= $credits['crawl_cost'] ?? 0.2 ?>;
    const csrfToken = '<?= csrf_token() ?>';

    // Form submit handler
    const configForm = document.getElementById('crawl-config-form');
    if (configForm) {
        configForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const btn = document.getElementById('btn-start-crawl');
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Avvio...';

            try {
                const response = await fetch(`<?= url('/seo-audit/project/' . $project['id'] . '/crawl/start') ?>`, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    window.ainstein.alert(data.message || 'Errore avvio crawl', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Avvia Scansione';
                }
            } catch (err) {
                console.error('Crawl start error:', err);
                window.ainstein.alert('Errore di rete', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Avvia Scansione';
            }
        });
    }

    // Stop crawl handler
    const stopBtn = document.getElementById('btn-stop-crawl');
    if (stopBtn) {
        stopBtn.addEventListener('click', async function() {
            const self = this;
            window.ainstein.confirm('Vuoi interrompere la scansione?', {destructive: false, buttonText: 'Conferma'}).then(async () => {

            self.disabled = true;
            this.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Arresto...';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);

                const response = await fetch(`<?= url('/seo-audit/project') ?>/${projectId}/crawl/stop`, {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    window.ainstein.alert(data.message || 'Errore stop crawl', 'error');
                }
            } catch (err) {
                console.error('Errore:', err);
                window.ainstein.alert('Errore di rete', 'error');
            }
            }).catch(() => {});
        });
    }

    // Polling for progress (if crawling)
    <?php if ($isCrawling && !$isStopping): ?>
    let pollInterval;
    let batchInProgress = false;

    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    function updateUI(data) {
        if (data.progress) {
            document.getElementById('progress-bar').style.width = data.progress.percent + '%';
            document.getElementById('progress-text').textContent = data.progress.pages_crawled + '/' + data.progress.pages_found + ' pagine';
            document.getElementById('stat-pages').textContent = data.progress.pages_crawled;
            document.getElementById('stat-percent').textContent = Math.round(data.progress.percent) + '%';
            document.getElementById('stat-time').textContent = formatTime(data.progress.elapsed_seconds || 0);

            if (data.progress.current_url) {
                document.getElementById('current-url').textContent = data.progress.current_url;
            }
        }

        if (data.issues) {
            const totalIssues = (data.issues.critical || 0) + (data.issues.warning || 0) + (data.issues.notice || 0);
            document.getElementById('stat-issues').textContent = totalIssues;
        }
    }

    async function processBatch() {
        if (batchInProgress) return;
        batchInProgress = true;

        // Mostra indicatore elaborazione
        const currentUrlEl = document.getElementById('current-url');
        if (currentUrlEl && currentUrlEl.textContent === 'In attesa...') {
            currentUrlEl.textContent = 'Elaborazione in corso...';
        }

        try {
            const formData = new FormData();
            formData.append('_csrf_token', csrfToken);
            formData.append('batch_size', '2');

            const response = await fetch(`<?= url('/seo-audit/project') ?>/${projectId}/crawl/batch`, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            console.log('Batch response:', data); // Debug

            if (data.error) {
                console.error('Batch error:', data.message);
            }

            if (data.progress) {
                updateUI(data);
            }

            if (data.complete || data.stopped) {
                clearInterval(pollInterval);
                window.location.reload();
                return;
            }

        } catch (err) {
            console.error('Batch error:', err);
        } finally {
            batchInProgress = false;
        }
    }

    // Polling ogni 1.5 secondi per aggiornamenti frequenti
    pollInterval = setInterval(() => {
        processBatch();
    }, 1500);

    processBatch();
    <?php endif; ?>
});
</script>
