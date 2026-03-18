<?php
$currentPage = 'campaigns';
$canEdit = ($access_role ?? 'owner') !== 'viewer';
include __DIR__ . '/../partials/project-nav.php';

$campaignTypeConfig = [
    'SEARCH' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Search'],
    'SHOPPING' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Shopping'],
    'PERFORMANCE_MAX' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'PMax'],
    'DISPLAY' => ['bg' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300', 'label' => 'Display'],
    'VIDEO' => ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'label' => 'Video'],
];
?>

<div class="space-y-6" x-data="campaignPageManager()">

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_clicks'] ?? 0) ?></p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Click</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_cost'] ?? 0, 2, ',', '.') ?>&euro;</p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Costo</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($latestStats['total_conversions'] ?? 0, 1) ?></p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Conversioni</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                    </svg>
                </div>
                <div>
                    <?php if ($latestRun): ?>
                    <?php
                    $totalCtr = ($latestStats['total_impressions'] ?? 0) > 0
                        ? round(($latestStats['total_clicks'] ?? 0) / ($latestStats['total_impressions'] ?? 1) * 100, 2)
                        : 0;
                    ?>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($totalCtr, 2) ?>%</p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">-</p>
                    <?php endif; ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">CTR</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$latestRun): ?>
    <!-- No data state -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Nessun dato sincronizzato</p>
                <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                    Collega il tuo account Google Ads dalla pagina <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/connect') ?>" class="underline font-medium">Connessione</a> e avvia una sincronizzazione dalla Dashboard.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <?php if ($canEdit): ?>
                <button
                    @click="showCampaignFilter = !showCampaignFilter"
                    :disabled="loading || !canEvaluate"
                    class="inline-flex items-center px-4 py-2 rounded-lg font-medium transition-colors"
                    :class="canEvaluate && !loading
                        ? 'bg-amber-600 text-white hover:bg-amber-700'
                        : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed'"
                >
                    <template x-if="!loading">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                    </template>
                    <template x-if="loading">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="loading ? 'Analisi AI in corso...' : 'Valuta con AI'"></span>
                </button>
                <?php $evalCost = \Core\Credits::getCost('campaign_evaluation', 'ads-analyzer', 7); ?>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-medium">Costo:</span> <?= number_format($evalCost, 1) ?> crediti
                    <span class="mx-1">&bull;</span>
                    <span>Disponibili: <strong class="<?= $userCredits >= $evalCost ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>"><?= number_format($userCredits, 1) ?></strong></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($latestSync): ?>
            <div class="text-xs text-slate-400 dark:text-slate-500">
                Ultimo sync: <?= date('d/m/Y H:i', strtotime($latestSync['started_at'] ?? $latestSync['completed_at'] ?? 'now')) ?>
                <?php if (!empty($latestSync['date_range_start']) && !empty($latestSync['date_range_end'])): ?>
                &bull; Periodo: <?= date('d/m', strtotime($latestSync['date_range_start'])) ?> - <?= date('d/m/Y', strtotime($latestSync['date_range_end'])) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Loading banner -->
        <template x-if="loading">
            <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Analisi AI in corso</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">L'analisi potrebbe richiedere 1-2 minuti. Non chiudere questa pagina.</p>
                </div>
            </div>
        </template>

        <!-- Error message -->
        <template x-if="errorMsg">
            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300" x-text="errorMsg"></div>
        </template>
    </div>

    <!-- Campaign Filter (v2) -->
    <div x-show="showCampaignFilter" x-cloak x-transition class="space-y-0">
        <?php
        $filterCampaigns = [];
        if (!empty($latestSync)) {
            $filterCampaigns = \Core\Database::fetchAll(
                "SELECT campaign_id_google, campaign_name as name, campaign_type, clicks, cost,
                        CASE WHEN cost > 0 AND conversion_value > 0 THEN ROUND(conversion_value / cost, 1) ELSE 0 END as roas
                 FROM ga_campaigns
                 WHERE sync_id = ? AND campaign_status = 'ENABLED' AND clicks > 0
                 ORDER BY cost DESC LIMIT 20",
                [$latestSync['id']]
            );
        }
        $evaluationCost = \Core\Credits::getCost('campaign_evaluation', 'ads-analyzer', 10);
        include __DIR__ . '/partials/report-campaign-filter.php';
        ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- CAMPAIGNS PERFORMANCE TABLE                                       -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <?php if (!empty($campaignsPerformance ?? [])): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Campagne</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500"><?= count($campaignsPerformance) ?> campagne</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Campagna</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Click</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">CTR</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Spesa</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Conv.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Val. Conv.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">ROAS</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">CPA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($campaignsPerformance as $camp):
                        $type = strtoupper($camp['type'] ?? 'SEARCH');
                        $typeConf = $campaignTypeConfig[$type] ?? $campaignTypeConfig['SEARCH'];
                        $isEnabled = ($camp['status'] ?? '') === 'ENABLED';
                        $cpa = ($camp['conversions'] > 0) ? $camp['cost'] / $camp['conversions'] : 0;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= !$isEnabled ? 'opacity-50' : '' ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold <?= $typeConf['bg'] ?>"><?= $typeConf['label'] ?></span>
                                <span class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-[250px]" title="<?= e($camp['name']) ?>"><?= e($camp['name']) ?></span>
                                <?php if (!$isEnabled): ?>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500">(<?= e(strtolower($camp['status'])) ?>)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-slate-900 dark:text-white whitespace-nowrap">
                            <?= number_format($camp['clicks']) ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                            <?= number_format($camp['ctr'], 2) ?>%
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-slate-900 dark:text-white whitespace-nowrap">
                            <?= number_format($camp['cost'], 2, ',', '.') ?>&euro;
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap <?= $camp['conversions'] > 0 ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-400 dark:text-slate-500' ?>">
                            <?= $camp['conversions'] > 0 ? number_format($camp['conversions'], 1) : '-' ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap <?= $camp['conversion_value'] > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' ?>">
                            <?= $camp['conversion_value'] > 0 ? number_format($camp['conversion_value'], 0, ',', '.') . '&euro;' : '-' ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap <?= $camp['roas'] >= 3 ? 'text-emerald-600 dark:text-emerald-400 font-medium' : ($camp['roas'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500') ?>">
                            <?= $camp['roas'] > 0 ? number_format($camp['roas'], 1) . 'x' : '-' ?>
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap <?= $cpa > 0 && $cpa < 20 ? 'text-emerald-600 dark:text-emerald-400' : ($cpa > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500') ?>">
                            <?= $cpa > 0 ? number_format($cpa, 2, ',', '.') . '&euro;' : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- VALUTAZIONI AI (compact)                                          -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <?php if (!empty($evaluations)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valutazioni AI</h2>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($evaluations as $evaluation):
                $esColors = ['completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'error' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'];
                $esLabels = ['completed' => 'Completata', 'running' => 'In corso', 'error' => 'Errore'];
                $esColor = $esColors[$evaluation['status']] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
                $esLabel = $esLabels[$evaluation['status']] ?? ucfirst($evaluation['status']);
            ?>
            <a href="<?= $evaluation['status'] === 'completed' ? url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluations/' . $evaluation['id']) : '#' ?>"
               class="flex items-center justify-between px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= $evaluation['status'] !== 'completed' ? 'pointer-events-none' : '' ?>">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="font-medium text-sm text-slate-900 dark:text-white truncate"><?= e($evaluation['name']) ?></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0 <?= $esColor ?>"><?= $esLabel ?></span>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-400 dark:text-slate-500 flex-shrink-0 ml-4">
                    <span><?= date('d/m/Y H:i', strtotime($evaluation['created_at'])) ?></span>
                    <span><?= number_format($evaluation['campaigns_evaluated'] ?? 0) ?> camp.</span>
                    <?php if ($evaluation['status'] === 'completed'): ?>
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Campaign Selection Modal -->
    <template x-if="showCampaignModal">
    <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" @click="showCampaignModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full max-h-[80vh] flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Seleziona Campagne</h3>
                    <button @click="showCampaignModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <button @click="selectAllCampaigns()" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">Seleziona tutte</button>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <button @click="selectedCampaigns = []" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:underline">Deseleziona tutte</button>
                        <span class="ml-auto text-xs text-slate-500" x-text="selectedCampaigns.length + '/' + maxCampaigns"></span>
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="c in allCampaigns" :key="c.id_google">
                            <label class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors"
                                   :class="selectedCampaigns.includes(c.id_google) ? 'bg-amber-50 dark:bg-amber-900/20' : ''">
                                <input type="checkbox" :value="c.id_google" x-model="selectedCampaigns"
                                       :disabled="!selectedCampaigns.includes(c.id_google) && selectedCampaigns.length >= maxCampaigns"
                                       class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="c.name"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        <span x-text="c.type"></span> &bull; Costo: <span x-text="c.cost.toFixed(2)"></span>&euro; &bull; Click: <span x-text="c.clicks"></span>
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                    <button @click="showCampaignModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Annulla</button>
                    <button @click="doEvaluate(selectedCampaigns)" :disabled="selectedCampaigns.length === 0"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="selectedCampaigns.length > 0 ? 'bg-amber-600 text-white hover:bg-amber-700' : 'bg-slate-200 text-slate-400 cursor-not-allowed'">
                        Valuta <span x-text="selectedCampaigns.length"></span> campagne
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
</div>

<script>
function campaignPageManager() {
    return {
        loading: false,
        errorMsg: '',
        canEvaluate: <?= ($latestSync && $userCredits >= $evalCost) ? 'true' : 'false' ?>,
        showCampaignFilter: false,
        showCampaignModal: false,
        allCampaigns: <?= json_encode($campaignsList ?? [], JSON_UNESCAPED_UNICODE) ?>,
        selectedCampaigns: [],
        maxCampaigns: 15,

        startEvaluation() {
            if (!this.canEvaluate || this.loading) return;
            if (this.allCampaigns.length > 10) {
                this.selectedCampaigns = this.allCampaigns
                    .filter(c => c.status === 'ENABLED')
                    .sort((a, b) => b.cost - a.cost)
                    .slice(0, 10)
                    .map(c => c.id_google);
                this.showCampaignModal = true;
            } else {
                this.doEvaluate([]);
            }
        },

        selectAllCampaigns() {
            this.selectedCampaigns = this.allCampaigns.slice(0, this.maxCampaigns).map(c => c.id_google);
        },

        async doEvaluate(filter) {
            this.showCampaignModal = false;
            this.loading = true;
            this.errorMsg = '';

            try {
                const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value;
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                if (filter && filter.length > 0) {
                    formData.append('campaigns_filter', JSON.stringify(filter));
                }

                const resp = await fetch('<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/evaluate') ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!resp.ok) {
                    this.errorMsg = 'Valutazione avviata. Ricarica la pagina tra poco.';
                    this.loading = false;
                    setTimeout(() => location.reload(), 15000);
                    return;
                }

                const data = await resp.json();

                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.error) {
                    this.errorMsg = data.error;
                    this.loading = false;
                } else {
                    this.errorMsg = 'Errore imprevisto.';
                    this.loading = false;
                }
            } catch (err) {
                console.error('Evaluation start failed:', err);
                this.errorMsg = 'Valutazione avviata. Ricarica la pagina tra poco.';
                this.loading = false;
                setTimeout(() => location.reload(), 15000);
            }
        }
    };
}
</script>
