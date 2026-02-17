<?php
$overview = $overview ?? [];
$apiCosts = $apiCosts ?? [];
$creditsData = $creditsData ?? [];
$plansData = $plansData ?? [];
$providerNames = $providerNames ?? [];
$moduleNames = $moduleNames ?? [];
$period = $period ?? '30';
$tab = $tab ?? 'overview';

// Delta helper
$calcDelta = function(float $current, float $previous): ?float {
    if ($previous == 0) return $current > 0 ? 100.0 : null;
    return round((($current - $previous) / $previous) * 100, 1);
};

$periodLabels = ['7' => '7 giorni', '30' => '30 giorni', '90' => '90 giorni', 'ytd' => 'Anno'];
$tabs = [
    'overview' => 'Panoramica',
    'api-costs' => 'Costi API',
    'credits' => 'Crediti & Utenti',
    'plans' => 'Piani & Revenue',
];
?>

<div class="space-y-6">
    <!-- Header + Period selector -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Finance</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Quadro finanziario completo: costi, crediti, revenue</p>
        </div>
        <div class="flex items-center gap-2">
            <?php foreach ($periodLabels as $pKey => $pLabel): ?>
            <a href="<?= url('/admin/finance') ?>?period=<?= $pKey ?>&tab=<?= $tab ?>"
               class="px-3 py-1.5 text-sm rounded-lg transition-colors <?= $period === $pKey
                   ? 'bg-primary-600 text-white'
                   : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <?= $pLabel ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-6 -mb-px">
            <?php foreach ($tabs as $tKey => $tLabel): ?>
            <a href="<?= url('/admin/finance') ?>?period=<?= $period ?>&tab=<?= $tKey ?>"
               class="py-3 px-1 text-sm font-medium border-b-2 transition-colors <?= $tab === $tKey
                   ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                   : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300' ?>">
                <?= $tLabel ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if ($tab === 'overview'): ?>
    <!-- ════════════════════════ TAB 1: OVERVIEW ════════════════════════ -->

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php
        $kpis = [
            [
                'label' => 'Costi API',
                'value' => '$' . number_format($overview['api_cost'] ?? 0, 4),
                'delta' => $calcDelta($overview['api_cost'] ?? 0, $overview['prev_api_cost'] ?? 0),
                'icon_bg' => 'bg-red-50 dark:bg-red-900/30',
                'icon_color' => 'text-red-600 dark:text-red-400',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'invert_delta' => true,
            ],
            [
                'label' => 'Costi AI',
                'value' => '$' . number_format($overview['ai_cost'] ?? 0, 4),
                'delta' => $calcDelta($overview['ai_cost'] ?? 0, $overview['prev_ai_cost'] ?? 0),
                'icon_bg' => 'bg-amber-50 dark:bg-amber-900/30',
                'icon_color' => 'text-amber-600 dark:text-amber-400',
                'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'invert_delta' => true,
            ],
            [
                'label' => 'Crediti Consumati',
                'value' => number_format($overview['credits_consumed'] ?? 0, 1),
                'delta' => $calcDelta($overview['credits_consumed'] ?? 0, $overview['prev_credits_consumed'] ?? 0),
                'icon_bg' => 'bg-blue-50 dark:bg-blue-900/30',
                'icon_color' => 'text-blue-600 dark:text-blue-400',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'invert_delta' => false,
            ],
            [
                'label' => 'Crediti Distribuiti',
                'value' => number_format($overview['credits_distributed'] ?? 0, 1),
                'delta' => $calcDelta($overview['credits_distributed'] ?? 0, $overview['prev_credits_distributed'] ?? 0),
                'icon_bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                'icon_color' => 'text-emerald-600 dark:text-emerald-400',
                'icon' => 'M12 4v16m8-8H4',
                'invert_delta' => false,
            ],
        ];
        foreach ($kpis as $kpi):
            $deltaClass = '';
            if ($kpi['delta'] !== null) {
                $isPositive = $kpi['delta'] > 0;
                $isGood = $kpi['invert_delta'] ? !$isPositive : $isPositive;
                $deltaClass = $isGood ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
            }
        ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400"><?= $kpi['label'] ?></p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white"><?= $kpi['value'] ?></p>
                </div>
                <div class="h-11 w-11 rounded-lg <?= $kpi['icon_bg'] ?> flex items-center justify-center">
                    <svg class="h-5 w-5 <?= $kpi['icon_color'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $kpi['icon'] ?>"/>
                    </svg>
                </div>
            </div>
            <?php if ($kpi['delta'] !== null): ?>
            <div class="mt-3 flex items-center gap-1 text-sm <?= $deltaClass ?>">
                <svg class="h-4 w-4 <?= $kpi['delta'] >= 0 ? '' : 'rotate-180' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <?= abs($kpi['delta']) ?>%
                <span class="text-slate-500 dark:text-slate-400">vs periodo prec.</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Cost Trend -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Trend costi nel tempo</h3>
            <div class="h-64">
                <canvas id="costTrendChart"></canvas>
            </div>
        </div>

        <!-- Provider Doughnut -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Costi per provider</h3>
            <div class="h-64">
                <canvas id="providerChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Credits by Module -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Crediti per modulo</h3>
        <div class="h-64">
            <canvas id="moduleChart"></canvas>
        </div>
    </div>

    <?php elseif ($tab === 'api-costs'): ?>
    <!-- ════════════════════════ TAB 2: COSTI API ════════════════════════ -->

    <!-- Provider Stats Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Costi per Provider API</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Provider</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Chiamate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Successo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Errori</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rate Limit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo USD</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Media</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($apiCosts['provider_stats'])): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun dato nel periodo selezionato</td></tr>
                    <?php else: ?>
                    <?php foreach ($apiCosts['provider_stats'] as $ps): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= $providerNames[$ps['provider']] ?? $ps['provider'] ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300"><?= number_format($ps['calls']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400"><?= number_format($ps['success_count']) ?></td>
                        <td class="px-4 py-3 text-sm text-right <?= $ps['error_count'] > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-500 dark:text-slate-400' ?>"><?= number_format($ps['error_count']) ?></td>
                        <td class="px-4 py-3 text-sm text-right <?= $ps['rate_limited_count'] > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-slate-500 dark:text-slate-400' ?>"><?= number_format($ps['rate_limited_count']) ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white">$<?= number_format($ps['total_cost'], 4) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400">$<?= number_format($ps['avg_cost'], 6) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($ps['avg_duration']) ?>ms</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- AI Model Stats Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Costi per Modello AI</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modello</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Chiamate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Token Input</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Token Output</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo USD</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($apiCosts['ai_model_stats'])): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun dato nel periodo selezionato</td></tr>
                    <?php else: ?>
                    <?php foreach ($apiCosts['ai_model_stats'] as $ms): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?= $providerNames[$ms['provider']] ?? ucfirst($ms['provider']) ?></td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-700 dark:text-slate-300"><?= e($ms['model']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300"><?= number_format($ms['calls']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($ms['total_input']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($ms['total_output']) ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white">$<?= number_format($ms['total_cost'], 4) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($ms['avg_duration']) ?>ms</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Cost Trend Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Trend costi giornaliero (API + AI)</h3>
        <div class="h-64">
            <canvas id="dailyCostChart"></canvas>
        </div>
    </div>

    <!-- Top 10 Most Expensive Calls -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Top 10 chiamate piu costose</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dettaglio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modulo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durata</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($apiCosts['top_expensive'])): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun dato</td></tr>
                    <?php else: ?>
                    <?php foreach ($apiCosts['top_expensive'] as $tc): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $tc['type'] === 'ai' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' ?>">
                                <?= strtoupper($tc['type']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?= $providerNames[$tc['provider']] ?? $tc['provider'] ?></td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-600 dark:text-slate-400 max-w-xs truncate"><?= e($tc['detail']) ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400"><?= $moduleNames[$tc['module_slug']] ?? ($tc['module_slug'] ?: '-') ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white">$<?= number_format($tc['cost_usd'], 4) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($tc['duration_ms'] ?? 0) ?>ms</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400">
                            <a href="<?= url('/admin/' . ($tc['type'] === 'ai' ? 'ai' : 'api') . '-logs/' . $tc['id']) ?>" class="text-primary-600 dark:text-primary-400 hover:underline">
                                <?= date('d/m H:i', strtotime($tc['created_at'])) ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($tab === 'credits'): ?>
    <!-- ════════════════════════ TAB 3: CREDITI & UTENTI ════════════════════════ -->

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Costo medio per credito</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">$<?= number_format($creditsData['cost_per_credit'] ?? 0, 4) ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Costo API+AI / crediti consumati</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Crediti consumati oggi</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($creditsData['credits_today'] ?? 0, 1) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Crediti consumati (mese)</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($creditsData['credits_month'] ?? 0, 1) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Credit Flow Doughnut -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Flusso crediti (periodo)</h3>
            <div class="h-64">
                <canvas id="creditFlowChart"></canvas>
            </div>
        </div>

        <!-- Top Users -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Top utenti per consumo</h3>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php if (empty($creditsData['top_users'])): ?>
                <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun utilizzo nel periodo</div>
                <?php else: ?>
                <?php
                $totalUsed = array_sum(array_column($creditsData['top_users'], 'total_used'));
                foreach ($creditsData['top_users'] as $i => $u):
                    $pct = $totalUsed > 0 ? round(($u['total_used'] / $totalUsed) * 100, 1) : 0;
                ?>
                <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center h-7 w-7 rounded-full text-xs font-bold <?= $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                            <?= $i + 1 ?>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($u['name'] ?? $u['email']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?= e($u['email']) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900 dark:text-white"><?= number_format($u['total_used'], 1) ?> cr</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= $pct ?>%</p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Credits by Module & Action -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Crediti per modulo e azione</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Modulo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azione</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Conteggio</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Crediti Totali</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Media</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($creditsData['credits_by_action'])): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessun dato</td></tr>
                    <?php else: ?>
                    <?php foreach ($creditsData['credits_by_action'] as $ca): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= $moduleNames[$ca['module_slug']] ?? ($ca['module_slug'] ?: '-') ?></td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400"><?= e($ca['action']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300"><?= number_format($ca['count']) ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white"><?= number_format($ca['total_credits'], 1) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= number_format($ca['avg_credits'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($tab === 'plans'): ?>
    <!-- ════════════════════════ TAB 4: PIANI & REVENUE ════════════════════════ -->

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">MRR Proiettato</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">&euro;<?= number_format($plansData['mrr'] ?? 0, 2) ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Monthly Recurring Revenue</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">ARR Proiettato</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">&euro;<?= number_format($plansData['arr'] ?? 0, 2) ?></p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Annual Recurring Revenue</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Utenti Totali</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($plansData['total_users'] ?? 0) ?></p>
        </div>
    </div>

    <!-- Plan Adoption Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Adozione piani</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Piano</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prezzo/mese</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Crediti/mese</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Utenti</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">% Totale</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">MRR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php
                    $totalUsers = $plansData['total_users'] ?? 1;
                    $unassigned = $plansData['users_without_plan'] ?? 0;
                    foreach ($plansData['plan_adoption'] ?? [] as $plan):
                        $userCount = (int) $plan['user_count'];
                        // Add unassigned users to free plan
                        if ($plan['slug'] === 'free') {
                            $userCount += $unassigned;
                        }
                        $pct = $totalUsers > 0 ? round(($userCount / $totalUsers) * 100, 1) : 0;
                        $planMrr = (float) $plan['price_monthly'] * $userCount;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?= e($plan['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300"><?= $plan['price_monthly'] > 0 ? '&euro;' . number_format($plan['price_monthly'], 2) : 'Gratis' ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-700 dark:text-slate-300"><?= number_format($plan['credits_monthly']) ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white"><?= number_format($userCount) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= $pct ?>%</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold <?= $planMrr > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' ?>"><?= $planMrr > 0 ? '&euro;' . number_format($planMrr, 2) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Margin Summary -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Margine operativo (ultimi 30 giorni)</h3>
        </div>
        <?php
        $mrr = $plansData['mrr'] ?? 0;
        $apiCost30 = $plansData['api_cost_30d'] ?? 0;
        $aiCost30 = $plansData['ai_cost_30d'] ?? 0;
        $totalCost30 = $apiCost30 + $aiCost30;
        $margin = $mrr - $totalCost30;
        $marginPct = $mrr > 0 ? round(($margin / $mrr) * 100, 1) : 0;
        ?>
        <div class="p-4 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">Revenue mensile (MRR)</span>
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">&euro;<?= number_format($mrr, 2) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">Costi API (30g)</span>
                <span class="font-semibold text-red-600 dark:text-red-400">-$<?= number_format($apiCost30, 4) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">Costi AI (30g)</span>
                <span class="font-semibold text-red-600 dark:text-red-400">-$<?= number_format($aiCost30, 4) ?></span>
            </div>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-3 flex justify-between text-sm">
                <span class="font-semibold text-slate-900 dark:text-white">Margine lordo</span>
                <span class="font-bold text-lg <?= $margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                    &euro;<?= number_format($margin, 2) ?>
                    <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(<?= $marginPct ?>%)</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Stripe Info -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-lg <?= ($plansData['stripe_enabled'] ?? false) ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-700' ?> flex items-center justify-center">
                <svg class="h-5 w-5 <?= ($plansData['stripe_enabled'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                    Integrazione Stripe
                    <?php if ($plansData['stripe_enabled'] ?? false): ?>
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Attivo</span>
                    <?php else: ?>
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Non configurato</span>
                    <?php endif; ?>
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <?= ($plansData['stripe_enabled'] ?? false)
                        ? 'Pagamenti attivi. Gestisci i piani dalla sezione Piani.'
                        : 'Configura le chiavi Stripe in Impostazioni per abilitare i pagamenti automatici.' ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Charts JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const chartColors = [
        '#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6',
        '#ec4899', '#06b6d4', '#f97316', '#6366f1', '#14b8a6'
    ];

    <?php if ($tab === 'overview'): ?>
    // ── Cost Trend (Line) ──
    const costTrendData = <?= json_encode($overview['cost_trend'] ?? []) ?>;
    const aiCostTrendData = <?= json_encode($overview['ai_cost_trend'] ?? []) ?>;

    // Build unified day list
    const allDays = new Set();
    costTrendData.forEach(r => allDays.add(r.day));
    aiCostTrendData.forEach(r => allDays.add(r.day));
    const days = [...allDays].sort();

    const apiValues = days.map(d => {
        const found = costTrendData.find(r => r.day === d);
        return found ? parseFloat(found.api_cost) : 0;
    });
    const aiValues = days.map(d => {
        const found = aiCostTrendData.find(r => r.day === d);
        return found ? parseFloat(found.ai_cost) : 0;
    });
    const dayLabels = days.map(d => {
        const dt = new Date(d + 'T00:00:00');
        return dt.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
    });

    new Chart(document.getElementById('costTrendChart'), {
        type: 'line',
        data: {
            labels: dayLabels,
            datasets: [
                {
                    label: 'Costi API ($)',
                    data: apiValues,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true, tension: 0.4, pointRadius: 2,
                },
                {
                    label: 'Costi AI ($)',
                    data: aiValues,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true, tension: 0.4, pointRadius: 2,
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: textColor } } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, callback: v => '$' + v.toFixed(4) } },
                x: { grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 15 } }
            }
        }
    });

    // ── Provider Doughnut ──
    const providerData = <?= json_encode($overview['provider_breakdown'] ?? []) ?>;
    const aiProviderData = <?= json_encode($overview['ai_provider_breakdown'] ?? []) ?>;
    const providerNames = <?= json_encode($providerNames) ?>;

    // Merge API + AI providers
    const allProviders = {};
    providerData.forEach(p => {
        const name = providerNames[p.provider] || p.provider;
        allProviders[name] = (allProviders[name] || 0) + parseFloat(p.total_cost);
    });
    aiProviderData.forEach(p => {
        const name = providerNames[p.provider] || p.provider;
        allProviders[name] = (allProviders[name] || 0) + parseFloat(p.total_cost);
    });

    const provLabels = Object.keys(allProviders);
    const provValues = Object.values(allProviders);

    if (provLabels.length > 0) {
        new Chart(document.getElementById('providerChart'), {
            type: 'doughnut',
            data: {
                labels: provLabels,
                datasets: [{
                    data: provValues,
                    backgroundColor: chartColors.slice(0, provLabels.length),
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: textColor, padding: 12, usePointStyle: true } },
                    tooltip: { callbacks: { label: ctx => ctx.label + ': $' + ctx.parsed.toFixed(4) } }
                }
            }
        });
    }

    // ── Credits by Module (Horizontal Bar) ──
    const moduleData = <?= json_encode($overview['credits_by_module'] ?? []) ?>;
    const moduleNames = <?= json_encode($moduleNames) ?>;

    if (moduleData.length > 0) {
        new Chart(document.getElementById('moduleChart'), {
            type: 'bar',
            data: {
                labels: moduleData.map(m => moduleNames[m.module_slug] || m.module_slug || 'Altro'),
                datasets: [{
                    label: 'Crediti',
                    data: moduleData.map(m => parseFloat(m.total_credits)),
                    backgroundColor: chartColors.slice(0, moduleData.length),
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                    y: { grid: { display: false }, ticks: { color: textColor } }
                }
            }
        });
    }

    <?php elseif ($tab === 'api-costs'): ?>
    // ── Daily Cost Stacked Bar ──
    const dailyData = <?= json_encode($apiCosts['daily_cost_trend'] ?? []) ?>;

    if (dailyData.length > 0) {
        new Chart(document.getElementById('dailyCostChart'), {
            type: 'bar',
            data: {
                labels: dailyData.map(d => {
                    const dt = new Date(d.day + 'T00:00:00');
                    return dt.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
                }),
                datasets: [
                    {
                        label: 'API ($)',
                        data: dailyData.map(d => parseFloat(d.api_cost)),
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                    },
                    {
                        label: 'AI ($)',
                        data: dailyData.map(d => parseFloat(d.ai_cost)),
                        backgroundColor: '#f59e0b',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 15 } },
                    y: { stacked: true, beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, callback: v => '$' + v.toFixed(4) } }
                }
            }
        });
    }

    <?php elseif ($tab === 'credits'): ?>
    // ── Credit Flow Doughnut ──
    const consumed = <?= json_encode($creditsData['credits_consumed'] ?? 0) ?>;
    const distributed = <?= json_encode($creditsData['credits_distributed'] ?? 0) ?>;
    const remaining = Math.max(0, distributed - consumed);

    new Chart(document.getElementById('creditFlowChart'), {
        type: 'doughnut',
        data: {
            labels: ['Consumati', 'Rimanenti'],
            datasets: [{
                data: [consumed, remaining],
                backgroundColor: ['#ef4444', '#10b981'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor, padding: 12, usePointStyle: true } },
                tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed.toFixed(1) + ' cr' } }
            }
        }
    });
    <?php endif; ?>
});
</script>
