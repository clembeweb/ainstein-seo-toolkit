<!-- AI Analysis Page -->
<div x-data="analysisManager()" x-init="init()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Analisi AI</h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                Analizza i link interni con intelligenza artificiale
            </p>
        </div>
    </div>

    <?php if (empty($isConfigured)): ?>
    <!-- API Key Warning -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">API Key Richiesta</h3>
                <p class="mt-1 text-amber-700 dark:text-amber-300">
                    Per utilizzare l'analisi AI, devi configurare la tua Claude API key nelle impostazioni.
                </p>
                <p class="mt-3 text-sm text-amber-600 dark:text-amber-400">
                    Ottieni la tua API key su <a href="https://console.anthropic.com/" target="_blank" class="underline hover:no-underline">console.anthropic.com</a>
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <!-- Stats Cards -->
    <?= \Core\View::partial('components/dashboard-stats-row', [
        'cards' => [
            [
                'label' => 'Link Totali',
                'value' => $stats['total_links'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                'color' => 'cyan',
            ],
            [
                'label' => 'Analizzati',
                'value' => $stats['analyzed'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'color' => 'emerald',
            ],
            [
                'label' => 'In Attesa',
                'value' => $stats['pending'] ?? 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'color' => 'amber',
            ],
            [
                'label' => 'Score Medio',
                'value' => isset($stats['avg_relevance']) && $stats['avg_relevance'] !== null ? number_format($stats['avg_relevance'], 1) : '-',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
                'color' => 'purple',
            ],
        ],
    ]) ?>

    <!-- Analysis Control Panel -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Avvia Analisi</h2>
            <div class="flex items-center gap-2">
                <span x-show="status === 'running'" class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Analisi in corso...
                </span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-slate-600 dark:text-slate-400">
                    <span x-text="stats.analyzed"></span> di <span x-text="stats.total_links"></span> link analizzati
                </span>
                <span class="font-semibold text-primary-600 dark:text-primary-400" x-text="progressPercent + '%'"></span>
            </div>
            <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full transition-all duration-500"
                     :style="{ width: progressPercent + '%' }"></div>
            </div>
        </div>

        <!-- Controls -->
        <div class="flex flex-wrap items-center gap-4">
            <!-- Batch Size -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-slate-600 dark:text-slate-400">Dimensione Batch:</label>
                <select x-model="batchSize" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm">
                    <option value="10">10 link</option>
                    <option value="25">25 link</option>
                    <option value="50">50 link</option>
                    <option value="100">100 link</option>
                </select>
            </div>

            <!-- Estimated Cost -->
            <div class="text-sm text-slate-500 dark:text-slate-400">
                Costo Stimato: <span class="font-medium text-slate-700 dark:text-slate-300">$<?= number_format($stats['estimated_cost'] ?? 0, 4) ?></span>
            </div>

            <!-- Start Button -->
            <button x-show="status !== 'running'"
                    @click="startAnalysis()"
                    :disabled="stats.pending === 0"
                    class="ml-auto inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg shadow-primary-600/25">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Avvia Analisi
            </button>

            <!-- Stop Button -->
            <button x-show="status === 'running'" x-cloak
                    @click="stopAnalysis()"
                    class="ml-auto inline-flex items-center gap-2 px-6 py-2.5 bg-red-500 text-white text-sm font-medium rounded-xl hover:bg-red-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                Ferma
            </button>
        </div>

        <!-- Last Batch Results -->
        <div x-show="lastBatch.analyzed > 0" x-cloak class="mt-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Ultimo batch: <span class="font-medium text-slate-900 dark:text-white" x-text="lastBatch.analyzed"></span> link analizzati
                <span x-show="lastBatch.errors > 0" class="text-red-500">
                    (<span x-text="lastBatch.errors"></span> errori)
                </span>
            </p>
        </div>
    </div>

    <!-- Score Distribution Charts -->
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Score Distribution -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Distribuzione Score</h3>
            <div class="h-64">
                <canvas id="scoreChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-4 gap-2 text-center text-sm">
                <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="font-bold text-red-600"><?= $scoreDistribution['low'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500">Basso (1-3)</p>
                </div>
                <div class="p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <p class="font-bold text-amber-600"><?= $scoreDistribution['medium'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500">Medio (4-6)</p>
                </div>
                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="font-bold text-green-600"><?= $scoreDistribution['high'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500">Alto (7-10)</p>
                </div>
                <div class="p-2 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                    <p class="font-bold text-slate-600"><?= $scoreDistribution['unanalyzed'] ?? 0 ?></p>
                    <p class="text-xs text-slate-500">In Attesa</p>
                </div>
            </div>
        </div>

        <!-- Juice Flow Distribution -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Qualita Link</h3>
            <div class="h-64">
                <canvas id="juiceChart"></canvas>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-3 text-sm">
                <?php
                $juiceColors = [
                    'optimal' => 'bg-green-500',
                    'good' => 'bg-blue-500',
                    'weak' => 'bg-amber-500',
                    'poor' => 'bg-red-500',
                ];
                foreach (($juiceDistribution ?? []) as $type => $count):
                    if ($type === 'unanalyzed' || $count === 0) continue;
                ?>
                <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded-lg">
                    <span class="w-2 h-2 rounded-full <?= $juiceColors[$type] ?? 'bg-slate-400' ?>"></span>
                    <?= ucfirst($type) ?>: <?= $count ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Low Score Links -->
    <?php if (!empty($lowScoreLinks)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Link che Richiedono Attenzione</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Link con score di rilevanza basso</p>
            </div>
            <a href="<?= url("/internal-links/project/{$project['id']}/links?max_score=4") ?>"
               class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                Vedi Tutti
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Sorgente</th>
                        <th>Anchor Text</th>
                        <th>Destinazione</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Qualita</th>
                        <th>Suggerimenti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowScoreLinks as $link): ?>
                    <tr>
                        <td class="max-w-[150px] truncate text-sm">
                            <?= e(mb_substr($link['source_url'] ?? '', 0, 30)) ?>
                        </td>
                        <td class="max-w-[150px]">
                            <span class="text-sm font-medium text-slate-900 dark:text-white truncate block">
                                <?= e(mb_substr($link['anchor_text'] ?? '[no anchor]', 0, 30)) ?>
                            </span>
                        </td>
                        <td class="max-w-[150px] truncate text-sm text-slate-500">
                            <?= e(mb_substr($link['destination_url'] ?? '', 0, 30)) ?>
                        </td>
                        <td class="text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold
                                <?php
                                $score = $link['ai_relevance_score'] ?? 0;
                                if ($score <= 3) echo 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                                elseif ($score <= 6) echo 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                else echo 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                                ?>">
                                <?= $link['ai_relevance_score'] ?? '-' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($link['ai_juice_flow'])): ?>
                            <span class="px-2 py-1 text-xs rounded-lg
                                <?php
                                $flow = $link['ai_juice_flow'];
                                if ($flow === 'optimal') echo 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                                elseif ($flow === 'good') echo 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                                elseif ($flow === 'weak') echo 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                else echo 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                                ?>">
                                <?= $link['ai_juice_flow'] ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-slate-500 dark:text-slate-400 max-w-[200px]">
                            <?= e(mb_substr($link['ai_suggestions'] ?? '-', 0, 50)) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Come funziona -->
    <?= \Core\View::partial('components/dashboard-how-it-works', [
        'color' => 'cyan',
        'steps' => [
            ['title' => 'Importa URL', 'description' => 'Sitemap o lista pagine'],
            ['title' => 'Scrape Link', 'description' => 'Estrai tutti i link interni'],
            ['title' => 'Analisi Struttura', 'description' => 'Mappa dei collegamenti'],
            ['title' => 'Ottimizza', 'description' => 'Suggerimenti AI per link'],
        ],
    ]) ?>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function analysisManager() {
    return {
        status: 'idle',
        batchSize: '25',
        stats: {
            total_links: <?= (int) ($stats['total_links'] ?? 0) ?>,
            analyzed: <?= (int) ($stats['analyzed'] ?? 0) ?>,
            pending: <?= (int) ($stats['pending'] ?? 0) ?>,
            avg_relevance: <?= ($stats['avg_relevance'] ?? null) !== null ? $stats['avg_relevance'] : 'null' ?>,
            estimated_cost: <?= (float) ($stats['estimated_cost'] ?? 0) ?>
        },
        lastBatch: { analyzed: 0, errors: 0 },
        shouldStop: false,

        get progressPercent() {
            if (this.stats.total_links === 0) return 0;
            return Math.round((this.stats.analyzed / this.stats.total_links) * 100);
        },

        init() {
            this.initCharts();
        },

        async startAnalysis() {
            this.status = 'running';
            this.shouldStop = false;
            this.continueAnalysis();
        },

        async continueAnalysis() {
            if (this.shouldStop || this.status !== 'running') {
                this.status = 'idle';
                return;
            }

            try {
                const response = await fetch('<?= url("/internal-links/api/analyze") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        project_id: <?= $project['id'] ?>,
                        batch_size: parseInt(this.batchSize),
                        only_unanalyzed: true
                    })
                });

                const data = await response.json();

                if (data.error) {
                    if (typeof app !== 'undefined') app.showToast(data.error, 'error');
                    this.status = 'idle';
                    return;
                }

                // Update stats
                if (data.stats) {
                    this.stats = data.stats;
                }

                // Update last batch
                if (data.result) {
                    this.lastBatch = data.result;
                }

                // Check if complete
                if (data.complete || this.stats.pending === 0) {
                    this.status = 'idle';
                    if (typeof app !== 'undefined') app.showToast('Analisi completata!', 'success');
                    return;
                }

                // Continue if not stopped
                if (!this.shouldStop) {
                    setTimeout(() => this.continueAnalysis(), 1000);
                }

            } catch (error) {
                console.error('Analysis error:', error);
                if (typeof app !== 'undefined') app.showToast('Si e verificato un errore durante l\'analisi', 'error');
                this.status = 'idle';
            }
        },

        stopAnalysis() {
            this.shouldStop = true;
            this.status = 'idle';
            if (typeof app !== 'undefined') app.showToast('Analisi fermata', 'info');
        },

        initCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';

            // Score Distribution Doughnut
            const scoreCtx = document.getElementById('scoreChart');
            if (scoreCtx) {
                new Chart(scoreCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Basso (1-3)', 'Medio (4-6)', 'Alto (7-10)', 'In Attesa'],
                        datasets: [{
                            data: [
                                <?= (int) ($scoreDistribution['low'] ?? 0) ?>,
                                <?= (int) ($scoreDistribution['medium'] ?? 0) ?>,
                                <?= (int) ($scoreDistribution['high'] ?? 0) ?>,
                                <?= (int) ($scoreDistribution['unanalyzed'] ?? 0) ?>
                            ],
                            backgroundColor: [
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(148, 163, 184, 0.4)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: textColor, padding: 16, usePointStyle: true }
                            }
                        }
                    }
                });
            }

            // Juice Flow Bar Chart
            const juiceCtx = document.getElementById('juiceChart');
            if (juiceCtx) {
                new Chart(juiceCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Ottimale', 'Buono', 'Debole', 'Scarso'],
                        datasets: [{
                            data: [
                                <?= (int) ($juiceDistribution['optimal'] ?? 0) ?>,
                                <?= (int) ($juiceDistribution['good'] ?? 0) ?>,
                                <?= (int) ($juiceDistribution['weak'] ?? 0) ?>,
                                <?= (int) ($juiceDistribution['poor'] ?? 0) ?>
                            ],
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)'
                            ],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: textColor } },
                            y: { grid: { color: isDark ? '#334155' : '#e2e8f0' }, ticks: { color: textColor } }
                        }
                    }
                });
            }
        }
    };
}
</script>
