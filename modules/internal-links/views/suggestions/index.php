<?php
/**
 * Suggerimenti Link - Lista con filtri e azioni
 *
 * Variabili dal controller:
 *   $project      - progetto corrente
 *   $suggestions  - array da Suggestion::getByProject() {data, total, per_page, current_page, last_page}
 *   $stats        - array da Suggestion::getStats() {total, pending, validated, snippet_ready, applied, dismissed, actionable, avg_ai_score}
 *   $filters      - filtri attivi {status, reason, min_score, confidence, search}
 *   $credits      - crediti disponibili utente
 *   $costValidation - costo crediti per validazione AI
 *   $costSnippet    - costo crediti per snippet AI
 */

$rows = $suggestions['data'] ?? [];
$pagination = [
    'current_page' => $suggestions['current_page'] ?? 1,
    'last_page'    => $suggestions['last_page'] ?? 1,
    'total'        => $suggestions['total'] ?? 0,
    'per_page'     => $suggestions['per_page'] ?? 30,
    'from'         => ($suggestions['total'] ?? 0) > 0 ? (($suggestions['current_page'] ?? 1) - 1) * ($suggestions['per_page'] ?? 30) + 1 : 0,
    'to'           => min(($suggestions['current_page'] ?? 1) * ($suggestions['per_page'] ?? 30), $suggestions['total'] ?? 0),
];
$baseUrl = url("/internal-links/project/{$project['id']}/suggestions");
$hasConnector = !empty($project['connector_id']);
?>

<div class="space-y-6" x-data="suggestionPage()">

    <!-- Breadcrumb + Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
                <a href="<?= url('/internal-links') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Internal Links</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="<?= url('/internal-links/project/' . $project['id']) ?>" class="hover:text-slate-700 dark:hover:text-slate-300"><?= e($project['name']) ?></a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 dark:text-white">Suggerimenti Link</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Suggerimenti Link</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Suggerimenti intelligenti per migliorare la struttura dei link interni di <?= e($project['name']) ?>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <!-- Genera Suggerimenti -->
            <button type="button"
                    @click="generate()"
                    :disabled="generating"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-cyan-600 rounded-xl hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                <template x-if="!generating">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </template>
                <template x-if="generating">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <span x-text="generating ? generateProgress : 'Genera Suggerimenti'"></span>
            </button>
        </div>
    </div>

    <!-- Generate progress message -->
    <div x-show="generateResult" x-cloak x-transition
         class="rounded-xl border p-4"
         :class="generateResult?.success ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'">
        <div class="flex items-center gap-3">
            <template x-if="generateResult?.success">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </template>
            <template x-if="!generateResult?.success">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </template>
            <p class="text-sm" :class="generateResult?.success ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'" x-text="generateResult?.message"></p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1">Totale Suggerimenti</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= number_format($stats['pending'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1">In Attesa</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400"><?= number_format($stats['snippet_ready'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1">Snippet Pronti</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= number_format($stats['applied'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 mt-1">Applicati</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" action="<?= $baseUrl ?>" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
            <!-- Status -->
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Stato</label>
                <select name="status" class="w-full sm:w-36 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-700 dark:text-slate-300">
                    <option value="">Tutti</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>In attesa</option>
                    <option value="ai_validated" <?= ($filters['status'] ?? '') === 'ai_validated' ? 'selected' : '' ?>>Validato AI</option>
                    <option value="snippet_ready" <?= ($filters['status'] ?? '') === 'snippet_ready' ? 'selected' : '' ?>>Snippet pronto</option>
                    <option value="applied" <?= ($filters['status'] ?? '') === 'applied' ? 'selected' : '' ?>>Applicato</option>
                    <option value="dismissed" <?= ($filters['status'] ?? '') === 'dismissed' ? 'selected' : '' ?>>Ignorato</option>
                </select>
            </div>

            <!-- Reason -->
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Motivo</label>
                <select name="reason" class="w-full sm:w-36 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-700 dark:text-slate-300">
                    <option value="">Tutti</option>
                    <option value="hub_needs_outgoing" <?= ($filters['reason'] ?? '') === 'hub_needs_outgoing' ? 'selected' : '' ?>>Hub</option>
                    <option value="orphan_needs_inbound" <?= ($filters['reason'] ?? '') === 'orphan_needs_inbound' ? 'selected' : '' ?>>Orfano</option>
                    <option value="topical_relevance" <?= ($filters['reason'] ?? '') === 'topical_relevance' ? 'selected' : '' ?>>Topico</option>
                </select>
            </div>

            <!-- Min Score -->
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Score min.</label>
                <input type="number" name="min_score" min="0" max="100"
                       value="<?= e($filters['min_score'] ?? '') ?>"
                       placeholder="0"
                       class="w-full sm:w-24 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-700 dark:text-slate-300">
            </div>

            <!-- Search -->
            <div class="w-full sm:flex-1">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cerca</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search"
                           value="<?= e($filters['search'] ?? '') ?>"
                           placeholder="Cerca URL o keyword..."
                           class="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-700 dark:text-slate-300 placeholder-slate-400">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 rounded-lg hover:bg-cyan-700 transition">
                    Filtra
                </button>
                <?php if (array_filter($filters)): ?>
                <a href="<?= $baseUrl ?>"
                   class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Bulk Action Bar -->
    <?= \Core\View::partial('components/table-bulk-bar', [
        'actions' => [
            ['label' => 'Segna Applicati', 'action' => 'bulkAction(\'apply\')', 'color' => 'emerald'],
            ['label' => 'Ignora Selezionati', 'action' => 'bulkAction(\'dismiss\')', 'color' => 'red'],
        ]
    ]) ?>

    <!-- Table -->
    <?php if (empty($rows)): ?>
        <?= \Core\View::partial('components/table-empty-state', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
            'heading' => 'Nessun suggerimento trovato',
            'message' => ($stats['total'] ?? 0) === 0
                ? 'Genera i suggerimenti per scoprire le migliori opportunita di internal linking.'
                : 'Nessun suggerimento corrisponde ai filtri selezionati. Prova a modificare i criteri di ricerca.',
        ]) ?>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50">
                        <th class="w-10 text-center px-4 py-3">
                            <input type="checkbox"
                                   @change="toggleAll($event)"
                                   :checked="selectedIds.length === <?= count($rows) ?> && selectedIds.length > 0"
                                   class="rounded border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sorgente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destinazione</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Score</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Motivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ancore Suggerite</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($rows as $s): ?>
                    <?php
                        $anchors = json_decode($s['ai_suggested_anchors'] ?? '[]', true) ?: [];
                        $statusLabels = [
                            'pending' => 'In attesa',
                            'ai_validated' => 'Validato AI',
                            'snippet_ready' => 'Snippet pronto',
                            'applied' => 'Applicato',
                            'dismissed' => 'Ignorato',
                        ];
                        $statusColors = [
                            'pending' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                            'ai_validated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'snippet_ready' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300',
                            'applied' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'dismissed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                        ];
                        $reasonLabels = [
                            'hub_needs_outgoing' => 'Hub',
                            'orphan_needs_inbound' => 'Orfano',
                            'topical_relevance' => 'Topico',
                        ];
                        $reasonColors = [
                            'hub_needs_outgoing' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                            'orphan_needs_inbound' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                            'topical_relevance' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                        ];
                        $status = $s['status'] ?? 'pending';
                        $reason = $s['reason'] ?? '';
                        $totalScore = (int) ($s['total_score'] ?? 0);
                        $aiScore = $s['ai_relevance_score'] ?? null;
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" data-id="<?= $s['id'] ?>">
                        <!-- Checkbox -->
                        <td class="text-center px-4 py-3">
                            <input type="checkbox"
                                   value="<?= $s['id'] ?>"
                                   @change="toggleOne(<?= $s['id'] ?>, $event)"
                                   :checked="selectedIds.includes(<?= $s['id'] ?>)"
                                   class="suggestion-cb rounded border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500">
                        </td>

                        <!-- Sorgente -->
                        <td class="px-4 py-3 max-w-[200px]">
                            <a href="<?= e($s['source_url'] ?? '') ?>" target="_blank"
                               class="text-sm text-slate-700 dark:text-slate-300 hover:text-cyan-600 dark:hover:text-cyan-400 truncate block"
                               title="<?= e($s['source_url'] ?? '') ?>">
                                <?= e(strlen($s['source_url'] ?? '') > 40 ? '...' . substr($s['source_url'], -37) : ($s['source_url'] ?? '')) ?>
                            </a>
                            <?php if (!empty($s['source_keyword'])): ?>
                            <span class="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 max-w-full truncate"
                                  title="<?= e($s['source_keyword']) ?>">
                                <?= e(strlen($s['source_keyword']) > 25 ? substr($s['source_keyword'], 0, 25) . '...' : $s['source_keyword']) ?>
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Destinazione -->
                        <td class="px-4 py-3 max-w-[200px]">
                            <a href="<?= e($s['destination_url'] ?? '') ?>" target="_blank"
                               class="text-sm text-slate-700 dark:text-slate-300 hover:text-cyan-600 dark:hover:text-cyan-400 truncate block"
                               title="<?= e($s['destination_url'] ?? '') ?>">
                                <?= e(strlen($s['destination_url'] ?? '') > 40 ? '...' . substr($s['destination_url'], -37) : ($s['destination_url'] ?? '')) ?>
                            </a>
                            <?php if (!empty($s['destination_keyword'])): ?>
                            <span class="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 max-w-full truncate"
                                  title="<?= e($s['destination_keyword']) ?>">
                                <?= e(strlen($s['destination_keyword']) > 25 ? substr($s['destination_keyword'], 0, 25) . '...' : $s['destination_keyword']) ?>
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Score -->
                        <td class="text-center px-4 py-3">
                            <?php
                                if ($totalScore >= 70) $scoreClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                                elseif ($totalScore >= 40) $scoreClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                else $scoreClass = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                            ?>
                            <span class="inline-flex items-center justify-center w-9 h-7 rounded-lg text-xs font-bold <?= $scoreClass ?>">
                                <?= $totalScore ?>
                            </span>
                            <?php if ($aiScore !== null): ?>
                            <span class="block text-[10px] text-slate-400 mt-0.5" title="Score AI">
                                AI: <?= $aiScore ?>/10
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Motivo -->
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $reasonColors[$reason] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' ?>">
                                <?= e($reasonLabels[$reason] ?? ucfirst($reason)) ?>
                            </span>
                        </td>

                        <!-- Ancore Suggerite -->
                        <td class="px-4 py-3">
                            <?php if (!empty($anchors)): ?>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach (array_slice($anchors, 0, 3) as $anchor): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300 max-w-[140px] truncate"
                                      title="<?= e($anchor) ?>">
                                    <?= e(strlen($anchor) > 20 ? substr($anchor, 0, 20) . '...' : $anchor) ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if (count($anchors) > 3): ?>
                                <span class="text-xs text-slate-400">+<?= count($anchors) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-xs text-slate-400">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Stato -->
                        <td class="text-center px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                <?= e($statusLabels[$status] ?? $status) ?>
                            </span>
                        </td>

                        <!-- Azioni -->
                        <td class="px-4 py-3">
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.outside="open = false"
                                        class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition
                                     class="absolute right-0 mt-1 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg z-20 py-1">

                                    <?php if ($status !== 'snippet_ready' && $status !== 'applied'): ?>
                                    <!-- Genera Snippet -->
                                    <button @click="open = false; generateSnippet(<?= $s['id'] ?>)"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                        Genera Snippet
                                    </button>
                                    <?php endif; ?>

                                    <?php if (!empty($s['ai_snippet_html'])): ?>
                                    <!-- Mostra Snippet -->
                                    <button @click="open = false; showSnippet(<?= $s['id'] ?>, <?= e(json_encode($s)) ?>)"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Mostra Snippet
                                    </button>

                                    <!-- Copia Snippet -->
                                    <button @click="open = false; copySnippet(<?= e(json_encode($s['ai_snippet_html'])) ?>)"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                        Copia Snippet
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($status !== 'applied'): ?>
                                    <!-- Segna Applicato -->
                                    <button @click="open = false; apply(<?= $s['id'] ?>, 'manual_copy')"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Segna Applicato
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($hasConnector && !empty($s['ai_snippet_html']) && $status !== 'applied'): ?>
                                    <!-- Applica CMS -->
                                    <button @click="open = false; apply(<?= $s['id'] ?>, 'connector')"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        Applica via CMS
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($status !== 'dismissed'): ?>
                                    <div class="border-t border-slate-200 dark:border-slate-700 my-1"></div>
                                    <!-- Ignora -->
                                    <button @click="open = false; dismiss(<?= $s['id'] ?>)"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Ignora
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?= \Core\View::partial('components/table-pagination', [
            'pagination' => $pagination,
            'baseUrl' => $baseUrl,
            'filters' => $filters,
        ]) ?>
    </div>
    <?php endif; ?>

    <!-- Snippet Modal -->
    <div x-show="snippetModal.open" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="snippetModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="snippetModal.open = false"></div>

            <!-- Modal -->
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[85vh] overflow-hidden" x-transition>
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Anteprima Snippet</h3>
                    <button @click="snippetModal.open = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto max-h-[calc(85vh-140px)] space-y-6">
                    <!-- Info -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">Ancora usata:</span>
                            <span class="ml-1 font-medium text-slate-900 dark:text-white" x-text="snippetModal.suggestion?.ai_anchor_used || '-'"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">Metodo inserimento:</span>
                            <span class="ml-1 font-medium text-slate-900 dark:text-white" x-text="snippetModal.suggestion?.ai_insertion_method || '-'"></span>
                        </div>
                    </div>

                    <!-- Original paragraph -->
                    <template x-if="snippetModal.suggestion?.ai_original_paragraph">
                        <div>
                            <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Paragrafo Originale</h4>
                            <div class="p-4 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg text-sm text-slate-700 dark:text-slate-300 prose dark:prose-invert prose-sm max-w-none"
                                 x-html="snippetModal.suggestion?.ai_original_paragraph"></div>
                        </div>
                    </template>

                    <!-- Snippet HTML -->
                    <div>
                        <h4 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Paragrafo con Link Inserito</h4>
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-slate-700 dark:text-slate-300 prose dark:prose-invert prose-sm max-w-none"
                             x-html="snippetModal.suggestion?.ai_snippet_html"></div>
                    </div>

                    <!-- Diversity note -->
                    <template x-if="snippetModal.suggestion?.ai_anchor_diversity_note">
                        <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm text-amber-700 dark:text-amber-300" x-text="snippetModal.suggestion?.ai_anchor_diversity_note"></p>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <div class="text-sm text-slate-500 dark:text-slate-400" x-show="snippetModal.copied" x-transition>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">Copiato negli appunti</span>
                    </div>
                    <div class="flex items-center gap-3 ml-auto">
                        <button @click="copySnippet(snippetModal.suggestion?.ai_snippet_html)"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            Copia HTML
                        </button>
                        <button @click="snippetModal.open = false"
                                class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 rounded-lg hover:bg-cyan-700 transition">
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function suggestionPage() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                      document.querySelector('input[name="_csrf_token"]')?.value;
    const projectId = <?= (int) $project['id'] ?>;
    const baseApiUrl = '<?= url("/internal-links/project/{$project['id']}/suggestions") ?>';

    return {
        generating: false,
        generateProgress: 'Generazione in corso...',
        generateResult: null,
        selectedIds: [],
        snippetModal: { open: false, suggestion: null, copied: false },
        snippetLoading: {},

        // Generate suggestions
        async generate() {
            if (this.generating) return;
            this.generating = true;
            this.generateResult = null;
            this.generateProgress = 'Analisi struttura link...';

            try {
                const resp = await fetch(baseApiUrl + '/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ _csrf_token: csrfToken })
                });

                if (!resp.ok) {
                    this.generateResult = { success: false, message: 'Errore di connessione al server (' + resp.status + ')' };
                    this.generating = false;
                    return;
                }

                const data = await resp.json();

                if (data.success) {
                    const parts = [];
                    if (data.total_candidates > 0) parts.push(data.total_candidates + ' candidati trovati');
                    if (data.plan_a > 0) parts.push(data.plan_a + ' hub/orfani');
                    if (data.plan_b > 0) parts.push(data.plan_b + ' topici');
                    if (data.ai_validated > 0) parts.push(data.ai_validated + ' validati AI');

                    this.generateResult = {
                        success: true,
                        message: 'Generazione completata: ' + (parts.length ? parts.join(', ') : 'nessun nuovo suggerimento')
                    };

                    // Reload after short delay to show updated data
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.generateResult = { success: false, message: data.error || 'Errore durante la generazione' };
                }
            } catch (err) {
                this.generateResult = { success: false, message: 'Errore di rete: ' + err.message };
            }

            this.generating = false;
        },

        // Generate snippet for a single suggestion
        async generateSnippet(id) {
            if (this.snippetLoading[id]) return;
            this.snippetLoading[id] = true;

            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) row.classList.add('opacity-50');

            try {
                const resp = await fetch(baseApiUrl + '/' + id + '/snippet', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ _csrf_token: csrfToken })
                });

                if (!resp.ok) {
                    if (window.ainstein?.alert) window.ainstein.alert('Errore di connessione (' + resp.status + ')', 'error');
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    if (window.ainstein?.alert) window.ainstein.alert('Snippet generato con successo', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    if (window.ainstein?.alert) window.ainstein.alert(data.error || 'Errore nella generazione dello snippet', 'error');
                }
            } catch (err) {
                if (window.ainstein?.alert) window.ainstein.alert('Errore di rete: ' + err.message, 'error');
            } finally {
                this.snippetLoading[id] = false;
                if (row) row.classList.remove('opacity-50');
            }
        },

        // Show snippet modal
        showSnippet(id, suggestion) {
            this.snippetModal = {
                open: true,
                suggestion: suggestion,
                copied: false
            };
        },

        // Apply suggestion
        async apply(id, method) {
            try {
                const resp = await fetch(baseApiUrl + '/' + id + '/apply', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ _csrf_token: csrfToken, method: method })
                });

                if (!resp.ok) {
                    if (window.ainstein?.alert) window.ainstein.alert('Errore di connessione', 'error');
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    if (window.ainstein?.alert) window.ainstein.alert('Suggerimento segnato come applicato', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (window.ainstein?.alert) window.ainstein.alert(data.error || 'Errore', 'error');
                }
            } catch (err) {
                if (window.ainstein?.alert) window.ainstein.alert('Errore di rete', 'error');
            }
        },

        // Dismiss suggestion
        async dismiss(id) {
            try {
                const resp = await fetch(baseApiUrl + '/' + id + '/dismiss', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ _csrf_token: csrfToken })
                });

                if (!resp.ok) {
                    if (window.ainstein?.alert) window.ainstein.alert('Errore di connessione', 'error');
                    return;
                }

                const data = await resp.json();
                if (data.success) {
                    if (window.ainstein?.alert) window.ainstein.alert('Suggerimento ignorato', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (window.ainstein?.alert) window.ainstein.alert(data.error || 'Errore', 'error');
                }
            } catch (err) {
                if (window.ainstein?.alert) window.ainstein.alert('Errore di rete', 'error');
            }
        },

        // Bulk action
        bulkAction(action) {
            if (this.selectedIds.length === 0) {
                if (window.ainstein?.alert) window.ainstein.alert('Seleziona almeno un suggerimento', 'warning');
                return;
            }

            const label = action === 'apply' ? 'applicare' : 'ignorare';
            const ids = [...this.selectedIds];

            const doAction = async () => {
                try {
                    const body = new URLSearchParams({ _csrf_token: csrfToken, action: action });
                    ids.forEach(id => body.append('ids[]', id));

                    const resp = await fetch(baseApiUrl + '/bulk', {
                        method: 'POST',
                        body: body
                    });

                    if (!resp.ok) {
                        if (window.ainstein?.alert) window.ainstein.alert('Errore di connessione', 'error');
                        return;
                    }

                    const data = await resp.json();
                    if (data.success) {
                        if (window.ainstein?.alert) window.ainstein.alert(`${data.affected} suggerimenti aggiornati`, 'success');
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        if (window.ainstein?.alert) window.ainstein.alert(data.error || 'Errore', 'error');
                    }
                } catch (err) {
                    if (window.ainstein?.alert) window.ainstein.alert('Errore di rete', 'error');
                }
            };

            if (window.ainstein?.confirm) {
                window.ainstein.confirm(
                    `Sei sicuro di voler ${label} ${ids.length} suggerimenti?`,
                    action === 'dismiss' ? { destructive: true } : {}
                ).then(() => doAction());
            } else {
                if (confirm(`${label} ${ids.length} suggerimenti?`)) doAction();
            }
        },

        // Copy snippet HTML to clipboard
        copySnippet(html) {
            if (!html) return;
            navigator.clipboard.writeText(html).then(() => {
                this.snippetModal.copied = true;
                if (window.ainstein?.alert) window.ainstein.alert('Snippet HTML copiato negli appunti', 'success');
                setTimeout(() => { this.snippetModal.copied = false; }, 2500);
            }).catch(() => {
                // Fallback
                const ta = document.createElement('textarea');
                ta.value = html;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                this.snippetModal.copied = true;
                if (window.ainstein?.alert) window.ainstein.alert('Snippet HTML copiato negli appunti', 'success');
                setTimeout(() => { this.snippetModal.copied = false; }, 2500);
            });
        },

        // Toggle all checkboxes
        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = <?= json_encode(array_map(fn($r) => (int) $r['id'], $rows)) ?>;
            } else {
                this.selectedIds = [];
            }
        },

        // Toggle single checkbox
        toggleOne(id, event) {
            if (event.target.checked) {
                if (!this.selectedIds.includes(id)) this.selectedIds.push(id);
            } else {
                this.selectedIds = this.selectedIds.filter(i => i !== id);
            }
        }
    };
}
</script>
