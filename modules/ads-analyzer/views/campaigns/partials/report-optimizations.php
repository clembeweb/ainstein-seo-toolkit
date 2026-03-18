<?php
/**
 * Batch Optimizations Partial — Before/After Preview
 *
 * Variables from parent view:
 * - $allOptimizations: flat array keyed by opt_{cIdx}_{agIdx}_{oIdx}, each with:
 *     type, priority, target_ad_index, reason, scope, campaign_name, ad_group_name, context
 * - $access_role: user role (owner|editor|viewer)
 * - $generateUrl: URL for generating fixes
 * - $applyUrl: URL for applying fixes
 *
 * Parent Alpine component (evaluationReport) provides:
 * - generators, selectedOptimizations, campaignsData
 * - generateFix(), generateAll(), applySelected(), exportCsv(), openApplyModal(), copyResult()
 * - generatingAll, genAllCurrent, genAllTotal
 */

$canEdit = ($access_role ?? 'owner') !== 'viewer';

$priorityClasses = [
    'high'   => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'low'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
];
$priorityLabels = [
    'high'   => 'Alta',
    'medium' => 'Media',
    'low'    => 'Bassa',
];

$typeLabels = [
    'rewrite_ad'       => 'Annunci',
    'rewrite_ads'      => 'Annunci',
    'add_extensions'    => 'Estensioni',
    'add_negatives'     => 'Keyword Negative',
    'replace_asset'     => 'Asset PMax',
    'add_asset'         => 'Asset PMax',
    'landing_suggestion'=> 'Landing Page',
    'remove_duplicates' => 'Duplicati',
];

$typeIcons = [
    'rewrite_ad'       => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    'rewrite_ads'      => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    'add_extensions'   => 'M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z',
    'add_negatives'    => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
    'replace_asset'    => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    'add_asset'        => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
    'landing_suggestion'=> 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064',
    'remove_duplicates'=> 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
];

// Manual-only types (no AI generation possible)
$manualOnlyTypes = ['landing_suggestion'];

// PMax types (no auto-apply via API)
$pmaxTypes = ['replace_asset', 'add_asset'];
?>

<div class="space-y-0">

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- BATCH TOOLBAR (sticky)                                                -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

    <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between sticky top-0 z-10">
        <div class="flex items-center gap-4">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <!-- Heroicon: bolt -->
                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                Ottimizzazioni
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400"
                  x-text="`${Object.keys(allOptimizations).length} totali &middot; ${generatedCount} generate &middot; ${selectedCount} selezionate`">
            </span>
        </div>

        <?php if ($canEdit): ?>
        <div class="flex items-center gap-2">
            <!-- Seleziona tutte le generate -->
            <label class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 cursor-pointer"
                   x-show="generatedCount > 0">
                <input type="checkbox" @change="selectAll($event.target.checked)"
                       class="rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
                <span>Tutte</span>
            </label>

            <!-- Genera Tutte -->
            <button @click="generateAll()"
                    :disabled="generatingAll || pendingGenCount === 0"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <template x-if="!generatingAll">
                    <span class="flex items-center gap-1.5">
                        <!-- Heroicon: sparkles -->
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                        </svg>
                        Genera Tutte (<span x-text="pendingGenCount"></span>)
                    </span>
                </template>
                <template x-if="generatingAll">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="`${genAllCurrent}/${genAllTotal}...`"></span>
                    </span>
                </template>
            </button>

            <!-- Applica Selezionate -->
            <button @click="openBatchApplyModal()"
                    :disabled="selectedCount === 0"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <!-- Heroicon: cloud-arrow-up -->
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/>
                </svg>
                Applica Selezionate (<span x-text="selectedCount"></span>)
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- OPTIMIZATION CARDS                                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="divide-y divide-slate-200 dark:divide-slate-700/50">

        <?php if (empty($allOptimizations)): ?>
        <div class="p-8 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Nessuna ottimizzazione suggerita</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Le campagne sono gia ben ottimizzate.</p>
        </div>
        <?php else: ?>

        <?php foreach ($allOptimizations as $key => $opt):
            $priority = $opt['priority'] ?? 'medium';
            $type = $opt['type'] ?? 'rewrite_ads';
            $pClass = $priorityClasses[$priority] ?? $priorityClasses['medium'];
            $pLabel = $priorityLabels[$priority] ?? 'Media';
            $tLabel = $typeLabels[$type] ?? ucfirst($type);
            $tIcon  = $typeIcons[$type] ?? $typeIcons['rewrite_ads'];
            $isManualOnly = in_array($type, $manualOnlyTypes);
            $isPmaxType   = in_array($type, $pmaxTypes);
            $safeKey = e($key);
            $safeOpt = e(json_encode($opt, JSON_UNESCAPED_UNICODE));
        ?>

        <!-- Single Optimization Card -->
        <div class="p-5 transition-colors"
             :class="{
                'bg-emerald-50/50 dark:bg-emerald-900/5': generators['<?= $safeKey ?>']?.applied,
                'hover:bg-slate-50 dark:hover:bg-slate-700/20': !generators['<?= $safeKey ?>']?.applied
             }">
            <div class="flex items-start gap-3">

                <!-- ── Checkbox / Applied icon ── -->
                <template x-if="generators['<?= $safeKey ?>']?.applied">
                    <div class="mt-0.5 w-5 h-5 rounded bg-emerald-600 flex items-center justify-center flex-shrink-0">
                        <!-- Heroicon: check -->
                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                </template>
                <template x-if="!generators['<?= $safeKey ?>']?.applied">
                    <?php if ($canEdit && !$isManualOnly): ?>
                    <input type="checkbox"
                           x-model="selectedOptimizations['<?= $safeKey ?>']"
                           :disabled="!generators['<?= $safeKey ?>']?.result"
                           class="mt-1 rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500 disabled:opacity-30">
                    <?php else: ?>
                    <div class="mt-0.5 w-5 h-5 flex-shrink-0"></div>
                    <?php endif; ?>
                </template>

                <!-- ── Card content ── -->
                <div class="flex-1 min-w-0">

                    <!-- Badges row -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Priority badge -->
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $pClass ?>">
                            <?= e($pLabel) ?>
                        </span>

                        <!-- Type badge -->
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $tIcon ?>"/>
                            </svg>
                            <?= e($tLabel) ?>
                        </span>

                        <!-- Applied badge -->
                        <template x-if="generators['<?= $safeKey ?>']?.applied">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                Applicato
                            </span>
                        </template>

                        <?php if ($isManualOnly): ?>
                        <span class="inline-flex items-center px-2 py-0.5 text-[10px] text-slate-500 bg-slate-100 dark:bg-slate-800 dark:text-slate-400 rounded">
                            Azione manuale
                        </span>
                        <?php endif; ?>

                        <!-- Scope: campaign > ad group -->
                        <span class="text-xs text-slate-400 dark:text-slate-500 truncate max-w-[300px]"
                              title="<?= e(($opt['campaign_name'] ?? '') . ($opt['ad_group_name'] ? ' > ' . $opt['ad_group_name'] : '')) ?>">
                            <?= e($opt['campaign_name'] ?? '') ?><?php if (!empty($opt['ad_group_name'])): ?> <span class="mx-0.5">&rsaquo;</span> <?= e($opt['ad_group_name']) ?><?php endif; ?><?php if (isset($opt['target_ad_index']) && $opt['target_ad_index'] !== null): ?> <span class="mx-0.5">&rsaquo;</span> #<?= (int)$opt['target_ad_index'] + 1 ?><?php endif; ?>
                        </span>
                    </div>

                    <!-- Reason -->
                    <p class="text-sm text-slate-600 dark:text-slate-300 mt-1.5 leading-relaxed">
                        <?= e($opt['reason'] ?? '') ?>
                    </p>

                    <!-- ── STATE: APPLIED ── -->
                    <template x-if="generators['<?= $safeKey ?>']?.applied">
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 flex items-center gap-1.5">
                            <!-- Heroicon: check-circle -->
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Ottimizzazione applicata su Google Ads
                        </p>
                    </template>

                    <!-- ── STATE: NOT APPLIED ── -->
                    <template x-if="!generators['<?= $safeKey ?>']?.applied">
                        <div>
                            <!-- Loading spinner -->
                            <div x-show="generators['<?= $safeKey ?>']?.loading" class="mt-3 flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Generazione in corso...
                            </div>

                            <!-- Error -->
                            <div x-show="generators['<?= $safeKey ?>']?.error" x-cloak
                                 class="mt-2 text-xs text-red-600 dark:text-red-400"
                                 x-text="generators['<?= $safeKey ?>']?.error">
                            </div>

                            <!-- Generate button (when no result yet and not manual-only) -->
                            <?php if ($canEdit && !$isManualOnly): ?>
                            <div x-show="!generators['<?= $safeKey ?>']?.result && !generators['<?= $safeKey ?>']?.loading" class="mt-2">
                                <button @click="generateFix('<?= e($type) ?>', <?= $safeOpt ?>.context || <?= $safeOpt ?>, '<?= $safeKey ?>')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                                    <!-- Heroicon: sparkles -->
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                                    </svg>
                                    Genera Ottimizzazione (1 credito)
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- ── STATE: GENERATED — Before/After Preview ── -->
                            <template x-if="generators['<?= $safeKey ?>']?.result && !generators['<?= $safeKey ?>']?.loading">
                                <div class="mt-3 space-y-3">

                                    <!-- Before → After preview box -->
                                    <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-2.5 flex items-center gap-1.5">
                                            <!-- Heroicon: arrows-right-left -->
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                                            </svg>
                                            Preview Before / After
                                        </div>

                                        <div class="space-y-2 text-xs">

                                            <!-- ══ Rewrite Ads: headline/description diffs ══ -->
                                            <template x-if="['rewrite_ads','rewrite_ad','copy'].includes(generators['<?= $safeKey ?>']?.type)">
                                                <div>
                                                    <template x-if="getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)">
                                                        <div class="space-y-1.5">
                                                            <!-- Headlines -->
                                                            <template x-for="(h, i) in (getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.after?.headlines || [])" :key="'h'+i">
                                                                <div class="grid grid-cols-[60px_1fr_24px_1fr] gap-x-2 items-baseline">
                                                                    <span class="text-slate-400 font-mono" x-text="'H' + (i+1) + ':'"></span>
                                                                    <span class="text-red-500 dark:text-red-400 line-through"
                                                                          x-text="getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.before?.headlines?.[i] || '—'"></span>
                                                                    <!-- Heroicon: arrow-right (inline) -->
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 mx-auto flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                                                    </svg>
                                                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium" x-text="h"></span>
                                                                </div>
                                                            </template>
                                                            <!-- Descriptions -->
                                                            <template x-for="(d, i) in (getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.after?.descriptions || [])" :key="'d'+i">
                                                                <div class="grid grid-cols-[60px_1fr_24px_1fr] gap-x-2 items-baseline">
                                                                    <span class="text-slate-400 font-mono" x-text="'D' + (i+1) + ':'"></span>
                                                                    <span class="text-red-500 dark:text-red-400 line-through"
                                                                          x-text="getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.before?.descriptions?.[i] || '—'"></span>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 mx-auto flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                                                    </svg>
                                                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium" x-text="d"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- ══ Extensions: new items (green) ══ -->
                                            <template x-if="['add_extensions','extensions'].includes(generators['<?= $safeKey ?>']?.type)">
                                                <div class="space-y-1">
                                                    <template x-for="(item, i) in (getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.items || [])" :key="'ext'+i">
                                                        <div class="flex items-center gap-2">
                                                            <!-- Heroicon: plus-circle -->
                                                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span class="text-emerald-600 dark:text-emerald-400" x-text="typeof item === 'string' ? item : (item.text || item.link_text || item.description || JSON.stringify(item))"></span>
                                                        </div>
                                                    </template>
                                                    <!-- Fallback: show raw result text -->
                                                    <template x-if="!(getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.items || []).length">
                                                        <pre class="text-emerald-600 dark:text-emerald-400 whitespace-pre-wrap font-sans leading-relaxed" x-text="generators['<?= $safeKey ?>']?.result"></pre>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- ══ PMax Assets: before → after or new ══ -->
                                            <template x-if="['replace_asset','add_asset'].includes(generators['<?= $safeKey ?>']?.type)">
                                                <div class="space-y-1.5">
                                                    <!-- Single replacement -->
                                                    <template x-if="getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.before">
                                                        <div class="flex items-baseline gap-2 flex-wrap">
                                                            <span class="text-red-500 dark:text-red-400 line-through" x-text="getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.before"></span>
                                                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                                            </svg>
                                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium" x-text="getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.after"></span>
                                                        </div>
                                                    </template>
                                                    <!-- Multiple new assets -->
                                                    <template x-for="(asset, i) in (getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.items || [])" :key="'asset'+i">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span class="text-emerald-600 dark:text-emerald-400" x-text="typeof asset === 'string' ? asset : (asset.text || asset.headline || JSON.stringify(asset))"></span>
                                                        </div>
                                                    </template>
                                                    <!-- Fallback -->
                                                    <template x-if="!getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.before && !(getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.items || []).length">
                                                        <pre class="text-emerald-600 dark:text-emerald-400 whitespace-pre-wrap font-sans leading-relaxed" x-text="generators['<?= $safeKey ?>']?.result"></pre>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- ══ Negatives: keyword list ══ -->
                                            <template x-if="['add_negatives','keywords'].includes(generators['<?= $safeKey ?>']?.type)">
                                                <div class="space-y-1">
                                                    <template x-for="(kw, i) in (getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.keywords || [])" :key="'neg'+i">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                            </svg>
                                                            <span class="text-slate-700 dark:text-slate-300" x-text="typeof kw === 'string' ? kw : (kw.text || kw.keyword || JSON.stringify(kw))"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="!(getBeforeAfterData('<?= $safeKey ?>', <?= $safeOpt ?>)?.keywords || []).length">
                                                        <pre class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-sans leading-relaxed" x-text="generators['<?= $safeKey ?>']?.result"></pre>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- ══ Fallback: raw text for unknown types ══ -->
                                            <template x-if="!['rewrite_ads','rewrite_ad','copy','add_extensions','extensions','replace_asset','add_asset','add_negatives','keywords'].includes(generators['<?= $safeKey ?>']?.type)">
                                                <pre class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-sans leading-relaxed max-h-48 overflow-y-auto" x-text="generators['<?= $safeKey ?>']?.result"></pre>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Action buttons for generated content -->
                                    <?php if ($canEdit): ?>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <!-- Rigenera -->
                                        <button @click="generateFix('<?= e($type) ?>', <?= $safeOpt ?>.context || <?= $safeOpt ?>, '<?= $safeKey ?>')"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                                            <!-- Heroicon: arrow-path -->
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.992 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182"/>
                                            </svg>
                                            Rigenera
                                        </button>

                                        <!-- Copia -->
                                        <button @click="copyResult('<?= $safeKey ?>')"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/60 transition-colors">
                                            <!-- Heroicon: clipboard-document -->
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z"/>
                                            </svg>
                                            <span x-text="generators['<?= $safeKey ?>']?.copied ? 'Copiato!' : 'Copia'"></span>
                                        </button>

                                        <!-- CSV Export -->
                                        <button @click="exportCsv('<?= $safeKey ?>')"
                                                x-show="generators['<?= $safeKey ?>']?.data && ['rewrite_ads','rewrite_ad','copy','add_extensions','extensions','add_negatives','keywords','remove_duplicates'].includes(generators['<?= $safeKey ?>']?.type)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-rose-700 bg-rose-100 hover:bg-rose-200 dark:text-rose-300 dark:bg-rose-900/40 dark:hover:bg-rose-900/60 transition-colors">
                                            <!-- Heroicon: document-arrow-down -->
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                            CSV Ads Editor
                                        </button>

                                        <!-- Applica su Google Ads (solo Search, non PMax) -->
                                        <?php if (!$isPmaxType): ?>
                                        <button x-show="generators['<?= $safeKey ?>']?.data && ['rewrite_ads','rewrite_ad','copy','add_extensions','extensions','add_negatives','keywords','remove_duplicates'].includes(generators['<?= $safeKey ?>']?.type)"
                                                @click="openApplyModal('<?= $safeKey ?>')"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                                            <!-- Heroicon: cloud-arrow-up -->
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/>
                                            </svg>
                                            Applica su Google Ads
                                        </button>
                                        <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs text-slate-500 dark:text-slate-400">
                                            <!-- Heroicon: hand-raised -->
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 10-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 013.15 0v1.5m-3.15 0l.075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 013.15 0V15M6.9 7.575a1.575 1.575 0 10-3.15 0v8.175a6.75 6.75 0 006.75 6.75h2.018a5.25 5.25 0 003.712-1.538l1.732-1.732a5.25 5.25 0 001.538-3.712l.003-2.024a.668.668 0 01.198-.471 1.575 1.575 0 10-2.228-2.228 3.818 3.818 0 00-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0116.35 15"/>
                                            </svg>
                                            Applicazione manuale per PMax
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- BATCH APPLY CONFIRMATION MODAL                                        -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<?php if ($canEdit): ?>
<div x-show="applyModalBatch.open" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="applyModalBatch.open = false"></div>

    <!-- Modal -->
    <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full p-6 z-10"
         @click.stop
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        <!-- Not done yet -->
        <template x-if="!applyModalBatch.done">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center flex-shrink-0">
                        <!-- Heroicon: exclamation-triangle -->
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Applica Ottimizzazioni su Google Ads</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Stai per applicare <span class="font-medium" x-text="applyModalBatch.count"></span> ottimizzazioni
                        </p>
                    </div>
                </div>

                <ul class="space-y-1.5 mb-4 text-sm text-slate-600 dark:text-slate-300">
                    <li x-show="applyModalBatch.newAds > 0" class="flex items-center gap-2">
                        <!-- Heroicon: document-plus -->
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span x-text="applyModalBatch.newAds"></span> nuovi annunci RSA (creati IN PAUSA)
                    </li>
                    <li x-show="applyModalBatch.newExtensions > 0" class="flex items-center gap-2">
                        <!-- Heroicon: squares-plus -->
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        <span x-text="applyModalBatch.newExtensions"></span> nuove estensioni (attive)
                    </li>
                    <li x-show="applyModalBatch.newNegatives > 0" class="flex items-center gap-2">
                        <!-- Heroicon: no-symbol -->
                        <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <span x-text="applyModalBatch.newNegatives"></span> keyword negative
                    </li>
                </ul>

                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    I nuovi annunci verranno creati in stato PAUSED. Potrai attivarli manualmente da Google Ads quando sarai pronto.
                </p>

                <div class="flex gap-3">
                    <button @click="applyModalBatch.open = false"
                            :disabled="applyModalBatch.applying"
                            class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 disabled:opacity-50 transition-colors">
                        Annulla
                    </button>
                    <button @click="executeBatchApply()"
                            :disabled="applyModalBatch.applying"
                            class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                        <template x-if="!applyModalBatch.applying">
                            <span class="flex items-center gap-1.5">
                                <!-- Heroicon: check -->
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                                Conferma e Applica
                            </span>
                        </template>
                        <template x-if="applyModalBatch.applying">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Applicazione in corso...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </template>

        <!-- Done / Error -->
        <template x-if="applyModalBatch.done">
            <div class="text-center">
                <!-- Success -->
                <template x-if="!applyModalBatch.error">
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-3">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="applyModalBatch.resultMessage"></p>
                    </div>
                </template>
                <!-- Error -->
                <template x-if="applyModalBatch.error">
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center mb-3">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-red-600 dark:text-red-400" x-text="applyModalBatch.error"></p>
                    </div>
                </template>

                <button @click="applyModalBatch = { open: false, count: 0, newAds: 0, newExtensions: 0, newNegatives: 0, applying: false, done: false, error: null, resultMessage: '' }"
                        class="mt-4 px-4 py-2 rounded-lg text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                    Chiudi
                </button>
            </div>
        </template>
    </div>
</div>
<?php endif; ?>

</div>
