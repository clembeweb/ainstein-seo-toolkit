<?php
/**
 * Report: Filtro Campagne pre-evaluation
 * Mostrato quando l'utente clicca "Valuta con AI" per selezionare le campagne da analizzare.
 *
 * Variables from parent:
 * - $filterCampaigns: array di campagne ENABLED dal sync più recente con metriche base
 * - $project: progetto corrente
 * - $evaluationCost: costo crediti per l'evaluation
 */

$campaignTypeConfig = [
    'SEARCH' => ['bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300', 'label' => 'Search'],
    'SHOPPING' => ['bg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300', 'label' => 'Shopping'],
    'PERFORMANCE_MAX' => ['bg' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300', 'label' => 'PMax'],
    'DISPLAY' => ['bg' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300', 'label' => 'Display'],
    'VIDEO' => ['bg' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', 'label' => 'Video'],
];

$evaluationCost = $evaluationCost ?? \Core\Credits::getCost('campaign_evaluation', 'ads-analyzer', 10);
$evaluateUrl = url("/ads-analyzer/projects/{$project['id']}/campaigns/evaluate");
?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6"
     x-data="{
        campaigns: <?= e(json_encode(array_map(fn($c) => [
            'id' => $c['campaign_id_google'],
            'name' => $c['campaign_name'] ?? $c['name'] ?? '',
            'type' => strtoupper($c['campaign_type'] ?? 'SEARCH'),
            'clicks' => (int)($c['clicks'] ?? 0),
            'cost' => (float)($c['cost'] ?? 0),
            'roas' => (float)($c['roas'] ?? 0),
            'selected' => true,
        ], $filterCampaigns ?? []))) ?>,
        get selectedCount() { return this.campaigns.filter(c => c.selected).length; },
        get selectedIds() { return this.campaigns.filter(c => c.selected).map(c => c.id); },
        submitting: false,
        toggleAll(val) { this.campaigns.forEach(c => c.selected = val); },

        startEvaluation() {
            if (this.selectedCount === 0) return;
            this.submitting = true;

            const formData = new FormData();
            formData.append('_csrf_token', '<?= csrf_token() ?>');
            this.selectedIds.forEach(id => formData.append('campaigns_filter[]', id));

            fetch('<?= $evaluateUrl ?>', { method: 'POST', body: formData })
                .then(response => {
                    if (!response.ok) throw new Error('Errore server (' + response.status + ')');
                    return response.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data.evaluation_id) {
                        window.location.href = '<?= url("/ads-analyzer/projects/{$project['id']}/campaigns/evaluations") ?>/' + data.evaluation_id;
                    }
                })
                .catch(err => {
                    this.submitting = false;
                    alert('Errore: ' + err.message);
                });
        }
     }">

    <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Seleziona campagne da analizzare
    </h3>

    <!-- Campaign List -->
    <div class="space-y-2 mb-4">
        <template x-for="(camp, idx) in campaigns" :key="camp.id">
            <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors">
                <input type="checkbox" x-model="camp.selected"
                       class="rounded text-rose-600 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-rose-500">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <?php foreach ($campaignTypeConfig as $type => $conf): ?>
                        <template x-if="camp.type === '<?= $type ?>'">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $conf['bg'] ?>"><?= $conf['label'] ?></span>
                        </template>
                        <?php endforeach; ?>
                        <span class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="camp.name"></span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        <span x-text="camp.clicks.toLocaleString('it-IT')"></span> click
                        &middot; &euro;<span x-text="camp.cost.toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0})"></span>
                        &middot; ROAS <span x-text="camp.roas.toFixed(1)"></span>x
                    </div>
                </div>
            </label>
        </template>
    </div>

    <!-- Select All / None -->
    <div class="flex items-center gap-3 mb-4 text-xs">
        <button @click="toggleAll(true)" class="text-rose-600 dark:text-rose-400 hover:underline">Seleziona tutte</button>
        <span class="text-slate-400">/</span>
        <button @click="toggleAll(false)" class="text-slate-500 hover:underline">Deseleziona tutte</button>
    </div>

    <!-- Footer -->
    <div class="border-t border-slate-200 dark:border-slate-700 pt-4 flex items-center justify-between">
        <div class="text-sm text-slate-500 dark:text-slate-400">
            <span x-text="selectedCount"></span> campagne selezionate
            &middot; Costo: <strong class="text-slate-700 dark:text-slate-300"><?= $evaluationCost ?> crediti</strong>
        </div>
        <button @click="startEvaluation()"
                :disabled="selectedCount === 0 || submitting"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <template x-if="!submitting">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </template>
            <template x-if="submitting">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <span x-text="submitting ? 'Analisi in corso...' : 'Avvia Analisi AI'"></span>
        </button>
    </div>

    <!-- SSE Progress (shown during analysis) -->
    <div x-show="submitting" x-cloak class="mt-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/30 rounded-lg p-4 text-center">
        <svg class="w-8 h-8 mx-auto mb-2 text-rose-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p x-ref="progressMessage" class="text-sm text-rose-700 dark:text-rose-300">Avvio analisi...</p>
    </div>

</div>
