<div class="space-y-6">
    <!-- Header -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/internal-links') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Internal Links</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="<?= url('/internal-links/project/' . $project['id']) ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white">Scraper</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Scraper</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Panel -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Progress Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Progresso Scraping</h3>
                    <span id="scrape-status" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        In attesa
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-slate-500 dark:text-slate-400">
                            <span id="scraped-count"><?= $progress['scraped'] ?></span> / <span id="total-count"><?= $progress['total'] ?></span> URL
                        </span>
                        <span id="progress-percent" class="font-medium text-slate-900 dark:text-white"><?= $progress['total'] > 0 ? round(($progress['scraped'] / $progress['total']) * 100) : 0 ?>%</span>
                    </div>
                    <div class="w-full h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div id="progress-bar" class="h-full bg-primary-500 rounded-full transition-all duration-300" style="width: <?= $progress['total'] > 0 ? ($progress['scraped'] / $progress['total']) * 100 : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p id="stat-success" class="text-xl font-bold text-emerald-600 dark:text-emerald-400"><?= $progress['scraped'] ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Completati</p>
                    </div>
                    <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p id="stat-pending" class="text-xl font-bold text-amber-600 dark:text-amber-400"><?= $progress['pending'] ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">In attesa</p>
                    </div>
                    <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p id="stat-errors" class="text-xl font-bold text-red-600 dark:text-red-400"><?= $progress['errors'] ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Errori</p>
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-3">
                    <button type="button" id="btn-start" class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Avvia Scraping
                    </button>
                    <button type="button" id="btn-stop" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors hidden">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Live Log -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Log Attività</h3>
                    <button type="button" id="btn-clear-log" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700">Pulisci</button>
                </div>
                <div id="scrape-log" class="p-4 h-64 overflow-y-auto font-mono text-xs bg-slate-900 text-slate-100 space-y-1">
                    <p class="text-slate-500">In attesa di avvio...</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Impostazioni</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Batch Size</label>
                        <input type="number" id="batch-size" value="10" min="1" max="50" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">URL per batch (1-50)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Delay (ms)</label>
                        <input type="number" id="delay" value="<?= $project['scrape_delay'] ?? 1000 ?>" min="100" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Delay tra richieste</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="follow-redirects" checked class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <label for="follow-redirects" class="ml-2 text-sm text-slate-700 dark:text-slate-300">Segui redirect</label>
                    </div>
                </div>
            </div>

            <!-- Credits Info -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Crediti</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Disponibili</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= number_format($credits['available']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Costo per URL</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= $credits['cost_per_scrape'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">URL scrapeabili</span>
                        <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400"><?= number_format(floor($credits['available'] / max(1, $credits['cost_per_scrape']))) ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Azioni</h3>
                <div class="space-y-3">
                    <form action="<?= url('/internal-links/project/' . $project['id'] . '/urls/reset') ?>" method="POST" onsubmit="return confirm('Vuoi resettare tutti gli URL a pending?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            Reset tutti a pending
                        </button>
                    </form>
                    <form action="<?= url('/internal-links/project/' . $project['id'] . '/urls/reset-errors') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            Reset solo errori
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let isRunning = false;
let shouldStop = false;
const projectId = <?= $project['id'] ?>;
const csrfToken = '<?= csrf_token() ?>';

const elements = {
    btnStart: document.getElementById('btn-start'),
    btnStop: document.getElementById('btn-stop'),
    status: document.getElementById('scrape-status'),
    progressBar: document.getElementById('progress-bar'),
    progressPercent: document.getElementById('progress-percent'),
    scrapedCount: document.getElementById('scraped-count'),
    totalCount: document.getElementById('total-count'),
    statSuccess: document.getElementById('stat-success'),
    statPending: document.getElementById('stat-pending'),
    statErrors: document.getElementById('stat-errors'),
    log: document.getElementById('scrape-log'),
    batchSize: document.getElementById('batch-size'),
    delay: document.getElementById('delay')
};

elements.btnStart.addEventListener('click', startScraping);
elements.btnStop.addEventListener('click', stopScraping);
document.getElementById('btn-clear-log').addEventListener('click', () => {
    elements.log.innerHTML = '<p class="text-slate-500">Log pulito...</p>';
});

function log(message, type = 'info') {
    const colors = {
        info: 'text-slate-300',
        success: 'text-emerald-400',
        error: 'text-red-400',
        warning: 'text-amber-400'
    };
    const time = new Date().toLocaleTimeString();
    const p = document.createElement('p');
    p.className = colors[type] || colors.info;
    p.textContent = `[${time}] ${message}`;
    elements.log.appendChild(p);
    elements.log.scrollTop = elements.log.scrollHeight;
}

function updateUI(data) {
    const total = data.total || parseInt(elements.totalCount.textContent.replace(/,/g, ''));
    const scraped = data.scraped;
    const pending = data.pending;
    const errors = data.errors;
    const percent = total > 0 ? Math.round((scraped / total) * 100) : 0;

    elements.scrapedCount.textContent = scraped.toLocaleString();
    elements.progressBar.style.width = percent + '%';
    elements.progressPercent.textContent = percent + '%';
    elements.statSuccess.textContent = scraped.toLocaleString();
    elements.statPending.textContent = pending.toLocaleString();
    elements.statErrors.textContent = errors.toLocaleString();
}

async function startScraping() {
    if (isRunning) return;

    isRunning = true;
    shouldStop = false;

    elements.btnStart.classList.add('hidden');
    elements.btnStop.classList.remove('hidden');
    elements.status.textContent = 'In esecuzione';
    elements.status.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';

    log('Avvio scraping...', 'info');

    const batchSize = parseInt(elements.batchSize.value) || 10;
    const delay = parseInt(elements.delay.value) || 1000;

    while (!shouldStop) {
        try {
            const response = await fetch(`<?= url('/internal-links/project/' . $project['id']) ?>/scrape/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ batch_size: batchSize, delay: delay })
            });

            const data = await response.json();

            if (!data.success) {
                log(data.error || 'Errore durante lo scraping', 'error');
                break;
            }

            if (data.results) {
                data.results.forEach(r => {
                    if (r.success) {
                        log(`✓ ${r.url} - ${r.links_found} link trovati`, 'success');
                    } else {
                        log(`✗ ${r.url} - ${r.error}`, 'error');
                    }
                });
            }

            updateUI(data.progress);

            if (data.progress.pending === 0) {
                log('Scraping completato!', 'success');
                break;
            }

            // Small delay between batches
            await new Promise(r => setTimeout(r, 500));

        } catch (error) {
            log('Errore di rete: ' + error.message, 'error');
            break;
        }
    }

    stopScraping();
}

function stopScraping() {
    shouldStop = true;
    isRunning = false;

    elements.btnStart.classList.remove('hidden');
    elements.btnStop.classList.add('hidden');
    elements.status.textContent = 'Fermo';
    elements.status.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';

    log('Scraping fermato', 'warning');
}
</script>
