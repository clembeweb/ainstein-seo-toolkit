<?php
/**
 * Dashboard SEO Meta Tag
 * Mostra statistiche e azioni rapide per la gestione dei meta tag
 */
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <?= \Core\View::partial('components/dashboard-stats-row', [
        'cards' => [
            [
                'label' => 'Totale URL',
                'value' => $stats['total'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                'color' => 'blue',
            ],
            [
                'label' => 'Scrappate',
                'value' => $stats['scraped'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>',
                'color' => 'cyan',
            ],
            [
                'label' => 'Generate',
                'value' => $stats['generated'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
                'color' => 'purple',
            ],
            [
                'label' => 'Pubblicate',
                'value' => $stats['published'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                'color' => 'emerald',
            ],
        ],
    ]) ?>

    <?php if ($stats['errors'] > 0): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-red-700 dark:text-red-300 font-medium"><?= $stats['errors'] ?> URL con errori</span>
            </div>
            <form action="<?= url("/ai-content/projects/{$project['id']}/meta-tags/reset-scrape-errors") ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 underline">
                    Riprova
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Import -->
        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/import") ?>"
           class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-900 transition-colors">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Importa URL</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">WordPress, Sitemap, CSV</p>
                </div>
            </div>
        </a>

        <!-- Scrape -->
        <button type="button"
                onclick="runScrape()"
                <?= $stats['pending'] === 0 ? 'disabled' : '' ?>
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group text-left disabled:opacity-50 disabled:cursor-not-allowed">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-900 transition-colors">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Scrape Pagine</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $stats['pending'] ?> in attesa (1 cr/pag)</p>
                </div>
            </div>
        </button>

        <!-- Generate -->
        <button type="button"
                onclick="runGenerate()"
                <?= $stats['scraped'] === 0 ? 'disabled' : '' ?>
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group text-left disabled:opacity-50 disabled:cursor-not-allowed">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900 transition-colors">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Genera Meta Tag</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $stats['scraped'] ?> pronte (2 cr/pag)</p>
                </div>
            </div>
        </button>

        <!-- View All -->
        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>"
           class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-slate-200 dark:group-hover:bg-slate-600 transition-colors">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Vedi Tutti</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $stats['total'] ?> meta tag</p>
                </div>
            </div>
        </a>
    </div>

    <!-- WordPress Status -->
    <?php if ($wpSite): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white"><?= e($wpSite['name']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($wpSite['url']) ?></p>
                </div>
            </div>
            <span class="px-2 py-1 text-xs font-medium rounded-full <?= $wpSite['is_active'] ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                <?= $wpSite['is_active'] ? 'Connesso' : 'Disconnesso' ?>
            </span>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-medium text-amber-700 dark:text-amber-300">Nessun sito WordPress collegato</p>
                <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                    Per importare pagine e pubblicare meta tag su WordPress, collega un sito nelle
                    <a href="<?= url("/ai-content/projects/{$project['id']}/settings") ?>" class="underline">impostazioni</a>.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Meta Tags -->
    <?php if (!empty($recent)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-semibold text-slate-900 dark:text-white">Ultimi Meta Tag</h2>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($recent as $item): ?>
            <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$item['id']}") ?>"
                           class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block">
                            <?= e($item['original_title'] ?: $item['url']) ?>
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($item['url']) ?></p>
                    </div>
                    <span class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full
                        <?php
                        switch ($item['status']) {
                            case 'pending':
                                echo 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';
                                break;
                            case 'scraped':
                                echo 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                                break;
                            case 'generated':
                                echo 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                                break;
                            case 'approved':
                                echo 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                                break;
                            case 'published':
                                echo 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300';
                                break;
                            case 'error':
                                echo 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                                break;
                        }
                        ?>">
                        <?= ucfirst($item['status']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>"
               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                Vedi tutti i meta tag
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Workflow Guide -->
    <?= \Core\View::partial('components/dashboard-how-it-works', [
        'color' => 'amber',
        'steps' => [
            ['title' => 'Importa', 'description' => 'URL da WP/Sitemap/CSV'],
            ['title' => 'Scrape', 'description' => 'Analizza contenuto pagine'],
            ['title' => 'Genera', 'description' => 'AI crea title e description'],
            ['title' => 'Approva', 'description' => 'Rivedi e modifica'],
            ['title' => 'Pubblica', 'description' => 'Invia a WordPress'],
        ],
    ]) ?>
</div>

<!-- Progress Modal con SSE -->
<div id="progressModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-lg w-full mx-4 p-6">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h3 id="progressTitle" class="text-lg font-semibold text-slate-900 dark:text-white">Elaborazione in corso...</h3>
                <button id="cancelBtn" onclick="cancelJob()" class="text-slate-400 hover:text-red-500 transition-colors hidden">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span id="progressCount" class="text-slate-600 dark:text-slate-400">0 / 0</span>
                    <span id="progressPercent" class="font-medium text-primary-600">0%</span>
                </div>
                <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-primary-500 transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Current Item -->
            <div id="currentItemContainer" class="hidden">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">URL corrente:</p>
                <p id="currentItem" class="text-sm text-slate-700 dark:text-slate-300 truncate font-mono bg-slate-100 dark:bg-slate-700/50 px-2 py-1 rounded"></p>
            </div>

            <!-- Status Message -->
            <p id="progressMessage" class="text-sm text-slate-500 dark:text-slate-400 text-center"></p>

            <!-- Results (shown on completion) -->
            <div id="resultsContainer" class="hidden bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div id="resultCompleted" class="text-xl font-bold text-emerald-600">0</div>
                        <div class="text-xs text-slate-500">Completati</div>
                    </div>
                    <div>
                        <div id="resultFailed" class="text-xl font-bold text-red-500">0</div>
                        <div class="text-xs text-slate-500">Errori</div>
                    </div>
                    <div>
                        <div id="resultCredits" class="text-xl font-bold text-amber-600">0</div>
                        <div class="text-xs text-slate-500">Crediti</div>
                    </div>
                </div>
            </div>

            <!-- Close Button (shown on completion) -->
            <button id="closeBtn" onclick="closeAndReload()" class="hidden w-full py-2 px-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                Chiudi e aggiorna
            </button>
        </div>
    </div>
</div>

<script>
const projectId = <?= $project['id'] ?>;
const csrfToken = '<?= csrf_token() ?>';
const baseUrl = '<?= url("/ai-content/projects/{$project['id']}/meta-tags") ?>';

let currentJobId = null;
let eventSource = null;
let pollingInterval = null;

function showProgress(title, message) {
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressMessage').textContent = message;
    document.getElementById('progressModal').style.display = 'flex';
    document.getElementById('cancelBtn').classList.remove('hidden');
    document.getElementById('closeBtn').classList.add('hidden');
    document.getElementById('resultsContainer').classList.add('hidden');
    document.getElementById('currentItemContainer').classList.add('hidden');
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressCount').textContent = '0 / 0';
}

function hideProgress() {
    document.getElementById('progressModal').style.display = 'none';
    if (eventSource) {
        eventSource.close();
        eventSource = null;
    }
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    currentJobId = null;
}

function updateProgress(completed, total, percent) {
    document.getElementById('progressBar').style.width = percent + '%';
    document.getElementById('progressPercent').textContent = percent + '%';
    document.getElementById('progressCount').textContent = completed + ' / ' + total;
}

function showCurrentItem(url) {
    document.getElementById('currentItemContainer').classList.remove('hidden');
    document.getElementById('currentItem').textContent = url;
}

function showResults(completed, failed, credits) {
    document.getElementById('cancelBtn').classList.add('hidden');
    document.getElementById('closeBtn').classList.remove('hidden');
    document.getElementById('resultsContainer').classList.remove('hidden');
    document.getElementById('resultCompleted').textContent = completed;
    document.getElementById('resultFailed').textContent = failed;
    document.getElementById('resultCredits').textContent = credits;
    document.getElementById('currentItemContainer').classList.add('hidden');
}

function closeAndReload() {
    hideProgress();
    location.reload();
}

async function cancelJob() {
    if (!currentJobId) return;

    try {
        await window.ainstein.confirm('Vuoi annullare il job? Le pagine gia scrappate verranno salvate.', {destructive: true});
    } catch (e) { return; }

    try {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('job_id', currentJobId);

        const response = await fetch(baseUrl + '/cancel-scrape-job', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (!data.success) {
            console.error('Cancel failed:', data.error);
        }
    } catch (error) {
        console.error('Cancel error:', error);
    }
}

async function runScrape() {
    showProgress('Avvio scraping...', 'Preparazione del job in corso');

    try {
        // 1. Avvia il job
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);

        const response = await fetch(baseUrl + '/start-scrape-job', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            document.getElementById('progressMessage').textContent = 'Errore: ' + data.error;
            document.getElementById('cancelBtn').classList.add('hidden');
            document.getElementById('closeBtn').classList.remove('hidden');
            document.getElementById('closeBtn').textContent = 'Chiudi';
            document.getElementById('closeBtn').onclick = hideProgress;
            return;
        }

        currentJobId = data.job_id;
        document.getElementById('progressTitle').textContent = 'Scraping pagine...';
        document.getElementById('progressMessage').textContent = data.items_queued + ' pagine da elaborare';
        updateProgress(0, data.items_queued, 0);

        // 2. Connetti SSE
        connectSSE(data.job_id, data.items_queued);

    } catch (error) {
        console.error('Start job error:', error);
        document.getElementById('progressMessage').textContent = 'Errore di connessione. Riprova.';
        document.getElementById('cancelBtn').classList.add('hidden');
        document.getElementById('closeBtn').classList.remove('hidden');
        document.getElementById('closeBtn').textContent = 'Chiudi';
        document.getElementById('closeBtn').onclick = hideProgress;
    }
}

function connectSSE(jobId, totalItems) {
    const streamUrl = baseUrl + '/scrape-stream?job_id=' + jobId;
    eventSource = new EventSource(streamUrl);

    eventSource.addEventListener('started', (e) => {
        const data = JSON.parse(e.data);
        document.getElementById('progressMessage').textContent = 'Elaborazione avviata';
    });

    eventSource.addEventListener('progress', (e) => {
        const data = JSON.parse(e.data);
        updateProgress(data.completed, data.total, data.percent);
        showCurrentItem(data.current_url);
        document.getElementById('progressMessage').textContent = 'Analisi contenuto...';
    });

    eventSource.addEventListener('item_completed', (e) => {
        const data = JSON.parse(e.data);
        document.getElementById('progressMessage').textContent = 'Completato: ' + (data.title || data.url);
    });

    eventSource.addEventListener('item_error', (e) => {
        const data = JSON.parse(e.data);
        document.getElementById('progressMessage').textContent = 'Errore: ' + data.error;
    });

    eventSource.addEventListener('completed', (e) => {
        const data = JSON.parse(e.data);
        eventSource.close();
        eventSource = null;
        document.getElementById('progressTitle').textContent = 'Scraping completato';
        document.getElementById('progressMessage').textContent = '';
        updateProgress(data.total_completed, data.total_completed + data.total_failed, 100);
        showResults(data.total_completed, data.total_failed, data.credits_used);
    });

    eventSource.addEventListener('cancelled', (e) => {
        const data = JSON.parse(e.data);
        eventSource.close();
        eventSource = null;
        document.getElementById('progressTitle').textContent = 'Job annullato';
        document.getElementById('progressMessage').textContent = data.message;
        showResults(data.completed, 0, 0);
    });

    eventSource.onerror = (e) => {
        console.warn('SSE error, switching to polling');
        eventSource.close();
        eventSource = null;
        startPolling(jobId);
    };
}

function startPolling(jobId) {
    document.getElementById('progressMessage').textContent = 'Verifica stato...';

    pollingInterval = setInterval(async () => {
        try {
            const response = await fetch(baseUrl + '/scrape-job-status?job_id=' + jobId);
            const data = await response.json();

            if (!data.success) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                document.getElementById('progressMessage').textContent = 'Errore: ' + data.error;
                return;
            }

            const job = data.job;
            const total = job.items_requested;
            const completed = job.items_completed + job.items_failed;

            updateProgress(completed, total, job.progress);

            if (job.current_item) {
                showCurrentItem(job.current_item);
            }

            if (job.status === 'completed') {
                clearInterval(pollingInterval);
                pollingInterval = null;
                document.getElementById('progressTitle').textContent = 'Scraping completato';
                document.getElementById('progressMessage').textContent = '';
                showResults(job.items_completed, job.items_failed, job.credits_used);
            } else if (job.status === 'cancelled') {
                clearInterval(pollingInterval);
                pollingInterval = null;
                document.getElementById('progressTitle').textContent = 'Job annullato';
                showResults(job.items_completed, job.items_failed, job.credits_used);
            } else if (job.status === 'error') {
                clearInterval(pollingInterval);
                pollingInterval = null;
                document.getElementById('progressTitle').textContent = 'Errore';
                document.getElementById('progressMessage').textContent = job.error_message || 'Errore sconosciuto';
                showResults(job.items_completed, job.items_failed, job.credits_used);
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }, 2000);
}

async function runGenerate() {
    showProgress('Generazione meta tag...', 'AI sta elaborando i contenuti');
    document.getElementById('cancelBtn').classList.add('hidden');

    try {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('batch_size', '10');

        const response = await fetch(baseUrl + '/generate', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('progressTitle').textContent = 'Generazione completata';
            document.getElementById('progressMessage').textContent = data.message;
            showResults(data.generated, data.errors, data.generated * 2);
        } else {
            document.getElementById('progressTitle').textContent = 'Errore';
            document.getElementById('progressMessage').textContent = data.error;
            document.getElementById('closeBtn').classList.remove('hidden');
            document.getElementById('closeBtn').textContent = 'Chiudi';
            document.getElementById('closeBtn').onclick = hideProgress;
        }
    } catch (error) {
        document.getElementById('progressTitle').textContent = 'Errore';
        document.getElementById('progressMessage').textContent = 'Errore di connessione';
        document.getElementById('closeBtn').classList.remove('hidden');
        document.getElementById('closeBtn').textContent = 'Chiudi';
        document.getElementById('closeBtn').onclick = hideProgress;
    }
}
</script>
