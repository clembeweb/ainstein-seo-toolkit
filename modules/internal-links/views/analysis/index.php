<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/internal-links') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Internal Links</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="<?= url('/internal-links/project/' . $project['id']) ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white">Analisi AI</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Analisi AI Link</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Analisi della rilevanza dei link interni con intelligenza artificiale</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Panel -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Progress Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Stato Analisi</h3>
                    <span id="analysis-status" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        In attesa
                    </span>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p id="stat-analyzed" class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['analyzed']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Analizzati</p>
                    </div>
                    <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p id="stat-pending" class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($stats['pending']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">In attesa</p>
                    </div>
                    <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400"><?= $stats['avg_score'] ? number_format($stats['avg_score'], 1) : '-' ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Score Medio</p>
                    </div>
                </div>

                <!-- Progress bar -->
                <?php $progress = ($stats['analyzed'] + $stats['pending']) > 0 ? ($stats['analyzed'] / ($stats['analyzed'] + $stats['pending'])) * 100 : 0; ?>
                <div class="mb-6">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-slate-500 dark:text-slate-400">Progresso</span>
                        <span id="progress-percent" class="font-medium text-slate-900 dark:text-white"><?= number_format($progress, 0) ?>%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div id="progress-bar" class="h-full bg-primary-500 rounded-full transition-all" style="width: <?= $progress ?>%"></div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-3">
                    <button type="button" id="btn-start" class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors" <?= $stats['pending'] === 0 ? 'disabled' : '' ?>>
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        Avvia Analisi AI
                    </button>
                    <button type="button" id="btn-stop" class="hidden px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Stop
                    </button>
                </div>
            </div>

            <!-- Score Distribution -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Score</h3>
                <div class="space-y-3">
                    <?php
                    $distribution = $stats['distribution'] ?? [];
                    $maxCount = max(array_column($distribution, 'count') ?: [1]);
                    $ranges = [
                        ['min' => 0, 'max' => 20, 'label' => '0-20', 'color' => 'red'],
                        ['min' => 20, 'max' => 40, 'label' => '20-40', 'color' => 'orange'],
                        ['min' => 40, 'max' => 60, 'label' => '40-60', 'color' => 'amber'],
                        ['min' => 60, 'max' => 80, 'label' => '60-80', 'color' => 'lime'],
                        ['min' => 80, 'max' => 100, 'label' => '80-100', 'color' => 'emerald'],
                    ];
                    foreach ($ranges as $range):
                        $count = 0;
                        foreach ($distribution as $d) {
                            if ($d['range_min'] >= $range['min'] && $d['range_min'] < $range['max']) {
                                $count = $d['count'];
                                break;
                            }
                        }
                        $width = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                    ?>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 dark:text-slate-400 w-14"><?= $range['label'] ?></span>
                        <div class="flex-1 h-6 bg-slate-100 dark:bg-slate-700 rounded overflow-hidden">
                            <div class="h-full bg-<?= $range['color'] ?>-500 rounded" style="width: <?= $width ?>%"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white w-12 text-right"><?= number_format($count) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Analysis Log -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Log Analisi</h3>
                    <button type="button" id="btn-clear-log" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700">Pulisci</button>
                </div>
                <div id="analysis-log" class="p-4 h-48 overflow-y-auto font-mono text-xs bg-slate-900 text-slate-100 space-y-1">
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
                        <input type="number" id="batch-size" value="5" min="1" max="20" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Link per batch (1-20)</p>
                    </div>
                    <div class="flex items-center text-sm text-slate-500 dark:text-slate-400">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Modello AI configurato dall'amministratore</span>
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
                        <span class="text-sm text-slate-500 dark:text-slate-400">Costo per analisi</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?= $credits['cost_per_analysis'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Link analizzabili</span>
                        <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400"><?= number_format(floor($credits['available'] / max(1, $credits['cost_per_analysis']))) ?></span>
                    </div>
                </div>
            </div>

            <!-- What AI Analyzes -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Cosa Analizza</h3>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-primary-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Rilevanza semantica
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-primary-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Qualita anchor text
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-primary-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Contesto del link
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-primary-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Suggerimenti miglioramento
                    </li>
                </ul>
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
    status: document.getElementById('analysis-status'),
    progressBar: document.getElementById('progress-bar'),
    progressPercent: document.getElementById('progress-percent'),
    statAnalyzed: document.getElementById('stat-analyzed'),
    statPending: document.getElementById('stat-pending'),
    log: document.getElementById('analysis-log'),
    batchSize: document.getElementById('batch-size')
};

elements.btnStart.addEventListener('click', startAnalysis);
elements.btnStop.addEventListener('click', stopAnalysis);
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
    const total = data.analyzed + data.pending;
    const percent = total > 0 ? Math.round((data.analyzed / total) * 100) : 0;

    elements.progressBar.style.width = percent + '%';
    elements.progressPercent.textContent = percent + '%';
    elements.statAnalyzed.textContent = data.analyzed.toLocaleString();
    elements.statPending.textContent = data.pending.toLocaleString();
}

async function startAnalysis() {
    if (isRunning) return;

    isRunning = true;
    shouldStop = false;

    elements.btnStart.classList.add('hidden');
    elements.btnStop.classList.remove('hidden');
    elements.status.textContent = 'In esecuzione';
    elements.status.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';

    log('Avvio analisi AI...', 'info');

    const batchSize = parseInt(elements.batchSize.value) || 5;

    while (!shouldStop) {
        try {
            const response = await fetch(`<?= url('/internal-links/project/' . $project['id']) ?>/analysis/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ batch_size: batchSize })
            });

            const data = await response.json();

            if (!data.success) {
                log(data.error || 'Errore durante l\'analisi', 'error');
                break;
            }

            if (data.results) {
                data.results.forEach(r => {
                    if (r.success) {
                        log(`✓ Link analizzato - Score: ${r.score}`, 'success');
                    } else {
                        log(`✗ Errore analisi: ${r.error}`, 'error');
                    }
                });
            }

            updateUI(data.progress);

            if (data.progress.pending === 0) {
                log('Analisi completata!', 'success');
                break;
            }

            // Delay between batches to avoid rate limiting
            await new Promise(r => setTimeout(r, 1000));

        } catch (error) {
            log('Errore di rete: ' + error.message, 'error');
            break;
        }
    }

    stopAnalysis();
}

function stopAnalysis() {
    shouldStop = true;
    isRunning = false;

    elements.btnStart.classList.remove('hidden');
    elements.btnStop.classList.add('hidden');
    elements.status.textContent = 'Fermo';
    elements.status.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';

    log('Analisi fermata', 'warning');
}
</script>
