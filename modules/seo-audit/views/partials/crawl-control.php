<?php
/**
 * Crawl Control Partial - Spider Integrato
 *
 * Widget unificato per configurazione e controllo crawl
 * Include preset rapidi e impostazioni avanzate
 * Usa SSE (Server-Sent Events) con polling fallback per progress real-time
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
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
        'desc' => '100 pagine, profondità 2',
        'max_pages' => 100,
        'max_depth' => 2,
        'request_delay' => 0,
    ],
    'bilanciato' => [
        'label' => 'Bilanciato',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>',
        'desc' => '500 pagine, profondità 3',
        'max_pages' => 500,
        'max_depth' => 3,
        'request_delay' => 200,
    ],
    'completo' => [
        'label' => 'Completo',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
        'desc' => '2000 pagine, profondità 5',
        'max_pages' => 2000,
        'max_depth' => 5,
        'request_delay' => 500,
    ],
];

// Accordion: chiuso se ha già scansioni completate (e non sta crawlando)
$hasCompletedScans = (($project['pages_crawled'] ?? 0) > 0) && !$isCrawling;
$lastScanDate = $project['completed_at'] ?? null;

// Stato iniziale per Alpine.js (server-side seeding)
$initialStatus = 'idle';
if ($isStopping) {
    $initialStatus = 'cancelling';
} elseif ($isCrawling) {
    $initialStatus = 'running';
}
?>

<!-- Crawl Control Card con Accordion -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
     id="crawl-control"
     x-data="{ expanded: <?= $hasCompletedScans ? 'false' : 'true' ?> }">

    <!-- Header (sempre visibile, cliccabile se ha scansioni) -->
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between <?= $hasCompletedScans ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors' : '' ?>"
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
    <div x-show="expanded" x-collapse class="p-6" x-data="crawlJob()">

        <!-- ======================== -->
        <!-- CRAWL IN PROGRESS (SSE)  -->
        <!-- ======================== -->
        <div x-show="status !== 'idle'" x-cloak>

            <!-- Status message -->
            <div x-show="status === 'starting'" class="text-center py-4 mb-4">
                <svg class="w-8 h-8 mx-auto mb-2 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Avvio scansione in corso...</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Ricerca URL nel sito</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4" x-show="status === 'running' || status === 'cancelling'">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-slate-600 dark:text-slate-400">Progresso</span>
                    <span class="font-medium text-slate-900 dark:text-white" x-text="completed + '/' + total + ' pagine'"></span>
                </div>
                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500"
                         :style="'width: ' + percent + '%'"></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4" x-show="status === 'running' || status === 'cancelling'">
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="completed"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pagine</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="issues"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Issues</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="percent + '%'"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Completato</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="elapsed"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tempo</p>
                </div>
            </div>

            <!-- Current URL -->
            <div class="mb-4" x-show="status === 'running' || status === 'cancelling'">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">URL corrente:</p>
                <p class="text-sm text-slate-700 dark:text-slate-300 truncate font-mono"
                   x-text="currentUrl || 'In attesa...'"></p>
            </div>

            <!-- Stop/Cancel Button -->
            <div class="flex justify-center" x-show="status === 'running'">
                <button type="button" @click="cancelCrawl()"
                        class="inline-flex items-center px-6 py-3 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                    Interrompi Scansione
                </button>
            </div>

            <!-- Cancelling state -->
            <div class="text-center text-amber-600 dark:text-amber-400" x-show="status === 'cancelling'">
                <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm font-medium">Arresto in corso...</p>
                <p class="text-xs">Il crawl si fermera al termine della pagina corrente</p>
            </div>

            <!-- Completed state -->
            <div class="text-center py-4" x-show="status === 'completed'">
                <svg class="w-12 h-12 mx-auto mb-2 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Scansione completata!</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Caricamento risultati...</p>
            </div>

            <!-- Cancelled state -->
            <div class="text-center py-4" x-show="status === 'cancelled'">
                <svg class="w-12 h-12 mx-auto mb-2 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Scansione annullata</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Caricamento risultati parziali...</p>
            </div>

            <!-- Error state -->
            <div class="text-center py-4" x-show="status === 'error'">
                <svg class="w-12 h-12 mx-auto mb-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-red-600 dark:text-red-400" x-text="errorMsg || 'Errore durante la scansione'"></p>
                <button type="button" @click="status = 'idle'" class="mt-3 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                    Riprova
                </button>
            </div>

            <!-- Polling indicator -->
            <div class="text-center mt-3" x-show="polling">
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    <svg class="inline w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Connessione in modalita polling
                </p>
            </div>
        </div>

        <!-- ======================== -->
        <!-- CONFIGURATION FORM       -->
        <!-- ======================== -->
        <div x-show="status === 'idle'" x-data="crawlConfigForm()">
        <form class="space-y-6" @submit.prevent="$dispatch('crawl-start', { form: $event.target })">
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
                        <span class="text-primary-500 dark:text-primary-400"><?= $preset['icon'] ?></span>
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
                <button type="submit"
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
        </div>

    </div>
</div>

<script>
/**
 * Alpine.js component: Crawl Config Form
 * Manages preset selection and form values.
 * MUST be defined BEFORE DOMContentLoaded so Alpine can find it.
 */
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

/**
 * Alpine.js component: Crawl Job (SSE + Polling Fallback)
 *
 * Manages the full crawl lifecycle:
 * 1. Check for active job on init (recover after navigation)
 * 2. Start crawl via POST /crawl/start
 * 3. Connect to SSE stream for real-time progress
 * 4. Fall back to polling if SSE disconnects (proxy timeout)
 * 5. Cancel crawl via POST /crawl/cancel-job
 */
window.crawlJob = function() {
    const projectId = <?= $project['id'] ?? 0 ?>;
    const baseUrl = '<?= url('/seo-audit/project/' . ($project['id'] ?? 0)) ?>';
    const csrfToken = '<?= csrf_token() ?>';

    // Server-side initial state
    const initialStatus = '<?= $initialStatus ?>';
    const initialCompleted = <?= $session ? (int)($session['pages_crawled'] ?? 0) : 0 ?>;
    const initialTotal = <?= $session ? (int)($session['pages_found'] ?? 0) : 0 ?>;
    const initialIssues = <?= $session ? (int)($session['issues_found'] ?? 0) : 0 ?>;
    const initialPercent = <?= round($progressPercent) ?>;
    const initialCurrentUrl = '<?= $session && ($session['current_url'] ?? '') ? addslashes($session['current_url']) : '' ?>';

    return {
        // State
        jobId: null,
        status: initialStatus,   // idle, starting, running, completed, cancelled, cancelling, error
        eventSource: null,
        polling: false,
        errorMsg: '',

        // Progress
        completed: initialCompleted,
        total: initialTotal,
        failed: 0,
        percent: initialPercent,
        currentUrl: initialCurrentUrl,
        issues: initialIssues,
        elapsed: '0:00',

        // Timer
        _startTime: null,
        _timerInterval: null,

        /**
         * Init: check for active job on page load.
         * If the user navigated away and came back, we recover the job state.
         */
        async init() {
            // Listen for form submit from child crawlConfigForm component
            this.$el.addEventListener('crawl-start', (e) => {
                this.startCrawl(e.detail.form);
            });

            // If server already knows we're crawling, check for active job
            if (this.status === 'running' || this.status === 'cancelling') {
                try {
                    const resp = await fetch(baseUrl + '/crawl/status');
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.active_job_id) {
                            this.jobId = data.active_job_id;
                            // Recover progress from status response
                            if (data.session) {
                                this.completed = data.session.pages_crawled || this.completed;
                                this.total = data.session.pages_found || this.total;
                                this.percent = this.total > 0 ? Math.round((this.completed / this.total) * 100) : 0;
                                this.currentUrl = data.session.current_url || this.currentUrl;
                            }
                            if (data.issues) {
                                this.issues = (data.issues.critical || 0) + (data.issues.warning || 0) + (data.issues.notice || 0);
                            }
                            // Connect to SSE to get live updates
                            this.startTimer();
                            this.connectSSE();
                        } else {
                            // No active job but server thought we were crawling - refresh
                            this.status = 'idle';
                        }
                    }
                } catch (e) {
                    // Network error, try polling
                    if (this.status === 'running') {
                        this.startPolling();
                    }
                }
            }
        },

        /**
         * Start crawl: POST form data to /crawl/start, then connect SSE.
         */
        async startCrawl(form) {
            this.status = 'starting';
            this.completed = 0;
            this.total = 0;
            this.failed = 0;
            this.percent = 0;
            this.issues = 0;
            this.currentUrl = '';
            this.elapsed = '0:00';
            this.errorMsg = '';

            const formData = new FormData(form);

            try {
                const resp = await fetch(baseUrl + '/crawl/start', {
                    method: 'POST',
                    body: formData,
                });

                if (!resp.ok) {
                    const text = await resp.text();
                    try {
                        const err = JSON.parse(text);
                        this.showError(err.message || err.error || 'Errore durante l\'avvio');
                    } catch (_) {
                        this.showError('Errore di connessione');
                    }
                    this.status = 'error';
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    this.jobId = data.job_id;
                    this.total = data.urls_found || 0;
                    this.status = 'running';
                    this.startTimer();
                    this.connectSSE();
                } else {
                    this.showError(data.message || data.error || 'Errore durante l\'avvio');
                    this.status = 'error';
                }
            } catch (e) {
                this.showError('Errore di connessione al server');
                this.status = 'error';
            }
        },

        /**
         * Connect to SSE stream for real-time progress.
         */
        connectSSE() {
            if (this.eventSource) {
                this.eventSource.close();
            }

            this.polling = false;
            this.eventSource = new EventSource(
                baseUrl + '/crawl/stream?job_id=' + this.jobId
            );

            this.eventSource.addEventListener('started', (e) => {
                const d = JSON.parse(e.data);
                this.total = d.total || this.total;
                this.status = 'running';
            });

            this.eventSource.addEventListener('page_completed', (e) => {
                const d = JSON.parse(e.data);
                this.completed = d.completed;
                this.total = d.total;
                this.percent = d.percent || Math.round((d.completed / Math.max(d.total, 1)) * 100);
                this.currentUrl = d.url || '';
                this.issues += (d.issues || 0);
            });

            this.eventSource.addEventListener('page_error', (e) => {
                const d = JSON.parse(e.data);
                this.failed++;
                this.currentUrl = d.url || '';
            });

            this.eventSource.addEventListener('completed', (e) => {
                this.eventSource.close();
                this.eventSource = null;
                this.stopTimer();
                this.status = 'completed';
                this.percent = 100;
                if (window.ainstein && window.ainstein.toast) {
                    window.ainstein.toast('Scansione completata! ' + this.completed + ' pagine analizzate.', 'success');
                }
                // Reload page to show results
                setTimeout(() => location.reload(), 2000);
            });

            this.eventSource.addEventListener('cancelled', (e) => {
                this.eventSource.close();
                this.eventSource = null;
                this.stopTimer();
                this.status = 'cancelled';
                if (window.ainstein && window.ainstein.toast) {
                    window.ainstein.toast('Scansione annullata.', 'warning');
                }
                setTimeout(() => location.reload(), 2000);
            });

            // Server-sent error event (with data)
            this.eventSource.addEventListener('error', (e) => {
                try {
                    const d = JSON.parse(e.data);
                    this.showError(d.message || 'Errore dal server');
                    this.eventSource.close();
                    this.eventSource = null;
                    this.stopTimer();
                    this.status = 'error';
                } catch (_) {
                    // Native SSE error (no data) - handled by onerror below
                }
            });

            // Native SSE error = disconnection (proxy timeout on SiteGround)
            // Backend continues with ignore_user_abort(true), so we fall back to polling
            this.eventSource.onerror = () => {
                this.eventSource.close();
                this.eventSource = null;
                // Don't change status - backend continues processing
                this.startPolling();
            };
        },

        /**
         * Polling fallback when SSE disconnects.
         * Uses GET /crawl/job-status endpoint to read progress from DB.
         */
        async startPolling() {
            if (this.polling) return; // Avoid duplicate polling loops
            this.polling = true;

            while (this.polling && this.status === 'running') {
                try {
                    const resp = await fetch(baseUrl + '/crawl/job-status?job_id=' + this.jobId);
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.success && data.job) {
                            const job = data.job;
                            this.completed = job.items_completed || 0;
                            this.total = job.items_total || 0;
                            this.failed = job.items_failed || 0;
                            this.currentUrl = job.current_item || '';
                            this.percent = Math.round(job.progress || 0);

                            // Update issues from session stats
                            if (data.issues) {
                                this.issues = (data.issues.critical || 0) + (data.issues.warning || 0) + (data.issues.notice || 0);
                            }

                            if (job.status === 'completed') {
                                this.polling = false;
                                this.stopTimer();
                                this.status = 'completed';
                                this.percent = 100;
                                if (window.ainstein && window.ainstein.toast) {
                                    window.ainstein.toast('Scansione completata! ' + this.completed + ' pagine analizzate.', 'success');
                                }
                                setTimeout(() => location.reload(), 2000);
                                return;
                            }

                            if (job.status === 'cancelled') {
                                this.polling = false;
                                this.stopTimer();
                                this.status = 'cancelled';
                                if (window.ainstein && window.ainstein.toast) {
                                    window.ainstein.toast('Scansione annullata.', 'warning');
                                }
                                setTimeout(() => location.reload(), 2000);
                                return;
                            }

                            if (job.status === 'error') {
                                this.polling = false;
                                this.stopTimer();
                                this.showError(job.error_message || 'Errore durante la scansione');
                                this.status = 'error';
                                return;
                            }
                        }
                    }
                } catch (e) {
                    // Network error, continue polling
                }
                // Poll every 3 seconds
                await new Promise(r => setTimeout(r, 3000));
            }
        },

        /**
         * Cancel the running crawl job.
         * The SSE stream will receive a 'cancelled' event from the server.
         */
        async cancelCrawl() {
            if (!confirm('Vuoi annullare il crawl in corso?')) return;

            this.status = 'cancelling';

            try {
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                formData.append('job_id', this.jobId);

                await fetch(baseUrl + '/crawl/cancel-job', {
                    method: 'POST',
                    body: formData,
                });
                // The SSE stream or polling will detect the cancellation
            } catch (e) {
                this.showError('Errore durante l\'annullamento');
                this.status = 'running'; // Revert, let SSE/polling handle it
            }
        },

        /**
         * Show error message via toast or inline.
         */
        showError(msg) {
            this.errorMsg = msg;
            if (window.ainstein && window.ainstein.alert) {
                window.ainstein.alert(msg, 'error');
            }
        },

        /**
         * Start elapsed time counter.
         */
        startTimer() {
            this._startTime = Date.now();
            this.stopTimer(); // Clear any existing timer
            this._timerInterval = setInterval(() => {
                const seconds = Math.floor((Date.now() - this._startTime) / 1000);
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                this.elapsed = mins + ':' + secs.toString().padStart(2, '0');
            }, 1000);
        },

        /**
         * Stop elapsed time counter.
         */
        stopTimer() {
            if (this._timerInterval) {
                clearInterval(this._timerInterval);
                this._timerInterval = null;
            }
        },

        /**
         * Cleanup on component destroy.
         */
        destroy() {
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
            this.polling = false;
            this.stopTimer();
        }
    };
};
</script>
